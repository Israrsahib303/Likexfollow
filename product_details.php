<?php
// File: product_details.php (Root Directory - Public)
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 0. VALIDATE PRODUCT ID ---
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch Product Data
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: products.php?error=Product+unavailable");
        exit;
    }
} catch (Exception $e) {
    die("<div style='padding:20px; text-align:center;'><h3>Database Error</h3><p>Ensure table exists.</p></div>");
}

// --- 🚀 DUAL-QUERY VARIATION ENGINE ---
$variations = [];
try {
    $v_stmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY id ASC");
    $v_stmt->execute([$product_id]);
    $variations = $v_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $v_stmt2 = $db->prepare("SELECT * FROM variations WHERE product_id = ? ORDER BY id ASC");
        $v_stmt2->execute([$product_id]);
        $variations = $v_stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {}
}

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$prod_name = $product['name'] ?? $product['product_name'] ?? 'Premium Product';
$raw_desc = strip_tags($product['description'] ?? '');
$seo_title = sanitize($prod_name) . " - Buy Cheap at LikexFollow";
$seo_desc = strlen($raw_desc) > 150 ? substr($raw_desc, 0, 150) . '...' : ($raw_desc ?: "Buy premium " . sanitize($prod_name) . " instantly.");
$seo_kws = "buy " . strtolower(sanitize($prod_name)) . ", cheap digital products, digital store pakistan";

if (isset($db)) {
    try {
        $seo_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_url = ? OR page_name = ? LIMIT 1");
        $seo_stmt->execute([$current_url, $current_public_page . "?id=" . $product_id]);
        $seo_data = $seo_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seo_data) {
            if (!empty($seo_data['meta_title'])) $seo_title = $seo_data['meta_title'];
            if (!empty($seo_data['meta_description'])) $seo_desc = $seo_data['meta_description'];
            if (!empty($seo_data['meta_keywords'])) $seo_kws = $seo_data['meta_keywords'];
        }
        
        $log_stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$user_ip, $current_url, 200, $user_agent, date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {}
}
// --- 🚀 ADVANCED 2-WAY SEO ENGINE ENDS ---

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

ob_start();
include 'user/_header.php'; 
$header_html = ob_get_clean();

$header_html = preg_replace('/<title>(.*?)<\/title>/', '<title>' . htmlspecialchars($seo_title) . '</title>', $header_html);
echo $header_html;

$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = 1; 
$curr_symbol = 'Rs';

if ($curr_code != 'PKR') {
    if(function_exists('getCurrencyRate')) {
        $curr_rate = getCurrencyRate($curr_code);
    }
    $symbols = ['PKR'=>'Rs','USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','SAR'=>'﷼','AED'=>'د.إ'];
    $curr_symbol = $symbols[$curr_code] ?? $curr_code;
}

// SMART TYPE-DETECTION AI
$prod_cat = (isset($product['category']) && !empty($product['category']) && !is_numeric($product['category'])) ? sanitize($product['category']) : 'Digital Asset';
$cat_lower = strtolower($prod_cat . ' ' . $prod_name);

$type_badge = '🏷️ Digital Asset';
$type_style = 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'; 
$is_subscription = false;

if (strpos($cat_lower, 'course') !== false || strpos($cat_lower, 'tutorial') !== false) {
    $type_badge = '📚 Premium Course';
    $type_style = 'background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;'; 
} elseif (strpos($cat_lower, 'apk') !== false || strpos($cat_lower, 'app') !== false || strpos($cat_lower, 'script') !== false) {
    $type_badge = '📱 Premium APK/Script';
    $type_style = 'background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa;'; 
} else {
    $type_badge = '💎 Subscription';
    $type_style = 'background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff;'; 
    $is_subscription = true;
}

// BULLETPROOF PRICE LOGIC
$raw_price = $product['price'] ?? $product['rate'] ?? $product['amount'] ?? 0;
$base_price = (float)$raw_price;
$old_price_final = 0;

if ($is_subscription && !empty($variations)) {
    $first_var_price = $variations[0]['price'] ?? $variations[0]['amount'] ?? $variations[0]['rate'] ?? 0;
    $base_price = (float)$first_var_price;
    
    $first_old_price = $variations[0]['old_price'] ?? $variations[0]['original_price'] ?? $variations[0]['strike_price'] ?? 0;
    $old_price_final = (float)$first_old_price * $curr_rate;
}
$final_price = $base_price * $curr_rate;

