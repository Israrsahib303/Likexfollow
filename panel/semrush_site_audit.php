<?php
// File: panel/semrush_site_audit.php

// 1. HEADER ALWAYS GOES AT THE VERY TOP (Fixes the 500 Error completely)
if (file_exists('_header.php')) {
    require_once '_header.php';
} else {
    die("<b>CRITICAL ERROR:</b> _header.php file missing in the current directory!");
}

// Safe check for DB and Helpers just in case header missed them
if (!isset($db) && file_exists('../includes/db.php')) { require_once '../includes/db.php'; }
if (!function_exists('sanitize') && file_exists('../includes/helpers.php')) { require_once '../includes/helpers.php'; }

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE & AUTO-PATCH AUDIT HISTORY TABLE ---
try {
    if (isset($db) && is_object($db)) {
        // Create table if it doesn't exist at all
        $db->exec("CREATE TABLE IF NOT EXISTS semrush_site_audits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            domain_name VARCHAR(150),
            health_score INT DEFAULT 0,
            total_errors INT DEFAULT 0,
            total_warnings INT DEFAULT 0,
            total_notices INT DEFAULT 0,
            load_time DECIMAL(5,2) DEFAULT 0.00,
            audit_date DATE,
            UNIQUE KEY unique_audit (domain_name, audit_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        // BUG FIX: Auto-Patch for existing tables missing the new columns!
        try { $db->exec("ALTER TABLE semrush_site_audits ADD COLUMN audit_date DATE;"); } catch (Throwable $e) {}
        try { $db->exec("ALTER TABLE semrush_site_audits ADD UNIQUE KEY unique_audit (domain_name, audit_date);"); } catch (Throwable $e) {}

        $table_exists = true;
    } else {
        $table_exists = false;
        $message = "Database Connection Missing! Please check _header.php or db.php";
        $msg_type = "danger";
    }
} catch (Throwable $e) { 
    $table_exists = false;
    $message = "DB Schema Error: " . $e->getMessage();
    $msg_type = "danger";
}

// SAFE FUNCTION DECLARATIONS
if(!function_exists('sanitize')){ 
    function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } 
}

if(!function_exists('extractDomain')){
    function extractDomain($url) {
        if(empty($url)) return 'Unknown';
        $host = parse_url('http://' . preg_replace('#^https?://#', '', $url), PHP_URL_HOST);
        return str_ireplace('www.', '', $host ?? 'Unknown');
    }
}

// ==========================================
// 📡 1. DEEP STEALTH CRAWLER ENGINE (CRASH-PROOF)
// ==========================================
$latest_audit = null;
$audit_details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_audit'])) {
    $raw_target = sanitize($_POST['target_domain']);
    $url = (strpos($raw_target, 'http') === false) ? "https://" . $raw_target : $raw_target;
    $domain = extractDomain($url);
    $current_date = date('Y-m-d');

    if (empty($domain) || $domain === 'Unknown') {
        $message = "Invalid Target Domain!"; $msg_type = "danger";
    } else {
        $start_time = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_ENCODING, ""); 
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: en-US,en;q=0.5",
            "Cache-Control: no-cache"
        ]);
        
        $html = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $end_time = microtime(true);
        curl_close($ch);

        $load_time = round(($end_time - $start_time), 2);

        if ($html === false || $http_code === 0) {
            $message = "Crawler Blocked: Server firewall or Cloudflare is rejecting the bot. Error: " . $curl_error;
            $msg_type = "danger";
        } elseif ($http_code >= 400) {
            $message = "Critical Error: Server responded with HTTP Code $http_code.";
            $msg_type = "danger";
        } else {
            $errors = 0; $warnings = 0; $notices = 0;
            $health_score = 100;
            
            // 1. SSL Check
            if (strpos($url, 'https://') === false) {
                $errors++; $health_score -= 10;
                $audit_details['errors'][] = "No SSL Detected. HTTPS is critical for ranking.";
            }

            // CRASH PROOF PARSING
            if (class_exists('DOMDocument')) {
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                $safe_html = function_exists('mb_convert_encoding') ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : $html;
                @$dom->loadHTML($safe_html);
                
                // Title
                $titles = $dom->getElementsByTagName('title');
                $title_text = $titles->length > 0 ? trim($titles->item(0)->nodeValue) : '';
                
                // Meta Desc
                $metas = $dom->getElementsByTagName('meta');
                $desc_len = 0;
                foreach ($metas as $meta) {
                    if (strtolower($meta->getAttribute('name')) === 'description') {
                        $desc_len = strlen(trim($meta->getAttribute('content')));
                    }
                }
                
                // H1
                $h1s = $dom->getElementsByTagName('h1')->length;
                
                // Imgs
                $imgs = $dom->getElementsByTagName('img');
                $img_missing_alt = 0;
                foreach ($imgs as $img) {
                    if (empty(trim($img->getAttribute('alt')))) $img_missing_alt++;
                }
                
                // Links
                $links_count = $dom->getElementsByTagName('a')->length;
                
                libxml_clear_errors();
            } else {
                // REGEX FALLBACK (If DOM is disabled on hosting)
                preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $title_matches);
                $title_text = isset($title_matches[1]) ? trim(strip_tags($title_matches[1])) : '';
                
                preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/is', $html, $desc_matches);
                $desc_len = isset($desc_matches[1]) ? strlen(trim($desc_matches[1])) : 0;
                
                $h1s = preg_match_all('/<h1[^>]*>.*?<\/h1>/is', $html);
                $img_missing_alt = 0; 
                $links_count = preg_match_all('/<a\s+[^>]*href=["\'][^"\']+["\']/is', $html);
            }

            // Analyze Data
            if (empty($title_text)) {
                $errors++; $health_score -= 10;
                $audit_details['errors'][] = "Missing Title Tag.";
            } else {
                $title_len = strlen($title_text);
                if ($title_len < 30 || $title_len > 60) {
                    $warnings++; $health_score -= 3;
                    $audit_details['warnings'][] = "Title length ($title_len chars) is not optimal (Aim for 30-60).";
                }
            }

            if ($desc_len == 0) {
                $errors++; $health_score -= 10;
                $audit_details['errors'][] = "Missing Meta Description. Huge missed SEO opportunity.";
            } elseif ($desc_len < 70 || $desc_len > 160) {
                $warnings++; $health_score -= 3;
                $audit_details['warnings'][] = "Meta description length ($desc_len chars) is sub-optimal.";
            }

            if ($h1s === 0) {
                $errors++; $health_score -= 10;
                $audit_details['errors'][] = "No H1 tag found. Google needs an H1 to understand the page.";
            } elseif ($h1s > 1) {
                $warnings++; $health_score -= 5;
                $audit_details['warnings'][] = "Multiple H1 tags found ($h1s). Use only one per page.";
            }

            if (isset($img_missing_alt) && $img_missing_alt > 0) {
                $warnings++; $health_score -= (min(10, $img_missing_alt * 1));
                $audit_details['warnings'][] = "$img_missing_alt images are missing 'alt' attributes.";
            }

            if ($load_time > 3.0) {
                $errors++; $health_score -= 10;
                $audit_details['errors'][] = "Page load time is critically slow ({$load_time}s). Aim for under 2s.";
            } elseif ($load_time > 1.5) {
                $notices++; $health_score -= 2;
                $audit_details['notices'][] = "Page load time is okay ({$load_time}s), but can be optimized.";
            }

            if ($links_count === 0) {
                $warnings++; $health_score -= 5;
                $audit_details['warnings'][] = "No internal/external links found on page.";
            }

            $health_score = max(0, $health_score);

            // Save to Database
            if($table_exists && isset($db) && is_object($db)) {
                try {
                    $stmt = $db->prepare("INSERT INTO semrush_site_audits (domain_name, health_score, total_errors, total_warnings, total_notices, load_time, audit_date) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?) 
                                          ON DUPLICATE KEY UPDATE health_score=?, total_errors=?, total_warnings=?, total_notices=?, load_time=?");
                    
                    $stmt->execute([$domain, $health_score, $errors, $warnings, $notices, $load_time, $current_date,
                                    $health_score, $errors, $warnings, $notices, $load_time]);
                } catch (Throwable $e) {} 
            }
            
            $message = "Deep Scan Completed for <b>$domain</b>!";
            $msg_type = "success";
            
            $latest_audit = [
                'domain' => $domain, 'score' => $health_score, 'load' => $load_time,
                'e' => $errors, 'w' => $warnings, 'n' => $notices
            ];
        }
    }
}

