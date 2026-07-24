<?php
// Session start karna zaroori hai admin authentication ke liye
session_start();

// Header set kar rahe hain taake output JSON format mein jaye (SweetAlert2 ko yahi format chahiye hota hai)
header('Content-Type: application/json');

// Database connection file include karein (Path apne panel ke hisaab se check kar lein)
require_once '../includes/db.php';

// Agar request POST nahi hai toh reject kar do (Security feature)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Variables ko securely get karna
    $refill_id = $_POST['refill_id'] ?? null;
    $status = $_POST['status'] ?? null;

    // Validation: Check karein ke ID aur Status khali toh nahi?
    if (empty($refill_id) || empty($status)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required data. Refill ID or Status is empty.']);
        exit;
    }

    // Security Check: Status sirf 'Completed' ya 'Rejected' ho sakta hai, koi aur lafz database mein na jaye
    $allowed_statuses = ['Completed', 'Rejected'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status provided! System only accepts Completed or Rejected.']);
        exit;
    }

    try {
        // 1. Sabse pehle check karein ke kya yeh refill request waqai database mein mojood hai?
        $stmt_check = $db->prepare("SELECT * FROM refill_requests WHERE id = ?");
        $stmt_check->execute([$refill_id]);
        $refill = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$refill) {
            echo json_encode(['status' => 'error', 'message' => 'Refill request not found in the database.']);
            exit;
        }

        // 2. Agar mojood hai toh uska Status Update kar dein
        $stmt_update = $db->prepare("UPDATE refill_requests SET status = ? WHERE id = ?");
        $stmt_update->execute([$status, $refill_id]);

        // 3. (Optional & Advanced) Activity Log - Agar aapke panel mein admin_logs ki table hai toh log ban jaye
        try {
            $admin_id = $_SESSION['admin_id'] ?? 1; // Default admin ID
            $log_msg = "Admin updated Refill Request #{$refill_id} to {$status}";
            // Agar table nahi hogi toh error nahi aayega, chupke se skip ho jayega (Silent Try-Catch)
            $db->prepare("INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())")->execute([$admin_id, $log_msg]);
        } catch (Exception $e) {
            // Ignore silently if logs table doesn't exist
        }

        // 4. Success Response wapis SweetAlert2 ko bhej diya
        echo json_encode([
            'status' => 'success', 
            'message' => "Refill request #{$refill_id} has been securely marked as {$status}."
        ]);

    } catch (PDOException $e) {
        // Database Error Handling
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred while processing the request.']);
    }

} else {
    // Agar koi is file ko direct URL se open karne ki koshish kare toh error do
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method. Please use the dashboard buttons.']);
}
?>
