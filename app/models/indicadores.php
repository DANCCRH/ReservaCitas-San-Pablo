<?php

require_once __DIR__.'/agenda_modelo.php';
function indicadores_periodo($desde, $hasta, $mid = 0)
{
    global $conexion;
    $v = [$desde,$hasta];
    $f = 'fecha_cita BETWEEN ? AND ?';
    if ($mid) {
        $f .= ' AND medico_id=?';
        $v[] = $mid;
    }$q = $conexion->prepare("SELECT COUNT(*) total,SUM(estado='cancelado') canceladas,SUM(estado='no_asistio') ausentes,AVG(CASE WHEN inicio_atencion IS NOT NULL AND llegada_en IS NOT NULL AND inicio_atencion>=llegada_en THEN TIMESTAMPDIFF(MINUTE,llegada_en,inicio_atencion) END) espera,COUNT(CASE WHEN inicio_atencion IS NOT NULL AND llegada_en IS NOT NULL THEN 1 END) muestra FROM citas WHERE $f");
    $q->execute($v);
    $r = $q->fetch();
    $q = $conexion->prepare('SELECT * FROM turnos'.($mid ? ' WHERE medico_id=?' : ''));
    $q->execute($mid ? [$mid] : []);
    $turnos = $q->fetchAll();
    $ids=$mid?[$mid]:$conexion->query('SELECT id FROM medicos')->fetchAll(PDO::FETCH_COLUMN);
    // Cargar planes una sola vez evita una consulta por médico y por día del reporte.
    $planes=[];
    foreach($turnos as $turno) $planes[$turno['medico_id']][]=$turno;
    $q=$conexion->prepare('SELECT medico_id,desde,turnos_json FROM horarios_versiones WHERE desde<=?'.($mid?' AND medico_id=?':'').' ORDER BY medico_id,desde');
    $q->execute($mid?[$hasta,$mid]:[$hasta]);
    $versiones=[];$cursor=[];
    foreach($q as $version) {
        $version['turnos']=json_decode($version['turnos_json'],true,512,JSON_THROW_ON_ERROR);
        $versiones[$version['medico_id']][]=$version;
    }

    $q=$conexion->prepare('SELECT MAX(primera) FROM (SELECT medico_id,MIN(desde) primera FROM horarios_versiones GROUP BY medico_id) versiones'.($mid?' WHERE medico_id=?':''));$q->execute($mid?[$mid]:[]);$vigencias_desde=$q->fetchColumn();
    $q = $conexion->prepare('SELECT * FROM bloqueos WHERE fecha BETWEEN ? AND ?'.($mid ? ' AND medico_id=?' : ''));
    $q->execute($v);
    $bloqueos = $q->fetchAll();
    $capacidad = 0;
    $q = $conexion->prepare("SELECT medico_id,fecha_cita,hora_cita,duracion FROM citas WHERE $f AND estado NOT IN('cancelado','rechazado')");
    $q->execute($v);
    $reservas = $q->fetchAll();
    $reservas_por_dia = [];
    foreach ($reservas as $c) {
        $reservas_por_dia[$c['fecha_cita']][$c['medico_id']][] = $c;
    }$bloqueos_por_dia = [];
    foreach ($bloqueos as $b) {
        $bloqueos_por_dia[$b['fecha']][$b['medico_id']][] = $b;
    }$ocupados = 0;
    for ($d = new DateTimeImmutable($desde);$d <= new DateTimeImmutable($hasta);$d = $d->modify('+1 day')) {
        $fecha = $d->format('Y-m-d');
        $turnos=[];
        foreach($ids as $idmedico) {
            $i=$cursor[$idmedico]??0;
            while(isset($versiones[$idmedico][$i]) && $versiones[$idmedico][$i]['desde']<=$fecha) {
                $planes[$idmedico]=$versiones[$idmedico][$i]['turnos'];$i++;
            }
            $cursor[$idmedico]=$i;
            foreach($planes[$idmedico]??[] as $t) {$t['medico_id']=$idmedico;$turnos[]=$t;}
        }
        $grupos = [];
        foreach ($turnos as $t) {
            if ((int)$t['dia'] === (int)$d->format('N')) {
                $grupos[$t['medico_id']][] = $t;
            }
        }foreach ($grupos as $medico => $ts) {
            foreach (espacios_de_turnos($ts, $fecha) as $h) {
                $bloqueado = false;
                foreach (($bloqueos_por_dia[$fecha][$medico] ?? []) as $b) {
                    if ($h['hora'] < substr($b['fin'], 0, 5) && $h['fin'] > substr($b['inicio'], 0, 5)) {
                        $bloqueado = true;
                        break;
                    }
                }if ($bloqueado) {
                    continue;
                }$capacidad++;
                foreach (($reservas_por_dia[$fecha][$medico] ?? []) as $c) {
                    if ($h['desde'] < strtotime($fecha.' '.$c['hora_cita']) + $c['duracion'] * 60 && $h['hasta'] > strtotime($fecha.' '.$c['hora_cita'])) {
                        $ocupados++;
                        break;
                    }
                }
            }
        }
    }
    return ['vigencias_desde'=>$vigencias_desde,'historico_estimado'=>$desde<($vigencias_desde?:date('Y-m-d')),'cancelacion' => $r['total'] ? round(100 * $r['canceladas'] / $r['total'], 1) : 0,'inasistencia' => $r['total'] ? round(100 * $r['ausentes'] / $r['total'], 1) : 0,'espera' => $r['espera'] === null ? null : round($r['espera'], 1),'muestra' => $r['muestra'],'capacidad' => $capacidad,'ocupados' => $ocupados,'ocupacion' => $capacidad ? round(100 * $ocupados / $capacidad, 1) : 0];
}
