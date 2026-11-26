<?php
// Hapus pengadaan yang masih menunggu (status P) dan belum punya penerimaan.
require_once __DIR__ . '/inc/auth.php';
cek_role([1]);

// Hanya boleh lewat POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: pengadaan.php');
    exit;
}

// Ambil id pengadaan.
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID pengadaan tidak valid.');
    header('Location: pengadaan.php');
    exit;
}

try {
    // Cek status pengadaan.
    $status = db_val("SELECT status FROM pengadaan WHERE idpengadaan = ?", [$id]);

    // Hanya status P yang boleh dihapus.
    if ($status !== 'P') {
        flash('error', 'Hanya pengadaan yang masih menunggu persetujuan yang bisa dihapus.');
        redirect_dengan_log('pengadaan_detail.php?id=' . $id);
    }

    // Cek apakah sudah ada penerimaan.
    $punyaPenerimaan = (int) db_val(
        "SELECT COUNT(*) FROM penerimaan WHERE idpengadaan = ?",
        [$id]
    );

    // Kalau sudah ada penerimaan, tolak.
    if ($punyaPenerimaan > 0) {
        flash('error', 'Pengadaan sudah punya penerimaan, tidak boleh dihapus.');
        redirect_dengan_log('pengadaan_detail.php?id=' . $id);
    }

    // Hapus detail dulu, lalu header, dalam satu transaksi.
    $pdo->beginTransaction();
    db_exec("DELETE FROM detail_pengadaan WHERE idpengadaan = ?", [$id]);
    db_exec("DELETE FROM pengadaan WHERE idpengadaan = ?", [$id]);
    $pdo->commit();

    flash('sukses', 'Pengadaan #' . $id . ' berhasil dihapus.');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', pesan_db($e));
}

// Balik ke daftar pengadaan.
redirect_dengan_log('pengadaan.php');
