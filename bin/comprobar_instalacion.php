<?php
/** Diagnóstico administrativo de despliegue. Nunca se publica como endpoint web. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/config/conexion.php';
require __DIR__.'/../app/services/correo.php';
$checks=[];
$checks['php_compatible']=version_compare(PHP_VERSION,'8.2.0','>=');
foreach(['pdo_mysql','mbstring','openssl'] as $ext) $checks[$ext]=extension_loaded($ext);
$checks['tablas']=(int)$conexion->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()===28;
$checks['triggers']=(int)$conexion->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchColumn()===2;
$checks['claves_consultorios']=(int)$conexion->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME IN('fk_medico_sala','fk_cita_sala','fk_cita_serie','fk_cita_asistencia')")->fetchColumn()===4;
$checks['almacenamiento_privado']=is_writable(directorio_privado('logs'));
$checks['url_https']=($config['environment']??'local')!=='production'||str_starts_with($config['app_url']??'','https://');
$counts=[];
foreach(['cuentas_acceso','medicos','consultorios','citas','historial_citas'] as $table) $counts[$table]=(int)$conexion->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
$counts['pacientes']=(int)$conexion->query("SELECT COUNT(*) FROM pacientes WHERE rol='paciente'")->fetchColumn();
echo json_encode(['php'=>PHP_VERSION,'entorno'=>$config['environment']??'local','url'=>$config['app_url']??null,'verificaciones'=>$checks,'registros'=>$counts],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
exit(in_array(false,$checks,true)?1:0);
