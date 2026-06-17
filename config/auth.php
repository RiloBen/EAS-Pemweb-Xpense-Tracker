<?php
// =====================================================
// config/auth.php — Middleware Autentikasi
// =====================================================

// Mulai session hanya jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Paksa pengguna login.
 * Dipanggil di awal setiap halaman yang memerlukan autentikasi.
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect pengguna yang sudah login keluar dari halaman auth.
 * Dipanggil di awal login.php dan register.php.
 */
function redirectIfLoggedIn(): void {
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Helper global: data pengguna yang sedang login
$currentUser = [
    'id'    => $_SESSION['user_id']  ?? null,
    'nama'  => $_SESSION['nama']     ?? '',
    'email' => $_SESSION['email']    ?? '',
];
