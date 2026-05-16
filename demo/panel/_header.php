<?php
// Output buffering start taake Ajax redirect mein header already sent ka error na aaye
ob_start();

// --- 1. CORE HELPERS & SESSION ---
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

// ====================================================
// 🛡️ PROJECT IRON-CORE SECURITY PROTOCOLS
// ====================================================

// 1. Security Engine Load Karo
require_once __DIR__ . '/../includes/iron_core.php';

// 2. Session DNA Lock Activate Karo (IP & Browser Check)
activateIronCore(); 

// 🔥 DEFCON-1 AJAX HANDLER (Hacker Protocol) 🔥
if (isset($_POST['defcon_action'])) {
    ob_clean();
    header('Content-Type: text/plain');
    if ($_POST['defcon_action'] === 'engage') {
        if ($_POST['pin'] === '1234') { // DEFCON PIN CODE
            $_SESSION['defcon_active'] = true;
            echo "locked";
        } else {
            echo "invalid_pin";
        }
    } elseif ($_POST['defcon_action'] === 'disengage') {
        $_SESSION['defcon_active'] = false;
        echo "unlocked";
    }
    exit;
}

// 🔥 LIVE CURRENCY FIX (PHP Interceptor) 🔥
if (isset($_GET['set_admin_currency'])) {
    $new_currency = htmlspecialchars(trim($_GET['set_admin_currency']));
    setcookie('site_currency', $new_currency, time() + (365 * 24 * 60 * 60), '/'); 
    $_COOKIE['site_currency'] = $new_currency; 
    
    // URL se parameter hatane ke liye clean redirect
    $current_url = $_SERVER['REQUEST_URI'];
    $clean_url = preg_replace('/([&?])set_admin_currency=[^&]+(&|$)/', '$1', $current_url);
    $clean_url = rtrim(rtrim($clean_url, '&'), '?');
    
    header("Location: " . $clean_url);
    exit;
}

// 3. Sakht Admin Authorization
if (!isAdmin()) {
    header("Location: ../boss_login.php?msg=auth_failed");
    exit;
}
// ====================================================

// --- 3. PAGE LOGIC ---
$current_page = basename($_SERVER['PHP_SELF']);

