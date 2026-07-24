<?php
// --- CRON JOB: SMM SERVICE SYNC (SECURE & DEBUG MODE) ---

// 1. Session Start (Admin Check ke liye)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SECURITY CHECK (End Level)
// Allow only if running from CLI (Server) OR Logged in Admin (Ghost Mode)
$is_cli = (php_sapi_name() === 'cli' || !isset($_SERVER['REMOTE_ADDR']));
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1 && isset($_SESSION['ghost_access']) && $_SESSION['ghost_access'] === true);

if (!$is_cli && !$is_admin) {
    header('HTTP/1.0 403 Forbidden');
    die("Access Denied: You are not authorized to run this cron manually.");
}

// 3. Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../assets/logs/php_error.log');

// 4. Time & Memory
set_time_limit(300); // 5 Minutes max (Sync heavy hota hai)
ini_set('memory_limit', '512M');

// 5. Absolute Paths
$base_path = dirname(dirname(__DIR__));
$log_dir = $base_path . '/assets/logs';
$log_file = $log_dir . '/smm_service_sync.log';

// --- AUTO CREATE LOG FOLDER IF NOT EXISTS ---
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function writeLog($msg) {
    global $log_file;
    $entry = "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    // Force write
    if (!@file_put_contents($log_file, $entry, FILE_APPEND)) {
        // Fallback for debugging
        echo "LOG ERROR: Could not write to $log_file. Msg: $msg <br>";
    }
}

writeLog("--- SYNC STARTED ---");

