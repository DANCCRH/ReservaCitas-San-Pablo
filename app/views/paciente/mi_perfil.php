<?php
require BASE_PATH.'/app/controllers/pages/paciente/mi_perfil.php';
abrir_pagina('Mi perfil');
encabezado_gestion('Mi perfil', 'Mantén tus datos de contacto y la seguridad de tu cuenta al día.');
ver_aviso();
if ($u['clave_temporal']) {
    echo '<p class="alert alert-info" role="status">Cambia tu contraseña inicial para continuar usando el sistema.</p>';
}
?>
<div class="perfil-resumen"><div class="medico-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['nombres'], 0, 1))) ?></div><div><h2><?= e($u['nombres']) ?></h2><p><?= e(ROLES[$u['rol']]) ?> · <?= e($u['correo']) ?></p></div></div><div class="gestion-dos-columnas">
<section class="gestion-tarjeta"><h2>Datos personales</h2><form method="post" class="form-gestion" data-csrf><input type="hidden" name="accion" value="datos"><?php campo_csrf(); ?>
<label>Nombres y apellidos<input name="nombres" minlength="5" maxlength="150" value="<?= e($u['nombres']) ?>" required autocomplete="name"></label>
<label>DNI<input value="<?= e($u['dni']) ?>" readonly></label><label>Correo electrónico<input value="<?= e($u['correo']) ?>" readonly></label>
<label>Teléfono<input name="telefono" type="tel" maxlength="15" pattern="\+?[0-9]{7,15}" value="<?= e($u['telefono'] ?? '') ?>" autocomplete="tel"></label>
<?php campos_demografia($u); ?><button class="boton boton-primario">Guardar datos</button></form></section>
<section class="gestion-tarjeta"><h2>Cambiar contraseña</h2><p class="texto-ayuda">Utiliza al menos 8 caracteres con letras y números. El cambio cerrará las demás sesiones abiertas.</p>
<form method="post" class="form-gestion" data-csrf data-claves><input type="hidden" name="accion" value="clave"><?php campo_csrf(); ?>
<label>Contraseña actual<input type="password" name="actual" autocomplete="current-password" maxlength="72" required></label>
<label>Nueva contraseña<input type="password" name="nueva" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<label>Repetir nueva contraseña<input type="password" name="confirmar" autocomplete="new-password" minlength="8" maxlength="72" required></label>
<button class="boton boton-primario">Actualizar contraseña</button></form></section></div>
<?php cerrar_pagina(); ?>
