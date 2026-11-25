<?php
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: pengadaan.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID pengadaan tidak valid.');
    header('Location: pengadaan.php');
    exit;
}

try {
    // Konfirmasi pengadaan: data detail barang yang ditampung di session
    // baru dimasukkan ke tabel detail_pengadaan setelah tombol konfirmasi dipencet.
    if (!empty($_SESSION['pending_pengadaan'][$id])) {
        foreach ($_SESSION['pending_pengadaan'][$id] as $item) {
            db_exec(
                "INSERT INTO detail_pengadaan
                    (harga_satuan, jumlah, sub_total, idbarang, idpengadaan)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    (int) $item['harga'],
                    (int) $item['jumlah'],
                    (int) db_val(
                        "SELECT fn_hitung_subtotal(?, ?)",
                        [(int) $item['harga'], (int) $item['jumlah']]
                    ),
                    (int) $item['idbarang'],
                    $id,
                ]
            );
        }
        unset($_SESSION['pending_pengadaan'][$id]);
    }

    $jumlah = db_exec(
        "UPDATE pengadaan SET status = 'A' WHERE idpengadaan = ? AND status = 'D'",
        [$id]
    );

    if ($jumlah > 0) {
        flash('sukses', 'Pengadaan #' . $id . ' disetujui. Barang siap diterima.');
    } else {
        flash('info', 'Pengadaan tidak berubah (mungkin sudah disetujui).');
    }
} catch (PDOException $e) {
    flash('error', pesan_db($e));
}

redirect_dengan_log('pengadaan_detail.php?id=' . $id);
