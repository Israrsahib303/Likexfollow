<?php
// File: panel/semrush_keyword_magic.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE & AUTO-SEED DB TABLES ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_keywords (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword VARCHAR(255) UNIQUE,
        search_volume INT DEFAULT 0,
        keyword_difficulty INT DEFAULT 0,
        cpc DECIMAL(10,2) DEFAULT 0.00,
        intent VARCHAR(50) DEFAULT 'Unknown',
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS site_seo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(255),
        page_url VARCHAR(255) UNIQUE,
        meta_title VARCHAR(255),
        meta_description TEXT,
        meta_keywords TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // 🔥 FIX FOR "0 PAGES" BUG: Auto-Seed the Matrix if it's empty!
    $page_count = $db->query("SELECT COUNT(*) FROM site_seo")->fetchColumn();
    if ($page_count == 0) {
        $default_pages = [
            ['Homepage', '/index.php'], ['Services', '/services.php'], 
            ['Products/Store', '/products.php'], ['FAQ', '/faq.php'], 
            ['About Us', '/about.php'], ['Contact', '/contact.php'],
            ['Blog Hub', '/blog.php']
        ];
        $stmt_seed = $db->prepare("INSERT INTO site_seo (page_name, page_url, meta_title, meta_description, meta_keywords) VALUES (?, ?, '', '', '')");
        foreach($default_pages as $dp) {
            $stmt_seed->execute([$dp[0], $dp[1]]);
        }
    }
    
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// Helper function to smartly inject keywords without duplication
function smartInjectKeyword($db, $page_id, $keywords_to_add) {
    if (!is_array($keywords_to_add)) $keywords_to_add = [$keywords_to_add];
    
    $stmt = $db->prepare("SELECT id, meta_keywords FROM site_seo WHERE id = ?");
    $stmt->execute([$page_id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($page) {
        $current = array_filter(array_map('trim', explode(',', $page['meta_keywords'] ?? '')));
        $added_any = false;
        
        foreach($keywords_to_add as $kw) {
            $exists = false;
            foreach($current as $c) {
                if(strtolower($c) === strtolower($kw)) { $exists = true; break; }
            }
            if(!$exists && !empty($kw)) {
                $current[] = $kw;
                $added_any = true;
            }
        }
        
        if ($added_any) {
            $new_string = implode(', ', $current);
            $upd = $db->prepare("UPDATE site_seo SET meta_keywords = ? WHERE id = ?");
            $upd->execute([$new_string, $page_id]);
            return true;
        }
    }
    return false;
}

// ==========================================
// 📡 1. DYNAMIC SEMRUSH API FETCHER (MULTI-LINE + REGION)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_fetch_keywords'])) {
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $seeds_raw = sanitize($_POST['seed_keywords']);
    $region = sanitize($_POST['target_region']); // Target Database (US, PK, UK, IN)
    
    $seeds = array_filter(array_map('trim', explode("\n", $seeds_raw)));
    
    if (!empty($seeds)) {
        $inserted = 0; $updated = 0; $failed_seeds = [];
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO semrush_keywords (keyword, search_volume, keyword_difficulty, cpc, intent) 
                                  VALUES (?, ?, ?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE search_volume=?, keyword_difficulty=?, cpc=?, intent=?");
            
            foreach ($seeds as $seed) {
                // Now fetching specifically for the selected region (e.g. PK for Pakistan)
                $api_url = "https://api.semrush.com/?type=phrase_related&key=" . urlencode($api_key) . "&phrase=" . urlencode($seed) . "&export_columns=Ph,Nq,Kd,Cp,In&database=" . urlencode($region) . "&display_limit=30";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response && strpos($response, 'ERROR') !== 0 && !empty(trim($response))) {
                    $lines = explode("\n", trim($response));
                    array_shift($lines); 
                    
                    foreach($lines as $line) {
                        if (empty(trim($line))) continue;
                        $data = str_getcsv($line, ";");
                        if (count($data) < 5) continue;
                        
                        $kw = sanitize($data[0]);
                        $vol = (int)$data[1];
                        $kd = (int)$data[2];
                        $cpc = (float)$data[3];
                        $intent_id = (int)$data[4];
                        
                        $intent = 'Unknown';
                        if ($intent_id === 0) $intent = 'Commercial';
                        elseif ($intent_id === 1) $intent = 'Informational';
                        elseif ($intent_id === 2) $intent = 'Navigational';
                        elseif ($intent_id === 3) $intent = 'Transactional';
                        
                        if(!empty($kw)) {
                            $check = $db->prepare("SELECT id FROM semrush_keywords WHERE keyword = ?");
                            $check->execute([$kw]);
                            if($check->fetchColumn()) $updated++; else $inserted++;
                            $stmt->execute([$kw, $vol, $kd, $cpc, $intent, $vol, $kd, $cpc, $intent]);
                        }
                    }
                } else {
                    $failed_seeds[] = $seed; 
                }
            }
            $db->commit();
            
            $message = "API Sync Complete for Region [".strtoupper($region)."]! 🚀 Added $inserted new, updated $updated.";
            if(!empty($failed_seeds)) {
                $message .= " (No API data for: " . implode(', ', $failed_seeds) . ")";
            }
            $msg_type = "success";
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Database Error during sync."; $msg_type = "danger";
        }
    } else {
        $message = "Seed keywords cannot be empty!"; $msg_type = "danger";
    }
}

