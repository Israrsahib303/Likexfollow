<?php
// File: panel/semrush_importer.php
require_once '_header.php';

$message = '';
$msg_type = '';

// --- 0. AUTO-CREATE LOG TABLE (Safety First) ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_upload_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_name VARCHAR(255),
        date_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50)
    )");
} catch (PDOException $e) {
    // Ignore if already exists or permission denied
}

// Helper: Log Action to DB
function logSemrushUpload($db, $action, $status) {
    try {
        $stmt = $db->prepare("INSERT INTO semrush_upload_logs (action_name, status) VALUES (?, ?)");
        $stmt->execute([$action, $status]);
    } catch (PDOException $e) {}
}

// Helper: Sanitize string
if(!function_exists('sanitize')){
    function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); }
}

// --- 1. CORE CSV PROCESSING ENGINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['semrush_csv'])) {
    $file = $_FILES['semrush_csv'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        if (strtolower($ext) === 'csv') {
            $handle = fopen($file['tmp_name'], "r");
            $headers = fgetcsv($handle, 10000, ","); // Read first row (Headers)
            
            // --- AUTO-DETECT REPORT TYPE ---
            $report_type = 'unknown';
            $action_name = 'Unknown CSV Upload';
            
            if (in_array('Keyword Difficulty Index', $headers) || (in_array('Keyword', $headers) && in_array('Volume', $headers))) {
                $report_type = 'keywords';
                $action_name = 'Imported Keyword Magic Data';
                // Auto-create table if missing
                $db->exec("CREATE TABLE IF NOT EXISTS semrush_keywords (id INT AUTO_INCREMENT PRIMARY KEY, keyword VARCHAR(255) UNIQUE, search_volume INT, keyword_difficulty INT, cpc DECIMAL(10,2))");
            } 
            elseif (in_array('Position', $headers) && in_array('URL', $headers)) {
                $report_type = 'rankings';
                $action_name = 'Imported Position Tracking Data';
                $db->exec("CREATE TABLE IF NOT EXISTS semrush_rankings (id INT AUTO_INCREMENT PRIMARY KEY, keyword VARCHAR(255), position INT, url VARCHAR(500), track_date DATE, UNIQUE KEY unique_track (keyword, track_date))");
            } 
            elseif (in_array('Source URL', $headers) && in_array('Target URL', $headers)) {
                $report_type = 'backlinks';
                $action_name = 'Imported Backlink Analytics';
                $db->exec("CREATE TABLE IF NOT EXISTS semrush_backlinks_data (id INT AUTO_INCREMENT PRIMARY KEY, source_url VARCHAR(500), target_url VARCHAR(500), anchor_text VARCHAR(255), page_score INT)");
            }

            // --- PROCESS DATA ---
            if ($report_type === 'unknown') {
                $message = "Error: Unrecognized SEMrush CSV format. Please upload unmodified SEMrush exports.";
                $msg_type = "danger";
                logSemrushUpload($db, 'Failed Upload - Invalid Format', 'failed');
            } else {
                $inserted = 0;
                $skipped = 0;
                
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    // Skip empty rows
                    if(count($headers) !== count($data)) { $skipped++; continue; }
                    $row = array_combine($headers, $data);
                    
                    try {
                        if ($report_type === 'keywords') {
                            $kw = sanitize($row['Keyword']);
                            $vol = (int)str_replace(',', '', $row['Volume'] ?? 0);
                            $kd = (int)($row['Keyword Difficulty'] ?? $row['Keyword Difficulty Index'] ?? 0);
                            $cpc = (float)($row['CPC'] ?? 0);
                            
                            $stmt = $db->prepare("INSERT INTO semrush_keywords (keyword, search_volume, keyword_difficulty, cpc) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE search_volume=?, keyword_difficulty=?, cpc=?");
                            $stmt->execute([$kw, $vol, $kd, $cpc, $vol, $kd, $cpc]);
                            $inserted++;
                        }
                        
                        elseif ($report_type === 'rankings') {
                            $kw = sanitize($row['Keyword']);
                            $pos = (int)($row['Position'] ?? 0);
                            $url = sanitize($row['URL'] ?? '');
                            $date = date('Y-m-d'); // Track as today's ranking
                            
                            $stmt = $db->prepare("INSERT INTO semrush_rankings (keyword, position, url, track_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE position=?, url=?");
                            $stmt->execute([$kw, $pos, $url, $date, $pos, $url]);
                            $inserted++;
                        }

                        elseif ($report_type === 'backlinks') {
                            $src = sanitize($row['Source URL']);
                            $tgt = sanitize($row['Target URL']);
                            $anchor = sanitize($row['Anchor'] ?? '');
                            $score = (int)($row['Page AS'] ?? 0);
                            
                            $stmt = $db->prepare("INSERT INTO semrush_backlinks_data (source_url, target_url, anchor_text, page_score) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$src, $tgt, $anchor, $score]);
                            $inserted++;
                        }
                    } catch (Exception $e) {
                        $skipped++;
                    }
                }
                fclose($handle);
                
                $message = "Success: $action_name. Inserted/Updated $inserted records (Skipped $skipped).";
                $msg_type = "success";
                logSemrushUpload($db, $action_name, 'success');
            }
        } else {
            $message = "Error: Invalid file extension. Only .csv files are allowed.";
            $msg_type = "danger";
            logSemrushUpload($db, 'Failed Upload - Bad Extension', 'failed');
        }
    } else {
        $message = "Error: File upload failed due to server limits.";
        $msg_type = "danger";
        logSemrushUpload($db, 'Failed Upload - Server Limit', 'failed');
    }
}
?>

