<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
abrir_pagina('Ayuda', 'ayuda');
encabezado_gestion('Centro de ayuda', 'Encuentra orientación para gestionar tu cuenta y tus citas.');?>

<section class="gestion-tarjeta"><h2>ReservaCitas</h2><p>ReservaCitas reúne la consulta de especialidades, la disponibilidad de profesionales y el seguimiento de citas de Clínica San Pablo. Desde tu cuenta puedes organizar tus próximas atenciones y consultar su historial.</p></section>
<section class="gestion-tarjeta"><h2>Horario de atención administrativa</h2><p>El canal administrativo atiende de lunes a sábado de 08:00 a 18:00, hora de Lima. Los turnos médicos se consultan en la agenda y pueden variar según la especialidad y la disponibilidad del profesional.</p></section>
<section class="gestion-tarjeta"><h2>Cómo solicitar una cita</h2><p>Crea una cuenta, elige una especialidad y un profesional, consulta los horarios y revisa el resumen de la solicitud. Desde Mis citas podrás revisar su estado y descargar un PDF o un archivo de calendario. Si no encuentras un espacio, registra una preferencia en Lista de espera.</p></section>
<section class="gestion-tarjeta"><h2>Asistencia con tu cuenta</h2><p>Utiliza Recuperar acceso si olvidaste tu contraseña. Para corregir datos administrativos o el responsable de un menor, envía una consulta a recepción mediante Contacto. No incluyas diagnósticos ni datos sensibles en los mensajes.</p></section>
<?php cerrar_pagina(); ?>
