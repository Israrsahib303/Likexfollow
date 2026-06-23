<?php
// File: panel/semrush_backlink_audit.php

// --- 0. ADVANCED DISAVOW EXPORT ENGINE (Must be before any HTML) ---
// If the user clicks "Export Disavow File", we intercept the request and force a .txt file download.
if (isset($_POST['export_disavow_file'])) {
    require_once 'includes/db.php'; // Or however you connect to DB in standalone scripts
    // Fallback if db is not loaded
    if(!isset($db)) {
        require_once '_header.php'; 
    }
    
    $stmt = $db->query("SELECT source_url FROM semrush_toxic_links WHERE status = 'Disavowed'");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $content = "# LikexFollow Disavow File\n# Exported from Advanced SEO Panel on " . date('Y-m-d H:i') . "\n# Total domains to disavow: " . count($links) . "\n\n";
    
    $unique_domains = [];
    foreach($links as $l) {
        $host = parse_url($l['source_url'], PHP_URL_HOST);
        $domain = str_ireplace('www.', '', $host);
        if(!empty($domain) && !in_array($domain, $unique_domains)) {
            $unique_domains[] = $domain;
            $content .= "domain:" . $domain . "\n";
        }
    }
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="likexfollow_disavow.txt"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit; // Stop execution to only output the text file
}

require_once '_header.php';

$message = ''; $msg_type = '';

// --- 1. ADVANCED AUTO-CREATE TOXIC LINKS TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_toxic_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_url VARCHAR(500),
        toxic_score INT DEFAULT 0,
        anchor_text VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Pending', -- Pending, Disavowed, Safe
        data_date DATE,
        UNIQUE KEY unique_audit_link (source_url)
    )");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

function extractRootDomain($url) {
    $host = parse_url($url, PHP_URL_HOST);
    return str_ireplace('www.', '', $host ?? 'Unknown');
}

// --- 2. ACTION: UPDATE LINK STATUS (Safe vs Disavow) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $link_id = (int)$_POST['link_id'];
    $new_status = sanitize($_POST['new_status']);
    
    $stmt = $db->prepare("UPDATE semrush_toxic_links SET status = ? WHERE id = ?");
    if($stmt->execute([$new_status, $link_id])) {
        $msg_word = $new_status == 'Disavowed' ? 'added to Disavow List ☢️' : 'marked as Safe ✅';
        $message = "Domain successfully $msg_word."; 
        $msg_type = "success";
    }
}

// --- 3. ADVANCED CSV PARSER (Backlink Audit Export) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audit_csv'])) {
    $file = $_FILES['audit_csv'];
    
    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($file['tmp_name'], "r");
        $headers = fgetcsv($handle, 10000, ",");
        $inserted = 0; $updated = 0;
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO semrush_toxic_links (source_url, toxic_score, anchor_text, data_date) 
                                  VALUES (?, ?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE toxic_score=?, anchor_text=?");
            
            $current_date = date('Y-m-d');
            
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if(count($headers) !== count($data)) continue;
                $row = array_combine($headers, $data);
                
                // Map SEMrush Backlink Audit Headers
                $src = sanitize($row['Source URL'] ?? $row['URL'] ?? '');
                $score = (int)($row['Toxic Score'] ?? $row['Toxicity Score'] ?? 0);
                $anchor = sanitize($row['Anchor'] ?? $row['Anchor text'] ?? 'No Anchor');
                
                if(!empty($src)) {
                    $check = $db->prepare("SELECT id FROM semrush_toxic_links WHERE source_url = ?");
                    $check->execute([$src]);
                    if($check->fetchColumn()) $updated++; else $inserted++;

                    $stmt->execute([$src, $score, $anchor, $current_date, $score, $anchor]);
                }
            }
            $db->commit();
            $message = "Audit Scan Complete! 🕵️‍♂️ Flagged $inserted new links, updated $updated.";
            $msg_type = "success";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Error parsing CSV. Make sure you upload the 'Backlink Audit' export from SEMrush.";
            $msg_type = "danger";
        }
        fclose($handle);
    } else {
        $message = "Invalid file. Please upload a valid CSV."; $msg_type = "danger";
    }
}

