<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario(['paciente','admin','recepcion']);
$staff = $u['rol'] !== 'paciente';
$q = $conexion->prepare('SELECT l.*,p.nombres paciente,e.nombre especialidad,m.nombres medico FROM lista_espera l JOIN pacientes p ON p.id=l.paciente_id JOIN especialidades e ON e.id=l.especialidad_id LEFT JOIN medicos m ON m.id=l.medico_id'.($staff ? '' : ' WHERE l.paciente_id=? OR l.paciente_id IN(SELECT paciente_id FROM responsables_pacientes WHERE responsable_id=?)').' ORDER BY l.id DESC LIMIT 100');
$q->execute($staff ? [] : [$u['id'],$u['id']]);
$filas = $q->fetchAll();
