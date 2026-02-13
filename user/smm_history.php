<?php
include '_smm_header.php'; 
require_once __DIR__ . '/../includes/smm_api.class.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

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
        $stmt_order = $db->prepare("SELECT o.*, s.has_refill, s.has_cancel, p.api_url, p.api_key
                                    FROM smm_orders o
                                    JOIN smm_services s ON o.service_id = s.id
                                    JOIN smm_providers p ON s.provider_id = p.id
                                    WHERE o.id = ? AND o.user_id = ?");
        $stmt_order->execute([$order_id, $user_id]);
        $order = $stmt_order->fetch();

        if (!$order || empty($order['provider_order_id'])) {
            $error = 'Action unavailable: Service may have been deleted or disabled.';
        } else {
            $api = new SmmApi($order['api_url'], $order['api_key']);
            if ($_POST['action'] == 'refill' && $order['has_refill']) {
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
            } elseif ($_POST['action'] == 'cancel' && $order['has_cancel']) {
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

// --- FETCH ORDERS ---
try {
    // 1. LEFT JOIN to keep orders visible even if service is deleted
    $stmt = $db->prepare("SELECT o.*, s.name as service_name, s.has_refill, s.has_cancel 
                          FROM smm_orders o 
                          LEFT JOIN smm_services s ON o.service_id = s.id 
                          WHERE o.user_id = ? 
                          ORDER BY o.created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $smm_orders = $stmt->fetchAll();

    // 2. Name Recovery Logic
    foreach ($smm_orders as &$ord) {
        if (empty($ord['service_name'])) {
            // Check if name saved in smm_orders (New Logic)
            if (!empty($ord['service_name_saved'])) {
                 $ord['service_name'] = $ord['service_name_saved'];
            } else {
                // Check logs if not saved directly
                try {
                    $stmtLog = $db->prepare("SELECT service_name FROM service_updates WHERE service_id = ? ORDER BY id DESC LIMIT 1");
                    $stmtLog->execute([$ord['service_id']]);
                    $logName = $stmtLog->fetchColumn();
                    $ord['service_name'] = $logName ? $logName . ' [Del]' : 'Service #' . $ord['service_id'];
                } catch (Exception $e) { $ord['service_name'] = 'Unknown Service'; }
            }
        }
    }
    unset($ord);

} catch (PDOException $e) { $smm_orders = []; }
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Global Variables */
    :root {
        --primary: #4f46e5; 
        --bg-gray: #f3f4f6;
        --text-dark: #1f2937;
        --text-light: #6b7280;
        --danger: #ef4444;
        --success: #10b981; 
        --refund-color: var(--success);
    }
    body { background-color: var(--bg-gray); font-family: 'Outfit', sans-serif; }
    .sh-container { max-width: 600px; margin: 0 auto; padding: 15px; }

    /* Header & Base Card Styles */
    .page-head { display: flex; align-items: center; margin-bottom: 20px; }
    .page-head h2 { margin-left: 15px; font-size: 1.2rem; font-weight: 700; color: var(--text-dark); }
    .back-circle { background: #fff; padding: 8px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; color: var(--text-dark); text-decoration: none; }

    .sh-card { 
        background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 15px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; 
        position: relative; overflow: hidden;
        transition: transform 0.2s ease;
    }
    .sh-card:hover { transform: translateY(-2px); }
    
    .card-watermark {
        position: absolute; top: 0; right: 0; width: 100%; height: 100%; 
        opacity: 0.10; background-repeat: no-repeat; background-size: 200px; 
        background-position: top 20px right 20px; pointer-events: none;
        filter: brightness(120%); z-index: 1; 
    }
    
    .sh-header, .sh-meta, .sh-stats-grid, .sh-footer, .sh-actions, .progress-area { position: relative; z-index: 5; }

    .sh-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    .sh-title { font-weight: 700; font-size: 0.95rem; color: var(--text-dark); line-height: 1.4; width: 75%; }
    .sh-price { font-weight: 800; color: var(--primary); font-size: 1rem; text-align: right; }
    
    .sh-meta { font-size: 0.8rem; color: var(--text-light); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .sh-badge { padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }

    .st-pending { background: #fef3c7; color: #d97706; }
    .st-completed { background: #d1fae5; color: #059669; }
    .st-processing { background: #dbeafe; color: #2563eb; }
    .st-cancelled { background: #fee2e2; color: #dc2626; }
    .st-partial { background: #e0e7ff; color: #4338ca; }

    /* --- CUTE ANIMATED PROGRESS BAR START --- */
    .progress-area { margin: 15px 0; }
    .cute-progress-track {
        height: 18px;
        background: #f1f5f9;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
    .cute-progress-fill {
        height: 100%;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 8px;
        color: white;
        font-size: 0.7rem;
        font-weight: 800;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        
        /* Candy Stripe Animation */
        background-image: linear-gradient(
            45deg, 
            rgba(255, 255, 255, 0.2) 25%, 
            transparent 25%, 
            transparent 50%, 
            rgba(255, 255, 255, 0.2) 50%, 
            rgba(255, 255, 255, 0.2) 75%, 
            transparent 75%, 
            transparent
        );
        background-size: 20px 20px;
        animation: candyStripe 1s linear infinite;
    }

    /* Gradients for different statuses */
    .pg-processing { background-color: #3b82f6; } /* Fallback */
    .pg-processing-gradient { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    
    .pg-completed { background-color: #10b981; } /* Fallback */
    .pg-completed-gradient { background: linear-gradient(90deg, #10b981, #34d399); }
    
    .pg-pending { background-color: #f59e0b; } /* Fallback */
    .pg-pending-gradient { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

    .pg-cancelled { background-color: #ef4444; } /* Fallback */
    .pg-cancelled-gradient { background: linear-gradient(90deg, #ef4444, #f87171); }

    @keyframes candyStripe {
        0% { background-position: 0 0; }
        100% { background-position: 20px 20px; }
    }
    
    /* Pulse effect for processing */
    .pulse-glow {
        animation: candyStripe 1s linear infinite, glowPulse 2s infinite;
    }
    @keyframes glowPulse {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    /* --- CUTE ANIMATED PROGRESS BAR END --- */

    .sh-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; background: #f9fafb; padding: 12px; border-radius: 12px; margin-bottom: 15px; border: 1px solid #f3f4f6; }
    .sh-stat-item { text-align: center; }
    .sh-stat-lbl { font-size: 0.65rem; color: var(--text-light); text-transform:uppercase; letter-spacing:0.5px; display: block; margin-bottom: 4px; }
    .sh-stat-val { font-size: 0.9rem; font-weight: 700; color: var(--text-dark); }

    .sh-footer { font-size: 0.8rem; color: var(--text-light); border-top: 1px dashed #e5e7eb; padding-top: 10px; margin-bottom: 15px; }
    .sh-link { color: var(--primary); text-decoration: none; word-break: break-all; display: block; margin-bottom: 5px; transition: color 0.2s; }
    .sh-link:hover { color: #4338ca; }
    
    .sh-actions { display: flex; gap: 10px; margin-top: 10px; }
    
    .hide-cancel { display: none !important; }

    /* Elite Button Styles */
    .btn-refill-elite { position: relative; width: 100%; padding: 10px; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 800; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; gap: 8px; text-transform: uppercase; background: #f3f4f6; color: #1f2937; transition: all 0.3s ease; }
    .btn-refill-elite:hover { transform: scale(1.02); }
    .btn-refill-elite.ready { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
    .btn-refill-elite.locked { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; opacity: 0.7; cursor: not-allowed; }
    .btn-refill-elite.cooldown { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; cursor: not-allowed; }
    .timer-text { position: relative; z-index: 2; color: #d97706; background: rgba(254, 243, 199, 0.5); padding: 2px 6px; border-radius: 4px; }

    .btn-cancel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: #fee2e2; color: var(--danger); transition: 0.2s; }
    .btn-cancel:hover { background: #fecaca; transform: scale(1.02); }
    
    /* Receipt Button Style */
    .btn-receipt { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 10px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: #e0e7ff; color: #4338ca; transition: 0.2s; }
    .btn-receipt:hover { background: #c7d2fe; transform: scale(1.02); }

    .msg-err { background: #fee2e2; color: #991b1b; padding:12px; border-radius:12px; margin-bottom:15px; text-align:center; font-weight:600; border:1px solid #fecaca; }
    .msg-suc { background: #dcfce7; color: #166534; padding:12px; border-radius:12px; margin-bottom:15px; text-align:center; font-weight:600; border:1px solid #bbf7d0; }
</style>

<div class="sh-container">
    <div class="page-head"><a href="smm_order.php" class="back-circle"><i class="fa-solid fa-chevron-left"></i></a><h2>My Orders</h2></div>

    <?php if ($error): ?><div class="msg-err"><i class="fa-solid fa-triangle-exclamation"></i> <?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg-suc"><i class="fa-solid fa-check-circle"></i> <?= sanitize($success) ?></div><?php endif; ?>

    <?php if (empty($smm_orders)): ?>
        <div style="text-align:center; padding:60px; color:#9ca3af;">
            <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i><br>
            No orders found yet.
        </div>
    <?php else: ?>
        <?php foreach ($smm_orders as $order): ?>
            <?php
                $st = strtolower($order['status']);
                $stClass = 'st-processing';
                $pgGradient = 'pg-processing-gradient pulse-glow'; // Default animation style
                
                if(strpos($st, 'pend')!==false) { 
                    $stClass='st-pending'; 
                    $pgGradient = 'pg-pending-gradient';
                }
                elseif(strpos($st, 'comp')!==false) { 
                    $stClass='st-completed'; 
                    $pgGradient = 'pg-completed-gradient';
                }
                elseif(strpos($st, 'cancel')!==false) { 
                    $stClass='st-cancelled'; 
                    $pgGradient = 'pg-cancelled-gradient';
                }
                elseif(strpos($st, 'partial')!==false) { 
                    $stClass='st-partial'; 
                    $pgGradient = 'pg-processing-gradient';
                }

                $start = (int)$order['start_count']; $qty = (int)$order['quantity']; $remains = (int)$order['remains'];
                $delivered = 0; $current = $start;
                
                // Logic for calculation
                if ($st == 'completed') { $delivered = $qty; $current = $start + $qty; } 
                elseif ($st == 'partial' || ($st == 'in_progress' && $start > 0)) { $delivered = $qty - $remains; $current = $start + $delivered; }
                if ($st == 'cancelled') $remains = $qty; 
                
                // --- PERCENTAGE CALCULATION FOR BAR ---
                $percent = 0;
                if($qty > 0) {
                    $percent = round(($delivered / $qty) * 100);
                }
                if($percent > 100) $percent = 100;
                if($percent < 0) $percent = 0;
                
                // Force full bar if completed, empty if pending (unless manual start count logic exists)
                if($st == 'completed') $percent = 100;
                if($st == 'pending') $percent = 5; // Small start to show it's alive

                $refundAmount = 0.00;
                if ($st == 'partial' || $st == 'cancelled') {
                    $remains_for_refund = (int)$order['remains']; 
                    if ($remains_for_refund > 0) {
                        $charge_per_item = (float)$order['charge'] / (float)$order['quantity'];
                        $refundAmount = $charge_per_item * $remains_for_refund;
                    }
                }

                $displayServiceName = !empty($order['service_name']) ? $order['service_name'] : 'Service Deleted (ID: ' . $order['service_id'] . ')';
                $iconPath = getIconPath($displayServiceName, $icon_base_path, $app_icons);

                // Refill Button Logic
                $canRefill = false;
                $btnState = 'locked'; 
                $btnContent = '<span>🔒 Refill</span>'; 
                $timerAttr = ''; 
                
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
                            $btnState = 'ready';
                            $canRefill = true;
                            $btnContent = '<span>⚡ Refill</span>';
                        }
                    }
                }
                
                $hasCancel = !empty($order['has_cancel']) ? $order['has_cancel'] : 0;
                $canCancel = ($hasCancel && ($st == 'pending' || $st == 'in_progress'));
                $hideCancel = ($hasCancel == 0);
            ?>

            <div class="sh-card target-card">
                <?php if (!empty($iconPath)): ?>
                    <div class="card-watermark" style="background-image: url('<?= $iconPath ?>');"></div>
                <?php endif; ?>
                
                <div class="sh-header">
                    <div class="sh-title">
                        <span class="service-name-text"><?= sanitize($displayServiceName) ?></span>
                    </div>
                    <div class="sh-price"><?= formatCurrency($order['charge']) ?></div>
                </div>
                <div class="sh-meta">
                    <span class="sh-badge <?= $stClass ?>"><?= ucfirst($order['status']) ?></span>
                    <?php if ($st == 'partial' || $st == 'cancelled'): ?>
                        <?php if ($refundAmount > 0.01): ?>
                            <span style="font-weight: 700; color: var(--refund-color); font-size: 0.8rem;">
                                Refunded: <?= formatCurrency($refundAmount) ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <span>ID: #<?= $order['id'] ?></span>
                </div>

                <div class="progress-area">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.75rem; font-weight:700; color:var(--text-light);">
                        <span>Progress</span>
                        <span><?= $percent ?>%</span>
                    </div>
                    <div class="cute-progress-track">
                        <div class="cute-progress-fill <?= $pgGradient ?>" style="width: <?= $percent ?>%;">
                        </div>
                    </div>
                </div>

                <div class="sh-stats-grid">
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Start</span><span class="sh-stat-val"><?= number_format($start) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Delivered</span><span class="sh-stat-val"><?= number_format($delivered) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Remains</span><span class="sh-stat-val"><?= number_format($remains) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Current</span><span class="sh-stat-val"><?= number_format($current) ?></span></div>
                </div>
                <div class="sh-footer">
                    <a href="<?= sanitize($order['link']) ?>" target="_blank" class="sh-link"><i class="fa-solid fa-link"></i> <?= substr(sanitize($order['link']), 0, 40) . '...' ?></a>
                    <div style="display:flex; align-items:center; gap:5px;"><span>📅 <?= date('d M, Y h:i A', strtotime($order['created_at'])) ?></span></div>
                </div>
                <div class="sh-actions">
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="action" value="refill">
                        <button type="submit" class="btn-refill-elite <?= $btnState ?>" <?= $canRefill ? '' : 'disabled' ?> <?= $timerAttr ?>>
                            <?= $btnContent ?>
                        </button>
                    </form>
                    
                    <button type="button" class="btn-receipt" onclick="generateReceipt(this, '<?= $order['id'] ?>')">
                        <i class="fa-solid fa-file-invoice"></i> Receipt
                    </button>

                    <form method="POST" style="flex:1;" class="<?= $hideCancel ? 'hide-cancel' : '' ?>">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-cancel" <?= $canCancel ? '' : 'disabled' ?>>✕ Cancel</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="receipt-gen-area" style="position:fixed; top:-9999px; left:-9999px; background:#fff; padding:20px;">
    </div>

<input type="hidden" id="site-logo-url" value="<?= $logo_url ?>">

<script>
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

function generateReceipt(btn, orderId) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; // Loading state

    // 1. Clone the specific card
    const card = btn.closest('.sh-card');
    const clone = card.cloneNode(true);

    // 2. Prepare the clone for Receipt
    // Remove the actions div (buttons)
    const actionsDiv = clone.querySelector('.sh-actions');
    if(actionsDiv) actionsDiv.remove();
    
    // Remove Progress Bar for receipt (Clean look)
    const progressDiv = clone.querySelector('.progress-area');
    if(progressDiv) progressDiv.remove();

    // Style the clone for receipt look
    clone.style.width = "500px"; // Fixed width for receipt
    clone.style.padding = "30px";
    clone.style.border = "2px solid #e5e7eb";
    clone.style.borderRadius = "20px";
    clone.style.background = "#fff";
    clone.style.boxShadow = "none";
    clone.style.position = "relative"; // Ensure watermark works

    // Add Logo at the top
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

    // 3. Append to hidden area
    const genArea = document.getElementById('receipt-gen-area');
    genArea.innerHTML = ''; // Clear previous
    genArea.appendChild(clone);

    // 4. Capture
    html2canvas(clone, {
        scale: 2, // Better quality
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const a = document.createElement('a');
        a.download = 'Order-Receipt-' + orderId + '.jpg';
        a.href = canvas.toDataURL('image/jpeg', 0.95);
        a.click();
        
        btn.innerHTML = originalText;
        genArea.innerHTML = ''; // Cleanup
    }).catch(err => {
        alert('Error generating receipt');
        console.error(err);
        btn.innerHTML = originalText;
    });
}

document.addEventListener('DOMContentLoaded', startEliteCountdowns);
</script>

<?php include '_smm_footer.php'; ?>