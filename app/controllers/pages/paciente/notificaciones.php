<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conexion->prepare('UPDATE notificaciones SET leido_en=COALESCE(leido_en,NOW()) WHERE id=? AND paciente_id=?')->execute([$id,$u['id']]);
        } else {
            $conexion->prepare('UPDATE notificaciones SET leido_en=NOW() WHERE paciente_id=? AND leido_en IS NULL')->execute([$u['id']]);
        }header('Location: notificaciones.php', true, 303);
        exit;
    } catch (Throwable $e) {
        error_formulario($e, 'notificaciones.php');
    }
}
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$s = $conexion->prepare('SELECT COUNT(*) FROM notificaciones WHERE paciente_id=?');
$s->execute([$u['id']]);
$total = (int)$s->fetchColumn();
$paginas = max(1, (int)ceil($total / 20));
$pagina = min($pagina, $paginas);
$s = $conexion->prepare('SELECT * FROM notificaciones WHERE paciente_id=? ORDER BY id DESC LIMIT 20 OFFSET '.(($pagina - 1) * 20));
$s->execute([$u['id']]);
$filas = $s->fetchAll();
