<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php'; 

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = trim($_POST['order_id'] ?? '');
    
    if (empty($order_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid Order ID.']);
        exit;
    }

    try {
        // --- AUTO CREATE REFILL TABLE ---
        $db->exec("CREATE TABLE IF NOT EXISTS `refill_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `order_id` varchar(255) NOT NULL,
            `provider_id` int(11) DEFAULT 0,
            `api_order_id` varchar(255) DEFAULT NULL,
            `refill_type` varchar(20) DEFAULT 'manual',
            `status` varchar(50) DEFAULT 'Pending',
            `api_response` text DEFAULT NULL,
            `date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // --- DOUBLE REQUEST CHECK ---
        $stmt_check = $db->prepare("SELECT * FROM refill_requests WHERE order_id = ? AND status IN ('Pending', 'Refilling')");
        $stmt_check->execute([$order_id]);
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'A refill request for this order is already in progress!']);
            exit;
        }

        $matched_provider = null;
        $api_order_id_db = $order_id;
        $provider_id_db = 0;

        // ---------------------------------------------------------
        // 🚀 STEP 1: DYNAMIC API DISCOVERY ENGINE
        // ---------------------------------------------------------
        
        $provider_table = '';
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $possible_names = ['providers', 'api_providers', 'smm_providers', 'apis', 'api', 'provider'];
        
        foreach($tables as $tbl) {
            if (in_array(strtolower($tbl), $possible_names)) {
                $provider_table = $tbl;
                break;
            }
        }
        
        if (empty($provider_table)) {
            echo json_encode(['status' => 'error', 'message' => 'API table not found! Please check database structure.']);
            exit;
        }

        $stmt_all_prov = $db->query("SELECT * FROM {$provider_table}");
        $all_providers = $stmt_all_prov->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all_providers as $prov) {
            $api_url = $prov['url'] ?? $prov['api_url'] ?? '';
            $api_key = $prov['api_key'] ?? $prov['key'] ?? '';
            $prov_id = $prov['id'] ?? 0;

            if (empty($api_url) || empty($api_key)) {
                continue;
            }

            // Status check karne ke liye ping bhejna
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'key' => $api_key,
                'action' => 'status',
                'order' => $order_id
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6); 
            $status_result = curl_exec($ch);
            curl_close($ch);

            $status_res = json_decode($status_result, true);

            // Match mil gaya (Order is provider ke paas mojood hai)
            if (isset($status_res['status']) && !isset($status_res['error'])) {
                $matched_provider = $prov;
                $provider_id_db = $prov_id;
                break; 
            }
        }

        // ---------------------------------------------------------
        // 🚀 STEP 2: REFILL ACTION & SAVING
        // ---------------------------------------------------------
        
        $refill_type = 'manual';
        $status = 'Pending';
        $api_response = 'Order did not match any connected API provider (Saved for manual processing).';
        $final_status = 'success';
        $final_message = 'Request submitted to admin for manual review.';

        if ($matched_provider) {
            $api_url = $matched_provider['url'] ?? $matched_provider['api_url'] ?? '';
            $api_key = $matched_provider['api_key'] ?? $matched_provider['key'] ?? '';

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $api_url);
            curl_setopt($ch2, CURLOPT_POST, 1);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
                'key' => $api_key,
                'action' => 'refill',
                'order' => $order_id
            ]));
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
            $refill_result = curl_exec($ch2);
            curl_close($ch2);

            $refill_res = json_decode($refill_result, true);
            $api_response = $refill_result; // Raw response save ho raha hai

            // SUCCESS CHECK (Different API formats handle kiye hain)
            if (isset($refill_res['refill']) || isset($refill_res['refill_id']) || (isset($refill_res['status']) && strtolower($refill_res['status']) === 'success')) {
                $status = 'Refilling';
                $refill_type = 'api';
                $final_message = 'Success! Order matched with provider and auto-refill has started.';
            } else {
                $status = 'Pending'; 
                $refill_type = 'manual';
                
                // ERROR CAPTURE LOGIC (Agar error nahi hai toh seedha provider ka response dikha dega)
                if (isset($refill_res['error'])) {
                    $real_error = $refill_res['error'];
                } elseif (isset($refill_res['message'])) {
                    $real_error = $refill_res['message'];
                } else {
                    $real_error = 'Provider API Said: ' . strip_tags($refill_result);
                    // Lamba response ho toh cut kar do popup ke liye
                    if (strlen($real_error) > 80) {
                        $real_error = substr($real_error, 0, 80) . '...';
                    }
                }
                
                $final_status = 'error'; 
                $final_message = 'API Rejection: "' . $real_error . '". Request sent to admin for manual check.';
            }
        }

        // Always save to Database
        $stmt_insert = $db->prepare("INSERT INTO refill_requests (user_id, order_id, provider_id, api_order_id, refill_type, status, api_response) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->execute([$user_id, $order_id, $provider_id_db, $api_order_id_db, $refill_type, $status, $api_response]);

        echo json_encode([
            'status' => $final_status, 
            'message' => $final_message
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method!']);
}
?>
