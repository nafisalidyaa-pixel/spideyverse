# CATATAN SPIDEYVERSE — penjelasan lengkap (responsi)

> Disalin dari penjelasan lengkap. Proyek **PHP 8 + PDO + SQL mentah + MySQL 8**, app `C:\laragon\www\spideyverse`, db `spideyverse`, **tidak ada ORM/query builder**.

---

## 0. Inti proyek

- **Nama app/db:** `SPIDEYVERSE`, db `spideyverse`, app `C:\laragon\www\spideyverse`.
- **Bahasa:** **PHP 8 native + PDO + SQL mentah**. Tidak ada ORM/query builder. **Tidak ada fitur tampil query.**
- **DB:** **MySQL 8** (Laragon). `127.0.0.1:3306`, `root`, password kosong, `utf8mb4`, `PDO::ERRMODE_EXCEPTION`, `PDO::FETCH_ASSOC`, `PDO::ATTR_EMULATE_PREPARES=false`, `SET time_zone='+07:00'`.
- **Cara kerja:** request → `inc/auth.php` → `session_start`, `koneksi`, `cek_login/cek_role` → `header.php` → raw PDO prepared (`?`) → `db_all/db_one/db_val/db_exec/db_insert_id` → transaksi bila perlu → **SP `CALL`** → **trigger otomatis** → `kartu_stok` → **flash popup** → **redirect PRG** → `footer.php` JS.
- **Alur utama inventory:** **Pengadaan (PO)** → **Penerimaan** → **Retur** / **Penjualan** → **Kartu Stok**. **Pengadaan saja tidak menambah stok.**
- **DB layer** (`database/`): `01_tabel.sql`, `02_trigger.sql`, `03_function.sql`, `04_sp.sql`, `05_view.sql`, import urut.
- **4 trigger**, **3 stored procedure**, **2 function**, **6 view**.
- **Popup** `render_flash()`: **Berhasil / Tidak Dapat Diproses / Informasi**, **OK/Esc**, tidak auto-dismiss, tidak membuka mekanisme query. **3D button** + **ripple** + **loading “Memproses...”**.
- **Tidak ada print/cetak**; penjualan receipt **kertas putih enterprise invoice** + **LUNAS** + subtotal + **PPN 11%** + total. Tidak pakai kata terlarang (Omzet, Nota, Draft, Petugas, Qty, POS, Stok Menipis).
- **Password case-sensitive** `hash_equals`. `$_SESSION['user']` + `$_SESSION['pending_pengadaan']` **array biasa, tidak JSON**.
- **Akun default:** `admin/Admin123!` r1, `staff/Staff123!` r2, `kasir/Kasir123!` r3.

---

## 1. Cara menjalankan (step web ini jalan)

**Spesifikasi:** PHP 8 native + PDO + raw SQL + MySQL 8/MariaDB. App `C:\laragon\www\spideyverse`.

**Step:**
1. Nyalakan MySQL (atau Laragon), jalankan MySQL 8.0.30 `port=3306`, user `root`, password kosong.
2. Buat database `spideyverse`.
3. Import **urut**:
   - `database/01_tabel.sql` (tabel + data awal; `SET FOREIGN_KEY_CHECKS=0`, `CREATE DATABASE`, `USE`, `SET FOREIGN_KEY_CHECKS=1`)
   - `database/02_trigger.sql`
   - `database/03_function.sql`
   - `database/04_sp.sql`
   - `database/05_view.sql` (`DELIMITER ;;`, `DROP ... IF EXISTS`, `USE spideyverse`, paling akhir)
4. Jalankan PHP dari `C:\laragon\www`:
   ```powershell
   php -S localhost:80 -t C:\laragon\www
   ```
5. Buka `http://localhost/spideyverse`. `login.php`. Setelah login `index.php`.
6. Koneksi `C:\laragon\www\spideyverse\inc\koneksi.php`. Jalankan dari `C:\laragon\www` agar `localhost/spideyverse` ke `C:\laragon\www\spideyverse`.

**Data awal counts (verified):** barang 50, satuan 3, vendor 5, role 3, user 3, margin_penjualan 3, penjualan 4, detail_penjualan 4, pengadaan 7, detail_pengadaan 8, penerimaan 3, detail_penerimaan 4, retur 3, detail_retur 3, kartu_stok 11.

---

## 2. Koneksi (`inc/koneksi.php`)

```php
$DB_HOST='127.0.0.1'; $DB_PORT='3306'; $DB_NAME='spideyverse'; $DB_USER='root'; $DB_PASS='';
$pdo = new PDO("mysql:host=...;dbname=...;charset=utf8mb4", ..., [
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,
]);
$pdo->exec("SET time_zone='+07:00'");
```
`$pdo` global; helpers `global $pdo`. PHP `date_default_timezone_set('Asia/Jakarta')`. Koneksi gagal `die(...)`. **Semua SQL `?` + `$stmt->execute($params)`. Output `e()`.**

---

## 3. Cara programnya kerja

### Request lifecycle
1. `http://localhost/spideyverse/x.php`. `C:\laragon\www` docroot.
2. `require_once inc/auth.php` → `session_start()`, `ambil_query_lalu()`, `require inc/helpers.php` + `inc/koneksi.php`.
3. `cek_login()` / `cek_role(...)`. `header.php`. `render_flash`.
4. Logic page. Query raw PDO. `pesan_db`. `redirect_dengan_log`. `footer.php`.

