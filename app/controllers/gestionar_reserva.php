<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$operador = exigir_usuario(['admin','recepcion']);
try {
    comprobar_csrf();
    $pid = id_post('paciente_id');
    $s = $conexion->prepare("SELECT * FROM pacientes WHERE id=? AND rol='paciente' AND activo=1");
    $s->execute([$pid]);
    $paciente = $s->fetch();
    if (!$paciente) {
        throw new DomainException('Selecciona un paciente activo.');
    }
    if (($_POST['confirmacion'] ?? '') !== 'on') {
        throw new DomainException('Revisa y confirma los datos de la solicitud.');
    }
    $id = guardar_reserva($paciente, [
        'medico_id' => id_post('medico_id'),'especialidad_id' => id_post('especialidad_id'),
        'telefono' => telefono_normalizado(texto_post('telefono', 30)),'fecha_cita' => texto_post('fecha_cita', 10),
        'hora_cita' => texto_post('hora_cita', 5),'comentario' => texto_post('comentario', 250),'motivo' => texto_post('motivo', 250)
    ], (int)($_POST['id'] ?? 0), $operador);
    aviso('La solicitud se registró a nombre del paciente.');
    header('Location: comprobante.php?id='.$id, true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e, 'reservar_paciente.php');
}
