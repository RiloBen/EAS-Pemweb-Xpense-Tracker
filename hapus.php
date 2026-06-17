<?php
// =====================================================
// hapus.php — Proses Hapus Transaksi
// =====================================================
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$userId = (int) $currentUser['id'];
$id     = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Ambil data transaksi milik user ini (untuk mendapatkan nama foto + validasi ownership)
$stmt = mysqli_prepare($conn, "SELECT foto_nota FROM transaksi WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$transaksi = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Jika tidak ditemukan (bisa jadi milik user lain atau ID tidak ada)
if (!$transaksi) {
    header('Location: index.php');
    exit;
}

// Hapus file foto nota dari server jika ada
if (!empty($transaksi['foto_nota'])) {
    $filePath = __DIR__ . '/uploads/' . $transaksi['foto_nota'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Hapus record dari database
$stmtDel = mysqli_prepare($conn, "DELETE FROM transaksi WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmtDel, 'ii', $id, $userId);
mysqli_stmt_execute($stmtDel);
mysqli_stmt_close($stmtDel);

header('Location: index.php?deleted=1');
exit;
