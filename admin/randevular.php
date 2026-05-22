<?php

require_once '../config/db.php';
require_once '../config/security.php';

check_auth();

try {
    $stmt = $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC");
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed fetching appointments: " . $e->getMessage());
    $appointments = [];
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Rezervasyon Listesi</title>
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
            <!-- Brand Logo -->
            <div class="hidden md:flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-neonOrange text-white flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <i class="fa-solid fa-truck-pickup text-sm"></i>
                </div>
                <div>
                    <span class="font-extrabold text-sm tracking-wider">YOL DESTEK</span>
                </div>
            </div>

            <!-- Profile Info Card -->
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
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-900/40 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-chart-line text-base"></i> Genel Durum
                </a>
                <a href="randevular.php" class="sidebar-item-active flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-calendar-check text-base"></i> Rezervasyonlar
                </a>
                <a href="../index.php" target="_blank" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-white hover:bg-slate-900/40 rounded-xl text-sm font-semibold transition-all duration-200">
                    <i class="fa-solid fa-globe text-base"></i> Rezervasyon Sayfası
                </a>
            </nav>
        </div>

        <!-- Safe Logout Button -->
        <div class="pt-6 border-t border-white/5 mt-8">
            <a href="dashboard.php?logout=true" onclick="return confirm('Çıkış yapmak istediğinize emin misiniz?')" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-xl text-sm font-semibold transition-all duration-200">
                <i class="fa-solid fa-right-from-bracket text-base"></i> Güvenli Çıkış
            </a>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="flex-grow p-6 lg:p-10 overflow-y-auto w-full md:max-w-[calc(100%-16rem)]">
        
        <!-- Header Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-6 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Rezervasyon Yönetimi</h1>
                <p class="text-xs text-slate-400 mt-1">Sistemdeki tüm randevuları görüntüleyebilir, durumlarını güncelleyebilir veya silebilirsiniz.</p>
            </div>
            
            <!-- Dynamic search panel -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="table-search" placeholder="İsim, plaka, kod ara..." class="w-full pl-9 pr-4 py-2 rounded-xl input-dark text-xs">
            </div>
        </div>

        <!-- Quick Debug: appointment count -->
        <div class="mb-4">
            <div class="inline-block px-3 py-2 rounded-lg bg-slate-900/60 border border-white/5 text-xs text-slate-300">
                Toplam randevu: <strong class="text-white"><?php echo count($appointments); ?></strong>
            </div>
            <?php if (count($appointments) > 0): ?>
                <div class="inline-block ml-3 text-xs text-slate-400">İlk kayıtlar: 
                    <?php echo implode(', ', array_map(function($a){ return htmlspecialchars($a['appointment_code']); }, array_slice($appointments,0,5))); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Data Table Container -->
        <div class="w-full glass-panel rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/40 text-slate-400 font-bold border-b border-white/5">
                        <tr>
                            <th class="p-4 uppercase tracking-wider">Kod</th>
                            <th class="p-4 uppercase tracking-wider">Müşteri Detayları</th>
                            <th class="p-4 uppercase tracking-wider">Araç & Plaka</th>
                            <th class="p-4 uppercase tracking-wider">Konumlar (Alış / Bırakış)</th>
                            <th class="p-4 uppercase tracking-wider">Zamanlama</th>
                            <th class="p-4 uppercase tracking-wider">Tutar</th>
                            <th class="p-4 uppercase tracking-wider">Durum</th>
                            <th class="p-4 uppercase tracking-wider text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="appointments-tbody" class="divide-y divide-white/5">
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $item): ?>
                                <tr id="row-<?php echo $item['id']; ?>" class="hover:bg-slate-800/20 transition-colors">
                                    <!-- Code -->
                                    <td class="p-4 font-bold text-neonOrange tracking-wide whitespace-nowrap">
                                        <?php echo $item['appointment_code']; ?>
                                    </td>
                                    
                                    <!-- Client Details -->
                                    <td class="p-4">
                                        <div class="font-semibold text-white text-sm"><?php echo htmlspecialchars($item['fullname']); ?></div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($item['phone']); ?></div>
                                    </td>
                                    
                                    <!-- Vehicle / Plate -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-200"><?php echo htmlspecialchars($item['brand_model']); ?></div>
                                        <span class="mt-1 inline-block bg-slate-800 border border-white/10 text-white font-bold tracking-wider px-1.5 py-0.5 rounded text-[10px] uppercase">
                                            <?php echo htmlspecialchars($item['plate']); ?>
                                        </span>
                                        <span class="text-[10px] text-slate-500 ml-1.5"><?php echo htmlspecialchars($item['issue_type']); ?></span>
                                    </td>
                                    
                                    <!-- Locations -->
                                    <td class="p-4 max-w-xs">
                                        <div class="truncate text-slate-400" title="<?php echo htmlspecialchars($item['pickup_location']); ?>">
                                            <i class="fa-solid fa-location-arrow text-orange-500 mr-1 text-[10px]"></i><?php echo htmlspecialchars($item['pickup_location']); ?>
                                        </div>
                                        <div class="truncate text-slate-400 mt-1" title="<?php echo htmlspecialchars($item['dropoff_location']); ?>">
                                            <i class="fa-solid fa-flag-checkered text-neonGreen mr-1 text-[10px]"></i><?php echo htmlspecialchars($item['dropoff_location']); ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Schedule -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-200"><?php echo date('d.m.Y', strtotime($item['appointment_date'])); ?></div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><?php echo date('H:i', strtotime($item['appointment_time'])); ?></div>
                                    </td>

                                    <!-- Price -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="font-bold text-neonGreen text-sm"><?php echo number_format((float)($item['price'] ?? 0), 2, ',', '.'); ?> TL</div>
                                        <div class="text-[10px] text-slate-500 mt-0.5"><?php echo number_format((float)($item['distance_km'] ?? 0), 1, ',', '.'); ?> km</div>
                                    </td>
                                    
                                    <!-- Status -->
                                    <td class="p-4 whitespace-nowrap">
                                        <div class="relative">
                                            <select onchange="updateStatus(<?php echo $item['id']; ?>, this.value)" class="bg-slate-900 border border-white/10 rounded-lg px-2.5 py-1 text-[11px] font-bold outline-none cursor-pointer text-white focus:border-neonOrange transition-colors <?php 
                                                if($item['status'] === 'pending') echo 'text-orange-400 border-orange-500/20';
                                                elseif($item['status'] === 'on_way') echo 'text-amber-400 border-amber-500/20';
                                                elseif($item['status'] === 'canceled') echo 'text-red-400 border-red-500/20';
                                                else echo 'text-neonGreen border-neonGreen/20';
                                            ?>">
                                                <option value="pending" class="text-orange-400" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                                                <option value="on_way" class="text-amber-400" <?php echo $item['status'] === 'on_way' ? 'selected' : ''; ?>>Çekici Yolda</option>
                                                <option value="completed" class="text-neonGreen" <?php echo $item['status'] === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                                <option value="canceled" class="text-red-400" <?php echo $item['status'] === 'canceled' ? 'selected' : ''; ?>>İptal</option>
                                            </select>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="p-4 text-center">
                                        <button onclick="deleteAppointment(<?php echo $item['id']; ?>)" class="h-8 w-8 rounded-lg bg-red-500/10 hover:bg-red-500/25 border border-red-500/20 hover:border-red-500/40 text-red-400 hover:text-white flex items-center justify-center mx-auto transition-all duration-200" title="Randevuyu Sil">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-500 text-sm font-medium">
                                    <i class="fa-solid fa-folder-open text-3xl mb-3 block"></i>
                                    Kayıtlı randevu kaydı bulunmamaktadır.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Custom Premium Toast Element -->
    <div id="toast-notification" class="fixed top-6 right-6 z-50 transform translate-y-[-100px] opacity-0 transition-all duration-300 pointer-events-none">
        <div class="px-5 py-4 rounded-2xl shadow-2xl flex items-center gap-3 max-w-sm border backdrop-blur-md">
            <div id="toast-icon-wrapper" class="h-9 w-9 rounded-xl flex items-center justify-center text-white text-base shadow-md">
                <!-- Filled via JS -->
            </div>
            <div>
                <h4 id="toast-title" class="font-bold text-white text-xs">Başarılı</h4>
                <p id="toast-desc" class="text-[11px] text-slate-300 mt-0.5">İşlem başarıyla tamamlandı.</p>
            </div>
        </div>
    </div>

    <!-- AJAX Client Operations -->
    <script>
        const searchInput = document.getElementById('table-search');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#appointments-tbody tr');
                
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    if (text.includes(term)) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }
                });
            });
        }

        function showToast(type, title, desc) {
            const toast = document.getElementById('toast-notification');
            const iconWrapper = document.getElementById('toast-icon-wrapper');
            const titleEl = document.getElementById('toast-title');
            const descEl = document.getElementById('toast-desc');
            
            if (type === 'success') {
                toast.firstElementChild.className = "px-5 py-4 rounded-2xl shadow-2xl flex items-center gap-3 max-w-sm border border-emerald-500/20 bg-emerald-950/85 backdrop-blur-md";
                iconWrapper.className = "h-9 w-9 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-neonGreen flex items-center justify-center text-base shadow-md shadow-emerald-500/10";
                iconWrapper.innerHTML = '<i class="fa-solid fa-circle-check animate-bounce"></i>';
            } else if (type === 'error') {
                toast.firstElementChild.className = "px-5 py-4 rounded-2xl shadow-2xl flex items-center gap-3 max-w-sm border border-red-500/20 bg-red-950/85 backdrop-blur-md";
                iconWrapper.className = "h-9 w-9 rounded-xl bg-red-500/20 border border-red-500/30 text-red-400 flex items-center justify-center text-base shadow-md shadow-red-500/10";
                iconWrapper.innerHTML = '<i class="fa-solid fa-lock"></i>';
            }

            titleEl.innerText = title;
            descEl.innerText = desc;
            
            toast.classList.remove('translate-y-[-100px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-[-100px]', 'opacity-0');
            }, 4000);
        }

        function updateStatus(id, newStatus) {
            fetch('islem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=change_status&id=${id}&status=${newStatus}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'İşlem Başarılı', data.message);
                    
                    const selectEl = document.querySelector(`#row-${id} select`);
                    selectEl.className = "bg-slate-900 border border-white/10 rounded-lg px-2.5 py-1 text-[11px] font-bold outline-none cursor-pointer text-white focus:border-neonOrange transition-colors";
                    if (newStatus === 'pending') {
                        selectEl.classList.add('text-orange-400', 'border-orange-500/20');
                    } else if (newStatus === 'on_way') {
                        selectEl.classList.add('text-amber-400', 'border-amber-500/20');
                    } else if (newStatus === 'canceled') {
                        selectEl.classList.add('text-red-400', 'border-red-500/20');
                    } else {
                        selectEl.classList.add('text-neonGreen', 'border-neonGreen/20');
                    }
                } else {
                    showToast('error', 'İşlem Engellendi', data.message);
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(err => {
                showToast('error', 'Hata', 'İletişim sağlanamadı. Lütfen tekrar deneyin.');
            });
        }

        function deleteAppointment(id) {
            if (!confirm('Bu randevu kaydını silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
                return;
            }

            fetch('islem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=delete&id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Silme Başarılı', data.message);
                    const row = document.getElementById(`row-${id}`);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                        }, 400);
                    }
                } else {
                    showToast('error', 'Silme Engellendi', data.message);
                }
            })
            .catch(err => {
                showToast('error', 'Hata', 'İletişim sağlanamadı. Lütfen tekrar deneyin.');
            });
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
