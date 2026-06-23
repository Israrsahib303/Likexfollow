<?php
// File: panel/semrush_competitor_pages.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE & GOD-MODE PATCHER ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_competitor_top_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competitor_domain VARCHAR(150),
        page_url VARCHAR(191),
        traffic INT DEFAULT 0,
        traffic_percent DECIMAL(5,2) DEFAULT 0.00,
        keywords_count INT DEFAULT 0,
        data_date DATE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Force Add ANY missing column silently
    $required_columns = [
        'competitor_domain' => 'VARCHAR(150)',
        'page_url' => 'VARCHAR(191)',
        'traffic' => 'INT DEFAULT 0',
        'traffic_percent' => 'DECIMAL(5,2) DEFAULT 0.00',
        'keywords_count' => 'INT DEFAULT 0',
        'data_date' => 'DATE'
    ];

    foreach ($required_columns as $col => $type) {
        try {
            $db->exec("ALTER TABLE semrush_competitor_top_pages ADD COLUMN $col $type");
        } catch (Exception $e) {}
    }

    // Force patch for Safe Indexing
    try { $db->exec("ALTER TABLE semrush_competitor_top_pages MODIFY page_url VARCHAR(191)"); } catch (Exception $e) {}
    try { 
        $db->exec("ALTER TABLE semrush_competitor_top_pages DROP INDEX unique_comp_page"); 
        $db->exec("ALTER TABLE semrush_competitor_top_pages ADD UNIQUE KEY unique_comp_page (competitor_domain, page_url, data_date)");
    } catch(Exception $e) {}

    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
    $message = "CRITICAL DB Error: " . $e->getMessage();
    $msg_type = "danger";
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

function getCleanDomainFromUrl($url) {
    if(empty($url)) return 'Unknown';
    $host = parse_url('http://' . preg_replace('#^https?://#', '', $url), PHP_URL_HOST);
    return str_ireplace('www.', '', $host ?? 'Unknown');
}

// ==========================================
// 📡 1. DYNAMIC API FETCHER (100% REAL DATA ONLY)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_sync_pages'])) {
    header('Content-Type: application/json');
    
    if (!function_exists('curl_init')) {
        echo json_encode(['success' => false, 'error' => 'cURL extension missing!']);
        exit;
    }

    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $raw_domain = sanitize($_POST['target_domain'] ?? '');
    $db_region = sanitize($_POST['region'] ?? 'us');
    $current_date = date('Y-m-d');
    
    $domain = getCleanDomainFromUrl($raw_domain);

    if (empty($domain) || $domain === 'Unknown') {
        echo json_encode(['success' => false, 'error' => 'Invalid Competitor Domain!']);
        exit;
    }

    if(stripos($domain, 'likexfollow.com') !== false) {
        echo json_encode(['success' => false, 'error' => 'You cannot X-Ray your own domain here!']);
        exit;
    }

    // SEMrush API Call: Domain Organic (Ur=URL, Nq=Search Vol/Traffic Est, Tr=Traffic %)
    $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($domain) . "&export_columns=Ur,Nq,Tr&database=" . urlencode($db_region) . "&display_limit=100";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response && strpos($response, 'ERROR') !== 0 && !empty(trim($response))) {
        $lines = explode("\n", trim($response));
        array_shift($lines); // Remove Headers
        
        $url_data = [];
        $inserted = 0; $updated = 0;
        
        foreach($lines as $line) {
            if (empty(trim($line))) continue;
            $data = str_getcsv($line, ";");
            if (count($data) < 3) continue;
            
            $url = sanitize(substr($data[0], 0, 191));
            $vol = (int)$data[1]; 
            $traffic_pct = (float)$data[2];
            
            if(!empty($url)) {
                if(!isset($url_data[$url])) {
                    $url_data[$url] = ['kw_count' => 0, 'traffic_vol' => 0, 'traffic_pct' => 0];
                }
                $url_data[$url]['kw_count'] += 1;
                $url_data[$url]['traffic_vol'] += $vol;
                $url_data[$url]['traffic_pct'] += $traffic_pct;
            }
        }

        if (count($url_data) > 0) {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO semrush_competitor_top_pages (competitor_domain, page_url, traffic, traffic_percent, keywords_count, data_date) 
                                      VALUES (?, ?, ?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE traffic=?, traffic_percent=?, keywords_count=?");
                
                foreach($url_data as $url => $metrics) {
                    $t_pct = min(100.00, $metrics['traffic_pct']);
                    
                    $check = $db->prepare("SELECT id FROM semrush_competitor_top_pages WHERE competitor_domain = ? AND page_url = ? AND data_date = ?");
                    $check->execute([$domain, $url, $current_date]);
                    if($check->fetchColumn()) $updated++; else $inserted++;

                    $stmt->execute([$domain, $url, $metrics['traffic_vol'], $t_pct, $metrics['kw_count'], $current_date, 
                                    $metrics['traffic_vol'], $t_pct, $metrics['kw_count']]);
                }
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Top Pages X-Ray Complete! 🚀 Scanned " . count($url_data) . " real pages for $domain."
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'error' => 'Database Sync Error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => "API returned 0 organic traffic pages for '$domain'. It might be a dead domain."]);
        }
    } else {
        // FAKE DATA REMOVED COMPLETELY. STRICT REAL DATA ONLY.
        $error_msg = strpos($response, 'ERROR') === 0 ? trim($response) : "No organic data found in SEMrush for this domain.";
        echo json_encode(['success' => false, 'error' => "Real API Data: " . $error_msg]);
    }
    exit;
}