// --- 4. ADVANCED DATA AGGREGATION ---
$total_audited = 0; $high_toxic = 0; $ready_disavow = 0; $safe_links = 0;
$pending_links = [];

if($table_exists) {
    // Top Metrics
    $total_audited = $db->query("SELECT COUNT(*) FROM semrush_toxic_links")->fetchColumn() ?: 0;
    $high_toxic = $db->query("SELECT COUNT(*) FROM semrush_toxic_links WHERE toxic_score >= 60 AND status = 'Pending'")->fetchColumn() ?: 0;
    $ready_disavow = $db->query("SELECT COUNT(*) FROM semrush_toxic_links WHERE status = 'Disavowed'")->fetchColumn() ?: 0;
    $safe_links = $db->query("SELECT COUNT(*) FROM semrush_toxic_links WHERE status = 'Safe'")->fetchColumn() ?: 0;

    // Fetch Pending Links (Sorted by Toxicity)
    $pending_links = $db->query("SELECT * FROM semrush_toxic_links WHERE status = 'Pending' ORDER BY toxic_score DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: Toxicity Badge
function getToxicityBadge($score) {
    if($score >= 60) return ['class' => 'tox-high', 'text' => 'High Toxic', 'icon' => 'fa-skull-crossbones'];
    if($score >= 45) return ['class' => 'tox-med', 'text' => 'Suspicious', 'icon' => 'fa-exclamation-triangle'];
    return ['class' => 'tox-low', 'text' => 'Safe', 'icon' => 'fa-shield-alt'];
}
?>

<style>
    /* Premium Crisp White & Purple Theme */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --b-color: #e2e8f0; 
        --t-dark: #1e293b; --t-muted: #64748b; 
        --c-danger: #ef4444; --c-warning: #f59e0b; --c-success: #10b981;
    }
    body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    
    .audit-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .metric-box { padding: 1.5rem; border-right: 1px solid var(--b-color); text-align: center; }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .m-value { font-size: 2.2rem; font-weight: 800; color: var(--t-dark); line-height: 1; }
    
    .btn-action { background: var(--p-purple); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action:hover { background: #4f46e5; color: #fff; transform: translateY(-1px); }

    .btn-export { background: #1e293b; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-export:hover { background: #0f172a; color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

    .btn-sm-safe { background: #f8fafc; color: var(--c-success); border: 1px solid #bbf7d0; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; transition: 0.2s; cursor: pointer; }
    .btn-sm-safe:hover { background: #dcfce7; }
    
    .btn-sm-disavow { background: #f8fafc; color: var(--c-danger); border: 1px solid #fecaca; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; transition: 0.2s; cursor: pointer; }
    .btn-sm-disavow:hover { background: #fee2e2; }

    /* Toxicity Heatmap Styling */
    .tox-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; border: 1px solid; }
    .tox-high { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
    .tox-med { background: #fef3c7; color: #b45309; border-color: #fde68a; }
    .tox-low { background: #dcfce7; color: #166534; border-color: #86efac; }
    
    .score-circle { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; margin: 0 auto; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .sc-high { background: var(--c-danger); }
    .sc-med { background: var(--c-warning); }
    .sc-low { background: var(--c-success); }

    .url-display { font-weight: 600; color: var(--t-dark); font-size: 0.9rem; word-break: break-all; }
    .domain-display { color: var(--p-purple); font-size: 0.8rem; font-weight: 700; display: inline-block; margin-top: 3px; background: var(--l-purple); padding: 2px 8px; border-radius: 4px; }
</style>

<div class="container-fluid p-4" style="max-width: 1400px;">
    
    <div class="audit-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-biohazard me-2" style="color: var(--p-purple);"></i> Backlink Audit & Disavow</h2>
            <p class="text-muted mb-0">Clean your link profile. Flag toxic domains and generate Google Disavow files.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form method="POST" enctype="multipart/form-data" id="auditForm">
                <input type="file" name="audit_csv" id="audit_csv" accept=".csv" class="d-none" onchange="showSpinner()">
                <button type="button" class="btn-action" id="uploadBtn" onclick="document.getElementById('audit_csv').click()">
                    <i class="fas fa-upload"></i> Import Audit CSV
                </button>
            </form>
            
            <form method="POST" class="m-0">
                <button type="submit" name="export_disavow_file" class="btn-export">
                    <i class="fas fa-file-download"></i> Export Disavow.txt
                </button>
            </form>
        </div>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-3 shadow-sm mb-4"><i class="fas fa-info-circle me-2"></i> <?= $message ?></div> 
    <?php endif; ?>

    <div class="audit-card mb-4 overflow-hidden">
        <div class="row g-0">
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-search me-1"></i> Total Links Audited</div>
                <div class="m-value"><?= number_format($total_audited) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title text-danger"><i class="fas fa-skull me-1"></i> High Toxic (Pending)</div>
                <div class="m-value text-danger"><?= number_format($high_toxic) ?></div>
            </div>
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title" style="color: var(--t-dark);"><i class="fas fa-ban me-1"></i> Ready for Disavow</div>
                <div class="m-value"><?= number_format($ready_disavow) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title text-success"><i class="fas fa-shield-alt me-1"></i> Whitelisted (Safe)</div>
                <div class="m-value text-success"><?= number_format($safe_links) ?></div>
            </div>
        </div>
    </div>

    <div class="audit-card overflow-hidden h-100 d-flex flex-column">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="fas fa-list-ol me-2 text-primary"></i> Pending Links Review (Top 300)</span>
            <span class="badge bg-white text-dark border px-3 py-2">Sorted by Highest Toxicity</span>
        </div>
        
        <div class="table-responsive flex-grow-1" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-hover mb-0 align-middle">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="ps-4 text-muted small text-uppercase">Spam Source URL & Domain</th>
                        <th class="text-center text-muted small text-uppercase">Toxicity Score</th>
                        <th class="text-center text-muted small text-uppercase">Danger Level</th>
                        <th class="text-muted small text-uppercase">Anchor Text</th>
                        <th class="text-end pe-4 text-muted small text-uppercase" style="width: 250px;">Verdict Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pending_links)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-check-double fa-3x mb-3 opacity-25 text-success"></i><br>Your link profile is clean! No pending links to review.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($pending_links as $link): 
                        $score = (int)$link['toxic_score'];
                        $tox_data = getToxicityBadge($score);
                        $sc_class = $score >= 60 ? 'sc-high' : ($score >= 45 ? 'sc-med' : 'sc-low');
                    ?>
                    <tr <?= $score >= 60 ? 'style="background-color: #fef2f2; opacity: 0.9;"' : '' ?>>
                        <td class="ps-4 py-3">
                            <div class="url-display"><?= htmlspecialchars($link['source_url']) ?></div>
                            <div class="domain-display"><i class="fas fa-globe me-1"></i> Root: <?= extractRootDomain($link['source_url']) ?></div>
                        </td>
                        <td class="text-center">
                            <div class="score-circle <?= $sc_class ?>" title="Score: <?= $score ?>/100"><?= $score ?></div>
                        </td>
                        <td class="text-center">
                            <span class="tox-badge <?= $tox_data['class'] ?>"><i class="fas <?= $tox_data['icon'] ?>"></i> <?= $tox_data['text'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-muted border text-truncate" style="max-width: 150px; display: inline-block;">
                                <?= htmlspecialchars($link['anchor_text']) ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <form method="POST" class="m-0">
                                    <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                    <input type="hidden" name="new_status" value="Safe">
                                    <button type="submit" name="update_status" class="btn-sm-safe" title="Whitelist Domain">
                                        <i class="fas fa-check"></i> Safe
                                    </button>
                                </form>
                                <form method="POST" class="m-0">
                                    <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                    <input type="hidden" name="new_status" value="Disavowed">
                                    <button type="submit" name="update_status" class="btn-sm-disavow" title="Send to Disavow Generator">
                                        <i class="fas fa-ban"></i> Disavow
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Real Upload Spinner
    function showSpinner() {
        const btn = document.getElementById('uploadBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Scanning Toxicity...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';
        document.getElementById('auditForm').submit();
    }
</script>

<?php require_once '_footer.php'; ?>
