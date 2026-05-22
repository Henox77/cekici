<?php

require_once 'config/db.php';
require_once 'config/security.php';

$code = isset($_GET['code']) ? strtoupper(clean_input($_GET['code'])) : '';
$appointment = null;
$error_message = '';

function mask_name($name) {
    $parts = explode(' ', $name);
    $masked = [];
    foreach ($parts as $part) {
        $len = mb_strlen($part, 'UTF-8');
        if ($len > 2) {
            $masked[] = mb_substr($part, 0, 2, 'UTF-8') . str_repeat('*', $len - 2);
        } else {
            $masked[] = $part . '*';
        }
    }
    return implode(' ', $masked);
}

function mask_phone($phone) {
    $len = strlen($phone);
    if ($len > 7) {
        return substr($phone, 0, 4) . ' *** ' . substr($phone, -4);
    }
    return '*** ***';
}

if (!empty($code)) {
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE appointment_code = ?");
        $stmt->execute([$code]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            $error_message = "Girdiğiniz '$code' koduyla eşleşen bir çekici randevusu bulunamadı. Lütfen kodunuzu kontrol edin.";
        }
    } catch (PDOException $e) {
        error_log("Query error in sorgula.php: " . $e->getMessage());
        $error_message = "Sorgulama yapılırken teknik bir sorun oluştu.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Durumu Sorgula - Yol Destek</title>
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
    
    <style>
        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            background-image: 
                radial-gradient(at 0% 0%, rgba(249, 115, 22, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .input-dark {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            transition: all 0.3s ease;
        }
        .input-dark:focus {
            border-color: #f97316;
            box-shadow: 0 0 10px rgba(249, 115, 22, 0.25);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between overflow-x-hidden">

    <!-- Header Navbar -->
    <header class="w-full py-5 px-6 lg:px-16 flex justify-between items-center z-10 glass-panel border-b border-white/5 sticky top-0">
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-neonOrange to-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20 transform group-hover:rotate-12 transition-transform duration-300">
                <i class="fa-solid fa-truck-pickup text-white text-lg"></i>
            </div>
            <div>
                <span class="font-extrabold text-xl tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-200 to-orange-400">YOL</span>
                <span class="font-bold text-xl text-neonOrange">DESTEK</span>
            </div>
        </a>
        
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
            <a href="index.php" class="hover:text-neonOrange transition-colors pb-1">Randevu Al</a>
            <a href="sorgula.php" class="hover:text-neonOrange transition-colors text-white border-b-2 border-neonOrange pb-1">Randevu Sorgula</a>
            <a href="admin/index.php" class="hover:text-neonOrange transition-colors pb-1">Yönetici Paneli</a>
        </nav>

        <div>
            <a href="index.php" class="px-5 py-2.5 rounded-full bg-slate-800/80 hover:bg-neonOrange hover:text-white border border-white/10 hover:border-neonOrange transition-all duration-300 flex items-center gap-2 text-sm font-semibold tracking-wide shadow-md">
                <i class="fa-solid fa-arrow-left"></i> Randevu Al
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col items-center py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Page Title -->
        <div class="text-center max-w-xl mx-auto mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-3">Randevu Durumu Takip Et</h1>
            <p class="text-slate-400 text-sm">
                Rezervasyon sırasında size verilen takip kodunu (Örn: REZ-XXXXXX) yazarak çekicinizin yola çıkıp çıkmadığını veya işlem durumunu anlık inceleyin.
            </p>
        </div>

        <!-- Search Form Card -->
        <div class="w-full max-w-2xl glass-panel rounded-3xl p-6 sm:p-8 shadow-2xl mb-8">
            <form action="sorgula.php" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-neonOrange"></i>
                    </span>
                    <input type="text" name="code" value="<?php echo htmlspecialchars($code); ?>" required placeholder="Randevu Takip Kodu (Örn: REZ-A1B2C3)" class="w-full pl-11 pr-4 py-3.5 rounded-xl input-dark text-sm tracking-wider uppercase font-semibold">
                </div>
                <button type="submit" class="px-8 py-3.5 bg-gradient-to-r from-neonOrange to-orange-500 hover:shadow-lg hover:shadow-orange-500/20 text-white text-sm font-bold rounded-xl transition-all duration-300 whitespace-nowrap">
                    Sorgula <i class="fa-solid fa-angle-right ml-1"></i>
                </button>
            </form>
        </div>

        <!-- Result Box -->
        <?php if (!empty($code)): ?>
            
            <?php if ($appointment): ?>
                
                <!-- Appointment details and Timeline -->
                <div class="w-full max-w-2xl space-y-6">
                    
                    <!-- Glassmorphism Card: Timeline -->
                    <div class="glass-panel rounded-3xl p-6 sm:p-8 shadow-xl">
                        <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-road-circle-check text-neonOrange"></i> Hizmet Takip Çizelgesi
                        </h3>

                        <?php
                            $status = $appointment['status'];
                            $step1_active = true;
                            $step2_active = ($status === 'on_way' || $status === 'completed');
                            $step3_active = ($status === 'completed');
                            $is_canceled = ($status === 'canceled');
                        ?>

                        <?php if ($is_canceled): ?>
                            <div class="mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 px-4 py-3 text-red-300 text-sm font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Bu randevu iptal edilmiştir.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Timeline Layout -->
                        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-8 md:gap-4 py-4 px-2">
                            <!-- Horizontal connectors for medium screens -->
                            <div class="absolute top-[28px] left-[40px] right-[40px] h-[3px] bg-slate-800 hidden md:block z-0"></div>
                            <div class="absolute top-[28px] left-[40px] h-[3px] bg-gradient-to-r from-neonOrange to-neonGreen hidden md:block z-0 transition-all duration-500" style="width: <?php echo $status === 'pending' ? '0%' : ($status === 'on_way' ? '50%' : '100%'); ?>;"></div>

                            <!-- Step 1: Pending (Beklemede) -->
                            <div class="flex md:flex-col items-center gap-4 md:gap-3 z-10 w-full md:w-1/3 text-left md:text-center relative">
                                <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-xl font-bold transition-all duration-500 <?php echo $step1_active ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30' : 'bg-slate-800 text-slate-500'; ?>">
                                    <i class="fa-solid fa-hourglass-half <?php echo $status === 'pending' ? 'animate-spin' : ''; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold <?php echo $step1_active ? 'text-orange-400' : 'text-slate-500'; ?>">Talep Alındı</h4>
                                    <p class="text-xs text-slate-400 mt-1">Müşteri temsilcisi incelemesinde.</p>
                                </div>
                            </div>

                            <!-- Step 2: On the Way (Yolda) -->
                            <div class="flex md:flex-col items-center gap-4 md:gap-3 z-10 w-full md:w-1/3 text-left md:text-center relative">
                                <!-- Vertical indicator connector for mobile screens -->
                                <div class="absolute -top-[32px] left-[26px] w-[3px] h-[32px] md:hidden z-0 <?php echo $step2_active ? 'bg-orange-500' : 'bg-slate-800'; ?>"></div>
                                
                                <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-xl font-bold transition-all duration-500 <?php echo $step2_active ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'bg-slate-800 text-slate-500'; ?>">
                                    <i class="fa-solid fa-truck-moving <?php echo $status === 'on_way' ? 'animate-bounce' : ''; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold <?php echo $step2_active ? 'text-amber-400' : 'text-slate-500'; ?>">Çekici Yolda</h4>
                                    <p class="text-xs text-slate-400 mt-1">Çekici aracı konuma geliyor.</p>
                                </div>
                            </div>

                            <!-- Step 3: Completed (Tamamlandı) -->
                            <div class="flex md:flex-col items-center gap-4 md:gap-3 z-10 w-full md:w-1/3 text-left md:text-center relative">
                                <!-- Vertical indicator connector for mobile screens -->
                                <div class="absolute -top-[32px] left-[26px] w-[3px] h-[32px] md:hidden z-0 <?php echo $step3_active ? 'bg-neonGreen' : 'bg-slate-800'; ?>"></div>

                                <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-xl font-bold transition-all duration-500 <?php echo $step3_active ? 'bg-neonGreen text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-800 text-slate-500'; ?>">
                                    <i class="fa-solid fa-flag-checkered"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold <?php echo $step3_active ? 'text-neonGreen' : 'text-slate-500'; ?>">Hizmet Tamamlandı</h4>
                                    <p class="text-xs text-slate-400 mt-1">Araç hedefine güvenle ulaştırıldı.</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Glassmorphism Card: Details -->
                    <div class="glass-panel rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
                        <div class="flex justify-between items-center border-b border-white/5 pb-4">
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <i class="fa-solid fa-file-invoice text-neonOrange"></i> Detay Bilgiler
                            </h3>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full border bg-slate-900/80 <?php 
                                if($status === 'pending') echo 'text-orange-400 border-orange-500/20';
                                elseif($status === 'on_way') echo 'text-amber-400 border-amber-500/20';
                                    elseif($status === 'canceled') echo 'text-red-400 border-red-500/20';
                                    else echo 'text-neonGreen border-neonGreen/20';
                            ?>">
                                <?php 
                                    if($status === 'pending') echo 'Beklemede';
                                    elseif($status === 'on_way') echo 'Çekici Yolda';
                                    elseif($status === 'canceled') echo 'İptal Edildi';
                                    else echo 'Tamamlandı';
                                ?>
                            </span>
                        </div>

                        <!-- Payment info removed -->

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                            
                            <!-- Tracking Code -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Takip Kodu</span>
                                <span class="font-semibold text-white tracking-wide"><?php echo $appointment['appointment_code']; ?></span>
                            </div>

                            <!-- Plate -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Araç Plakası</span>
                                <span class="font-semibold text-white bg-slate-800 px-2 py-0.5 rounded text-xs tracking-wider border border-white/5 inline-block uppercase"><?php echo $appointment['plate']; ?></span>
                            </div>

                            <!-- Brand & Model -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Araç Marka / Model</span>
                                <span class="font-semibold text-white"><?php echo $appointment['brand_model']; ?></span>
                            </div>

                            <!-- Issue Type -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Arıza Nedeni</span>
                                <span class="font-semibold text-slate-300"><?php echo $appointment['issue_type']; ?></span>
                            </div>

                            <!-- Date / Time -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Planlanan Rezervasyon Zamanı</span>
                                <span class="font-semibold text-white"><i class="fa-regular fa-calendar-days text-neonOrange mr-1"></i> <?php echo date('d.m.Y', strtotime($appointment['appointment_date'])) . ' - ' . date('H:i', strtotime($appointment['appointment_time'])); ?></span>
                            </div>

                            <!-- Client Name -->
                            <div class="border-b border-white/5 pb-2">
                                <span class="text-slate-400 text-xs block mb-1">Randevu Sahibi</span>
                                <span class="font-semibold text-white"><?php echo mask_name($appointment['fullname']); ?></span>
                            </div>

                            <!-- Phone -->
                            <div class="border-b border-white/5 pb-2 sm:col-span-2">
                                <span class="text-slate-400 text-xs block mb-1">Telefon Numarası</span>
                                <span class="font-semibold text-white"><?php echo mask_phone($appointment['phone']); ?></span>
                            </div>

                            <!-- Pickup Location -->
                            <div class="pb-2 sm:col-span-2">
                                <span class="text-slate-400 text-xs block mb-1"><i class="fa-solid fa-map-pin text-orange-500 mr-1"></i> Alınacağı Yer</span>
                                <span class="text-slate-300 block text-xs bg-slate-900/60 p-3 rounded-xl border border-white/5 mt-1"><?php echo $appointment['pickup_location']; ?></span>
                            </div>

                            <!-- Dropoff Location -->
                            <div class="pb-2 sm:col-span-2">
                                <span class="text-slate-400 text-xs block mb-1"><i class="fa-solid fa-flag-checkered text-neonGreen mr-1"></i> Bırakılacağı Yer</span>
                                <span class="text-slate-300 block text-xs bg-slate-900/60 p-3 rounded-xl border border-white/5 mt-1"><?php echo $appointment['dropoff_location']; ?></span>
                            </div>

                        </div>
                    </div>

                </div>

            <?php else: ?>
                <!-- Error Box -->
                <div class="w-full max-w-2xl p-6 rounded-3xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-start gap-4 shadow-xl">
                    <i class="fa-solid fa-circle-exclamation text-2xl mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-white text-base">Hata: Randevu Bulunamadı</h4>
                        <p class="text-sm text-slate-300 mt-1"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="w-full py-8 text-center border-t border-white/5 bg-slate-950/80 text-xs text-slate-500 mt-12">
        <p>&copy; <?php echo date('Y'); ?> Yol Destek. Tüm hakları saklıdır. Henox</p>
    </footer>

</body>
</html>
