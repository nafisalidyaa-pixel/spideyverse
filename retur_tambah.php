<?php
// retur_tambah.php ⤳ form retur baru: validasi sisa penerimaan & stok, lalu simpan retur.
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

// Siapkan variabel form + nilai awal dari POST (biar form nggak kosong kalau gagal).
$errors = [];
$idDetail = (int) ($_POST['iddetail_penerimaan'] ?? 0);
$jumlah   = (int) ($_POST['jumlah'] ?? 1);
$alasan   = trim((string) ($_POST['alasan'] ?? ''));

// Proses cuma jalan kalau form dikirim lewat POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Validasi dasar: item wajib dipilih, jumlah > 0, alasan wajib diisi.
    if ($idDetail <= 0) {
        $errors[] = 'Item penerimaan wajib dipilih.';
    }
    if ($jumlah <= 0) {
        $errors[] = 'Jumlah retur harus lebih dari nol.';
    }
    if ($alasan === '') {
        $errors[] = 'Alasan retur wajib diisi.';
    }

    // Kalau validasi dasar lolos, cek data penerimaan + hitung sisa & stok.
    if (!$errors) {
        $detail = db_one(
            "SELECT dpe.iddetail_penerimaan, dpe.idpenerimaan, dpe.barang_idbarang, dpe.jumlah_terima,
                    COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr
                              WHERE dr.iddetail_penerimaan = dpe.iddetail_penerimaan), 0) AS sudah_retur,
                    COALESCE((SELECT SUM(k.masuk - k.keluar) FROM kartu_stok k
                              WHERE k.idbarang = dpe.barang_idbarang), 0) AS stok
             FROM detail_penerimaan dpe
             WHERE dpe.iddetail_penerimaan = ?",
            [$idDetail]
        );

        // Data penerimaan nggak ada? ya gagal.
        if (!$detail) {
            $errors[] = 'Data penerimaan tidak ditemukan.';
        } else {
            // Sisa yang boleh diretur = jumlah terima dikurangi yang sudah pernah diretur,
            // tapi tetap dibatasi stok yang masih ada (barang yang sudah terjual nggak bisa diretur).
            $sisa = (int) $detail['jumlah_terima'] - (int) $detail['sudah_retur'];
            $stok = (int) $detail['stok'];
            $maks = min($sisa, $stok);
            if ($jumlah > $maks) {
                $errors[] = 'Jumlah retur maksimal ' . $maks . ' pcs '
                    . '(sisa penerimaan ' . $sisa . ', stok tersedia ' . $stok . '). '
                    . 'Barang yang sudah terjual tidak dapat diretur ke vendor.';
            }
        }
    }

    // Kalau semua valid, simpan retur dalam satu transaksi.
    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Insert header retur dulu, lalu ambil id-nya.
            db_exec(
                "INSERT INTO retur (idpenerimaan, iduser) VALUES (?, ?)",
                [(int) $detail['idpenerimaan'], id_user()]
            );
            $idRetur = db_insert_id();

            // Insert detail retur → memicu trigger trg_retur_stok (database/02_trigger.sql).
            // Trigger yang validasi sisa penerimaan & stok, terus ngurangi stok (kartu_stok jenis R).
            db_exec(
                "INSERT INTO detail_retur (jumlah, alasan, idretur, iddetail_penerimaan)
                 VALUES (?, ?, ?, ?)",
                [$jumlah, $alasan, $idRetur, $idDetail]
            );

            $pdo->commit();
            flash('sukses', 'Retur #' . $idRetur . ' berhasil diproses.');
            redirect_dengan_log('retur_detail.php?id=' . $idRetur);
        } catch (PDOException $e) {
            // Ada error (misal ditolak trigger) → rollback biar nggak setengah jadi.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = pesan_db($e);
        }
    }
}

// Ambil semua item penerimaan yang bisa diretur + info barang, pengadaan, vendor,
// plus hitung yang sudah diretur dan stok saat ini.
$pilihan = db_all(
    "SELECT dpe.iddetail_penerimaan, dpe.idpenerimaan, dpe.jumlah_terima,
            b.nama, b.jenis, p.idpengadaan, v.nama_vendor,
            COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr
                      WHERE dr.iddetail_penerimaan = dpe.iddetail_penerimaan), 0) AS sudah_retur,
            COALESCE((SELECT SUM(k.masuk - k.keluar) FROM kartu_stok k
                      WHERE k.idbarang = dpe.barang_idbarang), 0) AS stok
     FROM detail_penerimaan dpe
     JOIN barang b ON b.idbarang = dpe.barang_idbarang
     JOIN penerimaan pr ON pr.idpenerimaan = dpe.idpenerimaan
     JOIN pengadaan p ON p.idpengadaan = pr.idpengadaan
     JOIN vendor v ON v.idvendor = p.vendor_idvendor
     ORDER BY dpe.iddetail_penerimaan DESC"
);

