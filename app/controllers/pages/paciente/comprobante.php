<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/models/agenda_modelo.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario();
$id = (int)($_GET['id'] ?? 0);
$s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,p.correo,p.telefono,m.nombres medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id LEFT JOIN medicos m ON m.id=c.medico_id WHERE c.id=?');
$s->execute([$id]);
$c = $s->fetch();
if($c){$q=$conexion->prepare('SELECT nombre FROM consultorios WHERE id=?');$q->execute([$c['consultorio_id']]);$c['consultorio']=$q->fetchColumn()?:'No registrado';}
if (!$c || !puede_ver_cita($u, $c)) {
    http_response_code(404);
    require_once BASE_PATH.'/app/views/shared/plantilla_resultado.php';
    mostrar_resultado(false, 'Cita no disponible', 'No se encontró una cita accesible desde tu cuenta.', 'mis_solicitudes.php', 'Mis citas');
}
