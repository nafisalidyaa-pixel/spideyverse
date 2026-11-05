<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$daftar = db_all(
    "SELECT r.idretur, r.created_at, r.idpenerimaan, u.username,
            (SELECT COUNT(*) FROM detail_retur d WHERE d.idretur = r.idretur) AS jml_item,
            (SELECT COALESCE(SUM(d.jumlah), 0) FROM detail_retur d
             WHERE d.idretur = r.idretur) AS total_qty
     FROM retur r
     JOIN `user` u ON u.iduser = r.iduser
     ORDER BY r.idretur DESC"
);

$JUDUL = 'Retur Barang';
$AKTIF = 'retur.php';
require __DIR__ . '/inc/header.php';
?>

<div class="judul-halaman">
    <div class="sub">
        Retur akan mengurangi stok barang dan divalidasi agar tidak melebihi
        jumlah penerimaan.
    </div>
    <a class="btn" href="retur_tambah.php">+ Retur Baru</a>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Tanggal</th><th>Penerimaan</th><th>Dicatat Oleh</th>
                    <th class="angka">Item</th><th class="angka">Total Kuantitas</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td class="kosong" colspan="7">Belum ada retur.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $r): ?>
                    <tr>
                        <td><?= (int) $r['idretur'] ?></td>
                        <td><?= e(tanggal($r['created_at'])) ?></td>
                        <td>#<?= (int) $r['idpenerimaan'] ?></td>
                        <td><?= e($r['username']) ?></td>
                        <td class="angka"><?= (int) $r['jml_item'] ?></td>
                        <td class="angka"><?= (int) $r['total_qty'] ?></td>
                        <td>
                            <a class="btn btn-kecil btn-outline"
                               href="retur_detail.php?id=<?= (int) $r['idretur'] ?>">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
