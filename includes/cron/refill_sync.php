<?php
/**
 * ====================================================
 * SMART REFILL STATUS SYNCHRONIZER (CLI CRON JOB)
 * ====================================================
 */

// CLI Check (Optional but good practice)
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("This script can only be run from the command line.");
}

// Database connection include karein (Path apne structure ke hisaab se verify kar lein)
// Agar file includes/cron/ mein hai, toh db.php usually includes/ mein hota hai:
require_once dirname(__DIR__) . '/db.php'; 

echo "[ " . date('Y-m-d H:i:s') . " ] 🚀 Starting Smart Refill Sync...\n";

try {
    // Sirf un requests ko uthao jo API ke through gayi thi aur abhi tak Pending ya Refilling hain
    $stmt = $db->query("SELECT * FROM refill_requests WHERE refill_type = 'api' AND status NOT IN ('Completed', 'Rejected', 'Canceled', 'Error')");
    $active_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($active_requests) === 0) {
        echo "[ OK ] All caught up! No active API refill requests found to sync.\n";
        exit;
    }

    echo "[ INFO ] Found " . count($active_requests) . " active requests. Checking statuses...\n";

    foreach ($active_requests as $req) {
        $id = $req['id'];
        $provider_id = $req['provider_id'];
        
        // Puranay API response se remote refill ID nikalna
        $api_response = json_decode($req['api_response'], true);
        $remote_refill_id = $api_response['refill'] ?? null;

        if (!$remote_refill_id) {
            echo "[ WARN ] Refill #{$id} has no remote ID in response. Skipping...\n";
            continue;
        }

        // Provider ki details DB se nikalna
        $stmt_prov = $db->prepare("SELECT url, api_key FROM providers WHERE id = ?");
        $stmt_prov->execute([$provider_id]);
        $provider = $stmt_prov->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            echo "[ ERROR ] Provider missing for Refill #{$id}.\n";
            continue;
        }

        // --- cURL REQUEST TO PROVIDER ---
        $post_data = [
            'key' => $provider['api_key'],
            'action' => 'refill_status',
            'refill' => $remote_refill_id
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $provider['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($result, true);

        if (isset($res['status'])) {
            $new_status = ucfirst(strtolower($res['status'])); 
            
            // Standard SMM API mapping
            if ($new_status == 'In progress') {
                $new_status = 'Refilling';
            } elseif ($new_status == 'Canceled') {
                $new_status = 'Rejected';
            }

            // Agar status change hua hai, toh Database update karo
            if ($new_status != $req['status']) {
                $update = $db->prepare("UPDATE refill_requests SET status = ? WHERE id = ?");
                $update->execute([$new_status, $id]);
                
                echo "[ UPDATE ] Refill #{$id} status changed to {$new_status}\n";
            } else {
                echo "[ SKIP ] Refill #{$id} is still {$new_status}. No change.\n";
            }

        } else {
            echo "[ ERROR ] Failed to fetch status for Refill #{$id}. Response: " . $result . "\n";
        }
    }

    echo "[ " . date('Y-m-d H:i:s') . " ] ✅ Sync Process Completed Successfully!\n";

} catch (PDOException $e) {
    echo "[ DB ERROR ] " . $e->getMessage() . "\n";
}
?>
