<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$id = (int) ($_GET['id'] ?? 0);

$header = db_one(
    "SELECT pr.idpenerimaan, pr.created_at, pr.status, pr.idpengadaan,
            v.nama_vendor, u.username
     FROM penerimaan pr
     JOIN pengadaan p ON p.idpengadaan = pr.idpengadaan
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     JOIN `user` u ON u.iduser = pr.iduser
     WHERE pr.idpenerimaan = ?",
    [$id]
);

if (!$header) {
    flash('error', 'Penerimaan tidak ditemukan.');
    header('Location: penerimaan.php');
    exit;
}

$detail = db_all(
    "SELECT dpe.iddetail_penerimaan, dpe.jumlah_terima, dpe.harga_satuan_terima,
            dpe.sub_total_terima, dpe.barang_idbarang, b.nama, b.jenis,
            COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr
                      WHERE dr.iddetail_penerimaan = dpe.iddetail_penerimaan), 0) AS diretur
     FROM detail_penerimaan dpe
     JOIN barang b ON b.idbarang = dpe.barang_idbarang
     WHERE dpe.idpenerimaan = ?
     ORDER BY dpe.iddetail_penerimaan",
    [$id]
);

$retur = db_all(
    "SELECT r.idretur, r.created_at, dr.jumlah, dr.alasan, b.nama, u.username
     FROM retur r
     JOIN detail_retur dr ON dr.idretur = r.idretur
     JOIN detail_penerimaan dpe ON dpe.iddetail_penerimaan = dr.iddetail_penerimaan
     JOIN barang b ON b.idbarang = dpe.barang_idbarang
     JOIN `user` u ON u.iduser = r.iduser
     WHERE r.idpenerimaan = ?
     ORDER BY r.idretur DESC",
    [$id]
);

$totalNilai = array_sum(array_map(fn($d) => (int) $d['sub_total_terima'], $detail));

$JUDUL = 'Detail Penerimaan #' . $id;
$AKTIF = 'penerimaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <div class="judul-halaman">
        <div>
            <h2>Penerimaan #<?= (int) $header['idpenerimaan'] ?></h2>
            <div class="info-baris">
                <span>Pengadaan: <b>#<?= (int) $header['idpengadaan'] ?></b></span>
                <span>Vendor: <b><?= e($header['nama_vendor']) ?></b></span>
                <span>Dicatat Oleh: <b><?= e($header['username']) ?></b></span>
                <span>Tanggal: <b><?= e(tanggal($header['created_at'])) ?></b></span>
                <span>Status: <span class="badge badge-hijau"><?= e($header['status'] === 'A' ? 'Selesai' : 'Diproses') ?></span></span>
            </div>
        </div>
        <div class="aksi">
            <a class="btn btn-outline" href="penerimaan.php">Kembali</a>
            <a class="btn" href="retur_tambah.php">Buat Retur</a>
        </div>
    </div>

    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID Detail</th><th>Barang</th><th>Jenis</th>
                    <th class="angka">Jumlah Terima</th><th class="angka">Harga</th>
                    <th class="angka">Subtotal</th><th class="angka">Sudah Retur</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detail as $d): ?>
                    <tr>
                        <td><?= (int) $d['iddetail_penerimaan'] ?></td>
                        <td><?= e($d['nama']) ?></td>
                        <td><?= e($d['jenis']) ?></td>
                        <td class="angka"><?= (int) $d['jumlah_terima'] ?></td>
                        <td class="angka"><?= rupiah($d['harga_satuan_terima']) ?></td>
                        <td class="angka"><?= rupiah($d['sub_total_terima']) ?></td>
                        <td class="angka"><?= (int) $d['diretur'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel-putih panel mt">
        <div class="teks-kanan total-besar">Total Nilai: <?= rupiah($totalNilai) ?></div>
    </div>
</div>

<div class="panel">
    <h2>Riwayat Retur Penerimaan Ini</h2>
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr><th>ID Retur</th><th>Tanggal</th><th>Barang</th><th class="angka">Jumlah</th><th>Alasan</th><th>Dicatat Oleh</th></tr>
            </thead>
            <tbody>
                <?php if (!$retur): ?>
                    <tr><td class="kosong" colspan="6">Belum ada retur.</td></tr>
                <?php endif; ?>
                <?php foreach ($retur as $r): ?>
                    <tr>
                        <td><?= (int) $r['idretur'] ?></td>
                        <td><?= e(tanggal($r['created_at'])) ?></td>
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
