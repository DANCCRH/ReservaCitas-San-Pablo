<?php

/** Compatibilidad con enlaces antiguos: el comprobante vuelve a comprobar la propiedad de la cita. */
require_once __DIR__.'/../config/conexion.php';
$u = exigir_usuario(['paciente']);
$id = (int)($_SESSION['cita_guardada']['id'] ?? 0);
header('Location: '.($id ? 'comprobante.php?id='.$id : destino_rol($u)));
exit;
