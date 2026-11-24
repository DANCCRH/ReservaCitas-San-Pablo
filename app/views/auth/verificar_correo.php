<?php
require BASE_PATH.'/app/controllers/pages/auth/verificar_correo.php';
abrir_pagina('Verificar correo');
encabezado_gestion('Verificar correo', 'Confirma el correo de tu cuenta antes de utilizar el servicio público.');?><section class="gestion-tarjeta"><h2>Estado de verificación</h2><p><?= e($mensaje ?: 'Revisa tu bandeja de entrada y abre el enlace personal.') ?></p><?php if (usuario_actual()):?><form method="post"><?php campo_csrf();?><button class="boton boton-primario">Solicitar otro enlace</button></form><?php endif ?><a href="login.php">Continuar</a></section><?php cerrar_pagina(); ?>
