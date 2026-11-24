<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(true);
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$entidad = substr(sanitizar($_GET['entidad'] ?? ''), 0, 40);
$v = ['%'.$q.'%'];
$where = ' WHERE (a.detalle LIKE ?'.($q ? ' OR p.nombres LIKE ?' : '').')';
if ($q) {
    $v[] = '%'.$q.'%';
}if ($entidad) {
    $where .= ' AND a.entidad=?';
    $v[] = $entidad;
}$from = ' FROM auditoria_eventos a LEFT JOIN pacientes p ON p.id=a.usuario_id'.$where;
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($v);
$total = $s->fetchColumn();
$paginas = max(1, (int)ceil($total / 25));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$s = $conexion->prepare('SELECT a.*,p.nombres autor'.$from.' ORDER BY a.id DESC LIMIT 25 OFFSET '.(($pagina - 1) * 25));
$s->execute($v);
$eventos = $s->fetchAll();
