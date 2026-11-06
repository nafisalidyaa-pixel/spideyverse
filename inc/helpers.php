<?php
/**
 * Fungsi bantu: escaping, format, query mentah, log query untuk presentasi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

/* =========================
   FORMAT & TAMPILAN
========================= */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($nilai): string
{
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
}

function tanggal($nilai): string
{
    if (!$nilai) {
        return '-';
    }
    return date('d-m-Y H:i', strtotime($nilai));
}

function tanggal_indonesia(?string $waktu = null): string
{
    $ts = $waktu ? strtotime($waktu) : time();
    if ($ts === false) {
        $ts = time();
    }

    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    return $hari[(int) date('w', $ts)] . ', '
        . (int) date('j', $ts) . ' '
        . $bulan[(int) date('n', $ts)] . ' '
        . date('Y', $ts);
}

function tanggal_valid(string $teks): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $teks);
    return $d instanceof DateTime && $d->format('Y-m-d') === $teks;
}

function panjang_teks(string $teks): int
{
    return function_exists('mb_strlen') ? mb_strlen($teks) : strlen($teks);
}

function jenis_barang(): array
{
    return [
        'A' => 'A - Botol & Perlengkapan Minum',
        'B' => 'B - Aksesori',
        'C' => 'C - Action Figure & Koleksi',
        'D' => 'D - Mainan Superhero',
        'E' => 'E - Boneka',
        'F' => 'F - Alat Tulis',
        'G' => 'G - Tas & Perlengkapan',
        'H' => 'H - Dekorasi & Suvenir',
        'I' => 'I - Perlengkapan Elektronik',
        'J' => 'J - Perlengkapan Kamar',
    ];
}

function badge_stok($stok): string
{
    $stok = (int) $stok;
    if ($stok <= 0) {
        return '<span class="badge badge-merah">Habis</span>';
    }
    if ($stok < 10) {
        return '<span class="badge badge-kuning">Rendah</span>';
    }
    return '<span class="badge badge-hijau">Aman</span>';
}

function badge_status_pengadaan($status): string
{
    return $status === 'A'
        ? '<span class="badge badge-hijau">Disetujui</span>'
        : '<span class="badge badge-kuning">Menunggu</span>';
}

function badge_jenis_transaksi($jenis): string
{
    $map = [
        'M' => ['Masuk (Penerimaan)', 'badge-hijau'],
        'K' => ['Keluar (Penjualan)', 'badge-merah'],
        'R' => ['Keluar (Retur)', 'badge-kuning'],
    ];
    [$label, $kelas] = $map[$jenis] ?? ['Tidak diketahui', 'badge-abu'];
    return '<span class="badge ' . $kelas . '">' . $label . '</span>';
}

/* =========================
   FLASH MESSAGE
========================= */

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Menampilkan notifikasi sebagai dialog popup yang ditutup dengan tombol OK.
 */
