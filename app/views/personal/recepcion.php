<?php

require_once __DIR__.'/../../config/conexion.php';
exigir_usuario(['admin','recepcion']);
require __DIR__.'/../admin/admin.php';
