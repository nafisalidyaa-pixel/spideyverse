<?php
// Hapus user. `$master='user'`, panggil `inc/crud_hapus.php`.
// Tidak bisa hapus akun sendiri (`crud_hapus`). FK transaksi juga mencegah.
$master = 'user';
require __DIR__ . '/inc/crud_hapus.php';
