DROP PROCEDURE IF EXISTS `sp_tambah_penjualan`;
DELIMITER ;;

CREATE PROCEDURE `sp_tambah_penjualan`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_idbarang INT,
    IN p_jumlah INT
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
-- Menghapus SP lama.

DELIMITER ;;
-- Mengubah tanda akhir query.

CREATE PROCEDURE `sp_tambah_penjualan_keranjang`(
    IN p_iduser INT,
    IN p_idmargin INT,
    IN p_items JSON
)
-- Membuat SP untuk penjualan banyak barang.
-- IN = input data.

BEGIN
-- Awal SP.

    DECLARE v_persen DOUBLE;
    DECLARE v_subtotal INT DEFAULT 0;
    DECLARE v_ppn INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_idpenjualan INT DEFAULT 0;
    -- Menyiapkan variabel untuk perhitungan transaksi.

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    -- Jika error, transaksi dibatalkan.

    START TRANSACTION;
    -- Memulai transaksi.

    SELECT persen INTO v_persen
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin
      AND status = 1
    LIMIT 1;
    -- Mengambil margin yang aktif.

    IF v_persen IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Margin tidak ditemukan atau tidak aktif';
    END IF;
    -- Memastikan margin tersedia.

    IF p_items IS NULL OR JSON_LENGTH(p_items) = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Minimal satu barang harus diisi';
    END IF;
    -- Memastikan keranjang tidak kosong.

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
    -- Memastikan semua barang tersedia dan aktif.

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
    -- Memastikan jumlah barang valid.

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
    -- Memastikan stok setiap barang mencukupi.

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
    -- Menghitung subtotal seluruh barang menggunakan Function.

    SET v_ppn = ROUND(v_subtotal * 0.11);
    -- Menghitung PPN 11%.

    SET v_total = v_subtotal + v_ppn;
    -- Menghitung total pembayaran.

    INSERT INTO penjualan
        (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES
        (v_subtotal, v_ppn, v_total, p_iduser, p_idmargin);
    -- Menyimpan transaksi utama.

    SET v_idpenjualan = LAST_INSERT_ID();
    -- Mengambil ID transaksi terbaru.

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
    -- Menyimpan detail barang dan menjalankan Trigger stok.

    COMMIT;
    -- Menyimpan transaksi.

    SELECT
        v_idpenjualan AS id_penjualan,
        v_subtotal AS subtotal,
        v_ppn AS ppn,
        v_total AS total_pembayaran;
    -- Menampilkan hasil transaksi.

END ;;
-- Akhir SP.

DELIMITER ;
-- Mengembalikan tanda akhir query.