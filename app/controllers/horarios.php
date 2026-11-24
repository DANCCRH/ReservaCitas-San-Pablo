<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
$u = exigir_usuario(['paciente','admin','recepcion'], true);
try {
    $pid = (int)($_GET['paciente'] ?? 0) ?: ($u['rol'] === 'paciente' ? (int)$u['id'] : 0);
    paciente_autorizado($u, $pid);
    $s = $conexion->prepare("SELECT id FROM pacientes WHERE id=? AND rol='paciente' AND activo=1");
    $s->execute([$pid]);
    if (!$s->fetch()) {
        throw new DomainException('Selecciona un paciente activo.');
    }
    $mid = isset($_GET['medico']) ? filter_var($_GET['medico'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
    if ($mid === false) {
        throw new DomainException('Selecciona un médico para consultar los turnos.');
    }
    $eid = isset($_GET['especialidad']) ? filter_var($_GET['especialidad'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
    if ($eid === false) {
        throw new DomainException('Selecciona una especialidad válida.');
    }
    $excluir = (int)($_GET['excluir'] ?? 0);
    if ($excluir) {
        $s = $conexion->prepare("SELECT id FROM citas WHERE id=? AND paciente_id=? AND estado IN ('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW()");
        $s->execute([$excluir,$pid]);
        if (!$s->fetch()) {
            throw new DomainException('La cita seleccionada no se puede reprogramar.');
        }
    }
    $disponibilidad = disponibilidad_reserva($mid, sanitizar($_GET['fecha'] ?? ''), $pid, $excluir, $eid);
    if ($disponibilidad['cita_del_dia']) {
        $cita = $disponibilidad['cita_del_dia'];
        $inicio = strtotime($cita['fecha'].' '.$cita['hora']);
        $anticipacion = $u['rol'] === 'paciente' ? (int)reglas_agenda()['cancelacion_horas'] * 3600 : 0;
        $ahora = time();
        $disponibilidad['cita_del_dia']['reprogramable'] = in_array($cita['estado'], ['pendiente','confirmado'], true)
            && $inicio > $ahora && $inicio >= $ahora + $anticipacion;
    }
    responder_json(['hoy' => date('Y-m-d')] + $disponibilidad);
} catch (DomainException $e) {
    responder_json(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log($e);
    responder_json(['error' => 'No fue posible consultar los turnos.'], 500);
}
