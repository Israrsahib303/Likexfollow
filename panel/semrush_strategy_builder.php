<?php
// File: panel/semrush_importer.php

// --- 1. DEBUGGING ON (White Screen Fix) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- 2. INCLUDE HEADER ---
if (!file_exists('_header.php')) { die("Error: _header.php not found."); }
include '_header.php'; 

// --- 3. CHECK ADMIN ACCESS ---
if (!function_exists('requireAdmin')) { die("Error: requireAdmin missing."); }
requireAdmin();

// =========================================================
//      🛠️ AUTO-FIX DATABASE (THE VAULT CREATION)
// =========================================================
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_keywords (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword VARCHAR(255) UNIQUE NOT NULL,
        search_volume INT DEFAULT 0,
        keyword_difficulty INT DEFAULT 0,
        cpc DECIMAL(10,2) DEFAULT 0.00,
        imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    die("<div style='background:#ef4444;color:white;padding:20px;border-radius:10px;margin:20px;'>Database Error: " . $e->getMessage() . "</div>");
}
// =========================================================

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

$success = ''; $error = '';
$imported_count = 0; $updated_count = 0;

// --- 4. HANDLE CSV UPLOAD & SMART PARSING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed. Error code: " . $file['error'];
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            $error = "Access Denied! Only .csv files are allowed. Please export your list from SEMrush as CSV.";
        } else {
            // Open the file
            $handle = fopen($file['tmp_name'], "r");
            if ($handle !== FALSE) {
                
                // Read the first row (Headers)
                $header = fgetcsv($handle, 10000, ",");
                
                // Smart Index Mapping
                $kw_idx = -1; $vol_idx = -1; $kd_idx = -1; $cpc_idx = -1;
                
                if ($header) {
                    foreach ($header as $index => $col) {
                        $col = strtolower(trim($col));
                        if (strpos($col, 'keyword') !== false && strpos($col, 'difficulty') === false) $kw_idx = $index;
                        if (strpos($col, 'volume') !== false || $col == 'search volume') $vol_idx = $index;
                        if ($col == 'kd' || strpos($col, 'difficulty') !== false) $kd_idx = $index;
                        if (strpos($col, 'cpc') !== false) $cpc_idx = $index;
                    }
                }

                if ($kw_idx === -1) {
                    $error = "Smart Parser Error: Could not find the 'Keyword' column in your CSV. Please ensure standard SEMrush format.";
                } else {
                    // Prepare DB Statement (Insert new or Update existing)
                    $stmt = $db->prepare("INSERT INTO semrush_keywords (keyword, search_volume, keyword_difficulty, cpc) VALUES (?, ?, ?, ?) 
                                          ON DUPLICATE KEY UPDATE search_volume=VALUES(search_volume), keyword_difficulty=VALUES(keyword_difficulty), cpc=VALUES(cpc)");
                    
                    // Loop through data rows
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        $kw = isset($data[$kw_idx]) ? trim($data[$kw_idx]) : '';
                        
                        // Skip empty keywords or header repetition
                        if (empty($kw) || strtolower($kw) == 'keyword') continue;
                        
                        $vol = isset($vol_idx) && isset($data[$vol_idx]) ? (int)str_replace(',', '', $data[$vol_idx]) : 0;
                        $kd = isset($kd_idx) && isset($data[$kd_idx]) ? (int)$data[$kd_idx] : 0;
                        $cpc = isset($cpc_idx) && isset($data[$cpc_idx]) ? (float)$data[$cpc_idx] : 0.00;
                        
                        $stmt->execute([$kw, $vol, $kd, $cpc]);
                        
                        // rowCount() is 1 for insert, 2 for update in MySQL
                        $rowsAffected = $stmt->rowCount();
                        if ($rowsAffected == 1) $imported_count++;
                        else if ($rowsAffected == 2) $updated_count++;
                    }
                    
                    $success = "Vault Synced! Successfully injected {$imported_count} new keywords and updated {$updated_count} existing ones.";
                }
                fclose($handle);
            } else {
                $error = "Could not open the uploaded file.";
            }
        }
    }
}

