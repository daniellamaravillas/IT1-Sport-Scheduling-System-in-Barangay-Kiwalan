-- MySQL dump 10.19  Distrib 10.3.39-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: mariadb
-- ------------------------------------------------------
-- Server version	10.3.39-MariaDB-0ubuntu0.20.04.2

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
-- Table structure for table `Clients`
--

DROP TABLE IF EXISTS `Clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Clients` (
  `ClientID` bigint(20) NOT NULL AUTO_INCREMENT,
  `clients_name` text NOT NULL,
  `contact_number` bigint(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `ID` bigint(20) NOT NULL,
  PRIMARY KEY (`ClientID`),
  KEY `users_Clients` (`ID`),
  CONSTRAINT `users_Clients` FOREIGN KEY (`ID`) REFERENCES `users` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Clients`
--

LOCK TABLES `Clients` WRITE;
/*!40000 ALTER TABLE `Clients` DISABLE KEYS */;
INSERT INTO `Clients` VALUES (1,'Jamie Moya',92546425,'Iligan City',1),(2,'jade',51686,'kiwalan',1),(3,'Daniella Maravillas',22312212,'Linamon',1);
/*!40000 ALTER TABLE `Clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Events`
--

DROP TABLE IF EXISTS `Events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Events` (
  `EventID` bigint(20) NOT NULL AUTO_INCREMENT,
  `Events_name` text NOT NULL,
  `ClientID` bigint(20) NOT NULL,
  PRIMARY KEY (`EventID`),
  KEY `Clients_Events` (`ClientID`),
  CONSTRAINT `Clients_Events` FOREIGN KEY (`ClientID`) REFERENCES `Clients` (`ClientID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Events`
--

LOCK TABLES `Events` WRITE;
/*!40000 ALTER TABLE `Events` DISABLE KEYS */;
INSERT INTO `Events` VALUES (1,'Badminton',1),(2,'VOLLEYBALL',2),(3,'Chinesse Garter',3);
/*!40000 ALTER TABLE `Events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Schedule`
--

DROP TABLE IF EXISTS `Schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Schedule` (
  `ScheduleID` bigint(20) NOT NULL AUTO_INCREMENT,
  `start_date_time` datetime NOT NULL,
  `end_date_time` datetime NOT NULL,
  `date_schedule` datetime NOT NULL,
  `EventID` bigint(20) NOT NULL,
  `StatusID` bigint(20) NOT NULL,
  PRIMARY KEY (`ScheduleID`),
  KEY `Events_Schedule` (`EventID`),
  KEY `Updated_Status_Schedule` (`StatusID`),
  CONSTRAINT `Events_Schedule` FOREIGN KEY (`EventID`) REFERENCES `Events` (`EventID`),
  CONSTRAINT `Updated_Status_Schedule` FOREIGN KEY (`StatusID`) REFERENCES `Updated_Status` (`StatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Schedule`
--

LOCK TABLES `Schedule` WRITE;
/*!40000 ALTER TABLE `Schedule` DISABLE KEYS */;
INSERT INTO `Schedule` VALUES (1,'2025-02-27 11:16:00','2025-02-27 23:17:00','0000-00-00 00:00:00',1,3),(2,'2025-02-28 10:00:00','2025-02-28 11:00:00','0000-00-00 00:00:00',2,3),(3,'2025-03-01 08:00:00','2025-03-01 21:30:00','0000-00-00 00:00:00',3,3);
/*!40000 ALTER TABLE `Schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Updated_Status`
--

DROP TABLE IF EXISTS `Updated_Status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Updated_Status` (
  `StatusID` bigint(20) NOT NULL AUTO_INCREMENT,
  `updated_status` text NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`StatusID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Updated_Status`
--

LOCK TABLES `Updated_Status` WRITE;
/*!40000 ALTER TABLE `Updated_Status` DISABLE KEYS */;
INSERT INTO `Updated_Status` VALUES (3,'confirm'),(4,'Completed'),(5,'Completed'),(6,'Cancelled');
/*!40000 ALTER TABLE `Updated_Status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `history`
--

DROP TABLE IF EXISTS `history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `history` (
  `historyID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ClientID` bigint(20) NOT NULL,
  `ScheduleID` bigint(20) NOT NULL,
  `EventID` bigint(20) NOT NULL,
  PRIMARY KEY (`historyID`),
  KEY `Clients_history` (`ClientID`),
  KEY `Schedule_history` (`ScheduleID`),
  KEY `Events_history` (`EventID`),
  CONSTRAINT `Clients_history` FOREIGN KEY (`ClientID`) REFERENCES `Clients` (`ClientID`),
  CONSTRAINT `Events_history` FOREIGN KEY (`EventID`) REFERENCES `Events` (`EventID`),
  CONSTRAINT `Schedule_history` FOREIGN KEY (`ScheduleID`) REFERENCES `Schedule` (`ScheduleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `history`
--

LOCK TABLES `history` WRITE;
/*!40000 ALTER TABLE `history` DISABLE KEYS */;
/*!40000 ALTER TABLE `history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL,
  `username` varchar(40) NOT NULL,
  `account_level` varchar(40) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Aron@yahoo.com','$2y$10$TWWXV2nM.sjVHD3R98PJVOeJfl2pkkcioH.QupH05APDbpFyat6ty','Aron','admin'),(2,'dandan@gmail.com','$2y$10$ugnPayvk/4PTFKBra8GsTelsGqcwz6M8TordQ.c8wRPf0z2r3a6iK','dan','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-12  1:28:54
