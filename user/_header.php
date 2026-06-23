<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🔥 SMART PUBLIC SEO EXCEPTION LIST 🔥
// In pages par login ka lock bypass ho jayega taake SEO/Guests inko access kar sakein
$current_page = basename($_SERVER['PHP_SELF']);
$public_seo_pages = ['index.php', 'services.php', 'faq.php', 'api_docs.php', 'about.php', 'contact.php', 'terms.php', 'blog.php', 'blog_post.php', 'products.php', 'store.php', 'product_details.php'];
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
if (isset($_SESSION['user_id'])) {
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

if (isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_wa_trap'])) {
        $phone_input = sanitize($_POST['wa_phone']);
        
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
$user_balance = isset($_SESSION['user_id']) ? getUserBalance($_SESSION['user_id']) : 0.00;
$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow';
$logo = $GLOBALS['settings']['site_logo'] ?? '';

// --- 💵 CURRENCY SETUP ---
$curr_list = function_exists('getCurrencyList') ? getCurrencyList() : ['PKR' => ['rate'=>1, 'symbol'=>'Rs', 'flag'=>'🇵🇰', 'name'=>'Pakistani Rupee']];
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
if (!isset($curr_list[$curr_code])) $curr_code = 'PKR';

$curr_data = $curr_list[$curr_code];
$curr_flag = $curr_data['flag'];

// --- 🤖 SEO LOADER ---
$stmt = $db->prepare("SELECT * FROM site_seo WHERE page_name = ?");
$stmt->execute([$current_page]);
$seo = $stmt->fetch();

$meta_title = !empty($seo['meta_title']) ? $seo['meta_title'] : ($GLOBALS['settings']['seo_title'] ?? $site_name);
$meta_desc = !empty($seo['meta_description']) ? $seo['meta_description'] : ($GLOBALS['settings']['seo_desc'] ?? '');

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR 🚀
// Yeh poori website ke header meta tags aur JSON schema ko control karega
if (file_exists(__DIR__ . '/../seo_auto_injector.php')) {
    require_once __DIR__ . '/../seo_auto_injector.php';
}

// ==========================================
// 🚀 DYNAMIC MENU FETCH (WITH SMART FILTER)
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
// 🔥 SMART FILTER: Ab yeh saari faltu chizein block kar dega har page par!
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
            --nav-h: 74px; 
            --radius: 50px; 
            --primary: #4f46e5; 
            --text-main: #111827; 
        }

        body {
            background-color: var(--app-bg);
            color: var(--app-text);
            font-family: 'Inter', sans-serif;
            margin: 0; padding: 0;
            overflow-x: hidden;
        }

        /* 🛡️ UNIVERSAL WRAPPER (Works for both SMM and Other Pages) */
        .smm-content-wrapper, .main-content-wrapper {
            padding: 10px 20px 100px 20px; 
            max-width: 1200px; margin: 0 auto;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn { from {opacity:0} to {opacity:1} }

        /* ==========================================
           🎨 THE ANIMATED PURPLE CRAZY SPIN HEADER
           ========================================== */
        .nav-spacer { height: 110px; }
        
        .neo-capsule {
            position: fixed; top: 15px; left: 50%; transform: translateX(-50%);
            width: 85%; max-width: 1200px;
            height: var(--nav-h);
            border-radius: var(--radius); 
            z-index: 1020; /* Fixed Z-Index */
            padding: 4px; 
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.15); 
            overflow: hidden; 
        }

        .purple-spin-bg {
            position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; 
            background: conic-gradient(from 0deg, #4f46e5, #7e22ce, #d946ef, #4f46e5, #8b5cf6, #4f46e5);
            animation: crazySpin 3s linear infinite; 
            z-index: 0;
        }
        @keyframes crazySpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .nav-inner {
            position: relative; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            border-radius: 44px; display: flex; align-items: center; justify-content: space-between;
            padding: 0 15px 0 28px; z-index: 1; 
        }

        .logo-area { flex-shrink: 0; display: flex; align-items: center; max-width: 40%; text-decoration: none; }
        .logo-area img { height: 48px; width: auto; object-fit: contain; transition: transform 0.3s; }
        .logo-area:hover img { transform: scale(1.05); }
        .logo-text { font-weight: 800; font-size: 1.5rem; color: var(--text-main); }

        .desk-menu { display: flex; align-items: center; gap: 8px; height: 100%; }
        .pill-link {
            text-decoration: none; color: #1f2937; font-size: 0.85rem; font-weight: 700;
            padding: 7px 14px; border-radius: 50px; display: flex; align-items: center; white-space: nowrap;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid transparent;
        }
        .pill-link.single:hover, .pill-link.single.active {
            background: #f3f4f6; color: var(--primary); transform: scale(1.08); 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.15); border-color: rgba(79, 70, 229, 0.1);
        }
        .pill-group {
            display: flex; align-items: center; background: transparent; border-radius: 50px; padding: 2px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid transparent;
        }
        .pill-group:hover, .pill-group.active-group {
            background: #f3f4f6; transform: scale(1.05); box-shadow: 0 4px 15px rgba(79, 70, 229, 0.15); border-color: rgba(79, 70, 229, 0.1);
        }
        .pill-group:hover .pill-link, .pill-group:hover .pill-trigger { color: var(--primary); }
        .pill-trigger { background: transparent; border: none; cursor: pointer; padding: 7px 10px 7px 5px; border-radius: 0 30px 30px 0; display: flex; align-items: center; }
        .pill-divider { width: 1px; height: 14px; background: #9ca3af; margin: 0 2px; }

        .drop-box {
            position: absolute; top: 140%; right: 0; width: 220px; background: #fff; border-radius: 16px; padding: 8px; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.15); display: none; animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            border: 1px solid rgba(0,0,0,0.05); z-index: 10002; max-height: 50vh; overflow-y: auto;
        }
        .drop-box.show { display: block; }
        .drop-box a { display: flex; align-items: center; padding: 10px 12px; color: #333; text-decoration: none; border-radius: 10px; font-size: 0.9rem; font-weight: 500; transition: 0.2s; }
        .drop-box a:hover { background: #f3f4f6; color: var(--primary); transform: translateX(5px); }

        .nav-right { display: flex; align-items: center; gap: 10px; margin-left: 20px; flex-shrink: 0; }
        .nav-btn { width: 44px; height: 44px; border-radius: 50%; background: #f3f4f6; border: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-decoration:none; color:inherit; }
        .nav-btn:hover { transform: scale(1.15) rotate(5deg); border-color: var(--primary); background: #fff; box-shadow: 0 5px 15px rgba(79, 70, 229, 0.2); }
        .hamburger { display: none; flex-direction: column; gap: 6px; background: none; border: none; cursor: pointer; width: 44px; height: 44px; justify-content: center; align-items: center; }
        .hamburger .bar { width: 22px; height: 2.5px; background: #111; border-radius: 2px; }

        /* Drawer */
        .drawer-panel { position: fixed; top: 0; right: 0; width: 320px; height: 100dvh; background: #fff; z-index: 100001; transform: translateX(100%); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: -10px 0 40px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .drawer-panel.open { transform: translateX(0); }
        .drawer-back { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 100000; opacity: 0; visibility: hidden; transition: 0.3s; backdrop-filter: blur(4px); }
        .drawer-back.open { opacity: 1; visibility: visible; }
        .drawer-top { flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; padding: 25px; border-bottom: 1px solid #f3f4f6; }
        .drawer-scroll-area { flex: 1; overflow-y: auto; padding: 20px; }
        .drawer-footer { flex-shrink: 0; padding: 20px 20px 40px 20px; border-top: 1px solid #f3f4f6; background: #fff; }
        .close-btn-styled { width: 40px; height: 40px; background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; transition: 0.3s; }
        .close-btn-styled:hover { transform: rotate(90deg) scale(1.1); }
        .mob-header { display: flex; align-items: center; background: #f9fafb; border-radius: 12px; padding-right: 5px; margin-bottom: 5px; }
        .mob-item { display: flex; align-items: center; padding: 14px 16px; margin-bottom: 6px; background: #f9fafb; border-radius: 14px; text-decoration: none; color: #1f2937; font-weight: 600; font-size: 1rem; }
        .mob-header .mob-item { background: transparent; margin-bottom: 0; }
        .mob-arrow { background: transparent; border: none; padding: 10px; font-size: 1rem; color: #6b7280; cursor:pointer; }
        .mob-sub { display: none; padding-left: 20px; border-left: 2px solid #eee; margin-top: 5px; margin-bottom: 10px; }
        .mob-sub-item { display: block; padding: 10px 14px; color: #4b5563; text-decoration: none; border-radius: 10px; }
        .logout-btn-styled { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 14px; background: #fee2e2; color: #b91c1c; border-radius: 14px; text-decoration: none; font-weight: 700; transition: 0.2s; }

        /* --- BULLETPROOF CURRENCY MODAL CSS --- */
        .currency-modal-supreme { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(12px); z-index: 999999 !important; align-items: center; justify-content: center; }
        .currency-modal-box { position: relative; width: 90%; max-width: 450px; background: rgba(255, 255, 255, 0.95); border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255,255,255,0.6); overflow: hidden; animation: zoomSpring 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .currency-glow { position: absolute; top: 0; left: 0; right: 0; height: 150px; background: radial-gradient(circle at 50% 0%, rgba(79, 70, 229, 0.15), transparent 70%); pointer-events: none; }
        .currency-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 30px 30px 10px 30px; position: relative; z-index: 2; }
        .currency-title { margin: 0; font-size: 1.5rem; font-weight: 800; color: #111; letter-spacing: -0.5px; }
        .currency-desc { margin: 5px 0 0 0; color: #6b7280; font-size: 0.9rem; }
        .currency-close { width: 36px; height: 36px; background: #f3f4f6; border: none; border-radius: 50%; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
        .currency-close:hover { background: #e5e7eb; transform: rotate(90deg); color: #000; }
        .currency-body { padding: 20px 30px 35px 30px; max-height: 60vh; overflow-y: auto; position: relative; z-index: 2; }
        .currency-grid { display: grid; gap: 12px; }
        .curr-card { display: flex; align-items: center; gap: 16px; padding: 16px 20px; background: #fff; border: 2px solid #e5e7eb; border-radius: 20px; cursor: pointer; transition: all 0.2s ease; }
        .curr-card:hover { border-color: #c7d2fe; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(0,0,0,0.05); }
        .curr-card.active { border-color: var(--primary); background: #eef2ff; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        .curr-flag { font-size: 1.8rem; line-height: 1; }
        .curr-details { flex: 1; display: flex; flex-direction: column; }
        .curr-code { font-weight: 800; color: #1f2937; font-size: 1.1rem; }
        .curr-name { font-size: 0.85rem; color: #6b7280; font-weight: 500; }
        .curr-check { font-size: 1.2rem; color: var(--primary); display: flex; align-items: center; }
        .curr-circle { width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 50%; }

        @media (max-width: 992px) { .desk-menu { display: none; } .hamburger { display: flex; } .neo-capsule { width: 90%; max-width: none; } .nav-inner { padding: 0 20px; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes zoomSpring { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }

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
                <img src="../assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-text">⚡ <?php echo sanitize($GLOBALS['settings']['site_name'] ?? 'SUBHUB'); ?></div>
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
                <a href="<?= $item['link'] ?>" class="mob-item <?= isActive($item['link'], $current_page) ?>">
                    <?= renderNavIcon($item['icon'], $item['icon_color']) ?> <?= $item['label'] ?>
                </a>
            <?php else: ?>
                <div class="mob-group">
                    <div class="mob-header">
                        <a href="<?= $item['link'] ?>" class="mob-item flex-grow-1 <?= isActive($item['link'], $current_page) ?>">
                            <?= renderNavIcon($item['icon'], $item['icon_color']) ?> <?= $item['label'] ?>
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