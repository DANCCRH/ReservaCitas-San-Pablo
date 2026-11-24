<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../views/shared/plantilla_resultado.php';
exigir_post();
iniciar_sesion_segura();
try {
    comprobar_csrf();
    $nombre = texto_post('nombre', 100);
    $correo = strtolower(texto_post('correo', 100));
    $telefono = telefono_normalizado(texto_post('telefono', 30));
    $asunto = texto_post('asunto', 120);
    $mensaje = texto_post('mensaje', 1000);
    if (mb_strlen($nombre) < 3 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($asunto) < 3 || mb_strlen($mensaje) < 10) {
        throw new DomainException('Revisa tu nombre, correo, asunto y mensaje. El mensaje debe tener al menos 10 caracteres.');
    }
    if ($telefono && !preg_match('/^\+?[0-9]{7,15}$/', $telefono)) {
        throw new DomainException('Ingresa un teléfono válido o deja ese campo vacío.');
    }
    if (!limitar('contacto:'.($_SERVER['REMOTE_ADDR'] ?? 'local'), 10, 600)) {
        http_response_code(429);
        throw new DomainException('Se recibieron varias consultas. Inténtalo más tarde.');
    }
    $conexion->beginTransaction();
    $s = $conexion->prepare('INSERT INTO clientes(nombres,correo,telefono) VALUES (?,?,?) ON DUPLICATE KEY UPDATE id_cliente=LAST_INSERT_ID(id_cliente)');
    $s->execute([$nombre,$correo,$telefono ?: null]);
    $cliente = (int)$conexion->lastInsertId();
    $s = $conexion->prepare('INSERT INTO consultas(id_cliente,asunto,mensaje,remitente_nombre,remitente_correo,remitente_telefono) VALUES (?,?,?,?,?,?)');
    $s->execute([$cliente,$asunto,$mensaje,$nombre,$correo,$telefono ?: null]);
    $conexion->commit();
    mostrar_resultado(true, '¡Mensaje enviado!', 'Gracias, '.e($nombre).'. Tu consulta quedó registrada para su revisión.', 'index.php', 'Volver al inicio');
} catch (Throwable $e) {
    error_formulario($e, 'contacto.php');
}
