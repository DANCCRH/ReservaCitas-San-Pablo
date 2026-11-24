<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada(); ?>
<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
abrir_pagina('Nosotros', 'nosotros');
?>
<div class="gestion-cabecera"><div><p class="eyebrow">NUESTRA PROPUESTA</p><h1>Organizar una cita puede ser más sencillo.</h1><p>ReservaCitas conecta la información del centro médico con un proceso claro para solicitar y gestionar atenciones.</p></div></div>
<section class="gestion-tarjeta" data-revelar>
    <h2>Historia de la Clínica San Pablo</h2>
    <p>En agosto de 1991, el doctor José Álvarez Blas y un equipo de especialistas fundaron la Clínica San Pablo. La institución se propuso ofrecer atención especializada con apoyo de tecnología médica.</p>
    <p><a href="https://www.sanpablo.com.pe/nosotros/">Conocer la historia en el sitio de la clínica</a></p>
    <h2>Cómo nació ReservaCitas</h2>
    <p>ReservaCitas nació de una pregunta: ¿cómo saber qué médico tiene un horario disponible y qué pasó con la solicitud después de enviarla? El portal reúne ambas consultas. El paciente puede elegir un turno y seguir su cita, y el equipo de atención dispone de una agenda para coordinarla.</p>
</section>
<div class="gestion-dos-columnas"><section class="gestion-tarjeta"><h2>Misión</h2><p>Facilitar la organización de citas y consultas mediante una plataforma comprensible, accesible desde distintos dispositivos y conectada a una base de datos.</p></section><section class="gestion-tarjeta"><h2>Visión</h2><p>Consolidar una gestión de citas accesible y organizada, que facilite el seguimiento de la atención y acompañe el crecimiento de los servicios del centro médico.</p></section></div>
<section class="gestion-tarjeta"><h2>Nuestros valores</h2><div class="valores-grid"><article><i class="fas fa-comment" aria-hidden="true"></i><h3>Claridad</h3><p>Información y estados fáciles de comprender durante todo el proceso.</p></article><article><i class="fas fa-universal-access" aria-hidden="true"></i><h3>Accesibilidad</h3><p>Navegación que considera celulares, computadoras y el uso del teclado.</p></article><article><i class="fas fa-shield-heart" aria-hidden="true"></i><h3>Responsabilidad</h3><p>Validación de los datos y permisos definidos para cada cuenta.</p></article><article><i class="fas fa-people-group" aria-hidden="true"></i><h3>Colaboración</h3><p>Coordinación entre pacientes, recepción y profesionales para dar seguimiento a cada atención.</p></article></div></section>
<section class="gestion-tarjeta"><h2>Qué puedes hacer en el portal</h2><p>Consultar especialidades, elegir médico y horario, solicitar o reprogramar una cita, recibir notificaciones y enviar consultas. El equipo administrativo revisa las solicitudes, configura la agenda y consulta reportes de atención.</p><a class="boton boton-primario" href="servicios.php">Conocer los servicios</a></section>
<section class="nosotros-fotografia" data-revelar><img src="imagenes/fotografias/equipo.webp" srcset="imagenes/responsive/equipo-480.webp 480w, imagenes/responsive/equipo-800.webp 800w, imagenes/fotografias/equipo.webp 1200w" sizes="(max-width: 650px) 92vw, (max-width: 1100px) 48vw, 600px" alt="Equipo de profesionales coordinando la atención" width="1200" height="800" loading="lazy"><div><p class="eyebrow">ATENCIÓN CERCANA</p><h2>Un equipo que te acompaña.</h2><p>La coordinación entre recepción, profesionales y personal asistencial permite dar seguimiento a cada cita.</p><a class="boton boton-primario" href="medicos.php">Conoce a los profesionales</a></div></section><?php cerrar_pagina(); ?>
