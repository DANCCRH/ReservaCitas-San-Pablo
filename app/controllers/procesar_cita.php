<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../views/shared/plantilla_resultado.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$u = exigir_usuario(['paciente']);
try {
    comprobar_csrf();
    $paciente = paciente_autorizado($u, (int)($_POST['paciente_id'] ?? $u['id']));
    if (($_POST['confirmacion'] ?? '') !== 'on') {
        throw new DomainException('Acepta las condiciones de la solicitud.');
    }
    $id = guardar_reserva($paciente, ['medico_id' => id_post('medico_id'),'especialidad_id' => id_post('especialidad_id'),'telefono' => telefono_normalizado(texto_post('telefono', 30)),'fecha_cita' => texto_post('fecha_cita', 10),'hora_cita' => texto_post('hora_cita', 5),'comentario' => texto_post('comentario', 250)], 0, $u);
    $_SESSION['cita_guardada'] = ['id' => $id];
    header('Location: comprobante.php?id='.$id.'&nueva=1', true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e, 'reserva.php');
}
