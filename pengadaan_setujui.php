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
    // (dalam bentuk JSON) dimasukkan ke tabel detail_pengadaan melalui
    // stored procedure sp_simpan_pengadaan, lalu status pengadaan diubah.
    $dataPending = json_decode($_SESSION['pending_pengadaan'][$id] ?? '', true);
    if (is_array($dataPending)) {
        foreach ($dataPending as $item) {
            // Memanggil stored procedure: sp_simpan_pengadaan (definisi di database/04_sp.sql)
            // SP menyimpan detail barang lalu menghitung ulang subtotal, PPN, dan total
            // melalui function fn_calculate_ppn (definisi di database/03_function.sql).
            $stmt = $pdo->prepare('CALL sp_simpan_pengadaan(?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                id_user(),
                (int) $item['idvendor'],
                (int) $item['idbarang'],
                (int) $item['jumlah'],
                (int) $item['harga'],
                $id,
            ]);
            $stmt->closeCursor();
        }
        unset($_SESSION['pending_pengadaan'][$id]);
    }

    $jumlah = db_exec(
        "UPDATE pengadaan SET status = 'A' WHERE idpengadaan = ? AND status = 'P'",
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
