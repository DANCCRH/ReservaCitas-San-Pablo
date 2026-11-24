<?php
/** Vista compartida con ámbitos diferentes, definidos siempre desde la cuenta autenticada. */
if (!isset($usuario_panel)) {
    http_response_code(403);
    exit;
}
require_once __DIR__.'/admin_componentes.php';
$asistencial = $usuario_panel['rol'] === 'asistencial';
$fecha = $asistencial ? date('Y-m-d') : sanitizar($_GET['fecha'] ?? date('Y-m-d'));
$d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) {
    $fecha = date('Y-m-d');
}
$ids = $asistencial ? equipo_del_usuario($usuario_panel) : [medico_del_usuario($usuario_panel)];
$ids = array_values(array_filter($ids));
$where = 'c.medico_id IN ('.implode(',', array_fill(0, max(1, count($ids)), '?')).') AND c.fecha_cita=?';
$params = array_merge($ids ?: [0], [$fecha]);
if ($asistencial) {
    $where .= " AND c.estado='confirmado'";
}
$s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id WHERE '.$where.' ORDER BY c.hora_cita,c.id');
$s->execute($params);
$citas = $s->fetchAll();
$resumen_medico = null;
if (!$asistencial && $ids) {
    $s = $conexion->prepare("SELECT COUNT(*) atenciones,COUNT(DISTINCT paciente_id) pacientes FROM citas WHERE medico_id=? AND estado='atendido' AND fecha_cita>=DATE_SUB(CURDATE(),INTERVAL 90 DAY)");
    $s->execute([$ids[0]]);
    $resumen_medico = $s->fetch();
}
abrir_pagina($asistencial ? 'Atención del día' : 'Mi agenda médica');
encabezado_gestion($asistencial ? 'Atención del día' : 'Mi agenda médica', $asistencial ? 'Consulta las llegadas registradas en recepción y los turnos del equipo asignado.' : 'Consulta tus citas, llama a los pacientes que ya llegaron e inicia su atención.');
ver_aviso();
?>
<?php if ($resumen_medico): ?><section class="panel-bienvenida"><div><h2>Tu atención hace la diferencia.</h2><p><?= (int)$resumen_medico['atenciones'] ?> atenciones realizadas a <?= (int)$resumen_medico['pacientes'] ?> pacientes en los últimos 90 días.</p></div><span>Mi agenda profesional</span></section><?php endif ?><p class="rol-identidad"><span class="estado-cita"><?= e(ROLES[$usuario_panel['rol']]) ?></span> <?= e($usuario_panel['nombres']) ?></p>
<?php if (!$ids): ?><p class="alert alert-info">Tu cuenta aún no tiene <?= $asistencial ? 'un equipo asignado' : 'un profesional vinculado' ?>. Solicita la asignación al administrador.</p><?php endif ?>
<?php if (!$asistencial): ?><form method="get" class="gestion-filtros"><label>Fecha de atención<input type="date" name="fecha" value="<?= e($fecha) ?>" required></label><button class="boton boton-primario">Consultar agenda</button></form><?php endif ?>
<?php if (!$asistencial): ?><p><a class="boton boton-secundario" href="agenda.php?vista=horarios&amp;fecha=<?= e($fecha) ?>">Ver mi disponibilidad por horarios</a></p><?php endif ?>
<div class="metricas-final"><div class="metrica-destacada"><span>Citas <?= fecha_texto($fecha) ?></span><strong><?= count($citas) ?></strong></div><div><span>Confirmadas</span><strong><?= count(array_filter($citas, fn ($c) => $c['estado'] === 'confirmado')) ?></strong></div><div><span>Llegadas registradas</span><strong><?= count(array_filter($citas, fn ($c) => !empty($c['llegada_en']))) ?></strong></div></div>
<section class="gestion-tarjeta sin-relleno"><div class="gestion-panel-titulo"><h2><?= $asistencial ? 'Pacientes del equipo' : 'Atenciones asignadas' ?></h2></div><?php tabla_citas($citas); ?></section>
<?php cerrar_pagina(); ?>