function render_flash(): void
{
    $flash = ambil_flash();
    if (!$flash) {
        return;
    }

    $judul = [
        'sukses' => 'Berhasil',
        'error'  => 'Tidak Dapat Diproses',
        'info'   => 'Informasi',
    ];
    $ikon = [
        'sukses' => '<path d="M20 6 9 17l-5-5"/>',
        'error'  => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/>',
        'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
    ];

    echo '<div class="notif-lapisan" id="notifLapisan">';
    foreach ($flash as $i => $f) {
        $tipe = in_array($f['tipe'] ?? '', ['error', 'info', 'sukses'], true) ? $f['tipe'] : 'sukses';
        $id = 'notif-' . $i;
        echo '<div class="notif-kartu notif-' . $tipe . '" role="alertdialog" aria-modal="true" '
            . 'aria-labelledby="' . $id . '-judul" aria-describedby="' . $id . '-pesan">';
        echo '<div class="notif-ikon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . $ikon[$tipe] . '</svg></div>';
        echo '<div class="notif-label">SPIDEYVERSE</div>';
        echo '<div class="notif-judul" id="' . $id . '-judul">' . $judul[$tipe] . '</div>';
        echo '<div class="notif-pesan" id="' . $id . '-pesan">' . e($f['pesan']) . '</div>';
        echo '<button type="button" class="notif-ok" onclick="tutupNotif(this)">OK</button>';
        echo '<div class="notif-petunjuk">Tekan <b>Esc</b> untuk menutup</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<script>'
        . 'function tutupNotif(el){'
        . 'var k=el.closest(".notif-kartu");if(!k||k.classList.contains("notif-keluar"))return;'
        . 'k.classList.add("notif-keluar");'
        . 'setTimeout(function(){var l=k.parentNode;k.remove();'
        . 'if(l&&!l.querySelector(".notif-kartu")){l.remove();document.body.classList.remove("notif-aktif");}},260);'
        . '}'
        . 'document.addEventListener("keydown",function(e){'
        . 'if(e.key==="Escape"){var b=document.querySelector(".notif-kartu .notif-ok");if(b){tutupNotif(b);}}'
        . '});'
        . 'document.body.classList.add("notif-aktif");'
        . 'var ok=document.querySelector(".notif-kartu .notif-ok");'
        . 'if(ok){setTimeout(function(){ok.focus();},140);}'
        . '</script>';
}

/* =========================
   QUERY MENTAH + LOG QUERY
========================= */

function catat_query(string $sql, array $params = []): void
{
    $GLOBALS['QUERY_LOG'][] = ['sql' => $sql, 'params' => $params];
}

/**
 * Mengganti tanda ? dengan nilai asli, hanya untuk ditampilkan di halaman.
 * Eksekusi tetap memakai prepared statement.
 */
function sql_lengkap(string $sql, array $params = []): string
{
    $i = 0;
    return preg_replace_callback('/\?/', function () use (&$i, $params) {
        $nilai = $params[$i++] ?? null;
        if ($nilai === null) {
            return 'NULL';
        }
        if (is_int($nilai) || is_float($nilai)) {
            return (string) $nilai;
        }
        return "'" . str_replace("'", "''", (string) $nilai) . "'";
    }, $sql);
}

function db_all(string $sql, array $params = []): array
{
    global $pdo;
    catat_query($sql, $params);
    // Query di sini boleh berisi view (v_barang, v_satuan, dst.) / pemanggilan SP
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    global $pdo;
    catat_query($sql, $params);
    // Query di sini boleh berisi view / pemanggilan SP
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_val(string $sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row === null ? null : reset($row);
}

function db_exec(string $sql, array $params = []): int
{
    global $pdo;
    catat_query($sql, $params);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function db_insert_id(): int
{
    global $pdo;
    return (int) $pdo->lastInsertId();
}

/**
 * Pesan error database yang ramah untuk pengguna.
 */
function pesan_db(PDOException $e): string
{
    $kode = $e->errorInfo[1] ?? 0;

    if ($kode === 1644) {
        return $e->errorInfo[2] ?? 'Proses tidak dapat dilakukan.';
    }
    if ($kode === 1451) {
        return 'Data tidak bisa dihapus karena masih digunakan pada transaksi lain.';
    }
    if ($kode === 1452) {
        return 'Data relasi tidak ditemukan. Periksa pilihan Anda.';
    }
    if ($kode === 1062) {
        return 'Data sudah ada (duplikat).';
    }
    if ($kode === 1264) {
        return 'Nilai yang dimasukkan terlalu besar.';
    }

    return 'Terjadi kesalahan saat memproses data. Silakan coba lagi.';
}

/* =========================
   LOG QUERY LINTAS REDIRECT
========================= */

function redirect_dengan_log(string $url): void
{
    $_SESSION['query_lalu'] = $GLOBALS['QUERY_LOG'] ?? [];
    header('Location: ' . $url);
    exit;
}

function ambil_query_lalu(): void
{
    if (!empty($_SESSION['query_lalu'])) {
        $GLOBALS['QUERY_LOG'] = array_merge(
            $_SESSION['query_lalu'],
            $GLOBALS['QUERY_LOG'] ?? []
        );
        unset($_SESSION['query_lalu']);
    }
}
