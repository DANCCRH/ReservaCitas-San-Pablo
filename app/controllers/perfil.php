<?php

require_once __DIR__.'/../config/conexion.php';
try {
    $u = usuario_actual();
    if (!$u) {
        responder_json(['logueado' => false]);
    }
    $s = $conexion->prepare('SELECT COUNT(*) FROM notificaciones WHERE paciente_id=? AND leido_en IS NULL');
    $s->execute([$u['id']]);
    responder_json(['logueado' => true,'nombres' => $u['nombres'],'dni' => $u['dni'],'correo' => $u['correo'],'telefono' => $u['telefono'],'rol' => $u['rol'],'destino' => destino_rol($u),'notificaciones' => (int)$s->fetchColumn()]);
} catch (Throwable $e) {
    error_log($e);
    responder_json(['error' => 'No fue posible comprobar tu cuenta.'], 503);
}
