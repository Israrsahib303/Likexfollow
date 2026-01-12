<?php
// includes/iron_core.php - The Security Engine
if (session_status() === PHP_SESSION_NONE) session_start();

function activateIronCore() {
    global $db;
    
    // 1. Fetch Security Settings
    $sec = [];
    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'security_%'");
        while($r = $stmt->fetch()) { $sec[$r['setting_key']] = $r['setting_value']; }
    } catch(Exception $e) {}

    // 2. Session Locking (DNA Check) 🧬
    // Agar Session Lock ON hai, to IP aur Browser check karo
    if (isset($sec['security_session_lock']) && $sec['security_session_lock'] == '1') {
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
            
            $current_ip = $_SERVER['REMOTE_ADDR'];
            $current_ua = md5($_SERVER['HTTP_USER_AGENT']);
            
            // Agar session mein data nahi hai, to abhi set karo
            if (!isset($_SESSION['admin_lock_ip'])) {
                $_SESSION['admin_lock_ip'] = $current_ip;
                $_SESSION['admin_lock_ua'] = $current_ua;
            }
            
            // CHECK: Agar IP ya Browser change hua -> KICK OUT ⛔
            if ($_SESSION['admin_lock_ip'] !== $current_ip || $_SESSION['admin_lock_ua'] !== $current_ua) {
                // Suspicious activity detected!
                session_unset();
                session_destroy();
                // User ko wapis Boss Login par bhejo error ke sath
                header("Location: ../boss_login.php?error=security_breach");
                exit;
            }
        }
    }
}
?>