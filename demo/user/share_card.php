<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Agar user login nahi hai to login page par bhejo
if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$site_name = $GLOBALS['settings']['site_name'] ?? 'Beast Panel';
$wa_number = $GLOBALS['settings']['whatsapp_number'] ?? '';

// --- 1. DETERMINE MODE (Receipt vs Marketing) ---
$mode = 'marketing'; // Default
$data = [];

if (isset($_GET['id'])) {
    // === RECEIPT MODE (Order ID) ===
    $mode = 'receipt';
    $order_id = (int)$_GET['id'];
    
    // Fetch Order
    $stmt = $db->prepare("SELECT o.*, s.name as service_name FROM smm_orders o JOIN smm_services s ON o.service_id = s.id WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if(!$order) {
        // Try Subscription Table
        $stmt = $db->prepare("SELECT o.*, p.name as service_name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ? AND o.user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();
    }

    if($order) {
        $data['name'] = $order['service_name'];
        $data['price'] = $order['charge'] ?? $order['total_price'];
        $data['status'] = '✅ PAID / ACTIVE';
        $data['tag'] = 'VERIFIED PURCHASE';
        $data['qty'] = $order['quantity'] ?? '1 Month';
        $data['color'] = '#10b981'; // Green for Success
    }

} elseif (isset($_GET['svc'])) {
    // === MARKETING MODE (Service ID) ===
    $mode = 'offer';
    $svc_id = (int)$_GET['svc'];
    
    // Fetch Service
    $stmt = $db->prepare("SELECT * FROM smm_services WHERE id = ?");
    $stmt->execute([$svc_id]);
    $svc = $stmt->fetch();

    if($svc) {
        $data['name'] = $svc['name'];
        $data['price'] = $svc['service_rate']; // Rate per 1000
        $data['status'] = '🔥 HOT SELLING';
        $data['tag'] = 'LIMITED TIME OFFER';
        $data['qty'] = 'Per 1000';
        $data['color'] = '#f59e0b'; // Orange for Offer
    }
}

if (empty($data)) die("Invalid Link");

// --- 2. PENDU FRIENDLY VISUALS ---
// Naam aur Rang khud set karein taake anpadh banda bhi rang dekh kar samajh jaye
$bg_color = "#4f46e5"; // Default Blue
$icon_emoji = "🚀";

if (stripos($data['name'], 'Netflix') !== false) { $bg_color = "#E50914"; $icon_emoji = "🍿"; }
elseif (stripos($data['name'], 'TikTok') !== false) { $bg_color = "#000000"; $icon_emoji = "🎵"; }
elseif (stripos($data['name'], 'Instagram') !== false) { $bg_color = "#E1306C"; $icon_emoji = "📸"; }
elseif (stripos($data['name'], 'YouTube') !== false) { $bg_color = "#FF0000"; $icon_emoji = "▶️"; }
elseif (stripos($data['name'], 'PUBG') !== false) { $bg_color = "#facc15"; $icon_emoji = "🔫"; }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Card</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            background-color: #eef2ff;
            font-family: 'Outfit', sans-serif;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 100vh; margin: 0; padding: 20px;
        }

        /* --- THE POSTER --- */
        .poster {
            width: 100%; max-width: 380px;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.25);
            overflow: hidden;
            position: relative;
            text-align: center;
            border: 6px solid #fff;
        }

        /* Top Banner */
        .poster-top {
            background: <?= $data['color'] ?>; color: #fff;
            padding: 12px; font-weight: 800; font-size: 1.1rem;
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* Main Content */
        .poster-content {
            padding: 30px 20px;
            background: radial-gradient(circle at top, #fff 40%, #f8fafc 100%);
        }

        /* Huge Icon */
        .p-icon {
            font-size: 5rem; margin-bottom: 10px;
            filter: drop-shadow(0 15px 15px rgba(0,0,0,0.15));
            display: inline-block;
            animation: float 3s ease-in-out infinite;
        }

        /* Product Title */
        .p-title {
            font-size: 1.8rem; font-weight: 900; line-height: 1.2;
            color: #1e293b; margin-bottom: 5px;
        }
        
        /* Category Badge */
        .p-badge {
            display: inline-block; background: #f1f5f9; color: #64748b;
            padding: 5px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700;
            text-transform: uppercase; margin-bottom: 25px;
        }

        /* Price Area (Eye Catching) */
        .p-price-box {
            background: <?= $bg_color ?>; color: #fff;
            padding: 15px; border-radius: 16px;
            box-shadow: 0 10px 25px -5px <?= $bg_color ?>80;
            transform: rotate(-2deg); margin: 0 20px;
        }
        .p-price-lbl { font-size: 0.8rem; opacity: 0.9; font-weight: 600; text-transform: uppercase; }
        .p-price-val { font-size: 2.2rem; font-weight: 900; }

        /* Footer (Marketing) */
        .poster-foot {
            background: #1e293b; padding: 15px; color: #fff;
        }
        .foot-wa {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-size: 1.2rem; font-weight: 800; color: #4ade80;
        }

        /* --- BUTTONS --- */
        .btn-group { display: flex; gap: 10px; margin-top: 25px; width: 100%; max-width: 380px; }
        .btn {
            flex: 1; padding: 15px; border: none; border-radius: 12px;
            font-weight: 700; font-size: 1rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: 0.2s; text-decoration: none;
        }
        .btn-save { background: #fff; border: 2px solid #e2e8f0; color: #334155; }
        .btn-share { background: #25D366; color: #fff; box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3); }
        .btn:hover { transform: translateY(-3px); }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    </style>
</head>
<body>

    <div class="poster" id="card">
        <div class="poster-top">
            <?= $data['status'] ?>
        </div>
        
        <div class="poster-content">
            <div class="p-icon"><?= $icon_emoji ?></div>
            <div class="p-title"><?= $data['name'] ?></div>
            <div class="p-badge"><?= $data['tag'] ?></div>

            <div class="p-price-box">
                <div class="p-price-lbl">Sirf (Only)</div>
                <div class="p-price-val"><?= formatCurrency($data['price']) ?></div>
                <div class="p-price-lbl" style="margin-top:5px; font-size:0.7rem;"><?= $data['qty'] ?></div>
            </div>
        </div>

        <div class="poster-foot">
            <div style="font-size:0.8rem; margin-bottom:5px; opacity:0.7;">Order on WhatsApp 👇</div>
            <div class="foot-wa">
                <img src="../assets/img/icons/Whatsapp.png" width="24"> <?= $wa_number ?>
            </div>
        </div>
    </div>

    <div class="btn-group">
        <button class="btn btn-save" onclick="saveCard()">
            📸 Save
        </button>
        <a href="https://wa.me/?text=Check%20this%20amazing%20offer!%20<?= urlencode($data['name']) ?>%20for%20<?= formatCurrency($data['price']) ?>" class="btn btn-share">
            🚀 Share
        </a>
    </div>

    <p style="margin-top:20px; color:#64748b; font-size:0.9rem;">
        <a href="index.php" style="color:#64748b; text-decoration:none;">← Back Home</a>
    </p>

    <script>
    function saveCard() {
        const btn = document.querySelector('.btn-save');
        btn.innerText = '⏳ Saving...';
        html2canvas(document.getElementById('card'), { scale: 3 }).then(canvas => {
            let link = document.createElement('a');
            link.download = 'Offer-<?= time() ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            btn.innerText = '📸 Save';
        });
    }
    </script>

</body>
</html>