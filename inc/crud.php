<?php
/**
 * Konfigurasi CRUD master data (SQL mentah).
 * Semua nama tabel/kolom berasal dari konfigurasi internal, bukan input user.
 */

function master_config(string $key): array
{
    $jenis = jenis_barang();

    $config = [
        'barang' => [
            'judul'    => 'Data Barang',
            'halaman'  => 'barang.php',
            'tabel'    => 'barang',
            'pk'       => 'idbarang',
            'roles'    => [1, 2, 3],
            'edit_roles' => [1],
            'cari'     => ['b.nama', 'b.idbarang'],
            'filter_jenis' => true,
            'list_sql' => "SELECT b.idbarang, b.jenis, b.nama, s.nama_satuan, b.status, b.harga,
                                  COALESCE((SELECT SUM(k.masuk - k.keluar) FROM kartu_stok k
                                            WHERE k.idbarang = b.idbarang), 0) AS stok
                           FROM barang b
                           JOIN satuan s ON s.idsatuan = b.idsatuan",
            'order_sql' => " ORDER BY b.jenis, b.idbarang",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'idbarang'],
                ['label' => 'Jenis', 'key' => 'jenis', 'format' => 'jenis'],
                ['label' => 'Nama Barang', 'key' => 'nama'],
                ['label' => 'Satuan', 'key' => 'nama_satuan'],
                ['label' => 'Harga', 'key' => 'harga', 'format' => 'rupiah', 'class' => 'angka'],
                ['label' => 'Stok', 'key' => 'stok', 'format' => 'stok', 'class' => 'angka'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status_aktif'],
            ],
            'fields'   => [
                ['name' => 'jenis', 'label' => 'Jenis Barang', 'type' => 'select',
                 'options' => $jenis, 'required' => true],
                ['name' => 'nama', 'label' => 'Nama Barang', 'type' => 'text',
                 'required' => true, 'max' => 45],
                ['name' => 'idsatuan', 'label' => 'Satuan', 'type' => 'select',
                 'options_sql' => "SELECT idsatuan AS nilai, nama_satuan AS label FROM satuan ORDER BY idsatuan",
                 'required' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select',
                 'options' => [1 => 'Aktif', 0 => 'Nonaktif'], 'required' => true, 'default' => 1],
                ['name' => 'harga', 'label' => 'Harga Dasar (Rp)', 'type' => 'number',
                 'required' => true, 'min' => 0, 'default' => 0],
            ],
        ],

        'satuan' => [
            'judul'    => 'Data Satuan',
            'halaman'  => 'satuan.php',
            'tabel'    => 'satuan',
            'pk'       => 'idsatuan',
            'roles'    => [1, 2],
            'edit_roles' => [1],
            'list_sql' => "SELECT s.idsatuan, s.nama_satuan, s.status,
                                  (SELECT COUNT(*) FROM barang b WHERE b.idsatuan = s.idsatuan) AS dipakai
                           FROM satuan s",
            'order_sql' => " ORDER BY s.idsatuan",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'idsatuan'],
                ['label' => 'Nama Satuan', 'key' => 'nama_satuan'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status_aktif'],
                ['label' => 'Dipakai Barang', 'key' => 'dipakai', 'class' => 'angka'],
            ],
            'fields'   => [
                ['name' => 'nama_satuan', 'label' => 'Nama Satuan', 'type' => 'text',
                 'required' => true, 'max' => 45],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select',
                 'options' => [1 => 'Aktif', 0 => 'Nonaktif'], 'required' => true, 'default' => 1],
            ],
        ],

        'vendor' => [
            'judul'    => 'Data Vendor',
            'halaman'  => 'vendor.php',
            'tabel'    => 'vendor',
            'pk'       => 'idvendor',
            'roles'    => [1, 2],
            'edit_roles' => [1],
            'list_sql' => "SELECT v.idvendor, v.nama_vendor, v.badan_hukum, v.status,
                                  (SELECT COUNT(*) FROM pengadaan p WHERE p.vendor_idvendor = v.idvendor) AS dipakai
                           FROM vendor v",
            'order_sql' => " ORDER BY v.idvendor",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'idvendor'],
                ['label' => 'Nama Vendor', 'key' => 'nama_vendor'],
                ['label' => 'Badan Hukum', 'key' => 'badan_hukum'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status_vendor'],
                ['label' => 'Dipakai Pengadaan', 'key' => 'dipakai', 'class' => 'angka'],
            ],
            'fields'   => [
                ['name' => 'nama_vendor', 'label' => 'Nama Vendor', 'type' => 'text',
                 'required' => true, 'max' => 100],
                ['name' => 'badan_hukum', 'label' => 'Badan Hukum (1 huruf)', 'type' => 'text',
                 'required' => true, 'max' => 1, 'default' => 'P',
                 'help' => 'Data awal memakai kode P.'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select',
                 'options' => ['A' => 'A - Aktif', 'N' => 'N - Nonaktif'],
                 'required' => true, 'default' => 'A'],
            ],
        ],

        'role' => [
            'judul'    => 'Data Role',
            'halaman'  => 'role.php',
            'tabel'    => 'role',
            'pk'       => 'idrole',
            'roles'    => [1],
            'edit_roles' => [1],
            'list_sql' => "SELECT r.idrole, r.nama_role,
                                  (SELECT COUNT(*) FROM `user` u WHERE u.idrole = r.idrole) AS jumlah_user
                           FROM `role` r",
            'order_sql' => " ORDER BY r.idrole",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'idrole'],
                ['label' => 'Nama Role', 'key' => 'nama_role'],
                ['label' => 'Jumlah User', 'key' => 'jumlah_user', 'class' => 'angka'],
            ],
            'fields'   => [
                ['name' => 'nama_role', 'label' => 'Nama Role', 'type' => 'text',
                 'required' => true, 'max' => 100],
            ],
        ],

        'user' => [
            'judul'    => 'Data User',
            'halaman'  => 'user.php',
            'tabel'    => 'user',
            'pk'       => 'iduser',
            'roles'    => [1],
            'edit_roles' => [1],
            'cari'     => ['u.username'],
            'list_sql' => "SELECT u.iduser, u.username, u.password, u.idrole, r.nama_role,
                                  (SELECT COUNT(*) FROM penjualan p WHERE p.iduser = u.iduser) AS jumlah_penjualan
                           FROM `user` u
                           JOIN `role` r ON r.idrole = u.idrole",
            'order_sql' => " ORDER BY u.iduser",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'iduser'],
                ['label' => 'Username', 'key' => 'username'],
                ['label' => 'Password', 'key' => 'password', 'format' => 'sandi'],
                ['label' => 'Role', 'key' => 'nama_role'],
                ['label' => 'Transaksi Jual', 'key' => 'jumlah_penjualan', 'class' => 'angka'],
            ],
            'fields'   => [
                ['name' => 'username', 'label' => 'Username', 'type' => 'text',
                 'required' => true, 'max' => 45],
                ['name' => 'password', 'label' => 'Password', 'type' => 'password',
                 'required' => true, 'max' => 100],
                ['name' => 'idrole', 'label' => 'Role', 'type' => 'select',
                 'options_sql' => "SELECT idrole AS nilai, nama_role AS label FROM `role` ORDER BY idrole",
                 'required' => true],
            ],
            'validasi' => function (array $data, int $id): ?string {
                $ada = db_val(
                    "SELECT COUNT(*) FROM `user` WHERE username = ? AND iduser <> ?",
                    [$data['username'], $id]
                );
                return ((int) $ada > 0) ? 'Username sudah dipakai user lain.' : null;
            },
        ],

        'margin' => [
            'judul'    => 'Margin Penjualan',
            'halaman'  => 'margin.php',
            'tabel'    => 'margin_penjualan',
            'pk'       => 'idmargin_penjualan',
            'roles'    => [1],
            'edit_roles' => [1],
            'list_sql' => "SELECT m.idmargin_penjualan, m.persen, m.status, m.iduser, u.username,
                                  (SELECT COUNT(*) FROM penjualan p
                                   WHERE p.idmargin_penjualan = m.idmargin_penjualan) AS dipakai,
                                  m.created_at, m.updated_at
                           FROM margin_penjualan m
                           JOIN `user` u ON u.iduser = m.iduser",
            'order_sql' => " ORDER BY m.idmargin_penjualan",
            'kolom'    => [
                ['label' => 'ID', 'key' => 'idmargin_penjualan'],
                ['label' => 'Persen', 'key' => 'persen', 'format' => 'persen', 'class' => 'angka'],
                ['label' => 'Status', 'key' => 'status', 'format' => 'status_aktif'],
                ['label' => 'Dibuat Oleh', 'key' => 'username'],
                ['label' => 'Dipakai', 'key' => 'dipakai', 'class' => 'angka'],
                ['label' => 'Dibuat', 'key' => 'created_at', 'format' => 'tanggal'],
            ],
            'fields'   => [
                ['name' => 'persen', 'label' => 'Persen Margin (%)', 'type' => 'number',
                 'required' => true, 'min' => 0, 'step' => '0.01', 'default' => 10],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select',
                 'options' => [1 => 'Aktif', 0 => 'Nonaktif'], 'required' => true, 'default' => 1,
                 'help' => 'Hanya margin aktif yang digunakan dalam transaksi penjualan.'],
                ['name' => 'iduser', 'label' => 'Dibuat Oleh', 'type' => 'select',
                 'options_sql' => "SELECT iduser AS nilai, username AS label FROM `user` ORDER BY iduser",
                 'required' => true, 'default' => id_user()],
            ],
        ],
    ];

    return $config[$key];
}

