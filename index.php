<?php

/** Punto único de entrada HTTP. Los archivos de aplicación no son públicos. */
define('BASE_PATH', __DIR__);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base && str_starts_with($path, $base.'/')) {
    $path = substr($path, strlen($base) + 1);
} else {
    $path = ltrim($path, '/');
}
if ($path === '') {
    $path = 'index.php';
}
if (preg_match('/^(index|especialidades|servicios|nosotros|contacto|reserva|login|registro|galeria)\.html$/', $path, $m)) {
    header('Location: '.$m[1].'.php'.(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : ''), true, 302);
    exit;
}
$routes = require BASE_PATH.'/app/routes.php';
require_once BASE_PATH.'/app/support/rutas_limpias.php';
$aliases=preparar_rutas_limpias($routes,$base);
if(isset($aliases[$path]))$path=$aliases[$path];
$GLOBALS['ruta_actual']=$path;
if(in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)&&isset($routes[$path])&&preg_match('/\.php$/',parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)??'')){
    $canonical=url_limpia($path.(!empty($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:''));
    header('Location: '.$canonical,true,301);exit;
}
if (!isset($routes[$path])) {
    http_response_code(preg_match('/\.(sql|md|log|ini|env|zip)$|^(config|app|bin|database|docs|herramientas|resources|tests)\//', $path) ? 403 : 404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Página no disponible</title><h1>Página no disponible</h1><p><a href="index.php">Volver al inicio</a></p></html>';
    exit;
}
try { require BASE_PATH.'/'.$routes[$path]; }
catch(Throwable $ex) {
    if(isset($conexion)&&$conexion->inTransaction())$conexion->rollBack();
    if(http_response_code()<400)http_response_code($ex instanceof DomainException?422:500);
    if(!function_exists('mostrar_resultado'))require_once BASE_PATH.'/app/views/shared/plantilla_resultado.php';
    if(!($ex instanceof DomainException))error_log('Error de página: '.get_class($ex));
    mostrar_resultado(false,'No se pudo abrir esta sección',$ex instanceof DomainException?e($ex->getMessage()):'Inténtalo nuevamente en unos minutos.','index.php','Volver al inicio');
}
