<?php

/** Restaura únicamente campos no sensibles del último formulario rechazado. */
function insertar_ayudas_formulario($html)
{
    if (stripos($html, '</body>') === false) {
        return $html;
    }
    $u = usuario_actual();
    $actual = ruta_actual();
    $anterior = $_SESSION['formulario_anterior'] ?? null;
    $contenido = '';
    if ($anterior && $anterior['pagina'] === $actual) {
        unset($_SESSION['formulario_anterior']);
        $contenido .= '<script type="application/json" id="datos-recuperados">'.json_encode($anterior, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE).'</script>';
    }
    if ($u) {
        $contenido .= '<div id="aviso-sesion" class="aviso-sesion" role="alert" hidden><p>Tu sesión está por expirar por inactividad.</p><button type="button" class="boton boton-primario" id="continuar-sesion">Continuar sesión</button><a href="logout.php">Cerrar sesión</a></div>';
    }
    $contenido .= '<script src="js/operacion.js?v=a907bb8006" defer></script>';
    $html = str_ireplace('</body>', $contenido.'</body>', $html);
    return preg_replace('/<script(?![^>]*\bnonce=)/i', '<script nonce="'.htmlspecialchars($GLOBALS['csp_nonce'] ?? '', ENT_QUOTES).'"', $html);
}
if (PHP_SAPI !== 'cli') {
    ob_start('insertar_ayudas_formulario');
}