// --- 2. X-RAY DATA AGGREGATION ---
$competitors = [];
$selected_rival = '';
$top_pages = [];
$total_rival_traffic = 0;
$dependency_ratio = 0;
$chart_labels = []; $chart_traffic = [];

if($table_exists) {
    try {
        $competitors = $db->query("SELECT DISTINCT competitor_domain FROM semrush_competitor_top_pages ORDER BY competitor_domain ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
    
    $selected_rival = isset($_GET['rival']) ? sanitize($_GET['rival']) : (!empty($competitors) ? $competitors[0] : '');

    if(!empty($selected_rival)) {
        try {
            $stmt = $db->prepare("SELECT * FROM semrush_competitor_top_pages WHERE competitor_domain = ? ORDER BY traffic DESC LIMIT 50");
            $stmt->execute([$selected_rival]);
            $top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if(count($top_pages) > 0) {
                $dependency_ratio = (float)$top_pages[0]['traffic_percent'];
                $sum_traffic = 0;
                $limit = 0;
                
                foreach($top_pages as $p) {
                    $sum_traffic += $p['traffic'];
                    if($limit++ < 6) {
                        $path = parse_url($p['page_url'], PHP_URL_PATH);
                        $chart_labels[] = empty($path) || $path == '/' ? 'Homepage' : (strlen($path) > 15 ? substr($path, 0, 15).'..' : $path);
                        $chart_traffic[] = (int)$p['traffic'];
                    }
                }
                $total_rival_traffic = $sum_traffic;
            }
        } catch(PDOException $e) {}
    }
}

$page_title = "Competitor Top Pages";
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
        --c-success: #10b981; --c-warning: #f59e0b; --c-danger: #ef4444;
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .beast-container { width: 100%; max-width: 1500px; margin: 0 auto; padding: 15px; }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    .anim-slide { animation: slideInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    .xray-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 20px -10px rgba(0,0,0,0.05); overflow: hidden; transition: 0.3s; }
    
    /* Responsive Metrics Grid */
    .metrics-grid { display: flex; flex-wrap: wrap; }
    .metric-box { flex: 1 1 300px; padding: 1.5rem; text-align: center; border-right: 1px solid var(--b-color); display: flex; flex-direction: column; justify-content: center; }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 800; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .m-value { font-size: 2.2rem; font-weight: 900; color: var(--t-dark); line-height: 1; letter-spacing: -1px; word-break: break-word; }

    /* Inputs & Selects */
    .api-input { border: 2px solid var(--b-color); border-radius: 10px; padding: 10px 15px; font-weight: 700; color: var(--t-dark); outline: none; transition: 0.3s; width: 100%; }
    .api-input:focus { border-color: var(--d-purple); box-shadow: 0 0 0 3px var(--l-purple); }
    
    .api-select-region { border: 2px solid var(--b-color); border-radius: 10px; padding: 10px; font-weight: 800; color: var(--t-dark); background: #fff; transition: 0.3s; outline: none; cursor: pointer; }
    .api-select-region:focus { border-color: var(--d-purple); }

    .select-premium { border: 1px solid var(--b-color); background: #fff; border-radius: 8px; padding: 8px 15px; font-weight: 700; color: var(--d-purple); outline: none; cursor: pointer; width: 100%; max-width: 300px; }

    /* Buttons */
    .btn-action { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; white-space: nowrap; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(15, 23, 42, 0.3); color: #fff; }

    .btn-info-modal { background: #f8fafc; color: var(--t-dark); border: 2px solid var(--b-color); padding: 8px 16px; border-radius: 10px; font-weight: 800; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-info-modal:hover { background: var(--l-purple); border-color: var(--d-purple); color: var(--d-purple); }

    .btn-clone { background: var(--l-purple); color: var(--d-purple); border: 1px solid var(--c-purple); padding: 5px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; transition: 0.2s; cursor: pointer; white-space: nowrap; }
    .btn-clone:hover { background: var(--d-purple); color: #fff; }

    /* Tables & Badges */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table th { background: #f8fafc; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: var(--t-muted); border-bottom: 2px solid var(--b-color); padding: 15px; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
    .table td { padding: 15px; border-bottom: 1px solid var(--b-color); vertical-align: middle; white-space: nowrap; transition: 0.2s; }
    .table tr:hover td { background: #f8fafc; }

    .table-url { font-size: 0.9rem; font-weight: 700; color: var(--d-purple); text-decoration: none; word-break: break-all; transition: 0.2s; }
    .table-url:hover { color: #000; text-decoration: underline; }

    .badge-traffic { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 800; display: inline-block; }
    .badge-dependency { background: #fee2e2; color: var(--c-danger); font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; font-weight: 800; margin-left: 8px; border: 1px solid #fecaca; display: inline-block; margin-top: 5px; }

    /* Modal Styling */
    .glass-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 9999; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s ease; }
    .glass-overlay.show { display: flex; opacity: 1; }
    .modal-beast { background: #fff; width: 90%; max-width: 500px; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.9); transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
    .glass-overlay.show .modal-beast { transform: scale(1); }
    .close-modal { position: absolute; top: 15px; right: 15px; width: 35px; height: 35px; background: #f1f5f9; border: none; border-radius: 50%; font-size: 1.2rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; color: var(--t-muted); }
    .close-modal:hover { background: #fee2e2; color: #ef4444; }

    /* --- MOBILE RESPONSIVENESS FIXES --- */
    @media (max-width: 992px) {
        .metric-box { border-right: none; border-bottom: 1px solid var(--b-color); padding: 1rem; }
        .metric-box:last-child { border-bottom: none; }
        
        /* Stack header forms nicely */
        .header-controls { flex-direction: column; width: 100%; align-items: stretch !important; }
        .header-controls .btn-info-modal { width: 100%; justify-content: center; margin-bottom: 10px; }
        #apiForm { flex-direction: column; width: 100%; }
        .api-input, .api-select-region, .btn-action { width: 100%; max-width: 100%; }
        
        .m-value { font-size: 1.8rem; }
        .page-title-text { font-size: 1.6rem !important; }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="beast-container">
    
    <div class="xray-card p-4 mb-4 anim-slide d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="w-100 w-lg-auto mb-3 mb-lg-0">
            <h2 class="fw-bolder text-dark mb-1 page-title-text" style="font-size: 2rem; letter-spacing: -1px;">
                <i class="fas fa-file-invoice me-2 text-indigo-500"></i> Top Pages Sniper
            </h2>
            <p class="text-muted fw-medium mb-0 fs-6">Live API X-Ray. Discover real pages driving traffic to rivals.</p>
        </div>
        
        <div class="header-controls d-flex flex-wrap gap-2 align-items-center">
            <button class="btn-info-modal" onclick="toggleModal(true)">
                <i class="fas fa-info-circle"></i> Guide
            </button>
            <form method="POST" id="apiForm" class="m-0 d-flex gap-2 align-items-center">
                <input type="hidden" name="api_sync_pages" value="1">
                <input type="text" name="target_domain" class="api-input" placeholder="Rival Domain (e.g. smm.com)" required>
                <select name="region" class="api-select-region">
                    <option value="us">🇺🇸 US</option>
                    <option value="pk">🇵🇰 PK</option>
                    <option value="uk">🇬🇧 UK</option>
                    <option value="in">🇮🇳 IN</option>
                </select>
                <button type="button" class="btn-action" id="fetchApiBtn" onclick="runXrayApi()">
                    <i class="fas fa-crosshairs text-danger"></i> X-Ray
                </button>
            </form>
        </div>
    </div>

    <div id="alertPlaceholder"></div>
    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-3 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'info' ? '#e0e7ff; color: #4338ca;' : '#fee2e2; color: #991b1b;') ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> fs-4 me-3"></i> 
            <span style="font-size: 1rem;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 anim-slide anim-delay-1 gap-2">
        <h5 class="fw-bold m-0 text-dark"><i class="fas fa-filter text-muted me-2"></i> Sniper DB Vault</h5>
        <form method="GET" class="m-0 w-100 w-sm-auto">
            <select name="rival" class="select-premium shadow-sm w-100" onchange="this.form.submit()">
                <?php if(empty($competitors)): ?><option>No rivals scanned yet</option><?php endif; ?>
                <?php foreach($competitors as $comp): ?>
                    <option value="<?= htmlspecialchars($comp) ?>" <?= $comp === $selected_rival ? 'selected' : '' ?>><?= htmlspecialchars($comp) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if(!empty($selected_rival) && count($top_pages) > 0): ?>
    <div class="xray-card mb-4 anim-slide anim-delay-1">
        <div class="metrics-grid">
            <div class="metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-globe me-1"></i> Target Domain</div>
                <div class="m-value" style="color: var(--d-purple);"><?= htmlspecialchars($selected_rival) ?></div>
            </div>
            <div class="metric-box">
                <div class="m-title text-success"><i class="fas fa-chart-line me-1"></i> Estimated Traffic</div>
                <div class="m-value text-success"><?= number_format($total_rival_traffic) ?></div>
            </div>
            <div class="metric-box">
                <div class="m-title text-danger"><i class="fas fa-exclamation-circle me-1"></i> Dependency Risk</div>
                <div class="m-value text-danger">
                    <?= $dependency_ratio ?>% 
                    <?php if($dependency_ratio > 40): ?><br><span class="badge-dependency shadow-sm"><i class="fas fa-fire me-1"></i> Critical Target</span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 anim-slide anim-delay-2">
        <div class="col-lg-4">
            <div class="xray-card p-4 h-100 d-flex flex-column">
                <h6 class="fw-bolder mb-3 text-dark border-bottom pb-2"><i class="fas fa-chart-pie me-2 text-muted"></i> Traffic Share (Top 5)</h6>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                    <?php if(empty($chart_labels)): ?>
                        <div class="text-center text-muted w-100">
                            <i class="fas fa-chart-pie fa-3x mb-3 opacity-25"></i>
                            <div class="fw-bold small">No Data</div>
                        </div>
                    <?php else: ?>
                        <div style="height: 250px; width: 100%;">
                            <canvas id="topPagesChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="xray-card overflow-hidden h-100 d-flex flex-column">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bolder text-dark m-0"><i class="fas fa-list-ol me-2 text-primary"></i> Target List (Top 50)</h6>
                </div>
                <div class="table-responsive flex-grow-1" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-3">Page URL</th>
                                <th class="text-end">Traffic</th>
                                <th class="text-end">Share %</th>
                                <th class="text-end">KWs</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_pages as $index => $page): ?>
                            <tr>
                                <td class="ps-3 py-3">
                                    <div class="d-flex align-items-center">
                                        <?php if($index === 0): ?><i class="fas fa-crown text-warning me-2" title="The Alpha Page"></i><?php endif; ?>
                                        <a href="<?= htmlspecialchars($page['page_url']) ?>" target="_blank" class="table-url" title="<?= htmlspecialchars($page['page_url']) ?>">
                                            <?= htmlspecialchars(parse_url($page['page_url'], PHP_URL_PATH) ?: '/') ?>
                                        </a>
                                    </div>
                                    <div class="small text-muted mt-1 fw-bold" style="font-size: 0.7rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-link me-1"></i><?= htmlspecialchars($page['page_url']) ?></div>
                                </td>
                                <td class="text-end">
                                    <span class="badge-traffic"><?= number_format($page['traffic']) ?></span>
                                </td>
                                <td class="text-end fw-bolder text-dark fs-6">
                                    <?= number_format($page['traffic_percent'], 1) ?>%
                                </td>
                                <td class="text-end text-muted fw-bold small">
                                    <i class="fas fa-key me-1 text-primary"></i> <?= number_format($page['keywords_count']) ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn-clone" onclick="copyToClipboard(this, '<?= addslashes(htmlspecialchars($page['page_url'])) ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted xray-card anim-slide anim-delay-1 mx-3" style="min-height: 300px; display: flex; flex-direction: column; justify-content: center;">
            <i class="fas fa-satellite-dish fa-3x mb-3 opacity-25" style="color: var(--d-purple);"></i>
            <h5 class="fw-bolder text-dark">Awaiting Competitor Target</h5>
            <p class="small">Enter a real domain above to start the X-Ray scan.</p>
        </div>
    <?php endif; ?>
</div>

<div class="glass-overlay" id="infoModal">
    <div class="modal-beast">
        <button class="close-modal" onclick="toggleModal(false)"><i class="fas fa-times"></i></button>
        <h4 class="fw-bolder text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i> How Sniper Works?</h4>
        
        <div class="mb-3">
            <h6 class="fw-bold text-dark"><i class="fas fa-crosshairs text-danger me-2"></i> 1. True Data Sync</h6>
            <p class="text-muted small" style="line-height: 1.5;">This tool fetches 100% real data from SEMrush API. If a site has 0 traffic, it will show an error. No fake data is generated.</p>
        </div>
        
        <div class="mb-3">
            <h6 class="fw-bold text-dark"><i class="fas fa-fire text-warning me-2"></i> 2. Dependency Risk</h6>
            <p class="text-muted small" style="line-height: 1.5;">If a rival relies on <strong>one page</strong> for >40% traffic, that is their weak point. Clone their structure.</p>
        </div>
    </div>
</div>

<script>
    function toggleModal(show) {
        const modal = document.getElementById('infoModal');
        if(show) {
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
        } else {
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300);
        }
    }

    function showAlert(msg, type) {
        const ph = document.getElementById('alertPlaceholder');
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const bg = type === 'success' ? '#dcfce7' : '#fee2e2';
        const color = type === 'success' ? '#166534' : '#991b1b';
        
        ph.innerHTML = `
            <div class="alert fw-bold rounded-3 border-0 shadow-sm p-3 mb-4 d-flex align-items-center" style="background: ${bg}; color: ${color};">
                <i class="fas ${icon} fs-4 me-3"></i> 
                <span style="font-size: 0.95rem;">${msg}</span>
            </div>
        `;
    }

    function runXrayApi() {
        const inp = document.querySelector('.api-input');
        if(inp.value.trim() === '') {
            alert('Please enter a target domain first!');
            return;
        }
        
        document.getElementById('alertPlaceholder').innerHTML = ''; // Clear old alerts
        
        const btn = document.getElementById('fetchApiBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Scanning...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';
        
        const formData = new FormData(document.getElementById('apiForm'));
        
        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Complete!';
                btn.style.background = '#10b981';
                setTimeout(() => { window.location.href = window.location.href; }, 1000);
            } else {
                showAlert(data.error || "Unknown Error from API.", "danger");
                resetApiBtn(btn);
            }
        })
        .catch(err => {
            showAlert('Connection Failed. Please check your internet or API key limits.', "danger");
            resetApiBtn(btn);
        });
    }
    
    function resetApiBtn(btn) {
        btn.innerHTML = '<i class="fas fa-crosshairs text-danger"></i> X-Ray';
        btn.style.background = '';
        btn.style.opacity = '1';
        btn.style.pointerEvents = 'auto';
    }

    function copyToClipboard(button, text) {
        navigator.clipboard.writeText(text).then(function() {
            let originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.style.background = '#eef2ff';
            button.style.color = '#6366f1';
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style = '';
            }, 2000);
        });
    }

    <?php if(!empty($chart_labels)): ?>
    const ctx = document.getElementById('topPagesChart').getContext('2d');
    const doughnutColors = ['#6366f1', '#8b5cf6', '#d946ef', '#f43f5e', '#f59e0b'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                data: <?= json_encode($chart_traffic) ?>,
                backgroundColor: doughnutColors,
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '75%',
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', titleFont: {size: 13}, bodyFont: {size: 14, weight: 'bold'}, padding: 10 }
            }
        }
    });
    <?php endif; ?>
</script>

<?php require_once '_footer.php'; ?>