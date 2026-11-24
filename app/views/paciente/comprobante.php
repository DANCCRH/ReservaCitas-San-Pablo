<?php
require BASE_PATH.'/app/controllers/pages/paciente/comprobante.php';
abrir_pagina('Comprobante de cita');
encabezado_gestion('Comprobante de cita', 'Consulta o imprime los datos de tu solicitud.');
ver_aviso();
?>
<?php if (isset($_GET['nueva'])): ?><p class="alert alert-success" role="status">Tu solicitud se guardó correctamente. El turno quedó reservado, pendiente de confirmación administrativa.</p><?php endif ?>
<article class="gestion-tarjeta comprobante"><div class="comprobante-marca"><img src="imagenes/marca/reservacitas-192.png" alt="ReservaCitas" width="56" height="56"><div><strong>ReservaCitas</strong><span>Clínica San Pablo</span></div></div><div class="gestion-barra"><h2><?= e(codigo_cita($id)) ?></h2><?php etiqueta_estado($c['estado']); ?></div><dl class="comprobante-datos">
<?php foreach (['Paciente' => $c['nombres'],'DNI' => $c['dni'],'Especialidad' => $c['especialidad'],'Médico' => $c['medico'] ?? 'No asignado','Fecha' => fecha_texto($c['fecha_cita']),'Hora' => substr($c['hora_cita'], 0, 5),'Duración' => $c['duracion'].' minutos','Consultorio'=>$c['consultorio'],'Turno de llegada'=>$c['codigo_turno'] ?: 'Se asigna al registrar tu llegada','Correo' => $c['correo'],'Teléfono' => $c['telefono']] as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v ?? '') ?></dd></div><?php endforeach ?></dl>
<?php if ($c['estado'] === 'pendiente'): ?><p class="comprobante-nota">La solicitud está pendiente de confirmación por administración. Revisa sus novedades en Notificaciones.</p><?php endif ?>
</article>
<div class="acciones-fila no-imprimir"><a class="boton boton-primario" href="descargar_cita.php?id=<?= $id ?>">Descargar PDF</a><?php if (!in_array($c['estado'], ['cancelado','rechazado'], true)):?><a class="boton boton-secundario" href="descargar_cita.php?id=<?= $id ?>&amp;formato=ics">Añadir al calendario</a><?php endif ?><button class="boton boton-primario" data-imprimir><i class="fas fa-print" aria-hidden="true"></i> Imprimir o guardar PDF</button><a class="boton boton-secundario" href="<?= e(destino_rol($u)) ?>">Volver a mi panel</a></div>
<?php cerrar_pagina(); ?>
