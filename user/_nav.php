<?php
// _nav.php - Purple Crazy Spin & Glass Header (Ultra UI/UX Optimized)
$current_page = basename($_SERVER['PHP_SELF']);

function isActive($link, $current) {
    if ($link == '#' || empty($link)) return '';
    return (strpos($link, $current) !== false) ? 'active' : '';
}

// Currency Logic
$curr_list = function_exists('getCurrencyList') ? getCurrencyList() : ['PKR' => ['rate'=>1, 'symbol'=>'Rs', 'flag'=>'🇵🇰', 'name'=>'Pakistani Rupee']];
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
if (!isset($curr_list[$curr_code])) $curr_code = 'PKR';
$curr_flag = $curr_list[$curr_code]['flag'];

// --- MENU DATA FETCH ---
$menu_items = [];
try {
    if (isset($db)) {
        $raw_menus = $db->query("SELECT * FROM navigation WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
        $by_id = [];
        foreach ($raw_menus as $m) {
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
    }
} catch (Exception $e) {}

// --- INJECTED SYSTEM FEATURES (No DB alterations needed) ---
// 1. Rent Panel Button
$menu_items['rent_panel'] = [
    'id' => 'rent_panel',
    'parent_id' => 0,
    'label' => 'Rent Panel',
    'link' => 'rent_panel.php',
    'icon' => 'fas fa-rocket',
    'icon_color' => '#8b5cf6',
    'children' => []
];

// 2. Smart Refill Button
$menu_items['smart_refill'] = [
    'id' => 'smart_refill',
    'parent_id' => 0,
    'label' => 'Refills',
    'link' => 'refills.php',
    'icon' => 'fas fa-arrows-rotate',
    'icon_color' => '#3b82f6',
    'children' => []
];
// -------------------------------------------------------------

// Icon Helper
function renderNavIcon($icon, $color) {
    if (empty($icon)) return '';
    $c = !empty($color) ? $color : '#374151'; 
    return "<i class='$icon' style='color: $c; margin-right: 6px; font-size: 1em;'></i>";
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<div class="nav-spacer"></div>

<nav class="neo-capsule">
    <div class="purple-spin-bg"></div>
    
    <div class="nav-inner">
        <a href="index.php" class="logo-area">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="../assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-text">⚡ <?php echo function_exists('sanitize') ? sanitize($GLOBALS['settings']['site_name'] ?? 'LIKEXFOLLOW') : ($GLOBALS['settings']['site_name'] ?? 'LIKEXFOLLOW'); ?></div>
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
            <div class="nav-btn curr-btn" onclick="openModal()" title="Select Currency">
                <span class="fs-5" style="line-height:1;"><?= $curr_flag ?></span>
            </div>
            <a href="profile.php" class="nav-btn icon-btn" title="My Profile">
                <i class="fas fa-user-circle" style="font-size: 1.6rem; color: #4f46e5;"></i>
            </a>
            <button class="hamburger" id="hamBtn" aria-label="Open Menu">
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </button>
        </div>
    </div>
</nav>

<!-- MOBILE DRAWER PANEL -->
<div class="drawer-back" id="drawerBack"></div>
<div class="drawer-panel" id="drawerPanel">
    <div class="drawer-top">
        <h3 class="m-0 fw-bold" style="font-size:1.3rem; color:var(--text-main);">Menu</h3>
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
                        <a href="<?= $item['link'] ?>" class="mob-item flex-grow-1">
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
    <div class="drawer-footer">
        <a href="../logout.php" class="logout-btn-styled">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<!-- CURRENCY SELECTION MODAL -->
<div id="currModal" class="modal-overlay" onclick="if(event.target===this) closeModal()">
    <div class="modal-card ultra-modal">
        <div class="ultra-glow"></div>
        <div class="modal-header-ultra">
            <div>
                <h2 class="modal-title-ultra">Currency</h2>
                <p class="modal-desc-ultra">Select your preferred currency</p>
            </div>
            <button onclick="closeModal()" class="close-ultra">&times;</button>
        </div>
        <div class="modal-body-ultra custom-scrollbar">
            <div class="currency-grid">
                <?php foreach($curr_list as $code => $c): ?>
                <div class="curr-card <?= ($code == $curr_code) ? 'active' : '' ?>" onclick="setCurrency('<?= $code ?>')">
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

<style>
/* --- CORE STYLES --- */
:root { 
    --nav-h: 70px; 
    --radius: 50px; 
    --primary: #4f46e5; 
    --text-main: #0f172a; 
}
.nav-spacer { height: 95px; }
body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }

/* --- CAPSULE CONTAINER --- */
.neo-capsule {
    position: fixed; 
    top: 15px; 
    left: 50%; 
    transform: translateX(-50%);
    width: 90%; 
    max-width: 1240px;
    height: var(--nav-h);
    border-radius: var(--radius); 
    z-index: 9999; 
    padding: 3px; 
    box-shadow: 0 12px 35px rgba(79, 70, 229, 0.18); 
    overflow: hidden; 
    transition: all 0.3s ease;
}

/* --- PURPLE CRAZY SPIN BG --- */
.purple-spin-bg {
    position: absolute; 
    top: -100%; left: -100%; 
    width: 300%; height: 300%; 
    background: conic-gradient(
        from 0deg,
        #4f46e5, 
        #7e22ce, 
        #ec4899, 
        #4f46e5, 
        #8b5cf6, 
        #4f46e5 
    );
    animation: crazySpin 4s linear infinite;
    z-index: 0;
}
@keyframes crazySpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.nav-inner {
    position: relative; width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.92); 
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 46px; 
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 12px 0 24px;
    z-index: 1; 
}

/* --- LOGO AREA --- */
.logo-area {
    flex-shrink: 0; display: flex; align-items: center; text-decoration: none;
}
.logo-area img { 
    height: 42px; width: auto; object-fit: contain; transition: transform 0.3s;
}
.logo-area:hover img { transform: scale(1.05); }
.logo-text { font-weight: 800; font-size: 1.35rem; color: var(--text-main); letter-spacing: -0.5px; }

/* --- DESKTOP MENU --- */
.desk-menu { display: flex; align-items: center; gap: 6px; height: 100%; }
.pill-link {
    text-decoration: none; color: #334155;
    font-size: 0.85rem; font-weight: 700;
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

/* --- DROPDOWNS --- */
.drop-box {
    position: absolute; top: 130%; right: 0; width: 220px; background: #ffffff; border-radius: 16px; padding: 8px; 
    box-shadow: 0 15px 35px rgba(0,0,0,0.12); display: none; animation: slideUp 0.25s ease; 
    border: 1px solid rgba(0,0,0,0.06); z-index: 10002; max-height: 50vh; overflow-y: auto;
}
.drop-box.show { display: block; }
.drop-box a { display: flex; align-items: center; padding: 10px 12px; color: #334155; text-decoration: none; border-radius: 10px; font-size: 0.88rem; font-weight: 600; transition: 0.2s; }
.drop-box a:hover { background: #f8fafc; color: var(--primary); transform: translateX(4px); }

/* --- RIGHT ACTION BUTTONS --- */
.nav-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.nav-btn { width: 42px; height: 42px; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.25s ease; }
.nav-btn:hover { transform: scale(1.08); border-color: var(--primary); background: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15); }
.hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; width: 42px; height: 42px; justify-content: center; align-items: center; border-radius: 50%; background: #f8fafc; border: 1px solid #e2e8f0; }
.hamburger .bar { width: 20px; height: 2px; background: #0f172a; border-radius: 2px; transition: 0.3s; }

/* --- MOBILE DRAWER --- */
.drawer-panel { position: fixed; top: 0; right: 0; width: 310px; height: 100dvh; background: #ffffff; z-index: 10001; transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: -10px 0 40px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
.drawer-panel.open { transform: translateX(0); }
.drawer-back { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); z-index: 10000; opacity: 0; visibility: hidden; transition: 0.3s; backdrop-filter: blur(4px); }
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

/* --- CURRENCY MODAL --- */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 11000; align-items: center; justify-content: center; }
.ultra-modal { position: relative; width: 90%; max-width: 420px; background: #ffffff; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid rgba(255,255,255,0.8); overflow: hidden; animation: zoomSpring 0.3s ease; }
.ultra-glow { position: absolute; top: 0; left: 0; right: 0; height: 120px; background: radial-gradient(circle at 50% 0%, rgba(79, 70, 229, 0.12), transparent 70%); pointer-events: none; }
.modal-header-ultra { display: flex; justify-content: space-between; align-items: flex-start; padding: 25px 25px 10px 25px; position: relative; z-index: 2; }
.modal-title-ultra { margin: 0; font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
.modal-desc-ultra { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; }
.close-ultra { width: 32px; height: 32px; background: #f1f5f9; border: none; border-radius: 50%; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
.close-ultra:hover { background: #e2e8f0; transform: rotate(90deg); }
.modal-body-ultra { padding: 15px 25px 25px 25px; max-height: 55vh; overflow-y: auto; position: relative; z-index: 2; }
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

/* --- RESPONSIVE FIXES --- */
@media (max-width: 992px) { 
    .desk-menu { display: none; } 
    .hamburger { display: flex; } 
    .neo-capsule { width: 92%; } 
    .nav-inner { padding: 0 15px; } 
}
@keyframes slideUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
@keyframes zoomSpring { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
</style>

<script>
// Mobile Drawer Controller
const ham = document.getElementById('hamBtn'); 
const panel = document.getElementById('drawerPanel'); 
const back = document.getElementById('drawerBack'); 
const closeD = document.getElementById('closeDrawer');

function toggleDrawer() { 
    const isOpen = panel.classList.contains('open'); 
    if(isOpen) { 
        panel.classList.remove('open'); 
        back.classList.remove('open'); 
        document.body.style.overflow = ''; 
    } else { 
        panel.classList.add('open'); 
        back.classList.add('open'); 
        document.body.style.overflow = 'hidden'; 
    } 
}
if(ham) ham.addEventListener('click', toggleDrawer); 
if(closeD) closeD.addEventListener('click', toggleDrawer); 
if(back) back.addEventListener('click', toggleDrawer);

// Currency Modal Controller
function openModal() { document.getElementById('currModal').style.display = 'flex'; }
function closeModal() { document.getElementById('currModal').style.display = 'none'; }
function setCurrency(code) { document.cookie = "site_currency=" + code + "; path=/; max-age=" + (30*24*60*60); location.reload(); }

// Desktop Dropdown Toggle
function toggleDesktopDrop(id, e) {
    e.stopPropagation();
    const box = document.getElementById(id);
    const wasOpen = box.classList.contains('show');
    document.querySelectorAll('.drop-box').forEach(b => b.classList.remove('show'));
    if (!wasOpen) box.classList.add('show');
}
document.addEventListener('click', () => { document.querySelectorAll('.drop-box').forEach(b => b.classList.remove('show')); });

// Mobile Submenu Toggle
function toggleMobileSub(id, btn) {
    const sub = document.getElementById(id);
    const icon = btn.querySelector('i');
    if(sub.style.display === 'block') { 
        sub.style.display = 'none'; 
        icon.classList.remove('fa-chevron-up'); 
        icon.classList.add('fa-chevron-down'); 
    } else { 
        sub.style.display = 'block'; 
        icon.classList.remove('fa-chevron-down'); 
        icon.classList.add('fa-chevron-up'); 
    }
}
</script>
