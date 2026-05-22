<?php

require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/notifications.php';

check_auth();

function read_tail_lines($filePath, $limit = 5) {
    if (!file_exists($filePath)) {
        return [];
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return [];
    }

    return array_slice($lines, -$limit);
}

function resolve_report_range($range, $startInput, $endInput) {
    $today = new DateTimeImmutable('today');

    switch ($range) {
        case 'today':
            return [$today, $today, 'Bugün'];
        case 'month':
            return [$today->modify('first day of this month'), $today, 'Bu Ay'];
        case 'custom':
            $start = !empty($startInput) ? new DateTimeImmutable($startInput) : $today->modify('-6 days');
            $end = !empty($endInput) ? new DateTimeImmutable($endInput) : $today;

            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            return [$start, $end, 'Özel Tarih'];
        default:
            return [$today->modify('-6 days'), $today, 'Son 7 Gün'];
    }
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];

try {
    $price_stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tow_base_price'");
    $price_stmt->execute();
    $base_price = (float) ($price_stmt->fetchColumn() ?: 500);
} catch (PDOException $e) {
    $base_price = 500;
}

$range = isset($_GET['range']) ? clean_input($_GET['range']) : '7d';
$customStartInput = isset($_GET['start_date']) ? clean_input($_GET['start_date']) : '';
$customEndInput = isset($_GET['end_date']) ? clean_input($_GET['end_date']) : '';
[$rangeStart, $rangeEnd, $rangeLabel] = resolve_report_range($range, $customStartInput, $customEndInput);
$rangeStartSql = $rangeStart->format('Y-m-d');
$rangeEndSql = $rangeEnd->format('Y-m-d');

$total_count = $pending_count = $on_way_count = $completed_count = $canceled_count = 0;
$total_earnings = 0;
$chart_labels = [];
$chart_revenue_data = [];
$chart_completed_data = [];
$report_appointments = [];
$notification_logs = [];

