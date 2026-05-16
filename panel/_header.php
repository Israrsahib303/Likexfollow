<?php
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
        'color' => '#6366f1', 
        'children' => []
    ],
    [
        'label' => 'SMM Panel',
        'link' => '#',
        'icon' => 'fa-rocket',
        'color' => '#ec4899', 
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
        'color' => '#3b82f6', 
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
        'color' => '#f59e0b', 
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
        'color' => '#10b981', 
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
        'color' => '#ef4444', 
        'children' => [
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
        'color' => '#8b5cf6', 
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
        'color' => '#14b8a6', 
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
        'color' => '#64748b', 
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
        'color' => '#0f172a', 
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
        :root { --primary-color: #4f46e5; --sidebar-width: 280px; }
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* SIDEBAR STYLES */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 1000;
            display: flex; flex-direction: column; transition: transform 0.3s ease;
            box-shadow: 4px 0 15px rgba(0,0,0,0.02);
        }
        .sidebar-brand {
            padding: 20px 25px; font-size: 1.4rem; font-weight: 800; color: var(--primary-color);
            border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;
            background: linear-gradient(to right, #ffffff, #f8fafc);
        }

        .nav-links { list-style: none; padding: 15px 10px; margin: 0; flex: 1; overflow-y: auto; }
        .nav-item { position: relative; margin-bottom: 4px; }

        .nav-link {
            color: #475569; padding: 12px 18px; font-weight: 600; display: flex; align-items: center; gap: 12px;
            transition: all 0.2s; border-radius: 12px; text-decoration: none; cursor: pointer; font-size: 0.92rem;
        }
        .nav-link:hover { background-color: #f1f5f9; color: var(--primary-color); transform: translateX(4px); }
        .nav-link.active { background-color: #eef2ff; color: var(--primary-color); box-shadow: inset 3px 0 0 var(--primary-color); }
        
        .nav-link i.icon { width: 24px; text-align: center; font-size: 1.1rem; transition: 0.3s; }
        .nav-link:hover i.icon { transform: scale(1.1); }
        
        .arrow { margin-left: auto; transition: transform 0.3s ease; font-size: 0.8rem; opacity: 0.5; }
        
        .nav-item.open .arrow { transform: rotate(180deg); }
        .nav-item.open > .nav-link { color: var(--primary-color); background: #f8fafc; }
        
        .sub-menu {
            list-style: none; padding: 5px 0 5px 10px; 
            background: #ffffff; overflow: hidden; 
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 0px; display: block;
            margin-left: 15px; border-left: 2px solid #e2e8f0;
        }
        
        .sub-menu li a {
            display: flex; align-items: center; padding: 10px 15px; font-size: 0.85rem; color: #64748b;
            text-decoration: none; transition: 0.2s; border-radius: 8px; margin-bottom: 2px;
        }
        .sub-menu li a i { font-size: 0.8rem; width: 20px; text-align: center; margin-right: 8px; opacity: 0.7; }
        .sub-menu li a:hover, .sub-menu li a.active { color: var(--primary-color); background: #f8fafc; font-weight: 700; }
        .sub-menu li a.active { background: #eef2ff; }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* 🔥 TOOLS AREA INSIDE SIDEBAR 🔥 */
        .sidebar-tools {
            padding: 15px; background: #f8fafc; border-top: 1px solid #e2e8f0;
            display: flex; flex-direction: column; gap: 10px;
        }
        
        .tool-box {
            display: flex; align-items: center; gap: 10px;
            background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px;
            padding: 8px 12px; font-size: 0.85rem;
        }
        
        .tool-box i { color: var(--primary-color); font-size: 1rem; width: 20px; text-align: center; }
        
        .tool-box select {
            border: none; outline: none; background: transparent; width: 100%;
            font-weight: 700; color: #334155; cursor: pointer;
        }
        
        /* Google Translate Widget Overrides */
        .goog-te-combo { border: none !important; outline: none !important; background: transparent !important; width: 100%; font-weight: 700; color: #334155; cursor: pointer; }
        .goog-logo-link { display:none !important; } 
        .goog-te-gadget { color: transparent !important; font-size: 0px !important; }
        .goog-te-banner-frame { display:none !important; }
        body { top: 0px !important; }

        /* MAIN CONTENT AREA */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 0; 
            transition: 0.3s; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
        }
        
        .page-content-wrapper { padding: 30px; flex: 1; }

        /* MOBILE */
        .mobile-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; border-radius: 50%; width: 45px; height: 45px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: var(--primary-color); border:none; color:white; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.show { transform: translateX(0); box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: flex; align-items: center; justify-content: center; }
            .page-content-wrapper { padding: 15px; padding-top: 70px; } 
        }
    </style>
</head>
<body>

    <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('show')"><i class="fas fa-bars"></i></button>

    <div class="sidebar">
        <div class="sidebar-brand">
            <div style="width:38px; height:38px; background:var(--primary-color); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.2rem; box-shadow: 0 4px 10px rgba(79,70,229,0.3);">
                <i class="fas fa-bolt"></i>
            </div>
            <span>Beast Panel</span>
        </div>

        <ul class="nav-links">
            <?php foreach ($master_menu as $menu): 
                $children = $menu['children'] ?? [];
                $has_children = !empty($children);
                
                // Active Page Logic Check
                $is_active = ($menu['link'] == $current_page);
                $child_active = false;
                
                if ($has_children) {
                    foreach ($children as $c) {
                        if ($c['link'] == $current_page) { $child_active = true; break; }
                    }
                }
                
                $menu_link = $has_children ? 'javascript:void(0);' : $menu['link'];
                $toggle_class = $has_children ? 'has-dropdown' : '';
                $color_style = isset($menu['color']) ? "color: {$menu['color']};" : "";
            ?>
            
            <li class="nav-item <?= $child_active ? 'open' : '' ?>">
                <a href="<?= $menu_link ?>" class="nav-link <?= ($is_active || $child_active) ? 'active' : '' ?> <?= $toggle_class ?>">
                    <i class="fa-solid <?= $menu['icon'] ?> icon" style="<?= $color_style ?>"></i>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                    <?php if ($has_children): ?>
                        <i class="fa-solid fa-chevron-down arrow"></i>
                    <?php endif; ?>
                </a>

                <?php if ($has_children): ?>
                <ul class="sub-menu" style="max-height: <?= $child_active ? '1000px' : '0px' ?>;">
                    <?php foreach ($children as $sub): 
                        $sub_active = ($sub['link'] == $current_page) ? 'active' : '';
                    ?>
                    <li>
                        <a href="<?= $sub['link'] ?>" class="<?= $sub_active ?>">
                            <i class="fa-solid <?= $sub['icon'] ?>"></i>
                            <?= htmlspecialchars($sub['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="sidebar-tools">
            <div class="tool-box">
                <i class="fas fa-language"></i>
                <div id="google_translate_element" style="width:100%; overflow:hidden;"></div>
            </div>
            
            <div class="tool-box">
                <i class="fas fa-coins"></i>
                <select id="admin_live_currency" onchange="changeAdminCurrency(this.value)">
                    <?php 
                    $current_curr = $_COOKIE['site_currency'] ?? 'PKR';
                    // List of Top/Common World Currencies
                    $currencies = [
                        'PKR' => 'PKR - Pakistan', 'USD' => 'USD - US Dollar', 'INR' => 'INR - India',
                        'EUR' => 'EUR - Euro', 'GBP' => 'GBP - UK Pound', 'AED' => 'AED - UAE',
                        'SAR' => 'SAR - Saudi', 'BDT' => 'BDT - Bangladesh', 'CAD' => 'CAD - Canada',
                        'AUD' => 'AUD - Australia', 'TRY' => 'TRY - Turkey', 'CNY' => 'CNY - China',
                        'JPY' => 'JPY - Japan', 'MYR' => 'MYR - Malaysia', 'IDR' => 'IDR - Indonesia',
                        'ZAR' => 'ZAR - S. Africa', 'BRL' => 'BRL - Brazil', 'RUB' => 'RUB - Russia',
                        'AFN' => 'AFN - Afghan', 'NPR' => 'NPR - Nepal', 'LKR' => 'LKR - Sri Lanka',
                        'KWD' => 'KWD - Kuwait', 'QWD' => 'QWD - Qatar', 'OMR' => 'OMR - Oman'
                    ];
                    foreach($currencies as $code => $name) {
                        $sel = ($current_curr == $code) ? 'selected' : '';
                        echo "<option value='{$code}' {$sel}>{$name}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div style="padding: 15px; border-top: 1px solid #e2e8f0; background:#ffffff;">
            <a href="../logout.php" style="display:flex; align-items:center; justify-content:center; gap:10px; padding:12px; background:#fee2e2; color:#b91c1c; border-radius:10px; text-decoration:none; font-weight:700; transition:0.3s; border: 1px solid #fca5a5;">
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
// 1. Sidebar Dropdown Accordion
document.addEventListener("DOMContentLoaded", function() {
    const dropdowns = document.querySelectorAll('.nav-link.has-dropdown');
    dropdowns.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            const parentLi = this.parentElement;
            const submenu = parentLi.querySelector('.sub-menu');
            const isOpen = parentLi.classList.contains('open');

            document.querySelectorAll('.nav-item.open').forEach(item => {
                if (item !== parentLi) {
                    item.classList.remove('open');
                    const otherSub = item.querySelector('.sub-menu');
                    if(otherSub) otherSub.style.maxHeight = "0px";
                }
            });

            if (isOpen) {
                submenu.style.maxHeight = "0px"; parentLi.classList.remove('open');
            } else {
                parentLi.classList.add('open'); submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        });
    });
});

// 2. Google Translate Live Script Init
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
      pageLanguage: 'en', 
      autoDisplay: false,
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE
  }, 'google_translate_element');
}

// 3. ✨ NEW: LIVE API DOM SCANNER FOR CURRENCY (Never Fake Again) ✨
function changeAdminCurrency(currencyCode) {
    // Elegant Loading Popup
    Swal.fire({
        title: 'Syncing Exchange Rates...',
        html: 'Connecting to Global API for ' + currencyCode + ' 🌐',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    document.cookie = "site_currency=" + currencyCode + "; path=/; max-age=" + (365*24*60*60);
    
    let url = new URL(window.location.href);
    url.searchParams.set('set_admin_currency', currencyCode);
    
    // Thoda delay taake user loading effect feel kare
    setTimeout(() => { window.location.href = url.href; }, 1000);
}

document.addEventListener("DOMContentLoaded", function() {
    // Current Target Currency from Cookie
    let targetCurrency = "<?= $_COOKIE['site_currency'] ?? 'PKR' ?>";
    
    // Agar PKR (Base) hai toh conversion mat chalao
    if(targetCurrency === 'PKR') return; 

    // Symbols Dictionary
    const symMap = { 'USD':'$', 'INR':'₹', 'EUR':'€', 'GBP':'£', 'AED':'د.إ', 'SAR':'﷼', 'BDT':'৳', 'CAD':'C$', 'AUD':'A$', 'TRY':'₺', 'CNY':'¥', 'JPY':'¥', 'MYR':'RM', 'IDR':'Rp', 'ZAR':'R', 'BRL':'R$', 'RUB':'₽', 'AFN':'؋', 'NPR':'NPR', 'LKR':'LKR', 'KWD':'د.ك', 'QWD':'ر.ق', 'OMR':'ر.ع.' };
    let targetSym = symMap[targetCurrency] || targetCurrency;

    // Fetch Live Rates from API (PKR as base)
    fetch('https://open.er-api.com/v6/latest/PKR')
    .then(res => res.json())
    .then(data => {
        if(data && data.rates && data.rates[targetCurrency]) {
            let rate = data.rates[targetCurrency];
            
            // DOM Walker: Har text ko scan karega
            function convertTextNodes(node) {
                if (node.nodeType === 3) {
                    let text = node.nodeValue;
                    // Regex: "Rs 500", "Rs.500", "PKR 500", "500 PKR", "500 Rs" pakrega
                    let regex = /(?:Rs\.?|PKR|₨)\s*([\d,]+(?:\.\d+)?)|([\d,]+(?:\.\d+)?)\s*(?:Rs\.?|PKR|₨)/gi;
                    
                    if (regex.test(text)) {
                        let newText = text.replace(regex, function(match, p1, p2) {
                            let amountStr = p1 || p2;
                            let amount = parseFloat(amountStr.replace(/,/g, '')); // Comma hatao
                            if(!isNaN(amount)) {
                                let converted = (amount * rate).toFixed(2);
                                return targetSym + " " + converted;
                            }
                            return match;
                        });
                        node.nodeValue = newText;
                    }
                } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE' && node.nodeName !== 'INPUT' && node.nodeName !== 'TEXTAREA') {
                    for (let i = 0; i < node.childNodes.length; i++) {
                        convertTextNodes(node.childNodes[i]);
                    }
                }
            }
            
            // Chalo poori page scan karte hain!
            convertTextNodes(document.body);
        }
    }).catch(err => console.log("Currency API Error: ", err));
});
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
