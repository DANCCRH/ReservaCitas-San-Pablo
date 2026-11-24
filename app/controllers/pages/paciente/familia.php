<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario(['paciente']);
$q = $conexion->prepare('SELECT p.*,r.parentesco FROM responsables_pacientes r JOIN pacientes p ON p.id=r.paciente_id WHERE r.responsable_id=? ORDER BY p.nombres');
$q->execute([$u['id']]);
$familia = $q->fetchAll();
