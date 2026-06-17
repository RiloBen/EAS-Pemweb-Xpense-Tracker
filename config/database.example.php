<?php
// =====================================================
// config/database.example.php — Template Koneksi Database
// =====================================================
// SALIN file ini menjadi config/database.php
// lalu isi dengan kredensial database Anda.
// File database.php ada di .gitignore (tidak ter-commit).
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Username MySQL Anda
define('DB_PASS', 'your_password'); // Password MySQL Anda
define('DB_NAME', 'xpense_tracker');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    die('Koneksi ke database gagal. Silakan hubungi administrator.');
}

mysqli_set_charset($conn, 'utf8mb4');
