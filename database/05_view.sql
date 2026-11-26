-- 05_view.sql ⤳ 6 view siap pakai buat baca data + hitungan (stok, jumlah relasi, status).
-- Jalankan paling akhir, setelah 01_tabel.sql - 04_sp.sql.
USE spideyverse;

-- v_barang: barang + satuan + kategori + stok hasil SUM kartu_stok.
DROP VIEW IF EXISTS `v_barang`;
CREATE VIEW `v_barang` AS
SELECT
    b.`idbarang`,
    b.`nama`,
    b.`jenis`,
    CASE b.`jenis`
        WHEN 'A' THEN 'Botol & Perlengkapan Minum'
        WHEN 'B' THEN 'Aksesori'
        WHEN 'C' THEN 'Action Figure & Koleksi'
        WHEN 'D' THEN 'Mainan Superhero'
        WHEN 'E' THEN 'Boneka'
        WHEN 'F' THEN 'Alat Tulis'
        WHEN 'G' THEN 'Tas & Perlengkapan'
        WHEN 'H' THEN 'Dekorasi & Suvenir'
        WHEN 'I' THEN 'Perlengkapan Elektronik'
        WHEN 'J' THEN 'Perlengkapan Kamar'
        ELSE 'Lainnya'
    END AS `kategori`,
    b.`idsatuan`,
    s.`nama_satuan`,
    b.`harga`,
    b.`status`,
    CASE WHEN b.`status` = 1 THEN 'Aktif' ELSE 'Nonaktif' END AS `status_barang`,
    COALESCE((
        SELECT SUM(k.`masuk` - k.`keluar`)
        FROM `kartu_stok` k
        WHERE k.`idbarang` = b.`idbarang`
    ), 0) AS `stok`
FROM `barang` b
LEFT JOIN `satuan` s ON s.`idsatuan` = b.`idsatuan`;

-- v_satuan: satuan + status + jumlah barang yang pakai satuan itu.
DROP VIEW IF EXISTS `v_satuan`;
CREATE VIEW `v_satuan` AS
SELECT
    s.`idsatuan`,
    s.`nama_satuan`,
    s.`status`,
    CASE WHEN s.`status` = 1 THEN 'Aktif' ELSE 'Nonaktif' END AS `status_satuan`,
    (SELECT COUNT(*) FROM `barang` b WHERE b.`idsatuan` = s.`idsatuan`) AS `jumlah_barang`
FROM `satuan` s;

-- v_vendor: vendor + status + jumlah pengadaan yang pernah dibuat.
DROP VIEW IF EXISTS `v_vendor`;
CREATE VIEW `v_vendor` AS
SELECT
    v.`idvendor`,
    v.`nama_vendor`,
    v.`badan_hukum`,
    v.`status`,
    CASE WHEN v.`status` = 'A' THEN 'Aktif' ELSE 'Nonaktif' END AS `status_vendor`,
    (SELECT COUNT(*) FROM `pengadaan` p WHERE p.`vendor_idvendor` = v.`idvendor`) AS `jumlah_pengadaan`
FROM `vendor` v;

-- v_role: role + jumlah user yang pakai role itu.
DROP VIEW IF EXISTS `v_role`;
CREATE VIEW `v_role` AS
SELECT
    r.`idrole`,
    r.`nama_role`,
    (SELECT COUNT(*) FROM `user` u WHERE u.`idrole` = r.`idrole`) AS `jumlah_user`
FROM `role` r;

-- v_user: user + nama role + jumlah penjualan yang pernah dicatat.
DROP VIEW IF EXISTS `v_user`;
CREATE VIEW `v_user` AS
SELECT
    u.`iduser`,
    u.`username`,
    u.`idrole`,
    r.`nama_role`,
    (SELECT COUNT(*) FROM `penjualan` p WHERE p.`iduser` = u.`iduser`) AS `jumlah_penjualan`
FROM `user` u
LEFT JOIN `role` r ON r.`idrole` = u.`idrole`;

-- v_margin_penjualan: margin + status + username pembuat + jumlah penjualan yang pakai margin ini.
DROP VIEW IF EXISTS `v_margin_penjualan`;
CREATE VIEW `v_margin_penjualan` AS
SELECT
    m.`idmargin_penjualan`,
    m.`persen`,
    m.`status`,
    CASE WHEN m.`status` = 1 THEN 'Aktif' ELSE 'Nonaktif' END AS `status_margin`,
    m.`iduser`,
    u.`username`,
    (SELECT COUNT(*) FROM `penjualan` p
     WHERE p.`idmargin_penjualan` = m.`idmargin_penjualan`) AS `jumlah_penjualan`,
    m.`created_at`,
    m.`updated_at`
FROM `margin_penjualan` m
LEFT JOIN `user` u ON u.`iduser` = m.`iduser`;
