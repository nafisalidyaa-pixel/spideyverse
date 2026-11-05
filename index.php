<?php
require_once __DIR__ . '/inc/auth.php';
cek_login();

$role = id_role();

$totalBarang  = (int) db_val("SELECT COUNT(*) FROM barang WHERE status = 1");
$totalStok    = (int) db_val("SELECT COALESCE(SUM(masuk - keluar), 0) FROM kartu_stok");
$jumlahKritis = (int) db_val(
    "SELECT COUNT(*) FROM (
        SELECT b.idbarang, COALESCE(SUM(ks.masuk - ks.keluar), 0) AS stok
        FROM barang b
        LEFT JOIN kartu_stok ks ON ks.idbarang = b.idbarang
        WHERE b.status = 1
        GROUP BY b.idbarang
        HAVING stok < 10
     ) AS kritis"
);

$stokKritis = db_all(
    "SELECT b.idbarang, b.jenis, b.nama, s.nama_satuan, b.harga,
            COALESCE(SUM(ks.masuk - ks.keluar), 0) AS stok
     FROM barang b
     JOIN satuan s ON s.idsatuan = b.idsatuan
     LEFT JOIN kartu_stok ks ON ks.idbarang = b.idbarang
     WHERE b.status = 1
     GROUP BY b.idbarang, b.jenis, b.nama, s.nama_satuan, b.harga
     HAVING stok < 10
     ORDER BY stok ASC, b.nama ASC
     LIMIT 8"
);

$jualHariIni = db_one(
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(total_nilai), 0) AS pendapatan
     FROM penjualan
     WHERE DATE(created_at) = CURDATE()"
);

$JUDUL = 'Dashboard ' . nama_role();
$AKTIF = 'index.php';
require __DIR__ . '/inc/header.php';
?>

<?php if ($role === 1): ?>
    <!-- ================= ADMINISTRATOR ================= -->
    <div class="grid mb">
        <div class="kartu">
            <div class="ket">Barang Aktif</div>
            <div class="angka"><?= $totalBarang ?></div>
            <small>barang terdaftar</small>
        </div>
        <div class="kartu">
            <div class="ket">Total Stok Tersedia</div>
            <div class="angka"><?= $totalStok ?></div>
            <small>akumulasi mutasi stok</small>
        </div>
        <div class="kartu">
            <div class="ket">Stok Rendah (&lt; 10)</div>
            <div class="angka"><?= $jumlahKritis ?></div>
            <small>perlu pengadaan</small>
        </div>
        <div class="kartu">
            <div class="ket">Pendapatan Hari Ini</div>
            <div class="angka"><?= rupiah($jualHariIni['pendapatan']) ?></div>
            <small><?= (int) $jualHariIni['jumlah'] ?> transaksi</small>
        </div>
    </div>

    <div class="panel">
        <div class="ringkas">
            <a class="btn" href="barang_tambah.php">+ Barang</a>
            <a class="btn" href="pengadaan_tambah.php">+ Pengadaan</a>
            <a class="btn" href="penerimaan_tambah.php">+ Penerimaan</a>
            <a class="btn" href="retur_tambah.php">+ Retur</a>
            <a class="btn" href="penjualan_tambah.php">+ Penjualan</a>
            <a class="btn btn-outline" href="user_tambah.php">+ User</a>
        </div>
    </div>

    <div class="panel">
        <h2>Ringkasan Cepat</h2>
        <p>Gunakan menu di samping untuk mengelola data master, transaksi, dan laporan.
           Setiap proses transaksi berada di halaman tersendiri agar alur kerja lebih
           rapi dan mudah dipantau.</p>
    </div>

