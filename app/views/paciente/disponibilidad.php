<?php
require_once BASE_PATH.'/app/config/conexion.php';require_once BASE_PATH.'/app/services/seguimientos.php';require_once BASE_PATH.'/app/views/shared/componentes.php';
$u=exigir_usuario(['paciente','admin','recepcion']);$pid=(int)($_GET['paciente']??($u['rol']==='paciente'?$u['id']:0));$p=$pid?paciente_autorizado($u,$pid):null;
$eid=(int)($_GET['esp']??0);$fecha=texto_get_integral('fecha',date('Y-m-d'));$resultados=null;$error='';
if($p&&$eid)try{$resultados=buscar_proxima_cita($p['id'],$eid,$fecha);}catch(DomainException $e){$error=$e->getMessage();}
abrir_pagina('Primer horario');
encabezado_gestion('Encuentra tu próximo horario', 'Consulta los primeros espacios disponibles en la especialidad que elijas.', in_array($u['rol'], ['admin','recepcion'], true));
ver_aviso();
?>
<section class="gestion-tarjeta"><form class="gestion-filtros" method="get">
<label>Paciente<?php campo_buscador('paciente', 'paciente', $pid, ['requerido' => true, 'etiqueta' => 'Paciente', 'placeholder' => 'Nombre, apellido o documento']); ?></label>
<label>Especialidad<?php campo_buscador('especialidad', 'esp', $eid, ['requerido' => true, 'etiqueta' => 'Especialidad', 'placeholder' => 'Buscar especialidad']); ?></label><label>A partir del<input type="date" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($fecha) ?>" required></label><button class="boton boton-primario">Buscar primer horario</button></form><p class="texto-ayuda">Se revisan hasta 30 días desde la fecha elegida. La disponibilidad incluye cruces del paciente, médico y consultorio, y se vuelve a validar al reservar.</p></section>
<?php if($error):?><p class="alert alert-warning"><?= e($error) ?></p><?php endif ?>
<?php if(is_array($resultados)):?><section class="gestion-tarjeta"><h2><?= $resultados?'Primeros horarios disponibles':'No hay espacios en este periodo' ?></h2><?php foreach($resultados as $r):$url=in_array($u['rol'],['admin','recepcion'],true)?'reservar_paciente.php':'reserva.php';?><article class="proximo-horario"><div><strong><?= fecha_texto($r['fecha']) ?> · <?= e($r['hora']) ?></strong><p><?= e($r['medico']) ?> · <?= $r['duracion'] ?> min</p></div><a class="boton boton-primario" href="<?= $url ?>?<?= e(http_build_query(['paciente'=>$pid,'esp'=>$eid,'medico'=>$r['medico_id'],'fecha'=>$r['fecha'],'hora'=>$r['hora']])) ?>">Elegir horario</a></article><?php endforeach ?></section><?php endif;cerrar_pagina(); ?>
