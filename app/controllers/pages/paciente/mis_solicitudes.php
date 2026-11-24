<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/models/agenda_modelo.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario();
if ($u['rol'] !== 'paciente') {
    header('Location: '.destino_rol($u));
    exit;
}
$paciente = paciente_autorizado($u, (int)($_GET['paciente'] ?? $u['id']));
$vista = sanitizar($_GET['vista'] ?? 'proximas');
if (!in_array($vista, ['proximas','historial','todas'], true)) {
    $vista = 'proximas';
}
$estado = sanitizar($_GET['estado'] ?? '');
$busqueda = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$id = (int)($_GET['cita'] ?? 0);
$where = ['c.paciente_id=?'];
$valores = [$paciente['id']];
if ($id) {
    $where[] = 'c.id=?';
    $valores[] = $id;
    $vista = 'todas';
} elseif ($vista === 'proximas') {
    $where[] = "c.estado IN ('pendiente','confirmado') AND TIMESTAMP(c.fecha_cita,c.hora_cita)>NOW()";
} elseif ($vista === 'historial') {
    $where[] = "(c.estado NOT IN ('pendiente','confirmado') OR TIMESTAMP(c.fecha_cita,c.hora_cita)<=NOW())";
}
if (array_key_exists($estado, ESTADOS_CITA)) {
    $where[] = 'c.estado=?';
    $valores[] = $estado;
}
if ($busqueda) {
    $where[] = '(c.especialidad LIKE ? OR m.nombres LIKE ?)';
    $valores[] = '%'.$busqueda.'%';
    $valores[] = '%'.$busqueda.'%';
}
$from = ' FROM citas c LEFT JOIN medicos m ON m.id=c.medico_id WHERE '.implode(' AND ', $where);
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($valores);
$total = (int)$s->fetchColumn();
$paginas = max(1, (int)ceil($total / 10));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$s = $conexion->prepare('SELECT c.*,m.nombres medico'.$from.' ORDER BY c.fecha_cita '.($vista === 'proximas' ? 'ASC' : 'DESC').',c.hora_cita,id DESC LIMIT 10 OFFSET '.(($pagina - 1) * 10));
$s->execute($valores);
$citas = $s->fetchAll();
$s = $conexion->prepare('SELECT estado,COUNT(*) cantidad FROM citas WHERE paciente_id=? GROUP BY estado');
$s->execute([$paciente['id']]);
$historiales = [];
if ($citas) {
    $ids = array_column($citas, 'id');
    $h = $conexion->prepare('SELECT h.*,p.nombres autor FROM historial_citas h JOIN pacientes p ON p.id=h.usuario_id WHERE h.cita_id IN('.implode(',', array_fill(0, count($ids), '?')).') ORDER BY h.id DESC');
    $h->execute($ids);
    foreach ($h as $cambio) {
        $historiales[$cambio['cita_id']][] = $cambio;
    }
}
$conteos = array_column($s->fetchAll(), 'cantidad', 'estado');
