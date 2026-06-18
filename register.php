<?php
// =====================================================
// register.php — Halaman & Proses Register
// =====================================================
require_once 'config/database.php';
require_once 'config/auth.php';

redirectIfLoggedIn();

$errors = [];
$old    = []; // untuk repopulate form jika ada error

// ---- Proses POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama             = trim($_POST['nama']             ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         =      $_POST['password']         ?? '';
    $confirmPassword  =      $_POST['confirm_password'] ?? '';

    $old = compact('nama', 'email');

    // Validasi
    if (empty($nama))            $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email))           $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (empty($password))        $errors[] = 'Password wajib diisi.';
    elseif (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $confirmPassword) $errors[] = 'Konfirmasi password tidak cocok.';

    // Cek email duplikat
    if (empty($errors)) {
        $stmtCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtCheck, 's', $email);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            $errors[] = 'Email sudah terdaftar. Gunakan email lain atau login.';
        }
        mysqli_stmt_close($stmtCheck);
    }

    // Insert jika lolos semua validasi
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $nama, $email, $hashedPassword);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Xpense Tracker</title>
    <meta name="description" content="Buat akun Xpense Tracker gratis dan mulai lacak keuangan pribadi Anda hari ini.">
    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] } } }
        }
    </script>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center font-sans antialiased">

    <!-- Background glow -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-4 py-10">

        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="assets/logo.svg" alt="Xpense Tracker" class="w-14 h-14 rounded-2xl object-contain shadow-lg shadow-indigo-500/30 mb-4 mx-auto">
            <h1 class="text-2xl font-bold text-white tracking-tight">Xpense Tracker</h1>
            <p class="text-slate-400 mt-1 text-sm">Mulai perjalanan keuangan cerdasmu.</p>
        </div>

        <!-- Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-6">Buat Akun Baru</h2>

            <?php if (!empty($errors)): ?>
            <div class="mb-5 p-3.5 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm" role="alert">
                <p class="font-medium mb-1 flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Terdapat <?= count($errors) ?> kesalahan:
                </p>
                <ul class="list-disc list-inside space-y-0.5 pl-1">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate>
                <div class="space-y-4">
                    <!-- Nama -->
                    <div>
                        <label for="nama" class="block text-sm font-medium text-slate-300 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required autocomplete="name"
                            value="<?= htmlspecialchars($old['nama'] ?? '') ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="Nama Lengkap Anda">
                    </div>
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="nama@email.com">
                    </div>
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Password <span class="text-slate-500 font-normal">(min. 6 karakter)</span>
                        </label>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="••••••••">
                    </div>
                    <!-- Konfirmasi Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-slate-300 mb-1.5">Konfirmasi Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" id="btn-register"
                    class="mt-6 w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Buat Akun
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Sudah punya akun?
                <a href="login.php" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors ml-1">Masuk di sini</a>
            </p>
        </div>

        <p class="text-center text-xs text-slate-600 mt-6">© <?= date('Y') ?> Xpense Tracker &mdash; EAS Pemrograman Web</p>
    </div>
</body>
</html>
