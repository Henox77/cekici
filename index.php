<?php

require_once 'config/db.php';
require_once 'config/security.php';

$csrf_token = generate_csrf_token();

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'contact_phone'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $contact_phone = $setting ? $setting['setting_value'] : '+90 (555) 123 45 67';
} catch (Exception $e) {
    $contact_phone = '+90 (555) 123 45 67';
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'tow_base_price'");
    $stmt->execute();
    $base_price = (float) ($stmt->fetchColumn() ?: 500);
} catch (Exception $e) {
    $base_price = 500;
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'google_maps_api_key'");
    $stmt->execute();
    $google_maps_api_key = trim((string) ($stmt->fetchColumn() ?: ''));
} catch (Exception $e) {
    $google_maps_api_key = trim((string) getenv('GOOGLE_MAPS_API_KEY'));
}
?>
<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevulu Çekici Sistemi - Güvenilir Yol Yardım</title>
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
    <?php if (!empty($google_maps_api_key)): ?>
        <script>
            window.GOOGLE_MAPS_API_KEY = <?php echo json_encode($google_maps_api_key); ?>;
        </script>
    <?php endif; ?>
    
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
        .glass-panel-heavy {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(249, 115, 22, 0.2);
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
        .step-progress-line {
            background: rgba(255, 255, 255, 0.1);
        }
        .step-progress-line-active {
            background: linear-gradient(to right, #f97316, #10b981);
            transition: width 0.5s ease;
        }
        .pricing-summary-float {
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(249, 115, 22, 0.18);
        }
        .card-chip {
            background:
                linear-gradient(135deg, rgba(255,255,255,0.36), rgba(255,255,255,0.08)),
                linear-gradient(135deg, #f59e0b, #f97316 40%, #fb7185 100%);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.35), 0 14px 28px rgba(249, 115, 22, 0.24);
        }
        /* payment-preview-glow removed */
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316;
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
            <a href="index.php" class="hover:text-neonOrange transition-colors text-white border-b-2 border-neonOrange pb-1">Randevu Al</a>
            <a href="sorgula.php" class="hover:text-neonOrange transition-colors pb-1">Randevu Sorgula</a>
            <a href="admin/index.php" class="hover:text-neonOrange transition-colors pb-1">Yönetici Paneli</a>
        </nav>

        <div>
            <a href="tel:<?php echo $contact_phone; ?>" class="px-5 py-2.5 rounded-full bg-slate-800/80 hover:bg-neonOrange hover:text-white border border-white/10 hover:border-neonOrange transition-all duration-300 flex items-center gap-2 text-sm font-semibold tracking-wide shadow-md">
                <i class="fa-solid fa-phone text-neonGreen animate-pulse"></i>
                <span class="hidden sm:inline">Hemen Çekici Çağır:</span> <?php echo $contact_phone; ?>
            </a>
        </div>
    </header>

    <!-- Hero Section & Main Booking Container -->
    <main class="flex-grow flex flex-col items-center py-12 px-4 sm:px-6 lg:px-8 z-0">
        
        <!-- Hero Text -->
        <div class="text-center max-w-3xl mx-auto mb-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs font-semibold uppercase tracking-widest mb-4">
                <i class="fa-solid fa-circle-check text-[10px] text-neonGreen animate-ping"></i> 7/24 Aktif Çekici Rezervasyon Sistemi
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4">
                Yolda Kaldıysanız, <br class="sm:hidden"> Endişelenmeyin!
            </h1>
            <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto">
                Konumunuzu belirtin, arıza durumunuzu seçin ve randevulu çekici talebinizi anında oluşturun. Çekicimiz tam zamanında yanınızda olsun.
            </p>
        </div>

        <!-- Booking Box Container (Glassmorphic Card) -->
        <div class="w-full max-w-3xl glass-panel rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden">
            
            <!-- Step Indicators -->
            <div class="mb-10 relative">
                <!-- Bar background -->
                <div class="absolute top-1/2 left-0 right-0 h-1 step-progress-line -translate-y-1/2 rounded-full z-0"></div>
                <!-- Active bar -->
                <div id="step-progress-active" class="absolute top-1/2 left-0 h-1 step-progress-line-active -translate-y-1/2 rounded-full z-0" style="width: 0%;"></div>
                
                <!-- Steps numbers -->
                <div class="flex justify-between items-center relative z-10">
                    <!-- Step 1 Indicator -->
                    <div class="step-indicator-node flex flex-col items-center gap-2 group cursor-pointer" onclick="goToStep(1)">
                        <div id="step-node-1" class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-neonOrange text-white flex items-center justify-center font-bold text-base sm:text-lg shadow-lg shadow-orange-500/30 transition-all duration-300">
                            <i class="fa-solid fa-car"></i>
                        </div>
                        <span id="step-text-1" class="text-xs font-semibold text-neonOrange transition-colors hidden sm:block">Araç Bilgileri</span>
                    </div>

                    <!-- Step 2 Indicator -->
                    <div class="step-indicator-node flex flex-col items-center gap-2 group cursor-pointer" onclick="goToStep(2)">
                        <div id="step-node-2" class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-base sm:text-lg border border-white/5 transition-all duration-300">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <span id="step-text-2" class="text-xs font-semibold text-slate-400 transition-colors hidden sm:block">Konumlar</span>
                    </div>

                    <!-- Step 3 Indicator -->
                    <div class="step-indicator-node flex flex-col items-center gap-2 group cursor-pointer" onclick="goToStep(3)">
                        <div id="step-node-3" class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-base sm:text-lg border border-white/5 transition-all duration-300">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <span id="step-text-3" class="text-xs font-semibold text-slate-400 transition-colors hidden sm:block">Tarih & Saat</span>
                    </div>

                    <!-- Step 4 Indicator -->
                    <div class="step-indicator-node flex flex-col items-center gap-2 group cursor-pointer" onclick="goToStep(4)">
                        <div id="step-node-4" class="h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-base sm:text-lg border border-white/5 transition-all duration-300">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span id="step-text-4" class="text-xs font-semibold text-slate-400 transition-colors hidden sm:block">İletişim</span>
                    </div>

                </div>
            </div>

            <!-- Booking Form -->
            <form id="towing-booking-form" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="distance_km" name="distance_km" value="0">
                <input type="hidden" id="price" name="price" value="0">
                <input type="hidden" id="pickup_latitude" name="pickup_latitude" value="">
                <input type="hidden" id="pickup_longitude" name="pickup_longitude" value="">
                <input type="hidden" id="dropoff_latitude" name="dropoff_latitude" value="">
                <input type="hidden" id="dropoff_longitude" name="dropoff_longitude" value="">
                <input type="hidden" id="pickup_place_id" name="pickup_place_id" value="">
                <input type="hidden" id="dropoff_place_id" name="dropoff_place_id" value="">
                
                <!-- Error Message Box -->
                <div id="form-error-box" class="hidden p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span id="form-error-msg" class="text-sm font-medium">Lütfen tüm alanları doldurun.</span>
                </div>

                <!-- ================== STEP 1: VEHICLE INFO ================== -->
                <div id="step-section-1" class="step-section space-y-6">
                    <div class="border-b border-white/5 pb-4">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-car text-neonOrange"></i> Araç ve Arıza Detayları
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Lütfen çekilecek aracın marka/model, plaka ve arıza bilgilerini doldurun.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Brand & Model -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="brand_model">Araç Marka / Model <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-car-side"></i>
                                </span>
                                <input type="text" id="brand_model" name="brand_model" required placeholder="Örn: Volkswagen Golf, Ford Focus" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                            </div>
                        </div>

                        <!-- License Plate -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="plate">Araç Plakası <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-rectangle-ad"></i>
                                </span>
                                <input type="text" id="plate" name="plate" required placeholder="Örn: 34 ABC 123" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm uppercase">
                            </div>
                        </div>
                    </div>

                    <!-- Issue Type -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-3">Arıza / Durum Tipi <span class="text-neonOrange">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800/40 border border-white/5 hover:border-neonOrange/50 cursor-pointer transition-all duration-300 text-center gap-2 group">
                                <input type="radio" name="issue_type" value="Motor Arızası" checked class="hidden peer">
                                <i class="fa-solid fa-gauge-high text-slate-400 group-hover:text-neonOrange text-2xl transition-colors"></i>
                                <span class="text-xs font-medium text-slate-300">Motor Arızası</span>
                            </label>

                            <label class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800/40 border border-white/5 hover:border-neonOrange/50 cursor-pointer transition-all duration-300 text-center gap-2 group">
                                <input type="radio" name="issue_type" value="Kaza / Hasar" class="hidden peer">
                                <i class="fa-solid fa-car-burst text-slate-400 group-hover:text-neonOrange text-2xl transition-colors"></i>
                                <span class="text-xs font-medium text-slate-300">Kaza / Hasar</span>
                            </label>

                            <label class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800/40 border border-white/5 hover:border-neonOrange/50 cursor-pointer transition-all duration-300 text-center gap-2 group">
                                <input type="radio" name="issue_type" value="Lastik Patlaması" class="hidden peer">
                                <i class="fa-solid fa-circle-dot text-slate-400 group-hover:text-neonOrange text-2xl transition-colors"></i>
                                <span class="text-xs font-medium text-slate-300">Lastik Patlaması</span>
                            </label>

                            <label class="flex flex-col items-center justify-center p-4 rounded-xl bg-slate-800/40 border border-white/5 hover:border-neonOrange/50 cursor-pointer transition-all duration-300 text-center gap-2 group">
                                <input type="radio" name="issue_type" value="Diğer Nedenler" class="hidden peer">
                                <i class="fa-solid fa-screwdriver-wrench text-slate-400 group-hover:text-neonOrange text-2xl transition-colors"></i>
                                <span class="text-xs font-medium text-slate-300">Diğer / Diğer</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ================== STEP 2: LOCATIONS ================== -->
                <div id="step-section-2" class="step-section hidden space-y-6">
                    <div class="border-b border-white/5 pb-4">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-map-location-dot text-neonOrange"></i> Konum Bilgileri
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Adresleri Google Maps üzerinden seçin, ardından haritadan doğrulayın.</p>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-[1fr_1.1fr] gap-5 items-start">
                        <div class="space-y-5">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-semibold text-slate-300" for="pickup_location">Aracın Alınacağı Adres <span class="text-neonOrange">*</span></label>
                                    <button type="button" class="text-[10px] font-bold uppercase tracking-widest text-neonOrange hover:text-white transition-colors" onclick="setActiveMapTarget('pickup')">Haritada Seç</button>
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                        <i class="fa-solid fa-location-dot text-orange-500"></i>
                                    </span>
                                    <input type="text" id="pickup_location" name="pickup_location" required placeholder="Google Maps ile arayın veya haritadan seçin" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                                </div>
                                <p class="mt-2 text-[11px] text-slate-500">Seçilen nokta: <span id="pickup-coord-label" class="text-slate-300">-</span></p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-semibold text-slate-300" for="dropoff_location">Bırakılacak Adres / Sanayi <span class="text-neonOrange">*</span></label>
                                    <button type="button" class="text-[10px] font-bold uppercase tracking-widest text-neonOrange hover:text-white transition-colors" onclick="setActiveMapTarget('dropoff')">Haritada Seç</button>
                                </div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                        <i class="fa-solid fa-flag-checkered text-neonGreen"></i>
                                    </span>
                                    <input type="text" id="dropoff_location" name="dropoff_location" required placeholder="Google Maps ile arayın veya haritadan seçin" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                                </div>
                                <p class="mt-2 text-[11px] text-slate-500">Seçilen nokta: <span id="dropoff-coord-label" class="text-slate-300">-</span></p>
                            </div>

                            <div class="rounded-2xl bg-slate-900/60 border border-white/5 p-4 text-[11px] text-slate-400 leading-6">
                                Haritaya tıklayarak aktif konumu belirleyin. Önce alınacak adresi, sonra bırakılacak adresi seçmeniz yeterli.
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-widest">
                                <button type="button" id="map-target-pickup" onclick="setActiveMapTarget('pickup')" class="px-3 py-2 rounded-full border border-orange-500/20 bg-orange-500/10 text-orange-300">Alış Noktası</button>
                                <button type="button" id="map-target-dropoff" onclick="setActiveMapTarget('dropoff')" class="px-3 py-2 rounded-full border border-white/10 bg-slate-900/60 text-slate-400">Bırakış Noktası</button>
                            </div>
                            <div id="map-status" class="rounded-xl border border-white/5 bg-slate-900/60 px-4 py-3 text-[11px] text-slate-400">
                                Google Maps yükleniyor. API anahtarı tanımlı değilse harita görünmez.
                            </div>
                            <div id="booking-map" class="w-full h-[420px] rounded-3xl border border-white/10 overflow-hidden bg-slate-900/80"></div>
                        </div>
                    </div>
                </div>

                <!-- ================== STEP 3: DATE & TIME ================== -->
                <div id="step-section-3" class="step-section hidden space-y-6">
                    <div class="border-b border-white/5 pb-4">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-calendar-days text-neonOrange"></i> Planlanan Randevu Tarihi ve Saati
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Çekici hizmetinin gelmesini istediğiniz en uygun zaman dilimini seçin.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Date Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="appointment_date">Randevu Tarihi <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-calendar"></i>
                                </span>
                                <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="appointment_time">Randevu Saati <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-clock"></i>
                                </span>
                                <input type="time" id="appointment_time" name="appointment_time" required class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================== STEP 4: CONTACT INFO ================== -->
                <div id="step-section-4" class="step-section hidden space-y-6">
                    <div class="border-b border-white/5 pb-4">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fa-solid fa-id-card text-neonOrange"></i> İletişim Bilgileri
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">Talebiniz alındıktan sonra durumu bildirmek veya acil aramalarda ulaşmak için bilgilerinizi yazın.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="fullname">Ad Soyad <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" id="fullname" name="fullname" required placeholder="Örn: Ahmet Yılmaz" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2" for="phone">Telefon Numarası <span class="text-neonOrange">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                                    <i class="fa-solid fa-mobile-screen-button"></i>
                                </span>
                                <input type="tel" id="phone" name="phone" required placeholder="Örn: 0555 123 4567" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Navigation Controls -->
                <div class="flex items-center justify-between pt-6 border-t border-white/5 mt-8">
                    <!-- Prev Button -->
                    <button type="button" id="prev-btn" onclick="prevStep()" class="px-6 py-3 text-sm font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 rounded-xl border border-white/5 transition-all duration-300 flex items-center gap-2 invisible">
                        <i class="fa-solid fa-arrow-left"></i> Geri Dön
                    </button>

                    <!-- Next Button / Submit -->
                    <button type="button" id="next-btn" onclick="nextStep()" class="px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-neonOrange to-orange-500 hover:shadow-lg hover:shadow-orange-500/20 rounded-xl transition-all duration-300 flex items-center gap-2">
                        İlerle <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Floating Pricing Summary -->
    <aside id="pricing-summary-card" class="pricing-summary-float fixed right-5 top-28 z-30 hidden xl:block w-80 rounded-3xl overflow-hidden bg-slate-950/90 backdrop-blur-xl">
        <div class="p-5 border-b border-white/5 bg-gradient-to-r from-slate-950 to-slate-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[10px] uppercase tracking-[0.35em] text-slate-400">Anlık Fiyat</p>
                    <h3 class="text-xl font-black text-white">Dinamik Ücret</h3>
                </div>
                <div class="h-11 w-11 rounded-2xl bg-neonOrange/10 border border-neonOrange/20 text-neonOrange flex items-center justify-center">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">Taban Fiyat</span>
                    <span id="summary-base-price" class="font-bold text-white"><?php echo number_format($base_price, 0, ',', '.'); ?> TL</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">Mesafe</span>
                    <span id="summary-distance" class="font-bold text-white">0 km</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">Kilometre Ücreti</span>
                    <span class="font-bold text-white">40 TL</span>
                </div>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <div class="rounded-2xl bg-white/5 p-4 border border-white/5">
                <p class="text-[10px] uppercase tracking-[0.35em] text-slate-400 mb-2">Tahmini Toplam</p>
                <p id="summary-total-price" class="text-3xl font-black text-neonGreen">0 TL</p>
                <p class="text-[11px] text-slate-500 mt-2">Adım 1 ve 2 dolduruldukça otomatik güncellenir.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-[11px]">
                <div class="rounded-2xl bg-white/5 p-3 border border-white/5">
                    <p class="text-slate-400">Pickup Koordinat</p>
                    <p id="summary-pickup-coords" class="text-white font-semibold mt-1">-</p>
                </div>
                <div class="rounded-2xl bg-white/5 p-3 border border-white/5">
                    <p class="text-slate-400">Dropoff Koordinat</p>
                    <p id="summary-dropoff-coords" class="text-white font-semibold mt-1">-</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Success Modal Overlay -->
    <div id="success-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="w-full max-w-md glass-panel-heavy rounded-3xl p-8 text-center shadow-2xl relative border border-neonGreen/30 transform scale-95 transition-transform duration-300">
            <div class="h-16 w-16 bg-neonGreen/10 border border-neonGreen/30 text-neonGreen rounded-full mx-auto flex items-center justify-center text-3xl mb-5 shadow-lg shadow-neonGreen/10">
                <i class="fa-solid fa-circle-check animate-bounce"></i>
            </div>
            
            <h3 class="text-2xl font-extrabold text-white mb-2">Randevunuz Alındı!</h3>
            <p class="text-sm text-slate-300 mb-6">Talebiniz başarıyla kaydedilmiştir. Çekicimiz randevu saatinde belirttiğiniz konumda olacaktır.</p>
            
            <!-- Reservation Code Area -->
            <div class="bg-slate-900/80 border border-white/10 rounded-2xl p-4 mb-6">
                <span class="text-xs uppercase tracking-widest text-slate-400 block mb-1">Takip / Sorgulama Kodu</span>
                <span id="display-appointment-code" class="text-2xl font-black text-neonOrange tracking-wider">REZ-123456</span>
                <button onclick="copyCode()" class="block mx-auto mt-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                    <i class="fa-solid fa-copy"></i> Kodu Kopyala
                </button>
            </div>

            <div class="flex flex-col gap-3">
                <a id="track-btn" href="#" class="w-full py-3.5 bg-gradient-to-r from-neonGreen to-emerald-500 text-white text-sm font-bold rounded-xl hover:shadow-lg hover:shadow-emerald-500/20 transition-all duration-300 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i> Talebi Anlık Takip Et
                </a>
                <button onclick="closeModal()" class="w-full py-3 text-slate-400 hover:text-white text-xs font-semibold hover:underline">
                    Yeni Randevu Oluştur
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-8 text-center border-t border-white/5 bg-slate-950/80 text-xs text-slate-500">
        <p>&copy; <?php echo date('Y'); ?> Yol Destek. Tüm hakları saklıdır. Henox</p>
    </footer>

    <!-- Interactive JS Controller -->
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        const BASE_PRICE = <?php echo (float) $base_price; ?>;
        const GOOGLE_MAPS_API_KEY = window.GOOGLE_MAPS_API_KEY || '';
        const PER_KM_RATE = 40; 
        const USE_LEAFLET = !GOOGLE_MAPS_API_KEY;

        const bookingMapState = {
            map: null,
            geocoder: null,
            pickupMarker: null,
            dropoffMarker: null,
            pickupAutocomplete: null,
            dropoffAutocomplete: null,
            activeTarget: 'pickup',
            ready: false,
        };

        function normalizeText(value) {
            return (value || '').trim().toLowerCase().replace(/\s+/g, ' ');
        }

        function hashString(value) {
            let hash = 5381;
            const normalized = normalizeText(value);
            for (let index = 0; index < normalized.length; index++) {
                hash = (((hash << 5) + hash) + normalized.charCodeAt(index)) >>> 0;
            }
            return hash >>> 0;
        }

        function simulateDistanceKm(pickup, dropoff) {
            const seed = hashString(`${pickup}|${dropoff}`);
            return Number((6 + ((seed % 4200) / 100)).toFixed(1));
        }

        function simulateCoordinates(address, offset) {
            const seed = hashString(`${address}|${offset}`);
            const latitude = (40.85 + ((seed % 7000) / 100000)).toFixed(6);
            const longitude = (29.00 + (((seed / 7000) % 7000) / 100000)).toFixed(6);
            return { latitude, longitude };
        }

        function calculateHaversineKm(pickupLat, pickupLng, dropoffLat, dropoffLng) {
            const radius = 6371;
            const toRadians = value => value * Math.PI / 180;
            const deltaLat = toRadians(dropoffLat - pickupLat);
            const deltaLng = toRadians(dropoffLng - pickupLng);
            const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
                Math.cos(toRadians(pickupLat)) * Math.cos(toRadians(dropoffLat)) *
                Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
            return 2 * radius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function setActiveMapTarget(target) {
            bookingMapState.activeTarget = target;
            const pickupButton = document.getElementById('map-target-pickup');
            const dropoffButton = document.getElementById('map-target-dropoff');

            if (pickupButton && dropoffButton) {
                pickupButton.className = target === 'pickup'
                    ? 'px-3 py-2 rounded-full border border-orange-500/20 bg-orange-500/20 text-white'
                    : 'px-3 py-2 rounded-full border border-white/10 bg-slate-900/60 text-slate-400';
                dropoffButton.className = target === 'dropoff'
                    ? 'px-3 py-2 rounded-full border border-orange-500/20 bg-orange-500/20 text-white'
                    : 'px-3 py-2 rounded-full border border-white/10 bg-slate-900/60 text-slate-400';
            }
        }

        function updateCoordLabel(target, latitude, longitude) {
            const label = document.getElementById(target === 'pickup' ? 'pickup-coord-label' : 'dropoff-coord-label');
            if (label) {
                label.innerText = latitude !== null && longitude !== null ? `${latitude.toFixed(6)}, ${longitude.toFixed(6)}` : '-';
            }
        }

        function setHiddenCoordinates(target, latitude, longitude, placeId = '') {
            const latField = document.getElementById(target === 'pickup' ? 'pickup_latitude' : 'dropoff_latitude');
            const lngField = document.getElementById(target === 'pickup' ? 'pickup_longitude' : 'dropoff_longitude');
            const placeField = document.getElementById(target === 'pickup' ? 'pickup_place_id' : 'dropoff_place_id');

            if (latField) latField.value = latitude !== null && latitude !== undefined ? latitude.toFixed(6) : '';
            if (lngField) lngField.value = longitude !== null && longitude !== undefined ? longitude.toFixed(6) : '';
            if (placeField) placeField.value = placeId || '';
            updateCoordLabel(target, latitude, longitude);
        }

        function getStoredCoords(target) {
            const latitudeField = document.getElementById(target === 'pickup' ? 'pickup_latitude' : 'dropoff_latitude');
            const longitudeField = document.getElementById(target === 'pickup' ? 'pickup_longitude' : 'dropoff_longitude');
            const latitude = parseFloat(latitudeField ? latitudeField.value : '');
            const longitude = parseFloat(longitudeField ? longitudeField.value : '');

            if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
                return { latitude, longitude };
            }

            return null;
        }

        function updatePricingEstimate() {
            const pickup = document.getElementById('pickup_location').value.trim();
            const dropoff = document.getElementById('dropoff_location').value.trim();
            const pickupCoords = getStoredCoords('pickup');
            const dropoffCoords = getStoredCoords('dropoff');

            let distance = 0;
            const finishUpdate = (distanceVal, geometry) => {
                distance = Number(distanceVal || 0);

                const price = Number((distance * PER_KM_RATE).toFixed(2));

                document.getElementById('distance_km').value = distance || 0;
                document.getElementById('price').value = price || 0;

                const formattedDistance = distance ? `${distance.toFixed(1)} km` : '0 km';
                const formattedPrice = `${price.toLocaleString('tr-TR')} TL`;

                const summaryDistance = document.getElementById('summary-distance');
                const summaryTotalPrice = document.getElementById('summary-total-price');
                const pickupCoordsText = document.getElementById('summary-pickup-coords');
                const dropoffCoordsText = document.getElementById('summary-dropoff-coords');

                if (summaryDistance) summaryDistance.innerText = formattedDistance;
                if (summaryTotalPrice) summaryTotalPrice.innerText = formattedPrice;
                if (pickupCoordsText) pickupCoordsText.innerText = pickupCoords ? `${pickupCoords.latitude.toFixed(6)}, ${pickupCoords.longitude.toFixed(6)}` : '-';
                if (dropoffCoordsText) dropoffCoordsText.innerText = dropoffCoords ? `${dropoffCoords.latitude.toFixed(6)}, ${dropoffCoords.longitude.toFixed(6)}` : '-';

                if (geometry) {
                    drawRouteOnMap(geometry);
                }
            };

            if (pickupCoords && dropoffCoords) {
                getOsrmRouteDistance(pickupCoords, dropoffCoords).then(res => {
                    if (res && res.distance_km !== undefined) {
                        finishUpdate(Number(res.distance_km.toFixed(1)), res.geometry);
                    } else {
                        const est = Number((calculateHaversineKm(pickupCoords.latitude, pickupCoords.longitude, dropoffCoords.latitude, dropoffCoords.longitude) * 1.25).toFixed(1));
                        finishUpdate(est, null);
                    }
                }).catch(() => {
                    const est = Number((calculateHaversineKm(pickupCoords.latitude, pickupCoords.longitude, dropoffCoords.latitude, dropoffCoords.longitude) * 1.25).toFixed(1));
                    finishUpdate(est, null);
                });
                return;
            } else if (pickup && dropoff) {
                distance = simulateDistanceKm(pickup, dropoff);
                finishUpdate(distance, null);
                return;
            }

            const price = Number((distance * PER_KM_RATE).toFixed(2));

            document.getElementById('distance_km').value = distance || 0;
            document.getElementById('price').value = price || 0;

            const formattedDistance = distance ? `${distance.toFixed(1)} km` : '0 km';
            const formattedPrice = `${price.toLocaleString('tr-TR')} TL`;

            const summaryDistance = document.getElementById('summary-distance');
            const summaryTotalPrice = document.getElementById('summary-total-price');
            const pickupCoordsText = document.getElementById('summary-pickup-coords');
            const dropoffCoordsText = document.getElementById('summary-dropoff-coords');

            if (summaryDistance) summaryDistance.innerText = formattedDistance;
            if (summaryTotalPrice) summaryTotalPrice.innerText = formattedPrice;
            if (pickupCoordsText) pickupCoordsText.innerText = pickupCoords ? `${pickupCoords.latitude.toFixed(6)}, ${pickupCoords.longitude.toFixed(6)}` : '-';
            if (dropoffCoordsText) dropoffCoordsText.innerText = dropoffCoords ? `${dropoffCoords.latitude.toFixed(6)}, ${dropoffCoords.longitude.toFixed(6)}` : '-';
        }

        function getOsrmRouteDistance(pickupCoords, dropoffCoords) {
            return new Promise((resolve, reject) => {
                if (!pickupCoords || !dropoffCoords) return reject();
                const pLat = pickupCoords.latitude;
                const pLng = pickupCoords.longitude;
                const dLat = dropoffCoords.latitude;
                const dLng = dropoffCoords.longitude;
                const url = `https://router.project-osrm.org/route/v1/driving/${pLng},${pLat};${dLng},${dLat}?overview=full&geometries=geojson&alternatives=false&steps=false`;

                fetch(url).then(r => r.json()).then(json => {
                    if (json && json.routes && json.routes.length) {
                        const route = json.routes[0];
                        const distance_km = (route.distance || 0) / 1000;
                        const geometry = route.geometry || null;
                        resolve({ distance_km, geometry });
                    } else {
                        reject();
                    }
                }).catch(reject);
            });
        }

        function drawRouteOnMap(geometry) {
            if (!geometry || !geometry.coordinates) return;
            if (bookingMapState.routeLayer) {
                if (USE_LEAFLET && bookingMapState.routeLayer.remove) bookingMapState.routeLayer.remove();
                else if (bookingMapState.routeLayer.setMap) bookingMapState.routeLayer.setMap(null);
                bookingMapState.routeLayer = null;
            }

            const coords = geometry.coordinates.map(c => [c[1], c[0]]);

            if (USE_LEAFLET) {
                bookingMapState.routeLayer = L.polyline(coords, { color: '#10b981', weight: 5, opacity: 0.8 }).addTo(bookingMapState.map);
                bookingMapState.map.fitBounds(bookingMapState.routeLayer.getBounds(), { padding: [40, 40] });
            } else if (window.google && window.google.maps) {
                const path = coords.map(c => ({ lat: c[0], lng: c[1] }));
                bookingMapState.routeLayer = new google.maps.Polyline({ path, strokeColor: '#10b981', strokeOpacity: 0.9, strokeWeight: 5 });
                bookingMapState.routeLayer.setMap(bookingMapState.map);
                const bounds = new google.maps.LatLngBounds();
                path.forEach(p => bounds.extend(p));
                bookingMapState.map.fitBounds(bounds);
            }
        }

        /* Leaflet + Nominatim fallback for free maps */
        function initLeafletBooking() {
            const mapContainer = document.getElementById('booking-map');
            const statusBox = document.getElementById('map-status');

            if (!mapContainer || typeof L === 'undefined') {
                if (statusBox) statusBox.innerText = 'Harita yüklenemedi.';
                return;
            }

            bookingMapState.map = L.map(mapContainer).setView([41.0082, 28.9784], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(bookingMapState.map);

            bookingMapState.map.on('click', function (e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                reverseGeocodeNominatim(bookingMapState.activeTarget, lat, lng);
            });

            bookingMapState.ready = true;
            setActiveMapTarget('pickup');
            if (statusBox) statusBox.innerText = 'Harita hazır. Alış/bırakış noktalarını tıklayın veya arama yapın.';
            syncMapMarkers();
            updatePricingEstimate();

            ['pickup_location', 'dropoff_location'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('keypress', function (ev) {
                    if (ev.key === 'Enter') {
                        ev.preventDefault();
                        const q = el.value.trim();
                        if (!q) return;
                        searchNominatim(q).then(results => {
                            if (results && results.length) {
                                const r = results[0];
                                assignNominatimResult(id === 'pickup_location' ? 'pickup' : 'dropoff', r);
                            }
                        }).catch(() => {});
                    }
                });
            });
        }

        function loadLeafletScript() {
            if (window.L && document.querySelector('link[data-leaflet]')) {
                initLeafletBooking();
                return;
            }

            if (!document.querySelector('link[data-leaflet]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.dataset.leaflet = 'true';
                document.head.appendChild(link);
            }

            if (!document.querySelector('script[data-leaflet]')) {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.async = true;
                script.dataset.leaflet = 'true';
                script.onload = () => initLeafletBooking();
                document.head.appendChild(script);
            } else {
                initLeafletBooking();
            }
        }

        function searchNominatim(q) {
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(q)}&accept-language=tr`;
            return fetch(url, { headers: { 'User-Agent': 'yol-destek/1.0' } }).then(r => r.json());
        }

        function reverseGeocodeNominatim(target, lat, lng) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&accept-language=tr`;
            return fetch(url, { headers: { 'User-Agent': 'yol-destek/1.0' } })
                .then(r => r.json())
                .then(data => {
                    const display = (data && data.display_name) ? data.display_name : `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    document.getElementById(target === 'pickup' ? 'pickup_location' : 'dropoff_location').value = display;
                    setHiddenCoordinates(target, lat, lng, data && data.place_id ? data.place_id : '');
                    syncMapMarkers();
                    updatePricingEstimate();
                }).catch(() => {});
        }

        function assignNominatimResult(target, result) {
            if (!result) return;
            const lat = parseFloat(result.lat);
            const lon = parseFloat(result.lon);
            const display = result.display_name || result.name || `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
            document.getElementById(target === 'pickup' ? 'pickup_location' : 'dropoff_location').value = display;
            setHiddenCoordinates(target, lat, lon, result.osm_id || '');
            syncMapMarkers();
            updatePricingEstimate();
        }

        function syncMapMarkers() {
            if (!bookingMapState.map) {
                return;
            }
            const pickupCoords = getStoredCoords('pickup');
            const dropoffCoords = getStoredCoords('dropoff');

            if (USE_LEAFLET) {
                const bounds = [];
                if (pickupCoords) {
                    const latlng = [pickupCoords.latitude, pickupCoords.longitude];
                    if (!bookingMapState.pickupMarker) {
                        bookingMapState.pickupMarker = L.marker(latlng, { title: 'Alış Noktası' }).addTo(bookingMapState.map).bindTooltip('A');
                    } else {
                        bookingMapState.pickupMarker.setLatLng(latlng);
                    }
                    bounds.push(latlng);
                }

                if (dropoffCoords) {
                    const latlng = [dropoffCoords.latitude, dropoffCoords.longitude];
                    if (!bookingMapState.dropoffMarker) {
                        bookingMapState.dropoffMarker = L.marker(latlng, { title: 'Bırakış Noktası' }).addTo(bookingMapState.map).bindTooltip('B');
                    } else {
                        bookingMapState.dropoffMarker.setLatLng(latlng);
                    }
                    bounds.push(latlng);
                }

                if (bounds.length === 2) {
                    bookingMapState.map.fitBounds(bounds, { padding: [40, 40] });
                } else if (bounds.length === 1) {
                    bookingMapState.map.setView(bounds[0], 13);
                }

                return;
            }

            const bounds = new google.maps.LatLngBounds();

            if (pickupCoords) {
                const pickupLatLng = new google.maps.LatLng(pickupCoords.latitude, pickupCoords.longitude);
                if (!bookingMapState.pickupMarker) {
                    bookingMapState.pickupMarker = new google.maps.Marker({
                        position: pickupLatLng,
                        map: bookingMapState.map,
                        label: 'A',
                        title: 'Alış Noktası',
                    });
                } else {
                    bookingMapState.pickupMarker.setPosition(pickupLatLng);
                }
                bounds.extend(pickupLatLng);
            }

            if (dropoffCoords) {
                const dropoffLatLng = new google.maps.LatLng(dropoffCoords.latitude, dropoffCoords.longitude);
                if (!bookingMapState.dropoffMarker) {
                    bookingMapState.dropoffMarker = new google.maps.Marker({
                        position: dropoffLatLng,
                        map: bookingMapState.map,
                        label: 'B',
                        title: 'Bırakış Noktası',
                    });
                } else {
                    bookingMapState.dropoffMarker.setPosition(dropoffLatLng);
                }
                bounds.extend(dropoffLatLng);
            }

            if (pickupCoords && dropoffCoords) {
                bookingMapState.map.fitBounds(bounds);
            } else if (pickupCoords) {
                bookingMapState.map.setCenter(pickupCoords);
                bookingMapState.map.setZoom(13);
            } else if (dropoffCoords) {
                bookingMapState.map.setCenter(dropoffCoords);
                bookingMapState.map.setZoom(13);
            }
        }

        function assignSelectedPlace(target, place) {
            if (!place || !place.geometry || !place.geometry.location) {
                return;
            }

            const location = place.geometry.location;
            const latitude = typeof location.lat === 'function' ? location.lat() : location.lat;
            const longitude = typeof location.lng === 'function' ? location.lng() : location.lng;
            const address = place.formatted_address || place.name || `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;

            document.getElementById(target === 'pickup' ? 'pickup_location' : 'dropoff_location').value = address;
            setHiddenCoordinates(target, latitude, longitude, place.place_id || '');
            syncMapMarkers();
            updatePricingEstimate();
        }

        function reverseGeocodeAndAssign(target, latitude, longitude) {
            if (!bookingMapState.geocoder) {
                return;
            }

            bookingMapState.geocoder.geocode({ location: { lat: latitude, lng: longitude } }, (results, status) => {
                let address = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                if (status === 'OK' && results && results[0]) {
                    address = results[0].formatted_address || address;
                }

                document.getElementById(target === 'pickup' ? 'pickup_location' : 'dropoff_location').value = address;
                setHiddenCoordinates(target, latitude, longitude);
                syncMapMarkers();
                updatePricingEstimate();
            });
        }

        function initializeAutocomplete(inputId, target) {
            const input = document.getElementById(inputId);
            if (!input || !google.maps.places) {
                return;
            }

            const autocomplete = new google.maps.places.Autocomplete(input, {
                fields: ['place_id', 'geometry', 'formatted_address', 'name'],
                componentRestrictions: { country: 'tr' },
            });

            autocomplete.addListener('place_changed', () => {
                assignSelectedPlace(target, autocomplete.getPlace());
            });

            if (target === 'pickup') {
                bookingMapState.pickupAutocomplete = autocomplete;
            } else {
                bookingMapState.dropoffAutocomplete = autocomplete;
            }
        }

        function initGoogleMapsBooking() {
            const mapContainer = document.getElementById('booking-map');
            const statusBox = document.getElementById('map-status');

            if (!mapContainer || typeof google === 'undefined' || !google.maps) {
                if (statusBox) {
                    statusBox.innerText = 'Google Maps yüklenemedi. Lütfen API anahtarını tanımlayın.';
                }
                return;
            }

            bookingMapState.map = new google.maps.Map(mapContainer, {
                center: { lat: 41.0082, lng: 28.9784 },
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                styles: [
                    { elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#0f172a' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0ea5e9' }] },
                ],
            });

            bookingMapState.geocoder = new google.maps.Geocoder();
            initializeAutocomplete('pickup_location', 'pickup');
            initializeAutocomplete('dropoff_location', 'dropoff');

            bookingMapState.map.addListener('click', (event) => {
                if (event.latLng) {
                    reverseGeocodeAndAssign(bookingMapState.activeTarget, event.latLng.lat(), event.latLng.lng());
                }
            });

            bookingMapState.ready = true;
            setActiveMapTarget('pickup');

            if (statusBox) {
                statusBox.innerText = 'Haritadan seçim hazır. Alış ve bırakış noktalarını tıklayarak belirleyebilirsiniz.';
            }

            syncMapMarkers();
            updatePricingEstimate();
        }

        function loadGoogleMapsScript() {
            const statusBox = document.getElementById('map-status');
            if (!GOOGLE_MAPS_API_KEY) {
                loadLeafletScript();
                return;
            }

            if (window.google && window.google.maps) {
                initGoogleMapsBooking();
                return;
            }

            window.initGoogleMapsBooking = initGoogleMapsBooking;

            if (document.querySelector('script[data-google-maps-booking]')) {
                return;
            }

            const script = document.createElement('script');
            script.dataset.googleMapsBooking = 'true';
            script.async = true;
            script.defer = true;
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(GOOGLE_MAPS_API_KEY)}&libraries=places&callback=initGoogleMapsBooking`;
            document.head.appendChild(script);
        }

        function updateProgress() {
            const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('step-progress-active').style.width = percent + '%';

            for (let i = 1; i <= totalSteps; i++) {
                const node = document.getElementById(`step-node-${i}`);
                const text = document.getElementById(`step-text-${i}`);
                
                if (i < currentStep) {
                    node.className = "h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-neonGreen text-white flex items-center justify-center font-bold text-base sm:text-lg shadow-lg shadow-emerald-500/20 border-none transition-all duration-300";
                    node.innerHTML = '<i class="fa-solid fa-check"></i>';
                    if(text) text.className = "text-xs font-semibold text-neonGreen transition-colors hidden sm:block";
                } else if (i === currentStep) {
                    node.className = "h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-neonOrange text-white flex items-center justify-center font-bold text-base sm:text-lg shadow-lg shadow-orange-500/30 border-none transition-all duration-300";
                    if (i === 1) node.innerHTML = '<i class="fa-solid fa-car"></i>';
                    if (i === 2) node.innerHTML = '<i class="fa-solid fa-map-location-dot"></i>';
                    if (i === 3) node.innerHTML = '<i class="fa-solid fa-calendar-days"></i>';
                    if (i === 4) node.innerHTML = '<i class="fa-solid fa-id-card"></i>';
                    if(text) text.className = "text-xs font-semibold text-neonOrange transition-colors hidden sm:block";
                } else {
                    node.className = "h-10 w-10 sm:h-12 sm:w-12 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center font-bold text-base sm:text-lg border border-white/5 transition-all duration-300";
                    if (i === 1) node.innerHTML = '<i class="fa-solid fa-car"></i>';
                    if (i === 2) node.innerHTML = '<i class="fa-solid fa-map-location-dot"></i>';
                    if (i === 3) node.innerHTML = '<i class="fa-solid fa-calendar-days"></i>';
                    if (i === 4) node.innerHTML = '<i class="fa-solid fa-id-card"></i>';
                    if(text) text.className = "text-xs font-semibold text-slate-400 transition-colors hidden sm:block";
                }
            }

            const prevBtn = document.getElementById('prev-btn');
            if (currentStep === 1) {
                prevBtn.classList.add('invisible');
            } else {
                prevBtn.classList.remove('invisible');
            }

            const nextBtn = document.getElementById('next-btn');
            if (currentStep === totalSteps) {
                nextBtn.innerHTML = 'Randevuyu Oluştur <i class="fa-solid fa-circle-check"></i>';
                nextBtn.className = "px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-neonGreen to-emerald-500 hover:shadow-lg hover:shadow-emerald-500/20 rounded-xl transition-all duration-300 flex items-center gap-2";
            } else {
                nextBtn.innerHTML = 'İlerle <i class="fa-solid fa-arrow-right"></i>';
                nextBtn.className = "px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-neonOrange to-orange-500 hover:shadow-lg hover:shadow-orange-500/20 rounded-xl transition-all duration-300 flex items-center gap-2";
            }
        }

        function goToStep(step) {
            if (step < currentStep) {
                currentStep = step;
                showStepSection();
            } else if (step > currentStep) {
                while (currentStep < step) {
                    if (!validateStep(currentStep)) break;
                    currentStep++;
                }
                showStepSection();
            }
        }

        function showStepSection() {
            document.querySelectorAll('.step-section').forEach(sec => {
                sec.classList.add('hidden');
            });
            document.getElementById(`step-section-${currentStep}`).classList.remove('hidden');
            updateProgress();
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStepSection();
            }
        }

        function nextStep() {
            if (!validateStep(currentStep)) {
                return;
            }

            if (currentStep < totalSteps) {
                currentStep++;
                showStepSection();
            } else {
                submitBookingForm();
            }
        }

        function validateStep(step) {
            const errorBox = document.getElementById('form-error-box');
            errorBox.classList.add('hidden');

            if (step === 1) {
                const brandModel = document.getElementById('brand_model').value.trim();
                const plate = document.getElementById('plate').value.trim();
                if (!brandModel || !plate) {
                    showError('Lütfen araç marka/model ve plaka alanlarını doldurun.');
                    return false;
                }
            } else if (step === 2) {
                const pickup = document.getElementById('pickup_location').value.trim();
                const dropoff = document.getElementById('dropoff_location').value.trim();
                if (!pickup || !dropoff) {
                    showError('Lütfen aracın alınacağı ve bırakılacağı konumları detaylı yazın.');
                    return false;
                }
                if (bookingMapState.ready) {
                    const pickupCoords = getStoredCoords('pickup');
                    const dropoffCoords = getStoredCoords('dropoff');
                    if (!pickupCoords || !dropoffCoords) {
                        showError('Lütfen alış ve bırakış konumlarını Google Maps üzerinden seçin.');
                        return false;
                    }
                }
            } else if (step === 3) {
                const date = document.getElementById('appointment_date').value;
                const time = document.getElementById('appointment_time').value;
                if (!date || !time) {
                    showError('Lütfen randevu tarihi ve saati belirtin.');
                    return false;
                }
            } else if (step === 4) {
                const name = document.getElementById('fullname').value.trim();
                const phone = document.getElementById('phone').value.trim();
                if (!name || !phone) {
                    showError('Lütfen isim soyisim ve telefon bilgilerinizi doldurun.');
                    return false;
                }
            }
            return true;
        }

        function showError(msg) {
            const errorBox = document.getElementById('form-error-box');
            const errorMsg = document.getElementById('form-error-msg');
            errorMsg.innerText = msg;
            errorBox.classList.remove('hidden');
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function submitBookingForm() {
            const form = document.getElementById('towing-booking-form');
            const formData = new FormData(form);

            const nextBtn = document.getElementById('next-btn');
            const originalHTML = nextBtn.innerHTML;
            nextBtn.disabled = true;
            nextBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> Gönderiliyor...';

            fetch('kaydet.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Sunucu hatası oluştu.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('display-appointment-code').innerText = data.code;
                    document.getElementById('track-btn').href = 'sorgula.php?code=' + data.code;
                    
                    const modal = document.getElementById('success-modal');
                    modal.classList.remove('hidden');
                    setTimeout(() => {
                        modal.classList.remove('opacity-0');
                        modal.querySelector('.transform').classList.remove('scale-95');
                    }, 50);
                } else {
                    showError(data.message || 'Randevu kaydedilemedi. Lütfen tekrar deneyin.');
                    nextBtn.disabled = false;
                    nextBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                showError('Ağ hatası veya sunucu bağlantı problemi yaşandı. Lütfen tekrar deneyin.');
                nextBtn.disabled = false;
                nextBtn.innerHTML = originalHTML;
            });
        }

        function copyCode() {
            const code = document.getElementById('display-appointment-code').innerText;
            navigator.clipboard.writeText(code).then(() => {
                alert('Sorgulama kodunuz kopyalandı: ' + code);
            });
        }

        function closeModal() {
            const modal = document.getElementById('success-modal');
            modal.classList.add('opacity-0');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('towing-booking-form').reset();
                currentStep = 1;
                showStepSection();
            }, 300);
        }

        updateProgress();
        updatePricingEstimate();
        loadGoogleMapsScript();

        const pickupInput = document.getElementById('pickup_location');
        const dropoffInput = document.getElementById('dropoff_location');

        [pickupInput, dropoffInput].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    if (input.id === 'pickup_location') {
                        setHiddenCoordinates('pickup', null, null, '');
                    } else {
                        setHiddenCoordinates('dropoff', null, null, '');
                    }
                    updatePricingEstimate();
                });
            }
        });
    </script>
</body>
</html>
