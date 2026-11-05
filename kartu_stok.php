<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2, 3]);

$idbarang = (int) ($_GET['barang'] ?? 0);
$jenis    = trim($_GET['jenis'] ?? '');

$where = [];
$params = [];

if ($idbarang > 0) {
    $where[] = 't.idbarang = ?';
    $params[] = $idbarang;
}
if (in_array($jenis, ['M', 'K', 'R'], true)) {
    $where[] = 't.jenis_transaksi = ?';
    $params[] = $jenis;
}

$sql =
    "SELECT * FROM (
        SELECT ks.idkartu_stok, ks.created_at, ks.jenis_transaksi, ks.masuk, ks.keluar,
               ks.stock, ks.idtransaksi, ks.idbarang, b.nama, b.jenis,
               SUM(ks.masuk - ks.keluar) OVER (
                   PARTITION BY ks.idbarang ORDER BY ks.idkartu_stok
               ) AS stok_jalan
        FROM kartu_stok ks
        JOIN barang b ON b.idbarang = ks.idbarang
     ) t";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY t.idkartu_stok DESC LIMIT 300';

$mutasi = db_all($sql, $params);
$daftarBarang = db_all("SELECT idbarang, nama FROM barang ORDER BY nama");

$JUDUL = 'Kartu Stok';
$AKTIF = 'kartu_stok.php';
require __DIR__ . '/inc/header.php';
?>

<div class="judul-halaman">
    <div class="sub">
        Catatan keluar-masuk stok barang. Kode M berarti barang masuk dari penerimaan,
        K barang keluar karena penjualan, dan R barang keluar karena retur.
        Kolom stok berjalan menunjukkan sisa stok setelah setiap transaksi.
    </div>
</div>

<div class="panel">
    <form method="get" action="kartu_stok.php">
        <div class="form-grid">
            <div>
                <label for="barang">Barang</label>
                <select id="barang" name="barang">
                    <option value="">Semua barang</option>
                    <?php foreach ($daftarBarang as $b): ?>
                        <option value="<?= (int) $b['idbarang'] ?>" <?= $idbarang === (int) $b['idbarang'] ? 'selected' : '' ?>>
                            <?= e($b['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="jenis">Jenis Transaksi</label>
                <select id="jenis" name="jenis">
                    <option value="">Semua jenis</option>
                    <option value="M" <?= $jenis === 'M' ? 'selected' : '' ?>>M - Masuk (Penerimaan)</option>
                    <option value="K" <?= $jenis === 'K' ? 'selected' : '' ?>>K - Keluar (Penjualan)</option>
                    <option value="R" <?= $jenis === 'R' ? 'selected' : '' ?>>R - Keluar (Retur)</option>
                </select>
            </div>
        </div>
        <div class="form-aksi">
            <button type="submit" class="btn">Filter</button>
            <a class="btn btn-outline" href="kartu_stok.php">Reset</a>
        </div>
    </form>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Waktu</th><th>Barang</th><th>Jenis</th>
                    <th class="angka">Masuk</th><th class="angka">Keluar</th>
                    <th class="angka">Stok Berjalan</th><th class="angka">Stok Tercatat</th>
                    <th class="angka">ID Transaksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$mutasi): ?>
                    <tr><td class="kosong" colspan="9">Belum ada mutasi stok.</td></tr>
                <?php endif; ?>
                <?php foreach ($mutasi as $m): ?>
                    <tr>
                        <td><?= (int) $m['idkartu_stok'] ?></td>
                        <td><?= e(tanggal($m['created_at'])) ?></td>
                        <td><?= e($m['nama']) ?></td>
                        <td><?= badge_jenis_transaksi($m['jenis_transaksi']) ?></td>
                        <td class="angka"><?= (int) $m['masuk'] ?></td>
                        <td class="angka"><?= (int) $m['keluar'] ?></td>
                        <td class="angka"><b><?= (int) $m['stok_jalan'] ?></b></td>
                        <td class="angka"><?= (int) $m['stock'] ?></td>
                        <td class="angka"><?= (int) $m['idtransaksi'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="mt"><small>Maksimal 300 baris terbaru. Gunakan filter untuk mempersempit.</small></p>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