<style>
    /* Professional Clean UI - Crisp White & Purple ONLY */
    :root { 
        --semrush-purple: #6366f1; 
        --semrush-light: #eef2ff; 
        --semrush-dark: #1e293b; 
        --semrush-gray: #64748b;
        --border-color: #e2e8f0; 
    }
    
    body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif; }
    
    .importer-card { 
        background: #fff; border-radius: 16px; border: 1px solid var(--border-color); 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); padding: 2.5rem; margin-bottom: 2rem;
    }
    
    .header-title { color: var(--semrush-dark); font-weight: 800; font-size: 1.5rem; margin-bottom: 0.5rem; }
    
    /* Drag & Drop Upload Zone */
    .upload-zone {
        border: 2px dashed var(--semrush-purple); border-radius: 12px; padding: 4rem 2rem; 
        text-align: center; background: var(--semrush-light); cursor: pointer; transition: all 0.2s ease;
    }
    .upload-zone:hover { background: #e0e7ff; transform: translateY(-2px); }
    .upload-zone i { font-size: 3.5rem; color: var(--semrush-purple); margin-bottom: 1rem; }
    .upload-zone h4 { font-weight: 700; color: var(--semrush-dark); margin-bottom: 0.5rem; }
    
    .real-alert { border-radius: 12px; font-weight: 600; padding: 1rem 1.5rem; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    
    .supported-formats-box {
        background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem;
    }
    .format-item { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; font-weight: 500; color: var(--semrush-gray); }
    .format-item i { color: var(--semrush-purple); font-size: 1.1rem; }
</style>

<div class="container-fluid p-4" style="max-width: 900px;">
    
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="header-title"><i class="fas fa-cloud-upload-alt me-2" style="color: var(--semrush-purple);"></i> Master CSV Importer</h2>
            <p class="text-muted mb-0">Upload pure SEMrush data. The AI engine will auto-route it to the correct vault.</p>
        </div>
        <a href="semrush_dashboard.php" class="btn btn-outline-secondary" style="border-radius: 8px; font-weight: 600;">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> real-alert mb-4 d-flex align-items-center">
            <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?> fs-4 me-3"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="importer-card">
        
        <form method="POST" enctype="multipart/form-data" id="csvForm">
            <div class="upload-zone mb-4" id="uploadDropzone" onclick="document.getElementById('semrush_csv').click()">
                <i class="fas fa-file-csv"></i>
                <h4>Click to browse or drag CSV here</h4>
                <p class="text-muted small mb-0">Maximum file size: 50MB (Wait while large files process)</p>
                <input type="file" name="semrush_csv" id="semrush_csv" accept=".csv" class="d-none" onchange="showSpinner()">
            </div>
            
            <div class="supported-formats-box">
                <h6 class="fw-bold text-dark mb-3">Auto-Detected Reports:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="format-item"><i class="fas fa-magic"></i> Keyword Magic Tool</div>
                        <div class="format-item"><i class="fas fa-trophy"></i> Position Tracking</div>
                    </div>
                    <div class="col-md-6">
                        <div class="format-item"><i class="fas fa-link"></i> Backlink Analytics</div>
                        <div class="format-item"><i class="fas fa-user-secret"></i> Organic Research (Competitors)</div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
    // --- Real UI Spinner Logic ---
    // Page load slow hone par showoff toast ki jagah real spinner aayega
    function showSpinner() {
        const fileInput = document.getElementById('semrush_csv');
        if(fileInput.files.length > 0) {
            const zone = document.getElementById('uploadDropzone');
            zone.innerHTML = `
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem; margin-bottom: 1rem; color: var(--semrush-purple)!important;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mt-2">Processing Data...</h4>
                <p class="text-muted small">Please do not close or refresh this page. This may take a minute for large files.</p>
            `;
            zone.style.pointerEvents = 'none'; // Prevent double clicking
            zone.style.opacity = '0.8';
            document.getElementById('csvForm').submit();
        }
    }

    // --- Optional Drag and Drop Logic ---
    const dropzone = document.getElementById('uploadDropzone');
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.style.backgroundColor = '#e0e7ff';
        dropzone.style.borderColor = '#4f46e5';
    });
    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.style.backgroundColor = 'var(--semrush-light)';
        dropzone.style.borderColor = 'var(--semrush-purple)';
    });
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length > 0) {
            document.getElementById('semrush_csv').files = e.dataTransfer.files;
            showSpinner();
        }
    });
</script>

<?php require_once '_footer.php'; ?>
