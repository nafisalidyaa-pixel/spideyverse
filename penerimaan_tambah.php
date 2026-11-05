<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$errors = [];
$idpengadaan = (int) ($_POST['idpengadaan'] ?? $_GET['pengadaan'] ?? 0);

/* =====================================================
   PROSES SIMPAN PENERIMAAN
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $terima = $_POST['terima'] ?? [];

    if ($idpengadaan <= 0) {
        $errors[] = 'Pengadaan tidak valid.';
    }

    $header = null;
    if (!$errors) {
        $header = db_one(
            "SELECT p.idpengadaan, p.status FROM pengadaan p WHERE p.idpengadaan = ?",
            [$idpengadaan]
        );
        if (!$header) {
            $errors[] = 'Pengadaan tidak ditemukan.';
        } elseif ($header['status'] !== 'A') {
            $errors[] = 'Pengadaan harus berstatus disetujui.';
        }
    }

    $items = [];
    if (!$errors) {
        $detail = db_all(
            "SELECT dp.iddetail_pengadaan, dp.idbarang, dp.harga_satuan, dp.jumlah,
                    COALESCE((SELECT SUM(dpe.jumlah_terima)
                              FROM detail_penerimaan dpe
                              JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
                              WHERE pr.idpengadaan = dp.idpengadaan
                                AND dpe.barang_idbarang = dp.idbarang), 0) AS sudah
             FROM detail_pengadaan dp
             WHERE dp.idpengadaan = ?
             ORDER BY dp.iddetail_pengadaan",
            [$idpengadaan]
        );

        foreach ($detail as $d) {
            $idDetail = (int) $d['iddetail_pengadaan'];
            $qty = (int) ($terima[$idDetail] ?? 0);
            $sisa = (int) $d['jumlah'] - (int) $d['sudah'];

            if ($qty < 0) {
                $errors[] = 'Jumlah terima tidak boleh negatif.';
                break;
            }
            if ($qty > $sisa) {
                $errors[] = 'Jumlah terima untuk barang #' . (int) $d['idbarang']
                    . ' melebihi sisa (' . $sisa . ').';
                break;
            }
            if ($qty > 0) {
                $items[] = [
                    'idbarang' => (int) $d['idbarang'],
                    'harga'    => (int) $d['harga_satuan'],
                    'jumlah'   => $qty,
                ];
            }
        }

        if (!$errors && !$items) {
            $errors[] = 'Isi minimal satu jumlah terima.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            db_exec(
                "INSERT INTO penerimaan (status, idpengadaan, iduser) VALUES ('A', ?, ?)",
                [$idpengadaan, id_user()]
            );
            $idPenerimaan = db_insert_id();

            foreach ($items as $item) {
                $subtotal = (int) db_val(
                    "SELECT fn_hitung_subtotal(?, ?)",
                    [$item['harga'], $item['jumlah']]
                );

                db_exec(
                    "INSERT INTO detail_penerimaan
                        (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
                     VALUES (?, ?, ?, ?, ?)",
                    [$idPenerimaan, $item['idbarang'], $item['jumlah'], $item['harga'], $subtotal]
                );
            }

            $pdo->commit();
            flash('sukses', 'Penerimaan #' . $idPenerimaan . ' berhasil dicatat.');
            redirect_dengan_log('penerimaan_detail.php?id=' . $idPenerimaan);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = pesan_db($e);
        }
    }
}

/* =====================================================
   DATA UNTUK TAMPILAN
===================================================== */
$header = null;
$detail = [];

if ($idpengadaan > 0) {
    $header = db_one(
        "SELECT p.idpengadaan, p.timestamp, p.status, v.nama_vendor, p.total_nilai
         FROM pengadaan p
         JOIN vendor v ON v.idvendor = p.vendor_idvendor
         WHERE p.idpengadaan = ?",
        [$idpengadaan]
    );

    if ($header && $header['status'] === 'A') {
        $detail = db_all(
            "SELECT dp.iddetail_pengadaan, dp.idbarang, dp.harga_satuan, dp.jumlah,
                    b.nama, b.jenis,
                    COALESCE((SELECT SUM(dpe.jumlah_terima)
                              FROM detail_penerimaan dpe
                              JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
                              WHERE pr.idpengadaan = dp.idpengadaan
                                AND dpe.barang_idbarang = dp.idbarang), 0) AS sudah
             FROM detail_pengadaan dp
             JOIN barang b ON b.idbarang = dp.idbarang
             WHERE dp.idpengadaan = ?
             ORDER BY dp.iddetail_pengadaan",
            [$idpengadaan]
        );
    }
}

