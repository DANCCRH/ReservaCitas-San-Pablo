<?php

require_once __DIR__.'/../../config/conexion.php';
$usuario_panel = exigir_usuario(['medico']);
require __DIR__.'/../shared/panel_atencion.php';
