<?php

/** Reglas administrativas compartidas. No se modifican datos desde las plantillas. */
function telefono_normalizado($valor)
{
    return preg_replace('/[\s()\-]+/u', '', is_string($valor) ? trim($valor) : '');
}
function telefono_valido($valor, $opcional = false)
{
    return ($opcional && $valor === '') || preg_match('/^\+?[0-9]{7,15}$/D', $valor);
}
function clave_post($campo)
{
    $v = $_POST[$campo] ?? '';
    if (!is_string($v) || strlen($v) > 72) {
        throw new DomainException('La contraseña admite hasta 72 bytes.');
    }return $v;
}
function documento_valido($tipo, $numero)
{
    return match($tipo) {
        'DNI' => preg_match('/^\d{8}$/D', $numero),'CE' => preg_match('/^[A-Z0-9]{9,12}$/D', $numero),'PASAPORTE' => preg_match('/^[A-Z0-9]{6,20}$/D', $numero),default => false
    };
}
function auditar_evento($entidad, $id, $accion, $detalle)
{
    global $conexion;
    $u = usuario_actual();
    $conexion->prepare('INSERT INTO auditoria_eventos(usuario_id,entidad,registro_id,accion,detalle) VALUES(?,?,?,?,?)')->execute([$u['id'] ?? null,$entidad,$id ?: null,$accion,mb_substr($detalle, 0, 1000)]);
}
function es_responsable($u, $pid)
{
    global $conexion;
    if ((int)$u['id'] === (int)$pid) {
        return true;
    }static $cache = [];
    $key = $u['id'].':'.$pid;
    if (!array_key_exists($key, $cache)) {
        $q = $conexion->prepare('SELECT 1 FROM responsables_pacientes WHERE responsable_id=? AND paciente_id=?');
        $q->execute([$u['id'],$pid]);
        $cache[$key] = (bool)$q->fetchColumn();
    }return $cache[$key];
}
function paciente_autorizado($u, $pid)
{
    global $conexion;
    if (!in_array($u['rol'], ['admin','recepcion'], true) && !es_responsable($u, $pid)) {
        http_response_code(403);
        throw new DomainException('No tienes acceso a este paciente.');
    }$q = $conexion->prepare("SELECT * FROM pacientes WHERE id=? AND rol='paciente' AND activo=1");
    $q->execute([$pid]);
    $p = $q->fetch();
    if (!$p) {
        throw new DomainException('Selecciona un paciente activo.');
    }return $p;
}
function notificar_persona($id, $titulo, $mensaje, $enlace = 'notificaciones.php')
{
    global $conexion;
    $conexion->prepare('INSERT INTO notificaciones(paciente_id,titulo,mensaje,enlace) VALUES(?,?,?,?)')->execute([$id,$titulo,$mensaje,$enlace]);
}
function citas_futuras_medico($mid, $dia = null, $inicio = null, $fin = null)
{
    global $conexion;
    $sql = "SELECT c.*,p.nombres FROM citas c JOIN pacientes p ON p.id=c.paciente_id WHERE c.medico_id=? AND c.estado IN ('pendiente','confirmado') AND (c.fecha_cita>CURDATE() OR (c.fecha_cita=CURDATE() AND c.hora_cita>CURTIME()))";
    $v = [$mid];
    if ($dia !== null) {
        $sql .= ' AND WEEKDAY(c.fecha_cita)+1=? AND c.hora_cita<? AND ADDTIME(c.hora_cita,SEC_TO_TIME(c.duracion*60))>?';
        array_push($v, $dia, $fin, $inicio);
    }$q = $conexion->prepare($sql.' ORDER BY c.fecha_cita,c.hora_cita');
    $q->execute($v);
    return $q->fetchAll();
}
function guardar_error_datos($retorno, $mensaje)
{
    iniciar_sesion_segura();
    $datos = [];
    foreach ($_POST as $k => $v) {
        if (is_string($v) && !preg_match('/clave|pass|contrasena|csrf|token|actual|nueva|confirmar/i', $k)) {
            $datos[$k] = mb_substr($v, 0, 1500);
        }
    }$_SESSION['formulario_anterior'] = ['pagina' => basename(parse_url($retorno, PHP_URL_PATH) ?? ''),'datos' => $datos,'mensaje' => $mensaje];
}
function registro_operativo($evento, $datos = [])
{
    require_once __DIR__.'/../services/correo.php';
    $ruta = directorio_privado('logs');
    $archivo = $ruta.'/'.date('Y-m-d').'.jsonl';
    foreach (glob($ruta.'/*.jsonl') ?: [] as $f) {
        if (filemtime($f) < time() - 30 * 86400) {
            @unlink($f);
        }
    }$linea = json_encode(['id' => bin2hex(random_bytes(6)),'fecha' => date(DATE_ATOM),'evento' => $evento,'datos' => $datos], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    file_put_contents($archivo,$linea."\n",FILE_APPEND | LOCK_EX);
}
