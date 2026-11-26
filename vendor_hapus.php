<?php
// Hapus vendor. `$master='vendor'`, panggil `inc/crud_hapus.php`.
// FK `pengadaan.vendor_idvendor` mencegah hapus kalau sudah dipakai pengadaan.
$master = 'vendor';
require __DIR__ . '/inc/crud_hapus.php';
