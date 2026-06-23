<?php
// File: panel/semrush_link_building.php
require_once '_header.php';

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE CRM TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_outreach_crm (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_domain VARCHAR(255),
        target_url VARCHAR(500),
        authority_score INT DEFAULT 0,
        contact_email VARCHAR(255) DEFAULT '',
        status VARCHAR(50) DEFAULT 'Prospect', -- Prospect, Contacted, Negotiating, Acquired, Rejected
        notes VARCHAR(500) DEFAULT '',
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_domain (target_domain)
    )");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// Helper: Extract Domain
function getOutreachDomain($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if(!$host) return $url;
    return str_ireplace('www.', '', $host);
}

// --- 1. ACTION: UPDATE CRM RECORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_crm'])) {
    $crm_id = (int)$_POST['crm_id'];
    $new_status = sanitize($_POST['status']);
    $email = sanitize($_POST['contact_email']);
    $notes = sanitize($_POST['notes']);
    
    $stmt = $db->prepare("UPDATE semrush_outreach_crm SET status=?, contact_email=?, notes=? WHERE id=?");
    if($stmt->execute([$new_status, $email, $notes, $crm_id])) {
        $message = "Outreach pipeline updated for this domain! 🚀"; 
        $msg_type = "success";
    }
}

// --- 2. ADVANCED CSV PARSER (Link Building Prospects) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['lb_csv'])) {
    $file = $_FILES['lb_csv'];
    
    if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($file['tmp_name'], "r");
        $headers = fgetcsv($handle, 10000, ",");
        $inserted = 0; $updated = 0;
        
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO semrush_outreach_crm (target_domain, target_url, authority_score) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE target_url=?, authority_score=?");
            
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if(count($headers) !== count($data)) continue;
                $row = array_combine($headers, $data);
                
                // Map SEMrush Link Building Headers
                $url = sanitize($row['URL'] ?? $row['Domain'] ?? '');
                $as = (int)($row['AS'] ?? $row['Authority Score'] ?? $row['Rating'] ?? 0);
                
                if(!empty($url)) {
                    $domain = getOutreachDomain($url);
                    
                    $check = $db->prepare("SELECT id FROM semrush_outreach_crm WHERE target_domain = ?");
                    $check->execute([$domain]);
                    if($check->fetchColumn()) $updated++; else $inserted++;

                    $stmt->execute([$domain, $url, $as, $url, $as]);
                }
            }
            $db->commit();
            $message = "Prospects Synced! 🎯 Added $inserted new targets, updated $updated.";
            $msg_type = "success";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Error analyzing CSV. Please upload the 'Link Building Prospects' export.";
            $msg_type = "danger";
        }
        fclose($handle);
    } else {
        $message = "Invalid file format. Upload a valid CSV."; $msg_type = "danger";
    }
}

// --- 3. ADVANCED DATA AGGREGATION ---
$total_prospects = 0; $active_outreach = 0; $acquired = 0; $win_rate = 0;
$crm_list = [];

