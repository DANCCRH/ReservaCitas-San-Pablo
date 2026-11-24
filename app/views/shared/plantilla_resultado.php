<?php
/**
 * plantilla_resultado.php · Página de respuesta común de los procesar_*.php.
 * $mensaje_html debe venir ya escapado con e() (puede contener <br> o enlaces propios).
 */
require_once __DIR__.'/componentes.php';
function mostrar_resultado($exito, $titulo, $mensaje_html, $enlace, $texto_enlace)
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> - ReservaCitas</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="css/base.css?v=f1dbab84d9">
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">


<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
<body data-pagina="resultado">
<?php cabecera(); ?>
    <main class="container resultado-contenedor">
        <section class="resultado-caja <?= $exito ? 'resultado-ok' : 'resultado-fallo' ?>" role="status">
            <i class="fas <?= $exito ? 'fa-check-circle resultado-exito' : 'fa-exclamation-triangle resultado-error' ?>"></i>
            <h1 class="resultado-titulo"><?= e($titulo) ?></h1>
            <p><?= $mensaje_html ?></p>
            <a href="<?= e($enlace) ?>" class="boton boton-primario"><?= e($texto_enlace) ?></a>
        </section>
    </main>
<?php pie(); ?>
<script src="js/menu.js?v=a72d8155da" defer></script>
<script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
    <?php
    exit;
}

/** Corta la petición si el token CSRF del formulario no coincide con el de la sesión. */
function verificar_csrf()
{
    iniciar_sesion_segura();
    $enviado = $_POST['csrf'] ?? '';
    if (!csrf_valido($enviado)) {
        http_response_code(403);
        mostrar_resultado(false, 'Solicitud no válida', 'Tu sesión del formulario expiró. Vuelve a cargar la página e inténtalo de nuevo.', 'index.php', 'Volver al inicio');
    }
}

/** Devuelve un mensaje claro si el error se debe a una base de datos con estructura antigua. */
function mensaje_error_bd($ex, $generico)
{
    $codigo = $ex->errorInfo[0] ?? '';
    if ($codigo === '42S22' || $codigo === '42S02') {
        return 'El sistema necesita una actualización de su base de datos. Comunícate con el administrador para aplicarla sin borrar los registros.';
    }
    return $generico;
}
