<?php
require BASE_PATH.'/app/controllers/pages/paciente/notificaciones.php';
abrir_pagina('Notificaciones');
encabezado_gestion('Notificaciones', 'Recibe las novedades sobre tus solicitudes y cambios de horario.');
?>
<div class="gestion-barra"><p><?= $total ?> notificaciones</p><form method="post" data-csrf><?php campo_csrf(); ?><button class="boton boton-secundario">Marcar todas como leídas</button></form></div>
<?php if (!$filas): ?><div class="gestion-vacio"><i class="fas fa-bell" aria-hidden="true"></i><h2>No tienes notificaciones</h2><p>Las novedades de tus citas aparecerán aquí.</p></div><?php endif ?>
<div class="notificaciones-lista"><?php foreach ($filas as $n): ?><article class="gestion-tarjeta notificacion-fila <?= !$n['leido_en'] ? 'sin-leer' : '' ?>"><div><small><?= e(date('d/m/Y H:i', strtotime($n['creado_en']))) ?> · <?= $n['leido_en'] ? 'Leída' : 'Nueva' ?></small><h2><?= e($n['titulo']) ?></h2><p><?= e($n['mensaje']) ?></p><a href="<?= e($n['enlace']) ?>">Ver solicitud</a></div><?php if (!$n['leido_en']): ?><form method="post" data-csrf><?php campo_csrf(); ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="boton boton-secundario">Marcar leída</button></form><?php endif ?></article><?php endforeach ?></div>
<nav class="gestion-paginacion" aria-label="Páginas de notificaciones"><?php if ($pagina > 1): ?><a href="?pagina=<?= $pagina - 1 ?>">Anterior</a><?php endif ?><span>Página <?= $pagina ?> de <?= $paginas ?></span><?php if ($pagina < $paginas): ?><a href="?pagina=<?= $pagina + 1 ?>">Siguiente</a><?php endif ?></nav>
<?php cerrar_pagina(); ?>
