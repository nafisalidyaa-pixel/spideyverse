<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$daftar = db_all(
    "SELECT pr.idpenerimaan, pr.created_at, pr.status, pr.idpengadaan,
            v.nama_vendor, u.username,
            (SELECT COUNT(*) FROM detail_penerimaan d WHERE d.idpenerimaan = pr.idpenerimaan) AS jml_item,
            (SELECT COALESCE(SUM(d.jumlah_terima), 0) FROM detail_penerimaan d
             WHERE d.idpenerimaan = pr.idpenerimaan) AS total_terima,
            (SELECT COALESCE(SUM(d.sub_total_terima), 0) FROM detail_penerimaan d
             WHERE d.idpenerimaan = pr.idpenerimaan) AS total_nilai
     FROM penerimaan pr
     JOIN pengadaan p ON p.idpengadaan = pr.idpengadaan
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     JOIN `user` u ON u.iduser = pr.iduser
     ORDER BY pr.idpenerimaan DESC"
);

$JUDUL = 'Penerimaan Barang';
$AKTIF = 'penerimaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="judul-halaman">
    <div class="sub">
        Setiap penerimaan yang disimpan otomatis menambah stok barang.
    </div>
    <a class="btn" href="penerimaan_tambah.php">+ Penerimaan Baru</a>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Tanggal</th><th>Pengadaan</th><th>Vendor</th>
                    <th>Dicatat Oleh</th><th class="angka">Item</th><th class="angka">Kuantitas</th>
                    <th class="angka">Nilai</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td class="kosong" colspan="10">Belum ada penerimaan.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $p): ?>
                    <tr>
                        <td><?= (int) $p['idpenerimaan'] ?></td>
                        <td><?= e(tanggal($p['created_at'])) ?></td>
                        <td>#<?= (int) $p['idpengadaan'] ?></td>
                        <td><?= e($p['nama_vendor']) ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td class="angka"><?= (int) $p['jml_item'] ?></td>
                        <td class="angka"><?= (int) $p['total_terima'] ?></td>
                        <td class="angka"><?= rupiah($p['total_nilai']) ?></td>
                        <td><span class="badge badge-hijau"><?= e($p['status']) ?></span></td>
                        <td>
                            <a class="btn btn-kecil btn-outline"
                               href="penerimaan_detail.php?id=<?= (int) $p['idpenerimaan'] ?>">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