if($table_exists) {
    // Pipeline Metrics
    $total_prospects = $db->query("SELECT COUNT(*) FROM semrush_outreach_crm")->fetchColumn() ?: 0;
    
    // Active outreach means Contacted or Negotiating
    $active_outreach = $db->query("SELECT COUNT(*) FROM semrush_outreach_crm WHERE status IN ('Contacted', 'Negotiating')")->fetchColumn() ?: 0;
    
    $acquired = $db->query("SELECT COUNT(*) FROM semrush_outreach_crm WHERE status = 'Acquired'")->fetchColumn() ?: 0;
    $rejected = $db->query("SELECT COUNT(*) FROM semrush_outreach_crm WHERE status = 'Rejected'")->fetchColumn() ?: 0;
    
    // Win Rate Calculation (Acquired / (Acquired + Rejected))
    $closed_deals = $acquired + $rejected;
    if($closed_deals > 0) {
        $win_rate = round(($acquired / $closed_deals) * 100, 1);
    }

    // Fetch CRM List (Sorted by highest AS first, pending action)
    // We put 'Rejected' and 'Acquired' at the bottom
    $crm_list = $db->query("
        SELECT * FROM semrush_outreach_crm 
        ORDER BY 
            CASE status
                WHEN 'Negotiating' THEN 1
                WHEN 'Contacted' THEN 2
                WHEN 'Prospect' THEN 3
                WHEN 'Acquired' THEN 4
                WHEN 'Rejected' THEN 5
            END,
            authority_score DESC 
        LIMIT 300
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
    /* Premium Crisp White & Purple Theme */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --b-color: #e2e8f0; 
        --t-dark: #1e293b; --t-muted: #64748b; 
        --c-success: #10b981; --c-warning: #f59e0b; --c-danger: #ef4444; --c-info: #3b82f6;
    }
    body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    
    .crm-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .metric-box { padding: 1.5rem; border-right: 1px solid var(--b-color); text-align: center; }
    .metric-box:last-child { border-right: none; }
    .m-title { font-size: 0.8rem; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .m-value { font-size: 2.2rem; font-weight: 800; color: var(--t-dark); line-height: 1; }
    
    .btn-action { background: var(--p-purple); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-action:hover { background: #4f46e5; color: #fff; transform: translateY(-1px); }

    .btn-save { background: #f8fafc; color: var(--p-purple); border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s; cursor: pointer; }
    .btn-save:hover { background: var(--l-purple); border-color: var(--p-purple); }

    .btn-copy-pitch { background: #f8fafc; color: var(--t-muted); border: 1px solid var(--b-color); padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; transition: 0.2s; cursor: pointer; margin-top: 5px; display: inline-block;}
    .btn-copy-pitch:hover { background: var(--t-dark); color: #fff; border-color: var(--t-dark); }

    /* Custom Input fields for table */
    .crm-input { border: 1px solid var(--b-color); border-radius: 6px; padding: 6px 10px; font-size: 0.85rem; width: 100%; outline: none; transition: 0.3s; color: var(--t-dark); font-weight: 500; }
    .crm-input:focus { border-color: var(--p-purple); }
    
    .crm-select { border: 1px solid var(--b-color); border-radius: 6px; padding: 6px 10px; font-size: 0.85rem; width: 100%; font-weight: 700; outline: none; transition: 0.3s; cursor: pointer; }
    
    /* Dynamic Select Colors */
    .sel-Prospect { background: #f8fafc; color: var(--t-muted); }
    .sel-Contacted { background: var(--l-purple); color: var(--p-purple); border-color: #c7d2fe; }
    .sel-Negotiating { background: #fef3c7; color: var(--c-warning); border-color: #fde68a; }
    .sel-Acquired { background: #dcfce7; color: var(--c-success); border-color: #86efac; }
    .sel-Rejected { background: #fee2e2; color: var(--c-danger); border-color: #fca5a5; }

    .as-circle { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 0 auto;}
    
    .url-display { font-weight: 700; color: var(--t-dark); font-size: 0.95rem; }
    .url-display:hover { color: var(--p-purple); }
    
    /* Search Bar */
    .search-wrapper { position: relative; width: 300px; }
    .search-wrapper i { position: absolute; left: 15px; top: 12px; color: var(--t-muted); }
    .search-input { width: 100%; padding: 8px 15px 8px 40px; border: 2px solid var(--b-color); border-radius: 8px; font-weight: 600; color: var(--t-dark); outline: none; transition: 0.3s; background: #f8fafc; }
    .search-input:focus { border-color: var(--p-purple); background: #fff; }
</style>

<div class="container-fluid p-4" style="max-width: 1500px;">
    
    <div class="crm-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-handshake me-2" style="color: var(--p-purple);"></i> Link Building CRM</h2>
            <p class="text-muted mb-0">Manage outreach campaigns, track email pitches, and acquire high-AS backlinks.</p>
        </div>
        <form method="POST" enctype="multipart/form-data" id="lbForm">
            <input type="file" name="lb_csv" id="lb_csv" accept=".csv" class="d-none" onchange="showSpinner()">
            <button type="button" class="btn-action" id="uploadBtn" onclick="document.getElementById('lb_csv').click()">
                <i class="fas fa-cloud-upload-alt"></i> Import Prospects CSV
            </button>
        </form>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-3 shadow-sm mb-4"><i class="fas fa-check-circle me-2"></i> <?= $message ?></div> 
    <?php endif; ?>

    <div class="crm-card mb-4 overflow-hidden">
        <div class="row g-0">
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title text-primary"><i class="fas fa-inbox me-1"></i> Total Prospects</div>
                <div class="m-value"><?= number_format($total_prospects) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title" style="color: var(--p-purple);"><i class="fas fa-paper-plane me-1"></i> Active Outreach</div>
                <div class="m-value" style="color: var(--p-purple);"><?= number_format($active_outreach) ?></div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="m-title text-success"><i class="fas fa-trophy me-1"></i> Links Acquired</div>
                <div class="m-value text-success"><?= number_format($acquired) ?></div>
            </div>
            <div class="col-md-3 metric-box bg-light">
                <div class="m-title text-dark"><i class="fas fa-chart-pie me-1"></i> Win Rate</div>
                <div class="m-value"><?= $win_rate ?>%</div>
            </div>
        </div>
    </div>

    <div class="crm-card overflow-hidden h-100 d-flex flex-column">
        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark"><i class="fas fa-tasks me-2 text-primary"></i> Outreach Pipeline (Top 300)</span>
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="liveSearch" class="search-input" placeholder="Search domains..." onkeyup="filterTable()">
            </div>
        </div>
        
        <div class="table-responsive flex-grow-1" style="max-height: 700px; overflow-y: auto;">
            <table class="table table-hover mb-0 align-middle" id="crmTable">
                <thead style="position: sticky; top: 0; background: #fff; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th class="ps-4 text-muted small text-uppercase" style="width: 25%;">Target Domain</th>
                        <th class="text-center text-muted small text-uppercase" style="width: 8%;">AS Score</th>
                        <th class="text-muted small text-uppercase" style="width: 20%;">Contact Info</th>
                        <th class="text-muted small text-uppercase" style="width: 22%;">CRM Status</th>
                        <th class="text-end pe-4 text-muted small text-uppercase" style="width: 25%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($crm_list)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-3x mb-3 opacity-25"></i><br>No prospects found. Upload Link Building CSV.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($crm_list as $crm): 
                        // AS Color Logic
                        $as = (int)$crm['authority_score'];
                        $as_bg = $as >= 50 ? 'var(--c-success)' : ($as >= 25 ? 'var(--c-warning)' : 'var(--c-danger)');
                        
                        $is_closed = in_array($crm['status'], ['Acquired', 'Rejected']);
                    ?>
                    <tr class="crm-row" <?= $is_closed ? 'style="opacity: 0.6; background-color: #f8fafc;"' : '' ?>>
                        <td class="ps-4 py-3">
                            <a href="<?= htmlspecialchars($crm['target_url']) ?>" target="_blank" class="url-display text-decoration-none d-block dom-text">
                                <?= htmlspecialchars($crm['target_domain']) ?>
                            </a>
                            <button type="button" class="btn-copy-pitch" onclick="copyPitch(this, '<?= htmlspecialchars($crm['target_domain']) ?>')">
                                <i class="fas fa-copy"></i> Copy Pitch Template
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="as-circle" style="background: <?= $as_bg ?>;"><?= $as ?></div>
                        </td>
                        <form method="POST" class="m-0">
                            <td>
                                <input type="email" name="contact_email" class="crm-input mb-2" placeholder="Email address..." value="<?= htmlspecialchars($crm['contact_email']) ?>">
                                <input type="text" name="notes" class="crm-input" placeholder="Notes/Price..." value="<?= htmlspecialchars($crm['notes']) ?>">
                            </td>
                            <td>
                                <select name="status" class="crm-select sel-<?= $crm['status'] ?>" onchange="updateSelectColor(this)">
                                    <option value="Prospect" <?= $crm['status'] == 'Prospect' ? 'selected' : '' ?>>🎯 Prospect</option>
                                    <option value="Contacted" <?= $crm['status'] == 'Contacted' ? 'selected' : '' ?>>📧 Contacted</option>
                                    <option value="Negotiating" <?= $crm['status'] == 'Negotiating' ? 'selected' : '' ?>>💬 Negotiating</option>
                                    <option value="Acquired" <?= $crm['status'] == 'Acquired' ? 'selected' : '' ?>>✅ Acquired (Won)</option>
                                    <option value="Rejected" <?= $crm['status'] == 'Rejected' ? 'selected' : '' ?>>❌ Rejected (Lost)</option>
                                </select>
                                <div class="small text-muted mt-1 text-end" style="font-size: 0.7rem;">Updated: <?= date('d M, y', strtotime($crm['last_updated'])) ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <input type="hidden" name="crm_id" value="<?= $crm['id'] ?>">
                                <button type="submit" name="update_crm" class="btn-save">
                                    <i class="fas fa-save me-1"></i> Update
                                </button>
                            </td>
                        </form>
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
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Syncing Prospects...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';
        document.getElementById('lbForm').submit();
    }

    // Dynamic Select Background Color Changer
    function updateSelectColor(selectObj) {
        let val = selectObj.value;
        selectObj.className = "crm-select sel-" + val;
    }

    // Live JS Search
    function filterTable() {
        let input = document.getElementById("liveSearch").value.toLowerCase();
        let rows = document.getElementsByClassName("crm-row");

        for (let i = 0; i < rows.length; i++) {
            let domainText = rows[i].querySelector(".dom-text").textContent.toLowerCase();
            if (domainText.indexOf(input) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }

    // The Ultimate Email Pitch Generator Hack
    function copyPitch(button, domain) {
        let siteName = "LikexFollow"; // Replace with dynamic site name if needed
        let template = `Hi Team at ${domain},\n\nI was researching content for my audience and found your site ${domain} really insightful! I run ${siteName}, and we have some high-quality resources that would perfectly complement your recent posts.\n\nAre you open to editorial contributions or link collaborations?\n\nLet me know if we can discuss this further!\n\nBest regards,\nIsrar\n${siteName}`;
        
        navigator.clipboard.writeText(template).then(function() {
            let originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Pitch Copied!';
            button.style.background = '#10b981';
            button.style.color = '#fff';
            button.style.borderColor = '#10b981';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style = ''; // reset
            }, 2000);
        });
    }
</script>

<?php require_once '_footer.php'; ?>
