<?php
include '_header.php';

// --- 1. SETTINGS & LOGO SETUP ---
$db_logo = $settings['site_logo'] ?? '';
if (file_exists("../assets/img/logo.png")) {
    $receipt_logo = "../assets/img/logo.png";
} elseif (!empty($db_logo) && file_exists("../assets/img/" . $db_logo)) {
    $receipt_logo = "../assets/img/" . $db_logo;
} else {
    $receipt_logo = "https://via.placeholder.com/150x50/000000/ffffff?text=STORE";
}

$wa_number = $settings['whatsapp_number'] ?? '+92 309 7856447'; 

// --- 2. HANDLE PURCHASE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buy_product'])) {
    $p_id = (int)$_POST['product_id'];
    $price = (float)$_POST['price'];
    
    // Balance Check
    $stmt_bal = $db->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt_bal->execute([$user_id]);
    $current_bal = $stmt_bal->fetchColumn();

    if ($current_bal < $price) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Insufficient Funds', 
                    text: 'Please recharge your wallet.', 
                    confirmButtonColor: '#7c3aed'
                });
            });
        </script>";
    } else {
        try {
            $db->beginTransaction();
            
            // Deduct Amount
            $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$price, $user_id]);
            
            // Generate Order
            $code = 'INV-' . strtoupper(bin2hex(random_bytes(3))) . date('s');
            $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, total_price, status, code, created_at) VALUES (?, ?, ?, 'completed', ?, NOW())");
            $stmt->execute([$user_id, $p_id, $price, $code]);
            $order_db_id = $db->lastInsertId();
            
            // Ledger Entry
            $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, ref_type, ref_id, note) VALUES (?, 'debit', ?, 'order', ?, 'Product Purchase')")->execute([$user_id, $price, $order_db_id]);
            
            $db->commit();
            
            // --- LIVE UPDATE BALANCE ---
            $user_balance = $current_bal - $price; 

            // Fetch Item Data
            $item = $db->query("SELECT name, icon, description, download_link, original_price FROM products WHERE id=$p_id")->fetch();
            $item_icon = !empty($item['icon']) ? "../assets/img/".$item['icon'] : "https://via.placeholder.com/80";
            
            echo "<script>
                if ( window.history.replaceState ) {
                    window.history.replaceState( null, null, window.location.href );
                }
                window.onload = function() {
                    confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, colors: ['#7c3aed', '#a78bfa'] });
                    showReceipt(
                        '$code', 
                        '".date('M d, Y • h:i A')."', 
                        '".addslashes($item['name'])."', 
                        '$price', 
                        '{$item['download_link']}',
                        '$item_icon',
                        '$receipt_logo'
                    );
                };
            </script>";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    }
}

// --- 3. DATA ---
$stats = $db->prepare("SELECT COUNT(o.id) as count, COALESCE(SUM(o.total_price), 0) as spent FROM orders o LEFT JOIN products p ON o.product_id = p.id WHERE o.user_id = ? AND o.status = 'completed' AND (p.is_digital = 1 OR o.code LIKE 'INV-%')");
$stats->execute([$user_id]);
$s = $stats->fetch();

$products = $db->query("SELECT * FROM products WHERE is_digital = 1 AND is_active = 1 ORDER BY id DESC")->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&family=Outfit:wght@300;500;700;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
/* --- PURPLE THEME --- */
:root {
    --bg: #f5f3ff;
    --white: #ffffff;
    --primary: #7c3aed;
    --primary-dark: #5b21b6;
    --primary-light: #ddd6fe;
    --text-dark: #1e1b4b;
    --text-gray: #6b7280;
    --radius-lg: 24px;
    --radius-md: 16px;
    --font-base: 'Inter', sans-serif;
    --font-mono: 'Space Mono', monospace;
}

body {
    background: var(--bg);
    font-family: var(--font-base);
    color: var(--text-dark);
    margin: 0; padding: 0;
    -webkit-font-smoothing: antialiased;
}

