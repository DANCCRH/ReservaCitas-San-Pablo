<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
exigir_post();
$u = exigir_usuario(['admin','recepcion','paciente']);
$retorno = $u['rol'] === 'paciente' ? 'familia.php' : 'nuevo_paciente.php';
try {
    comprobar_csrf();
    $nombre = texto_post('nombres', 150);
    $tipo = texto_post('tipo_documento', 12) ?: 'DNI';
    $dni = strtoupper(texto_post('dni', 20));
    $tel = telefono_normalizado(texto_post('telefono', 30));
    $correo = strtolower(texto_post('correo', 100));
    [$genero,$nacimiento] = validar_demografia();
    if (mb_strlen($nombre) < 5 || !documento_valido($tipo, $dni) || !telefono_valido($tel) || ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
        throw new DomainException('Revisa nombre, documento, teléfono y correo opcional.');
    }
    $dependiente = $u['rol'] === 'paciente';
    if ($dependiente) {
        if (!$u['fecha_nacimiento'] || (new DateTimeImmutable($u['fecha_nacimiento']))->diff(new DateTimeImmutable())->y < 18) {
            throw new DomainException('Completa tu fecha de nacimiento en Mi perfil. El responsable debe ser mayor de edad.');
        }if (!$nacimiento || (new DateTimeImmutable($nacimiento))->diff(new DateTimeImmutable())->y >= 18) {
            throw new DomainException('Desde esta pantalla se registran dependientes menores de edad.');
        }$correo = null;
    }
    $cuenta = !$dependiente && isset($_POST['crear_cuenta']);
    $clave = $cuenta ? clave_post('clave') : bin2hex(random_bytes(20));
    if ($cuenta && (!$correo || !clave_nueva_valida($clave))) {
        throw new DomainException('Para crear acceso se necesita correo y una clave inicial de 8 caracteres con letras y números.');
    }
    $conexion->beginTransaction();
    $conexion->prepare("INSERT INTO pacientes(nombres,dni,tipo_documento,correo,telefono,contrasena,es_invitado,rol,genero,fecha_nacimiento,clave_temporal) VALUES(?,?,?,?,?,?,?,'paciente',?,?,?)")->execute([$nombre,$dni,$tipo,$correo ?: null,$tel,password_hash($clave, PASSWORD_DEFAULT),$cuenta ? 0 : 1,$genero,$nacimiento,$cuenta ? 1 : 0]);
    $id = (int)$conexion->lastInsertId();
    if ($dependiente) {
        $parentesco = texto_post('parentesco', 30);
        if (!in_array($parentesco, ['Madre','Padre','Tutor legal'], true)) {
            throw new DomainException('Selecciona el parentesco.');
        }$conexion->prepare('INSERT INTO responsables_pacientes(responsable_id,paciente_id,parentesco,creado_por) VALUES(?,?,?,?)')->execute([$u['id'],$id,$parentesco,$u['id']]);
    }
    auditar_evento('pacientes', $id, 'alta', $dependiente ? 'Dependiente registrado por su responsable' : ($cuenta ? 'Ficha y acceso temporal' : 'Ficha sin cuenta de acceso'));
    $conexion->commit();
    aviso('Paciente registrado correctamente.');
    $url = $dependiente ? 'familia.php' : 'reservar_paciente.php?'.http_build_query(['paciente' => $id,...(function_exists('contexto_horario') ? contexto_horario($_POST) : [])]);
    header('Location: '.$url, true, 303);
    exit;
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) == 1062) {
        $e = new DomainException('Ese documento o correo ya existe. Consulta con recepción antes de registrar otra ficha.');
    }error_formulario($e, $retorno);
} catch (Throwable $e) {
    error_formulario($e,$retorno);
}
