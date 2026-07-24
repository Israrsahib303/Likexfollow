<?php
// user/flash_deal_action.php
// Beast9 - Final v25.0: Natural Flow (Pending -> Cron Processing)

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/wallet.class.php';
// API Class removed intentionally as Cron will handle it

header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status'=>'error', 'msg'=>'Login required']); 
    exit; 
}

$user_id = (int)$_SESSION['user_id'];
$deal_id = (int)$_POST['deal_id'];
$link = sanitize($_POST['link'] ?? '');
// Get QTY (Default 1000 if missing)
$quantity = (int)($_POST['quantity'] ?? 1000); 

if (empty($link)) { 
    echo json_encode(['status'=>'error', 'msg'=>'Link is required']); 
    exit; 
}

// 2. Fetch Deal Data
$stmt = $db->prepare("SELECT * FROM flash_sales WHERE id = ? AND status='active' AND end_time > NOW()");
$stmt->execute([$deal_id]);
$deal = $stmt->fetch();

if (!$deal) { 
    echo json_encode(['status'=>'error', 'msg'=>'Deal expired or invalid!']); 
    exit; 
}

// 3. Price & Quantity Logic
$total_charge = 0;

if ($deal['type'] == 'smm') {
    // SMM: Validate Limits
    $svc = $db->prepare("SELECT min, max FROM smm_services WHERE id = ?");
    $svc->execute([$deal['item_id']]);
    $limits = $svc->fetch();

    if ($limits) {
        if ($quantity < $limits['min']) {
            echo json_encode(['status'=>'error', 'msg'=>"Minimum Quantity is {$limits['min']}"]); 
            exit;
        }
        if ($quantity > $limits['max']) {
            echo json_encode(['status'=>'error', 'msg'=>"Maximum Quantity is {$limits['max']}"]); 
            exit;
        }
    }
    
    // Calculate Price (Rate per 1000)
    $total_charge = ($quantity / 1000) * $deal['discounted_price'];

} else {
    // Other Types
    $quantity = 1; 
    $total_charge = $deal['discounted_price'];
}

// 4. Check Duplicate Claim
$chk = $db->prepare("SELECT id FROM flash_orders WHERE user_id = ? AND flash_id = ?");
$chk->execute([$user_id, $deal_id]);
if ($chk->fetch()) { 
    echo json_encode(['status'=>'error', 'msg'=>'You have already claimed this deal!']); 
    exit; 
}

// 5. Balance Check
$wallet = new Wallet($db);
$bal = $wallet->getBalance($user_id);
if ($bal < $total_charge) { 
    echo json_encode(['status'=>'error', 'msg'=>'Insufficient Balance. Please deposit funds.']); 
    exit; 
}

// 6. Process Transaction
$db->beginTransaction();
try {
    // Deduct Balance
    $wallet->addDebit($user_id, $total_charge, 'flash_deal', $deal['id'], "Flash Deal: {$deal['item_name']}");
    
    // Log Flash Order
    $db->prepare("INSERT INTO flash_orders (user_id, flash_id, amount_paid) VALUES (?, ?, ?)")
       ->execute([$user_id, $deal_id, $total_charge]);
    
    // --- ORDER PLACEMENT (DB ONLY) ---
    
    if ($deal['type'] == 'smm') {
        // Fetch Provider ID for reference, but DO NOT call API here
        $svc = $db->prepare("SELECT provider_id FROM smm_services WHERE id = ?");
        $svc->execute([$deal['item_id']]);
        $sdata = $svc->fetch();
        
        $provider_id = $sdata['provider_id'] ?? 0;

        // 🔥 NATURAL FLOW FIX: 
        // Insert as 'pending' with NO api_order_id.
        // Your existing Cron Job (smm_order_placer.php) will pick this up automatically, 
        // place it on the API, and update status to 'in_progress'.
        
        $db->prepare("
            INSERT INTO smm_orders 
            (user_id, service_id, link, quantity, charge, status, provider_id, api_order_id, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, NULL, NOW())
        ")->execute([
            $user_id, 
            $deal['item_id'], 
            $link, 
            $quantity, 
            $total_charge, 
            $provider_id
        ]);
    
    } elseif ($deal['type'] == 'digital') {
        // Digital Product - Stays pending until manual completion or auto-delivery logic
        $code = 'FL-DG-' . strtoupper(bin2hex(random_bytes(3)));
        $db->prepare("INSERT INTO orders (code, user_id, product_id, total_price, status) VALUES (?, ?, ?, ?, 'pending')")
           ->execute([$code, $user_id, $deal['item_id'], $total_charge]);

    } elseif ($deal['type'] == 'download') {
        // Digital Download - Usually auto-completed
        $code = 'FL-DL-' . strtoupper(bin2hex(random_bytes(3)));
        $db->prepare("INSERT INTO orders (code, user_id, product_id, total_price, status) VALUES (?, ?, ?, ?, 'completed')")
           ->execute([$code, $user_id, $deal['item_id'], $total_charge]);
    }
    
    $db->commit();
    echo json_encode(['status'=>'success']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['status'=>'error', 'msg'=>'Order Failed: ' . $e->getMessage()]);
}
?>