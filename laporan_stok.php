<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2, 3]);

$jenis = trim($_GET['jenis'] ?? '');

$where = '';
$params = [];
if ($jenis !== '' && isset(jenis_barang()[$jenis])) {
    $where = 'WHERE b.jenis = ?';
    $params[] = $jenis;
}

$laporan = db_all(
    "SELECT b.idbarang, b.jenis, b.nama, s.nama_satuan, b.harga,
            COALESCE(SUM(ks.masuk), 0) AS total_masuk,
            COALESCE(SUM(ks.keluar), 0) AS total_keluar,
            COALESCE(SUM(ks.masuk - ks.keluar), 0) AS stok_akhir
     FROM barang b
     JOIN satuan s ON s.idsatuan = b.idsatuan
     LEFT JOIN kartu_stok ks ON ks.idbarang = b.idbarang
     {$where}
     GROUP BY b.idbarang, b.jenis, b.nama, s.nama_satuan, b.harga
     ORDER BY b.jenis, b.idbarang",
    $params
);

$totalMasuk = array_sum(array_map(fn($r) => (int) $r['total_masuk'], $laporan));
$totalKeluar = array_sum(array_map(fn($r) => (int) $r['total_keluar'], $laporan));
$totalStok = array_sum(array_map(fn($r) => (int) $r['stok_akhir'], $laporan));

$JUDUL = 'Laporan Stok Barang';
$AKTIF = 'laporan_stok.php';
require __DIR__ . '/inc/header.php';
?>

<div class="grid mb">
    <div class="kartu">
        <div class="ket">Total Barang Masuk</div>
        <div class="angka"><?= $totalMasuk ?></div>
    </div>
    <div class="kartu">
        <div class="ket">Total Barang Keluar</div>
        <div class="angka"><?= $totalKeluar ?></div>
    </div>
    <div class="kartu">
        <div class="ket">Stok Akhir</div>
        <div class="angka"><?= $totalStok ?></div>
    </div>
    <div class="kartu">
        <div class="ket">Jenis Barang Ditampilkan</div>
        <div class="angka"><?= count(array_unique(array_column($laporan, 'jenis'))) ?></div>
    </div>
</div>

<div class="panel">
    <form method="get" action="laporan_stok.php">
        <div class="form-grid">
            <div>
                <label for="jenis">Filter Jenis</label>
                <select id="jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    <?php foreach (jenis_barang() as $kode => $label): ?>
                        <option value="<?= e($kode) ?>" <?= $jenis === $kode ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-aksi">
            <button type="submit" class="btn">Terapkan</button>
            <a class="btn btn-outline" href="laporan_stok.php">Reset</a>
        </div>
    </form>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Jenis</th><th>Nama Barang</th><th>Satuan</th>
                    <th class="angka">Harga</th><th class="angka">Masuk</th>
                    <th class="angka">Keluar</th><th class="angka">Stok Akhir</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($laporan as $r): ?>
                    <tr>
                        <td><?= (int) $r['idbarang'] ?></td>
                        <td><?= e($r['jenis']) ?></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['nama_satuan']) ?></td>
                        <td class="angka"><?= rupiah($r['harga']) ?></td>
                        <td class="angka"><?= (int) $r['total_masuk'] ?></td>
                        <td class="angka"><?= (int) $r['total_keluar'] ?></td>
                        <td class="angka"><b><?= (int) $r['stok_akhir'] ?></b></td>
                        <td><?= badge_stok($r['stok_akhir']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
