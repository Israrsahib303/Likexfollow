<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🔥 SMART PUBLIC SEO EXCEPTION LIST 🔥
// In pages par login ka lock bypass ho jayega taake SEO/Guests inko access kar sakein
$current_page = basename($_SERVER['PHP_SELF']);
$public_seo_pages = ['index.php', 'services.php', 'faq.php', 'api_docs.php', 'about.php', 'contact.php', 'terms.php', 'blog.php', 'blog_post.php', 'products.php', 'store.php', 'product_details.php', 'rent_panel.php'];
$is_public_page = in_array($current_page, $public_seo_pages);

// --- 🔒 0. SMART LOGIN CHECK ---
if (!isset($_SESSION['user_id']) && !$is_public_page) {
    header("Location: ../login.php");
    exit;
}

// --- 1. REQUIRED FILES ---
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php'; 
}

// Sirf tab requireLogin() chalao jab user dashboard mein ho, public SEO page par nahi
if (isset($_SESSION['user_id']) || !$is_public_page) {
    if(function_exists('requireLogin')) {
        requireLogin();
    }
}

// --- 🚫 1.5 REAL-TIME BAN CHECK ---
if (isset($_SESSION['user_id']) && isset($db)) {
    $chk_stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
    $chk_stmt->execute([$_SESSION['user_id']]);
    $uStatus = $chk_stmt->fetchColumn();

    if ($uStatus === 'banned') {
        session_unset();
        session_destroy();
        header("Location: ../login.php?banned=1"); 
        exit;
    }
}

// --- 📱 1.6 MANDATORY WHATSAPP COLLECTION TRAP ---
$show_wa_trap = false;
$wa_error = '';

if (isset($_SESSION['user_id']) && isset($db)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_wa_trap'])) {
        $phone_input = function_exists('sanitize') ? sanitize($_POST['wa_phone']) : trim($_POST['wa_phone']);
        
        if (!empty($phone_input) && strlen($phone_input) >= 10) {
            $upd = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $upd->execute([$phone_input, $_SESSION['user_id']]);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $wa_error = "Invalid Number! Please enter correct WhatsApp number.";
            $show_wa_trap = true;
        }
    }
    
    if (!$show_wa_trap) {
        $stmt_ph = $db->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt_ph->execute([$_SESSION['user_id']]);
        $uPhone = $stmt_ph->fetchColumn();

        if (empty($uPhone)) {
            $show_wa_trap = true;
        }
    }
}

// --- 💰 USER BALANCE & SITE DATA ---
$user_balance = (isset($_SESSION['user_id']) && function_exists('getUserBalance')) ? getUserBalance($_SESSION['user_id']) : 0.00;
$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow';
$logo = $GLOBALS['settings']['site_logo'] ?? '';

// --- 💵 CURRENCY SETUP ---
$curr_list = function_exists('getCurrencyList') ? getCurrencyList() : ['PKR' => ['rate'=>1, 'symbol'=>'Rs', 'flag'=>'🇵🇰', 'name'=>'Pakistani Rupee']];
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
if (!isset($curr_list[$curr_code])) $curr_code = 'PKR';

$curr_data = $curr_list[$curr_code];
$curr_flag = $curr_data['flag'];

// --- 🤖 SEO LOADER ---
$meta_title = $site_name;
$meta_desc = '';
if (isset($db)) {
    try {
        $stmt = $db->prepare("SELECT * FROM site_seo WHERE page_name = ?");
        $stmt->execute([$current_page]);
        $seo = $stmt->fetch();

        $meta_title = !empty($seo['meta_title']) ? $seo['meta_title'] : ($GLOBALS['settings']['seo_title'] ?? $site_name);
        $meta_desc = !empty($seo['meta_description']) ? $seo['meta_description'] : ($GLOBALS['settings']['seo_desc'] ?? '');
    } catch(Exception $e){}
}

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR 🚀
if (file_exists(__DIR__ . '/../seo_auto_injector.php')) {
    require_once __DIR__ . '/../seo_auto_injector.php';
}

