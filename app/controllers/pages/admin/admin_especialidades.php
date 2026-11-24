<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(true);
$imagenes = ['general','pediatria','ginecologia','cardiologia','dermatologia','traumatologia','odontologia'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $nombre = texto_post('nombre', 80);
        $desc = texto_post('descripcion', 400);
        $imagen = texto_post('imagen', 30);
        $activo = isset($_POST['activo']) ? 1 : 0;
        if (mb_strlen($nombre) < 3 || mb_strlen($desc) < 10 || !in_array($imagen, $imagenes, true)) {
            throw new DomainException('Completa un nombre, una descripción y una fotografía válidos.');
        }
        if ($id) {
            $s = $conexion->prepare('UPDATE especialidades SET nombre=?,descripcion=?,imagen=?,activo=? WHERE id=?');
            $s->execute([$nombre,$desc,'imagenes/fotografias/'.$imagen.'.webp',$activo,$id]);
        } else {
            $s = $conexion->prepare('INSERT INTO especialidades(nombre,descripcion,imagen,activo) VALUES (?,?,?,?)');
            $s->execute([$nombre,$desc,'imagenes/fotografias/'.$imagen.'.webp',$activo]);
        }
        auditar_evento('especialidades', $id ?: $conexion->lastInsertId(), 'guardar', $activo ? 'Visible para nuevas reservas' : 'Sin nuevas reservas; citas existentes conservadas');
        aviso('Especialidad guardada. Los cambios ya se reflejan en el catálogo.');
        header('Location: admin_especialidades.php', true, 303);
        exit;
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) == 1062) {
            $e = new DomainException('Ya existe una especialidad con ese nombre.');
        }error_formulario($e, 'admin_especialidades.php');
    } catch (Throwable $e) {
        error_formulario($e, 'admin_especialidades.php');
    }
}
$editar = (int)($_GET['editar'] ?? 0);
$item = null;
if ($editar) {
    $s = $conexion->prepare('SELECT * FROM especialidades WHERE id=?');
    $s->execute([$editar]);
    $item = $s->fetch();
}
