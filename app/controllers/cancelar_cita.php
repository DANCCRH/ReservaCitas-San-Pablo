<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$u = exigir_usuario(['paciente'], true);
try {
    comprobar_csrf();
    cambiar_estado_cita(id_post('id'), 'cancelado', $u, texto_post('motivo', 250));
    responder_json(['ok' => true]);
} catch (DomainException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }responder_json(['error' => $e->getMessage()], http_response_code() >= 400 ? http_response_code() : 422);
} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }error_log($e);
    responder_json(['error' => 'No fue posible cancelar la cita.'], 500);
}
