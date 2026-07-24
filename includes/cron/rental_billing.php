<?php
// Prevent manual browser access without secret key
define('CRON_SECRET_KEY', 'LikexFollow_Rental_Cron_2026'); // Aap is key ko change kar sakte hain

if (php_sapi_name() !== 'cli') {
    $provided_key = $_GET['key'] ?? '';
    if ($provided_key !== CRON_SECRET_KEY) {
        http_response_code(403);
        exit("Access Denied: Invalid Security Key.");
    }
}

// Set time limit for background processing
set_time_limit(0);
header('Content-Type: text/plain');

// Database Connection
$db_path = __DIR__ . '/../db.php';
if (!file_exists($db_path)) {
    $db_path = __DIR__ . '/../../includes/db.php'; // Fallback path check
}
require_once $db_path;

echo "=========================================\n";
echo "  RENTAL PANEL AUTO-BILLING CRON STARTED \n";
echo "  Time: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n\n";

try {
    // 1. Fetch all ACTIVE panels where expiry date has passed or is due today
    $query = "SELECT pr.*, u.balance, u.email as user_email 
              FROM panel_rentals pr 
              JOIN users u ON pr.user_id = u.id 
              WHERE pr.status = 'Active' AND pr.expiry_date <= NOW()";
              
    $stmt = $db->query($query);
    $expired_panels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $renewed_count = 0;
    $suspended_count = 0;

    if (count($expired_panels) === 0) {
        echo "No expired active panels found today. Everything is up to date.\n";
    } else {
        foreach ($expired_panels as $panel) {
            $panel_id      = $panel['id'];
            $user_id       = $panel['user_id'];
            $domain        = $panel['domain'];
            $price         = (float)$panel['price_per_month'];
            $user_balance  = (float)$panel['balance'];
            $current_exp   = $panel['expiry_date'];

            echo "Processing Panel ID #{$panel_id} ({$domain}) - User #{$user_id}...\n";

            // CHECK BALANCE
            if ($user_balance >= $price) {
                // --- CASE A: ENOUGH BALANCE -> AUTO-RENEW ---
                $db->beginTransaction();

                try {
                    // 1. Deduct balance from user
                    $stmt_deduct = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
                    $stmt_deduct->execute([$price, $user_id, $price]);

                    if ($stmt_deduct->rowCount() > 0) {
                        // 2. Calculate new expiry date (+1 month from old expiry)
                        $base_time = !empty($current_exp) ? strtotime($current_exp) : time();
                        $new_expiry = date('Y-m-d H:i:s', strtotime('+1 month', $base_time));

                        // 3. Update panel record
                        $stmt_renew = $db->prepare("UPDATE panel_rentals SET expiry_date = ?, status = 'Active' WHERE id = ?");
                        $stmt_renew->execute([$new_expiry, $panel_id]);

                        $db->commit();
                        $renewed_count++;
                        echo "  [SUCCESS] Renewed! Deducted \${$price}. New Expiry: {$new_expiry}\n";
                    } else {
                        $db->rollBack();
                        // Suspend fallback if balance check failed during update
                        $db->query("UPDATE panel_rentals SET status = 'Suspended' WHERE id = {$panel_id}");
                        $suspended_count++;
                        echo "  [SUSPENDED] Balance deduction failed.\n";
                    }
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    echo "  [ERROR] DB Error during renewal: " . $e->getMessage() . "\n";
                }

            } else {
                // --- CASE B: INSUFFICIENT BALANCE -> SUSPEND ---
                $stmt_suspend = $db->prepare("UPDATE panel_rentals SET status = 'Suspended' WHERE id = ?");
                $stmt_suspend->execute([$panel_id]);
                
                $suspended_count++;
                echo "  [SUSPENDED] Low balance (\${$user_balance} available vs \${$price} required).\n";
            }
        }
    }

    echo "\n-----------------------------------------\n";
    echo "SUMMARY:\n";
    echo "Total Processed : " . count($expired_panels) . "\n";
    echo "Auto-Renewed    : {$renewed_count}\n";
    echo "Suspended       : {$suspended_count}\n";
    echo "-----------------------------------------\n";

} catch (Exception $e) {
    echo "CRON FATAL ERROR: " . $e->getMessage() . "\n";
}
?>
