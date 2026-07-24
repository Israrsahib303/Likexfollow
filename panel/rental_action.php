<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php'; 

// 1. Admin Authentication Check
if (!isset($_SESSION['admin_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    if (!isset($_SESSION['user_id'])) { // Fallback check
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Admin session required.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = trim($_POST['action'] ?? '');

    // ---------------------------------------------------------
    // ACTION 1: UPDATE MONTHLY RENTAL RATES
    // ---------------------------------------------------------
    if ($action === 'update_prices') {
        $child_price  = (float)($_POST['child_panel_price'] ?? 10.00);
        $rental_price = (float)($_POST['rental_panel_price'] ?? 25.00);

        try {
            $stmt_check = $db->query("SELECT id FROM settings LIMIT 1");
            if ($stmt_check->rowCount() > 0) {
                $stmt_up = $db->prepare("UPDATE settings SET child_panel_price = ?, rental_panel_price = ? LIMIT 1");
                $stmt_up->execute([$child_price, $rental_price]);
            } else {
                $stmt_in = $db->prepare("INSERT INTO settings (child_panel_price, rental_panel_price) VALUES (?, ?)");
                $stmt_in->execute([$child_price, $rental_price]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Rental panel rates updated successfully!']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
            exit;
        }
    }

    // ---------------------------------------------------------
    // ACTION 2: UPDATE PANEL STATUS (Pending / Active / Suspended / Canceled)
    // ---------------------------------------------------------
    elseif ($action === 'update_status') {
        $rental_id  = (int)($_POST['rental_id'] ?? 0);
        $new_status = trim($_POST['status'] ?? '');

        $valid_statuses = ['Pending', 'Active', 'Suspended', 'Canceled'];

        if ($rental_id <= 0 || !in_array($new_status, $valid_statuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid panel ID or status option.']);
            exit;
        }

        try {
            // Fetch current record
            $stmt_get = $db->prepare("SELECT * FROM panel_rentals WHERE id = ?");
            $stmt_get->execute([$rental_id]);
            $panel = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if (!$panel) {
                echo json_encode(['status' => 'error', 'message' => 'Panel request record not found.']);
                exit;
            }

            // If changing from Pending to Active for first time, ensure expiry_date is set to +1 month from now
            if ($panel['status'] === 'Pending' && $new_status === 'Active') {
                $new_expiry = date('Y-m-d H:i:s', strtotime('+1 month'));
                $stmt_up = $db->prepare("UPDATE panel_rentals SET status = ?, expiry_date = ? WHERE id = ?");
                $stmt_up->execute([$new_status, $new_expiry, $rental_id]);
            } else {
                $stmt_up = $db->prepare("UPDATE panel_rentals SET status = ? WHERE id = ?");
                $stmt_up->execute([$new_status, $rental_id]);
            }

            echo json_encode([
                'status' => 'success', 
                'message' => 'Panel #' . $rental_id . ' status updated to "' . $new_status . '" successfully!'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
            exit;
        }
    }

    // ---------------------------------------------------------
    // ACTION 3: EXTEND EXPIRY DATE (+1 Month Manual Renewal)
    // ---------------------------------------------------------
    elseif ($action === 'extend_expiry') {
        $rental_id = (int)($_POST['rental_id'] ?? 0);

        if ($rental_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid panel ID.']);
            exit;
        }

        try {
            $stmt_get = $db->prepare("SELECT expiry_date FROM panel_rentals WHERE id = ?");
            $stmt_get->execute([$rental_id]);
            $panel = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if (!$panel) {
                echo json_encode(['status' => 'error', 'message' => 'Panel record not found.']);
                exit;
            }

            // Extend from existing expiry date if in future, else extend from current time
            $current_expiry = !empty($panel['expiry_date']) ? strtotime($panel['expiry_date']) : time();
            $base_time = ($current_expiry > time()) ? $current_expiry : time();
            $new_expiry = date('Y-m-d H:i:s', strtotime('+1 month', $base_time));

            $stmt_ext = $db->prepare("UPDATE panel_rentals SET expiry_date = ?, status = 'Active' WHERE id = ?");
            $stmt_ext->execute([$new_expiry, $rental_id]);

            echo json_encode([
                'status' => 'success', 
                'message' => 'Panel validity extended by 1 month! New Expiry: ' . date('d M Y', strtotime($new_expiry))
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
            exit;
        }
    }

    // ---------------------------------------------------------
    // ACTION 4: DELETE PANEL RECORD
    // ---------------------------------------------------------
    elseif ($action === 'delete_panel') {
        $rental_id = (int)($_POST['rental_id'] ?? 0);

        if ($rental_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid panel ID.']);
            exit;
        }

        try {
            $stmt_del = $db->prepare("DELETE FROM panel_rentals WHERE id = ?");
            $stmt_del->execute([$rental_id]);

            echo json_encode(['status' => 'success', 'message' => 'Panel record deleted successfully.']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
            exit;
        }
    }

    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing action parameter.']);
        exit;
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method!']);
}
?>
