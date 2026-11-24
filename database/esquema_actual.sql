-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: reservacitas_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auditoria_eventos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `entidad` varchar(40) NOT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `accion` varchar(60) NOT NULL,
  `detalle` varchar(1000) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ix_auditoria` (`fecha`,`entidad`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `auditoria_eventos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auditoria_usuarios`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `detalle` varchar(600) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `actor_id` (`actor_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `auditoria_usuarios_ibfk_1` FOREIGN KEY (`actor_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `auditoria_usuarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bloqueos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bloqueos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medico_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `inicio` time NOT NULL DEFAULT '00:00:00',
  `fin` time NOT NULL DEFAULT '23:59:59',
  `motivo` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bloqueo_medico_fecha` (`medico_id`,`fecha`),
  CONSTRAINT `bloqueos_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `citas`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `especialidad` varchar(80) NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `comentario` varchar(250) DEFAULT NULL,
  `estado` enum('pendiente','confirmado','atendido','cancelado','rechazado','no_asistio') NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `bloqueo_horario` tinyint(4) GENERATED ALWAYS AS (case when `estado` not in ('cancelado','rechazado') then 1 else NULL end) STORED,
  `llegada_en` datetime DEFAULT NULL,
  `medico_id` int(11) DEFAULT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `duracion` smallint(6) NOT NULL DEFAULT 30,
  `inicio_atencion` datetime DEFAULT NULL,
  `fin_atencion` datetime DEFAULT NULL,
  `atendido_por` int(11) DEFAULT NULL,
  `consultorio_id` int(11) DEFAULT NULL,
  `serie_id` int(11) DEFAULT NULL,
  `asistencia_confirmada_en` datetime DEFAULT NULL,
  `asistencia_confirmada_por` int(11) DEFAULT NULL,
  `codigo_turno` char(8) DEFAULT NULL,
  `llamado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cita_horario_paciente` (`paciente_id`,`fecha_cita`,`hora_cita`,`bloqueo_horario`),
  UNIQUE KEY `cita_horario_medico` (`medico_id`,`fecha_cita`,`hora_cita`,`bloqueo_horario`),
  UNIQUE KEY `codigo_turno` (`codigo_turno`),
  KEY `citas_estado_fecha` (`estado`,`fecha_cita`),
  KEY `cita_especialidad_fk` (`especialidad_id`),
  KEY `ix_citas_operacion` (`fecha_cita`,`estado`,`hora_cita`),
  KEY `ix_consultorio_fecha` (`consultorio_id`,`fecha_cita`,`estado`),
  KEY `fk_cita_serie` (`serie_id`),
  KEY `fk_cita_asistencia` (`asistencia_confirmada_por`),
  CONSTRAINT `cita_especialidad_fk` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  CONSTRAINT `cita_medico_fk` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cita_asistencia` FOREIGN KEY (`asistencia_confirmada_por`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `fk_cita_sala` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `fk_cita_serie` FOREIGN KEY (`serie_id`) REFERENCES `series_citas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52691 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(150) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=519 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consultas`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultas` (
  `id_consulta` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `asunto` varchar(120) NOT NULL,
  `mensaje` varchar(1000) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `leido_en` datetime DEFAULT NULL,
  `origen_contacto_id` int(11) DEFAULT NULL,
  `remitente_nombre` varchar(100) DEFAULT NULL,
  `remitente_correo` varchar(100) DEFAULT NULL,
  `remitente_telefono` varchar(15) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'nueva',
  `asignado_id` int(11) DEFAULT NULL,
  `respuesta` text DEFAULT NULL,
  `respondido_en` datetime DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_consulta`),
  UNIQUE KEY `origen_contacto_id` (`origen_contacto_id`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=519 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `consultorios`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consultorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `ubicacion` varchar(120) NOT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `correos_pendientes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `correos_pendientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `para` varchar(100) NOT NULL,
  `asunto` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `proximo_intento` datetime DEFAULT current_timestamp(),
  `creado_en` datetime DEFAULT current_timestamp(),
  `enviado_en` datetime DEFAULT NULL,
  `ultimo_error` varchar(200) DEFAULT NULL,
  `clave_evento` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave_evento` (`clave_evento`),
  KEY `ix_correo` (`estado`,`proximo_intento`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cuentas_acceso`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_acceso` (
  `persona_id` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(20) NOT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `sesion_version` int(11) NOT NULL DEFAULT 0,
  `clave_temporal` tinyint(4) NOT NULL DEFAULT 0,
  `correo_verificado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`persona_id`),
  UNIQUE KEY `correo` (`correo`),
  CONSTRAINT `fk_cuenta_persona` FOREIGN KEY (`persona_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `especialidades`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(400) NOT NULL,
  `imagen` varchar(180) NOT NULL,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historial_citas`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cita_id` int(11) NOT NULL,
  `estado_anterior` varchar(20) DEFAULT NULL,
  `estado_nuevo` varchar(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `accion` varchar(30) NOT NULL DEFAULT 'estado',
  `detalle` varchar(600) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `historial_cita` (`cita_id`,`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historial_citas_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_citas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=227794 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `horarios_versiones`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `horarios_versiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medico_id` int(11) NOT NULL,
  `desde` date NOT NULL,
  `turnos_json` longtext NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_version` (`medico_id`,`desde`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `horarios_versiones_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  CONSTRAINT `horarios_versiones_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `limites_acceso`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `limites_acceso` (
  `clave` char(64) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `inicio` bigint(20) NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lista_espera`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lista_espera` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `especialidad_id` int(11) NOT NULL,
  `medico_id` int(11) DEFAULT NULL,
  `desde` date NOT NULL,
  `hasta` date NOT NULL,
  `hora_desde` time NOT NULL,
  `hora_hasta` time NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'activa',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `oferta_cita_id` int(11) DEFAULT NULL,
  `oferta_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `especialidad_id` (`especialidad_id`),
  KEY `medico_id` (`medico_id`),
  KEY `ix_espera` (`estado`,`especialidad_id`,`desde`,`hasta`),
  CONSTRAINT `lista_espera_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `lista_espera_ibfk_2` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  CONSTRAINT `lista_espera_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medicos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `especialidad_id` int(11) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `colegiatura` varchar(20) NOT NULL,
  `descripcion` varchar(500) NOT NULL DEFAULT '',
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `usuario_id` int(11) DEFAULT NULL,
  `consultorio_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colegiatura` (`colegiatura`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `especialidad_id` (`especialidad_id`),
  KEY `fk_medico_sala` (`consultorio_id`),
  CONSTRAINT `fk_medico_sala` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `medico_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `medicos_ibfk_1` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mensajes_contacto`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensajes_contacto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `asunto` varchar(120) NOT NULL,
  `mensaje` varchar(1000) NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `leido_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notificaciones`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` varchar(600) NOT NULL,
  `enlace` varchar(150) NOT NULL DEFAULT 'mis_solicitudes.php',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `leido_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notificacion_usuario` (`paciente_id`,`leido_en`,`id`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=430 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pacientes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(150) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `es_invitado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `rol` enum('paciente','admin','recepcion','medico','asistencial') NOT NULL DEFAULT 'paciente',
  `sesion_version` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `genero` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `tipo_documento` varchar(12) NOT NULL DEFAULT 'DNI',
  `clave_temporal` tinyint(4) NOT NULL DEFAULT 0,
  `correo_verificado` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=5061 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER sincronizar_cuenta_insert AFTER INSERT ON pacientes FOR EACH ROW BEGIN IF NEW.es_invitado=0 AND NEW.correo IS NOT NULL THEN INSERT INTO cuentas_acceso VALUES(NEW.id,NEW.correo,NEW.contrasena,NEW.rol,NEW.activo,NEW.sesion_version,NEW.clave_temporal,NEW.correo_verificado) ON DUPLICATE KEY UPDATE correo=NEW.correo,contrasena=NEW.contrasena,rol=NEW.rol,activo=NEW.activo,sesion_version=NEW.sesion_version,clave_temporal=NEW.clave_temporal,correo_verificado=NEW.correo_verificado; ELSE DELETE FROM cuentas_acceso WHERE persona_id=NEW.id; END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 */ /*!50003 TRIGGER sincronizar_cuenta_update AFTER UPDATE ON pacientes FOR EACH ROW BEGIN IF NEW.es_invitado=0 AND NEW.correo IS NOT NULL THEN INSERT INTO cuentas_acceso VALUES(NEW.id,NEW.correo,NEW.contrasena,NEW.rol,NEW.activo,NEW.sesion_version,NEW.clave_temporal,NEW.correo_verificado) ON DUPLICATE KEY UPDATE correo=NEW.correo,contrasena=NEW.contrasena,rol=NEW.rol,activo=NEW.activo,sesion_version=NEW.sesion_version,clave_temporal=NEW.clave_temporal,correo_verificado=NEW.correo_verificado; ELSE DELETE FROM cuentas_acceso WHERE persona_id=NEW.id; END IF; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `personal_equipos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_equipos` (
  `usuario_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  PRIMARY KEY (`usuario_id`,`medico_id`),
  KEY `medico_id` (`medico_id`),
  CONSTRAINT `personal_equipos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `personal_equipos_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `preferencias_avisos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preferencias_avisos` (
  `usuario_id` int(11) NOT NULL,
  `citas_email` tinyint(4) NOT NULL DEFAULT 1,
  `recordatorios_email` tinyint(4) NOT NULL DEFAULT 1,
  `espera_email` tinyint(4) NOT NULL DEFAULT 1,
  `recordatorios_web` tinyint(4) NOT NULL DEFAULT 1,
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`),
  CONSTRAINT `preferencias_avisos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `propuestas_citas`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `propuestas_citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cita_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `motivo` varchar(250) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `creado_por` int(11) NOT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `resuelto_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medico_id` (`medico_id`),
  KEY `creado_por` (`creado_por`),
  KEY `ix_propuesta` (`cita_id`,`estado`),
  CONSTRAINT `propuestas_citas_ibfk_1` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`),
  CONSTRAINT `propuestas_citas_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  CONSTRAINT `propuestas_citas_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recuperaciones`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `vence_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `recuperaciones_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reglas_agenda`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reglas_agenda` (
  `clave` varchar(40) NOT NULL,
  `valor` int(11) NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `responsables_pacientes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `responsables_pacientes` (
  `responsable_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `parentesco` varchar(30) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`responsable_id`,`paciente_id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `responsables_pacientes_ibfk_1` FOREIGN KEY (`responsable_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `responsables_pacientes_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `responsables_pacientes_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `series_citas`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `series_citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `solicitud` char(64) NOT NULL,
  `intervalo_dias` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `solicitud` (`solicitud`),
  KEY `paciente_id` (`paciente_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `series_citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  CONSTRAINT `series_citas_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turnos`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medico_id` int(11) NOT NULL,
  `dia` tinyint(4) NOT NULL,
  `inicio` time NOT NULL,
  `fin` time NOT NULL,
  `duracion` smallint(6) NOT NULL DEFAULT 30,
  PRIMARY KEY (`id`),
  UNIQUE KEY `turno_unico` (`medico_id`,`dia`,`inicio`),
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=576 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `verificaciones_correo`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verificaciones_correo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `verificaciones_correo_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `pacientes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `versiones_esquema`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `versiones_esquema` (
  `version` varchar(40) NOT NULL,
  `aplicado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
