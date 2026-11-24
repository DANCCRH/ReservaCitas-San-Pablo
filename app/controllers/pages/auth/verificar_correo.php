<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
require_once BASE_PATH.'/app/services/verificacion.php';
iniciar_sesion_segura();
$mensaje = '';
$token = sanitizar($_GET['token'] ?? '');
if (preg_match('/^[a-f0-9]{64}$/D', $token)) {
    $conexion->beginTransaction();
    $q = $conexion->prepare('SELECT * FROM verificaciones_correo WHERE token_hash=? AND usado_en IS NULL AND expira>NOW() FOR UPDATE');
    $q->execute([hash('sha256', $token)]);
    $r = $q->fetch();
    if ($r) {
        $conexion->prepare('UPDATE pacientes SET correo_verificado=1 WHERE id=?')->execute([$r['usuario_id']]);
        $conexion->prepare('UPDATE verificaciones_correo SET usado_en=NOW() WHERE id=?')->execute([$r['id']]);
        $mensaje = 'Correo verificado correctamente.';
    } else {
        $mensaje = 'El enlace expiró o ya fue utilizado.';
    }$conexion->commit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $u = exigir_usuario();
        if (!limitar('verificacion:'.$u['id'], 3, 900)) {
            throw new DomainException('Espera antes de solicitar otro enlace.');
        }enviar_verificacion($u['id'], $u['correo']);
        $mensaje = 'Se preparó un nuevo enlace de verificación.';
    } catch (Throwable $e) {
        error_formulario($e, 'verificar_correo.php');
    }
}