### Helper PDO (`inc/helpers.php`)
- `db_all($sql,$params=[])` fetchAll
- `db_one` satu baris/null
- `db_val` satu nilai/`reset`
- `db_exec` INSERT/UPDATE/DELETE return `rowCount`
- `db_insert_id` `PDO::lastInsertId`
- `catat_query`, `sql_lengkap`, `QUERY_LOG`, `query_lalu`, `redirect_dengan_log`, `ambil_query_lalu` — **log internal, bukan halaman query.**
- `e` HTML escape; `rupiah`; `tanggal`, `tanggal_indonesia`, `tanggal_valid`, `panjang_teks`, `jenis_barang`, `badge_stok`, `badge_status_pengadaan`, `badge_jenis_transaksi`, `pesan_db`, `flash`, `ambil_flash`, `render_flash`.

### Transaksi
```php
$pdo->beginTransaction();
try { /* ... */ $pdo->commit(); }
catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); /* pesan_db */ }
```
- Dipakai `crud_form` (edit/insert + manual rollback), `pengadaan_tambah`, `pengadaan_hapus`, `penerimaan_tambah`, `retur_tambah`.
- SP penjualan `START TRANSACTION`; `EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;`; `COMMIT`.
- **`pengadaan_setujui.php` `CALL sp_simpan_pengadaan` + `UPDATE status='A'` TIDAK dibungkus app transaction.**
- **`sp_simpan_pengadaan` TIDAK `START TRANSACTION`.**
- Trigger `SIGNAL SQLSTATE '45000'` → `PDOException` → rollback app + `pesan_db()`.
- `SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=...`; app `pesan_db` kode `1644` → pesan teknis terbatas/ramah, bukan query.

### SP call PDO
```php
$stmt=$pdo->prepare('CALL sp_tambah_penjualan_keranjang(?, ?, ?)');
$stmt->execute([id_user(), $idmarginAuto, json_encode($items)]);
$hasil=$stmt->fetch(); $stmt->closeCursor();
$stmt=$pdo->prepare('CALL sp_simpan_pengadaan(?, ?, ?, ?, ?, ?)');
$stmt->execute([id_user(), $idvendor, $idbarang, $jumlah, $harga, $id]); $stmt->closeCursor();
```
**INOUT PDO MySQL tidak selalu propagate.** `sp_simpan_pengadaan` app pakai `p_idpengadaan` real non-0. `PDO::PARAM_INOUT`, `$pdo->lastInsertId()` setelah CALL tidak diandalkan. 

### Flash + redirect + session
- `$_SESSION['flash'][]`. `ambil_flash` bersihkan. `render_flash` card. **OK/Esc**, no auto-dismiss. `$_SESSION['query_lalu']`.
- `session_regenerate_id(true)`. `$_SESSION['user']`. `$_SESSION['pending_pengadaan']`. `unset`. `logout`.
- `redirect_dengan_log`. PRG. `header('Location:...'); exit;`. `POST` saja untuk aksi.
- `$_SESSION['pending_pengadaan'][$id]=$rowsBarang;` **array `['idbarang','harga','jumlah','idvendor']`, tidak `json_encode`.** `$dataPending=$_SESSION...??[]; is_array`. `unset` confirm. `idpengadaan` numeric key.

### Frontend
- Layout `inc/header.php`, `inc/footer.php`, `assets/css/style.css`. Sidebar role. `render_flash`. `header(Location)`.
- JS `footer.php`: submit `btn-loading`, disabled, `Memproses...`; click ripple `.riak`; `.btn`, `.btn-glow`, `.btn-merah`, `.btn-outline`, `.btn-kecil`, `.notif-ok`, `.menu a`. `login.php` juga loading. `hitungTotal` PPN `ROUND(subtotal*0.11)` preview, server recalculates. `hapus` confirm JS + server.
- `retur_tambah.php` `cekJumlah`. `margin` `help`. `pengadaan_tambah` vendor auto/dynamic. 
- **Tidak `print`/`Cetak`.** 
- **CSS** pink `#e91e63`, white etc. (jangan repeat warna dari summary kalau tidak relevan responsi, tapi enterprise white invoice). 

---

## 4. Login + hak akses

**Login (`login.php`):**
- `$_SERVER['REQUEST_METHOD']==='POST'`, `$_POST['username']`, `$_POST['password']`; username `trim`, password string **not trimmed**, `!==''`.
- `SELECT u.iduser,u.username,u.password,u.idrole,r.nama_role FROM user u JOIN role r ON r.idrole=u.idrole WHERE u.username=? LIMIT 1`, `$stmt->execute([$username])`.
- `!$data || !hash_equals((string)$data['password'],$password)` → `Username atau password salah.` **case-sensitive.** Password plaintext `user.password`. 
- `session_regenerate_id(true)`, `$_SESSION['user']=[iduser,username,idrole,nama_role]`, flash Berhasil `Berhasil masuk...`, `Location: index.php`.
- `role` dari `role`. `?role=` GET just message login role, not actual permission. `login` halaman hanya `SPIDEYVERSE`. `logout` clear session. `PDOException` `pesan_db`.
- Default `admin/Admin123!` r1, `staff/Staff123!` r2, `kasir/Kasir123!` r3.

