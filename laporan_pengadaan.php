<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if (!tanggal_valid($dari)) {
    $dari = date('Y-m-01');
}
if (!tanggal_valid($sampai)) {
    $sampai = date('Y-m-d');
}

$pengadaan = db_all(
    "SELECT p.idpengadaan, p.timestamp, p.status, p.subtotal_nilai, p.ppn, p.total_nilai,
            v.nama_vendor, u.username,
            (SELECT COUNT(*) FROM detail_pengadaan dp
             WHERE dp.idpengadaan = p.idpengadaan) AS jml_item,
            (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_pengadaan dp
             WHERE dp.idpengadaan = p.idpengadaan) AS kuantitas
     FROM pengadaan p
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     JOIN `user` u ON u.iduser = p.user_iduser
     WHERE DATE(p.timestamp) BETWEEN ? AND ?
     ORDER BY p.idpengadaan",
    [$dari, $sampai]
);

$totalPengadaan = count($pengadaan);
$totalKuantitas = array_sum(array_map(fn($r) => (int) $r['kuantitas'], $pengadaan));
$totalNilai = array_sum(array_map(fn($r) => (int) $r['total_nilai'], $pengadaan));

$perVendor = db_all(
    "SELECT v.nama_vendor, COUNT(*) AS jml_pengadaan, SUM(p.total_nilai) AS total
     FROM pengadaan p
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     WHERE DATE(p.timestamp) BETWEEN ? AND ?
     GROUP BY v.idvendor, v.nama_vendor
     ORDER BY total DESC",
    [$dari, $sampai]
);

$JUDUL = 'Laporan Pengadaan';
$AKTIF = 'laporan_pengadaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <form method="get" action="laporan_pengadaan.php">
        <div class="form-grid">
            <div>
                <label for="dari">Dari Tanggal</label>
                <input type="date" id="dari" name="dari" value="<?= e($dari) ?>">
            </div>
            <div>
                <label for="sampai">Sampai Tanggal</label>
                <input type="date" id="sampai" name="sampai" value="<?= e($sampai) ?>">
            </div>
        </div>
        <div class="form-aksi">
            <button type="submit" class="btn">Tampilkan</button>
        </div>
    </form>
</div>

<div class="grid mb">
    <div class="kartu"><div class="ket">Jumlah Pengadaan</div><div class="angka"><?= $totalPengadaan ?></div></div>
    <div class="kartu"><div class="ket">Total Kuantitas Barang</div><div class="angka"><?= $totalKuantitas ?></div></div>
    <div class="kartu"><div class="ket">Total Nilai</div><div class="angka"><?= rupiah($totalNilai) ?></div></div>
</div>

<div class="panel">
    <h2>Daftar Pengadaan <?= e($dari) ?> s.d. <?= e($sampai) ?></h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Tanggal</th><th>Vendor</th><th>Dibuat Oleh</th>
                    <th class="angka">Item</th><th class="angka">Kuantitas</th>
                    <th class="angka">Subtotal</th><th class="angka">PPN</th>
                    <th class="angka">Total</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pengadaan): ?>
                    <tr><td class="kosong" colspan="10">Tidak ada pengadaan pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($pengadaan as $p): ?>
                    <tr>
                        <td><?= (int) $p['idpengadaan'] ?></td>
                        <td><?= e(tanggal($p['timestamp'])) ?></td>
                        <td><?= e($p['nama_vendor']) ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td class="angka"><?= (int) $p['jml_item'] ?></td>
                        <td class="angka"><?= (int) $p['kuantitas'] ?></td>
                        <td class="angka"><?= rupiah($p['subtotal_nilai']) ?></td>
                        <td class="angka"><?= rupiah($p['ppn']) ?></td>
                        <td class="angka"><b><?= rupiah($p['total_nilai']) ?></b></td>
                        <td><?= badge_status_pengadaan($p['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h2>Rekap per Vendor</h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>Vendor</th><th class="angka">Jumlah Pengadaan</th><th class="angka">Total Nilai</th></tr>
            </thead>
            <tbody>
                <?php if (!$perVendor): ?>
                    <tr><td class="kosong" colspan="3">Belum ada data.</td></tr>
                <?php endif; ?>
                <?php foreach ($perVendor as $v): ?>
                    <tr>
                        <td><?= e($v['nama_vendor']) ?></td>
                        <td class="angka"><?= (int) $v['jml_pengadaan'] ?></td>
                        <td class="angka"><?= rupiah($v['total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
