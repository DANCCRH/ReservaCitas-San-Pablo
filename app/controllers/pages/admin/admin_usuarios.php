<?php

require_once BASE_PATH.'/app/config/conexion.php';
require_once BASE_PATH.'/app/views/shared/admin_componentes.php';
$u = exigir_usuario(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        comprobar_csrf();
        if (!limitar('usuarios:'.$u['id'], 15, 300) || !password_verify(clave_post('clave_admin'), $u['contrasena'])) {
            throw new DomainException('Confirma tu contraseña de administrador. Si agotaste los intentos, espera unos minutos.');
        }
        $id = (int)($_POST['id'] ?? 0);
        $nombre = texto_post('nombres', 150);
        $dni = texto_post('dni', 8);
        $correo = strtolower(texto_post('correo', 100));
        $telefono = telefono_normalizado(texto_post('telefono', 30));
        $rol = texto_post('rol', 20);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $clave = clave_post('clave');
        $mid = $rol === 'medico' ? (int)($_POST['medico_id'] ?? 0) : 0;
        $equipo = $rol === 'asistencial' && is_array($_POST['equipo'] ?? null) ? array_unique(array_map('intval', $_POST['equipo'])) : [];
        if (mb_strlen($nombre) < 5 || !preg_match('/^\d{8}$/', $dni) || !filter_var($correo, FILTER_VALIDATE_EMAIL) || ($telefono && !preg_match('/^\+?[0-9]{7,15}$/', $telefono)) || !isset(ROLES[$rol])) {
            throw new DomainException('Revisa el nombre, DNI, correo, teléfono y rol de la cuenta.');
        }
        if ((!$id || $clave !== '') && !clave_nueva_valida($clave)) {
            throw new DomainException('La contraseña debe tener al menos 8 caracteres con letras y números.');
        }
        if ($id === (int)$u['id'] && ($rol !== 'admin' || !$activo)) {
            throw new DomainException('No puedes retirar tu propio acceso administrativo. Utiliza otra cuenta administrativa para ese cambio.');
        }
        $conexion->beginTransaction();
        $conexion->query("SELECT id FROM pacientes WHERE rol='admin' AND activo=1 FOR UPDATE")->fetchAll();
        $anterior = null;
        if ($id) {
            $s = $conexion->prepare('SELECT * FROM pacientes WHERE id=? AND es_invitado=0 FOR UPDATE');
            $s->execute([$id]);
            $anterior = $s->fetch();
            if (!$anterior) {
                throw new DomainException('La cuenta no está disponible.');
            }
            if ($anterior['rol'] === 'paciente' && $rol !== 'paciente') {
                $s = $conexion->prepare('SELECT COUNT(*) FROM citas WHERE paciente_id=?');
                $s->execute([$id]);
                if ($s->fetchColumn()) {
                    throw new DomainException('Esta cuenta conserva citas de paciente. Crea una cuenta de personal independiente para conservar el historial.');
                }
            }
        }
        if ($id && $anterior['rol'] === 'medico' && (!$activo || $rol !== 'medico' || $mid !== medico_del_usuario($anterior))) {
            if (citas_futuras_medico(medico_del_usuario($anterior))) {
                throw new DomainException('La cuenta médica tiene compromisos futuros. Coordina su reprogramación antes de retirar el acceso o el vínculo.');
            }
        }
        $s = $conexion->prepare('SELECT id FROM pacientes WHERE (dni=? OR correo=?) AND id<>?');
        $s->execute([$dni,$correo,$id]);
        if ($s->fetch()) {
            throw new DomainException('Ya existe otra cuenta con ese DNI o correo.');
        }
        if ($mid) {
            $s = $conexion->prepare('SELECT id,usuario_id FROM medicos WHERE id=? AND activo=1 FOR UPDATE');
            $s->execute([$mid]);
            $m = $s->fetch();
            if (!$m || ($m['usuario_id'] && (int)$m['usuario_id'] !== $id)) {
                throw new DomainException('El profesional no está disponible o ya tiene otra cuenta vinculada.');
            }
        } elseif ($rol === 'medico' && $activo) {
            throw new DomainException('Vincula la cuenta médica a un profesional del catálogo.');
        }
        foreach ($equipo as $mi) {
            $s = $conexion->prepare('SELECT id FROM medicos WHERE id=? AND activo=1');
            $s->execute([$mi]);
            if (!$s->fetch()) {
                throw new DomainException('Selecciona un equipo válido.');
            }
        }
        if ($rol === 'asistencial' && $activo && !$equipo) {
            throw new DomainException('Asigna al menos un profesional al equipo asistencial.');
        }
        if ($id) {
            $s = $conexion->prepare('UPDATE pacientes SET nombres=?,dni=?,correo=?,telefono=?,rol=?,activo=?,sesion_version=sesion_version+1 WHERE id=?');
            $s->execute([$nombre,$dni,$correo,$telefono,$rol,$activo,$id]);
            if ($clave !== '') {
                $conexion->prepare('UPDATE pacientes SET contrasena=? WHERE id=?')->execute([password_hash($clave, PASSWORD_DEFAULT),$id]);
            }
        } else {
            $s = $conexion->prepare('INSERT INTO pacientes(nombres,dni,correo,telefono,rol,activo,contrasena) VALUES (?,?,?,?,?,?,?)');
            $s->execute([$nombre,$dni,$correo,$telefono,$rol,$activo,password_hash($clave, PASSWORD_DEFAULT)]);
            $id = (int)$conexion->lastInsertId();
        }
        $conexion->prepare('UPDATE medicos SET usuario_id=NULL WHERE usuario_id=?')->execute([$id]);
        if ($mid) {
            $conexion->prepare('UPDATE medicos SET usuario_id=?,nombres=? WHERE id=?')->execute([$id,$nombre,$mid]);
        }
        $conexion->prepare('DELETE FROM personal_equipos WHERE usuario_id=?')->execute([$id]);
        foreach ($equipo as $mi) {
            $conexion->prepare('INSERT INTO personal_equipos(usuario_id,medico_id) VALUES (?,?)')->execute([$id,$mi]);
        }
        $detalle = ($anterior ? 'Cuenta actualizada' : 'Cuenta creada').'. Rol: '.$rol.'. Activa: '.$activo.'. Profesional: '.$mid.'. Equipo: '.implode(',', $equipo).($clave !== '' ? '. Contraseña establecida.' : '');
        $conexion->prepare('INSERT INTO auditoria_usuarios(actor_id,usuario_id,detalle) VALUES (?,?,?)')->execute([$u['id'],$id,$detalle]);
        if ((!$anterior || $clave !== '') && $id !== (int)$u['id']) {
            $conexion->prepare('UPDATE pacientes SET clave_temporal=1 WHERE id=?')->execute([$id]);
        }
        auditar_evento('cuentas', $id, 'guardar', $detalle);
        $conexion->commit();
        if ($id === (int)$u['id']) {
            $_SESSION['sesion_version'] = (int)$u['sesion_version'] + 1;
            session_regenerate_id(true);
        }
        aviso('Cuenta guardada. Los cambios de acceso cierran sus sesiones anteriores.');
        header('Location: admin_usuarios.php?id='.$id, true, 303);
        exit;
    } catch (Throwable $e) {
        error_formulario($e, 'admin_usuarios.php');
    }
}
$id = (int)($_GET['id'] ?? 0);
$edit = null;
if ($id) {
    $s = $conexion->prepare('SELECT * FROM pacientes WHERE id=? AND es_invitado=0');
    $s->execute([$id]);
    $edit = $s->fetch();
}
$equipo = [];
$mid = 0;
if ($edit) {
    $equipo = equipo_del_usuario($edit);
    $mid = medico_del_usuario($edit);
}
$q = substr(sanitizar($_GET['q'] ?? ''), 0, 100);
$from = ' FROM pacientes WHERE es_invitado=0 AND (nombres LIKE ? OR correo LIKE ? OR dni LIKE ?)';
$params = array_fill(0, 3, '%'.$q.'%');
$s = $conexion->prepare('SELECT COUNT(*)'.$from);
$s->execute($params);
$total = (int)$s->fetchColumn();
$paginas = max(1, (int)ceil($total / 20));
$pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);
$s = $conexion->prepare('SELECT id,nombres,dni,correo,rol,activo'.$from.' ORDER BY nombres LIMIT 20 OFFSET '.(($pagina - 1) * 20));
$s->execute($params);
$usuarios = $s->fetchAll();
