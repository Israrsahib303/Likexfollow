<?php
// File: products.php (Root Directory - Public Store)
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Default SEO Fallbacks for Digital Store
$seo_title = "Premium Digital Store - Buy Netflix, Canva Pro, ChatGPT";
$seo_desc = "Get official premium subscriptions at wholesale prices. Instant delivery for Netflix, Canva Pro, ChatGPT Plus, Spotify, and more. 100% Guaranteed accounts.";
$seo_kws = "buy premium accounts, netflix cheap, canva pro lifetime, chatgpt plus cheap, digital store pakistan";

if (isset($db)) {
    try {
        $seo_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_name = ? OR page_url = ? LIMIT 1");
        $seo_stmt->execute([$current_public_page, $current_url]);
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

// --- 1. Header Logic & AUTO-SEO INJECTION ---
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

ob_start();
include 'user/_header.php'; 
$header_html = ob_get_clean();

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR INTO BUFFERED HEADER 🚀
if (file_exists(__DIR__ . '/seo_auto_injector.php')) {
    require_once __DIR__ . '/seo_auto_injector.php';
    
    // Remove old static tags to prevent duplication
    $header_html = preg_replace('/<title>.*?<\/title>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']description["\'].*?>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']keywords["\'].*?>/i', '', $header_html);
    
    // Inject the fully automated API SEO Tags + JSON Schema right before closing </head>
    $header_html = str_ireplace('</head>', $beast_seo_injection . "\n</head>", $header_html);
} else {
    // Fallback if file missing
    $header_html = preg_replace('/<title>(.*?)<\/title>/', '<title>' . htmlspecialchars($seo_title) . '</title>', $header_html);
}
echo $header_html;

// --- 2. Currency Setup ---
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

// --- 3. Fetch Active Digital Products ---
try {
    $stmt = $db->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $grouped = [];
    foreach ($products as $p) {
        $cat = (isset($p['category']) && !empty($p['category']) && !is_numeric($p['category'])) ? $p['category'] : 'Premium Subscriptions';
        $grouped[$cat][] = $p;
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

.page-header {
    background: #ffffff; padding: 50px 30px; border-radius: 16px; margin-bottom: 35px;
    text-align: center; border: 1px solid var(--border);
    box-shadow: 0 10px 25px -15px rgba(0,0,0,0.05);
    background-image: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    position: relative; overflow: hidden;
}
.page-header::before {
    content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(79, 70, 229, 0.05) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
}
.page-title { font-size: 2.6rem; font-weight: 800; margin: 0; color: var(--text-main); letter-spacing: -1px; position: relative; z-index: 1; }
.page-subtitle { color: var(--text-sub); font-size: 1.1rem; margin-top: 10px; font-weight: 500; position: relative; z-index: 1; }

.search-container { max-width: 650px; margin: 0 auto 45px auto; position: relative; z-index: 1; }
.search-input {
    width: 100%; padding: 16px 25px 16px 55px; border: 2px solid #e2e8f0;
    border-radius: 50px; background: #ffffff; font-size: 1.05rem; font-weight: 600;
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); transition: all 0.3s ease; color: var(--text-main);
}
.search-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12); }
.search-icon { position: absolute; left: 22px; top: 50%; transform: translateY(-50%); color: var(--text-sub); font-size: 1.2rem; }

.store-cat-title {
    font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--border); padding-bottom: 10px;
}

.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; margin-bottom: 50px; }

.product-card {
    background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid var(--border);
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03); transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex; flex-direction: column;
}
.product-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.1); border-color: #cbd5e1; }

.product-img-box {
    width: 100%; height: 160px; background: #f1f5f9; display: flex; align-items: center; justify-content: center;
    overflow: hidden; position: relative; border-bottom: 1px solid var(--border);
}
.product-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.product-card:hover .product-img { transform: scale(1.05); }
.product-icon { font-size: 3.5rem; color: #94a3b8; opacity: 0.5; }

.product-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
.product-type-pill {
    display: inline-block; font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; 
    margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; width: fit-content;
}
.product-title { font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0 0 8px 0; line-height: 1.3; }
.product-desc { font-size: 0.85rem; color: var(--text-sub); margin-bottom: 15px; line-height: 1.5; flex: 1; }

.product-footer {
    display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border); padding-top: 15px; margin-top: auto;
}
.product-price { font-size: 1.25rem; font-weight: 900; color: var(--primary); }

