<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada(); ?>
<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
require_once __DIR__.'/../../models/agenda_modelo.php';
$items = especialidades_activas();
abrir_pagina('Especialidades', 'especialidades', ['especialidades.css']);
?>
<div class="gestion-cabecera"><div><p class="eyebrow">ATENCIÓN PARA CADA ETAPA</p><h1>Encuentra tu especialidad.</h1><p>Explora el catálogo, elige un profesional y consulta su disponibilidad.</p></div><a class="boton boton-primario" href="reserva.php">Solicitar cita</a></div>
<div class="catalogo-herramientas"><div class="buscador-especialidades"><i class="fas fa-search" aria-hidden="true"></i><label class="solo-lectores" for="buscar-especialidad">Buscar especialidad</label><input id="buscar-especialidad" type="search" placeholder="Busca una especialidad" autocomplete="off"><button id="limpiar-busqueda" type="button" aria-label="Limpiar búsqueda" hidden>×</button></div><p id="resultado-busqueda" role="status"><?= count($items) ?> especialidades disponibles</p></div>
<section class="especialidades-grid" aria-label="Catálogo de especialidades"><?php foreach ($items as $e): ?><article class="tarjeta-especialidad"><div class="tarjeta-imagen"><img src="<?= e($e['imagen']) ?>" alt="Consulta de <?= e($e['nombre']) ?>" width="600" height="380" loading="lazy"></div><div class="tarjeta-cuerpo"><h2><?= e($e['nombre']) ?></h2><p><?= e($e['descripcion']) ?></p><a href="reserva.php?esp=<?= (int)$e['id'] ?>">Elegir médico y horario <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div></article><?php endforeach ?></section>
<div id="catalogo-vacio" class="gestion-vacio" hidden><h2>No encontramos esa especialidad</h2><p>Prueba con otro nombre o limpia la búsqueda.</p></div>
<script src="js/especialidades.js?v=201998fff2" defer></script>
<?php cerrar_pagina(); ?>