$daftarPengadaan = [];
if ($idpengadaan === 0) {
    $daftarPengadaan = db_all(
        "SELECT p.idpengadaan, p.timestamp, v.nama_vendor, p.total_nilai,
                (SELECT COUNT(*) FROM detail_pengadaan dp
                 WHERE dp.idpengadaan = p.idpengadaan) AS jml_item,
                (SELECT COALESCE(SUM(dp.jumlah), 0) FROM detail_pengadaan dp
                 WHERE dp.idpengadaan = p.idpengadaan) AS total_qty,
                (SELECT COALESCE(SUM(dpe.jumlah_terima), 0)
                 FROM detail_penerimaan dpe
                 JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
                 WHERE pr.idpengadaan = p.idpengadaan) AS diterima
         FROM pengadaan p
         JOIN vendor v ON v.idvendor = p.vendor_idvendor
         WHERE p.status = 'A'
         ORDER BY p.idpengadaan DESC"
    );
    $daftarPengadaan = array_values(
        array_filter($daftarPengadaan, fn($p) => (int) $p['diterima'] < (int) $p['total_qty'])
    );
}

$JUDUL = 'Penerimaan Barang Baru';
$AKTIF = 'penerimaan.php';
require __DIR__ . '/inc/header.php';
?>

<?php foreach ($errors as $pesan): ?>
    <div class="alert alert-error"><?= e($pesan) ?></div>
<?php endforeach; ?>

<?php if ($idpengadaan === 0): ?>
    <div class="panel">
        <h2>Pilih Pengadaan yang Disetujui</h2>
        <div class="tabel-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Tanggal</th><th>Vendor</th>
                        <th class="angka">Item</th><th class="angka">Kuantitas</th>
                        <th class="angka">Diterima</th><th class="angka">Sisa</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$daftarPengadaan): ?>
                        <tr><td class="kosong" colspan="8">
                            Tidak ada pengadaan disetujui yang belum diterima penuh.
                            Setujui pengadaan dulu di halaman Pengadaan.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($daftarPengadaan as $p): ?>
                        <tr>
                            <td><?= (int) $p['idpengadaan'] ?></td>
                            <td><?= e(tanggal($p['timestamp'])) ?></td>
                            <td><?= e($p['nama_vendor']) ?></td>
                            <td class="angka"><?= (int) $p['jml_item'] ?></td>
                            <td class="angka"><?= (int) $p['total_qty'] ?></td>
                            <td class="angka"><?= (int) $p['diterima'] ?></td>
                            <td class="angka"><?= (int) $p['total_qty'] - (int) $p['diterima'] ?></td>
                            <td>
                                <a class="btn btn-kecil" href="penerimaan_tambah.php?pengadaan=<?= (int) $p['idpengadaan'] ?>">Pilih</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <?php if (!$header): ?>
        <div class="panel">
            <div class="alert alert-error">Pengadaan tidak ditemukan.</div>
            <a class="btn btn-outline" href="penerimaan_tambah.php">Kembali</a>
        </div>
    <?php elseif ($header['status'] !== 'A'): ?>
        <div class="panel">
            <div class="alert alert-error">Pengadaan belum disetujui. Setujui dulu di halaman Pengadaan.</div>
            <a class="btn btn-outline" href="pengadaan_detail.php?id=<?= (int) $idpengadaan ?>">Buka Pengadaan</a>
        </div>
    <?php else: ?>
        <div class="panel">
            <div class="judul-halaman">
                <div>
                    <h2>Penerimaan untuk Pengadaan #<?= (int) $header['idpengadaan'] ?></h2>
                    <div class="info-baris">
                        <span>Vendor: <b><?= e($header['nama_vendor']) ?></b></span>
                        <span>Tanggal pengadaan: <b><?= e(tanggal($header['timestamp'])) ?></b></span>
                        <span>Dicatat Oleh: <b><?= e(nama_user()) ?></b></span>
                    </div>
                </div>
                <a class="btn btn-outline" href="penerimaan_tambah.php">Ganti Pengadaan</a>
            </div>

            <form method="post" action="penerimaan_tambah.php">
                <input type="hidden" name="idpengadaan" value="<?= (int) $idpengadaan ?>">

                <div class="tabel-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Barang</th><th>Jenis</th>
                                <th class="angka">Harga</th><th class="angka">Dipesan</th>
                                <th class="angka">Sudah Diterima</th><th class="angka">Sisa</th>
                                <th class="angka">Terima Sekarang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detail as $d): ?>
                                <?php
                                $sisa = (int) $d['jumlah'] - (int) $d['sudah'];
                                ?>
                                <tr>
                                    <td><?= e($d['nama']) ?></td>
                                    <td><?= e($d['jenis']) ?></td>
                                    <td class="angka"><?= rupiah($d['harga_satuan']) ?></td>
                                    <td class="angka"><?= (int) $d['jumlah'] ?></td>
                                    <td class="angka"><?= (int) $d['sudah'] ?></td>
                                    <td class="angka"><b><?= $sisa ?></b></td>
                                    <td class="angka">
                                        <input type="number" style="max-width:110px;margin-left:auto"
                                               name="terima[<?= (int) $d['iddetail_pengadaan'] ?>]"
                                               min="0" max="<?= $sisa ?>" value="<?= $sisa ?>"
                                               <?= $sisa <= 0 ? 'disabled' : '' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-aksi">
                    <button type="submit" class="btn">Simpan Penerimaan</button>
                    <a class="btn btn-outline" href="penerimaan.php">Batal</a>
                </div>
                <p class="mt"><small>Stok barang akan otomatis bertambah setelah penerimaan disimpan.</small></p>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
