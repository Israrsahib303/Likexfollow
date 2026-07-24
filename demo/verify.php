<?php
// verify.php - Handles Email Verification
require_once 'includes/db.php';
require_once 'includes/helpers.php'; // For site url settings if needed

$success_msg = "";
$error_msg = "";

if (isset($_GET['token'])) {
    $token = $_GET['token']; // Token sanitization is handled by prepared statement
    
    try {
        // 1. Check if token exists
        $stmt = $db->prepare("SELECT id, name, email, is_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_verified'] == 1) {
                $error_msg = "Account is already verified. Please Login.";
            } else {
                // 2. Verify User & Remove Token (One-time use)
                // Note: We keep status as 'active' or update it if needed. 
                // Using is_verified = 1 prevents auto-ban logic.
                $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, status = 'active', ban_reason = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                
                $success_msg = "Email verified successfully! You can now login.";
            }
        } else {
            $error_msg = "Invalid or expired verification link.";
        }

    } catch (Exception $e) {
        $error_msg = "Something went wrong: " . $e->getMessage();
    }
} else {
    $error_msg = "No token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); max-width: 400px; width: 90%; text-align: center; }
        .icon { font-size: 50px; margin-bottom: 20px; }
        h2 { margin: 0 0 10px 0; color: #0f172a; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #4f46e5; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="card">
        <?php if($success_msg): ?>
            <div class="icon">✅</div>
            <h2>Verified!</h2>
            <p><?= $success_msg ?></p>
            <a href="login.php" class="btn btn-primary">Login Now</a>
        <?php else: ?>
            <div class="icon">⚠️</div>
            <h2>Verification Failed</h2>
            <p><?= $error_msg ?></p>
            <a href="login.php" class="btn btn-primary">Go to Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
