<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada(); ?>
<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
require_once __DIR__.'/../../models/agenda_modelo.php';
$items = especialidades_activas();
abrir_pagina('Servicios', 'servicios');
?>
<section class="servicios-presentacion"><div><p class="eyebrow">TE ACOMPAÑAMOS EN CADA PASO</p><h1>Atención que empieza por escucharte.</h1><p>Conoce las especialidades del catálogo y organiza tu atención desde el portal. Puedes elegir profesional, revisar sus turnos y consultar el estado de tu solicitud.</p><a class="boton boton-primario" href="especialidades.php">Explorar especialidades</a></div><img src="imagenes/marca/atencion-medica.webp" alt="Doctora escuchando a una paciente en una consulta ilustrativa" width="720" height="900"></section>
<section class="gestion-tarjeta"><h2>Catálogo de servicios</h2><div class="servicios-detalle-grid"><?php foreach ($items as $e): ?><article><img src="<?= e($e['imagen']) ?>" alt="" width="75" height="75"><div><h3><?= e($e['nombre']) ?></h3><p><?= e($e['descripcion']) ?></p><a href="reserva.php?esp=<?= (int)$e['id'] ?>">Consultar médicos y turnos</a></div></article><?php endforeach ?></div></section>
<section class="gestion-tarjeta"><h2>Preguntas sobre las citas</h2><div class="accordion" id="preguntas-servicios">
<?php foreach ([
 ['¿Cómo solicito una cita?','Crea una cuenta o ingresa, elige una especialidad, selecciona un médico y consulta los turnos libres. Completa el teléfono y revisa el resumen antes de enviar la solicitud.'],
 ['¿Cuándo queda confirmada?','La solicitud reserva el turno y queda pendiente de confirmación. Administración revisa la cita y su decisión aparece en Mis citas y Notificaciones.'],
 ['¿Puedo cambiar o cancelar la cita?','Sí. Desde Mis citas puedes reprogramar o cancelar una cita futura pendiente o confirmada, indicando el motivo. La reprogramación requiere una nueva confirmación y conserva el historial anterior.']
] as $i => [$pregunta,$respuesta]): ?><div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pregunta-<?= $i ?>" aria-expanded="false" aria-controls="pregunta-<?= $i ?>"><?= e($pregunta) ?></button></h3><div id="pregunta-<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#preguntas-servicios"><div class="accordion-body"><?= e($respuesta) ?></div></div></div><?php endforeach ?></div></section>
<?php cerrar_pagina(); ?>
