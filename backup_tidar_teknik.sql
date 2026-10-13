-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: tidar_teknik
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `customer_wishlist`
--

DROP TABLE IF EXISTS `customer_wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(50) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`customer_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `customer_wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_wishlist`
--

LOCK TABLES `customer_wishlist` WRITE;
/*!40000 ALTER TABLE `customer_wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` varchar(50) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `alamat` text,
  `npwp` varchar(100) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `pic` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES ('1','PT. INDO MINA BAHARI','NP','JAKARTA','Jl. Indo Mina No. 1, Jakarta',NULL,'customer@tidarteknik.com','$2y$10$NCcOhpw24MYoeDW7m/X5letQk9Br9WNCD/RXbGLhYfD3qYJG/9Dym','081234567890','Bpk. Hendra','active','2026-09-10 11:03:23'),('10','MAYA MUNCAR','MM','BANYUWANGI','Jl. Muncar No. 2, Banyuwangi',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('11','PT. WAHYU DAYA MULIA','WDM','SURABAYA','Jl. Wahyu No. 6, Surabaya',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('12','BAPAK SUGENG','SG','MALANG','Jl. Sugeng No. 3, Malang',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('13','PT. SAPUTRA TIRTHA AMERTHA','STA','BALI','Jl. Tirtha No. 10, Bali',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('14','PT. CAKRA JAYARAYA ABADI','CJA','SURABAYA','Jl. Cakra No. 7, Surabaya',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('15','PT. ANUGERAH MULIA FARM','AMF','MALANG','Jl. Anugerah No. 5, Malang',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('16','PT.Gresik Cipta Sejahtera','GCS','GRESIK','Jl.KIG Raya Selatan Blok A/5 Gresik 61121',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('2','PT MCENERGY BIOMAS INDONESIA','MBI','PASURUAN','JL. RAYA SURABAYA MALANG NO 12, PASURUAN (PAK RIDWAN 081775766704)',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('3','BAPAK MOH. HOSEN','MH','MALANG','Jl. Hosen No. 5, Malang',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('4','BITUMEN MARASENDE','BM','KALIMANTAN','Jl. Marasende, Kalimantan',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('5','BAPAK MASHUDI','','MALANG','Jl. Mashudi, Malang',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('6','BESTAGAR PUREINDO INTERNASIONAL','BPI','JAKARTA','Jl. Bestagar No. 8, Jakarta',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('7','PT. HEFRAM ASASTA INDONESIA','HAI','SURABAYA','Jl. Hefram No. 12, Surabaya',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('8','BIOSEVEN FIBERGLAS INDONESIA','BFI','JAKARTA','Jl. Bioseven No. 4, Jakarta',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('9','PT. MAJU PERKASA NUSANTARA','MPN','SURABAYA','Jl. Maju Perkasa No. 9, Surabaya',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23'),('mopmsk3g0yoo','PT.WIDYA CIPTA TEHNIK','WCT','SURABAYA','Ruko Lotus Regency Blok D-21\nJl. Ketintang Baru Selatan I/30 Surabaya 60231',NULL,NULL,NULL,NULL,NULL,'active','2026-09-10 11:03:23');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ledger`
--

DROP TABLE IF EXISTS `ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger` (
  `id` varchar(50) NOT NULL,
  `type` varchar(50) DEFAULT 'sales',
  `tanggal` date DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `data` longtext,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger`
--

LOCK TABLES `ledger` WRITE;
/*!40000 ALTER TABLE `ledger` DISABLE KEYS */;
INSERT INTO `ledger` VALUES ('mozf4e4t7mvq','purchase_ppn','2026-05-10','PT Maju Jaya','{\"id\":\"mozf4e4t7mvq\",\"type\":\"purchase_ppn\",\"tanggal\":\"2026-05-10\",\"nama\":\"PT Maju Jaya\",\"data\":[null,\"\",\"2026-05-10\",\"PT Maju Jaya\",\"JL Pesona Mutiara Tidar\",\"932489\",\"PO eijeiw\",\"2026-07-12\",\"9023840234\",\"Pompa\",1000000,\"\",\"2026-07-13\",\"PT Maju Perkasa\",\"Wilis Malang\",\"085778780727\",\"234242\",\"7567533\",\"Pompa\",500000,\"1\",50000000,50000,11,403,354353453,35435,\"2026-07-28\",5000000,1000000,\"1\",5000,2342423,11,11,2424,24324,\"2026-07-28\",23432342,234324234,242432,\"1\",234243,2432432423,234242,23424],\"created_at\":\"2026-05-10 13:55:01\"}','2026-06-07 08:52:25'),('mq3jn7y9shcp','sales','2026-06-07','PT Maju Jaya','{\"id\":\"mq3jn7y9shcp\",\"data\":[null,\"\",\"2026-06-07\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",0,\"\",\"2026-06-07\",\"PT Maju Jaya\",\"Jl. Kebon Jeruk No. 12\",\"021-5551234\",\"PO-1001\",\"FP-2001\",\"Kabel Cooper 2x1.5, Kabel Cooper 3x2.5\",450000,15,25000,450000,49500,499500,\"\",0,\"\",0,499500,\"\",\"\",\"\",0,0,\"\",0,\"\",0,0,0,\"\",\"\",0,\"\",0],\"cicilan_sales\":[],\"cicilan_purchase\":[],\"items_sales\":[{\"barang\":\"Kabel Cooper 2x1.5\",\"qty\":10,\"hrg\":25000,\"jumlah\":250000},{\"barang\":\"Kabel Cooper 3x2.5\",\"qty\":5,\"hrg\":40000,\"jumlah\":200000}],\"items_purchase\":[],\"nama\":\"PT Maju Jaya\",\"tanggal\":\"2026-06-07\",\"type\":\"sales\"}','2026-06-07 08:52:25');
/*!40000 ALTER TABLE `ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nota`
--

DROP TABLE IF EXISTS `nota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nota` (
  `id` varchar(50) NOT NULL,
  `nomor` varchar(100) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `customer` varchar(255) DEFAULT NULL,
  `alamat_invoice` text,
  `alamat_sj` text,
  `ref_po` varchar(100) DEFAULT NULL,
  `ekspedisi` varchar(100) DEFAULT NULL,
  `tujuan_kirim` varchar(255) DEFAULT NULL,
  `tanggal_kirim` date DEFAULT NULL,
  `seri_faktur` varchar(100) DEFAULT NULL,
  `ppn_mode` varchar(20) DEFAULT NULL,
  `disc` decimal(15,2) DEFAULT '0.00',
  `dp` decimal(15,2) DEFAULT '0.00',
  `total` decimal(15,2) DEFAULT '0.00',
  `pembayaran` decimal(15,2) DEFAULT '0.00',
  `tanggal_pembayaran` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `catatan` text,
  `termin` int DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nota`
--

LOCK TABLES `nota` WRITE;
/*!40000 ALTER TABLE `nota` DISABLE KEYS */;
INSERT INTO `nota` VALUES ('mofnr6d89kwq','24/IV/TT/GCS/2026','2026-04-22','PT.Gresik Cipta Sejahtera','Jl.KIG Raya Selatan Blok A/5 Gresik 61121','Jl.KIG Raya Selatan Blok A/5 Gresik 61121','F260-422007','Dikirim Sendiri','Gresik',NULL,NULL,'include',0.00,0.00,3601000.00,0.00,NULL,'Belum Bayar','',30),('mon67apz3a2w','25/ V /TT-CUST/2026','2026-05-02','PT Maju Jaya','dada','','0','','',NULL,NULL,'exclude',0.00,0.00,11100000.00,0.00,NULL,'Belum Bayar','Bagus',30),('mooh53fvql4h','34/ II/TT-WDM/2026','2026-03-03','PT. WAHYU DAYA MULIA','DUSUN BAKALAN RT/RW 02/01 DESA PULOREJOI KEC NGORO JOMBANG','DUSUN BAKALAN RT/RW 02/01 DESA PULOREJOI KEC NGORO JOMBANG','PO-2026030022','PAYMENT 21HARI','',NULL,NULL,'exclude',0.00,0.00,25400000.00,0.00,NULL,'Belum Bayar','',21),('mooh53fxmk3p','31/ II/TT-CGG/2026','2026-03-04','PT. CANADA GREEN GATE','KRATON INDUSTRI RAYA NO 3 PIER PASURUAN','KRATON INDUSTRI RAYA NO 3 PIER PASURUAN','PO/2602-0206','PAYMENT 14hari','',NULL,NULL,'none',0.00,0.00,94525000.00,0.00,NULL,'Belum Bayar','',14),('mooh53fxoh15','33/ II/TT-RA/2026','2026-04-11','RAVA ATHAYA','JL. KALIMAS BARU 29B N0 3 TANJUNG PERAK SURABAYA','JL. KALIMAS BARU 29B N0 3 TANJUNG PERAK SURABAYA','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,546120000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fxsz9d','32/ II/TT-BL/2026','2026-02-28','BAPAK TAUFIQUR RAHMAN','PAMEKASAN','PAMEKASAN','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,42000000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fxttpd','30/ II/TT-CGG/2026','2026-03-04','PT. CANADA GREEN GATE','KRATON INDUSTRI RAYA NO 3 PIER PASURUAN','KRATON INDUSTRI RAYA NO 3 PIER PASURUAN','PO/2602-0205','PAYMENT 14hari','',NULL,NULL,'none',0.00,0.00,94525000.00,0.00,NULL,'Belum Bayar','',14),('mooh53fy19es','28/ II/TT-NTB/2026','2026-02-27','PT NUSA TENGGARA BAHARI','GEDUNG CYBER LT II JLN. KUNINGAN BARAT MAMPANG PRAPATAN KOTA ADM JAKARTA SELATAN','GEDUNG CYBER LT II JLN. KUNINGAN BARAT MAMPANG PRAPATAN KOTA ADM JAKARTA SELATAN','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,46000000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fycapc','01/ II/TT-/2026','2026-02-26','BAPAK MARIYANTO','-','-','-','PAYMENT','',NULL,NULL,'none',0.00,0.00,1188000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fylat5','27/ II/TT-BSBS/2026','2026-02-04','PT. SAMUDERA BERKAH SENTOSA','KAWASAN INDUSTRI SAFE N LOCK E 1509 RANGKAHKIDUL SIDOARJO','KAWASAN INDUSTRI SAFE N LOCK E 1509 RANGKAHKIDUL SIDOARJO','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,26700000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fylnh1','26/ II/TT-PBS/2026','2026-02-27','CV. PANGAN BERKAH SENTOSA','DSN PLOSOGENENG JOMBANG','DSN PLOSOGENENG JOMBANG','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,6780000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fyrtsq','29/ II/TT-MPE/2026','2026-02-26','PT. MITRA PRIMA ENVIRO','JL. KERTOMENANGGAL III NO 1 DUKUH MENANGGAL KEC GAYUNGAN SURABAYA','JL. KERTOMENANGGAL III NO 1 DUKUH MENANGGAL KEC GAYUNGAN SURABAYA','026/MPE/PO/II/2026','PAYMENT 30 HARI','',NULL,NULL,'exclude',0.00,0.00,8436000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fz3qhr','25/ II/TT-MIA/2026','2026-03-13','PT. MULTI INDO ABADI','JL. SIMPANG SULFAT SELATAN NO 17 PANDANWANGI BLIMBING MALNG','JL. SIMPANG SULFAT SELATAN NO 17 PANDANWANGI BLIMBING MALNG','-','PAYMENT','',NULL,NULL,'exclude',0.00,25000000.00,92000000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fz5af2','22/ II/TT-M/2026','2026-02-26','BAPAK MARIYANTO','-','-','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,6200000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fzb0hs','20/ II/TT-BB/2026','2026-02-23','PT. BERKAH BERSAUDARA','JL. 21 JANUARI RT 02 NO 15 KEL KAMPUNG BARU TENGAH BALIKPAPAN KALIMANTAN TIMUR','DESA REMEN RT01/01 KEC JENU KAB TUBAN 62352','001753/BBR/II/2026','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,12387600.00,0.00,NULL,'Belum Bayar','',30),('mooh53fzb2g9','24/ II/TT-MBI/2026','2026-02-26','PT MCENERGY BIOMAS INDONESIA','JL. RAYA SURABAYA MALANG NO 12 PARENTINAP PAREREJO PURWODADI PASURUAN','JL. RAYA SURABAYA MALANG NO 12 PARENTINAP PAREREJO PURWODADI PASURUAN','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,865800.00,0.00,NULL,'Belum Bayar','',30),('mooh53fzfqgw','23/ II/TT-BB/2026','2026-03-05','PT. BERKAH BERSAUDARA','JL. 21 JANUARI RT 02 NO 15 KEL KAMPUNG BARU TENGAH BALIKPAPAN KALIMANTAN TIMUR','JL. IR.M PUTUHENA DESA WAYANE PANGKALAN SPIT LORONG NIPA RT 02/01 KEC TELUK AMBON KOTA AMBON 0823 9882 4466','001840/BBR/II/2026','PAYMENT 30HARI','',NULL,NULL,'exclude',0.00,0.00,10989000.00,0.00,NULL,'Belum Bayar','',30),('mooh53fzi3sx','21/ II/TT-SMIP/2026','2026-02-21','PT.SUMBER MAS INDAH PLYWOOD','JL.TANJUNG SADARI NO 23 PERAK BARAT - KREMBANGAN SURABAYA','JL.TANJUNG SADARI NO 23 PERAK BARAT - KREMBANGAN SURABAYA','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,16594500.00,0.00,NULL,'Belum Bayar','',30),('mooh53fzr1y1','19/ II/TT-MPN/2026','2026-02-19','PT. MAJU PERKASA NUSANTARA','JL. MAHAKAM NO 66 KEL PULAU ATAS KEC SAMBUTAN SAMARINDA KALIMANTAN TIMUR','JL. YOS SUDARSO (JEMBATAN BESI) RT 15 NO 40 GANG MAHONI KOTA TARAKAN KALIMANTAN UTARA PAK ROAHMAT BERKAH 0895 4011 60661','000404/MPN/II/2026','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,2218890.00,0.00,NULL,'Belum Bayar','',30),('mooh53g046bz','14/ II/TT-OASI/2026','2026-02-20','CV. OLEINDO AMANA SEJAHTERA INDONESIA','JL. LINGKAR TIMUR NO KM2.5 SIWALANPANJI KEC BUDURAN KAB SIDOARJO','JL. LINGKAR TIMUR NO KM2.5 SIWALANPANJI KEC BUDURAN KAB SIDOARJO BAPAK AGUNG 081217558252','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,35000000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g08cn3','18/ II/TT-IMS/2026','2026-02-19','INDAH MAKMUR SUKSES','JL. BRANTAS 42 PILANG, KADEMANGAN KOTA PROBOLINGGO 67221 BAPAK HENDRA 0822 3344 4778','JL. BRANTAS 42 PILANG, KADEMANGAN KOTA PROBOLINGGO 67221 BAPAK HENDRA 0822 3344 4778','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,10212000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g09kyh','16/ II/TT-CE/2026','2026-02-19','CAHAYA ELEKTRINDO','JL. DUKUH NO 37 NYAMPLUNGAN PABEAN CANTIKAN SURABAYA','JL. DUKUH NO 37 NYAMPLUNGAN PABEAN CANTIKAN SURABAYA','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,15896000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g0bv3u','13/ II/TT-MMM/2026','2026-02-13','PT. METRO MESIN MENDUNIA','JL. RAYA CURUNG REJO NO 421 TR/RW 08/02 BORO UTARA CURUNG REJO KAB MALANG 65163','JL. RAYA CURUNG REJO NO 421 TR/RW 08/02 BORO UTARA CURUNG REJO KAB MALANG 65163','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,5908286.00,0.00,NULL,'Belum Bayar','',30),('mooh53g0p841','15/ II/TT-SJS/2026','2026-02-16','PT SARANA JAYA SERBAGUNA','JL. SUROPATI 75 BULULAWANG MALANG','JL. SUROPATI 75 BULULAWANG MALANG','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,3330000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g0zrq1','17/ II/TT-BB/2026','2026-02-21','PT. BERKAH BERSAUDARA','JL. 21 JANUARI RT 02 NO 15 KEL KAMPUNG BARU TENGAH BALIKPAPAN KALIMANTAN TIMUR','JL. 21 JANUARI RT 02 NO 15 KEL KAMPUNG BARU TENGAH BALIKPAPAN KALIMANTAN TIMUR BU MUZDALIFAH 08115931249','001722/BBR/II/2026','PAYMENT 30HARI','',NULL,NULL,'exclude',0.00,0.00,21645000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g10oz5','07/ II/TT-BFI/2026','2026-02-09','BIOSEVEN FIBERGLAS INDONESIA','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,27259380.00,0.00,NULL,'Belum Bayar','',30),('mooh53g160mk','11/ II/TT-M/2026','2026-03-10','BAPAK MARIYANTO','-','-','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,137640000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g1e86r','08/ II/TT-B/2025','2026-02-12','PT MCENERGY BIOMAS INDONESIA','JL. RAYA SURABAYA MALANG NO 12 PARENTINAP PAREREJO PURWODADI PASURUAN','JL. RAYA SURABAYA MALANG NO 12 PARENTINAP PAREREJO PURWODADI PASURUAN BAPAK RIDWAN 0817 7576 6704','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,39399999.00,0.00,NULL,'Belum Bayar','',30),('mooh53g1nj6o','10/ II/TT-PBS/2026','2026-02-14','CV. PANGAN BERKAH SENTOSA','JL. CENDRAWASIH PLOSOGENENG KEC JOMBANG KAB JOBANG 61416','JL. CENDRAWASIH PLOSOGENENG KEC JOMBANG KAB JOBANG 61416','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,7500000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g1zjm1','09/ II/TT-BFI/2026','2026-02-11','BIOSEVEN FIBERGLAS INDONESIA','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','JL.STASIUN KOTA KOMPLEK SEMUT INDAH BLOK D25-26 087878767686 PT. BIOSESVEN (TUJUAN MAKASAR)','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,14340090.00,0.00,NULL,'Belum Bayar','',30),('mooh53g1zvfo','12/ II/TT-M/2026','2026-02-12','BAPAK MARIYANTO','-','BAPAK NURAHMAN - PADANG PELASAN AIR PERIUKAN KAB. SELUMA BENGKULU 38881','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,24600000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g266nv','04/ II/TT-BFI/2026','2026-02-05','BIOSEVEN FIBERGLAS INDONESIA','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,5550000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g2gg0z','02/ II/TT-PMI/2026','2026-02-09','PT. PASIFIK MASAMI INDONESIA','JL. RAYA SITUBONDO NO KM 12 SELOGIRI KETAPANG BANYUWANGI','JL. RAYA SITUBONDO NO KM 12 SELOGIRI KETAPANG BANYUWANGI','258/PMI-PO/BWI.II/2026','PAYMENT 30hari','',NULL,NULL,'exclude',0.00,0.00,15961800.00,0.00,NULL,'Belum Bayar','',30),('mooh53g2nbup','03/ II/TT-BFI/2026','2026-02-05','BIOSEVEN FIBERGLAS INDONESIA','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','JL. SUTOREJO SELATAN BLOK 8 NO 42 RT/RW 06/08 KEL DUKUH SUTOREJO MULYOREJO SURABAYA 60113','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,14800000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g2zej9','06/ II/TT-D/2026','2026-02-07','BAPAK DADANG','-','-','-','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,6105000.00,0.00,NULL,'Belum Bayar','',30),('mooh53g3s28e','01/ II/TT-/20WDM25','2026-02-03','PT. WAHYU DAYA MULIA','DUSUN BAKALAN RT02 RW01 DESA PULOREJO KEC NGORO JOMBANG','DUSUN BAKALAN RT02 RW01 DESA PULOREJO KEC NGORO JOMBANG','PO2026020031','PAYMENT','',NULL,NULL,'exclude',0.00,0.00,11500000.00,0.00,NULL,'Belum Bayar','',30),('mopwe3xk02ju','TEST/STATS/001','2026-05-03','Test Customer','','','0','','',NULL,NULL,'none',0.00,0.00,100000000.00,0.00,NULL,'Sementara','',0),('mopwmbk9bq4i','TEST/STATS/002','2026-05-03','Test Customer','','','0','','',NULL,NULL,'none',0.00,0.00,100000000.00,0.00,NULL,'Belum Bayar','',30),('nota1','21/ IV /TT-MBI/2026','2026-04-15','PT MCENERGY BIOMAS INDONESIA','JL. RAYA SURABAYA MALANG NO 12, PASURUAN (PAK RIDWAN 081775766704)','JL. RAYA SURABAYA MALANG NO 12, PASURUAN','0','','BERAU',NULL,NULL,'include',0.00,0.00,0.00,0.00,NULL,'Belum Bayar','',30);
/*!40000 ALTER TABLE `nota` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nota_items`
--

DROP TABLE IF EXISTS `nota_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nota_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nota_id` varchar(50) DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `deskripsi` text,
  `jasa_rakit` decimal(15,2) DEFAULT '0.00',
  `price` decimal(15,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `has_specs` tinyint(1) DEFAULT '0',
  `specs` text,
  PRIMARY KEY (`id`),
  KEY `nota_id` (`nota_id`),
  CONSTRAINT `nota_items_ibfk_1` FOREIGN KEY (`nota_id`) REFERENCES `nota` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nota_items`
--

LOCK TABLES `nota_items` WRITE;
/*!40000 ALTER TABLE `nota_items` DISABLE KEYS */;
INSERT INTO `nota_items` VALUES (1,'mopwe3xk02ju',1.00,'PCS','','Test Item for Statistics Logic Verification',0.00,100000000.00,100000000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\"}'),(2,'mopwmbk9bq4i',1.00,'PCS','','Test Item for Belum Bayar Status Verification',0.00,100000000.00,100000000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\"}'),(3,'mon67apz3a2w',1.00,'PCS','','Flugo sda wdfsdfggggggggggggg',0.00,10000000.00,10000000.00,1,'{\"capacity\":\"300 l\",\"pressure\":\"400 j okowk okow\",\"inoutlet\":\"500 je\",\"power\":\"1000\"}'),(4,'mofnr6d89kwq',4.00,'Unit','-','Flange Stainless 2\"',0.00,290000.00,1160000.00,1,'{\"capacity\":\"400 M3\\/jam\",\"pressure\":\"\\t9 bar\",\"inoutlet\":\"150-125 mm Pn 16\",\"power\":\"220 hp Rpm 1400 380\\/660 v 50 hz Onthe baseplate\",\"material\":\"Body ci Impeller ci Gland seal Ready stok\"}'),(5,'mofnr6d89kwq',1.00,'Unit','-','CAMLOCK STAINLESS 2\" A',0.00,385000.00,385000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(6,'mofnr6d89kwq',2.00,'Unit','-','CAMLOCK STAINLESS 1,5\" A',0.00,328000.00,656000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(7,'mofnr6d89kwq',2.00,'PCS','-','TUTUP CAMLOCK 2\" dc',0.00,370000.00,740000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(8,'mofnr6d89kwq',2.00,'PCS','-','TUTUP CAMLOCK 1,5\" dc',0.00,330000.00,660000.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(9,'nota1',1.00,'PCS','','80-1 SS JEPANG',0.00,13900000.00,13900000.00,0,'[]'),(10,'nota1',1.00,'PCS','','80-2 SS JEPANG',0.00,17498000.00,17498000.00,0,'[]'),(11,'nota1',4.00,'PCS','','CONNETING RP 80-II',0.00,350000.00,1400000.00,0,'[]'),(12,'mooh53fxoh15',1.00,'SET','','85-75KW | ENGINE SET DAILY TANK | PANEL CONTROL POMPA 8-6\" | 459-500m3/JAM 3bar | MATERIAL POMPA ALL SS 316 | SESUAI SKIR KONTRUKSI PIPA DIBAWAH',0.00,389000000.00,389000000.00,0,'[]'),(13,'mooh53fxoh15',2.00,'LJR','','HOSE SUCTION 8\" | PER LONJOR 7mtr',0.00,29000000.00,58000000.00,0,'[]'),(14,'mooh53fxoh15',1.00,'','','HOSE PIPIH 6\" PANJANG 100meter',0.00,45000000.00,45000000.00,0,'[]'),(15,'mooh53fz3qhr',1.00,'UNIT','','POMPA FLUGO FA 40-26 ALL SS 316 ( ONLY PUMP )',0.00,30180180.18,30180180.18,0,'[]'),(16,'mooh53fz3qhr',1.00,'UNIT','','POMPA FLUGO FES 40 IN 65 OUT 40',0.00,31081081.08,31081081.08,0,'[]'),(17,'mooh53fz3qhr',1.00,'UNIT','','POMPA GEAR FIG 160',0.00,44144144.14,44144144.14,0,'[]'),(18,'mooh53g160mk',1.00,'UNIT','','KOPLE SET DENGAN POMPA HYDRANT AURORA',0.00,124000000.00,124000000.00,0,'[]'),(19,'mooh53fzfqgw',1.00,'UNIT','','BLOWER JAMUR 16\"/3PHASE/ 1500RPM ( RAIS )',0.00,9900000.00,9900000.00,0,'[]'),(20,'mooh53fxmk3p',1.00,'SET','','PUMP LOBE FLUGO 7,5KW',0.00,94525000.00,94525000.00,0,'[]'),(21,'mooh53fxttpd',1.00,'SET','','PUMP LOBE FLUGO 7,5KW',0.00,94525000.00,94525000.00,0,'[]'),(22,'mooh53fvql4h',1.00,'UNIT','','CENTRIFUGAL PUMP | SEMINOPEN POMPS 3HP 380V 2\" | HIGH ABRASIF AND CRROSIF PUMP 15M3/JAM | 25MTR 40-32MM',0.00,5855855.86,5855855.86,0,'[]'),(23,'mooh53fvql4h',1.00,'UNIT','','CENTRIFUGAL PUMP | 3KW SET PANGKON',0.00,17027027.00,17027027.00,0,'[]'),(24,'mooh53fxsz9d',1.00,'UNIT','','VACUUM PUMP 2FBV5131 | 11KW 380V 33MBAR',0.00,37837837.84,37837837.84,0,'[]'),(25,'mooh53fy19es',4.00,'SET','','MOTOR 18.5KW 2Hhp 1400rpm FOOT MOUNT | HEAVY DUTY',0.00,10360360.36,41441441.44,0,'[]'),(26,'mooh53fylnh1',4.00,'UNIT','','DOSING PUMP 2LITER/JAM PVC',0.00,1527027.00,6108108.00,0,'[]'),(27,'mooh53fycapc',2.00,'BTG','','STUD BOLD 3/4\"',0.00,450000.00,900000.00,0,'[]'),(28,'mooh53fycapc',16.00,'PCS','','MUR BAJA 3/4\"',0.00,18000.00,288000.00,0,'[]'),(29,'mooh53fyrtsq',2.00,'UNIT','','POMPA DOSING | MERK  : FLUGO | TIPE  : FMP 1002 | KAPASITAS  : 10 hp | PRESSURE : 2 bar | POWER : 28 watt/220 V/ 50Hz',0.00,3800000.00,7600000.00,0,'[]'),(30,'mooh53fz5af2',1.00,'UNIT','','JOCKY FVM 2-160  FLANGE 1\"  2UNIT',0.00,5585585.59,5585585.59,0,'[]'),(31,'mooh53fzb2g9',20.00,'PCS','','BAUT MUR M20x170mm | DRAT 2,5MATRIX KEPALA BAUT 30MM',0.00,39000.00,780000.00,0,'[]'),(32,'mooh53fzb0hs',4.00,'PCS','','MEMBRAN PNEMATIC DIAPHRAGM PUMP | QBY3 50L P/N 04-1010-52  ( MTRR )',0.00,2790000.00,11160000.00,0,'[]'),(33,'mooh53fzi3sx',1.00,'PCS','','RUBBER ACCUMULATOR 1ea',0.00,14950000.00,14950000.00,0,'[]'),(34,'mooh53g0zrq1',1.00,'SET','','DINAMO + POMPA CENTRIFUGAL EBARA | FSHA 3\"x2.5\" 7,5KW 2900RPM    ( RAGA )',0.00,19500000.00,19500000.00,0,'[]'),(35,'mooh53g046bz',2.00,'SET','','CENTRIFUGAL FLUGO CAP 60-80M3/JAM | PRES 1-2BAR IN-OUT 80-65MM | POWER 11KW 380V SET | ON THE BASEPLATE',0.00,15765765.77,31531531.54,0,'[]'),(36,'mooh53g09kyh',2.00,'PCS','','FILTER DAB V700',0.00,7160360.36,14320720.72,0,'[]'),(37,'mooh53g08cn3',2.00,'PCS','','M SEAL LOBE',0.00,4600000.00,9200000.00,0,'[]'),(38,'mooh53fzr1y1',1.00,'UNIT','','POMPA DORONG CENTRIFUGAL | DB 402 1C OUTPUT 2\" 220V/380V   ( SARF )',0.00,1999000.00,1999000.00,0,'[]'),(39,'mooh53g0p841',2.00,'PCS','','GLOVE VALVE 25mm',0.00,1500000.00,3000000.00,0,'[]'),(40,'mooh53g1nj6o',1.00,'UNIT','','2.2KW 380V IN 40mm OUT 32mm ALL SS 304',0.00,6756756.76,6756756.76,0,'[]'),(41,'mooh53g0bv3u',14.00,'SET','','FERULLE SANITARY COMPLETE 1\"',0.00,270270.00,3783780.00,0,'[]'),(42,'mooh53g0bv3u',6.00,'SET','','FERULLE SANITARY COMPLETE 3/4\"',0.00,256500.00,1539000.00,0,'[]'),(43,'mooh53g1e86r',1.00,'UNIT','','45 KW 60HP 1400 RPM TANPA FLANGE | TANPA GEARBOX',0.00,18468468.00,18468468.00,0,'[]'),(44,'mooh53g1e86r',1.00,'UNIT','','3HP 3PHASE 3000RPM VIBRATOR MOTOR FOOT',0.00,17027027.03,17027027.03,0,'[]'),(45,'mooh53g1zvfo',1.00,'UNIT','','55KW 75HP 2900RPM FRAME 250 M-2 | FOOT BB HEAFDUTY',0.00,22162162.16,22162162.16,0,'[]'),(46,'mooh53g1zjm1',56.00,'PCS','','WATER MUR PVC 2\"',0.00,125000.00,7000000.00,0,'[]'),(47,'mooh53g1zjm1',22.00,'PCS','','CHEK VALVE PVC 2\"',0.00,240000.00,5280000.00,0,'[]'),(48,'mooh53g1zjm1',5.00,'PCS','','WATER MUR PVC 1\"',0.00,31000.00,155000.00,0,'[]'),(49,'mooh53g1zjm1',4.00,'PCS','','OVER SOCK PVC 3-2',0.00,121000.00,484000.00,0,'[]'),(50,'mooh53g10oz5',4.00,'PCS','','CHEK VALVE DRAT KUNINGAN 2\"',0.00,225000.00,900000.00,0,'[]'),(51,'mooh53g10oz5',4.00,'PCS','','DOUBLE NEPEL 2\"',0.00,47000.00,188000.00,0,'[]'),(52,'mooh53g10oz5',4.00,'PCS','','GEAR PLASTUK 1OT 20 CM',0.00,1900000.00,7600000.00,0,'[]'),(53,'mooh53g10oz5',6.00,'MTR','','RANTAI PLASTIK LEBAR 3CM',0.00,1500000.00,9000000.00,0,'[]'),(54,'mooh53g10oz5',3.00,'PCS','','POMPA CELUP CUTTER 1HP 2\" 220V MANUAL',0.00,2290000.00,6870000.00,0,'[]'),(55,'mooh53g2gg0z',1.00,'UNIT','','WATER METER 3\" MERK ONDA',0.00,2480000.00,2480000.00,0,'[]'),(56,'mooh53g2gg0z',4.00,'UNIT','','WATER METER 2\" MERK ONDA',0.00,2190000.00,8760000.00,0,'[]'),(57,'mooh53g2gg0z',1.00,'UNIT','','WATER METER 1/2\" MERK AMICO',0.00,1650000.00,1650000.00,0,'[]'),(58,'mooh53g2gg0z',1.00,'UNIT','','RAGUM/CATOK BESI 6\"',0.00,900000.00,900000.00,0,'[]'),(59,'mooh53g2gg0z',1.00,'DUS','','SKRUP KHUSUS GYPSUM',0.00,590000.00,590000.00,0,'[]'),(60,'mooh53g2zej9',100.00,'PCS','','BOLA BESI 1Kg',0.00,55000.00,5500000.00,0,'[]'),(61,'mooh53g266nv',3.00,'UNIT','','POMPA CELUP CUTTER 2\" 10M3/JAM 10mtr 0,75KW | 220V',0.00,1666667.00,5000001.00,0,'[]'),(62,'mooh53g2nbup',1.00,'UNIT','','BV 80 8BAR KOMPRESOR PISTON BISON 3HP | 3PHASE',0.00,13333333.00,13333333.00,0,'[]'),(63,'mooh53fylat5',1.00,'SET','','ROOT BLOWER 3KW 50MM | ALLIANCE HEAVY DUTY',0.00,22522522.52,22522522.52,0,'[]'),(64,'mooh53fylat5',10.00,'PCS','','DIFF 10\"',0.00,153153.15,1531531.50,0,'[]'),(65,'mooh53g3s28e',2.00,'UNIT','','CENTRIFUGAL PRESSURE BLOWER | SCIP 950W ; 2640rpm,100MM,ST PRES; 2100pa',0.00,5180180.00,10360360.00,0,'[]');
/*!40000 ALTER TABLE `nota_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` varchar(50) NOT NULL,
  `product_id` varchar(50) DEFAULT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT '1.00',
  `satuan` varchar(50) DEFAULT 'PCS',
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `spesifikasi` text,
  `catatan` text,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `product_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` varchar(50) NOT NULL,
  `nomor_order` varchar(100) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `ongkir` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `alamat_pengiriman` text NOT NULL,
  `kota_pengiriman` varchar(100) NOT NULL,
  `ekspedisi` varchar(100) DEFAULT 'Ekspedisi Rekanan CV. Tidar Teknik',
  `metode_pembayaran` varchar(100) NOT NULL,
  `status_pembayaran` enum('menunggu_pembayaran','sudah_bayar','terverifikasi','dibatalkan') DEFAULT 'menunggu_pembayaran',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `waktu_bayar` datetime DEFAULT NULL,
  `status_pesanan` enum('pending','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
  `no_resi` varchar(100) DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_order` (`nomor_order`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penawaran`
--

DROP TABLE IF EXISTS `penawaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penawaran` (
  `id` varchar(50) NOT NULL,
  `nomor` varchar(100) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `customer` varchar(255) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `att` varchar(100) DEFAULT NULL,
  `sub_judul` varchar(255) DEFAULT NULL,
  `note` text,
  `show_total` tinyint(1) DEFAULT '1',
  `total` decimal(15,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'Pending',
  `ppn_mode` varchar(20) DEFAULT 'none',
  `disc` decimal(15,2) DEFAULT '0.00',
  `dp` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penawaran`
--

LOCK TABLES `penawaran` WRITE;
/*!40000 ALTER TABLE `penawaran` DISABLE KEYS */;
INSERT INTO `penawaran` VALUES ('185ol796wmoojeupl','','2026-02-25','PT MITRA PRIMA ENVIRO.','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,3800000.00,'Pending','none',0.00,0.00),('1u37z0799moojeupl','','2026-02-04','PT. BALATIF','','','','READY STOCK, EXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,16900000.00,'Pending','none',0.00,0.00),('5rq8llbeemoojeupm','','2026-02-21','PT. WAHYU DAYA MULIA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,5900000.00,'Pending','none',0.00,0.00),('614zkdhl4moojeupl','','2026-02-06','SMIP','','','','DISC 35%\nEXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,23000000.00,'Pending','none',0.00,0.00),('7cf6hr1yamoojeupk','','2026-02-25','RIOTA JAYA LESTARI','','','','EXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,492000000.00,'Pending','none',0.00,0.00),('7gqjcmi2rmoojeupl','','2026-02-18','BERDIKARI','','','','MATERIAL BESI , EXCLUDE PPN, BARANG READY 2 HR\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,9520000.00,'Pending','none',0.00,0.00),('c9jf8yf4kmoojeupl','','2026-02-23','PT. PULAU BIRU MANOKWARI','','','','EXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,145767600.00,'Pending','none',0.00,0.00),('er9r7zoq9mop5o7if','','2026-01-23','','','','','BARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,0.00,'Pending','none',0.00,0.00),('f3v0sp3crmop5o7ig','','2026-01-29','PT. MCENERGY BIOMAS','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,20500000.00,'Pending','none',0.00,0.00),('g0pdhkp98mop5o7ih','','2026-01-12','PT. WAHYU DAYA MULIA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,2690000.00,'Pending','none',0.00,0.00),('geghsi1ammoojeupl','','2026-02-13','CV. MINA BERKAH SEJAHTERA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,35589000.00,'Pending','none',0.00,0.00),('h532o54ofmop5o7ig','','2026-01-23','Bpk. Hermawan','','','','BARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,64800000.00,'Pending','none',0.00,0.00),('jaoosozyrmop5o7ii','','2026-01-05','PT. METRO MESIN MENDUNIA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,14780000.00,'Pending','none',0.00,0.00),('jxok6vgkwmoojeupm','','2026-02-02','PT. WAHYU DAYA MULIA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,5750000.00,'Pending','none',0.00,0.00),('k9bfg26l1mop5o7ii','','2026-01-07','BAPAK. JULIYONO','','','','EXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,19950000.00,'Pending','none',0.00,0.00),('kmmjo3zr2moojeupk','','2026-02-25','PT. MULTI INDO ABADI','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,117000000.00,'Pending','none',0.00,0.00),('mpxilxyb0wb0','2501/SP-CUST/TT/VI/26','2026-06-03','CV. Tidar Teknik','','','','',1,207000.00,'Pending','none',0.00,0.00),('mpxisbic5swd','2502/SP-CUST/TT/VI/26','2026-06-03','CV. Tidar Teknik','','','','',1,207000.00,'Pending','exclude',10000.00,5000.00),('ngzjvggufmop5o7ii','','2026-01-08','PT. INDO MINA BAHARI','','','','HARGA INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,2575200.00,'Pending','none',0.00,0.00),('olu859d8bmop5o7ig','','2026-01-29','PT. MCENERGY BIOMAS','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,18900000.00,'Pending','none',0.00,0.00),('qi3f2n2uumop5o7ih','','2025-01-15','PT. GAPURA LIQUA SOLUTIONS','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT DAN DAPAT BERUBAH SEWAKTU WAKTU\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,9300000.00,'Pending','none',0.00,0.00),('rki911709moojeupl','','2026-02-14','CV. OLEINDO AMANA SEJAHTERA INDONESIA','','','','INCLUDE\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,35000000.00,'Pending','none',0.00,0.00),('tzd8pg9hqmop5o7ih','','2026-01-10','PT. WAHYU DAYA MULIA','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,2900000.00,'Pending','none',0.00,0.00),('uupnfrpufmoojeupm','','2026-02-02','PT. PASIFIK MASAMI INDONESIA','','','','EXCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,6957000.00,'Pending','none',0.00,0.00),('v017uwil3moojeupk','2500/SP-WDM/TT/V/26','2026-02-28','PT. WAHYU DAYA MULIA','','','','READY  DAN HARGA INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,25400000.00,'Pending','none',0.00,0.00),('w4vfx0a2xmpxdt96d','0808/SP-/TT/VIII/2025','2025-08-22','PT. PAGRINDO JAYA SENTOSA','','','','BARANG DAN HARGA TIDAK MENGIKAT SEWAKTU WAKTU DAPAT BERUBAH',1,279398100.00,'Pending','exclude',0.00,0.00),('wjcbw597hmop5o7ih','','2025-01-21','PT. SUKA SARI','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,65700000.00,'Pending','none',0.00,0.00),('xtf1pfh98moojeupk','','2026-02-27','PT. CANADA GREEN GATE','','','','READY\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,94525000.00,'Pending','none',0.00,0.00),('xzf8idzh6mop5o7ii','','2025-01-05','PT. INDO MINA BAHARI','','','','INCLUDE PPN\nBARANG DAN HARGA TIDAK MENGIKAT\nDemikian Penawaran Harga kami sampaikan, sambil menunggu kabar\nselanjutnya dari  Bapak / Ibu, atas perhatiannya kami ucapkan terima kasih.',1,3650000.00,'Pending','none',0.00,0.00);
/*!40000 ALTER TABLE `penawaran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penawaran_items`
--

DROP TABLE IF EXISTS `penawaran_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penawaran_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `penawaran_id` varchar(50) DEFAULT NULL,
  `deskripsi` text,
  `qty` decimal(10,2) DEFAULT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `note_item` text,
  `has_specs` tinyint(1) DEFAULT '0',
  `specs` text,
  PRIMARY KEY (`id`),
  KEY `penawaran_id` (`penawaran_id`),
  CONSTRAINT `penawaran_items_ibfk_1` FOREIGN KEY (`penawaran_id`) REFERENCES `penawaran` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penawaran_items`
--

LOCK TABLES `penawaran_items` WRITE;
/*!40000 ALTER TABLE `penawaran_items` DISABLE KEYS */;
INSERT INTO `penawaran_items` VALUES (1,'mpxisbic5swd','Test Item',2.00,'UNIT',100000.00,200000.00,'',0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(2,'mpxilxyb0wb0','Barang A',2.00,'UNIT',100000.00,200000.00,'',0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(3,'v017uwil3moojeupk','SEMINOPEN POMPA\n3hp 380v 2\"\nHIGH ABRASIF AND CORROSIF PUMP',1.00,'UNIT',6500000.00,6500000.00,'',1,'{\"capacity\":\"400 M3\\/jam\",\"pressure\":\"9 bar\",\"inoutlet\":\"150-125 mm Pn 16\",\"power\":\"220 hp Rpm 1400 380\\/660 v 50 hz Onthe baseplate\",\"material\":\"Body ci Impeller ci Gland seal Ready stok\"}'),(4,'v017uwil3moojeupk','15M3/JAM 25MTR 40-32mm 3KW SET PANGKON',1.00,'UNIT',18900000.00,18900000.00,'',0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(5,'xtf1pfh98moojeupk','PUMP LOBE FLUGO\nDELIVERY SCHEDULE  : ASAP\nBRAND /TYPE                  : FLUGO / FFL 35\nCAP                                     : 10 M3 / HOURS\nIN/OUT                               : 1,5 INCH\nPOWER                              : 7,5KW - 10HP  - 400RPM\nBODY                                 : STAINLESS SS 316',1.00,'UNIT',94525000.00,94525000.00,'',0,'[]'),(6,'185ol796wmoojeupl','POMPA DOSING\nMERK : FLUGO\nTIPE : FMP 1003\nKAPASITAS : 10hp\nPRESSURE : 2bar\nPOWER : 28watt / 220V',1.00,'UNIT',3800000.00,3800000.00,'',0,'[]'),(7,'7cf6hr1yamoojeupk','85-75KW\nENGINE SET DAILY TANK\nPANEL CONTROL POMPA 8-6\"\n459-500m3/JAM 3bar\nMATERIAL POMPA ALL SS 316\nSESUAI SKIR KONTRUKSI PIPA DIBAWAH',1.00,'UNIT',389000000.00,389000000.00,'',0,'[]'),(8,'7cf6hr1yamoojeupk','HOSE SUCTION 8\"\nPER LONJOR 7mtr',2.00,'UNIT',29000000.00,58000000.00,'',0,'[]'),(9,'7cf6hr1yamoojeupk','HOSE PIPIH 6\" PANJANG 100meter',1.00,'UNIT',45000000.00,45000000.00,'',0,'[]'),(10,'kmmjo3zr2moojeupk','POMPA FLUGO FA 40-26 ALL SS 316 (POMPA SAJA)',1.00,'UNIT',33500000.00,33500000.00,'',0,'[]'),(11,'kmmjo3zr2moojeupk','POMPA SEMINOPEN FCK 40 ALL SS 304',1.00,'UNIT',34500000.00,34500000.00,'',0,'[]'),(12,'kmmjo3zr2moojeupk','POMPA GEAR FIG 160',1.00,'UNIT',49000000.00,49000000.00,'',0,'[]'),(13,'c9jf8yf4kmoojeupl','HYDRANT ELEKTRIK POMPA 3PHASE\n100-80 EBARA 500GPM 8BAR 45KW',1.00,'UNIT',54560000.00,54560000.00,'',0,'[]'),(14,'c9jf8yf4kmoojeupl','HYDRANT JOCKEY POMPA 3PHASE\nCR 5-15 MERK GROUNFOS',1.00,'UNIT',27445000.00,27445000.00,'',0,'[]'),(15,'c9jf8yf4kmoojeupl','TRANSFER POMPA 1PHASE JPa 5-61 MERK GROUNFOS',2.00,'UNIT',14225750.00,28451500.00,'',0,'[]'),(16,'c9jf8yf4kmoojeupl','POMPA BOOSTER 1PHASE OUTOMATIS JP 4-54\nMERK GROUFOS',2.00,'UNIT',6160000.00,12320000.00,'',0,'[]'),(17,'c9jf8yf4kmoojeupl','POMPA HALAMAN 1PHASE OUTOMATIS\nJP 4-47 MERK GROUNFOS',1.00,'UNIT',5402100.00,5402100.00,'',0,'[]'),(18,'c9jf8yf4kmoojeupl','PRESSURE TANG 500 LITER',1.00,'UNIT',17589000.00,17589000.00,'',0,'[]'),(19,'5rq8llbeemoojeupm','VERTIKAL PUMP 5M3/JAM 2.5BAR 1HP 3PHASE\nFLUGO + ABB',1.00,'UNIT',5900000.00,5900000.00,'',0,'[]'),(20,'7gqjcmi2rmoojeupl','GEAR PUMP\n50 LTR , 220 V',1.00,'UNIT',2950000.00,2950000.00,'',0,'[]'),(21,'7gqjcmi2rmoojeupl','GEAR PUMP\n75 LTR , 220 V',1.00,'UNIT',3190000.00,3190000.00,'',0,'[]'),(22,'7gqjcmi2rmoojeupl','GEAR PUMP\n100 LTR , 220 V',1.00,'UNIT',3380000.00,3380000.00,'',0,'[]'),(23,'rki911709moojeupl','CENTRIFUGAL FLUGO\nCAP 60-80M3/JAM PRESS 1-2BAR\nINOUT 80-65MM\nPOWER 11KW 380V SET\nONTHE BASEPLATE',2.00,'UNIT',17500000.00,35000000.00,'',0,'[]'),(24,'geghsi1ammoojeupl','2HP 380V 20M3 MAKSIMAL 12MTR\nALL SS 304',1.00,'UNIT',13500000.00,13500000.00,'',0,'[]'),(25,'geghsi1ammoojeupl','FLOWTER MINYAK 2\" FLANGE ACURASI 0.1%\nBELUM TERA ( KALAU DIPERLUKAN ) 2HARI',1.00,'UNIT',22089000.00,22089000.00,'',0,'[]'),(26,'614zkdhl4moojeupl','RUBBER ACCUMULATOR  1 ea',1.00,'UNIT',23000000.00,23000000.00,'',0,'[]'),(27,'1u37z0799moojeupl','GEARPUMP MANUAL\nALL SS 304',1.00,'UNIT',16900000.00,16900000.00,'',0,'[]'),(28,'jxok6vgkwmoojeupm','CENTRIFUGAL PRESSURE BLOWER\nSCIP 950w : 2640rpm, 100mm, ST PRES 2100pa',1.00,'UNIT',5750000.00,5750000.00,'',0,'[]'),(29,'uupnfrpufmoojeupm','RAGUN CATOK',1.00,'UNIT',900000.00,900000.00,'',0,'[]'),(30,'uupnfrpufmoojeupm','SKRUP GYPSUN PER DUS',1.00,'UNIT',590000.00,590000.00,'',0,'[]'),(31,'uupnfrpufmoojeupm','ONDA WATER METER 3\"',1.00,'UNIT',2480000.00,2480000.00,'',0,'[]'),(32,'uupnfrpufmoojeupm','ONDA WATER METER 2\"',1.00,'UNIT',2190000.00,2190000.00,'',0,'[]'),(33,'uupnfrpufmoojeupm','ONDA WATER METER 1/2\"',1.00,'UNIT',797000.00,797000.00,'',0,'[]'),(34,'f3v0sp3crmop5o7ig','45KW 60HP 1400RPM',0.00,'UNIT',0.00,0.00,'',0,'[]'),(35,'f3v0sp3crmop5o7ig','TANPA FLANGE TANPA GEARBOX',1.00,'UNIT',20500000.00,20500000.00,'',0,'[]'),(36,'olu859d8bmop5o7ig','VIBRATOR MOTOR FOOT',0.00,'UNIT',0.00,0.00,'',0,'[]'),(37,'olu859d8bmop5o7ig','3HP 3PHS 3000RPM',1.00,'UNIT',18900000.00,18900000.00,'',0,'[]'),(38,'h532o54ofmop5o7ig','Pompa panel Surya\n12 m3/jam\n32 mtr\n2.2 kW\nCw panel Surya\n250 wp 3 lembar\nInverter control 1000 wat\nKabel+ box panel+ asesories klemp\nBatt 120 AH 4 pcs',1.00,'UNIT',64800000.00,64800000.00,'',0,'[]'),(39,'g0pdhkp98mop5o7ih','GC 50\nPOMPA ONLY',1.00,'UNIT',2690000.00,2690000.00,'',0,'[]'),(40,'tzd8pg9hqmop5o7ih','GEAR BOX 2HP\nRATIO 1:59  FOOT MOUNTING\nGBOX ONLY KAKI',1.00,'UNIT',2900000.00,2900000.00,'',0,'[]'),(41,'ngzjvggufmop5o7ii','DRUM PUMP JEPANG + SELANG 6 meter',1.00,'UNIT',2575200.00,2575200.00,'',0,'[]'),(42,'k9bfg26l1mop5o7ii','POMPA BAHAN BAKAR SOLAR\nIN 65mm  OUT 40mm\nKAPASITAS 20M3/JAM\nPOWER 2HP EXPLOSION PROFF SERTIFIKAT\nON THE BASEPLATE   SET',1.00,'UNIT',19950000.00,19950000.00,'',0,'[]'),(43,'jaoosozyrmop5o7ii','BALL VALVE SANITARY 1 1/2\"  SS 316',2.00,'UNIT',4625000.00,9250000.00,'',0,'[]'),(44,'jaoosozyrmop5o7ii','BALL VALVE SANITARY 1\"  SS 316',2.00,'UNIT',2465000.00,4930000.00,'',0,'[]'),(45,'jaoosozyrmop5o7ii','FERULE SANITARY COMPLITE 1\"',2.00,'UNIT',300000.00,600000.00,'',0,'[]'),(46,'wjcbw597hmop5o7ih','CENTRIFUGAL PUMP CLOSE IMPELER\nKAPASITAS 15M3/JAM PRESSURE 2BAR\nTYPE 50-32 MATERIAL SS 316 MOTOR 3KW',1.00,'UNIT',35900000.00,35900000.00,'',0,'[]'),(47,'wjcbw597hmop5o7ih','1\"  DRAT 2.5M3 2HP 3PHASE 2BAR ALL SS 304\nSET PANGKON',1.00,'UNIT',29800000.00,29800000.00,'',0,'[]'),(48,'qi3f2n2uumop5o7ih','GEARBOX ARXF\nRASIO 2,9 AS 30mm FRAME 90 B5',1.00,'UNIT',4900000.00,4900000.00,'',0,'[]'),(49,'qi3f2n2uumop5o7ih','MOTOR 0,37 KW 750 RPM B5 3PHASE',1.00,'UNIT',1900000.00,1900000.00,'',0,'[]'),(50,'qi3f2n2uumop5o7ih','AGITATOR SS316 AS 25mm PANJANG 80CM',1.00,'UNIT',2500000.00,2500000.00,'',0,'[]'),(51,'xzf8idzh6mop5o7ii','POMPA CELUP 3PHASE 1HP',1.00,'UNIT',3650000.00,3650000.00,'',0,'[]'),(147,'w4vfx0a2xmpxdt96d','FLUGO PUMP+NPFA20 STANDART PANEL\nTYPE FLUGO FVM 5-18',1.00,'',16410000.00,16410000.00,'',1,'{\"capacity\":\"25USGPM\",\"pressure\":\"\",\"inoutlet\":\"DN32 / 1,25\",\"power\":\"3KW IE MOTOR\",\"material\":\"FULL SS 304/ MECHANICAL SEAL\"}'),(148,'w4vfx0a2xmpxdt96d','FLUGO ELECTRIC FIREPUMP + NFPA20 STANDARD PANEL\nTYPE FA 65/26\nP. SHAFT POW 50KW\nMOTOR POWER A-Y3E-280S-2 75KW ALLIANCE MOTORI\nCOUPLING FCL250\nINCLUDED SET ELECTRIC MOTOR, PUMP BASEPLATE,\nCOUPLING COVER, COUPLING\nPUMP SPEC BODY - CAST IRON\nIMPELLER - BRONZE\nSHAFT - SS\nSEAL - GP',1.00,'',68270000.00,68270000.00,'',1,'{\"capacity\":\"500 USGPM \",\"pressure\":\"8 BAR\",\"inoutlet\":\"80-65 mm\",\"power\":\"2900RPM\",\"material\":\"\"}');
/*!40000 ALTER TABLE `penawaran_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po`
--

DROP TABLE IF EXISTS `po`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po` (
  `id` varchar(50) NOT NULL,
  `nomor` varchar(100) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `note1` text,
  `note2` text,
  `alamat_kirim` text,
  `ppn_mode` varchar(20) DEFAULT NULL,
  `disc` decimal(15,2) DEFAULT '0.00',
  `dp` decimal(15,2) DEFAULT '0.00',
  `total` decimal(15,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'Pending',
  `catatan` text,
  `termin` int DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po`
--

LOCK TABLES `po` WRITE;
/*!40000 ALTER TABLE `po` DISABLE KEYS */;
INSERT INTO `po` VALUES ('moogvrrk5ytd','2051/TT-MTE/II/26','2026-02-27','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,9275000.00,'Belum Bayar','',30),('moogvrrket28','2053/TT-MTE/II/26','2026-02-28','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,35500000.00,'Belum Bayar','',30),('moogvrrlft1q','2050/TT-MTE/II/26','2026-02-27','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,8000000.00,'Belum Bayar','',30),('moogvrrlm58d','2047/TT-MTE/II/26','2026-02-27','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,53900000.00,'Belum Bayar','',30),('moogvrrlpksh','2045/TT-MTE/II/26','2026-02-27','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,4600000.00,'Belum Bayar','',30),('moogvrrm2dj9','2042/TT-MTE/II/26','2026-02-26','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,1960000.00,'Belum Bayar','',30),('moogvrrminzs','2041/TT-MTE/II/26','2026-02-26','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,39200000.00,'Belum Bayar','',30),('moogvrrmlbz7','2043/TT-MTE/II/26','2026-02-26','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,6000000.00,'Belum Bayar','',30),('moogvrrmzdzk','2044/TT-MTE/II/26','2026-02-26','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,21937000.00,'Belum Bayar','',30),('moogvrrn3z74','2036/TT-SWL/II/26','2026-02-23','PT. SUMBER WAJA LESTARI','','Note  : PAYMENT 14 HARI     Kepada Yth,','','','exclude',0.00,0.00,6856412.28,'Belum Bayar','',14),('moogvrrntf1i','2035/TT-TTI/II/26','2026-02-21','PT. TULIP TONATA INDONESIA','','Note  : PAYMENT CASH     Kepada Yth,','','','exclude',0.00,0.00,56940000.00,'Belum Bayar','',0),('moogvrrnx56w','2037/TT-MTE/II/26','2026-02-23','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,1840000.00,'Belum Bayar','',30),('moogvrrocyvw','2033/TT-IP/II/26','2026-02-19','PT. ISTANA POMPA','','PAYMENT 30 HARI     PT. ISTANA POMPA','','','none',0.00,0.00,9490000.00,'Belum Bayar','',30),('moogvrrogcrt','2035/TT-SSB/II/26','2026-02-19','PT. SURYA SEJAHTERA BERSAMA','','','','','none',0.00,0.00,15096900.00,'Belum Bayar','',30),('moogvrrojudm','2032/TT-YMM/II/26','2026-02-19','PT YAMAMAX INDONESIA','','PAYMENT CASH     PT YAMAMAX INDONESIA','','','none',0.00,0.00,1094400.00,'Belum Bayar','',0),('moogvrroycg7','2034/TT-MTE/II/26','2026-02-19','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,3870000.00,'Belum Bayar','',30),('moogvrrpa6fv','2001/TT-/II/26','2026-02-18','CV. KARYA JAYA','','PAYMENT 30HARI     CV. KARYA JAYA','','','none',0.00,0.00,105000000.00,'Belum Bayar','',30),('moogvrrpgaq0','2031/TT-MTE/II/26','2026-02-18','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,920000.00,'Belum Bayar','',30),('moogvrrpwl0m','2030/TT-/II/26','2026-02-18','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,18000000.00,'Belum Bayar','',30),('moogvrrq3idq','2021/TT-MTE/II/26','2026-02-13','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,5300000.00,'Belum Bayar','',30),('moogvrrq6wkp','2020/TT-MTE/II/26','2026-02-12','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,22100000.00,'Belum Bayar','',30),('moogvrrqaz5j','2022/TT-MTE/II/26','2026-02-13','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,100000.00,'Belum Bayar','',30),('moogvrrqc59j','2023/TT-MTE/II/26','2026-02-14','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,6600000.00,'Belum Bayar','',30),('moogvrrqf7qr','2018/TT-MTE/II/26','2026-02-11','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,31000000.00,'Belum Bayar','',30),('moogvrrr01vs','2011/TT-SWL/II/26','2026-02-06','PT. SUMBER WAJA LESTARI','','Note  : PAYMENT 14 HARI     Kepada Yth,','','','exclude',0.00,0.00,28210650.00,'Belum Bayar','',14),('moogvrrrc7ul','2012/TT-IS/II/26','2026-02-11','ISTANA POMPA','','PAYMENT 30HARI     ISTANA POMPA','','','none',0.00,0.00,43806150.00,'Belum Bayar','',30),('moogvrrrwzpw','2017/TT-MTE/II/26','2026-02-11','MEGA TEKNIK ENGINEERING','','PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,73800000.00,'Belum Bayar','',30),('moogvrrseedu','2009/TT-MTE/II/26','2026-02-06','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,2100000.00,'Belum Bayar','',30),('moogvrrseku3','2008/TT-MTE/II/26','2026-02-06','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,35150000.00,'Belum Bayar','',30),('moogvrrsiz4r','2010/TT-MTE/II/26','2026-02-06','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,77460000.00,'Belum Bayar','',30),('moogvrrsmhmw','2006/TT-MTE/II/26','2026-02-05','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,70004000.00,'Belum Bayar','',30),('moogvrrswn07','2007/TT-MTE/II/26','2026-02-05','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,19200000.00,'Belum Bayar','',30),('moogvrrt3ey0','2001/TT-SIS/II/26','2026-02-03','SEMERU INTI SUKSES','','','','','exclude',0.00,0.00,7840001.04,'Belum Bayar','',30),('moogvrrtvcr3','2005/TT-MTE/II/26','2026-02-05','MEGA TEKNIK ENGINEERING','','Note  : PAYMENT 30 HARI     Kepada Yth,','','','none',0.00,0.00,70690000.00,'Belum Bayar','',30),('mopkyjit9m3k','PO-2026050021','2026-05-03','PT. Wahyu Daya Mulia','JOMBANG','Payment 21 Hari','','Dikirim di Andik','exclude',0.00,0.00,2899999.32,'Belum Bayar','',30),('moplssafte8s','2054/TT-SUPP/V/26','2026-03-16','Mega Teknik Engineering','SURABAYA','PAYMENT 30 Hari','','','include',0.00,0.00,2450000.00,'Belum Bayar','Dikirim Di Andik',30),('twfco3tz0mopjk63b','1201/TT-/XII/25','2025-12-01','','','','','','none',0.00,0.00,0.00,'Belum Bayar','',30);
/*!40000 ALTER TABLE `po` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_items`
--

DROP TABLE IF EXISTS `po_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `po_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` varchar(50) DEFAULT NULL,
  `deskripsi` text,
  `tipe` varchar(100) DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `has_specs` tinyint(1) DEFAULT '0',
  `specs` text,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `po` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_items`
--

LOCK TABLES `po_items` WRITE;
/*!40000 ALTER TABLE `po_items` DISABLE KEYS */;
INSERT INTO `po_items` VALUES (1,'mopkyjit9m3k','Gearbox\nBWD1 90B5 Ration 1:59 (Foot Mounting, Flange Mounting)','Unit',1.00,2612612.00,2612612.00,0,'{\"capacity\":\"\",\"pressure\":\"\",\"inoutlet\":\"\",\"power\":\"\",\"material\":\"\"}'),(2,'moplssafte8s','BWD 1 1/59 2HP Foot Gbox Saja\n\n\nNotes: Dikirim di Andik','',1.00,2450000.00,2450000.00,0,'[]'),(3,'moogvrrket28','VACUM PUMP 2FBV5131 CI POWER 11 KW','',1.00,35500000.00,35500000.00,1,'{\"capacity\":\"400 M3\\/jam\",\"pressure\":\"9 bar\",\"inoutlet\":\"150-125 mm Pn 16\",\"power\":\" 220 hp Rpm 1400 380\\/660 v 50 hz Onthe baseplate\",\"material\":\"Body ci Impeller ci Gland seal Ready stok\"}'),(4,'moogvrrk5ytd','M2BAX ie2','',1.00,9275000.00,9275000.00,0,'[]'),(5,'moogvrrlft1q','ARX 79 1/3.08  FOOT UNTUK 10HP GBOX SAJA','',2.00,4000000.00,8000000.00,0,'[]'),(6,'moogvrrlm58d','FFL35','',1.00,53900000.00,53900000.00,0,'[]'),(7,'moogvrrlpksh','FMP 1002','',2.00,2300000.00,4600000.00,0,'[]'),(8,'moogvrrm2dj9','FCL 180','',4.00,490000.00,1960000.00,0,'[]'),(9,'moogvrrminzs','MOTOR ABU 18.5KW 1400 B3','',4.00,9800000.00,39200000.00,0,'[]'),(10,'moogvrrmlbz7','CKS 03','',4.00,1500000.00,6000000.00,0,'[]'),(11,'moogvrrmzdzk','FRB 50 SET','',1.00,17750000.00,17750000.00,0,'[]'),(12,'moogvrrmzdzk','ALLIANCE ABU 3KW 1400 B3','',1.00,2400000.00,2400000.00,0,'[]'),(13,'moogvrrmzdzk','SPB 150-2 TAPERLOCK 2012 AS 28MM','',1.00,587000.00,587000.00,0,'[]'),(14,'moogvrrmzdzk','DIFF 10\"','',10.00,120000.00,1200000.00,0,'[]'),(15,'moogvrrn3z74','DIAPHRAGM 04-1010-52','',4.00,1544237.00,6176948.00,0,'[]'),(16,'moogvrrnx56w','3KW 2900 FOOT BB','',1.00,1840000.00,1840000.00,0,'[]'),(17,'moogvrrntf1i','HYDRANT FIRE HOSE RUBBER DENGSN ALMUNIUM MACHINO COUPLING 2.5\"X20m TONATA','',4.00,1351351.35,5405405.41,0,'[]'),(18,'moogvrrntf1i','Y CONNECTION HYDRANT /2 HANDLE 2.5 X 1.5 X 1.5/ MACHINO','',4.00,1126126.13,4504504.50,0,'[]'),(19,'moogvrrntf1i','BOX HYDRANT C ( DENGAN KACA+KUNCI ) / 66 cm X 95 cm X 20 cm / 0.8 mm - MEREK TONATA','',4.00,1126126.13,4504504.50,0,'[]'),(20,'moogvrrntf1i','HYDRANT PILLAR IMPORT MCH MCH 4\" X 2.5\" X 2.5\" FLANGE 16 K','',4.00,4108108.11,16432432.43,0,'[]'),(21,'moogvrrntf1i','GUN NOZZLE JENIS PROTEK NHT 1.5\"+ APAPTOR MACHINO','',4.00,3220720.72,12882882.88,0,'[]'),(22,'moogvrrntf1i','HYDRANT FIRE HOSE RUBBER DENGAN ALUMUNIUM MACHINO COUPLING 1.5\" X 20 m - MEREK TONATA','',8.00,945945.95,7567567.57,0,'[]'),(23,'moogvrrocyvw','EBARA 80x65 H GP','',1.00,9490000.00,9490000.00,0,'[]'),(24,'moogvrrogcrt','DAB SAND FILTER V700 B','',2.00,7548450.00,15096900.00,0,'[]'),(25,'moogvrrojudm','DB 402 1.5HP','',1.00,1094400.00,1094400.00,0,'[]'),(26,'moogvrroycg7','ALLIANCE BB 7.5KW 2900','',1.00,3580000.00,3580000.00,0,'[]'),(27,'moogvrroycg7','FCL 140','',1.00,290000.00,290000.00,0,'[]'),(28,'moogvrrpwl0m','FA 65-13 M SEAL CI CI MS','',2.00,3400000.00,6800000.00,0,'[]'),(29,'moogvrrpwl0m','BB 11KW 2900 B3 FOOT','',2.00,5600000.00,11200000.00,0,'[]'),(30,'moogvrrpa6fv','ENGINE HYDRANT FAW 4DX 23 COPLE BASE PLATE','',1.00,105000000.00,105000000.00,0,'[]'),(31,'moogvrrpgaq0','FCL 160','',2.00,460000.00,920000.00,0,'[]'),(32,'moogvrrqc59j','FHM 8-50 2.2KW 380V','',1.00,6600000.00,6600000.00,0,'[]'),(33,'moogvrrqaz5j','FLANGE 10K 1\"','',2.00,50000.00,100000.00,0,'[]'),(34,'moogvrrq3idq','FVM 2-160','',1.00,5300000.00,5300000.00,0,'[]'),(35,'moogvrrq6wkp','55KW  75HP  2900RPM FRAME 250 M-2 FOOT AY3E BB','',1.00,22100000.00,22100000.00,0,'[]'),(36,'moogvrrqf7qr','AY3E 45 KW 4P B3','',1.00,15500000.00,15500000.00,0,'[]'),(37,'moogvrrqf7qr','AF T2/3200','',1.00,15500000.00,15500000.00,0,'[]'),(38,'moogvrrrc7ul','EBARA 80x65 FSHA MSEAL','',1.00,10739250.00,10739250.00,0,'[]'),(39,'moogvrrrc7ul','EBARA 150x125 FS4LA MSEAL','',1.00,33066900.00,33066900.00,0,'[]'),(40,'moogvrrrwzpw','ALLIANCE BB 75KW 1400 4pole','',1.00,28000000.00,28000000.00,0,'[]'),(41,'moogvrrrwzpw','KOPLING FCL 250','',1.00,1400000.00,1400000.00,0,'[]'),(42,'moogvrrrwzpw','ABB EXPROFF COO 15KW 2900 2pole FOOT','',1.00,43900000.00,43900000.00,0,'[]'),(43,'moogvrrrwzpw','KOPLING GECJ 38','',1.00,500000.00,500000.00,0,'[]'),(44,'moogvrrr01vs','TZ2/AAAAB/BNS/BN/ABN/0014','',2.00,12707500.00,25415000.00,0,'[]'),(45,'moogvrrseedu','ALL ABU 3HP 1400 B3','',1.00,2100000.00,2100000.00,0,'[]'),(46,'moogvrrseku3','DOSING GLS 120 PVC','',4.00,6300000.00,25200000.00,0,'[]'),(47,'moogvrrseku3','DOSING GMS 240/MA 265 PVC','',1.00,9950000.00,9950000.00,0,'[]'),(48,'moogvrrsiz4r','FRB 50','',4.00,17350000.00,69400000.00,0,'[]'),(49,'moogvrrsiz4r','SPB 150-2 TAPPER LOCK 2012 AS 24mm','',4.00,525000.00,2100000.00,0,'[]'),(50,'moogvrrsiz4r','V BELT SPB 1250','',4.00,90000.00,360000.00,0,'[]'),(51,'moogvrrsiz4r','AY3E 2HP 4P B3','',4.00,1400000.00,5600000.00,0,'[]'),(52,'moogvrrsmhmw','FRB 40','',4.00,15300000.00,61200000.00,0,'[]'),(53,'moogvrrsmhmw','ALL BB 2HP 1400 3PHASE B3','',4.00,1400000.00,5600000.00,0,'[]'),(54,'moogvrrsmhmw','TAPPER LOCK 1610 AS 24mm','',4.00,400000.00,1600000.00,0,'[]'),(55,'moogvrrsmhmw','PULLEY SPB 150-1','',4.00,305000.00,1220000.00,0,'[]'),(56,'moogvrrsmhmw','V BELT SPB 1250','',4.00,96000.00,384000.00,0,'[]'),(57,'moogvrrswn07','RING BLOWER 2HP 3PHASE','',4.00,4800000.00,19200000.00,0,'[]'),(58,'moogvrrtvcr3','FVM 10-2 3PHASE','',11.00,4990000.00,54890000.00,0,'[]'),(59,'moogvrrtvcr3','FVM 10-8 3PHASE','',2.00,7900000.00,15800000.00,0,'[]'),(60,'moogvrrt3ey0','CENTRIFUGAL PRESSURE BLOWER SIS-21/SCIP-950W','',2.00,3531532.00,7063064.00,0,'[]');
/*!40000 ALTER TABLE `po_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_requests`
--

DROP TABLE IF EXISTS `product_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_requests` (
  `id` varchar(50) NOT NULL,
  `nomor_request` varchar(100) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `product_id` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `spesifikasi` text,
  `qty` decimal(10,2) NOT NULL DEFAULT '1.00',
  `satuan` varchar(50) DEFAULT 'PCS',
  `catatan` text,
  `status` enum('pending','approved','rejected','ordered') DEFAULT 'pending',
  `harga_disetujui` decimal(15,2) DEFAULT NULL,
  `catatan_admin` text,
  `tanggal_review` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_request` (`nomor_request`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_requests_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_requests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_requests`
--

LOCK TABLES `product_requests` WRITE;
/*!40000 ALTER TABLE `product_requests` DISABLE KEYS */;
INSERT INTO `product_requests` VALUES ('req_test_01','REQ-TEST-001','1','PRD-004','ROLLER CHAIN 80-2 SS JEPANG','ANSI 80-2 SUS304',2.00,'BOX','Butuh segera untuk perbaikan mesin boiler','approved',17500000.00,'Stok ready di supplier Surabaya, estimasi kirim 2 hari setelah checkout.','2026-09-10 18:08:43','2026-09-10 11:08:43');
/*!40000 ALTER TABLE `product_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` varchar(50) NOT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `nama` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stok` int NOT NULL DEFAULT '0',
  `satuan` varchar(50) DEFAULT 'PCS',
  `spesifikasi` text,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL,
  `supplier_id` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('PRD-001','BRG-33A120','BEARING 33A120 ZWZ','Bearing & Bushing',1500000.00,15,'PCS','Brand: ZWZ | Type: Tapered Roller Bearing | Material: Chrome Steel | Origin: China High Grade','Bearing industri heavy duty cocok untuk beban radial dan aksial tinggi.',NULL,'1',1,'2026-09-10 11:03:24'),('PRD-002','BRG-NETRAL','BEARING 33A120 NETRAL','Bearing & Bushing',450000.00,8,'PCS','Type: Neutral Roller Bearing | OD: 120mm | Standard Quality','Bearing netral untuk aplikasi putaran sedang dengan beban normal.',NULL,'1',1,'2026-09-10 11:03:24'),('PRD-003','CHN-80-1SS','ROLLER CHAIN 80-1 SS JEPANG','Chain & Sprocket',13900000.00,5,'BOX','Size: ANSI 80-1 | Material: Stainless Steel SUS304 | Pitch: 25.4mm | Length: 10 FT / Box | Origin: Japan','Rantai industri stainless steel anti-korosi standar Jepang untuk pabrik biomassa & pengolahan makanan.',NULL,'1',1,'2026-09-10 11:03:24'),('PRD-004','CHN-80-2SS','ROLLER CHAIN 80-2 SS JEPANG','Chain & Sprocket',17498000.00,0,'BOX','Size: ANSI 80-2 (Double Strand) | Material: Stainless Steel SUS304 | Pitch: 25.4mm | Origin: Japan','Rantai ganda (double strand) stainless steel untuk transmisi daya beban sangat berat.',NULL,'1',1,'2026-09-10 11:03:24'),('PRD-005','ELC-SC-CY','SPEED CONTROL CHANG YI 220V','Electrical & Automation',4000000.00,3,'UNIT','Input: AC 220V 50/60Hz | Output Power: Max 90W | Speed Range: 90-1400 RPM | Controller: Digital Display','Kontrol kecepatan motor AC presisi tinggi merek Chang Yi.',NULL,'1',1,'2026-09-10 11:03:24'),('PRD-006','CON-RP-80II','CONNECTING LINK RP 80-II','Chain & Sprocket',350000.00,0,'PCS','Type: Connecting Link / Master Link | Size: 80-2 | Material: Alloy Carbon Steel','Sambungan rantai tipe 80 double strand.',NULL,'1',1,'2026-09-10 11:03:24');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` varchar(50) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `alamat` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES ('1','CV. MEGA TEKNIK ENGINEERING','MTE','SURABAYA','Jl. Raya Surabaya No. 10'),('2','CV. ANTIKA SUKSES GEMILANG','ASG','SURABAYA','Jl. Antika No. 5, Surabaya'),('3','PT. BOSCO INDOLESTARI','BI','JAKARTA','Jl. Bosco Raya, Jakarta'),('4','PT CAHAYA SELANG','CS','SURABAYA','Jl. Cahaya No. 12, Surabaya'),('5','SAKURA BEARING','SB','SURABAYA','Jl. Sakura No. 7, Surabaya'),('6','CV. SOLUSI JAYA BERSAMA','SJB','MALANG','Jl. Solusi No. 3, Malang'),('7','PT. ISTANA POMPA','IS','SURABAYA','Jl. Istana No. 15, Surabaya');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-12 12:56:44
