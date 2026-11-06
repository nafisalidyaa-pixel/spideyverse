# SpideyVerse

Aplikasi web persediaan barang **SpideyVerse** menggunakan **PHP 8 native + PDO + SQL mentah (tanpa framework / ORM)** dan database MySQL 8.0 (`spideyverse`) yang dikelola lewat HeidiSQL / Laragon.

Tema tampilan: **enterprise pink premium** — sidebar wine gelap, area kerja blush lembut, panel putih bersih, tabel rapi, dan tombol aksen rose.

---

## Prasyarat

- PHP 8.0 atau lebih baru dengan ekstensi `pdo_mysql`
- MySQL 8.0 / MariaDB
- Laragon (disarankan) atau PHP built-in server

---

## Cara Menjalankan

### 1. Impor database
Database dipecah menjadi beberapa berkas berdasarkan objeknya. Jalankan **secara berurutan**:

| Urutan | Berkas | Isi |
|---|---|---|
| 1 | `database/01_tabel.sql` | Struktur 15 tabel + data awal |
| 2 | `database/02_trigger.sql` | 4 trigger |
| 3 | `database/03_function.sql` | Function `fn_hitung_subtotal` |
| 4 | `database/04_sp.sql` | Stored procedure (penjualan keranjang) |
| 5 | `database/05_view.sql` | 6 view master |

**Lewat HeidiSQL**
1. Buat koneksi ke MySQL lokal.
2. Pilih menu **Import**, pilih semua berkas di bawah ini, lalu jalankan dalam satu sesi.
3. Urutan: `01_tabel.sql` → `02_trigger.sql` → `03_function.sql` → `04_sp.sql` → `05_view.sql`.

**Lewat terminal (gabung semua berkas lalu impor sekali)**
```powershell
Get-Content database\01_tabel.sql,database\02_trigger.sql,database\03_function.sql,database\04_sp.sql,database\05_view.sql -Raw | mysql -uroot --default-character-set=utf8mb4 -D spideyverse
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
- Transaksi penjualan: keranjang banyak barang (tambah/hapus baris) dalam satu transaksi
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
- **2 stored procedure**:
  - `sp_tambah_penjualan(iduser, idmargin, idbarang, jumlah)` - penjualan satu item (warisan modul praktikum)
  - `sp_tambah_penjualan_keranjang(iduser, idmargin, items JSON)` - penjualan keranjang multi-item; seluruh validasi (barang aktif, margin aktif, stok gabungan), perhitungan harga, subtotal, PPN, dan penyimpanan benar-benar diproses di database

## Pemakaian View di Halaman

- Halaman master **Barang, Satuan, Vendor, Role, User, Margin Penjualan** membaca dari view (`v_barang`, `v_satuan`, `v_vendor`, `v_role`, `v_user`, `v_margin_penjualan`)
- Dropdown barang pada **Transaksi Penjualan** juga membaca dari `v_barang`
- Laporan (Kartu Stok, Penjualan, Pengadaan, Retur, Stok) memakai SQL agregasi & `WINDOW FUNCTION` langsung di query
- Trigger `trg_penjualan_validasi_stok` dan `trg_penjualan_stok` otomatis memvalidasi dan mengurangi stok saat detail penjualan tersimpan

## View Master

File view master utama berada di `database/05_view.sql` (bagian dump ekspor yang berisi definisi ke-6 view).
File `database/views_master.sql` tetap tersedia sebagai alternatif pembuatan view **Create-or-Replace** yang aman dijalankan berulang di HeidiSQL.
File `database/sp_tambah_penjualan_keranjang.sql` tersedia sebagai alternatif pembuatan ulang SP keranjang.

---

## Struktur Folder

```
spideyverse/
├── assets/css/style.css        # Tema pink, popup notifikasi, kartu bukti transaksi
├── database/01_tabel.sql       # Struktur 15 tabel + data awal
├── database/02_trigger.sql     # 4 trigger
├── database/03_function.sql    # Function fn_hitung_subtotal
├── database/04_sp.sql          # Stored procedure keranjang penjualan
├── database/05_view.sql        # 6 view master + reset waktu sesi
├── database/views_master.sql   # Alternatif pembuatan ulang 6 view
├── database/sp_tambah_penjualan_keranjang.sql # Alternatif pembuatan ulang SP
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
- Margin penjualan dipilih otomatis dari margin yang berstatus aktif.
- Vendor pengadaan dipilih otomatis berdasarkan jenis setiap barang; barang dari vendor berbeda dibuatkan pengadaan terpisah.
- Role aktif pada topbar berupa menu pilihan; memilih role lain akan mengarahkan pengguna ke halaman login.
- Panel, kartu, dan tabel memakai latar putih bersih dengan garis halus, cocok untuk suasana kantoran.
- Tombol menampilkan bayangan tebal khas tombol fisik; saat ditekan, tombol tenggelam mendekati panel.
- Efek kilau muncul pada tombol utama dan tombol OK di popup.
- Tombol submit menampilkan status **Memproses...** saat form dikirim.
- Bukti transaksi ditampilkan sebagai kartu invoice dengan badge **Lunas**.
- Tidak ada menu mencetak pada aplikasi ini.

---

## Catatan Teknis

- Semua query memakai **prepared statement PDO** dengan SQL mentah.
- Trigger `SIGNAL SQLSTATE '45000'` ditangkap dan ditampilkan sebagai pesan ramah (mis. *"Stok barang tidak mencukupi"*).
- Notifikasi sukses muncul setelah login, logout, tambah/edit/hapus data, dan setiap transaksi.
- PHP CLI di mesin ini tidak memiliki ekstensi `mbstring`, sehingga aplikasi memakai fallback `panjang_teks()`.
