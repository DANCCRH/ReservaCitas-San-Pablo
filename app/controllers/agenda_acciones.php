<?php
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../services/seguimientos.php';
exigir_post();$u=exigir_usuario();$retorno=destino_rol($u);
try {
    comprobar_csrf();$accion=texto_post('accion',30);
    if ($accion==='asistencia') {$retorno='mis_solicitudes.php';confirmar_asistencia($u,id_post('id'));}
    elseif ($accion==='llamar') {$retorno=$u['rol']==='medico' ? destino_rol($u) : 'operaciones.php?vista=espera';llamar_paciente($u,id_post('id'));}
    elseif ($accion==='mover') {
        $retorno='calendario.php';
        if (!in_array($u['rol'],['admin','recepcion'],true)) throw new DomainException('Solo recepción o administración reprograma desde este calendario.');
        $q=$conexion->prepare('SELECT * FROM citas WHERE id=?');$q->execute([id_post('id')]);$c=$q->fetch();
        if (!$c) throw new DomainException('Cita no encontrada.');
        $p=paciente_autorizado($u,$c['paciente_id']);$m=medico_agenda(id_post('medico_id'));
        if (!$m || (int)$m['especialidad_id']!==(int)$c['especialidad_id']) throw new DomainException('El nuevo profesional debe pertenecer a la misma especialidad.');
        guardar_reserva($p,['medico_id'=>$m['id'],'especialidad_id'=>$m['especialidad_id'],'fecha_cita'=>texto_post('fecha_cita',10),'hora_cita'=>texto_post('hora_cita',5),'telefono'=>$p['telefono'],'comentario'=>$c['comentario'],'motivo'=>texto_post('motivo',250)],$c['id'],$u);
        $retorno.='?medico='.$m['id'].'&fecha='.urlencode(texto_post('fecha_cita',10));
    } elseif ($accion==='preferencias') {
        $retorno='preferencias.php';$campos=['citas_email','recordatorios_email','espera_email','recordatorios_web'];$v=[$u['id']];foreach($campos as $k)$v[]=isset($_POST[$k])?1:0;
        $conexion->prepare('INSERT INTO preferencias_avisos(usuario_id,citas_email,recordatorios_email,espera_email,recordatorios_web) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE citas_email=VALUES(citas_email),recordatorios_email=VALUES(recordatorios_email),espera_email=VALUES(espera_email),recordatorios_web=VALUES(recordatorios_web),actualizado_en=NOW()')->execute($v);
        auditar_evento('preferencias_avisos',$u['id'],'guardar','Preferencias personales actualizadas');
    } elseif ($accion==='reglas') {
        $retorno='configuracion.php';if($u['rol']!=='admin')throw new DomainException('Solo administración modifica reglas.');
        $limites=['dias_maximos'=>[7,180],'anticipacion_minutos'=>[0,10080],'cancelacion_horas'=>[0,168],'maximas_pendientes'=>[2,50],'maximas_serie'=>[2,12]];$v=[];
        foreach($limites as $k=>[$min,$max]) {$n=filter_var($_POST[$k]??null,FILTER_VALIDATE_INT);if($n===false||$n<$min||$n>$max)throw new DomainException('Revisa el valor de '.$k.'.');$v[$k]=$n;}
        if($v['maximas_serie']>$v['maximas_pendientes'])throw new DomainException('El máximo por serie no debe superar el máximo de citas futuras.');
        $conexion->beginTransaction();foreach($v as $k=>$n)$conexion->prepare('UPDATE reglas_agenda SET valor=? WHERE clave=?')->execute([$n,$k]);auditar_evento('reglas_agenda',null,'actualizar',json_encode($v));$conexion->commit();
    } elseif ($accion==='consultorio') {
        $retorno='consultorios.php';if($u['rol']!=='admin')throw new DomainException('Solo administración modifica consultorios.');
        $id=(int)($_POST['id']??0);$nombre=texto_post('nombre',80);$ubicacion=texto_post('ubicacion',120);$activo=isset($_POST['activo'])?1:0;
        if(mb_strlen($nombre)<3||mb_strlen($ubicacion)<3)throw new DomainException('Completa nombre y ubicación.');
        $conexion->beginTransaction();
        if($id) {
            $q=$conexion->prepare('SELECT id FROM consultorios WHERE id=? FOR UPDATE');$q->execute([$id]);if(!$q->fetch())throw new DomainException('Consultorio no encontrado.');
            $q=$conexion->prepare("SELECT id FROM citas WHERE consultorio_id=? AND estado IN('pendiente','confirmado') AND TIMESTAMP(fecha_cita,hora_cita)>NOW() LIMIT 1");$q->execute([$id]);if(!$activo&&$q->fetch())throw new DomainException('Hay citas futuras asignadas. Reprograma esas citas antes de desactivar el consultorio.');
            $conexion->prepare('UPDATE consultorios SET nombre=?,ubicacion=?,activo=? WHERE id=?')->execute([$nombre,$ubicacion,$activo,$id]);
        } else {$conexion->prepare('INSERT INTO consultorios(nombre,ubicacion,activo) VALUES(?,?,?)')->execute([$nombre,$ubicacion,$activo]);$id=(int)$conexion->lastInsertId();}
        auditar_evento('consultorios',$id,'guardar',$nombre);$conexion->commit();
    } else throw new DomainException('Acción no válida.');
    aviso('Los cambios se guardaron correctamente.');header('Location: '.$retorno,true,303);exit;
} catch(Throwable $e) { error_formulario($e,$retorno); }
