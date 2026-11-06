-- =========================================================
-- SPIDEYVERSE - View
-- Jalankan paling akhir, setelah 01_tabel.sql - 04_sp.sql.
-- =========================================================

--
-- Temporary view structure for view `v_barang`
--

DROP TABLE IF EXISTS `v_barang`;
/*!50001 DROP VIEW IF EXISTS `v_barang`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_barang` AS SELECT 
 1 AS `idbarang`,
 1 AS `nama`,
 1 AS `jenis`,
 1 AS `kategori`,
 1 AS `idsatuan`,
 1 AS `nama_satuan`,
 1 AS `harga`,
 1 AS `status`,
 1 AS `status_barang`,
 1 AS `stok`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_margin_penjualan`
--

DROP TABLE IF EXISTS `v_margin_penjualan`;
/*!50001 DROP VIEW IF EXISTS `v_margin_penjualan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_margin_penjualan` AS SELECT 
 1 AS `idmargin_penjualan`,
 1 AS `persen`,
 1 AS `status`,
 1 AS `status_margin`,
 1 AS `iduser`,
 1 AS `username`,
 1 AS `jumlah_penjualan`,
 1 AS `created_at`,
 1 AS `updated_at`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_role`
--

DROP TABLE IF EXISTS `v_role`;
/*!50001 DROP VIEW IF EXISTS `v_role`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_role` AS SELECT 
 1 AS `idrole`,
 1 AS `nama_role`,
 1 AS `jumlah_user`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_satuan`
--

DROP TABLE IF EXISTS `v_satuan`;
/*!50001 DROP VIEW IF EXISTS `v_satuan`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_satuan` AS SELECT 
 1 AS `idsatuan`,
 1 AS `nama_satuan`,
 1 AS `status`,
 1 AS `status_satuan`,
 1 AS `jumlah_barang`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_user`
--

DROP TABLE IF EXISTS `v_user`;
/*!50001 DROP VIEW IF EXISTS `v_user`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_user` AS SELECT 
 1 AS `iduser`,
 1 AS `username`,
 1 AS `idrole`,
 1 AS `nama_role`,
 1 AS `jumlah_penjualan`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_vendor`
--

DROP TABLE IF EXISTS `v_vendor`;
/*!50001 DROP VIEW IF EXISTS `v_vendor`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_vendor` AS SELECT 
 1 AS `idvendor`,
 1 AS `nama_vendor`,
 1 AS `badan_hukum`,
 1 AS `status`,
 1 AS `status_vendor`,
 1 AS `jumlah_pengadaan`*/;
SET character_set_client = @saved_cs_client;
--
-- Current Database: `spideyverse`
--

USE `spideyverse`;

--
-- Final view structure for view `v_barang`
--

/*!50001 DROP VIEW IF EXISTS `v_barang`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_barang` AS select `b`.`idbarang` AS `idbarang`,`b`.`nama` AS `nama`,`b`.`jenis` AS `jenis`,(case `b`.`jenis` when 'A' then 'Botol & Perlengkapan Minum' when 'B' then 'Aksesori' when 'C' then 'Action Figure & Koleksi' when 'D' then 'Mainan Superhero' when 'E' then 'Boneka' when 'F' then 'Alat Tulis' when 'G' then 'Tas & Perlengkapan' when 'H' then 'Dekorasi & Suvenir' when 'I' then 'Perlengkapan Elektronik' when 'J' then 'Perlengkapan Kamar' else 'Lainnya' end) AS `kategori`,`b`.`idsatuan` AS `idsatuan`,`s`.`nama_satuan` AS `nama_satuan`,`b`.`harga` AS `harga`,`b`.`status` AS `status`,(case when (`b`.`status` = 1) then 'Aktif' else 'Nonaktif' end) AS `status_barang`,coalesce((select sum((`k`.`masuk` - `k`.`keluar`)) from `kartu_stok` `k` where (`k`.`idbarang` = `b`.`idbarang`)),0) AS `stok` from (`barang` `b` left join `satuan` `s` on((`s`.`idsatuan` = `b`.`idsatuan`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_margin_penjualan`
--

/*!50001 DROP VIEW IF EXISTS `v_margin_penjualan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_margin_penjualan` AS select `m`.`idmargin_penjualan` AS `idmargin_penjualan`,`m`.`persen` AS `persen`,`m`.`status` AS `status`,(case when (`m`.`status` = 1) then 'Aktif' else 'Nonaktif' end) AS `status_margin`,`m`.`iduser` AS `iduser`,`u`.`username` AS `username`,(select count(0) from `penjualan` `p` where (`p`.`idmargin_penjualan` = `m`.`idmargin_penjualan`)) AS `jumlah_penjualan`,`m`.`created_at` AS `created_at`,`m`.`updated_at` AS `updated_at` from (`margin_penjualan` `m` left join `user` `u` on((`u`.`iduser` = `m`.`iduser`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_role`
--

/*!50001 DROP VIEW IF EXISTS `v_role`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_role` AS select `r`.`idrole` AS `idrole`,`r`.`nama_role` AS `nama_role`,(select count(0) from `user` `u` where (`u`.`idrole` = `r`.`idrole`)) AS `jumlah_user` from `role` `r` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_satuan`
--

/*!50001 DROP VIEW IF EXISTS `v_satuan`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_satuan` AS select `s`.`idsatuan` AS `idsatuan`,`s`.`nama_satuan` AS `nama_satuan`,`s`.`status` AS `status`,(case when (`s`.`status` = 1) then 'Aktif' else 'Nonaktif' end) AS `status_satuan`,(select count(0) from `barang` `b` where (`b`.`idsatuan` = `s`.`idsatuan`)) AS `jumlah_barang` from `satuan` `s` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_user`
--

/*!50001 DROP VIEW IF EXISTS `v_user`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_user` AS select `u`.`iduser` AS `iduser`,`u`.`username` AS `username`,`u`.`idrole` AS `idrole`,`r`.`nama_role` AS `nama_role`,(select count(0) from `penjualan` `p` where (`p`.`iduser` = `u`.`iduser`)) AS `jumlah_penjualan` from (`user` `u` left join `role` `r` on((`r`.`idrole` = `u`.`idrole`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_vendor`
--

/*!50001 DROP VIEW IF EXISTS `v_vendor`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_vendor` AS select `v`.`idvendor` AS `idvendor`,`v`.`nama_vendor` AS `nama_vendor`,`v`.`badan_hukum` AS `badan_hukum`,`v`.`status` AS `status`,(case when (`v`.`status` = 'A') then 'Aktif' else 'Nonaktif' end) AS `status_vendor`,(select count(0) from `pengadaan` `p` where (`p`.`vendor_idvendor` = `v`.`idvendor`)) AS `jumlah_pengadaan` from `vendor` `v` */;
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

-- Dump completed on 2026-11-05 15:41:32
