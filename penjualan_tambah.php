<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 3]);

$errors = [];
$errorTrigger = '';

$barang = db_all(
    "SELECT v.idbarang, v.nama, v.jenis, v.harga, v.stok
     FROM v_barang v
     WHERE v.status = 1
     ORDER BY v.nama"
);

$marginAuto = db_one(
    "SELECT m.idmargin_penjualan, m.persen
     FROM v_margin_penjualan m
     WHERE m.status = 1
     ORDER BY m.idmargin_penjualan
     LIMIT 1"
);

$idmarginAuto = $marginAuto ? (int) $marginAuto['idmargin_penjualan'] : 0;
$persenAuto   = $marginAuto ? (float) $marginAuto['persen'] : 0;

$baris = $_POST['baris'] ?? [];

/* =====================================================
   PROSES PENJUALAN: KERANJANG (BANYAK BARANG)
   Seluruh validasi & perhitungan berjalan di database.
===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];

    foreach ($baris as $row) {
        $idbarang = (int) ($row['idbarang'] ?? 0);
        $jumlah   = (int) ($row['jumlah'] ?? 0);

        if ($idbarang <= 0 && $jumlah <= 0) {
            continue;
        }
        if ($idbarang <= 0) {
            $errors[] = 'Barang pada salah satu baris belum dipilih.';
            continue;
        }
        if ($jumlah <= 0) {
            $errors[] = 'Jumlah pada salah satu baris harus lebih dari nol.';
            continue;
        }

        $items[] = ['idbarang' => $idbarang, 'jumlah' => $jumlah];
    }

    if (!$items) {
        $errors[] = 'Minimal satu barang harus diisi.';
    }

    if (!$errors) {
        try {
            catat_query(
                'CALL sp_tambah_penjualan_keranjang(?, ?, ?)',
                [id_user(), $idmarginAuto, json_encode($items)]
            );

            $stmt = $pdo->prepare('CALL sp_tambah_penjualan_keranjang(?, ?, ?)');
            $stmt->execute([id_user(), $idmarginAuto, json_encode($items)]);
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
        Harga jual dihitung otomatis dari harga dasar ditambah margin yang aktif, lalu dikenakan PPN 11%.
        Gunakan <b>+ Tambah Barang</b> untuk membeli beberapa barang dalam satu transaksi.
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

    <?php if (!$marginAuto): ?>
        <div class="alert alert-error">Belum ada margin aktif. Aktifkan margin di menu Margin Penjualan.</div>
    <?php endif; ?>

    <form method="post" action="penjualan_tambah.php">
        <div class="form-grid">
            <div>
                <label>Margin <span class="teks-otomatis">otomatis</span></label>
                <input type="text" readonly tabindex="-1"
                       value="<?= $marginAuto ? e((float) $marginAuto['persen'] . ' %') : 'Belum ada margin aktif' ?>">
                <input type="hidden" name="idmargin" value="<?= (int) $idmarginAuto ?>">
                <small class="hint-otomatis">Margin diambil dari margin yang berstatus aktif.</small>
            </div>
            <div>
                <label>Dibuat Oleh</label>
                <input type="text" value="<?= e(nama_user() . ' (' . nama_role() . ')') ?>" disabled>
            </div>
        </div>

        <h3 class="mt">Daftar Barang</h3>
        <div class="tabel-wrap">
            <table id="tabelItem">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th class="angka">Harga Jual</th>
                        <th class="angka">Stok</th>
                        <th class="angka">Jumlah</th>
                        <th class="angka">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="baris-item">
                        <td>
                            <select name="baris[0][idbarang]" class="pilih-barang" onchange="isiHarga(this)">
                                <option value="">- pilih barang -</option>
                                <?php foreach ($barang as $b): ?>
                                    <?php $stok = (int) $b['stok']; ?>
                                    <option value="<?= (int) $b['idbarang'] ?>"
                                            data-harga="<?= (int) $b['harga'] ?>"
                                            data-stok="<?= $stok ?>"
                                            <?= $stok <= 0 ? 'disabled' : '' ?>>
                                        <?= e($b['nama']) ?> (<?= rupiah($b['harga']) ?><?= $stok <= 0 ? ' · habis' : '' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="angka harga-jual-baris">Rp 0</td>
                        <td class="angka stok-baris">-</td>
                        <td class="angka">
                            <input type="number" name="baris[0][jumlah]" class="input-jumlah"
                                   min="1" max="1" value="1"
                                   onchange="hitungTotal()" oninput="hitungTotal()">
                        </td>
                        <td class="angka subtotal-baris">Rp 0</td>
                        <td><button type="button" class="btn btn-kecil btn-merah" onclick="hapusBaris(this)">X</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-aksi">
            <button type="button" class="btn btn-outline" onclick="tambahBaris()">+ Tambah Barang</button>
        </div>

        <div class="panel-putih panel mt">
            <div class="info-baris">
                <span>Margin: <b><?= $marginAuto ? e((float) $marginAuto['persen'] . '%') : '-' ?></b></span>
                <span>PPN 11%</span>
            </div>
            <div class="teks-kanan">
                <div>Subtotal: <b id="teksSubtotal">Rp 0</b></div>
                <div>PPN 11%: <b id="teksPpn">Rp 0</b></div>
                <div class="total-besar">Total Bayar: <span id="teksTotal">Rp 0</span></div>
            </div>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn" <?= !$marginAuto ? 'disabled' : '' ?>>Proses Penjualan</button>
            <a class="btn btn-outline" href="penjualan.php">Riwayat</a>
        </div>
    </form>
</div>

<script>
let nomorBaris = 1;
const MARGIN_PERSEN = <?= json_encode($persenAuto) ?>;

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function isiHarga(select) {
    const baris = select.closest('.baris-item');
    const opsi = select.options[select.selectedIndex];
    const harga = opsi ? Number(opsi.dataset.harga || 0) : 0;
    const stok = opsi ? Number(opsi.dataset.stok || 0) : 0;

    baris.querySelector('.stok-baris').textContent = stok > 0 ? stok : '-';

    const jumlah = baris.querySelector('.input-jumlah');
    jumlah.setAttribute('max', stok > 0 ? stok : 1);
    jumlah.value = Math.max(1, Math.min(Number(jumlah.value || 1), stok > 0 ? stok : 1));

    hitungTotal();
}

function tambahBaris() {
    const tbody = document.querySelector('#tabelItem tbody');
    const awal = tbody.querySelector('.baris-item');
    const baru = awal.cloneNode(true);
    const n = nomorBaris++;

    baru.querySelector('.pilih-barang').name = 'baris[' + n + '][idbarang]';
    baru.querySelector('.pilih-barang').selectedIndex = 0;
    baru.querySelector('.input-jumlah').name = 'baris[' + n + '][jumlah]';
    baru.querySelector('.input-jumlah').value = 1;
    baru.querySelector('.input-jumlah').setAttribute('max', 1);
    baru.querySelector('.harga-jual-baris').textContent = 'Rp 0';
    baru.querySelector('.stok-baris').textContent = '-';
    baru.querySelector('.subtotal-baris').textContent = 'Rp 0';

    tbody.appendChild(baru);
    hitungTotal();
}

function hapusBaris(btn) {
    const semua = document.querySelectorAll('.baris-item');
    if (semua.length <= 1) {
        return;
    }
    btn.closest('.baris-item').remove();
    hitungTotal();
}

function hitungTotal() {
    let subtotal = 0;

    document.querySelectorAll('.baris-item').forEach(function (baris) {
        const select = baris.querySelector('.pilih-barang');
        const opsi = select.options[select.selectedIndex];
        const hargaDasar = opsi ? Number(opsi.dataset.harga || 0) : 0;
        const stok = opsi ? Number(opsi.dataset.stok || 0) : 0;

        const jumlah = Number(baris.querySelector('.input-jumlah').value || 0);
        const hargaJual = Math.round(hargaDasar + (hargaDasar * MARGIN_PERSEN / 100));
        const sub = hargaJual * jumlah;

        baris.querySelector('.harga-jual-baris').textContent = rupiah(hargaJual);
        baris.querySelector('.subtotal-baris').textContent = rupiah(sub);
        subtotal += sub;

        const input = baris.querySelector('.input-jumlah');
        if (stok > 0 && jumlah > stok) {
            input.value = stok;
        }
    });

    const ppn = Math.round(subtotal * 0.11);
    document.getElementById('teksSubtotal').textContent = rupiah(subtotal);
    document.getElementById('teksPpn').textContent = rupiah(ppn);
    document.getElementById('teksTotal').textContent = rupiah(subtotal + ppn);
}

hitungTotal();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