// 🔥 DEEP IMAGE RESOLVER 🔥
$img_name = $product['image'] ?? $product['img'] ?? $product['thumbnail'] ?? '';
$img_src = '';
if (!empty($img_name)) {
    $img_name = basename($img_name);
    $paths_to_check = [
        'assets/img/products/' . $img_name,
        'assets/uploads/' . $img_name,
        'assets/img/' . $img_name,
        'user/assets/img/products/' . $img_name
    ];
    foreach($paths_to_check as $p) {
        if(file_exists(__DIR__ . '/' . $p)) {
            $img_src = $p; break;
        }
    }
    if(empty($img_src)) $img_src = 'assets/img/products/' . $img_name;
}

$in_stock = isset($product['stock']) ? ((int)$product['stock'] > 0) : true;
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let metaDesc = document.querySelector('meta[name="description"]');
        if(metaDesc) metaDesc.setAttribute("content", "<?= addslashes($seo_desc) ?>");
        else {
            let meta = document.createElement('meta');
            meta.name = "description"; meta.content = "<?= addslashes($seo_desc) ?>";
            document.head.appendChild(meta);
        }
        
        let metaKws = document.querySelector('meta[name="keywords"]');
        if(metaKws) metaKws.setAttribute("content", "<?= addslashes($seo_kws) ?>");
        else {
            let meta = document.createElement('meta');
            meta.name = "keywords"; meta.content = "<?= addslashes($seo_kws) ?>";
            document.head.appendChild(meta);
        }
    });
</script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary: #4F46E5;
    --primary-hover: #4338ca;
    --bg-body: #F8FAFC;
    --card-bg: #FFFFFF;
    --text-main: #0F172A;
    --text-sub: #64748b;
    --border: #E2E8F0;
}

body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); }

.breadcrumbs {
    font-size: 0.9rem; font-weight: 600; color: var(--text-sub); margin-bottom: 25px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.breadcrumbs a { color: var(--primary); text-decoration: none; transition: 0.2s; }
.breadcrumbs a:hover { text-decoration: underline; }
.breadcrumbs .separator { color: #cbd5e1; font-size: 0.8rem; }

.product-wrapper {
    display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; margin-bottom: 60px;
    background: #ffffff; border-radius: 20px; padding: 30px; border: 1px solid var(--border);
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
}

.product-gallery {
    background: #f8fafc; border-radius: 16px; display: flex; align-items: center; justify-content: center;
    padding: 20px; min-height: 400px; border: 1px solid #f1f5f9; position: relative; overflow: hidden;
}
.product-image {
    max-width: 100%; max-height: 400px; object-fit: contain; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.1));
    transition: transform 0.4s ease;
}
.product-gallery:hover .product-image { transform: scale(1.05); }

