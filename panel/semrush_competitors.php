<?php
// File: panel/semrush_competitors.php
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

// --- 0. ADVANCED AUTO-CREATE SPY TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_competitor_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competitor_domain VARCHAR(255),
        keyword VARCHAR(255),
        position INT,
        search_volume INT,
        traffic_share DECIMAL(5,2),
        cpc DECIMAL(10,2),
        url VARCHAR(500),
        data_date DATE,
        UNIQUE KEY unique_comp_kw (competitor_domain(100), keyword(100), data_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Ensure site_seo exists for Auto-Steal logic
    $db->exec("CREATE TABLE IF NOT EXISTS site_seo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(150),
        meta_keywords TEXT
    )");
    
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// Helper: Extract clean domain from URL
function getCleanDomain($url) {
    $host = parse_url('http://' . preg_replace('#^https?://#', '', $url), PHP_URL_HOST);
    return str_ireplace('www.', '', $host ?? 'Unknown');
}

// Helper: Smart Inject Keyword for Auto-Steal
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
            foreach($current as $c) { if(strtolower($c) === strtolower($kw)) { $exists = true; break; } }
            if(!$exists && !empty($kw)) { $current[] = $kw; $added_any = true; }
        }
        if ($added_any) {
            $upd = $db->prepare("UPDATE site_seo SET meta_keywords = ? WHERE id = ?");
            $upd->execute([implode(', ', $current), $page_id]);
            return true;
        }
    }
    return false;
}

// ==========================================
// 📡 1. DYNAMIC SEMRUSH COMPETITOR API SYNC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_run_spy'])) {
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $competitor_url = sanitize($_POST['competitor_domain']);
    $db_region = sanitize($_POST['region'] ?? 'us');
    
    $domain = getCleanDomain($competitor_url);

    if (!empty($domain) && $domain !== 'Unknown') {
        if(stripos($domain, 'likexfollow.com') !== false) {
            $message = "You cannot spy on your own domain!"; $msg_type = "danger";
        } else {
            // API Call: Get Domain Organic Keywords (Ph=Keyword, Po=Position, Nq=Search Volume, Tr=Traffic %, Cp=CPC, Ur=URL)
            $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($domain) . "&export_columns=Ph,Po,Nq,Tr,Cp,Ur&database=" . urlencode($db_region) . "&display_limit=50";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response && strpos($response, 'ERROR') !== 0 && !empty(trim($response))) {
                $lines = explode("\n", trim($response));
                array_shift($lines); // Remove Headers
                
                $inserted = 0; $updated = 0;
                $current_date = date('Y-m-d');
                $db->beginTransaction();
                
                try {
                    $stmt = $db->prepare("INSERT INTO semrush_competitor_data (competitor_domain, keyword, position, search_volume, traffic_share, cpc, url, data_date) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                                          ON DUPLICATE KEY UPDATE position=?, search_volume=?, traffic_share=?, cpc=?, url=?");
                    
                    foreach($lines as $line) {
                        if (empty(trim($line))) continue;
                        $data = str_getcsv($line, ";");
                        if (count($data) < 6) continue;
                        
                        $kw = sanitize($data[0]);
                        $pos = (int)$data[1];
                        $vol = (int)$data[2];
                        $traffic = (float)$data[3];
                        $cpc = (float)$data[4];
                        $url = sanitize($data[5]);
                        
                        if(!empty($kw)) {
                            $check = $db->prepare("SELECT id FROM semrush_competitor_data WHERE competitor_domain = ? AND keyword = ? AND data_date = ?");
                            $check->execute([$domain, $kw, $current_date]);
                            if($check->fetchColumn()) $updated++; else $inserted++;

                            $stmt->execute([$domain, $kw, $pos, $vol, $traffic, $cpc, $url, $current_date, $pos, $vol, $traffic, $cpc, $url]);
                        }
                    }
                    $db->commit();
                    $message = "Spy Scan Complete on $domain! 🕵️‍♂️ Acquired $inserted new rival keywords, updated $updated metrics.";
                    $msg_type = "success";
                } catch (Exception $e) {
                    $db->rollBack();
                    $message = "Database Error during scan."; $msg_type = "danger";
                }
            } else {
                $message = "API Error: No organic ranking data found for '$domain' in region [".strtoupper($db_region)."].";
                $msg_type = "warning";
            }
        }
    } else {
        $message = "Invalid Competitor Domain!"; $msg_type = "danger";
    }
}

