<?php
require_once __DIR__.'/componentes.php';
require_once __DIR__.'/acciones_medicas.php';
require_once __DIR__.'/acciones_recepcion.php';
require_once __DIR__.'/../../models/agenda_modelo.php';

/** La consulta que llama a esta tabla ya viene limitada al ámbito del rol. */
function tabla_citas($citas)
{
    global $conexion;
    $usuario = usuario_actual();
    if (!$citas) {
        echo '<div class="gestion-vacio"><h2>No hay citas en esta vista</h2><p>Prueba con otra fecha o con otros filtros.</p></div>';
        return;
    }
    $historial_por_cita = [];
    if ($usuario['rol'] !== 'asistencial') {
        $ids = array_map(fn ($c) => (int)$c['id'], array_filter($citas, fn ($c) => puede_ver_cita($usuario, $c)));
        if ($ids) {
            $h = $conexion->prepare('SELECT h.*,p.nombres autor FROM historial_citas h JOIN pacientes p ON p.id=h.usuario_id WHERE cita_id IN ('.implode(',', array_fill(0, count($ids), '?')).') ORDER BY h.id DESC');
            $h->execute(array_values($ids));
            foreach ($h as $row) {
                $historial_por_cita[$row['cita_id']][] = $row;
            }
        }
    }
    ?>
<?php if ($usuario['rol'] === 'medico'): ?>
<link rel="stylesheet" href="css/atencion-medica.css?v=118c6a4ed6">
<script defer src="js/atencion-medica.js?v=31ee36efa1"></script>
<p hidden data-actualizar-atencion role="status">Hay cambios en la agenda médica. <a href="">Actualizar agenda</a></p>
<?php endif ?>
<div class="tabla-desplazable" tabindex="0" role="region" aria-label="Citas, desplaza para ver todas las columnas"><table class="table tabla-gestion"><thead><tr><th scope="col">Código y paciente</th><th scope="col">Atención</th><th scope="col">Fecha y hora</th><th scope="col">Estado</th><th scope="col">Gestión</th></tr></thead><tbody>
<?php foreach ($citas as $c): if (!puede_ver_cita($usuario, $c)) {
    continue;
} $acciones = acciones_cita($usuario, $c);
    $mostrarControlesMedicos = $usuario['rol'] === 'medico' && in_array($c['estado'], ['pendiente','confirmado'], true); ?>
<tr><td><a href="comprobante.php?id=<?= (int)$c['id'] ?>"><strong><?= e(codigo_cita($c['id'])) ?></strong></a><span><?= e($c['nombres']) ?></span><small>DNI <?= e($c['dni']) ?></small>
<?php if (in_array($usuario['rol'], ['admin','recepcion'], true)): ?><a href="admin_pacientes.php?id=<?= (int)$c['paciente_id'] ?>">Ver paciente</a><?php endif ?></td>
<td><strong><?= e($c['especialidad']) ?></strong><span><?= e($c['medico'] ?? 'Sin asignar') ?></span></td>
<td><strong><?= fecha_texto($c['fecha_cita']) ?></strong><span><?= e(substr($c['hora_cita'], 0, 5)) ?> · <?= (int)$c['duracion'] ?> min</span></td>
<td><?php etiqueta_estado($c['estado']);
    if($c['estado']==='confirmado'): ?><small><?= $c['asistencia_confirmada_en']?'Paciente confirmó asistencia':'Asistencia sin confirmar' ?></small><?php endif; if ($c['llegada_en']): ?><small class="llegada-registrada">Llegó <?= e(date('H:i', strtotime($c['llegada_en']))) ?></small><?php endif ?></td>
<td class="gestion-cita-celda"><?php if($c['codigo_turno']):?><p><strong>Turno <?= e($c['codigo_turno']) ?></strong><?= $c['llamado_en']?' · Llamado':'' ?></p><?php endif ?><?php if ($mostrarControlesMedicos) {
    controles_atencion_medica($c);
    if ($c['inicio_atencion']) {
        unset($acciones['atendido']);
    }
} elseif ($usuario['rol'] === 'recepcion') {
    controles_recepcion($c, $acciones);
} else { ?>
<?php if($c['fecha_cita']===date('Y-m-d') && $c['estado']==='confirmado' && $c['llegada_en'] && !$c['inicio_atencion']):?><form action="agenda_acciones.php" method="post"><?php campo_csrf();?><input type="hidden" name="accion" value="llamar"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="boton boton-secundario">Llamar por turno</button></form><?php endif ?>
<?php if ($c['estado'] === 'confirmado' && $c['llegada_en'] && !$c['inicio_atencion'] && $c['fecha_cita'] === date('Y-m-d') && in_array($usuario['rol'], ['admin','medico'], true)): ?><form action="operar.php" method="post"><?php campo_csrf();?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="accion" value="inicio_atencion"><button class="boton boton-primario"<?= strtotime($c['fecha_cita'].' '.$c['hora_cita']) > time() ? ' disabled title="Se habilita a la hora programada"' : '' ?>>Iniciar atención</button></form><?php endif ?><p class="texto-ayuda"><?= $c['inicio_atencion'] ? 'Inicio '.e(date('H:i', strtotime($c['inicio_atencion']))) : '' ?><?= $c['fin_atencion'] ? ' · Fin '.e(date('H:i', strtotime($c['fin_atencion']))) : '' ?></p>
<?php } ?>
<?php if ($acciones && $usuario['rol'] !== 'recepcion'): ?><details class="acciones-detalle"><summary>Gestionar cita</summary><form class="form-gestion accion-cita" action="admin_acciones.php" method="post" data-accion-cita><?php campo_csrf(); ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
<label class="solo-lectores" for="accion-<?= (int)$c['id'] ?>">Acción para <?= e(codigo_cita($c['id'])) ?></label>
<select name="accion" id="accion-<?= (int)$c['id'] ?>" required><option value="">Seleccionar acción</option><?php foreach ($acciones as $valor => $texto): ?><option value="<?= $valor ?>"><?= $texto ?></option><?php endforeach ?></select>
<label for="razon-<?= (int)$c['id'] ?>">Motivo <span class="texto-ayuda">para cancelar, rechazar o corregir atención histórica</span></label><textarea name="motivo" id="razon-<?= (int)$c['id'] ?>" maxlength="250" rows="2"></textarea><button class="boton boton-primario">Aplicar</button></form></details><?php endif ?>
<?php if ($usuario['rol'] === 'admin') { control_llegada_recepcion($c); } ?>
<?php if ($usuario['rol'] === 'admin' && in_array($c['estado'], ['pendiente','confirmado'], true) && strtotime($c['fecha_cita'].' '.$c['hora_cita']) > time()): ?><a href="reservar_paciente.php?paciente=<?= (int)$c['paciente_id'] ?>&cita=<?= (int)$c['id'] ?>">Reprogramar</a><?php endif ?>
<?php if ($usuario['rol'] !== 'asistencial'):
    $historial = $historial_por_cita[$c['id']] ?? [];
    if ($historial): ?><details class="historial-detalle"><summary>Ver historial</summary><ol><?php foreach ($historial as $h): ?><li><strong><?= e(['reprogramacion' => 'Reprogramación','llegada' => 'Llegada registrada'][$h['accion']] ?? (ESTADOS_CITA[$h['estado_nuevo']] ?? $h['estado_nuevo'])) ?></strong><span><?= e($h['autor']) ?> · <?= e(date('d/m/Y H:i', strtotime($h['fecha']))) ?></span><p><?= e($h['detalle']) ?></p></li><?php endforeach ?></ol></details><?php endif; endif ?></td></tr>
<?php endforeach ?></tbody></table></div>
<?php }
function paginacion($pagina, $paginas)
{ ?>
<nav class="gestion-paginacion" aria-label="Páginas"><?php if ($pagina > 1): ?><a href="?<?= e(http_build_query(array_merge($_GET, ['pagina' => $pagina - 1]))) ?>">Anterior</a><?php endif ?><span>Página <?= $pagina ?> de <?= $paginas ?></span><?php if ($pagina < $paginas): ?><a href="?<?= e(http_build_query(array_merge($_GET, ['pagina' => $pagina + 1]))) ?>">Siguiente</a><?php endif ?></nav>
<?php }
