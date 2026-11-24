<?php

/** Servidor local de desarrollo: php -S 127.0.0.1:9084 bin/router.php */
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~^/(css|js|imagenes|vendor)/|^/(favicon\.ico|site\.webmanifest)$~', $path)) {
    $file = realpath($root.'/public'.$path);
    if (!$file || !is_file($file) || !str_starts_with(strtolower(str_replace('\\', '/', $file)), strtolower(str_replace('\\', '/', $root.'/public/')))) {
        http_response_code(404);
        exit;
    }
    $types = ['css' => 'text/css','js' => 'application/javascript','json' => 'application/json','webmanifest' => 'application/manifest+json','svg' => 'image/svg+xml','png' => 'image/png','jpg' => 'image/jpeg','jpeg' => 'image/jpeg','webp' => 'image/webp','gif' => 'image/gif','ico' => 'image/x-icon','woff2' => 'font/woff2','woff' => 'font/woff','txt' => 'text/plain'];
    header('Content-Type: '.($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
    readfile($file);
    return;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root.'/index.php';