try {
    if (!file_exists($base_path . '/includes/config.php')) {
        throw new Exception("Config file not found.");
    }
    
    // Directory Change
    chdir($base_path . '/includes');
    
    require_once 'config.php';
    require_once 'db.php';
    require_once 'helpers.php';
    require_once 'smm_api.class.php';

    if (!$db) throw new Exception("Database connection failed.");

    // --- 1. TABLE CHECK (LOGGING KE LIYE ZAROORI HAI) ---
    $db->exec("CREATE TABLE IF NOT EXISTS `service_updates` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `service_id` int(11) DEFAULT NULL,
      `service_name` varchar(255) NOT NULL,
      `category_name` varchar(255) NOT NULL,
      `rate` decimal(10,4) NOT NULL,
      `type` enum('new','removed','enabled','price_increase','price_decrease') NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // --- 2. GET PERFECT RATE FROM DB ---
    // Yeh wohi rate uthayega jo smart_currency_sync.php ne set kiya hai
    $stmt_rate = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'currency_conversion_rate'");
    $stmt_rate->execute();
    $db_rate = $stmt_rate->fetchColumn();
    
    // Agar DB mein rate nahi mila to Default 280
    $usd_rate = ($db_rate > 0) ? (float)$db_rate : 280.00;
    
    writeLog("Syncing with Rate: 1 USD = $usd_rate PKR");

    $providers = $db->query("SELECT * FROM smm_providers WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    
    $new_count = 0; 
    $upd_count = 0;
    $rem_count = 0;

    foreach ($providers as $provider) {
        $api = new SmmApi($provider['api_url'], $provider['api_key']);
        $res = $api->getServices();
        
        if (!$res['success']) {
            writeLog("API Error ({$provider['name']}): " . ($res['error'] ?? 'Unknown'));
            continue;
        }
        
        $db->beginTransaction();
        $seen_ids = [];

        foreach ($res['services'] as $s) {
            if (empty($s['service']) || !isset($s['rate'])) continue;
            
            $sid = (int)$s['service'];
            $seen_ids[] = $sid;

            // ====================================================
            // 🚀 SMART DATA CLEANING & EXTRACTION LOGIC
            // ====================================================
            $name = sanitize($s['name']);
            $cat = sanitize($s['category']);
            $min = (int)$s['min'];
            $max = (int)$s['max'];
            $type = sanitize($s['type'] ?? 'Default');
            
            // 🧠 FIX AVG TIME
            $avg = $s['average_time'] ?? $s['avg_time'] ?? 'N/A';
            if (is_array($avg) || is_object($avg)) $avg = 'N/A';
            $avg = sanitize(trim(strip_tags((string)$avg)));

            // 🧠 FIX DESCRIPTION (Do NOT strip HTML tags completely)
            $raw_desc = $s['description'] ?? $s['desc'] ?? $s['details'] ?? $s['content'] ?? '';
            $desc = trim((string)$raw_desc); 
            
            // 🧠 FIX BOOLEAN FLAGS (Handle 1/0, true/false, "Yes"/"No")
            $refill = 0;
            if (isset($s['refill'])) {
                $r_val = strtolower(trim((string)$s['refill']));
                if ($r_val === '1' || $r_val === 'true' || $r_val === 'yes' || $r_val === true) $refill = 1;
            }

            $cancel = 0;
            if (isset($s['cancel'])) {
                $c_val = strtolower(trim((string)$s['cancel']));
                if ($c_val === '1' || $c_val === 'true' || $c_val === 'yes' || $c_val === true) $cancel = 1;
            }

            $drip = 0;
            if (isset($s['dripfeed'])) {
                $d_val = strtolower(trim((string)$s['dripfeed']));
                if ($d_val === '1' || $d_val === 'true' || $d_val === 'yes' || $d_val === true) $drip = 1;
            }

            // --- PRICE CALCULATION (EXACT MATCH) ---
            $rate_usd = (float)$s['rate'];
            
            // Base Price (Provider Rate in PKR)
            $base_price_pkr = $rate_usd * $usd_rate;
            
            // Selling Price (With your profit margin)
            $selling_price = $base_price_pkr * (1 + ($provider['profit_margin'] / 100));

            // DB Check (Fetch existing description, avg_time, refill, etc. too!)
            $stmt = $db->prepare("SELECT id, manually_deleted, description, avg_time, has_refill, has_cancel FROM smm_services WHERE provider_id=? AND service_id=?");
            $stmt->execute([$provider['id'], $sid]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['manually_deleted'] == 1) continue;

                // 1. Initial values set karo jo API se aai hain
                $final_desc = $desc;
                $final_refill = $refill;
                $final_cancel = $cancel;
                $final_avg = ($avg !== 'N/A' && $avg !== '') ? $avg : 'Instant / 1-2 Hours';

                // 🧠 2. SMART EXTRACTOR: Agar API ne description NAHI bheji (provider nakhre kar raha hai)
                if (empty($final_desc)) {
                    $name_lower = strtolower($name);
                    
                    // Naam se REFILL detect karo
                    if (strpos($name_lower, 'refill') !== false || strpos($name_lower, 'guarantee') !== false || strpos($name_lower, '♻️') !== false || strpos($name_lower, 'r30') !== false || strpos($name_lower, 'r60') !== false) {
                        $final_refill = 1;
                    }
                    
                    // Naam se CANCEL detect karo
                    if (strpos($name_lower, 'cancel') !== false || strpos($name_lower, '❌') !== false) {
                        $final_cancel = 1;
                    }

                    // Ek zordaar Auto-Description generate kar do
                    $refill_txt = $final_refill ? "✅ Yes (Guaranteed)" : "❌ No Refill (Non-Guaranteed)";
                    $cancel_txt = $final_cancel ? "✅ Available" : "❌ Not Available";
                    
                    $final_desc = "🔥 **Service Details:**\n";
                    $final_desc .= "🔹 **Start Time:** " . $final_avg . "\n";
                    $final_desc .= "♻️ **Refill:** " . $refill_txt . "\n";
                    $final_desc .= "🛑 **Cancel Option:** " . $cancel_txt . "\n";
                    $final_desc .= "⚡ **Speed:** Super Fast\n";
                    $final_desc .= "💎 **Quality:** High Quality Accounts\n\n";
                    $final_desc .= "*(Note: Information auto-extracted from provider)*";
                }

                // 3. Agar tumne Admin panel se manually koi Description/Refill set kar diya hai, 
                // toh API ya Auto-Extractor usko kharab nahi karega! Priority hamesha tumhari!
                if (!empty($existing['description']) && strpos($existing['description'], 'Auto-extracted') === false) {
                    $final_desc = $existing['description'];
                    $final_refill = $existing['has_refill'];
                    $final_cancel = $existing['has_cancel'];
                    $final_avg = $existing['avg_time'];
                }

                // 🚀 FIXED: Removed `name=?` and `category=?` from this query!
                // Ab tumhari set ki hui category aur name safe rahenge!
                $sql = "UPDATE smm_services SET base_price=?, service_rate=?, min=?, max=?, avg_time=?, description=?, has_refill=?, has_cancel=?, service_type=?, dripfeed=?, is_active=1 WHERE id=?";
                $db->prepare($sql)->execute([
                    $base_price_pkr, $selling_price, $min, $max, 
                    $final_avg, $final_desc, $final_refill, $final_cancel, 
                    $type, $drip, $existing['id']
                ]);
                $upd_count++;
            } else {
                // 🚀 FIXED: Auto-Insert block ko maine comment kar diya hai.
                // Ab system khud se nayi services add nahi karega jab tak tum admin panel se import nahi karte!
                /*
                // Insert New Service
                $sql = "INSERT INTO smm_services (provider_id, service_id, name, category, base_price, service_rate, min, max, avg_time, description, has_refill, has_cancel, service_type, dripfeed, is_active, manually_deleted) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,0)";
                $db->prepare($sql)->execute([$provider['id'], $sid, $name, $cat, $base_price_pkr, $selling_price, $min, $max, $final_avg, $final_desc, $final_refill, $final_cancel, $type, $drip]);
                
                // Log New
                $new_id = $db->lastInsertId();
                $db->prepare("INSERT INTO service_updates (service_id, service_name, category_name, rate, type) VALUES (?, ?, ?, ?, 'new')")
                   ->execute([$new_id, $name, $cat, $selling_price]);
                   
                $new_count++;
                */
            }
        }

        // Handle Removed Services (Disable local services not in API)
        if (!empty($seen_ids)) {
            $in = implode(',', array_fill(0, count($seen_ids), '?'));
            $params = array_merge([$provider['id']], $seen_ids);
            
            // Identify Removed
            $to_remove = $db->prepare("SELECT id, name, category, service_rate FROM smm_services WHERE provider_id=? AND is_active=1 AND manually_deleted=0 AND service_id NOT IN ($in)");
            $to_remove->execute($params);
            $removed_list = $to_remove->fetchAll();
            
            $rem_count += count($removed_list);

            foreach ($removed_list as $rm) {
                $db->prepare("INSERT INTO service_updates (service_id, service_name, category_name, rate, type) VALUES (?, ?, ?, ?, 'removed')")
                   ->execute([$rm['id'], $rm['name'], $rm['category'], $rm['service_rate']]);
            }

            // Disable Them
            $db->prepare("UPDATE smm_services SET is_active=0 WHERE provider_id=? AND manually_deleted=0 AND service_id NOT IN ($in)")->execute($params);
        }

        // Update Categories Table
        $db->query("INSERT IGNORE INTO smm_categories (name, is_active) SELECT DISTINCT category, 1 FROM smm_services WHERE is_active=1 AND manually_deleted=0");

        // Cleanup Old Logs (3 Days)
        $db->query("DELETE FROM service_updates WHERE created_at < NOW() - INTERVAL 3 DAY");

        $db->commit();
    }

    writeLog("Sync Complete. New: $new_count, Updated: $upd_count, Removed: $rem_count");

    // Agar browser se run ho raha hai to output dikhayein
    if (!$is_cli) {
        echo "<div style='font-family:sans-serif; padding:20px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; border-radius:10px;'>";
        echo "<h3>✅ Sync Completed Successfully!</h3>";
        echo "<strong>Rate Used:</strong> 1 USD = " . number_format($usd_rate, 2) . " PKR<br>";
        echo "<strong>Services Updated:</strong> $upd_count<br>";
        echo "<strong>New Services Added:</strong> $new_count<br>";
        echo "</div>";
    }

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    writeLog("CRITICAL ERROR: " . $e->getMessage());
    if (!$is_cli) echo "Error: " . $e->getMessage();
}
?>