<?php
/**
 * ==============================================================================
 * 🚀 ADVANCED SEO RANK TRACKER - CRON JOB ENGINE
 * File: panel/crons/cron_seo_tracker.php
 * Warning: Keep this file secure. It runs background tasks automatically.
 * ==============================================================================
 */

// 1. Core Server Settings for Background Tasks
set_time_limit(0);         // Remove execution time limit
ignore_user_abort(true);   // Continue executing even if the HTTP connection breaks
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from direct browser view, log them instead

// 2. Include Database Connection (Path goes one level up to 'panel', then to 'includes')
$dbPath = dirname(__DIR__) . '/../includes/db.php';
if (!file_exists($dbPath)) {
    die("CRITICAL ERROR: Database file not found at $dbPath\n");
}
require_once $dbPath;

// 3. Automated Logging System
function logTrackerAction($message, $type = "INFO") {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
    
    $log_file = $log_dir . '/cron_seo_tracker.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Also echo for direct CLI/Browser testing
    echo nl2br($log_entry);
}

logTrackerAction("Cron Engine Initialized. Starting Rank Tracker...", "START");

// 4. API & Target Configuration
$api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
$target_domain = 'likexfollow.com'; // Auto-tracking this domain
$current_date = date('Y-m-d');
$db_region = 'us'; // Target region (us, pk, uk, in)
$display_limit = 100; // Fetch top 100 keywords to save memory & API credits

try {
    logTrackerAction("Connecting to SEMrush Live API for domain: $target_domain (Region: $db_region)", "INFO");
    
    // API Endpoint: Domain Organic Search Positions
    $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($target_domain) . "&export_columns=Ph,Po,Ur&database=" . urlencode($db_region) . "&display_limit=" . $display_limit;
    
    // 5. Execute Highly Optimized cURL Request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'LikexFollow Auto-Cron Engine/1.0');
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // 6. Response Validation & Error Handling
    if ($response === false) {
        throw new Exception("cURL Error: " . $curl_error);
    }
    
    if ($http_status !== 200 || strpos($response, 'ERROR') === 0) {
        throw new Exception("API Response Error (HTTP $http_status): " . trim($response));
    }

    logTrackerAction("API Data received successfully. Analyzing payload...", "SUCCESS");

    // 7. Data Parsing & Injection
    $lines = explode("\n", trim($response));
    array_shift($lines); // Remove header row
    
    if (empty($lines) || (count($lines) == 1 && empty(trim($lines[0])))) {
        logTrackerAction("No ranking data found for $target_domain today.", "WARNING");
        exit;
    }

    $db->beginTransaction();
    logTrackerAction("Database Transaction Started.", "INFO");
    
    // Prepared Statement (ON DUPLICATE KEY ensures we don't spam the DB if cron runs twice a day)
    $stmt = $db->prepare("INSERT INTO semrush_rankings (keyword, position, url, track_date) 
                          VALUES (?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE position=?, url=?");
    
    $processed_count = 0;
    $top_3 = 0;
    $top_10 = 0;

    foreach($lines as $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line, ";");
        if (count($data) >= 3 && !empty(trim($data[0]))) {
            $kw = htmlspecialchars(strip_tags(trim($data[0])));
            $pos = (int)$data[1];
            $url = htmlspecialchars(strip_tags(trim($data[2])));
            
            // Execute Injection
            $stmt->execute([$kw, $pos, $url, $current_date, $pos, $url]);
            $processed_count++;

            // Analytics logic
            if ($pos <= 3) $top_3++;
            if ($pos <= 10) $top_10++;
        }
    }
    
    // Commit the data securely
    $db->commit();
    logTrackerAction("Transaction Committed securely.", "INFO");
    
    // Final Summary Log
    logTrackerAction("✅ Tracker Job Completed Successfully!", "SUCCESS");
    logTrackerAction("📊 Stats: Tracked $processed_count Keywords | Top 3: $top_3 | Top 10: $top_10", "METRICS");

} catch (Exception $e) {
    // If anything fails, rollback the DB so no corrupt data is saved
    if(isset($db) && $db->inTransaction()) {
        $db->rollBack();
        logTrackerAction("Transaction Rolled Back due to error.", "WARNING");
    }
    logTrackerAction($e->getMessage(), "ERROR");
}

logTrackerAction("Engine Shutdown.", "STOP");
echo "<hr><strong style='color:green;'>Cron Job Execution Finished. Check /logs/cron_seo_tracker.log for details.</strong>";
?>