<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada(); ?>
<?php require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../../models/agenda_modelo.php';
$catalogo_inicio = especialidades_activas(); ?>
<!doctype html>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ReservaCitas · Clínica San Pablo: solicita tu cita médica en línea." name="description">
    <title>Clínica San Pablo — ReservaCitas</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link href="css/base.css?v=f1dbab84d9" rel="stylesheet">
    <link href="vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
  

<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">

<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
  <body data-pagina="index">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <!-- ENCABEZADO -->
    <?php require_once __DIR__.'/../shared/componentes.php';
cabecera(); ?>
    <main id="contenido">
 <section class="inicio-hero"><div class="hero-decoracion" aria-hidden="true"></div><div class="container inicio-grid">
  <div class="inicio-texto"><p class="eyebrow"><span></span> CUIDARTE EMPIEZA CON UNA CITA</p>
   <h1>Tu salud merece<br>un lugar en<br><em>tu agenda.</em></h1>
   <p class="inicio-descripcion">Encuentra tu especialidad, elige un horario y organiza tus citas desde un solo lugar. Así de simple.</p>
   <div class="inicio-acciones"><a class="boton boton-primario" href="reserva.php">Solicitar una cita <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a class="boton boton-secundario" href="especialidades.php">Ver especialidades</a></div>
   <div class="inicio-confianza"><span class="icono-confianza"><i class="fas fa-shield-heart" aria-hidden="true"></i></span><p>Tu cuenta, tus solicitudes.<br><strong>Todo en un mismo lugar.</strong></p></div>
  </div>
  <div class="inicio-visual"><div class="foto-marco"><img src="imagenes/marca/atencion-medica.webp" width="720" height="900" alt="Doctora escuchando a una paciente durante una consulta" fetchpriority="high"><div class="foto-etiqueta"><i class="fas fa-heart" aria-hidden="true"></i> Atención que se siente cercana</div></div>
   <div class="hero-especialidades"><strong><?= count($catalogo_inicio) ?></strong><span>especialidades<br>para cuidarte</span></div>
   <div class="hero-consulta"><span class="consulta-icono"><i class="fas fa-calendar-check" aria-hidden="true"></i></span><div><small>A TU RITMO</small><strong>Elige el momento para ti</strong><span>Revisa tu solicitud antes de enviarla.</span></div><i class="fas fa-check-circle" aria-hidden="true"></i></div>
  </div>
 </div></section>
 <section class="inicio-proceso" data-revelar><div class="container proceso-grid">
  <a href="registro.php" class="proceso-item"><span class="proceso-numero">01</span><div><h2>Crea tu cuenta</h2><p>Un espacio para organizar tus solicitudes.</p></div><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
  <a href="reserva.php" class="proceso-item"><span class="proceso-numero">02</span><div><h2>Solicita tu cita</h2><p>Elige especialidad, fecha y horario.</p></div><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
  <a href="mis_solicitudes.php" class="proceso-item"><span class="proceso-numero">03</span><div><h2>Consulta el estado</h2><p>Encuentra tus citas en Mis solicitudes.</p></div><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
 </div></section>
 <section class="inicio-especialidades container"><div class="titulo-seccion"><div><p class="eyebrow">ATENCIÓN PARA CADA ETAPA</p><h2>El cuidado que necesitas.</h2></div><a class="enlace-flecha" href="especialidades.php">Ver todas las especialidades <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
  <div class="destacadas-grid"><?php foreach (array_slice($catalogo_inicio, 0, 3) as $e): ?><a class="destacada" href="reserva.php?esp=<?= (int)$e['id'] ?>"><img src="<?= e($e['imagen']) ?>" alt="Consulta de <?= e($e['nombre']) ?>" width="480" height="300" loading="lazy"><div><span>ATENCIÓN ESPECIALIZADA</span><h3><?= e($e['nombre']) ?></h3><p><?= e($e['descripcion']) ?></p><strong>Solicitar cita <i class="fas fa-arrow-right" aria-hidden="true"></i></strong></div></a><?php endforeach ?></div>
</section>
<section class="experiencia-seccion container" data-revelar><div class="experiencia-fotos"><img src="imagenes/fotografias/equipo.webp" srcset="imagenes/responsive/equipo-480.webp 480w, imagenes/responsive/equipo-800.webp 800w, imagenes/fotografias/equipo.webp 1200w" sizes="(max-width: 650px) 92vw, (max-width: 1100px) 48vw, 600px" alt="Equipo de salud en un espacio de atención" width="1100" height="730" loading="lazy"><div class="experiencia-sello"><svg viewBox="0 0 160 44" aria-hidden="true"><path d="M0 23h33l7-8 9 20 12-32 13 39 11-20h75"/></svg><span>Cuidamos cada detalle.</span></div></div><div class="experiencia-texto"><p class="eyebrow">PERSONAS QUE CUIDAN DE PERSONAS</p><h2>La confianza empieza<br>por sentirte escuchado.</h2><p>Detrás de cada cita hay un equipo que te acompaña. Conoce las especialidades, encuentra un profesional y lleva el seguimiento de tus atenciones en un solo lugar.</p><ul class="lista-bienestar"><li><i class="fas fa-check" aria-hidden="true"></i> Profesionales por especialidad</li><li><i class="fas fa-check" aria-hidden="true"></i> Horarios y disponibilidad actualizados</li><li><i class="fas fa-check" aria-hidden="true"></i> Tu historial, siempre a mano</li></ul><a class="enlace-flecha" href="medicos.php">Conoce al equipo médico <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div></section></main>
    <!-- PIE DE PÁGINA -->
    <?php pie(false); ?>
    <script defer="" src="js/menu.js?v=a72d8155da"></script>
    <script src="js/extras.js?v=2a0488c1d5" defer></script>
  <script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
