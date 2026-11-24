<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $accion = texto_post('accion', 20);
        if ($accion === 'datos') {
            $nombre = texto_post('nombres', 150);
            $tel = telefono_normalizado(texto_post('telefono', 30));
            if (mb_strlen($nombre) < 5 || ($tel && !preg_match('/^\+?[0-9]{7,15}$/', $tel))) {
                throw new DomainException('Revisa tu nombre y teléfono.');
            }
            [$genero,$nacimiento] = validar_demografia();
            $conexion->beginTransaction();
            $conexion->prepare('UPDATE pacientes SET nombres=?,telefono=?,genero=?,fecha_nacimiento=? WHERE id=?')->execute([$nombre,$tel,$genero,$nacimiento,$u['id']]);
            $conexion->prepare('UPDATE medicos SET nombres=? WHERE usuario_id=?')->execute([$nombre,$u['id']]);
            auditar_evento('perfil', $u['id'], 'datos', 'Actualización de identidad y contacto');
            $conexion->commit();
            aviso('Tus datos se actualizaron correctamente.');
        } elseif ($accion === 'clave') {
            $actual = clave_post('actual');
            $nueva = clave_post('nueva');
            $confirmar = clave_post('confirmar');
            if (!limitar('cambio-clave:'.$u['id'], 5, 300)) {
                throw new DomainException('Espera unos minutos antes de volver a intentar.');
            }
            if (!password_verify($actual, $u['contrasena'])) {
                throw new DomainException('La contraseña actual no es correcta.');
            }
            if (!clave_nueva_valida($nueva) || $nueva !== $confirmar || password_verify($nueva, $u['contrasena'])) {
                throw new DomainException('Usa una contraseña diferente de al menos 8 caracteres, con letras y números, y repítela correctamente.');
            }
            $conexion->prepare('UPDATE pacientes SET contrasena=?,clave_temporal=0,sesion_version=sesion_version+1 WHERE id=?')->execute([password_hash($nueva, PASSWORD_DEFAULT),$u['id']]);
            $_SESSION['sesion_version'] = (int)$u['sesion_version'] + 1;
            session_regenerate_id(true);
            aviso('Contraseña actualizada. Las otras sesiones quedaron cerradas.');
        } else {
            throw new DomainException('Acción no válida.');
        }
        header('Location: mi_perfil.php', true, 303);
        exit;
    } catch (Throwable $e) {
        error_formulario($e, 'mi_perfil.php');
    }
}
