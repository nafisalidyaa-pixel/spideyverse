<?php
// Panggil helpers (flash, query, dll) dan koneksi database.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/koneksi.php';

// Pindahkan log query dari redirect sebelumnya.
ambil_query_lalu();

// Ambil data user yang lagi login.
function user_login(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Ambil id user yang lagi login.
function id_user(): int
{
    return (int) (user_login()['iduser'] ?? 0);
}

// Ambil id role user yang lagi login.
function id_role(): int
{
    return (int) (user_login()['idrole'] ?? 0);
}

// Ambil username user yang lagi login.
function nama_user(): string
{
    return (string) (user_login()['username'] ?? '');
}

// Ambil nama role user yang lagi login.
function nama_role(): string
{
    return (string) (user_login()['nama_role'] ?? '');
}

// Cek apakah user sudah login, kalau belum lempar ke halaman login.
function cek_login(): void
{
    if (!user_login()) {
        header('Location: login.php');
        exit;
    }
}

// Cek apakah role user boleh akses halaman ini.
function cek_role(array $roles): void
{
    cek_login();
    if (!in_array(id_role(), $roles, true)) {
        flash('error', 'Role Anda tidak punya akses ke halaman tersebut.');
        header('Location: index.php');
        exit;
    }
}