// --- 2. FETCH HISTORY FOR GROWTH CHART ---
$chart_dates = [];
$chart_scores = [];
$recent_scans = [];

if($table_exists && isset($db) && is_object($db)) {
    $default_domain = isset($_GET['domain']) ? sanitize($_GET['domain']) : 'likexfollow.com';
    
    // Wrapped in Try-Catch so even if query fails, page won't crash
    try {
        $stmt = $db->prepare("SELECT audit_date, health_score FROM semrush_site_audits WHERE domain_name = ? ORDER BY audit_date ASC LIMIT 14");
        $stmt->execute([$default_domain]);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['audit_date'])) {
                $chart_dates[] = date('d M', strtotime($row['audit_date']));
                $chart_scores[] = (int)$row['health_score'];
            }
        }

        $recent_scans = $db->query("SELECT * FROM semrush_site_audits ORDER BY audit_date DESC, id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // Silently handle any SQL fetch errors
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ==============================================
       🔥 BEAST RESPONSIVE UI/UX DESIGN 🔥
       ============================================== */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --d-purple: #4f46e5;
        --bg-body: #f8fafc; --b-color: #e2e8f0; 
        --t-dark: #0f172a; --t-muted: #64748b; 
        --c-error: #ef4444; --c-warn: #f59e0b; --c-notice: #3b82f6; --c-success: #10b981;
    }
    
    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; overflow-x: hidden; margin: 0; padding: 0; }
    
    .beast-container { 
        width: 100%; 
        max-width: 1500px; 
        margin: 0 auto; 
        padding: 15px; 
        overflow-x: hidden; 
        box-sizing: border-box; 
    }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .anim-slide { animation: slideInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    .audit-card { 
        background: #fff; 
        border-radius: 20px; 
        border: 1px solid var(--b-color); 
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); 
        overflow: hidden; 
        transition: 0.3s; 
        width: 100%; 
    }
    
    .scan-input-group { display: flex; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden; border: 2px solid var(--b-color); transition: 0.3s; width: 100%; }
    .scan-input-group:focus-within { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }
    .scan-input { flex-grow: 1; border: none; padding: 15px 20px; font-weight: 700; color: var(--t-dark); outline: none; font-size: 1.1rem; width: 100%; min-width: 0; }
    .btn-scan { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; border: none; padding: 0 30px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: 0.3s; white-space: nowrap; }
    .btn-scan:hover { background: var(--p-purple); }

    .health-dial { position: relative; width: 180px; height: 180px; margin: 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 50%; box-shadow: inset 0 0 20px rgba(0,0,0,0.05); }
    .health-dial::before { content: ''; position: absolute; inset: 15px; border-radius: 50%; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1; }
    .health-score-val { position: relative; z-index: 2; font-size: 3.5rem; font-weight: 900; color: var(--t-dark); letter-spacing: -2px; }
    .health-score-val span { font-size: 1.5rem; color: var(--t-muted); font-weight: 700; margin-left: -5px; }
    
    .dial-green { background: conic-gradient(var(--c-success) var(--score-deg), var(--b-color) 0deg); }
    .dial-yellow { background: conic-gradient(var(--c-warn) var(--score-deg), var(--b-color) 0deg); }
    .dial-red { background: conic-gradient(var(--c-error) var(--score-deg), var(--b-color) 0deg); }

    .metric-grid { display: flex; flex-wrap: wrap; gap: 15px; }
    .m-box { flex: 1 1 30%; padding: 20px 10px; border-radius: 16px; text-align: center; border: 1px solid var(--b-color); transition: 0.3s; }
    .m-err { background: #fef2f2; border-color: #fecaca; } .m-err h3 { color: var(--c-error); }
    .m-warn { background: #fffbeb; border-color: #fde68a; } .m-warn h3 { color: var(--c-warn); }
    .m-not { background: #eff6ff; border-color: #bfdbfe; } .m-not h3 { color: var(--c-notice); }
    .m-box h3 { font-size: 2.2rem; font-weight: 900; margin: 0 0 5px 0; line-height: 1; }
    .m-box p { font-size: 0.8rem; font-weight: 800; color: var(--t-dark); text-transform: uppercase; margin: 0; }

    .issue-list-wrapper { max-height: 250px; overflow-y: auto; border-radius: 12px; border: 1px solid var(--b-color); overflow-x: hidden; }
    .issue-item { padding: 15px; border-bottom: 1px solid var(--b-color); display: flex; align-items: flex-start; gap: 12px; background: #fff; word-break: break-word; }
    .issue-item:last-child { border-bottom: none; }
    .i-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; color: #fff; }
    .i-err { background: var(--c-error); box-shadow: 0 4px 10px rgba(239,68,68,0.3); }
    .i-warn { background: var(--c-warn); box-shadow: 0 4px 10px rgba(245,158,11,0.3); }
    .i-not { background: var(--c-notice); box-shadow: 0 4px 10px rgba(59,130,246,0.3); }
    .i-text { font-weight: 600; color: var(--t-dark); font-size: 0.9rem; line-height: 1.5; margin-top: 3px; }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .table th { background: #f8fafc; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: var(--t-muted); white-space: nowrap; }
    .table td { vertical-align: middle; font-weight: 600; color: var(--t-dark); word-break: break-word; }
    .badge-score { padding: 6px 12px; border-radius: 8px; font-weight: 800; }

    @media (max-width: 768px) {
        .scan-input-group { flex-direction: column; border-radius: 12px; border: none; box-shadow: none; gap: 10px; }
        .scan-input { border: 2px solid var(--b-color); border-radius: 10px; padding: 15px; }
        .btn-scan { padding: 15px; border-radius: 10px; width: 100%; }
        .header-content-wrapper { flex-direction: column; width: 100%; text-align: center; }
        .header-content-wrapper p { text-align: center !important; }
        .m-box { flex: 1 1 100%; } 
        .health-dial { width: 150px; height: 150px; }
        .health-score-val { font-size: 2.8rem; }
    }
</style>

<div class="beast-container">
    
    <div class="audit-card p-4 p-md-5 mb-4 anim-slide d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div class="header-content-wrapper w-100 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="w-100 w-lg-auto mb-2 mb-lg-0">
                <h2 class="fw-bolder text-dark mb-2" style="font-size: 2rem; letter-spacing: -1px;">
                    <i class="fas fa-stethoscope me-2 text-indigo-500"></i> Site Audit Engine
                </h2>
                <p class="text-muted fw-medium mb-0 fs-6 text-start">Stealth deep-scan to uncover hidden technical SEO blocks.</p>
            </div>
            
            <form method="POST" class="m-0 w-100 w-lg-auto flex-grow-1" style="max-width: 500px;" onsubmit="showSpinner()">
                <input type="hidden" name="run_audit" value="1">
                <div class="scan-input-group">
                    <input type="text" name="target_domain" class="scan-input" placeholder="Enter domain (e.g. likexfollow.com)" value="<?= isset($_POST['target_domain']) ? sanitize($_POST['target_domain']) : 'likexfollow.com' ?>" required>
                    <button type="submit" class="btn-scan" id="auditBtn"><i class="fas fa-radar me-2"></i> Scan</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($message): ?> 
        <div class="alert fw-bold rounded-4 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>; word-break: break-word;">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 me-3 flex-shrink-0"></i> 
            <span style="font-size: 0.95rem; line-height: 1.5;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <?php if($latest_audit): 
        $score = $latest_audit['score'];
        $deg = ($score / 100) * 360;
        $dial_class = $score >= 80 ? 'dial-green' : ($score >= 50 ? 'dial-yellow' : 'dial-red');
    ?>
    <div class="row g-4 mb-4 anim-slide anim-delay-1">
        <div class="col-lg-4">
            <div class="audit-card p-4 h-100 text-center d-flex flex-column justify-content-center">
                <h6 class="fw-bolder text-uppercase text-muted mb-4">Site Health Score</h6>
                <div class="health-dial <?= $dial_class ?>" style="--score-deg: <?= $deg ?>deg;">
                    <div class="health-score-val"><?= $score ?><span>%</span></div>
                </div>
                <div class="mt-4">
                    <span class="badge bg-light text-dark border fs-6 px-4 py-2 shadow-sm">
                        Load Time: <?= $latest_audit['load'] ?>s
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="audit-card p-4 h-100 d-flex flex-column">
                <h6 class="fw-bolder text-uppercase text-dark mb-4 border-bottom pb-3"><i class="fas fa-bug text-danger me-2"></i> Technical Breakdown</h6>
                <div class="metric-grid mb-4">
                    <div class="m-box m-err">
                        <h3><?= $latest_audit['e'] ?></h3>
                        <p>Errors</p>
                    </div>
                    <div class="m-box m-warn">
                        <h3><?= $latest_audit['w'] ?></h3>
                        <p>Warnings</p>
                    </div>
                    <div class="m-box m-not">
                        <h3><?= $latest_audit['n'] ?></h3>
                        <p>Notices</p>
                    </div>
                </div>
                
                <div class="flex-grow-1 issue-list-wrapper">
                    <?php if($latest_audit['e'] == 0 && $latest_audit['w'] == 0): ?>
                        <div class="p-4 text-center text-success fw-bold"><i class="fas fa-check-circle fa-2x mb-2"></i><br>Perfect! No issues found.</div>
                    <?php else: ?>
                        <?php if(isset($audit_details['errors'])): foreach($audit_details['errors'] as $err): ?>
                            <div class="issue-item">
                                <div class="i-icon i-err"><i class="fas fa-times"></i></div>
                                <div class="i-text"><?= $err ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                        
                        <?php if(isset($audit_details['warnings'])): foreach($audit_details['warnings'] as $warn): ?>
                            <div class="issue-item">
                                <div class="i-icon i-warn"><i class="fas fa-exclamation"></i></div>
                                <div class="i-text"><?= $warn ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                        
                        <?php if(isset($audit_details['notices'])): foreach($audit_details['notices'] as $not): ?>
                            <div class="issue-item">
                                <div class="i-icon i-not"><i class="fas fa-info"></i></div>
                                <div class="i-text"><?= $not ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 anim-slide anim-delay-2">
        <div class="col-lg-7">
            <div class="audit-card p-4 h-100 w-100 overflow-hidden">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bolder m-0 text-dark" style="font-size: 1.1rem;"><i class="fas fa-chart-line text-success me-2"></i> Health Trend</h5>
                </div>
                <?php if(count($chart_dates) < 2): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-chart-area fa-3x mb-3 opacity-25"></i>
                        <h6>Need at least 2 scans to generate a trend chart.</h6>
                    </div>
                <?php else: ?>
                    <div style="height: 280px; width: 100%; position: relative;">
                        <canvas id="growthChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="audit-card h-100 d-flex flex-column w-100 overflow-hidden">
                <div class="p-3 bg-light border-bottom">
                    <h5 class="fw-bolder m-0 text-dark" style="font-size: 1.1rem;"><i class="fas fa-history text-primary me-2"></i> Recent Scans</h5>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover mb-0 w-100">
                        <thead>
                            <tr>
                                <th class="ps-3">Domain</th>
                                <th>Date</th>
                                <th class="text-center pe-3">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_scans)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No history recorded yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach($recent_scans as $row): 
                                $s_color = $row['health_score'] >= 80 ? 'bg-success' : ($row['health_score'] >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                $show_date = isset($row['audit_date']) ? date('d M, Y', strtotime($row['audit_date'])) : 'N/A';
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-primary" style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($row['domain_name']) ?></td>
                                <td class="text-muted small" style="white-space: nowrap;"><?= $show_date ?></td>
                                <td class="text-center pe-3"><span class="badge-score <?= $s_color ?> text-white"><?= $row['health_score'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function showSpinner() {
        const btn = document.getElementById('auditBtn');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Scanning...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.9';
    }

    <?php if(count($chart_dates) >= 2): ?>
    const ctx = document.getElementById('growthChart').getContext('2d');
    
    let gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)'); 
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_dates) ?>,
            datasets: [{
                label: 'Site Health Score',
                data: <?= json_encode($chart_scores) ?>,
                borderColor: '#10b981',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#10b981',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', titleFont: {size: 13}, bodyFont: {size: 14, weight: 'bold'}, padding: 12, displayColors: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: {weight: '600'}, color: '#64748b' } },
                y: { 
                    min: 0, max: 100, 
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { font: {weight: 'bold'}, color: '#1e293b', stepSize: 20 }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<?php 
if (file_exists('_footer.php')) {
    require_once '_footer.php'; 
}
?>
