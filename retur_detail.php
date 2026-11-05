<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$id = (int) ($_GET['id'] ?? 0);

$header = db_one(
    "SELECT r.idretur, r.created_at, r.idpenerimaan, u.username,
            v.nama_vendor, pr.created_at AS tgl_penerimaan
     FROM retur r
     JOIN `user` u ON u.iduser = r.iduser
     JOIN penerimaan pr ON pr.idpenerimaan = r.idpenerimaan
     JOIN pengadaan p ON p.idpengadaan = pr.idpengadaan
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     WHERE r.idretur = ?",
    [$id]
);

if (!$header) {
    flash('error', 'Retur tidak ditemukan.');
    header('Location: retur.php');
    exit;
}

$detail = db_all(
    "SELECT dr.iddetail_retur, dr.jumlah, dr.alasan, dr.iddetail_penerimaan,
            dpe.jumlah_terima, dpe.harga_satuan_terima,
            b.nama, b.jenis
     FROM detail_retur dr
     JOIN detail_penerimaan dpe ON dpe.iddetail_penerimaan = dr.iddetail_penerimaan
     JOIN barang b ON b.idbarang = dpe.barang_idbarang
     WHERE dr.idretur = ?
     ORDER BY dr.iddetail_retur",
    [$id]
);

$JUDUL = 'Detail Retur #' . $id;
$AKTIF = 'retur.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <div class="judul-halaman">
        <div>
            <h2>Retur #<?= (int) $header['idretur'] ?></h2>
            <div class="info-baris">
                <span>Penerimaan: <b>#<?= (int) $header['idpenerimaan'] ?></b></span>
                <span>Vendor: <b><?= e($header['nama_vendor']) ?></b></span>
                <span>Dicatat Oleh: <b><?= e($header['username']) ?></b></span>
                <span>Tanggal: <b><?= e(tanggal($header['created_at'])) ?></b></span>
            </div>
        </div>
        <div class="aksi">
            <a class="btn btn-outline" href="retur.php">Kembali</a>
            <a class="btn btn-outline" href="penerimaan_detail.php?id=<?= (int) $header['idpenerimaan'] ?>">Lihat Penerimaan</a>
        </div>
    </div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID Detail</th><th>Barang</th><th>Jenis</th>
                    <th class="angka">Jumlah Retur</th><th class="angka">Jumlah Terima</th>
                    <th class="angka">Harga</th><th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?= (int) $d['iddetail_retur'] ?></td>
                        <td><?= e($d['nama']) ?></td>
                        <td><?= e($d['jenis']) ?></td>
                        <td class="angka"><?= (int) $d['jumlah'] ?></td>
                        <td class="angka"><?= (int) $d['jumlah_terima'] ?></td>
                        <td class="angka"><?= rupiah($d['harga_satuan_terima']) ?></td>
                        <td><?= e($d['alasan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