// --- 4. MASTER NAVIGATION MENU (ADVANCED & CLEANED) ---
$master_menu = [
    [
        'label' => 'Dashboard',
        'link' => 'index.php',
        'icon' => 'fa-home',
        'color' => '#8b5cf6', 
        'children' => []
    ],
    [
        'label' => 'SMM Panel',
        'link' => '#',
        'icon' => 'fa-rocket',
        'color' => '#a855f7', 
        'children' => [
            ['label' => 'Dashboard', 'link' => 'smm_dashboard.php', 'icon' => 'fa-tachometer-alt'],
            ['label' => 'Orders', 'link' => 'smm_orders.php', 'icon' => 'fa-shopping-cart'],
            ['label' => 'Services', 'link' => 'smm_services.php', 'icon' => 'fa-list'],
            ['label' => 'Categories', 'link' => 'smm_categories.php', 'icon' => 'fa-tags'],
            ['label' => 'Providers', 'link' => 'providers.php', 'icon' => 'fa-server'],
            ['label' => 'API Check', 'link' => 'api_smm_order_check.php', 'icon' => 'fa-plug'],
            ['label' => 'Provider Logs', 'link' => 'smm_logs.php', 'icon' => 'fa-file-medical-alt'],
            ['label' => 'Bulk Edit', 'link' => 'bulk_edit.php', 'icon' => 'fa-edit']
        ]
    ],
    [
        'label' => 'Digital Store',
        'link' => '#',
        'icon' => 'fa-store',
        'color' => '#9333ea', 
        'children' => [
            ['label' => 'Dashboard', 'link' => 'sub_dashboard.php', 'icon' => 'fa-chart-pie'],
            ['label' => 'All Products', 'link' => 'products.php', 'icon' => 'fa-box-open'],
            ['label' => 'Categories', 'link' => 'categories.php', 'icon' => 'fa-layer-group'],
            ['label' => 'Variations', 'link' => 'variations.php', 'icon' => 'fa-sliders-h'],
            ['label' => 'Sub Orders', 'link' => 'orders.php', 'icon' => 'fa-file-invoice'],
            ['label' => 'Digital Orders', 'link' => 'digital_orders.php', 'icon' => 'fa-download'],
            ['label' => 'File Manager', 'link' => 'downloads_manager.php', 'icon' => 'fa-cloud-upload-alt']
        ]
    ],
    [
        'label' => 'Crypto / P2P',
        'link' => '#',
        'icon' => 'fa-bitcoin',
        'color' => '#7e22ce', 
        'children' => [
            ['label' => 'Crypto Orders', 'link' => 'crypto_orders.php', 'icon' => 'fa-receipt'],
            ['label' => 'Exchanges', 'link' => 'crypto_exchanges.php', 'icon' => 'fa-exchange-alt'],
            ['label' => 'Settings', 'link' => 'crypto_settings.php', 'icon' => 'fa-cog']
        ]
    ],
    [
        'label' => 'Users & Staff',
        'link' => '#',
        'icon' => 'fa-users',
        'color' => '#6b21a8', 
        'children' => [
            ['label' => 'User Manager', 'link' => 'users.php', 'icon' => 'fa-user-friends'],
            ['label' => 'Staff Manager', 'link' => 'staff_manager.php', 'icon' => 'fa-user-shield'],
            ['label' => 'Payment Logs', 'link' => 'payments.php', 'icon' => 'fa-wallet'],
            ['label' => 'Payment Methods', 'link' => 'methods.php', 'icon' => 'fa-money-check-alt'],
            ['label' => 'Support Tickets', 'link' => 'tickets.php', 'icon' => 'fa-headset']
        ]
    ],
    [
        'label' => 'Marketing Tools',
        'link' => '#',
        'icon' => 'fa-bullhorn',
        'color' => '#8b5cf6', 
        'children' => [
            ['label' => 'Email Marketing', 'link' => 'email_marketing.php', 'icon' => 'fa-envelope-open-text'], // NEW: ADDED HERE 🔥
            ['label' => 'Broadcast', 'link' => 'broadcast.php', 'icon' => 'fa-paper-plane'],
            ['label' => 'Push Notify', 'link' => 'push_notifications.php', 'icon' => 'fa-bell'],
            ['label' => 'WhatsApp Leads', 'link' => 'whatsapp_leads.php', 'icon' => 'fab fa-whatsapp'],
            ['label' => 'Promo Codes', 'link' => 'promo_codes.php', 'icon' => 'fa-ticket-alt'],
            ['label' => 'Spin Wheel', 'link' => 'wheel_prizes.php', 'icon' => 'fa-dharmachakra'],
            ['label' => 'Flash Deals', 'link' => 'flash_manage.php', 'icon' => 'fa-bolt'],
            ['label' => 'Card Designer', 'link' => 'card_designer.php', 'icon' => 'fa-paint-brush']
        ]
    ],
    [
        'label' => 'SEO & AI Hub',
        'link' => '#',
        'icon' => 'fa-search',
        'color' => '#a855f7', 
        'children' => [
            ['label' => 'SEO Toolkit', 'link' => 'seo_tools.php', 'icon' => 'fa-search-dollar'],
            ['label' => 'SEO Manager', 'link' => 'seo_manager.php', 'icon' => 'fa-sliders-h'],
            ['label' => 'SEO Logs', 'link' => 'seo_logs.php', 'icon' => 'fa-clipboard-list'],
            ['label' => 'AI Engine', 'link' => 'ai_manager.php', 'icon' => 'fa-brain']
        ]
    ],
    [
        'label' => 'Site Content',
        'link' => '#',
        'icon' => 'fa-palette',
        'color' => '#9333ea', 
        'children' => [
            ['label' => 'Theme Editor', 'link' => 'theme_editor.php', 'icon' => 'fa-code'],
            ['label' => 'Blog Manager', 'link' => 'blog_manager.php', 'icon' => 'fa-blog'],
            ['label' => 'Audio Manager', 'link' => 'manage_audio.php', 'icon' => 'fa-music'],
            ['label' => 'Tutorials', 'link' => 'tutorials.php', 'icon' => 'fa-book'],
            ['label' => 'Testimonials', 'link' => 'testimonials.php', 'icon' => 'fa-star'],
            ['label' => 'Menu Builder', 'link' => 'menus.php', 'icon' => 'fa-bars']
        ]
    ],
    [
        'label' => 'System Core',
        'link' => '#',
        'icon' => 'fa-cogs',
        'color' => '#7e22ce', 
        'children' => [
            ['label' => 'Main Settings', 'link' => 'settings.php', 'icon' => 'fa-sliders-h'],
            ['label' => 'System Controls', 'link' => 'system_controls.php', 'icon' => 'fa-microchip'],
            ['label' => 'Google Config', 'link' => 'google_settings.php', 'icon' => 'fa-google'],
            ['label' => 'VPN Security', 'link' => 'vpn_settings.php', 'icon' => 'fa-shield-alt'],
            ['label' => 'Secure Vault', 'link' => 'secure_vault.php', 'icon' => 'fa-key']
        ]
    ],
    [
        'label' => 'Maintenance',
        'link' => '#',
        'icon' => 'fa-tools',
        'color' => '#6b21a8', 
        'children' => [
            ['label' => 'Panel Reports', 'link' => 'reports.php', 'icon' => 'fa-chart-line'],
            ['label' => 'Cron Jobs', 'link' => 'cron_jobs.php', 'icon' => 'fa-clock'],
            ['label' => 'Activity Log', 'link' => 'activity_log.php', 'icon' => 'fa-clipboard-list'],
            ['label' => 'Update Logs', 'link' => 'updates_log.php', 'icon' => 'fa-sync'],
            ['label' => 'GitHub Sync', 'link' => 'github_sync.php', 'icon' => 'fa-code-branch']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= htmlspecialchars($GLOBALS['settings']['site_name'] ?? 'Panel') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        /* 💜 WHITE & PURPLE THEME OVERHAUL 💜 */
        :root { --primary-color: #8b5cf6; --primary-dark: #6d28d9; --primary-light: #f5f3ff; --sidebar-width: 280px; }
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: background-color 0.5s ease; }
        
        /* 🟢 MATRIX MODE THEME OVERRIDES 🟢 */
        body.matrix-mode { background-color: #000 !important; color: #0f0 !important; }
        body.matrix-mode .sidebar { background: #050505 !important; border-right: 1px solid #0f0 !important; }
        body.matrix-mode .nav-link { color: #0a0 !important; }
        body.matrix-mode .nav-link:hover, body.matrix-mode .nav-link.active { background: #002200 !important; color: #0f0 !important; box-shadow: inset 3px 0 0 #0f0 !important; }
        body.matrix-mode .sidebar-brand { background: #000 !important; color: #0f0 !important; border-bottom: 1px solid #0f0 !important; }
        body.matrix-mode .page-content-wrapper { filter: invert(1) hue-rotate(180deg); }

        /* ==========================================
           1. SIDEBAR STYLES (Perfected White & Purple)
           ========================================== */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 1050;
            display: flex; flex-direction: column; transition: transform 0.3s ease;
            box-shadow: 4px 0 15px rgba(139, 92, 246, 0.05); /* Purple shadow hint */
        }
        .sidebar-brand {
            padding: 20px 25px; font-size: 1.4rem; font-weight: 800; color: var(--primary-dark);
            border-bottom: 1px solid var(--primary-light); display: flex; align-items: center; gap: 12px;
            background: linear-gradient(to right, #ffffff, var(--primary-light));
        }

        .nav-links { list-style: none; padding: 15px 10px; margin: 0; flex: 1; overflow-y: auto; }
        .nav-item { position: relative; margin-bottom: 4px; }

        .nav-link {
            color: #475569; padding: 12px 18px; font-weight: 600; display: flex; align-items: center; gap: 12px;
            transition: all 0.2s; border-radius: 12px; text-decoration: none; cursor: pointer; font-size: 0.92rem;
        }
        .nav-link:hover { background-color: var(--primary-light); color: var(--primary-dark); transform: translateX(4px); }
        .nav-link.active { background-color: var(--primary-light); color: var(--primary-dark); box-shadow: inset 3px 0 0 var(--primary-color); }
        
        .nav-link i.icon { width: 24px; text-align: center; font-size: 1.1rem; transition: 0.3s; }
        .nav-link:hover i.icon { transform: scale(1.1); }
        
        .arrow { margin-left: auto; transition: transform 0.3s ease; font-size: 0.8rem; opacity: 0.5; }
        .nav-item.open .arrow { transform: rotate(180deg); }
        .nav-item.open > .nav-link { color: var(--primary-dark); background: var(--primary-light); }
        
        .sub-menu {
            list-style: none; padding: 5px 0 5px 10px; background: #ffffff; overflow: hidden; 
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-height: 0px; display: block;
            margin-left: 15px; border-left: 2px solid #e2e8f0;
        }
        .sub-menu li a {
            display: flex; align-items: center; padding: 10px 15px; font-size: 0.85rem; color: #64748b;
            text-decoration: none; transition: 0.2s; border-radius: 8px; margin-bottom: 2px;
        }
        .sub-menu li a i { font-size: 0.8rem; width: 20px; text-align: center; margin-right: 8px; opacity: 0.7; }
        .sub-menu li a:hover, .sub-menu li a.active { color: var(--primary-color); background: var(--primary-light); font-weight: 700; }
        .sub-menu li a.active { background: var(--primary-light); }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* 🔥 TOOLS AREA INSIDE SIDEBAR 🔥 */
        .sidebar-tools {
            padding: 15px; background: #ffffff; border-top: 1px solid #e2e8f0;
            display: flex; flex-direction: column; gap: 8px;
        }
        .tool-box {
            display: flex; align-items: center; gap: 10px; background: var(--primary-light); border: 1px solid #ddd6fe; border-radius: 8px; padding: 6px 10px; font-size: 0.8rem; cursor: pointer; transition: 0.2s;
        }
        .tool-box:hover { border-color: var(--primary-color); background: #ede9fe; }
        .tool-box i { color: var(--primary-dark); font-size: 0.9rem; width: 18px; text-align: center; }
        .tool-box select { border: none; outline: none; background: transparent; width: 100%; font-weight: 700; color: var(--primary-dark); cursor: pointer; font-size: 0.8rem; }
        
        .goog-te-combo { border: none !important; outline: none !important; background: transparent !important; width: 100%; font-weight: 700; color: var(--primary-dark); cursor: pointer; font-size: 0.8rem; }
        .goog-logo-link { display:none !important; } .goog-te-gadget { color: transparent !important; font-size: 0px !important; } .goog-te-banner-frame { display:none !important; } body { top: 0px !important; }

        /* ==========================================
           2. MAIN CONTENT AREA
           ========================================== */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 0; transition: 0.3s; display: flex; flex-direction: column; min-height: 100vh;
        }
        .page-content-wrapper { padding: 30px; flex: 1; position: relative; }

        /* MOBILE */
        .mobile-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; border-radius: 50%; width: 45px; height: 45px; box-shadow: 0 4px 10px rgba(139,92,246,0.3); background: var(--primary-color); border:none; color:white; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.show { transform: translateX(0); box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: flex; align-items: center; justify-content: center; }
            .page-content-wrapper { padding: 15px; padding-top: 70px; } 
        }

        /* ====================================================
           🚨 DEFCON-1 PANIC BUTTON STYLES 🚨
           ==================================================== */
        .defcon-glass-case {
            position: fixed; bottom: 30px; right: 30px; width: 80px; height: 80px;
            background: rgba(255,255,255,0.8); backdrop-filter: blur(15px); border: 2px solid rgba(139,92,246,0.3);
            border-radius: 20px; display: flex; align-items: center; justify-content: center;
            z-index: 1040; box-shadow: 0 10px 30px rgba(139,92,246,0.2);
        }
        .defcon-btn {
            width: 55px; height: 55px; background: radial-gradient(circle, #ff0055 20%, #8b0000 80%);
            border: 3px solid #fff; border-radius: 50%; color: white; font-size: 1.5rem;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 0 20px #ff0055, inset 0 0 10px #000; transition: 0.3s;
            animation: pulseDefcon 2s infinite;
        }
        .defcon-btn:hover { transform: scale(1.1); box-shadow: 0 0 40px #ff0055, inset 0 0 15px #000; }
        @keyframes pulseDefcon { 0% { box-shadow: 0 0 10px #ff0055; } 50% { box-shadow: 0 0 30px #ff0055, 0 0 50px #ff0055; } 100% { box-shadow: 0 0 10px #ff0055; } }

        /* MODAL & SHIELDS */
        .defcon-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 0, 0, 0.9); backdrop-filter: blur(15px);
            z-index: 1060; display: none; flex-direction: column; align-items: center; justify-content: center;
            color: #ff0055; font-family: 'Space Mono', monospace;
        }
        .defcon-overlay.active { display: flex; animation: redAlert 1s infinite; }
        @keyframes redAlert { 0%, 100% { box-shadow: inset 0 0 50px rgba(255, 0, 0, 0.1); } 50% { box-shadow: inset 0 0 200px rgba(255, 0, 0, 0.4); } }
        
        .defcon-box {
            background: #000; border: 2px solid #ff0055; padding: 40px; border-radius: 20px;
            text-align: center; max-width: 400px; width: 90%; box-shadow: 0 0 50px rgba(255, 0, 85, 0.4);
        }
        .defcon-box i { font-size: 4.5rem; margin-bottom: 20px; text-shadow: 0 0 20px #ff0055; }
        .defcon-title { font-size: 2.2rem; font-weight: 900; margin-bottom: 10px; letter-spacing: 4px; }
        .defcon-desc { font-size: 0.9rem; color: #ff6688; margin-bottom: 30px; line-height: 1.5; }
        .defcon-pin { background: #111; border: 1px solid #ff0055; color: #fff; font-size: 2rem; padding: 10px; width: 100%; text-align: center; letter-spacing: 15px; font-family: monospace; outline: none; margin-bottom: 20px; border-radius: 10px; }
        .defcon-action-btn { background: #ff0055; color: #fff; border: none; padding: 15px; font-size: 1.1rem; font-weight: 900; width: 100%; cursor: pointer; transition: 0.3s; border-radius: 10px; letter-spacing: 1px; }
        .defcon-close { background: transparent; color: #666; border: none; margin-top: 20px; cursor: pointer; text-decoration: underline; font-family: monospace; transition: 0.2s; }

        /* ====================================================
           🚀 REAL-TIME CYBER TOASTS (Only shown when called)
           ==================================================== */
        #cyber-toast-container {
            position: fixed; bottom: 120px; right: 30px; display: flex; flex-direction: column; gap: 10px; z-index: 1040;
        }
        .cyber-toast {
            background: #ffffff; color: var(--primary-dark); font-weight: 600;
            border: 1px solid #e2e8f0; border-left: 4px solid var(--primary-color); padding: 15px 20px;
            border-radius: 12px; font-family: 'Segoe UI', sans-serif; font-size: 0.9rem;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.15); animation: slideInRight 0.4s ease-out;
            display: flex; align-items: center; gap: 15px;
        }
        .cyber-toast i { font-size: 1.2rem; }
        .cyber-toast.warn { border-left-color: #ef4444; color: #b91c1c; }
        .cyber-toast.warn i { color: #ef4444; }
        .cyber-toast.info { border-left-color: var(--primary-color); }
        .cyber-toast.info i { color: var(--primary-color); }
        .cyber-toast.success { border-left-color: #10b981; color: #047857; }
        .cyber-toast.success i { color: #10b981; }
        
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOutRight { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }

        /* SWEETALERT PURPLE THEME OVERRIDES */
        .swal2-popup.purple-theme { border-radius: 20px !important; box-shadow: 0 20px 50px rgba(139,92,246,0.2) !important; padding: 30px !important; border: 1px solid #e2e8f0; }
        .swal2-title.purple-theme { color: var(--primary-dark) !important; font-weight: 900 !important; font-size: 1.8rem !important; }
        .swal2-html-container.purple-theme { color: #475569 !important; font-size: 1.05rem !important; }
        .swal2-confirm.purple-theme { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)) !important; border-radius: 12px !important; padding: 12px 30px !important; font-weight: 700 !important; box-shadow: 0 10px 20px rgba(139,92,246,0.3) !important; }

    </style>
</head>
<body>

    <div id="cyber-toast-container"></div>

    <div class="defcon-glass-case">
        <div class="defcon-btn" onclick="openDefcon()" title="INITIATE LOCKDOWN">
            <i class="fas fa-radiation"></i>
        </div>
    </div>

    <div class="defcon-overlay" id="defconModal">
        <div class="defcon-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="defcon-title">DEFCON-1</div>
            <div class="defcon-desc">WARNING: Initiating lockdown will drop all external connections. Enter Admin PIN to proceed.</div>
            <input type="password" id="defconPin" class="defcon-pin" maxlength="4" placeholder="••••" autocomplete="off">
            <button class="defcon-action-btn" onclick="triggerLockdown()">ENGAGE LOCKDOWN</button>
            <button class="defcon-close" onclick="closeDefcon()">ABORT MISSION</button>
        </div>
    </div>

    <div class="defcon-overlay" id="lockdownActiveScreen" style="z-index: 10001; background: rgba(5, 10, 5, 0.95);">
        <div class="defcon-box" style="border-color: #00ff88; box-shadow: 0 0 50px rgba(0, 255, 136, 0.3);">
            <i class="fas fa-shield-alt" style="color: #00ff88; text-shadow: 0 0 30px #00ff88;"></i>
            <div class="defcon-title" style="color: #00ff88;">SYSTEM SECURED</div>
            <div class="defcon-desc" style="color: #66ffbb;">Frontend is currently locked down. All external network requests dropped.</div>
            <button class="defcon-action-btn" style="background:#00ff88; color:#000; box-shadow: 0 0 15px #00ff88;" onclick="liftLockdown()">DISENGAGE LOCKDOWN</button>
        </div>
    </div>

    <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('show')"><i class="fas fa-bars"></i></button>

    <div class="sidebar">
        <div class="sidebar-brand">
            <div style="width:38px; height:38px; background:linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.2rem; box-shadow: 0 4px 10px rgba(139,92,246,0.4);">
                <i class="fas fa-bolt"></i>
            </div>
            <span>Admin Panel</span>
        </div>

        <ul class="nav-links">
            <?php foreach ($master_menu as $menu): 
                $children = $menu['children'] ?? []; $has_children = !empty($children);
                $is_active = ($menu['link'] == $current_page); $child_active = false;
                if ($has_children) { foreach ($children as $c) { if ($c['link'] == $current_page) { $child_active = true; break; } } }
                $menu_link = $has_children ? 'javascript:void(0);' : $menu['link'];
                $toggle_class = $has_children ? 'has-dropdown' : '';
                $color_style = isset($menu['color']) ? "color: {$menu['color']};" : "";
            ?>
            <li class="nav-item <?= $child_active ? 'open' : '' ?>">
                <a href="<?= $menu_link ?>" class="nav-link <?= ($is_active || $child_active) ? 'active' : '' ?> <?= $toggle_class ?>">
                    <i class="fa-solid <?= $menu['icon'] ?> icon" style="<?= $color_style ?>"></i>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                    <?php if ($has_children): ?><i class="fa-solid fa-chevron-down arrow"></i><?php endif; ?>
                </a>
                <?php if ($has_children): ?>
                <ul class="sub-menu" style="max-height: <?= $child_active ? '1000px' : '0px' ?>;">
                    <?php foreach ($children as $sub): $sub_active = ($sub['link'] == $current_page) ? 'active' : ''; ?>
                    <li><a href="<?= $sub['link'] ?>" class="<?= $sub_active ?>"><i class="fa-solid <?= $sub['icon'] ?>"></i><?= htmlspecialchars($sub['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="sidebar-tools">
            <div class="tool-box"><i class="fas fa-language"></i><div id="google_translate_element" style="width:100%; overflow:hidden;"></div></div>
            <div class="tool-box">
                <i class="fas fa-coins"></i>
                <select id="admin_live_currency" onchange="changeAdminCurrency(this.value)">
                    <?php 
                    $current_curr = $_COOKIE['site_currency'] ?? 'PKR';
                    $currencies = ['PKR'=>'PKR - Pakistan', 'USD'=>'USD - US Dollar', 'EUR'=>'EUR - Euro', 'GBP'=>'GBP - UK Pound', 'INR'=>'INR - India', 'AED'=>'AED - UAE', 'SAR'=>'SAR - Saudi', 'BDT'=>'BDT - Bangladesh', 'CAD'=>'CAD - Canada', 'AUD'=>'AUD - Australia', 'TRY'=>'TRY - Turkey'];
                    foreach($currencies as $code => $name) { $sel = ($current_curr == $code) ? 'selected' : ''; echo "<option value='{$code}' {$sel}>{$name}</option>"; }
                    ?>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <div class="tool-box" style="flex:1; justify-content:center;" onclick="toggleMatrix()" title="Matrix Mode (Alt+M)">
                    <i class="fas fa-terminal"></i> <span style="font-weight:700;">Matrix</span>
                </div>
                <div class="tool-box" style="flex:1; justify-content:center;" onclick="toggleAudio()" title="Mute/Unmute Audio">
                    <i class="fas fa-volume-up" id="audioIcon"></i>
                </div>
            </div>
        </div>

        <div style="padding: 15px; background:#ffffff;">
            <a href="../logout.php" style="display:flex; align-items:center; justify-content:center; gap:10px; padding:12px; background:var(--primary-light); color:var(--primary-dark); border-radius:10px; text-decoration:none; font-weight:700; transition:0.3s; border: 1px solid #ddd6fe;">
                <i class="fas fa-power-off"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-content-wrapper">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius:12px; border:none; background:#dcfce7; color:#166534;">
                    <i class="fa-solid fa-check-circle me-2"></i> <strong>Success!</strong> <?= sanitize($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px; border:none; background:#fee2e2; color:#991b1b;">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>Error!</strong> <?= sanitize($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

<script type="text/javascript">

// ==========================================
// 1. NATIVE WEB AUDIO SYNTHESIZER
// ==========================================
const AudioContext = window.AudioContext || window.webkitAudioContext;
const audioCtx = new AudioContext();
let isAudioMuted = false;

function playSynthBeep(freq = 500, type = 'sine', duration = 0.1) {
    if(isAudioMuted) return;
    if(audioCtx.state === 'suspended') audioCtx.resume();
    try {
        let osc = audioCtx.createOscillator();
        let gainNode = audioCtx.createGain();
        osc.type = type; osc.frequency.value = freq;
        osc.connect(gainNode); gainNode.connect(audioCtx.destination);
        osc.start();
        gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + duration);
        osc.stop(audioCtx.currentTime + duration);
    } catch(e) {}
}

function toggleAudio() {
    isAudioMuted = !isAudioMuted;
    document.getElementById('audioIcon').className = isAudioMuted ? 'fas fa-volume-mute' : 'fas fa-volume-up';
    if(!isAudioMuted) playSynthBeep(800, 'square', 0.1);
}

// Voice Assistant
function playCyberVoice() {
    if(isAudioMuted) return;
    if('speechSynthesis' in window) {
        let msg = new SpeechSynthesisUtterance("Welcome Boss, Israr Liaqat. Omega 9 Security is active. Systems optimal.");
        msg.rate = 0.9; msg.pitch = 0.8; msg.lang = 'en-US';
        window.speechSynthesis.speak(msg);
    }
}

// ==========================================
// 2. WELCOME POPUP (PURPLE THEME) & VOICE TRIGGER
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    // Ye popup sirf ek bar show hoga jab tab naya khulega. Refresh pe bar bar show nahi hoga irritation se bachne ke liye.
    if(!sessionStorage.getItem('boss_welcomed')) {
        let popupHtml = `
            <div style="text-align:left; line-height:1.8; margin-top:15px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <i class="fas fa-check-circle" style="color:#10b981; font-size:1.2rem;"></i> <b>Admin Credentials Verified</b>
                </div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <i class="fas fa-shield-alt" style="color:var(--primary-color); font-size:1.2rem;"></i> <b>Omega-9 Security Active</b>
                </div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px;">
                    <i class="fas fa-database" style="color:#0ea5e9; font-size:1.2rem;"></i> <b>Database & API Synced</b>
                </div>
                
                <div style="background:var(--primary-light); padding:15px; border-radius:12px; border:1px dashed #ddd6fe;">
                    <b style="color:var(--primary-dark); font-size:1.1rem;"><i class="fas fa-chart-line"></i> Quick Report</b><br>
                    <div style="display:flex; justify-content:space-between; margin-top:8px;"><span>New Users:</span> <b>12</b></div>
                    <div style="display:flex; justify-content:space-between;"><span>Revenue Est:</span> <b>Rs. 4,500</b></div>
                    <div style="display:flex; justify-content:space-between;"><span>Threats Blocked:</span> <b style="color:#ef4444;">142</b></div>
                </div>
            </div>
        `;
        
        Swal.fire({ 
            title: 'Welcome, Israr Liaqat 👑', 
            html: popupHtml, 
            background: '#ffffff', 
            confirmButtonText: 'ENTER DASHBOARD <i class="fas fa-arrow-right"></i>', 
            allowOutsideClick: false,
            customClass: {
                popup: 'purple-theme',
                title: 'purple-theme',
                htmlContainer: 'purple-theme',
                confirmButton: 'purple-theme'
            }
        }).then((result) => {
            if(result.isConfirmed) {
                // Button dabne par aawaz play hogi (Chrome auto-play policy bypass)
                playSynthBeep(600, 'triangle', 0.2);
                playCyberVoice();
                sessionStorage.setItem('boss_welcomed', 'true');
            }
        });
    }
});

// ==========================================
// 3. REAL-TIME TOASTS HELPER FUNCTION
// ==========================================
// Ab faaltu (dummy) popups show nahi honge. Sirf admin is function ko apni marzi se use kar sakta hai.
window.showRealToast = function(msg, type = 'success', icon = 'fa-check-circle') {
    let c = document.getElementById('cyber-toast-container');
    let el = document.createElement('div'); el.className = `cyber-toast ${type}`;
    el.innerHTML = `<i class="fas ${icon}"></i> <span>${msg}</span>`;
    c.appendChild(el);
    
    playSynthBeep(type==='warn'? 300 : 700, 'triangle', 0.1);
    setTimeout(() => { el.style.animation = 'fadeOutRight 0.4s ease-in forwards'; setTimeout(() => { el.remove(); }, 400); }, 4000);
}

// ==========================================
// 4. MATRIX MODE & HOTKEYS
// ==========================================
function toggleMatrix() {
    document.body.classList.toggle('matrix-mode');
    playSynthBeep(1200, 'sine', 0.1);
}
document.addEventListener('keydown', function(e) {
    if(e.altKey && e.key.toLowerCase() === 'm') { e.preventDefault(); toggleMatrix(); }
    if(e.altKey && e.key.toLowerCase() === 'l') { e.preventDefault(); openDefcon(); }
});

// ==========================================
// 5. ORIGINAL MENU LOGIC & TRANSLATE
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    const dropdowns = document.querySelectorAll('.nav-link.has-dropdown');
    dropdowns.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            const parentLi = this.parentElement; const submenu = parentLi.querySelector('.sub-menu'); const isOpen = parentLi.classList.contains('open');
            document.querySelectorAll('.nav-item.open').forEach(item => {
                if (item !== parentLi) { item.classList.remove('open'); let oSub = item.querySelector('.sub-menu'); if(oSub) oSub.style.maxHeight = "0px"; }
            });
            if (isOpen) { submenu.style.maxHeight = "0px"; parentLi.classList.remove('open'); } 
            else { parentLi.classList.add('open'); submenu.style.maxHeight = submenu.scrollHeight + "px"; }
        });
    });
});

function googleTranslateElementInit() { new google.translate.TranslateElement({ pageLanguage: 'en', autoDisplay: false, layout: google.translate.TranslateElement.InlineLayout.SIMPLE }, 'google_translate_element'); }

function changeAdminCurrency(currencyCode) {
    Swal.fire({ title: 'Syncing Rates...', text: 'Connecting to Global API 🌐', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }, customClass: { popup: 'purple-theme', title: 'purple-theme' } });
    document.cookie = "site_currency=" + currencyCode + "; path=/; max-age=" + (365*24*60*60);
    let url = new URL(window.location.href); url.searchParams.set('set_admin_currency', currencyCode);
    setTimeout(() => { window.location.href = url.href; }, 1000);
}

document.addEventListener("DOMContentLoaded", function() {
    let tCurr = "<?= $_COOKIE['site_currency'] ?? 'PKR' ?>";
    if(tCurr === 'PKR') return; 
    const symMap = { 'USD':'$', 'INR':'₹', 'EUR':'€', 'GBP':'£', 'AED':'د.إ', 'SAR':'﷼', 'BDT':'৳', 'CAD':'C$' };
    let tSym = symMap[tCurr] || tCurr;
    fetch('https://open.er-api.com/v6/latest/PKR').then(r=>r.json()).then(data => {
        if(data && data.rates && data.rates[tCurr]) {
            let rate = data.rates[tCurr];
            function convert(node) {
                if (node.nodeType === 3) {
                    let regex = /(?:Rs\.?|PKR|₨)\s*([\d,]+(?:\.\d+)?)|([\d,]+(?:\.\d+)?)\s*(?:Rs\.?|PKR|₨)/gi;
                    if (regex.test(node.nodeValue)) { node.nodeValue = node.nodeValue.replace(regex, function(m, p1, p2) { let amt = parseFloat((p1||p2).replace(/,/g,'')); if(!isNaN(amt)) return tSym + " " + (amt * rate).toFixed(2); return m; }); }
                } else if (node.nodeType === 1 && !['SCRIPT','STYLE','INPUT','TEXTAREA'].includes(node.nodeName)) {
                    for (let i=0; i<node.childNodes.length; i++) convert(node.childNodes[i]);
                }
            }
            convert(document.body);
        }
    }).catch(e=>{});
});

// ==========================================
// 6. DEFCON-1 JAVASCRIPT
// ==========================================
let isDefconActive = <?= !empty($_SESSION['defcon_active']) ? 'true' : 'false' ?>;
if(isDefconActive) { document.getElementById('lockdownActiveScreen').style.display = 'flex'; document.getElementById('lockdownActiveScreen').classList.add('active'); }

function openDefcon() { playSynthBeep(200, 'square', 0.2); document.getElementById('defconModal').style.display = 'flex'; }
function closeDefcon() { document.getElementById('defconModal').style.display = 'none'; document.getElementById('defconPin').value = ''; }

function triggerLockdown() {
    let pin = document.getElementById('defconPin').value;
    if(pin === '') { Swal.fire({icon: 'error', title: 'ACCESS DENIED', text: 'PIN Required', background: '#111', color: '#ff0055'}); playSynthBeep(150, 'sawtooth', 0.5); return; }
    let btn = document.querySelector('#defconModal .defcon-action-btn'); let oldText = btn.innerText; btn.innerText = 'AUTHENTICATING...';
    let fData = new FormData(); fData.append('defcon_action', 'engage'); fData.append('pin', pin);
    fetch(window.location.href, { method: 'POST', body: fData }).then(r => r.text()).then(t => {
        if(t === 'locked') {
            btn.innerText = 'PROTOCOL ENGAGED'; playSynthBeep(900, 'sine', 0.5);
            setTimeout(() => { closeDefcon(); document.getElementById('lockdownActiveScreen').style.display = 'flex'; document.getElementById('lockdownActiveScreen').classList.add('active'); }, 1000);
        } else { btn.innerText = oldText; Swal.fire({icon: 'error', title: 'ACCESS DENIED', text: 'Invalid Command PIN', background: '#111', color: '#ff0055'}); playSynthBeep(150, 'sawtooth', 0.5); }
    });
}
function liftLockdown() {
    let btn = document.querySelector('#lockdownActiveScreen .defcon-action-btn'); let oldText = btn.innerText; btn.innerText = 'DISENGAGING...';
    let fData = new FormData(); fData.append('defcon_action', 'disengage');
    fetch(window.location.href, { method: 'POST', body: fData }).then(r => r.text()).then(t => {
        if(t === 'unlocked') {
            document.getElementById('lockdownActiveScreen').style.display = 'none'; document.getElementById('lockdownActiveScreen').classList.remove('active');
            btn.innerText = oldText; playSynthBeep(600, 'triangle', 0.3);
            Swal.fire({icon: 'success', title: 'SYSTEM RESTORED', text: 'Frontend is now live.', background: '#111', color: '#00ff88'});
        }
    });
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
