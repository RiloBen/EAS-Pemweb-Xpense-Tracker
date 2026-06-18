<?php
// =====================================================
// index.php — Dashboard Utama
// =====================================================
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

$userId = (int) $currentUser['id'];
$limit  = 10;

// ---- Sanitasi Input ----
$search = trim($_GET['search'] ?? '');
$page   = max(1, intval($_GET['page'] ?? 1));

// ---- Filter Bulan & Tahun (dari Flatpickr monthSelect) ----
// Format GET: bulan_filter = "2026-06"
$filterBulanParam = trim($_GET['bulan_filter'] ?? '');
if (!empty($filterBulanParam) && preg_match('/^\d{4}-\d{2}$/', $filterBulanParam)) {
    [$tahun, $bulan] = explode('-', $filterBulanParam);
    $tahun = (int) $tahun;
    $bulan = (int) $bulan;
} else {
    $bulan            = (int) date('m');
    $tahun            = (int) date('Y');
    $filterBulanParam = date('Y-m'); // default: bulan ini
}

// ---- Kartu Ringkasan Bulan Berjalan ----
$stmtSum = mysqli_prepare($conn,
    "SELECT
        COALESCE(SUM(CASE WHEN jenis='pendapatan'  THEN jumlah ELSE 0 END), 0) AS total_pendapatan,
        COALESCE(SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_pengeluaran
     FROM transaksi
     WHERE user_id = ? AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
mysqli_stmt_bind_param($stmtSum, 'iii', $userId, $bulan, $tahun);
mysqli_stmt_execute($stmtSum);
$sumRow          = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSum));
$totalPendapatan = (float) $sumRow['total_pendapatan'];
$totalPengeluaran = (float) $sumRow['total_pengeluaran'];
$sisaSaldo       = $totalPendapatan - $totalPengeluaran;
mysqli_stmt_close($stmtSum);

