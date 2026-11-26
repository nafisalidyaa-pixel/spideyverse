<?php
// Buat pengadaan baru: pilih barang, vendor otomatis, simpan header, detail ditampung di session.
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

// Ambil vendor aktif dan barang aktif buat pilihan form.
$daftarVendor = db_all("SELECT idvendor, nama_vendor FROM vendor WHERE status = 'A' ORDER BY idvendor");
$barang = db_all("SELECT idbarang, nama, jenis, harga FROM barang WHERE status = 1 ORDER BY nama");

// Pemetaan vendor otomatis per jenis barang.
$jenisVendor = [];
if ($daftarVendor) {
    $daftarJenis = array_keys(jenis_barang());
    foreach ($daftarJenis as $i => $jenis) {
        $jenisVendor[$jenis] = $daftarVendor[$i % count($daftarVendor)];
    }
}
// Simpan info vendor untuk tiap barang.
$vendorBarang = [];
foreach ($barang as $b) {
    $v = $jenisVendor[$b['jenis']] ?? ($daftarVendor[0] ?? null);
    if ($v) {
        $vendorBarang[(int) $b['idbarang']] = [
            'idvendor' => (int) $v['idvendor'],
            'nama'     => $v['nama_vendor'],
        ];
    }
}

$errors = [];
$baris = $_POST['baris'] ?? [];

// Proses simpan pengadaan.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Wajib ada vendor aktif.
    if (!$daftarVendor) {
        $errors[] = 'Belum ada vendor aktif. Aktifkan vendor di menu Data Vendor.';
    }

    // Validasi tiap baris barang.
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

        // Vendor harus ketemu buat barang ini.
        $v = $vendorBarang[$idbarang] ?? null;
        if (!$v) {
            $errors[] = 'Vendor otomatis untuk salah satu barang belum tersedia.';
            continue;
        }

        $items[] = [
            'idbarang' => $idbarang,
            'harga'    => $harga,
            'jumlah'   => $jumlah,
            'idvendor' => $v['idvendor'],
        ];
    }

    // Minimal satu barang.
    if (!$items) {
        $errors[] = 'Minimal satu barang harus diisi.';
    }

    // Kalau valid, simpan ke database.
    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Setiap barang otomatis dikaitkan dengan vendornya.
            // Barang dari vendor berbeda dibuat dalam pengadaan terpisah.
            $grup = [];
            foreach ($items as $item) {
                $grup[$item['idvendor']][] = $item;
            }

            $daftarId = [];
            $daftarNama = [];
            $daftarItems = [];
            // Buat header pengadaan per vendor.
            foreach ($grup as $idvendor => $rows) {
                $subtotal = 0;
                $hitung = [];
                foreach ($rows as $item) {
                    // Memanggil function: fn_hitung_subtotal (definisi di database/03_function.sql)
                    $sub = (int) db_val(
                        "SELECT fn_hitung_subtotal(?, ?)",
                        [$item['harga'], $item['jumlah']]
                    );
                    $hitung[] = $sub;
                    $subtotal += $sub;
                }

                // Memanggil function: fn_calculate_ppn (definisi di database/03_function.sql)
                // PPN dihitung dari subtotal = subtotal x 0.11
                $ppn = (int) db_val("SELECT fn_calculate_ppn(?)", [$subtotal]);
                $total = $subtotal + $ppn;

                // Simpan header pengadaan dengan status P (menunggu).
                db_exec(
                    "INSERT INTO pengadaan
                        (user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai)
                     VALUES (?, 'P', ?, ?, ?, ?)",
                    [id_user(), $idvendor, $subtotal, $ppn, $total]
                );
                $idPengadaan = db_insert_id();
                $daftarId[] = $idPengadaan;

                // Cari nama vendor buat pesan.
                $namaVendor = 'Vendor';
                foreach ($daftarVendor as $v) {
                    if ((int) $v['idvendor'] === (int) $idvendor) {
                        $namaVendor = $v['nama_vendor'];
                        break;
                    }
                }
                $daftarNama[] = '#' . $idPengadaan . ' (' . $namaVendor . ')';

                // Data detail pengadaan ditampung di session; akan dimasukkan
                // ke tabel detail_pengadaan pada saat tombol konfirmasi dipencet.
                $daftarItems[(int) $idPengadaan] = $rows;
            }

            // Menyimpan data detail pengadaan yang belum dikonfirmasi (di session).
            foreach ($daftarItems as $idP => $rowsBarang) {
                $_SESSION['pending_pengadaan'][(int) $idP] = $rowsBarang;
            }

            $pdo->commit();

            // Susun pesan sukses.
            $pesan = (count($daftarId) > 1)
                ? 'Barang dari vendor berbeda dibuatkan pengadaan terpisah: ' . implode(', ', $daftarNama) . '. Menunggu konfirmasi.'
                : 'Pengadaan #' . $daftarId[0] . ' berhasil dibuat dan menunggu konfirmasi.';

            flash('sukses', $pesan);
            redirect_dengan_log('pengadaan_detail.php?id=' . (int) $daftarId[0]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = pesan_db($e);
        }
    }
}

