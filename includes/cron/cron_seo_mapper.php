<?php
/**
 * ==============================================================================
 * 🚀 ADVANCED SEO MAPPER - CRON ENGINE (Auto Meta Generator)
 * File: panel/crons/cron_seo_mapper.php
 * Warning: Keep this file secure. It runs background tasks automatically.
 * ==============================================================================
 */

// 1. Core Server Settings for Background Tasks
set_time_limit(0);         // Remove execution time limit
ignore_user_abort(true);   // Continue executing even if HTTP connection breaks
error_reporting(E_ALL);
ini_set('display_errors', 0); 

// 2. Include Database Connection
$dbPath = dirname(__DIR__) . '/../includes/db.php';
if (!file_exists($dbPath)) {
    die("CRITICAL ERROR: Database file not found at $dbPath\n");
}
require_once $dbPath;

// 3. Automated Logging System
function logMapperAction($message, $type = "INFO") {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
    
    $log_file = $log_dir . '/cron_seo_mapper.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo nl2br($log_entry);
}

logMapperAction("Cron Engine Initialized. Starting Auto SEO Mapper...", "START");

$api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
$batch_size = 10; // Process 10 pages per run to prevent API timeouts

try {
    // 4. Find pages missing Meta Title, Description, or Keywords
    logMapperAction("Scanning Global Matrix for unoptimized pages (Limit: $batch_size)...", "INFO");
    
    $empty_pages = $db->query("SELECT id, page_name, page_url FROM site_seo WHERE meta_title = '' OR meta_description = '' OR meta_keywords = '' LIMIT $batch_size")->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($empty_pages)) {
        logMapperAction("✅ All pages are fully mapped and optimized. No blanks found.", "SUCCESS");
        exit;
    }

    $db->beginTransaction();
    $upd_stmt = $db->prepare("UPDATE site_seo SET meta_title = ?, meta_description = ?, meta_keywords = ? WHERE id = ?");
    $mapped_count = 0;

    foreach($empty_pages as $page) {
        // 5. Smart Seed Keyword Extraction from URL or Name
        $raw_seed = str_replace(['.php', '/', '-', '_'], ' ', strtolower($page['page_url']));
        $raw_seed = trim(str_replace(['page', 'list', 'index', 'home'], '', $raw_seed));
        
        if(empty($raw_seed) || strlen($raw_seed) < 3) {
            $raw_seed = strtolower(str_replace(['page', 'list'], '', $page['page_name']));
        }
        if(empty(trim($raw_seed))) {
            $raw_seed = 'smm panel'; // Ultimate fallback
        }
        
        $seed = trim($raw_seed);
        logMapperAction("Processing Page ID: {$page['id']} | Smart Seed: '$seed'", "PROCESS");

        // 6. SEMrush API Call for LSI Keywords
        $api_url = "https://api.semrush.com/?type=phrase_related&key=" . urlencode($api_key) . "&phrase=" . urlencode($seed) . "&export_columns=Ph&database=us&display_limit=5";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $kws = [$seed]; // Always include the exact seed
        
        if ($response && strpos($response, 'ERROR') !== 0) {
            $lines = explode("\n", trim($response));
            array_shift($lines);
            foreach($lines as $line) {
                $kw = trim(str_getcsv($line, ";")[0] ?? '');
                if(!empty($kw)) $kws[] = $kw;
            }
        } else {
            // Algorithmic Keyword Fallback if API is unreachable
            $kws = array_merge($kws, ['best ' . $seed, 'cheap ' . $seed, 'buy ' . $seed, $seed . ' pakistan', 'premium ' . $seed]);
            logMapperAction("API restricted for '$seed'. Used algorithmic fallback.", "WARNING");
        }
        
        $kws = array_unique($kws);
        $top_3_kws = implode(", ", array_slice($kws, 0, 3));
        $all_kws_string = implode(', ', $kws);

        // 7. Smart AI Content Generation Logic
        $titleCase = ucwords($seed);
        $year = date("Y");
        
        $auto_title = "{$titleCase} Services {$year} - LikexFollow";
        $auto_desc = "Get the best {$seed} with LikexFollow. We provide instant, premium, and cheap {$top_3_kws} to boost your digital growth safely.";
        
        // Contextual Overrides based on page type
        if (strpos(strtolower($page['page_name']), 'about') !== false) {
            $auto_title = "About Us | Experts in {$titleCase} - LikexFollow";
            $auto_desc = "Learn how LikexFollow became the #1 provider for {$seed}. We specialize in {$top_3_kws} and global digital dominance.";
        } elseif (strpos(strtolower($page['page_name']), 'contact') !== false || strpos(strtolower($page['page_url']), 'contact') !== false) {
            $auto_title = "Contact LikexFollow | Support for {$titleCase}";
            $auto_desc = "Need help with your {$seed} order? Contact our 24/7 expert support team for queries related to {$top_3_kws}.";
        } elseif (strpos(strtolower($page['page_name']), 'faq') !== false) {
            $auto_title = "FAQ - {$titleCase} Queries Answered | LikexFollow";
            $auto_desc = "Find all the answers regarding {$seed}, our refill policies, and how to get the best {$top_3_kws} safely.";
        }

        // 8. Execute Database Update
        $upd_stmt->execute([$auto_title, $auto_desc, $all_kws_string, $page['id']]);
        $mapped_count++;
        
        // Respect API rate limits
        sleep(1); 
    }

    $db->commit();
    logMapperAction("Transaction Committed Securely.", "INFO");
    logMapperAction("✅ [SEO Mapper] Successfully mapped & generated meta tags for $mapped_count blank pages.", "SUCCESS");

} catch (Exception $e) {
    if(isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    logMapperAction("❌ [SEO Mapper] Error: " . $e->getMessage(), "ERROR");
}

logMapperAction("Engine Shutdown.", "STOP");
echo "<hr><strong style='color:green;'>Cron Job Execution Finished. Check /logs/cron_seo_mapper.log for detailed output.</strong>";
?>