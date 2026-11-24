<?php

/** Conexión PDO y utilidades comunes. Los valores locales mantienen XAMPP en 3307. */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}
date_default_timezone_set('America/Lima');
$config = is_file(BASE_PATH . '/config/config.local.php') ? require BASE_PATH . '/config/config.local.php' : [];
if (PHP_SAPI === 'cli' && is_file(BASE_PATH.'/config/maintenance.local.php')) {
    $config = array_replace($config, require BASE_PATH.'/config/maintenance.local.php');
}
$entorno = ['host' => 'DB_HOST','port' => 'DB_PORT','database' => 'DB_DATABASE','username' => 'DB_USERNAME','password' => 'DB_PASSWORD','app_url' => 'APP_URL','environment' => 'APP_ENV','storage_path' => 'APP_STORAGE'];
foreach ($entorno as $clave => $variable) {
    $valor = getenv($variable);
    if ($valor !== false) {
        $config[$clave] = $valor;
    }
}
foreach (['mail_transport' => 'MAIL_TRANSPORT','mail_from' => 'MAIL_FROM','smtp_host' => 'SMTP_HOST','smtp_port' => 'SMTP_PORT','smtp_tls' => 'SMTP_TLS','smtp_username' => 'SMTP_USERNAME','smtp_password' => 'SMTP_PASSWORD'] as $k => $variable) {
    $v = getenv($variable);
    if ($v !== false) {
        $config[$k] = $v;
    }
}
if (PHP_SAPI !== 'cli') {
    $GLOBALS['csp_nonce'] = base64_encode(random_bytes(18));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-".$GLOBALS['csp_nonce']."'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$host = $config['host'] ?? '127.0.0.1';
$puerto = (int) ($config['port'] ?? 3307);
$dbname = $config['database'] ?? 'reservacitas_db';
try {
    $conexion = new PDO(
        "mysql:host=$host;port=$puerto;dbname=$dbname;charset=utf8mb4",
        $config['username'] ?? 'root',
        $config['password'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $conexion->exec("SET time_zone = '-05:00'");
} catch (PDOException $ex) {
    error_log('Conexión: ' . $ex->getMessage());
    http_response_code(503);
    exit('El servicio no está disponible en este momento. Inténtalo nuevamente en unos minutos.');
}

/** Solo acepta entradas de texto; conserva apóstrofos y barras legítimas. */
function sanitizar($dato)
{
    return is_string($dato) ? trim($dato) : '';
}
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function iniciar_sesion_segura()
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true,
            'samesite' => 'Lax', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        session_start();
    }
}
function token_csrf()
{
    iniciar_sesion_segura();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_valido($valor)
{
    return is_string($valor) && $valor !== '' && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $valor);
}
/** Las respuestas privadas nunca se reutilizan después de cerrar sesión. */
function respuesta_privada()
{
    header('Cache-Control: no-store, private');
}
function responder_json($datos, $codigo = 200)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    respuesta_privada();
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function es_administrador($conexion)
{
    $u = usuario_actual();
    return $u && $u['rol'] === 'admin';
}
require_once __DIR__.'/../support/roles.php';
require_once __DIR__.'/../support/seguridad.php';
if (!function_exists('mb_strlen')) {
    function mb_strlen($texto)
    {
        return preg_match_all('/./us', (string) $texto);
    }
}

require_once __DIR__.'/../support/perfiles.php';
require_once __DIR__.'/../support/operacion.php';
require_once __DIR__.'/../support/formularios.php';
