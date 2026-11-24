<?php

/** Acciones del profesional asignado para una solicitud pendiente o confirmada. */
function controles_atencion_medica($cita)
{
    $ahora = time();
    $hoy = date('Y-m-d', $ahora);
    $fecha = $cita['fecha_cita'];
    $programada = strtotime($fecha.' '.$cita['hora_cita']);
    $cuando = date('d/m/Y', $programada).' a las '.date('H:i', $programada);
    $confirmada = $cita['estado'] === 'confirmado';
    $esHoy = $fecha === $hoy;
    $llego = !empty($cita['llegada_en']);
    $iniciada = !empty($cita['inicio_atencion']);
    $enSala = $confirmada && $esHoy && $llego && !$iniciada;
    $id = (int)$cita['id'];

    if (!$confirmada) {
        $mensaje = 'Pendiente de confirmación por recepción o administración. Atención programada para el '.$cuando.'.';
    } elseif ($fecha > $hoy) {
        $mensaje = 'Recepción registrará la llegada del paciente el '.date('d/m/Y', $programada).'. Podrás iniciar la atención desde las '.date('H:i', $programada).', una vez registrada la llegada.';
    } elseif ($iniciada) {
        $mensaje = 'Atención iniciada a las '.date('H:i', strtotime($cita['inicio_atencion'])).'. Al terminar, pulsa «Marcar atendida».';
    } elseif ($fecha < $hoy) {
        $mensaje = 'La fecha programada ya pasó. Usa «Gestionar cita» para registrar la inasistencia o justificar una atención anterior.';
    } elseif ($ahora < $programada) {
        $mensaje = ($llego ? 'El paciente está en sala de espera. Puedes llamarlo por turno.' : 'Pendiente de llegada en recepción.').' La atención comienza desde las '.date('H:i', $programada).'.';
    } else {
        $mensaje = $llego ? 'El paciente está en sala de espera. Puedes llamarlo e iniciar la atención.' : 'Pendiente de llegada en recepción. Podrás llamar al paciente cuando se registre su llegada.';
    }

    // Reconsultar al cambiar de día o al llegar la hora programada.
    $cambio = 0;
    if ($confirmada && $fecha > $hoy) {
        $cambio = strtotime($fecha);
    } elseif ($confirmada && $esHoy) {
        $cambio = $ahora < $programada ? $programada : strtotime('tomorrow', $ahora);
    }
    $controles = [
        [$cita['llamado_en'] ? 'Volver a llamar' : 'Llamar por turno', 'agenda_acciones.php', 'llamar', $enSala],
        ['Iniciar atención', 'operar.php', 'inicio_atencion', $enSala && $ahora >= $programada],
        ['Marcar atendida', 'admin_acciones.php', 'atendido', $confirmada && $iniciada && $ahora >= $programada],
    ];
    ?>
    <p class="ayuda-atencion-medica" id="ayuda-control-<?= $id ?>"><?= e($mensaje) ?></p>
    <div class="controles-atencion-medica" data-ahora="<?= $ahora ?>" data-cambio="<?= $cambio ?>"
      <?php if ($esHoy && !$llego): ?>data-cita="<?= $id ?>" data-espera="<?= e($cita['estado'].'@'.$cita['hora_cita']) ?>"<?php endif ?>>
      <?php foreach ($controles as [$texto, $destino, $accion, $habilitada]): ?>
        <form method="post" action="<?= $destino ?>"<?= $accion === 'atendido' ? ' data-confirmar="Se registrará el fin de la atención de esta cita."' : '' ?>>
          <?php campo_csrf(); ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="accion" value="<?= $accion ?>">
          <button type="submit" aria-describedby="ayuda-control-<?= $id ?>"<?= $habilitada ? '' : ' disabled' ?>><?= e($texto) ?></button>
        </form>
      <?php endforeach ?>
    </div>
    <?php
}
