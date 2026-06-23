<?php
// File: services.php (Root Directory)
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Default SEO Fallbacks
$seo_title = "Best SMM Services List - Cheap & Instant Delivery";
$seo_desc = "Explore our complete SMM Services list. Get instant Instagram followers, high-retention YouTube views, and wholesale TikTok likes at factory prices.";
$seo_kws = "smm services list, cheap smm panel, wholesale smm panel, buy followers list, automatic smm provider";

if (isset($db)) {
    try {
        // 1. Backend-to-Frontend: Fetch Expert SEO Data for Services Page
        $seo_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_name = ? OR page_url = ? LIMIT 1");
        $seo_stmt->execute([$current_public_page, $current_url]);
        $seo_data = $seo_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seo_data) {
            if (!empty($seo_data['meta_title'])) $seo_title = $seo_data['meta_title'];
            if (!empty($seo_data['meta_description'])) $seo_desc = $seo_data['meta_description'];
            if (!empty($seo_data['meta_keywords'])) $seo_kws = $seo_data['meta_keywords'];
        }
        
        // 2. Frontend-to-Backend: Live Traffic Crawler Tracker (Feeds log analyzer & traffic stats directly)
        $log_stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$user_ip, $current_url, 200, $user_agent, date('Y-m-d H:i:s')]);
        
    } catch (PDOException $e) {
        // Silently skip if database is sleeping or migrating
    }
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
    
    // Remove old static title to prevent duplication
    $header_html = preg_replace('/<title>.*?<\/title>/i', '', $header_html);
    
    // Inject the fully automated API SEO Tags + JSON Schema right before closing </head>
    $header_html = str_ireplace('</head>', $beast_seo_injection . "\n</head>", $header_html);
} else {
    // Smooth Regex title overwrite fallback mechanism
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

// --- 3. Fetch Active Services ---
try {
    $stmt = $db->query("
        SELECT * FROM smm_services 
        WHERE is_active = 1 AND manually_deleted = 0 
        ORDER BY category ASC, service_rate ASC
    ");
    $services = $stmt->fetchAll();
    
    $grouped = [];
    foreach ($services as $s) {
        $grouped[$s['category']][] = $s;
    }

} catch (Exception $e) {
    echo "<div class='p-4 bg-red-100 text-red-700 text-center rounded-xl my-4 font-bold'>Database Error: " . $e->getMessage() . "</div>";
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

/* Premium Header styling with high contrast */
.page-header {
    background: #ffffff; padding: 50px 30px; border-radius: 16px; margin-bottom: 35px;
    text-align: center; border: 1px solid var(--border);
    box-shadow: 0 10px 25px -15px rgba(0,0,0,0.05);
    background-image: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}
.page-title { font-size: 2.6rem; font-weight: 800; margin: 0; color: var(--text-main); letter-spacing: -1px; }
.page-subtitle { color: var(--text-sub); font-size: 1.1rem; margin-top: 10px; font-weight: 500; }

/* Custom Search Box with glass borders */
.search-container { max-width: 650px; margin: 0 auto 45px auto; position: relative; }
.search-input {
    width: 100%; padding: 16px 25px 16px 55px; border: 2px solid #e2e8f0;
    border-radius: 50px; background: #ffffff; font-size: 1.05rem; font-weight: 600;
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); transition: all 0.3s ease; color: var(--text-main);
}
.search-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12); }
.search-icon { position: absolute; left: 22px; top: 50%; transform: translateY(-50%); color: var(--text-sub); font-size: 1.2rem; }

