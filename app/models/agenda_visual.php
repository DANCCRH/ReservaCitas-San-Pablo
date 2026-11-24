<?php

require_once __DIR__.'/agenda_modelo.php';

/** Consulta en conjunto los turnos, bloqueos y citas de los profesionales autorizados. */
function agenda_por_horarios($medicos, $fecha)
{
    global $conexion;
    if (!$medicos) {
        return [];
    }
    $ids = array_column($medicos, 'id');
    $lista = implode(',', array_fill(0, count($ids), '?'));
    $dia = (int)(new DateTimeImmutable($fecha))->format('N');
    $s = $conexion->prepare('SELECT * FROM turnos WHERE medico_id IN ('.$lista.') AND dia=? ORDER BY inicio');
    $s->execute([...$ids, $dia]);
    $turnos = [];
    foreach ($s as $t) {
        $turnos[$t['medico_id']][] = $t;
    }
    $s = $conexion->prepare('SELECT * FROM bloqueos WHERE medico_id IN ('.$lista.') AND fecha=? ORDER BY inicio');
    $s->execute([...$ids, $fecha]);
    $bloqueos = [];
    foreach ($s as $b) {
        $bloqueos[$b['medico_id']][] = $b;
    }
    $s = $conexion->prepare("SELECT c.*,p.nombres paciente FROM citas c JOIN pacientes p ON p.id=c.paciente_id WHERE c.medico_id IN ($lista) AND c.fecha_cita=? AND c.estado NOT IN ('cancelado','rechazado') ORDER BY c.hora_cita,c.id");
    $s->execute([...$ids, $fecha]);
    $citas = [];
    foreach ($s as $c) {
        $citas[$c['medico_id']][] = $c;
    }
    $resultado = [];
    $ahora = time();
    $fecha_reservable = fecha_admitida($fecha);
    foreach ($medicos as $m) {
        $mid = $m['id'];
        $horarios = array_values(array_filter(turnos_vigentes($mid,$fecha),fn($t)=>(int)$t['dia']===$dia));
        $espacios = [];
        foreach (espacios_de_turnos($horarios, $fecha) as $e) {
            $e['tipo'] = !$m['activo'] || !$m['especialidad_activa'] ? 'inactivo' : (!$fecha_reservable || $e['desde'] <= $ahora ? 'pasado' : 'libre');
            $e['citas'] = [];
            if($e['tipo']==='libre' && (!consultorio_libre($m['consultorio_id'],$fecha,$e['hora'],$e['duracion']) || $e['desde']<=time()+(int)reglas_agenda()['anticipacion_minutos']*60)) $e['tipo']='bloqueado';
            $e['motivos'] = $e['tipo']==='bloqueado' ? ['Consultorio ocupado/no habilitado o fuera de anticipación mínima.'] : [];
            foreach ($bloqueos[$mid] ?? [] as $b) {
                if ($e['desde'] < strtotime($fecha.' '.$b['fin']) && $e['hasta'] > strtotime($fecha.' '.$b['inicio'])) {
                    $e['tipo'] = 'bloqueado';
                    $e['motivos'][] = $b['motivo'];
                }
            }
            foreach ($citas[$mid] ?? [] as $c) {
                $desde = strtotime($fecha.' '.$c['hora_cita']);
                if ($e['desde'] < $desde + (int)$c['duracion'] * 60 && $e['hasta'] > $desde) {
                    $e['tipo'] = 'ocupado';
                    $e['citas'][] = $c;
                }
            }
            $espacios[] = $e;
        }
        $fuera_turno = array_values(array_filter($citas[$mid] ?? [], function ($c) use ($horarios, $fecha) {
            $inicio = strtotime($fecha.' '.$c['hora_cita']);
            $fin = $inicio + (int)$c['duracion'] * 60;
            foreach ($horarios as $t) {
                if ($inicio >= strtotime($fecha.' '.$t['inicio']) && $fin <= strtotime($fecha.' '.$t['fin'])) {
                    return false;
                }
            }
            return true;
        }));
        $resultado[] = ['medico' => $m, 'turnos' => $horarios, 'espacios' => $espacios, 'fuera_turno' => $fuera_turno];
    }
    return $resultado;
}
