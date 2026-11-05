<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1]);

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
    $status = db_val("SELECT status FROM pengadaan WHERE idpengadaan = ?", [$id]);

    if ($status !== 'D') {
        flash('error', 'Hanya pengadaan yang masih menunggu persetujuan yang bisa dihapus.');
        redirect_dengan_log('pengadaan_detail.php?id=' . $id);
    }

    $punyaPenerimaan = (int) db_val(
        "SELECT COUNT(*) FROM penerimaan WHERE idpengadaan = ?",
        [$id]
    );

    if ($punyaPenerimaan > 0) {
        flash('error', 'Pengadaan sudah punya penerimaan, tidak boleh dihapus.');
        redirect_dengan_log('pengadaan_detail.php?id=' . $id);
    }

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

redirect_dengan_log('pengadaan.php');
