<?php
// --- 1. CORE HELPERS & SESSION ---
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

// ====================================================
// 🛡️ PROJECT IRON-CORE SECURITY PROTOCOLS
// ====================================================

// 1. Include Security Engine
require_once __DIR__ . '/../includes/iron_core.php';

// 2. Activate Session DNA Lock (IP & Browser Check)
activateIronCore(); 

// 3. Strict Admin Authorization
if (!isAdmin()) {
    header("Location: ../boss_login.php?msg=auth_failed");
    exit;
}
// ====================================================

// --- 3. PAGE LOGIC ---
$current_page = basename($_SERVER['PHP_SELF']);

// --- 4. MASTER NAVIGATION MENU (AUTO-CONFIGURED) ---
// This array includes ALL files found in your panel folder.
$master_menu = [
    [
        'label' => 'Dashboard',
        'link' => 'index.php',
        'icon' => 'fa-home',
        'color' => '#6366f1', // Indigo
        'children' => []
    ],
    [
        'label' => 'SMM Panel',
        'link' => '#',
        'icon' => 'fa-rocket',
        'color' => '#ec4899', // Pink
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
        'color' => '#3b82f6', // Blue
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
        'color' => '#f59e0b', // Amber
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
        'color' => '#10b981', // Emerald
        'children' => [
            ['label' => 'User Manager', 'link' => 'users.php', 'icon' => 'fa-user-friends'],
            ['label' => 'Staff Manager', 'link' => 'staff_manager.php', 'icon' => 'fa-user-shield'],
            ['label' => 'Payments', 'link' => 'payments.php', 'icon' => 'fa-wallet'],
            ['label' => 'Support Tickets', 'link' => 'tickets.php', 'icon' => 'fa-headset']
        ]
    ],
    [
        'label' => 'Marketing Tools',
        'link' => '#',
        'icon' => 'fa-bullhorn',
        'color' => '#ef4444', // Red
        'children' => [
            ['label' => 'Broadcast', 'link' => 'broadcast.php', 'icon' => 'fa-paper-plane'],
            ['label' => 'Push Notify', 'link' => 'push_notifications.php', 'icon' => 'fa-bell'],
            ['label' => 'Promo Codes', 'link' => 'promo_codes.php', 'icon' => 'fa-ticket-alt'],
            ['label' => 'Spin Wheel', 'link' => 'wheel_prizes.php', 'icon' => 'fa-dharmachakra'],
            ['label' => 'Flash Deals', 'link' => 'flash_manage.php', 'icon' => 'fa-bolt'],
            ['label' => 'Card Designer', 'link' => 'card_designer.php', 'icon' => 'fa-paint-brush']
        ]
    ],
    [
        'label' => 'System Core',
        'link' => '#',
        'icon' => 'fa-cogs',
        'color' => '#64748b', // Slate
        'children' => [
            ['label' => 'Main Settings', 'link' => 'settings.php', 'icon' => 'fa-sliders-h'],
            ['label' => 'System Controls', 'link' => 'system_controls.php', 'icon' => 'fa-microchip'],
            ['label' => 'Google Config', 'link' => 'google_settings.php', 'icon' => 'fa-google'],
            ['label' => 'VPN Security', 'link' => 'vpn_settings.php', 'icon' => 'fa-shield-alt'],
            ['label' => 'Secure Vault', 'link' => 'secure_vault.php', 'icon' => 'fa-key'],
            ['label' => 'Admin Lock', 'link' => 'admin_lock.php', 'icon' => 'fa-lock']
        ]
    ],
    [
        'label' => 'Site Content',
        'link' => '#',
        'icon' => 'fa-palette',
        'color' => '#8b5cf6', // Violet
        'children' => [
            ['label' => 'Theme Editor', 'link' => 'theme_editor.php', 'icon' => 'fa-code'],
            ['label' => 'Tutorials', 'link' => 'tutorials.php', 'icon' => 'fa-book'],
            ['label' => 'Testimonials', 'link' => 'testimonials.php', 'icon' => 'fa-star'],
            ['label' => 'Menu Manager', 'link' => 'menus.php', 'icon' => 'fa-bars']
        ]
    ],
    [
        'label' => 'Maintenance',
        'link' => '#',
        'icon' => 'fa-tools',
        'color' => '#0f172a', // Dark
        'children' => [
            ['label' => 'Cron Jobs', 'link' => 'cron_jobs.php', 'icon' => 'fa-clock'],
            ['label' => 'Activity Log', 'link' => 'activity_log.php', 'icon' => 'fa-clipboard-list'],
            ['label' => 'Update Logs', 'link' => 'updates_log.php', 'icon' => 'fa-sync'],
            ['label' => 'GitHub Sync', 'link' => 'github_sync.php', 'icon' => 'fa-code-branch'],
            ['label' => 'DB Backup', 'link' => 'db_backup.php', 'icon' => 'fa-database'],
            ['label' => 'Full Backup', 'link' => 'full_backup.php', 'icon' => 'fa-file-archive']
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        :root { --primary-color: #4f46e5; --sidebar-width: 270px; }
        body { background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0;
            background: #fff; border-right: 1px solid #e5e7eb; z-index: 1000;
            display: flex; flex-direction: column; transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.03);
        }
        .sidebar-brand {
            padding: 25px; font-size: 1.4rem; font-weight: 800; color: var(--primary-color);
            border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 12px;
            background: linear-gradient(to right, #ffffff, #f9fafb);
        }

        .nav-links { list-style: none; padding: 15px 10px; margin: 0; flex: 1; overflow-y: auto; }
        .nav-item { position: relative; margin-bottom: 4px; }

        /* NAV LINK */
        .nav-link {
            color: #4b5563; padding: 12px 18px; font-weight: 600; display: flex; align-items: center; gap: 12px;
            transition: all 0.2s; border-radius: 10px; text-decoration: none; cursor: pointer; font-size: 0.92rem;
        }
        .nav-link:hover { background-color: #f8fafc; color: var(--primary-color); transform: translateX(3px); }
        .nav-link.active { background-color: #eef2ff; color: var(--primary-color); }
        
        .nav-link i.icon { width: 24px; text-align: center; font-size: 1.1rem; transition: 0.3s; }
        .nav-link:hover i.icon { transform: scale(1.1); }
        
        /* Arrow Rotation Transition */
        .arrow { margin-left: auto; transition: transform 0.3s ease; font-size: 0.8rem; opacity: 0.5; }
        
        /* OPEN STATE */
        .nav-item.open .arrow { transform: rotate(180deg); }
        .nav-item.open > .nav-link { color: var(--primary-color); background: #f1f5f9; }
        
        /* SUB-MENU */
        .sub-menu {
            list-style: none; padding: 5px 0 5px 10px; 
            background: #fff;
            overflow: hidden; 
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 0px; 
            display: block;
            margin-left: 15px; border-left: 2px solid #f1f5f9;
        }
        
        .sub-menu li a {
            display: flex; align-items: center; padding: 10px 15px; font-size: 0.85rem; color: #64748b;
            text-decoration: none; transition: 0.2s; border-radius: 8px; margin-bottom: 2px;
        }
        .sub-menu li a i { font-size: 0.8rem; width: 20px; text-align: center; margin-right: 8px; opacity: 0.7; }
        
        .sub-menu li a:hover, .sub-menu li a.active { color: var(--primary-color); background: #f8fafc; font-weight: 600; }
        .sub-menu li a.active { background: #eef2ff; }

        .main-content { margin-left: var(--sidebar-width); padding: 30px; transition: 0.3s; }
        
        /* SCROLLBAR */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        /* MOBILE */
        .mobile-toggle { display: none; position: fixed; top: 15px; right: 15px; z-index: 1100; border-radius: 50%; width: 45px; height: 45px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); background: var(--primary-color); border:none; color:white; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.show { transform: translateX(0); box-shadow: 10px 0 30px rgba(0,0,0,0.1); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: flex; align-items: center; justify-content: center; }
        }
    </style>
</head>
<body>

    <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('show')"><i class="fas fa-bars"></i></button>

    <div class="sidebar">
        <div class="sidebar-brand">
            <div style="width:35px; height:35px; background:var(--primary-color); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.1rem;">
                <i class="fas fa-bolt"></i>
            </div>
            <span>Beast Panel</span>
        </div>

        <ul class="nav-links">
            <?php foreach ($master_menu as $menu): 
                $children = $menu['children'] ?? [];
                $has_children = !empty($children);
                
                // Active Logic
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

        <div style="padding: 20px; border-top: 1px solid #f3f4f6; background:#fff;">
            <a href="../logout.php" style="display:flex; align-items:center; gap:10px; padding:12px; background:#fee2e2; color:#991b1b; border-radius:10px; text-decoration:none; font-weight:600; transition:0.2s;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropdowns = document.querySelectorAll('.nav-link.has-dropdown');

    dropdowns.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const parentLi = this.parentElement;
            const submenu = parentLi.querySelector('.sub-menu');
            const isOpen = parentLi.classList.contains('open');

            // Close others (Accordion)
            document.querySelectorAll('.nav-item.open').forEach(item => {
                if (item !== parentLi) {
                    item.classList.remove('open');
                    const otherSub = item.querySelector('.sub-menu');
                    if(otherSub) otherSub.style.maxHeight = "0px";
                }
            });

            // Toggle Current
            if (isOpen) {
                submenu.style.maxHeight = "0px";
                parentLi.classList.remove('open');
            } else {
                parentLi.classList.add('open');
                submenu.style.maxHeight = submenu.scrollHeight + "px";
            }
        });
    });
});
</script>
</body>
</html>