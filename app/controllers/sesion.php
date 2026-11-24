<?php

require_once __DIR__.'/../config/conexion.php';
$u = usuario_actual();
if (!$u) {
    responder_json(['activa' => false], 401);
}if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $_SESSION['sesion_actividad'] = time();
    } catch (Throwable $e) {
        responder_json(['error' => 'Formulario expirado.'], 403);
    }
}
$idle = (int)($config['session_idle'] ?? 1800);
$max = (int)($config['session_max'] ?? 28800);
responder_json(['activa' => true,'segundos' => max(0, min($idle - (time() - $_SESSION['sesion_actividad']), $max - (time() - $_SESSION['sesion_inicio']))),'csrf' => token_csrf()]);
