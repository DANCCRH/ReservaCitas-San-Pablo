<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../services/correo.php';
exigir_post();
$u = exigir_usuario(['admin','recepcion']);
try {
    comprobar_csrf();
    $id = id_post('id');
    $accion = texto_post('accion', 20);
    $conexion->beginTransaction();
    $q = $conexion->prepare('SELECT * FROM consultas WHERE id_consulta=? FOR UPDATE');
    $q->execute([$id]);
    $c = $q->fetch();
    if (!$c) {
        throw new DomainException('Consulta no disponible.');
    }
    if ($accion === 'leido' || $accion === 'no_leido') {
        $conexion->prepare('UPDATE consultas SET leido_en='.($accion === 'leido' ? 'NOW()' : 'NULL').' WHERE id_consulta=?')->execute([$id]);
    } elseif ($accion === 'gestionar') {
        $estado = texto_post('estado', 20);
        $asignado = (int)($_POST['asignado_id'] ?? 0);
        $respuesta = texto_post('respuesta', 2000);
        if (!in_array($estado, ['nueva','en_proceso','resuelta'], true) || ($estado === 'resuelta' && mb_strlen($respuesta) < 10)) {
            throw new DomainException('Para resolver una consulta escribe una respuesta de al menos 10 caracteres.');
        }if ($asignado) {
            $q = $conexion->prepare("SELECT id FROM pacientes WHERE id=? AND activo=1 AND rol IN('admin','recepcion')");
            $q->execute([$asignado]);
            if (!$q->fetchColumn()) {
                throw new DomainException('Responsable no válido.');
            }
        }
        $conexion->prepare('UPDATE consultas SET estado=?,asignado_id=?,respuesta=?,respondido_en=IF(?<>\'\',NOW(),NULL),respondido_por=?,leido_en=COALESCE(leido_en,NOW()) WHERE id_consulta=?')->execute([$estado,$asignado ?: null,$respuesta ?: null,$respuesta,$respuesta ? $u['id'] : null,$id]);
        if (isset($_POST['enviar_correo']) && $respuesta) {
            encolar_correo($c['remitente_correo'], 'Respuesta a tu consulta: '.$c['asunto'], $respuesta, 'consulta:'.$id.':'.hash('sha256', $respuesta));
        }
    } else {
        throw new DomainException('Acción no válida.');
    }auditar_evento('consultas', $id, $accion, 'Gestión administrativa de consulta');
    $conexion->commit();
    aviso('Consulta actualizada. El envío externo depende del transporte de correo configurado.');
    header('Location: admin_consultas.php', true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e,'admin_consultas.php');
}
