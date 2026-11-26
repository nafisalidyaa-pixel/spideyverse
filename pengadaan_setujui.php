<?php
// Setujui pengadaan: pindahkan detail dari session ke tabel, lalu ubah status P -> A.
require_once __DIR__ . '/inc/auth.php';
cek_role([1, 2]);

// Hanya boleh lewat POST.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: pengadaan.php');
    exit;
}

// Ambil id pengadaan.
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'ID pengadaan tidak valid.');
    header('Location: pengadaan.php');
    exit;
}

try {
    // Konfirmasi pengadaan: data detail barang yang ditampung di session
    // baru dimasukkan ke tabel detail_pengadaan melalui stored procedure
    // sp_simpan_pengadaan, lalu status pengadaan diubah.
    $dataPending = $_SESSION['pending_pengadaan'][$id] ?? [];
    if (is_array($dataPending)) {
        // Simpan tiap barang ke detail_pengadaan lewat SP.
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
        // Session pending sudah dipakai, hapus.
        unset($_SESSION['pending_pengadaan'][$id]);
    }

    // Ubah status pengadaan dari P ke A.
    $jumlah = db_exec(
        "UPDATE pengadaan SET status = 'A' WHERE idpengadaan = ? AND status = 'P'",
        [$id]
    );

    // Kasih pesan sesuai hasil update.
    if ($jumlah > 0) {
        flash('sukses', 'Pengadaan #' . $id . ' disetujui. Barang siap diterima.');
    } else {
        flash('info', 'Pengadaan tidak berubah (mungkin sudah disetujui).');
    }
} catch (PDOException $e) {
    flash('error', pesan_db($e));
}

// Balik ke halaman detail.
redirect_dengan_log('pengadaan_detail.php?id=' . $id);