**Role:** `role.idrole=1 Administrator,2 Staff Gudang,3 Kasir`. `user.idrole`. `cek_role`.
- Pengadaan `[1,2]`, pengadaan hapus `[1]`, penerimaan `[1,2]`, retur `[1,2]`, penjualan `[1,3]`, margin `[1]`, master `roles`, `edit_roles`.
- Admin CRUD `role`,`user`,`margin`, barang, satuan, vendor; Staff Gudang data lihat + pengadaan/penerimaan/retur; Kasir penjualan+riwayat+barang/stok. Admin `hapus user` tidak akun sendiri. `id_role`,`nama_role`. Role aktif topbar + ganti role relogin.

---

## 5. ERD lengkap + relasi

**PK/FK. `status` tidak CHECK. `kartu_stok.idtransaksi` tidak FK.**

### Master & akses
**`role`:** `idrole PK`, `nama_role`. → `user`.
**`user`:** `iduser PK`, `username`, `password`, `idrole FK→role.idrole`. `user 1:N pengadaan`, `penerimaan`, `retur`, `penjualan`, `margin_penjualan`.
**`satuan`:** `idsatuan PK`, `nama_satuan`, `status`. `satuan 1:N barang`. `PCS/BOX/SET`. 
**`barang`:** `idbarang PK`, `jenis`, `nama`, `idsatuan FK→satuan`, `status`, `harga`. `barang 1:N detail_pengadaan`, `detail_penerimaan`, `detail_penjualan`, `kartu_stok`. **harga beli, no stok.** `jenis A-J`, `status 1/0`.
**`vendor`:** `idvendor PK`, `nama_vendor`, `badan_hukum`, `status`. `vendor 1:N pengadaan`. `status A/N`.
**`margin_penjualan`:** `idmargin_penjualan PK`, `created_at`, `persen`, `status`, `iduser FK→user`, `updated_at`. `margin 1:N penjualan`. `status 1/0`. **hanya penjualan.**

### Pengadaan → Penerimaan → Retur
**`pengadaan`:** `idpengadaan PK`, `timestamp`, `user_iduser FK→user`, `status`, `vendor_idvendor FK→vendor`, `subtotal_nilai`, `ppn`, `total_nilai`. `pengadaan 1:N detail_pengadaan`, `1:N penerimaan`. `status 'P'→'A'`. `user_iduser` not `iduser`.
**`detail_pengadaan`:** `iddetail_pengadaan PK`, `harga_satuan`, `jumlah`, `sub_total`, `idbarang FK→barang`, `idpengadaan FK→pengadaan`. **no trigger stok.**
**`penerimaan`:** `idpenerimaan PK`, `created_at`, `status`, `idpengadaan FK→pengadaan`, `iduser FK→user`. `status 'A'`. `pengadaan 1:N penerimaan`; `penerimaan 1:N detail_penerimaan`; `penerimaan 1:N retur`.
**`detail_penerimaan`:** `iddetail_penerimaan PK`, `idpenerimaan FK→penerimaan`, `barang_idbarang FK→barang`, `jumlah_terima`, `harga_satuan_terima`, `sub_total_terima`. **memicu trg_penerimaan_stok.** `1:N detail_retur`.
**`retur`:** `idretur PK`, `created_at`, `idpenerimaan FK→penerimaan`, `iduser FK→user`. `1:N detail_retur`. not `idpengadaan`.
**`detail_retur`:** `iddetail_retur PK`, `jumlah`, `alasan`, `idretur FK→retur`, `iddetail_penerimaan FK→detail_penerimaan`. **memicu trg_retur_stok.**

### Penjualan
**`penjualan`:** `idpenjualan PK`, `created_at`, `subtotal_nilai`, `ppn`, `total_nilai`, `iduser FK→user`, `idmargin_penjualan FK→margin_penjualan`. `1:N detail_penjualan`.
**`detail_penjualan`:** `iddetail_penjualan PK`, `harga_satuan`, `jumlah`, `subtotal`, `penjualan_idpenjualan FK→penjualan`, `idbarang FK→barang`. **memicu 2 trigger.** `penjualan_idpenjualan`, `idbarang`.

### Stok
**`kartu_stok`:** `idkartu_stok PK`, `jenis_transaksi`, `masuk`, `keluar`, `stock`, `created_at`, `idtransaksi`, `idbarang FK→barang`. `barang 1:N kartu_stok`. `jenis M/K/R`. `idtransaksi` semantic no FK. **stok total SUM(masuk-keluar). no `barang.stok`. no update/delete app.**

### 6 Views (`05_view.sql`)
- `v_barang` `barang`+`satuan`, `kategori`, `stok=COALESCE(SUM(masuk-keluar),0)`, `status_barang`.
- `v_satuan` + `jumlah_barang`.
- `v_vendor` + `jumlah_pengadaan`, `status_vendor`.
- `v_role` + `jumlah_user`.
- `v_user` + `nama_role`, `jumlah_penjualan`.
- `v_margin_penjualan` + `status_margin`, `jumlah_penjualan`, `username`.
**No trigger/function `on view`. no `v_pengadaan`/`v_kartu_stok`.**

**Status kode (app, no CHECK):** `pengadaan 'P'/'A'`; `penerimaan.status='A'`; `kartu_stok M/K/R`; `barang.status 1/0`; `satuan 1/0`; `margin 1/0`; `vendor A/N`; `barang.jenis A-J`; `role`; `penerimaan` for retur; `pengadaan 'A'` for penerimaan; hapus `'P'` no penerimaan. `P` no penerimaan. 

---

## 6. SETIAP PROSES + “kalo mau hasilnya ini harus apa”

