<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php'; 

// 1. Session Auth Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Fetch & Sanitize Inputs
    $panel_type  = trim($_POST['panel_type'] ?? '');
    $domain      = trim($_POST['domain'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = trim($_POST['admin_pass'] ?? '');
    $currency    = trim($_POST['currency'] ?? 'USD');

    // Basic Validations
    if (empty($panel_type) || empty($domain) || empty($admin_email) || empty($admin_pass)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (!in_array($panel_type, ['Child', 'Rental'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid panel type selected.']);
        exit;
    }

    // Clean Domain Input (Remove http/https and trailing slashes)
    $domain = strtolower($domain);
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = rtrim($domain, '/');

    try {
        // 3. AUTO CREATE TABLE IF NOT EXISTS (Safety Mechanism)
        $db->exec("CREATE TABLE IF NOT EXISTS `panel_rentals` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `panel_type` VARCHAR(20) NOT NULL,
            `domain` VARCHAR(255) NOT NULL,
            `admin_user` VARCHAR(255) NOT NULL,
            `admin_pass` VARCHAR(255) NOT NULL,
            `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
            `price_per_month` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
            `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
            `expiry_date` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 4. Check for existing Active/Pending request for same domain
        $stmt_domain_check = $db->prepare("SELECT id FROM panel_rentals WHERE domain = ? AND status IN ('Pending', 'Active')");
        $stmt_domain_check->execute([$domain]);
        if ($stmt_domain_check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'A panel request for this domain is already active or pending approval.']);
            exit;
        }

        // 5. Fetch Pricing from settings table
        $child_price = 10.00;
        $rental_price = 25.00;
        
        $stmt_set = $db->query("SELECT * FROM settings LIMIT 1");
        $settings = $stmt_set->fetch(PDO::FETCH_ASSOC);
        if ($settings) {
            if (isset($settings['child_panel_price'])) {
                $child_price = (float)$settings['child_panel_price'];
            }
            if (isset($settings['rental_panel_price'])) {
                $rental_price = (float)$settings['rental_panel_price'];
            }
        }

        $monthly_price = ($panel_type === 'Child') ? $child_price : $rental_price;

        // 6. Check User Balance
        $stmt_user = $db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User account not found.']);
            exit;
        }

        $user_balance = (float)$user['balance'];

        if ($user_balance < $monthly_price) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Insufficient balance! You need $' . number_format($monthly_price, 2) . ' to order this panel. Please add funds first.'
            ]);
            exit;
        }

        // 7. TRANSACTION START (Atomic operations)
        $db->beginTransaction();

        // Step A: Deduct Balance
        $stmt_deduct = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
        $stmt_deduct->execute([$monthly_price, $user_id, $monthly_price]);

        if ($stmt_deduct->rowCount() === 0) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Failed to deduct balance. Please refresh and try again.']);
            exit;
        }

        // Step B: Calculate 1 Month Expiry Date
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 month'));

        // Step C: Insert Order Record
        $stmt_insert = $db->prepare("INSERT INTO panel_rentals (user_id, panel_type, domain, admin_user, admin_pass, currency, price_per_month, status, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt_insert->execute([
            $user_id,
            $panel_type,
            $domain,
            $admin_email,
            $admin_pass,
            $currency,
            $monthly_price,
            $expiry_date
        ]);

        // Commit Transaction
        $db->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Success! Your ' . $panel_type . ' panel order has been submitted. Monthly fee of $' . number_format($monthly_price, 2) . ' has been deducted.'
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'System Error: ' . $e->getMessage()
        ]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method!']);
}
?>
