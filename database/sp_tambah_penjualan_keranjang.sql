-- =========================================================
-- SPIDEYVERSE - Stored Procedure Keranjang Penjualan
-- Memproses seluruh transaksi penjualan (keranjang) di dalam database.
-- Jalankan seluruh isi berkas ini di HeidiSQL (F9 / Execute).
-- Aman dijalankan berulang kali karena memakai DROP PROCEDURE IF EXISTS.
-- =========================================================

USE `spideyverse`;

DELIMITER ;;

DROP PROCEDURE IF EXISTS `sp_tambah_penjualan_keranjang`;;

CREATE PROCEDURE `sp_tambah_penjualan_keranjang`(
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

    -- Margin harus ada dan berstatus aktif.
    SELECT persen INTO v_persen
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin
      AND status = 1
    LIMIT 1;

    IF v_persen IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;

    -- Keranjang wajib berisi minimal satu item.
    IF p_items IS NULL OR JSON_LENGTH(p_items) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Minimal satu barang harus diisi';
    END IF;

    -- Barang pada setiap baris harus aktif dan tersedia.
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

    -- Jumlah pada setiap baris harus lebih dari nol.
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

    -- Stok gabungan per barang harus mencukupi.
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

    -- Subtotal dihitung memakai function.
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

    -- Detail item. Trigger pada detail_penjualan otomatis
    -- memvalidasi stok dan mengurangi stok per barang.
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
END;;

DELIMITER ;
