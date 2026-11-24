<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../services/operaciones.php';
exigir_post();
$u = exigir_usuario();
$retorno = 'operaciones.php';
try {
    comprobar_csrf();
    $accion = texto_post('accion', 30);
    if ($accion === 'inicio_atencion') {
        if ($u['rol'] === 'medico') $retorno = destino_rol($u);
        iniciar_atencion($u, id_post('id'));
    } elseif ($accion === 'bloquear_rango') {
        if ($u['rol'] !== 'admin') {
            throw new DomainException('Solo administración modifica bloqueos.');
        }$retorno = 'reorganizar.php';
        bloquear_rango($u, id_post('medico_id'), texto_post('desde', 10), texto_post('hasta', 10), texto_post('inicio', 5), texto_post('fin', 5), texto_post('motivo', 250));
    } elseif ($accion === 'proponer') {
        if (!in_array($u['rol'], ['admin','recepcion'], true)) {
            throw new DomainException('Acceso restringido.');
        }$retorno = 'reorganizar.php';
        $ids = $_POST['citas'] ?? [];
        if (!is_array($ids)) {
            throw new DomainException('Selecciona citas válidas.');
        }proponer_cambios($u, $ids, id_post('medico_id'), texto_post('desde', 10), texto_post('motivo', 250));
    } elseif ($accion === 'aceptar_propuesta' || $accion === 'rechazar_propuesta') {
        $retorno = 'propuestas.php';
        responder_propuesta($u, id_post('id'), $accion === 'aceptar_propuesta');
    } else {
        throw new DomainException('Acción no válida.');
    }aviso('La operación se guardó correctamente.');
    header('Location: '.$retorno, true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e,$retorno);
}
