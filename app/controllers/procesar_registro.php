<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../views/shared/plantilla_resultado.php';
exigir_post();
iniciar_sesion_segura();
try {
    comprobar_csrf();
    if (!limitar('registro:'.($_SERVER['REMOTE_ADDR'] ?? 'local'), 5, 3600)) {
        http_response_code(429);
        throw new DomainException('Se alcanzó el límite de registros. Espera antes de volver a intentar.');
    }
    $nombre = texto_post('nombre_completo', 150);
    $dni = strtoupper(texto_post('dni_reg', 20));
    $tipo = texto_post('tipo_documento', 12) ?: 'DNI';
    $correo = strtolower(texto_post('correo_reg', 100));
    $pass = clave_post('pass_reg');
    $conf = clave_post('pass_conf');
    if (mb_strlen($nombre) < 5 || !documento_valido($tipo, $dni) || !filter_var($correo, FILTER_VALIDATE_EMAIL) || !clave_nueva_valida($pass) || $pass !== $conf || !isset($_POST['terminos'])) {
        throw new DomainException('Revisa nombre, documento, correo y contraseña. Acepta los términos y repite exactamente tu clave.');
    }
    $verificado = ($config['environment'] ?? 'local') === 'production' ? 0 : 1;
    $conexion->prepare("INSERT INTO pacientes(nombres,dni,tipo_documento,correo,contrasena,correo_verificado) VALUES(?,?,?,?,?,?)")->execute([$nombre,$dni,$tipo,$correo,password_hash($pass, PASSWORD_DEFAULT),$verificado]);
    $id = (int)$conexion->lastInsertId();
    if (!$verificado) {
        require_once __DIR__.'/../services/verificacion.php';
        enviar_verificacion($id, $correo);
    }
    mostrar_resultado(true, 'Cuenta creada', 'Ya puedes iniciar sesión.'.(!$verificado ? ' Revisa tu correo para verificar tu cuenta.' : ''), 'login.php', 'Ir a iniciar sesión');
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) == 1062) {
        $e = new DomainException('El documento o el correo ya están registrados. Ingresa a tu cuenta o consulta con recepción.');
    }error_formulario($e, 'registro.php');
} catch (Throwable $e) {
    error_formulario($e,'registro.php');
}
