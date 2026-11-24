<?php

require_once __DIR__.'/../config/conexion.php';
exigir_post();
$u = exigir_usuario(['paciente','admin','recepcion']);
try {
    comprobar_csrf();
    $pid = (int)($_POST['paciente_id'] ?? $u['id']);
    paciente_autorizado($u, $pid);
    if (($_POST['accion'] ?? '') === 'retirar') {
        $conexion->prepare("UPDATE lista_espera SET estado='retirada' WHERE id=? AND paciente_id=? AND estado IN ('activa','ofertada')")->execute([id_post('id'),$pid]);
    } else {
        $eid = id_post('especialidad_id');
        $mid = (int)($_POST['medico_id'] ?? 0);
        $desde = texto_post('desde', 10);
        $hasta = texto_post('hasta', 10);
        $hi = texto_post('hora_desde', 5);
        $hf = texto_post('hora_hasta', 5);
        require_once __DIR__.'/../models/agenda_modelo.php';
        if (!fecha_admitida($desde) || !fecha_admitida($hasta) || $desde > $hasta || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $hi) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $hf) || $hi >= $hf) {
            throw new DomainException('Revisa las fechas y las horas de preferencia.');
        }$q = $conexion->prepare('SELECT id FROM especialidades WHERE id=? AND activo=1');
        $q->execute([$eid]);
        if (!$q->fetchColumn()) {
            throw new DomainException('Especialidad no disponible.');
        }if ($mid) {
            $q = $conexion->prepare('SELECT id FROM medicos WHERE id=? AND especialidad_id=? AND activo=1');
            $q->execute([$mid,$eid]);
            if (!$q->fetchColumn()) {
                throw new DomainException('El médico no corresponde a la especialidad.');
            }
        }
        $conexion->beginTransaction();
        $conexion->prepare('SELECT id FROM pacientes WHERE id=? FOR UPDATE')->execute([$pid]);
        if ($desde === $hasta) {
            $disponibilidad = disponibilidad_reserva($mid, $desde, $pid, 0, $eid);
            if ($disponibilidad['cita_del_dia']) {
                throw new DomainException($disponibilidad['mensaje']);
            }
        }
        $q = $conexion->prepare("SELECT COUNT(*) FROM lista_espera WHERE paciente_id=? AND estado IN('activa','ofertada') AND hasta>=CURDATE()");
        $q->execute([$pid]);
        if ($q->fetchColumn() >= 3) {
            throw new DomainException('Puedes mantener hasta tres preferencias activas.');
        }$conexion->prepare('INSERT INTO lista_espera(paciente_id,especialidad_id,medico_id,desde,hasta,hora_desde,hora_hasta) VALUES(?,?,?,?,?,?,?)')->execute([$pid,$eid,$mid ?: null,$desde,$hasta,$hi,$hf]);
        $conexion->commit();
    }
    aviso('Lista de espera actualizada. Los avisos no reservan un horario automáticamente.');
    header('Location: lista_espera.php', true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e,'lista_espera.php');
}