.product-info { display: flex; flex-direction: column; }
.product-type-pill {
    display: inline-block; font-size: 0.75rem; font-weight: 800; padding: 5px 12px; border-radius: 6px; 
    margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; width: fit-content;
}
.product-title { font-size: 2.2rem; font-weight: 900; color: var(--text-main); line-height: 1.2; margin: 0 0 15px 0; letter-spacing: -0.5px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px;
    font-size: 0.85rem; font-weight: 700; margin-bottom: 20px; width: fit-content;
}
.status-in { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.status-out { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

.price-box {
    display: flex; align-items: baseline; gap: 10px; padding: 20px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    margin-bottom: 25px; flex-wrap: wrap;
}
.current-price { font-size: 2.5rem; font-weight: 900; color: var(--text-main); }
.currency-mark { font-size: 1.5rem; font-weight: 700; color: var(--text-sub); }
.old-price { color: #94a3b8; font-size: 1.2rem; text-decoration: line-through; margin-left: 10px; font-weight: 600; }

/* VARIATION BOXES CSS */
.var-container { margin-bottom: 25px; }
.var-label { font-size: 1rem; font-weight: 800; color: var(--text-main); margin-bottom: 10px; display: block; }
.var-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; }
.var-box {
    border: 2px solid var(--border); border-radius: 12px; padding: 15px 10px; text-align: center;
    cursor: pointer; transition: all 0.3s ease; background: #fff;
}
.var-box:hover { border-color: #c7d2fe; transform: translateY(-2px); }
.var-box.active {
    border-color: var(--primary); background: var(--l-purple);
    box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);
}
.var-name { font-weight: 800; color: var(--text-main); font-size: 0.95rem; margin-bottom: 6px; line-height: 1.3; }
.var-dur { font-size: 0.75rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 6px; }
.var-price-tag { font-weight: 800; color: var(--primary); font-size: 0.95rem; }
.var-strike { color: #94a3b8; font-size: 0.75rem; text-decoration: line-through; margin-right: 5px; font-weight: 600; }

.desc-title { font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 10px; }
.product-description {
    font-size: 1rem; color: var(--text-sub); line-height: 1.7; margin-bottom: 30px;
}
.product-description ul { padding-left: 20px; margin-top: 10px; }
.product-description li { margin-bottom: 8px; }

.action-form { display: flex; flex-direction: column; gap: 15px; background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid var(--border); }
.qty-wrapper { display: flex; align-items: center; gap: 10px; }
.qty-label { font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
.qty-input {
    width: 80px; padding: 10px; border: 2px solid var(--border); border-radius: 8px;
    text-align: center; font-weight: 800; font-size: 1.1rem; outline: none; transition: 0.3s;
}
.qty-input:focus { border-color: var(--primary); }

.buy-btn {
    background: var(--primary); color: #ffffff; padding: 16px 24px; border-radius: 12px;
    text-decoration: none; font-size: 1.1rem; font-weight: 800; border: none; cursor: pointer;
    transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 10px;
    box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3); flex: 1;
}
.buy-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.4); }

.wa-btn-large {
    background: #25D366; color: #ffffff; padding: 16px 24px; border-radius: 12px; text-decoration: none;
    font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: 0.2s;
    box-shadow: 0 10px 20px -5px rgba(37, 211, 102, 0.3); flex-shrink: 0;
}
.wa-btn-large:hover { background: #128C7E; transform: translateY(-2px); color: #ffffff; }

.action-buttons-group { display: flex; gap: 15px; width: 100%; }

.features-list { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 25px; }
.feature-item { display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; color: var(--text-main); }
.feature-icon { width: 24px; height: 24px; background: #eef2ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; }

@media (max-width: 992px) {
    .product-wrapper { grid-template-columns: 1fr; padding: 20px; gap: 30px; }
    .product-gallery { min-height: 300px; }
    .product-image { max-height: 300px; }
    .product-title { font-size: 1.8rem; }
    .action-buttons-group { flex-direction: column; }
}
</style>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    
    <div class="breadcrumbs">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <i class="fas fa-chevron-right separator"></i>
        <a href="products.php">Digital Store</a>
        <i class="fas fa-chevron-right separator"></i>
        <span style="color: var(--text-main);"><?= sanitize($prod_cat) ?></span>
    </div>

    <div class="product-wrapper">
        
        <div class="product-gallery">
            <?php if (!empty($img_src)): ?>
                <img src="<?= $img_src ?>" alt="<?= sanitize($prod_name) ?>" class="product-image" onerror="if(!this.src.includes('assets/img/products/')) { this.src='assets/img/products/<?= sanitize($img_name) ?>'; } else if(!this.src.includes('assets/uploads/')) { this.src='assets/uploads/<?= sanitize($img_name) ?>'; } else { this.outerHTML='<i class=\'fas fa-box-open\' style=\'font-size: 8rem; color: #cbd5e1;\'></i>'; }">
            <?php else: ?>
                <i class="fas fa-box-open" style="font-size: 8rem; color: #cbd5e1;"></i>
            <?php endif; ?>
        </div>

        <div class="product-info">
            
            <div class="product-type-pill" style="<?= $type_style ?>"><?= $type_badge ?></div>
            
            <h1 class="product-title"><?= sanitize($prod_name) ?></h1>
            
            <?php if ($in_stock): ?>
                <div class="status-badge status-in"><i class="fas fa-check-circle"></i> In Stock & Ready</div>
            <?php else: ?>
                <div class="status-badge status-out"><i class="fas fa-times-circle"></i> Out of Stock</div>
            <?php endif; ?>

            <div class="price-box">
                <span class="currency-mark"><?= $curr_symbol ?></span>
                <span class="current-price" id="displayPrice"><?= number_format($final_price, 2) ?></span>
                <span class="old-price" id="displayOldPrice" style="<?= $old_price_final > $final_price ? '' : 'display:none;' ?>">
                    <?= $curr_symbol ?> <?= number_format($old_price_final, 2) ?>
                </span>
            </div>

            <?php if ($is_subscription && !empty($variations)): ?>
            <div class="var-container">
                <span class="var-label">Select Package:</span>
                <div class="var-grid">
                    <?php foreach($variations as $index => $v): 
                        
                        // 🧠 AI NAME SCANNER
                        $v_name = 'Package ' . ($index + 1);
                        $name_keys = ['variation_name', 'name', 'title', 'pkg_name', 'package_name', 'type', 'plan', 'variation', 'attribute', 'account_type'];
                        foreach($name_keys as $key) {
                            if(isset($v[$key]) && trim($v[$key]) !== '') { $v_name = trim($v[$key]); break; }
                        }

                        // 🧠 AI DURATION SCANNER
                        $v_dur = '';
                        $dur_keys = ['duration', 'validity', 'months', 'time', 'days', 'period', 'length', 'expiry'];
                        foreach($dur_keys as $key) {
                            if(isset($v[$key]) && trim($v[$key]) !== '') { $v_dur = trim($v[$key]); break; }
                        }

                        $v_raw_price = (float)($v['price'] ?? $v['amount'] ?? $v['rate'] ?? 0);
                        $v_final_price = $v_raw_price * $curr_rate;
                        
                        $v_raw_old = (float)($v['old_price'] ?? $v['original_price'] ?? $v['strike_price'] ?? $v['fake_price'] ?? 0);
                        $v_old_price = $v_raw_old * $curr_rate;
                    ?>
                    <div class="var-box <?= $index === 0 ? 'active' : '' ?>" onclick="selectVariation(this, <?= $v_final_price ?>, <?= $v_old_price ?>, '<?= $v['id'] ?>')">
                        <div class="var-name"><?= sanitize($v_name) ?></div>
                        
                        <?php if(!empty($v_dur)): ?>
                            <span class="var-dur">( <?= sanitize($v_dur) ?> )</span>
                        <?php endif; ?>

                        <div class="var-price-tag">
                            <?php if($v_old_price > $v_final_price): ?>
                                <span class="var-strike"><?= $curr_symbol ?> <?= number_format($v_old_price, 2) ?></span>
                            <?php endif; ?>
                            <?= $curr_symbol ?> <?= number_format($v_final_price, 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="desc-title">Product Details:</div>
            <div class="product-description">
                <?= nl2br(sanitize($product['description'] ?? 'High quality digital premium asset with instant automated delivery.')) ?>
            </div>

            <form action="user/checkout.php" method="POST" class="action-form">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?? 0 ?>">
                <input type="hidden" name="variation_id" id="selectedVariationId" value="<?= (!empty($variations) && $is_subscription) ? $variations[0]['id'] : 0 ?>">
                
                <div class="qty-wrapper">
                    <span class="qty-label">Quantity:</span>
                    <input type="number" name="quantity" id="qtyInput" class="qty-input" value="1" min="1" max="<?= isset($product['stock']) ? (int)$product['stock'] : 100 ?>" onchange="updateLivePrice()">
                </div>
                
                <div class="action-buttons-group">
                    <a href="https://wa.me/923097856447?text=Hi! Need details about <?= urlencode($prod_name) ?>" target="_blank" class="wa-btn-large" title="Chat on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    
                    <?php if ($in_stock): ?>
                        <?php if ($user_id > 0): ?>
                            <button type="submit" class="buy-btn">
                                <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="buy-btn" style="background: var(--text-main);">
                                <i class="fas fa-sign-in-alt"></i> Login to Purchase
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="buy-btn" style="background:#94a3b8; cursor:not-allowed;" disabled>
                            <i class="fas fa-ban"></i> Sold Out
                        </button>
                    <?php endif; ?>
                </div>
            </form>

            <div class="features-list">
                <div class="feature-item"><div class="feature-icon"><i class="fas fa-bolt"></i></div> Instant Delivery</div>
                <div class="feature-item"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div> Genuine Asset</div>
                <div class="feature-item"><div class="feature-icon"><i class="fas fa-headset"></i></div> 24/7 Support</div>
                <div class="feature-item"><div class="feature-icon"><i class="fas fa-sync-alt"></i></div> Guaranteed</div>
            </div>

        </div>
    </div>
    
</div>

<script>
let currentBasePrice = <?= $final_price ?>;
let currentOldPrice = <?= $old_price_final ?>;
let currencySymbol = "<?= $curr_symbol ?>";

function selectVariation(boxElement, price, oldPrice, varId) {
    document.querySelectorAll('.var-box').forEach(box => {
        box.classList.remove('active');
    });
    boxElement.classList.add('active');
    
    currentBasePrice = parseFloat(price);
    currentOldPrice = parseFloat(oldPrice);
    document.getElementById('selectedVariationId').value = varId;
    
    updateLivePrice();
}

function updateLivePrice() {
    let qty = parseInt(document.getElementById('qtyInput').value);
    if(isNaN(qty) || qty < 1) { qty = 1; document.getElementById('qtyInput').value = 1; }
    
    let total = currentBasePrice * qty;
    let oldTotal = currentOldPrice * qty;
    
    document.getElementById('displayPrice').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    let oldPriceEl = document.getElementById('displayOldPrice');
    if (oldTotal > total) {
        oldPriceEl.style.display = 'inline-block';
        oldPriceEl.innerText = currencySymbol + ' ' + oldTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    } else {
        oldPriceEl.style.display = 'none';
    }
}
</script>

<?php include 'user/_smm_footer.php'; ?>