// --- 5. FETCH VAULT STATS ---
try {
    $total_kws = $db->query("SELECT COUNT(*) FROM semrush_keywords")->fetchColumn();
    $easy_kws = $db->query("SELECT COUNT(*) FROM semrush_keywords WHERE keyword_difficulty <= 40")->fetchColumn();
    
    // Fetch last 10 imported for display
    $recent_kws = $db->query("SELECT * FROM semrush_keywords ORDER BY imported_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $total_kws = 0; $easy_kws = 0; $recent_kws = [];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
    :root {
        --p-blue: #3b82f6; --p-indigo: #6366f1; --p-purple: #8b5cf6;
        --bg-color: #f8fafc; --card-bg: #ffffff;
        --text-main: #0f172a; --text-sub: #64748b;
        --border: #e2e8f0;
    }
    body { background-color: var(--bg-color); font-family: 'Outfit', sans-serif; }

    /* Top Stats Header */
    .importer-header { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border-radius: 20px; padding: 40px; color: white; margin-bottom: 30px; position: relative; overflow: hidden; box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.4); }
    .importer-header-content { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .ih-title h1 { margin: 0; font-size: 2.2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; }
    .ih-title p { margin: 5px 0 0 0; color: #cbd5e1; font-size: 1.05rem; }
    
    .ih-stats-grid { display: flex; gap: 20px; }
    .ih-stat-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 15px 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); text-align: center; }
    .ih-stat-num { font-size: 1.8rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
    .ih-stat-lbl { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; }

    /* Main Grid */
    .importer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start; }

    /* Upload Zone */
    .upload-card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
    .uc-title { font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }

    .drop-zone {
        border: 3px dashed #cbd5e1; border-radius: 16px; padding: 50px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s ease; position: relative; margin-bottom: 20px;
    }
    .drop-zone:hover, .drop-zone.dragover { border-color: var(--p-indigo); background: #eef2ff; }
    .drop-zone i { font-size: 3.5rem; color: #94a3b8; margin-bottom: 15px; transition: 0.3s; }
    .drop-zone:hover i, .drop-zone.dragover i { color: var(--p-indigo); transform: translateY(-5px); }
    .drop-zone h3 { margin: 0 0 10px 0; font-weight: 800; color: var(--text-main); font-size: 1.2rem; }
    .drop-zone p { margin: 0; color: var(--text-sub); font-size: 0.95rem; }
    
    .file-input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

    .btn-submit { width: 100%; background: var(--p-indigo); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; transition: 0.3s; box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-submit:hover { transform: translateY(-3px); background: #4f46e5; }
    .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; transform: none; }

    /* Recent Data Table */
    .data-card { background: var(--card-bg); border-radius: 20px; border: 1px solid var(--border); padding: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
    .table-responsive { overflow-x: auto; }
    .seo-table { width: 100%; border-collapse: collapse; }
    .seo-table th { background: #f8fafc; padding: 12px 15px; font-size: 0.85rem; font-weight: 800; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--border); text-align: left; }
    .seo-table td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 0.95rem; font-weight: 600; color: var(--text-main); }
    .seo-table tr:last-child td { border-bottom: none; }
    
    .kd-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; color: white; display: inline-block; text-align: center; min-width: 50px; }
    .kd-easy { background: var(--success); }
    .kd-med { background: #f59e0b; }
    .kd-hard { background: #ef4444; }

    /* Success/Error Alerts */
    .alert-box { padding: 15px 20px; border-radius: 12px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    @media(max-width: 992px) {
        .importer-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="container-fluid" style="padding: 20px;">

    <div class="importer-header">
        <div class="importer-header-content">
            <div class="ih-title">
                <h1><i class="fas fa-cloud-upload-alt text-info"></i> Master Importer</h1>
                <p>Inject raw CSV data from SEMrush/Ahrefs directly into your SEO Vault.</p>
            </div>
            
            <div class="ih-stats-grid">
                <div class="ih-stat-box">
                    <div class="ih-stat-num text-white"><?= number_format($total_kws) ?></div>
                    <div class="ih-stat-lbl">Vault Size</div>
                </div>
                <div class="ih-stat-box">
                    <div class="ih-stat-num text-success"><?= number_format($easy_kws) ?></div>
                    <div class="ih-stat-lbl">Easy Targets (KD < 40)</div>
                </div>
            </div>
        </div>
        <i class="fas fa-database position-absolute" style="font-size: 20rem; color: rgba(255,255,255,0.03); right: -50px; bottom: -50px; z-index: 1;"></i>
    </div>

    <?php if ($success): ?>
        <div class="alert-box alert-success"><i class="fas fa-check-circle fs-4"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-box alert-danger"><i class="fas fa-exclamation-triangle fs-4"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="importer-grid">
        
        <div class="upload-card">
            <h2 class="uc-title"><i class="fas fa-file-csv me-2 text-primary"></i> Data Injection Portal</h2>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="drop-zone" id="dropZone">
                    <i class="fas fa-cloud-upload-alt" id="dzIcon"></i>
                    <h3 id="dzText">Drag & Drop your CSV here</h3>
                    <p id="dzSub">or click to browse from your computer</p>
                    <input type="file" name="csv_file" id="fileInput" class="file-input" accept=".csv" required>
                </div>
                
                <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 0 10px 10px 0; margin-bottom: 20px;">
                    <p style="margin:0; font-size:0.85rem; color:#92400e; font-weight:600;">
                        <i class="fas fa-magic me-1"></i> <b>Smart Parser Enabled:</b> Our engine will automatically detect Keyword, Search Volume, and KD% columns from standard SEO tools.
                    </p>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-bolt"></i> Sync to Vault
                </button>
            </form>
        </div>

        <div class="data-card">
            <h2 class="uc-title"><i class="fas fa-history me-2 text-primary"></i> Recent Vault Injections</h2>
            
            <?php if(empty($recent_kws)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3" style="opacity: 0.5;"></i>
                    <h4 class="fw-bold text-dark">Vault is Empty</h4>
                    <p class="text-muted">Upload your first CSV file to populate the SEO Vault.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive custom-scrollbar" style="max-height: 400px;">
                    <table class="seo-table">
                        <thead>
                            <tr>
                                <th>Keyword</th>
                                <th>Volume</th>
                                <th>Difficulty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_kws as $r): 
                                $kd = $r['keyword_difficulty'];
                                $kd_class = $kd <= 40 ? 'kd-easy' : ($kd <= 65 ? 'kd-med' : 'kd-hard');
                            ?>
                            <tr>
                                <td style="color: var(--p-indigo);"><?= htmlspecialchars($r['keyword']) ?></td>
                                <td><i class="fas fa-search me-2 text-muted" style="font-size:0.8rem;"></i> <?= number_format($r['search_volume']) ?></td>
                                <td><span class="kd-badge <?= $kd_class ?>"><?= $kd ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // --- Drag and Drop UI Logic ---
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const dzText = document.getElementById('dzText');
    const dzSub = document.getElementById('dzSub');
    const dzIcon = document.getElementById('dzIcon');
    const submitBtn = document.getElementById('submitBtn');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('dragover');
    }

    function unhighlight(e) {
        dropZone.classList.remove('dragover');
    }

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        handleFiles(files);
    }

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            let file = files[0];
            
            // Check if it's a CSV
            if (file.name.toLowerCase().endsWith('.csv')) {
                dzIcon.className = 'fas fa-file-csv text-success';
                dzText.innerText = 'File Selected:';
                dzSub.innerHTML = `<span style="color:var(--p-indigo); font-weight:800;">${file.name}</span>`;
                dropZone.style.borderColor = '#10b981';
                dropZone.style.background = '#dcfce7';
                
                submitBtn.disabled = false;
                
                // Assign to input if dropped
                if (fileInput.files.length === 0) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                }
            } else {
                dzIcon.className = 'fas fa-times-circle text-danger';
                dzText.innerText = 'Invalid File Type!';
                dzSub.innerText = 'Please upload a .csv file only.';
                dropZone.style.borderColor = '#ef4444';
                dropZone.style.background = '#fee2e2';
                submitBtn.disabled = true;
            }
        }
    }

    // Show loading on form submit
    document.getElementById('uploadForm').addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Injecting Data...';
        submitBtn.style.opacity = '0.8';
        submitBtn.style.pointerEvents = 'none';
    });
</script>

<?php require_once '_footer.php'; ?>