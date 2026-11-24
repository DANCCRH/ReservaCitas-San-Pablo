<?php
require BASE_PATH.'/app/controllers/pages/auth/recuperar.php';
abrir_pagina('Recuperar acceso');
encabezado_gestion('Recuperar acceso', 'Solicita un enlace temporal para establecer una nueva contraseña.');
ver_aviso();
?>
<section class="gestion-tarjeta formulario-estrecho"><h2>Correo de tu cuenta</h2>
<p class="texto-ayuda">Por seguridad, no se confirma si un correo está registrado.</p>
<form method="post" class="form-gestion" data-csrf><?php campo_csrf(); ?><label>Correo electrónico<input type="email" name="correo" autocomplete="email" maxlength="100" required></label><button class="boton boton-primario">Solicitar recuperación</button><a href="login.php">Volver a ingresar</a></form></section>
<?php cerrar_pagina(); ?>
