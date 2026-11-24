<?php

require_once __DIR__.'/correo.php';
function enviar_verificacion($id, $correo)
{
    global $conexion,$config;
    $token = bin2hex(random_bytes(32));
    $conexion->prepare('UPDATE verificaciones_correo SET usado_en=NOW() WHERE usuario_id=? AND usado_en IS NULL')->execute([$id]);
    $conexion->prepare('INSERT INTO verificaciones_correo(usuario_id,token_hash,expira) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))')->execute([$id,hash('sha256', $token)]);
    preparar_correo($correo, 'Verifica tu cuenta de ReservaCitas', 'Abre este enlace personal en las próximas 24 horas: '.rtrim($config['app_url'] ?? '', '/').'/verificar_correo.php?token='.$token);
}
