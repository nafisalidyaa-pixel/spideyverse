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

$retur = db_all(
    "SELECT r.idretur, r.created_at, r.idpenerimaan, u.username, v.nama_vendor,
            dr.jumlah, dr.alasan, b.nama, b.jenis, b.idbarang
     FROM retur r
     JOIN detail_retur dr ON dr.idretur = r.idretur
     JOIN detail_penerimaan dpe ON dpe.iddetail_penerimaan = dr.iddetail_penerimaan
     JOIN barang b ON b.idbarang = dpe.barang_idbarang
     JOIN `user` u ON u.iduser = r.iduser
     JOIN penerimaan pr ON pr.idpenerimaan = r.idpenerimaan
     JOIN pengadaan p ON p.idpengadaan = pr.idpengadaan
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     WHERE DATE(r.created_at) BETWEEN ? AND ?
     ORDER BY r.idretur DESC",
    [$dari, $sampai]
);

$totalRetur = count(array_unique(array_column($retur, 'idretur')));
$totalKuantitas = array_sum(array_map(fn($r) => (int) $r['jumlah'], $retur));

$JUDUL = 'Laporan Retur';
$AKTIF = 'laporan_retur.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <form method="get" action="laporan_retur.php">
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
    <div class="kartu"><div class="ket">Jumlah Retur</div><div class="angka"><?= $totalRetur ?></div></div>
    <div class="kartu"><div class="ket">Total Kuantitas Diretur</div><div class="angka"><?= $totalKuantitas ?></div></div>
</div>

<div class="panel">
    <h2>Daftar Retur <?= e($dari) ?> s.d. <?= e($sampai) ?></h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID Retur</th><th>Tanggal</th><th>Penerimaan</th><th>Vendor</th>
                    <th>Barang</th><th class="angka">Kuantitas</th><th>Alasan</th><th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$retur): ?>
                    <tr><td class="kosong" colspan="8">Tidak ada retur pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($retur as $r): ?>
                    <tr>
                        <td>
                            <a href="retur_detail.php?id=<?= (int) $r['idretur'] ?>">
                                #<?= (int) $r['idretur'] ?>
                            </a>
                        </td>
                        <td><?= e(tanggal($r['created_at'])) ?></td>
                        <td>#<?= (int) $r['idpenerimaan'] ?></td>
                        <td><?= e($r['nama_vendor']) ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td class="angka"><?= (int) $r['jumlah'] ?></td>
                        <td><?= e($r['alasan']) ?></td>
                        <td><?= e($r['username']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
