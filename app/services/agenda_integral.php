<?php
/** Reglas, vigencias y capacidad física compartidas por todos los puntos de reserva. */
function texto_get_integral($clave,$defecto='') {
    $v=$_GET[$clave]??$defecto;return is_string($v)?substr(trim($v),0,100):$defecto;
}
function familia_integral($u) {
    global $conexion;$q=$conexion->prepare('SELECT p.id,p.nombres FROM responsables_pacientes r JOIN pacientes p ON p.id=r.paciente_id WHERE r.responsable_id=? AND p.activo=1 ORDER BY p.nombres');$q->execute([$u['id']]);return $q->fetchAll();
}
function reglas_agenda() {
    global $conexion;
    static $r;
    return $r ??= $conexion->query('SELECT clave,valor FROM reglas_agenda')->fetchAll(PDO::FETCH_KEY_PAIR);
}
function turnos_vigentes($mid,$fecha) {
    global $conexion;
    $q=$conexion->prepare('SELECT turnos_json FROM horarios_versiones WHERE medico_id=? AND desde<=? ORDER BY desde DESC LIMIT 1');$q->execute([$mid,$fecha]);
    $json=$q->fetchColumn();
    if ($json!==false) return json_decode($json,true,512,JSON_THROW_ON_ERROR);
    $q=$conexion->prepare('SELECT * FROM turnos WHERE medico_id=? ORDER BY dia,inicio');$q->execute([$mid]);
    return $q->fetchAll(); // Antes de la primera versión: estimación, identificada en reportes.
}
function guardar_version_horario($mid,$desde,$actor) {
    global $conexion;
    if (!fecha_admitida($desde)) throw new DomainException('El inicio de vigencia debe estar dentro del periodo de reserva.');
    $q=$conexion->prepare('SELECT MAX(desde) FROM horarios_versiones WHERE medico_id=?');$q->execute([$mid]);
    if (($q->fetchColumn() ?: '')>$desde) throw new DomainException('Hay una programación posterior. Modifica la última vigencia o elige una fecha posterior.');
    $q=$conexion->prepare('SELECT dia,inicio,fin,duracion FROM turnos WHERE medico_id=? ORDER BY dia,inicio');$q->execute([$mid]);
    $conexion->prepare('INSERT INTO horarios_versiones(medico_id,desde,turnos_json,creado_por) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE turnos_json=VALUES(turnos_json),creado_por=VALUES(creado_por),creado_en=NOW()')->execute([$mid,$desde,json_encode($q->fetchAll()),$actor]);
    auditar_evento('horarios_versiones',$mid,'vigencia','Desde '.$desde);
}
function consultorio_libre($rid,$fecha,$hora,$duracion,$excluir=0) {
    global $conexion;
    if (!$rid) return false;
    $q=$conexion->prepare('SELECT activo FROM consultorios WHERE id=?'.($conexion->inTransaction()?' FOR UPDATE':''));$q->execute([$rid]);
    if (!$q->fetchColumn()) return false;
    $q=$conexion->prepare("SELECT id FROM citas WHERE consultorio_id=? AND fecha_cita=? AND id<>? AND estado NOT IN('cancelado','rechazado') AND hora_cita<ADDTIME(?,SEC_TO_TIME(?*60)) AND ADDTIME(hora_cita,SEC_TO_TIME(duracion*60))>? LIMIT 1".($conexion->inTransaction()?' FOR UPDATE':''));
    $q->execute([$rid,$fecha,$excluir,$hora,$duracion,$hora]);
    return !$q->fetchColumn();
}
function comprobar_reglas_reserva($paciente,$fecha,$hora,$id=0,$actor=null) {
    global $conexion;
    $r=reglas_agenda();
    if (strtotime($fecha.' '.$hora)<time()+(int)$r['anticipacion_minutos']*60) throw new DomainException('La agenda exige reservar con al menos '.$r['anticipacion_minutos'].' minutos de anticipación.');
    if (!$id) {
        $q=$conexion->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id=? AND estado IN('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW()");$q->execute([$paciente]);
        if ($q->fetchColumn()>=(int)$r['maximas_pendientes']) throw new DomainException('El paciente alcanzó el máximo de '.$r['maximas_pendientes'].' citas futuras activas.');
    } elseif (($actor['rol']??'')==='paciente') {
        $q=$conexion->prepare('SELECT TIMESTAMP(fecha_cita,hora_cita) FROM citas WHERE id=?');$q->execute([$id]);
        if (strtotime($q->fetchColumn())<time()+(int)$r['cancelacion_horas']*3600) throw new DomainException('Para cambiar una cita se requieren '.$r['cancelacion_horas'].' horas de anticipación. Contacta con recepción.');
    }
}
function preferencia_aviso($id,$tipo) {
    global $conexion;
    $campos=['citas_email','recordatorios_email','espera_email','recordatorios_web'];
    if (!in_array($tipo,$campos,true)) throw new LogicException('Preferencia desconocida.');
    $q=$conexion->prepare('SELECT '.$tipo.' FROM preferencias_avisos WHERE usuario_id=?');$q->execute([$id]);$v=$q->fetchColumn();
    return $v===false || (bool)$v;
}
function correo_cita_preferido($pid,$titulo,$mensaje,$tipo='citas_email',$clave=null) {
    global $conexion;
    require_once __DIR__.'/correo.php';
    $q=$conexion->prepare('SELECT id,correo FROM pacientes WHERE id=? UNION SELECT p.id,p.correo FROM responsables_pacientes r JOIN pacientes p ON p.id=r.responsable_id WHERE r.paciente_id=?');$q->execute([$pid,$pid]);
    foreach ($q->fetchAll() as $p) if ($p['correo'] && preferencia_aviso($p['id'],$tipo)) encolar_correo($p['correo'],$titulo,$mensaje,$clave ? $clave.':'.$p['id'] : null);
}