### 6.1 Login
**Kalo mau hasilnya masuk:** `POST login.php`, `username`,`password`, match + `hash_equals` case-sensitive, role ada. **Tabel `user`,`role`.** `user.idrole→role`. session + regenerate. Kalo salah `Username atau password salah.` Role gating. 
**Kalo tidak masuk:** username kosong/password kosong; password salah case; username tidak ada; role tidak ada; DB error; sudah `$_SESSION['user']` redirect; `cek_login`; `cek_role`.

### 6.2 Master data
**Kalo mau hasilnya tambah/edit/hapus master:** role; `POST _tambah/_edit`; `Simpan`; `crud_form`; `PDOException` rollback; flash; redirect.
- **`barang`** `nama` req, `jenis`, `idsatuan`, `status`, `harga`. satuan. `v_barang`. 
- **`satuan`** `nama_satuan`, `status`. 
- **`vendor`** `nama_vendor`,`badan_hukum`,`status`. 
- **`role`** `nama_role`. role `user`. 
- **`user`** `username`,`password`,`idrole`, no duplicate, no self delete. 
- **`margin`** `persen`,`status`,`iduser`, no auto off. 
**FK mencegah hapus dipakai. `pesan_db 1451`.**

### 6.3 PENGADAAN BUAT (`pengadaan_tambah.php`, role 1/2)
**Kalo mau hasilnya pengadaan baru `'P'` menunggu persetujuan:**

**Prasyarat:** vendor `status='A'`; barang `status=1`; `idbarang>0`,`jumlah>0`,`harga>=0`; minimal 1; vendor auto. vendor berbeda → pengadaan terpisah `$grup[$idvendor][]`.
**Vendor auto:** map `jenis`→vendor index, fallback vendor 0. 
**Form:** `baris[i][idbarang|harga|jumlah]`; vendor; `subtotal`. JS. 
**Process:**
1. `beginTransaction`
2. per grup `subtotal += fn_hitung_subtotal(harga,jumlah)` `SELECT fn_hitung_subtotal(?,?)`
3. `SELECT fn_calculate_ppn(?)`, `total=subtotal+ppn`
4. `INSERT INTO pengadaan (user_iduser,status,vendor_idvendor,subtotal_nilai,ppn,total_nilai) VALUES (?, 'P', ?, ?, ?, ?)`
5. `idpengadaan=db_insert_id`
6. `$_SESSION['pending_pengadaan'][$idpengadaan]=$rowsBarang;` **array no JSON**
7. `commit`, Berhasil, redirect `pengadaan_detail.php?id=...`.
**Tabel `pengadaan`. session. no trigger. no `detail_pengadaan`. no `kartu_stok`. no SP yet. `status='P'`. badge Menunggu. `pengadaan_detail` `jml_item=0`, session totals vs header.** multi vendor redirect first id. `catch rollback`. 
**Kalo mau hasilnya nomor lain:** harus valid + vendor + transaction + no partial. 
**Gap:** pending hanya session, no pending DB.

### 6.4 PENGADAAN SETUJUI (`pengadaan_setujui.php`, role 1/2, POST `id`)
**Kalo mau hasilnya `P→A`, detail masuk, totals korek, tidak stok pengadaan:**

**Prasyarat:** `POST`, `id>0`, `$_SESSION['pending_pengadaan'][$id]`, status `'P'`. 
**Process:**
1. `$dataPending=$_SESSION['pending_pengadaan'][$id] ?? []; is_array`
2. per item **`CALL sp_simpan_pengadaan(p_iduser,p_idvendor,p_idbarang,p_jumlah,p_harga,p_idpengadaan)`** `id_user`, `$idvendor`,`$idbarang`,`$jumlah`,`$harga`,`$id`, `closeCursor`
3. `unset($_SESSION['pending_pengadaan'][$id]);`
4. `UPDATE pengadaan SET status='A' WHERE idpengadaan=? AND status='P'`
5. `rowCount>0` Berhasil `Pengadaan #X disetujui. Barang siap diterima.` else Informasi `Pengadaan tidak berubah...`. `PDOException` `pesan_db`. redirect.
**SP `sp_simpan_pengadaan`. `detail_pengadaan`. `pengadaan`. `fn_calculate_ppn`. `SUM`. no `kartu_stok`. no trigger. no `fn_hitung_subtotal` PHP now. no app transaction. `unset` before update. `is_array([])`.** 
**Stok tetap; baru penerimaan. `'P'→'A'`. `pengadaan_setujui` only.** 
**`pengadaan.php`/`detail` `if status==='P'`, elseif `'A'`. badge. `penerimaan` requires `'A'`.** 

### 6.5 PENGADAAN HAPUS (`pengadaan_hapus.php`, role 1)
**Kalo mau hasilnya hapus PO menunggu:** `POST`, `id>0`, `status==='P'`, `COUNT(penerimaan WHERE idpengadaan=?)=0`, transaction, `DELETE detail_pengadaan`, `DELETE pengadaan`, commit. Berhasil. **`'A'` no; penerimaan no; FK.** no `kartu_stok`. `redirect_dengan_log`. 

