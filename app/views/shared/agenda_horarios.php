<?php
/** Recibe únicamente profesionales autorizados desde agenda.php. */
if (!isset($agenda_horas, $u)) {
    http_response_code(403);
    exit;
}
$conteos_horas = ['libre' => 0, 'ocupado' => 0, 'bloqueado' => 0, 'pasado' => 0, 'inactivo' => 0];
foreach ($agenda_horas as $profesional) {
    foreach ($profesional['espacios'] as $espacio) {
        $conteos_horas[$espacio['tipo']]++;
    }
}
?>
<section class="horas-introduccion">
 <div><h2>Horarios del día</h2><p>Abre una cita ocupada o selecciona un espacio libre para reservar.</p></div>
 <button class="boton boton-secundario" type="button" data-imprimir><i class="fas fa-print" aria-hidden="true"></i> Imprimir horario</button>
</section>
<div class="horas-resumen" role="group" aria-label="Resumen de horarios">
 <?php foreach (['libre' => 'Libres', 'ocupado' => 'Ocupados', 'bloqueado' => 'Bloqueados', 'pasado' => 'Pasados o no habilitados'] as $tipo => $nombre): ?>
 <div class="horas-indicador <?= $tipo ?>"><span><?= $nombre ?></span><strong><?= $conteos_horas[$tipo] + ($tipo === 'pasado' ? $conteos_horas['inactivo'] : 0) ?></strong></div>
 <?php endforeach ?>
</div>
<p class="horas-ayuda">Una cita ocupa todo su intervalo, aunque dure más que un espacio de la agenda. Canceladas y rechazadas liberan el horario. La disponibilidad se comprueba nuevamente al guardar.</p>
<?php if (!$agenda_horas): ?><section class="gestion-vacio"><h2>No hay profesionales para este filtro.</h2><p>Elige otro profesional.</p></section><?php endif ?>
<div class="horas-profesionales <?= count($agenda_horas) === 1 ? 'un-profesional' : '' ?>">
<?php foreach ($agenda_horas as $a):$m = $a['medico']; ?>
 <section class="horas-profesional" aria-labelledby="medico-horas-<?= (int)$m['id'] ?>">
  <div class="horas-cabecera"><div><span class="eyebrow"><?= e($m['especialidad']) ?></span><h3 id="medico-horas-<?= (int)$m['id'] ?>"><?= e($m['nombres']) ?></h3><p><?php if ($a['turnos']): ?>Turnos: <?= e(implode(' · ', array_map(fn ($t) => substr($t['inicio'], 0, 5).'–'.substr($t['fin'], 0, 5).' ('.$t['duracion'].' min)', $a['turnos']))) ?><?php else: ?>Sin turno configurado para este día.<?php endif ?></p></div><span class="horas-fecha"><?= fecha_texto($fecha) ?></span></div>
  <?php if (!$m['activo'] || !$m['especialidad_activa']): ?><p class="horas-advertencia">Este profesional o su especialidad no admite nuevas reservas. Las citas registradas se conservan.</p><?php endif ?>
  <div class="horas-espacios">
  <?php foreach ($a['espacios'] as $e):$tipo = $e['tipo']; ?>
   <div class="hora-espacio <?= $tipo ?>" data-horario="<?= e($e['hora']) ?>" data-tipo="<?= $tipo ?>">
    <div class="hora-inicio"><strong><?= e($e['hora']) ?><span>–<?= e($e['fin']) ?></span></strong><span><?= (int)$e['duracion'] ?> min</span></div>
    <?php if ($tipo === 'libre'): ?>
     <span class="hora-etiqueta"><i class="far fa-circle-check" aria-hidden="true"></i> Libre</span>
     <?php if (in_array($u['rol'], ['admin', 'recepcion'], true)): ?><a class="hora-accion" href="reservar_paciente.php?<?= e(http_build_query(['esp' => $m['especialidad_id'], 'medico' => $m['id'], 'fecha' => $fecha, 'hora' => $e['hora']])) ?>">Reservar este horario <i class="fas fa-arrow-right" aria-hidden="true"></i></a><?php else: ?><p>Disponible para una nueva reserva.</p><?php endif ?>
    <?php elseif ($tipo === 'ocupado'): ?>
     <span class="hora-etiqueta"><i class="fas fa-calendar-check" aria-hidden="true"></i> Ocupado</span>
     <?php foreach ($e['citas'] as $c): ?><a class="hora-cita" href="comprobante.php?id=<?= (int)$c['id'] ?>"><strong><?= e($c['paciente']) ?></strong><span><?= e(codigo_cita($c['id'])) ?> · <?= e(ESTADOS_CITA[$c['estado']]) ?> · <?= e(substr($c['hora_cita'], 0, 5)) ?> (<?= (int)$c['duracion'] ?> min)</span></a><?php endforeach ?>
    <?php elseif ($tipo === 'bloqueado'): ?>
     <span class="hora-etiqueta"><i class="fas fa-lock" aria-hidden="true"></i> Bloqueado</span><p><?= e(implode(' · ', array_unique($e['motivos']))) ?></p>
    <?php elseif ($tipo === 'inactivo'): ?>
     <span class="hora-etiqueta">No habilitado</span><p>Profesional o especialidad inactivos.</p>
    <?php else: ?>
     <span class="hora-etiqueta">Sin reserva</span><p><?= $fecha < date('Y-m-d') || $e['desde'] <= time() ? 'El horario ya pasó.' : 'Fuera del plazo de reserva.' ?></p>
    <?php endif ?>
   </div>
  <?php endforeach ?>
  </div>
  <?php if (!$a['espacios']): ?><div class="horas-sin-turno"><i class="far fa-calendar" aria-hidden="true"></i><p>No hay espacios de atención en este día.</p></div><?php endif ?>
  <?php if ($a['fuera_turno']): ?><div class="horas-advertencia"><strong>Citas registradas fuera del turno actual</strong><p>Revisa la configuración o coordina su reprogramación.</p><?php foreach ($a['fuera_turno'] as $c): ?><a href="comprobante.php?id=<?= (int)$c['id'] ?>"><?= e(substr($c['hora_cita'], 0, 5).' · '.codigo_cita($c['id']).' · '.$c['paciente']) ?></a><?php endforeach ?></div><?php endif ?>
 </section>
<?php endforeach ?>
</div>
