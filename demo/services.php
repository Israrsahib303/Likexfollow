<?php
// File: services.php (Root Directory)
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 1. Header Logic ---
// Hum user header use karenge lekin ensure karenge ke paths broken na hon
// Agar user logged in nahi hai to session error na aye
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Page Title for SEO
$page_title = "Best SMM Services List - Cheap & Instant";
ob_start();
include 'user/_header.php'; 
$header_html = ob_get_clean();
// Title Replace Hack for SEO
$header_html = str_replace('<title>LikexFollow | The Crazy SMM Panel</title>', "<title>$page_title</title>", $header_html);
echo $header_html;

// --- 2. Currency Setup ---
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = 1; 
$curr_symbol = 'Rs';

if ($curr_code != 'PKR') {
    // Helper function se rate lein (ensure function exists in helpers.php)
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
    echo "<div class='p-4 bg-red-100 text-red-700 text-center'>Database Error: " . $e->getMessage() . "</div>";
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* --- 🎨 THEME STYLES --- */
:root {
    --primary: #4F46E5;
    --bg-body: #F8FAFC;
    --card-bg: #FFFFFF;
    --text-main: #1E293B;
    --text-sub: #64748B;
    --border: #E2E8F0;
    --radius: 12px;
}

body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); }

/* --- HEADER --- */
.page-header {
    background: #fff; padding: 40px 20px; border-radius: var(--radius); margin-bottom: 40px;
    text-align: center; border: 1px solid var(--border);
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
    background-image: linear-gradient(to right, #f8fafc, #fff);
}
.page-title { font-size: 2.5rem; font-weight: 800; margin: 0; color: var(--text-main); letter-spacing: -1px; }
.page-subtitle { color: var(--text-sub); font-size: 1.1rem; margin-top: 10px; }

/* --- SEARCH --- */
.search-container { max-width: 600px; margin: 0 auto 50px auto; position: relative; }
.search-input {
    width: 100%; padding: 18px 25px 18px 55px; border: 2px solid #e2e8f0;
    border-radius: 50px; background: #fff; font-size: 1.1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.3s; color: var(--text-main);
}
.search-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
.search-icon { position: absolute; left: 25px; top: 50%; transform: translateY(-50%); color: var(--text-sub); font-size: 1.2rem; }

/* --- CATEGORY CARD --- */
.cat-card {
    background: #fff; border-radius: 16px; margin-bottom: 30px;
    overflow: hidden; border: 1px solid var(--border); 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01);
}
.cat-header {
    padding: 20px 30px; background: #fff; border-bottom: 1px solid var(--border);
    font-weight: 800; font-size: 1.2rem; color: #0f172a;
    display: flex; justify-content: space-between; align-items: center;
}
.cat-badge { background: #eff6ff; color: var(--primary); padding: 5px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; }

/* --- TABLE --- */
.table-responsive { overflow-x: auto; }
.svc-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.svc-table th {
    text-align: left; padding: 15px 30px; color: var(--text-sub);
    font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;
    background: #f8fafc; border-bottom: 1px solid var(--border);
}
.svc-table td {
    padding: 20px 30px; border-bottom: 1px solid #f1f5f9;
    color: var(--text-main); vertical-align: middle;
}
.svc-table tr:hover td { background: #fcfcfc; }
.svc-table tr:last-child td { border-bottom: none; }

/* --- LINKS & BUTTONS --- */
.svc-link { 
    font-weight: 600; color: #334155; font-size: 1rem; margin-bottom: 8px; display: block; text-decoration: none; transition: 0.2s; 
}
.svc-link:hover { color: var(--primary); text-decoration: underline; }

.meta-tags { display: flex; gap: 8px; flex-wrap: wrap; }
.badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.bg-time { background: #f1f5f9; color: var(--text-sub); border: 1px solid #e2e8f0; }
.bg-refill { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.bg-cancel { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.price-tag {
    font-weight: 800; color: var(--text-main); background: #f1f5f9;
    padding: 8px 12px; border-radius: 8px; font-size: 0.9rem;
}

.action-btn {
    background: var(--primary); color: #fff;
    padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600;
    transition: 0.2s; display: inline-block;
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
}
.action-btn:hover { background: #4338ca; transform: translateY(-1px); }

.id-pill {
    background: #fff; color: var(--text-sub); padding: 4px 8px; border-radius: 6px;
    font-family: monospace; border: 1px solid #e2e8f0; font-size: 0.8rem;
}

/* Mobile */
@media (max-width: 768px) {
    .svc-table th, .svc-table td { padding: 15px 15px; }
    .meta-tags { gap: 4px; }
    .badge { font-size: 0.65rem; padding: 2px 6px; }
    .action-btn { padding: 6px 12px; font-size: 0.8rem; }
    .page-title { font-size: 1.8rem; }
}
</style>

<div class="container mx-auto px-4 py-10 max-w-7xl"> <div class="page-header">
        <h1 class="page-title">Explore Services</h1>
        <p class="page-subtitle">Boost your social media presence with our premium services.</p>
    </div>

    <div class="search-container">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <input type="text" id="search" class="search-input" placeholder="What are you looking for? (e.g. TikTok Views)...">
    </div>

    <div id="services-wrapper">
        <?php if (empty($grouped)): ?>
            <div class="text-center py-20">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-2xl font-bold text-slate-400">No Services Found</h3>
            </div>
        <?php else: ?>
            
            <?php foreach ($grouped as $catName => $list): ?>
                <div class="cat-card" data-name="<?= strtolower(sanitize($catName)) ?>">
                    <div class="cat-header">
                        <span><i class="fas fa-folder-open text-indigo-400 mr-2"></i> <?= sanitize($catName) ?></span>
                        <span class="cat-badge"><?= count($list) ?></span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="svc-table">
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th>Service Name</th>
                                    <th width="120">Price / 1K</th>
                                    <th width="140">Min / Max</th>
                                    <th width="120" class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list as $s): 
                                    // 🔥 CALCULATE CUSTOM PRICE IF USER IS LOGGED IN
                                    $base_rate = (float)$s['service_rate'];
                                    if ($user_id > 0) {
                                        $base_rate = get_final_user_price($user_id, $s['provider_id'], $s['category'], $s['id'], $base_rate);
                                    }
                                    
                                    $rate = $base_rate;
                                    if ($curr_code != 'PKR') $rate *= $curr_rate;
                                    
                                    // SEO Link Generation
                                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $s['name'])));
                                    $seo_link = "service/{$s['id']}/$slug"; // Uses .htaccess rewrite
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
                                            <span class="badge bg-time"><i class="far fa-clock"></i> <?= formatSmmAvgTime($s['avg_time'] ?? 'Instant') ?></span>
                                            <?php if($s['has_refill']): ?><span class="badge bg-refill"><i class="fas fa-sync-alt"></i> Refill</span><?php endif; ?>
                                            <?php if($s['has_cancel']): ?><span class="badge bg-cancel"><i class="fas fa-ban"></i> Cancel</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price-tag"><?= $curr_symbol . ' ' . number_format($rate, 2) ?></span>
                                    </td>
                                    <td>
                                        <div class="text-xs text-slate-500 font-bold mb-1">MIN: <?= number_format($s['min']) ?></div>
                                        <div class="text-xs text-slate-500 font-bold">MAX: <?= number_format($s['max']) ?></div>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= $seo_link ?>" class="action-btn">
                                            Buy Now <i class="fas fa-arrow-right ml-1"></i>
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
// Search Functionality
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