/* ANIMATIONS */
@keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
@keyframes floatBlob { 0% { transform: translateY(0px) scale(1); } 50% { transform: translateY(-15px) scale(1.05); } 100% { transform: translateY(0px) scale(1); } }
@keyframes softPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.4); }
    70% { transform: scale(1.02); box-shadow: 0 0 0 6px rgba(124, 58, 237, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); }
}

.screen-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    padding-bottom: 80px;
}

/* HEADER CARD */
.purple-header {
    background: linear-gradient(-45deg, #7c3aed, #6d28d9, #4c1d95, #8b5cf6);
    background-size: 400% 400%;
    animation: gradientMove 12s ease infinite;
    border-radius: var(--radius-lg);
    padding: 30px;
    color: var(--white);
    box-shadow: 0 20px 40px rgba(124, 58, 237, 0.3);
    margin-bottom: 30px;
    position: relative; overflow: hidden; z-index: 1;
}
.purple-header::after {
    content: ''; position: absolute; bottom: -30px; right: -30px;
    width: 180px; height: 180px;
    background: radial-gradient(circle, rgba(255,255,255,0.25) 0%, transparent 70%);
    border-radius: 50%; animation: floatBlob 6s ease-in-out infinite; z-index: -1;
}
.bal-amount { font-size: 40px; font-weight: 800; margin: 5px 0 20px 0; }
.stats-row { display: flex; gap: 15px; }
.stat-pill { background: rgba(255,255,255,0.15); padding: 8px 16px; border-radius: var(--radius-md); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1); }
.stat-val { font-weight: 700; font-size: 15px; } 
.stat-lbl { font-size: 11px; opacity: 0.8; }

/* --- PRODUCT CARDS --- */
.section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: var(--text-dark); }

.grid-products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.prod-card {
    background: var(--white);
    border-radius: 20px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    border: 1px solid transparent;
    position: relative;
}
.prod-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(124, 58, 237, 0.12); border-color: var(--primary-light); }

.prod-img-box {
    width: 100%;
    height: 180px;
    border-radius: 16px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    box-sizing: border-box;
    border: 1px solid #f1f5f9;
}

.prod-img { 
    width: 100%; 
    height: 100%; 
    object-fit: contain; 
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.08));
    transition: transform 0.3s ease; 
}
.prod-card:hover .prod-img { transform: scale(1.05); }