### 6.6 PENERIMAAN (`penerimaan_tambah.php`, role 1/2)
**Kalo mau hasilnya stok masuk `M`:** pengadaan `'A'`; `idpengadaan`; `iddetail_penerimaan`; `jumlah_terima>=0`, `<=sisa`; minimal 1; transaction.
1. `INSERT INTO penerimaan (status,idpengadaan,iduser) VALUES ('A',?,?)` `idpenerimaan`
2. per `qty>0`: `INSERT INTO detail_penerimaan (idpenerimaan,barang_idbarang,jumlah_terima,harga_satuan_terima,sub_total_terima) VALUES (?,?,?,?,?)`, `sub_total_terima=fn_hitung_subtotal`, `harga_satuan_terima=harga_satuan`, `barang_idbarang`, `idpenerimaan`.
3. `commit`, Berhasil, redirect `penerimaan_detail`.
**`penerimaan`,`detail_penerimaan`,`kartu_stok`. `trg_penerimaan_stok`. `fn_hitung_subtotal`. `pengadaan` read. no pengadaan status change. no pengadaan trigger. `penerimaan.status='A'`. `idpengadaan`.** 
**partial.** `sudah` + `sisa`. penerimaan dapat >1. `diterima` check. `pengadaan_tambah`/`penerimaan` `total_qty` `SUM jumlah`. no qty word. `NULL` 0. 
**Trigger `M`.** 
**`penerimaan` `idpengadaan`,`iduser`,`status`. `detail_penerimaan.barang_idbarang`, not `idbarang`.** `kartu_stok idtransaksi=idpenerimaan`. 

### 6.7 RETUR (`retur_tambah.php`, role 1/2)
**Kalo mau hasilnya stok turun `R`:** `iddetail_penerimaan`; `jumlah>0`; `alasan`; `jumlah<=min(sisa_penerimaan, stok)`. transaction.
1. `INSERT INTO retur (idpenerimaan,iduser) VALUES (?,?)` `idpenerimaan` `detail_penerimaan.idpenerimaan`, `idretur`.
2. `INSERT INTO detail_retur (jumlah,alasan,idretur,iddetail_penerimaan) VALUES (?,?,?,?)`
3. `commit`, Berhasil `Retur #X berhasil diproses.`, redirect `retur_detail`.
**`retur`,`detail_retur`,`kartu_stok`. `trg_retur_stok`. app validasi. no `kartu_stok` app. `iddetail_penerimaan`, `idretur`. `alasan`. `retur.idpenerimaan`, not `idpengadaan`. `idbarang` from `detail_penerimaan`, not `detail_retur`.** 
**Trigger validasi `v_total_retur>v_jumlah_terima`, `v_stock<NEW.jumlah`. `SUM` includes current. `MIN`, `COALESCE`.** `sisa_penerimaan`, `sudah_retur`, `stok`, `maks`, `harga`. barang sudah terjual not. filter. no qty word. 

### 6.8 PENJUALAN (`penjualan_tambah.php`, role 1/3)
**Kalo mau hasilnya penjualan + stok turun `K` + receipt:** margin `status=1`, barang `status=1`, `jumlah>0`, stok cukup, `idmargin`, `idbarang`, `harga`. keranjang. 
**`CALL sp_tambah_penjualan_keranjang(p_iduser,p_idmargin,p_items)`** `p_items JSON` `[{"idbarang":1,"jumlah":2}]`, `json_encode`. SP. **`sp_tambah_penjualan`** 1. 
**harga jual `ROUND(harga + harga*persen/100)`. PPN `ROUND(subtotal*0.11)`. total. `penjualan`,`detail_penjualan`,`kartu_stok`. `pengadaan` no. receipt LUNAS. no cetak.** 
**`idmarginAuto` first active `v_margin_penjualan WHERE status=1 ORDER BY idmargin_penjualan LIMIT 1`. kasir not choose; hidden idmargin not read. `idmargin` margin active.** `idpengadaan` no. `idmargin_penjualan` `penjualan`. `p_iduser`. 
**SP `sp_tambah_penjualan_keranjang` `p_items JSON`, `JSON_TABLE`, `fn_hitung_subtotal`, `ROUND`, `START TRANSACTION`, `EXIT HANDLER`, `COMMIT`, `LAST_INSERT_ID`, `SELECT`. `sp_tambah_penjualan` `COUNT`, `status=1`, `fn_hitung_subtotal`, `ROUND`.** 
**Trigger `trg_penjualan_validasi_stok`, `trg_penjualan_stok`.** 

### 6.9 MARGIN (`margin.php`, role 1)
**Kalo mau hasilnya margin aktif:** `persen`, `status=1`, `iduser`. 
**Temanmu “margin dinyalakan lainya mati” TIDAK BENAR di kode ini.** `status` independen, no auto off, no trigger. Bisa beberapa `status=1`. **Transaksi hanya satu margin pertama `status=1 ORDER BY idmargin_penjualan LIMIT 1`.** `sp_tambah_penjualan` `status=1`. **Tidak mengubah histori. `idmargin_penjualan`.** 
**Data verified `1=10% on`,`2=15% off`,`3=20% off`. no trigger margin.** 
**Kalo mau exclusive satu aktif:** app `UPDATE margin_penjualan SET status=0 WHERE idmargin_penjualan<>?` + `status=1`, transaction; atau trigger. **Belum.** 
**Margin no pengadaan.** PPN separate 11%. 

### 6.10 Kartu stok + laporan
**Kartu Stok** `kartu_stok`, `barang`, `satuan`. `M`,`K`,`R`. `masuk`,`keluar`,`stock`,`created_at`,`idtransaksi`,`idbarang`. **stok `SUM(masuk-keluar)`.** 
**Laporan:** `laporan_pengadaan`,`laporan_penjualan`,`laporan_retur`,`laporan_stok`,`laporan_penjualan saya`,`kartu_stok`. filter tanggal `tanggal_valid`. `v_*`. **no query display.** 
**Kalo mau hasilnya laporan:** data + filter + role; output read-only. 
**Dashboard role** stok rendah `<10`, total stok, count, pengadaan `'P'`, `'A'` sisa `diterima<total_qty`, penjualan. `badge`. 

