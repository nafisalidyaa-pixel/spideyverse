<?php
// Logout: ambil role pilihan (kalau ada), kosongkan session, lalu balik ke login.

// Panggil koneksi dan helper.
require_once __DIR__ . '/inc/koneksi.php';
require_once __DIR__ . '/inc/helpers.php';

// Kalau ada ?role=, ambil data role buat pesan di halaman login.
$idRole = (int) ($_GET['role'] ?? 0);
$roleDipilih = null;
if ($idRole > 0) {
    $roleDipilih = db_one('SELECT idrole, nama_role FROM role WHERE idrole = ?', [$idRole]);
}

// Kosongkan session dan ganti ID session.
$_SESSION = [];
session_regenerate_id(true);

// Tentukan pesan + tujuan redirect.
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

// Redirect ke halaman login.
header('Location: ' . $lokasi);
exit;