// Tampilkan form.
$JUDUL = 'Buat Pengadaan';
$AKTIF = 'pengadaan.php';
require __DIR__ . '/inc/header.php';
?>

<div class="panel">
    <h2>Form Pengadaan Baru</h2>
    <p><small>Vendor setiap barang dipilih otomatis. Barang dari vendor berbeda dibuatkan pengadaan terpisah.</small></p>

    <?php foreach ($errors as $pesan): ?>
        <div class="alert alert-error"><?= e($pesan) ?></div>
    <?php endforeach; ?>

    <?php if (!$daftarVendor): ?>
        <div class="alert alert-error">Belum ada vendor aktif. Aktifkan vendor di menu Data Vendor.</div>
    <?php endif; ?>

    <form method="post" action="pengadaan_tambah.php">
        <div class="form-grid">
            <div>
                <label>Vendor <span class="teks-otomatis">otomatis</span></label>
                <input type="text" tabindex="-1" readonly
                       value="Mengikuti vendor barang yang dipilih">
                <small class="hint-otomatis">Setiap barang otomatis terhubung ke vendornya. Barang dari vendor berbeda dibuatkan pengadaan terpisah.</small>
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
                        <th>Vendor</th>
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
                                    <option value="<?= (int) $b['idbarang'] ?>"
                                            data-harga="<?= (int) $b['harga'] ?>"
                                            data-vendor="<?= e($vendorBarang[(int) $b['idbarang']]['nama'] ?? '-') ?>">
                                        <?= e($b['nama']) ?> (<?= rupiah($b['harga']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="vendor-baris">Otomatis</td>
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
            <button type="button" class="btn btn-outline" onclick="tambahBaris()">+ Tambah Barang</button>
        </div>

        <div class="panel-putih panel mt">
            <div class="teks-kanan">
                <div>Subtotal: <b id="teksSubtotal">Rp 0</b></div>
                <div>PPN 11%: <b id="teksPpn">Rp 0</b></div>
                <div class="total-besar">Total: <span id="teksTotal">Rp 0</span></div>
            </div>
        </div>

        <div class="form-aksi">
            <button type="submit" class="btn" <?= !$daftarVendor ? 'disabled' : '' ?>>Simpan Pengadaan</button>
            <a class="btn btn-outline" href="pengadaan.php">Batal</a>
        </div>
    </form>
</div>

<script>
// JS form pengadaan: isi harga, tambah/hapus baris, dan hitung total.
let nomorBaris = 1;

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

// Isi harga & vendor otomatis waktu barang dipilih.
function isiHarga(select) {
    const baris = select.closest('.baris-item');
    const opsi = select.options[select.selectedIndex];
    const harga = opsi ? (opsi.dataset.harga || 0) : 0;
    const vendor = opsi && opsi.dataset.vendor ? opsi.dataset.vendor : 'Otomatis';
    baris.querySelector('.input-harga').value = harga;
    baris.querySelector('.vendor-baris').textContent = vendor;
    hitungTotal();
}

// Tambah baris barang baru.
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
    baru.querySelector('.vendor-baris').textContent = 'Otomatis';

    tbody.appendChild(baru);
    hitungTotal();
}

// Hapus baris, minimal sisakan satu baris.
function hapusBaris(btn) {
    const semua = document.querySelectorAll('.baris-item');
    if (semua.length <= 1) {
        return;
    }
    btn.closest('.baris-item').remove();
    hitungTotal();
}

// Hitung subtotal, PPN 11%, dan total semua baris.
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
