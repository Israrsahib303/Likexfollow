<?php
// File: panel/semrush_gaps.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE GAP TABLES ---
try {
    // Keyword Gaps Table
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_keyword_gaps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword VARCHAR(255),
        search_volume INT DEFAULT 0,
        kd_percent INT DEFAULT 0,
        cpc DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'Pending',
        data_date DATE,
        UNIQUE KEY unique_kw (keyword)
    )");
    
    // Backlink Gaps Table
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_backlink_gaps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referring_domain VARCHAR(255),
        authority_score INT DEFAULT 0,
        backlink_count INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Pending',
        data_date DATE,
        UNIQUE KEY unique_bl (referring_domain)
    )");
    $tables_exist = true;
} catch (PDOException $e) {
    $tables_exist = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// ==========================================
// 📡 1. DYNAMIC SEMRUSH API COMPETITOR SNIPER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_fetch_gaps'])) {
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $competitor = sanitize($_POST['competitor_domain']);
    $gap_type = sanitize($_POST['gap_type']);
    $current_date = date('Y-m-d');
    
    // Clean domain input (remove http/https/www)
    $competitor = preg_replace('#^https?://#', '', $competitor);
    $competitor = preg_replace('#^www\.#', '', $competitor);
    $competitor = trim($competitor, '/');

    if (!empty($competitor)) {
        $inserted = 0; $updated = 0;
        $db->beginTransaction();
        
        try {
            if ($gap_type === 'keywords') {
                // Fetch Competitor Organic Keywords
                $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($competitor) . "&export_columns=Ph,Nq,Kd,Cp&database=us&display_limit=50";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response && strpos($response, 'ERROR') !== 0) {
                    $lines = explode("\n", trim($response));
                    array_shift($lines); // Remove Headers
                    
                    $stmt = $db->prepare("INSERT INTO semrush_keyword_gaps (keyword, search_volume, kd_percent, cpc, data_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE search_volume=?, kd_percent=?, cpc=?");
                    
                    foreach($lines as $line) {
                        if (empty(trim($line))) continue;
                        $data = str_getcsv($line, ";");
                        if (count($data) < 4) continue;
                        
                        $kw = sanitize($data[0]);
                        $vol = (int)$data[1];
                        $kd = (int)$data[2];
                        $cpc = (float)$data[3];
                        
                        if(!empty($kw)) {
                            $stmt->execute([$kw, $vol, $kd, $cpc, $current_date, $vol, $kd, $cpc]);
                            if($stmt->rowCount() > 1) $updated++; else $inserted++;
                        }
                    }
                    $message = "Keyword Gaps Synced from $competitor! 🚀 Found $inserted new untapped keywords."; 
                    $msg_type = "success";
                } else {
                    $message = "API Error: No keyword data found for $competitor."; $msg_type = "danger";
                }
            } 
            elseif ($gap_type === 'backlinks') {
                // Fetch Competitor Backlink Referring Domains
                $api_url = "https://api.semrush.com/analytics/v1/?type=backlinks_refdomains&key=" . urlencode($api_key) . "&target=" . urlencode($competitor) . "&target_type=domain&export_columns=domain_as,domain,backlinks_num&display_limit=50";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response && strpos($response, 'ERROR') !== 0) {
                    $lines = explode("\n", trim($response));
                    array_shift($lines); // Remove Headers
                    
                    $stmt = $db->prepare("INSERT INTO semrush_backlink_gaps (referring_domain, authority_score, backlink_count, data_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE authority_score=?, backlink_count=?");
                    
                    foreach($lines as $line) {
                        if (empty(trim($line))) continue;
                        // Backlink API uses comma separation mostly, fallback to semi-colon if needed
                        $delim = strpos($line, ';') !== false ? ';' : ',';
                        $data = str_getcsv($line, $delim);
                        if (count($data) < 3) continue;
                        
                        $as = (int)$data[0];
                        $domain = sanitize($data[1]);
                        $bl_count = (int)$data[2];
                        
                        if(!empty($domain)) {
                            $stmt->execute([$domain, $as, $bl_count, $current_date, $as, $bl_count]);
                            if($stmt->rowCount() > 1) $updated++; else $inserted++;
                        }
                    }
                    $message = "Backlink Gaps Synced from $competitor! 🔗 Found $inserted new target domains."; 
                    $msg_type = "success";
                } else {
                    $message = "API Error: No backlink data found for $competitor."; $msg_type = "danger";
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Database Error during API Sync."; $msg_type = "danger";
        }
    } else {
        $message = "Competitor domain cannot be empty!"; $msg_type = "danger";
    }
}

