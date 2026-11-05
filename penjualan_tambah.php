<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 3]);

$errors = [];
$errorTrigger = '';

$barang = db_all(
    "SELECT b.idbarang, b.nama, b.jenis, b.harga,
            COALESCE((SELECT SUM(k.masuk - k.keluar) FROM kartu_stok k
                      WHERE k.idbarang = b.idbarang), 0) AS stok
     FROM barang b
     WHERE b.status = 1
     ORDER BY b.nama"
);

$margin = db_all(
    "SELECT idmargin_penjualan, persen
     FROM margin_penjualan
     WHERE status = 1
     ORDER BY idmargin_penjualan"
);

$idbarang = (int) ($_POST['idbarang'] ?? 0);
$idmargin = (int) ($_POST['idmargin'] ?? 0);
$jumlah   = (int) ($_POST['jumlah'] ?? 1);

/* =====================================================
   PROSES PENJUALAN -> CALL stored procedure
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($idbarang <= 0) {
        $errors[] = 'Barang wajib dipilih.';
    }
    if ($idmargin <= 0) {
        $errors[] = 'Margin penjualan wajib dipilih.';
    }
    if ($jumlah <= 0) {
        $errors[] = 'Jumlah harus lebih dari nol.';
    }

    if (!$errors) {
        try {
            catat_query(
                "CALL sp_tambah_penjualan(?, ?, ?, ?)",
                [id_user(), $idmargin, $idbarang, $jumlah]
            );

            $stmt = $pdo->prepare("CALL sp_tambah_penjualan(?, ?, ?, ?)");
            $stmt->execute([id_user(), $idmargin, $idbarang, $jumlah]);
            $hasil = $stmt->fetch();
            $stmt->closeCursor();

            if (!$hasil) {
                throw new RuntimeException('Transaksi tidak dapat diselesaikan.');
            }

            flash(
                'sukses',
                'Penjualan #' . $hasil['id_penjualan'] . ' berhasil diproses. Total ' . rupiah($hasil['total_pembayaran']) . '.'
            );
            redirect_dengan_log('penjualan_detail.php?id=' . (int) $hasil['id_penjualan']);
        } catch (PDOException $e) {
            $errorTrigger = pesan_db($e);
        } catch (RuntimeException $e) {
            $errorTrigger = $e->getMessage();
        }
    }
}

$JUDUL = 'Transaksi Penjualan';
$AKTIF = 'penjualan_tambah.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <h2>Transaksi Penjualan</h2>
    <p><small>
        Harga jual dihitung dari harga dasar ditambah margin, lalu dikenakan PPN 11%.
        Stok akan otomatis berkurang setelah transaksi disimpan.
    </small></p>

    <?php foreach ($errors as $pesan): ?>
        <div class="alert alert-error"><?= e($pesan) ?></div>
    <?php endforeach; ?>

    <?php if ($errorTrigger !== ''): ?>
        <div class="alert alert-error">
            <b>Transaksi tidak dapat diproses:</b> <?= e($errorTrigger) ?>
        </div>
    <?php endif; ?>

    <?php if (!$margin): ?>
        <div class="alert alert-error">Tidak ada margin aktif. Aktifkan margin dulu di menu Margin Penjualan.</div>
    <?php endif; ?>

    <form method="post" action="penjualan_tambah.php">
        <div class="form-grid">
            <div style="grid-column:1/-1">
                <label for="idbarang">Barang</label>
                <select id="idbarang" name="idbarang" required onchange="hitung()">
                    <option value="">- pilih barang -</option>
                    <?php foreach ($barang as $b): ?>
                        <option value="<?= (int) $b['idbarang'] ?>"
                                data-harga="<?= (int) $b['harga'] ?>"
                                data-stok="<?= (int) $b['stok'] ?>"
                                data-nama="<?= e($b['nama']) ?>"
                                <?= $idbarang === (int) $b['idbarang'] ? 'selected' : '' ?>>
                            <?= e($b['nama']) ?> &middot; stok <?= (int) $b['stok'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="idmargin">Margin</label>
                <select id="idmargin" name="idmargin" required onchange="hitung()">
                    <option value="">- pilih margin -</option>
                    <?php foreach ($margin as $m): ?>
                        <option value="<?= (int) $m['idmargin_penjualan'] ?>"
                                data-persen="<?= (float) $m['persen'] ?>"
                                <?= $idmargin === (int) $m['idmargin_penjualan'] ? 'selected' : '' ?>>
                            <?= (float) $m['persen'] ?> %
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="jumlah">Jumlah</label>
                <input type="number" id="jumlah" name="jumlah" min="1"
                       value="<?= e($jumlah > 0 ? $jumlah : 1) ?>" required onchange="hitung()" oninput="hitung()">
            </div>
        </div>

        <div class="panel-putih panel mt">
            <div class="info-baris">
                <span>Harga dasar: <b id="infoHarga">Rp 0</b></span>
                <span>Stok tersedia: <b id="infoStok">-</b></span>
                <span>Harga setelah margin: <b id="infoJual">Rp 0</b></span>
            </div>
            <div class="teks-kanan">
                <div>Subtotal: <b id="teksSubtotal">Rp 0</b></div>
                <div>PPN 11%: <b id="teksPpn">Rp 0</b></div>
                <div class="total-besar">Total Bayar: <span id="teksTotal">Rp 0</span></div>
            </div>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn" <?= !$margin ? 'disabled' : '' ?>>Proses Penjualan</button>
            <a class="btn btn-outline" href="penjualan.php">Riwayat</a>
        </div>
    </form>
</div>

<script>
function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function hitung() {
    const pilihBarang = document.getElementById('idbarang');
    const pilihMargin = document.getElementById('idmargin');
    const jumlah = Number(document.getElementById('jumlah').value || 0);

    let harga = 0;
    let stok = '-';

    if (pilihBarang.value) {
        const ob = pilihBarang.options[pilihBarang.selectedIndex];
        harga = Number(ob.dataset.harga || 0);
        stok = ob.dataset.stok || '-';
    }

    let persen = 0;
    if (pilihMargin.value) {
        const om = pilihMargin.options[pilihMargin.selectedIndex];
        persen = Number(om.dataset.persen || 0);
    }

    const hargaJual = Math.round(harga + (harga * persen / 100));
    const subtotal = hargaJual * jumlah;
    const ppn = Math.round(subtotal * 0.11);

    document.getElementById('infoHarga').textContent = rupiah(harga);
    document.getElementById('infoStok').textContent = stok;
    document.getElementById('infoJual').textContent = rupiah(hargaJual) + ' (' + persen + '%)';
    document.getElementById('teksSubtotal').textContent = rupiah(subtotal);
    document.getElementById('teksPpn').textContent = rupiah(ppn);
    document.getElementById('teksTotal').textContent = rupiah(subtotal + ppn);
}

hitung();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
