<?php
require_once __DIR__.'/../../config/conexion.php';
require_once __DIR__.'/../shared/componentes.php';
$spec = (int)($_GET['especialidad'] ?? 0);
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$where = ['m.activo=1','e.activo=1'];
$params = [];
if ($spec) {
    $where[] = 'm.especialidad_id=?';
    $params[] = $spec;
}
if ($q) {
    $where[] = 'm.nombres LIKE ?';
    $params[] = '%'.$q.'%';
}
$from = ' FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id WHERE '.implode(' AND ', $where);
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($params);
$total = (int)$s->fetchColumn();
$paginas = max(1, (int)ceil($total / 20));
$pagina = min($paginas, max(1, (int)($_GET['pagina'] ?? 1)));
$s = $conexion->prepare('SELECT m.*,e.nombre especialidad'.$from.' ORDER BY e.nombre,m.nombres,m.id LIMIT 20 OFFSET '.(($pagina-1)*20));
$s->execute($params);
$medicos = $s->fetchAll();
abrir_pagina('Equipo médico', 'medicos'); ?>
<div class="gestion-cabecera"><div><p class="eyebrow">PERSONAS QUE CUIDAN DE PERSONAS</p><h1>Encuentra a tu profesional.</h1><p>Conoce al equipo y consulta los horarios de cada especialidad.</p></div></div>
<form method="get" class="gestion-filtros"><label>Nombre del profesional<input type="search" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Busca por nombre"></label><label>Especialidad<?php campo_buscador('especialidad', 'especialidad', $spec, ['etiqueta' => 'Especialidad', 'placeholder' => 'Todas las especialidades']); ?></label><button class="boton boton-primario">Buscar profesional</button><a href="medicos.php">Limpiar</a></form>
<p class="texto-ayuda"><?= $total ?> profesionales disponibles</p>
<div class="medicos-grid"><?php foreach ($medicos as $m):$words = explode(' ', $m['nombres']);
    $initials = mb_substr($words[0], 0, 1).mb_substr($words[2] ?? $words[1], 0, 1); ?><article class="medico-tarjeta"><div class="medico-avatar" aria-hidden="true"><?= e($initials) ?></div><span class="medico-especialidad"><?= e($m['especialidad']) ?></span><h2><?= e($m['nombres']) ?></h2><p><?= e($m['descripcion']) ?></p><a class="enlace-flecha" href="reserva.php?esp=<?= (int)$m['especialidad_id'] ?>&medico=<?= (int)$m['id'] ?>">Ver disponibilidad <i class="fas fa-arrow-right" aria-hidden="true"></i></a></article><?php endforeach ?></div>
<?php if (!$medicos): ?><section class="gestion-vacio"><h2>No encontramos profesionales con ese filtro.</h2><a href="medicos.php">Ver el equipo completo</a></section><?php endif;
if ($paginas > 1): ?><nav class="gestion-paginacion" aria-label="Páginas de profesionales">
<?php if ($pagina > 1): ?><a href="?<?= e(http_build_query(['q'=>$q,'especialidad'=>$spec,'pagina'=>$pagina-1])) ?>">Anterior</a><?php endif ?>
<span>Página <?= $pagina ?> de <?= $paginas ?></span>
<?php if ($pagina < $paginas): ?><a href="?<?= e(http_build_query(['q'=>$q,'especialidad'=>$spec,'pagina'=>$pagina+1])) ?>">Siguiente</a><?php endif ?>
</nav><?php endif; cerrar_pagina(); ?>
