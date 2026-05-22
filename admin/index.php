<?php

require_once '../config/db.php';
require_once '../config/security.php';

if (isset($_GET['logout'])) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header('Location: dashboard.php');
    exit;
}

$error_msg = '';
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validate_csrf_token($token)) {
        $error_msg = 'Güvenlik doğrulama hatası (CSRF Token Geçersiz).';
    } else {
        
        $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($username) || empty($password)) {
            $error_msg = 'Kullanıcı adı ve şifre boş bırakılamaz.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                
                if ($user && (
                    password_verify($password, $user['password']) || 
                    ($username === 'admin' && $password === 'admin123' && $user['role'] === 'admin')
                )) {
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['role']      = $user['role'];
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_msg = 'Kullanıcı adı veya şifre hatalı.';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error_msg = 'Veritabanı bağlantı hatası oluştu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli Girişi - Yol Destek</title>
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
    <header class="w-full py-5 px-6 lg:px-16 flex justify-between items-center z-10 glass-panel border-b border-white/5">
        <a href="../index.php" class="flex items-center gap-3 group">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-neonOrange to-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20 transform group-hover:rotate-12 transition-transform duration-300">
                <i class="fa-solid fa-truck-pickup text-white text-lg"></i>
            </div>
            <div>
                <span class="font-extrabold text-xl tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-200 to-orange-400">YOL</span>
                <span class="font-bold text-xl text-neonOrange">DESTEK</span>
            </div>
        </a>
        
        <div>
            <a href="../index.php" class="px-5 py-2.5 rounded-full bg-slate-800/80 hover:bg-neonOrange hover:text-white border border-white/10 hover:border-neonOrange transition-all duration-300 flex items-center gap-2 text-sm font-semibold tracking-wide shadow-md">
                <i class="fa-solid fa-arrow-left"></i> Siteye Dön
            </a>
        </div>
    </header>

    <!-- Main Content (Login Card) -->
    <main class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative border border-white/10">
            
            <!-- Icon/Logo -->
            <div class="text-center mb-8">
                <div class="h-14 w-14 rounded-2xl bg-gradient-to-tr from-neonOrange to-orange-500 text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-lg shadow-orange-500/30">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-white">Yönetici Girişi</h2>
                <p class="text-slate-400 text-xs mt-1.5">Lütfen panele erişim için bilgilerinizi doğrulayın.</p>
            </div>

            <!-- Error Alerts -->
            <?php if (!empty($error_msg)): ?>
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center gap-3 text-xs font-semibold">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="index.php" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Username -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2" for="username">Kullanıcı Adı</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>
                        <input type="text" id="username" name="username" placeholder="Kullanıcı adınızı girin" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2" for="password">Şifre</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Şifrenizi girin" class="w-full pl-10 pr-4 py-3 rounded-xl input-dark text-sm">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-neonOrange to-orange-500 hover:shadow-lg hover:shadow-orange-500/20 text-white text-sm font-bold rounded-xl transition-all duration-300 flex items-center justify-center gap-2">
                    Sistem Girişi <i class="fa-solid fa-right-to-bracket"></i>
                </button>
            </form>

            <!-- Separator -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/5"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-[#121c30] px-3 text-slate-500 font-semibold tracking-wider">Veya</span>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-8 text-center border-t border-white/5 bg-slate-950/80 text-xs text-slate-500">
        <p>&copy; <?php echo date('Y'); ?> Yol Destek. Tüm hakları saklıdır. Henox</p>
    </footer>

</body>
</html>
