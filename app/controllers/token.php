<?php

/** Devuelve el token CSRF de la sesión para los formularios del navegador. */
require_once __DIR__.'/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['csrf' => token_csrf()]);
