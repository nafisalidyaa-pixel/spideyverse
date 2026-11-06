
DROP TRIGGER IF EXISTS `trg_penerimaan_stok`;

DELIMITER ;;
CREATE TRIGGER `trg_penerimaan_stok` AFTER INSERT ON `detail_penerimaan` FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.barang_idbarang;

    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
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

DELIMITER ;;
CREATE TRIGGER `trg_penjualan_validasi_stok` BEFORE INSERT ON `detail_penjualan` FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah penjualan harus lebih dari nol';
    END IF;

    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.idbarang;

    IF v_stock < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok barang tidak mencukupi';
    END IF;
END ;;
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_penjualan_stok`;

DELIMITER ;;
CREATE TRIGGER `trg_penjualan_stok` AFTER INSERT ON `detail_penjualan` FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;

    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = NEW.idbarang;

    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
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

DELIMITER ;;
CREATE TRIGGER `trg_retur_stok` AFTER INSERT ON `detail_retur` FOR EACH ROW
BEGIN
    DECLARE v_jumlah_terima INT DEFAULT 0;
    DECLARE v_total_retur INT DEFAULT 0;
    DECLARE v_idbarang INT DEFAULT 0;
    DECLARE v_stock INT DEFAULT 0;

    SELECT jumlah_terima, barang_idbarang
    INTO v_jumlah_terima, v_idbarang
    FROM detail_penerimaan
    WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;

    SELECT COALESCE(SUM(jumlah), 0)
    INTO v_total_retur
    FROM detail_retur
    WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;

    IF v_total_retur > v_jumlah_terima THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah retur melebihi penerimaan';
    END IF;

    SELECT COALESCE(SUM(masuk - keluar), 0)
    INTO v_stock
    FROM kartu_stok
    WHERE idbarang = v_idbarang;

    IF v_stock < NEW.jumlah THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok tidak cukup untuk retur';
    END IF;

    INSERT INTO kartu_stok
    (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
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
