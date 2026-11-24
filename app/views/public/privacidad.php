<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
abrir_pagina('Aviso de privacidad', 'privacidad');
encabezado_gestion('Aviso de privacidad', 'Conoce cómo se utiliza la información de tu cuenta.');?>

<section class="gestion-tarjeta"><h2>Finalidad del tratamiento</h2><p>ReservaCitas utiliza la información registrada para identificar a cada usuario, organizar solicitudes de atención y mantener el historial administrativo de sus citas.</p></section>
<section class="gestion-tarjeta"><h2>Datos solicitados</h2><p>La ficha administrativa contiene nombre, documento, fecha de nacimiento, género y contacto. La cuenta utiliza correo y una contraseña que se almacena mediante un hash. Las citas incluyen especialidad, profesional, fecha, hora, estado e historial. No se solicitan diagnósticos ni resultados de exámenes.</p></section>
<section class="gestion-tarjeta"><h2>Uso y acceso</h2><p>Cada rol accede a las funciones y registros que le corresponden. El responsable de un menor puede gestionar las citas de sus dependientes. Las correcciones administrativas quedan registradas en auditoría.</p></section>
<section class="gestion-tarjeta"><h2>Conservación y solicitudes</h2><p>El sistema conserva el historial necesario para dar seguimiento a las citas y registra los cambios realizados por el personal autorizado. Los enlaces de recuperación y verificación tienen vencimiento. Puedes solicitar acceso o corrección de tu información mediante Contacto; administración revisa los cambios que afectan registros con historial.</p></section>
<section class="gestion-tarjeta"><h2>Cookies y comunicaciones</h2><p>Se utiliza una cookie de sesión necesaria para mantener el acceso a la cuenta. No se emplean cookies de seguimiento publicitario. Puedes administrar los avisos opcionales desde Preferencias de avisos.</p></section>
<?php cerrar_pagina(); ?>
