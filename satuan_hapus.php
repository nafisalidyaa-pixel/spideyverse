<?php
// Hapus satuan. `$master='satuan'`, panggil `inc/crud_hapus.php`.
// FK `barang.idsatuan` mencegah hapus kalau masih dipakai barang.
$master = 'satuan';
require __DIR__ . '/inc/crud_hapus.php';
