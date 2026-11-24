<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/componentes.php';
require_once BASE_PATH.'/app/models/agenda_modelo.php';
$u = exigir_usuario(['admin','recepcion']);
$contexto = contexto_horario($_GET);
