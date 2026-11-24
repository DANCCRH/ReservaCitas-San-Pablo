<?php

/** Entrega local privada o mail() cuando el alojamiento tenga correo configurado. */
function directorio_privado($subcarpeta)
{
    global $config;
    $raiz = $config['storage_path'] ?? dirname(BASE_PATH, 2).'/reservacitas-privado-'.substr(hash('sha256', BASE_PATH), 0, 12);
    if (!is_dir($raiz) && !mkdir($raiz, 0700, true)) {
        throw new RuntimeException('No se pudo crear el almacenamiento privado.');
    }
    $ruta = $raiz.'/'.$subcarpeta;
    if (!is_dir($ruta) && !mkdir($ruta, 0700, true)) {
        throw new RuntimeException('No se pudo crear el directorio privado.');
    }
    $publico = str_replace('\\', '/', realpath(BASE_PATH));
    $privado = str_replace('\\', '/', realpath($ruta));
    if (str_starts_with(strtolower($privado).'/', strtolower($publico).'/')) {
        throw new RuntimeException('El almacenamiento debe estar fuera de la carpeta pública.');
    }
    return $ruta;
}

/** Cola durable: transporte local, mail del servidor o SMTP con STARTTLS/SMTPS. */
function encolar_correo($para, $asunto, $texto, $clave = null)
{
    global $conexion;
    if (!filter_var($para, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $para.$asunto)) {
        throw new DomainException('Destinatario o asunto de correo no válido.');
    }
    $q = $conexion->prepare('INSERT INTO correos_pendientes(para,asunto,mensaje,clave_evento) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
    $q->execute([$para,mb_substr($asunto, 0, 200),$texto,$clave]);
    return (int)$conexion->lastInsertId();
}
function preparar_correo($para, $asunto, $texto)
{
    return encolar_correo($para, $asunto, $texto);
}
function smtp_enviar($para, $asunto, $texto)
{
    global $config;
    $host = $config['smtp_host'] ?? '';
    $puerto = (int)($config['smtp_port'] ?? 587);
    $tls = $config['smtp_tls'] ?? 'starttls';
    $from = $config['mail_from'] ?? '';
    if (!preg_match('/^[a-z0-9.-]+$/iD', $host) || !filter_var($from, FILTER_VALIDATE_EMAIL) || !in_array($tls, ['starttls','ssl'], true)) {
        throw new RuntimeException('Configuración SMTP incompleta.');
    }
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true,'verify_peer_name' => true,'peer_name' => $host]]);
    $socket = stream_socket_client(($tls === 'ssl' ? 'ssl://' : 'tcp://').$host.':'.$puerto, $errno, $error, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        throw new RuntimeException('No se pudo conectar al servidor SMTP.');
    }stream_set_timeout($socket, 20);
    $leer = function ($esperado) use ($socket) {
        $respuesta = '';
        do {
            $linea = fgets($socket, 2048);
            if ($linea === false) {
                throw new RuntimeException('Se interrumpió la sesión SMTP.');
            }$respuesta .= $linea;
        } while (isset($linea[3]) && $linea[3] === '-');
        if (!in_array((int)substr($respuesta, 0, 3), (array)$esperado, true)) {
            throw new RuntimeException('Respuesta SMTP '.(int)substr($respuesta, 0, 3));
        }
    };
    $enviar = function ($orden, $codigo) use ($socket, $leer) {
        fwrite($socket, $orden."\r\n");
        $leer($codigo);
    };
    try {
        $leer(220);
        $enviar('EHLO reservacitas.local', 250);
        if ($tls === 'starttls') {
            $enviar('STARTTLS', 220);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se estableció TLS.');
            }$enviar('EHLO reservacitas.local', 250);
        }
        if (!empty($config['smtp_username'])) {
            $enviar('AUTH LOGIN', 334);
            $enviar(base64_encode($config['smtp_username']), 334);
            $enviar(base64_encode($config['smtp_password'] ?? ''), 235);
        }
        $enviar('MAIL FROM:<'.$from.'>', 250);
        $enviar('RCPT TO:<'.$para.'>', [250,251]);
        $enviar('DATA', 354);
        $contenido = 'From: ReservaCitas <'.$from.">\r\nTo: <".$para.">\r\nSubject: =?UTF-8?B?".base64_encode($asunto)."?=\r\nDate: ".date(DATE_RFC2822)."\r\nMessage-ID: <".bin2hex(random_bytes(12))."@reservacitas.local>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".chunk_split(base64_encode($texto), 76, "\r\n");
        fwrite($socket, $contenido."\r\n.\r\n");
        $leer(250);
        $enviar('QUIT', 221);
    } finally {
        fclose($socket);
    }
}
function procesar_correos($limite = 30)
{
    global $conexion,$config;
    $procesados = 0;
    $bloqueo = $conexion->query("SELECT GET_LOCK('".substr(hash('sha256', $config['database'] ?? ''), 0, 20)."_correo',0)")->fetchColumn();
    if (!$bloqueo) {
        return 0;
    }
    try {
        $q = $conexion->query("SELECT * FROM correos_pendientes WHERE estado='pendiente' AND proximo_intento<=NOW() AND intentos<5 ORDER BY id LIMIT ".max(1, min(100, (int)$limite)));
        foreach ($q->fetchAll() as $c) {
            try {
                $transporte = $config['mail_transport'] ?? 'archivo';
                if ($transporte === 'smtp') {
                    smtp_enviar($c['para'], $c['asunto'], $c['mensaje']);
                } elseif ($transporte === 'php_mail') {
                    $from = $config['mail_from'] ?? '';
                    if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !mail($c['para'], '=?UTF-8?B?'.base64_encode($c['asunto']).'?=', $c['mensaje'], ['From' => $from,'Content-Type' => 'text/plain; charset=UTF-8'])) {
                        throw new RuntimeException('El servidor no aceptó el mensaje.');
                    }
                } else {
                    if ($transporte !== 'archivo') {
                        throw new RuntimeException('Transporte no reconocido.');
                    }$ruta = directorio_privado('correo').'/cola-'.$c['id'].'.json';
                    if (file_put_contents($ruta, json_encode(['para' => $c['para'],'asunto' => $c['asunto'],'mensaje' => $c['mensaje'],'generado' => date(DATE_ATOM)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX) === false) {
                        throw new RuntimeException('No se pudo guardar el mensaje local.');
                    }
                }
                $conexion->prepare('UPDATE correos_pendientes SET estado=?,enviado_en=NOW(),intentos=intentos+1,ultimo_error=NULL WHERE id=?')->execute([$transporte === 'archivo' ? 'local' : 'enviado',$c['id']]);
                $procesados++;
            } catch (Throwable $e) {
                $intentos = (int)$c['intentos'] + 1;
                $conexion->prepare('UPDATE correos_pendientes SET estado=?,intentos=?,ultimo_error=?,proximo_intento=DATE_ADD(NOW(),INTERVAL 15 MINUTE) WHERE id=?')->execute([$intentos >= 5 ? 'fallido' : 'pendiente',$intentos,'No se completó la entrega. Revisar configuración SMTP.',$c['id']]);
                registro_operativo('correo_fallido',['correo_id' => $c['id'],'intento' => $intentos]);
            }
        }
    } finally {
        $conexion->query("SELECT RELEASE_LOCK('".substr(hash('sha256',$config['database'] ?? ''),0,20)."_correo')");
    }return $procesados;
}