// ---- Hitung Total Data (dengan filter search + bulan/tahun) ----
$searchParam = '%' . $search . '%';
$stmtCount   = mysqli_prepare($conn,
    "SELECT COUNT(*) AS total FROM transaksi
     WHERE user_id = ? AND keterangan LIKE ?
       AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
mysqli_stmt_bind_param($stmtCount, 'isii', $userId, $searchParam, $bulan, $tahun);
mysqli_stmt_execute($stmtCount);
$totalData = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
mysqli_stmt_close($stmtCount);

// ---- Kalkulasi Pagination ----
$totalHalaman = ($totalData > 0) ? (int) ceil($totalData / $limit) : 1;
$page         = min($page, $totalHalaman); // boundary check
$offset       = ($page - 1) * $limit;
$dataMulai    = ($totalData > 0) ? $offset + 1 : 0;
$dataSelesai  = min($offset + $limit, $totalData);

// ---- Ambil Data Transaksi (dengan filter bulan/tahun + search) ----
$stmtData = mysqli_prepare($conn,
    "SELECT * FROM transaksi
     WHERE user_id = ? AND keterangan LIKE ?
       AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
     ORDER BY tanggal DESC, created_at DESC
     LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmtData, 'isiiii', $userId, $searchParam, $bulan, $tahun, $limit, $offset);
mysqli_stmt_execute($stmtData);
$transaksiRows = mysqli_stmt_get_result($stmtData);

// ---- Helper ----
function rupiahFormat(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function buildUrl(array $params): string {
    return 'index.php?' . http_build_query($params);
}

$namaBulan = [
    '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April',
    '05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus',
    '09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
];

// Flash messages
$flashType = '';
$flashMsg  = '';
if (isset($_GET['added']))   { $flashType = 'success'; $flashMsg = 'Transaksi berhasil ditambahkan!'; }
if (isset($_GET['updated'])) { $flashType = 'success'; $flashMsg = 'Transaksi berhasil diperbarui!'; }
if (isset($_GET['deleted'])) { $flashType = 'info';    $flashMsg = 'Transaksi berhasil dihapus.'; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Xpense Tracker</title>
    <meta name="description" content="Dashboard Xpense Tracker: pantau total pendapatan, pengeluaran, dan saldo bulan ini secara real-time.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Flatpickr core -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr monthSelect plugin -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
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
            <!-- Brand -->
            <div class="flex items-center gap-2.5">
                <img src="assets/logo.svg" alt="Xpense Tracker" class="w-8 h-8 rounded-lg object-contain">
                <span class="font-bold text-white">Xpense Tracker</span>
            </div>
            <!-- Right side -->
            <div class="flex items-center gap-3">
                <a href="tambah.php" id="btn-tambah-nav"
                    class="hidden sm:flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah
                </a>
                <span class="text-sm text-slate-400 hidden md:block">
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

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        <!-- Flash Message -->
        <?php if ($flashMsg): ?>
        <?php $fc = ($flashType === 'success') ? 'emerald' : 'sky'; ?>
        <div class="flash-msg flex items-center gap-2.5 p-3.5 bg-<?= $fc ?>-500/10 border border-<?= $fc ?>-500/30 rounded-xl text-<?= $fc ?>-400 text-sm" role="alert">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <?= htmlspecialchars($flashMsg) ?>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Dashboard</h1>
                <p class="text-slate-400 text-sm mt-0.5">
                    Ringkasan keuangan bulan <span class="text-indigo-400 font-medium"><?= $namaBulan[sprintf('%02d', $bulan)] . ' ' . $tahun ?></span>
                </p>
            </div>
            <a href="tambah.php" id="btn-tambah-mobile"
                class="sm:hidden flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Pendapatan -->
            <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl p-5 shadow-lg">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-emerald-500/15 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-400 font-medium">Total Pendapatan</span>
                </div>
                <p class="text-2xl font-bold text-emerald-400"><?= rupiahFormat($totalPendapatan) ?></p>
            </div>
            <!-- Pengeluaran -->
            <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl p-5 shadow-lg">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-red-500/15 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-400 font-medium">Total Pengeluaran</span>
                </div>
                <p class="text-2xl font-bold text-red-400"><?= rupiahFormat($totalPengeluaran) ?></p>
            </div>
            <!-- Saldo -->
            <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl p-5 shadow-lg">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 <?= $sisaSaldo >= 0 ? 'bg-blue-500/15' : 'bg-amber-500/15' ?> rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 <?= $sisaSaldo >= 0 ? 'text-blue-400' : 'text-amber-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-400 font-medium">Sisa Saldo</span>
                </div>
                <p class="text-2xl font-bold <?= $sisaSaldo >= 0 ? 'text-blue-400' : 'text-amber-400' ?>">
                    <?= rupiahFormat(abs($sisaSaldo)) ?><?= $sisaSaldo < 0 ? ' <span class="text-base font-normal">(minus)</span>' : '' ?>
                </p>
            </div>
        </div>

        <!-- Search & Table Section -->
        <div class="bg-slate-900/80 border border-slate-700/60 rounded-2xl shadow-xl overflow-hidden">

            <!-- Search Bar + Filter Bulan -->
            <div class="p-4 sm:p-5 border-b border-slate-800">
                <form method="GET" action="index.php" class="flex flex-wrap gap-3">
                    <!-- Filter Bulan/Tahun (Flatpickr monthSelect) -->
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input type="text" id="filter-bulan" name="bulan_filter"
                            value="<?= htmlspecialchars($filterBulanParam) ?>"
                            readonly
                            class="pl-9 pr-4 py-2.5 w-44 bg-slate-800 border border-indigo-500/50 rounded-xl text-white text-sm focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all cursor-pointer"
                            placeholder="Pilih bulan...">
                    </div>
                    <!-- Search Keterangan -->
                    <div class="relative flex-1 min-w-[160px]">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input type="text" id="search" name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all"
                            placeholder="Cari keterangan...">
                    </div>
                    <button type="submit" id="btn-search"
                        class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors">
                        Cari
                    </button>
                    <?php if ($search || $filterBulanParam !== date('Y-m')): ?>
                    <a href="index.php" id="btn-reset-search"
                        class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium rounded-xl transition-colors"
                        title="Reset ke bulan ini">
                        Reset
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800 bg-slate-900/50">
                            <th class="text-left py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider w-10">#</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Jenis</th>
                            <th class="text-right py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Jumlah</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Keterangan</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Foto</th>
                            <th class="text-center py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($totalData === 0): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <?php
                                $namaBulanFull = [
                                    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
                                    5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
                                    9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                                ];
                                $labelBulan = ($namaBulanFull[$bulan] ?? $bulan) . ' ' . $tahun;
                                ?>
                                <?php if ($search): ?>
                                    <p class="font-medium">Tidak ada transaksi dengan kata kunci <span class="text-indigo-400">"<?= htmlspecialchars($search) ?>"</span> di bulan <span class="text-indigo-400"><?= $labelBulan ?></span></p>
                                <?php else: ?>
                                    <p class="font-medium">Tidak ada transaksi di bulan <span class="text-indigo-400"><?= $labelBulan ?></span></p>
                                    <p class="text-sm mt-1">Mulai dengan <a href="tambah.php" class="text-indigo-400 hover:underline">menambah transaksi</a> atau pilih bulan lain.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $no = $dataMulai; ?>
                        <?php while ($row = mysqli_fetch_assoc($transaksiRows)): ?>
                        <tr class="border-b border-slate-800/60 hover:bg-slate-800/30 transition-colors">
                            <td class="py-3.5 px-4 text-slate-500"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 text-slate-300 whitespace-nowrap">
                                <?= date('d M Y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($row['jenis'] === 'pendapatan'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-medium rounded-full border border-emerald-500/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 11l5-5m0 0l5 5"/>
                                    </svg>
                                    Pendapatan
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-500/10 text-red-400 text-xs font-medium rounded-full border border-red-500/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 13l-5 5m0 0l-5-5"/>
                                    </svg>
                                    Pengeluaran
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold <?= $row['jenis'] === 'pendapatan' ? 'text-emerald-400' : 'text-red-400' ?> whitespace-nowrap">
                                <?= $row['jenis'] === 'pengeluaran' ? '- ' : '+ ' ?><?= rupiahFormat((float) $row['jumlah']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-300 max-w-xs truncate">
                                <?= htmlspecialchars($row['keterangan']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <?php if ($row['foto_nota']): ?>
                                <a href="uploads/<?= htmlspecialchars($row['foto_nota']) ?>"
                                    target="_blank" title="Lihat foto nota"
                                    class="inline-flex items-center justify-center w-8 h-8 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <?php else: ?>
                                <span class="text-slate-700 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="edit.php?id=<?= $row['id'] ?>"
                                        class="inline-flex items-center justify-center w-8 h-8 bg-slate-700 hover:bg-indigo-600/80 text-slate-300 hover:text-white rounded-lg transition-colors"
                                        title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <a href="hapus.php?id=<?= $row['id'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus transaksi ini? Tindakan tidak dapat dibatalkan.')"
                                        class="inline-flex items-center justify-center w-8 h-8 bg-slate-700 hover:bg-red-600/80 text-slate-300 hover:text-white rounded-lg transition-colors"
                                        title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination & Status Info -->
            <?php if ($totalData > 0): ?>
            <div class="px-4 sm:px-5 py-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Status Info -->
                <p class="text-xs text-slate-500 order-2 sm:order-1">
                    Halaman <span class="text-slate-300 font-medium"><?= $page ?></span> dari
                    <span class="text-slate-300 font-medium"><?= $totalHalaman ?></span>
                    &nbsp;|&nbsp; Menampilkan
                    <span class="text-slate-300 font-medium"><?= $dataMulai ?></span> sampai
                    <span class="text-slate-300 font-medium"><?= $dataSelesai ?></span> dari
                    <span class="text-slate-300 font-medium"><?= $totalData ?></span> data
                </p>

                <!-- Pagination Buttons -->
                <div class="flex items-center gap-1.5 order-1 sm:order-2">
                    <!-- Previous -->
                    <?php if ($page > 1): ?>
                    <a href="<?= buildUrl(['bulan_filter' => $filterBulanParam, 'search' => $search, 'page' => $page - 1]) ?>"
                        id="btn-prev"
                        class="flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-medium rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Sebelumnya
                    </a>
                    <?php else: ?>
                    <span class="flex items-center gap-1 px-3 py-1.5 bg-slate-900 border border-slate-800 text-slate-600 text-xs font-medium rounded-lg cursor-not-allowed">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Sebelumnya
                    </span>
                    <?php endif; ?>

                    <!-- Page Numbers (tampilkan maks 5) -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage   = min($totalHalaman, $startPage + 4);
                    $startPage = max(1, $endPage - 4);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                    <a href="<?= buildUrl(['bulan_filter' => $filterBulanParam, 'search' => $search, 'page' => $p]) ?>"
                        id="btn-page-<?= $p ?>"
                        class="w-8 h-8 flex items-center justify-center text-xs font-medium rounded-lg transition-colors
                            <?= $p === $page
                                ? 'bg-indigo-600 text-white border border-indigo-500'
                                : 'bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300' ?>">
                        <?= $p ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $totalHalaman): ?>
                    <a href="<?= buildUrl(['bulan_filter' => $filterBulanParam, 'search' => $search, 'page' => $page + 1]) ?>"
                        id="btn-next"
                        class="flex items-center gap-1 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-medium rounded-lg transition-colors">
                        Berikutnya
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <?php else: ?>
                    <span class="flex items-center gap-1 px-3 py-1.5 bg-slate-900 border border-slate-800 text-slate-600 text-xs font-medium rounded-lg cursor-not-allowed">
                        Berikutnya
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="max-w-5xl mx-auto px-4 sm:px-6 py-6 text-center text-xs text-slate-700">
        © <?= date('Y') ?> Xpense Tracker &mdash; EAS Pemrograman Web
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
