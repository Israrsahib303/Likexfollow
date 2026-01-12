<?php
include '_smm_header.php';

// --- 1. SETTINGS & CURRENCY ---
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = (function_exists('getCurrencyRate')) ? getCurrencyRate($curr_code) : 1;

$currency_symbols = [
    'PKR' => 'Rs', 'USD' => '$', 'INR' => '₹', 'EUR' => '€', 
    'GBP' => '£', 'SAR' => '﷼', 'AED' => 'د.إ'
];
$curr_symbol = $currency_symbols[$curr_code] ?? $curr_code;

// Site Details
$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow.com'; 
$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
$wa_number = $GLOBALS['settings']['whatsapp_number'] ?? '+92 309 7856447'; 
$logo_path = !empty($site_logo) ? "../assets/img/$site_logo" : "";

// --- 2. HELPER: CATEGORY ICON ---
function getCatIcon($name) {
    $apps = ['Instagram', 'TikTok', 'Youtube', 'Facebook', 'Twitter', 'Spotify', 'Telegram', 'Whatsapp', 'Snapchat', 'Netflix', 'Canva', 'Pubg'];
    foreach ($apps as $app) {
        if (stripos($name, $app) !== false) return "../assets/img/icons/$app.png";
    }
    return "../assets/img/icons/smm.png"; 
}

// --- 3. FETCH UPDATES ---
$updates = $db->query("
    SELECT u.*, s.service_rate AS live_rate 
    FROM service_updates u
    LEFT JOIN smm_services s ON u.service_id = s.id
    ORDER BY u.created_at DESC LIMIT 50
")->fetchAll();
?>

<script>
    window.siteData = { logo: "<?=$logo_path?>", name: "<?=$site_name?>", wa: "<?=$wa_number?>" };
    window.currency = { sym: "<?=$curr_symbol?>", code: "" };
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary: #4f46e5;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
    --bg: #f8fafc;
    --card: #ffffff;
}
body { background: var(--bg); color: #1e293b; font-family: 'Outfit', sans-serif; }

.updates-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }

.page-head { text-align: center; margin-bottom: 40px; animation: fadeInDown 0.8s ease; }
.page-title { font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, var(--primary), #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
.page-sub { color: #64748b; margin-top: 5px; }

/* --- WEB CARD DESIGN (Landscape) --- */
.update-card {
    background: var(--card); border-radius: 20px; padding: 25px; margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    display: flex; align-items: flex-start; gap: 20px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative; overflow: hidden; animation: fadeInUp 0.5s ease backwards;
    padding-left: 35px; /* Padding for thick bar */
}
.update-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.1); border-color: var(--primary); }

/* --- HIGH VOLTAGE LASER ANIMATION --- */
.update-card::before { 
    content:''; position:absolute; left:0; top:0; bottom:0; 
    width: 12px; /* Moti Line */
    z-index: 2;
    background-size: 100% 200%; /* Large vertical gradient */
    animation: laserScan 1.5s linear infinite; /* FAST Scanning Effect */
}

@keyframes laserScan {
    0% { background-position: 0% 0%; }
    100% { background-position: 0% 200%; } /* Moves continuously down */
}

/* THE "SURPRISE" COLORS 
   Structure: Dark Color -> Neon Color -> WHITE HOT CENTER -> Neon Color -> Dark Color
   This creates a shining light effect.
*/

