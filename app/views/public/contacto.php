<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada(); ?>
<!doctype html>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ReservaCitas · Clínica San Pablo: solicita tu cita médica en línea." name="description">
    <title>Contacto — Clínica San Pablo</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link href="css/base.css?v=f1dbab84d9" rel="stylesheet">
    <link href="vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
    <link href="css/contacto.css?v=817251270c" rel="stylesheet">
  

<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">

<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
  <body data-pagina="contacto">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <?php require_once __DIR__.'/../shared/componentes.php';
cabecera(); ?>
    <main class="pagina-contacto container" id="contenido">
      <div class="encabezado-contacto">
        <p class="eyebrow">ESTAMOS PARA ESCUCHARTE</p><h1>Hablemos de tu<br><span>próxima cita.</span></h1>
        <p>¿Tienes una consulta? Deja un mensaje y el equipo podrá revisarlo.</p>
      </div>
      <div class="contacto-presentacion" data-revelar><div><span class="contacto-disponible"><span></span> TE ACOMPAÑAMOS EN CADA PASO</span><h2>Una buena atención<br>empieza al escucharte.</h2><p>Preguntas sobre especialidades, horarios o el uso de tu cuenta. Cuéntanos qué necesitas y revisaremos tu mensaje.</p><a class="enlace-flecha" href="reserva.php">¿Ya sabes qué necesitas? Solicita tu cita <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div><img src="imagenes/fotografias/contacto.webp" srcset="imagenes/responsive/contacto-480.webp 480w, imagenes/responsive/contacto-800.webp 800w, imagenes/fotografias/contacto.webp 1200w" sizes="(max-width: 650px) 92vw, (max-width: 1100px) 48vw, 600px" alt="Recepcionista atendiendo una consulta en la clínica" width="1100" height="730" loading="lazy"></div><div class="tarjetas-contacto">
        <article class="tarjeta-contacto">
          <div class="icono-contacto"><i class="fas fa-map-marker-alt"></i></div>
          <h2>Ubicación</h2>
          <p>Lima, Perú.</p>
        </article>
        <article class="tarjeta-contacto">
          <div class="icono-contacto"><i class="fas fa-comments"></i></div>
          <h2>Consultas en línea</h2>
          <p>Lunes a sábado, 08:00 a 18:00, hora de Lima. Envía tu consulta desde el formulario.</p>
        </article>
        <article class="tarjeta-contacto">
          <div class="icono-contacto"><i class="fas fa-calendar-check"></i></div>
          <h2>Seguimiento de citas</h2>
          <p>Consulta tus solicitudes desde tu cuenta.</p>
        </article>
      </div>
      <section aria-labelledby="titulo-form-contacto" class="bloque-formulario">
        <h2 id="titulo-form-contacto">Envíanos un mensaje</h2>
        <form action="procesar_contacto.php" id="form-contacto" data-csrf method="POST" novalidate="">
          <div class="campo-contacto campo-mitad">
            <label for="nombre">Nombre *</label>
            <input id="nombre" maxlength="100" name="nombre" placeholder="Ej: María López" required="" type="text">
            <small class="error-campo" id="error-nombre" role="alert"></small>
          </div>
          <div class="campo-contacto campo-mitad">
            <label for="correo">Correo electrónico *</label>
            <input id="correo" maxlength="100" name="correo" placeholder="tu@correo.com" required="" type="email">
            <small class="error-campo" id="error-correo" role="alert"></small>
          </div>
          <div class="campo-contacto">
            <label for="asunto">Asunto *</label>
            <input
              id="asunto"
              maxlength="120"
              name="asunto"
              placeholder="Ej: Consulta sobre horarios"
              required=""
              type="text">
            <small class="error-campo" id="error-asunto" role="alert"></small>
          </div>
          <div class="campo-contacto">
            <label for="mensaje">Mensaje *</label>
            <textarea
              id="mensaje"
              maxlength="1000"
              name="mensaje"
              placeholder="Escribe tu consulta (mínimo 8 caracteres)"
              required=""
              rows="5"
            ></textarea>
            <small class="contador-contacto"><span id="contador-mensaje">0</span>/1000</small>
            <small class="error-campo" id="error-mensaje" role="alert"></small>
          </div>
          <div class="form-grupo campo-mitad"><label for="telefono-contacto">Teléfono opcional</label><input type="tel" id="telefono-contacto" name="telefono" maxlength="15" pattern="\+?[0-9]{7,15}" autocomplete="tel"></div><button class="boton boton-primario" type="submit"><i class="fas fa-paper-plane"></i> Enviar mensaje</button>
        </form>
      </section>
      <div class="bloque-inferior"><h2>Gestiona tu consulta desde el portal</h2><p>Envía un mensaje para que el personal revise tu consulta o ingresa a tu cuenta para solicitar una cita y seguir su estado.</p></div>
    </main>
    <?php pie(false); ?>
    <script defer="" src="js/menu.js?v=a72d8155da"></script>
    <script defer="" src="js/contacto.js?v=ecf7b7c187"></script>
    <script src="js/csrf.js?v=7f4dc7fec3" defer></script>
    <script src="js/extras.js?v=2a0488c1d5" defer></script>
  <script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
