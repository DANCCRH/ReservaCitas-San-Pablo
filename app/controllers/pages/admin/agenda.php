<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
require_once BASE_PATH.'/app/models/agenda_visual.php';
$u = exigir_usuario(['admin', 'recepcion', 'medico']);
$gestion = in_array($u['rol'], ['admin', 'recepcion'], true);
$fecha = sanitizar($_GET['fecha'] ?? date('Y-m-d'));
$d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
if (!$d || $d->format('Y-m-d') !== $fecha) {
    $d = new DateTimeImmutable('today');
    $fecha = $d->format('Y-m-d');
}
$vista = in_array($_GET['vista'] ?? '', ['horarios', 'dia', 'semana'], true) ? $_GET['vista'] : 'horarios';
$mid = (int)($_GET['medico'] ?? 0);
$profesional_propio = $gestion ? 0 : medico_del_usuario($u);
if (!$gestion && $mid && $mid !== $profesional_propio) {
    http_response_code(403);
    require_once BASE_PATH.'/app/views/shared/plantilla_resultado.php';
    mostrar_resultado(false, 'Acceso restringido', 'Solo puedes consultar la agenda del profesional vinculado a tu cuenta.', destino_rol($u), 'Mi panel');
}
if (!$gestion) {
    $mid = $profesional_propio;
}
$eid = $gestion ? (int)($_GET['esp'] ?? 0) : 0;
$condiciones = [];
$valores = [];
if ($eid) {
    $condiciones[] = 'm.especialidad_id=?';
    $valores[] = $eid;
}
if (!$gestion || $mid) {
    $condiciones[] = 'm.id=?';
    $valores[] = $mid;
}
$desde_medicos = ' FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id';
$filtro_equipo = $condiciones ? ' WHERE '.implode(' AND ', $condiciones) : '';
$orden_equipo = ' ORDER BY m.activo DESC,e.nombre,m.nombres,m.id';
$seleccionados = [];
$paginas_equipo = $equipo_pagina = 1;
if ($vista === 'horarios') {
    $primero = $gestion && !array_key_exists('medico', $_GET) && !$eid;
    if (!$mid && !$primero && $gestion) {
        $s = $conexion->prepare('SELECT COUNT(*)'.$desde_medicos.$filtro_equipo);
        $s->execute($valores);
        $paginas_equipo = max(1,(int)ceil($s->fetchColumn()/6));
        $equipo_pagina = min($paginas_equipo,max(1,(int)($_GET['equipo_pagina'] ?? 1)));
    }
    $limite = $primero ? 1 : 6;
    $s = $conexion->prepare('SELECT m.*,e.nombre especialidad,e.activo especialidad_activa'.$desde_medicos.$filtro_equipo.$orden_equipo.' LIMIT '.$limite.' OFFSET '.(($equipo_pagina-1)*6));
    $s->execute($valores);
    $seleccionados = $s->fetchAll();
    if ($primero) $mid = (int)($seleccionados[0]['id'] ?? 0);
}
$desde = $vista === 'semana' ? $d->modify('-'.((int)$d->format('N') - 1).' days') : $d;
$hasta = $vista === 'semana' ? $desde->modify('+6 days') : $d;
$citas = [];
if ($vista === 'horarios') {
    $agenda_horas = agenda_por_horarios($seleccionados, $fecha);
} else {
    $filtros_cita = [...$condiciones, 'c.fecha_cita BETWEEN ? AND ?'];
    $s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id WHERE '.implode(' AND ', $filtros_cita).' ORDER BY c.fecha_cita,c.hora_cita,c.id LIMIT 501');
    $s->execute([...$valores, $desde->format('Y-m-d'), $hasta->format('Y-m-d')]);
    $citas = $s->fetchAll();
}
