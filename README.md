# SpideyVerse

Aplikasi web persediaan barang **SpideyVerse** menggunakan **PHP 8 native + PDO + SQL mentah (tanpa framework / ORM)** dan database MySQL 8.0 (`spideyverse`) yang dikelola lewat HeidiSQL / Laragon.

Tema tampilan: **latar pink dengan seluruh tulisan putih** dan tombol bergaya profesional.

---

## Prasyarat

- PHP 8.0 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 8.0 / MariaDB
- Laragon (disarankan) atau PHP built-in server

---

## Cara Menjalankan

### 1. Impor database
Berkas ekspor database tersedia di `database/spideyverse.sql`. Berkas ini sudah memuat struktur tabel, data awal, trigger, function, dan stored procedure.

**Lewat HeidiSQL / phpMyAdmin**
1. Buat koneksi ke MySQL lokal.
2. Pilih menu **Import** (HeidiSQL) atau tab **Import** (phpMyAdmin).
3. Pilih `database/spideyverse.sql`, lalu jalankan.

**Lewat terminal**
```powershell
mysql -uroot -e "SOURCE C:/laragon/www/spideyverse/database/spideyverse.sql"
```

### 2. Sesuaikan koneksi
Konfigurasi ada di `inc/koneksi.php`:
- Host: `127.0.0.1`, Port: `3306`
- Database: `spideyverse`
- User: `root`, Password: *(kosong)*

Jika MySQL Anda memakai password, ubah nilai `DB_PASS` pada berkas tersebut.

### 3. Jalankan aplikasi

**Opsi A - Laragon (disarankan)**
1. Pastikan Apache dan MySQL Laragon berjalan.
2. Letakkan folder aplikasi di `C:\laragon\www\spideyverse`.
3. Buka **http://localhost/spideyverse**.

**Opsi B - PHP built-in server**
```powershell
php -S 127.0.0.1:8123 -t C:\laragon\www\spideyverse
```
Lalu buka **http://127.0.0.1:8123**.

---

## Akun Demo

| Username | Password   | Role          |
|----------|------------|---------------|
| admin    | Admin123!  | Administrator |
| staff    | Staff123!  | Staff Gudang  |
| kasir    | Kasir123!  | Kasir         |

> Password pada tabel `user` disimpan sebagai teks biasa; perbandingan saat login bersifat **case-sensitive** (membedakan huruf besar dan kecil).

---

## Hak Akses per Role

### Administrator
- Dashboard ringkasan seluruh data
- Master data penuh (tambah/edit/hapus): Barang, Satuan, Vendor, Role, User, Margin Penjualan
- Transaksi: Pengadaan, Penerimaan, Retur, Penjualan
- Semua laporan

### Staff Gudang
- Dashboard gudang (pengadaan menunggu persetujuan, penerimaan, retur, stok rendah)
- Melihat master: Barang, Satuan, Vendor (tanpa edit/hapus)
- Transaksi: Pengadaan, Penerimaan, Retur
- Laporan: Kartu Stok, Stok, Pengadaan, Retur
- **Tidak bisa**: penjualan, user, role, margin, objek database

### Kasir
- Dashboard kasir (transaksi & pendapatan hari ini)
- Transaksi penjualan (memanggil stored procedure)
- Riwayat penjualan (hanya miliknya sendiri) + bukti transaksi
- Melihat Barang & Stok, Kartu Stok, Laporan Stok
- **Tidak bisa**: master data, pengadaan/penerimaan/retur, user, laporan penjualan global

---

## Alur Proses (Halaman Terpisah)

