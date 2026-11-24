<?php
require BASE_PATH.'/app/controllers/pages/admin/admin_pacientes.php';
abrir_pagina('Pacientes');
encabezado_gestion('Pacientes', 'Busca una cuenta y consulta su historial de citas.', true);
if ($id) {
    $s = $conexion->prepare("SELECT id,nombres,dni,correo,telefono,genero,fecha_nacimiento,es_invitado FROM pacientes WHERE id=? AND rol='paciente'");
    $s->execute([$id]);
    $p = $s->fetch();
    if (!$p) {
        echo '<p class="alert alert-info">Paciente no encontrado.</p>';
        cerrar_pagina();
        exit;
    }
    ?><section class="gestion-tarjeta"><h2><?= e($p['nombres']) ?></h2><p>DNI <?= e($p['dni']) ?> · <?= e($p['correo']) ?> · <?= e($p['telefono'] ?? 'Sin teléfono') ?></p><p class="texto-ayuda"><?= e(GENEROS_PERFIL[$p['genero']] ?? 'Sin especificar') ?><?= $p['fecha_nacimiento'] ? ' · '.fecha_texto($p['fecha_nacimiento']).' · '.(new DateTimeImmutable())->diff(new DateTimeImmutable($p['fecha_nacimiento']))->y.' años' : '' ?></p><a class="boton boton-primario" href="reservar_paciente.php?paciente=<?= (int)$p['id'] ?>">Reservar para este paciente</a></section><?php
    ?><details class="gestion-tarjeta"><summary>Editar contacto administrativo</summary><form method="post" action="editar_paciente.php" class="form-gestion"><?php campo_csrf();?><input type="hidden" name="id" value="<?= $id ?>"><label>Teléfono<input type="tel" name="telefono" maxlength="30" value="<?= e($p['telefono']) ?>" required></label><label>Correo<input type="email" name="correo" maxlength="100" value="<?= e($p['correo']) ?>" <?= !$p['es_invitado'] ? 'readonly' : '' ?>></label><label>Motivo<input name="motivo" minlength="5" maxlength="250" required></label><button class="boton boton-primario">Guardar contacto</button></form></details><?php
    $n = $conexion->prepare('SELECT COUNT(*) FROM citas WHERE paciente_id=?');
    $n->execute([$id]);
    $paginas = max(1, (int)ceil((int)$n->fetchColumn() / 15));
    $pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
    $s = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id LEFT JOIN medicos m ON m.id=c.medico_id WHERE c.paciente_id=? ORDER BY c.fecha_cita DESC,c.hora_cita DESC LIMIT 15 OFFSET '.(($pagina - 1) * 15));
    $s->execute([$id]);
    tabla_citas($s->fetchAll());
    paginacion($pagina, $paginas);
} else {
    $from = " FROM pacientes p WHERE p.rol='paciente'";
    $values = [];
    if ($q) {
        $from .= ' AND (p.nombres LIKE ? OR p.dni LIKE ? OR p.correo LIKE ?)';
        $values = ['%'.$q.'%','%'.$q.'%','%'.$q.'%'];
    }
    $s = $conexion->prepare('SELECT COUNT(*)'.$from);
    $s->execute($values);
    $total = (int)$s->fetchColumn();
    $paginas = max(1, (int)ceil($total / 20));
    $pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
    $s = $conexion->prepare('SELECT p.id,p.nombres,p.dni,p.correo,p.telefono,(SELECT COUNT(*) FROM citas c WHERE c.paciente_id=p.id) total'.$from.' ORDER BY p.nombres LIMIT 20 OFFSET '.(($pagina - 1) * 20));
    $s->execute($values);
    $filas = $s->fetchAll();
    ?><form method="get" class="gestion-filtros"><label>Nombre, DNI o correo<input type="search" name="q" maxlength="100" value="<?= e($q) ?>"></label><button class="boton boton-primario">Buscar paciente</button></form><section class="gestion-tarjeta sin-relleno"><div class="gestion-panel-titulo"><h2>Pacientes registrados</h2></div><div class="tabla-desplazable" tabindex="0" role="region" aria-label="Pacientes"><table class="table tabla-gestion"><thead><tr><th scope="col">Paciente</th><th scope="col">DNI</th><th scope="col">Contacto</th><th scope="col">Citas</th><th scope="col">Consulta</th></tr></thead><tbody><?php foreach ($filas as $p): ?><tr><td><?= e($p['nombres']) ?></td><td><?= e($p['dni']) ?></td><td><?= e($p['correo']) ?><span><?= e($p['telefono'] ?? '') ?></span></td><td><?= (int)$p['total'] ?></td><td><a href="?id=<?= (int)$p['id'] ?>">Ver historial</a></td></tr><?php endforeach ?></tbody></table><?php if (!$filas): ?><p class="gestion-vacio">No se encontraron pacientes.</p><?php endif ?></div><?php paginacion($pagina, $paginas); ?></section><?php
}cerrar_pagina(); ?>