.prod-info { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.prod-name { font-weight: 700; font-size: 16px; color: var(--text-dark); line-height: 1.3; margin-top: 5px; }

/* TAGS & DESC */
.prod-tags-row { display: flex; gap: 8px; margin-top: 4px; margin-bottom: 4px; flex-wrap: wrap; }
.tag-pill { font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; color: white; box-shadow: 0 2px 6px rgba(124, 58, 237, 0.25); animation: softPulse 2s infinite ease-in-out; }
.tag-pill.pri { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }
.tag-pill.sec { background: linear-gradient(135deg, #1e1b4b, #312e81); animation-delay: 0.5s; }
.tag-pill.offer { background: linear-gradient(135deg, #ef4444, #f87171); text-decoration: line-through; opacity: 0.9; animation-delay: 1s; }

.prod-desc-box {
    font-size: 12px; color: var(--text-gray); line-height: 1.5; max-height: 80px; overflow-y: auto;
    padding-right: 5px; border-left: 2px solid #eee; padding-left: 8px; margin: 5px 0;
}
.prod-desc-box::-webkit-scrollbar { width: 4px; }
.prod-desc-box::-webkit-scrollbar-track { background: transparent; }
.prod-desc-box::-webkit-scrollbar-thumb { background: #e0e7ff; border-radius: 10px; }

.prod-footer { margin-top: auto; padding-top: 12px; border-top: 1px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
.prod-price { font-weight: 800; font-size: 18px; color: var(--primary); }
.prod-actions { display: flex; gap: 8px; }
.btn-buy-icon, .btn-dl-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: none; font-size: 14px; transition: 0.2s; cursor: pointer; }
.btn-buy-icon { background: var(--text-dark); color: white; }
.btn-dl-icon { background: #f3f4f6; color: #6b7280; font-size: 16px; }
.prod-card:hover .btn-buy-icon { background: var(--primary); transform: rotate(-45deg); }
.prod-card:hover .btn-dl-icon { background: #dcfce7; color: #16a34a; transform: scale(1.1); }

/* MODALS & RECEIPT */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(18, 19, 25, 0.7); backdrop-filter: blur(8px); z-index: 9999; display: none; justify-content: center; align-items: center; padding: 15px; }
.modal-overlay.active { display: flex; animation: fadeUp 0.3s ease; }
.confirm-card { background: var(--white); width: 100%; max-width: 340px; border-radius: 24px; padding: 25px; text-align: center; }
.c-icon { width: 60px; height: 60px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px; }
.receipt-wrapper { background: transparent; width: 300px; max-width: 100%; margin: auto; }
.receipt-paper { background: var(--white); border-radius: 16px; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); mask-image: radial-gradient(circle at 10px bottom, transparent 10px, black 11px); mask-position: -10px bottom; mask-size: 20px 100%; mask-repeat: repeat-x; -webkit-mask-image: radial-gradient(circle at 10px bottom, transparent 10px, black 11px); -webkit-mask-position: -10px bottom; -webkit-mask-size: 20px 100%; -webkit-mask-repeat: repeat-x; padding-bottom: 20px; }
.rp-content { padding: 20px; }
.rp-header { text-align: center; border-bottom: 2px dashed #e5e7eb; padding-bottom: 15px; margin-bottom: 15px; }
.rp-logo { height: 30px; margin-bottom: 5px; }
.rp-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
.rp-item { background: var(--bg); border-radius: 10px; padding: 10px; display: flex; gap: 10px; align-items: center; margin: 15px 0; }
.rp-thumb { width: 40px; height: 40px; background: white; border-radius: 8px; object-fit: cover; }
.rp-total { border-top: 2px dashed #e5e7eb; padding-top: 10px; text-align: right; }
.rp-price-lg { font-size: 22px; font-weight: 800; color: var(--primary); }
.rp-actions { margin-top: 15px; display: flex; flex-direction: column; gap: 8px; }
.btn-dl, .btn-sv { padding: 10px; border-radius: 12px; font-weight: 600; font-size: 13px; text-align: center; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
.btn-dl { background: var(--text-dark); color: white; }
.btn-sv { background: var(--white); color: var(--text-dark); }

/* --- 🌟 LANDSCAPE CARD CSS (1920x1080) --- */
#promo-card {
    position: fixed; left: -9999px; top: 0;
    /* Changed to LANDSCAPE Ratio */
    width: 1920px; height: 1080px; 
    background: #ffffff;
    color: #1e293b; font-family: 'Outfit', sans-serif;
    display: flex; flex-direction: column; 
    justify-content: space-between; 
    padding: 60px 80px; /* Adjusted padding for wide screen */
    box-sizing: border-box;
    border: 0px solid #f8fafc;
}

/* Header (Logo + Badge) */
.pc-header { 
    width: 100%; 
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 40px; flex-shrink: 0; 
}
.pc-logo { height: 120px; object-fit: contain; }
.pc-badge {
    background: #4f46e5; color: #fff; padding: 15px 50px; border-radius: 100px;
    font-size: 28px; font-weight: 900; text-transform: uppercase; letter-spacing: 3px;
    box-shadow: 0 10px 30px rgba(79, 70, 229, 0.25);
}

/* MAIN BODY - SPLIT LAYOUT (Left Image, Right Text) */
.pc-body { 
    flex: 1; 
    display: flex; 
    flex-direction: row; /* Horizontal Layout */
    align-items: center; 
    justify-content: center; 
    width: 100%; 
    gap: 80px; /* Space between Image and Text */
}

/* LEFT: IMAGE BOX */
.pc-svc-icon-box {
    width: 650px; /* Square large box */
    height: 650px;
    background: #ffffff;
    border-radius: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px; 
    box-shadow: 0 30px 80px rgba(79, 70, 229, 0.15); 
    border: 4px solid #f1f5f9;
    box-sizing: border-box;
    flex-shrink: 0; /* Prevent shrinking */
}
.pc-svc-icon { 
    width: 100%; height: 100%; object-fit: contain; border-radius: 40px; 
}

/* RIGHT: DETAILS COLUMN */
.pc-details-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Left Align Text */
    justify-content: center;
    gap: 25px;
    text-align: left;
}

.pc-title { 
    font-size: 80px; /* Bigger Title */
    font-weight: 900; 
    line-height: 1.1; 
    color: #0f172a; 
    text-align: left; /* Align Left */
    max-width: 100%; 
}

.pc-meta-row { 
    display: flex; 
    gap: 20px; 
    justify-content: flex-start; /* Align Left */
    flex-wrap: wrap; 
    width: 100%;
}

.pc-tag { 
    background: #eff6ff; color: #4338ca; 
    padding: 12px 35px; border-radius: 50px; 
    font-size: 24px; font-weight: 800; text-transform: uppercase; 
    border: 3px solid #e0e7ff; 
}

.pc-price-box {
    background: transparent; 
    border: none;
    padding: 0; 
    text-align: left; 
    display: flex; 
    flex-direction: column; 
    align-items: flex-start; 
    margin-top: 10px;
}
.pc-old-price { font-size: 40px; color: #ef4444; font-weight: 800; text-decoration: line-through; text-decoration-thickness: 4px; display: none; margin-bottom: 5px; }
.pc-price-lbl { font-size: 24px; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
.pc-price { font-size: 110px; font-weight: 900; color: #4f46e5; letter-spacing: -4px; line-height: 1; }

/* FOOTER */
.pc-footer { 
    width: 100%; 
    display: flex; 
    align-items: center; 
    justify-content: space-between; /* Space between elements */
    margin-top: 20px; flex-shrink: 0; 
    background: #0f172a;
    padding: 25px 50px;
    border-radius: 40px;
    color: white;
}
.pc-wa-row { display: flex; align-items: center; gap: 20px; }
.pc-wa-icon { width: 60px; height: 60px; }
.pc-wa-num { font-size: 40px; font-weight: 800; color: #25D366; letter-spacing: 1px; }
.pc-brand { font-size: 30px; font-weight: 700; opacity: 0.9; }
</style>

<div class="screen-wrapper">
    
    <div class="purple-header">
        <div style="font-size:13px; opacity:0.9;">MY WALLET</div>
        <div class="bal-amount"><?php echo formatCurrency($user_balance); ?></div>
        <div class="stats-row">
            <div class="stat-pill">
                <div class="stat-val"><?php echo $s['count']; ?></div>
                <div class="stat-lbl">Orders</div>
            </div>
            <div class="stat-pill">
                <div class="stat-val"><?php echo formatCurrency($s['spent']); ?></div>
                <div class="stat-lbl">Spent</div>
            </div>
        </div>
    </div>

    <div class="section-title">Latest Products</div>

    <div class="grid-products">
        <?php foreach($products as $p): 
            $price = (float)$p['price'];
            $orig_price = (float)($p['original_price'] ?? 0);
            $desc_full = strip_tags($p['description']);
            
            // Get Data
            $lang_val = !empty($p['language']) ? $p['language'] : '';
            $size_val = !empty($p['file_size']) ? $p['file_size'] : '';
            $icon_src = !empty($p['icon']) ? "../assets/img/".sanitize($p['icon']) : "https://via.placeholder.com/80";
            
            // Format for JS
            $fmt_price = formatCurrency($price);
            $fmt_orig = ($orig_price > 0) ? formatCurrency($orig_price) : '';
        ?>
        <div class="prod-card" onclick="openConfirm(
            <?= $p['id'] ?>, 
            '<?= addslashes(sanitize($p['name'])) ?>', 
            <?= $price ?>
        )">
            <div class="prod-img-box">
                <?php if(!empty($p['icon'])): ?>
                    <img src="../assets/img/<?= sanitize($p['icon']) ?>" class="prod-img">
                <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:30px;"><i class="fas fa-cube"></i></div>
                <?php endif; ?>
            </div>
            
            <div class="prod-info">
                <div class="prod-name"><?= sanitize($p['name']) ?></div>
                
                <div class="prod-tags-row">
                    <?php if($lang_val): ?>
                        <span class="tag-pill pri"><i class="fas fa-globe"></i> <?= $lang_val ?></span>
                    <?php endif; ?>
                    
                    <?php if($size_val): ?>
                        <span class="tag-pill sec"><i class="fas fa-file-alt"></i> <?= $size_val ?></span>
                    <?php endif; ?>
                    
                    <?php if($orig_price > $price): ?>
                        <span class="tag-pill offer"><?= formatCurrency($orig_price) ?></span>
                    <?php endif; ?>
                </div>

                <div class="prod-desc-box">
                    <?= !empty($desc_full) ? $desc_full : 'Instant digital download available after purchase.' ?>
                </div>
            </div>
            
            <div class="prod-footer">
                <div class="prod-price"><?= $fmt_price ?></div>
                <div class="prod-actions">
                    <button class="btn-dl-icon" title="Create Status Card"
                        onclick="event.stopPropagation(); genCard(
                            '<?= addslashes(sanitize($p['name'])) ?>',
                            '<?= $fmt_price ?>',
                            '<?= $fmt_orig ?>',
                            '<?= $icon_src ?>',
                            '<?= $lang_val ?>',
                            '<?= $size_val ?>'
                        )">
                        📸
                    </button>
                    <button class="btn-buy-icon"><i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="promo-card">
    <div class="pc-header">
        <img id="pc-logo" src="<?= $receipt_logo ?>" class="pc-logo">
        <div class="pc-badge">INSTANT DOWNLOAD</div>
    </div>
    
    <div class="pc-body">
        <div class="pc-svc-icon-box">
            <img id="pc-icon" src="" class="pc-svc-icon">
        </div>
        
        <div class="pc-details-col">
            <div id="pc-title" class="pc-title">Product Name</div>
            
            <div class="pc-meta-row" id="pc-tags-container"></div>
            
            <div class="pc-price-box">
                <div id="pc-old-price" class="pc-old-price"></div>
                <div class="pc-price-lbl">Price Per Item</div>
                <div id="pc-price" class="pc-price">Rs 0</div>
            </div>
        </div>
    </div>

    <div class="pc-footer">
        <div class="pc-wa-row">
            <img src="../assets/img/icons/Whatsapp.png" class="pc-wa-icon">
            <span class="pc-wa-num"><?= $wa_number ?></span>
        </div>
        <div class="pc-brand">LikexFollow.com 🚀</div>
    </div>
</div>

<div class="modal-overlay" id="confModal">
    <div class="confirm-card">
        <div class="c-icon"><i class="fas fa-shopping-bag"></i></div>
        <h3>Confirm Purchase</h3>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Buy <b id="c_name">...</b>?</p>
        <form method="POST">
            <input type="hidden" name="buy_product" value="1">
            <input type="hidden" name="product_id" id="form_pid">
            <input type="hidden" name="price" id="form_price">
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeModals()" style="flex:1; padding:12px; border:none; background:#f5f3ff; border-radius:12px; cursor:pointer;">Cancel</button>
                <button type="submit" style="flex:1; padding:12px; border:none; background:var(--primary); color:white; border-radius:12px; cursor:pointer;">Pay Now</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="recModal">
    <div class="receipt-wrapper">
        <div class="receipt-paper" id="receipt-capture">
            <div class="rp-content">
                <div class="rp-header">
                    <img src="<?= $receipt_logo ?>" class="rp-logo">
                    <div style="font-size:10px; font-weight:700; color:#888; letter-spacing:1px;"><?= strtoupper($site_name) ?></div>
                </div>
                <div class="rp-row"><span>Date</span><span id="r_date">...</span></div>
                <div class="rp-row"><span>ID</span><span id="r_code" style="font-family:monospace">...</span></div>
                
                <div class="rp-item">
                    <img src="" id="r_img" class="rp-thumb">
                    <div class="rp-det">
                        <div id="r_name" style="font-weight:700; font-size:13px;">...</div>
                        <div style="font-size:10px; color:#888;">Paid via Wallet</div>
                    </div>
                </div>
                
                <div class="rp-total">
                    <div style="font-size:10px; text-transform:uppercase;">Total Paid</div>
                    <div class="rp-price-lg" id="r_price">...</div>
                </div>
            </div>
        </div>
        <div class="rp-actions">
            <a href="#" id="r_link" target="_blank" class="btn-dl"><i class="fas fa-download"></i> Download File</a>
            <button onclick="downloadReceiptImg()" class="btn-sv"><i class="fas fa-image"></i> Save Receipt</button>
            <button onclick="closeModals()" style="background:none; border:none; color:white; margin-top:5px; text-decoration:underline; cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.body.appendChild(document.getElementById('confModal'));
    document.body.appendChild(document.getElementById('recModal'));
});

// GENERATE STATUS CARD
function genCard(name, price, oldPrice, icon, lang, size) {
    // 1. Fill Text
    document.getElementById('pc-title').innerText = name;
    document.getElementById('pc-price').innerText = price;
    document.getElementById('pc-icon').src = icon;
    
    // 2. Old Price Logic
    const oldP = document.getElementById('pc-old-price');
    if(oldPrice && oldPrice !== '') {
        oldP.innerText = oldPrice;
        oldP.style.display = 'block';
    } else {
        oldP.style.display = 'none';
    }

    // 3. Tags Logic
    const tagBox = document.getElementById('pc-tags-container');
    tagBox.innerHTML = '';
    if(lang) tagBox.innerHTML += `<div class="pc-tag">🌐 ${lang}</div>`;
    if(size) tagBox.innerHTML += `<div class="pc-tag">💾 ${size}</div>`;

    // 4. Capture
    const node = document.getElementById('promo-card');
    html2canvas(node, {
        scale: 1, // Native 1920x1080 (Landscape)
        backgroundColor: '#ffffff',
        useCORS: true
    }).then(canvas => {
        let a = document.createElement('a');
        a.download = 'Digital-Product-Landscape.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
    });
}

function openConfirm(id, name, price) {
    document.getElementById('form_pid').value = id;
    document.getElementById('form_price').value = price;
    document.getElementById('c_name').innerText = name;
    document.getElementById('confModal').classList.add('active');
}
function showReceipt(code, date, name, price, link, icon, logoUrl) {
    document.getElementById('r_code').innerText = code;
    document.getElementById('r_date').innerText = date;
    document.getElementById('r_name').innerText = name;
    document.getElementById('r_price').innerText = '<?= $settings['currency_symbol'] ?? 'PKR' ?> ' + parseFloat(price).toFixed(2);
    document.getElementById('r_link').href = link;
    document.getElementById('r_img').src = icon;
    closeModals();
    document.getElementById('recModal').classList.add('active');
}
function downloadReceiptImg() {
    html2canvas(document.getElementById('receipt-capture'), { scale: 2, backgroundColor: null }).then(c => {
        let l = document.createElement('a'); l.download = 'Receipt.png'; l.href = c.toDataURL("image/png"); l.click();
    });
}
function closeModals() { document.querySelectorAll('.modal-overlay').forEach(e => e.classList.remove('active')); }
</script>

<?php include '_footer.php'; ?>