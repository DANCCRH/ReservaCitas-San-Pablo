<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(['admin','recepcion']);
$id = (int)($_GET['id'] ?? 0);
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
