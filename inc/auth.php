<?php
/**
 * Session + hak akses per role.
 * role 1 = Administrator
 * role 2 = Staff Gudang
 * role 3 = Kasir
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/koneksi.php';

ambil_query_lalu();

function user_login(): ?array
{
    return $_SESSION['user'] ?? null;
}

function id_user(): int
{
    return (int) (user_login()['iduser'] ?? 0);
}

function id_role(): int
{
    return (int) (user_login()['idrole'] ?? 0);
}

function nama_user(): string
{
    return (string) (user_login()['username'] ?? '');
}

function nama_role(): string
{
    return (string) (user_login()['nama_role'] ?? '');
}

function cek_login(): void
{
    if (!user_login()) {
        header('Location: login.php');
        exit;
    }
}

function cek_role(array $roles): void
{
    cek_login();
    if (!in_array(id_role(), $roles, true)) {
        flash('error', 'Role Anda tidak punya akses ke halaman tersebut.');
        header('Location: index.php');
        exit;
    }
}
