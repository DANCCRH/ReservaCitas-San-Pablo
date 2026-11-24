<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
require_once BASE_PATH.'/app/services/correo.php';
iniciar_sesion_segura();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $correo = strtolower(texto_post('correo', 100));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Ingresa un correo válido.');
        }
        if (!limitar('recuperar:'.($_SERVER['REMOTE_ADDR'] ?? 'local'), 5, 900)) {
            http_response_code(429);
            throw new DomainException('Espera unos minutos antes de solicitar otro enlace.');
        }
        $s = $conexion->prepare('SELECT persona_id FROM cuentas_acceso WHERE correo=? AND activo=1');
        $s->execute([$correo]);
        $id = $s->fetchColumn();
        if ($id) {
            $token = bin2hex(random_bytes(32));
            $conexion->beginTransaction();
            $conexion->prepare('UPDATE recuperaciones SET usado_en=NOW() WHERE paciente_id=? AND usado_en IS NULL')->execute([$id]);
            $conexion->prepare('INSERT INTO recuperaciones(paciente_id,token_hash,vence_en) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))')->execute([$id,hash('sha256', $token)]);
            $base = rtrim($config['app_url'] ?? 'http://localhost/ReservaCitas2', '/');
            if (!filter_var($base, FILTER_VALIDATE_URL) || !in_array(parse_url($base, PHP_URL_SCHEME), ['http','https'], true)) {
                throw new RuntimeException('Configura APP_URL.');
            }
            preparar_correo($correo, 'Recuperación de acceso a ReservaCitas', "Para establecer una nueva contraseña, abre este enlace en los próximos 30 minutos:\n".$base.'/restablecer.php?token='.$token."\n\nSi no solicitaste este cambio, ignora el mensaje.");
            $conexion->commit();
        }
        aviso('Si el correo corresponde a una cuenta, se prepararon las instrucciones de recuperación.');
        header('Location: recuperar.php', true, 303);
        exit;
    } catch (Throwable $e) {
        error_formulario($e, 'recuperar.php');
    }
}
