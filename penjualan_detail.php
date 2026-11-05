<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 3]);

$id = (int) ($_GET['id'] ?? 0);

$header = db_one(
    "SELECT p.*, u.username, m.persen
     FROM penjualan p
     JOIN `user` u ON u.iduser = p.iduser
     JOIN margin_penjualan m ON m.idmargin_penjualan = p.idmargin_penjualan
     WHERE p.idpenjualan = ?",
    [$id]
);

if (!$header) {
    flash('error', 'Transaksi tidak ditemukan.');
    header('Location: penjualan.php');
    exit;
}

if (id_role() === 3 && (int) $header['iduser'] !== id_user()) {
    flash('error', 'Anda hanya dapat melihat transaksi milik Anda.');
    header('Location: penjualan.php');
    exit;
}

$detail = db_all(
    "SELECT dp.iddetail_penjualan, dp.harga_satuan, dp.jumlah, dp.subtotal,
            b.idbarang, b.nama, b.jenis, s.nama_satuan
     FROM detail_penjualan dp
     JOIN barang b ON b.idbarang = dp.idbarang
     JOIN satuan s ON s.idsatuan = b.idsatuan
     WHERE dp.penjualan_idpenjualan = ?
     ORDER BY dp.iddetail_penjualan",
    [$id]
);

$JUDUL = 'Bukti Transaksi';
$AKTIF = 'penjualan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="struk-wrap">
    <div class="struk">
        <div class="struk-kepala">
            <div class="struk-brand">
                <div class="logo"><?php include __DIR__ . '/inc/logo_spider.php'; ?></div>
                <div>
                    <b>SPIDEYVERSE</b>
                    <small>Bukti Transaksi</small>
                </div>
            </div>
            <span class="badge badge-hijau">Lunas</span>
        </div>

        <div class="struk-meta">
            <div>
                <span>No. Transaksi</span>
                <b><?= sprintf('TRX-%05d', (int) $header['idpenjualan']) ?></b>
            </div>
            <div>
                <span>Tanggal</span>
                <b><?= e(tanggal($header['created_at'])) ?></b>
            </div>
            <div>
                <span>Kasir</span>
                <b><?= e($header['username']) ?></b>
            </div>
            <div>
                <span>Margin</span>
                <b><?= (float) $header['persen'] ?>%</b>
            </div>
        </div>

        <div class="struk-garis"></div>

        <div class="struk-daftar">
            <div class="struk-daftar-judul">
                <span>Rincian Item</span>
                <span>Subtotal</span>
            </div>

            <?php foreach ($detail as $d): ?>
                <div class="struk-item">
                    <div class="struk-item-info">
                        <b><?= e($d['nama']) ?></b>
                        <small>
                            <?= e($d['jenis']) ?> &middot;
                            <?= (int) $d['jumlah'] ?> <?= e($d['nama_satuan']) ?> &times;
                            <?= rupiah($d['harga_satuan']) ?>
                        </small>
                    </div>
                    <b class="struk-item-nilai"><?= rupiah($d['subtotal']) ?></b>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="struk-garis"></div>

        <div class="struk-ringkasan">
            <div>
                <span>Subtotal</span>
                <b><?= rupiah($header['subtotal_nilai']) ?></b>
            </div>
            <div>
                <span>PPN 11%</span>
                <b><?= rupiah($header['ppn']) ?></b>
            </div>
            <div class="struk-total">
                <span>Total Bayar</span>
                <b><?= rupiah($header['total_nilai']) ?></b>
            </div>
        </div>

        <div class="struk-kaki">
            <span>Terima kasih atas kepercayaan Anda.</span>
            <span>Dokumen ini dibuat secara elektronik.</span>
        </div>
    </div>

    <div class="struk-aksi">
        <a class="btn btn-outline" href="penjualan.php">Kembali ke Riwayat</a>
        <a class="btn" href="penjualan_tambah.php">Transaksi Baru</a>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
