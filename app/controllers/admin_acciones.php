<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$u = exigir_usuario(['admin','recepcion','medico','asistencial']);
try {
    comprobar_csrf();
    if (texto_post('accion', 20) === 'llegada') {
        registrar_llegada(id_post('id'), $u);
    } else {
        cambiar_estado_cita(id_post('id'), texto_post('accion', 20), $u, texto_post('motivo', 250));
    }
    aviso('El estado de la cita se actualizó correctamente.');
    header('Location: '.destino_rol($u), true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e, destino_rol($u));
}
