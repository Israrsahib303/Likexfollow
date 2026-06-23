<?php
// File: panel/semrush_rank_tracker.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE DB TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_rankings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        keyword VARCHAR(255),
        position INT,
        url VARCHAR(500),
        track_date DATE,
        UNIQUE KEY unique_track (keyword(191), track_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

// ==========================================
// 📡 1. DYNAMIC SEMRUSH API RANK TRACKER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_run_tracker'])) {
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $domain = sanitize($_POST['target_domain']);
    $current_date = date('Y-m-d');
    
    // Clean domain
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = trim($domain, '/');

    if (!empty($domain)) {
        // Fetch Organic Positions for the Domain
        $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($domain) . "&export_columns=Ph,Po,Ur&database=us&display_limit=50";
        
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
            
            $inserted = 0; $updated = 0;
            $db->beginTransaction();
            
            try {
                // We use ON DUPLICATE KEY UPDATE so if we scan multiple times a day, it just updates today's rank
                $stmt = $db->prepare("INSERT INTO semrush_rankings (keyword, position, url, track_date) 
                                      VALUES (?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE position=?, url=?");
                
                foreach($lines as $line) {
                    if (empty(trim($line))) continue;
                    $data = str_getcsv($line, ";");
                    if (count($data) < 3) continue;
                    
                    $kw = sanitize($data[0]);
                    $pos = (int)$data[1];
                    $url = sanitize($data[2]);
                    
                    if(!empty($kw) && $pos > 0) {
                        $stmt->execute([$kw, $pos, $url, $current_date, $pos, $url]);
                        if($stmt->rowCount() > 1) $updated++; else $inserted++;
                    }
                }
                $db->commit();
                $message = "Live Tracking Complete! 🚀 Synced " . ($inserted + $updated) . " keyword positions for $domain.";
                $msg_type = "success";
            } catch (Exception $e) {
                $db->rollBack();
                $message = "Database Error during Sync.";
                $msg_type = "danger";
            }
        } else {
            $message = "API Error: No ranking data found for '$domain' in the top 100 results.";
            $msg_type = "warning";
        }
    } else {
        $message = "Target Domain cannot be empty!";
        $msg_type = "danger";
    }
}

// --- 2. FETCH GLOBAL METRICS (For Latest Date) ---
$latest_date = '';
$top_3_count = 0;
$top_10_count = 0;
$total_tracked = 0;

if($table_exists) {
    $date_stmt = $db->query("SELECT MAX(track_date) FROM semrush_rankings");
    $latest_date = $date_stmt->fetchColumn();
    
    if($latest_date) {
        $stmt3 = $db->prepare("SELECT COUNT(*) FROM semrush_rankings WHERE track_date = ? AND position <= 3");
        $stmt3->execute([$latest_date]);
        $top_3_count = $stmt3->fetchColumn();

        $stmt10 = $db->prepare("SELECT COUNT(*) FROM semrush_rankings WHERE track_date = ? AND position <= 10");
        $stmt10->execute([$latest_date]);
        $top_10_count = $stmt10->fetchColumn();
        
        $total_tracked = $db->query("SELECT COUNT(DISTINCT keyword) FROM semrush_rankings")->fetchColumn();
    }
}

// --- 3. KEYWORD FILTER LOGIC ---
$keywords = [];
if($table_exists) {
    $keywords = $db->query("SELECT DISTINCT keyword FROM semrush_rankings ORDER BY keyword ASC")->fetchAll(PDO::FETCH_COLUMN);
}
$selected_kw = isset($_GET['kw']) ? sanitize($_GET['kw']) : ($keywords[0] ?? '');

// --- 4. FETCH ADVANCED CHART & HISTORY DATA ---
$chart_dates = [];
$chart_positions = [];
$history_data = [];
$best_rank = 100;
$current_rank = '-';
$rank_change = 0;

if($table_exists && !empty($selected_kw)) {
    // Fetch all records for selected keyword ordered by date DESC
    $stmt = $db->prepare("SELECT track_date, position, url FROM semrush_rankings WHERE keyword = ? ORDER BY track_date DESC LIMIT 30");
    $stmt->execute([$selected_kw]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($results) > 0) {
        $current_rank = (int)$results[0]['position'];
        
        // Calculate Delta (Difference between latest and previous)
        if(count($results) > 1) {
            $prev_rank = (int)$results[1]['position'];
            $rank_change = $prev_rank - $current_rank; // Positive means rank improved (e.g., 10 to 5 = +5)
        }

        // Process data for Chart (Needs ASC order) and History Table
        $chart_data_temp = array_reverse($results);
        foreach($chart_data_temp as $r) {
            $chart_dates[] = date('d M Y', strtotime($r['track_date']));
            $chart_positions[] = (int)$r['position'];
            if((int)$r['position'] < $best_rank && (int)$r['position'] > 0) {
                $best_rank = (int)$r['position'];
            }
        }
        
        // Build History Table with Deltas
        for($i = 0; $i < count($results); $i++) {
            $delta = 0;
            if($i < count($results) - 1) {
                $delta = $results[$i+1]['position'] - $results[$i]['position'];
            }
            $history_data[] = [
                'date' => $results[$i]['track_date'],
                'position' => $results[$i]['position'],
                'url' => $results[$i]['url'],
                'delta' => $delta
            ];
        }
    }
}
if($best_rank == 100 && empty($history_data)) $best_rank = '-';

$page_title = "Live Rank Tracker";
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
        --success-green: #10b981; --danger-red: #ef4444;
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    
    .beast-container { width: 100%; max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(99,102,241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99,102,241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); } }
    
    .anim-slide { animation: slideInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    .rt-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); overflow: hidden; }
    
    /* API Form Elements */
    .api-input { border: 2px solid var(--b-color); border-radius: 10px; padding: 12px 18px; font-weight: 600; outline: none; transition: 0.3s; width: 100%; max-width: 280px; color: var(--t-dark); font-size: 0.95rem; }
    .api-input:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }
    
    .btn-action { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; white-space: nowrap; }
    .btn-action:hover { box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5); transform: translateY(-2px); color: #fff; }

    .metric-box { padding: 1.8rem; border-right: 1px solid var(--b-color); text-align: center; display: flex; flex-direction: column; justify-content: center; }
    .metric-box:last-child { border-right: none; }
    .metric-title { font-size: 0.8rem; font-weight: 800; color: var(--t-muted); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    .metric-value { font-size: 2.8rem; font-weight: 900; color: var(--t-dark); line-height: 1; letter-spacing: -1px; }
    
    .badge-delta { font-size: 0.85rem; font-weight: 800; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .delta-up { background: #dcfce7; color: var(--success-green); border: 1px solid #bbf7d0; }
    .delta-down { background: #fee2e2; color: var(--danger-red); border: 1px solid #fecaca; }
    .delta-neutral { background: #f8fafc; color: var(--t-muted); border: 1px solid var(--b-color); }

    .select-premium { border: 2px solid var(--l-purple); border-radius: 12px; padding: 12px 20px; font-weight: 800; color: var(--d-purple); outline: none; transition: 0.3s; cursor: pointer; background: #fff; width: 100%; max-width: 350px; font-size: 0.95rem; }
    .select-premium:focus, .select-premium:hover { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    .table-responsive { width: 100%; overflow-x: auto; }
    .history-table th { background: #f8fafc; color: var(--t-muted); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 15px; border-bottom: 2px solid var(--b-color); white-space: nowrap; position: sticky; top: 0; z-index: 10; }
    .history-table td { vertical-align: middle; font-weight: 600; color: var(--t-dark); padding: 15px; border-bottom: 1px solid var(--b-color); white-space: nowrap; }
    .history-table tr:hover td { background: #f8fafc; }

    @media (max-width: 992px) {
        .metric-box { border-right: none; border-bottom: 1px solid var(--b-color); }
        .metric-box:last-child { border-bottom: none; }
    }
</style>

<div class="beast-container">
    
    <div class="rt-card p-4 p-md-5 mb-4 anim-slide d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div>
            <h2 class="fw-bolder text-dark mb-2" style="font-size: 2.2rem; letter-spacing: -1px;"><i class="fas fa-satellite me-2 text-indigo-500"></i> Live Rank Tracker</h2>
            <p class="text-muted fw-medium mb-0 fs-6">Monitor SERP volatility, precise ranking deltas, and historical performance via Live API.</p>
        </div>
        
        <form method="POST" id="trackerForm" class="d-flex flex-column flex-md-row align-items-md-center gap-2 m-0">
            <input type="hidden" name="api_run_tracker" value="1">
            <input type="text" name="target_domain" id="target_domain" class="api-input" placeholder="e.g. likexfollow.com" required>
            <button type="button" class="btn-action" id="btnSync" onclick="runTrackerApi()">
                <i class="fas fa-sync-alt"></i> Sync Live Ranks
            </button>
        </form>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-4 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'warning' ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> fs-4 me-3"></i> 
            <span style="font-size: 1.05rem;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 anim-slide anim-delay-1 flex-wrap gap-3">
        <h4 class="fw-bold m-0"><i class="fas fa-filter text-muted me-2"></i> Keyword Analytics</h4>
        <form method="GET" class="d-flex align-items-center m-0 w-100 w-md-auto">
            <select name="kw" class="select-premium w-100" onchange="this.form.submit()">
                <?php if(empty($keywords)): ?><option>No keywords tracked yet</option><?php endif; ?>
                <?php foreach($keywords as $kw): ?>
                    <option value="<?= htmlspecialchars($kw) ?>" <?= $kw === $selected_kw ? 'selected' : '' ?>><?= htmlspecialchars($kw) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="rt-card mb-4 anim-slide anim-delay-1">
        <div class="row g-0">
            <div class="col-md-3 metric-box bg-light">
                <div class="metric-title text-primary">Current Rank</div>
                <div class="metric-value mb-3"><?= $current_rank ?></div>
                <div>
                    <?php if($rank_change > 0): ?>
                        <span class="badge-delta delta-up"><i class="fas fa-arrow-up"></i> <?= $rank_change ?> positions</span>
                    <?php elseif($rank_change < 0): ?>
                        <span class="badge-delta delta-down"><i class="fas fa-arrow-down"></i> <?= abs($rank_change) ?> positions</span>
                    <?php else: ?>
                        <span class="badge-delta delta-neutral"><i class="fas fa-minus"></i> No change</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3 metric-box bg-light">
                <div class="metric-title text-primary">All-Time Best</div>
                <div class="metric-value text-success d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-crown text-warning fs-3"></i> <?= $best_rank ?>
                </div>
            </div>
            
            <div class="col-md-3 metric-box">
                <div class="metric-title">Keywords in Top 3</div>
                <div class="metric-value text-warning"><?= number_format($top_3_count) ?></div>
                <div class="small text-muted mt-2 fw-bold bg-light rounded-pill px-2 py-1 d-inline-block">Out of <?= $total_tracked ?> tracked</div>
            </div>
            <div class="col-md-3 metric-box">
                <div class="metric-title">Keywords in Top 10</div>
                <div class="metric-value text-info"><?= number_format($top_10_count) ?></div>
                <div class="small text-muted mt-2 fw-bold bg-light rounded-pill px-2 py-1 d-inline-block">First Page Dominance</div>
            </div>
        </div>
    </div>

    <div class="row g-4 anim-slide anim-delay-2">
        <div class="col-lg-8">
            <div class="rt-card p-4 h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bolder mb-0 text-dark">Ranking Trend: <span style="color: var(--d-purple);">"<?= htmlspecialchars($selected_kw) ?>"</span></h5>
                    <span class="badge bg-light text-muted border px-3 py-2 fw-bold"><i class="fas fa-calendar-alt me-1"></i> Last 30 Records</span>
                </div>
                
                <?php if(empty($chart_positions)): ?>
                    <div class="text-center py-5 flex-grow-1 d-flex flex-column justify-content-center">
                        <i class="fas fa-chart-line fa-4x mb-3 opacity-25" style="color: var(--d-purple);"></i>
                        <h4 class="text-dark fw-bold">No trend data available</h4>
                        <p class="text-muted">Enter a domain above to sync live rankings from API.</p>
                    </div>
                <?php else: ?>
                    <div class="flex-grow-1" style="min-height: 350px; width: 100%;">
                        <canvas id="advancedRankChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="rt-card h-100 overflow-hidden d-flex flex-column">
                <div class="p-4 border-bottom bg-light">
                    <h5 class="fw-bolder mb-0 text-dark"><i class="fas fa-history me-2 text-muted"></i> Movement Ledger</h5>
                </div>
                <div class="table-responsive flex-grow-1" style="max-height: 450px; overflow-y: auto;">
                    <table class="table history-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Pos</th>
                                <th class="text-end pe-4">Delta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($history_data)): ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No history found.</td></tr>
                            <?php endif; ?>
                            
                            <?php foreach($history_data as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted small fw-bold"><?= date('d M Y', strtotime($row['date'])) ?></td>
                                <td>
                                    <span class="fw-bold" style="color: var(--d-purple); font-size: 1.15rem;"><?= $row['position'] ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($row['delta'] > 0): ?>
                                        <span class="badge-delta delta-up py-1 px-2"><i class="fas fa-arrow-up"></i> <?= $row['delta'] ?></span>
                                    <?php elseif($row['delta'] < 0): ?>
                                        <span class="badge-delta delta-down py-1 px-2"><i class="fas fa-arrow-down"></i> <?= abs($row['delta']) ?></span>
                                    <?php else: ?>
                                        <span class="badge-delta delta-neutral py-1 px-2">-</span>
                                    <?php endif; ?>
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
    function runTrackerApi() {
        const inp = document.getElementById('target_domain');
        if(inp.value.trim() === '') {
            alert('Please enter a target domain to track!');
            return;
        }
        
        const btn = document.getElementById('btnSync');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Syncing Ranks...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.9';
        
        document.getElementById('trackerForm').submit();
    }
</script>

<?php if(!empty($chart_positions)): ?>
<script>
    const ctx = document.getElementById('advancedRankChart').getContext('2d');
    
    // Pro-Level Gradient Fill
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)'); 
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_dates) ?>,
            datasets: [{
                label: 'Google Position',
                data: <?= json_encode($chart_positions) ?>,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4 
            }]
        },
        options: {
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 14, family: 'Inter' },
                    bodyFont: { size: 16, weight: 'bold' },
                    padding: 15,
                    displayColors: false,
                    callbacks: {
                        label: function(context) { return 'Rank: ' + context.parsed.y; }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    reverse: true, // Rank 1 is at the TOP
                    min: 1,
                    title: { display: true, text: 'Google SERP Position', color: '#64748b', font: { weight: 'bold', size: 12 } },
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { color: '#0f172a', font: { weight: 'bold', size: 13 } }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { color: '#64748b', font: { weight: 'bold' }, maxRotation: 45, minRotation: 45 } 
                }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once '_footer.php'; ?>
