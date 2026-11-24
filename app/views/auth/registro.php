<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada();
$cuenta = usuario_actual();
if ($cuenta) {
    header('Location: '.destino_rol($cuenta));
    exit;
} ?>
<!doctype html>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ReservaCitas · Clínica San Pablo: solicita tu cita médica en línea." name="description">
    <title>Regístrate — Clínica San Pablo</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link href="css/base.css?v=f1dbab84d9" rel="stylesheet">
    <link href="vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
    <link href="css/registro.css?v=40a5fb99b1" rel="stylesheet">
  

<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">

<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
  <body data-pagina="registro">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <?php require_once __DIR__.'/../shared/componentes.php';
cabecera(); ?>
    <main class="registro-seccion container" id="contenido">
      <div class="registro-grid">
  <div class="acceso-presentacion"><a class="volver-enlace" href="index.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al inicio</a>
   <div class="acceso-marca"><img src="imagenes/marca/reservacitas-256.png" width="56" height="56" alt=""><span>ReservaCitas</span></div>
   <p class="eyebrow">TU ESPACIO DE SALUD</p><h1>El primer paso<br>para cuidarte.</h1><p class="acceso-descripcion">Crea tu cuenta y empieza a organizar tus citas con más tranquilidad.</p>
   <ul class="acceso-beneficios"><li><i class="fas fa-calendar-plus" aria-hidden="true"></i><div><strong>Solicita con tranquilidad</strong><span>Elige una especialidad y un horario.</span></div></li><li><i class="fas fa-list-check" aria-hidden="true"></i><div><strong>Sigue tus solicitudes</strong><span>Consulta su estado desde tu cuenta.</span></div></li><li><i class="fas fa-circle-check" aria-hidden="true"></i><div><strong>Revisa antes de enviar</strong><span>Comprueba tus datos en el resumen.</span></div></li></ul>
   <div class="acceso-pie"><i class="fas fa-heart" aria-hidden="true"></i> Más tiempo para lo que importa.</div>
  </div><div>
  <div class="tarjeta-registro">
            <h2>Crear cuenta nueva</h2>
            <p class="subtitulo-registro">Completa tus datos para registrarte</p>
            <form action="procesar_registro.php" id="form-registro" data-csrf method="POST" novalidate="">
              <div class="campo-grupo">
                <label for="fullName">Nombres y apellidos</label>
                <input id="fullName" maxlength="150" autocomplete="name" name="nombre_completo" placeholder="María López Pérez" required="" type="text">
              </div>
              <div class="campo-grupo">
                <label for="tipo_documento">Tipo de documento</label><select id="tipo_documento" name="tipo_documento"><option value="DNI">DNI</option><option value="CE">Carné de extranjería</option><option value="PASAPORTE">Pasaporte</option></select><label for="document">Número de documento</label>
                <input
                  id="document"
                  inputmode="numeric"
                  maxlength="20"
                  name="dni_reg"
                  placeholder="12345678"
                  required=""
                  type="text">
              </div>
              <div class="campo-grupo">
                <label for="email">Correo electrónico</label>
                <input id="email" maxlength="100" autocomplete="email" name="correo_reg" placeholder="correo@ejemplo.com" required="" type="email">
              </div>
              <div class="campo-grupo">
                <label for="password">Contraseña</label>
                <div class="input-con-boton">
                  <input
                    id="password"
                    minlength="8" maxlength="72"
                    autocomplete="new-password"
                    name="pass_reg"
                    placeholder="8 caracteres con letras y números"
                    required=""
                    type="password">
                  <button id="ver-clave" type="button" aria-label="Mostrar contraseña">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
              <div class="campo-grupo">
                <label for="confirmPassword">Confirmar contraseña</label>
                <div class="input-con-boton">
                  <input
                    id="confirmPassword"
                    minlength="8" maxlength="72"
                    autocomplete="new-password"
                    name="pass_conf"
                    placeholder="Repite tu contraseña"
                    required=""
                    type="password">
                  <button id="ver-clave2" type="button" aria-label="Mostrar confirmación de contraseña">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
              <div class="checkbox-terminos">
                <input id="terminos" name="terminos" required="" type="checkbox">
                <label for="terminos">Acepto los <a href="terminos.php" target="_blank" rel="noopener">términos de uso</a> y he leído el <a href="privacidad.php" target="_blank" rel="noopener">aviso de privacidad</a>.</label>
              </div>
              <button class="boton-registro" type="submit">Registrarme</button>
            </form>
            <div class="mensaje-feedback" id="mensaje-feedback"></div>
            <p class="enlace-login">¿Ya tienes cuenta? <a href="login.php?ui=9">Inicia sesión aquí</a></p>
          </div>
        </div>
      </div>
    </main>
    <?php pie(true); ?>
    <script defer="" src="js/menu.js?v=a72d8155da"></script>
    <script defer="" src="js/registro.js?v=4dee8e1928"></script>
    <script src="js/csrf.js?v=7f4dc7fec3" defer></script>
    <script src="js/extras.js?v=2a0488c1d5" defer></script>
  <script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
