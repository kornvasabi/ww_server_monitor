/*!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: server_monitor
-- ------------------------------------------------------
-- Server version	11.4.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL COMMENT 'เก็บ IP หรือ URL',
  `type` enum('ip','url') NOT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Unknown',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servers`
--

LOCK TABLES `servers` WRITE;
/*!40000 ALTER TABLE `servers` DISABLE KEYS */;
INSERT INTO `servers` VALUES
(1,'korn','192.168.0.18','ip',NULL,'Unknown',0),
(2,'bag','192.168.0.17','ip',NULL,'Unknown',0),
(3,'qrcode_korn','http://192.168.0.18:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(4,'qrcode_bag','http://192.168.0.17:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(5,'qrcode_old_wu','http://192.168.3.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(6,'qrcode_old_wb','http://192.168.6.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(7,'qrcode_old_wc','http://192.168.2.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(8,'qrcode_old_wq','http://192.168.8.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(9,'qrcode_old_wa','http://192.168.5.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(10,'qrcode_old_wn','http://192.168.4.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(11,'qrcode_old_wt','http://192.168.7.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(12,'qrcode_old_wp','http://192.168.9.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(13,'qrcode_old_wo','http://192.168.10.2:8080/woodwork_barcode_2/login/auth','url',NULL,'Unknown',1),
(14,'qrcode_new_wu','https://wu.al8m.com/login/','url',NULL,'Unknown',1),
(16,'Metabase','192.168.99.33','ip',NULL,'Unknown',1),
(19,'test','192.168.99.34','ip',NULL,'Unknown',1),
(20,'test_offline','https://www.google.con','url',NULL,'Unknown',1);
/*!40000 ALTER TABLE `servers` ENABLE KEYS */;
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
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2y$10$6hus73Y.QNkPYu/yBip1c.OHR4fcYsXm9CbEBC7Wo3TM9zk9dAXXe','2025-10-17 08:22:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-10-25 11:09:07
