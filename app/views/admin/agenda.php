<?php
require BASE_PATH.'/app/controllers/pages/admin/agenda.php';
abrir_pagina('Agenda y disponibilidad', 'agenda', ['agenda.css?v=1']);
encabezado_gestion('Agenda y disponibilidad', 'Visualiza horarios libres, ocupados y bloqueados por profesional y fecha.', $gestion);
ver_aviso();
$filtros = ['vista' => $vista, 'medico' => $mid, 'esp' => $eid];
?>
<form method="get" class="gestion-filtros"><label>Fecha<input type="date" name="fecha" value="<?= e($fecha) ?>" required></label><label>Vista<select name="vista"><?php foreach (['horarios' => 'Por horarios', 'dia' => 'Listado del día', 'semana' => 'Semana'] as $valor => $texto): ?><option value="<?= $valor ?>" <?= $vista === $valor ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach ?></select></label><?php if ($gestion): ?>
<label>Especialidad<?php campo_buscador('especialidad', 'esp', $eid, ['id' => 'filtro-especialidad', 'etiqueta' => 'Especialidad', 'placeholder' => 'Todas las especialidades', 'alcance' => 'agenda']); ?></label>
<label>Profesional<?php campo_buscador('medico', 'medico', $mid, ['id' => 'profesional', 'etiqueta' => 'Profesional', 'especialidad' => 'filtro-especialidad', 'alcance' => 'agenda', 'placeholder' => 'Todos los profesionales']); ?></label>
<?php else: ?><input type="hidden" name="medico" value="<?= $mid ?>"><?php endif ?>
<button class="boton boton-primario">Consultar agenda</button></form>
<div class="gestion-barra"><a href="?<?= e(http_build_query([...$filtros, 'fecha' => $d->modify($vista === 'semana' ? '-7 days' : '-1 day')->format('Y-m-d')])) ?>">← Anterior</a><strong><?= fecha_texto($desde->format('Y-m-d')) ?><?= $vista === 'semana' ? ' al '.fecha_texto($hasta->format('Y-m-d')) : '' ?></strong><a href="?<?= e(http_build_query([...$filtros, 'fecha' => $d->modify($vista === 'semana' ? '+7 days' : '+1 day')->format('Y-m-d')])) ?>">Siguiente →</a></div>
<div class="acciones-fila"><a class="boton boton-secundario" href="?<?= e(http_build_query([...$filtros, 'fecha' => date('Y-m-d')])) ?>">Hoy</a><a class="boton boton-secundario" href="?<?= e(http_build_query([...$filtros, 'fecha' => date('Y-m-d', strtotime('+1 day'))])) ?>">Mañana</a></div>
<?php if ($vista === 'horarios' && !$mid && $paginas_equipo > 1):?><nav class="gestion-paginacion" aria-label="Grupos de profesionales"><?php if ($equipo_pagina > 1): ?><a href="?<?= e(http_build_query([...$filtros,'fecha'=>$fecha,'equipo_pagina'=>$equipo_pagina-1])) ?>">Grupo anterior</a><?php endif ?>
<span>Grupo <?= $equipo_pagina ?> de <?= $paginas_equipo ?></span>
<?php if ($equipo_pagina < $paginas_equipo): ?><a href="?<?= e(http_build_query([...$filtros,'fecha'=>$fecha,'equipo_pagina'=>$equipo_pagina+1])) ?>">Grupo siguiente</a><?php endif ?></nav><?php endif ?>
<?php if (count($citas) > 500):?><p class="alert alert-info">Esta vista muestra las primeras 500 citas. Filtra por profesional para revisar el resto.</p><?php $citas = array_slice($citas, 0, 500);endif ?>
<?php if ($vista === 'horarios'): ?>
<?php require __DIR__.'/../shared/agenda_horarios.php'; ?>
<?php else: ?>
<?php if ($vista === 'semana'): $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']; ?><div class="agenda-semana" tabindex="0" role="region" aria-label="Agenda semanal, desplaza para ver todos los días"><div class="agenda-semana-grid"><?php for ($i = 0; $i < 7; $i++):$f = $desde->modify('+'.$i.' days')->format('Y-m-d'); ?><section><h2><?= $dias[$i] ?><span><?= fecha_texto($f) ?></span></h2><?php $n = 0;
    foreach ($citas as $c):if ($c['fecha_cita'] !== $f) {
        continue;
    }$n++; ?><a class="evento-agenda" href="comprobante.php?id=<?= (int)$c['id'] ?>"><strong><?= e(substr($c['hora_cita'], 0, 5)) ?> · <?= e($c['nombres']) ?></strong><span><?= e($c['especialidad']) ?></span><span><?= e($c['medico'] ?? 'Sin asignar') ?></span><?php etiqueta_estado($c['estado']); ?></a><?php endforeach;
    if (!$n): ?><p class="texto-ayuda">Sin citas</p><?php endif ?></section><?php endfor ?></div></div><?php endif ?>
<section class="gestion-tarjeta sin-relleno"><div class="gestion-panel-titulo"><h2>Detalle de atenciones</h2><span><?= count($citas) ?> citas</span></div><?php tabla_citas($citas); ?></section>
<?php endif ?>
<?php cerrar_pagina(); ?>
