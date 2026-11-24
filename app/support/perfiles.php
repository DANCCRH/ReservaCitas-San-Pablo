<?php
const GENEROS_PERFIL = ['femenino' => 'Femenino','masculino' => 'Masculino','otro' => 'Otro','prefiero_no_indicar' => 'Prefiero no indicarlo'];
function validar_demografia()
{
    $genero = texto_post('genero', 20);
    $fecha = texto_post('fecha_nacimiento', 10);
    if ($genero !== '' && !array_key_exists($genero, GENEROS_PERFIL)) {
        throw new DomainException('Selecciona un género válido.');
    }
    if ($fecha !== '') {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha || $fecha > date('Y-m-d') || $fecha < date('Y-m-d', strtotime('-120 years'))) {
            throw new DomainException('Revisa tu fecha de nacimiento.');
        }
    }
    return [$genero ?: null,$fecha ?: null];
}
function campos_demografia($datos = [])
{ ?>
<label>Género<select name="genero"><option value="">Sin especificar</option><?php foreach (GENEROS_PERFIL as $valor => $texto): ?><option value="<?= $valor ?>" <?= ($datos['genero'] ?? '') === $valor ? 'selected' : '' ?>><?= $texto ?></option><?php endforeach ?></select></label>
<label>Fecha de nacimiento<input type="date" name="fecha_nacimiento" min="<?= date('Y-m-d', strtotime('-120 years')) ?>" max="<?= date('Y-m-d') ?>" value="<?= e($datos['fecha_nacimiento'] ?? '') ?>" autocomplete="bday"></label>
<?php }