/* 1. New Service (Matrix Green Laser) */
.type-new::before { 
    background-image: linear-gradient(180deg, #047857, #10b981, #ffffff, #10b981, #047857);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); /* STRONG GLOW */
}

/* 2. REMOVED (Red Alert - Emergency Light) */
.type-removed::before { 
    background-image: linear-gradient(180deg, #7f1d1d, #ef4444, #ffbfbf, #ef4444, #7f1d1d);
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.6);
}

/* 3. Restocked (Cyber Blue) */
.type-enabled::before { 
    background-image: linear-gradient(180deg, #312e81, #4f46e5, #ffffff, #4f46e5, #312e81);
    box-shadow: 0 0 20px rgba(79, 70, 229, 0.6);
}

/* 4. Price Changes (Gold & Ice) */
.type-price_increase::before { 
    background-image: linear-gradient(180deg, #78350f, #f59e0b, #ffffff, #f59e0b, #78350f);
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.6);
}
.type-price_decrease::before { 
    background-image: linear-gradient(180deg, #1e3a8a, #3b82f6, #ffffff, #3b82f6, #1e3a8a);
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
}

/* Icons & Content */
.platform-icon {
    width: 55px; height: 55px; border-radius: 14px; object-fit: cover;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-shrink: 0; background: #fff; padding: 4px; border: 1px solid #f1f5f9;
}
.content { flex: 1; display: flex; flex-direction: column; justify-content: center; padding-top: 2px; min-width: 0; }

/* Badges */
.badge-label { 
    font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; 
    padding: 4px 10px; border-radius: 6px; display: inline-block; margin-bottom: 8px; width: fit-content;
}
.bg-new { background: #dcfce7; color: #15803d; }
.bg-removed { background: #fee2e2; color: #b91c1c; }
.bg-enabled { background: #e0e7ff; color: #4338ca; }
.bg-inc { background: #fef3c7; color: #b45309; }
.bg-dec { background: #dbeafe; color: #1d4ed8; }

/* Typography */
.svc-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; line-height: 1.5; white-space: normal; word-break: break-word; }
.cat-name { font-size: 0.9rem; color: #64748b; margin-top: 4px; font-weight: 500; white-space: normal; word-break: break-word; }
.time-ago { font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 5px; margin-top: 10px; font-weight: 600; }

/* Actions */
.action-box { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px; flex-shrink: 0; padding-top: 5px; }
.price-tag { font-size: 1.2rem; font-weight: 800; color: var(--primary); background: #eef2ff; padding: 6px 12px; border-radius: 10px; }
.btn-dl {
    background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; 
    width: 40px; height: 40px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;
    font-size: 1.2rem;
}
.btn-dl:hover { background: var(--primary); color: #fff; border-color: var(--primary); transform: scale(1.1); box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); }

/* --- HIDDEN CANVAS FOR CAPTURE (Portrait Fixed) --- */
#receipt-node {
    position: fixed; left: -9999px; top: 0;
    width: 480px; /* PORTRAIT WIDTH */
    background: #ffffff; /* FORCE WHITE BG */
    box-sizing: border-box;
}

@keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeInDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }

@media (max-width: 600px) {
    .update-card { flex-direction: column; align-items: flex-start; } 
    .action-box { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 10px; }
    .svc-name { font-size: 1rem; }
}
</style>

<div class="updates-container">
    <div class="page-head">
        <h1 class="page-title">Live Updates</h1>
        <p class="page-sub">Latest services, price drops & new stocks.</p>
    </div>

    <?php if (empty($updates)): ?>
        <div class="empty-state" style="text-align:center; padding:60px; color:#94a3b8;">
            <div style="font-size:60px; margin-bottom:15px; animation:float 3s infinite;">📦</div>
            <h3 style="font-weight:700;">No Recent Updates</h3>
            <p>Check back later for new services!</p>
        </div>
    <?php else: ?>
        <?php foreach($updates as $u): 
            $base_rate = ($u['live_rate'] !== null) ? (float)$u['live_rate'] : (float)$u['rate'];
            $final_rate = ($curr_code != 'PKR') ? $base_rate * $curr_rate : $base_rate;
            
            // Logic
            $typeClass = 'type-new'; $badgeClass = 'bg-new'; $badgeText = "New Arrival";
            if($u['type'] == 'removed') { $typeClass = 'type-removed'; $badgeClass = 'bg-removed'; $badgeText = "Removed"; }
            elseif($u['type'] == 'enabled') { $typeClass = 'type-enabled'; $badgeClass = 'bg-enabled'; $badgeText = "Restocked"; }
            elseif($u['type'] == 'price_increase') { $typeClass = 'type-price_increase'; $badgeClass = 'bg-inc'; $badgeText = "Price Increased"; }
            elseif($u['type'] == 'price_decrease') { $typeClass = 'type-price_decrease'; $badgeClass = 'bg-dec'; $badgeText = "Price Dropped"; }

            $iconPath = getCatIcon($u['category_name']);
            $p_formatted = $curr_symbol . ' ' . number_format($final_rate, 2);
            $timeAgo = time_elapsed_string($u['created_at']);
        ?>
        <div class="update-card <?= $typeClass ?>">
            <img src="<?= $iconPath ?>" class="platform-icon" onerror="this.src='../assets/img/icons/smm.png'">
            
            <div class="content">
                <span class="badge-label <?= $badgeClass ?>"><?= $badgeText ?></span>
                <div class="svc-name"><?= sanitize($u['service_name']) ?></div>
                <div class="cat-name"><?= sanitize($u['category_name']) ?></div>
                <div class="time-ago">
                    <i class="fa-regular fa-clock"></i> <?= $timeAgo ?>
                </div>
            </div>

            <div class="action-box">
                <div class="price-tag"><?= $p_formatted ?></div>
                <?php if($u['type'] != 'removed'): ?>
                <button class="btn-dl" title="Download Status Card" 
                    onclick="genCustomReceipt({
                        name: '<?= addslashes(sanitize($u['service_name'])) ?>',
                        price: '<?= $p_formatted ?>',
                        cat: '<?= addslashes(sanitize($u['category_name'])) ?>',
                        typeClass: '<?= $typeClass ?>',
                        badgeClass: '<?= $badgeClass ?>',
                        badgeText: '<?= $badgeText ?>',
                        icon: '<?= $iconPath ?>',
                        time: '<?= $timeAgo ?>'
                    })">
                    📸
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="receipt-node"></div>

<script>
// --- PORTRAIT CARD GENERATOR ---
function genCustomReceipt(data) {
    const node = document.getElementById('receipt-node');
    
    // We construct a specific PORTRAIT HTML structure
    // Reuse existing classes for colors, but change layout to Vertical Column
    const cardHTML = `
        <div class="update-card ${data.typeClass}" style="
            display: flex;
            flex-direction: column; /* VERTICAL LAYOUT */
            align-items: center;     /* CENTER ALIGN */
            text-align: center;
            width: 100%;             /* Fills the 480px node */
            padding: 50px 30px;
            box-shadow: none;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            gap: 15px;
            margin: 0;
            padding-left: 30px; /* Keep padding balanced */
            /* Note: The animated bar comes from the class ${data.typeClass} */
        ">
            <img src="${data.icon}" style="
                width: 90px; 
                height: 90px; 
                border-radius: 22px; 
                margin-bottom: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.08);
                border: 1px solid #f1f5f9;
            ">
            
            <span class="badge-label ${data.badgeClass}" style="
                font-size: 0.9rem; 
                padding: 6px 16px;
                border-radius: 8px;
            ">
                ${data.badgeText}
            </span>

            <div style="width: 100%; margin-top:5px;">
                <div class="svc-name" style="font-size: 1.5rem; margin-bottom: 8px; line-height:1.3; color:#1e293b;">${data.name}</div>
                <div class="cat-name" style="font-size: 1.1rem; opacity:0.8; color:#64748b;">${data.cat}</div>
            </div>

            <div class="price-tag" style="
                font-size: 2rem; 
                padding: 12px 30px; 
                margin-top: 15px;
                border-radius: 14px;
            ">
                ${data.price}
            </div>

            <div style="margin-top: 25px; color: #94a3b8; font-size: 0.9rem; display:flex; align-items:center; gap:8px; font-weight:500;">
                 <i class="fa-regular fa-clock"></i> ${data.time} &bull; ${window.siteData.name}
            </div>
        </div>
    `;

    node.innerHTML = cardHTML;

    // Capture with High Quality & WHITE Background
    setTimeout(() => {
        html2canvas(node, { 
            scale: 4, // 4x Scale = Very Sharp Image
            useCORS: true,
            backgroundColor: "#ffffff" // Force White Background
        }).then(canvas => {
            let link = document.createElement('a');
            link.download = 'Service-Update-' + Date.now() + '.png';
            link.href = canvas.toDataURL();
            link.click();
            
            node.innerHTML = ''; 
        });
    }, 150);
}
</script>

<?php 
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($string as $k => &$v) {
        if ($k === 'w') $value = floor($diff->d / 7); else $value = $diff->$k;
        if ($k === 'd') $value -= floor($diff->d / 7) * 7;
        if ($value) $v = $value . ' ' . $v . ($value > 1 ? 's' : ''); else unset($string[$k]);
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
include '_smm_footer.php'; 
?>