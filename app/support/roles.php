<?php

/** Los permisos se comprueban en PHP; ocultar un enlace nunca concede ni revoca acceso. */
const ROLES = [
    'admin' => 'Administrador',
    'recepcion' => 'Recepción',
    'medico' => 'Médico',
    'asistencial' => 'Personal asistencial',
    'paciente' => 'Paciente',
];
function destino_rol($usuario)
{
    return match ($usuario['rol'] ?? null) {
        'admin' => 'admin.php',
        'recepcion' => 'recepcion.php',
        'medico' => 'panel_medico.php',
        'asistencial' => 'panel_asistencial.php',
        'paciente' => 'mis_solicitudes.php',
        default => 'login.php',
    };
}
function medico_del_usuario($usuario)
{
    global $conexion;
    static $cache = [];
    if (array_key_exists($usuario['id'], $cache)) {
        return $cache[$usuario['id']];
    }
    $s = $conexion->prepare('SELECT id FROM medicos WHERE usuario_id=?');
    $s->execute([$usuario['id']]);
    return $cache[$usuario['id']] = (int)$s->fetchColumn();
}
function equipo_del_usuario($usuario)
{
    global $conexion;
    static $cache = [];
    if (array_key_exists($usuario['id'], $cache)) {
        return $cache[$usuario['id']];
    }
    $s = $conexion->prepare('SELECT pe.medico_id FROM personal_equipos pe JOIN medicos m ON m.id=pe.medico_id WHERE pe.usuario_id=?');
    $s->execute([$usuario['id']]);
    return $cache[$usuario['id']] = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
}
function puede_ver_cita($usuario, $cita)
{
    return match ($usuario['rol']) {
        'admin', 'recepcion' => true,
        'paciente' => es_responsable($usuario, $cita['paciente_id']),
        'medico' => (int)$cita['medico_id'] > 0 && (int)$cita['medico_id'] === medico_del_usuario($usuario),
        'asistencial' => $cita['fecha_cita'] === date('Y-m-d') && $cita['estado'] === 'confirmado'
            && in_array((int)$cita['medico_id'], equipo_del_usuario($usuario), true),
        default => false,
    };
}
function puede_registrar_llegada($usuario)
{
    return in_array($usuario['rol'], ['recepcion', 'admin'], true);
}

function acciones_cita($usuario, $cita)
{
    if (!puede_ver_cita($usuario, $cita)) {
        return [];
    }
    $futura = strtotime($cita['fecha_cita'].' '.$cita['hora_cita']) > time();
    $acciones = [];
    if (in_array($usuario['rol'], ['admin', 'recepcion'], true)) {
        if ($cita['estado'] === 'pendiente' && $futura) {
            $acciones = ['confirmado' => 'Confirmar cita', 'rechazado' => 'Rechazar solicitud'];
        }
        if (in_array($cita['estado'], ['pendiente', 'confirmado'], true)) {
            $acciones['cancelado'] = 'Cancelar cita';
        }
    }
    if (in_array($usuario['rol'], ['admin', 'medico'], true) && $cita['estado'] === 'confirmado' && !$futura) {
        if (($cita['llegada_en'] && $cita['inicio_atencion']) || $cita['fecha_cita'] < date('Y-m-d')) {
            $acciones['atendido'] = 'Marcar atendida';
        }
        $acciones['no_asistio'] = 'Marcar no asistió';
    }
    return $acciones;
}
