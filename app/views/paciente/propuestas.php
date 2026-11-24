<?php
require BASE_PATH.'/app/controllers/pages/paciente/propuestas.php';
abrir_pagina('Propuestas de cambio');
encabezado_gestion('Propuestas de cambio', 'Tu cita original se mantiene hasta que aceptes una alternativa disponible.');
ver_aviso();
if (!$propuestas) {
    echo '<p>No tienes propuestas pendientes.</p>';
}foreach ($propuestas as $p):?>
<article class="gestion-tarjeta"><h2><?= e(codigo_cita($p['cita_id']).' · '.$p['paciente']) ?></h2><p>Reserva actual: <?= fecha_texto($p['fecha_cita']) ?> a las <?= e(substr($p['hora_cita'], 0, 5)) ?>.</p><p><strong>Propuesta: <?= fecha_texto($p['fecha']) ?> a las <?= e(substr($p['hora'], 0, 5)) ?> con <?= e($p['medico']) ?>.</strong></p><p><?= e($p['motivo']) ?></p><form method="post" action="operar.php" class="acciones-fila"><?php campo_csrf();?><input type="hidden" name="id" value="<?= $p['id'] ?>"><button name="accion" value="aceptar_propuesta" class="boton boton-primario">Aceptar propuesta</button><button name="accion" value="rechazar_propuesta" class="boton boton-secundario">Conservar mi cita</button></form></article>
<?php endforeach;
cerrar_pagina(); ?>
