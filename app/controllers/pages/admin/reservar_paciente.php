<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$operador = exigir_usuario(['admin','recepcion']);
$contexto = contexto_horario($_GET);
$pid = (int)($_GET['paciente'] ?? 0);
if ($pid) {
    $s = $conexion->prepare("SELECT id,nombres,dni,correo,telefono FROM pacientes WHERE id=? AND rol='paciente' AND activo=1");
    $s->execute([$pid]);
    $paciente_reserva = $s->fetch();
    if (!$paciente_reserva) {
        http_response_code(404);
        require_once BASE_PATH.'/app/views/shared/plantilla_resultado.php';
        mostrar_resultado(false, 'Paciente no disponible', 'Selecciona una cuenta activa de paciente.', 'reservar_paciente.php', 'Buscar paciente');
    }
    $reserva_personal = true;
    require BASE_PATH.'/app/views/paciente/reserva.php';
    exit;
}
