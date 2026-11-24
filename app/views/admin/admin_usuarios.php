<?php
require BASE_PATH.'/app/controllers/pages/admin/admin_usuarios.php';
abrir_pagina('Usuarios y roles');
encabezado_gestion('Usuarios y roles', 'Administra los accesos del equipo. El administrador mantiene el control máximo del sistema.', true);
ver_aviso();
?>
<section class="gestion-tarjeta"><h2><?= $edit ? 'Editar cuenta' : 'Crear cuenta' ?></h2><p class="texto-ayuda">Paciente: citas propias. Médico: agenda asignada. Recepción: reservas y pacientes. Personal asistencial: llegadas del equipo. Administrador: control general.</p>
<form method="post" class="form-gestion" data-roles><?php campo_csrf(); ?><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
<div class="gestion-dos-columnas"><label>Nombres y apellidos<input name="nombres" minlength="5" maxlength="150" value="<?= e($edit['nombres'] ?? '') ?>" required autocomplete="off"></label><label>DNI<input name="dni" pattern="[0-9]{8}" inputmode="numeric" maxlength="8" value="<?= e($edit['dni'] ?? '') ?>" required></label>
<label>Correo<input type="email" name="correo" maxlength="100" value="<?= e($edit['correo'] ?? '') ?>" required autocomplete="off"></label><label>Teléfono<input name="telefono" type="tel" maxlength="15" pattern="\+?[0-9]{7,15}" value="<?= e($edit['telefono'] ?? '') ?>"></label>
<label>Rol<select name="rol"><?php foreach (ROLES as $v => $nombre): ?><option value="<?= $v ?>" <?= ($edit['rol'] ?? 'paciente') === $v ? 'selected' : '' ?>><?= e($nombre) ?></option><?php endforeach ?></select></label>
<label><?= $edit ? 'Nueva contraseña (opcional)' : 'Contraseña inicial' ?><input name="clave" type="password" minlength="8" maxlength="72" autocomplete="new-password" <?= !$edit ? 'required' : '' ?>></label></div>
<div data-rol-campo="medico"><label>Profesional vinculado<?php campo_buscador('medico', 'medico_id', $mid, ['alcance' => 'sin_cuenta', 'etiqueta' => 'Profesional vinculado', 'placeholder' => 'Buscar profesional', 'usuario' => $id]); ?></label></div>
<fieldset data-rol-campo="asistencial"><legend>Profesionales del equipo</legend><?php campo_buscador('medico', 'equipo[]', $equipo, ['multiple' => true, 'etiqueta' => 'Profesionales del equipo', 'placeholder' => 'Añadir profesional']); ?></fieldset>
<label class="gestion-checkbox"><input type="checkbox" name="activo" <?= !$edit || $edit['activo'] ? 'checked' : '' ?>> Cuenta activa</label>
<label>Tu contraseña de administrador<input name="clave_admin" type="password" maxlength="72" required autocomplete="current-password"></label><p class="texto-ayuda">Las contraseñas nuevas deben tener al menos 8 caracteres con letras y números.</p>
<div class="acciones-fila"><button class="boton boton-primario">Guardar cuenta</button><?php if ($edit): ?><a href="admin_usuarios.php">Crear otra cuenta</a><?php endif ?></div></form></section>
<form method="get" class="gestion-filtros"><label>Nombre, DNI o correo<input type="search" name="q" maxlength="100" value="<?= e($q) ?>"></label><button class="boton boton-primario">Buscar cuenta</button></form>
<section class="gestion-tarjeta sin-relleno"><div class="gestion-panel-titulo"><h2>Cuentas de acceso</h2></div><div class="tabla-desplazable" tabindex="0" role="region" aria-label="Cuentas de acceso"><table class="table tabla-gestion"><thead><tr><th scope="col">Cuenta</th><th scope="col">Rol</th><th scope="col">Acceso</th><th scope="col">Gestión</th></tr></thead><tbody><?php foreach ($usuarios as $fila): ?><tr><td><strong><?= e($fila['nombres']) ?></strong><span><?= e($fila['correo']) ?></span></td><td><?= e(ROLES[$fila['rol']]) ?></td><td><?= $fila['activo'] ? 'Activa' : 'Desactivada' ?></td><td><a href="?id=<?= (int)$fila['id'] ?>">Editar cuenta</a></td></tr><?php endforeach ?></tbody></table></div><?php paginacion($pagina, $paginas); ?></section>
<?php if ($edit): $s = $conexion->prepare('SELECT a.*,p.nombres autor FROM auditoria_usuarios a JOIN pacientes p ON p.id=a.actor_id WHERE a.usuario_id=? ORDER BY a.id DESC LIMIT 10');
    $s->execute([$edit['id']]); ?><details class="gestion-tarjeta"><summary>Últimos cambios de esta cuenta</summary><?php foreach ($s as $a): ?><p><strong><?= e($a['autor']) ?> · <?= e($a['fecha']) ?></strong><br><?= e($a['detalle']) ?></p><?php endforeach ?></details><?php endif ?>
<?php cerrar_pagina(); ?>
