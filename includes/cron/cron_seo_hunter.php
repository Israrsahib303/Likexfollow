<?php
/**
 * ==============================================================================
 * 🚀 ADVANCED SEO HUNTER - CRON ENGINE (Auto Competitor Spy)
 * File: panel/crons/cron_seo_hunter.php
 * Warning: Keep this file secure. It runs background tasks automatically.
 * ==============================================================================
 */

// 1. Core Server Settings for Heavy Background Tasks
set_time_limit(0);         // Remove execution time limit
ignore_user_abort(true);   // Continue executing even if HTTP connection breaks
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from browser, log them securely

// 2. Include Database Connection
$dbPath = dirname(__DIR__) . '/../includes/db.php';
if (!file_exists($dbPath)) {
    die("CRITICAL ERROR: Database file not found at $dbPath\n");
}
require_once $dbPath;

// 3. Automated Logging System
function logHunterAction($message, $type = "INFO") {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
    
    $log_file = $log_dir . '/cron_seo_hunter.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo nl2br($log_entry);
}

logHunterAction("Cron Engine Initialized. Starting SEO Hunter (Competitor Spy)...", "START");

$api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
$current_date = date('Y-m-d');
$db_region = 'us'; // Default hunting region
$display_limit = 50; // Steal top 50 keywords per run to save credits & memory

try {
    // 4. Smart Auto-Target Selection (Find the biggest threat currently in DB)
    logHunterAction("Analyzing Radar for the biggest competitor threat...", "ANALYSIS");
    
    $stmt_threat = $db->query("SELECT competitor_domain FROM semrush_competitor_data GROUP BY competitor_domain ORDER BY SUM(traffic_share) DESC LIMIT 1");
    $top_rival = $stmt_threat->fetchColumn();
    
    if(!$top_rival) {
        // Fallback target if database is completely empty
        $top_rival = 'smmfollows.com'; 
        logHunterAction("Vault empty. Defaulting to initial target: $top_rival", "WARNING");
    } else {
        logHunterAction("Target Locked: $top_rival", "SUCCESS");
    }

    // 5. Connect to SEMrush Organic Research API
    $api_url = "https://api.semrush.com/?type=domain_organic&key=" . urlencode($api_key) . "&domain=" . urlencode($top_rival) . "&export_columns=Ph,Po,Nq,Tr,Cp,Ur&database=" . urlencode($db_region) . "&display_limit=" . $display_limit;
    
    logHunterAction("Initiating API infiltration on $top_rival...", "INFO");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'LikexFollow Auto-Cron Hunter/1.0');
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("cURL Connection Error: " . $curl_error);
    }

    if ($http_status !== 200 || strpos($response, 'ERROR') === 0) {
        throw new Exception("Target evaded scan or API Error (HTTP $http_status): " . trim($response));
    }

    // 6. Data Parsing & Injection
    $lines = explode("\n", trim($response));
    array_shift($lines); // Remove header
    
    if (empty($lines) || (count($lines) == 1 && empty(trim($lines[0])))) {
        logHunterAction("Target has no valuable organic data today. Aborting raid.", "WARNING");
        exit;
    }

    $db->beginTransaction();
    logHunterAction("Transaction Started. Extracting golden keywords...", "INFO");

    $stmt = $db->prepare("INSERT INTO semrush_competitor_data (competitor_domain, keyword, position, search_volume, traffic_share, cpc, url, data_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE position=?, search_volume=?, traffic_share=?, cpc=?, url=?");
    
    $hunted = 0;
    $updated = 0;

    foreach($lines as $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line, ";");
        if (count($data) >= 6 && !empty(trim($data[0]))) {
            
            // Limit strings for DB safety
            $kw = htmlspecialchars(strip_tags(trim(substr($data[0], 0, 191)))); 
            $pos = (int)$data[1]; 
            $vol = (int)$data[2]; 
            $traffic = (float)$data[3]; 
            $cpc = (float)$data[4]; 
            $url = htmlspecialchars(strip_tags(trim(substr($data[5], 0, 490))));
            
            // Determine if insert or update (for logs)
            $check = $db->prepare("SELECT id FROM semrush_competitor_data WHERE competitor_domain = ? AND keyword = ? AND data_date = ?");
            $check->execute([$top_rival, $kw, $current_date]);
            if($check->fetchColumn()) { $updated++; } else { $hunted++; }

            $stmt->execute([$top_rival, $kw, $pos, $vol, $traffic, $cpc, $url, $current_date, $pos, $vol, $traffic, $cpc, $url]);
        }
    }
    
    // 7. Secure Commit
    $db->commit();
    logHunterAction("Data secured in vault.", "SUCCESS");
    
    // Final Analytics Log
    logHunterAction("✅ Hunter Raid Completed Successfully!", "SUCCESS");
    logHunterAction("📊 Loot Report: $hunted New Keywords Stolen | $updated Keywords Updated from $top_rival.", "METRICS");

} catch (Exception $e) {
    if(isset($db) && $db->inTransaction()) {
        $db->rollBack();
        logHunterAction("Transaction Rolled Back to protect database integrity.", "WARNING");
    }
    logHunterAction($e->getMessage(), "ERROR");
}

logHunterAction("Hunter Engine Shutdown.", "STOP");
echo "<hr><strong style='color:green;'>Cron Job Execution Finished. Check /logs/cron_seo_hunter.log for detailed output.</strong>";
?>