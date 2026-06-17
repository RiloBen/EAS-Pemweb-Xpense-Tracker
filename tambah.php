<?php
// =====================================================
// tambah.php — Halaman & Proses Tambah Transaksi
// =====================================================
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$userId = (int) $currentUser['id'];
$errors = [];
$old    = [];

// ---- Proses POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis      = $_POST['jenis']      ?? '';
    $jumlah     = $_POST['jumlah']     ?? '';
    $tanggal    = $_POST['tanggal']    ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $old        = compact('jenis', 'jumlah', 'tanggal', 'keterangan');

    // Validasi
    if (!in_array($jenis, ['pendapatan', 'pengeluaran'])) $errors[] = 'Jenis transaksi tidak valid.';
    if (!is_numeric($jumlah) || $jumlah <= 0)             $errors[] = 'Jumlah harus berupa angka positif.';
    if (empty($tanggal))                                  $errors[] = 'Tanggal wajib diisi.';
    if (empty($keterangan))                               $errors[] = 'Keterangan wajib diisi.';

    // Upload foto nota
    $fotoNama = null;
    if (!empty($_FILES['foto_nota']['name'])) {
        $file      = $_FILES['foto_nota'];
        $allowedExt = ['jpg', 'jpeg', 'png'];
        $maxSize    = 2 * 1024 * 1024; // 2 MB

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            $errors[] = 'Foto nota hanya boleh berformat JPG, JPEG, atau PNG.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Ukuran foto nota maksimal 2 MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah foto.';
        } else {
            $fotoNama = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
            if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $fotoNama)) {
                $errors[] = 'Gagal menyimpan foto. Pastikan folder uploads/ dapat ditulis.';
                $fotoNama = null;
            }
        }
    }

    // Simpan ke database
    if (empty($errors)) {
        $jumlahDecimal = (float) $jumlah;
        $stmt = mysqli_prepare($conn,
            "INSERT INTO transaksi (user_id, jenis, jumlah, tanggal, keterangan, foto_nota)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isdsss',
            $userId, $jenis, $jumlahDecimal, $tanggal, $keterangan, $fotoNama);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            header('Location: index.php?added=1');
            exit;
        } else {
            $errors[] = 'Terjadi kesalahan saat menyimpan data.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi — Xpense Tracker</title>
    <meta name="description" content="Tambah transaksi baru ke catatan keuangan Anda.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
</head>
<body class="bg-slate-950 min-h-screen font-sans antialiased text-white">

    <!-- Navbar -->
    <nav class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-slate-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2.5 group">
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="font-semibold text-white group-hover:text-indigo-400 transition-colors">Xpense Tracker</span>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-400 hidden sm:block">
                    👤 <?= htmlspecialchars($currentUser['nama']) ?>
                </span>
                <a href="logout.php" id="btn-logout"
                    class="text-sm text-slate-400 hover:text-red-400 transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <a href="index.php" class="w-9 h-9 flex items-center justify-center bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg transition-colors">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-white">Tambah Transaksi</h1>
                <p class="text-sm text-slate-400">Catat pemasukan atau pengeluaran baru</p>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm" role="alert">
            <p class="font-medium mb-1">Terdapat <?= count($errors) ?> kesalahan:</p>
            <ul class="list-disc list-inside space-y-0.5">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl">
            <form method="POST" action="tambah.php" enctype="multipart/form-data" novalidate>
                <div class="space-y-5">

                    <!-- Jenis Transaksi -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Jenis Transaksi</label>
                        <div class="grid grid-cols-2 gap-3" id="jenis-selector">
                            <label class="jenis-option cursor-pointer">
                                <input type="radio" name="jenis" value="pendapatan" class="sr-only"
                                    <?= (($old['jenis'] ?? '') === 'pendapatan') ? 'checked' : '' ?>>
                                <div class="jenis-card flex items-center gap-2.5 p-3.5 bg-slate-800 border-2 border-slate-700 rounded-xl transition-all hover:border-emerald-500/50">
                                    <div class="w-8 h-8 bg-emerald-500/15 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium text-sm text-slate-300">Pendapatan</span>
                                </div>
                            </label>
                            <label class="jenis-option cursor-pointer">
                                <input type="radio" name="jenis" value="pengeluaran" class="sr-only"
                                    <?= (($old['jenis'] ?? '') === 'pengeluaran') ? 'checked' : '' ?>>
                                <div class="jenis-card flex items-center gap-2.5 p-3.5 bg-slate-800 border-2 border-slate-700 rounded-xl transition-all hover:border-red-500/50">
                                    <div class="w-8 h-8 bg-red-500/15 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                        </svg>
                                    </div>
                                    <span class="font-medium text-sm text-slate-300">Pengeluaran</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Jumlah -->
                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-slate-300 mb-1.5">Jumlah (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium">Rp</span>
                            <input type="number" id="jumlah" name="jumlah" min="1" step="1" required
                                value="<?= htmlspecialchars($old['jumlah'] ?? '') ?>"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                                placeholder="0">
                        </div>
                    </div>

                    <!-- Tanggal -->
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-slate-300 mb-1.5">Tanggal</label>
                        <input type="text" id="tanggal" name="tanggal" required readonly
                            value="<?= htmlspecialchars($old['tanggal'] ?? '') ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all cursor-pointer"
                            placeholder="Pilih tanggal...">
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-slate-300 mb-1.5">Keterangan</label>
                        <input type="text" id="keterangan" name="keterangan" required maxlength="255"
                            value="<?= htmlspecialchars($old['keterangan'] ?? '') ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="Contoh: Makan siang, Gaji bulanan, ...">
                    </div>

                    <!-- Foto Nota -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">
                            Foto Nota <span class="text-slate-500 font-normal">(opsional, maks. 2 MB)</span>
                        </label>
                        <div id="upload-area"
                            class="relative border-2 border-dashed border-slate-600 rounded-xl p-6 text-center hover:border-indigo-500/60 transition-colors cursor-pointer">
                            <input type="file" id="foto_nota" name="foto_nota" accept=".jpg,.jpeg,.png"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div id="upload-placeholder">
                                <svg class="w-8 h-8 text-slate-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm text-slate-400">Klik atau seret foto ke sini</p>
                                <p class="text-xs text-slate-600 mt-1">JPG, JPEG, PNG • Maks. 2 MB</p>
                            </div>
                            <div id="preview-container" class="hidden">
                                <img id="preview-img" src="" alt="Preview foto nota"
                                    class="max-h-48 mx-auto rounded-lg object-contain">
                                <p id="preview-name" class="text-xs text-slate-400 mt-2"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-6">
                    <a href="index.php"
                        class="flex-1 py-2.5 px-4 bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-300 text-sm font-medium rounded-xl text-center transition-colors">
                        Batal
                    </a>
                    <button type="submit" id="btn-tambah"
                        class="flex-1 py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-500/25">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
