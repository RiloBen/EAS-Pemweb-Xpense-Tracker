<?php
// =====================================================
// login.php — Halaman & Proses Login
// =====================================================
require_once 'config/database.php';
require_once 'config/auth.php';

redirectIfLoggedIn();

$error   = '';
$success = '';

// Flash message dari register
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = 'Akun berhasil dibuat! Silakan login untuk melanjutkan.';
}

// ---- Proses POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$emailOld = htmlspecialchars($_POST['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Xpense Tracker</title>
    <meta name="description" content="Masuk ke Xpense Tracker dan mulai pantau keuangan pribadi Anda secara real-time.">
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

    <!-- Radial background glow -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-4 py-10">

        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg shadow-indigo-500/30 mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Xpense Tracker</h1>
            <p class="text-slate-400 mt-1 text-sm">Catat. Pantau. Kendalikan keuanganmu.</p>
        </div>

        <!-- Card -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-6">Masuk ke Akun</h2>

            <?php if ($error): ?>
            <div class="mb-5 flex items-start gap-2.5 p-3.5 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm" role="alert">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-5 flex items-start gap-2.5 p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-sm" role="alert">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email"
                            value="<?= $emailOld ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="nama@email.com">
                    </div>
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                            class="w-full px-3.5 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" id="btn-login"
                    class="mt-6 w-full py-2.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Masuk
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Belum punya akun?
                <a href="register.php" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors ml-1">Daftar sekarang</a>
            </p>
        </div>

        <p class="text-center text-xs text-slate-600 mt-6">© <?= date('Y') ?> Xpense Tracker &mdash; EAS Pemrograman Web</p>
    </div>
</body>
</html>