// --- 2. AUTO-STEAL ALL LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_steal_all'])) {
    $top_stolen_kws = $db->query("SELECT keyword FROM semrush_competitor_data ORDER BY traffic_share DESC, search_volume DESC LIMIT 15")->fetchAll(PDO::FETCH_COLUMN);
    if(!empty($top_stolen_kws)) {
        $pages = $db->query("SELECT id FROM site_seo")->fetchAll(PDO::FETCH_ASSOC);
        $updated_pages = 0;
        foreach($pages as $p) {
            if (smartInjectKeyword($db, $p['id'], $top_stolen_kws)) $updated_pages++;
        }
        if ($updated_pages > 0) {
            $message = "Auto-Steal Success! 🥷 Top 15 rival keywords injected into $updated_pages pages globally."; $msg_type = "success";
        } else {
            $message = "All Top 15 keywords are already injected into your pages!"; $msg_type = "info";
        }
    } else {
        $message = "Vault is empty. Please scan a competitor first."; $msg_type = "warning";
    }
}

// --- 3. DATA AGGREGATION ---
$total_rivals = 0; $total_stolen_kws = 0; $top_threat = 'None';
$rivals_list = []; $chart_domains = []; $chart_traffic = [];
$xray_keywords = [];

if($table_exists) {
    $total_rivals = $db->query("SELECT COUNT(DISTINCT competitor_domain) FROM semrush_competitor_data")->fetchColumn() ?: 0;
    $total_stolen_kws = $db->query("SELECT COUNT(*) FROM semrush_competitor_data")->fetchColumn() ?: 0;
    
    // Competitor Radar
    $radar_query = $db->query("
        SELECT competitor_domain, COUNT(keyword) as kw_count, SUM(traffic_share) as total_traffic_score 
        FROM semrush_competitor_data 
        GROUP BY competitor_domain 
        ORDER BY total_traffic_score DESC
    ");
    $rivals_list = $radar_query->fetchAll(PDO::FETCH_ASSOC);

    if(!empty($rivals_list)) {
        $top_threat = $rivals_list[0]['competitor_domain'];
        $limit = 0;
        foreach($rivals_list as $r) {
            if($limit++ >= 7) break;
            $chart_domains[] = $r['competitor_domain'];
            $chart_traffic[] = (float)$r['total_traffic_score'];
        }
    }

    // Top Keywords to Steal (Global)
    $xray_keywords = $db->query("
        SELECT competitor_domain, keyword, position, search_volume, traffic_share, url 
        FROM semrush_competitor_data 
        ORDER BY traffic_share DESC, search_volume DESC 
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Competitor Spy Engine";
if (file_exists('_header.php')) { include '_header.php'; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* ==============================================
       🔥 BEAST RESPONSIVE UI/UX DESIGN 🔥
       ============================================== */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --d-purple: #4f46e5;
        --b-color: #e2e8f0; --bg-color: #f8fafc;
        --t-dark: #0f172a; --t-muted: #64748b; 
        --c-danger: #ef4444; --c-warning: #f59e0b;
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    
    .beast-container { width: 100%; max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(99,102,241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99,102,241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); } }
    @keyframes pulseDanger { 0% { box-shadow: 0 0 0 0 rgba(239,68,68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239,68,68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68, 0); } }

    .anim-slide { animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    .spy-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); overflow: hidden; transition: 0.3s; }
    
    /* Metrics */
    .metric-box { padding: 1.5rem; text-align: center; border-right: 1px solid var(--b-color); display: flex; flex-direction: column; justify-content: center; }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 800; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .m-value { font-size: 2.2rem; font-weight: 900; color: var(--t-dark); line-height: 1; letter-spacing: -1px; }

    /* Forms & Inputs */
    .api-input { border: 2px solid var(--b-color); border-radius: 12px; padding: 12px 18px; font-weight: 700; color: var(--t-dark); outline: none; transition: 0.3s; width: 100%; max-width: 250px; }
    .api-input:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }
    
    .api-select-region { border: 2px solid var(--b-color); border-radius: 12px; padding: 12px; font-weight: 800; color: var(--t-dark); background: #fff; transition: 0.3s; outline: none; cursor: pointer; }
    .api-select-region:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    /* Buttons */
    .btn-spy { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; white-space: nowrap; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.3); }
    .btn-spy:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.4); color: #fff; }

    .btn-auto-steal { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 800; transition: 0.3s; animation: pulseDanger 2s infinite; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
    .btn-auto-steal:hover { transform: translateY(-2px); animation: none; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4); color: #fff; }

    .btn-steal-single { background: var(--l-purple); color: var(--d-purple); border: 2px solid var(--l-purple); padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; transition: 0.2s; cursor: pointer; white-space: nowrap; }
    .btn-steal-single:hover { background: var(--d-purple); border-color: var(--d-purple); color: #fff; transform: scale(1.05); }

    .btn-info-modal { background: #f8fafc; color: var(--t-dark); border: 2px solid var(--b-color); padding: 10px 20px; border-radius: 12px; font-weight: 800; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-info-modal:hover { background: var(--l-purple); border-color: var(--d-purple); color: var(--d-purple); }

    /* Tables & Badges */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; }
    .table th { background: #f8fafc; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: var(--t-muted); border-bottom: 2px solid var(--b-color); padding: 15px; white-space: nowrap; position: sticky; top: 0; z-index: 10; }
    .table td { padding: 15px; border-bottom: 1px solid var(--b-color); vertical-align: middle; white-space: nowrap; transition: 0.2s; }
    .table tr:hover td { background: #f8fafc; transform: scale(1.01); box-shadow: 0 4px 10px rgba(0,0,0,0.02); z-index: 2; position: relative; border-radius: 8px; }

    .badge-pos { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: var(--t-muted); font-weight: 900; font-size: 0.85rem; }
    .pos-top3 { background: #dcfce7; color: #166534; box-shadow: 0 2px 5px rgba(22, 101, 52, 0.2); }
    .pos-top10 { background: #fef3c7; color: #b45309; }
    
    .threat-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; }
    .threat-high { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .threat-med { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .table-url { font-size: 0.85rem; color: var(--t-muted); text-decoration: none; word-break: break-all; max-width: 250px; display: inline-block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; transition: 0.2s; }
    .table-url:hover { color: var(--p-purple); }

    /* Modal Styling */
    .glass-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 9999; display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s ease; }
    .glass-overlay.show { display: flex; opacity: 1; }
    .modal-beast { background: #fff; width: 90%; max-width: 600px; border-radius: 24px; padding: 35px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); transform: scale(0.9); transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; }
    .glass-overlay.show .modal-beast { transform: scale(1); }
    .close-modal { position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: #f1f5f9; border: none; border-radius: 50%; font-size: 1.2rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; color: var(--t-muted); }
    .close-modal:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }

    @media (max-width: 992px) {
        .metric-box { border-right: none; border-bottom: 1px solid var(--b-color); }
        .metric-box:last-child { border-bottom: none; }
    }
</style>

<div class="beast-container">
    
    <div class="spy-card p-4 p-md-5 mb-4 anim-slide d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div>
            <h2 class="fw-bolder text-dark mb-2" style="font-size: 2.2rem; letter-spacing: -1px;">
                <i class="fas fa-user-secret me-2 text-indigo-500"></i> Competitor Spy Engine
            </h2>
            <p class="text-muted fw-medium mb-0 fs-6">Target a rival domain via Live API. Expose and auto-steal their golden keywords.</p>
        </div>
        
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center w-100 w-lg-auto">
            <button class="btn-info-modal" onclick="toggleModal(true)">
                <i class="fas fa-info-circle"></i> How it works?
            </button>
            <form method="POST" id="spyForm" class="m-0 d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="api_run_spy" value="1">
                <input type="text" name="competitor_domain" class="api-input" placeholder="e.g. smmxyz.com" required>
                <select name="region" class="api-select-region">
                    <option value="us">🇺🇸 USA</option>
                    <option value="pk">🇵🇰 PK</option>
                    <option value="uk">🇬🇧 UK</option>
                    <option value="in">🇮🇳 IND</option>
                </select>
                <button type="button" class="btn-spy" id="fetchSpyBtn" onclick="runSpyApi()">
                    <i class="fas fa-crosshairs text-danger"></i> Target & Scan
                </button>
            </form>
        </div>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-4 shadow-sm mb-4 border-0 p-3 anim-slide d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'info' ? '#e0e7ff; color: #4338ca;' : '#fee2e2; color: #991b1b;') ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> fs-4 me-3"></i> 
            <span style="font-size: 1.05rem;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <div class="spy-card mb-4 anim-slide anim-delay-1">
        <div class="row g-0">
            <div class="col-md-4 metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-users-slash me-1"></i> Tracked Rivals</div>
                <div class="m-value"><?= number_format($total_rivals) ?></div>
            </div>
            <div class="col-md-4 metric-box">
                <div class="m-title text-success"><i class="fas fa-key me-1"></i> Total Keywords Stolen</div>
                <div class="m-value text-success"><?= number_format($total_stolen_kws) ?></div>
            </div>
            <div class="col-md-4 metric-box">
                <div class="m-title text-danger"><i class="fas fa-biohazard me-1"></i> Biggest Threat</div>
                <div class="m-value text-danger" style="font-size: 1.6rem; word-break: break-all;"><?= htmlspecialchars($top_threat) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 anim-slide anim-delay-2">
        <div class="col-lg-8">
            <div class="spy-card p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bolder mb-0 text-dark"><i class="fas fa-chart-pie me-2 text-muted"></i> Rival Market Share (Estimated Traffic)</h5>
                </div>
                <div class="flex-grow-1" style="min-height: 250px; width: 100%;">
                    <?php if(empty($chart_domains)): ?>
                        <div class="text-center py-5 text-muted d-flex flex-column justify-content-center h-100">
                            <i class="fas fa-satellite-dish fa-3x mb-3 opacity-25"></i>
                            <h5 class="fw-bold">No rivals detected yet.</h5>
                        </div>
                    <?php else: ?>
                        <canvas id="marketShareChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="spy-card h-100 overflow-hidden d-flex flex-column">
                <div class="p-4 bg-light border-bottom">
                    <h5 class="fw-bolder mb-0 text-dark"><i class="fas fa-crosshairs me-2 text-danger"></i> Threat Radar</h5>
                </div>
                <div class="table-responsive flex-grow-1" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                            <tr><th class="ps-4">Domain</th><th class="text-end pe-4">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($rivals_list)): ?><tr><td colspan="2" class="text-center py-4 text-muted">Radar is clear.</td></tr><?php endif; ?>
                            <?php foreach($rivals_list as $index => $rival): 
                                $threat_class = $index === 0 ? 'threat-high' : ($index < 3 ? 'threat-med' : 'bg-light text-muted border');
                                $threat_text = $index === 0 ? 'ALPHA' : 'ACTIVE';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bolder text-dark"><?= htmlspecialchars($rival['competitor_domain']) ?></div>
                                    <div class="small text-muted fw-bold"><i class="fas fa-key me-1"></i><?= number_format($rival['kw_count']) ?> Stolen</div>
                                </td>
                                <td class="text-end pe-4"><span class="threat-badge <?= $threat_class ?> shadow-sm"><?= $threat_text ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="spy-card overflow-hidden anim-slide anim-delay-2 d-flex flex-column" style="min-height: 500px;">
        <div class="p-4 border-bottom bg-light d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h4 class="fw-bolder mb-0 text-dark"><i class="fas fa-gem me-2" style="color: var(--p-purple);"></i> Golden Keywords Vault</h4>
            
            <div class="d-flex align-items-center gap-3">
                <form method="POST" id="autoStealForm" class="m-0">
                    <input type="hidden" name="auto_steal_all" value="1">
                    <button type="button" class="btn-auto-steal" onclick="if(confirm('Warning: This will steal the Top 15 competitor keywords and inject them into ALL your pages globally. Proceed?')) document.getElementById('autoStealForm').submit();">
                        <i class="fas fa-mask me-1"></i> Auto-Steal All
                    </button>
                </form>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="liveSearch" class="search-input" placeholder="Search keywords..." onkeyup="filterTable()">
                </div>
            </div>
        </div>
        
        <div class="table-responsive flex-grow-1" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover mb-0 align-middle" id="kwTable">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="ps-4">Golden Keyword</th>
                        <th>Target Rival</th>
                        <th class="text-center">Rival Rank</th>
                        <th class="text-end">Search Vol</th>
                        <th class="text-end">Traffic %</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($xray_keywords)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-4x mb-3 opacity-25"></i><br><h5 class="fw-bold text-dark">Vault is Empty</h5>Scan a rival to populate the vault.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($xray_keywords as $kw): 
                        $pos_class = $kw['position'] <= 3 ? 'pos-top3' : ($kw['position'] <= 10 ? 'pos-top10' : '');
                    ?>
                    <tr class="kw-row">
                        <td class="ps-4 py-3">
                            <div class="fw-bolder kw-text" style="color: var(--d-purple); font-size: 1.1rem;"><?= htmlspecialchars($kw['keyword']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bolder text-dark small"><?= htmlspecialchars($kw['competitor_domain']) ?></div>
                            <a href="<?= htmlspecialchars($kw['url']) ?>" target="_blank" class="table-url" title="<?= htmlspecialchars($kw['url']) ?>"><i class="fas fa-external-link-alt me-1" style="font-size:0.75rem;"></i><?= htmlspecialchars($kw['url']) ?></a>
                        </td>
                        <td class="text-center">
                            <span class="badge-pos <?= $pos_class ?> shadow-sm" title="Ranked #<?= $kw['position'] ?> on Google"><?= $kw['position'] ?></span>
                        </td>
                        <td class="text-end fw-bolder text-dark fs-6">
                            <?= number_format($kw['search_volume']) ?>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-light text-success border border-success px-2 py-1 shadow-sm"><i class="fas fa-fire me-1 text-danger"></i><?= number_format($kw['traffic_share'], 1) ?>%</span>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn-steal-single" onclick="stealKeyword(this, '<?= addslashes(htmlspecialchars($kw['keyword'])) ?>')">
                                <i class="fas fa-copy me-1"></i> Copy
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="glass-overlay" id="infoModal">
    <div class="modal-beast">
        <button class="close-modal" onclick="toggleModal(false)"><i class="fas fa-times"></i></button>
        <h3 class="fw-bolder text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i> How Competitor Spy Works?</h3>
        
        <div class="mb-4">
            <h5 class="fw-bold text-dark"><i class="fas fa-radar text-danger me-2"></i> 1. Target & Scan</h5>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">Enter a competitor's domain (e.g. <code>rivalpanel.com</code>) and select a region. The API fetches their top organic keywords directly from SEMrush database.</p>
        </div>
        
        <div class="mb-4">
            <h5 class="fw-bold text-dark"><i class="fas fa-chart-pie text-warning me-2"></i> 2. Analyze the Threat</h5>
            <p class="text-muted" style="font-size: 0.95rem; line-height: 1.6;">The radar automatically calculates which competitor holds the biggest market share based on "Estimated Traffic" driven by their keywords.</p>
        </div>
        
        <div>
            <h5 class="fw-bold text-dark"><i class="fas fa-mask text-success me-2"></i> 3. Steal & Inject (Auto-Pilot)</h5>
            <p class="text-muted mb-0" style="font-size: 0.95rem; line-height: 1.6;">Click <strong>Copy</strong> to copy a single golden keyword. Or, hit <strong class="text-danger">Auto-Steal All</strong> to instantly inject the top 15 traffic-driving competitor keywords into all your website pages globally!</p>
        </div>
    </div>
</div>

<script>
    // Modal Logic
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

    // Filter Table
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

    // API Spinner
    function runSpyApi() {
        const inp = document.querySelector('.api-input');
        if(inp.value.trim() === '') {
            alert('Please enter a competitor domain (e.g., website.com)');
            return;
        }
        const btn = document.getElementById('fetchSpyBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Infiltrating...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.9';
        document.getElementById('spyForm').submit();
    }

    // Copy Keyword
    function stealKeyword(button, keyword) {
        navigator.clipboard.writeText(keyword).then(function() {
            let originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.style.background = '#10b981';
            button.style.color = '#fff';
            button.style.borderColor = '#10b981';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style = ''; 
            }, 2000);
        });
    }

    <?php if(!empty($chart_domains)): ?>
    // Premium Bar Chart
    const ctx = document.getElementById('marketShareChart').getContext('2d');
    let gradBar = ctx.createLinearGradient(0, 0, 0, 400);
    gradBar.addColorStop(0, '#6366f1'); 
    gradBar.addColorStop(1, '#a855f7'); 

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_domains) ?>,
            datasets: [{
                label: 'Estimated Traffic Score',
                data: <?= json_encode($chart_traffic) ?>,
                backgroundColor: gradBar,
                borderRadius: 8,
                borderWidth: 0,
                barPercentage: 0.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', titleFont: {size: 14, family: 'Inter'}, bodyFont: {size: 16, weight: 'bold'}, padding: 12, displayColors: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9', drawBorder: false }, ticks: { color: '#64748b', font: {weight: 'bold'} } },
                x: { grid: { display: false, drawBorder: false }, ticks: { color: '#1e293b', font: {weight: 'bold'} } }
            }
        }
    });
    <?php endif; ?>
</script>

<?php require_once '_footer.php'; ?>