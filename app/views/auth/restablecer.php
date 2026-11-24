<?php
require BASE_PATH.'/app/controllers/pages/auth/restablecer.php';
abrir_pagina('Nueva contraseña');
encabezado_gestion('Nueva contraseña', 'El enlace es personal, vence en 30 minutos y solo puede utilizarse una vez.');
?>
<section class="gestion-tarjeta formulario-estrecho"><?php if (!$valido): ?><h2>Enlace no disponible</h2><p>Puede haber expirado o haber sido utilizado.</p><a class="boton boton-primario" href="recuperar.php">Solicitar otro enlace</a><?php else: ?><h2>Establecer contraseña</h2><form method="post" class="form-gestion" data-csrf data-claves><?php campo_csrf(); ?><input type="hidden" name="token" value="<?= e($token) ?>"><label>Nueva contraseña<input type="password" name="nueva" minlength="8" maxlength="72" autocomplete="new-password" required></label><label>Repetir contraseña<input type="password" name="confirmar" minlength="8" maxlength="72" autocomplete="new-password" required></label><button class="boton boton-primario">Guardar contraseña</button></form><?php endif ?></section>
<?php cerrar_pagina(); ?>
