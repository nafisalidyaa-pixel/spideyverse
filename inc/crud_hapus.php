<?php
// Proses hapus master data (POST saja).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crud.php';

// Ambil config master dan cek role yang boleh hapus.
$cfg = master_config($master);
cek_role($cfg['edit_roles']);

// Hanya boleh lewat POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $cfg['halaman']);
    exit;
}

// Ambil id yang mau dihapus.
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID data tidak valid.');
    header('Location: ' . $cfg['halaman']);
    exit;
}

// User tidak boleh hapus akun yang lagi dipakai.
if ($cfg['tabel'] === 'user' && $id === id_user()) {
    flash('error', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
    header('Location: ' . $cfg['halaman']);
    exit;
}

// Hapus data, kalau masih dipakai transaksi bakal ditolak FK.
try {
    db_exec("DELETE FROM `{$cfg['tabel']}` WHERE `{$cfg['pk']}` = ?", [$id]);
    flash('sukses', $cfg['judul'] . ' berhasil dihapus.');
} catch (PDOException $e) {
    flash('error', pesan_db($e));
}

redirect_dengan_log($cfg['halaman']);
