<?php
require_once __DIR__.'/../models/agenda_modelo.php';
/** Busca por fecha y hora en todos los profesionales activos de la especialidad. */
function buscar_proxima_cita($paciente,$eid,$desde,$dias=30) {
    global $conexion;
    if (!fecha_admitida($desde)) throw new DomainException('Elige una fecha válida para iniciar la búsqueda.');
    $q=$conexion->prepare('SELECT m.*,e.activo especialidad_activa FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id WHERE e.id=? AND e.activo=1 AND m.activo=1 ORDER BY m.id');$q->execute([$eid]);$medicos=$q->fetchAll();
    $encontrados=[];
    for ($i=0;$i<min(30,max(1,$dias));$i++) {
        $fecha=date('Y-m-d',strtotime($desde.' +'.$i.' days'));
        if (!fecha_admitida($fecha)) break;
        foreach ($medicos as $m) foreach (horarios_disponibles($m['id'],$fecha,$paciente) as $h) if ($h['disponible']) $encontrados[]=['medico_id'=>(int)$m['id'],'medico'=>$m['nombres'],'especialidad_id'=>(int)$eid,'fecha'=>$fecha,'hora'=>$h['hora'],'duracion'=>$h['duracion']];
        if (count($encontrados)>=5) break;
    }
    usort($encontrados,fn($a,$b)=>[$a['fecha'],$a['hora'],$a['medico_id']]<=>[$b['fecha'],$b['hora'],$b['medico_id']]);
    return array_slice($encontrados,0,5);
}
function fechas_serie($datos) {
    $cantidad=(int)($datos['cantidad']??0);$intervalo=(int)($datos['intervalo']??0);
    if ($cantidad<2 || $cantidad>(int)reglas_agenda()['maximas_serie'] || !in_array($intervalo,[7,14,28],true)) throw new DomainException('Elige entre 2 y '.reglas_agenda()['maximas_serie'].' citas, cada 1, 2 o 4 semanas.');
    if (!fecha_admitida($datos['fecha_cita']??'')) throw new DomainException('Revisa la fecha inicial.');
    $fechas=[];
    for ($i=0;$i<$cantidad;$i++) $fechas[]=date('Y-m-d',strtotime($datos['fecha_cita'].' +'.($i*$intervalo).' days'));
    return $fechas;
}
function previsualizar_serie($p,$datos) {
    global $conexion;
    $m=medico_agenda((int)($datos['medico_id']??0));
    if (!$m || !$m['activo']) throw new DomainException('Selecciona un profesional activo.');
    $q=$conexion->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id=? AND estado IN('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW()");$q->execute([$p['id']]);$actuales=(int)$q->fetchColumn();
    $fechas=fechas_serie($datos);$resultado=[];
    foreach ($fechas as $fecha) {
        $motivo='';$libre=false;
        try {
            if ($actuales+count($fechas)>(int)reglas_agenda()['maximas_pendientes']) throw new DomainException('La serie supera el máximo de citas futuras del paciente.');
            comprobar_reglas_reserva($p['id'],$fecha,$datos['hora_cita']);
            $disponibilidad=disponibilidad_reserva($m['id'],$fecha,$p['id']);
            if ($disponibilidad['cita_del_dia']) throw new DomainException($disponibilidad['mensaje']);
            foreach ($disponibilidad['horas'] as $h) if ($h['hora']===$datos['hora_cita'] && $h['disponible']) $libre=true;
            if (!$libre) $motivo='Sin turno libre: revisa médico, paciente y consultorio.';
        } catch (DomainException $e) { $motivo=$e->getMessage(); }
        $resultado[]=['fecha'=>$fecha,'hora'=>$datos['hora_cita'],'libre'=>$libre && !$motivo,'motivo'=>$motivo];
    }
    return $resultado;
}
/** Serie indivisible; la clave única permite repetir el envío sin duplicar las citas. */
function guardar_serie($u,$p,$datos,$solicitud) {
    global $conexion;
    if (!preg_match('/^[a-f0-9]{64}$/D',$solicitud)) throw new DomainException('Vuelve a revisar la serie.');
    $conexion->beginTransaction();
    $conexion->prepare('SELECT id FROM pacientes WHERE id=? FOR UPDATE')->execute([$p['id']]);
    $q=$conexion->prepare('SELECT id,paciente_id,creado_por FROM series_citas WHERE solicitud=?');$q->execute([$solicitud]);$prev=$q->fetch();
    if ($prev) {
        if ((int)$prev['paciente_id']!==(int)$p['id'] || (int)$prev['creado_por']!==(int)$u['id']) throw new DomainException('Solicitud no válida.');
        $conexion->commit();return (int)$prev['id'];
    }
    medico_agenda((int)$datos['medico_id'],true);
    $m=medico_agenda((int)$datos['medico_id']);
    $conexion->prepare('SELECT id FROM consultorios WHERE id=? FOR UPDATE')->execute([$m['consultorio_id']]);
    $preview=previsualizar_serie($p,$datos);
    foreach ($preview as $item) if (!$item['libre']) throw new DomainException('La serie tiene conflictos. No se guardó ninguna cita; vuelve a revisarla.');
    $conexion->prepare('INSERT INTO series_citas(paciente_id,creado_por,solicitud,intervalo_dias,cantidad) VALUES(?,?,?,?,?)')->execute([$p['id'],$u['id'],$solicitud,(int)$datos['intervalo'],count($preview)]);$sid=(int)$conexion->lastInsertId();
    foreach ($preview as $item) {
        $id=guardar_reserva($p,array_merge($datos,['fecha_cita'=>$item['fecha'],'especialidad_id'=>$m['especialidad_id']]),0,$u);
        $conexion->prepare('UPDATE citas SET serie_id=? WHERE id=?')->execute([$sid,$id]);
    }
    auditar_evento('series_citas',$sid,'crear',count($preview).' citas de seguimiento');$conexion->commit();return $sid;
}
function confirmar_asistencia($u,$id) {
    global $conexion;
    $conexion->beginTransaction();$q=$conexion->prepare('SELECT * FROM citas WHERE id=? FOR UPDATE');$q->execute([$id]);$c=$q->fetch();
    if (!$c || $u['rol']!=='paciente' || !es_responsable($u,$c['paciente_id'])) {http_response_code(403);throw new DomainException('Solo el paciente o su responsable confirma asistencia.');}
    if ($c['estado']!=='confirmado' || strtotime($c['fecha_cita'].' '.$c['hora_cita'])<=time()) throw new DomainException('Solo puedes confirmar tu asistencia a una cita aprobada y futura.');
    if (!$c['asistencia_confirmada_en']) {
        $conexion->prepare('UPDATE citas SET asistencia_confirmada_en=NOW(),asistencia_confirmada_por=? WHERE id=?')->execute([$u['id'],$id]);
        auditar_cita($id,$c['estado'],$c['estado'],$u['id'],'asistencia','El paciente o responsable confirmó que asistirá.');
    }
    $conexion->commit();
}
function llamar_paciente($u,$id) {
    global $conexion;
    $conexion->beginTransaction();$q=$conexion->prepare('SELECT * FROM citas WHERE id=? FOR UPDATE');$q->execute([$id]);$c=$q->fetch();
    if (!$c || !in_array($u['rol'],['admin','medico','asistencial'],true) || !puede_ver_cita($u,$c)) {http_response_code(403);throw new DomainException('El llamado corresponde al equipo clínico asignado o al administrador.');}
    if ($c['fecha_cita']!==date('Y-m-d') || $c['estado']!=='confirmado' || !$c['llegada_en'] || $c['inicio_atencion']) throw new DomainException('El llamado requiere una llegada registrada, sin consulta iniciada, en una cita de hoy.');
    $codigo=$c['codigo_turno'] ?: strtoupper(bin2hex(random_bytes(4)));
    $conexion->prepare('UPDATE citas SET codigo_turno=?,llamado_en=NOW() WHERE id=?')->execute([$codigo,$id]);
    auditar_cita($id,$c['estado'],$c['estado'],$u['id'],'llamado','Llamado por código de turno.');$conexion->commit();
}