<?php elseif ($role === 2): ?>
    <!-- ================= STAFF GUDANG ================= -->
    <?php
    $pengadaanDraft = (int) db_val("SELECT COUNT(*) FROM pengadaan WHERE status = 'D'");
    $terimaHariIni  = (int) db_val("SELECT COUNT(*) FROM penerimaan WHERE DATE(created_at) = CURDATE()");
    $returHariIni   = (int) db_val("SELECT COUNT(*) FROM retur WHERE DATE(created_at) = CURDATE()");
    ?>
    <div class="grid mb">
        <div class="kartu">
            <div class="ket">Pengadaan Menunggu</div>
            <div class="angka"><?= $pengadaanDraft ?></div>
            <small>menunggu persetujuan</small>
        </div>
        <div class="kartu">
            <div class="ket">Penerimaan Hari Ini</div>
            <div class="angka"><?= $terimaHariIni ?></div>
            <small>barang masuk</small>
        </div>
        <div class="kartu">
            <div class="ket">Retur Hari Ini</div>
            <div class="angka"><?= $returHariIni ?></div>
            <small>barang keluar</small>
        </div>
        <div class="kartu">
            <div class="ket">Stok Rendah (&lt; 10)</div>
            <div class="angka"><?= $jumlahKritis ?></div>
            <small>dari <?= $totalBarang ?> barang aktif</small>
        </div>
    </div>

    <div class="panel">
        <div class="ringkas">
            <a class="btn" href="pengadaan_tambah.php">+ Pengadaan</a>
            <a class="btn" href="penerimaan_tambah.php">+ Penerimaan</a>
            <a class="btn" href="retur_tambah.php">+ Retur</a>
            <a class="btn btn-outline" href="kartu_stok.php">Lihat Kartu Stok</a>
        </div>
    </div>

    <?php
    $menunggu = db_all(
        "SELECT p.idpengadaan, v.nama_vendor, p.timestamp, p.total_nilai,
                (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_pengadaan dp
                 WHERE dp.idpengadaan = p.idpengadaan) AS total_qty,
                (SELECT COALESCE(SUM(dpe.jumlah_terima), 0)
                 FROM detail_penerimaan dpe
                 JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
                 WHERE pr.idpengadaan = p.idpengadaan) AS diterima
         FROM pengadaan p
         JOIN vendor v ON v.idvendor = p.vendor_idvendor
         WHERE p.status = 'A'
         ORDER BY p.idpengadaan DESC"
    );
    $menunggu = array_values(array_filter($menunggu, fn($p) => (int) $p['diterima'] < (int) $p['total_qty']));
    ?>
    <div class="panel">
        <h2>Pengadaan Siap Diterima</h2>
        <div class="tabel-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>Vendor</th><th>Tanggal</th><th class="angka">Kuantitas</th><th class="angka">Diterima</th><th class="angka">Sisa</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (!$menunggu): ?>
                        <tr><td class="kosong" colspan="7">Tidak ada pengadaan yang menunggu penerimaan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($menunggu as $p): ?>
                        <tr>
                            <td><?= (int) $p['idpengadaan'] ?></td>
                            <td><?= e($p['nama_vendor']) ?></td>
                            <td><?= e(tanggal($p['timestamp'])) ?></td>
                            <td class="angka"><?= (int) $p['total_qty'] ?></td>
                            <td class="angka"><?= (int) $p['diterima'] ?></td>
                            <td class="angka"><?= (int) $p['total_qty'] - (int) $p['diterima'] ?></td>
                            <td><a class="btn btn-kecil btn-outline" href="penerimaan_tambah.php?pengadaan=<?= (int) $p['idpengadaan'] ?>">Terima</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ================= KASIR ================= -->
    <?php
    $itemHariIni = (int) db_val(
        "SELECT COALESCE(SUM(dp.jumlah), 0)
         FROM detail_penjualan dp
         JOIN penjualan p ON p.idpenjualan = dp.penjualan_idpenjualan
         WHERE DATE(p.created_at) = CURDATE()"
    );
    ?>
    <div class="grid mb">
        <div class="kartu">
            <div class="ket">Transaksi Hari Ini</div>
            <div class="angka"><?= (int) $jualHariIni['jumlah'] ?></div>
            <small>transaksi penjualan</small>
        </div>
        <div class="kartu">
            <div class="ket">Pendapatan Hari Ini</div>
            <div class="angka"><?= rupiah($jualHariIni['pendapatan']) ?></div>
            <small>termasuk PPN 11%</small>
        </div>
        <div class="kartu">
            <div class="ket">Item Terjual Hari Ini</div>
            <div class="angka"><?= $itemHariIni ?></div>
            <small>total item terjual</small>
        </div>
        <div class="kartu">
            <div class="ket">Stok Rendah (&lt; 10)</div>
            <div class="angka"><?= $jumlahKritis ?></div>
            <small>cek sebelum jual</small>
        </div>
    </div>

    <div class="panel">
        <div class="ringkas">
            <a class="btn" href="penjualan_tambah.php">+ Transaksi Penjualan</a>
            <a class="btn btn-outline" href="barang.php">Cek Stok Barang</a>
            <a class="btn btn-outline" href="penjualan.php">Riwayat Penjualan</a>
        </div>
    </div>

    <?php
    $riwayat = db_all(
        "SELECT p.idpenjualan, p.created_at, p.total_nilai, u.username,
                (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_penjualan dp
                 WHERE dp.penjualan_idpenjualan = p.idpenjualan) AS item
         FROM penjualan p
         JOIN `user` u ON u.iduser = p.iduser
         WHERE p.iduser = ?
         ORDER BY p.idpenjualan DESC
         LIMIT 5",
        [id_user()]
    );
    ?>
    <div class="panel">
        <h2>Penjualan Terakhir Saya</h2>
        <div class="tabel-wrap">
            <table>
                <thead>
                    <tr><th>No</th><th>Waktu</th><th class="angka">Item</th><th class="angka">Total</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (!$riwayat): ?>
                        <tr><td class="kosong" colspan="5">Belum ada penjualan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <tr>
                            <td><?= (int) $r['idpenjualan'] ?></td>
                            <td><?= e(tanggal($r['created_at'])) ?></td>
                            <td class="angka"><?= (int) $r['item'] ?></td>
                            <td class="angka"><?= rupiah($r['total_nilai']) ?></td>
                            <td><a class="btn btn-kecil btn-outline" href="penjualan_detail.php?id=<?= (int) $r['idpenjualan'] ?>">Lihat</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <h2>Stok Rendah</h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Jenis</th><th>Barang</th><th class="angka">Harga</th><th class="angka">Stok</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if (!$stokKritis): ?>
                    <tr><td class="kosong" colspan="6">Semua stok aman.</td></tr>
                <?php endif; ?>
                <?php foreach ($stokKritis as $b): ?>
                    <tr>
                        <td><?= (int) $b['idbarang'] ?></td>
                        <td><?= e($b['jenis']) ?></td>
                        <td><?= e($b['nama']) ?></td>
                        <td class="angka"><?= rupiah($b['harga']) ?></td>
                        <td class="angka"><b><?= (int) $b['stok'] ?></b></td>
                        <td><?= badge_stok($b['stok']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
