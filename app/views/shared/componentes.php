<?php
require_once __DIR__.'/../../support/buscadores.php';
/** Marca, navegación y pie compartidos por todas las páginas del sistema. */
function cabecera()
{
    $cuenta = usuario_actual();
    $enlaces = ['index.php' => ['Inicio','house'], 'especialidades.php' => ['Especialidades','stethoscope'], 'servicios.php' => ['Servicios','hand-holding-medical'], 'nosotros.php' => ['Nosotros','users'], 'galeria.php' => ['Galería','images'], 'contacto.php' => ['Contacto','comments']];
    if (!$cuenta || $cuenta['rol'] === 'paciente') {
        $enlaces += ['reserva.php' => ['Solicitar cita','calendar-plus'], 'mis_solicitudes.php' => ['Mis citas','calendar-check']];
    } else {
        $enlaces[destino_rol($cuenta)] = ['Mi panel','table-columns'];
    }
    $sin_leer = 0;
    if ($cuenta) {
        global $conexion;
        $s = $conexion->prepare('SELECT COUNT(*) FROM notificaciones WHERE paciente_id=? AND leido_en IS NULL');
        $s->execute([$cuenta['id']]);
        $sin_leer = (int)$s->fetchColumn();
    }
    $enlaces['medicos.php'] = ['Médicos','user-doctor'];
    $actual = ruta_actual();
    $inicial = $cuenta ? mb_strtoupper(mb_substr($cuenta['nombres'], 0, 1)) : '';
    ?>
<header class="site-header"><div class="nav-container">
 <a class="logo" href="index.php" aria-label="ReservaCitas, ir al inicio"><img src="imagenes/marca/reservacitas-256.png" width="44" height="44" alt=""><div><strong>Reserva<span class="marca-acento">Citas</span></strong><span class="marca-descripcion">Clínica San Pablo</span></div></a>
 <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu-panel" aria-label="Abrir menú"><i class="fas fa-bars" aria-hidden="true"></i></button>
 <div class="menu-fondo" hidden></div>
 <div class="menu-panel" id="menu-panel" tabindex="-1">
  <div class="menu-movil-cabecera"><span><strong>Tu salud, más cerca.</strong><small>Encuentra lo que necesitas</small></span><button type="button" class="menu-cerrar" aria-label="Cerrar menú"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
  <nav class="menu-principal" aria-label="Menú principal"><ul>
  <?php foreach ($enlaces as $url => [$texto,$icono]): ?><li><a href="<?= $url ?>"<?= $actual === $url ? ' aria-current="page"' : '' ?>><i class="fas fa-<?= $icono ?> menu-icono" aria-hidden="true"></i><?= $texto ?></a></li><?php endforeach ?>
   <li class="cuenta-menu"><?php if (!$cuenta): ?><a href="login.php" data-acceso-sesion class="ingresar-enlace"><i class="far fa-user" aria-hidden="true"></i> Ingresar</a><?php else: ?>
    <button type="button" class="cuenta-toggle" data-cuenta-toggle data-acceso-sesion aria-expanded="false" aria-controls="opciones-cuenta"><span class="cuenta-avatar"><?= e($inicial) ?></span><span><?= e(explode(' ', $cuenta['nombres'])[0]) ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
    <div class="cuenta-opciones" id="opciones-cuenta"><div class="cuenta-resumen"><strong><?= e($cuenta['nombres']) ?></strong><small><?= e(ROLES[$cuenta['rol']]) ?></small></div><a href="mi_perfil.php"><i class="far fa-user" aria-hidden="true"></i> Mi perfil</a><a href="preferencias.php">Preferencias de avisos</a><a href="notificaciones.php"><i class="far fa-bell" aria-hidden="true"></i> Notificaciones<?php if ($sin_leer):?><span class="contador-notificaciones" role="status" aria-label="<?= $sin_leer ?> sin leer"><?= min(99, $sin_leer) ?><?= $sin_leer > 99 ? '+' : '' ?></span><?php endif ?></a><?php if ($cuenta['rol'] === 'paciente'):?><a href="familia.php">Mi familia</a><a href="lista_espera.php">Lista de espera</a><a href="propuestas.php">Propuestas de cambio</a><?php endif ?><a href="<?= destino_rol($cuenta) ?>"><i class="fas fa-table-columns" aria-hidden="true"></i> <?= $cuenta['rol'] === 'paciente' ? 'Mis citas' : 'Mi panel' ?></a><a href="logout.php" class="cuenta-salir"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i> Cerrar sesión</a></div>
    <?php endif ?></li>
  </ul></nav>
  <div class="menu-movil-pie"><span class="menu-pulso" aria-hidden="true"></span><p>Un espacio para cuidar de ti.<small>Reserva y consulta tu próxima atención.</small></p></div>
 </div>
</div></header>
<?php }
function pie($compacto = true)
{ ?>
<link rel="stylesheet" href="css/buscadores.css?v=4939dbded7"><script defer src="js/buscadores.js?v=48def099ca"></script>
<footer class="site-footer<?= $compacto ? ' is-compact' : '' ?>"><div class="container">
 <?php if (!$compacto): ?><div class="footer-cta"><div><p class="footer-etiqueta">UN PASO MÁS CERCA DE TU BIENESTAR</p><h2>Haz espacio para tu salud.</h2></div><a class="boton boton-secundario" href="reserva.php">Solicitar una cita <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div><?php endif ?>
 <div class="footer-marca-fila"><a class="footer-marca" href="index.php"><img src="imagenes/marca/reservacitas-256.png" width="40" height="40" alt=""><span><strong>ReservaCitas</strong><small>Clínica San Pablo</small></span></a><p>Tus citas, con más tranquilidad.</p><nav aria-label="Enlaces del sitio"><a href="especialidades.php">Especialidades</a><a href="contacto.php">Contacto</a><a href="ayuda.php">Ayuda</a><a href="privacidad.php">Privacidad</a><a href="terminos.php">Términos</a></nav></div>
 <div class="footer-legal"><span>© <?= date('Y') ?> ReservaCitas</span><span>Gestión de citas médicas</span></div>
</div></footer>
<?php }
function abrir_pagina($titulo, $pagina = 'cuenta', $estilos = [])
{ ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titulo) ?> · ReservaCitas</title>
<link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="css/base.css?v=f1dbab84d9"><link rel="stylesheet" href="vendor/inter/fonts.css"><link rel="stylesheet" href="vendor/fontawesome/css/all.min.css"><link rel="stylesheet" href="css/admin.css?v=f90bb98122"><?php foreach ($estilos as $css): ?><link rel="stylesheet" href="css/<?= e($css) ?>"><?php endforeach ?><link rel="icon" href="favicon.ico?v=30"><link rel="apple-touch-icon" href="imagenes/marca/reservacitas-180.png"><meta name="theme-color" content="#12365f"><link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head><body data-pagina="<?= e($pagina) ?>"><a class="saltar" href="#contenido">Saltar al contenido</a><?php cabecera(); ?>
<main id="contenido" class="container pagina-gestion">
<?php if (in_array(usuario_actual()['rol'] ?? '', ['medico','asistencial'], true)):?><p><a class="boton boton-secundario" href="operaciones.php">Operación diaria</a></p><?php endif ?>
<?php }
function cerrar_pagina()
{ ?></main><?php pie(); ?><script src="js/menu.js?v=a72d8155da" defer></script><script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/csrf.js?v=7f4dc7fec3" defer></script><script src="js/interfaz.js?v=b4f46b80fe" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body></html><?php }
function encabezado_gestion($titulo, $descripcion, $admin = false)
{
    $cuenta = usuario_actual(); ?>
<div class="gestion-cabecera"><div><p class="eyebrow"><?= $admin ? mb_strtoupper(ROLES[$cuenta['rol']]) : 'TU ESPACIO PERSONAL' ?></p><h1><?= e($titulo) ?></h1><p><?= e($descripcion) ?></p></div><a class="boton boton-secundario" href="<?= destino_rol($cuenta) ?>"><?= !$cuenta ? 'Ingresar' : ($cuenta['rol'] === 'paciente' ? 'Mis citas' : 'Mi panel') ?></a></div>
<?php if ($admin) {
    menu_admin();
}
}
function menu_admin()
{ ?>
<nav class="nav nav-pills gestion-pestanas" aria-label="Administración">
<?php $cuenta = usuario_actual();
    $pestanas = [destino_rol($cuenta) => 'Resumen','agenda.php' => 'Agenda','operaciones.php' => 'Operación diaria','disponibilidad.php' => 'Primer horario','calendario.php' => 'Mover citas','turnero.php' => 'Sala de espera','seguimientos.php' => 'Seguimientos','reorganizar.php' => 'Reorganizar citas','lista_espera.php' => 'Lista de espera','admin_pacientes.php' => 'Pacientes','admin_consultas.php' => 'Consultas'];
    if ($cuenta['rol'] === 'admin') {
        $pestanas += ['admin_medicos.php' => 'Médicos y horarios','admin_especialidades.php' => 'Especialidades','admin_usuarios.php' => 'Usuarios y roles','consultorios.php' => 'Consultorios','configuracion.php' => 'Reglas de agenda','reportes.php' => 'Reportes','auditoria.php' => 'Auditoría'];
    }
    foreach ($pestanas as $url => $texto):
        $actual = ruta_actual() === $url; ?>
        <a class="nav-link<?= $actual ? ' active' : '' ?>" href="<?= e($url) ?>"<?= $actual ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a>
    <?php endforeach ?>
</nav><?php }
function campo_csrf()
{
    echo '<input type="hidden" name="csrf" value="'.e(token_csrf()).'">';
}
function etiqueta_estado($estado)
{
    echo '<span class="estado-cita estado-'.e($estado).'">'.e(ESTADOS_CITA[$estado] ?? $estado).'</span>';
}
function fecha_texto($fecha)
{
    return date('d/m/Y', strtotime($fecha));
}
