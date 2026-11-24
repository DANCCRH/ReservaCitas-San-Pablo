<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
iniciar_sesion_segura();
$token = sanitizar($_POST['token'] ?? $_GET['token'] ?? '');
$valido = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $s = $conexion->prepare('SELECT id FROM recuperaciones WHERE token_hash=? AND usado_en IS NULL AND vence_en>NOW()');
    $s->execute([hash('sha256', $token)]);
    $valido = (bool)$s->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        if (!$valido) {
            throw new DomainException('El enlace expiró o ya fue utilizado. Solicita uno nuevo.');
        }
        $clave = clave_post('nueva');
        if (!clave_nueva_valida($clave) || $clave !== clave_post('confirmar')) {
            throw new DomainException('Usa al menos 8 caracteres con letras y números y repite la misma contraseña.');
        }
        $conexion->beginTransaction();
        $s = $conexion->prepare('SELECT * FROM recuperaciones WHERE token_hash=? AND usado_en IS NULL AND vence_en>NOW() FOR UPDATE');
        $s->execute([hash('sha256', $token)]);
        $r = $s->fetch();
        if (!$r) {
            throw new DomainException('El enlace ya no está disponible.');
        }
        $conexion->prepare('UPDATE pacientes SET contrasena=?,clave_temporal=0,sesion_version=sesion_version+1 WHERE id=?')->execute([password_hash($clave, PASSWORD_DEFAULT),$r['paciente_id']]);
        $conexion->prepare('UPDATE recuperaciones SET usado_en=NOW() WHERE paciente_id=? AND usado_en IS NULL')->execute([$r['paciente_id']]);
        $conexion->commit();
        require_once BASE_PATH.'/app/views/shared/plantilla_resultado.php';
        mostrar_resultado(true, 'Contraseña actualizada', 'Ya puedes ingresar con tu nueva contraseña.', 'login.php', 'Ingresar');
    } catch (Throwable $e) {
        error_formulario($e, 'recuperar.php');
    }
}
