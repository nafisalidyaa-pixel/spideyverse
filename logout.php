<?php
require_once __DIR__ . '/inc/helpers.php';

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['flash'][] = [
    'tipe'  => 'sukses',
    'pesan' => 'Anda berhasil keluar.',
];

header('Location: login.php');
exit;
