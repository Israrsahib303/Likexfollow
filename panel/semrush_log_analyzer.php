<?php
// File: panel/semrush_log_analyzer.php
require_once '_header.php';

$message = '';
$msg_type = '';

// --- 0. ADVANCED AUTO-CREATE DB TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_server_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(50),
        crawl_url VARCHAR(500),
        status_code INT,
        user_agent VARCHAR(255),
        crawl_date DATETIME,
        bot_type VARCHAR(50) DEFAULT 'Googlebot',
        UNIQUE KEY unique_crawl (ip_address, crawl_url, crawl_date)
    )");
    
    // Auto-patch old tables to include bot_type if missing
    try {
        $db->exec("ALTER TABLE semrush_server_logs ADD COLUMN bot_type VARCHAR(50) DEFAULT 'Googlebot' AFTER user_agent");
    } catch(Exception $e) { /* Column already exists */ }
    
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// --- 1. ADVANCED LOG PARSER ENGINE (Memory Safe & Multi-Bot) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['server_log'])) {
    $file = $_FILES['server_log'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['log', 'txt', 'csv'])) {
            $handle = fopen($file['tmp_name'], "r");
            $inserted = 0;
            $skipped = 0;
            
            $db->beginTransaction(); 
            
            try {
                $stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date, bot_type) VALUES (?, ?, ?, ?, ?, ?)");
                
                while (($line = fgets($handle)) !== false) {
                    $bot_type = 'Unknown';
                    if (stripos($line, 'Googlebot') !== false) $bot_type = 'Googlebot';
                    elseif (stripos($line, 'SemrushBot') !== false) $bot_type = 'SemrushBot';
                    elseif (stripos($line, 'bingbot') !== false) $bot_type = 'Bingbot';
                    elseif (stripos($line, 'AhrefsBot') !== false) $bot_type = 'AhrefsBot';
                    
                    if ($bot_type !== 'Unknown') {
                        // Standard Apache/Nginx Combined Log Regex
                        if (preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(?:GET|POST|HEAD) ([^"]+) HTTP\/\d\.\d" (\d+) \d+ "[^"]*" "([^"]*)"/', $line, $matches)) {
                            $ip = sanitize($matches[1]);
                            $date_raw = $matches[2]; 
                            $url = sanitize(strtok($matches[3], '?')); 
                            $status = (int)$matches[4];
                            $ua = sanitize($matches[5]);
                            
                            $date_formatted = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', substr($date_raw, 0, 20))));
                            
                            if($stmt->execute([$ip, $url, $status, $ua, $date_formatted, $bot_type])) {
                                if($stmt->rowCount() > 0) $inserted++;
                            }
                        } else {
                            $skipped++;
                        }
                    }
                }
                $db->commit();
                $message = "Deep Analysis Complete! 🚀 Found and injected $inserted new Search Engine hits.";
                $msg_type = "success";
            } catch (Exception $e) {
                $db->rollBack();
                $message = "Error during log parsing. System rolled back safely.";
                $msg_type = "danger";
            }
            fclose($handle);
        } else {
            $message = "Error: Please upload a valid server access.log or .txt file.";
            $msg_type = "danger";
        }
    } else {
        $message = "Upload failed. File might be too large for PHP settings.";
        $msg_type = "danger";
    }
}

// --- 2. ADVANCED DATA AGGREGATION & ANALYTICS ---
$total_hits = 0; $unique_urls = 0; $error_hits = 0;
$status_data = []; $timeline_dates = []; $timeline_hits = [];
$top_pages = []; $recent_logs = []; $bot_data = [];

