<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

$vendor = db_all("SELECT idvendor, nama_vendor FROM vendor WHERE status = 'A' ORDER BY nama_vendor");
$barang = db_all("SELECT idbarang, nama, harga FROM barang WHERE status = 1 ORDER BY nama");

$errors = [];
$vendorId = (int) ($_POST['vendor_idvendor'] ?? 0);
$baris = $_POST['baris'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($vendorId <= 0) {
        $errors[] = 'Vendor wajib dipilih.';
    }

    $items = [];
    foreach ($baris as $row) {
        $idbarang = (int) ($row['idbarang'] ?? 0);
        $harga    = (int) ($row['harga'] ?? 0);
        $jumlah   = (int) ($row['jumlah'] ?? 0);

        if ($idbarang <= 0 && $jumlah <= 0) {
            continue;
        }
        if ($idbarang <= 0) {
            $errors[] = 'Barang pada salah satu baris belum dipilih.';
            continue;
        }
        if ($jumlah <= 0) {
            $errors[] = 'Jumlah pada salah satu baris harus lebih dari 0.';
            continue;
        }
        if ($harga < 0) {
            $errors[] = 'Harga tidak boleh negatif.';
            continue;
        }
        $items[] = ['idbarang' => $idbarang, 'harga' => $harga, 'jumlah' => $jumlah];
    }

    if (!$items) {
        $errors[] = 'Minimal satu barang harus diisi.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $subtotal = 0;
            $hitung = [];
            foreach ($items as $item) {
                $sub = (int) db_val(
                    "SELECT fn_hitung_subtotal(?, ?)",
                    [$item['harga'], $item['jumlah']]
                );
                $hitung[] = $sub;
                $subtotal += $sub;
            }

            $ppn = (int) round($subtotal * 0.11);
            $total = $subtotal + $ppn;

            db_exec(
                "INSERT INTO pengadaan
                    (user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai)
                 VALUES (?, 'D', ?, ?, ?, ?)",
                [id_user(), $vendorId, $subtotal, $ppn, $total]
            );
            $idPengadaan = db_insert_id();

            foreach ($items as $i => $item) {
                db_exec(
                    "INSERT INTO detail_pengadaan
                        (harga_satuan, jumlah, sub_total, idbarang, idpengadaan)
                     VALUES (?, ?, ?, ?, ?)",
                    [$item['harga'], $item['jumlah'], $hitung[$i], $item['idbarang'], $idPengadaan]
                );
            }

            $pdo->commit();
            flash('sukses', 'Pengadaan #' . $idPengadaan . ' berhasil dibuat dan menunggu persetujuan.');
            redirect_dengan_log('pengadaan_detail.php?id=' . $idPengadaan);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = pesan_db($e);
        }
    }
}

$JUDUL = 'Buat Pengadaan';
$AKTIF = 'pengadaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <h2>Form Pengadaan Baru</h2>
    <p><small>Subtotal dihitung otomatis dari harga satuan dan jumlah, lalu ditambah PPN 11%.</small></p>

    <?php foreach ($errors as $pesan): ?>
        <div class="alert alert-error"><?= e($pesan) ?></div>
    <?php endforeach; ?>

    <form method="post" action="pengadaan_tambah.php">
        <div class="form-grid">
            <div>
                <label for="vendor">Vendor</label>
                <select id="vendor" name="vendor_idvendor" required>
                    <option value="">- pilih vendor -</option>
                    <?php foreach ($vendor as $v): ?>
                        <option value="<?= (int) $v['idvendor'] ?>" <?= $vendorId === (int) $v['idvendor'] ? 'selected' : '' ?>>
                            <?= e($v['nama_vendor']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                        <th class="angka">Harga Satuan</th>
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
                                    <option value="<?= (int) $b['idbarang'] ?>" data-harga="<?= (int) $b['harga'] ?>">
                                        <?= e($b['nama']) ?> (<?= rupiah($b['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="angka">
                            <input type="number" name="baris[0][harga]" class="input-harga" min="0" value="0" onchange="hitungTotal()">
                        </td>
                        <td class="angka">
                            <input type="number" name="baris[0][jumlah]" class="input-jumlah" min="1" value="1" onchange="hitungTotal()">
                        </td>
                        <td class="angka subtotal-baris">Rp 0</td>
                        <td><button type="button" class="btn btn-kecil btn-merah" onclick="hapusBaris(this)">X</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="form-aksi">
            <button type="button" class="btn btn-outline" onclick="tambahBaris()">+ Tambah Baris</button>
        </div>

        <div class="panel-putih panel mt">
            <div class="teks-kanan">
                <div>Subtotal: <b id="teksSubtotal">Rp 0</b></div>
                <div>PPN 11%: <b id="teksPpn">Rp 0</b></div>
                <div class="total-besar">Total: <span id="teksTotal">Rp 0</span></div>
            </div>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn">Simpan Pengadaan</button>
            <a class="btn btn-outline" href="pengadaan.php">Batal</a>
        </div>
    </form>
</div>

<script>
let nomorBaris = 1;

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function isiHarga(select) {
    const baris = select.closest('.baris-item');
    const opsi = select.options[select.selectedIndex];
    const harga = opsi ? (opsi.dataset.harga || 0) : 0;
    baris.querySelector('.input-harga').value = harga;
    hitungTotal();
}

function tambahBaris() {
    const tbody = document.querySelector('#tabelItem tbody');
    const awal = tbody.querySelector('.baris-item');
    const baru = awal.cloneNode(true);
    const n = nomorBaris++;

    baru.querySelector('.pilih-barang').name = 'baris[' + n + '][idbarang]';
    baru.querySelector('.pilih-barang').selectedIndex = 0;
    baru.querySelector('.input-harga').name = 'baris[' + n + '][harga]';
    baru.querySelector('.input-harga').value = 0;
    baru.querySelector('.input-jumlah').name = 'baris[' + n + '][jumlah]';
    baru.querySelector('.input-jumlah').value = 1;
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
        const harga = Number(baris.querySelector('.input-harga').value || 0);
        const jumlah = Number(baris.querySelector('.input-jumlah').value || 0);
        const sub = harga * jumlah;
        baris.querySelector('.subtotal-baris').textContent = rupiah(sub);
        subtotal += sub;
    });

    const ppn = Math.round(subtotal * 0.11);
    document.getElementById('teksSubtotal').textContent = rupiah(subtotal);
    document.getElementById('teksPpn').textContent = rupiah(ppn);
    document.getElementById('teksTotal').textContent = rupiah(subtotal + ppn);
}

hitungTotal();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
