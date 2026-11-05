<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$daftar = db_all(
    "SELECT p.idpengadaan, p.timestamp, p.status, p.subtotal_nilai, p.ppn, p.total_nilai,
            v.nama_vendor, u.username,
            (SELECT COUNT(*) FROM detail_pengadaan dp WHERE dp.idpengadaan = p.idpengadaan) AS jml_item,
            (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_pengadaan dp
             WHERE dp.idpengadaan = p.idpengadaan) AS total_qty,
            (SELECT COUNT(*) FROM penerimaan pr WHERE pr.idpengadaan = p.idpengadaan) AS jml_terima
     FROM pengadaan p
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     JOIN `user` u ON u.iduser = p.user_iduser
     ORDER BY p.idpengadaan DESC"
);

$JUDUL = 'Pengadaan';
$AKTIF = 'pengadaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="judul-halaman">
    <div class="sub">Daftar seluruh pengadaan barang beserta statusnya.</div>
    <a class="btn" href="pengadaan_tambah.php">+ Buat Pengadaan</a>
</div>

<div class="panel">
    <div class="tabel-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Tanggal</th><th>Vendor</th><th>Dibuat Oleh</th>
                    <th class="angka">Item</th><th class="angka">Kuantitas</th>
                    <th class="angka">Subtotal</th><th class="angka">PPN</th><th class="angka">Total</th>
                    <th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$daftar): ?>
                    <tr><td class="kosong" colspan="11">Belum ada pengadaan.</td></tr>
                <?php endif; ?>
                <?php foreach ($daftar as $p): ?>
                    <tr>
                        <td><?= (int) $p['idpengadaan'] ?></td>
                        <td><?= e(tanggal($p['timestamp'])) ?></td>
                        <td><?= e($p['nama_vendor']) ?></td>
                        <td><?= e($p['username']) ?></td>
                        <td class="angka"><?= (int) $p['jml_item'] ?></td>
                        <td class="angka"><?= (int) $p['total_qty'] ?></td>
                        <td class="angka"><?= rupiah($p['subtotal_nilai']) ?></td>
                        <td class="angka"><?= rupiah($p['ppn']) ?></td>
                        <td class="angka"><?= rupiah($p['total_nilai']) ?></td>
                        <td><?= badge_status_pengadaan($p['status']) ?></td>
                        <td>
                            <div class="aksi">
                                <a class="btn btn-kecil btn-outline"
                                   href="pengadaan_detail.php?id=<?= (int) $p['idpengadaan'] ?>">Detail</a>

                                <?php if ($p['status'] === 'D'): ?>
                                    <form method="post" action="pengadaan_setujui.php">
                                        <input type="hidden" name="id" value="<?= (int) $p['idpengadaan'] ?>">
                                        <button type="submit" class="btn btn-kecil">Setujui</button>
                                    </form>
                                    <?php if (id_role() === 1): ?>
                                        <form method="post" action="pengadaan_hapus.php"
                                              onsubmit="return confirm('Hapus pengadaan ini?')">
                                            <input type="hidden" name="id" value="<?= (int) $p['idpengadaan'] ?>">
                                            <button type="submit" class="btn btn-kecil btn-merah">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                <?php elseif ($p['status'] === 'A'): ?>
                                    <a class="btn btn-kecil"
                                       href="penerimaan_tambah.php?pengadaan=<?= (int) $p['idpengadaan'] ?>">Terima</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
