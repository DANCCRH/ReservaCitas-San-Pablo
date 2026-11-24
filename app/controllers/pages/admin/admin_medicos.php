<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(true);
$dias = [1 => 'Lunes',2 => 'Martes',3 => 'Miércoles',4 => 'Jueves',5 => 'Viernes',6 => 'Sábado',7 => 'Domingo'];
$volver = 'admin_medicos.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $accion = texto_post('accion', 30);
        $mid = (int)($_POST['medico_id'] ?? 0);
        if ($mid) {
            $volver .= '?id='.$mid;
        }
        if ($accion === 'medico') {
            $nombre = texto_post('nombres', 120);
            $cmp = strtoupper(texto_post('colegiatura', 20));
            $eid = id_post('especialidad_id');
            $desc = texto_post('descripcion', 500);
            $rid=(int)($_POST['consultorio_id']??0);
            if($rid){$q=$conexion->prepare('SELECT id FROM consultorios WHERE id=? AND activo=1');$q->execute([$rid]);if(!$q->fetch())throw new DomainException('Consultorio no disponible.');}
            $activo = isset($_POST['activo']) ? 1 : 0;
            if (mb_strlen($nombre) < 5 || !preg_match('/^[A-Z0-9-]{3,20}$/', $cmp)) {
                throw new DomainException('Revisa el nombre y el código del profesional.');
            }
            $s = $conexion->prepare('SELECT id FROM especialidades WHERE id=?');
            $s->execute([$eid]);
            if (!$s->fetch()) {
                throw new DomainException('Selecciona una especialidad válida.');
            }
            if ($mid) {
                $conexion->beginTransaction();
                $m = medico_agenda($mid, true);
                if (!$m) {
                    throw new DomainException('Médico no encontrado.');
                }
                if ((int)$m['especialidad_id'] !== $eid) {
                    $s = $conexion->prepare("SELECT COUNT(*) FROM citas WHERE medico_id=? AND estado IN ('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW()");
                    $s->execute([$mid]);
                    if ($s->fetchColumn()) {
                        throw new DomainException('El médico tiene citas futuras. Atiéndelas o cancélalas antes de cambiar su especialidad.');
                    }
                }
                $conexion->prepare('UPDATE medicos SET nombres=?,colegiatura=?,especialidad_id=?,descripcion=?,activo=?,consultorio_id=COALESCE(?,consultorio_id) WHERE id=?')->execute([$nombre,$cmp,$eid,$desc,$activo,$rid?:null,$mid]);
                if (!$activo && $m['activo']) {
                    foreach (citas_futuras_medico($mid) as $c) {
                        notificar_persona($c['paciente_id'], 'El profesional no admite nuevas reservas', 'Tu cita '.codigo_cita($c['id']).' se mantiene. Recepción coordinará cualquier cambio.', 'comprobante.php?id='.(int)$c['id']);
                    }
                }
                if ($m['usuario_id']) {
                    $conexion->prepare('UPDATE pacientes SET nombres=? WHERE id=?')->execute([$nombre,$m['usuario_id']]);
                }
                auditar_evento('medicos', $mid, 'actualizar', $activo ? 'Acepta nuevas reservas' : 'No admite reservas nuevas; conserva compromisos existentes');
                $conexion->commit();
            } else {
                if(!$rid)throw new DomainException('Selecciona un consultorio para el nuevo profesional.');
                $conexion->prepare('INSERT INTO medicos(nombres,colegiatura,especialidad_id,descripcion,activo,consultorio_id) VALUES (?,?,?,?,?,?)')->execute([$nombre,$cmp,$eid,$desc,$activo,$rid]);
                $mid = (int)$conexion->lastInsertId();
                guardar_version_horario($mid,date('Y-m-d'),$u['id']);
            }
        } elseif ($accion === 'turno') {
            if (!$mid) {
                throw new DomainException('Selecciona un médico.');
            }$dia = (int)($_POST['dia'] ?? 0);
            $inicio = texto_post('inicio', 5);
            $fin = texto_post('fin', 5);
            $duracion = (int)($_POST['duracion'] ?? 0);
            if (!isset($dias[$dia]) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $inicio) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $fin) || $inicio >= $fin || !in_array($duracion, [15,30,45,60], true) || strtotime($fin) - strtotime($inicio) < $duracion * 60) {
                throw new DomainException('Revisa el día, el inicio, el fin y la duración del turno.');
            }
            $conexion->beginTransaction();
            if (!medico_agenda($mid, true)) {
                throw new DomainException('Médico no encontrado.');
            }
            $s = $conexion->prepare('SELECT id FROM turnos WHERE medico_id=? AND dia=? AND inicio<? AND fin>?');
            $s->execute([$mid,$dia,$fin,$inicio]);
            if ($s->fetch()) {
                throw new DomainException('El turno se superpone con uno ya registrado.');
            }
            $conexion->prepare('INSERT INTO turnos(medico_id,dia,inicio,fin,duracion) VALUES (?,?,?,?,?)')->execute([$mid,$dia,$inicio,$fin,$duracion]);
            guardar_version_horario($mid,texto_post('vigencia',10) ?: date('Y-m-d'),$u['id']);
            $conexion->commit();
        } elseif ($accion === 'quitar_turno') {
            $conexion->beginTransaction();
            medico_agenda($mid, true);
            $q = $conexion->prepare('SELECT * FROM turnos WHERE id=? AND medico_id=?');
            $q->execute([id_post('id'),$mid]);
            $turno = $q->fetch();
            if (!$turno) {
                throw new DomainException('Turno no encontrado.');
            }
            $afectadas = citas_futuras_medico($mid, $turno['dia'], $turno['inicio'], $turno['fin']);
            $vigencia=texto_post('vigencia',10) ?: date('Y-m-d');
            $afectadas=array_filter($afectadas,fn($c)=>$c['fecha_cita']>=$vigencia);
            if ($afectadas) {
                throw new DomainException('No se puede quitar el turno: hay '.count($afectadas).' citas futuras. Gestiona una propuesta en Reorganizar citas y espera su aceptación antes de retirarlo.');
            }
            auditar_evento('turnos', id_post('id'), 'retirar', 'Profesional '.$mid);
            $conexion->prepare('DELETE FROM turnos WHERE id=? AND medico_id=?')->execute([id_post('id'),$mid]);
            guardar_version_horario($mid,$vigencia,$u['id']);
            $conexion->commit();
        } elseif ($accion === 'bloqueo') {
            $fecha = texto_post('fecha', 10);
            $inicio = texto_post('inicio', 5) ?: '00:00';
            $fin = texto_post('fin', 5) ?: '23:59';
            $motivo = texto_post('motivo', 250);
            if (!fecha_admitida($fecha) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $inicio) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $fin) || $inicio >= $fin || mb_strlen($motivo) < 5) {
                throw new DomainException('Revisa la fecha, las horas y el motivo del bloqueo.');
            }
            $conexion->beginTransaction();
            if (!medico_agenda($mid, true)) {
                throw new DomainException('Médico no encontrado.');
            }
            $s = $conexion->prepare("SELECT id FROM citas WHERE medico_id=? AND fecha_cita=? AND estado IN ('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)<TIMESTAMP(?,?) AND DATE_ADD(TIMESTAMP(fecha_cita,hora_cita),INTERVAL duracion MINUTE)>TIMESTAMP(?,?)");
            $s->execute([$mid,$fecha,$fecha,$fin,$fecha,$inicio]);
            if ($s->fetch()) {
                throw new DomainException('Hay citas reservadas dentro del bloqueo. Resuelve esas citas antes de bloquear el horario.');
            }
            $conexion->prepare('INSERT INTO bloqueos(medico_id,fecha,inicio,fin,motivo) VALUES (?,?,?,?,?)')->execute([$mid,$fecha,$inicio,$fin,$motivo]);
            $conexion->commit();
        } elseif ($accion === 'quitar_bloqueo') {
            $conexion->beginTransaction();
            medico_agenda($mid, true);
            $conexion->prepare('DELETE FROM bloqueos WHERE id=? AND medico_id=?')->execute([id_post('id'),$mid]);
            $conexion->commit();
        } else {
            throw new DomainException('Acción no válida.');
        }
        auditar_evento('medicos', $mid, $accion, 'Cambio de catálogo, turno o bloqueo del profesional');
        aviso('Los cambios se guardaron correctamente. Las citas ya registradas se conservan.');
        header('Location: admin_medicos.php?id='.$mid, true, 303);
        exit;
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) == 1062) {
            $e = new DomainException('Ese código o turno ya está registrado.');
        }error_formulario($e, $volver);
    } catch (Throwable $e) {
        error_formulario($e, $volver);
    }
}
$mid = (int)($_GET['id'] ?? 0);
$m = $mid ? medico_agenda($mid) : null;
