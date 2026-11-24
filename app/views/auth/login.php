<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada();
$cuenta = usuario_actual();
if ($cuenta) {
    header('Location: '.destino_rol($cuenta));
    exit;
}
$archivoAlias = BASE_PATH.'/config/alias_aula.local.php';
$aliasAula = is_file($archivoAlias) ? require $archivoAlias : [];
?>
<!doctype html>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ReservaCitas · Clínica San Pablo: solicita tu cita médica en línea." name="description">
    <title>Ingresar — Clínica San Pablo</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link href="css/base.css?v=f1dbab84d9" rel="stylesheet">
    <link href="vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
    <link href="css/login.css?v=975d40937b" rel="stylesheet">
  

<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">

<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
  <body data-pagina="login">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <?php require_once __DIR__.'/../shared/componentes.php';
cabecera(); ?>
    <main class="login-seccion container" id="contenido">
      <div class="login-grid">
  <div class="acceso-presentacion"><a class="volver-enlace" href="index.php"><i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al inicio</a>
   <div class="acceso-marca"><img src="imagenes/marca/reservacitas-256.png" width="56" height="56" alt=""><span>ReservaCitas</span></div>
   <p class="eyebrow">TU ESPACIO DE SALUD</p><h1>Tus citas, en un<br>solo lugar.</h1><p class="acceso-descripcion">Ingresa a tu cuenta para consultar tus solicitudes y organizar tu próxima atención.</p>
   <ul class="acceso-beneficios"><li><i class="fas fa-calendar-plus" aria-hidden="true"></i><div><strong>Solicita con tranquilidad</strong><span>Elige una especialidad y un horario.</span></div></li><li><i class="fas fa-list-check" aria-hidden="true"></i><div><strong>Sigue tus solicitudes</strong><span>Consulta su estado desde tu cuenta.</span></div></li><li><i class="fas fa-circle-check" aria-hidden="true"></i><div><strong>Revisa antes de enviar</strong><span>Comprueba tus datos en el resumen.</span></div></li></ul>
   <div class="acceso-pie"><i class="fas fa-heart" aria-hidden="true"></i> Más tiempo para lo que importa.</div>
  </div><div>
  <div class="tarjeta-login">
            <h2>Iniciar sesión</h2><?php if (isset($_GET['expirada'])):?><p class="alert alert-info" role="status">Tu sesión terminó por inactividad. Vuelve a ingresar.</p><?php endif ?>
            <p class="subtitulo-login">Ingresa el correo y la contraseña de tu cuenta</p>
            <form action="procesar_login.php" id="form-login" data-csrf method="POST"
                  data-identificador-medico="<?= e($aliasAula['medico']['identificador'] ?? '') ?>"
                  data-identificador-paciente="<?= e($aliasAula['paciente']['identificador'] ?? '') ?>">
              <div class="campo-grupo">
                <label for="usuario">Correo electrónico</label>
                <input id="usuario" maxlength="100" autocomplete="username" name="correo_login" placeholder="correo@ejemplo.com" required="" type="email">
              </div>
              <div class="campo-grupo">
                <label for="clave">Contraseña</label>
                <div class="input-con-boton">
                  <input
                    id="clave"
                    autocomplete="current-password"
                    name="contrasena_login"
                    placeholder="••••••••"
                    required=""
                    type="password">
                  <button id="ver-clave" type="button" aria-label="Mostrar contraseña">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
              <p class="enlace-recuperar"><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p><button class="boton-login" type="submit">Ingresar</button>
            </form>
            <div class="mensaje-feedback" id="mensaje-feedback"></div>
            <p class="enlace-registro">¿No tienes cuenta? <a href="registro.php?ui=9">Regístrate aquí</a></p>
          </div>
        </div>
      </div>
    </main>
    <?php pie(true); ?>
    <script defer="" src="js/menu.js?v=a72d8155da"></script>
    <script defer="" src="js/login.js?v=6b2b6b0c2e"></script>
    <script src="js/csrf.js?v=7f4dc7fec3" defer></script>
    <script src="js/extras.js?v=2a0488c1d5" defer></script>
  <script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
