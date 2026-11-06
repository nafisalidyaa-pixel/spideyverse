
DROP TRIGGER IF EXISTS `trg_penerimaan_stok`;
-- kerjanya : barang masuk-hitung stok lama-tambah stok baru, JADI INI AFTER YAA
DELIMITER ;;
-- kalo ada barang dimasukkan ke tabel detail_penerimaan = jalan
CREATE TRIGGER `trg_penerimaan_stok` AFTER INSERT ON `detail_penerimaan` FOR EACH ROW
-- tandanya mulai masuk ya ke kumpulan querynya
BEGIN
    DECLARE v_stock INT DEFAULT 0;
-- hitung total stok, kalo gaada dianggep 0
    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.barang_idbarang;
-- ditambahin sm si trigger ke kartu stock
    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
    -- nah ini dimasukin dengan urutan yg sesuai
    VALUES
    (
        'M',
        NEW.jumlah_terima,
        0,
        v_stock + NEW.jumlah_terima,
        NEW.idpenerimaan,
        NEW.barang_idbarang
    );
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_penjualan_validasi_stok`;
-- kerjanya = penjualan masuk- trigger periksa 2 kondisi, satunya jumlah beli, satunya jumlah stok, JADI INI BEFORE
DELIMITER ;;
-- kalo ada yang mau beli = jalan, selalu jalan tiap ada baris baru
CREATE TRIGGER `trg_penjualan_validasi_stok` BEFORE INSERT ON `detail_penjualan` FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah penjualan harus lebih dari nol';
    END IF;
-- POV masukin jumlah penjualan sebanyak 0, kondisi NEW.jumlah <= 0 jadinya ditolak

    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.idbarang;
-- kalo lebih dr 0 dihitung cukup apa mines
    IF v_stock < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok barang tidak mencukupi';
    END IF;
    --pov barangnya melebihi stok, ditolak
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_penjualan_stok`;
-- catat barang keluar lanjut kurangin stok setelah penjualan, AFTER YAA
DELIMITER ;;
CREATE TRIGGER `trg_penjualan_stok` AFTER INSERT ON `detail_penjualan` FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;
-- hitung stok saat ini
    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.idbarang;
-- baut catatan barang keluar
    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
    -- dimasukin dengan urutan yang sesuai
    VALUES
    (
        'K',
        0,
        NEW.jumlah,
        v_stock - NEW.jumlah,
        NEW.penjualan_idpenjualan,
        NEW.idbarang
    );
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_retur_stok`;
-- catat barang retur dan kurangi stok
DELIMITER ;;
CREATE TRIGGER `trg_retur_stok` AFTER INSERT ON `detail_retur` FOR EACH ROW
BEGIN
    DECLARE v_jumlah_terima INT DEFAULT 0;
    DECLARE v_total_retur INT DEFAULT 0;
    DECLARE v_idbarang INT DEFAULT 0;
    DECLARE v_stock INT DEFAULT 0;

    SELECT jumlah_terima, barang_idbarang
-- ambil
    INTO v_jumlah_terima, v_idbarang
    -- simpan
    FROM detail_penerimaan
    WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;
    -- cari penerimaan yg sesuai returnya

-- hitung total dari penerimaan
    SELECT COALESCE(SUM(jumlah), 0)
    INTO v_total_retur
    FROM detail_retur
    WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;
-- cek jumlah
    IF v_total_retur > v_jumlah_terima THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah retur melebihi penerimaan';
    END IF;
-- pov melebihi = tolak
    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = v_idbarang;
-- hitung sisa stok
    IF v_stock < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok tidak cukup untuk retur';
    END IF;
-- masukin sisa stok
    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )\
    -- isi urut 
    VALUES
    (
        'R',
        0,
        NEW.jumlah,
        v_stock - NEW.jumlah,
        NEW.idretur,
        v_idbarang
    );
END ;;
DELIMITER ;
