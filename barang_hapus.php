<?php
// Hapus barang. `$master='barang'`, panggil `inc/crud_hapus.php`.
// FK mencegah hapus kalau masih dipakai transaksi. POST saja.
$master = 'barang';
require __DIR__ . '/inc/crud_hapus.php';
