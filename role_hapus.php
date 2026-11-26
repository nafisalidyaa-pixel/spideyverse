<?php
// Hapus role. `$master='role'`, panggil `inc/crud_hapus.php`.
// FK `user.idrole` mencegah hapus kalau masih ada user ber-role ini.
$master = 'role';
require __DIR__ . '/inc/crud_hapus.php';
