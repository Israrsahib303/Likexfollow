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
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: 1px solid #e5e7eb; 
        position: relative; overflow: hidden;
    }
    
    .card-watermark {
        position: absolute; top: 0; right: 0; width: 100%; height: 100%; 
        opacity: 0.10; background-repeat: no-repeat; background-size: 200px; 
        background-position: top 20px right 20px; pointer-events: none;
        filter: brightness(120%); z-index: 1; 
    }
    
    .sh-header, .sh-meta, .sh-stats-grid, .sh-footer, .sh-actions { position: relative; z-index: 5; }

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

    .sh-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; background: #f9fafb; padding: 10px; border-radius: 10px; margin-bottom: 15px; }
    .sh-stat-item { text-align: center; }
    .sh-stat-lbl { font-size: 0.7rem; color: var(--text-light); display: block; margin-bottom: 2px; }
    .sh-stat-val { font-size: 0.9rem; font-weight: 700; color: var(--text-dark); }

    .sh-footer { font-size: 0.8rem; color: var(--text-light); border-top: 1px dashed #e5e7eb; padding-top: 10px; margin-bottom: 15px; }
    .sh-link { color: var(--primary); text-decoration: none; word-break: break-all; display: block; margin-bottom: 5px; }
    
    .sh-actions { display: flex; gap: 10px; margin-top: 10px; }
    
    .hide-cancel { display: none !important; }

    /* Elite Button Styles */
    .btn-refill-elite { position: relative; width: 100%; padding: 10px; border: none; border-radius: 8px; font-size: 0.85rem; font-weight: 800; cursor: pointer; overflow: hidden; display: flex; align-items: center; justify-content: center; gap: 8px; text-transform: uppercase; background: #f3f4f6; color: #1f2937; }
    .btn-refill-elite.ready { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; }
    .btn-refill-elite.locked { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; opacity: 0.7; cursor: not-allowed; }
    .btn-refill-elite.cooldown { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; cursor: not-allowed; }
    .timer-text { position: relative; z-index: 2; color: #d97706; background: rgba(254, 243, 199, 0.5); padding: 2px 6px; border-radius: 4px; }

    .btn-cancel { flex: 1; display: flex; align-items: center; justify-content: center; padding: 10px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: #fee2e2; color: var(--danger); transition: 0.2s; }
    
    /* Receipt Button Style */
    .btn-receipt { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 10px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.85rem; cursor: pointer; background: #e0e7ff; color: #4338ca; transition: 0.2s; }
    .btn-receipt:hover { background: #c7d2fe; }

    .msg-err { background: #fee2e2; color: #991b1b; padding:10px; border-radius:8px; margin-bottom:10px; text-align:center; }
    .msg-suc { background: #dcfce7; color: #166534; padding:10px; border-radius:8px; margin-bottom:10px; text-align:center; }
</style>

<div class="sh-container">
    <div class="page-head"><a href="smm_order.php" class="back-circle">❮</a><h2>My Orders</h2></div>

    <?php if ($error): ?><div class="msg-err"><?= sanitize($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg-suc"><?= sanitize($success) ?></div><?php endif; ?>

    <?php if (empty($smm_orders)): ?>
        <div style="text-align:center; padding:40px; color:#999;">No orders found.</div>
    <?php else: ?>
        <?php foreach ($smm_orders as $order): ?>
            <?php
                $st = strtolower($order['status']);
                $stClass = 'st-processing';
                if(strpos($st, 'pend')!==false) $stClass='st-pending';
                elseif(strpos($st, 'comp')!==false) $stClass='st-completed';
                elseif(strpos($st, 'cancel')!==false) $stClass='st-cancelled';
                elseif(strpos($st, 'partial')!==false) $stClass='st-partial';

                $start = (int)$order['start_count']; $qty = (int)$order['quantity']; $remains = (int)$order['remains'];
                $delivered = 0; $current = $start;
                
                if ($st == 'completed') { $delivered = $qty; $current = $start + $qty; } 
                elseif ($st == 'partial' || ($st == 'in_progress' && $start > 0)) { $delivered = $qty - $remains; $current = $start + $delivered; }
                if ($st == 'cancelled') $remains = $qty; 
                
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
                    <span>Qty: <?= $order['quantity'] ?></span>
                </div>
                <div class="sh-stats-grid">
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Start</span><span class="sh-stat-val"><?= number_format($start) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Delivered</span><span class="sh-stat-val"><?= number_format($delivered) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Remains</span><span class="sh-stat-val"><?= number_format($remains) ?></span></div>
                    <div class="sh-stat-item"><span class="sh-stat-lbl">Current</span><span class="sh-stat-val"><?= number_format($current) ?></span></div>
                </div>
                <div class="sh-footer">
                    <a href="<?= sanitize($order['link']) ?>" target="_blank" class="sh-link">🔗 <?= substr(sanitize($order['link']), 0, 40) . '...' ?></a>
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