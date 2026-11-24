<?php require_once __DIR__.'/../../config/conexion.php';
iniciar_sesion_segura();
respuesta_privada();
$cuenta = usuario_actual();
if ($cuenta && $cuenta['rol'] !== 'paciente' && empty($reserva_personal)) {
    header('Location: '.destino_rol($cuenta));
    exit;
} ?>
<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../../models/agenda_modelo.php';
$semilla = null;
$recuperar = $_SESSION['formulario_anterior'] ?? null;
if ($recuperar && $recuperar['pagina'] === 'reserva.php') {
    $d = $recuperar['datos'];
    foreach (['especialidad_id' => 'esp','medico_id' => 'medico','fecha_cita' => 'fecha','hora_cita' => 'hora','paciente_id' => 'paciente'] as $a => $b) {
        if (isset($d[$a])) {
            $_GET[$b] = $d[$a];
        }
    }
}

if ($cuenta && $cuenta['rol'] === 'paciente') {
    $paciente_reserva = paciente_autorizado($cuenta, (int)($_GET['paciente'] ?? $cuenta['id']));
}
if (!$cuenta) {
    $_SESSION['continuar_reserva'] = contexto_horario($_GET);
}

$cid = (int)($_GET['cita'] ?? 0);
if ($cid) {
    $u = exigir_usuario();
    $q = $conexion->prepare("SELECT * FROM citas WHERE id=? AND paciente_id=? AND estado IN ('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW()");
    $q->execute([$cid,$paciente_reserva['id'] ?? $u['id']]);
    $semilla = $q->fetch();
    if (!$semilla) {
        http_response_code(404);
        require_once __DIR__.'/../shared/plantilla_resultado.php';
        mostrar_resultado(false, 'Cita no disponible', 'Solo puedes reprogramar tus citas futuras pendientes o confirmadas.', 'mis_solicitudes.php', 'Mis citas');
    }
}
$contexto_reserva = contexto_horario($_GET);
$esp_inicial = (int)($semilla['especialidad_id'] ?? $contexto_reserva['esp'] ?? 0);
$medico_inicial = (int)($semilla['medico_id'] ?? $contexto_reserva['medico'] ?? 0);
if ($medico_inicial) {
    $profesional = medico_agenda($medico_inicial);
    if (!$profesional || !$profesional['activo'] || !$profesional['especialidad_activa'] || ($esp_inicial && $esp_inicial !== (int)$profesional['especialidad_id'])) {
        $medico_inicial = 0;
    } else {
        $esp_inicial = (int)$profesional['especialidad_id'];
    }
}
?>
<!doctype html>

<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="ReservaCitas · Clínica San Pablo: solicita tu cita médica en línea." name="description">
    <title>Reservar Cita — Clínica San Pablo</title>
    <link rel="stylesheet" href="vendor/bootstrap/bootstrap.min.css"><link href="css/base.css?v=f1dbab84d9" rel="stylesheet">
    <link href="vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/inter/fonts.css">
    <link href="css/reserva.css?v=ba88914da1" rel="stylesheet">
  

<link rel="icon" href="favicon.ico?v=20" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="imagenes/marca/reservacitas-32.png?v=20">
<link rel="apple-touch-icon" sizes="180x180" href="imagenes/marca/reservacitas-180.png">
<link rel="manifest" href="site.webmanifest"><meta name="theme-color" content="#12365f">

<link rel="stylesheet" href="css/componentes.css?v=4f7e0748d8"></head>
  <body data-pagina="reserva">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <?php require_once __DIR__.'/../shared/componentes.php';