// ==========================================
// 🚀 DYNAMIC MENU FETCH (WITH SMART INJECTIONS)
// ==========================================
function isActive($link, $current) {
    if ($link == '#' || empty($link)) return '';
    return (strpos($link, $current) !== false) ? 'active' : '';
}

function renderNavIcon($icon, $color) {
    if (empty($icon)) return '';
    $c = !empty($color) ? $color : '#374151'; 
    return "<i class='$icon' style='color: $c; margin-right: 6px; font-size: 1em;'></i>";
}

$menu_items = [];
$hide_links = [
    'p2p_trading.php', 
    'premium_store.php', 
    'downloads.php', 
    'ai_tools.php', 
    'sub_dashboard.php',
    'sub_orders.php',         
    'crypto_history.php',     
    'my_downloads.php',       
    'services.php',           
    'smm_services.php'        
]; 

if (isset($db)) {
    try {
        $raw_menus = $db->query("SELECT * FROM navigation WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
        $by_id = [];
        
        foreach ($raw_menus as $m) {
            if (in_array($m['link'], $hide_links)) continue;
            
            $m['children'] = [];
            $by_id[$m['id']] = $m;
        }
        
        foreach ($by_id as $id => $m) {
            if ($m['parent_id'] == 0) {
                $menu_items[$id] = &$by_id[$id];
            } else {
                if (isset($by_id[$m['parent_id']])) {
                    $by_id[$m['parent_id']]['children'][] = &$by_id[$id];
                } else {
                    $menu_items[$id] = &$by_id[$id];
                }
            }
        }
    } catch (Exception $e) {}
}

// 🔥 INJECTING MISSING VIP NAV OPTIONS SYSTEM-WIDE 🔥
$menu_items['rent_panel'] = [
    'id' => 'rent_panel',
    'parent_id' => 0,
    'label' => 'Rent Panel',
    'link' => 'rent_panel.php',
    'icon' => 'fas fa-rocket',
    'icon_color' => '#8b5cf6',
    'children' => []
];

$menu_items['smart_refill'] = [
    'id' => 'smart_refill',
    'parent_id' => 0,
    'label' => 'Refills',
    'link' => 'refills.php',
    'icon' => 'fas fa-arrows-rotate',
    'icon_color' => '#3b82f6',
    'children' => []
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <?php if (isset($beast_seo_injection)): ?>
        <?= $beast_seo_injection ?>
    <?php else: ?>
        <title><?= htmlspecialchars($meta_title) ?></title>
        <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <?php endif; ?>
    <link rel="shortcut icon" href="https://likexfollow.com/assets/img/favicon.jpg">
    <link rel="stylesheet" href="../assets/css/smm_style.css?v=4.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --app-primary: <?= $GLOBALS['settings']['theme_primary'] ?? '#4f46e5' ?>;
            --app-bg: <?= $GLOBALS['settings']['theme_bg'] ?? '#f8fafc' ?>;
            --app-text: <?= $GLOBALS['settings']['theme_text'] ?? '#1e293b' ?>;
            --nav-h: 72px; 
            --radius: 50px; 
            --primary: #4f46e5; 
            --text-main: #0f172a; 
        }

        body {
            background-color: var(--app-bg);
            color: var(--app-text);
            font-family: 'Inter', sans-serif;
            margin: 0; padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* 🛡️ UNIVERSAL CONTENT WRAPPER */
        .smm-content-wrapper, .main-content-wrapper {
            padding: 10px 20px 100px 20px; 
            max-width: 1240px; margin: 0 auto;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn { from {opacity:0; transform: translateY(6px);} to {opacity:1; transform: translateY(0);} }

        /* ==========================================
           🎨 THE ANIMATED PURPLE CRAZY SPIN HEADER
           ========================================== */
        .nav-spacer { height: 100px; }
        
        .neo-capsule {
            position: fixed; top: 15px; left: 50%; transform: translateX(-50%);
            width: 90%; max-width: 1240px;
            height: var(--nav-h);
            border-radius: var(--radius); 
            z-index: 1020; 
            padding: 3px; 
            box-shadow: 0 12px 35px rgba(79, 70, 229, 0.18); 
            overflow: hidden; 
            transition: all 0.3s ease;
        }

        .purple-spin-bg {
            position: absolute; top: -100%; left: -100%; width: 300%; height: 300%; 
            background: conic-gradient(from 0deg, #4f46e5, #7e22ce, #ec4899, #4f46e5, #8b5cf6, #4f46e5);
            animation: crazySpin 4s linear infinite; 
            z-index: 0;
        }
        @keyframes crazySpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .nav-inner {
            position: relative; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.92); 
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            border-radius: 46px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 12px 0 24px; z-index: 1; 
        }

        .logo-area { flex-shrink: 0; display: flex; align-items: center; max-width: 40%; text-decoration: none; }
        .logo-area img { height: 44px; width: auto; object-fit: contain; transition: transform 0.3s; }
        .logo-area:hover img { transform: scale(1.05); }
        .logo-text { font-weight: 800; font-size: 1.4rem; color: var(--text-main); letter-spacing: -0.5px; }

        .desk-menu { display: flex; align-items: center; gap: 6px; height: 100%; }
        .pill-link {
            text-decoration: none; color: #334155; font-size: 0.85rem; font-weight: 700;
            padding: 8px 14px; border-radius: 50px; display: flex; align-items: center; white-space: nowrap;
            transition: all 0.25s ease; border: 1px solid transparent;
        }
        .pill-link.single:hover, .pill-link.single.active {
            background: #f1f5f9; color: var(--primary); transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12); border-color: rgba(79, 70, 229, 0.15);
        }
        .pill-group {
            position: relative;
            display: flex; align-items: center; background: transparent; border-radius: 50px; padding: 2px;
            transition: all 0.25s ease; border: 1px solid transparent;
        }
        .pill-group:hover, .pill-group.active-group {
            background: #f1f5f9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12); border-color: rgba(79, 70, 229, 0.15);
        }
        .pill-group:hover .pill-link, .pill-group:hover .pill-trigger { color: var(--primary); }
        .pill-trigger { background: transparent; border: none; cursor: pointer; padding: 8px 10px 8px 4px; border-radius: 0 30px 30px 0; display: flex; align-items: center; color: #64748b; }
        .pill-divider { width: 1px; height: 14px; background: #cbd5e1; margin: 0 2px; }

        .drop-box {
            position: absolute; top: 130%; right: 0; width: 220px; background: #ffffff; border-radius: 16px; padding: 8px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.12); display: none; animation: slideUp 0.25s ease; 
            border: 1px solid rgba(0,0,0,0.06); z-index: 10002; max-height: 50vh; overflow-y: auto;
        }
        .drop-box.show { display: block; }
        .drop-box a { display: flex; align-items: center; padding: 10px 12px; color: #334155; text-decoration: none; border-radius: 10px; font-size: 0.88rem; font-weight: 600; transition: 0.2s; }
        .drop-box a:hover { background: #f8fafc; color: var(--primary); transform: translateX(4px); }

        .nav-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .nav-btn { width: 42px; height: 42px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.25s ease; text-decoration:none; color:inherit; }
        .nav-btn:hover { transform: scale(1.08); border-color: var(--primary); background: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15); }
        .hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; width: 42px; height: 42px; justify-content: center; align-items: center; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; }
        .hamburger .bar { width: 20px; height: 2px; background: #0f172a; border-radius: 2px; transition: 0.3s; }

        /* Drawer */
        .drawer-panel { position: fixed; top: 0; right: 0; width: 310px; height: 100dvh; background: #ffffff; z-index: 100001; transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: -10px 0 40px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .drawer-panel.open { transform: translateX(0); }
        .drawer-back { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); z-index: 100000; opacity: 0; visibility: hidden; transition: 0.3s; backdrop-filter: blur(4px); }
        .drawer-back.open { opacity: 1; visibility: visible; }
        .drawer-top { flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #f1f5f9; }
        .drawer-scroll-area { flex: 1; overflow-y: auto; padding: 15px; }
        .drawer-footer { flex-shrink: 0; padding: 15px 20px 30px 20px; border-top: 1px solid #f1f5f9; background: #fff; }
        .close-btn-styled { width: 36px; height: 36px; background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer; transition: 0.2s; }
        .close-btn-styled:hover { transform: rotate(90deg); }
        .mob-header { display: flex; align-items: center; background: #f8fafc; border-radius: 12px; padding-right: 5px; margin-bottom: 6px; }
        .mob-item { display: flex; align-items: center; padding: 12px 14px; margin-bottom: 6px; background: #f8fafc; border-radius: 12px; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 0.95rem; transition: 0.2s; }
        .mob-item.mob-active { background: rgba(79, 70, 229, 0.1); color: var(--primary); }
        .mob-header .mob-item { background: transparent; margin-bottom: 0; }
        .mob-arrow { background: transparent; border: none; padding: 10px; font-size: 0.9rem; color: #64748b; cursor: pointer; }
        .mob-sub { display: none; padding-left: 15px; border-left: 2px solid #f1f5f9; margin-top: 4px; margin-bottom: 8px; }
        .mob-sub-item { display: block; padding: 10px 12px; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; }
        .logout-btn-styled { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; background: #fee2e2; color: #b91c1c; border-radius: 12px; text-decoration: none; font-weight: 700; transition: 0.2s; }

        /* --- BULLETPROOF CURRENCY MODAL CSS --- */
        .currency-modal-supreme { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 999999 !important; align-items: center; justify-content: center; }
        .currency-modal-box { position: relative; width: 90%; max-width: 420px; background: #ffffff; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255,255,255,0.8); overflow: hidden; animation: zoomSpring 0.3s ease; }
        .currency-glow { position: absolute; top: 0; left: 0; right: 0; height: 120px; background: radial-gradient(circle at 50% 0%, rgba(79, 70, 229, 0.12), transparent 70%); pointer-events: none; }
        .currency-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 25px 25px 10px 25px; position: relative; z-index: 2; }
        .currency-title { margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .currency-desc { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; }
        .currency-close { width: 32px; height: 32px; background: #f1f5f9; border: none; border-radius: 50%; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
        .currency-close:hover { background: #e2e8f0; transform: rotate(90deg); }
        .currency-body { padding: 15px 25px 25px 25px; max-height: 55vh; overflow-y: auto; position: relative; z-index: 2; }
        .currency-grid { display: grid; gap: 10px; }
        .curr-card { display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: #fff; border: 2px solid #e2e8f0; border-radius: 16px; cursor: pointer; transition: all 0.2s ease; }
        .curr-card:hover { border-color: #c7d2fe; transform: translateY(-1px); }
        .curr-card.active { border-color: var(--primary); background: #eef2ff; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .curr-flag { font-size: 1.6rem; line-height: 1; }
        .curr-details { flex: 1; display: flex; flex-direction: column; }
        .curr-code { font-weight: 800; color: #0f172a; font-size: 1rem; }
        .curr-name { font-size: 0.8rem; color: #64748b; font-weight: 500; }
        .curr-check { font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; }
        .curr-circle { width: 18px; height: 18px; border: 2px solid #cbd5e1; border-radius: 50%; }

        @media (max-width: 992px) { .desk-menu { display: none; } .hamburger { display: flex; } .neo-capsule { width: 92%; } .nav-inner { padding: 0 15px; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes zoomSpring { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

        /* --- 🚨 WHATSAPP TRAP CSS --- */
        <?php if($show_wa_trap): ?>
        body { overflow: hidden !important; height: 100vh; }
        .wa-trap-overlay { position: fixed; inset: 0; background: rgba(255, 255, 255, 0.6); backdrop-filter: blur(20px); z-index: 999999; display: flex; align-items: center; justify-content: center; }
        .wa-trap-modal { background: white; width: 90%; max-width: 420px; padding: 40px 30px; border-radius: 24px; text-align: center; box-shadow: 0 30px 60px -15px rgba(0,0,0,0.15); animation: bounceIn 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
        .wt-input { width: 100%; padding: 16px; border: 2px solid #cbd5e1; border-radius: 14px; font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; text-align: center; }
        .wt-btn { width: 100%; padding: 16px; background: #25D366; color: white; font-weight: 700; border: none; border-radius: 14px; cursor: pointer; font-size: 1rem; }
        <?php endif; ?>
    </style>
</head>
<body class="smm-app-theme">

<?php if($show_wa_trap): ?>
    <div class="wa-trap-overlay">
        <div class="wa-trap-modal">
            <h2 style="margin-bottom:10px;">Verify WhatsApp!</h2>
            <p style="color:#64748b; margin-bottom:20px;">Please enter your WhatsApp Number to activate account.</p>
            <form method="POST">
                <input type="hidden" name="submit_wa_trap" value="1">
                <input type="text" name="wa_phone" class="wt-input" placeholder="e.g. +92 300 1234567" required>
                <button type="submit" class="wt-btn">Save & Continue</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="nav-spacer"></div>

<nav class="neo-capsule">
    <div class="purple-spin-bg"></div>
    
    <div class="nav-inner">
        <a href="smm_order.php" class="logo-area">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="../assets/img/<?php echo function_exists('sanitize') ? sanitize($GLOBALS['settings']['site_logo']) : $GLOBALS['settings']['site_logo']; ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-text">⚡ <?php echo function_exists('sanitize') ? sanitize($GLOBALS['settings']['site_name'] ?? 'SUBHUB') : ($GLOBALS['settings']['site_name'] ?? 'SUBHUB'); ?></div>
            <?php endif; ?>
        </a>

        <div class="desk-menu">
            <?php foreach($menu_items as $item): ?>
                <?php if (empty($item['children'])): ?>
                    <a href="<?= $item['link'] ?>" class="pill-link single <?= isActive($item['link'], $current_page) ?>">
                        <?= renderNavIcon($item['icon'], $item['icon_color']) ?>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php else: ?>
                    <div class="pill-group <?= isActive($item['link'], $current_page) ? 'active-group' : '' ?>">
                        <a href="<?= $item['link'] ?>" class="pill-link group-main">
                            <?= renderNavIcon($item['icon'], $item['icon_color']) ?>
                            <span><?= $item['label'] ?></span>
                        </a>
                        <div class="pill-divider"></div>
                        <button class="pill-trigger" onclick="toggleDesktopDrop('drop-<?= $item['id'] ?>', event)">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="drop-<?= $item['id'] ?>" class="drop-box">
                            <?php foreach($item['children'] as $child): ?>
                                <a href="<?= $child['link'] ?>">
                                    <?= renderNavIcon($child['icon'], $child['icon_color']) ?>
                                    <?= $child['label'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="nav-right">
            <div class="nav-btn curr-btn" onclick="showCurrencyPopupBox()" title="Currency">
                <span class="fs-5" style="line-height:1;"><?= $curr_flag ?></span>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="nav-btn icon-btn" title="Profile">
                <i class="fas fa-user-circle" style="font-size: 1.6rem; color: #4f46e5;"></i>
            </a>
            <?php else: ?>
            <a href="login.php" class="nav-btn icon-btn" title="Login" style="background: #eef2ff;">
                <i class="fas fa-sign-in-alt" style="font-size: 1.3rem; color: #4f46e5;"></i>
            </a>
            <?php endif; ?>

            <button class="hamburger" id="hamBtn">
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </button>
        </div>
    </div>
</nav>

<div class="drawer-back" id="drawerBack"></div>
<div class="drawer-panel" id="drawerPanel">
    <div class="drawer-top">
        <h3 class="m-0 fw-bold">Menu</h3>
        <button id="closeDrawer" class="close-btn-styled"><i class="fas fa-times"></i></button>
    </div>
    <div class="drawer-scroll-area">
        <?php foreach($menu_items as $item): ?>
            <?php if(empty($item['children'])): ?>
                <a href="<?= $item['link'] ?>" class="mob-item <?= isActive($item['link'], $current_page) ? 'mob-active' : '' ?>">
                    <?= renderNavIcon($item['icon'], $item['icon_color']) ?> <span><?= $item['label'] ?></span>
                </a>
            <?php else: ?>
                <div class="mob-group">
                    <div class="mob-header">
                        <a href="<?= $item['link'] ?>" class="mob-item flex-grow-1 <?= isActive($item['link'], $current_page) ? 'mob-active' : '' ?>">
                            <?= renderNavIcon($item['icon'], $item['icon_color']) ?> <span><?= $item['label'] ?></span>
                        </a>
                        <button class="mob-arrow" onclick="toggleMobileSub('sub-<?= $item['id'] ?>', this)">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div id="sub-<?= $item['id'] ?>" class="mob-sub">
                        <?php foreach($item['children'] as $child): ?>
                            <a href="<?= $child['link'] ?>" class="mob-sub-item">
                                <?= renderNavIcon($child['icon'], $child['icon_color']) ?> <?= $child['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="drawer-footer">
        <a href="../logout.php" class="logout-btn-styled">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    <?php else: ?>
    <div class="drawer-footer">
        <a href="login.php" class="logout-btn-styled" style="background: #eef2ff; color: var(--primary);">
            <i class="fas fa-sign-in-alt"></i> Login to Account
        </a>
    </div>
    <?php endif; ?>
</div>

<div id="uniqueCurrencyPopup" class="currency-modal-supreme" onclick="if(event.target===this) hideCurrencyPopupBox()">
    <div class="currency-modal-box">
        <div class="currency-glow"></div>
        <div class="currency-header">
            <div>
                <h2 class="currency-title">Currency</h2>
                <p class="currency-desc">Select your preferred currency</p>
            </div>
            <button onclick="hideCurrencyPopupBox()" class="currency-close">&times;</button>
        </div>
        <div class="currency-body custom-scrollbar">
            <div class="currency-grid">
                <?php foreach($curr_list as $code => $c): ?>
                <div class="curr-card <?= ($code == $curr_code) ? 'active' : '' ?>" onclick="updateSiteCurrency('<?= $code ?>')">
                    <div class="curr-flag"><?= $c['flag'] ?></div>
                    <div class="curr-details">
                        <span class="curr-code"><?= $code ?></span>
                        <span class="curr-name"><?= $c['name'] ?></span>
                    </div>
                    <div class="curr-check">
                        <?php if($code == $curr_code): ?> 
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <div class="curr-circle"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Mobile Drawer JS
const ham = document.getElementById('hamBtn'); 
const panel = document.getElementById('drawerPanel'); 
const back = document.getElementById('drawerBack'); 
const closeD = document.getElementById('closeDrawer');

function toggleDrawer() { 
    const isOpen = panel.classList.contains('open'); 
    if(isOpen) { 
        panel.classList.remove('open'); back.classList.remove('open'); 
        document.body.style.overflow = ''; 
    } else { 
        panel.classList.add('open'); back.classList.add('open'); 
        document.body.style.overflow = 'hidden'; 
    } 
}
if(ham) ham.addEventListener('click', toggleDrawer); 
if(closeD) closeD.addEventListener('click', toggleDrawer); 
if(back) back.addEventListener('click', toggleDrawer);

// Dropdown JS
function toggleDesktopDrop(id, e) {
    e.stopPropagation();
    const box = document.getElementById(id);
    const wasOpen = box.classList.contains('show');
    document.querySelectorAll('.drop-box').forEach(b => b.classList.remove('show'));
    if (!wasOpen) box.classList.add('show');
}
document.addEventListener('click', () => { document.querySelectorAll('.drop-box').forEach(b => b.classList.remove('show')); });

function toggleMobileSub(id, btn) {
    const sub = document.getElementById(id);
    const icon = btn.querySelector('i');
    if(sub.style.display === 'block') { sub.style.display = 'none'; icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); } 
    else { sub.style.display = 'block'; icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
}

// 🔥 GLOBALLY ACCESSIBLE UNIQUE CURRENCY JS 🔥
window.showCurrencyPopupBox = function() { 
    document.getElementById('uniqueCurrencyPopup').style.display = 'flex'; 
};
window.hideCurrencyPopupBox = function() { 
    document.getElementById('uniqueCurrencyPopup').style.display = 'none'; 
};
window.updateSiteCurrency = function(code) { 
    document.cookie = "site_currency=" + code + "; path=/; max-age=" + (30*24*60*60); 
    location.reload(); 
};
</script>

<div class="main-content-wrapper smm-content-wrapper">
