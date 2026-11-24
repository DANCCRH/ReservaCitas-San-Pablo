<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(true);
$desde = sanitizar($_GET['desde'] ?? date('Y-m-01'));
$hasta = sanitizar($_GET['hasta'] ?? date('Y-m-d'));
$mid = (int)($_GET['medico'] ?? 0);
try {
    foreach ([$desde,$hasta] as $f) {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $f);
        if (!$d || $d->format('Y-m-d') !== $f) {
            throw new DomainException('Selecciona fechas válidas.');
        }
    }
    if ($desde > $hasta || strtotime($hasta) - strtotime($desde) > 366 * 86400) {
        throw new DomainException('Selecciona un rango de hasta un año.');
    }
    $where = 'c.fecha_cita BETWEEN ? AND ?'.($mid ? ' AND c.medico_id=?' : '');
    $v = [$desde,$hasta];
    if ($mid) {
        $v[] = $mid;
    }
    $from = ' FROM citas c JOIN pacientes p ON p.id=c.paciente_id LEFT JOIN medicos m ON m.id=c.medico_id WHERE '.$where;
    if (($_GET['exportar'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="citas_'.$desde.'_'.$hasta.'.csv"');
        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['Código','Paciente','DNI','Especialidad','Médico','Fecha','Hora','Estado'], ';');
        $s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico'.$from.' ORDER BY c.fecha_cita,c.hora_cita');
        $s->execute($v);
        while ($c = $s->fetch()) {
            $row = [codigo_cita($c['id']),$c['nombres'],$c['dni'],$c['especialidad'],$c['medico'] ?? 'Sin asignar',$c['fecha_cita'],substr($c['hora_cita'], 0, 5),ESTADOS_CITA[$c['estado']]];
            $row = array_map(fn ($x) => preg_match('/^[=+@\-\t\r\n]/u', (string)$x) ? "'".$x : $x, $row);
            fputcsv($fp, $row, ';');
        }fclose($fp);
        exit;
    }
    $group = function ($expression) use ($conexion, $from, $v) {
        $s = $conexion->prepare('SELECT '.$expression.' nombre,COUNT(*) cantidad'.$from.' GROUP BY nombre ORDER BY cantidad DESC');
        $s->execute($v);
        return array_column($s->fetchAll(), 'cantidad', 'nombre');
    };
    $estados = $group('c.estado');
    $especialidades = $group('c.especialidad');
    $doctores = $group("COALESCE(m.nombres,'Sin asignar')");
    $total = array_sum($estados);
    $paginas = max(1, (int)ceil($total / 20));
    $pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
    $s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico'.$from.' ORDER BY c.fecha_cita DESC,c.hora_cita DESC LIMIT 20 OFFSET '.(($pagina - 1) * 20));
    $s->execute($v);
    $citas = $s->fetchAll();
} catch (Throwable $e) {
    error_formulario($e, 'reportes.php');
}
require_once BASE_PATH.'/app/models/indicadores.php';
$indicadores = indicadores_periodo($desde, $hasta, $mid);
