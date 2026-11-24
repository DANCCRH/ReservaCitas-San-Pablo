<?php

require_once __DIR__.'/../config/conexion.php';
try {
    $especialidad = (int)($_GET['especialidad'] ?? 0);
    $s = $conexion->prepare('SELECT m.id,m.nombres,m.descripcion,e.nombre especialidad FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id WHERE m.activo=1 AND e.activo=1 AND m.especialidad_id=? ORDER BY m.nombres');
    $s->execute([$especialidad]);
    responder_json(['medicos' => $s->fetchAll()]);
} catch (Throwable $e) {
    error_log($e);
    responder_json(['error' => 'No fue posible consultar el catálogo.'], 500);
}