// --- 2. ACTION: INJECT KEYWORD TO PAGE(S) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inject_keyword'])) {
    $keyword = sanitize($_POST['target_keyword']);
    $page_id = sanitize($_POST['page_id']);
    
    if(!empty($keyword) && !empty($page_id)) {
        if ($page_id === 'all') {
            $pages = $db->query("SELECT id FROM site_seo")->fetchAll(PDO::FETCH_ASSOC);
            $injected_count = 0;
            foreach($pages as $p) {
                if (smartInjectKeyword($db, $p['id'], $keyword)) $injected_count++;
            }
            if ($injected_count > 0) {
                $message = "Boom! 🚀 '$keyword' injected into $injected_count pages globally."; $msg_type = "success";
            } else {
                $message = "'$keyword' is already present in all pages."; $msg_type = "info";
            }
        } else {
            if (smartInjectKeyword($db, (int)$page_id, $keyword)) {
                $message = "Keyword '$keyword' injected perfectly!"; $msg_type = "success";
            } else {
                $message = "Keyword is already assigned to this page."; $msg_type = "info";
            }
        }
    }
}

// --- 3. AUTO-PILOT INJECTOR (TOP KEYWORDS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_inject_top'])) {
    $top_kws = $db->query("SELECT keyword FROM semrush_keywords ORDER BY search_volume DESC, keyword_difficulty ASC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
    
    if(!empty($top_kws)) {
        $pages = $db->query("SELECT id FROM site_seo")->fetchAll(PDO::FETCH_ASSOC);
        $updated_pages = 0;
        foreach($pages as $p) {
            if (smartInjectKeyword($db, $p['id'], $top_kws)) $updated_pages++;
        }
        if ($updated_pages > 0) {
            $message = "Auto-Pilot Success! 🤖 Top 10 high-ranking keywords injected into $updated_pages pages."; $msg_type = "success";
        } else {
            $message = "All Top 10 keywords are already perfectly injected in all pages! 👑"; $msg_type = "info";
        }
    } else {
        $message = "Vault is empty. Please fetch keywords first."; $msg_type = "warning";
    }
}

// --- 4. DATA AGGREGATION & UI PREP ---
$total_kws = 0; $total_vol = 0; $easy_kws = 0;
$keyword_list = []; $pages_list = [];

