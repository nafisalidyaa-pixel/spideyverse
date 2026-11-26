<?php
// Detail pengadaan: tampilkan header, detail barang, dan jumlah yang sudah diterima.
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

// Ambil header pengadaan + vendor + user.
$id = (int) ($_GET['id'] ?? 0);
$header = db_one(
    "SELECT p.*, v.nama_vendor, u.username
     FROM pengadaan p
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     JOIN `user` u ON u.iduser = p.user_iduser
     WHERE p.idpengadaan = ?",
    [$id]
);

// Kalau data tidak ada, balik ke daftar.
if (!$header) {
    flash('error', 'Pengadaan tidak ditemukan.');
    header('Location: pengadaan.php');
    exit;
}

// Ambil detail barang + hitung jumlah yang sudah diterima per barang.
$detail = db_all(
    "SELECT dp.iddetail_pengadaan, dp.harga_satuan, dp.jumlah, dp.sub_total, dp.idbarang,
            b.nama, b.jenis,
            COALESCE((SELECT SUM(dpe.jumlah_terima)
                      FROM detail_penerimaan dpe
                      JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
                      WHERE pr.idpengadaan = dp.idpengadaan
                        AND dpe.barang_idbarang = dp.idbarang), 0) AS diterima
     FROM detail_pengadaan dp
     JOIN barang b ON b.idbarang = dp.idbarang
     WHERE dp.idpengadaan = ?
     ORDER BY dp.iddetail_pengadaan",
    [$id]
);

// Tampilkan halaman.
$JUDUL = 'Detail Pengadaan #' . $id;
$AKTIF = 'pengadaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <div class="judul-halaman">
        <div>
            <h2>Pengadaan #<?= (int) $header['idpengadaan'] ?></h2>
            <div class="info-baris">
                <span>Vendor: <b><?= e($header['nama_vendor']) ?></b></span>
                <span>Dibuat oleh: <b><?= e($header['username']) ?></b></span>
                <span>Tanggal: <b><?= e(tanggal($header['timestamp'])) ?></b></span>
                <span>Status: <?= badge_status_pengadaan($header['status']) ?></span>
            </div>
        </div>
        <div class="aksi">
            <a class="btn btn-outline" href="pengadaan.php">Kembali</a>
            <?php // Kalau masih menunggu, tampilkan tombol setujui; kalau sudah A, tombol terima. ?>
            <?php if ($header['status'] === 'P'): ?>
                <form method="post" action="pengadaan_setujui.php">
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <button type="submit" class="btn">Setujui Pengadaan</button>
                </form>
            <?php else: ?>
                <a class="btn" href="penerimaan_tambah.php?pengadaan=<?= (int) $id ?>">Buat Penerimaan</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID Detail</th><th>Barang</th><th>Jenis</th>
                    <th class="angka">Harga Satuan</th><th class="angka">Jumlah</th>
                    <th class="angka">Subtotal</th><th class="angka">Sudah Diterima</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?= (int) $d['iddetail_pengadaan'] ?></td>
                        <td><?= e($d['nama']) ?></td>
                        <td><?= e($d['jenis']) ?></td>
                        <td class="angka"><?= rupiah($d['harga_satuan']) ?></td>
                        <td class="angka"><?= (int) $d['jumlah'] ?></td>
                        <td class="angka"><?= rupiah($d['sub_total']) ?></td>
                        <td class="angka"><?= (int) $d['diterima'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php // Ringkasan nilai pengadaan. ?>
    <div class="panel-putih panel mt">
        <div class="teks-kanan">
            <div>Subtotal: <b><?= rupiah($header['subtotal_nilai']) ?></b></div>
            <div>PPN 11%: <b><?= rupiah($header['ppn']) ?></b></div>
            <div class="total-besar">Total: <?= rupiah($header['total_nilai']) ?></div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
