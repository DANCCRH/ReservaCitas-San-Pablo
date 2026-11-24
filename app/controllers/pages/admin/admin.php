<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(['admin','recepcion']);
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$estado = sanitizar($_GET['estado'] ?? '');
$fecha = sanitizar($_GET['fecha'] ?? '');
$mid = (int)($_GET['medico'] ?? 0);
$condiciones = [];
$valores = [];
$filtro_paciente = (int)($_GET['paciente'] ?? 0);
if ($filtro_paciente) {
    $condiciones[] = 'p.id=?';
    $valores[] = $filtro_paciente;
} elseif ($q) {
    $condiciones[] = '(p.nombres LIKE ? OR p.dni LIKE ? OR p.correo LIKE ?)';
    array_push($valores, '%'.$q.'%', '%'.$q.'%', '%'.$q.'%');
}
if (array_key_exists($estado, ESTADOS_CITA)) {
    $condiciones[] = 'c.estado=?';
    $valores[] = $estado;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $condiciones[] = 'c.fecha_cita=?';
    $valores[] = $fecha;
}
if ($mid) {
    $condiciones[] = 'c.medico_id=?';
    $valores[] = $mid;
}
$from = ' FROM citas c JOIN pacientes p ON p.id=c.paciente_id LEFT JOIN medicos m ON m.id=c.medico_id'.($condiciones ? ' WHERE '.implode(' AND ', $condiciones) : '');
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($valores);
$total = (int)$s->fetchColumn();
$paginas = max(1, (int)ceil($total / 15));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico'.$from.' ORDER BY c.fecha_cita DESC,c.hora_cita DESC LIMIT 15 OFFSET '.(($pagina - 1) * 15));
$s->execute($valores);
$citas = $s->fetchAll();
$conteos = array_column($conexion->query('SELECT estado,COUNT(*) cantidad FROM citas GROUP BY estado')->fetchAll(), 'cantidad', 'estado');
$nuevas = (int)$conexion->query('SELECT COUNT(*) FROM consultas WHERE leido_en IS NULL')->fetchColumn();
$pacientes_total = (int)$conexion->query("SELECT COUNT(*) FROM pacientes WHERE rol='paciente' AND es_invitado=0")->fetchColumn();
$medicos_total = (int)$conexion->query('SELECT COUNT(*) FROM medicos WHERE activo=1')->fetchColumn();
$pacientes_atendidos = (int)$conexion->query("SELECT COUNT(DISTINCT paciente_id) FROM citas WHERE estado='atendido'")->fetchColumn();
$hoy = (int)$conexion->query("SELECT COUNT(*) FROM citas WHERE fecha_cita=CURDATE() AND estado IN ('pendiente','confirmado','atendido','no_asistio')")->fetchColumn();
$primero = (new DateTimeImmutable('first day of this month'))->modify('-2 months');
$s = $conexion->prepare("SELECT DATE_FORMAT(fecha_cita,'%Y-%m') mes,COUNT(*) cantidad FROM citas WHERE fecha_cita BETWEEN ? AND CURDATE() GROUP BY mes ORDER BY mes");
$s->execute([$primero->format('Y-m-d')]);
$serie = array_column($s->fetchAll(), 'cantidad', 'mes');
$meses = ['01' => 'Enero','02' => 'Febrero','03' => 'Marzo','04' => 'Abril','05' => 'Mayo','06' => 'Junio','07' => 'Julio','08' => 'Agosto','09' => 'Septiembre','10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre'];
