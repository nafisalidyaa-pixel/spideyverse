<?php
require_once __DIR__ . '/inc/koneksi.php';
require_once __DIR__ . '/inc/helpers.php';

$idRole = (int) ($_GET['role'] ?? 0);
$roleDipilih = null;
if ($idRole > 0) {
    $roleDipilih = db_one('SELECT idrole, nama_role FROM role WHERE idrole = ?', [$idRole]);
}

$_SESSION = [];
session_regenerate_id(true);

if ($roleDipilih) {
    $_SESSION['flash'][] = [
        'tipe'  => 'info',
        'pesan' => 'Silakan masuk dengan akun untuk role ' . $roleDipilih['nama_role'] . '.',
    ];
    $lokasi = 'login.php?role=' . (int) $roleDipilih['idrole'];
} else {
    $_SESSION['flash'][] = [
        'tipe'  => 'sukses',
        'pesan' => 'Anda berhasil keluar.',
    ];
    $lokasi = 'login.php';
}

header('Location: ' . $lokasi);
exit;