function opsi_field(array $field): array
{
    if (!empty($field['options'])) {
        return $field['options'];
    }
    if (!empty($field['options_sql'])) {
        $rows = db_all($field['options_sql']);
        $opsi = [];
        foreach ($rows as $row) {
            $opsi[$row['nilai']] = $row['label'];
        }
        return $opsi;
    }
    return [];
}

function tampil_nilai(string $format, $nilai)
{
    switch ($format) {
        case 'rupiah':
            return rupiah($nilai);
        case 'stok':
            return '<b>' . (int) $nilai . '</b> ' . badge_stok($nilai);
        case 'status_aktif':
            return ((int) $nilai === 1)
                ? '<span class="badge badge-hijau">Aktif</span>'
                : '<span class="badge badge-merah">Nonaktif</span>';
        case 'status_vendor':
            return ($nilai === 'A')
                ? '<span class="badge badge-hijau">A - Aktif</span>'
                : '<span class="badge badge-merah">N - Nonaktif</span>';
        case 'jenis':
            $map = jenis_barang();
            return '<span title="' . e($map[$nilai] ?? '') . '">' . e($nilai) . ' - '
                . e(explode(' - ', $map[$nilai] ?? $nilai)[1] ?? $nilai) . '</span>';
        case 'sandi':
            return '<span class="badge badge-abu">Tersimpan</span>';
        case 'persen':
            return rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',') . ' %';
        case 'tanggal':
            return e(tanggal($nilai));
        default:
            return e($nilai);
    }
}
