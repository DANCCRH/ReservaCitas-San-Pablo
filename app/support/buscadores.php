<?php

/** Consulta compartida por los resultados paginados y los valores seleccionados del formulario. */
function opciones_buscador($tipo, $u, $alcance = '', $filtro = '', $pagina = 1, $especialidad = 0, $titular = 0, $seleccion = [])
{
    global $conexion;
    $rol = $u['rol'] ?? '';
    $gestor = in_array($rol, ['admin', 'recepcion'], true);
    if (!in_array($alcance, ['', 'agenda', 'todos', 'sin_cuenta'], true)) {
        throw new InvalidArgumentException('Ámbito de búsqueda desconocido.');
    }
    if (($alcance === 'agenda' && !in_array($rol, ['admin','recepcion','medico','asistencial'], true))
        || (in_array($alcance, ['todos','sin_cuenta'], true) && $rol !== 'admin')
        || ($tipo === 'paciente' && !$gestor && $rol !== 'paciente')) {
        throw new DomainException('Tu cuenta no tiene acceso a este directorio.');
    }
    $where = [];
    $args = [];
    $activos = !in_array($alcance, ['todos', 'agenda'], true);
    switch ($tipo) {
        case 'paciente':
            $tabla = 'pacientes p';
            $id = 'p.id';
            $nombre = 'p.nombres';
            $detalle = "CONCAT(p.tipo_documento, ' ', p.dni)";
            $campos = [$nombre,'p.dni'];
            if ($gestor) $campos[] = 'p.correo';
            $where[] = "p.rol='paciente'";
            if ($activos) $where[] = 'p.activo=1';
            if (!$gestor) {
                $where[] = '(p.id=? OR EXISTS(SELECT 1 FROM responsables_pacientes r WHERE r.responsable_id=? AND r.paciente_id=p.id))';
                array_push($args, $u['id'], $u['id']);
            }
            break;
        case 'medico':
            $tabla = 'medicos m JOIN especialidades e ON e.id=m.especialidad_id';
            $id = 'm.id';
            $nombre = 'm.nombres';
            $detalle = "CONCAT(e.nombre, ' · ', m.colegiatura)";
            $campos = [$nombre, 'e.nombre', 'm.colegiatura'];
            if ($gestor) {
                $tabla .= ' LEFT JOIN pacientes p ON p.id=m.usuario_id';
                $campos[] = 'p.dni';
            }
            if ($activos) $where[] = 'm.activo=1 AND e.activo=1';
            if ($especialidad > 0) {
                $where[] = 'm.especialidad_id=?';
                $args[] = $especialidad;
            }
            if ($alcance === 'sin_cuenta') {
                $where[] = '(m.usuario_id IS NULL OR m.usuario_id=?)';
                $args[] = $titular;
            } elseif ($alcance === 'agenda' && $rol === 'medico') {
                $where[] = 'm.usuario_id=?';
                $args[] = $u['id'];
            } elseif ($alcance === 'agenda' && $rol === 'asistencial') {
                $where[] = 'EXISTS(SELECT 1 FROM personal_equipos t WHERE t.medico_id=m.id AND t.usuario_id=?)';
                $args[] = $u['id'];
            }
            break;
        case 'especialidad':
            $tabla = 'especialidades e';
            $id = 'e.id';
            $nombre = 'e.nombre';
            $detalle = "''";
            $campos = [$nombre];
            if ($activos) $where[] = 'e.activo=1';
            break;
        default:
            throw new InvalidArgumentException('Tipo de búsqueda desconocido.');
    }
    if ($seleccion) {
        $where[] = $id.' IN ('.implode(',',array_fill(0,count($seleccion),'?')).')';
        array_push($args, ...$seleccion);
    }
    foreach (preg_split('/\s+/u', trim($filtro), -1, PREG_SPLIT_NO_EMPTY) as $palabra) {
        $patron = '%'.strtr($palabra,['!' => '!!','%' => '!%','_' => '!_']).'%';
        $where[] = '('.implode(' OR ',array_map(fn($campo) => $campo." LIKE ? ESCAPE '!'", $campos)).')';
        array_push($args, ...array_fill(0, count($campos), $patron));
    }
    $sql = "SELECT $id id,$nombre nombre,$detalle detalle FROM $tabla";
    if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
    $sql .= " ORDER BY $nombre,$id";
    $tamano = 30;
    if (!$seleccion) $sql .= ' LIMIT '.($tamano + 1).' OFFSET '.(($pagina - 1)*$tamano);
    $q = $conexion->prepare($sql);
    $q->execute($args);
    $filas = $q->fetchAll(PDO::FETCH_ASSOC);
    $mas = !$seleccion && count($filas) > $tamano;
    if ($mas) array_pop($filas);
    return ['resultados' => $filas, 'mas' => $mas];
}

function campo_buscador($tipo, $nombre, $valor = 0, $opciones = [])
{
    static $secuencia = 0;
    $id = $opciones['id'] ?? 'selector-'.(++$secuencia);
    $alcance = $opciones['alcance'] ?? '';
    $titular = (int)($opciones['usuario'] ?? 0);
    $seleccion = array_values(array_filter(array_map('intval', (array)$valor), fn($n) => $n > 0));
    $filas = $seleccion ? opciones_buscador($tipo, usuario_actual(), $alcance, '', 1, 0, $titular, $seleccion)['resultados'] : [];
    $etiqueta = $opciones['etiqueta'] ?? ['medico'=>'Profesional','paciente'=>'Paciente','especialidad'=>'Especialidad'][$tipo];
    $placeholder = $opciones['placeholder'] ?? 'Seleccionar';
    ?>
    <select name="<?= e($nombre) ?>" id="<?= e($id) ?>" class="<?= e($opciones['clase'] ?? '') ?>"
        data-buscador="<?= e($tipo) ?>" data-alcance="<?= e($alcance) ?>" data-titular="<?= $titular ?>"
        data-especialidad="<?= e($opciones['especialidad'] ?? '') ?>" data-etiqueta="<?= e($etiqueta) ?>"
        data-placeholder="<?= e($placeholder) ?>" <?= !empty($opciones['requerido']) ? 'required' : '' ?>
        <?= !empty($opciones['multiple']) ? 'multiple' : '' ?> <?= !empty($opciones['disabled']) ? 'disabled' : '' ?>>
        <?php if (empty($opciones['multiple'])): ?><option value="<?= e($opciones['valor_vacio'] ?? '') ?>"><?= e($placeholder) ?></option><?php endif ?>
        <?php foreach ($filas as $fila): ?><option selected value="<?= (int)$fila['id'] ?>" data-nombre="<?= e($fila['nombre']) ?>" data-detalle="<?= e($fila['detalle']) ?>"><?= e($fila['nombre'].($fila['detalle'] !== '' ? ' · '.$fila['detalle'] : '')) ?></option><?php endforeach ?>
    </select>
    <?php
}
