<?php

/** Agenda compartida por reservas, reprogramaciones y administración. */
require_once __DIR__.'/../services/agenda_integral.php';
const ESTADOS_CITA = ['pendiente' => 'Pendiente','confirmado' => 'Confirmada','atendido' => 'Atendida','cancelado' => 'Cancelada','rechazado' => 'Rechazada','no_asistio' => 'No asistió'];
function fecha_admitida($fecha)
{
    $f = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$fecha);
    return $f && $f->format('Y-m-d') === $fecha && $fecha >= date('Y-m-d') && $fecha <= date('Y-m-d', strtotime('+'.(int)reglas_agenda()['dias_maximos'].' days'));
}
function codigo_cita($id)
{
    return 'RC-'.str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}
/** Datos de navegación; la disponibilidad y los permisos se vuelven a validar al guardar. */
function contexto_horario($entrada)
{
    $contexto = [];
    foreach (['esp', 'medico'] as $campo) {
        $id = filter_var($entrada[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id) {
            $contexto[$campo] = (int)$id;
        }
    }
    $fecha = $entrada['fecha'] ?? '';
    if (is_string($fecha) && fecha_admitida($fecha)) {
        $contexto['fecha'] = $fecha;
    }
    $hora = $entrada['hora'] ?? '';
    if (is_string($hora) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $hora)) {
        $contexto['hora'] = $hora;
    }
    return $contexto;
}
function especialidades_activas()
{
    global $conexion;
    return $conexion->query('SELECT * FROM especialidades WHERE activo=1 ORDER BY nombre')->fetchAll();
}
function medico_agenda($id, $bloquear = false)
{
    global $conexion;
    $s = $conexion->prepare('SELECT m.*,e.nombre especialidad,e.activo especialidad_activa FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id WHERE m.id=?'.($bloquear ? ' FOR UPDATE' : ''));
    $s->execute([$id]);
    return $s->fetch();
}
/** Genera los mismos intervalos para consultar disponibilidad y dibujar la agenda. */
function espacios_de_turnos($turnos, $fecha)
{
    $espacios = [];
    foreach ($turnos as $turno) {
        $duracion = (int)$turno['duracion'];
        if ($duracion <= 0) {
            continue;
        }
        $fin = strtotime($fecha.' '.$turno['fin']);
        for ($t = strtotime($fecha.' '.$turno['inicio']); $t + $duracion * 60 <= $fin; $t += $duracion * 60) {
            $espacios[date('H:i', $t)] = ['hora' => date('H:i', $t), 'duracion' => $duracion,
                'fin' => date('H:i', $t + $duracion * 60), 'desde' => $t, 'hasta' => $t + $duracion * 60];
        }
    }
    ksort($espacios);
    return array_values($espacios);
}
function horarios_disponibles($medico, $fecha, $paciente, $excluir = 0)
{
    return disponibilidad_reserva($medico, $fecha, $paciente, $excluir)['horas'];
}