if($table_exists) {
    $stats = $db->query("SELECT COUNT(*) as c, SUM(search_volume) as v FROM semrush_keywords")->fetch(PDO::FETCH_ASSOC);
    $total_kws = $stats['c'] ?? 0;
    $total_vol = $stats['v'] ?? 0;
    $easy_kws = $db->query("SELECT COUNT(*) FROM semrush_keywords WHERE keyword_difficulty <= 29")->fetchColumn() ?: 0;
    $keyword_list = $db->query("SELECT * FROM semrush_keywords ORDER BY search_volume DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
    try {
        $pages_list = $db->query("SELECT id, page_name FROM site_seo ORDER BY page_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}

function getKdBadge($kd) {
    if($kd <= 14) return ['class' => 'kd-very-easy', 'text' => 'Very Easy'];
    if($kd <= 29) return ['class' => 'kd-easy', 'text' => 'Easy'];
    if($kd <= 49) return ['class' => 'kd-possible', 'text' => 'Possible'];
    if($kd <= 84) return ['class' => 'kd-hard', 'text' => 'Hard'];
    return ['class' => 'kd-very-hard', 'text' => 'Very Hard'];
}

function getIntentBadge($intent) {
    $i = strtolower(trim($intent));
    if($i == 'informational' || $i == 'i') return 'intent-info';
    if($i == 'navigational' || $i == 'n') return 'intent-nav';
    if($i == 'commercial' || $i == 'c') return 'intent-com';
    if($i == 'transactional' || $i == 't') return 'intent-trans';
    return 'intent-none';
}

$page_title = "Global SEO Keyword Magic";
if (file_exists('_header.php')) { include '_header.php'; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ==============================================
       🔥 BEAST RESPONSIVE UI/UX DESIGN 🔥
       ============================================== */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --d-purple: #4f46e5;
        --b-color: #e2e8f0; --bg-color: #f8fafc;
        --t-dark: #0f172a; --t-muted: #64748b; 
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    
    /* Layout Fixes */
    .beast-container { width: 100%; max-width: 1500px; margin: 0 auto; padding: 20px; overflow: hidden; }
    
    /* Smooth Animations */
    @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(99,102,241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99,102,241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); } }
    
    .anim-slide { animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    /* Cards */
    .magic-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); overflow: hidden; }
    
    /* Stat Boxes */
    .metric-box { padding: 1.5rem; text-align: center; border-right: 1px solid var(--b-color); display: flex; flex-direction: column; justify-content: center; }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 800; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .m-value { font-size: 2.5rem; font-weight: 900; color: var(--t-dark); line-height: 1; letter-spacing: -1px; }

    /* Buttons */
    .btn-action { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; white-space: nowrap; }
    .btn-action:hover { box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5); transform: translateY(-2px); color: #fff; }

    .btn-auto-inject { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; transition: 0.3s; animation: pulseGlow 2s infinite; white-space: nowrap; }
    .btn-auto-inject:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5); color: #fff; animation: none; }

    .btn-inject { background: #f8fafc; color: var(--d-purple); border: 2px solid var(--l-purple); padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; transition: 0.2s; cursor: pointer; white-space: nowrap; }
    .btn-inject:hover { background: var(--d-purple); border-color: var(--d-purple); color: #fff; transform: scale(1.05); }

    /* Inputs */
    .form-select-sm { border-radius: 8px; border: 2px solid var(--b-color); font-weight: 700; color: var(--t-dark); padding: 6px 12px; transition: 0.3s; }
    .form-select-sm:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    .api-textarea { border: 2px solid var(--b-color); border-radius: 12px; padding: 12px 15px; font-weight: 600; outline: none; transition: 0.3s; width: 100%; height: 100px; resize: none; color: var(--t-dark); font-size: 0.95rem; }
    .api-textarea:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    .api-select-region { border: 2px solid var(--b-color); border-radius: 12px; padding: 12px; font-weight: 800; color: var(--t-dark); background: #f8fafc; transition: 0.3s; outline: none; }
    .api-select-region:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    .search-wrapper { position: relative; width: 100%; max-width: 350px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--t-muted); }
    .search-input { width: 100%; padding: 12px 15px 12px 42px; border: 2px solid var(--l-purple); border-radius: 12px; font-weight: 700; color: var(--t-dark); outline: none; transition: 0.3s; background: #fff; }
    .search-input:focus { border-color: var(--d-purple); }
    
    /* Table Fixes */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table th { background: #f8fafc; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: var(--t-muted); border-bottom: 2px solid var(--b-color); padding: 15px; white-space: nowrap; }
    .table td { padding: 15px; border-bottom: 1px solid var(--b-color); vertical-align: middle; white-space: nowrap; }
    .kw-text { font-size: 1rem; font-weight: 700; }

    /* Badges */
    .badge-metric { display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; }
    .b-vol { background: #f8fafc; color: var(--t-dark); border: 1px solid var(--b-color); }
    
    .kd-circle { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 0.85rem; margin: 0 auto; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
    .kd-very-easy { background: #166534; } 
    .kd-easy { background: #22c55e; } 
    .kd-possible { background: #f59e0b; } 
    .kd-hard { background: #ef4444; } 
    .kd-very-hard { background: #7f1d1d; } 

    .badge-intent { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .intent-info { background: #dbeafe; color: #1e40af; }
    .intent-nav { background: #f3e8ff; color: #4338ca; }
    .intent-com { background: #fef3c7; color: #b45309; }
    .intent-trans { background: #dcfce7; color: #166534; }
    .intent-none { background: #f1f5f9; color: #64748b; }

    /* Responsive Grid Fixes */
    @media (max-width: 992px) {
        .metric-box { border-right: none; border-bottom: 1px solid var(--b-color); }
        .metric-box:last-child { border-bottom: none; }
    }
</style>

<div class="beast-container">
    
    <div class="magic-card p-4 p-md-5 mb-4 anim-slide">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <h2 class="fw-bolder text-dark mb-2" style="font-size: 2rem; letter-spacing: -1px;">
                    <i class="fas fa-satellite-dish me-2 text-indigo-500"></i> Keyword Vault
                </h2>
                <p class="text-muted fw-medium mb-0">Target specific regions. Fetch keywords line-by-line via SEMrush API and auto-inject them across your website.</p>
            </div>
            
            <div class="col-lg-7 text-lg-end">
                <form method="POST" id="apiForm" class="d-flex flex-column flex-md-row justify-content-lg-end gap-3 m-0">
                    <input type="hidden" name="api_fetch_keywords" value="1">
                    
                    <div style="flex: 1; max-width: 300px;">
                        <textarea name="seed_keywords" class="api-textarea" placeholder="Enter keywords line-by-line...&#10;smm panel pakistan&#10;buy followers" required></textarea>
                    </div>
                    
                    <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                        <select name="target_region" class="api-select-region">
                            <option value="pk">🇵🇰 Pakistan Database</option>
                            <option value="us">🇺🇸 USA Database</option>
                            <option value="uk">🇬🇧 UK Database</option>
                            <option value="in">🇮🇳 India Database</option>
                        </select>
                        <button type="button" class="btn-action w-100" id="fetchApiBtn" onclick="runApiFetch()">
                            <i class="fas fa-bolt text-warning"></i> Run API Scan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-4 shadow-sm mb-4 border-0 p-3 anim-slide" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'warning' ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>; font-size: 1.05rem;">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> fs-5 me-2 align-middle"></i> <?= $message ?>
        </div> 
    <?php endif; ?>

    <div class="magic-card mb-4 anim-slide anim-delay-1">
        <div class="row g-0">
            <div class="col-md-4 metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-database me-1"></i> Keywords in Vault</div>
                <div class="m-value"><?= number_format($total_kws) ?></div>
            </div>
            <div class="col-md-4 metric-box">
                <div class="m-title"><i class="fas fa-chart-line me-1"></i> Total Search Volume</div>
                <div class="m-value" style="color: var(--d-purple);"><?= number_format($total_vol) ?></div>
            </div>
            <div class="col-md-4 metric-box">
                <div class="m-title"><i class="fas fa-crosshairs me-1"></i> Easy Targets (KD < 30)</div>
                <div class="m-value text-success"><?= number_format($easy_kws) ?></div>
            </div>
        </div>
    </div>

    <div class="magic-card d-flex flex-column anim-slide anim-delay-2" style="min-height: 500px;">
        <div class="p-3 p-md-4 bg-light border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            
            <form method="POST" id="autoInjectForm" class="m-0">
                <input type="hidden" name="auto_inject_top" value="1">
                <button type="button" class="btn-auto-inject" onclick="if(confirm('This will inject the Top 10 Keywords into ALL pages. Proceed?')) document.getElementById('autoInjectForm').submit();">
                    <i class="fas fa-robot me-2"></i> Auto-Pilot Injection
                </button>
            </form>

            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="liveSearch" class="search-input" placeholder="Search keywords..." onkeyup="filterTable()">
            </div>
        </div>
        
        <div class="table-responsive flex-grow-1" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover mb-0 align-middle" id="kwTable">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="ps-4" style="width: 35%;">Keyword & Intent</th>
                        <th class="text-end">Volume</th>
                        <th class="text-center">KD %</th>
                        <th class="text-end">CPC</th>
                        <th class="text-end pe-4" style="width: 30%;">Inject SEO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($keyword_list)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-4x mb-3 opacity-25"></i><br><h5 class="fw-bold text-dark">Vault is Empty</h5>Enter keywords above to fetch regional data.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($keyword_list as $kw): 
                        $kd_data = getKdBadge($kw['keyword_difficulty']);
                        $intent_class = getIntentBadge($kw['intent']);
                        $intent_label = $kw['intent'] == 'Unknown' ? '-' : $kw['intent'][0];
                    ?>
                    <tr class="kw-row">
                        <td class="ps-4 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="kw-text text-dark"><?= htmlspecialchars($kw['keyword']) ?></span>
                                <?php if($kw['intent'] != 'Unknown'): ?>
                                    <span class="badge-intent <?= $intent_class ?>" title="<?= htmlspecialchars($kw['intent']) ?> Intent"><?= $intent_label ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end">
                            <span class="badge-metric b-vol"><?= number_format($kw['search_volume']) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="kd-circle <?= $kd_data['class'] ?>" title="<?= $kd_data['text'] ?> to rank">
                                <?= $kw['keyword_difficulty'] ?>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-muted">
                            $<?= number_format($kw['cpc'], 2) ?>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" class="d-flex justify-content-end align-items-center gap-2 m-0">
                                <input type="hidden" name="target_keyword" value="<?= htmlspecialchars($kw['keyword']) ?>">
                                <select name="page_id" class="form-select form-select-sm w-auto" required>
                                    <option value="" disabled selected>Select Page...</option>
                                    <option value="all" class="fw-bold text-success">✅ Inject to All Pages</option>
                                    <?php foreach($pages_list as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['page_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="inject_keyword" class="btn-inject"><i class="fas fa-crosshairs me-1"></i> Inject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Live JS Fast Search Filter
    function filterTable() {
        let input = document.getElementById("liveSearch");
        let filter = input.value.toLowerCase();
        let table = document.getElementById("kwTable");
        let rows = table.getElementsByClassName("kw-row");

        for (let i = 0; i < rows.length; i++) {
            let kwText = rows[i].querySelector(".kw-text").textContent || rows[i].querySelector(".kw-text").innerText;
            if (kwText.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
    
    // API Fetch Loader Animation
    function runApiFetch() {
        const inp = document.querySelector('.api-textarea');
        if(inp.value.trim() === '') {
            alert('Please enter at least one keyword.');
            return;
        }
        const btn = document.getElementById('fetchApiBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Connecting API...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.9';
        document.getElementById('apiForm').submit();
    }
</script>

<?php 
if (file_exists('_footer.php')) { include '_footer.php'; }
?>