### 6.11 Master hapus + `redirect_dengan_log`
`crud_hapus.php`, POST, role, `DELETE`, `PDOException 1451`, no self user, `redirect`. `pengadaan_hapus` special. 

---

## 7. DB LAYER PRESISI

### 4 TRIGGER (`database/02_trigger.sql`)
**`trg_penerimaan_stok` `AFTER INSERT ON detail_penerimaan FOR EACH ROW`:**
```sql
v_stock=COALESCE(SUM(masuk-keluar),0) FROM kartu_stok WHERE idbarang=NEW.barang_idbarang;
INSERT INTO kartu_stok (jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
VALUES ('M',NEW.jumlah_terima,0,v_stock+NEW.jumlah_terima,NEW.idpenerimaan,NEW.barang_idbarang);
```
**`trg_penjualan_validasi_stok` `BEFORE INSERT ON detail_penjualan`:**
```sql
IF NEW.jumlah<=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Jumlah penjualan harus lebih dari nol'; END IF;
v_stock=COALESCE(SUM(masuk-keluar),0) FROM kartu_stok WHERE idbarang=NEW.idbarang;
IF v_stock<NEW.jumlah THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stok barang tidak mencukupi'; END IF;
```
**`trg_penjualan_stok` `AFTER INSERT ON detail_penjualan`:**
```sql
v_stock=COALESCE(SUM(masuk-keluar),0) FROM kartu_stok WHERE idbarang=NEW.idbarang;
INSERT INTO kartu_stok (jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
VALUES ('K',0,NEW.jumlah,v_stock-NEW.jumlah,NEW.penjualan_idpenjualan,NEW.idbarang);
```
**`trg_retur_stok` `AFTER INSERT ON detail_retur`:**
```sql
SELECT jumlah_terima,barang_idbarang INTO v_jumlah_terima,v_idbarang FROM detail_penerimaan WHERE iddetail_penerimaan=NEW.iddetail_penerimaan;
v_total_retur=COALESCE(SUM(jumlah),0) FROM detail_retur WHERE iddetail_penerimaan=NEW.iddetail_penerimaan;
IF v_total_retur>v_jumlah_terima THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Jumlah retur melebihi penerimaan'; END IF;
v_stock=COALESCE(SUM(masuk-keluar),0) FROM kartu_stok WHERE idbarang=v_idbarang;
IF v_stock<NEW.jumlah THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stok tidak cukup untuk retur'; END IF;
INSERT INTO kartu_stok (jenis_transaksi,masuk,keluar,stock,idtransaksi,idbarang)
VALUES ('R',0,NEW.jumlah,v_stock-NEW.jumlah,NEW.idretur,v_idbarang);
```
**Tidak trigger `pengadaan`, `detail_pengadaan`, `margin`, `barang`. no update/delete kartu_stok trigger.** 
**kartu stok `M +`, `K -`, `R -`. no `P` stock. no pengadaan stok.** `SIGNAL` rollback. `idtransaksi`. `idbarang`. 

### 3 STORED PROCEDURE (`database/04_sp.sql`)
**`sp_simpan_pengadaan`:**
```sql
CREATE PROCEDURE sp_simpan_pengadaan(
 IN p_iduser INT, IN p_idvendor INT, IN p_idbarang INT, IN p_jumlah INT, IN p_harga INT, INOUT p_idpengadaan INT
)
BEGIN
 DECLARE v_subtotal INT; SET v_subtotal=p_jumlah*p_harga;
 IF p_idpengadaan=0 THEN
   INSERT INTO pengadaan(user_iduser,vendor_idvendor,status,subtotal_nilai,ppn,total_nilai) VALUES(p_iduser,p_idvendor,'P',0,0,0);
   SET p_idpengadaan=LAST_INSERT_ID();
 END IF;
 INSERT INTO detail_pengadaan(idpengadaan,idbarang,harga_satuan,jumlah,sub_total) VALUES(p_idpengadaan,p_idbarang,p_harga,p_jumlah,v_subtotal);
 UPDATE pengadaan SET
   subtotal_nilai=(SELECT COALESCE(SUM(sub_total),0) FROM detail_pengadaan WHERE idpengadaan=p_idpengadaan),
   ppn=fn_calculate_ppn((SELECT COALESCE(SUM(sub_total),0) FROM detail_pengadaan WHERE idpengadaan=p_idpengadaan)),
   total_nilai=subtotal_nilai+ppn
 WHERE idpengadaan=p_idpengadaan;
END
```
- `v_subtotal=p_jumlah*p_harga`, **equivalent `fn_hitung_subtotal`, not call.**
- `status='P'`, **not `'A'`. app sets `'A'`.**
- `subtotal_nilai`,`ppn`,`total_nilai`, `COALESCE SUM`, `fn_calculate_ppn`. MySQL left-right updated. **not atomic. no `kartu_stok`. no trigger. `p_idpengadaan=0` branch app not use. `INOUT` not rely PDO.**
**`sp_tambah_penjualan` `IN p_iduser,p_idmargin,p_idbarang,p_jumlah`:** `START TRANSACTION`, `EXIT HANDLER`, `SIGNAL`, `COUNT status=1`, harga, `fn_hitung_subtotal`, `ROUND ppn`, `penjualan`,`detail_penjualan`, `LAST_INSERT_ID`, `COMMIT`, `SELECT id_penjualan,harga_jual,subtotal,ppn,total_pembayaran`. **trigger stok. no `kartu_stok` SP. `ROUND` not `fn_calculate_ppn`.**
**`sp_tambah_penjualan_keranjang` `IN p_iduser,p_idmargin,p_items JSON`:** `START TRANSACTION`, `EXIT HANDLER`, margin `status=1`, `JSON_LENGTH`, `JSON_TABLE`, barang `status=1`, `jumlah>0`, stok `SUM`, `fn_hitung_subtotal`, `ROUND ppn`, `penjualan`,`detail_penjualan`,`COMMIT`,`SELECT`. `p_items`. no `kartu_stok`. `ROUND`. 
**Kasir app uses keranjang. `sp_simpan_pengadaan` pengadaan.** 