/** Una cita por especialidad y fecha. Las cancelaciones y los rechazos liberan el cupo. */
function disponibilidad_reserva($medico, $fecha, $paciente, $excluir = 0, $especialidad = 0)
{
    global $conexion;
    if (!fecha_admitida($fecha)) {
        throw new DomainException('Elige una fecha desde hoy hasta los próximos '.reglas_agenda()['dias_maximos'].' días.');
    }
    $m = $medico ? medico_agenda($medico) : null;
    if ($medico && (!$m || !$m['activo'] || !$m['especialidad_activa'])) {
        throw new DomainException('El profesional no está disponible.');
    }
    $nombreEspecialidad = null;
    if ($m) {
        if ($especialidad && (int)$m['especialidad_id'] !== (int)$especialidad) {
            throw new DomainException('El médico no corresponde a la especialidad seleccionada.');
        }
        $especialidad = $m['especialidad_id'];
        $nombreEspecialidad = $m['especialidad'];
    } elseif ($especialidad) {
        $q = $conexion->prepare('SELECT nombre FROM especialidades WHERE id=? AND activo=1');
        $q->execute([$especialidad]);
        $nombreEspecialidad = $q->fetchColumn();
        if (!$nombreEspecialidad) {
            throw new DomainException('La especialidad no está disponible.');
        }
    }
    $resultado = ['cita_del_dia' => null, 'horas' => []];
    if ($paciente && $especialidad) {
        // En un guardado el paciente ya está bloqueado. Una lectura actual también ve la reserva
        // que acaba de confirmar otra transacción, incluso bajo REPEATABLE READ.
        $sql = "SELECT id, fecha_cita AS fecha, TIME_FORMAT(hora_cita, '%H:%i') AS hora, especialidad, estado
                FROM citas WHERE paciente_id=? AND fecha_cita=? AND id<>?
                AND estado NOT IN ('cancelado','rechazado')
                AND (especialidad_id=? OR (especialidad_id IS NULL AND especialidad=?) OR medico_id=?)
                ORDER BY hora_cita,id LIMIT 1";
        $q = $conexion->prepare($sql.($conexion->inTransaction() ? ' FOR UPDATE' : ''));
        $q->execute([$paciente, $fecha, $excluir, $especialidad, $nombreEspecialidad, $medico ?: null]);
        $cita = $q->fetch(PDO::FETCH_ASSOC);
        if ($cita) {
            $cita['id'] = (int)$cita['id'];
            $resultado['cita_del_dia'] = $cita;
            $resultado['mensaje'] = 'El paciente ya tiene una cita de '.$cita['especialidad'].' a las '.$cita['hora'].' ese día ('.ESTADOS_CITA[$cita['estado']].'). Elige otra fecha para una nueva cita en esa especialidad.';
            return $resultado;
        }
    }
    if (!$medico) {
        return $resultado;
    }
    $dia = (int)(new DateTimeImmutable($fecha))->format('N');
    $turnos = array_values(array_filter(turnos_vigentes($medico,$fecha), fn($t)=>(int)$t['dia']===$dia));
    $s = $conexion->prepare("SELECT hora_cita,duracion FROM citas WHERE fecha_cita=? AND (medico_id=? OR paciente_id=?) AND id<>? AND estado NOT IN ('cancelado','rechazado')".($conexion->inTransaction()?' FOR UPDATE':''));
    $s->execute([$fecha,$medico,$paciente,$excluir]);
    $ocupadas = $s->fetchAll();
    $s = $conexion->prepare('SELECT inicio,fin FROM bloqueos WHERE medico_id=? AND fecha=?');
    $s->execute([$medico,$fecha]);
    $bloqueos = $s->fetchAll();
    $opciones = [];
    $ahora = time();
    foreach (espacios_de_turnos($turnos, $fecha) as $espacio) {
        $t = $espacio['desde'];
        $hasta = $espacio['hasta'];
        $libre = $t > $ahora+(int)reglas_agenda()['anticipacion_minutos']*60 && consultorio_libre($m['consultorio_id'],$fecha,$espacio['hora'],$espacio['duracion'],$excluir);
        foreach ($ocupadas as $c) {
            $desde = strtotime($fecha.' '.$c['hora_cita']);
            if ($t < $desde + (int)$c['duracion'] * 60 && $hasta > $desde) {
                $libre = false;
            }
        }
        foreach ($bloqueos as $b) {
            if ($t < strtotime($fecha.' '.$b['fin']) && $hasta > strtotime($fecha.' '.$b['inicio'])) {
                $libre = false;
            }
        }
        $opciones[$espacio['hora']] = ['hora' => $espacio['hora'],'disponible' => $libre,'duracion' => $espacio['duracion'],'fin' => $espacio['fin']];
    }
    ksort($opciones);
    $resultado['horas'] = array_values($opciones);
    return $resultado;
}
function notificar_cita($paciente, $titulo, $mensaje, $id)
{
    global $conexion;
    $q = $conexion->prepare('SELECT responsable_id FROM responsables_pacientes WHERE paciente_id=?');
    $q->execute([$paciente]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $r) {
        if($titulo!=='Recordatorio de cita' || preferencia_aviso($r,'recordatorios_web')) notificar_persona($r, $titulo, $mensaje, 'mis_solicitudes.php?paciente='.(int)$paciente);
    }
    $s = $conexion->prepare('INSERT INTO notificaciones(paciente_id,titulo,mensaje,enlace) VALUES (?,?,?,?)');
    if($titulo!=='Recordatorio de cita' || preferencia_aviso($paciente,'recordatorios_web')) $s->execute([$paciente,$titulo,$mensaje,'mis_solicitudes.php?cita='.(int)$id]);
    $s = $conexion->prepare("INSERT INTO notificaciones(paciente_id,titulo,mensaje,enlace) SELECT m.usuario_id,?,?,? FROM citas c JOIN medicos m ON m.id=c.medico_id JOIN pacientes p ON p.id=m.usuario_id WHERE c.id=? AND p.activo=1 AND p.rol='medico'");
    $s->execute([$titulo, codigo_cita($id).': actualización en tu agenda.', 'comprobante.php?id='.(int)$id, $id]);
    if($titulo!=='Recordatorio de cita') correo_cita_preferido($paciente,$titulo,$mensaje);
}
function auditar_cita($id, $anterior, $nuevo, $usuario, $accion, $detalle = '')
{
    global $conexion;
    $s = $conexion->prepare('INSERT INTO historial_citas(cita_id,estado_anterior,estado_nuevo,usuario_id,accion,detalle) VALUES (?,?,?,?,?,?)');
    $s->execute([$id,$anterior,$nuevo,$usuario,$accion,$detalle]);
}
/** Orden de bloqueos: paciente, médicos por id y cita. Evita dos reservas del mismo turno. */
function guardar_reserva($usuario, $datos, $id = 0, $actor = null)
{
    global $conexion;
    $actor = $actor ?? $usuario;
    if (!in_array($actor['rol'], ['admin', 'recepcion'], true) && ($actor['rol'] !== 'paciente' || !es_responsable($actor, $usuario['id']))) {
        http_response_code(403);
        throw new DomainException('Esta cuenta no puede registrar reservas.');
    }
    $medico = (int)($datos['medico_id'] ?? 0);
    $fecha = $datos['fecha_cita'] ?? '';
    $hora = $datos['hora_cita'] ?? '';
    $telefono = telefono_normalizado($datos['telefono'] ?? '');
    $comentario = trim($datos['comentario'] ?? '');
    $motivo = trim($datos['motivo'] ?? '');
    if (!preg_match('/^\+?[0-9]{7,15}$/', $telefono)) {
        throw new DomainException('Ingresa un teléfono de 7 a 15 dígitos.');
    }
    if (mb_strlen($comentario) > 250) {
        throw new DomainException('El comentario admite hasta 250 caracteres.');
    }
    if ($id && (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 250)) {
        throw new DomainException('Explica el motivo de reprogramación entre 5 y 250 caracteres.');
    }
    $transaccion_propia = !$conexion->inTransaction();
    if ($transaccion_propia) {
        $conexion->beginTransaction();
    }
    $s = $conexion->prepare('SELECT id,rol,activo FROM pacientes WHERE id=? FOR UPDATE');
    $s->execute([$usuario['id']]);
    $paciente = $s->fetch();
    if (!$paciente || $paciente['rol'] !== 'paciente' || !$paciente['activo']) {
        throw new DomainException('Selecciona una cuenta de paciente activa. El personal no registra citas personales desde su panel.');
    }
    $previa = null;
    if ($id) {
        $s = $conexion->prepare('SELECT * FROM citas WHERE id=? AND paciente_id=?');
        $s->execute([$id,$usuario['id']]);
        $previa = $s->fetch();
        if (!$previa || !in_array($previa['estado'], ['pendiente','confirmado'], true) || strtotime($previa['fecha_cita'].' '.$previa['hora_cita']) <= time()) {
            throw new DomainException('Solo se pueden reprogramar tus citas pendientes o confirmadas que todavía no hayan comenzado.');
        }
    }
    $ids = array_unique(array_filter([$medico,(int)($previa['medico_id'] ?? 0)]));
    sort($ids);
    foreach ($ids as $mid) {
        medico_agenda($mid, true);
    }
    $m = medico_agenda($medico);
    if (!$m) {
        throw new DomainException('Selecciona un médico válido.');
    }
    if ((int)$m['especialidad_id'] !== (int)($datos['especialidad_id'] ?? 0)) {
        throw new DomainException('El médico no corresponde a la especialidad seleccionada.');
    }
    $rids=array_unique(array_filter([(int)$m['consultorio_id'],(int)($previa['consultorio_id']??0)]));sort($rids);
    foreach($rids as $rid) $conexion->prepare('SELECT id FROM consultorios WHERE id=? FOR UPDATE')->execute([$rid]);
    $disponibilidad = disponibilidad_reserva($medico, $fecha, $usuario['id'], $id);
    if ($disponibilidad['cita_del_dia']) {
        http_response_code(409);
        throw new DomainException($disponibilidad['mensaje']);
    }
    comprobar_reglas_reserva($usuario['id'],$fecha,$hora,$id,$actor);
    $seleccion = null;
    foreach ($disponibilidad['horas'] as $h) {
        if ($h['hora'] === $hora && $h['disponible']) {
            $seleccion = $h;
        }
    }
    if (!$seleccion) {
        http_response_code(409);
        throw new DomainException('Ese turno ya no está disponible. Selecciona otro horario.');
    }
    if ($id) {
        if ((int)$previa['medico_id'] === $medico && $previa['fecha_cita'] === $fecha && substr($previa['hora_cita'], 0, 5) === $hora) {
            throw new DomainException('Selecciona una fecha, médico u hora diferentes.');
        }
        $s = $conexion->prepare("UPDATE citas SET medico_id=?,especialidad_id=?,especialidad=?,fecha_cita=?,hora_cita=?,duracion=?,comentario=?,estado='pendiente',llegada_en=NULL,asistencia_confirmada_en=NULL,asistencia_confirmada_por=NULL,llamado_en=NULL,codigo_turno=NULL,consultorio_id=? WHERE id=?");
        $s->execute([$medico,$m['especialidad_id'],$m['especialidad'],$fecha,$hora,$seleccion['duracion'],$comentario,$m['consultorio_id'],$id]);
        $detalle = 'Anterior: '.$previa['fecha_cita'].' '.substr($previa['hora_cita'], 0, 5).'. Nuevo: '.$fecha.' '.$hora.'. Motivo: '.$motivo;
        auditar_cita($id, $previa['estado'], 'pendiente', $actor['id'], 'reprogramacion', $detalle);
        notificar_cita($usuario['id'], 'Cita reprogramada', codigo_cita($id).' se reprogramó al '.date('d/m/Y', strtotime($fecha)).' a las '.$hora.'. Está pendiente de confirmación.', $id);
    } else {
        $s = $conexion->prepare('INSERT INTO citas(paciente_id,medico_id,especialidad_id,especialidad,fecha_cita,hora_cita,duracion,comentario,consultorio_id) VALUES (?,?,?,?,?,?,?,?,?)');
        $s->execute([$usuario['id'],$medico,$m['especialidad_id'],$m['especialidad'],$fecha,$hora,$seleccion['duracion'],$comentario,$m['consultorio_id']]);
        $id = (int)$conexion->lastInsertId();
        auditar_cita($id, null, 'pendiente', $actor['id'], 'creacion', 'Solicitud registrada por '.ROLES[$actor['rol']].'.');
        notificar_cita($usuario['id'], 'Solicitud registrada', codigo_cita($id).' quedó pendiente de confirmación.', $id);
    }
    auditar_evento('citas', $id, $previa ? 'reprogramacion' : 'creacion', 'Actor '.ROLES[$actor['rol']]);
    $conexion->prepare('UPDATE pacientes SET telefono=? WHERE id=?')->execute([$telefono,$usuario['id']]);
    if ($transaccion_propia) {
        $conexion->commit();
    }
    return $id;
}
function cambiar_estado_cita($id, $nuevo, $usuario, $motivo = '')
{
    global $conexion;
    if (!array_key_exists($nuevo, ESTADOS_CITA)) {
        throw new DomainException('Selecciona un estado válido.');
    }
    $s = $conexion->prepare('SELECT * FROM citas WHERE id=?');
    $s->execute([$id]);
    $c = $s->fetch();
    if (!$c || !puede_ver_cita($usuario, $c)) {
        http_response_code(404);
        throw new DomainException('La cita no está disponible.');
    }
    $conexion->beginTransaction();
    $conexion->prepare('SELECT id FROM pacientes WHERE id=? FOR UPDATE')->execute([$c['paciente_id']]);
    if ($c['medico_id']) {
        medico_agenda($c['medico_id'], true);
    }
    $s = $conexion->prepare('SELECT * FROM citas WHERE id=? FOR UPDATE');
    $s->execute([$id]);
    $c = $s->fetch();
    $cancelacion_propia = $usuario['rol'] === 'paciente' && $nuevo === 'cancelado'
        && es_responsable($usuario, $c['paciente_id']) && strtotime($c['fecha_cita'].' '.$c['hora_cita']) > time();
    if ($cancelacion_propia && strtotime($c['fecha_cita'].' '.$c['hora_cita'])<time()+(int)reglas_agenda()['cancelacion_horas']*3600) throw new DomainException('Para cancelar se requieren '.reglas_agenda()['cancelacion_horas'].' horas de anticipación. Contacta con recepción.');
    $operacion_permitida = $usuario['rol'] === 'admin'
        || ($usuario['rol'] === 'recepcion' && in_array($nuevo, ['confirmado','rechazado','cancelado'], true))
        || ($usuario['rol'] === 'medico' && in_array($nuevo, ['atendido','no_asistio'], true));
    if (!$cancelacion_propia && !$operacion_permitida) {
        http_response_code(403);
        throw new DomainException('Tu rol no permite realizar este cambio de estado.');
    }
    $permitidos = ['pendiente' => ['confirmado','cancelado','rechazado'],'confirmado' => ['cancelado','atendido','no_asistio']];
    if (!in_array($nuevo, $permitidos[$c['estado']] ?? [], true)) {
        throw new DomainException('Ese cambio de estado no está permitido. Actualiza la página.');
    }
    if (in_array($nuevo, ['atendido','no_asistio'], true) && strtotime($c['fecha_cita'].' '.$c['hora_cita']) > time()) {
        throw new DomainException('La cita todavía no ha comenzado.');
    }
    if (in_array($nuevo, ['confirmado','rechazado'], true) && strtotime($c['fecha_cita'].' '.$c['hora_cita']) <= time()) {
        throw new DomainException('La solicitud ya pasó de fecha. Puedes cancelarla indicando el motivo.');
    }
    if (in_array($nuevo, ['cancelado','rechazado'], true) && (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 250)) {
        throw new DomainException('Escribe un motivo entre 5 y 250 caracteres.');
    }
    if ($nuevo === 'atendido' && $c['fecha_cita'] === date('Y-m-d') && (!$c['llegada_en'] || !$c['inicio_atencion'])) {
        throw new DomainException('Para cerrar la cita de hoy, recepción debe registrar la llegada y el médico debe iniciar la atención.');
    }
    if ($nuevo === 'atendido' && !$c['inicio_atencion'] && mb_strlen($motivo) < 10) {
        throw new DomainException('Inicia la atención o explica una corrección histórica con al menos 10 caracteres.');
    }
    if ($nuevo === 'no_asistio' && $c['llegada_en'] && mb_strlen($motivo) < 10) {
        throw new DomainException('Hay una llegada registrada. Explica la corrección con al menos 10 caracteres.');
    }
    $conexion->prepare('UPDATE citas SET estado=? WHERE id=?')->execute([$nuevo,$id]);
    if ($nuevo === 'atendido') {
        $conexion->prepare('UPDATE citas SET fin_atencion=IF(inicio_atencion IS NOT NULL,NOW(),NULL),atendido_por=? WHERE id=?')->execute([$usuario['id'],$id]);
    }
    auditar_cita($id, $c['estado'], $nuevo, $usuario['id'], 'estado', $motivo);
    notificar_cita($c['paciente_id'], 'Estado de tu cita', codigo_cita($id).': '.ESTADOS_CITA[$nuevo].($motivo ? '. Motivo: '.$motivo : ''), $id);
    if (in_array($nuevo, ['cancelado','rechazado'], true)) {
        require_once __DIR__.'/../services/operaciones.php';
        avisar_espacio_liberado($c);
    }
    $conexion->commit();
}
/** Admisión registra la presencia del paciente antes del llamado a consultorio. */
function registrar_llegada($id, $usuario)
{
    global $conexion;
    if (!puede_registrar_llegada($usuario)) {
        http_response_code(403);
        throw new DomainException('La llegada del paciente se registra en recepción.');
    }
    $conexion->beginTransaction();
    $s = $conexion->prepare('SELECT * FROM citas WHERE id=? FOR UPDATE');
    $s->execute([$id]);
    $c = $s->fetch();
    if (!$c || !puede_ver_cita($usuario, $c)) {
        http_response_code(403);
        throw new DomainException('No tienes acceso a esta atención.');
    }
    if ($c['fecha_cita'] !== date('Y-m-d') || $c['estado'] !== 'confirmado' || $c['llegada_en']) {
        throw new DomainException('Solo se registra una vez la llegada a una cita confirmada de hoy.');
    }
    $conexion->prepare('UPDATE citas SET llegada_en=NOW(),codigo_turno=COALESCE(codigo_turno,?) WHERE id=?')->execute([strtoupper(bin2hex(random_bytes(4))),$id]);
    auditar_cita($id, $c['estado'], $c['estado'], $usuario['id'], 'llegada', 'Se registró la llegada del paciente.');
    $conexion->commit();
}