/* Pure Enterprise Standard Cards Layout */
.cat-card {
    background: #ffffff; border-radius: 16px; margin-bottom: 35px;
    overflow: hidden; border: 1px solid var(--border); 
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.cat-card:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -8px rgba(79, 70, 229, 0.08); border-color: #cbd5e1; }

.cat-header {
    padding: 22px 30px; background: #ffffff; border-bottom: 1px solid var(--border);
    font-weight: 800; font-size: 1.25rem; color: #0f172a;
    display: flex; justify-content: space-between; align-items: center;
}
.cat-badge { background: #eef2ff; color: var(--primary); padding: 5px 14px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; border: 1px solid #c7d2fe; }

/* Premium Clean Scannable Tables */
.table-responsive { overflow-x: auto; }
.svc-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.svc-table th {
    text-align: left; padding: 16px 30px; color: var(--text-sub);
    font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;
    background: #f8fafc; border-bottom: 1px solid var(--border);
}
.svc-table td {
    padding: 18px 30px; border-bottom: 1px solid #f1f5f9;
    color: var(--text-main); vertical-align: middle;
}
.svc-table tr:hover td { background: #fafafa; }
.svc-table tr:last-child td { border-bottom: none; }

.svc-link { 
    font-weight: 700; color: #1e293b; font-size: 1.02rem; margin-bottom: 6px; display: block; text-decoration: none; transition: 0.2s; 
}
.svc-link:hover { color: var(--primary); text-decoration: underline; }

.meta-tags { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
.bg-time { background: #f1f5f9; color: var(--text-sub); border: 1px solid #e2e8f0; }
.bg-refill { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.bg-cancel { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

.price-tag {
    font-weight: 800; color: var(--text-main); background: #f1f5f9;
    padding: 8px 14px; border-radius: 8px; font-size: 0.95rem; border: 1px solid #e2e8f0; display: inline-block;
}

.action-btn {
    background: var(--primary); color: #ffffff;
    padding: 9px 18px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 700;
    transition: all 0.2s ease; display: inline-block; text-align: center;
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.15); border: none;
}
.action-btn:hover { background: var(--primary-hover); transform: translateY(-1px); color: #ffffff; box-shadow: 0 6px 12px -2px rgba(79, 70, 229, 0.3); }

.id-pill {
    background: #ffffff; color: var(--text-sub); padding: 4px 10px; border-radius: 6px;
    font-family: monospace; border: 1px solid #e2e8f0; font-size: 0.82rem; font-weight: 700;
}

/* Responsiveness Engine */
@media (max-width: 768px) {
    .svc-table th, .svc-table td { padding: 14px 16px; }
    .meta-tags { gap: 4px; }
    .badge { font-size: 0.7rem; padding: 2px 6px; }
    .action-btn { padding: 8px 14px; font-size: 0.8rem; width: 100%; }
    .page-title { font-size: 1.9rem; }
    .price-tag { font-size: 0.85rem; padding: 6px 10px; }
}
</style>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-rocket text-indigo-600 mr-2"></i> Wholesale Service Matrix</h1>
        <p class="page-subtitle">
            <?php 
            $subtitle_text = "Direct endpoint provider logs for LikexFollow accounts. Transparent pricing structure.";
            // 🕸️ Auto Spider Linker Active!
            echo function_exists('auto_spider_link') ? auto_spider_link($subtitle_text, $db) : $subtitle_text; 
            ?>
        </p>
    </div>

    <div class="search-container">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <input type="text" id="search" class="search-input" placeholder="Search across thousands of optimization packages (e.g., TikTok Views)...">
    </div>

    <div id="services-wrapper">
        <?php if (empty($grouped)): ?>
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-6xl mb-3 opacity-50">📁</div>
                <h3 class="text-xl font-bold text-slate-400">No Active Services Synchronized</h3>
                <p class="text-slate-400 text-sm mt-1">Please ensure your API provider sync configurations are healthy.</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($grouped as $catName => $list): ?>
                <div class="cat-card" data-name="<?= strtolower(sanitize($catName)) ?>">
                    <div class="cat-header">
                        <span><i class="fas fa-folder-open text-indigo-500 mr-2"></i> <?= sanitize($catName) ?></span>
                        <span class="cat-badge"><?= count($list) ?> Services Available</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="svc-table">
                            <thead>
                                <tr>
                                    <th width="90">Service ID</th>
                                    <th>Package Specification</th>
                                    <th width="140">Rate Per 1,000</th>
                                    <th width="160">Order Thresholds</th>
                                    <th width="130" class="text-right">Execution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $s): 
                                    // 🔥 RELATIONAL CALCULATED CUSTOM USER RATE
                                    $base_rate = (float)$s['service_rate'];
                                    if ($user_id > 0) {
                                        $base_rate = get_final_user_price($user_id, $s['provider_id'], $s['category'], $s['id'], $base_rate);
                                    }
                                    
                                    $rate = $base_rate;
                                    if ($curr_code != 'PKR') $rate *= $curr_rate;
                                    
                                    // Dynamic Rewrite Engine Slug Generation (Strictly SEO Compliant)
                                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $s['name'])));
                                    $seo_link = "service/{$s['id']}/$slug"; 
                                ?>
                                <tr class="service-row" data-name="<?= strtolower(sanitize($s['name'])) ?>">
                                    <td>
                                        <span class="id-pill">#<?= $s['id'] ?></span>
                                    </td>
                                    <td>
                                        <a href="<?= $seo_link ?>" class="svc-link">
                                            <?= sanitize($s['name']) ?>
                                        </a>
                                        
                                        <div class="meta-tags">
                                            <span class="badge bg-time"><i class="far fa-clock"></i> Delivery: <?= formatSmmAvgTime($s['avg_time'] ?? 'Instant') ?></span>
                                            <?php if($s['has_refill']): ?><span class="badge bg-refill"><i class="fas fa-history"></i> Auto-Refill Enabled</span><?php endif; ?>
                                            <?php if($s['has_cancel']): ?><span class="badge bg-cancel"><i class="fas fa-times-circle"></i> Guarantee Revoke</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price-tag"><?= $curr_symbol . ' ' . number_format($rate, 2) ?></span>
                                    </td>
                                    <td>
                                        <div class="text-xs text-slate-500 font-bold mb-1">MIN: <span class="text-slate-800"><?= number_format($s['min']) ?></span></div>
                                        <div class="text-xs text-slate-500 font-bold">MAX: <span class="text-slate-800"><?= number_format($s['max']) ?></span></div>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= $seo_link ?>" class="action-btn">
                                            Buy Package <i class="fas fa-chevron-right ml-1 small"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>

<script>
// Real-time Fast Filtering Node Walker
document.getElementById('search').addEventListener('input', function(e) {
    const val = e.target.value.toLowerCase();
    document.querySelectorAll('.cat-card').forEach(card => {
        const catName = card.getAttribute('data-name');
        let hasVisible = false;
        
        card.querySelectorAll('.service-row').forEach(row => {
            const svcName = row.getAttribute('data-name');
            if (svcName.includes(val) || catName.includes(val) || val === '') {
                row.style.display = '';
                hasVisible = true;
            } else { 
                row.style.display = 'none'; 
            }
        });
        
        card.style.display = hasVisible ? 'block' : 'none';
    });
});
</script>

<?php include 'user/_smm_footer.php'; ?>
