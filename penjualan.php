<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 3]);

$params = [];
$where = '';
if (id_role() === 3) {
    $where = 'WHERE p.iduser = ?';
    $params[] = id_user();
}

$daftar = db_all(
    "SELECT p.idpenjualan, p.created_at, p.subtotal_nilai, p.ppn, p.total_nilai,
            u.username, m.persen,
            (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_penjualan dp
             WHERE dp.penjualan_idpenjualan = p.idpenjualan) AS item
     FROM penjualan p
     JOIN `user` u ON u.iduser = p.iduser
     JOIN margin_penjualan m ON m.idmargin_penjualan = p.idmargin_penjualan
     {$where}
     ORDER BY p.idpenjualan DESC",
    $params
);

$JUDUL = 'Riwayat Penjualan';
$AKTIF = 'penjualan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="judul-halaman">
    <div class="sub">
        <?= id_role() === 3 ? 'Menampilkan transaksi milik Anda.' : 'Menampilkan seluruh transaksi penjualan.' ?>
    </div>
    <a class="btn" href="penjualan_tambah.php">+ Transaksi Baru</a>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Tanggal</th><th>Kasir</th><th>Margin</th>
                    <th class="angka">Item</th><th class="angka">Subtotal</th>
                    <th class="angka">PPN</th><th class="angka">Total</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td class="kosong" colspan="9">Belum ada penjualan.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $p): ?>
                    <tr>
                        <td><?= (int) $p['idpenjualan'] ?></td>
                        <td><?= e(tanggal($p['created_at'])) ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td><?= (float) $p['persen'] ?> %</td>
                        <td class="angka"><?= (int) $p['item'] ?></td>
                        <td class="angka"><?= rupiah($p['subtotal_nilai']) ?></td>
                        <td class="angka"><?= rupiah($p['ppn']) ?></td>
                        <td class="angka"><b><?= rupiah($p['total_nilai']) ?></b></td>
                        <td>
                            <a class="btn btn-kecil btn-outline"
                               href="penjualan_detail.php?id=<?= (int) $p['idpenjualan'] ?>">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
