<?php
require BASE_PATH.'/app/controllers/pages/admin/operaciones.php';
abrir_pagina('Operación diaria');
$descripcion = match ($u['rol']) {
    'recepcion' => 'Gestiona las solicitudes y registra la llegada de los pacientes para que pasen a la sala de espera.',
    'medico', 'asistencial' => 'Consulta las llegadas registradas en recepción y los turnos del equipo.',
    default => 'Supervisa las solicitudes, las llegadas a recepción y el estado de cada atención.',
};
encabezado_gestion('Operación diaria', $descripcion, in_array($u['rol'], ['admin','recepcion'], true));
ver_aviso(); ?>
<nav class="nav nav-pills gestion-pestanas" aria-label="Bandejas de atención"><?php foreach ($opciones as $k => $t):?><a class="nav-link <?= $vista === $k ? 'active' : '' ?>" href="?vista=<?= $k ?>"><?= $t ?></a><?php endforeach ?></nav>
<p class="texto-ayuda"><?= $total ?> citas en esta bandeja. Una cita vencida requiere revisión del personal; el sistema no presume una inasistencia.</p>
<?php tabla_citas($q->fetchAll());
paginacion($pagina, $paginas);
cerrar_pagina(); ?>