| Proses | Halaman |
|--------|---------|
| Login / Logout | `login.php`, `logout.php` |
| Dashboard per role | `index.php` |
| Master Barang | `barang.php`, `barang_tambah.php`, `barang_edit.php`, `barang_hapus.php` |
| Master Satuan | `satuan*.php` |
| Master Vendor | `vendor*.php` |
| Master Role | `role*.php` |
| Master User | `user*.php` |
| Margin Penjualan | `margin*.php` |
| Pengadaan | `pengadaan.php`, `pengadaan_tambah.php`, `pengadaan_detail.php`, `pengadaan_setujui.php`, `pengadaan_hapus.php` |
| Penerimaan | `penerimaan.php`, `penerimaan_tambah.php`, `penerimaan_detail.php` |
| Retur | `retur.php`, `retur_tambah.php`, `retur_detail.php` |
| Penjualan | `penjualan.php`, `penjualan_tambah.php`, `penjualan_detail.php` |
| Kartu Stok | `kartu_stok.php` |
| Laporan | `laporan_stok.php`, `laporan_penjualan.php`, `laporan_pengadaan.php`, `laporan_retur.php` |

---

## Objek Database yang Dipakai

- **15 tabel**: `barang`, `satuan`, `vendor`, `role`, `user`, `margin_penjualan`, `pengadaan`, `detail_pengadaan`, `penerimaan`, `detail_penerimaan`, `retur`, `detail_retur`, `penjualan`, `detail_penjualan`, `kartu_stok`
- **6 view master**: `v_barang`, `v_satuan`, `v_vendor`, `v_role`, `v_user`, `v_margin_penjualan`
- **4 trigger**:
  - `trg_penerimaan_stok` - menambah stok saat penerimaan
  - `trg_retur_stok` - mengurangi stok saat retur
  - `trg_penjualan_stok` - mengurangi stok saat penjualan
  - `trg_penjualan_validasi_stok` - menolak penjualan jika stok kurang
- **1 function**: `fn_hitung_subtotal(harga, jumlah)`
- **1 stored procedure**: `sp_tambah_penjualan(iduser, idmargin, idbarang, jumlah)`

## View Master

| View | Fungsi |
|---|---|
| `v_barang` | Daftar barang lengkap dengan satuan, kategori, status, dan stok |
| `v_satuan` | Daftar satuan beserta jumlah barang yang menggunakannya |
| `v_vendor` | Daftar vendor beserta jumlah pengadaan |
| `v_role` | Daftar role beserta jumlah user |
| `v_user` | Daftar user dan role tanpa menampilkan password |
| `v_margin_penjualan` | Daftar margin penjualan, status, dan user pembuat |

File query tersedia di `database/views_master.sql`. Jalankan file tersebut di HeidiSQL apabila view belum dibuat.

---

## Struktur Folder

```
spideyverse/
├── assets/css/style.css        # Tema pink, popup notifikasi, kartu bukti transaksi
├── database/spideyverse.sql    # Ekspor struktur + data + objek database
├── database/views_master.sql   # Pembuatan 6 view tabel master untuk HeidiSQL
├── inc/
│   ├── koneksi.php             # Koneksi PDO
│   ├── helpers.php             # Escaping, format, query mentah
│   ├── auth.php                # Session & cek role
│   ├── header.php / footer.php # Layout & efek loading form
│   ├── crud.php                # Konfigurasi master data
│   ├── crud_list.php
│   ├── crud_form.php
│   └── crud_hapus.php
├── barang.php ... laporan_*.php
├── login.php / logout.php
└── README.md
```

---

## Catatan Tampilan

- Setiap proses berhasil menampilkan **popup notifikasi** dengan tombol **OK**; popup tidak menutup sendiri.
- Tombol menampilkan bayangan tebal khas tombol fisik; saat ditekan, tombol tenggelam mendekati panel.
- Efek kilau muncul pada tombol utama dan tombol OK di popup.
- Tombol submit menampilkan status **Memproses...** saat form dikirim.
- Bukti transaksi ditampilkan sebagai kartu invoice dengan badge **Lunas**.

---

## Catatan Teknis

- Semua query memakai **prepared statement PDO** dengan SQL mentah.
- Trigger `SIGNAL SQLSTATE '45000'` ditangkap dan ditampilkan sebagai pesan ramah (mis. *"Stok barang tidak mencukupi"*).
- Notifikasi sukses muncul setelah login, logout, tambah/edit/hapus data, dan setiap transaksi.
- PHP CLI di mesin ini tidak memiliki ekstensi `mbstring`, sehingga aplikasi memakai fallback `panjang_teks()`.
