<?php
include '_smm_header.php'; 
require_once __DIR__ . '/../includes/smm_api.class.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// --- AUTO HEAL DATABASE (Permanent Name Future-Proofing) ---
try {
    $cols = $db->query("SHOW COLUMNS FROM smm_orders")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('service_name', $cols)) {
        $db->exec("ALTER TABLE smm_orders ADD COLUMN service_name VARCHAR(255) NULL AFTER service_id");
    }
} catch (Exception $e) { /* Silent fail if no permission */ }

// --- SETTINGS FOR RECEIPT LOGO ---
$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
$logo_url = !empty($site_logo) ? "../assets/img/$site_logo" : "../assets/img/logo.png"; // Fallback to default logo

// --- ICON LOGIC SETUP ---
$icon_base_path = '../assets/img/icons/';
$app_icons = [
    'instagram' => 'Instagram.png',
    'tiktok' => 'TikTok.png',
    'youtube' => 'YouTube.png',
    'facebook' => 'Facebook.png',
    'twitter' => 'X.png',
    'x ' => 'X.png', 
    'spotify' => 'Spotify.png',
    'telegram' => 'Telegram.png',
    'whatsapp' => 'WhatsApp.png',
    'linkedin' => 'LinkedIn.png',
    'snapchat' => 'Snapchat.png',
    'pinterest' => 'Pinterest.png',
    'twitch' => 'Twitch.png',
    'discord' => 'Discord.png',
    'threads' => 'Threads.png',
    'netflix' => 'Netflix.png',
    'pubg' => 'Pubg.png',
    'soundcloud' => 'SoundCloud.png',
    'website' => 'Website.png',
    'google' => 'Google.png',
    'likee' => 'Likee.png',
];

function getIconPath($serviceName, $path, $icons) {
    if (empty($serviceName)) return ''; 
    $name = strtolower($serviceName);
    foreach ($icons as $key => $iconFile) {
        if (strpos($name, $key) !== false) {
            return $path . $iconFile;
        }
    }
    return ''; 
}

