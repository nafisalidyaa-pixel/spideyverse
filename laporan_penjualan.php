<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 3]);

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
if (!tanggal_valid($dari)) {
    $dari = date('Y-m-01');
}
if (!tanggal_valid($sampai)) {
    $sampai = date('Y-m-d');
}

$params = [$dari, $sampai];
$filterKasir = '';
if (id_role() === 3) {
    $filterKasir = ' AND p.iduser = ?';
    $params[] = id_user();
}

$penjualan = db_all(
    "SELECT p.idpenjualan, p.created_at, p.subtotal_nilai, p.ppn, p.total_nilai,
            u.username, m.persen,
            (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_penjualan dp
             WHERE dp.penjualan_idpenjualan = p.idpenjualan) AS item
     FROM penjualan p
     JOIN `user` u ON u.iduser = p.iduser
     JOIN margin_penjualan m ON m.idmargin_penjualan = p.idmargin_penjualan
     WHERE DATE(p.created_at) BETWEEN ? AND ? {$filterKasir}
     ORDER BY p.idpenjualan",
    $params
);

$rekapParams = [$dari, $sampai];
$filterKasirRekap = '';
if (id_role() === 3) {
    $filterKasirRekap = ' AND p.iduser = ?';
    $rekapParams[] = id_user();
}

$rekap = db_all(
    "SELECT b.idbarang, b.nama, b.jenis,
            SUM(dp.jumlah) AS kuantitas,
            SUM(dp.subtotal) AS pendapatan
     FROM detail_penjualan dp
     JOIN penjualan p ON p.idpenjualan = dp.penjualan_idpenjualan
     JOIN barang b ON b.idbarang = dp.idbarang
     WHERE DATE(p.created_at) BETWEEN ? AND ? {$filterKasirRekap}
     GROUP BY b.idbarang, b.nama, b.jenis
     ORDER BY kuantitas DESC",
    $rekapParams
);

$totalTransaksi = count($penjualan);
$totalItem = array_sum(array_map(fn($r) => (int) $r['item'], $penjualan));
$totalPendapatan = array_sum(array_map(fn($r) => (int) $r['total_nilai'], $penjualan));
$totalPpn = array_sum(array_map(fn($r) => (int) $r['ppn'], $penjualan));

$JUDUL = 'Laporan Penjualan';
$AKTIF = 'laporan_penjualan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <form method="get" action="laporan_penjualan.php">
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
    <div class="kartu"><div class="ket">Jumlah Transaksi</div><div class="angka"><?= $totalTransaksi ?></div></div>
    <div class="kartu"><div class="ket">Item Terjual</div><div class="angka"><?= $totalItem ?></div></div>
    <div class="kartu"><div class="ket">Total PPN</div><div class="angka"><?= rupiah($totalPpn) ?></div></div>
    <div class="kartu"><div class="ket">Total Pendapatan</div><div class="angka"><?= rupiah($totalPendapatan) ?></div></div>
</div>

<div class="panel">
    <h2>Daftar Penjualan <?= e($dari) ?> s.d. <?= e($sampai) ?></h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Tanggal</th><th>Kasir</th><th class="angka">Margin</th>
                    <th class="angka">Item</th><th class="angka">Subtotal</th>
                    <th class="angka">PPN</th><th class="angka">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$penjualan): ?>
                    <tr><td class="kosong" colspan="8">Tidak ada penjualan pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($penjualan as $p): ?>
                    <tr>
                        <td><?= (int) $p['idpenjualan'] ?></td>
                        <td><?= e(tanggal($p['created_at'])) ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td class="angka"><?= (float) $p['persen'] ?> %</td>
                        <td class="angka"><?= (int) $p['item'] ?></td>
                        <td class="angka"><?= rupiah($p['subtotal_nilai']) ?></td>
                        <td class="angka"><?= rupiah($p['ppn']) ?></td>
                        <td class="angka"><b><?= rupiah($p['total_nilai']) ?></b></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h2>Rekap Penjualan per Barang</h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID</th><th>Barang</th><th>Jenis</th><th class="angka">Kuantitas</th><th class="angka">Pendapatan</th></tr>
            </thead>
            <tbody>
                <?php if (!$rekap): ?>
                    <tr><td class="kosong" colspan="5">Belum ada data.</td></tr>
                <?php endif; ?>
                <?php foreach ($rekap as $r): ?>
                    <tr>
                        <td><?= (int) $r['idbarang'] ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['jenis']) ?></td>
                        <td><?= (int) $r['kuantitas'] ?></td>
                        <td class="angka"><?= rupiah($r['pendapatan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
