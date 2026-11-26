<?php
// role_status.php ⤳ ubah status role (Aktif / Nonaktif).
require_once __DIR__ . '/inc/auth.php';
cek_role([1]);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    flash('error', 'Akses tidak valid.');
    redirect_dengan_log('role.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Role tidak ditemukan.');
    redirect_dengan_log('role.php');
}

$data = db_one("SELECT idrole, nama_role, status FROM role WHERE idrole = ?", [$id]);
if (!$data) {
    flash('error', 'Role tidak ditemukan.');
    redirect_dengan_log('role.php');
}

// Balik status: 1 (Aktif) jadi 0 (Nonaktif), begitu juga sebaliknya.
$statusBaru = ((int) $data['status']) === 1 ? 0 : 1;
db_exec("UPDATE role SET status = ? WHERE idrole = ?", [$statusBaru, $id]);

$label = $statusBaru === 1 ? 'Aktif' : 'Nonaktif';
flash('sukses', 'Role ' . e($data['nama_role']) . ' sekarang ' . $label . '.');
redirect_dengan_log('role.php');
