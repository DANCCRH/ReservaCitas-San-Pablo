<?php

/** Primera cuenta administrativa en una instalación vacía. JSON por STDIN, sin contraseñas en argumentos. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__.'/../app/config/conexion.php';
try {
    $d = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $nombre = sanitizar($d['nombres'] ?? null);
    $dni = sanitizar($d['dni'] ?? null);
    $correo = strtolower(sanitizar($d['correo'] ?? null));
    $clave = $d['clave'] ?? null;
    if (mb_strlen($nombre) < 5 || mb_strlen($nombre) > 150 || !preg_match('/^\d{8}$/', $dni) || !filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 100 || !clave_nueva_valida($clave)) {
        throw new DomainException('Revisa nombres, DNI, correo y contraseña de al menos 8 caracteres con letras y números.');
    }
    $conexion->beginTransaction();
    if ($conexion->query("SELECT COUNT(*) FROM pacientes WHERE rol='admin'")->fetchColumn()) {
        throw new DomainException('Ya existe un administrador. Gestiona las cuentas desde Usuarios y roles.');
    }
    $conexion->prepare("INSERT INTO pacientes(nombres,dni,correo,contrasena,rol) VALUES (?,?,?,?,'admin')")->execute([$nombre,$dni,$correo,password_hash($clave, PASSWORD_DEFAULT)]);
    $conexion->commit();
    echo "Primera cuenta administrativa creada.\n";
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    fwrite(STDERR, $e instanceof DomainException ? $e->getMessage()."\n" : "No fue posible crear la cuenta. Revisa el esquema o la existencia previa de DNI/correo.\n");
    exit(1);
}
