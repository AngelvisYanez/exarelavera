-- MariaDB dump 10.19-12.3.2-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: auditoria
-- ------------------------------------------------------
-- Server version	12.3.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `cfg_monitoreo`
--

DROP TABLE IF EXISTS `cfg_monitoreo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cfg_monitoreo` (
  `Cfg_Cod` int(11) NOT NULL AUTO_INCREMENT,
  `Emp_Cod` int(11) NOT NULL,
  `Org_Cod` int(11) NOT NULL,
  `Pcs_Cod` int(11) NOT NULL DEFAULT 0,
  `Cfg_Est` char(1) NOT NULL DEFAULT 'A',
  `Cfg_Fec` datetime DEFAULT NULL,
  `Usu_Cod` int(11) DEFAULT NULL,
  PRIMARY KEY (`Cfg_Cod`),
  UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
  KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cfg_monitoreo`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cfg_monitoreo` WRITE;
/*!40000 ALTER TABLE `cfg_monitoreo` DISABLE KEYS */;
/*!40000 ALTER TABLE `cfg_monitoreo` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `eventos`
--

DROP TABLE IF EXISTS `eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos` (
  `Eve_Cod` int(11) NOT NULL AUTO_INCREMENT,
  `Eve_Ini` char(1) NOT NULL,
  `Eve_Des` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Eve_Cod`),
  KEY `Eve_Ini` (`Eve_Ini`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `eventos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `eventos` WRITE;
/*!40000 ALTER TABLE `eventos` DISABLE KEYS */;
INSERT INTO `eventos` VALUES
(1,'F','Fallido'),
(2,'I','Insertar'),
(3,'U','Actualizar'),
(4,'D','Eliminar');
/*!40000 ALTER TABLE `eventos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `Log_Cod` bigint(20) NOT NULL AUTO_INCREMENT,
  `Usu_Cod` int(11) NOT NULL,
  `Pcs_Cod` int(11) NOT NULL,
  `Tab_Cod` int(11) NOT NULL,
  `Log_Fec` datetime NOT NULL,
  `Eve_Cod` int(11) NOT NULL,
  `Log_Cam` varchar(255) DEFAULT NULL,
  `Log_Val` text DEFAULT NULL,
  `Log_Int` text DEFAULT NULL,
  `Emp_Cod` int(11) DEFAULT NULL,
  `Suc_Cod` int(11) DEFAULT NULL,
  PRIMARY KEY (`Log_Cod`),
  KEY `Emp_Fec` (`Emp_Cod`,`Log_Fec`),
  KEY `Usu_Fec` (`Usu_Cod`,`Log_Fec`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES
(2,2430,0,2,'2026-08-14 10:55:40',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','DEMO-1',503,603),
(3,2430,0,2,'2026-08-14 10:55:40',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Cod=DEMO-1',503,603),
(4,2430,0,3,'2026-08-14 10:55:40',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(5,2430,0,2,'2026-08-14 10:56:11',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','DEMO-1',503,603),
(6,2430,0,2,'2026-08-14 10:56:11',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Cod=DEMO-1',503,603),
(7,2430,0,3,'2026-08-14 10:56:11',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(8,2430,0,2,'2026-08-14 11:00:06',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','DEMO-1',503,603),
(9,2430,0,2,'2026-08-14 11:00:07',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Cod=DEMO-1',503,603),
(10,2430,0,3,'2026-08-14 11:00:07',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(11,2430,111,2,'2026-08-14 11:12:04',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(12,2430,111,2,'2026-08-14 11:12:04',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(13,2430,111,3,'2026-08-14 11:12:04',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(14,6170,111,2,'2026-08-14 11:30:58',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(15,6170,111,2,'2026-08-14 11:30:58',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(16,6170,111,3,'2026-08-14 11:30:58',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(17,6170,111,2,'2026-08-14 11:37:52',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(18,6170,111,2,'2026-08-14 11:37:52',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(19,6170,111,3,'2026-08-14 11:37:52',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(20,6170,111,2,'2026-08-14 11:40:38',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(21,6170,111,2,'2026-08-14 11:40:38',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(22,6170,111,3,'2026-08-14 11:40:38',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(23,6170,111,2,'2026-08-14 11:43:11',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(24,6170,111,2,'2026-08-14 11:43:11',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(25,6170,111,3,'2026-08-14 11:43:11',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(26,6170,111,2,'2026-08-14 12:28:06',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(27,6170,111,2,'2026-08-14 12:28:06',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(28,6170,111,3,'2026-08-14 12:28:06',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(29,6170,111,2,'2026-08-14 12:30:49',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-14~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(30,6170,111,2,'2026-08-14 12:30:49',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(31,6170,111,3,'2026-08-14 12:30:49',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660),
(32,6170,0,8,'2026-08-14 12:29:39',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-14~,~2026-08-20~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',96,660),
(33,6170,0,7,'2026-08-14 12:29:49',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,660),
(34,6170,0,5,'2026-08-14 12:29:59',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-14~,~2026-08-20~,~A~','Tur_Cod=DEMO-TUR-1',96,660),
(35,6170,0,6,'2026-08-14 12:30:09',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-14~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',96,660),
(36,6170,0,4,'2026-08-14 12:30:19',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-14 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',96,660),
(37,6170,0,4,'2026-08-14 12:30:29',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',96,660),
(38,6170,0,6,'2026-08-14 12:30:39',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',96,660),
(39,6170,0,7,'2026-08-14 12:30:44',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,660),
(136,64250,111,2,'2026-08-15 13:31:02',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(137,64250,111,2,'2026-08-15 13:31:02',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(138,64250,111,3,'2026-08-15 13:31:02',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(139,64250,0,8,'2026-08-15 13:29:52',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(140,64250,0,7,'2026-08-15 13:30:02',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(141,64250,0,5,'2026-08-15 13:30:12',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(142,64250,0,6,'2026-08-15 13:30:22',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(143,64250,0,4,'2026-08-15 13:30:32',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(144,64250,0,4,'2026-08-15 13:30:43',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(145,64250,0,6,'2026-08-15 13:30:53',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(146,64250,0,7,'2026-08-15 13:30:58',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(147,64250,111,2,'2026-08-15 13:39:29',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(148,64250,111,2,'2026-08-15 13:39:29',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(149,64250,111,3,'2026-08-15 13:39:29',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(150,64250,0,8,'2026-08-15 13:38:19',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(151,64250,0,7,'2026-08-15 13:38:29',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(152,64250,0,5,'2026-08-15 13:38:39',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(153,64250,0,6,'2026-08-15 13:38:49',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(154,64250,0,4,'2026-08-15 13:38:59',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(155,64250,0,4,'2026-08-15 13:39:09',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(156,64250,0,6,'2026-08-15 13:39:19',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(157,64250,0,7,'2026-08-15 13:39:24',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(158,64250,111,2,'2026-08-15 13:47:50',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(159,64250,111,2,'2026-08-15 13:47:50',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(160,64250,111,3,'2026-08-15 13:47:50',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(161,64250,0,8,'2026-08-15 13:46:40',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(162,64250,0,7,'2026-08-15 13:46:50',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(163,64250,0,5,'2026-08-15 13:47:00',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(164,64250,0,6,'2026-08-15 13:47:10',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(165,64250,0,4,'2026-08-15 13:47:20',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(166,64250,0,4,'2026-08-15 13:47:30',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(167,64250,0,6,'2026-08-15 13:47:40',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(168,64250,0,7,'2026-08-15 13:47:45',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(169,64250,111,2,'2026-08-15 14:09:08',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(170,64250,111,2,'2026-08-15 14:09:08',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(171,64250,111,3,'2026-08-15 14:09:08',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(172,64250,0,8,'2026-08-15 14:07:58',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(173,64250,0,7,'2026-08-15 14:08:08',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(174,64250,0,5,'2026-08-15 14:08:18',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(175,64250,0,6,'2026-08-15 14:08:28',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(176,64250,0,4,'2026-08-15 14:08:38',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(177,64250,0,4,'2026-08-15 14:08:48',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(178,64250,0,6,'2026-08-15 14:08:58',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(179,64250,0,7,'2026-08-15 14:09:03',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(180,64250,111,2,'2026-08-15 14:10:34',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(181,64250,111,2,'2026-08-15 14:10:34',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(182,64250,111,3,'2026-08-15 14:10:34',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(183,64250,0,8,'2026-08-15 14:09:24',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(184,64250,0,7,'2026-08-15 14:09:34',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(185,64250,0,5,'2026-08-15 14:09:44',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(186,64250,0,6,'2026-08-15 14:09:54',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(187,64250,0,4,'2026-08-15 14:10:04',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(188,64250,0,4,'2026-08-15 14:10:14',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(189,64250,0,6,'2026-08-15 14:10:24',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(190,64250,0,7,'2026-08-15 14:10:29',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(191,64250,111,2,'2026-08-15 14:12:56',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',503,603),
(192,64250,111,2,'2026-08-15 14:12:56',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',503,603),
(193,64250,111,3,'2026-08-15 14:12:56',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',503,603),
(194,64250,0,8,'2026-08-15 14:11:46',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',503,603),
(195,64250,0,7,'2026-08-15 14:11:56',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(196,64250,0,5,'2026-08-15 14:12:06',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',503,603),
(197,64250,0,6,'2026-08-15 14:12:16',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(198,64250,0,4,'2026-08-15 14:12:26',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',503,603),
(199,64250,0,4,'2026-08-15 14:12:36',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',503,603),
(200,64250,0,6,'2026-08-15 14:12:46',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',503,603),
(201,64250,0,7,'2026-08-15 14:12:51',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',503,603),
(202,6273,111,2,'2026-08-15 15:00:53',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,99),
(203,6273,111,2,'2026-08-15 15:00:53',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,99),
(204,6273,111,3,'2026-08-15 15:00:53',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,99),
(205,6273,0,8,'2026-08-15 14:59:43',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',96,99),
(206,6273,0,7,'2026-08-15 14:59:53',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,99),
(207,6273,0,5,'2026-08-15 15:00:03',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',96,99),
(208,6273,0,6,'2026-08-15 15:00:13',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',96,99),
(209,6273,0,4,'2026-08-15 15:00:23',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',96,99),
(210,6273,0,4,'2026-08-15 15:00:33',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',96,99),
(211,6273,0,6,'2026-08-15 15:00:43',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',96,99),
(212,6273,0,7,'2026-08-15 15:00:48',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,99),
(213,6273,111,2,'2026-08-15 15:06:10',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-15~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,99),
(214,6273,111,2,'2026-08-15 15:06:10',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,99),
(215,6273,111,3,'2026-08-15 15:06:10',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,99),
(216,6273,0,8,'2026-08-15 15:05:00',2,'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst','~Jornada Demo Relavera~,~2026-08-15~,~2026-08-21~,8,~S~,~A~','Man_Eve=DEMO-EVE-1',96,99),
(217,6273,0,7,'2026-08-15 15:05:10',2,'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs','~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,99),
(218,6273,0,5,'2026-08-15 15:05:20',2,'Tur_Fei,Tur_Fef,Tur_Est','~2026-08-15~,~2026-08-21~,~A~','Tur_Cod=DEMO-TUR-1',96,99),
(219,6273,0,6,'2026-08-15 15:05:30',2,'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est','~2026-08-15~,~07:00:00~,~12:00:00~,15,~A~','Tud_Cod=DEMO-TUD-1',96,99),
(220,6273,0,4,'2026-08-15 15:05:40',2,'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est','1001,~2026-08-15 08:30:00~,12500,3.00,~P~,~A~','Man_Cod=DEMO-M-1001',96,99),
(221,6273,0,4,'2026-08-15 15:05:50',3,'Man_Pes,Man_Pun,Man_Obe','13200,3.50,~AJUSTE DEMO RELAVERA~','Man_Cod=DEMO-M-1001',96,99),
(222,6273,0,6,'2026-08-15 15:06:00',3,'Tud_Cup,Tud_Est','12,~A~','Tud_Cod=DEMO-TUD-1',96,99),
(223,6273,0,7,'2026-08-15 15:06:05',3,'MVis_Est,MVis_Obs','~I~,~ANULACION DEMO VISITANTE~','MVis_Cod=DEMO-VIS-1',96,99),
(224,6170,130,2,'2026-08-17 09:32:01',2,'Pec_Cod,Com_Num,Com_Fec,Com_Con,Com_Val','1,~001-DEMO-0001~,~2026-08-17~,~COMPROBANTE DEMO AUDITORIA~,150.00','Com_Num=001-DEMO-0001',96,660),
(225,6170,130,2,'2026-08-17 09:32:01',3,'Com_Con,Com_Val','~COMPROBANTE DEMO MODIFICADO~,175.50','Com_Num=001-DEMO-0001',96,660),
(226,6170,130,3,'2026-08-17 09:32:01',4,'Asi_Deh,Asi_Val,Asi_Con','~D~,50.00,~ANULACION ASIENTO DEMO~','Asi_Cod=DEMO-9',96,660);
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sesion`
--

DROP TABLE IF EXISTS `sesion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesion` (
  `Ses_Cod` int(11) NOT NULL,
  `Usu_Cod` int(11) NOT NULL,
  `Ses_Int` datetime DEFAULT NULL,
  `Ses_Out` datetime DEFAULT NULL,
  PRIMARY KEY (`Ses_Cod`),
  KEY `Usu_Cod` (`Usu_Cod`)
) ENGINE=Aria DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci PAGE_CHECKSUM=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesion`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sesion` WRITE;
/*!40000 ALTER TABLE `sesion` DISABLE KEYS */;
INSERT INTO `sesion` VALUES
(1,2430,'2026-07-07 10:50:19',NULL),
(2,1,'2026-07-07 12:00:24',NULL),
(3,1,'2026-07-07 11:00:29',NULL),
(4,1,'2026-07-07 11:01:59',NULL),
(5,1,'2026-07-07 11:02:09',NULL),
(6,1,'2026-07-07 11:02:20',NULL),
(7,1,'2026-07-07 11:02:31',NULL),
(8,1,'2026-07-07 11:02:41',NULL),
(9,1,'2026-07-07 11:07:49',NULL),
(10,1,'2026-07-07 11:10:16',NULL),
(11,1,'2026-07-13 22:38:09',NULL),
(12,1,'2026-07-13 22:38:46',NULL),
(13,1,'2026-07-13 22:38:55',NULL),
(14,1,'2026-07-13 22:39:13',NULL),
(15,1,'2026-07-13 22:40:26',NULL),
(16,1,'2026-08-12 09:41:35','2026-08-12 09:41:35'),
(17,1,'2026-08-12 09:41:40','2026-08-12 09:41:40'),
(18,1,'2026-08-14 09:51:47','2026-08-14 09:51:47');
/*!40000 ALTER TABLE `sesion` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tablas`
--

DROP TABLE IF EXISTS `tablas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tablas` (
  `Tab_Cod` int(11) NOT NULL AUTO_INCREMENT,
  `Tab_Nom` varchar(64) NOT NULL,
  `Tab_Des` varchar(255) DEFAULT NULL,
  `Tab_Ali` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`Tab_Cod`),
  KEY `Tab_Nom` (`Tab_Nom`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tablas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tablas` WRITE;
/*!40000 ALTER TABLE `tablas` DISABLE KEYS */;
INSERT INTO `tablas` VALUES
(1,'usuarios','Usuarios del sistema','usuarios'),
(2,'comprobantes','Comprobantes contables','Comprobantes'),
(3,'asientos','Asientos contables','Asientos'),
(4,'manifiesto','Manifiestos Relavera','Manifiestos'),
(5,'manifiesto_turnos_cab','Turnos Relavera','Turnos'),
(6,'manifiesto_turnos_det','Detalle de turnos Relavera','Detalle de turnos'),
(7,'manifiesto_visitante','Visitantes Relavera','Visitantes'),
(8,'manifiesto_evento','Eventos Relavera','Eventos');
/*!40000 ALTER TABLE `tablas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping routines for database 'auditoria'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-08-17 10:42:55
