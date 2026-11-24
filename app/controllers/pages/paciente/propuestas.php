<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
require_once BASE_PATH.'/app/models/agenda_modelo.php';
$u = exigir_usuario(['paciente']);
$q = $conexion->prepare("SELECT x.*,p.nombres paciente,m.nombres medico,c.fecha_cita,c.hora_cita FROM propuestas_citas x JOIN citas c ON c.id=x.cita_id JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=x.medico_id WHERE x.estado='pendiente' AND(c.paciente_id=? OR c.paciente_id IN(SELECT paciente_id FROM responsables_pacientes WHERE responsable_id=?)) ORDER BY x.creado_en DESC LIMIT 50");
$q->execute([$u['id'],$u['id']]);
$propuestas = $q->fetchAll();
