<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengadaan.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID pengadaan tidak valid.');
    header('Location: pengadaan.php');
    exit;
}

try {
    $jumlah = db_exec(
        "UPDATE pengadaan SET status = 'A' WHERE idpengadaan = ? AND status = 'D'",
        [$id]
    );

    if ($jumlah > 0) {
        flash('sukses', 'Pengadaan #' . $id . ' disetujui. Barang siap diterima.');
    } else {
        flash('info', 'Pengadaan tidak berubah (mungkin sudah disetujui).');
    }
} catch (PDOException $e) {
    flash('error', pesan_db($e));
}

redirect_dengan_log('pengadaan_detail.php?id=' . $id);