cabecera(); ?>

    <main id="contenido">
      <section class="cabecera-pagina">
        <p class="eyebrow">UN MOMENTO PARA TU SALUD</p><h1><?= $semilla ? 'Reprograma tu cita.' : 'Reserva tu próxima cita.' ?></h1>
        <p>Elige una especialidad, un médico y un horario disponible. <?= !empty($reserva_personal) ? 'La solicitud se guardará en la cuenta del paciente seleccionado.' : 'Consulta el estado desde Mis Solicitudes.' ?></p>
      </section>
      <div class="contenedor-formulario"><aside class="reserva-guia"><span class="guia-icono"><i class="fas fa-calendar-check" aria-hidden="true"></i></span><h2>Cuidarte puede<br>ser más simple.</h2><p>Prepara tu solicitud en unos minutos. Tú eliges el momento.</p><ol><li><span>1</span><div><strong>Elige tu atención</strong><p>Elige especialidad, profesional y horario.</p></div></li><li><span>2</span><div><strong>Revisa tus datos</strong><p>Comprueba el resumen antes de confirmar.</p></div></li><li><span>3</span><div><strong>Consulta el estado</strong><p>La solicitud aparecerá en tu cuenta.</p></div></li></ol><a href="mis_solicitudes.php">Ir a mis solicitudes <i class="fas fa-arrow-right" aria-hidden="true"></i></a></aside>
        <div class="form-tarjeta"><p><a href="disponibilidad.php?paciente=<?= (int)($paciente_reserva['id']??$cuenta['id']??0) ?>">Encontrar el primer horario disponible</a></p><div class="formulario-titulo"><span class="eyebrow">SOLICITUD DE CITA</span><h2>Cuéntanos cuándo te viene bien.</h2><p>Los campos marcados con * son obligatorios.</p></div>
          <div id="estado-cuenta" class="aviso-reserva" role="status">Comprobando tu cuenta…</div>
          <noscript><p>Activa JavaScript para validar y revisar los datos antes de enviar la solicitud.</p></noscript>
          <form action="<?= !empty($reserva_personal) ? 'gestionar_reserva.php' : ($semilla ? 'procesar_reprogramacion.php' : 'procesar_cita.php') ?>" id="formulario-cita" data-csrf method="POST" novalidate="">
            <?php if (!empty($paciente_reserva)):?><input type="hidden" name="paciente_id" value="<?= (int)$paciente_reserva['id'] ?>"><?php endif ?>
            <input type="hidden" name="id" value="<?= (int)($semilla['id'] ?? 0) ?>"><fieldset id="campos-cita" disabled>
            <div class="form-grupo">
              <label for="nombres">Nombres y Apellidos *</label>
              <input id="nombres" name="nombres" maxlength="150" readonly placeholder="Ej: Juan Pérez" required="" type="text">
            </div>
            <div class="form-grupo">
              <label for="dni">DNI / Documento *</label>
              <input
                id="dni" readonly
                inputmode="numeric"
                maxlength="20"
                name="dni"
                
                placeholder="Ej: 12345678"
                required=""
                type="text">
            </div>
            <div class="form-grupo">
              <label for="correo">Correo electrónico de referencia</label>
              <input id="correo" name="correo" maxlength="100" readonly placeholder="Sin correo propio" type="text">
            </div>
            <div class="form-grupo">
              <label for="telefono">Teléfono *</label>
              <input id="telefono" name="telefono" maxlength="15" autocomplete="tel" placeholder="Ej: 987654321" required="" type="tel">
            </div>
            <div class="form-grupo">
              <label for="especialidad">Especialidad *</label>
              <?php campo_buscador('especialidad', 'especialidad_id', $esp_inicial, ['id' => 'especialidad', 'etiqueta' => 'Especialidad', 'requerido' => true, 'placeholder' => 'Buscar especialidad']); ?>
            </div>
            <div class="form-grupo"><label for="medico_id">Médico *</label><?php campo_buscador('medico', 'medico_id', $medico_inicial, ['id' => 'medico_id', 'etiqueta' => 'Profesional', 'especialidad' => 'especialidad', 'requerido' => true, 'placeholder' => 'Buscar médico', 'disabled' => $esp_inicial === 0]); ?><small class="texto-ayuda">Consulta la disponibilidad del profesional antes de elegir el horario.</small></div><div class="fila-doble">
              <div class="form-grupo">
                <label for="fecha_cita">Fecha de la Cita *</label>
                <input id="fecha_cita" aria-describedby="regla-cita estado-horarios" data-dias-maximos="<?= (int)reglas_agenda()['dias_maximos'] ?>" name="fecha_cita" required="" type="date">
              </div>
              <div class="form-grupo">
                <label for="hora_cita">Hora de la Cita *</label>
                <select aria-describedby="estado-horarios" id="hora_cita" name="hora_cita" required disabled>
                  <option value="">Selecciona una hora</option>
                </select>
              </div>
            </div>
            <small id="regla-cita" class="texto-ayuda">Puedes reservar una cita por especialidad al día. Si eliges otra especialidad, los horarios deben ser compatibles.</small>
            <p id="estado-horarios" class="ayuda-horarios" role="status" aria-atomic="true">Selecciona una especialidad y una fecha para consultar la disponibilidad.</p>
            <template id="enlaces-cita-del-dia">
              <span class="acciones-cita">
                <?php if (!empty($reserva_personal)): ?>
                  <a href="comprobante.php" data-parametro="id" aria-label="Ver cita del paciente">Ver cita</a>
                <?php else: ?>
                  <a href="mis_solicitudes.php?paciente=<?= (int)($paciente_reserva['id'] ?? $cuenta['id'] ?? 0) ?>" data-parametro="cita" aria-label="Ver cita en Mis solicitudes">Ver cita</a>
                  <a href="reserva.php?paciente=<?= (int)($paciente_reserva['id'] ?? $cuenta['id'] ?? 0) ?>" data-parametro="cita" data-reprogramar aria-label="Reprogramar esta cita">Reprogramar</a>
                <?php endif ?>
              </span>
            </template>
            <?php if ($semilla): ?><div class="form-grupo motivo-reprogramacion"><label for="motivo">Motivo de reprogramación *</label><textarea id="motivo" name="motivo" minlength="5" maxlength="250" required></textarea></div><?php endif ?><div class="form-grupo"><label for="comentario">Comentarios adicionales</label>
              <textarea
                id="comentario"
                maxlength="250"
                name="comentario"
                placeholder="Escribe algo breve si lo necesitas..."
              ></textarea>
              <div class="contador" id="contador">0/250</div>
            </div>
            <div class="checkbox-grupo">
              <input id="confirmacion" name="confirmacion" required="" type="checkbox">
              <label for="confirmacion">Entiendo que registraré una solicitud pendiente y podré gestionarla desde mi cuenta *</label>
            </div>
            <div class="boton-grupo">
              <button class="boton boton-secundario" id="btn-revisar" type="button" disabled>
                <i class="fas fa-eye"></i> Revisar Datos
              </button>
              <button class="boton boton-primario" id="btn-guardar" type="submit" disabled>
                <i class="fas fa-save"></i> <?= $semilla ? 'Guardar cambios' : 'Guardar solicitud' ?>
              </button>
            </div>
            </fieldset>
          </form>
        </div>
      </div>
    </main>
    <!-- MODAL DE RESUMEN -->
    <div class="modal-fondo" id="modal-resumen">
      <div class="modal-contenido">
        <h2 class="modal-titulo">Revisa tu solicitud</h2>
        <p class="modal-ayuda">Comprueba tus datos antes de confirmar el envío.</p><div id="contenido-resumen"></div>
        <div class="modal-botones">
          <button class="boton boton-secundario" id="editar-datos" type="button">
            <i class="fas fa-edit"></i> Editar
          </button>
          <button class="boton boton-primario" id="cerrar-modal" type="button">
            <i class="fas fa-check"></i> Confirmar y Guardar
          </button>
        </div>
      </div>
    </div>
    <?php pie(true); ?>
    <script defer="" src="js/menu.js?v=a72d8155da"></script>
    <script>window.RESERVA_PACIENTE = <?= json_encode(isset($paciente_reserva) ? array_intersect_key($paciente_reserva, array_flip(['id','nombres','dni','correo','telefono'])) : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>; window.RESERVA_CONTEXTO = <?= json_encode(contexto_horario($_GET), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.RESERVA_INICIAL = <?= json_encode($semilla, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script><script defer="" src="js/reserva.js?v=61c1138eb3"></script>
    <script src="js/csrf.js?v=7f4dc7fec3" defer></script>
    <script src="js/extras.js?v=2a0488c1d5" defer></script>
  <script src="js/interfaz.js?v=b4f46b80fe" defer></script>
<script src="vendor/bootstrap/bootstrap.bundle.min.js" defer></script><script src="js/gestion.js?v=a71c40c68c" defer></script><script src="js/calidad.js?v=135d72e530" defer></script></body>
</html>