try {
    $summaryStmt = $pdo->prepare("SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'on_way' THEN 1 ELSE 0 END) AS on_way_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) AS canceled_count,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN COALESCE(price, ?) ELSE 0 END), 0) AS total_earnings
        FROM appointments
        WHERE DATE(created_at) BETWEEN ? AND ?");
    $summaryStmt->execute([$base_price, $rangeStartSql, $rangeEndSql]);
    $summary = $summaryStmt->fetch();

    if ($summary) {
        $total_count = (int) $summary['total_count'];
        $pending_count = (int) $summary['pending_count'];
        $on_way_count = (int) $summary['on_way_count'];
        $completed_count = (int) $summary['completed_count'];
        $canceled_count = (int) $summary['canceled_count'];
        $total_earnings = (float) $summary['total_earnings'];
    }

    
    if ($total_count === 0) {
        try {
            $allStmt = $pdo->query("SELECT
                COUNT(*) AS total_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = 'on_way' THEN 1 ELSE 0 END) AS on_way_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) AS canceled_count,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) AS total_earnings
                FROM appointments");
            $all = $allStmt->fetch();
            if ($all) {
                $total_count = (int) $all['total_count'];
                $pending_count = (int) $all['pending_count'];
                $on_way_count = (int) $all['on_way_count'];
                $completed_count = (int) $all['completed_count'];
                $canceled_count = (int) $all['canceled_count'];
                $total_earnings = (float) $all['total_earnings'];
            }
        } catch (PDOException $e) {
            
        }
    }

    $dayCursor = $rangeStart;
    while ($dayCursor <= $rangeEnd) {
        $currentDate = $dayCursor->format('Y-m-d');
        $chart_labels[] = $dayCursor->format('d M');

        $dailyStmt = $pdo->prepare("SELECT
            COALESCE(SUM(CASE WHEN status = 'completed' THEN COALESCE(price, ?) ELSE 0 END), 0) AS daily_revenue,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count
            FROM appointments
            WHERE DATE(created_at) = ?");
        $dailyStmt->execute([$base_price, $currentDate]);
        $daily = $dailyStmt->fetch();

        $chart_revenue_data[] = (float) ($daily['daily_revenue'] ?? 0);
        $chart_completed_data[] = (int) ($daily['completed_count'] ?? 0);

        $dayCursor = $dayCursor->modify('+1 day');
    }

    $appointmentsStmt = $pdo->prepare("SELECT appointment_code, fullname, phone, status, price, distance_km, appointment_date, appointment_time, created_at
        FROM appointments
        WHERE DATE(created_at) BETWEEN ? AND ?
        ORDER BY created_at DESC
        LIMIT 20");
    $appointmentsStmt->execute([$rangeStartSql, $rangeEndSql]);
    $report_appointments = $appointmentsStmt->fetchAll();
    
    if (empty($report_appointments)) {
        try {
            $fallbackStmt = $pdo->query("SELECT appointment_code, fullname, phone, status, price, distance_km, appointment_date, appointment_time, created_at FROM appointments ORDER BY created_at DESC LIMIT 20");
            $report_appointments = $fallbackStmt->fetchAll();
        } catch (PDOException $e) {
            
        }
    }
} catch (PDOException $e) {
    error_log('Dashboard query error: ' . $e->getMessage());
}

$smsLogs = read_tail_lines(__DIR__ . '/../logs/sms_logs.txt', 5);
$whatsAppLogs = read_tail_lines(__DIR__ . '/../logs/whatsapp_logs.txt', 5);
$notification_logs = array_merge($smsLogs, $whatsAppLogs);
usort($notification_logs, function ($left, $right) {
    $leftTime = substr($left, 1, 19);
    $rightTime = substr($right, 1, 19);
    return strcmp($rightTime, $leftTime);
});
$notification_logs = array_slice($notification_logs, 0, 5);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#0f172a',
                        darkCard: 'rgba(30, 41, 59, 0.7)',
                        neonOrange: '#f97316',
                        neonGreen: '#10b981',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Export Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(249, 115, 22, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.04) 0px, transparent 50%);
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.35);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .sidebar-item-active {
            background: rgba(249, 115, 22, 0.15);
            border-left: 4px solid #f97316;
            color: #f97316;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">

    <!-- Mobile Header -->
    <header class="md:hidden flex justify-between items-center px-6 py-4 glass-panel border-b border-white/5 w-full z-20">
        <a href="dashboard.php" class="flex items-center gap-2">
            <i class="fa-solid fa-truck-pickup text-neonOrange text-xl"></i>
            <span class="font-extrabold text-sm tracking-wider">YOL DESTEK</span>
        </a>
        <button id="mobile-menu-toggle" class="text-white hover:text-neonOrange text-xl focus:outline-none">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- Sidebar Navigation -->
    <aside id="sidebar-container" class="w-full md:w-64 bg-slate-950/90 border-r border-white/5 flex flex-col justify-between p-6 transition-all duration-300 md:translate-x-0 hidden md:flex fixed md:static inset-y-0 left-0 z-30">
        <div class="space-y-8">
            <!-- Brand -->
            <div class="hidden md:flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-neonOrange text-white flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fa-solid fa-truck-pickup text-sm"></i>
                </div>
                <div>
                    <span class="font-extrabold text-sm tracking-wider">YOL DESTEK</span>
                </div>
            </div>

            <!-- Profile Info Mini-Card -->
            <div class="p-4 rounded-2xl bg-slate-900/60 border border-white/5 flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-neonOrange to-orange-500 flex items-center justify-center text-white font-bold text-sm">
                    <?php echo strtoupper(substr($username, 0, 2)); ?>
                </div>
                <div class="overflow-hidden">
                    <h5 class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($username); ?></h5>
                    <span class="text-[10px] text-slate-400 capitalize block"><?php echo htmlspecialchars($role); ?> Hesabı</span>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="space-y-2">
                <a href="dashboard.php" class="sidebar-item-active flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-chart-line text-base"></i> Genel Durum
                </a>
                <a href="randevular.php" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-900/40 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-calendar-check text-base"></i> Rezervasyonlar
                </a>
                <a href="../index.php" target="_blank" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-900/40 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-globe text-base"></i> Rezervasyon Sayfası
                </a>
            </nav>
        </div>

        <!-- Logout -->
        <div class="pt-6 border-t border-white/5 mt-8">
            <a href="index.php?logout=true" onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?')" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-xl text-sm font-semibold transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket text-base"></i> Güvenli Çıkış
            </a>
        </div>
    </aside>

    <!-- Logout Action Handler -->
    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    ?>

    <!-- Main Workspace -->
    <main class="flex-grow p-6 lg:p-10 overflow-y-auto w-full md:max-w-[calc(100%-16rem)]">
        
        <!-- Dashboard Top Header Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-6 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Yönetim Paneli</h1>
                <p class="text-xs text-slate-400 mt-1">Sistem üzerindeki genel veri durumunu buradan takip edebilirsiniz.</p>
            </div>
            
            <!-- Glow Badge showing Role -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 font-medium">Bağlantı Rolü:</span>
                <span class="px-3.5 py-1 text-xs font-bold rounded-full bg-orange-500/10 text-neonOrange border border-orange-500/30 flex items-center gap-1.5 shadow-lg shadow-orange-500/5">
                    <span class="h-2 w-2 rounded-full bg-neonOrange animate-pulse"></span> Tam Yönetici
                </span>
            </div>
        </div>

        <!-- 4 Stat Widget Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Metric 1: Total -->
            <div class="glass-panel p-6 rounded-2xl shadow-lg relative overflow-hidden group">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toplam Talep</span>
                        <h3 class="text-3xl font-extrabold text-white mt-2"><?php echo $total_count; ?></h3>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-neonOrange flex items-center justify-center text-xl">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 mt-4">Sistemdeki tüm zamanların randevuları</div>
            </div>

            <!-- Metric 2: Pending -->
            <div class="glass-panel p-6 rounded-2xl shadow-lg relative overflow-hidden group">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Bekleyenler</span>
                        <h3 class="text-3xl font-extrabold text-orange-400 mt-2"><?php echo $pending_count; ?></h3>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 flex items-center justify-center text-xl">
                        <i class="fa-regular fa-clock animate-pulse"></i>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 mt-4">Onay bekleyen veya sırada olan randevular</div>
            </div>

            <!-- Metric 3: On Way -->
            <div class="glass-panel p-6 rounded-2xl shadow-lg relative overflow-hidden group">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Aktif (Yolda)</span>
                        <h3 class="text-3xl font-extrabold text-amber-400 mt-2"><?php echo $on_way_count; ?></h3>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-truck-moving"></i>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 mt-4">Çekici aracı konuma giden randevular</div>
            </div>

            <!-- Metric 4: Revenue -->
            <div class="glass-panel p-6 rounded-2xl shadow-lg relative overflow-hidden group">
                <div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Toplam Kazanç</span>
                            <h3 class="text-3xl font-extrabold text-neonGreen mt-2"><?php echo number_format($total_earnings, 0, ',', '.'); ?> <span class="text-xs">TL</span></h3>
                        </div>
                        <div class="h-12 w-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-neonGreen flex items-center justify-center text-xl">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-500 mt-4">Tamamlanan randevular bazlı tahmini kazanç</div>
                </div>
            </div>

        </div>

        <!-- Report Controls -->
        <div class="space-y-5 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-white">Finansal ve Operasyonel Rapor</h3>
                    <p class="text-[11px] text-slate-400">Seçili tarih aralığı: <?php echo htmlspecialchars($rangeLabel); ?> (<?php echo htmlspecialchars($rangeStartSql); ?> - <?php echo htmlspecialchars($rangeEndSql); ?>)</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="?range=today" class="px-4 py-2 rounded-full border text-xs font-bold transition-all duration-200 <?php echo $range === 'today' ? 'bg-neonOrange text-white border-neonOrange' : 'bg-slate-900/70 text-slate-300 border-white/5 hover:border-neonOrange/40 hover:text-white'; ?>">Bugün</a>
                    <a href="?range=7d" class="px-4 py-2 rounded-full border text-xs font-bold transition-all duration-200 <?php echo $range === '7d' ? 'bg-neonOrange text-white border-neonOrange' : 'bg-slate-900/70 text-slate-300 border-white/5 hover:border-neonOrange/40 hover:text-white'; ?>">Son 7 Gün</a>
                    <a href="?range=month" class="px-4 py-2 rounded-full border text-xs font-bold transition-all duration-200 <?php echo $range === 'month' ? 'bg-neonOrange text-white border-neonOrange' : 'bg-slate-900/70 text-slate-300 border-white/5 hover:border-neonOrange/40 hover:text-white'; ?>">Bu Ay</a>
                </div>
            </div>

            <form method="get" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 glass-panel rounded-2xl p-4 border border-white/5">
                <input type="hidden" name="range" value="custom">
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.3em] text-slate-400 mb-1" for="start_date">Başlangıç Tarihi</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($customStartInput); ?>" class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-2.5 text-sm text-white focus:border-neonOrange outline-none">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-[0.3em] text-slate-400 mb-1" for="end_date">Bitiş Tarihi</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($customEndInput); ?>" class="w-full rounded-xl bg-slate-900/80 border border-white/10 px-4 py-2.5 text-sm text-white focus:border-neonOrange outline-none">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-neonOrange to-orange-500 text-white font-bold text-sm hover:shadow-lg hover:shadow-orange-500/20 transition-all duration-300">Özel Aralığı Uygula</button>
            </form>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[1.55fr_0.85fr] gap-6">
            <div class="space-y-6">
                <div class="w-full glass-panel rounded-2xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-base font-bold text-white">Aylık Ciro ve Tamamlanan Randevu Sayısı</h3>
                            <p class="text-[11px] text-slate-400">Seçili aralıkta günlük bazlı çift çizgi grafiği</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-400 bg-slate-900/60 px-3 py-1 rounded-lg border border-white/5"><?php echo htmlspecialchars($rangeLabel); ?></span>
                    </div>
                    <div class="relative w-full h-[320px]">
                        <canvas id="reservationsChart"></canvas>
                    </div>

                    <!-- Demo overlay removed -->
                </div>

                <div class="w-full glass-panel rounded-2xl p-6 sm:p-8 shadow-xl">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                        <div>
                            <h3 class="text-base font-bold text-white">Randevu Listesi</h3>
                            <p class="text-[11px] text-slate-400">Seçili aralıkta son 20 kayıt</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="exportExcel()" class="px-4 py-2 rounded-xl bg-emerald-500/10 text-neonGreen border border-emerald-500/20 hover:bg-emerald-500/20 text-xs font-bold transition-colors">Excel'e Aktar</button>
                            <button type="button" onclick="exportPdf()" class="px-4 py-2 rounded-xl bg-blue-500/10 text-blue-300 border border-blue-500/20 hover:bg-blue-500/20 text-xs font-bold transition-colors">PDF Olarak İndir</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="report-table" class="w-full border-collapse text-left text-xs text-slate-300">
                            <thead class="bg-slate-950/40 text-slate-400 font-bold border-b border-white/5">
                                <tr>
                                    <th class="p-3 uppercase tracking-wider">Kod</th>
                                    <th class="p-3 uppercase tracking-wider">Müşteri</th>
                                    <th class="p-3 uppercase tracking-wider">Tarih</th>
                                    <th class="p-3 uppercase tracking-wider">Tutar</th>
                                    <th class="p-3 uppercase tracking-wider">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (!empty($report_appointments)): ?>
                                    <?php foreach ($report_appointments as $appointment): ?>
                                        <?php
                                            $statusLabels = [
                                                'pending' => 'Beklemede',
                                                'on_way' => 'Yolda',
                                                'completed' => 'Tamamlandı',
                                                'canceled' => 'İptal',
                                            ];
                                            $statusText = $statusLabels[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                        <tr class="hover:bg-slate-800/20 transition-colors">
                                            <td class="p-3 font-bold text-neonOrange whitespace-nowrap"><?php echo htmlspecialchars($appointment['appointment_code']); ?></td>
                                            <td class="p-3">
                                                <div class="font-semibold text-white"><?php echo htmlspecialchars($appointment['fullname']); ?></div>
                                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($appointment['phone']); ?></div>
                                            </td>
                                            <td class="p-3 whitespace-nowrap">
                                                <div class="font-medium text-slate-200"><?php echo date('d.m.Y', strtotime($appointment['appointment_date'])); ?></div>
                                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></div>
                                            </td>
                                            <td class="p-3 whitespace-nowrap font-bold text-neonGreen"><?php echo number_format((float)($appointment['price'] ?? 0), 2, ',', '.'); ?> TL</td>
                                            <td class="p-3 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo $appointment['status'] === 'completed' ? 'bg-emerald-500/10 border-emerald-500/20 text-neonGreen' : ($appointment['status'] === 'on_way' ? 'bg-amber-500/10 border-amber-500/20 text-amber-400' : ($appointment['status'] === 'canceled' ? 'bg-red-500/10 border-red-500/20 text-red-400' : 'bg-orange-500/10 border-orange-500/20 text-orange-400')); ?>">
                                                    <?php echo htmlspecialchars($statusText); ?>
                                                </span>
                                            </td>
                                            
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-500 text-sm font-medium">
                                            <i class="fa-solid fa-folder-open text-3xl mb-3 block"></i>
                                            Kayıtlı randevu kaydı bulunmamaktadır.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass-panel rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-white">Bildirim Kuyruğu</h3>
                            <p class="text-[11px] text-slate-400">SMS ve WhatsApp simülasyon logları</p>
                        </div>
                        <div class="h-11 w-11 rounded-2xl bg-neonOrange/10 text-neonOrange flex items-center justify-center">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                    </div>

                    <div class="space-y-3 max-h-[260px] overflow-auto pr-1">
                        <?php if (!empty($notification_logs)): ?>
                            <?php foreach ($notification_logs as $logLine): ?>
                                <div class="rounded-2xl bg-slate-900/60 border border-white/5 p-3 text-[11px] text-slate-300">
                                    <?php echo htmlspecialchars($logLine); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="rounded-2xl bg-slate-900/60 border border-white/5 p-3 text-[11px] text-slate-400">Henüz bildirim logu yok.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="glass-panel rounded-2xl p-6 shadow-xl">
                    <h3 class="text-base font-bold text-white mb-4">Rapor Özeti</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between"><span class="text-slate-400">Toplam Randevu</span><span class="font-bold text-white"><?php echo $total_count; ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-slate-400">Tamamlanan</span><span class="font-bold text-neonGreen"><?php echo $completed_count; ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-slate-400">İptal</span><span class="font-bold text-red-400"><?php echo $canceled_count; ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-slate-400">Toplam Ciro</span><span class="font-black text-neonGreen"><?php echo number_format($total_earnings, 0, ',', '.'); ?> TL</span></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ChartJS Configurations -->
    <script>
        const chartCanvas = document.getElementById('reservationsChart');

        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            const revenueGradient = ctx.createLinearGradient(0, 0, 0, 320);
            revenueGradient.addColorStop(0, 'rgba(249, 115, 22, 0.42)');
            revenueGradient.addColorStop(1, 'rgba(249, 115, 22, 0.02)');

            const countGradient = ctx.createLinearGradient(0, 0, 0, 320);
            countGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            countGradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Aylık Ciro',
                            data: <?php echo json_encode($chart_revenue_data); ?>,
                            borderColor: '#f97316',
                            backgroundColor: revenueGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#f97316',
                            pointBorderColor: '#ffffff',
                            yAxisID: 'yRevenue'
                        },
                        {
                            label: 'Tamamlanan Randevu Sayısı',
                            data: <?php echo json_encode($chart_completed_data); ?>,
                            borderColor: '#10b981',
                            backgroundColor: countGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            yAxisID: 'yCount'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#cbd5e1',
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: true
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.04)'
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        },
                        yRevenue: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.04)'
                            },
                            ticks: {
                                color: '#f97316',
                                callback: function(value) {
                                    return value.toLocaleString('tr-TR') + ' TL';
                                }
                            }
                        },
                        yCount: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                color: '#10b981',
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        function exportExcel() {
            const table = document.getElementById('report-table');
            if (!table || typeof XLSX === 'undefined') {
                return;
            }

            const workbook = XLSX.utils.book_new();
            const worksheet = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Randevular');
            XLSX.writeFile(workbook, 'randevu-listesi.xlsx');
        }

        function exportPdf() {
            const table = document.getElementById('report-table');
            if (!table || typeof window.jspdf === 'undefined') {
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
            const headers = ['Kod', 'Müşteri', 'Tarih', 'Tutar', 'Durum'];
            const rows = Array.from(table.querySelectorAll('tbody tr')).map(row => {
                return Array.from(row.querySelectorAll('td')).map(cell => cell.innerText.replace(/\s+/g, ' ').trim());
            });

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text('Randevu Listesi', 40, 40);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            doc.text('Filtre: <?php echo addslashes($rangeLabel); ?> | Tarih: <?php echo addslashes($rangeStartSql); ?> - <?php echo addslashes($rangeEndSql); ?>', 40, 58);

            let y = 88;
            const pageWidth = doc.internal.pageSize.getWidth();
            const colWidth = (pageWidth - 80) / headers.length;

            doc.setFont('helvetica', 'bold');
            headers.forEach((header, index) => {
                doc.text(header, 40 + (index * colWidth), y);
            });

            y += 16;
            doc.setDrawColor(80);

            rows.forEach(row => {
                if (y > 520) {
                    doc.addPage();
                    y = 40;
                }

                doc.setFont('helvetica', 'normal');
                row.slice(0, headers.length).forEach((cell, index) => {
                    const text = doc.splitTextToSize(cell, colWidth - 10);
                    doc.text(text, 40 + (index * colWidth), y);
                });
                y += 24;
            });

            doc.save('randevu-listesi.pdf');
        }

        const toggleBtn = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar-container');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
