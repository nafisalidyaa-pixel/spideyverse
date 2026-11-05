<?php
$JUDUL = $JUDUL ?? 'Dashboard';
$AKTIF = $AKTIF ?? '';
$u = user_login();
$role = id_role();

$menu = [];
if ($role === 1) {
    $menu = [
        'Utama' => [
            ['index.php', 'Dashboard'],
        ],
        'Master Data' => [
            ['barang.php', 'Data Barang'],
            ['satuan.php', 'Data Satuan'],
            ['vendor.php', 'Data Vendor'],
            ['role.php', 'Data Role'],
            ['user.php', 'Data User'],
            ['margin.php', 'Margin Penjualan'],
        ],
        'Transaksi' => [
            ['pengadaan.php', 'Pengadaan'],
            ['penerimaan.php', 'Penerimaan'],
            ['retur.php', 'Retur'],
            ['penjualan.php', 'Penjualan'],
        ],
        'Laporan' => [
            ['kartu_stok.php', 'Kartu Stok'],
            ['laporan_stok.php', 'Laporan Stok'],
            ['laporan_penjualan.php', 'Laporan Penjualan'],
            ['laporan_pengadaan.php', 'Laporan Pengadaan'],
            ['laporan_retur.php', 'Laporan Retur'],
        ],
    ];
} elseif ($role === 2) {
    $menu = [
        'Utama' => [
            ['index.php', 'Dashboard'],
        ],
        'Data (Lihat)' => [
            ['barang.php', 'Data Barang'],
            ['satuan.php', 'Data Satuan'],
            ['vendor.php', 'Data Vendor'],
        ],
        'Transaksi' => [
            ['pengadaan.php', 'Pengadaan'],
            ['penerimaan.php', 'Penerimaan'],
            ['retur.php', 'Retur'],
        ],
        'Laporan' => [
            ['kartu_stok.php', 'Kartu Stok'],
            ['laporan_stok.php', 'Laporan Stok'],
            ['laporan_pengadaan.php', 'Laporan Pengadaan'],
            ['laporan_retur.php', 'Laporan Retur'],
        ],
    ];
} else {
    $menu = [
        'Utama' => [
            ['index.php', 'Dashboard'],
        ],
        'Penjualan' => [
            ['penjualan_tambah.php', 'Transaksi Penjualan'],
            ['penjualan.php', 'Riwayat Penjualan'],
            ['laporan_penjualan.php', 'Laporan Penjualan Saya'],
        ],
        'Data' => [
            ['barang.php', 'Barang dan Stok'],
            ['kartu_stok.php', 'Kartu Stok'],
            ['laporan_stok.php', 'Laporan Stok'],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($JUDUL) ?> - SpideyVerse</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">SV</div>
            <div>
                <b>SPIDEYVERSE</b>
            </div>
        </div>

        <nav class="menu">
            <?php foreach ($menu as $bagian => $item): ?>
                <div class="label"><?= e($bagian) ?></div>
                <?php foreach ($item as [$url, $label]): ?>
                    <a href="<?= e($url) ?>" class="<?= $AKTIF === $url ? 'aktif' : '' ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="konten">
        <header class="topbar">
            <div class="topbar-utama">
                <div class="topbar-meta">
                    <span class="topbar-tanda" aria-hidden="true"></span>
                    <span>Ruang Kerja</span>
                    <span class="topbar-pisah">/</span>
                    <span><?= e($u['nama_role'] ?? '') ?></span>
                    <span class="topbar-pisah">/</span>
                    <span><?= e(tanggal_indonesia()) ?></span>
                </div>
                <h1 class="judul-timbul"><?= e($JUDUL) ?></h1>
            </div>
            <div class="topbar-user">
                <div class="avatar" aria-hidden="true"><?= e(strtoupper(substr((string) ($u['username'] ?? 'U'), 0, 1))) ?></div>
                <div class="user-info">
                    <span class="user-nama"><?= e($u['username'] ?? '') ?></span>
                    <span class="badge badge-role"><span class="badge-titik"></span> Role aktif: <?= e($u['nama_role'] ?? '') ?></span>
                </div>
                <a href="logout.php" class="btn btn-kecil btn-outline">Keluar</a>
            </div>
        </header>

        <?php render_flash(); ?>
