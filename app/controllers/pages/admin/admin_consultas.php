<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(['admin','recepcion']);
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$estado = sanitizar($_GET['estado'] ?? '');
$where = [];
$v = [];
if ($q) {
    $where[] = '(x.remitente_nombre LIKE ? OR x.remitente_correo LIKE ? OR x.asunto LIKE ?)';
    $v = array_fill(0, 3, '%'.$q.'%');
}if (in_array($estado, ['nueva','en_proceso','resuelta'], true)) {
    $where[] = 'x.estado=?';
    $v[] = $estado;
}if ($estado === 'nuevas') {
    $where[] = 'x.leido_en IS NULL';
}if ($estado === 'leidas') {
    $where[] = 'x.leido_en IS NOT NULL';
}$from = ' FROM consultas x LEFT JOIN pacientes p ON p.id=x.asignado_id'.($where ? ' WHERE '.implode(' AND ', $where) : '');
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($v);
$total = $s->fetchColumn();
$paginas = max(1, (int)ceil($total / 10));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$s = $conexion->prepare('SELECT x.*,p.nombres responsable'.$from.' ORDER BY (x.estado=\'resuelta\'),x.fecha DESC LIMIT 10 OFFSET '.(($pagina - 1) * 10));
$s->execute($v);
$filas = $s->fetchAll();
$equipo = $conexion->query("SELECT id,nombres FROM pacientes WHERE activo=1 AND rol IN('admin','recepcion') ORDER BY nombres")->fetchAll();