### 2 FUNCTION (`database/03_function.sql`)
**`fn_hitung_subtotal(p_harga INT,p_jumlah INT) RETURNS int DETERMINISTIC` `RETURN p_harga*p_jumlah`.** subtotal. SP + app + penerimaan + pengadaan. 
**`fn_calculate_ppn(p_subtotal DECIMAL(14,2)) RETURNS DECIMAL(14,2) DETERMINISTIC` `RETURN ROUND(p_subtotal*0.11)`. PPN 11%.** `sp_simpan_pengadaan`, pengadaan app. **penjualan `ROUND` not call.** 

### 6 VIEW
`v_barang`, `v_satuan`, `v_vendor`, `v_role`, `v_user`, `v_margin_penjualan`. 

### SESSION
`session_start`, `session_regenerate_id`, `$_SESSION['user']`, `$_SESSION['flash']`, `$_SESSION['query_lalu']`, `$_SESSION['pending_pengadaan']` **array no JSON**, `$_SESSION['pending_pengadaan'][$id]`, `unset`, `is_array`. `$_POST`,`$_GET`. no `$_SESSION` DB. no `json_encode` pending. `idmargin` hidden not read. `redirect_dengan_log`. 

---

## 8. “KALAU MAU HASILNYA INI HARUS APA” CHECKLIST

| Hasil | Harus apa | Tabel | Trigger/SP/FN | Relasi |
|---|---|---|---|---|
| Login | username/password benar case | `user`,`role` | `hash_equals` | `user.idrole→role.idrole` |
| Master | role, POST, Simpan | master | `crud_form`,`db_exec` | FK |
| Pengadaan `'P'` | vendor A, barang 1, valid, group vendor, transaction, session | `pengadaan`,session | `fn_hitung_subtotal`,`fn_calculate_ppn`; no SP yet | `pengadaan→user/vendor` |
| Detail pengadaan | setujui + pending + `CALL` | `detail_pengadaan`,`pengadaan` | `sp_simpan_pengadaan`,`fn_calculate_ppn`,`fn_hitung_subtotal` | `detail_pengadaan→pengadaan/barang` |
| Pengadaan `'A'` | `P→A status='P'`; ideal detail+atomic | `pengadaan` | app UPDATE | - |
| Stok naik | pengadaan `'A'` + penerimaan | `penerimaan`,`detail_penerimaan`,`kartu_stok` | `trg_penerimaan_stok M` + `fn_hitung_subtotal` | `penerimaan→pengadaan/user`; `detail_penerimaan→penerimaan/barang` |
| Partial penerimaan | `jumlah<=sisa` | `detail_penerimaan` | `trg_penerimaan_stok` per insert | - |
| Retur turun | `jumlah<=min(sisa,stok)`, alasan | `retur`,`detail_retur`,`kartu_stok` | `trg_retur_stok R` + `SIGNAL` | `detail_retur→retur/detail_penerimaan`; `retur→penerimaan/user` |
| Stok turun jual | margin+barang+stok+`CALL SP` | `penjualan`,`detail_penjualan`,`kartu_stok` | `sp_tambah_penjualan*`,`trg_penjualan_validasi_stok`,`trg_penjualan_stok K`,`fn_hitung_subtotal`,`ROUND`/`fn_calculate_ppn` | `penjualan→user/margin`; `detail_penjualan→penjualan/barang` |
| Margin dipakai | `status=1`, first `id`, margin not P | `margin_penjualan`,`penjualan` | SP | `margin→user`,`penjualan→margin` |
| Margin exclusive | **belum**; update others/trigger | `margin_penjualan` | - | - |
| Laporan stok | `kartu_stok` | `kartu_stok`,`barang` | `SUM` | `kartu_stok→barang` |
| Hapus pengadaan | `'P'`, no penerimaan, role1, POST | `pengadaan`,`detail_pengadaan` | - | - |
| Tidak duplikat | key+insert+transaction+`status='P'` | all | trigger+unique app | - |

---

## 9. CATATAN RESPONSI “YANG HARUS BENERIN” (saran; tidak auto-applied)

