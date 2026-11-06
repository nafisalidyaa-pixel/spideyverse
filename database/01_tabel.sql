-- Untuk impor ulang di database yang sudah punya isi, sementara matikan pemeriksaan FK.
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS spideyverse;

USE `spideyverse`;

DROP TABLE IF EXISTS `barang`;
CREATE TABLE `barang` (
  `idbarang` int NOT NULL AUTO_INCREMENT,
  `jenis` char(1) DEFAULT NULL,
  `nama` varchar(45) DEFAULT NULL,
  `idsatuan` int DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `harga` int DEFAULT NULL,
  PRIMARY KEY (`idbarang`),
  KEY `idsatuan` (`idsatuan`),
  CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`idsatuan`) REFERENCES `satuan` (`idsatuan`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `barang` VALUES (1,'A','Tumbler Spider-Man',1,1,85000),(2,'A','Botol Minum Spider-Man',1,1,65000),(3,'A','Mug Spider-Man',1,1,45000),(4,'A','Gelas Spider-Man',1,1,25000),(5,'A','Sedotan Spider-Man',1,1,15000),(6,'B','Gantungan Kunci Spider-Man',1,1,20000),(7,'B','Pin Spider-Man',1,1,15000),(8,'B','Phone Grip Spider-Man',1,1,25000),(9,'B','Dompet Spider-Man',1,1,45000),(10,'B','Lanyard Spider-Man',1,1,25000),(11,'C','Action Figure Spider-Man',1,1,150000),(12,'C','Mini Figure Spider-Man',1,1,45000),(13,'C','Blind Box Spider-Man',2,1,75000),(14,'C','Figur Miles Morales',1,1,175000),(15,'C','Figur Iron Spider',1,1,185000),(16,'D','Topeng Spider-Man',1,1,55000),(17,'D','Web Shooter Spider-Man',1,1,95000),(18,'D','Sarung Tangan Spider-Man',1,1,65000),(19,'D','Mainan Tangan Spider-Man',1,1,40000),(20,'D','Puzzle Spider-Man',2,1,55000),(21,'E','Boneka Spider-Man',1,1,95000),(22,'E','Boneka Mini Spider-Man',1,1,45000),(23,'E','Plushie Spider-Man',1,1,85000),(24,'E','Boneka Gantung Spider-Man',1,1,50000),(25,'E','Boneka Miles Morales',1,1,90000),(26,'F','Buku Tulis Spider-Man',1,1,15000),(27,'F','Notebook Spider-Man',1,1,30000),(28,'F','Pensil Spider-Man',1,1,8000),(29,'F','Pulpen Spider-Man',1,1,12000),(30,'F','Kotak Pensil Spider-Man',1,1,35000),(31,'G','Tas Ransel Spider-Man',1,1,175000),(32,'G','Tas Selempang Spider-Man',1,1,95000),(33,'G','Tas Bekal Spider-Man',1,1,55000),(34,'G','Lunch Box Spider-Man',1,1,65000),(35,'G','Kotak Penyimpanan Spider-Man',1,1,45000),(36,'H','Poster Spider-Man',1,1,25000),(37,'H','Stiker Dinding Spider-Man',1,1,30000),(38,'H','Jam Dinding Spider-Man',1,1,85000),(39,'H','Lampu Meja Spider-Man',1,1,95000),(40,'H','Kalender Spider-Man',1,1,35000),(41,'I','Case HP Spider-Man',1,1,45000),(42,'I','Mousepad Spider-Man',1,1,35000),(43,'I','Flashdisk Spider-Man',1,1,65000),(44,'I','Holder HP Spider-Man',1,1,30000),(45,'I','Lampu USB Spider-Man',1,1,55000),(46,'J','Bantal Spider-Man',1,1,65000),(47,'J','Selimut Spider-Man',1,1,125000),(48,'J','Sprei Spider-Man',1,1,175000),(49,'J','Keset Spider-Man',1,1,35000),(50,'J','Tempat Sampah Spider-Man',1,1,45000);

DROP TABLE IF EXISTS `detail_penerimaan`;
CREATE TABLE `detail_penerimaan` (
  `iddetail_penerimaan` bigint NOT NULL AUTO_INCREMENT,
  `idpenerimaan` bigint DEFAULT NULL,
  `barang_idbarang` int DEFAULT NULL,
  `jumlah_terima` int DEFAULT NULL,
  `harga_satuan_terima` int DEFAULT NULL,
  `sub_total_terima` int DEFAULT NULL,
  PRIMARY KEY (`iddetail_penerimaan`),
  KEY `idpenerimaan` (`idpenerimaan`),
  KEY `barang_idbarang` (`barang_idbarang`),
  CONSTRAINT `detail_penerimaan_ibfk_1` FOREIGN KEY (`idpenerimaan`) REFERENCES `penerimaan` (`idpenerimaan`),
  CONSTRAINT `detail_penerimaan_ibfk_2` FOREIGN KEY (`barang_idbarang`) REFERENCES `barang` (`idbarang`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_penerimaan` VALUES (1,1,1,10,85000,850000),(2,1,2,10,65000,650000),(3,2,47,7,125000,875000),(4,3,14,9,175000,1575000);

DROP TABLE IF EXISTS `detail_pengadaan`;
CREATE TABLE `detail_pengadaan` (
  `iddetail_pengadaan` bigint NOT NULL AUTO_INCREMENT,
  `harga_satuan` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `sub_total` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL,
  `idpengadaan` bigint DEFAULT NULL,
  PRIMARY KEY (`iddetail_pengadaan`),
  KEY `idbarang` (`idbarang`),
  KEY `idpengadaan` (`idpengadaan`),
  CONSTRAINT `detail_pengadaan_ibfk_1` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`),
  CONSTRAINT `detail_pengadaan_ibfk_2` FOREIGN KEY (`idpengadaan`) REFERENCES `pengadaan` (`idpengadaan`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_pengadaan` VALUES (1,85000,10,850000,1,1),(2,65000,10,650000,2,1),(3,125000,7,875000,47,2),(4,175000,9,1575000,14,3),(5,175000,30,5250000,31,4),(6,85000,17,1445000,23,5),(7,185000,35,6475000,15,6),(8,65000,20,1300000,43,7);

DROP TABLE IF EXISTS `detail_penjualan`;
CREATE TABLE `detail_penjualan` (
  `iddetail_penjualan` bigint NOT NULL AUTO_INCREMENT,
  `harga_satuan` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `subtotal` int DEFAULT NULL,
  `penjualan_idpenjualan` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL,
  PRIMARY KEY (`iddetail_penjualan`),
  KEY `penjualan_idpenjualan` (`penjualan_idpenjualan`),
  KEY `idbarang` (`idbarang`),
  CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`penjualan_idpenjualan`) REFERENCES `penjualan` (`idpenjualan`),
  CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_penjualan` VALUES (1,93500,2,187000,1,1),(2,93500,1,93500,3,1),(3,137500,7,962500,5,47),(4,93500,1,93500,8,1);

DROP TABLE IF EXISTS `detail_retur`;
CREATE TABLE `detail_retur` (
  `iddetail_retur` int NOT NULL AUTO_INCREMENT,
  `jumlah` int DEFAULT NULL,
  `alasan` varchar(200) DEFAULT NULL,
  `idretur` bigint DEFAULT NULL,
  `iddetail_penerimaan` bigint DEFAULT NULL,
  PRIMARY KEY (`iddetail_retur`),
  KEY `idretur` (`idretur`),
  KEY `iddetail_penerimaan` (`iddetail_penerimaan`),
  CONSTRAINT `detail_retur_ibfk_1` FOREIGN KEY (`idretur`) REFERENCES `retur` (`idretur`),
  CONSTRAINT `detail_retur_ibfk_2` FOREIGN KEY (`iddetail_penerimaan`) REFERENCES `detail_penerimaan` (`iddetail_penerimaan`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `detail_retur` VALUES (1,1,'Barang rusak',1,1),(4,2,'terdapat retakan di bawah tumbler',4,1),(5,1,'ada retakan',5,4);

DROP TABLE IF EXISTS `kartu_stok`;
CREATE TABLE `kartu_stok` (
  `idkartu_stok` bigint NOT NULL AUTO_INCREMENT,
  `jenis_transaksi` char(1) DEFAULT NULL,
  `masuk` int DEFAULT NULL,
  `keluar` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `idtransaksi` int DEFAULT NULL,
  `idbarang` int DEFAULT NULL,
  PRIMARY KEY (`idkartu_stok`),
  KEY `idbarang` (`idbarang`),
  CONSTRAINT `kartu_stok_ibfk_1` FOREIGN KEY (`idbarang`) REFERENCES `barang` (`idbarang`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `kartu_stok` VALUES (1,'M',10,0,10,'2026-11-04 16:47:48',1,1),(2,'M',10,0,10,'2026-11-04 16:47:48',1,2),(3,'R',0,1,9,'2026-11-04 16:47:48',1,1),(4,'K',0,2,7,'2026-11-04 16:47:48',1,1),(5,'K',0,1,6,'2026-11-04 17:26:46',3,1),(6,'M',7,0,7,'2026-11-04 17:35:27',2,47),(7,'M',9,0,9,'2026-11-05 01:48:53',3,14),(8,'K',0,7,0,'2026-11-05 01:50:03',5,47),(9,'R',0,2,4,'2026-11-05 04:19:39',4,1),(10,'K',0,1,3,'2026-11-05 04:20:58',8,1),(13,'R',0,1,8,'2026-11-05 06:05:38',5,14);

DROP TABLE IF EXISTS `margin_penjualan`;
CREATE TABLE `margin_penjualan` (
  `idmargin_penjualan` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `persen` double DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`idmargin_penjualan`),
  KEY `iduser` (`iduser`),
  CONSTRAINT `margin_penjualan_ibfk_1` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `margin_penjualan` VALUES (1,'2026-11-04 16:47:48',10,1,1,'2026-11-04 16:47:48'),(2,'2026-11-04 16:47:48',15,0,1,'2026-11-04 16:47:48'),(3,'2026-11-04 16:47:48',20,0,1,'2026-11-04 16:47:48');

DROP TABLE IF EXISTS `penerimaan`;
CREATE TABLE `penerimaan` (
  `idpenerimaan` bigint NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` char(1) DEFAULT NULL,
  `idpengadaan` bigint DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  PRIMARY KEY (`idpenerimaan`),
  KEY `idpengadaan` (`idpengadaan`),
  KEY `iduser` (`iduser`),
  CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`idpengadaan`) REFERENCES `pengadaan` (`idpengadaan`),
  CONSTRAINT `penerimaan_ibfk_2` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `penerimaan` VALUES (1,'2026-11-04 16:47:48','A',1,2),(2,'2026-11-04 17:35:27','A',2,2),(3,'2026-11-05 01:48:53','A',3,2);

DROP TABLE IF EXISTS `pengadaan`;
CREATE TABLE `pengadaan` (
  `idpengadaan` bigint NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_iduser` int DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  `vendor_idvendor` int DEFAULT NULL,
  `subtotal_nilai` int DEFAULT NULL,
  `ppn` int DEFAULT NULL,
  `total_nilai` int DEFAULT NULL,
  PRIMARY KEY (`idpengadaan`),
  KEY `user_iduser` (`user_iduser`),
  KEY `vendor_idvendor` (`vendor_idvendor`),
  CONSTRAINT `pengadaan_ibfk_1` FOREIGN KEY (`user_iduser`) REFERENCES `user` (`iduser`),
  CONSTRAINT `pengadaan_ibfk_2` FOREIGN KEY (`vendor_idvendor`) REFERENCES `vendor` (`idvendor`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `pengadaan` VALUES (1,'2026-11-04 16:47:48',2,'A',1,1500000,165000,1665000),(2,'2026-11-04 17:33:41',1,'A',4,875000,96250,971250),(3,'2026-11-05 01:46:00',1,'A',2,1575000,173250,1748250),(4,'2026-11-05 03:37:45',1,'A',2,5250000,577500,5827500),(5,'2026-11-05 03:58:21',1,'A',5,1445000,158950,1603950),(6,'2026-11-05 03:58:39',1,'A',3,6475000,712250,7187250),(7,'2026-11-05 06:03:39',1,'A',4,1300000,143000,1443000);

DROP TABLE IF EXISTS `penjualan`;
CREATE TABLE `penjualan` (
  `idpenjualan` int NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal_nilai` int DEFAULT NULL,
  `ppn` int DEFAULT NULL,
  `total_nilai` int DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  `idmargin_penjualan` int DEFAULT NULL,
  PRIMARY KEY (`idpenjualan`),
  KEY `iduser` (`iduser`),
  KEY `idmargin_penjualan` (`idmargin_penjualan`),
  CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`),
  CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`idmargin_penjualan`) REFERENCES `margin_penjualan` (`idmargin_penjualan`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `penjualan` VALUES (1,'2026-11-04 16:47:48',187000,20570,207570,3,1),(3,'2026-11-04 17:26:46',93500,10285,103785,3,1),(5,'2026-11-05 01:50:03',962500,105875,1068375,3,1),(8,'2026-11-05 04:20:58',93500,10285,103785,3,1);

DROP TABLE IF EXISTS `retur`;
CREATE TABLE `retur` (
  `idretur` bigint NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `idpenerimaan` bigint DEFAULT NULL,
  `iduser` int DEFAULT NULL,
  PRIMARY KEY (`idretur`),
  KEY `idpenerimaan` (`idpenerimaan`),
  KEY `iduser` (`iduser`),
  CONSTRAINT `retur_ibfk_1` FOREIGN KEY (`idpenerimaan`) REFERENCES `penerimaan` (`idpenerimaan`),
  CONSTRAINT `retur_ibfk_2` FOREIGN KEY (`iduser`) REFERENCES `user` (`iduser`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `retur` VALUES (1,'2026-11-04 16:47:48',1,2),(4,'2026-11-05 04:19:39',1,2),(5,'2026-11-05 06:05:38',3,2);

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `idrole` int NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`idrole`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `role` VALUES (1,'Administrator'),(2,'Staff Gudang'),(3,'Kasir');

DROP TABLE IF EXISTS `satuan`;
CREATE TABLE `satuan` (
  `idsatuan` int NOT NULL AUTO_INCREMENT,
  `nama_satuan` varchar(45) DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  PRIMARY KEY (`idsatuan`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `satuan` VALUES (1,'PCS',1),(2,'BOX',1),(3,'SET',1);

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `iduser` int NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `idrole` int DEFAULT NULL,
  PRIMARY KEY (`iduser`),
  KEY `idrole` (`idrole`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`idrole`) REFERENCES `role` (`idrole`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `user` VALUES (1,'admin','Admin123!',1),(2,'staff','Staff123!',2),(3,'kasir','Kasir123!',3);

DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor` (
  `idvendor` int NOT NULL AUTO_INCREMENT,
  `nama_vendor` varchar(100) DEFAULT NULL,
  `badan_hukum` char(1) DEFAULT NULL,
  `status` char(1) DEFAULT NULL,
  PRIMARY KEY (`idvendor`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `vendor` VALUES (1,'Marvel Merchandise Official','P','A'),(2,'Hero Collectibles Indonesia','P','A'),(3,'Spidey Toys Supplier','P','A'),(4,'Nusantara Gift Store','P','A'),(5,'Geek Universe Distributor','P','A');

SET FOREIGN_KEY_CHECKS = 1;

