<?php

function control_llegada_recepcion($cita)
{
    $hoy = date('Y-m-d');
    if ($cita['estado'] !== 'confirmado' || $cita['fecha_cita'] < $hoy || $cita['llegada_en']) {
        return;
    }
    $id = (int)$cita['id'];
    $esHoy = $cita['fecha_cita'] === $hoy;
    ?>
    <form action="admin_acciones.php" method="post" class="d-grid gap-1">
      <?php campo_csrf(); ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="accion" value="llegada">
      <button type="submit" class="boton boton-primario" aria-describedby="fecha-llegada-<?= $id ?>"<?= $esHoy ? '' : ' disabled' ?>>Registrar llegada</button>
      <small id="fecha-llegada-<?= $id ?>"><?= $esHoy
          ? 'Registra la llegada cuando el paciente se presente en recepción.'
          : 'Se habilita el '.e(fecha_texto($cita['fecha_cita'])).', cuando el paciente llegue a recepción.' ?></small>
    </form>
    <?php
}

function controles_recepcion($cita, $acciones)
{
    if (!in_array($cita['estado'], ['confirmado', 'pendiente'], true)) {
        return;
    }
    $id = (int)$cita['id'];
    $reprogramable = strtotime($cita['fecha_cita'].' '.$cita['hora_cita']) > time();
    ?>
    <div class="d-grid gap-2">
      <?php control_llegada_recepcion($cita); ?>
      <?php if ($cita['llegada_en'] && !$cita['inicio_atencion']): ?>
        <small class="llegada-registrada"><?= $cita['llamado_en'] ? 'Paciente llamado a consultorio.' : 'Paciente en sala de espera.' ?></small>
      <?php endif ?>
      <?php if (isset($acciones['confirmado'])): ?>
        <form action="admin_acciones.php" method="post" data-confirmar="Se confirmará la cita de este paciente.">
          <?php campo_csrf(); ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="accion" value="confirmado">
          <button class="boton boton-primario">Confirmar cita</button>
        </form>
      <?php endif ?>
      <?php if ($reprogramable): ?>
        <a class="boton boton-secundario" href="reservar_paciente.php?paciente=<?= (int)$cita['paciente_id'] ?>&amp;cita=<?= $id ?>">Reprogramar</a>
      <?php endif ?>
      <?php foreach (['rechazado', 'cancelado'] as $accion):
          if (!isset($acciones[$accion])) { continue; }
          $motivoId = 'motivo-'.$accion.'-'.$id;
          ?>
        <details class="acciones-detalle mb-0">
          <summary><?= e($acciones[$accion]) ?></summary>
          <form action="admin_acciones.php" method="post" class="form-gestion" data-confirmar="<?= $accion === 'cancelado' ? 'Se cancelará la cita y se registrará el motivo.' : 'Se rechazará la solicitud y se registrará el motivo.' ?>">
            <?php campo_csrf(); ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="accion" value="<?= $accion ?>">
            <label for="<?= $motivoId ?>">Motivo</label>
            <textarea id="<?= $motivoId ?>" name="motivo" minlength="5" maxlength="250" rows="2" required></textarea>
            <button class="boton boton-secundario"><?= $accion === 'cancelado' ? 'Confirmar cancelación' : 'Confirmar rechazo' ?></button>
          </form>
        </details>
      <?php endforeach ?>
    </div>
    <?php
}
