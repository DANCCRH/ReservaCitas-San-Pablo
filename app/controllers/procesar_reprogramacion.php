<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$u = exigir_usuario(['paciente']);
try {
    comprobar_csrf();
    $paciente = paciente_autorizado($u, (int)($_POST['paciente_id'] ?? $u['id']));
    $id = id_post('id');
    if (($_POST['confirmacion'] ?? '') !== 'on') {
        throw new DomainException('Revisa y acepta los datos de tu solicitud.');
    }
    guardar_reserva($paciente, ['medico_id' => id_post('medico_id'),'especialidad_id' => id_post('especialidad_id'),'telefono' => telefono_normalizado(texto_post('telefono', 30)),'fecha_cita' => texto_post('fecha_cita', 10),'hora_cita' => texto_post('hora_cita', 5),'comentario' => texto_post('comentario', 250),'motivo' => texto_post('motivo', 250)], $id, $u);
    aviso('Tu cita fue reprogramada y está pendiente de confirmación.');
    header('Location: comprobante.php?id='.$id, true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e, 'reserva.php?cita='.(int)($_POST['id'] ?? 0));
}
