<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(['admin','recepcion']);
$mid = (int)($_GET['medico'] ?? 0);
$eid = (int)($_GET['esp'] ?? 0);
$origen = $mid ? medico_agenda($mid) : null;
if (!$origen || ($eid && $eid !== (int)$origen['especialidad_id'])) $mid = 0;
$citas = $mid ? citas_futuras_medico($mid) : [];