if($table_exists) {
    $total_hits = $db->query("SELECT COUNT(*) FROM semrush_server_logs")->fetchColumn() ?: 0;
    $unique_urls = $db->query("SELECT COUNT(DISTINCT crawl_url) FROM semrush_server_logs")->fetchColumn() ?: 0;
    $error_hits = $db->query("SELECT COUNT(*) FROM semrush_server_logs WHERE status_code >= 400")->fetchColumn() ?: 0;

    // Status Code Distribution
    $status_query = $db->query("SELECT status_code, COUNT(*) as count FROM semrush_server_logs GROUP BY status_code ORDER BY count DESC");
    while($row = $status_query->fetch(PDO::FETCH_ASSOC)) {
        $status_data[$row['status_code']] = $row['count'];
    }

    // Bot Breakdown
    $bot_query = $db->query("SELECT bot_type, COUNT(*) as count FROM semrush_server_logs GROUP BY bot_type ORDER BY count DESC");
    while($row = $bot_query->fetch(PDO::FETCH_ASSOC)) {
        $bot_data[$row['bot_type']] = $row['count'];
    }

    // Crawl Timeline (Last 14 Days)
    $timeline_query = $db->query("SELECT DATE(crawl_date) as cdate, COUNT(*) as hits FROM semrush_server_logs GROUP BY DATE(crawl_date) ORDER BY cdate DESC LIMIT 14");
    $timeline_results = array_reverse($timeline_query->fetchAll(PDO::FETCH_ASSOC));
    foreach($timeline_results as $r) {
        $timeline_dates[] = date('d M', strtotime($r['cdate']));
        $timeline_hits[] = (int)$r['hits'];
    }

    // Crawl Budget (Top Pages)
    $top_pages = $db->query("SELECT crawl_url, COUNT(*) as hits FROM semrush_server_logs GROUP BY crawl_url ORDER BY hits DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // Live Stream
    $recent_logs = $db->query("SELECT * FROM semrush_server_logs ORDER BY crawl_date DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --b-color: #e2e8f0; 
        --t-dark: #1e293b; --t-muted: #64748b; 
        --s-200: #10b981; --s-300: #f59e0b; --s-400: #ef4444; --s-500: #991b1b;
    }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .log-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    
    .stat-block { padding: 1.5rem; text-align: center; border-right: 1px solid var(--b-color); }
    .stat-block:last-child { border-right: none; }
    .stat-val { font-size: 2.2rem; font-weight: 800; color: var(--t-dark); line-height: 1.1; }
    .stat-label { font-size: 0.8rem; font-weight: 700; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px; }

    .btn-upload { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; font-weight: 700; padding: 12px 24px; border-radius: 10px; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
    .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4); color: #fff; }

    .status-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; }
    .st-200 { background: #dcfce7; color: var(--s-200); }
    .st-300 { background: #fef3c7; color: var(--s-300); }
    .st-400 { background: #fee2e2; color: var(--s-400); }
    .st-500 { background: #7f1d1d; color: #fca5a5; }

    .bot-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; background: #f1f5f9; color: var(--t-dark); border: 1px solid var(--b-color); }

    .url-cell { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; font-weight: 600; color: var(--p-purple); text-decoration: none; }
    .url-cell:hover { text-decoration: underline; }
    
    .table th { background: #f8fafc; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; color: var(--t-muted); border-bottom: 2px solid var(--b-color); padding: 15px; }
    .table td { padding: 15px; border-bottom: 1px solid var(--b-color); vertical-align: middle; }
</style>

<div class="container-fluid p-4" style="max-width: 1400px;">
    
    <div class="log-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-satellite-dish me-2" style="color: var(--p-purple);"></i> Bot Intelligence Analyzer</h2>
            <p class="text-muted mb-0">Decode search engine crawler behavior. Upload your server's <code>access.log</code> to extract crawl budget insights.</p>
        </div>
        <form method="POST" enctype="multipart/form-data" id="logForm">
            <input type="file" name="server_log" id="server_log" accept=".log,.txt,.csv" class="d-none" onchange="showUploadSpinner()">
            <button type="button" class="btn-upload" id="uploadBtn" onclick="document.getElementById('server_log').click()">
                <i class="fas fa-microchip"></i> Process Access Log
            </button>
        </form>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-3 shadow-sm mb-4 border-0 p-3" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $message ?>
        </div> 
    <?php endif; ?>

    <div class="log-card mb-4 overflow-hidden">
        <div class="row g-0">
            <div class="col-md-4 stat-block bg-light">
                <div class="stat-val text-primary"><?= number_format($total_hits) ?></div>
                <div class="stat-label">Total Crawler Hits</div>
            </div>
            <div class="col-md-4 stat-block">
                <div class="stat-val text-success"><?= number_format($unique_urls) ?></div>
                <div class="stat-label">Unique URLs Crawled</div>
            </div>
            <div class="col-md-4 stat-block">
                <div class="stat-val text-danger"><?= number_format($error_hits) ?></div>
                <div class="stat-label">Dead Ends (4xx/5xx)</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="log-card p-4 h-100">
                <h5 class="fw-bold text-dark mb-4"><i class="fas fa-chart-area me-2 text-primary"></i> Crawl Velocity Timeline</h5>
                <?php if(empty($timeline_hits)): ?>
                    <div class="text-center py-5 text-muted"><i class="fas fa-wave-square fa-3x mb-3 opacity-25"></i><p>Awaiting log data...</p></div>
                <?php else: ?>
                    <div style="height: 300px; width: 100%;"><canvas id="crawlTimelineChart"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="log-card p-4 h-100">
                <h5 class="fw-bold text-dark mb-4"><i class="fas fa-chart-pie me-2 text-warning"></i> HTTP Status Flow</h5>
                <?php if(empty($status_data)): ?>
                    <div class="text-center py-5 text-muted"><i class="fas fa-circle-notch fa-3x mb-3 opacity-25"></i><p>Awaiting log data...</p></div>
                <?php else: ?>
                    <div style="height: 220px; width: 100%;"><canvas id="statusChart"></canvas></div>
                    <div class="mt-4 row text-center g-2">
                        <?php foreach($status_data as $code => $count): 
                            $badge = $code < 300 ? 'st-200' : ($code < 400 ? 'st-300' : ($code < 500 ? 'st-400' : 'st-500'));
                        ?>
                            <div class="col-6"><span class="status-badge <?= $badge ?> w-100 shadow-sm border">HTTP <?= $code ?> : <?= number_format($count) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="log-card overflow-hidden h-100">
                <div class="p-4 bg-light border-bottom">
                    <h5 class="fw-bold text-dark m-0"><i class="fas fa-fire-alt me-2 text-danger"></i> Crawl Budget Top Targets</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead><tr><th class="ps-4">URL Path</th><th class="text-end pe-4">Visits</th></tr></thead>
                        <tbody>
                            <?php if(empty($top_pages)): ?><tr><td colspan="2" class="text-center py-5 text-muted">No data.</td></tr><?php endif; ?>
                            <?php foreach($top_pages as $page): ?>
                            <tr>
                                <td class="ps-4"><a href="<?= htmlspecialchars($page['crawl_url']) ?>" target="_blank" class="url-cell" title="<?= htmlspecialchars($page['crawl_url']) ?>"><?= htmlspecialchars($page['crawl_url']) ?></a></td>
                                <td class="text-end pe-4 fw-bold text-dark fs-5"><?= number_format($page['hits']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="log-card overflow-hidden h-100 d-flex flex-column">
                <div class="p-4 bg-light border-bottom">
                    <h5 class="fw-bold text-dark m-0"><i class="fas fa-list-ol me-2 text-success"></i> Live Bot Stream</h5>
                </div>
                <div class="table-responsive flex-grow-1" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <tr><th class="ps-4">Time</th><th>Bot</th><th>Target URL</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recent_logs)): ?><tr><td colspan="4" class="text-center py-5 text-muted">Awaiting log file upload...</td></tr><?php endif; ?>
                            <?php foreach($recent_logs as $log): 
                                $badge = $log['status_code'] < 300 ? 'st-200' : ($log['status_code'] < 400 ? 'st-300' : ($log['status_code'] < 500 ? 'st-400' : 'st-500'));
                            ?>
                            <tr>
                                <td class="ps-4 text-muted small fw-bold"><?= date('M d, H:i', strtotime($log['crawl_date'])) ?></td>
                                <td><span class="bot-badge"><i class="fas fa-robot text-muted me-1"></i> <?= htmlspecialchars($log['bot_type']) ?></span></td>
                                <td><a href="<?= htmlspecialchars($log['crawl_url']) ?>" target="_blank" class="url-cell" style="max-width: 150px;"><?= htmlspecialchars($log['crawl_url']) ?></a></td>
                                <td><span class="status-badge <?= $badge ?>"><?= $log['status_code'] ?></span></td>
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
    // Real Upload Spinner
    function showUploadSpinner() {
        const btn = document.getElementById('uploadBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Analyzing Logs...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.8';
        document.getElementById('logForm').submit();
    }

    <?php if(!empty($timeline_hits)): ?>
    // Timeline Chart
    const ctxTime = document.getElementById('crawlTimelineChart').getContext('2d');
    let gradTime = ctxTime.createLinearGradient(0, 0, 0, 300);
    gradTime.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradTime.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctxTime, {
        type: 'line',
        data: {
            labels: <?= json_encode($timeline_dates) ?>,
            datasets: [{
                label: 'Search Engine Hits',
                data: <?= json_encode($timeline_hits) ?>,
                borderColor: '#6366f1', backgroundColor: gradTime, borderWidth: 3,
                pointBackgroundColor: '#fff', pointBorderColor: '#6366f1', pointBorderWidth: 2, pointRadius: 5, fill: true, tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { weight: 'bold' } } },
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if(!empty($status_data)): ?>
    // Status Code Doughnut Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    const statusKeys = <?= json_encode(array_keys($status_data)) ?>;
    const statusVals = <?= json_encode(array_values($status_data)) ?>;
    
    // Auto color based on status code range
    const bgColors = statusKeys.map(code => {
        if(code < 300) return '#10b981'; // Green
        if(code < 400) return '#f59e0b'; // Yellow
        if(code < 500) return '#ef4444'; // Red
        return '#7f1d1d'; // Dark Red
    });

    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: statusKeys.map(k => 'HTTP ' + k),
            datasets: [{
                data: statusVals,
                backgroundColor: bgColors,
                borderWidth: 0,
                hoverOffset: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { position: 'bottom', labels: { font: { weight: 'bold' } } } }
        }
    });
    <?php endif; ?>
</script>

<?php require_once '_footer.php'; ?>
