-- =========================================================
-- SPIDEYVERSE - Stored Procedure
-- Jalankan setelah 01_tabel.sql, 02_trigger.sql, 03_function.sql.
-- =========================================================

/*!50003 DROP PROCEDURE IF EXISTS `sp_tambah_penjualan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_penjualan`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_idbarang INT,
    IN p_jumlah INT
)
BEGIN
    DECLARE v_harga INT DEFAULT 0;
    DECLARE v_margin DOUBLE DEFAULT 0;
    DECLARE v_harga_jual INT DEFAULT 0;
    DECLARE v_subtotal INT DEFAULT 0;
    DECLARE v_ppn INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_idpenjualan INT DEFAULT 0;
    DECLARE v_jumlah_data INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Validasi jumlah penjualan.
    IF p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah harus lebih dari nol';
    END IF;

    -- Memastikan barang tersedia.
    SELECT COUNT(*)
    INTO v_jumlah_data
    FROM barang
    WHERE idbarang = p_idbarang
      AND status = 1;

    IF v_jumlah_data = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Barang tidak ditemukan atau tidak aktif';
    END IF;

    -- Memastikan margin tersedia dan aktif.
    SELECT COUNT(*)
    INTO v_jumlah_data
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin
      AND status = 1;

    IF v_jumlah_data = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;

    -- Mengambil harga barang.
    SELECT harga
    INTO v_harga
    FROM barang
    WHERE idbarang = p_idbarang;

    -- Mengambil persentase margin.
    SELECT persen
    INTO v_margin
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin;

    -- Menghitung harga jual setelah margin.
    SET v_harga_jual = ROUND(
        v_harga + (v_harga * v_margin / 100)
    );

    -- Menghitung subtotal menggunakan function.
    SET v_subtotal = fn_hitung_subtotal(
        v_harga_jual,
        p_jumlah
    );

    -- Menghitung PPN 11%.
    SET v_ppn = ROUND(v_subtotal * 0.11);

    -- Menghitung total pembayaran.
    SET v_total = v_subtotal + v_ppn;

    -- Memasukkan data penjualan.
    INSERT INTO penjualan
    (
        subtotal_nilai,
        ppn,
        total_nilai,
        iduser,
        idmargin_penjualan
    )
    VALUES
    (
        v_subtotal,
        v_ppn,
        v_total,
        p_iduser,
        p_idmargin
    );

    SET v_idpenjualan = LAST_INSERT_ID();

    -- Memasukkan detail penjualan.
    -- Trigger akan memvalidasi dan mengurangi stok.
    INSERT INTO detail_penjualan
    (
        harga_satuan,
        jumlah,
        subtotal,
        penjualan_idpenjualan,
        idbarang
    )
    VALUES
    (
        v_harga_jual,
        p_jumlah,
        v_subtotal,
        v_idpenjualan,
        p_idbarang
    );

    COMMIT;

    SELECT
        v_idpenjualan AS id_penjualan,
        v_harga_jual AS harga_jual,
        v_subtotal AS subtotal,
        v_ppn AS ppn,
        v_total AS total_pembayaran;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_tambah_penjualan_keranjang` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_penjualan_keranjang`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_items JSON
)
BEGIN
    DECLARE v_persen DOUBLE;
    DECLARE v_subtotal INT DEFAULT 0;
    DECLARE v_ppn INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_idpenjualan INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    
    SELECT persen INTO v_persen
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin
      AND status = 1
    LIMIT 1;

    IF v_persen IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;

    
    IF p_items IS NULL OR JSON_LENGTH(p_items) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Minimal satu barang harus diisi';
    END IF;

    
    IF EXISTS (
        SELECT 1
        FROM JSON_TABLE(
            p_items,
            '$[*]'
            COLUMNS (
                idbarang INT PATH '$.idbarang',
                jumlah   INT PATH '$.jumlah'
            )
        ) AS jt
        LEFT JOIN barang b ON b.idbarang = jt.idbarang AND b.status = 1
        WHERE b.idbarang IS NULL
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Barang tidak ditemukan atau tidak aktif';
    END IF;

    
    IF EXISTS (
        SELECT 1
        FROM JSON_TABLE(
            p_items,
            '$[*]'
            COLUMNS (
                idbarang INT PATH '$.idbarang',
                jumlah   INT PATH '$.jumlah'
            )
        ) AS jt
        WHERE jt.jumlah <= 0
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Jumlah harus lebih dari nol';
    END IF;

    
    IF EXISTS (
        SELECT 1
        FROM (
            SELECT jt.idbarang, SUM(jt.jumlah) AS diminta
            FROM JSON_TABLE(
                p_items,
                '$[*]'
                COLUMNS (
                    idbarang INT PATH '$.idbarang',
                    jumlah   INT PATH '$.jumlah'
                )
            ) AS jt
            GROUP BY jt.idbarang
        ) AS r
        WHERE COALESCE((
            SELECT SUM(k.masuk - k.keluar)
            FROM kartu_stok k
            WHERE k.idbarang = r.idbarang
        ), 0) < r.diminta
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stok barang tidak mencukupi';
    END IF;

    
    SELECT SUM(fn_hitung_subtotal(ROUND(b.harga + b.harga * v_persen / 100), jt.jumlah))
    INTO v_subtotal
    FROM JSON_TABLE(
        p_items,
        '$[*]'
        COLUMNS (
            idbarang INT PATH '$.idbarang',
            jumlah   INT PATH '$.jumlah'
        )
    ) AS jt
    JOIN barang b ON b.idbarang = jt.idbarang;

    SET v_ppn   = ROUND(v_subtotal * 0.11);
    SET v_total = v_subtotal + v_ppn;

    INSERT INTO penjualan
        (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES
        (v_subtotal, v_ppn, v_total, p_iduser, p_idmargin);

    SET v_idpenjualan = LAST_INSERT_ID();

    
    
    INSERT INTO detail_penjualan
        (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang)
    SELECT ROUND(b.harga + b.harga * v_persen / 100),
           jt.jumlah,
           fn_hitung_subtotal(ROUND(b.harga + b.harga * v_persen / 100), jt.jumlah),
           v_idpenjualan,
           jt.idbarang
    FROM JSON_TABLE(
        p_items,
        '$[*]'
        COLUMNS (
            idbarang INT PATH '$.idbarang',
            jumlah   INT PATH '$.jumlah'
        )
    ) AS jt
    JOIN barang b ON b.idbarang = jt.idbarang;

    COMMIT;

    SELECT
        v_idpenjualan AS id_penjualan,
        v_subtotal    AS subtotal,
        v_ppn         AS ppn,
        v_total       AS total_pembayaran;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
