<?php
require_once BASE_PATH.'/app/config/conexion.php';require_once BASE_PATH.'/app/models/agenda_modelo.php';require_once BASE_PATH.'/app/views/shared/componentes.php';
$u = exigir_usuario(['admin','recepcion']);
$eid = (int)($_GET['esp'] ?? 0);
$mid = (int)($_GET['medico'] ?? 0);
if (!$mid) {
    $q = $conexion->prepare('SELECT m.id FROM medicos m JOIN especialidades e ON e.id=m.especialidad_id WHERE m.activo=1 AND e.activo=1'.($eid ? ' AND e.id=?' : '').' ORDER BY m.nombres,m.id LIMIT 1');
    $q->execute($eid ? [$eid] : []);
    $mid = (int)$q->fetchColumn();
}
$m = $mid ? medico_agenda($mid) : null;
if (!$m || !$m['activo'] || !$m['especialidad_activa'] || ($eid && $eid !== (int)$m['especialidad_id'])) {
    $mid = 0;
}
$fecha=texto_get_integral('fecha',date('Y-m-d'));
if(!fecha_admitida($fecha))$fecha=date('Y-m-d');$inicio=new DateTimeImmutable($fecha);$fin=$inicio->modify('+6 days')->format('Y-m-d');
$q=$conexion->prepare("SELECT c.*,p.nombres paciente FROM citas c JOIN pacientes p ON p.id=c.paciente_id WHERE c.medico_id=? AND c.fecha_cita BETWEEN ? AND ? AND c.estado IN('pendiente','confirmado') AND TIMESTAMP(c.fecha_cita,c.hora_cita)>NOW() ORDER BY c.fecha_cita,c.hora_cita");$q->execute([$mid,$fecha,$fin]);$citas=$q->fetchAll();$citas_dia=[];foreach($citas as $c)$citas_dia[$c['fecha_cita']][]=$c;
$libres=[];$semana=[];for($i=0;$i<7;$i++){$f=$inicio->modify('+'.$i.' days')->format('Y-m-d');$hs=[];try{$hs=horarios_disponibles($mid,$f,0);}catch(DomainException $e){}foreach($hs as $h)if($h['disponible'])$libres[]=['fecha'=>$f,'hora'=>$h['hora']];$semana[$f]=$hs;}
abrir_pagina('Mover citas');encabezado_gestion('Un calendario para reorganizar','Arrastra una cita a un horario libre o utiliza el formulario. El cambio requiere confirmar el motivo.',true);ver_aviso();
?>
<form class="gestion-filtros"><label>Especialidad<?php campo_buscador('especialidad', 'esp', $eid, ['id' => 'filtro-especialidad', 'etiqueta' => 'Especialidad', 'placeholder' => 'Todas las especialidades']); ?></label>
<label>Profesional<?php campo_buscador('medico', 'medico', $mid, ['id' => 'profesional', 'etiqueta' => 'Profesional', 'especialidad' => 'filtro-especialidad', 'requerido' => true, 'placeholder' => 'Buscar profesional']); ?></label><label>Inicio de los 7 días<input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($fecha) ?>" required></label><button class="boton boton-primario">Mostrar calendario</button></form>
<p class="texto-ayuda">El calendario muestra disponibilidad del profesional y del consultorio. Al mover la cita también se revisan los cruces del paciente. El estado vuelve a pendiente y se solicita nuevamente la confirmación de asistencia.</p>
<div class="calendario-desplazable" tabindex="0" role="region" aria-label="Calendario de siete días"><div class="calendario-mover">
<?php foreach($semana as $f=>$hs):?><section><h2><?= fecha_texto($f) ?></h2><?php foreach($citas_dia[$f]??[] as $c):?><article class="cita-movible" draggable="true" data-mover-cita="<?= $c['id'] ?>" data-cita-texto="<?= e(codigo_cita($c['id']).' · '.$c['paciente'].' · '.substr($c['hora_cita'],0,5)) ?>"><strong><?= e(substr($c['hora_cita'],0,5)) ?> · <?= e(codigo_cita($c['id'])) ?></strong><p><?= e($c['paciente']) ?></p><span><?= e(ESTADOS_CITA[$c['estado']]) ?></span><button class="boton boton-secundario" type="button" data-elegir-cita="<?= $c['id'] ?>">Seleccionar para mover</button></article><?php endforeach ?>
<?php foreach($hs as $h):if($h['disponible']):?><button class="calendario-libre" type="button" data-destino-fecha="<?= $f ?>" data-destino-hora="<?= e($h['hora']) ?>"><?= e($h['hora']) ?><span>Libre · <?= (int)$h['duracion'] ?> min</span></button><?php else:?><div class="calendario-ocupado"><?= e($h['hora']) ?> · No disponible</div><?php endif;endforeach;if(!$hs):?><p>Sin turno habilitado</p><?php endif ?></section><?php endforeach ?></div></div>
<p role="status" data-mover-estado>Selecciona una cita y después un horario libre.</p>
<section class="gestion-tarjeta"><h2>Reprogramar con formulario</h2><form class="gestion-filtros" method="post" action="agenda_acciones.php" data-mover-form><?php campo_csrf();?><input type="hidden" name="accion" value="mover"><input type="hidden" name="medico_id" value="<?= $mid ?>"><input type="hidden" name="fecha_cita" data-mover-fecha><input type="hidden" name="hora_cita" data-mover-hora>
<label>Cita<select data-buscador-local name="id" data-mover-id required><option value="">Seleccionar</option><?php foreach($citas as $c):?><option value="<?= $c['id'] ?>"><?= e(codigo_cita($c['id']).' · '.$c['paciente'].' · '.$c['fecha_cita'].' '.substr($c['hora_cita'],0,5)) ?></option><?php endforeach ?></select></label><label>Nuevo horario<select data-mover-destino required><option value="">Seleccionar</option><?php foreach($libres as $l):?><option value="<?= $l['fecha'].'|'.$l['hora'] ?>"><?= fecha_texto($l['fecha']).' · '.e($l['hora']) ?></option><?php endforeach ?></select></label><label>Motivo<input name="motivo" minlength="5" maxlength="250" required></label><button class="boton boton-primario">Revisar cambio</button></form><noscript><p>Para reprogramar sin JavaScript utiliza <a href="reservar_paciente.php">la reserva asistida</a>.</p></noscript></section>
<dialog class="modal-mover" data-mover-dialog aria-labelledby="titulo-mover"><h2 id="titulo-mover">Confirma el cambio de horario</h2><p data-mover-resumen></p><p>La cita se actualizará al confirmar. El servidor comprobará que el horario siga disponible.</p><div class="acciones-fila"><button type="button" class="boton boton-secundario" data-mover-cancelar>Volver a editar</button><button type="button" class="boton boton-primario" data-mover-confirmar>Confirmar reprogramación</button></div></dialog><script src="js/agenda-integral.js?v=0e11e674c7" defer></script>
<?php cerrar_pagina(); ?>