.buy-btn {
    background: var(--text-main); color: #ffffff; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 700; transition: all 0.2s ease; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); flex: 1; text-align: center;
}
.buy-btn:hover { background: var(--primary); color: #ffffff; transform: translateY(-2px); box-shadow: 0 6px 12px -2px rgba(79, 70, 229, 0.25); }

/* WhatsApp Action Box */
.wa-icon-btn {
    background: #25D366; color: #ffffff; width: 38px; height: 38px; border-radius: 8px; 
    display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    text-decoration: none; transition: 0.2s; box-shadow: 0 4px 6px -1px rgba(37, 211, 102, 0.3); flex-shrink: 0;
}
.wa-icon-btn:hover { background: #128C7E; transform: translateY(-2px); color: #ffffff; }
.action-group { display: flex; gap: 8px; align-items: center; width: 100%; justify-content: flex-end; }

.badge-stock { position: absolute; top: 10px; right: 10px; background: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 10; }
.badge-out { background: #ef4444; }

@media (max-width: 768px) {
    .page-title { font-size: 2rem; }
    .product-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
}
</style>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-store text-indigo-600 mr-2"></i> Premium Digital Store</h1>
        <p class="page-subtitle">
            <?php 
            $subtitle_text = "Official subscriptions, premium courses & digital assets. Instant delivery.";
            // 🕸️ Auto Spider Linker Active!
            echo function_exists('auto_spider_link') ? auto_spider_link($subtitle_text, $db) : $subtitle_text; 
            ?>
        </p>
    </div>

    <div class="search-container">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <input type="text" id="search" class="search-input" placeholder="Search Netflix, Canva, Courses, APKs...">
    </div>

    <div id="store-wrapper">
        <?php if (isset($db_error)): ?>
            <div class="text-center py-10 bg-red-50 rounded-2xl border border-red-200">
                <div class="text-red-500 font-bold mb-2">Database Error</div>
                <p class="text-red-400 text-sm"><?= htmlspecialchars($db_error) ?></p>
            </div>
        <?php elseif (empty($grouped)): ?>
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-6xl mb-3 opacity-50">🛍️</div>
                <h3 class="text-xl font-bold text-slate-400">Store is Currently Empty</h3>
            </div>
        <?php else: ?>
            
            <?php foreach ($grouped as $catName => $items): ?>
                <div class="store-category" data-cat="<?= strtolower(sanitize($catName)) ?>">
                    <h2 class="store-cat-title">
                        <i class="fas fa-boxes text-indigo-500"></i> <?= sanitize($catName) ?>
                    </h2>
                    
                    <div class="product-grid">
                        <?php foreach ($items as $item): 
                            $raw_price = $item['price'] ?? $item['rate'] ?? $item['amount'] ?? $item['service_rate'] ?? 0;
                            $base_price = (float)$raw_price;
                            $final_price = $base_price * $curr_rate;
                            
                            // 🔥 DEEP IMAGE RESOLVER (Scans 4 Directories dynamically) 🔥
                            $img_name = $item['image'] ?? $item['img'] ?? $item['thumbnail'] ?? '';
                            $img_src = '';
                            if (!empty($img_name)) {
                                $img_name = basename($img_name); // Clean name just in case
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
                                if(empty($img_src)) $img_src = 'assets/img/products/' . $img_name; // ultimate fallback
                            }
                            
                            $in_stock = isset($item['stock']) ? ((int)$item['stock'] > 0) : true;
                            
                            $prod_name = $item['name'] ?? $item['product_name'] ?? 'Product';
                            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $prod_name)));
                            $prod_id = $item['id'] ?? 0;
                            $prod_link = "product_details.php?id={$prod_id}"; 

                            $prod_cat = (isset($item['category']) && !empty($item['category']) && !is_numeric($item['category'])) ? sanitize($item['category']) : 'Digital';
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
                        ?>
                        <div class="product-card item-card" data-name="<?= strtolower(sanitize($prod_name)) ?>">
                            <div class="product-img-box">
                                <?php if ($in_stock): ?>
                                    <span class="badge-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="badge-stock badge-out">Out of Stock</span>
                                <?php endif; ?>

                                <?php if (!empty($img_src)): ?>
                                    <img src="<?= $img_src ?>" alt="<?= sanitize($prod_name) ?>" class="product-img" onerror="this.outerHTML='<i class=\'fas fa-box-open product-icon\'></i>';">
                                <?php else: ?>
                                    <i class="fas fa-box-open product-icon"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-body">
                                <div class="product-type-pill" style="<?= $type_style ?>"><?= $type_badge ?></div>
                                
                                <h3 class="product-title"><?= sanitize($prod_name) ?></h3>
                                <div class="product-desc">
                                    <?php 
                                        $desc = strip_tags($item['description'] ?? '');
                                        $short_desc = strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : ($desc ?: 'Premium digital asset with instant automated delivery.');
                                        // 🕸️ Auto Spider Linker Active!
                                        echo function_exists('auto_spider_link') ? auto_spider_link($short_desc, $db) : $short_desc; 
                                    ?>
                                </div>
                                
                                <div class="product-footer">
                                    <?php if (!$is_subscription): ?>
                                        <div class="product-price"><?= $curr_symbol . ' ' . number_format($final_price, 2) ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="action-group" <?= $is_subscription ? 'style="width:100%; justify-content:space-between;"' : '' ?>>
                                        <a href="https://wa.me/923097856447?text=Hi! Need details about <?= urlencode($prod_name) ?>" target="_blank" class="wa-icon-btn" title="Chat on WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                        <a href="<?= $prod_link ?>" class="buy-btn">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>

<script>
document.getElementById('search').addEventListener('input', function(e) {
    const val = e.target.value.toLowerCase();
    document.querySelectorAll('.store-category').forEach(catNode => {
        let hasVisible = false;
        catNode.querySelectorAll('.item-card').forEach(card => {
            const prodName = card.getAttribute('data-name');
            if (prodName.includes(val) || val === '') {
                card.style.display = 'flex'; hasVisible = true;
            } else { card.style.display = 'none'; }
        });
        catNode.style.display = hasVisible ? 'block' : 'none';
    });
});
</script>

<?php include 'user/_smm_footer.php'; ?>
