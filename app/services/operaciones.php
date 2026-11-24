<?php

require_once __DIR__.'/../models/agenda_modelo.php';
require_once __DIR__.'/correo.php';
function avisar_espacio_liberado($c)
{
    global $conexion;
    if (strtotime($c['fecha_cita'].' '.$c['hora_cita']) <= time()) {
        return;
    }
    $enlace = 'agenda.php?'.http_build_query(['vista' => 'horarios','medico' => $c['medico_id'],'fecha' => $c['fecha_cita']]);
    $q = $conexion->query("SELECT id FROM pacientes WHERE activo=1 AND es_invitado=0 AND rol IN ('admin','recepcion')");
    foreach ($q as $p) {
        notificar_persona($p['id'], 'Espacio liberado', codigo_cita($c['id']).' liberó un espacio el '.$c['fecha_cita'].' a las '.substr($c['hora_cita'], 0, 5).'.', $enlace);
    }
    $q = $conexion->prepare("SELECT l.*,p.correo FROM lista_espera l JOIN pacientes p ON p.id=l.paciente_id
        WHERE l.estado IN ('activa','ofertada') AND l.especialidad_id=? AND (l.medico_id IS NULL OR l.medico_id=?)
        AND l.desde<=? AND l.hasta>=? AND l.hora_desde<=? AND l.hora_hasta>?
        AND (l.oferta_en IS NULL OR l.oferta_en<DATE_SUB(NOW(),INTERVAL 24 HOUR))
        AND NOT EXISTS (SELECT 1 FROM citas a WHERE a.paciente_id=l.paciente_id AND a.fecha_cita=?
            AND a.estado NOT IN ('cancelado','rechazado') AND (a.especialidad_id=l.especialidad_id
            OR (a.especialidad_id IS NULL AND a.especialidad=?) OR a.medico_id=? OR
            (a.hora_cita<ADDTIME(?,SEC_TO_TIME(?*60)) AND ADDTIME(a.hora_cita,SEC_TO_TIME(a.duracion*60))>?)))
        ORDER BY l.creado_en LIMIT 5");
    $q->execute([$c['especialidad_id'],$c['medico_id'],$c['fecha_cita'],$c['fecha_cita'],$c['hora_cita'],$c['hora_cita'],
        $c['fecha_cita'],$c['especialidad'],$c['medico_id'],$c['hora_cita'],$c['duracion'],$c['hora_cita']]);
    foreach ($q->fetchAll() as $l) {
        $url = 'reserva.php?'.http_build_query(['paciente' => $l['paciente_id'],'esp' => $c['especialidad_id'],'medico' => $c['medico_id'],'fecha' => $c['fecha_cita'],'hora' => substr($c['hora_cita'], 0, 5)]);
        notificar_persona($l['paciente_id'], 'Hay un horario para revisar', 'Se liberó un horario compatible con tu lista de espera. La disponibilidad se confirma al reservar.', $url);
        $conexion->prepare("UPDATE lista_espera SET estado='ofertada',oferta_cita_id=?,oferta_en=NOW() WHERE id=?")->execute([$c['id'],$l['id']]);
        if ($l['correo'] && preferencia_aviso($l['paciente_id'],'espera_email')) {
            encolar_correo($l['correo'], 'Horario disponible en ReservaCitas', 'Consulta tu cuenta para revisar el espacio disponible. No se ha reservado automáticamente.', 'espera:'.$l['id'].':'.$c['id']);
        }
    }
}
function bloquear_rango($u, $mid, $desde, $hasta, $inicio, $fin, $motivo)
{
    global $conexion;
    if (!fecha_admitida($desde) || !fecha_admitida($hasta) || $desde > $hasta || strtotime($hasta) - strtotime($desde) > 90 * 86400 || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $inicio) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $fin) || $inicio >= $fin || mb_strlen($motivo) < 5) {
        throw new DomainException('Revisa el periodo de hasta 91 días, horas y motivo.');
    }
    $conexion->beginTransaction();
    if (!medico_agenda($mid, true)) {
        throw new DomainException('Profesional no encontrado.');
    }
    $q = $conexion->prepare("SELECT id FROM citas WHERE medico_id=? AND fecha_cita BETWEEN ? AND ? AND estado IN ('pendiente','confirmado') AND hora_cita<? AND ADDTIME(hora_cita,SEC_TO_TIME(duracion*60))>? LIMIT 1");
    $q->execute([$mid,$desde,$hasta,$fin,$inicio]);
    if ($q->fetch()) {
        throw new DomainException('El periodo tiene citas activas. Coordina primero sus propuestas en Reorganizar citas.');
    }
    $i = $conexion->prepare('INSERT INTO bloqueos(medico_id,fecha,inicio,fin,motivo) SELECT ?,?,?,?,? WHERE NOT EXISTS(SELECT 1 FROM bloqueos WHERE medico_id=? AND fecha=? AND inicio=? AND fin=?)');
    for ($d = new DateTimeImmutable($desde);$d <= new DateTimeImmutable($hasta);$d = $d->modify('+1 day')) {
        $f = $d->format('Y-m-d');
        $i->execute([$mid,$f,$inicio,$fin,$motivo,$mid,$f,$inicio,$fin]);
    }
    auditar_evento('bloqueos', $mid, 'periodo', $desde.' a '.$hasta.' · '.$motivo);
    $conexion->commit();
}
function iniciar_atencion($u, $id)
{
    global $conexion;
    $conexion->beginTransaction();
    $q = $conexion->prepare('SELECT * FROM citas WHERE id=? FOR UPDATE');
    $q->execute([$id]);
    $c = $q->fetch();
    if (!$c || !in_array($u['rol'], ['admin','medico'], true) || !puede_ver_cita($u, $c)) {
        http_response_code(403);
        throw new DomainException('No puedes iniciar esta atención.');
    }
    if ($c['estado'] !== 'confirmado' || $c['fecha_cita'] !== date('Y-m-d') || !$c['llegada_en'] || $c['inicio_atencion']) {
        throw new DomainException('Se necesita una cita confirmada de hoy, con llegada registrada y sin atención iniciada.');
    }
    if (strtotime($c['fecha_cita'].' '.$c['hora_cita']) > time()) {
        throw new DomainException('Podrás iniciar la atención a partir de las '.substr($c['hora_cita'], 0, 5).'.');
    }
    $conexion->prepare('UPDATE citas SET inicio_atencion=NOW(),atendido_por=? WHERE id=?')->execute([$u['id'],$id]);
    auditar_cita($id, $c['estado'], $c['estado'], $u['id'], 'inicio_atencion', 'Se inició la atención.');
    $conexion->commit();
}
function proponer_cambios($u, $ids, $mid, $desde, $motivo)
{
    global $conexion;
    if (!fecha_admitida($desde) || mb_strlen($motivo) < 5 || !$ids || count($ids) > 50) {
        throw new DomainException('Selecciona hasta 50 citas, fecha y motivo.');
    }
    $m = medico_agenda($mid);
    if (!$m || !$m['activo'] || !$m['especialidad_activa']) {
        throw new DomainException('Elige un profesional disponible.');
    }
    $conexion->beginTransaction();
    medico_agenda($mid, true);
    $ocupadas = [];
    $diasPorPaciente = [];
    $cantidad = 0;
    foreach (array_unique(array_map('intval', $ids)) as $id) {
        $q = $conexion->prepare("SELECT c.*,p.* ,c.id cita_id,c.paciente_id pid FROM citas c JOIN pacientes p ON p.id=c.paciente_id WHERE c.id=? AND c.estado IN ('pendiente','confirmado') AND (c.fecha_cita>CURDATE() OR(c.fecha_cita=CURDATE() AND c.hora_cita>CURTIME())) FOR UPDATE");
        $q->execute([$id]);
        $c = $q->fetch();
        if (!$c || $c['especialidad_id'] != $m['especialidad_id']) {
            throw new DomainException('Todas las citas deben ser futuras y de la misma especialidad del sustituto.');
        }
        $elegida = null;
        for ($d = new DateTimeImmutable($desde);$d <= new DateTimeImmutable($desde.' +30 days');$d = $d->modify('+1 day')) {
            if (!fecha_admitida($d->format('Y-m-d'))) {
                break;
            }
            if (isset($diasPorPaciente[$c['pid']][$d->format('Y-m-d')])) {
                continue;
            }
            foreach (horarios_disponibles($mid, $d->format('Y-m-d'), $c['pid'], $id) as $h) {
                $k = $d->format('Y-m-d').' '.$h['hora'];
                if ($h['disponible'] && !isset($ocupadas[$k]) && !(($c['medico_id'] == $mid) && $c['fecha_cita'] === $d->format('Y-m-d') && substr($c['hora_cita'], 0, 5) === $h['hora'])) {
                    $elegida = [$d->format('Y-m-d'),$h['hora']];
                    $ocupadas[$k] = true;
                    $diasPorPaciente[$c['pid']][$d->format('Y-m-d')] = true;
                    break;
                }
            }if ($elegida) {
                break;
            }
        }
        if (!$elegida) {
            throw new DomainException('No se encontraron espacios suficientes. No se guardó ninguna propuesta.');
        }
        $conexion->prepare("UPDATE propuestas_citas SET estado='sustituida',resuelto_en=NOW() WHERE cita_id=? AND estado='pendiente'")->execute([$id]);
        $conexion->prepare('INSERT INTO propuestas_citas(cita_id,medico_id,fecha,hora,motivo,creado_por) VALUES(?,?,?,?,?,?)')->execute([$id,$mid,...$elegida,$motivo,$u['id']]);
        auditar_cita($id, $c['estado'], $c['estado'], $u['id'], 'propuesta', 'Propuesta '.$elegida[0].' '.$elegida[1].' con '.$m['nombres'].'. '.$motivo);
        notificar_cita($c['pid'], 'Propuesta de cambio', 'Revisa y acepta o rechaza la propuesta para '.codigo_cita($id).'. Tu reserva original se conserva hasta la aceptación.', $id);
        $cantidad++;
    }auditar_evento('propuestas', null, 'lote', $cantidad.' propuestas · '.$motivo);
    $conexion->commit();
    return $cantidad;
}
function responder_propuesta($u, $id, $aceptar)
{
    global $conexion;
    $conexion->beginTransaction();
    $q = $conexion->prepare("SELECT x.*,c.paciente_id,c.estado estado_cita FROM propuestas_citas x JOIN citas c ON c.id=x.cita_id WHERE x.id=? AND x.estado='pendiente' FOR UPDATE");
    $q->execute([$id]);
    $p = $q->fetch();
    if (!$p || $u['rol'] !== 'paciente' || !es_responsable($u, $p['paciente_id'])) {
        throw new DomainException('Propuesta no disponible.');
    }
    if ($aceptar) {
        $pac = paciente_autorizado($u, $p['paciente_id']);
        $m = medico_agenda($p['medico_id']);
        guardar_reserva($pac, ['medico_id' => $m['id'],'especialidad_id' => $m['especialidad_id'],'fecha_cita' => $p['fecha'],'hora_cita' => substr($p['hora'], 0, 5),'telefono' => $pac['telefono'],'comentario' => '','motivo' => 'Propuesta aceptada: '.$p['motivo']], $p['cita_id'], $u);
    }
    $conexion->prepare('UPDATE propuestas_citas SET estado=?,resuelto_en=NOW() WHERE id=?')->execute([$aceptar ? 'aceptada' : 'rechazada',$id]);
    auditar_evento('propuestas',$id,$aceptar ? 'aceptar' : 'rechazar','Respuesta del paciente o responsable');
    $conexion->commit();
}