1. **Atomic `sp_simpan_pengadaan`.** app `beginTransaction`, `unset` setelah `commit`, abort empty, `WHERE status='P'`; SP `START TRANSACTION` + `EXIT HANDLER ROLLBACK RESIGNAL`. Currently no app transaction in `pengadaan_setujui`. 
2. **Jangan `'A'` kalau no details.** `if empty($dataPending) abort`, `AND EXISTS(SELECT 1 FROM detail_pengadaan...)`. Currently `is_array([])`. 
3. **Retry/duplicate.** currently `unset` before `UPDATE status='A'`; if update fails pending lost; partial insert possible. `unset` after commit; transaction; check `status='P'`. 
4. **INOUT.** `SELECT LAST_INSERT_ID()` after CALL or app id; no rely PDO `PARAM_INOUT`/`lastInsertId` after CALL. currently app passes real id. 
5. **PPN konsisten `fn_calculate_ppn`.** `sp_tambah_penjualan*` `ROUND`, `sp_simpan_pengadaan` uses fn, app pengadaan uses fn. penjualan SP `ROUND`. 
6. **`kartu_stok.idtransaksi`** no FK; split `idpenerimaan/idpenjualan/idretur` or semantic doc. 
7. **`02_trigger.sql` `trg_retur_stok` `\`** before `VALUES`. verbatim import `1064`. remove `\`. 
8. **`sp_simpan_pengadaan` `v_subtotal`** `p_jumlah*p_harga`, could `fn_hitung_subtotal`. 
9. **`total_nilai=subtotal_nilai+ppn`** MySQL left-right, could explicit `SUM+fn`. 
10. **Password** plaintext+`hash_equals` case-sensitive; security `password_hash`+`password_verify`. not responsi core. 
11. **Penjualan `p_items JSON`.** pending pengadaan no JSON; if no JSON anywhere `sp_tambah_penjualan_keranjang` redesign `sp_tambah_penjualan` loop/temp. 
12. **Margin exclusive** if friend correct. currently no. 
13. **Status not CHECK.** `P/A`, `A`, `M/K/R`, `1/0`,`A/N`, `NULL COALESCE`, consistent queries. 
14. **Pengadaan ≠ stok.** PO `P→A`, stok `M` penerimaan, `K` penjualan, `R` retur. no `P` stock. 
15. **`idpengadaan` PO `user_iduser`, `detail_penjualan.penjualan_idpenjualan`, `detail_penerimaan.barang_idbarang`, `retur.idpenerimaan`, `detail_retur.iddetail_penerimaan`.** no naming confusion. 

---

## 10. JAWABAN SINGKAT RESPONSI

**Pengadaan:** `pengadaan`+`detail_pengadaan`, `'P'→'A'`, pending **session array** no JSON, `fn_hitung_subtotal`, `fn_calculate_ppn`, **`sp_simpan_pengadaan`**, **tidak trigger stok**. Buat `'P'`, detail session, setujui `CALL`, recalc, `'A'`, penerimaan. 
**Stok:** `kartu_stok`, `barang 1:N`, `stok=SUM(masuk-keluar)`, `M/K/R`. **Penerimaan `trg_penerimaan_stok M` +stok; Penjualan `trg_penjualan_validasi_stok` + `trg_penjualan_stok K` -stok; Retur `trg_retur_stok R` -stok +validasi.** no `barang.stok`, no pengadaan stock. 
**Penjualan:** `sp_tambah_penjualan`/`sp_tambah_penjualan_keranjang`, `penjualan`+`detail_penjualan`, margin `status=1` pertama, harga+margin, `fn_hitung_subtotal`, PPN11, transaction, triggers, receipt. no cetak. `p_items JSON` penjualan SP. 
**DB:** raw PDO prepared, `?`, `FETCH_ASSOC`, no emulate, `ERRMODE_EXCEPTION`, transaction, `SIGNAL`, `pesan_db`, escape, no ORM. `fn`, `SP`, `trigger`, `view`, `session`. 
**Margin:** temanmu **salah**; **tidak ada auto-off**; **hanya satu margin pertama aktif dipakai**. 
**Urutan buat stok:** Pengadaan `'A'` → penerimaan → `M`; penjualan `K`; retur `R`. 
**Kalau besok responsi, jelaskan dengan urutan:** request/login → role → PDO → tabel/relasi → proses → tabel yang kena → `fn` → `SP` → `trigger` → `kartu_stok` → output/popup/receipt. Jawab “kalau mau hasilnya ini harus apa” dengan prasyarat + POST + validasi + transaction + query/SP + trigger + hasil. 

---

## 11. FILE TERKAIT

- `C:\laragon\www\spideyverse\inc\koneksi.php`
- `C:\laragon\www\spideyverse\inc\auth.php`
- `C:\laragon\www\spideyverse\inc\helpers.php`
- `C:\laragon\www\spideyverse\inc\header.php`, `inc\footer.php`
- `C:\laragon\www\spideyverse\inc\crud.php`, `crud_form.php`, `crud_list.php`, `crud_hapus.php`
- `C:\laragon\www\spideyverse\login.php`, `logout.php`, `index.php`, `margin.php`
- `C:\laragon\www\spideyverse\pengadaan_tambah.php`, `pengadaan_detail.php`, `pengadaan_setujui.php`, `pengadaan_hapus.php`, `pengadaan.php`
- `C:\laragon\www\spideyverse\penerimaan_tambah.php`, `penerimaan.php`, `penerimaan_detail.php`
- `C:\laragon\www\spideyverse\retur_tambah.php`, `retur.php`, `retur_detail.php`
- `C:\laragon\www\spideyverse\penjualan_tambah.php`, `penjualan.php`, `penjualan_detail.php`
- `C:\laragon\www\spideyverse\kartu_stok.php`, `laporan_*.php`
- `C:\laragon\www\spideyverse\database\01_tabel.sql`, `02_trigger.sql`, `03_function.sql`, `04_sp.sql`, `05_view.sql`
