<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../views/shared/plantilla_resultado.php';
exigir_post();
iniciar_sesion_segura();
try {
    comprobar_csrf();
    $correo = strtolower(texto_post('correo_login', 100));
    if (in_array($correo, ['paciente', 'medico'], true)) {
        $archivoAlias = BASE_PATH.'/config/alias_aula.local.php';
        $alias = is_file($archivoAlias) ? require $archivoAlias : [];
        $entrada = $alias[$correo] ?? null;
        $prueba = texto_post('alias_aula', 64);
        if ($entrada && preg_match('/^[a-f0-9]{64}$/D', $prueba)
            && hash_equals($entrada['huella'], hash('sha256', $prueba))) {
            $correo = $entrada['correo'];
        }
    }
    $clave = clave_post('contrasena_login');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || !$clave) {
        throw new DomainException('Ingresa un correo y una contraseña válidos.');
    }
    if (!limitar('login:'.$ip.':'.$correo, 5, 300, false) || !limitar('login-ip:'.$ip, 30, 900, false)) {
        http_response_code(429);
        throw new DomainException('Demasiados intentos. Espera unos minutos antes de volver a ingresar.');
    }
    $s = $conexion->prepare('SELECT p.*,a.contrasena,a.rol,a.activo,a.sesion_version,a.clave_temporal,a.correo_verificado FROM cuentas_acceso a JOIN pacientes p ON p.id=a.persona_id WHERE a.correo=? LIMIT 1');
    $s->execute([$correo]);
    $u = $s->fetch();
    if (!$u || !$u['activo'] || !password_verify($clave, $u['contrasena'])) {
        limitar('login:'.$ip.':'.$correo);
        limitar('login-ip:'.$ip, 30, 900);
        throw new DomainException('Correo o contraseña incorrectos.');
    }
    $conexion->prepare('DELETE FROM limites_acceso WHERE clave=?')->execute([hash('sha256', 'login:'.$ip.':'.$correo)]);
    $continuar_previo = $_SESSION['continuar_reserva'] ?? null;
    session_regenerate_id(true);
    $_SESSION = ['usuario_id' => (int)$u['id'],'usuario_nombre' => $u['nombres'],'usuario_rol' => $u['rol'],'sesion_version' => (int)$u['sesion_version']];
    if ($continuar_previo) {
        $_SESSION['continuar_reserva'] = $continuar_previo;
    }
    $_SESSION['sesion_inicio'] = time();
    $_SESSION['sesion_actividad'] = time();
    token_csrf();
    if ($u['clave_temporal']) {
        header('Location: mi_perfil.php?cambiar=1', true, 303);
        exit;
    }
    if (($config['environment'] ?? 'local') === 'production' && !$u['correo_verificado']) {
        header('Location: verificar_correo.php', true, 303);
        exit;
    }
    $continuar = $_SESSION['continuar_reserva'] ?? null;
    if ($continuar && $u['rol'] === 'paciente') {
        unset($_SESSION['continuar_reserva']);
        header('Location: reserva.php?'.http_build_query($continuar), true, 303);
        exit;
    }
    unset($_SESSION['formulario_anterior']);
    mostrar_resultado(true, '¡Ingreso exitoso!', 'Bienvenido, '.e($u['nombres']).'.', destino_rol($u), $u['rol'] === 'paciente' ? 'Ir a mis solicitudes' : 'Ir a mi panel');
} catch (Throwable $e) {
    error_formulario($e, 'login.php');
}
