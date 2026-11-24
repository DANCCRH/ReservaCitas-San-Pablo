<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
abrir_pagina('Términos de uso', 'terminos');
encabezado_gestion('Términos de uso', 'Condiciones para utilizar tu cuenta y gestionar tus citas.');?>

<section class="gestion-tarjeta"><h2>Finalidad</h2><p>ReservaCitas permite consultar especialidades y gestionar solicitudes de citas. Una solicitud queda pendiente hasta la confirmación del personal. El portal no reemplaza una consulta médica ni ofrece atención de emergencias.</p></section>
<section class="gestion-tarjeta"><h2>Responsabilidad de la cuenta</h2><p>Conserva tus credenciales, proporciona información correcta y cierra sesión en equipos compartidos. Registra dependientes únicamente si eres su padre, madre o tutor legal. Recepción revisa las solicitudes de cambio de responsable.</p></section>
<section class="gestion-tarjeta"><h2>Reservas y cambios</h2><p>Los horarios se comprueban al enviar la solicitud. Si dos personas eligen el mismo espacio, solo se admite la primera reserva válida. Las citas futuras se pueden cancelar o reprogramar indicando un motivo y respetando los límites configurados. Las propuestas del personal requieren aceptación y una nueva comprobación de disponibilidad.</p></section>
<section class="gestion-tarjeta"><h2>Seguimiento de la atención</h2><p>Consulta el estado de tus citas y sus novedades desde Mis citas. La llegada, el llamado y el resultado de la atención se registran por separado. Las consultas administrativas se reciben mediante Contacto. El portal no procesa pagos.</p></section>
<?php cerrar_pagina(); ?>