// Saring: cuma tampilkan yang masih ada sisa penerimaan dan stoknya.
$pilihan = array_values(array_filter(
    $pilihan,
    fn($d) => ((int) $d['jumlah_terima'] - (int) $d['sudah_retur']) > 0
        && (int) $d['stok'] > 0
));

// Tampilkan halaman form retur.
$JUDUL = 'Buat Retur Baru';
$AKTIF = 'retur.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <h2>Form Retur Barang</h2>
    <p><small>Stok akan otomatis berkurang setelah retur disimpan. Jumlah maksimal retur
        dihitung dari sisa penerimaan dan stok yang tersedia, karena barang yang
        sudah terjual tidak dapat diretur ke vendor.</small></p>

    <?php foreach ($errors as $pesan): ?>
        <div class="alert alert-error"><?= e($pesan) ?></div>
    <?php endforeach; ?>

    <?php if (!$pilihan): ?>
        <div class="alert alert-info">Tidak ada item penerimaan yang masih bisa diretur, atau stok barang penerimaan sudah habis terjual.</div>
    <?php else: ?>
        <form method="post" action="retur_tambah.php" onsubmit="return cekJumlah()">
            <div class="form-grid">
                <div style="grid-column:1/-1">
                    <label for="pilih">Item Penerimaan</label>
                    <select id="pilih" name="iddetail_penerimaan" required onchange="tampilSisa()">
                        <option value="">- pilih barang dari penerimaan -</option>
                        <?php foreach ($pilihan as $d): ?>
                            <?php
                            $sisa  = (int) $d['jumlah_terima'] - (int) $d['sudah_retur'];
                            $stok  = (int) $d['stok'];
                            $maks  = min($sisa, $stok);
                            ?>
                            <option value="<?= (int) $d['iddetail_penerimaan'] ?>"
                                    data-max="<?= $maks ?>"
                                    data-stok="<?= $stok ?>"
                                    data-sisa="<?= $sisa ?>"
                                    <?= $idDetail === (int) $d['iddetail_penerimaan'] ? 'selected' : '' ?>>
                                #<?= (int) $d['iddetail_penerimaan'] ?> -
                                <?= e($d['nama']) ?> |
                                penerimaan #<?= (int) $d['idpenerimaan'] ?> |
                                maks <?= $maks ?> pcs
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="jumlah">Jumlah Retur</label>
                    <input type="number" id="jumlah" name="jumlah" min="1"
                           value="<?= e($jumlah > 0 ? $jumlah : 1) ?>" required>
                    <small id="infoSisa">Maks. retur: -</small>
                </div>
                <div>
                    <label for="alasan">Alasan Retur</label>
                    <input type="text" id="alasan" name="alasan" maxlength="200"
                           value="<?= e($alasan) ?>" placeholder="Contoh: Barang rusak" required>
                </div>
            </div>

            <div class="form-aksi">
                <button type="submit" class="btn">Simpan Retur</button>
                <a class="btn btn-outline" href="retur.php">Batal</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// Ambil option yang lagi dipilih di dropdown item penerimaan.
function ambilOpsi() {
    const pilih = document.getElementById('pilih');
    if (!pilih || !pilih.value) {
        return null;
    }
    return pilih.options[pilih.selectedIndex];
}

// Tampilkan info sisa penerimaan, stok tersedia, dan batas maksimal retur.
function tampilSisa() {
    const opsi = ambilOpsi();
    const info = document.getElementById('infoSisa');
    const input = document.getElementById('jumlah');
    if (!info) {
        return;
    }

    if (!opsi) {
        info.textContent = 'Maks. retur: -';
        return;
    }

    const max = Number(opsi.dataset.max || 0);
    const sisa = opsi.dataset.sisa || '0';
    const stok = opsi.dataset.stok || '0';

    info.textContent = 'Sisa penerimaan: ' + sisa + ' pcs · Stok tersedia: ' + stok
        + ' pcs · Maks. retur: ' + max + ' pcs';

    if (input) {
        input.setAttribute('max', max);
        if (Number(input.value || 0) > max) {
            input.value = max;
        }
    }
}

// Cek jumlah retur di browser sebelum form dikirim (validasi server tetap jalan).
function cekJumlah() {
    const opsi = ambilOpsi();
    if (!opsi) {
        return true;
    }
    const max = Number(opsi.dataset.max || 0);
    const jumlah = Number(document.getElementById('jumlah').value || 0);
    if (jumlah > max) {
        alert('Jumlah retur maksimal ' + max + ' pcs '
            + '(sisa penerimaan ' + opsi.dataset.sisa + ', stok tersedia ' + opsi.dataset.stok + ').');
        return false;
    }
    return true;
}

// Jalankan sekali saat halaman dibuka.
tampilSisa();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
