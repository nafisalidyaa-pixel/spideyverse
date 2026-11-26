<?php
// Koneksi database spideyverse pakai PDO + SQL mentah (tanpa ORM).

// Data koneksi MySQL/Laragon.
$DB_HOST = '127.0.0.1';
$DB_PORT = '3306';
$DB_NAME = 'spideyverse';
$DB_USER = 'root';
$DB_PASS = '';

// Bikin koneksi PDO, set error mode exception, fetch assoc, dan matikan emulasi prepare.
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    // Samakan zona waktu database dengan WIB.
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    // Kalau koneksi gagal, stop program dan tampilkan pesan.
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}
