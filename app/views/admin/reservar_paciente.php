<?php
require BASE_PATH.'/app/controllers/pages/admin/reservar_paciente.php';
abrir_pagina('Reservar para un paciente');
encabezado_gestion('Reservar para un paciente', 'Selecciona al paciente. La reserva quedará en su cuenta y tu intervención se guardará en el historial.', true);
?>
<div class="gestion-barra"><p>La cuenta del personal se utiliza para gestionar la atención.</p><a class="boton boton-secundario" href="nuevo_paciente.php?<?= e(http_build_query($contexto)) ?>">Registrar paciente</a></div>
<?php if ($contexto): ?><p class="alert alert-info">El horario seleccionado se conservará al elegir o registrar al paciente. Se revisarán también los cruces de sus otras citas.</p><?php endif ?>
<form method="get" class="gestion-filtros"><?php foreach ($contexto as $campo => $valor): ?><input type="hidden" name="<?= e($campo) ?>" value="<?= e($valor) ?>"><?php endforeach ?><label>Paciente<?php campo_buscador('paciente', 'paciente', 0, ['requerido' => true, 'etiqueta' => 'Paciente', 'placeholder' => 'Nombre, apellido, documento o correo']); ?></label><button class="boton boton-primario">Continuar con la reserva</button></form>
<?php cerrar_pagina(); ?>
