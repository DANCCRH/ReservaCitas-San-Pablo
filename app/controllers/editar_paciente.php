<?php

require_once __DIR__.'/../config/conexion.php';
exigir_post();
$u = exigir_usuario(['admin','recepcion']);
$id = (int)($_POST['id'] ?? 0);
try {
    comprobar_csrf();
    $p = paciente_autorizado($u, $id);
    $tel = telefono_normalizado(texto_post('telefono', 30));
    $correo = strtolower(texto_post('correo', 100));
    $motivo = texto_post('motivo', 250);
    if (!telefono_valido($tel) || ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) || mb_strlen($motivo) < 5) {
        throw new DomainException('Revisa el teléfono, correo y motivo.');
    }if (!$p['es_invitado'] && $correo !== $p['correo']) {
        throw new DomainException('El correo de una cuenta de acceso debe cambiarlo administración con verificación. Recepción puede corregir el teléfono.');
    }
    $conexion->beginTransaction();
    $conexion->prepare('UPDATE pacientes SET telefono=?,correo=? WHERE id=?')->execute([$tel,$correo ?: null,$id]);
    auditar_evento('pacientes', $id, 'contacto', 'Teléfono o correo administrativo actualizado. Motivo: '.$motivo);
    $conexion->commit();
    aviso('Contacto actualizado. El cambio quedó registrado.');
    header('Location: admin_pacientes.php?id='.$id, true, 303);
    exit;
} catch (Throwable $e) {
    error_formulario($e,'admin_pacientes.php?id='.$id);
}
