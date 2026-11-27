<?php
// margin_status.php ⤳ aktifkan / nonaktifkan margin penjualan langsung dari daftar.
// Kalau diaktifkan, margin lain otomatis mati supaya langsung dipakai semua transaksi.
require_once __DIR__ . '/inc/auth.php';
cek_role([1]);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    flash('error', 'Akses tidak valid.');
    redirect_dengan_log('margin.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Margin tidak ditemukan.');
    redirect_dengan_log('margin.php');
}

$data = db_one("SELECT idmargin_penjualan, persen, status FROM margin_penjualan WHERE idmargin_penjualan = ?", [$id]);
if (!$data) {
    flash('error', 'Margin tidak ditemukan.');
    redirect_dengan_log('margin.php');
}

try {
    $pdo->beginTransaction();

    if ((int) $data['status'] === 1) {
        // Nonaktifkan margin ini saja.
        db_exec("UPDATE margin_penjualan SET status = 0 WHERE idmargin_penjualan = ?", [$id]);
        $pesan = 'Margin ' . rtrim(rtrim(number_format((float) $data['persen'], 2, ',', '.'), '0'), ',') . '% dinonaktifkan.';
    } else {
        // Aktifkan margin ini, matikan semua margin lain (eksklusif).
        db_exec("UPDATE margin_penjualan SET status = 0 WHERE idmargin_penjualan <> ?", [$id]);
        db_exec("UPDATE margin_penjualan SET status = 1 WHERE idmargin_penjualan = ?", [$id]);
        $pesan = 'Margin ' . rtrim(rtrim(number_format((float) $data['persen'], 2, ',', '.'), '0'), ',') . '% sekarang aktif dan dipakai semua transaksi.';
    }

    $pdo->commit();
    flash('sukses', $pesan);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', pesan_db($e));
}

redirect_dengan_log('margin.php');
