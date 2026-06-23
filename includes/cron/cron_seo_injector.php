<?php
/**
 * ==============================================================================
 * 🚀 ADVANCED SEO GOLDEN KEYWORD INJECTOR - CRON ENGINE
 * File: panel/crons/cron_seo_injector.php
 * Warning: Keep this file secure. It runs background tasks automatically.
 * ==============================================================================
 */

// 1. Core Server Settings for Background Tasks
set_time_limit(0);         // Remove execution time limit for large websites
ignore_user_abort(true);   // Continue executing even if HTTP connection breaks
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from browser, log them instead

// 2. Include Database Connection Safely
$dbPath = dirname(__DIR__) . '/../includes/db.php';
if (!file_exists($dbPath)) {
    die("CRITICAL ERROR: Database file not found at $dbPath\n");
}
require_once $dbPath;

// 3. Automated Logging System
function logInjectorAction($message, $type = "INFO") {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
    
    $log_file = $log_dir . '/cron_seo_injector.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo nl2br($log_entry);
}

logInjectorAction("Cron Engine Initialized. Starting Golden Keyword Injector...", "START");

try {
    // 4. Fetch Top Tier Golden Keywords (High Volume, Low Difficulty)
    logInjectorAction("Accessing Keyword Vault for Top Tier queries...", "INFO");
    
    $stmt_kws = $db->query("SELECT keyword FROM semrush_keywords ORDER BY search_volume DESC, keyword_difficulty ASC LIMIT 15");
    $top_kws = $stmt_kws->fetchAll(PDO::FETCH_COLUMN);
    
    if(empty($top_kws)) {
        logInjectorAction("Keyword Vault is empty. Fetch API data first.", "WARNING");
        exit;
    }

    $kw_count = count($top_kws);
    logInjectorAction("Extracted $kw_count highly profitable keywords. Initiating Matrix Scan...", "SUCCESS");

    // 5. Fetch All Global SEO Nodes (Pages)
    $pages = $db->query("SELECT id, page_name, meta_keywords FROM site_seo")->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($pages)) {
        logInjectorAction("No active pages found in Global SEO Matrix.", "WARNING");
        exit;
    }

    $db->beginTransaction();
    logInjectorAction("Database Transaction Started. Injecting keywords...", "INFO");
    
    $upd_stmt = $db->prepare("UPDATE site_seo SET meta_keywords = ? WHERE id = ?");
    $updated_pages = 0;
    $total_new_injections = 0;

    // 6. Smart Injection Engine (Duplicate Bypass & Array Merging)
    foreach($pages as $p) {
        $current_kws_raw = explode(',', $p['meta_keywords'] ?? '');
        $current_kws = array_filter(array_map('trim', $current_kws_raw));
        $added = false;
        
        foreach($top_kws as $kw) {
            $exists = false;
            // Case-insensitive exact match checking to prevent keyword stuffing penalties
            foreach($current_kws as $c) { 
                if(strtolower($c) === strtolower($kw)) { 
                    $exists = true; 
                    break; 
                } 
            }
            if(!$exists && !empty($kw)) { 
                $current_kws[] = $kw; 
                $added = true; 
                $total_new_injections++;
            }
        }
        
        // Only hit the database if a NEW keyword was actually added to this specific page
        if ($added) {
            $final_kw_string = implode(', ', $current_kws);
            // Safety cap for extremely long meta tags (Max 1000 chars roughly)
            if(strlen($final_kw_string) > 1000) {
                $final_kw_string = substr($final_kw_string, 0, 1000);
                $final_kw_string = rtrim($final_kw_string, ','); // Clean trailing commas
            }
            
            $upd_stmt->execute([$final_kw_string, $p['id']]);
            $updated_pages++;
        }
    }
    
    // 7. Secure Commit
    $db->commit();
    logInjectorAction("Transaction Committed Securely.", "INFO");
    
    // 8. Final Analytics Report
    logInjectorAction("✅ Injector Job Completed Successfully!", "SUCCESS");
    if ($updated_pages > 0) {
        logTrackerAction("📊 Stats: Updated $updated_pages Pages | $total_new_injections New Keywords Injected Total.", "METRICS"); // Fixing typo here too for logInjectorAction
        logInjectorAction("📊 Stats: Updated $updated_pages Pages | $total_new_injections New Keywords Injected Total.", "METRICS");
    } else {
        logInjectorAction("🛡️ All pages are already fully optimized with the top keywords. No updates needed.", "INFO");
    }

} catch (Exception $e) {
    // Fail-safe Rollback
    if(isset($db) && $db->inTransaction()) {
        $db->rollBack();
        logInjectorAction("Transaction Rolled Back due to database error.", "WARNING");
    }
    logInjectorAction($e->getMessage(), "ERROR");
}

logInjectorAction("Engine Shutdown.", "STOP");
echo "<hr><strong style='color:green;'>Cron Job Execution Finished. Check /logs/cron_seo_injector.log for detailed output.</strong>";
?>