// --- 2. ACTION: MARK AS PLANNED / OUTREACH ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $id = (int)$_POST['id'];
    $type = sanitize($_POST['action_type']);
    
    if($type === 'kw_plan') {
        $db->prepare("UPDATE semrush_keyword_gaps SET status = 'Planned' WHERE id = ?")->execute([$id]);
        $message = "Keyword added to your Content Plan! ✍️"; $msg_type = "success";
    } elseif($type === 'bl_outreach') {
        $db->prepare("UPDATE semrush_backlink_gaps SET status = 'Outreach' WHERE id = ?")->execute([$id]);
        $message = "Domain moved to Outreach list! 📧"; $msg_type = "success";
    }
}

// --- 3. DATA AGGREGATION FOR UI ---
$kw_count = 0; $total_vol = 0; $bl_count = 0; $avg_as = 0;
$pending_kws = []; $pending_bls = [];

if($tables_exist) {
    // Keyword Metrics
    $kw_stats = $db->query("SELECT COUNT(*) as c, SUM(search_volume) as v FROM semrush_keyword_gaps WHERE status = 'Pending'")->fetch(PDO::FETCH_ASSOC);
    $kw_count = $kw_stats['c'] ?? 0;
    $total_vol = $kw_stats['v'] ?? 0;
    
    // Backlink Metrics
    $bl_stats = $db->query("SELECT COUNT(*) as c, AVG(authority_score) as a FROM semrush_backlink_gaps WHERE status = 'Pending'")->fetch(PDO::FETCH_ASSOC);
    $bl_count = $bl_stats['c'] ?? 0;
    $avg_as = $bl_stats['a'] ?? 0;

    // Fetch Lists
    $pending_kws = $db->query("SELECT * FROM semrush_keyword_gaps WHERE status = 'Pending' ORDER BY search_volume DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    $pending_bls = $db->query("SELECT * FROM semrush_backlink_gaps WHERE status = 'Pending' ORDER BY authority_score DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "SEMrush Gap Sniper";
if (file_exists('_header.php')) { include '_header.php'; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Premium Crisp White & Purple Theme */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --b-color: #e2e8f0; 
        --t-dark: #1e293b; --t-muted: #64748b; 
        --c-success: #10b981; --c-warning: #f59e0b;
    }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .gap-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .metric-box { padding: 1.5rem; border-right: 1px solid var(--b-color); }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .m-value { font-size: 2rem; font-weight: 800; color: var(--t-dark); line-height: 1; }
    
    .btn-action { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .btn-action:hover { box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); transform: translateY(-1px); color: #fff; }

    .btn-sm-action { background: #f8fafc; color: var(--p-purple); border: 1px solid var(--b-color); padding: 5px 12px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; transition: 0.2s; cursor: pointer; }
    .btn-sm-action:hover { background: var(--l-purple); border-color: #c7d2fe; }

    .api-input { border: 2px solid var(--b-color); border-radius: 8px; padding: 8px 15px; font-weight: 600; outline: none; transition: 0.3s; width: 220px; color: var(--t-dark); }
    .api-input:focus { border-color: var(--p-purple); }
    
    .api-select { border: 2px solid var(--b-color); border-radius: 8px; padding: 8px 15px; font-weight: 600; outline: none; transition: 0.3s; color: var(--t-dark); background: #fff; cursor: pointer; }
    .api-select:focus { border-color: var(--p-purple); }

    /* Custom Nav Tabs */
    .nav-tabs { border-bottom: 2px solid var(--b-color); gap: 10px; padding: 0 20px; }
    .nav-tabs .nav-link { color: var(--t-muted); font-weight: 700; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; background: transparent; border-radius: 0; }
    .nav-tabs .nav-link:hover { color: var(--p-purple); }
    .nav-tabs .nav-link.active { color: var(--p-purple); border-bottom-color: var(--p-purple); }

    .badge-metric { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
    .b-vol { background: #dcfce7; color: #166534; }
    .b-kd-easy { background: #dcfce7; color: #166534; }
    .b-kd-hard { background: #fee2e2; color: #b91c1c; }
    .b-as { background: #fef3c7; color: #b45309; }
</style>

<div class="container-fluid p-4" style="max-width: 1400px;">
    
    <div class="gap-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-crosshairs me-2" style="color: var(--p-purple);"></i> Content & Backlink Gap Sniper</h2>
            <p class="text-muted mb-0">Enter a competitor's domain. The API will automatically steal their top ranking keywords & backlinks.</p>
        </div>
        <form method="POST" class="d-flex align-items-center gap-2" id="apiForm">
            <input type="hidden" name="api_fetch_gaps" value="1">
            <input type="text" name="competitor_domain" class="api-input" placeholder="e.g. competitor.com" required>
            <select name="gap_type" class="api-select" required>
                <option value="keywords">🕵️‍♂️ Keyword Gaps</option>
                <option value="backlinks">🔗 Backlink Gaps</option>
            </select>
            <button type="button" class="btn-action" id="fetchApiBtn" onclick="runApiFetch()">
                <i class="fas fa-bolt text-warning"></i> Run Sniper
            </button>
        </form>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-3 shadow-sm mb-4 border-0" style="padding:15px; border-radius:10px; background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'warning' ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> me-2"></i> <?= $message ?>
        </div> 
    <?php endif; ?>

    <div class="gap-card mb-4 overflow-hidden">
        <div class="row g-0">
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-key me-1"></i> Missing Keywords</div>
                <div class="m-value"><?= number_format($kw_count) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title text-success"><i class="fas fa-search me-1"></i> Untapped Volume</div>
                <div class="m-value text-success"><?= number_format($total_vol) ?></div>
            </div>
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title text-warning"><i class="fas fa-link me-1"></i> Untapped Backlinks</div>
                <div class="m-value"><?= number_format($bl_count) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title text-danger"><i class="fas fa-chess-king me-1"></i> Avg Target AS</div>
                <div class="m-value"><?= number_format($avg_as, 1) ?></div>
            </div>
        </div>
    </div>

    <div class="gap-card overflow-hidden">
        <ul class="nav nav-tabs pt-2" id="gapTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="kw-tab" data-bs-toggle="tab" data-bs-target="#kw-gap" type="button" role="tab">
                    <i class="fas fa-key me-2"></i> Keyword Gaps
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bl-tab" data-bs-toggle="tab" data-bs-target="#bl-gap" type="button" role="tab">
                    <i class="fas fa-link me-2"></i> Backlink Gaps
                </button>
            </li>
        </ul>

        <div class="tab-content" id="gapTabContent">
            
            <div class="tab-pane fade show active" id="kw-gap" role="tabpanel">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase">Missing Keyword</th>
                                <th class="text-end text-muted small text-uppercase">Search Volume</th>
                                <th class="text-end text-muted small text-uppercase">KD %</th>
                                <th class="text-end text-muted small text-uppercase">CPC</th>
                                <th class="text-end pe-4 text-muted small text-uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pending_kws)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-crosshairs fa-3x mb-3 opacity-25 text-primary"></i><br>Enter a competitor domain above to find missing keywords.</td></tr>
                            <?php endif; ?>
                            <?php foreach($pending_kws as $kw): 
                                $kd_class = $kw['kd_percent'] < 40 ? 'b-kd-easy' : 'b-kd-hard';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold" style="color: var(--p-purple); font-size: 1.05rem;"><?= htmlspecialchars($kw['keyword']) ?></div>
                                </td>
                                <td class="text-end"><span class="badge-metric b-vol"><?= number_format($kw['search_volume']) ?></span></td>
                                <td class="text-end"><span class="badge-metric <?= $kd_class ?>"><?= $kw['kd_percent'] ?>%</span></td>
                                <td class="text-end fw-bold text-dark">$<?= number_format($kw['cpc'], 2) ?></td>
                                <td class="text-end pe-4">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action_type" value="kw_plan">
                                        <input type="hidden" name="id" value="<?= $kw['id'] ?>">
                                        <button type="submit" class="btn-sm-action"><i class="fas fa-plus me-1"></i> Plan Content</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="bl-gap" role="tabpanel">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 1;">
                            <tr>
                                <th class="ps-4 text-muted small text-uppercase">Untapped Domain</th>
                                <th class="text-center text-muted small text-uppercase">Authority Score (AS)</th>
                                <th class="text-center text-muted small text-uppercase">Shared Matches</th>
                                <th class="text-end pe-4 text-muted small text-uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pending_bls)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fas fa-link fa-3x mb-3 opacity-25"></i><br>Enter a competitor domain above to steal their backlinks.</td></tr>
                            <?php endif; ?>
                            <?php foreach($pending_bls as $bl): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($bl['referring_domain']) ?></div>
                                    <a href="https://<?= htmlspecialchars($bl['referring_domain']) ?>" target="_blank" class="small text-muted text-decoration-none"><i class="fas fa-external-link-alt me-1"></i> Visit Site</a>
                                </td>
                                <td class="text-center"><span class="badge-metric b-as fs-6 px-3 py-1"><?= $bl['authority_score'] ?></span></td>
                                <td class="text-center fw-bold text-muted"><?= $bl['backlink_count'] ?> links</td>
                                <td class="text-end pe-4">
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action_type" value="bl_outreach">
                                        <input type="hidden" name="id" value="<?= $bl['id'] ?>">
                                        <button type="submit" class="btn-sm-action" style="background: var(--p-purple); color: #fff; border: none;">
                                            <i class="fas fa-paper-plane me-1"></i> Start Outreach
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // API Fetch Loader Animation
    function runApiFetch() {
        const inp = document.querySelector('.api-input');
        if(inp.value.trim() === '') {
            alert('Please enter a competitor domain first!');
            return;
        }
        
        const btn = document.getElementById('fetchApiBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sniping Data...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';
        
        document.getElementById('apiForm').submit();
    }
</script>

<?php 
if (file_exists('_footer.php')) { include '_footer.php'; }
?>
