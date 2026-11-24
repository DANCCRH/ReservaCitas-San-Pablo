<?php
/** Identidad interna de la ruta, compartida por URL antiguas y limpias. */
function ruta_actual(){return $GLOBALS['ruta_actual']??basename(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'index.php');}


/** Autenticación vigente, límites persistentes y utilidades de formularios. */
function usuario_actual()
{
    global $conexion;
    iniciar_sesion_segura();
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $ahora = time();
    global $config;
    $inactivo = (int)($config['session_idle'] ?? 1800);
    $maximo = (int)($config['session_max'] ?? 28800);
    if (($ahora - ($_SESSION['sesion_actividad'] ?? $ahora) > $inactivo) || ($ahora - ($_SESSION['sesion_inicio'] ?? $ahora) > $maximo)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['sesion_inicio'] ??= $ahora;
    if (!in_array(ruta_actual(), ['sesion.php','estado_atencion.php'], true)) {
        $_SESSION['sesion_actividad'] = $ahora;
    }
    $s = $conexion->prepare('SELECT p.*,a.contrasena,a.rol,a.activo,a.sesion_version,a.clave_temporal,a.correo_verificado FROM cuentas_acceso a JOIN pacientes p ON p.id=a.persona_id WHERE a.persona_id=?');
    $s->execute([$_SESSION['usuario_id']]);
    $u = $s->fetch();
    if (!$u || !(int)($u['activo'] ?? 1) || !array_key_exists($u['rol'], ROLES) || (int)($u['sesion_version'] ?? 0) !== (int)($_SESSION['sesion_version'] ?? 0)) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['usuario_nombre'] = $u['nombres'];
    $_SESSION['usuario_rol'] = $u['rol'];
    $cache = $u;
    return $u;
}
function exigir_usuario($admin = false, $json = false)
{
    global $config;
    $u = usuario_actual();
    $roles = is_array($admin) ? $admin : ($admin ? ['admin'] : array_keys(ROLES));
    if (!$u || !in_array($u['rol'], $roles, true)) {
        if ($json) {
            responder_json(['error' => $u ? 'Acceso restringido.' : 'Inicia sesión para continuar.'], $u ? 403 : 401);
        }
        if (!$u) {
            header('Location: login.php');
            exit;
        }
        http_response_code(403);
        require_once __DIR__.'/../views/shared/plantilla_resultado.php';
        mostrar_resultado(false, 'Acceso restringido', 'Tu cuenta no tiene permiso para ingresar a esta sección.', destino_rol($u), 'Volver a mi panel');
    }
    $ruta = ruta_actual();
    if ($u['clave_temporal'] && !in_array($ruta, ['mi_perfil.php','logout.php','sesion.php','token.php'], true)) {
        if ($json) {
            responder_json(['error' => 'Actualiza tu contraseña temporal desde Mi perfil.'], 403);
        }header('Location: mi_perfil.php?cambiar=1');
        exit;
    }
    if (($config['environment'] ?? 'local') === 'production' && !$u['correo_verificado'] && !in_array($ruta, ['verificar_correo.php','mi_perfil.php','logout.php','sesion.php','token.php'], true)) {
        if ($json) {
            responder_json(['error' => 'Verifica tu correo antes de continuar.'], 403);
        }header('Location: verificar_correo.php');
        exit;
    }
    respuesta_privada();
    return $u;
}
function exigir_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Método no permitido.');
    }
}
function comprobar_csrf()
{
    if (!csrf_valido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        throw new DomainException('El formulario expiró. Recarga la página.');
    }
}
function texto_post($clave, $max = 500)
{
    $v = sanitizar($_POST[$clave] ?? '');
    if (mb_strlen($v) > $max) {
        throw new DomainException('El texto supera la longitud permitida.');
    }return $v;
}
function id_post($clave)
{
    $id = filter_var($_POST[$clave] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        throw new DomainException('Selecciona un registro válido.');
    }return (int)$id;
}
function limitar($contexto, $maximo = 5, $segundos = 300, $sumar = true)
{
    global $conexion;
    $clave = hash('sha256', $contexto);
    $ahora = time();
    $conexion->prepare('DELETE FROM limites_acceso WHERE inicio < ?')->execute([$ahora - 86400]);
    $s = $conexion->prepare('SELECT cantidad,inicio FROM limites_acceso WHERE clave=?');
    $s->execute([$clave]);
    $r = $s->fetch();
    if ($r && (int)$r['inicio'] > $ahora - $segundos && (int)$r['cantidad'] >= $maximo) {
        return false;
    }
    if ($sumar) {
        $s = $conexion->prepare('INSERT INTO limites_acceso(clave,cantidad,inicio) VALUES (?,1,?) ON DUPLICATE KEY UPDATE cantidad=IF(inicio < ?,1,cantidad+1), inicio=IF(inicio < ?,VALUES(inicio),inicio)');
        $s->execute([$clave,$ahora,$ahora - $segundos,$ahora - $segundos]);
    }
    return true;
}
function clave_nueva_valida($clave)
{
    return is_string($clave) && !str_contains($clave, "\0") && strlen($clave) <= 72 && mb_strlen($clave) >= 8 && preg_match('/[A-Za-z]/', $clave) && preg_match('/[0-9]/', $clave);
}
function aviso($mensaje)
{
    $_SESSION['aviso'] = $mensaje;
}
function ver_aviso()
{
    if (!empty($_SESSION['aviso'])) {
        echo '<p class="alert alert-success" role="status">'.e($_SESSION['aviso']).'</p>';
        unset($_SESSION['aviso']);
    }
}
function error_formulario($ex, $retorno)
{
    global $conexion;
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $dominio = $ex instanceof DomainException;
    if (!$dominio) {
        error_log((string)$ex);
    }
    if (http_response_code() < 400) {
        http_response_code($dominio ? 422 : 500);
    }
    guardar_error_datos($retorno, $dominio ? $ex->getMessage() : 'No fue posible completar la operación.');
    if ($dominio && !in_array(http_response_code(), [403,404,429], true)) {
        header('Location: '.$retorno, true, 303);
        exit;
    }
    registro_operativo('error_aplicacion', ['tipo' => get_class($ex)]);
    require_once __DIR__.'/../views/shared/plantilla_resultado.php';
    mostrar_resultado(false, 'No se realizó el cambio', $dominio ? e($ex->getMessage()) : 'No fue posible completar la operación. Inténtalo nuevamente.', $retorno, 'Volver');
}
