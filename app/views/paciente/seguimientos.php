<?php
require_once BASE_PATH.'/app/config/conexion.php';require_once BASE_PATH.'/app/services/seguimientos.php';require_once BASE_PATH.'/app/views/shared/componentes.php';
$u=exigir_usuario(['paciente','admin','recepcion']);$pid=(int)($_POST['paciente_id']??$_GET['paciente']??($u['rol']==='paciente'?$u['id']:0));$p=$pid?paciente_autorizado($u,$pid):null;$preview=null;$datos=null;
if($_SERVER['REQUEST_METHOD']==='POST')try {
    comprobar_csrf();if(!$p)throw new DomainException('Selecciona un paciente.');
    if(($_POST['accion']??'')==='guardar') {
        $token=texto_post('solicitud',64);$datos=$_SESSION['serie_previa'][$token]??null;
        if(!$datos||($datos['actor']??0)!==$u['id']||($datos['paciente_id']??0)!==$pid||($datos['vence']??0)<time())throw new DomainException('La revisión venció. Prepara otra vista previa.');
        $sid=guardar_serie($u,$p,$datos,$token);aviso('Se registró la serie '.$sid.' completa. Las citas están pendientes de aprobación.');header('Location: '.($u['rol']==='paciente'?'mis_solicitudes.php?paciente='.$pid:'admin_pacientes.php?id='.$pid),true,303);exit;
    }
    $datos=['medico_id'=>id_post('medico_id'),'fecha_cita'=>texto_post('fecha_cita',10),'hora_cita'=>texto_post('hora_cita',5),'cantidad'=>(int)($_POST['cantidad']??0),'intervalo'=>(int)($_POST['intervalo']??0),'telefono'=>$p['telefono'],'comentario'=>texto_post('comentario',250)];
    if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$datos['hora_cita']))throw new DomainException('Revisa la hora.');
    $preview=previsualizar_serie($p,$datos);$token=bin2hex(random_bytes(32));
    foreach($_SESSION['serie_previa']??[] as $k=>$s)if(($s['vence']??0)<time())unset($_SESSION['serie_previa'][$k]);
    if(count($_SESSION['serie_previa']??[])>=10)array_shift($_SESSION['serie_previa']);
    $_SESSION['serie_previa'][$token]=$datos+['actor'=>$u['id'],'paciente_id'=>$pid,'vence'=>time()+1200];
} catch(Throwable $e){error_formulario($e,'seguimientos.php?paciente='.$pid);}
abrir_pagina('Citas de seguimiento');
encabezado_gestion('Programa tus seguimientos', 'Revisa las fechas del plan de citas antes de registrarlo.', in_array($u['rol'], ['admin','recepcion'], true));
ver_aviso();
?>
<section class="gestion-tarjeta"><h2>Plan de citas</h2><p>La periodicidad se coordina con el profesional. Cada cita se gestiona por separado después del registro.</p><form method="post" class="gestion-filtros"><?php campo_csrf();?><input type="hidden" name="accion" value="preview">
<label>Paciente<?php campo_buscador('paciente', 'paciente_id', $pid, ['requerido' => true, 'etiqueta' => 'Paciente', 'placeholder' => 'Seleccionar paciente']); ?></label>
<label>Especialidad<?php campo_buscador('especialidad', 'esp', (int)($_POST['esp'] ?? 0), ['id' => 'serie-esp', 'etiqueta' => 'Especialidad', 'placeholder' => 'Todas las especialidades']); ?></label>
<label>Profesional<?php campo_buscador('medico', 'medico_id', (int)($_POST['medico_id'] ?? 0), ['id' => 'serie-medico', 'etiqueta' => 'Profesional', 'especialidad' => 'serie-esp', 'requerido' => true, 'placeholder' => 'Buscar profesional']); ?></label>
<label>Primera fecha<input type="date" name="fecha_cita" min="<?= date('Y-m-d') ?>" required></label><label>Hora<input type="time" name="hora_cita" required></label><label>Frecuencia<select name="intervalo"><option value="7">Cada semana</option><option value="14">Cada 2 semanas</option><option value="28">Cada 4 semanas</option></select></label><label>Cantidad<input type="number" min="2" max="<?= (int)reglas_agenda()['maximas_serie'] ?>" name="cantidad" value="3" required></label><label>Comentario<input name="comentario" maxlength="250"></label><button class="boton boton-primario">Revisar todas las fechas</button></form></section>
<?php if($preview):$conflictos=count(array_filter($preview,fn($r)=>!$r['libre']));?><section class="gestion-tarjeta"><h2>Vista previa de <?= count($preview) ?> citas</h2><p><?= $conflictos?'Hay conflictos. Ajusta el plan; todavía no se guardó ninguna cita.':'Todas las fechas tienen espacio. Se revisarán otra vez al confirmar.' ?></p><ul class="serie-preview"><?php foreach($preview as $r):?><li class="<?= $r['libre']?'serie-libre':'serie-conflicto' ?>"><strong><?= fecha_texto($r['fecha']).' · '.e($r['hora']) ?></strong><span><?= $r['libre']?'Disponible':e($r['motivo']) ?></span></li><?php endforeach ?></ul><?php if(!$conflictos):?><form method="post"><?php campo_csrf();?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="paciente_id" value="<?= $pid ?>"><input type="hidden" name="solicitud" value="<?= $token ?>"><button class="boton boton-primario">Confirmar serie completa</button></form><?php endif ?></section><?php endif;cerrar_pagina(); ?>
