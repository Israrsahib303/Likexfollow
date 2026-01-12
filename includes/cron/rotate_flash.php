<?php
// includes/cron/rotate_flash.php
require_once __DIR__ . '/../db.php';

// 1. Check if Active Deal Exists
$stmt = $db->query("SELECT id FROM flash_sales WHERE status='active' AND end_time > NOW()");
if ($stmt->fetch()) {
    die("Active deal already running.");
}

// 2. Expire Old Deals
$db->query("UPDATE flash_sales SET status='expired' WHERE status='active'");

// 3. Logic: 70% Chance SMM, 30% Digital
$type = (rand(1, 100) <= 70) ? 'smm' : 'digital';
$selected_deal = null;

if ($type == 'smm') {
    // SMM Service Uthao (Active Only)
    // PROFIT CHECK: Sirf wo uthao jahan margin > 20% ho
    $services = $db->query("SELECT * FROM smm_services WHERE is_active=1")->fetchAll();
    shuffle($services);

    foreach ($services as $s) {
        $cost = $s['base_price']; // Provider Price
        $sell = $s['service_rate']; // Your Price
        
        // Margin Check
        if ($sell > ($cost * 1.2)) { // Kam se kam 20% margin hona chahiye
            
            // Discount Calculate (15% se 40% ke beech)
            $discount_percent = rand(15, 40);
            $new_price = $sell - ($sell * ($discount_percent / 100));

            // FINAL SAFETY: Agar discount ke baad bhi Loss ho raha hai, to skip
            if ($new_price > ($cost * 1.05)) { // 5% profit must remain
                $selected_deal = [
                    'type' => 'smm',
                    'id' => $s['id'],
                    'name' => $s['name'],
                    'old' => $sell,
                    'new' => $new_price
                ];
                break; 
            }
        }
    }
} else {
    // Digital Product Uthao
    $products = $db->query("SELECT * FROM products WHERE is_active=1")->fetchAll();
    if ($products) {
        $p = $products[array_rand($products)];
        // Digital mein cost 0 hoti hai mostly, so flat discount
        $discount_percent = rand(20, 50);
        $new_price = $p['price'] - ($p['price'] * ($discount_percent / 100));
        
        $selected_deal = [
            'type' => 'digital',
            'id' => $p['id'],
            'name' => $p['name'],
            'old' => $p['price'],
            'new' => $new_price
        ];
    }
}

// 4. Save Deal
if ($selected_deal) {
    // Beast9 Fix: Use PHP Time (PKT) instead of Server SQL Time
    $start_time = date('Y-m-d H:i:s');
    $end_time = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $db->prepare("INSERT INTO flash_sales (type, item_id, item_name, original_price, discounted_price, start_time, end_time, status, max_claims) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
    $stmt->execute([
        $selected_deal['type'],
        $selected_deal['id'],
        $selected_deal['name'],
        $selected_deal['old'],
        $selected_deal['new'],
        $start_time,
        $end_time,
        rand(20, 100) // Random stock limit
    ]);
    echo "New Deal Activated: " . $selected_deal['name'];
} else {
    echo "No suitable deal found.";
}
?>