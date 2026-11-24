<?php

/** Uso: php restaurar.php archivo.sql --confirmar=restaurar. Solo respaldos privados propios. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__.'/../app/config/conexion.php';
require_once __DIR__.'/../app/services/correo.php';
try {
    if (($argv[2] ?? '') !== '--confirmar=restaurar') {
        throw new RuntimeException('Indica archivo.sql y --confirmar=restaurar. La operación reemplaza las tablas de esta base.');
    }
    $archivo = realpath($argv[1] ?? '');
    $permitido = realpath(directorio_privado('respaldos'));
    if (!$archivo || !str_starts_with(strtolower(str_replace('\\', '/', $archivo)), strtolower(str_replace('\\', '/', $permitido)).'/') || pathinfo($archivo, PATHINFO_EXTENSION) !== 'sql') {
        throw new RuntimeException('Selecciona un respaldo del directorio privado.');
    }
    $meta = json_decode(file_get_contents($archivo.'.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($meta['aplicacion'] ?? '') !== 'ReservaCitas' || ($meta['database'] ?? '') !== $dbname || !hash_equals($meta['sha256'] ?? '', hash_file('sha256', $archivo))) {
        throw new RuntimeException('El respaldo no coincide con la base o su integridad no es válida.');
    }
    // Restaurar tablas del mismo esquema. La base seleccionada proviene de configuración.
    // El archivo propio contiene varias sentencias DDL, no una consulta preparada de formulario.
    // Procesar sentencias por separado evita superar max_allowed_packet.
    // El separador respeta comillas, escapes y comentarios del SQL generado.
    $fp = fopen($archivo, 'rb');
    $buffer = '';
    $comilla = null;
    $escape = false;
    $comentario = false;
    $bloque = false;
    $lote = 0;
    $aplicar = function ($sql) use ($conexion, &$lote) {
        if (preg_match('/^\s*INSERT\s+INTO\b/i', $sql)) {
            if (!$conexion->inTransaction()) $conexion->beginTransaction();
            $conexion->exec($sql);
            if (++$lote >= 2000) { $conexion->commit(); $lote = 0; }
        } else {
            // El DDL confirma transacciones de forma implícita: cerrar el lote antes.
            if ($conexion->inTransaction()) $conexion->commit();
            $lote = 0;
            $conexion->exec($sql);
        }
    };
    while (($linea = fgets($fp)) !== false) {
        for ($i = 0,$n = strlen($linea);$i < $n;$i++) {
            $ch = $linea[$i];
            $next = $linea[$i + 1] ?? '';
            if ($comentario) {
                if ($ch === "\n") {
                    $comentario = false;
                    $buffer .= "\n";
                }continue;
            }
            if ($bloque) {
                if ($ch === '*' && $next === '/') {
                    $bloque = false;
                    $i++;
                }continue;
            }
            if ($comilla !== null) {
                $buffer .= $ch;
                if ($escape) {
                    $escape = false;
                    continue;
                }if ($ch === '\\') {
                    $escape = true;
                    continue;
                }if ($ch === $comilla) {
                    $comilla = null;
                }continue;
            }
            if ($ch === '#' || ($ch === '-' && $next === '-' && ctype_space($linea[$i + 2] ?? ' '))) {
                $comentario = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $bloque = true;
                $i++;
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $comilla = $ch;
                $buffer .= $ch;
                continue;
            }
            if ($ch === ';') {
                if (trim($buffer) !== '') {
                    $aplicar($buffer);
                }$buffer = '';
            } else {
                $buffer .= $ch;
            }
        }
    }
    fclose($fp);
    if (trim($buffer) !== '') {
        $aplicar($buffer);
    }
    if ($conexion->inTransaction()) $conexion->commit();
    foreach ($meta['triggers'] ?? [] as $ddl) {
        $conexion->exec($ddl);
    }
    echo "Respaldo restaurado en la base configurada.\n";
} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
