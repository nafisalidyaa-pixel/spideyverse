<?php
// Hapus margin penjualan. `$master='margin'`, panggil `inc/crud_hapus.php`.
// FK `penjualan.idmargin_penjualan` mencegah hapus kalau sudah dipakai.
$master = 'margin';
require __DIR__ . '/inc/crud_hapus.php';
