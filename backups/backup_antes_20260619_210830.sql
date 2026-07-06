-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: celr_app
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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_date` (`created_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-01 04:25:07'),(2,1,'UPDATE','USER',3,'Actualizaci├│n de usuario: conductor',NULL,NULL,'127.0.0.1','2026-02-01 04:25:42'),(3,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-01 16:37:41'),(4,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-02-01 17:26:29'),(5,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-02-01 18:07:47'),(6,1,'CREATE','TRIP',1,'Registro de nuevo viaje',NULL,NULL,'127.0.0.1','2026-02-01 19:14:30'),(7,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-02-01 23:05:13'),(8,NULL,'TEST','SYSTEM',0,'Prueba automatizada de auditor├¡a',NULL,NULL,'0.0.0.0','2026-02-01 23:31:38'),(9,NULL,'TEST','SYSTEM',0,'Prueba automatizada de auditor├¡a',NULL,NULL,'0.0.0.0','2026-02-01 23:32:58'),(10,NULL,'TEST','SYSTEM',0,'Prueba automatizada de auditor├¡a',NULL,NULL,'0.0.0.0','2026-02-01 23:34:12'),(11,NULL,'TEST','SYSTEM',0,'Prueba automatizada de auditor├¡a',NULL,NULL,'0.0.0.0','2026-02-01 23:34:55'),(12,1,'CREATE','EXPENSE',5,'Registro de nuevo gasto: Peaje',NULL,NULL,'0.0.0.0','2026-02-01 23:39:19'),(13,1,'TEST_INTEGRAL','SYSTEM',0,'Verificaci├│n de trazabilidad 2025',NULL,NULL,'0.0.0.0','2026-02-01 23:39:19'),(14,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-01 23:40:13'),(15,1,'CREATE','EXPENSE',6,'Registro de nuevo gasto: Peaje',NULL,NULL,'0.0.0.0','2026-02-01 23:49:32'),(16,1,'TEST_INTEGRAL','SYSTEM',0,'Verificaci├│n de trazabilidad 2025',NULL,NULL,'0.0.0.0','2026-02-01 23:49:32'),(17,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-01 23:53:23'),(18,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-01 23:53:51'),(19,1,'CREATE','EXPENSE',7,'Registro de nuevo gasto: Peaje',NULL,NULL,'0.0.0.0','2026-02-01 23:54:51'),(20,1,'TEST_INTEGRAL','SYSTEM',0,'Verificaci├│n de trazabilidad 2025',NULL,NULL,'0.0.0.0','2026-02-01 23:54:51'),(21,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-01 23:55:24'),(22,1,'CREATE','EXPENSE',8,'Registro de nuevo gasto: Peaje',NULL,NULL,'0.0.0.0','2026-02-02 00:02:49'),(23,1,'TEST_INTEGRAL','SYSTEM',0,'Verificaci├│n de trazabilidad 2025',NULL,NULL,'0.0.0.0','2026-02-02 00:02:49'),(24,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:03:17'),(25,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:03:32'),(26,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:03:52'),(27,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:05:32'),(28,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:05:37'),(29,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:07:11'),(30,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:08:20'),(31,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:09:57'),(32,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:10:34'),(33,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:10:36'),(34,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:10:56'),(35,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:11:35'),(36,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:12:03'),(37,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:12:42'),(38,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:14:13'),(39,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:17:10'),(40,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:17:58'),(41,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:18:34'),(42,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:18:36'),(43,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:19:03'),(44,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:20:37'),(45,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 2',NULL,NULL,'127.0.0.1','2026-02-02 00:21:43'),(46,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 00:24:07'),(47,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 00:26:40'),(48,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 00:27:27'),(49,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 00:56:55'),(50,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 01:15:34'),(51,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 01:15:39'),(52,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 01:15:44'),(53,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-02-02 01:24:16'),(60,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-07 17:05:27'),(64,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'::1','2026-05-07 18:56:02'),(83,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'::1','2026-05-07 19:16:37'),(84,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'::1','2026-05-07 19:17:17'),(85,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'::1','2026-05-07 19:20:44'),(86,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'::1','2026-05-07 19:21:36'),(87,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'127.0.0.1','2026-05-07 21:49:45'),(88,1,'UPDATE','TRIP',1,'Carga de prueba de entrega (ePOD)',NULL,NULL,'127.0.0.1','2026-05-07 21:50:13'),(89,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'127.0.0.1','2026-05-07 21:53:36'),(90,1,'CREATE','EXPENSE',9,'Registro de nuevo gasto: combustible',NULL,NULL,'127.0.0.1','2026-05-07 21:59:04'),(91,1,'CREATE','EXPENSE',10,'Registro de nuevo gasto: peajes',NULL,NULL,'127.0.0.1','2026-05-07 22:01:15'),(92,1,'CREATE','EXPENSE',11,'Registro de nuevo gasto: desencarrozada',NULL,NULL,'127.0.0.1','2026-05-07 22:02:01'),(93,1,'CREATE','EXPENSE',12,'Registro de nuevo gasto: cargue',NULL,NULL,'127.0.0.1','2026-05-07 22:02:34'),(94,1,'CREATE','EXPENSE',13,'Registro de nuevo gasto: descargue',NULL,NULL,'127.0.0.1','2026-05-07 22:03:09'),(95,1,'CREATE','EXPENSE',14,'Registro de nuevo gasto: comision',NULL,NULL,'127.0.0.1','2026-05-07 22:03:41'),(96,1,'CREATE','EXPENSE',15,'Registro de nuevo gasto: r_repuestos',NULL,NULL,'127.0.0.1','2026-05-07 22:04:19'),(97,1,'CREATE','EXPENSE',16,'Registro de nuevo gasto: bascula',NULL,NULL,'127.0.0.1','2026-05-07 22:04:54'),(98,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 00:30:06'),(99,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'127.0.0.1','2026-05-08 00:32:31'),(100,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 00:37:53'),(101,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 00:41:22'),(102,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 00:45:17'),(103,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 01:05:38'),(104,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 01:16:22'),(105,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 01:16:24'),(106,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 01:30:41'),(107,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 01:59:53'),(108,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 0',NULL,NULL,'127.0.0.1','2026-05-08 02:04:27'),(109,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 19:51:14'),(110,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 20:03:45'),(111,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 20:05:52'),(112,1,'BACKUP','SYSTEM',0,'Respaldo generado exitosamente: db_backup_2026-05-08_22-05-57.sql',NULL,NULL,'127.0.0.1','2026-05-08 20:05:57'),(113,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 20:06:01'),(114,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 20:08:21'),(115,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 20:08:25'),(116,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-05-08 23:51:52'),(117,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-06-17 11:23:08'),(118,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-06-18 00:34:26'),(119,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-06-18 00:35:38'),(120,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-06-18 00:39:06'),(121,1,'UPDATE','TRIP',1,'Actualizaci├│n informaci├│n viaje',NULL,NULL,'127.0.0.1','2026-06-18 00:45:23'),(122,1,'UPDATE','TRIP',1,'Carga de prueba de entrega (ePOD)',NULL,NULL,'127.0.0.1','2026-06-18 01:02:03'),(123,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: 1',NULL,NULL,'127.0.0.1','2026-06-18 08:25:30'),(124,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-06-18 09:10:49'),(125,1,'VIEW','SYSTEM_HEALTH',0,'Nivel de salud chequeado. Pendientes: -1',NULL,NULL,'127.0.0.1','2026-06-18 13:43:28'),(126,1,'UPDATE','VEHICLE',1,'Veh├¡culo actualizado: SKN756',NULL,NULL,'127.0.0.1','2026-06-18 13:49:35');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_type` enum('F├¡sica','Jur├¡dica') NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname1` varchar(100) DEFAULT NULL,
  `lastname2` varchar(100) DEFAULT NULL,
  `business_name` varchar(200) DEFAULT NULL,
  `legal_id` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Costa Rica',
  `department` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_person_type` (`person_type`),
  KEY `idx_active` (`active`),
  KEY `idx_business_name` (`business_name`),
  KEY `idx_lastname` (`lastname1`),
  KEY `idx_clients_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (2,'Jur├¡dica',NULL,NULL,NULL,'PRETECOR S.A.S.','890209207-6','facturacionycartera@pretecor.co','+57 (607) 697 0963','(+57) 322 269 5731','Calle 36 N┬░ 31 39, OF: 219, Chicamocha Centro Empresarial','Colombia','Santander','Bucaramanga',NULL,NULL,1,'2026-01-12 01:08:16','2026-01-12 01:08:16'),(3,'Jur├¡dica',NULL,NULL,NULL,'CARGAMOS S.A.S.','800050634-5',NULL,'0376960466',NULL,'CRA 17B # 53-28 PISO 2','Colombia','Santander','Bucaramanga',NULL,NULL,1,'2026-01-12 01:49:19','2026-01-12 01:50:19'),(4,'Jur├¡dica',NULL,NULL,NULL,'COLTANQUES SAS','860040576-1','liquidaciones@coltanques.com.co','3104770564','3143597442',NULL,'Colombia','Bogot├í D.C.','Bogot├í',NULL,NULL,1,'2026-01-12 13:19:54','2026-01-12 13:19:54');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency` varchar(10) DEFAULT 'COP',
  `base_country` varchar(100) DEFAULT 'Colombia',
  `currency_symbol` varchar(10) DEFAULT '$',
  `decimal_separator` varchar(1) DEFAULT ',',
  `thousands_separator` varchar(1) DEFAULT '.',
  `decimal_count` int(11) DEFAULT 0,
  `default_rete_fuente` decimal(5,2) DEFAULT 0.00,
  `default_rete_ica` decimal(5,2) DEFAULT 0.00,
  `default_iva_percent` decimal(5,2) DEFAULT 0.00,
  `default_rete_iva_percent` decimal(5,2) DEFAULT 0.00,
  `ganancia_nacional_percent` decimal(5,2) DEFAULT 0.00,
  `ganancia_urbano_percent` decimal(5,2) DEFAULT 0.00,
  `weight_unit` varchar(20) DEFAULT 'Toneladas',
  `distance_unit` varchar(20) DEFAULT 'Kil├│metros',
  `maint_warning_kms` int(11) DEFAULT 500,
  `doc_warning_days` int(11) DEFAULT 30,
  `last_backup_at` datetime DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `billing_resolution` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `satrack_api_key` varchar(255) DEFAULT NULL,
  `satrack_token` varchar(255) DEFAULT NULL,
  `satrack_api_url` varchar(255) DEFAULT 'https://api.satrack.com/v1/locations',
  `unusual_expense_threshold` decimal(15,2) DEFAULT 1000000.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES (1,'COP','Colombia','$',',','.',0,1.00,1.00,0.00,0.00,12.00,30.00,'Toneladas','Kil├│metros',500,30,'2026-05-08 15:05:57','','','','','','','uploads/logo/logo_1767932708.jpg','2026-05-08 20:08:17','','','https://api.satrack.com/v1/locations',2000000.00);
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `type` enum('variable','fixed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Combustible','combustible','variable','2025-12-30 03:35:12'),(2,'Peajes','peajes','variable','2025-12-30 03:35:12'),(3,'Cargue','cargue','variable','2025-12-30 03:35:12'),(4,'Descargue','descargue','variable','2025-12-30 03:35:12'),(5,'Vi├íticos','viaticos','variable','2025-12-30 03:35:12'),(6,'B├íscula','bascula','variable','2025-12-30 03:35:12'),(7,'Hospedaje','hospedaje','variable','2025-12-30 03:35:12'),(8,'Encarrozada','encarrozada','variable','2025-12-30 03:35:12'),(9,'Desencarrozada','desencarrozada','variable','2025-12-30 03:35:12'),(10,'Lavada','lavada','variable','2025-12-30 03:35:12'),(11,'Montaje de Llantas','montaje_llantas','variable','2025-12-30 03:35:12'),(12,'Engrase','engrase','variable','2025-12-30 03:35:12'),(13,'Repuestos (Viaje)','r_repuestos','variable','2025-12-30 03:35:12'),(14,'Comisi├│n','comision','variable','2025-12-30 03:35:12'),(15,'Parqueadero (Viaje)','parqueadero_viaje','variable','2025-12-30 03:35:12'),(16,'Otros (Viaje)','otros_viaje','variable','2025-12-30 03:35:12'),(17,'Mantenimiento','mantenimiento','fixed','2025-12-30 03:35:12'),(18,'Repuestos','repuestos','fixed','2025-12-30 03:35:12'),(20,'Aceite','aceite','fixed','2025-12-30 03:35:12'),(21,'SOAT','soat','fixed','2025-12-30 03:35:12'),(22,'Seguros','seguros','fixed','2025-12-30 03:35:12'),(23,'Impuestos Veh├¡culo','impuestos','fixed','2025-12-30 03:35:12'),(24,'Parqueadero','parqueadero_fijo','fixed','2025-12-30 03:35:12'),(25,'Sueldo B├ísico Conductor','sueldo','variable','2026-01-13 02:04:41'),(26,'Auxilio de Transporte','auxilio_transporte','variable','2026-01-13 02:04:41');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `paid_by` enum('Conductor','Propietario') NOT NULL DEFAULT 'Conductor',
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_photo` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `eds_name` varchar(100) DEFAULT NULL,
  `eds_location` varchar(100) DEFAULT NULL,
  `eds_state_id` int(11) DEFAULT NULL,
  `eds_city_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `gallons` decimal(10,2) DEFAULT NULL,
  `price_per_gallon` decimal(10,2) DEFAULT NULL,
  `invoice_status` enum('Pendiente','Cancelada') DEFAULT 'Pendiente',
  `created_by` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `tank_mileage` decimal(10,2) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `fk_expenses_supplier` (`supplier_id`),
  KEY `idx_expenses_trip` (`trip_id`),
  KEY `idx_expenses_vehicle` (`vehicle_id`),
  KEY `idx_expenses_category` (`category`),
  KEY `idx_expenses_date` (`date`),
  KEY `idx_expenses_paid_by` (`paid_by`),
  KEY `idx_expense_eds_city` (`eds_city_id`),
  KEY `idx_trip_id` (`trip_id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_date` (`date`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_expenses_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (9,1,1,'combustible','Propietario',1630000.00,'Combustible a cr├®dito.','uploads/receipts/rec_69fd0b28e5526.jpg','2022-09-05','Credito','EDS El Juve - Petromil','',0,0,'04320',200.00,8150.00,'Pendiente',1,3,NULL,NULL,NULL),(10,1,1,'peajes','Conductor',377900.00,'',NULL,'2026-09-02','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(11,1,1,'desencarrozada','Conductor',60000.00,'',NULL,'2022-09-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(12,1,1,'cargue','Conductor',50000.00,'',NULL,'2026-09-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(13,1,1,'descargue','Conductor',40000.00,'',NULL,'2022-09-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(14,1,1,'comision','Conductor',50000.00,'',NULL,'2022-09-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(15,1,1,'r_repuestos','Conductor',19000.00,'',NULL,'2022-09-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL),(16,1,1,'bascula','Conductor',120000.00,'',NULL,'2022-02-03','Efectivo','','',0,0,'',NULL,NULL,'Pendiente',1,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_vouchers`
--

DROP TABLE IF EXISTS `fuel_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `gallons` decimal(10,2) DEFAULT 0.00,
  `price_per_gallon` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `station_name` varchar(255) DEFAULT NULL,
  `station_location` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Efectivo',
  `invoice_required` tinyint(1) DEFAULT 0,
  `invoice_number` varchar(100) DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vehicle_id` (`vehicle_id`),
  KEY `idx_trip_id` (`trip_id`),
  KEY `idx_driver_id` (`driver_id`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fuel_vouchers_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fuel_vouchers_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fuel_vouchers_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_vouchers`
--

LOCK TABLES `fuel_vouchers` WRITE;
/*!40000 ALTER TABLE `fuel_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loc_cities`
--

DROP TABLE IF EXISTS `loc_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loc_cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loc_cities_state_id` (`state_id`),
  KEY `idx_loc_cities_name` (`name`),
  CONSTRAINT `loc_cities_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `loc_states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loc_cities`
--

LOCK TABLES `loc_cities` WRITE;
/*!40000 ALTER TABLE `loc_cities` DISABLE KEYS */;
INSERT INTO `loc_cities` VALUES (1,2,'Medell├¡n'),(2,2,'Bello'),(3,2,'Itag├╝├¡'),(4,2,'Envigado'),(5,2,'Apartad├│'),(6,2,'Rionegro'),(7,4,'Barranquilla'),(8,4,'Soledad'),(9,4,'Malambo'),(10,4,'Sabanalarga'),(11,5,'Bogot├í'),(12,6,'Cartagena'),(13,6,'Magangu├®'),(14,6,'Turbaco'),(15,8,'Manizales'),(16,8,'La Dorada'),(17,8,'Villamar├¡a'),(18,15,'Soacha'),(19,15,'Facatativ├í'),(20,15,'Girardot'),(21,15,'Ch├¡a'),(22,15,'Zipaquir├í'),(23,18,'Neiva'),(24,18,'Pitalito'),(25,20,'Santa Marta'),(26,20,'Ci├®naga'),(27,21,'Villavicencio'),(28,21,'Acac├¡as'),(29,22,'Pasto'),(30,22,'Ipiales'),(31,22,'Tumaco'),(32,23,'C├║cuta'),(33,23,'Oca├▒a'),(34,23,'Villa del Rosario'),(35,25,'Armenia'),(36,25,'Calarc├í'),(37,26,'Pereira'),(38,26,'Dosquebradas'),(39,28,'Bucaramanga'),(40,28,'Floridablanca'),(41,28,'Barrancabermeja'),(42,28,'Gir├│n'),(43,28,'Piedecuesta'),(44,30,'Ibagu├®'),(45,30,'Espinal'),(46,31,'Cali'),(47,31,'Buenaventura'),(48,31,'Palmira'),(49,31,'Tulu├í'),(50,31,'Yumbo'),(51,31,'Buga'),(52,31,'Cartago'),(53,31,'Jamund├¡'),(54,2,'Abejorral'),(55,2,'Abriaqu├¡'),(56,2,'Alejandr├¡a'),(57,2,'Amag├í'),(58,2,'Amalfi'),(59,2,'Andes'),(60,2,'Angel├│polis'),(61,2,'Angostura'),(62,2,'Anor├¡'),(63,2,'Anza'),(64,2,'Arboletes'),(65,2,'Argelia'),(66,2,'Armenia'),(67,2,'Barbosa'),(68,2,'Belmira'),(69,2,'Betania'),(70,2,'Betulia'),(71,2,'Brice├▒o'),(72,2,'Buritic├í'),(73,2,'Caicedo'),(74,2,'Caldas'),(75,2,'Campamento'),(76,2,'Caracol├¡'),(77,2,'Caramanta'),(78,2,'Carepa'),(79,2,'Carolina'),(80,2,'Caucasia'),(81,2,'Ca├▒asgordas'),(82,2,'Chigorod├│'),(83,2,'Cisneros'),(84,2,'Ciudad Bol├¡var'),(85,2,'Cocorn├í'),(86,2,'Concepci├│n'),(87,2,'Concordia'),(88,2,'Copacabana'),(89,2,'C├íceres'),(90,2,'Dabeiba'),(91,2,'Don Mat├¡as'),(92,2,'Eb├®jico'),(93,2,'El Bagre'),(94,2,'El Carmen de Viboral'),(95,2,'El Santuario'),(96,2,'Entrerrios'),(97,2,'Fredonia'),(98,2,'Frontino'),(99,2,'Giraldo'),(100,2,'Girardota'),(101,2,'Granada'),(102,2,'Guadalupe'),(103,2,'Guarne'),(104,2,'Guatap├®'),(105,2,'G├│mez Plata'),(106,2,'Heliconia'),(107,2,'Hispania'),(108,2,'Ituango'),(109,2,'Jard├¡n'),(110,2,'Jeric├│'),(111,2,'La Ceja'),(112,2,'La Estrella'),(113,2,'La Pintada'),(114,2,'La Uni├│n'),(115,2,'Liborina'),(116,2,'Maceo'),(117,2,'Marinilla'),(118,2,'Montebello'),(119,2,'Murind├│'),(120,2,'Mutat├í'),(121,2,'Nari├▒o'),(122,2,'Nech├¡'),(123,2,'Necocl├¡'),(124,2,'Olaya'),(125,2,'Peque'),(126,2,'Pe├▒ol'),(127,2,'Pueblorrico'),(128,2,'Puerto Berr├¡o'),(129,2,'Puerto Nare'),(130,2,'Puerto Triunfo'),(131,2,'Remedios'),(132,2,'Retiro'),(133,2,'Sabanalarga'),(134,2,'Sabaneta'),(135,2,'Salgar'),(136,2,'San Andr├®s de Cuerqu├¡a'),(137,2,'San Carlos'),(138,2,'San Francisco'),(139,2,'San Jer├│nimo'),(140,2,'San Jos├® de La Monta├▒a'),(141,2,'San Juan de Urab├í'),(142,2,'San Luis'),(143,2,'San Pedro'),(144,2,'San Pedro de Uraba'),(145,2,'San Rafael'),(146,2,'San Roque'),(147,2,'San Vicente'),(148,2,'Santa B├írbara'),(149,2,'Santa Rosa de Osos'),(150,2,'Santaf├® de Antioquia'),(151,2,'Santo Domingo'),(152,2,'Segovia'),(153,2,'Sons├│n'),(154,2,'Sopetr├ín'),(155,2,'Taraz├í'),(156,2,'Tarso'),(157,2,'Titirib├¡'),(158,2,'Toledo'),(159,2,'Turbo'),(160,2,'T├ímesis'),(161,2,'Uramita'),(162,2,'Urrao'),(163,2,'Valdivia'),(164,2,'Valpara├¡so'),(165,2,'Vegach├¡'),(166,2,'Venecia'),(167,2,'Vig├¡a del Fuerte'),(168,2,'Yal├¡'),(169,2,'Yarumal'),(170,2,'Yolomb├│'),(171,2,'Yond├│'),(172,2,'Zaragoza'),(173,3,'Arauca'),(174,3,'Arauquita'),(175,3,'Cravo Norte'),(176,3,'Fortul'),(177,3,'Puerto Rond├│n'),(178,3,'Saravena'),(179,3,'Tame'),(180,4,'Baranoa'),(181,4,'Campo de La Cruz'),(182,4,'Candelaria'),(183,4,'Galapa'),(184,4,'Juan de Acosta'),(185,4,'Luruaco'),(186,4,'Manat├¡'),(187,4,'Palmar de Varela'),(188,4,'Pioj├│'),(189,4,'Polonuevo'),(190,4,'Ponedera'),(191,4,'Puerto Colombia'),(192,4,'Repel├│n'),(193,4,'Sabanagrande'),(194,4,'Santa Luc├¡a'),(195,4,'Santo Tom├ís'),(196,4,'Suan'),(197,4,'Tubar├í'),(198,4,'Usiacur├¡'),(199,34,'Bogot├í D.C.'),(200,6,'Ach├¡'),(201,6,'Altos del Rosario'),(202,6,'Arenal'),(203,6,'Arjona'),(204,6,'Arroyohondo'),(205,6,'Barranco de Loba'),(206,6,'Calamar'),(207,6,'Cantagallo'),(208,6,'Cicuco'),(209,6,'Clemencia'),(210,6,'C├│rdoba'),(211,6,'El Carmen de Bol├¡var'),(212,6,'El Guamo'),(213,6,'El Pe├▒├│n'),(214,6,'Hatillo de Loba'),(215,6,'Mahates'),(216,6,'Margarita'),(217,6,'Mar├¡a la Baja'),(218,6,'Momp├│s'),(219,6,'Montecristo'),(220,6,'Morales'),(221,6,'Noros├¡'),(222,6,'Pinillos'),(223,6,'Regidor'),(224,6,'R├¡o Viejo'),(225,6,'San Crist├│bal'),(226,6,'San Estanislao'),(227,6,'San Fernando'),(228,6,'San Jacinto'),(229,6,'San Jacinto del Cauca'),(230,6,'San Juan Nepomuceno'),(231,6,'San Mart├¡n de Loba'),(232,6,'San Pablo de Borbur'),(233,6,'Santa Catalina'),(234,6,'Santa Rosa'),(235,6,'Santa Rosa del Sur'),(236,6,'Simit├¡'),(237,6,'Soplaviento'),(238,6,'Talaigua Nuevo'),(239,6,'Tiquisio'),(240,6,'Turban├í'),(241,6,'Villanueva'),(242,6,'Zambrano'),(243,7,'Almeida'),(244,7,'Aquitania'),(245,7,'Arcabuco'),(246,7,'Bel├®n'),(247,7,'Berbeo'),(248,7,'Bet├®itiva'),(249,7,'Boavita'),(250,7,'Boyac├í'),(251,7,'Brice├▒o'),(252,7,'Buena Vista'),(253,7,'Busbanz├í'),(254,7,'Caldas'),(255,7,'Campohermoso'),(256,7,'Cerinza'),(257,7,'Chinavita'),(258,7,'Chiquinquir├í'),(259,7,'Chiscas'),(260,7,'Chita'),(261,7,'Chitaraque'),(262,7,'Chivat├í'),(263,7,'Chivor'),(264,7,'Ch├¡quiza'),(265,7,'Ci├®nega'),(266,7,'Coper'),(267,7,'Corrales'),(268,7,'Covarach├¡a'),(269,7,'Cubar├í'),(270,7,'Cucaita'),(271,7,'Cu├¡tiva'),(272,7,'C├│mbita'),(273,7,'Duitama'),(274,7,'El Cocuy'),(275,7,'El Espino'),(276,7,'Firavitoba'),(277,7,'Floresta'),(278,7,'Gachantiv├í'),(279,7,'Gameza'),(280,7,'Garagoa'),(281,7,'Guacamayas'),(282,7,'Guateque'),(283,7,'Guayat├í'),(284,7,'G├╝ic├ín'),(285,7,'Iza'),(286,7,'Jenesano'),(287,7,'Jeric├│'),(288,7,'La Capilla'),(289,7,'La Uvita'),(290,7,'La Victoria'),(291,7,'Labranzagrande'),(292,7,'Macanal'),(293,7,'Marip├¡'),(294,7,'Miraflores'),(295,7,'Mongua'),(296,7,'Mongu├¡'),(297,7,'Moniquir├í'),(298,7,'Motavita'),(299,7,'Muzo'),(300,7,'Nobsa'),(301,7,'Nuevo Col├│n'),(302,7,'Oicat├í'),(303,7,'Otanche'),(304,7,'Pachavita'),(305,7,'Paipa'),(306,7,'Pajarito'),(307,7,'Panqueba'),(308,7,'Pauna'),(309,7,'Paya'),(310,7,'Paz de R├¡o'),(311,7,'Pesca'),(312,7,'Pisba'),(313,7,'Puerto Boyac├í'),(314,7,'P├íez'),(315,7,'Qu├¡pama'),(316,7,'Ramiriqu├¡'),(317,7,'Rond├│n'),(318,7,'R├íquira'),(319,7,'Saboy├í'),(320,7,'Samac├í'),(321,7,'San Eduardo'),(322,7,'San Jos├® de Pare'),(323,7,'San Luis de Gaceno'),(324,7,'San Mateo'),(325,7,'San Miguel de Sema'),(326,7,'San Pablo de Borbur'),(327,7,'Santa Mar├¡a'),(328,7,'Santa Rosa de Viterbo'),(329,7,'Santa Sof├¡a'),(330,7,'Santana'),(331,7,'Sativanorte'),(332,7,'Sativasur'),(333,7,'Siachoque'),(334,7,'Soat├í'),(335,7,'Socha'),(336,7,'Socot├í'),(337,7,'Sogamoso'),(338,7,'Somondoco'),(339,7,'Sora'),(340,7,'Sorac├í'),(341,7,'Sotaquir├í'),(342,7,'Susac├│n'),(343,7,'Sutamarch├ín'),(344,7,'Sutatenza'),(345,7,'S├íchica'),(346,7,'Tasco'),(347,7,'Tenza'),(348,7,'Tiban├í'),(349,7,'Tibasosa'),(350,7,'Tinjac├í'),(351,7,'Tipacoque'),(352,7,'Toca'),(353,7,'Tog├╝├¡'),(354,7,'Tota'),(355,7,'Tunja'),(356,7,'Tunungu├í'),(357,7,'Turmequ├®'),(358,7,'Tuta'),(359,7,'Tutaz├í'),(360,7,'T├│paga'),(361,7,'Umbita'),(362,7,'Ventaquemada'),(363,7,'Villa de Leyva'),(364,7,'Viracach├í'),(365,7,'Zetaquira'),(366,8,'Aguadas'),(367,8,'Anserma'),(368,8,'Aranzazu'),(369,8,'Belalc├ízar'),(370,8,'Chinchin├í'),(371,8,'Filadelfia'),(372,8,'La Merced'),(373,8,'Manzanares'),(374,8,'Marmato'),(375,8,'Marquetalia'),(376,8,'Marulanda'),(377,8,'Neira'),(378,8,'Norcasia'),(379,8,'Palestina'),(380,8,'Pensilvania'),(381,8,'P├ícora'),(382,8,'Riosucio'),(383,8,'Risaralda'),(384,8,'Salamina'),(385,8,'Saman├í'),(386,8,'San Jos├®'),(387,8,'Sup├¡a'),(388,8,'Victoria'),(389,8,'Viterbo'),(390,9,'Albania'),(391,9,'Bel├®n de Los Andaquies'),(392,9,'Cartagena del Chair├í'),(393,9,'Curillo'),(394,9,'El Doncello'),(395,9,'El Paujil'),(396,9,'Florencia'),(397,9,'La Monta├▒ita'),(398,9,'Mil├ín'),(399,9,'Morelia'),(400,9,'Puerto Rico'),(401,9,'San Jos├® del Fragua'),(402,9,'San Vicente del Cagu├ín'),(403,9,'Solano'),(404,9,'Solita'),(405,9,'Valpara├¡so'),(406,10,'Aguazul'),(407,10,'Ch├ímeza'),(408,10,'Hato Corozal'),(409,10,'La Salina'),(410,10,'Man├¡'),(411,10,'Monterrey'),(412,10,'Nunch├¡a'),(413,10,'Orocu├®'),(414,10,'Paz de Ariporo'),(415,10,'Pore'),(416,10,'Recetor'),(417,10,'Sabanalarga'),(418,10,'San Luis de Gaceno'),(419,10,'S├ícama'),(420,10,'Tauramena'),(421,10,'Trinidad'),(422,10,'T├ímara'),(423,10,'Villanueva'),(424,10,'Yopal'),(425,11,'Almaguer'),(426,11,'Argelia'),(427,11,'Balboa'),(428,11,'Bol├¡var'),(429,11,'Buenos Aires'),(430,11,'Cajib├¡o'),(431,11,'Caldono'),(432,11,'Caloto'),(433,11,'Corinto'),(434,11,'El Tambo'),(435,11,'Florencia'),(436,11,'Guachen├®'),(437,11,'Guapi'),(438,11,'Inz├í'),(439,11,'Jambal├│'),(440,11,'La Sierra'),(441,11,'La Vega'),(442,11,'L├│pez'),(443,11,'Mercaderes'),(444,11,'Miranda'),(445,11,'Morales'),(446,11,'Padilla'),(447,11,'Pat├¡a'),(448,11,'Piamonte'),(449,11,'Piendam├│'),(450,11,'Popay├ín'),(451,11,'Puerto Tejada'),(452,11,'Purac├®'),(453,11,'P├íez'),(454,11,'Rosas'),(455,11,'San Sebasti├ín'),(456,11,'Santa Rosa'),(457,11,'Santander de Quilichao'),(458,11,'Silvia'),(459,11,'Sotara'),(460,11,'Sucre'),(461,11,'Su├írez'),(462,11,'Timbiqu├¡'),(463,11,'Timb├¡o'),(464,11,'Toribio'),(465,11,'Totor├│'),(466,11,'Villa Rica'),(467,21,'Barranca de Up├¡a'),(468,21,'Cabuyaro'),(469,21,'Castilla la Nueva'),(470,21,'Cubarral'),(471,21,'Cumaral'),(472,21,'El Calvario'),(473,21,'El Castillo'),(474,21,'El Dorado'),(475,21,'Fuente de Oro'),(476,21,'Granada'),(477,21,'Guamal'),(478,21,'La Macarena'),(479,21,'Lejan├¡as'),(480,21,'Mapirip├ín'),(481,21,'Mesetas'),(482,21,'Puerto Concordia'),(483,21,'Puerto Gait├ín'),(484,21,'Puerto Lleras'),(485,21,'Puerto L├│pez'),(486,21,'Puerto Rico'),(487,21,'Restrepo'),(488,21,'San Carlos de Guaroa'),(489,21,'San Juan de Arama'),(490,21,'San Juanito'),(491,21,'San Mart├¡n'),(492,21,'Uribe'),(493,21,'Vista Hermosa'),(494,12,'Aguachica'),(495,12,'Agust├¡n Codazzi'),(496,12,'Astrea'),(497,12,'Becerril'),(498,12,'Bosconia'),(499,12,'Chimichagua'),(500,12,'Chiriguan├í'),(501,12,'Curuman├¡'),(502,12,'El Copey'),(503,12,'El Paso'),(504,12,'Gamarra'),(505,12,'Gonz├ílez'),(506,12,'La Gloria'),(507,12,'La Jagua de Ibirico'),(508,12,'La Paz'),(509,12,'Manaure'),(510,12,'Pailitas'),(511,12,'Pelaya'),(512,12,'Pueblo Bello'),(513,12,'R├¡o de Oro'),(514,12,'San Alberto'),(515,12,'San Diego'),(516,12,'San Mart├¡n'),(517,12,'Tamalameque'),(518,12,'Valledupar'),(519,13,'Acand├¡'),(520,13,'Alto Baudo'),(521,13,'Atrato'),(522,13,'Bagad├│'),(523,13,'Bah├¡a Solano'),(524,13,'Bajo Baud├│'),(525,13,'Bel├®n de Bajira'),(526,13,'Bojaya'),(527,13,'Carmen del Darien'),(528,13,'Condoto'),(529,13,'C├®rtegui'),(530,13,'El Cant├│n del San Pablo'),(531,13,'El Carmen de Atrato'),(532,13,'El Litoral del San Juan'),(533,13,'Istmina'),(534,13,'Jurad├│'),(535,13,'Llor├│'),(536,13,'Medio Atrato'),(537,13,'Medio Baud├│'),(538,13,'Medio San Juan'),(539,13,'Nuqu├¡'),(540,13,'N├│vita'),(541,13,'Quibd├│'),(542,13,'Riosucio'),(543,13,'R├¡o Iro'),(544,13,'R├¡o Quito'),(545,13,'San Jos├® del Palmar'),(546,13,'Sip├¡'),(547,13,'Tad├│'),(548,13,'Ungu├¡a'),(549,13,'Uni├│n Panamericana'),(550,14,'Ayapel'),(551,14,'Buenavista'),(552,14,'Canalete'),(553,14,'Ceret├®'),(554,14,'Chim├í'),(555,14,'Chin├║'),(556,14,'Ci├®naga de Oro'),(557,14,'Cotorra'),(558,14,'La Apartada'),(559,14,'Lorica'),(560,14,'Los C├│rdobas'),(561,14,'Momil'),(562,14,'Montel├¡bano'),(563,14,'Monter├¡a'),(564,14,'Mo├▒itos'),(565,14,'Planeta Rica'),(566,14,'Pueblo Nuevo'),(567,14,'Puerto Escondido'),(568,14,'Puerto Libertador'),(569,14,'Pur├¡sima'),(570,14,'Sahag├║n'),(571,14,'San Andr├®s Sotavento'),(572,14,'San Antero'),(573,14,'San Bernardo del Viento'),(574,14,'San Carlos'),(575,14,'San Jos├® de Ur├®'),(576,14,'San Pelayo'),(577,14,'Tierralta'),(578,14,'Tuch├¡n'),(579,14,'Valencia'),(580,15,'Agua de Dios'),(581,15,'Alb├ín'),(582,15,'Anapoima'),(583,15,'Anolaima'),(584,15,'Apulo'),(585,15,'Arbel├íez'),(586,15,'Beltr├ín'),(587,15,'Bituima'),(588,15,'Bojac├í'),(589,15,'Cabrera'),(590,15,'Cachipay'),(591,15,'Cajic├í'),(592,15,'Caparrap├¡'),(593,15,'Caqueza'),(594,15,'Carmen de Carupa'),(595,15,'Chaguan├¡'),(596,15,'Chipaque'),(597,15,'Choach├¡'),(598,15,'Chocont├í'),(599,15,'Cogua'),(600,15,'Cota'),(601,15,'Cucunub├í'),(602,15,'El Colegio'),(603,15,'El Pe├▒├│n'),(604,15,'El Rosal'),(605,15,'Fomeque'),(606,15,'Fosca'),(607,15,'Funza'),(608,15,'Fusagasug├í'),(609,15,'F├║quene'),(610,15,'Gachala'),(611,15,'Gachancip├í'),(612,15,'Gachet├í'),(613,15,'Gama'),(614,15,'Granada'),(615,15,'Guachet├í'),(616,15,'Guaduas'),(617,15,'Guasca'),(618,15,'Guataqu├¡'),(619,15,'Guatavita'),(620,15,'Guayabal de Siquima'),(621,15,'Guayabetal'),(622,15,'Guti├®rrez'),(623,15,'Jerusal├®n'),(624,15,'Jun├¡n'),(625,15,'La Calera'),(626,15,'La Mesa'),(627,15,'La Palma'),(628,15,'La Pe├▒a'),(629,15,'La Vega'),(630,15,'Lenguazaque'),(631,15,'Macheta'),(632,15,'Madrid'),(633,15,'Manta'),(634,15,'Medina'),(635,15,'Mosquera'),(636,15,'Nari├▒o'),(637,15,'Nemoc├│n'),(638,15,'Nilo'),(639,15,'Nimaima'),(640,15,'Nocaima'),(641,15,'Pacho'),(642,15,'Paime'),(643,15,'Pandi'),(644,15,'Paratebueno'),(645,15,'Pasca'),(646,15,'Puerto Salgar'),(647,15,'Pul├¡'),(648,15,'Quebradanegra'),(649,15,'Quetame'),(650,15,'Quipile'),(651,15,'Ricaurte'),(652,15,'San Antonio del Tequendama'),(653,15,'San Bernardo'),(654,15,'San Cayetano'),(655,15,'San Francisco'),(656,15,'San Juan de R├¡o Seco'),(657,15,'Sasaima'),(658,15,'Sesquil├®'),(659,15,'Sibat├®'),(660,15,'Silvania'),(661,15,'Simijaca'),(662,15,'Sop├│'),(663,15,'Subachoque'),(664,15,'Suesca'),(665,15,'Supat├í'),(666,15,'Susa'),(667,15,'Sutatausa'),(668,15,'Tabio'),(669,15,'Tausa'),(670,15,'Tena'),(671,15,'Tenjo'),(672,15,'Tibacuy'),(673,15,'Tibirita'),(674,15,'Tocaima'),(675,15,'Tocancip├í'),(676,15,'Topaip├¡'),(677,15,'Ubal├í'),(678,15,'Ubaque'),(679,15,'Une'),(680,15,'Venecia'),(681,15,'Vergara'),(682,15,'Vian├¡'),(683,15,'Villa de San Diego de Ubate'),(684,15,'Villag├│mez'),(685,15,'Villapinz├│n'),(686,15,'Villeta'),(687,15,'Viot├í'),(688,15,'Yacop├¡'),(689,15,'Zipac├│n'),(690,15,'├Ütica'),(691,16,'Barranco Minas'),(692,16,'Cacahual'),(693,16,'In├¡rida'),(694,16,'La Guadalupe'),(695,16,'Mapiripana'),(696,16,'Morichal'),(697,16,'Pana Pana'),(698,16,'Puerto Colombia'),(699,16,'San Felipe'),(700,17,'Calamar'),(701,17,'El Retorno'),(702,17,'Miraflores'),(703,17,'San Jos├® del Guaviare'),(704,18,'Acevedo'),(705,18,'Agrado'),(706,18,'Aipe'),(707,18,'Algeciras'),(708,18,'Altamira'),(709,18,'Baraya'),(710,18,'Campoalegre'),(711,18,'Colombia'),(712,18,'El├¡as'),(713,18,'Garz├│n'),(714,18,'Gigante'),(715,18,'Guadalupe'),(716,18,'Hobo'),(717,18,'Iquira'),(718,18,'Isnos'),(719,18,'La Argentina'),(720,18,'La Plata'),(721,18,'N├ítaga'),(722,18,'Oporapa'),(723,18,'Paicol'),(724,18,'Palermo'),(725,18,'Palestina'),(726,18,'Pital'),(727,18,'Rivera'),(728,18,'Saladoblanco'),(729,18,'San Agust├¡n'),(730,18,'Santa Mar├¡a'),(731,18,'Suaza'),(732,18,'Tarqui'),(733,18,'Tello'),(734,18,'Teruel'),(735,18,'Tesalia'),(736,18,'Timan├í'),(737,18,'Villavieja'),(738,18,'Yaguar├í'),(739,19,'Albania'),(740,19,'Barrancas'),(741,19,'Dibula'),(742,19,'Distracci├│n'),(743,19,'El Molino'),(744,19,'Fonseca'),(745,19,'Hatonuevo'),(746,19,'La Jagua del Pilar'),(747,19,'Maicao'),(748,19,'Manaure'),(749,19,'Riohacha'),(750,19,'San Juan del Cesar'),(751,19,'Uribia'),(752,19,'Urumita'),(753,19,'Villanueva'),(754,20,'Algarrobo'),(755,20,'Aracataca'),(756,20,'Ariguan├¡'),(757,20,'Cerro San Antonio'),(758,20,'Chivolo'),(759,20,'Concordia'),(760,20,'El Banco'),(761,20,'El Pi├▒on'),(762,20,'El Ret├®n'),(763,20,'Fundaci├│n'),(764,20,'Guamal'),(765,20,'Nueva Granada'),(766,20,'Pedraza'),(767,20,'Piji├▒o del Carmen'),(768,20,'Pivijay'),(769,20,'Plato'),(770,20,'Pueblo Viejo'),(771,20,'Remolino'),(772,20,'Sabanas de San Angel'),(773,20,'Salamina'),(774,20,'San Sebasti├ín de Buenavista'),(775,20,'San Zen├│n'),(776,20,'Santa Ana'),(777,20,'Santa B├írbara de Pinto'),(778,20,'Sitionuevo'),(779,20,'Tenerife'),(780,20,'Zapay├ín'),(781,20,'Zona Bananera'),(782,1,'El Encanto'),(783,1,'La Chorrera'),(784,1,'La Pedrera'),(785,1,'La Victoria'),(786,1,'Leticia'),(787,1,'Miriti Paran├í'),(788,1,'Puerto Alegr├¡a'),(789,1,'Puerto Arica'),(790,1,'Puerto Nari├▒o'),(791,1,'Puerto Santander'),(792,1,'Tarapac├í'),(793,22,'Alb├ín'),(794,22,'Aldana'),(795,22,'Ancuy├í'),(796,22,'Arboleda'),(797,22,'Barbacoas'),(798,22,'Bel├®n'),(799,22,'Buesaco'),(800,22,'Chachag├╝├¡'),(801,22,'Col├│n'),(802,22,'Consaca'),(803,22,'Contadero'),(804,22,'Cuaspud'),(805,22,'Cumbal'),(806,22,'Cumbitara'),(807,22,'C├│rdoba'),(808,22,'El Charco'),(809,22,'El Pe├▒ol'),(810,22,'El Rosario'),(811,22,'El Tabl├│n de G├│mez'),(812,22,'El Tambo'),(813,22,'Francisco Pizarro'),(814,22,'Funes'),(815,22,'Guachucal'),(816,22,'Guaitarilla'),(817,22,'Gualmat├ín'),(818,22,'Iles'),(819,22,'Imu├®s'),(820,22,'La Cruz'),(821,22,'La Florida'),(822,22,'La Llanada'),(823,22,'La Tola'),(824,22,'La Uni├│n'),(825,22,'Leiva'),(826,22,'Linares'),(827,22,'Los Andes'),(828,22,'Mag├╝├¡'),(829,22,'Mallama'),(830,22,'Mosquera'),(831,22,'Nari├▒o'),(832,22,'Olaya Herrera'),(833,22,'Ospina'),(834,22,'Policarpa'),(835,22,'Potos├¡'),(836,22,'Providencia'),(837,22,'Puerres'),(838,22,'Pupiales'),(839,22,'Ricaurte'),(840,22,'Roberto Pay├ín'),(841,22,'Samaniego'),(842,22,'San Andr├®s de Tumaco'),(843,22,'San Bernardo'),(844,22,'San Lorenzo'),(845,22,'San Pablo'),(846,22,'San Pedro de Cartago'),(847,22,'Sandon├í'),(848,22,'Santa B├írbara'),(849,22,'Santacruz'),(850,22,'Sapuyes'),(851,22,'Taminango'),(852,22,'Tangua'),(853,22,'T├║querres'),(854,22,'Yacuanquer'),(855,23,'Abrego'),(856,23,'Arboledas'),(857,23,'Bochalema'),(858,23,'Bucarasica'),(859,23,'Cachir├í'),(860,23,'Chin├ícota'),(861,23,'Chitag├í'),(862,23,'Convenci├│n'),(863,23,'Cucutilla'),(864,23,'C├ícota'),(865,23,'Durania'),(866,23,'El Carmen'),(867,23,'El Tarra'),(868,23,'El Zulia'),(869,23,'Gramalote'),(870,23,'Hacar├¡'),(871,23,'Herr├ín'),(872,23,'La Esperanza'),(873,23,'La Playa'),(874,23,'Labateca'),(875,23,'Los Patios'),(876,23,'Lourdes'),(877,23,'Mutiscua'),(878,23,'Pamplona'),(879,23,'Pamplonita'),(880,23,'Puerto Santander'),(881,23,'Ragonvalia'),(882,23,'Salazar'),(883,23,'San Calixto'),(884,23,'San Cayetano'),(885,23,'Santiago'),(886,23,'Sardinata'),(887,23,'Silos'),(888,23,'Teorama'),(889,23,'Tib├║'),(890,23,'Toledo'),(891,23,'Villa Caro'),(892,24,'Col├│n'),(893,24,'Legu├¡zamo'),(894,24,'Mocoa'),(895,24,'Orito'),(896,24,'Puerto As├¡s'),(897,24,'Puerto Caicedo'),(898,24,'Puerto Guzm├ín'),(899,24,'San Francisco'),(900,24,'San Miguel'),(901,24,'Santiago'),(902,24,'Sibundoy'),(903,24,'Valle de Guamez'),(904,24,'Villagarz├│n'),(905,25,'Buenavista'),(906,25,'Circasia'),(907,25,'C├│rdoba'),(908,25,'Filandia'),(909,25,'G├®nova'),(910,25,'La Tebaida'),(911,25,'Montenegro'),(912,25,'Pijao'),(913,25,'Quimbaya'),(914,25,'Salento'),(915,26,'Ap├¡a'),(916,26,'Balboa'),(917,26,'Bel├®n de Umbr├¡a'),(918,26,'Gu├ítica'),(919,26,'La Celia'),(920,26,'La Virginia'),(921,26,'Marsella'),(922,26,'Mistrat├│'),(923,26,'Pueblo Rico'),(924,26,'Quinch├¡a'),(925,26,'Santa Rosa de Cabal'),(926,26,'Santuario'),(927,27,'Providencia'),(928,27,'San Andr├®s'),(929,28,'Aguada'),(930,28,'Albania'),(931,28,'Aratoca'),(932,28,'Barbosa'),(933,28,'Barichara'),(934,28,'Betulia'),(935,28,'Bol├¡var'),(936,28,'Cabrera'),(937,28,'California'),(938,28,'Capitanejo'),(939,28,'Carcas├¡'),(940,28,'Cepit├í'),(941,28,'Cerrito'),(942,28,'Charal├í'),(943,28,'Charta'),(944,28,'Chim├í'),(945,28,'Chipat├í'),(946,28,'Cimitarra'),(947,28,'Concepci├│n'),(948,28,'Confines'),(949,28,'Contrataci├│n'),(950,28,'Coromoro'),(951,28,'Curit├¡'),(952,28,'El Carmen de Chucur├¡'),(953,28,'El Guacamayo'),(954,28,'El Pe├▒├│n'),(955,28,'El Play├│n'),(956,28,'Encino'),(957,28,'Enciso'),(958,28,'Flori├ín'),(959,28,'Gal├ín'),(960,28,'Gambita'),(961,28,'Guaca'),(962,28,'Guadalupe'),(963,28,'Guapot├í'),(964,28,'Guavat├í'),(965,28,'G├╝epsa'),(966,28,'Hato'),(967,28,'Jes├║s Mar├¡a'),(968,28,'Jord├ín'),(969,28,'La Belleza'),(970,28,'La Paz'),(971,28,'Land├ízuri'),(972,28,'Lebr├¡ja'),(973,28,'Los Santos'),(974,28,'Macaravita'),(975,28,'Matanza'),(976,28,'Mogotes'),(977,28,'Molagavita'),(978,28,'M├ílaga'),(979,28,'Ocamonte'),(980,28,'Oiba'),(981,28,'Onzaga'),(982,28,'Palmar'),(983,28,'Palmas del Socorro'),(984,28,'Pinchote'),(985,28,'Puente Nacional'),(986,28,'Puerto Parra'),(987,28,'Puerto Wilches'),(988,28,'P├íramo'),(989,28,'Rionegro'),(990,28,'Sabana de Torres'),(991,28,'San Andr├®s'),(992,28,'San Benito'),(993,28,'San Gil'),(994,28,'San Joaqu├¡n'),(995,28,'San Jos├® de Miranda'),(996,28,'San Miguel'),(997,28,'San Vicente de Chucur├¡'),(998,28,'Santa B├írbara'),(999,28,'Santa Helena del Op├│n'),(1000,28,'Simacota'),(1001,28,'Socorro'),(1002,28,'Suaita'),(1003,28,'Sucre'),(1004,28,'Surat├í'),(1005,28,'Tona'),(1006,28,'Valle de San Jos├®'),(1007,28,'Vetas'),(1008,28,'Villanueva'),(1009,28,'V├®lez'),(1010,28,'Zapatoca'),(1011,29,'Buenavista'),(1012,29,'Caimito'),(1013,29,'Chal├ín'),(1014,29,'Coloso'),(1015,29,'Corozal'),(1016,29,'Cove├▒as'),(1017,29,'El Roble'),(1018,29,'Galeras'),(1019,29,'Guaranda'),(1020,29,'La Uni├│n'),(1021,29,'Los Palmitos'),(1022,29,'Majagual'),(1023,29,'Morroa'),(1024,29,'Ovejas'),(1025,29,'Palmito'),(1026,29,'Sampu├®s'),(1027,29,'San Benito Abad'),(1028,29,'San Juan de Betulia'),(1029,29,'San Luis de Sinc├®'),(1030,29,'San Marcos'),(1031,29,'San Onofre'),(1032,29,'San Pedro'),(1033,29,'Santiago de Tol├║'),(1034,29,'Sincelejo'),(1035,29,'Sucre'),(1036,29,'Tol├║ Viejo'),(1037,30,'Alpujarra'),(1038,30,'Alvarado'),(1039,30,'Ambalema'),(1040,30,'Anzo├ítegui'),(1041,30,'Armero'),(1042,30,'Ataco'),(1043,30,'Cajamarca'),(1044,30,'Carmen de Apicala'),(1045,30,'Casabianca'),(1046,30,'Chaparral'),(1047,30,'Coello'),(1048,30,'Coyaima'),(1049,30,'Cunday'),(1050,30,'Dolores'),(1051,30,'Falan'),(1052,30,'Flandes'),(1053,30,'Fresno'),(1054,30,'Guamo'),(1055,30,'Herveo'),(1056,30,'Honda'),(1057,30,'Icononzo'),(1058,30,'L├®rida'),(1059,30,'L├¡bano'),(1060,30,'Mariquita'),(1061,30,'Melgar'),(1062,30,'Murillo'),(1063,30,'Natagaima'),(1064,30,'Ortega'),(1065,30,'Palocabildo'),(1066,30,'Piedras'),(1067,30,'Planadas'),(1068,30,'Prado'),(1069,30,'Purificaci├│n'),(1070,30,'Rio Blanco'),(1071,30,'Roncesvalles'),(1072,30,'Rovira'),(1073,30,'Salda├▒a'),(1074,30,'San Antonio'),(1075,30,'San Luis'),(1076,30,'Santa Isabel'),(1077,30,'Su├írez'),(1078,30,'Valle de San Juan'),(1079,30,'Venadillo'),(1080,30,'Villahermosa'),(1081,30,'Villarrica'),(1082,31,'Alcal├í'),(1083,31,'Andaluc├¡a'),(1084,31,'Ansermanuevo'),(1085,31,'Argelia'),(1086,31,'Bol├¡var'),(1087,31,'Bugalagrande'),(1088,31,'Caicedonia'),(1089,31,'Calima'),(1090,31,'Candelaria'),(1091,31,'Dagua'),(1092,31,'El Cairo'),(1093,31,'El Cerrito'),(1094,31,'El Dovio'),(1095,31,'El ├üguila'),(1096,31,'Florida'),(1097,31,'Ginebra'),(1098,31,'Guacar├¡'),(1099,31,'Guadalajara de Buga'),(1100,31,'La Cumbre'),(1101,31,'La Uni├│n'),(1102,31,'La Victoria'),(1103,31,'Obando'),(1104,31,'Pradera'),(1105,31,'Restrepo'),(1106,31,'Riofr├¡o'),(1107,31,'Roldanillo'),(1108,31,'San Pedro'),(1109,31,'Sevilla'),(1110,31,'Toro'),(1111,31,'Trujillo'),(1112,31,'Ulloa'),(1113,31,'Versalles'),(1114,31,'Vijes'),(1115,31,'Yotoco'),(1116,31,'Zarzal'),(1117,32,'Carur├║'),(1118,32,'Mit├║'),(1119,32,'Pacoa'),(1120,32,'Papunahua'),(1121,32,'Taraira'),(1122,32,'Yavarat├®'),(1123,33,'Cumaribo'),(1124,33,'La Primavera'),(1125,33,'Puerto Carre├▒o'),(1126,33,'Santa Rosal├¡a');
/*!40000 ALTER TABLE `loc_cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loc_countries`
--

DROP TABLE IF EXISTS `loc_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loc_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loc_countries_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loc_countries`
--

LOCK TABLES `loc_countries` WRITE;
/*!40000 ALTER TABLE `loc_countries` DISABLE KEYS */;
INSERT INTO `loc_countries` VALUES (1,'Colombia','CO');
/*!40000 ALTER TABLE `loc_countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loc_states`
--

DROP TABLE IF EXISTS `loc_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loc_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_loc_states_country_id` (`country_id`),
  KEY `idx_loc_states_name` (`name`),
  CONSTRAINT `loc_states_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `loc_countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loc_states`
--

LOCK TABLES `loc_states` WRITE;
/*!40000 ALTER TABLE `loc_states` DISABLE KEYS */;
INSERT INTO `loc_states` VALUES (1,1,'Amazonas'),(2,1,'Antioquia'),(3,1,'Arauca'),(4,1,'Atl├íntico'),(5,1,'Bogot├í D.C.'),(6,1,'Bol├¡var'),(7,1,'Boyac├í'),(8,1,'Caldas'),(9,1,'Caquet├í'),(10,1,'Casanare'),(11,1,'Cauca'),(12,1,'Cesar'),(13,1,'Choc├│'),(14,1,'C├│rdoba'),(15,1,'Cundinamarca'),(16,1,'Guain├¡a'),(17,1,'Guaviare'),(18,1,'Huila'),(19,1,'La Guajira'),(20,1,'Magdalena'),(21,1,'Meta'),(22,1,'Nari├▒o'),(23,1,'Norte de Santander'),(24,1,'Putumayo'),(25,1,'Quind├¡o'),(26,1,'Risaralda'),(27,1,'San Andr├®s y Providencia'),(28,1,'Santander'),(29,1,'Sucre'),(30,1,'Tolima'),(31,1,'Valle del Cauca'),(32,1,'Vaup├®s'),(33,1,'Vichada'),(34,1,'Bogot├í');
/*!40000 ALTER TABLE `loc_states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `country_id` int(11) DEFAULT NULL,
  `state_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `state_id` (`state_id`),
  KEY `city_id` (`city_id`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `loc_countries` (`id`),
  CONSTRAINT `locations_ibfk_2` FOREIGN KEY (`state_id`) REFERENCES `loc_states` (`id`),
  CONSTRAINT `locations_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `loc_cities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'Bogot├í',1,5,11,1),(2,'Buenaventura',1,31,47,1),(3,'Cartagena',1,6,12,1),(4,'Galapa',1,4,183,1),(5,'Barranquilla',1,4,7,1),(6,'Piedecuesta',1,28,43,1),(7,'Guacheta',1,15,615,1),(8,'Medellin',1,2,1,1),(9,'Cucunuba',1,15,601,1),(10,'Tocancipa',1,15,675,1),(11,'Bucaramanga',1,28,39,1),(12,'Funza',1,15,607,1),(13,'Samac├í',1,7,320,1),(14,'Lenguezaque',1,15,630,1),(15,'Subachoque',1,15,663,1),(16,'Sop├│',1,15,662,1),(17,'Cota',1,15,600,1),(18,'Sabanalarga',1,4,10,1);
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `performed_date` date NOT NULL,
  `performed_at_kms` decimal(15,2) NOT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `technician` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_logs`
--

LOCK TABLES `maintenance_logs` WRITE;
/*!40000 ALTER TABLE `maintenance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `interval_kms` int(11) NOT NULL,
  `interval_days` int(11) DEFAULT NULL,
  `last_service_kms` decimal(15,2) DEFAULT 0.00,
  `last_service_date` date DEFAULT NULL,
  `next_service_kms` decimal(15,2) DEFAULT 0.00,
  `next_service_date` date DEFAULT NULL,
  `warning_margin_kms` int(11) DEFAULT 500,
  `warning_margin_days` int(11) DEFAULT 7,
  `priority` enum('Baja','Media','Alta','Cr├¡tica') DEFAULT 'Media',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maint_vehicle` (`vehicle_id`),
  KEY `idx_maint_next_kms` (`next_service_kms`),
  KEY `idx_maint_next_date` (`next_service_date`),
  CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules`
--

LOCK TABLES `maintenance_schedules` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules` DISABLE KEYS */;
INSERT INTO `maintenance_schedules` VALUES (1,1,'Cambio de Aceite y Filtros',40000,NULL,509755.00,NULL,549755.00,NULL,500,7,'Media',1,'2026-01-13 21:06:28','2026-01-13 21:06:28');
/*!40000 ALTER TABLE `maintenance_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manifest_companies`
--

DROP TABLE IF EXISTS `manifest_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manifest_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manifest_companies`
--

LOCK TABLES `manifest_companies` WRITE;
/*!40000 ALTER TABLE `manifest_companies` DISABLE KEYS */;
INSERT INTO `manifest_companies` VALUES (1,'PRETECOR',1),(2,'CARGAMOS',1),(3,'COLTANQUES',1),(4,'COOTRANSVALLE',1),(5,'SOLISTICA',1),(6,'TRANSOLICAR',1),(7,'TTC',1);
/*!40000 ALTER TABLE `manifest_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manifiestos_rndc`
--

DROP TABLE IF EXISTS `manifiestos_rndc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manifiestos_rndc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `nro_manifiesto` varchar(50) NOT NULL,
  `autorizacion_rndc` varchar(50) DEFAULT NULL,
  `nro_remesa` varchar(50) DEFAULT NULL,
  `fecha_expedicion` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `empresa_transporte` varchar(255) NOT NULL,
  `nit_empresa` varchar(50) DEFAULT NULL,
  `remitente_nombre` varchar(255) NOT NULL,
  `remitente_nit` varchar(50) DEFAULT NULL,
  `remitente_codigo` varchar(50) DEFAULT NULL,
  `remitente_direccion` varchar(255) DEFAULT NULL,
  `remitente_ciudad` varchar(100) DEFAULT NULL,
  `destinatario_nombre` varchar(255) NOT NULL,
  `destinatario_nit` varchar(50) DEFAULT NULL,
  `destinatario_codigo` varchar(50) DEFAULT NULL,
  `destinatario_direccion` varchar(255) DEFAULT NULL,
  `destinatario_ciudad` varchar(100) DEFAULT NULL,
  `origen` varchar(255) NOT NULL,
  `destino` varchar(255) NOT NULL,
  `descripcion_mercancia` text DEFAULT NULL,
  `peso_kg` decimal(10,2) DEFAULT NULL,
  `unidades` int(11) DEFAULT NULL,
  `tipo_vehiculo` varchar(100) DEFAULT NULL,
  `flete_pactado` decimal(15,2) DEFAULT 0.00,
  `anticipo` decimal(15,2) DEFAULT 0.00,
  `saldo` decimal(15,2) DEFAULT 0.00,
  `cargue_pagado_por` enum('Remitente','Destinatario','Propietario') DEFAULT NULL,
  `descargue_pagado_por` enum('Remitente','Destinatario','Propietario') DEFAULT NULL,
  `lugar_pago` varchar(100) DEFAULT NULL,
  `fecha_pago_saldo` date DEFAULT NULL,
  `estado` enum('Borrador','Activo','Finalizado','Anulado') DEFAULT 'Activo',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `manifiestos_rndc_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE SET NULL,
  CONSTRAINT `manifiestos_rndc_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `manifiestos_rndc_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `personnel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manifiestos_rndc`
--

LOCK TABLES `manifiestos_rndc` WRITE;
/*!40000 ALTER TABLE `manifiestos_rndc` DISABLE KEYS */;
/*!40000 ALTER TABLE `manifiestos_rndc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,'CARBON',1),(2,'MAIZ',1),(3,'COBRE',0),(4,'COBRE',0),(5,'COBRE',1),(6,'CERAMICA',1),(7,'ABONO',1),(8,'POSTES',1),(9,'CAFE',1);
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations_log`
--

DROP TABLE IF EXISTS `migrations_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations_log`
--

LOCK TABLES `migrations_log` WRITE;
/*!40000 ALTER TABLE `migrations_log` DISABLE KEYS */;
INSERT INTO `migrations_log` VALUES (1,'001_update_schema_settlements.php','2026-01-31 15:40:30'),(2,'002_update_schema_settlements_v2.php','2026-01-31 15:40:30'),(3,'003_update_schema_settlements_v3.php','2026-01-31 15:40:30'),(4,'004_update_schema_settlements_v4.php','2026-01-31 15:40:30'),(5,'005_update_schema_alerts.php','2026-01-31 15:42:52'),(6,'006_update_schema_ownership.php','2026-01-31 15:42:52'),(7,'007_update_schema_maintenance.php','2026-01-31 15:42:52'),(8,'008_update_schema_epod.php','2026-01-31 22:36:36'),(9,'009_update_schema_maintenance_dates.php','2026-01-31 22:36:36'),(10,'010_update_schema_users_access.php','2026-01-31 22:36:36'),(11,'011_update_schema_satrack_integration.php','2026-01-31 22:36:36'),(12,'012_update_schema_satrack_config.php','2026-01-31 22:36:36'),(13,'013_update_schema_advance_flag.php','2026-01-31 22:36:36'),(14,'014_update_schema_audit_v2.php','2026-01-31 22:36:36'),(15,'015_update_schema_clients.php','2026-01-31 22:36:36'),(16,'016_update_schema_comprehensive_config.php','2026-01-31 22:36:36'),(17,'017_update_schema_expense_paid_by.php','2026-01-31 23:09:15'),(18,'018_update_schema_expenses_supplier.php','2026-01-31 23:09:15'),(19,'019_update_schema_extra_fields.php','2026-01-31 23:09:15'),(20,'020_update_schema_improvements.php','2026-01-31 23:09:15'),(21,'021_update_schema_indexes.php','2026-01-31 23:09:15'),(22,'022_update_schema_location_indexes.php','2026-01-31 23:09:15'),(23,'023_update_schema_location_tables.php','2026-01-31 23:09:15'),(24,'024_update_schema_locations_country.php','2026-01-31 23:09:15'),(25,'025_update_schema_logo.php','2026-01-31 23:09:15'),(26,'026_update_schema_money_module.php','2026-01-31 23:09:15'),(27,'027_update_schema_optimization_v2.php','2026-01-31 23:09:15'),(28,'028_update_schema_pay_received.php','2026-01-31 23:09:15'),(29,'029_update_schema_payments_concept.php','2026-01-31 23:09:15'),(30,'030_update_schema_personnel.php','2026-01-31 23:09:15'),(31,'031_update_schema_salary_categories.php','2026-01-31 23:09:15'),(32,'032_update_schema_system_health.php','2026-01-31 23:09:15'),(33,'033_update_schema_trip_payments.php','2026-01-31 23:09:15'),(34,'034_update_schema_trips_iva.php','2026-01-31 23:09:15'),(35,'035_update_schema_trips_status.php','2026-01-31 23:09:15'),(36,'036_update_schema_users_v2.php','2026-01-31 23:09:15'),(37,'037_update_audit_schema.php','2026-01-31 23:09:15'),(38,'038_create_table_suppliers.php','2026-01-31 23:09:15'),(39,'039_update_schema_system_alerts.php','2026-01-31 23:09:15'),(40,'040_update_schema_digital_trip_sheet.php','2026-01-31 23:09:15'),(41,'041_update_schema_fuel_performance.php','2026-02-01 03:25:44'),(42,'044_update_schema_expenses_location','2026-02-01 23:50:34'),(43,'042_update_schema_hierarchical_locations.php','2026-02-02 00:21:48'),(44,'043_create_table_fuel_vouchers.php','2026-02-02 00:21:48'),(45,'044_update_schema_expenses_location.php','2026-05-08 03:09:42'),(46,'045_update_schema_vehicle_insurance.php','2026-05-08 03:09:42'),(47,'055_create_table_manifiestos_rndc.php','2026-06-18 09:09:03'),(48,'056_create_table_socios.php','2026-06-18 09:09:03'),(49,'057_update_schema_indexes_and_auth_config.php','2026-06-18 13:17:13');
/*!40000 ALTER TABLE `migrations_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnel`
--

DROP TABLE IF EXISTS `personnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personnel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `document_number` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Colombia',
  `city_residence` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `type` enum('Administrativo','Conductor','Socio / Propietario') DEFAULT 'Conductor',
  `document_type` enum('CC','CE','NIT','Pasaporte') DEFAULT 'CC',
  `city` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `gender` enum('Masculino','Femenino') DEFAULT 'Masculino',
  `license_category` varchar(10) DEFAULT NULL,
  `date_entry` date DEFAULT NULL,
  `date_exit` date DEFAULT NULL,
  `salary_basic` decimal(15,2) DEFAULT 0.00,
  `salary_variable` decimal(15,2) DEFAULT 0.00,
  `salary_internal` decimal(15,2) DEFAULT 0.00,
  `transport_assistance` decimal(15,2) DEFAULT 0.00,
  `license_expiry` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_personnel_active` (`active`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel`
--

LOCK TABLES `personnel` WRITE;
/*!40000 ALTER TABLE `personnel` DISABLE KEYS */;
INSERT INTO `personnel` VALUES (1,'Luis Eduardo','Hernandez Sierra','91273703','3118096285','',1,'Calle 34 NO 34-60 APTO 504','Colombia','Bucaramanga Santander','1967-07-05','Cuenta de ahorros Bancolombia No: 793-148221-67','Conductor','CC','Bucaramanga','Santander','Masculino','C3',NULL,NULL,1423500.00,0.00,0.00,200000.00,NULL),(10,'Gustavo','Pinto Palomino','91355241','3156942906','',1,'Vereda altos de Mantilla. Finca Buenos Aires','Colombia',NULL,NULL,'Cuenta de ahorros Bancolombia No: 799-166303-40','Conductor','CC','Floridablanca','Santander','Masculino','C3','2025-08-08',NULL,1423500.00,0.00,0.00,200000.00,'2026-01-31'),(11,'Edgar Antonio','Lizarazo Mu├▒oz','91291845','3182855134','',1,'CRA 15 # 18-70 Conjunto residencial Reserva de la Loma','Colombia',NULL,NULL,'','Conductor','CC','Piedecuesta','Santander','Masculino','C3','2020-01-01',NULL,1423500.00,0.00,0.00,200000.00,'2026-12-01'),(12,'Robert Eduardo','Serrano Cruz','1098637244','3166292093','1098637244',1,'Calle 51 # 12 -09 Barrio Candiles','Colombia',NULL,NULL,'Cuenta de ahorros Bancolombia No: 793-148221-67','Socio / Propietario','CC','Bucaramanga','Santander','Masculino','C2','2023-05-05',NULL,0.00,0.00,0.00,0.00,'2027-10-04'),(13,'Carlos Eduardo','Serrano G├│mez','5794673','3186618118','5794673',1,'Calle 42 # 29-98 APTO 202. Edificio Posada de Alicante','Colombia',NULL,NULL,'Cuenta de corriente Bancolombia No: 604-95507784','Socio / Propietario','CC','Bucaramanga','Santander','Masculino','B3','1982-01-03',NULL,0.00,0.00,0.00,0.00,'2030-12-12');
/*!40000 ALTER TABLE `personnel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settlements`
--

DROP TABLE IF EXISTS `settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personnel_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `salary_basic` decimal(15,2) NOT NULL,
  `transport_assistance` decimal(15,2) NOT NULL,
  `total_commissions` decimal(15,2) NOT NULL,
  `total_advances_manifest` decimal(15,2) DEFAULT 0.00,
  `total_advances_owner` decimal(15,2) DEFAULT 0.00,
  `total_advances` decimal(15,2) NOT NULL,
  `total_expenses` decimal(15,2) NOT NULL,
  `balance_to_discount` decimal(15,2) NOT NULL,
  `partial_payment` decimal(15,2) DEFAULT 0.00,
  `net_to_pay` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `settlements_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`),
  CONSTRAINT `settlements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settlements`
--

LOCK TABLES `settlements` WRITE;
/*!40000 ALTER TABLE `settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `socios`
--

DROP TABLE IF EXISTS `socios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `socios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('Persona Natural','Empresa') DEFAULT 'Persona Natural',
  `nombre` varchar(255) NOT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `tipo_documento` enum('CC','NIT','CE','Pasaporte') DEFAULT 'CC',
  `telefono` varchar(30) DEFAULT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `cuenta_bancaria` varchar(50) DEFAULT NULL,
  `tipo_cuenta` enum('Ahorros','Corriente') DEFAULT NULL,
  `porcentaje_utilidad` decimal(5,2) DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_socios_nombre` (`nombre`),
  KEY `idx_socios_active` (`active`),
  KEY `idx_socios_ciudad` (`ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `socios`
--

LOCK TABLES `socios` WRITE;
/*!40000 ALTER TABLE `socios` DISABLE KEYS */;
/*!40000 ALTER TABLE `socios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_type` enum('Natural','Jur├¡dica') DEFAULT 'Natural',
  `tax_regime` enum('Com├║n','Simplificado') DEFAULT 'Simplificado',
  `nit` varchar(50) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname1` varchar(100) DEFAULT NULL,
  `lastname2` varchar(100) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Colombia',
  `department` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `account_type` enum('Ahorros','Corriente') DEFAULT 'Ahorros',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (3,'Jur├¡dica','Com├║n','18916750-3',NULL,NULL,NULL,'EDS EL JUVE','CRA 8 # 18-55','Colombia','Cesar','','Bancolombia','','Ahorros','2026-01-09 22:02:24');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_alerts`
--

DROP TABLE IF EXISTS `system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_alerts`
--

LOCK TABLES `system_alerts` WRITE;
/*!40000 ALTER TABLE `system_alerts` DISABLE KEYS */;
INSERT INTO `system_alerts` VALUES (1,'unusual_expense',9,'EXPENSE','Gasto Inusual Detectado','Se registr├│ un gasto de $ 1.630.000 en la categor├¡a \'combustible\'. Supera el umbral de seguridad.','high',0,'2026-05-07 21:59:04');
/*!40000 ALTER TABLE `system_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_payments`
--

DROP TABLE IF EXISTS `trip_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_concept` varchar(50) DEFAULT 'Abono',
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Transferencia',
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `trip_payments_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_payments`
--

LOCK TABLES `trip_payments` WRITE;
/*!40000 ALTER TABLE `trip_payments` DISABLE KEYS */;
INSERT INTO `trip_payments` VALUES (1,1,873760.00,'Saldo Final','2026-05-08','Cheque','1257938','PRETECOR','2026-05-07 22:07:05');
/*!40000 ALTER TABLE `trip_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_type` enum('urbano','nacional','internacional') NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `driver_id` int(11) NOT NULL,
  `material_id` int(11) DEFAULT NULL,
  `origin_point_id` int(11) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `origin_city_id` int(11) DEFAULT NULL,
  `origin_state_id` int(11) DEFAULT NULL,
  `destination_point_id` int(11) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `destination_city_id` int(11) DEFAULT NULL,
  `destination_state_id` int(11) DEFAULT NULL,
  `date_load` date DEFAULT NULL,
  `date_unload` date DEFAULT NULL,
  `kms_start` decimal(10,2) DEFAULT 0.00,
  `kms_end` decimal(10,2) DEFAULT 0.00,
  `kms_total` decimal(10,2) DEFAULT 0.00,
  `manifest_company` varchar(255) DEFAULT NULL,
  `manifest_company_id` int(11) DEFAULT NULL,
  `manifest_number` varchar(100) DEFAULT NULL,
  `manifest_date` date DEFAULT NULL,
  `weight_declared` decimal(10,2) DEFAULT 0.00,
  `weight_origin` decimal(10,2) DEFAULT 0.00,
  `weight_dest` decimal(10,2) DEFAULT 0.00,
  `flete_bruto` decimal(15,2) DEFAULT 0.00,
  `percent_rete_fuente` decimal(5,2) DEFAULT 0.00,
  `value_rete_fuente` decimal(15,2) DEFAULT 0.00,
  `percent_rete_ica` decimal(5,2) DEFAULT 0.00,
  `percent_iva` decimal(5,2) DEFAULT 0.00,
  `percent_rete_iva` decimal(5,2) DEFAULT 0.00,
  `value_rete_ica` decimal(15,2) DEFAULT 0.00,
  `value_iva` decimal(15,2) DEFAULT 0.00,
  `value_rete_iva` decimal(15,2) DEFAULT 0.00,
  `percent_deductible_3` decimal(5,2) DEFAULT 0.00,
  `value_deductible_3` decimal(15,2) DEFAULT 0.00,
  `value_deductible_4` decimal(15,2) DEFAULT 0.00,
  `value_deductible_5` decimal(15,2) DEFAULT 0.00,
  `value_deductible_6` decimal(15,2) DEFAULT 0.00,
  `total_deductibles` decimal(15,2) DEFAULT 0.00,
  `flete_neto` decimal(15,2) DEFAULT 0.00,
  `advance_owner` decimal(15,2) DEFAULT 0.00,
  `advance_owner_responsible` varchar(255) DEFAULT NULL,
  `advance_manifest` decimal(15,2) DEFAULT 0.00,
  `advance_manifest_to_driver` tinyint(1) DEFAULT 0,
  `commission_percent` decimal(5,2) DEFAULT 0.00,
  `commission_value` decimal(15,2) DEFAULT 0.00,
  `final_pay_expected` decimal(15,2) DEFAULT 0.00,
  `final_pay_received` decimal(15,2) DEFAULT 0.00,
  `settlement_status` enum('Pending','Settled') DEFAULT 'Pending',
  `settlement_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `status` enum('En Progreso','Finalizado','Cancelado') DEFAULT 'En Progreso',
  `delivery_proof_url` varchar(255) DEFAULT NULL,
  `manifest_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `manifest_company_id` (`manifest_company_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_trips_vehicle` (`vehicle_id`),
  KEY `idx_trips_driver` (`driver_id`),
  KEY `idx_trips_status` (`status`),
  KEY `idx_trips_date_load` (`date_load`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_trips_client` (`client_id`),
  KEY `idx_trips_settlement` (`settlement_status`),
  KEY `idx_trips_report_monthly` (`date_load`,`status`),
  KEY `origin_city_id` (`origin_city_id`),
  KEY `origin_state_id` (`origin_state_id`),
  KEY `destination_city_id` (`destination_city_id`),
  KEY `destination_state_id` (`destination_state_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date_load` (`date_load`),
  CONSTRAINT `fk_trips_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `personnel` (`id`),
  CONSTRAINT `trips_ibfk_3` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`),
  CONSTRAINT `trips_ibfk_4` FOREIGN KEY (`manifest_company_id`) REFERENCES `manifest_companies` (`id`),
  CONSTRAINT `trips_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `trips_ibfk_6` FOREIGN KEY (`origin_city_id`) REFERENCES `loc_cities` (`id`),
  CONSTRAINT `trips_ibfk_7` FOREIGN KEY (`origin_state_id`) REFERENCES `loc_states` (`id`),
  CONSTRAINT `trips_ibfk_8` FOREIGN KEY (`destination_city_id`) REFERENCES `loc_cities` (`id`),
  CONSTRAINT `trips_ibfk_9` FOREIGN KEY (`destination_state_id`) REFERENCES `loc_states` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trips`
--

LOCK TABLES `trips` WRITE;
/*!40000 ALTER TABLE `trips` DISABLE KEYS */;
INSERT INTO `trips` VALUES (1,'nacional',1,2,1,8,NULL,'Piedecuesta',43,28,NULL,'Sabanalarga',10,4,'2022-09-02','2022-09-04',228137.00,228787.00,650.00,NULL,1,'01-77457',NULL,35.00,35.00,35.00,3162000.00,1.00,31620.00,1.00,0.00,0.00,31620.00,0.00,0.00,0.00,0.00,25000.00,0.00,0.00,88240.00,3073760.00,150000.00,'Robert Eduardo Serrano Cruz',2200000.00,1,12.00,368851.20,873760.00,873760.00,'Settled','Sale de Piedecuesta en su primer viaje con Postes','2026-02-01 19:14:30','2026-06-18 01:02:03',1,'Finalizado','uploads/proofs/pod_1_1781744523.pdf','uploads/manifests/manifest_1778181644_69fce60c02bbd.pdf');
/*!40000 ALTER TABLE `trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','common') NOT NULL DEFAULT 'common',
  `related_client_id` int(11) DEFAULT NULL,
  `related_personnel_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_user_client` (`related_client_id`),
  KEY `fk_user_personnel` (`related_personnel_id`),
  CONSTRAINT `fk_user_client` FOREIGN KEY (`related_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_personnel` FOREIGN KEY (`related_personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','Administrador del Sistema','$2y$10$f.5TvkB0KoV1SS3s6Q559OLi5xahrTOfr0ZhZXeAhO04bcIG8Wvr.','admin',NULL,NULL,'2025-12-29 21:13:28','uploads/avatars/user_69612cba3c0399.37206448.png','active','2026-06-17 19:39:06',0,NULL),(2,'ines','Ines Alexandra Bustamante Guerra','$2y$10$LXZH1H1tKEL1Ve7G.lqW5eVOI89sNPIxb.R6KU/ODJOe6.BadWND6','common',NULL,NULL,'2026-01-09 11:21:17','uploads/avatars/user_69612c39bbc812.48067786.jpg','active','2026-01-09 11:22:26',0,NULL),(3,'conductor','Luis Eduardo Hernandez Sierra','$2y$10$Onq/lZDpvqVITNtefLsHMepkSg14df4g11N5xNyibwFMeKkdtkoDm','',NULL,1,'2026-01-31 18:13:31',NULL,'active','2026-01-31 18:44:22',0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `satrack_id` varchar(100) DEFAULT NULL,
  `ownership_type` enum('Propio','Tercero','Socio') DEFAULT 'Propio',
  `partner_id` int(11) DEFAULT NULL,
  `partner_percentage` decimal(5,2) DEFAULT 0.00,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `category` varchar(50) DEFAULT NULL,
  `model_year` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `motor_number` varchar(100) DEFAULT NULL,
  `chasis_number` varchar(100) DEFAULT NULL,
  `displacement` varchar(50) DEFAULT NULL,
  `max_load` varchar(50) DEFAULT NULL,
  `register_city` varchar(100) DEFAULT NULL,
  `register_country` varchar(100) DEFAULT 'Colombia',
  `register_department` varchar(100) DEFAULT NULL,
  `expiry_soat` date DEFAULT NULL,
  `expiry_tecno` date DEFAULT NULL,
  `expiry_policy` date DEFAULT NULL,
  `expiry_todo_riesgo` date DEFAULT NULL,
  `default_driver_id` int(11) DEFAULT NULL,
  `policy_number_todo_riesgo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `placa` (`placa`),
  KEY `fk_vehicle_driver` (`default_driver_id`),
  KEY `fk_vehicle_partner` (`partner_id`),
  CONSTRAINT `fk_vehicle_driver` FOREIGN KEY (`default_driver_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vehicle_partner` FOREIGN KEY (`partner_id`) REFERENCES `personnel` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,'SKN756','','Propio',NULL,0.00,'INTERNATIONAL','EAGLE 9400i',1,'Tractocami├│n','2009','Rojo','3HSCNAPT08N579408','79237658','3HSCNAPT08N579408','14945','35','Cajic├í','Colombia','Cundinamarca','2026-10-07','2026-06-13','2026-05-15',NULL,1,NULL),(5,'SSZ198',NULL,'Tercero',13,0.00,'INTERNATIONAL','EAGLE 9400i',1,'Tractocami├│n','2012','Amarillo','3HSCNAPTXCN566156','79476947','3HSCNAPTXCN566156','14945','35','Bucaramanga','Colombia','Santander','2026-10-04','2026-12-03',NULL,NULL,11,NULL);
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `view_vehicle_availability`
--

DROP TABLE IF EXISTS `view_vehicle_availability`;
/*!50001 DROP VIEW IF EXISTS `view_vehicle_availability`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `view_vehicle_availability` AS SELECT
 1 AS `id`,
  1 AS `placa`,
  1 AS `last_kms`,
  1 AS `is_busy` */;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `view_vehicle_availability`
--

/*!50001 DROP VIEW IF EXISTS `view_vehicle_availability`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `view_vehicle_availability` AS select `v`.`id` AS `id`,`v`.`placa` AS `placa`,(select `t`.`kms_end` from `trips` `t` where `t`.`vehicle_id` = `v`.`id` order by `t`.`date_load` desc,`t`.`id` desc limit 1) AS `last_kms`,exists(select 1 from `trips` `t` where `t`.`vehicle_id` = `v`.`id` and `t`.`status` = 'En Progreso' limit 1) AS `is_busy` from `vehicles` `v` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-19 21:08:31
