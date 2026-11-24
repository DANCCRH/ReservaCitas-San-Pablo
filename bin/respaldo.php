<?php

/** Exporta esquema y datos a un directorio privado. No se ejecuta por HTTP. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__.'/../app/config/conexion.php';
require_once __DIR__.'/../app/services/correo.php';
$archivo = directorio_privado('respaldos').'/reserva-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql';
$fp = fopen($archivo, 'xb');
if (!$fp) {
    throw new RuntimeException('No se pudo crear el respaldo.');
}
$tablas = $conexion->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
fwrite($fp, "-- ReservaCitas respaldo privado\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n");
$conexion->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
$conexion->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
foreach ($tablas as $tabla) {
    $nombre = '`'.str_replace('`', '``', $tabla).'`';
    $crear = $conexion->query('SHOW CREATE TABLE '.$nombre)->fetch(PDO::FETCH_NUM)[1];
    fwrite($fp, 'DROP TABLE IF EXISTS '.$nombre.";\n".$crear.";\n");
    $campos = [];
    foreach ($conexion->query('SHOW COLUMNS FROM '.$nombre) as $c) {
        if (!str_contains($c['Extra'], 'GENERATED')) {
            $campos[] = $c['Field'];
        }
    }
    $cols = implode(',', array_map(fn ($c) => '`'.str_replace('`', '``', $c).'`', $campos));
    // Leer fila a fila evita cargar el historial completo en memoria en hosting.
    $s = $conexion->prepare('SELECT '.$cols.' FROM '.$nombre, [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
    $s->execute();
    while ($fila = $s->fetch(PDO::FETCH_NUM)) {
        $valores = array_map(fn ($v) => $v === null ? 'NULL' : $conexion->quote((string)$v), $fila);
        fwrite($fp, 'INSERT INTO '.$nombre.' ('.$cols.') VALUES ('.implode(',', $valores).");\n");
    }
    $s->closeCursor();
}
$conexion->commit();
fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fp);
chmod($archivo, 0600);
$triggers=[];foreach($conexion->query('SHOW TRIGGERS') as $t){$nombre='`'.str_replace('`','``',$t['Trigger']).'`';$fila=$conexion->query('SHOW CREATE TRIGGER '.$nombre)->fetch();$triggers[]=preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/','',$fila['SQL Original Statement']);}
file_put_contents($archivo.'.json', json_encode(['aplicacion' => 'ReservaCitas','database' => $dbname,'sha256' => hash_file('sha256', $archivo),'fecha' => date(DATE_ATOM),'tablas' => $tablas,'triggers'=>$triggers], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo $archivo."\n";
