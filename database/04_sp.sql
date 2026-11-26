-- 04_sp.sql ⤳ 3 stored procedure: penjualan 1 barang, penjualan keranjang (CSV, tanpa JSON), dan simpan pengadaan.
DROP PROCEDURE IF EXISTS `sp_tambah_penjualan`;
DELIMITER ;;

CREATE PROCEDURE `sp_tambah_penjualan`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_idbarang INT,
    IN p_jumlah INT
)
-- IN = menerima input berupa ID user, ID margin, ID barang, dan jumlah.

BEGIN
-- Awal kumpulan query SP.

    DECLARE v_harga INT DEFAULT 0;
    DECLARE v_margin DOUBLE DEFAULT 0;
    DECLARE v_harga_jual INT DEFAULT 0;
    DECLARE v_subtotal INT DEFAULT 0;
    DECLARE v_ppn INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_idpenjualan INT DEFAULT 0;
    DECLARE v_jumlah_data INT DEFAULT 0;
    -- Membuat variabel untuk menyimpan harga, margin, subtotal,
    -- PPN, total pembayaran, ID penjualan, dan jumlah data.

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    -- Jika terjadi error, transaksi dibatalkan dan pesan error ditampilkan.

    START TRANSACTION;
    -- Memulai transaksi.

    IF p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah harus lebih dari nol';
    END IF;
    -- Memastikan jumlah barang yang dijual lebih dari nol.

    SELECT COUNT(*) INTO v_jumlah_data
    FROM barang
    WHERE idbarang = p_idbarang AND status = 1;
    -- Memastikan barang tersedia dan berstatus aktif.

    IF v_jumlah_data = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Barang tidak ditemukan atau tidak aktif';
    END IF;
    -- Jika barang tidak ditemukan atau tidak aktif, transaksi ditolak.

    SELECT COUNT(*) INTO v_jumlah_data
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin AND status = 1;
    -- Memastikan margin penjualan tersedia dan aktif.

    IF v_jumlah_data = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;
    -- Jika margin tidak ditemukan atau tidak aktif, transaksi ditolak.

    SELECT harga INTO v_harga
    FROM barang
    WHERE idbarang = p_idbarang;
    -- Mengambil harga asli barang.

    SELECT persen INTO v_margin
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin;
    -- Mengambil persentase margin.

    SET v_harga_jual = ROUND(
        v_harga + (v_harga * v_margin / 100)
    );
    -- Menghitung harga jual setelah ditambah margin.

    SET v_subtotal = fn_hitung_subtotal(v_harga_jual, p_jumlah);
    -- Menghitung subtotal menggunakan Function.

    SET v_ppn = ROUND(v_subtotal * 0.11);
    -- Menghitung PPN sebesar 11%.

    SET v_total = v_subtotal + v_ppn;
    -- Menghitung total pembayaran.

    INSERT INTO penjualan
    (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES
    (v_subtotal, v_ppn, v_total, p_iduser, p_idmargin);
    -- Menyimpan data utama transaksi ke tabel penjualan.

    SET v_idpenjualan = LAST_INSERT_ID();
    -- Mengambil ID penjualan yang baru saja dibuat.

    INSERT INTO detail_penjualan
    (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang)
    VALUES
    (v_harga_jual, p_jumlah, v_subtotal, v_idpenjualan, p_idbarang);
    -- Menyimpan detail barang yang dijual.
    -- Trigger penjualan otomatis memvalidasi dan mengurangi stok.

    COMMIT;
    -- Menyimpan transaksi jika semua proses berhasil.

    SELECT
        v_idpenjualan AS id_penjualan,
        v_harga_jual AS harga_jual,
        v_subtotal AS subtotal,
        v_ppn AS ppn,
        v_total AS total_pembayaran;
    -- Menampilkan hasil transaksi penjualan.

END ;;
-- Akhir kumpulan query SP.

DELIMITER ;
-- Mengembalikan tanda akhir query menjadi ;.

DROP PROCEDURE IF EXISTS `sp_tambah_penjualan_keranjang`;
-- SP penjualan banyak barang: TANPA JSON dan TANPA temporary table.
-- Data keranjang dikirim sebagai string CSV: "idbarang,jumlah,idbarang,jumlah,..."

DELIMITER ;;
CREATE PROCEDURE `sp_tambah_penjualan_keranjang`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_items_csv VARCHAR(255)
)
BEGIN
    DECLARE v_persen DOUBLE;
    DECLARE v_idpenjualan INT DEFAULT 0;
    DECLARE v_sisa VARCHAR(255);
    DECLARE v_idbarang INT DEFAULT 0;
    DECLARE v_jumlah INT DEFAULT 0;
    DECLARE v_harga INT DEFAULT 0;
    DECLARE v_harga_jual INT DEFAULT 0;
    DECLARE v_subtotal INT DEFAULT 0;
    DECLARE v_ppn INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_sub INT DEFAULT 0;
    DECLARE v_ada INT DEFAULT 0;
    DECLARE v_stok INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- 1. Validasi margin aktif.
    SELECT persen INTO v_persen
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin AND status = 1
    LIMIT 1;

    IF v_persen IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;

    -- 2. Validasi keranjang tidak kosong.
    SET v_sisa = TRIM(COALESCE(p_items_csv, ''));
    IF v_sisa = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Minimal satu barang harus diisi';
    END IF;

    -- 3. Buat header penjualan.
    INSERT INTO penjualan (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES (0, 0, 0, p_iduser, p_idmargin);
    SET v_idpenjualan = LAST_INSERT_ID();

    -- 4. Loop pasangan idbarang,jumlah dari string CSV.
    WHILE v_sisa IS NOT NULL AND v_sisa <> '' DO
        SET v_idbarang = CAST(SUBSTRING_INDEX(v_sisa, ',', 1) AS UNSIGNED);
        SET v_sisa = IF(LOCATE(',', v_sisa) > 0, SUBSTRING(v_sisa, LOCATE(',', v_sisa) + 1), '');

        SET v_jumlah = CAST(SUBSTRING_INDEX(v_sisa, ',', 1) AS UNSIGNED);
        SET v_sisa = IF(LOCATE(',', v_sisa) > 0, SUBSTRING(v_sisa, LOCATE(',', v_sisa) + 1), '');

        IF v_idbarang <= 0 OR v_jumlah <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data barang tidak valid';
        END IF;

        SELECT COUNT(*) INTO v_ada FROM barang WHERE idbarang = v_idbarang AND status = 1;
        IF v_ada = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tidak ditemukan atau tidak aktif';
        END IF;

        SELECT COALESCE(SUM(masuk - keluar), 0) INTO v_stok
        FROM kartu_stok WHERE idbarang = v_idbarang;
        IF v_stok < v_jumlah THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok barang tidak mencukupi';
        END IF;

        SELECT harga INTO v_harga FROM barang WHERE idbarang = v_idbarang;
        SET v_harga_jual = ROUND(v_harga + (v_harga * v_persen / 100));
        SET v_sub = fn_hitung_subtotal(v_harga_jual, v_jumlah);

        INSERT INTO detail_penjualan (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang)
        VALUES (v_harga_jual, v_jumlah, v_sub, v_idpenjualan, v_idbarang);

        SET v_subtotal = v_subtotal + v_sub;
    END WHILE;

    -- 5. Hitung ulang header.
    SET v_ppn = ROUND(v_subtotal * 0.11);
    SET v_total = v_subtotal + v_ppn;
    UPDATE penjualan
    SET subtotal_nilai = v_subtotal, ppn = v_ppn, total_nilai = v_total
    WHERE idpenjualan = v_idpenjualan;

    COMMIT;

    SELECT v_idpenjualan AS id_penjualan, v_subtotal AS subtotal,
           v_ppn AS ppn, v_total AS total_pembayaran;
END ;;
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_simpan_pengadaan`;
-- Menyimpan pengadaan barang: jika ID pengadaan masih 0, buat header baru.

DELIMITER ;;
CREATE PROCEDURE `sp_simpan_pengadaan`(
    IN p_iduser INT,
    IN p_idvendor INT,
    IN p_idbarang INT,
    IN p_jumlah INT,
    IN p_harga INT,
    INOUT p_idpengadaan INT
)
BEGIN
    DECLARE v_subtotal INT;
    SET v_subtotal = p_jumlah * p_harga;
    -- Subtotal = harga satuan x jumlah barang.

    IF p_idpengadaan = 0 THEN
        INSERT INTO pengadaan (user_iduser, vendor_idvendor, status, subtotal_nilai, ppn, total_nilai)
        VALUES (p_iduser, p_idvendor, 'P', 0, 0, 0);
        SET p_idpengadaan = LAST_INSERT_ID();
    END IF;
    -- Jika ID pengadaan masih 0, buat header pengadaan baru dan ambil ID-nya.

    INSERT INTO detail_pengadaan (idpengadaan, idbarang, harga_satuan, jumlah, sub_total)
    VALUES (p_idpengadaan, p_idbarang, p_harga, p_jumlah, v_subtotal);
    -- Simpan detail barang ke header pengadaan.

    UPDATE pengadaan
    SET subtotal_nilai = (SELECT COALESCE(SUM(sub_total), 0) FROM detail_pengadaan WHERE idpengadaan = p_idpengadaan),
        ppn = fn_calculate_ppn((SELECT COALESCE(SUM(sub_total), 0) FROM detail_pengadaan WHERE idpengadaan = p_idpengadaan)),
        total_nilai = subtotal_nilai + ppn
    WHERE idpengadaan = p_idpengadaan;
    -- Hitung ulang subtotal, PPN, dan total dari seluruh detail.
END ;;
-- Akhir SP simpan_pengadaan.

DELIMITER ;
-- Mengembalikan tanda akhir query.