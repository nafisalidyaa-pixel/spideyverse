<?php
/**
 * Proses hapus master data (POST saja).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crud.php';

$cfg = master_config($master);
cek_role($cfg['edit_roles']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $cfg['halaman']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID data tidak valid.');
    header('Location: ' . $cfg['halaman']);
    exit;
}

if ($cfg['tabel'] === 'user' && $id === id_user()) {
    flash('error', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
    header('Location: ' . $cfg['halaman']);
    exit;
}

try {
    db_exec("DELETE FROM `{$cfg['tabel']}` WHERE `{$cfg['pk']}` = ?", [$id]);
    flash('sukses', $cfg['judul'] . ' berhasil dihapus.');
} catch (PDOException $e) {
    flash('error', pesan_db($e));
}

redirect_dengan_log($cfg['halaman']);
