<?php

/** Elimina tanto la sesión del servidor como su cookie. */
require_once __DIR__.'/../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada();
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $cookie['path'],
    'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
header('Location: login.php', true, 303);
exit;