// --- REFILL/CANCEL LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    try {
        // LEFT JOIN ensures the query doesn't fail if service is deleted
        $stmt_order = $db->prepare("SELECT o.*, s.has_refill, s.has_cancel, p.api_url, p.api_key
                                    FROM smm_orders o
                                    LEFT JOIN smm_services s ON o.service_id = s.id
                                    LEFT JOIN smm_providers p ON s.provider_id = p.id
                                    WHERE o.id = ? AND o.user_id = ?");
        $stmt_order->execute([$order_id, $user_id]);
        $order = $stmt_order->fetch();

        if (!$order || empty($order['provider_order_id'])) {
            $error = 'Action unavailable: Provider link missing for this old order.';
        } else {
            $api = new SmmApi($order['api_url'], $order['api_key']);
            if ($_POST['action'] == 'refill' && !empty($order['has_refill'])) {
                $can_refill = true;
                if ($order['status'] == 'completed') {
                    $completion_time = strtotime($order['updated_at']);
                    if (time() < ($completion_time + 86400)) {
                        $can_refill = false;
                        $error = 'Refill available 24 hours after completion.';
                    }
                } else { 
                    $can_refill = false; 
                    $error = 'Order must be completed first.';
                }

                if ($can_refill) {
                    $result = $api->refillOrder($order['provider_order_id']);
                    if ($result['success']) {
                        $db->prepare("UPDATE smm_orders SET updated_at = NOW() WHERE id = ?")->execute([$order_id]);
                        $success = 'Refill request sent for Order #' . $order['id'] . '.';
                    } else { $error = 'Refill failed: ' . ($result['error'] ?? 'Provider error'); }
                }
            } elseif ($_POST['action'] == 'cancel' && !empty($order['has_cancel'])) {
                $result = $api->cancelOrder($order['provider_order_id']);
                if ($result['success']) {
                    $success = 'Cancel request sent. System will update status shortly.'; 
                } else { 
                    $error = 'Cancel request failed. Error: ' . ($result['error'] ?? 'Unknown API error.'); 
                }
            }
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// --- NEW: FETCH USER SUMMARY STATS ---
try {
    $stmt_stats = $db->prepare("SELECT 
        SUM(charge) as total_spent,
        COUNT(CASE WHEN status IN ('pending', 'in_progress', 'processing') THEN 1 END) as active_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
        FROM smm_orders WHERE user_id = ?");
    $stmt_stats->execute([$user_id]);
    $user_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $total_spent = $user_stats['total_spent'] ?? 0.00;
    $active_orders = $user_stats['active_orders'] ?? 0;
    $completed_orders = $user_stats['completed_orders'] ?? 0;
} catch (PDOException $e) { 
    $total_spent = 0; $active_orders = 0; $completed_orders = 0; 
}

// --- UPDATED: SEARCH, FILTER & PAGINATION LOGIC ---
$limit = 20; // Pagination limit
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

$where_sql = "WHERE o.user_id = :uid";
$params = [':uid' => $user_id];

if (!empty($search_query)) {
    $where_sql .= " AND (o.id = :sq OR o.link LIKE :sqlk OR s.name LIKE :sqlk OR o.service_name LIKE :sqlk)";
    $params[':sq'] = $search_query;
    $params[':sqlk'] = "%$search_query%";
}
if ($filter_status !== 'all' && !empty($filter_status)) {
    $where_sql .= " AND o.status = :status";
    $params[':status'] = $filter_status;
}

try {
    // Count Total for Pagination
    $stmt_count = $db->prepare("SELECT COUNT(o.id) FROM smm_orders o LEFT JOIN smm_services s ON o.service_id = s.id $where_sql");
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch Orders with Limits
    $stmt = $db->prepare("SELECT o.*, COALESCE(o.service_name, s.name) as final_service_name, s.has_refill, s.has_cancel 
                          FROM smm_orders o 
                          LEFT JOIN smm_services s ON o.service_id = s.id 
                          $where_sql 
                          ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $smm_orders = $stmt->fetchAll();

    // Name Recovery Logic Fallback
    foreach ($smm_orders as &$ord) {
        if (empty($ord['final_service_name'])) {
            try {
                $stmtLog = $db->prepare("SELECT service_name FROM service_updates WHERE service_id = ? ORDER BY id DESC LIMIT 1");
                $stmtLog->execute([$ord['service_id']]);
                $logName = $stmtLog->fetchColumn();
                $ord['final_service_name'] = $logName ? $logName . ' [Del]' : 'Deleted Service #' . $ord['service_id'];
            } catch (Exception $e) { $ord['final_service_name'] = 'Unknown Service'; }
        }
    }
    unset($ord);

} catch (PDOException $e) { $smm_orders = []; $total_pages = 1; }
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* CSS Variables & Global Fixes */
    :root {
        --primary: #4f46e5; 
        --bg-body: #f3f4f6;
        --bg-card: #ffffff;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --border-color: #e5e7eb;
        --danger: #ef4444;
        --success: #10b981; 
        --refund-color: var(--success);
        --grey-box: #f9fafb;
        --input-bg: #ffffff;
        --hover-bg: #f3f4f6;
    }

    /* Prevent Layout Overflow Sitewide */
    * { box-sizing: border-box; }
    body { background-color: var(--bg-body); font-family: 'Outfit', sans-serif; overflow-x: hidden; margin: 0; padding: 0; }
    
    .sh-container { max-width: 600px; margin: 0 auto; padding: 15px; width: 100%; }

    /* Header & Base Card Styles */
    .page-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .page-head-left { display: flex; align-items: center; }
    .page-head h2 { margin-left: 15px; font-size: 1.2rem; font-weight: 700; color: var(--text-dark); }
    .back-circle { background: var(--bg-card); padding: 8px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; color: var(--text-dark); text-decoration: none; border: 1px solid var(--border-color); }

    /* Stats Dashboard */
    .stats-dashboard { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .stat-box { background: var(--bg-card); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .stat-box i { font-size: 1.2rem; color: var(--primary); margin-bottom: 5px; }
    .stat-val { font-size: 1.1rem; font-weight: 800; color: var(--text-dark); display: block; }
    .stat-lbl { font-size: 0.7rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; }

    /* FIXED: Search & Filter Bar (No Overflow) */
    .search-filter-box { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; background: var(--bg-card); padding: 10px; border-radius: 12px; border: 1px solid var(--border-color); width: 100%; }
    .sh-input { flex: 1 1 150px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-dark); font-family: inherit; font-size: 0.85rem; }
    .sh-select { flex: 1 1 120px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-dark); font-family: inherit; font-size: 0.85rem; }
    .sh-btn-search { flex: 0 0 auto; background: var(--primary); color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; }

    /* Shimmer Skeleton Loader */
    .skeleton-card { background: var(--bg-card); border-radius: 16px; padding: 20px; margin-bottom: 15px; border: 1px solid var(--border-color); }
    .skeleton-line { height: 15px; background: var(--hover-bg); border-radius: 4px; margin-bottom: 10px; width: 100%; animation: shimmer 1.5s infinite linear; }
    .skeleton-line.w-50 { width: 50%; }
    .skeleton-box { height: 60px; background: var(--hover-bg); border-radius: 12px; margin-top: 15px; animation: shimmer 1.5s infinite linear; }
    @keyframes shimmer { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

    /* Real Card Styles */
    .sh-card { 
        background: var(--bg-card); border-radius: 16px; padding: 20px; margin-bottom: 15px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid var(--border-color); 
        position: relative; overflow: hidden; transition: transform 0.2s ease;
    }
    .sh-card:hover { transform: translateY(-2px); }
    
    .card-watermark {
        position: absolute; top: 0; right: 0; width: 100%; height: 100%; 
        opacity: 0.05; background-repeat: no-repeat; background-size: 200px; 
        background-position: top 20px right 20px; pointer-events: none; z-index: 1; 
    }
    
    .sh-header, .sh-stats-grid, .sh-footer, .sh-actions, .progress-area, .qty-grey-box { position: relative; z-index: 5; }

    /* NEW: Professional Micro-Line Dividers */
    .card-divider { border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px; }
    .card-divider:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

    .sh-header { display: flex; justify-content: space-between; align-items: flex-start; }
    .sh-title { font-weight: 700; font-size: 0.95rem; color: var(--text-dark); line-height: 1.4; width: 75%; }
    .sh-price { font-weight: 800; color: var(--primary); font-size: 1rem; text-align: right; }
    
    .sh-badge { padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; }

    .st-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; border-color: rgba(245, 158, 11, 0.2); }
    .st-completed { background: rgba(16, 185, 129, 0.1); color: #059669; border-color: rgba(16, 185, 129, 0.2); }
    .st-processing { background: rgba(59, 130, 246, 0.1); color: #2563eb; border-color: rgba(59, 130, 246, 0.2); }
    .st-cancelled { background: rgba(239, 68, 68, 0.1); color: #dc2626; border-color: rgba(239, 68, 68, 0.2); }
    .st-partial { background: rgba(99, 102, 241, 0.1); color: #4338ca; border-color: rgba(99, 102, 241, 0.2); }

    /* --- CUTE ANIMATED PROGRESS BAR --- */
    .progress-area { /* Applied with card-divider */ }
    .cute-progress-track {
        height: 18px; background: var(--grey-box); border-radius: 20px; overflow: hidden; position: relative; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
    .cute-progress-fill {
        height: 100%; border-radius: 20px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; color: white; font-size: 0.7rem; font-weight: 800; text-shadow: 0 1px 2px rgba(0,0,0,0.2); box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
        background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.2) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, transparent 75%, transparent);
        background-size: 20px 20px; animation: candyStripe 1s linear infinite;
    }
    .pg-processing-gradient { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .pg-completed-gradient { background: linear-gradient(90deg, #10b981, #34d399); }
    .pg-pending-gradient { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .pg-cancelled-gradient { background: linear-gradient(90deg, #ef4444, #f87171); }

    @keyframes candyStripe { 0% { background-position: 0 0; } 100% { background-position: 20px 20px; } }
    .pulse-glow { animation: candyStripe 1s linear infinite, glowPulse 2s infinite; }
    @keyframes glowPulse { 0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); } 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); } }

    /* Quantity & Stats */
    .qty-grey-box {
        display: flex; justify-content: center; align-items: center; gap: 8px; background: var(--hover-bg); color: var(--text-dark); padding: 8px 15px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; margin-bottom: 15px; border: 1px solid var(--border-color);
    }
    .qty-grey-box i { color: var(--text-light); }

    .sh-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; background: var(--grey-box); padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
    .sh-stat-item { text-align: center; }
    .sh-stat-lbl { font-size: 0.65rem; color: var(--text-light); text-transform:uppercase; letter-spacing:0.5px; display: block; margin-bottom: 4px; }
    .sh-stat-val { font-size: 0.9rem; font-weight: 700; color: var(--text-dark); }

    /* Footer & Actions */
    .sh-footer { display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-light); flex-wrap: wrap; gap: 10px; }
    
    .copy-btn { cursor: pointer; color: var(--text-light); transition: color 0.2s; margin-left: 4px; }
    .copy-btn:hover { color: var(--primary); }

    .btn-link-small, .btn-ticket {
        display: inline-flex; align-items: center; gap: 6px; background: var(--hover-bg); color: var(--text-dark); padding: 5px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: all 0.2s; border: 1px solid var(--border-color);
    }
    .btn-link-small:hover, .btn-ticket:hover { background: var(--border-color); }
    .btn-ticket { color: #d97706; }
    
    .sh-actions { display: flex; gap: 10px; margin-bottom: 10px; }

    .btn-refill-elite { position: relative; width: 100%; padding: 10px; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 800; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; gap: 8px; text-transform: uppercase; background: var(--hover-bg); color: var(--text-dark); transition: all 0.3s ease; }
    .btn-refill-elite:hover { transform: scale(1.02); }
    .btn-refill-elite.ready { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
    .btn-refill-elite.locked { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; opacity: 0.7; cursor: not-allowed; }
    .btn-refill-elite.cooldown { background: var(--hover-bg); color: var(--text-light); border: 1px solid var(--border-color); cursor: not-allowed; }
    .timer-text { position: relative; z-index: 2; color: #d97706; background: rgba(254, 243, 199, 0.5); padding: 2px 6px; border-radius: 4px; }

    .btn-cancel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: rgba(239, 68, 68, 0.1); color: var(--danger); transition: 0.2s; }
    .btn-cancel:hover { background: rgba(239, 68, 68, 0.2); transform: scale(1.02); }
    
    .btn-receipt-bottom { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border-radius: 8px; border: 1px dashed #a5b4fc; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: rgba(99, 102, 241, 0.1); color: var(--primary); transition: 0.2s; position: relative; z-index: 5; }
    .btn-receipt-bottom:hover { background: rgba(99, 102, 241, 0.2); border-style: solid; }

    .msg-err { background: rgba(239, 68, 68, 0.1); color: var(--danger); padding:12px; border-radius:12px; margin-bottom:15px; text-align:center; font-weight:600; border:1px solid rgba(239, 68, 68, 0.3); }
    .msg-suc { background: rgba(16, 185, 129, 0.1); color: var(--success); padding:12px; border-radius:12px; margin-bottom:15px; text-align:center; font-weight:600; border:1px solid rgba(16, 185, 129, 0.3); }

    /* Pagination */
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; flex-wrap: wrap; }
    .page-link { padding: 8px 14px; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-dark); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: 0.2s; }
    .page-link:hover { background: var(--hover-bg); }
    .page-link.active { background: var(--primary); color: #fff; border-color: var(--primary); }
</style>

<div class="sh-container">
    <div class="page-head">
        <div class="page-head-left">
            <a href="smm_order.php" class="back-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <h2>My Orders</h2>
        </div>
    </div>

    <div class="stats-dashboard">
        <div class="stat-box">
            <i class="fa-solid fa-wallet"></i>
            <span class="stat-val"><?= formatCurrency($total_spent) ?></span>
            <span class="stat-lbl">Spent</span>
        </div>
        <div class="stat-box">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span class="stat-val"><?= number_format($active_orders) ?></span>
            <span class="stat-lbl">Active</span>
        </div>
        <div class="stat-box">
            <i class="fa-solid fa-check-double"></i>
            <span class="stat-val"><?= number_format($completed_orders) ?></span>
            <span class="stat-lbl">Done</span>
        </div>
    </div>

    <form method="GET" action="" class="search-filter-box">
        <input type="text" name="search" class="sh-input" placeholder="Search ID or Link..." value="<?= htmlspecialchars($search_query) ?>">
        <select name="status" class="sh-select">
            <option value="all" <?= $filter_status == 'all' ? 'selected' : '' ?>>All Status</option>
            <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= $filter_status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="processing" <?= $filter_status == 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="partial" <?= $filter_status == 'partial' ? 'selected' : '' ?>>Partial</option>
            <option value="canceled" <?= $filter_status == 'canceled' ? 'selected' : '' ?>>Canceled</option>
        </select>
        <button type="submit" class="sh-btn-search"><i class="fa-solid fa-search"></i></button>
    </form>

    <?php if ($error): ?><div class="msg-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg-suc"><i class="fa-solid fa-check-circle"></i> <?= sanitize($success) ?></div><?php endif; ?>

    <div id="skeleton-container">
        <div class="skeleton-card"><div class="skeleton-line"></div><div class="skeleton-line w-50"></div><div class="skeleton-box"></div></div>
        <div class="skeleton-card"><div class="skeleton-line"></div><div class="skeleton-line w-50"></div><div class="skeleton-box"></div></div>
    </div>

    <div id="actual-orders" style="display: none;">
        <?php if (empty($smm_orders)): ?>
            <div style="text-align:center; padding:60px; color:var(--text-light);">
                <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i><br>
                No orders found.
            </div>
        <?php else: ?>
            <?php foreach ($smm_orders as $order): ?>
                <?php
                    $st = strtolower($order['status']);
                    $stClass = 'st-processing';
                    $pgGradient = 'pg-processing-gradient pulse-glow';
                    
                    if(strpos($st, 'pend')!==false) { $stClass='st-pending'; $pgGradient = 'pg-pending-gradient'; }
                    elseif(strpos($st, 'comp')!==false) { $stClass='st-completed'; $pgGradient = 'pg-completed-gradient'; }
                    elseif(strpos($st, 'cancel')!==false) { $stClass='st-cancelled'; $pgGradient = 'pg-cancelled-gradient'; }
                    elseif(strpos($st, 'partial')!==false) { $stClass='st-partial'; $pgGradient = 'pg-processing-gradient'; }

                    $start = (int)$order['start_count']; $qty = (int)$order['quantity']; $remains = (int)$order['remains'];
                    $delivered = 0; $current = $start;
                    
                    // Calculation Logic
                    if ($st == 'completed') { $delivered = $qty; $current = $start + $qty; } 
                    elseif ($st == 'partial' || ($st == 'in_progress' && $start > 0)) { $delivered = $qty - $remains; $current = $start + $delivered; }
                    if ($st == 'cancelled') $remains = $qty; 
                    
                    // Percent Logic
                    $percent = 0;
                    if($qty > 0) { $percent = round(($delivered / $qty) * 100); }
                    if($percent > 100) $percent = 100;
                    if($percent < 0) $percent = 0;
                    if($st == 'completed') $percent = 100;
                    if($st == 'pending') $percent = 5;

                    $refundAmount = 0.00;
                    if ($st == 'partial' || $st == 'cancelled') {
                        $remains_for_refund = (int)$order['remains']; 
                        if ($remains_for_refund > 0) {
                            $charge_per_item = (float)$order['charge'] / (float)$order['quantity'];
                            $refundAmount = $charge_per_item * $remains_for_refund;
                        }
                    }

                    $displayServiceName = !empty($order['final_service_name']) ? $order['final_service_name'] : 'Deleted Service (ID: ' . $order['service_id'] . ')';
                    $iconPath = getIconPath($displayServiceName, $icon_base_path, $app_icons);

                    // Refill Logic
                    $canRefill = false; $btnState = 'locked'; $btnContent = '<span>🔒 Refill</span>'; $timerAttr = ''; 
                    
                    if (!empty($order['has_refill']) && $order['has_refill']) {
                        if ($st == 'completed') {
                            $updated_time = strtotime($order['updated_at']);
                            $next_refill = $updated_time + 86400; 
                            $now = time();
                            if ($now < $next_refill) {
                                $btnState = 'cooldown refill-countdown';
                                $timerAttr = 'data-countdown="' . $next_refill . '"';
                                $btnContent = '<span>⏳ Wait...</span>';
                            } else {
                                $btnState = 'ready'; $canRefill = true; $btnContent = '<span>⚡ Refill</span>';
                            }
                        }
                    }
                    
                    // Cancel Logic
                    $hasCancel = !empty($order['has_cancel']) ? $order['has_cancel'] : 0;
                    $canCancel = ($hasCancel && ($st == 'pending' || $st == 'in_progress'));
                ?>

                <div class="sh-card target-card">
                    <?php if (!empty($iconPath)): ?>
                        <div class="card-watermark" style="background-image: url('<?= $iconPath ?>');"></div>
                    <?php endif; ?>
                    
                    <div class="sh-header card-divider">
                        <div class="sh-title"><span class="service-name-text"><?= sanitize($displayServiceName) ?></span></div>
                        <div class="sh-price"><?= formatCurrency($order['charge']) ?></div>
                    </div>
                    
                    <?php if ($refundAmount > 0.01 && ($st == 'partial' || $st == 'cancelled')): ?>
                        <div style="margin-bottom: 15px; font-weight: 700; color: var(--refund-color); font-size: 0.85rem; background: rgba(16, 185, 129, 0.1); display: inline-block; padding: 4px 10px; border-radius: 6px; position:relative; z-index:5;">
                            <i class="fa-solid fa-rotate-left"></i> Refunded: <?= formatCurrency($refundAmount) ?>
                        </div>
                    <?php endif; ?>

                    <div class="progress-area card-divider">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; font-size:0.75rem; font-weight:700; color:var(--text-light);">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span>Progress</span>
                                <span class="sh-badge <?= $stClass ?>" style="padding: 2px 8px; font-size: 0.65rem;"><?= ucfirst($order['status']) ?></span>
                            </div>
                            <span><?= $percent ?>%</span>
                        </div>
                        <div class="cute-progress-track">
                            <div class="cute-progress-fill <?= $pgGradient ?>" style="width: <?= $percent ?>%;"></div>
                        </div>
                    </div>

                    <div class="card-divider">
                        <div class="qty-grey-box">
                            <i class="fa-solid fa-layer-group"></i> Quantity: <?= number_format($qty) ?>
                        </div>

                        <div class="sh-stats-grid">
                            <div class="sh-stat-item"><span class="sh-stat-lbl">Start</span><span class="sh-stat-val"><?= number_format($start) ?></span></div>
                            <div class="sh-stat-item"><span class="sh-stat-lbl">Delivered</span><span class="sh-stat-val"><?= number_format($delivered) ?></span></div>
                            <div class="sh-stat-item"><span class="sh-stat-lbl">Remains</span><span class="sh-stat-val"><?= number_format($remains) ?></span></div>
                            <div class="sh-stat-item"><span class="sh-stat-lbl">Current</span><span class="sh-stat-val"><?= number_format($current) ?></span></div>
                        </div>
                    </div>
                    
                    <div class="sh-footer card-divider">
                        <div style="display:flex; align-items:center; gap:5px;"><span>📅 <?= date('d M, Y h:i A', strtotime($order['created_at'])) ?></span></div>
                        
                        <div style="display:flex; align-items:center; gap:8px;">
                            <a href="tickets.php?subject=Issue+with+Order+<?= $order['id'] ?>" class="btn-ticket" title="Report Issue">
                                <i class="fa-solid fa-headset"></i>
                            </a>

                            <span style="font-weight: 700; color: var(--text-dark); background: var(--hover-bg); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; border: 1px solid var(--border-color);">
                                ID: #<?= $order['id'] ?> <i class="fa-regular fa-copy copy-btn" onclick="copyToClipboard('<?= $order['id'] ?>', this)"></i>
                            </span>
                            
                            <?php if (!empty($order['link'])): ?>
                                <a href="<?= sanitize($order['link']) ?>" target="_blank" class="btn-link-small">
                                    <i class="fa-solid fa-link"></i> Target
                                </a>
                                <i class="fa-regular fa-copy copy-btn" style="font-size: 1rem;" onclick="copyToClipboard('<?= htmlspecialchars($order['link']) ?>', this)" title="Copy Link"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sh-actions">
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="refill">
                            <button type="submit" class="btn-refill-elite <?= $btnState ?>" <?= $canRefill ? '' : 'disabled' ?> <?= $timerAttr ?>>
                                <?= $btnContent ?>
                            </button>
                        </form>
                        
                        <?php if ($canCancel): ?>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="btn-cancel">✕ Cancel</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn-receipt-bottom" onclick="generateReceipt(this, '<?= $order['id'] ?>')">
                        <i class="fa-solid fa-file-invoice"></i> Download Receipt
                    </button>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= !empty($search_query) ? '&search='.urlencode($search_query) : '' ?><?= $filter_status!='all' ? '&status='.$filter_status : '' ?>" class="page-link <?= $page == $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<div id="receipt-gen-area" style="position:fixed; top:-9999px; left:-9999px; background:#fff; padding:20px;"></div>
<input type="hidden" id="site-logo-url" value="<?= $logo_url ?>">

<script>
// Remove skeleton loader when page is ready
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('skeleton-container').style.display = 'none';
    document.getElementById('actual-orders').style.display = 'block';
    startEliteCountdowns();
    startLiveSync(); // Start background sync
});

// Copy to Clipboard feature
function copyToClipboard(text, iconElem) {
    navigator.clipboard.writeText(text).then(() => {
        const originalClass = iconElem.className;
        iconElem.className = 'fa-solid fa-check copy-btn';
        iconElem.style.color = '#10b981';
        setTimeout(() => {
            iconElem.className = originalClass;
            iconElem.style.color = '';
        }, 2000);
    });
}

// Background Live Sync Logic
function startLiveSync() {
    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newStats = doc.querySelector('.stats-dashboard');
                if(newStats) document.querySelector('.stats-dashboard').innerHTML = newStats.innerHTML;
                
                const newOrders = doc.getElementById('actual-orders');
                if(newOrders) {
                    document.getElementById('actual-orders').innerHTML = newOrders.innerHTML;
                    startEliteCountdowns(); // Reset Timers
                }
            })
            .catch(err => console.log('Live Sync Paused'));
    }, 15000); // 15 seconds Auto-Refresh
}

// Timer Logic
function startEliteCountdowns() {
    const buttons = document.querySelectorAll('.refill-countdown');
    buttons.forEach(btn => {
        const attr = btn.getAttribute('data-countdown');
        if (!attr) return;
        const targetTime = parseInt(attr);
        if (isNaN(targetTime)) return; 
        const updateTimer = () => {
            const now = Math.floor(Date.now() / 1000);
            const diff = targetTime - now;
            if (diff <= 0) {
                btn.innerHTML = '<span>⚡ Refill</span>';
                btn.classList.remove('cooldown', 'refill-countdown');
                btn.classList.add('ready');
                btn.disabled = false;
                return;
            }
            const h = Math.floor(diff / 3600).toString().padStart(2, '0');
            const m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
            const s = (diff % 60).toString().padStart(2, '0');
            btn.innerHTML = `<span>Wait</span> <span class="timer-text">${h}:${m}:${s}</span>`;
            requestAnimationFrame(updateTimer);
        };
        updateTimer();
    });
}

// Generate Receipt Logic
function generateReceipt(btn, orderId) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...'; 

    const card = btn.closest('.sh-card');
    const clone = card.cloneNode(true);

    const actionsDiv = clone.querySelector('.sh-actions');
    if(actionsDiv) actionsDiv.remove();
    
    const receiptBtnNode = clone.querySelector('.btn-receipt-bottom');
    if(receiptBtnNode) receiptBtnNode.remove();
    
    const progressDiv = clone.querySelector('.progress-area');
    if(progressDiv) progressDiv.remove();
    
    const copyBtns = clone.querySelectorAll('.copy-btn');
    copyBtns.forEach(b => b.remove());

    clone.style.width = "500px"; 
    clone.style.padding = "30px";
    clone.style.border = "2px solid #e5e7eb";
    clone.style.borderRadius = "20px";
    clone.style.background = "#fff"; 
    clone.style.boxShadow = "none";
    clone.style.position = "relative"; 
    clone.style.color = "#1f2937"; 

    // Remove dividers styling for clean PDF view
    const dividers = clone.querySelectorAll('.card-divider');
    dividers.forEach(div => {
        div.style.borderBottom = "none";
        div.style.marginBottom = "5px";
    });

    const logoUrl = document.getElementById('site-logo-url').value;
    const headerDiv = document.createElement('div');
    headerDiv.style.textAlign = "center";
    headerDiv.style.marginBottom = "25px";
    headerDiv.style.borderBottom = "2px dashed #f3f4f6";
    headerDiv.style.paddingBottom = "15px";
    
    headerDiv.innerHTML = `
        <img src="${logoUrl}" style="height:50px; object-fit:contain; margin-bottom:5px;">
        <div style="font-weight:800; color:#1f2937; text-transform:uppercase; letter-spacing:1px; font-size:0.9rem;">Official Receipt</div>
    `;
    
    clone.prepend(headerDiv);

    const genArea = document.getElementById('receipt-gen-area');
    genArea.innerHTML = ''; 
    genArea.appendChild(clone);

    html2canvas(clone, {
        scale: 2, 
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const a = document.createElement('a');
        a.download = 'Order-Receipt-' + orderId + '.jpg';
        a.href = canvas.toDataURL('image/jpeg', 0.95);
        a.click();
        
        btn.innerHTML = originalText;
        genArea.innerHTML = ''; 
    }).catch(err => {
        alert('Error generating receipt');
        console.error(err);
        btn.innerHTML = originalText;
    });
}
</script>

<?php include '_smm_footer.php'; ?>