<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(['admin','recepcion','medico','asistencial']);
$vista = $_GET['vista'] ?? 'dia';
$opciones = ['dia' => 'Atenciones de hoy','vencidas' => 'Vencidas por resolver','pendientes' => 'Solicitudes pendientes','espera' => 'Pacientes en espera','sin_confirmar'=>'Asistencia sin confirmar'];
if (!isset($opciones[$vista])) {
    $vista = 'dia';
}
$where = [];
$v = [];
if($vista==='sin_confirmar'){$where[]="c.estado='confirmado' AND c.asistencia_confirmada_en IS NULL AND TIMESTAMP(c.fecha_cita,c.hora_cita)>NOW()";} elseif ($vista === 'vencidas') {
    $where[] = "c.estado IN ('pendiente','confirmado') AND (c.fecha_cita<CURDATE() OR(c.fecha_cita=CURDATE() AND c.hora_cita<CURTIME()))";
} elseif ($vista === 'pendientes') {
    $where[] = "c.estado='pendiente'";
} else {
    $where[] = 'c.fecha_cita=CURDATE()';
    if ($vista === 'espera') {
        $where[] = "c.estado='confirmado' AND c.llegada_en IS NOT NULL AND c.inicio_atencion IS NULL";
    }
}
if ($u['rol'] === 'medico') {
    $where[] = 'c.medico_id=?';
    $v[] = medico_del_usuario($u);
}if ($u['rol'] === 'asistencial') {
    $ids = equipo_del_usuario($u);
    $where[] = "c.fecha_cita=CURDATE() AND c.estado='confirmado' AND c.medico_id IN (".implode(',', array_map('intval', $ids ?: [0])).')';
}
$from = ' FROM citas c JOIN pacientes p ON p.id=c.paciente_id LEFT JOIN medicos m ON m.id=c.medico_id WHERE '.implode(' AND ', $where);
$q = $conexion->prepare('SELECT COUNT(*)'.$from);
$q->execute($v);
$total = (int)$q->fetchColumn();
$paginas = max(1, (int)ceil($total / 15));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$q = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico'.$from.' ORDER BY (c.llegada_en IS NOT NULL) DESC,c.fecha_cita,c.hora_cita LIMIT 15 OFFSET '.(($pagina - 1) * 15));
$q->execute($v);
