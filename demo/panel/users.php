<?php
include '_header.php'; 
requireAdmin();

// --- HELPER: Search Highlighting ---
function highlight($text, $search) {
    if (empty($search)) return htmlspecialchars($text ?? '');
    $escaped_search = preg_quote($search, '/');
    return preg_replace(
        "/($escaped_search)/i", 
        '<span class="search-highlight">$1</span>', 
        htmlspecialchars($text ?? '')
    );
}

// --- 0. AUTO-HEAL DATABASE (Self-Repairing) ---
try {
    $cols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    // Standard Columns
    if (!in_array('custom_rate', $cols)) $db->exec("ALTER TABLE users ADD COLUMN custom_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00");
    if (!in_array('admin_note', $cols)) $db->exec("ALTER TABLE users ADD COLUMN admin_note TEXT DEFAULT NULL");
    if (!in_array('status', $cols)) $db->exec("ALTER TABLE users ADD COLUMN status ENUM('active','banned') DEFAULT 'active'");
    // Tracking
    if (!in_array('last_login', $cols)) $db->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL");
    if (!in_array('last_ip', $cols)) $db->exec("ALTER TABLE users ADD COLUMN last_ip VARCHAR(50) DEFAULT NULL");
    // Verification & Ban Reason
    if (!in_array('is_verified_badge', $cols)) $db->exec("ALTER TABLE users ADD COLUMN is_verified_badge TINYINT(1) DEFAULT 0");
    if (!in_array('is_verified', $cols)) $db->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
    if (!in_array('verification_token', $cols)) $db->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(100) DEFAULT NULL");
    if (!in_array('ban_reason', $cols)) $db->exec("ALTER TABLE users ADD COLUMN ban_reason TEXT DEFAULT NULL");
    
    // WhatsApp Contact Permission for Banned Users
    if (!in_array('ban_show_contact', $cols)) $db->exec("ALTER TABLE users ADD COLUMN ban_show_contact TINYINT(1) DEFAULT 0");

    // Role
    if (!in_array('role', $cols)) $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin','staff') NOT NULL DEFAULT 'user'");

    // Specific Service/Category/Provider Rates Table
    $db->exec("CREATE TABLE IF NOT EXISTS user_custom_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        target_type ENUM('provider', 'category', 'service') NOT NULL,
        target_id VARCHAR(255) NOT NULL,
        custom_rate DECIMAL(5,2) NOT NULL,
        UNIQUE KEY user_target (user_id, target_type, target_id)
    )");
    
    // Upgrades if table already existed with older schema
    try { $db->exec("ALTER TABLE user_custom_rates MODIFY target_id VARCHAR(255) NOT NULL"); } catch(Exception $e) {}
    try { $db->exec("ALTER TABLE user_custom_rates MODIFY target_type ENUM('provider', 'category', 'service') NOT NULL"); } catch(Exception $e) {}

} catch (Exception $e) { /* Silent */ }

// Fetch Provider & Services Data
try {
    $providers_data = $db->query("SELECT id, name FROM smm_providers WHERE is_active=1")->fetchAll(PDO::FETCH_ASSOC);
    // Services with Website's Base Rates AND API Cost (base_price)
    $services_data = $db->query("SELECT id, provider_id, category, name, base_price, service_rate FROM smm_services WHERE is_active=1 AND manually_deleted=0")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $providers_data = [];
    $services_data = [];
}

// --- 🤖 FIXED: AUTO-BAN LOGIC (Send Mail FIRST, Then Ban) ---
$ban_msg = "Account Suspended: Unverified for 30+ Days. Check Spam Folder.";
$stale_users = $db->query("SELECT id, name, email FROM users WHERE is_verified = 0 AND created_at < (NOW() - INTERVAL 30 DAY) AND status = 'active'")->fetchAll();

if (!empty($stale_users)) {
    foreach ($stale_users as $su) {
        $subject = "Account Suspended - Action Required";
        $body = "Hi " . $su['name'] . ",<br><br>Your account has been suspended because it remained unverified for over 30 days.<br>Please contact support or verify your email to reactivate.";
        sendEmail($su['email'], $su['name'], $subject, $body);
        $db->prepare("UPDATE users SET status = 'banned', ban_reason = ? WHERE id = ?")->execute([$ban_msg, $su['id']]);
    }
}

// --- 🤖 AUTO-UNBAN LOGIC ---
$db->exec("UPDATE users SET status = 'active', ban_reason = NULL WHERE is_verified = 1 AND status = 'banned' AND ban_reason = '$ban_msg'");


$error = '';
$success = '';

// --- 1. ACTION HANDLERS ---

// A. EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $uid = (int)$_POST['user_id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = $_POST['role'];
    $note = sanitize($_POST['admin_note']);
    $badge = isset($_POST['is_verified_badge']) ? 1 : 0;
    $email_verified = isset($_POST['is_verified']) ? 1 : 0; 

    $rate_val = abs((float)$_POST['rate_value']); 
    $rate_type = $_POST['rate_type']; 
    $final_rate = ($rate_type == 'discount') ? -$rate_val : $rate_val;

    try {
        $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=?, custom_rate=?, admin_note=?, is_verified_badge=?, is_verified=? WHERE id=?");
        $stmt->execute([$name, $email, $role, $final_rate, $note, $badge, $email_verified, $uid]);
        $_SESSION['flash_success'] = "User #$uid updated successfully.";
    } catch (Exception $e) { $_SESSION['flash_error'] = $e->getMessage(); }
    
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// B. SEND SINGLE EMAIL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_single_mail'])) {
    $uid = (int)$_POST['user_id'];
    $subject = sanitize($_POST['subject']);
    $msg = $_POST['message']; 
    
    $u = $db->query("SELECT email, name FROM users WHERE id=$uid")->fetch();
    if ($u) {
        $mail_res = sendEmail($u['email'], $u['name'], $subject, $msg);
        if ($mail_res['success']) {
            $_SESSION['flash_success'] = "Email sent to " . htmlspecialchars($u['email']);
        } else {
            $_SESSION['flash_error'] = "Mail Failed: " . $mail_res['message'];
        }
    }
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// C. SEND VERIFICATION EMAIL (Single)
if (isset($_GET['send_verify'])) {
    $uid = (int)$_GET['send_verify'];
    $u = $db->query("SELECT * FROM users WHERE id=$uid")->fetch();
    
    if ($u && $u['is_verified'] == 0) {
        $token = $u['verification_token'];
        if(empty($token)) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE users SET verification_token = ? WHERE id = ?")->execute([$token, $uid]);
        }

        $verify_link = SITE_URL . '/verify.php?token=' . $token;
        $subject = "Verify Your Account - " . ($GLOBALS['settings']['site_name'] ?? 'SubHub');
        $body = "Hi " . $u['name'] . ",<br><br>Your account is pending verification. Please click below to verify and avoid suspension:<br><br>";
        $body .= "<a href='$verify_link' style='background:#0071e3;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Verify Now</a>";
        
        if(sendEmail($u['email'], $u['name'], $subject, $body)['success']) {
            $_SESSION['flash_success'] = "Verification link sent to " . htmlspecialchars($u['email']);
        } else {
            $_SESSION['flash_error'] = "Failed to send email.";
        }
    } else {
        $_SESSION['flash_error'] = "User already verified.";
    }
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// D. BULK VERIFICATION EMAIL
if (isset($_POST['bulk_verify_mail'])) {
    $unverified_users = $db->query("SELECT * FROM users WHERE is_verified = 0 AND status = 'active'")->fetchAll();
    $count = 0;
    
    foreach($unverified_users as $u) {
        $token = $u['verification_token'];
        if(empty($token)) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE users SET verification_token = ? WHERE id = ?")->execute([$token, $u['id']]);
        }
        $verify_link = SITE_URL . '/verify.php?token=' . $token;
        $subject = "Action Required: Verify Account";
        $body = "Hi " . $u['name'] . ",<br><br>Please verify your account to continue using our services.<br><br><a href='$verify_link'>Verify Here</a>";
        
        if(sendEmail($u['email'], $u['name'], $subject, $body)['success']) {
            $count++;
        }
    }
    $_SESSION['flash_success'] = "Sent $count verification emails.";
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// E. MANUAL BAN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ban_user_action'])) {
    $uid = (int)$_POST['user_id'];
    $reason = sanitize($_POST['ban_reason']);
    $show_contact = isset($_POST['show_contact']) ? 1 : 0; 
    
    $db->prepare("UPDATE users SET status = 'banned', ban_reason = ?, ban_show_contact = ?, remember_token = NULL WHERE id = ?")->execute([$reason, $show_contact, $uid]);
    
    $_SESSION['flash_success'] = "User BANNED. Reason: $reason";
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// F. UNBAN USER
if (isset($_GET['unban_id'])) {
    $uid = (int)$_GET['unban_id'];
    $db->prepare("UPDATE users SET status = 'active', ban_reason = NULL, ban_show_contact = 0, is_verified = 1 WHERE id = ?")->execute([$uid]);
    $_SESSION['flash_success'] = "User Activated & Verified successfully.";
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// G. UPDATE BALANCE
if (isset($_POST['update_balance'])) {
    $uid = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type']; 
    $reason = sanitize($_POST['reason']);
    
    try {
        $db->beginTransaction();
        if ($type == 'add') {
            $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $uid]);
            $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, note) VALUES (?, 'credit', ?, ?)")->execute([$uid, $amount, "Admin: $reason"]);
            $_SESSION['flash_success'] = "Added " . formatCurrency($amount);
        } else {
            $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $uid]);
            $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, note) VALUES (?, 'debit', ?, ?)")->execute([$uid, $amount, "Admin: $reason"]);
            $_SESSION['flash_success'] = "Deducted " . formatCurrency($amount);
        }
        $db->commit();
    } catch (Exception $e) { $db->rollBack(); $_SESSION['flash_error'] = $e->getMessage(); }
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// H. CHANGE PASSWORD
if (isset($_POST['change_pass'])) {
    $uid = (int)$_POST['user_id'];
    $pass = $_POST['new_password'];
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
    $_SESSION['flash_success'] = "Password changed.";
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// I. DELETE USER
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    if ($id == $_SESSION['user_id']) { $_SESSION['flash_error'] = "Self-delete not allowed!"; } 
    else {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $_SESSION['flash_success'] = "User deleted.";
    }
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// J. LOGIN AS USER
if (isset($_GET['login_as'])) {
    $target_uid = (int)$_GET['login_as'];
    $target_user = $db->query("SELECT * FROM users WHERE id = $target_uid")->fetch();
    
    if ($target_user) {
        $_SESSION['user_id'] = $target_user['id'];
        $_SESSION['role'] = $target_user['role'];
        $_SESSION['email'] = $target_user['email'];
        echo "<script>window.location.href='../user/index.php';</script>"; exit;
    } else {
        $_SESSION['flash_error'] = "User not found.";
        echo "<script>window.location.href='users.php';</script>"; exit;
    }
}

// K. MULTI-SELECT DYNAMIC CUSTOM RATES (NEW)
if (isset($_POST['save_specific_rate'])) {
    $uid = (int)$_POST['user_id'];
    $type = $_POST['target_type']; // provider, category, service
    
    $rate_val = abs((float)$_POST['rate_value']);
    $rate_action = $_POST['rate_type']; 
    $final_rate = ($rate_action == 'discount') ? -$rate_val : $rate_val;

    // Collect array of targets based on type
    $targets = [];
    if ($type == 'provider') {
        if (!empty($_POST['provider_id'])) $targets[] = $_POST['provider_id'];
    } elseif ($type == 'category') {
        $targets = $_POST['categories'] ?? [];
    } elseif ($type == 'service') {
        $targets = $_POST['services'] ?? [];
    }

    if (!empty($targets)) {
        try {
            $db->beginTransaction();
            foreach ($targets as $target_id) {
                if (empty($target_id)) continue;
                
                if ($rate_val == 0) { 
                    // Remove Custom Rate
                    $db->prepare("DELETE FROM user_custom_rates WHERE user_id=? AND target_type=? AND target_id=?")->execute([$uid, $type, $target_id]);
                } else {
                    // Apply Custom Rate
                    $db->prepare("INSERT INTO user_custom_rates (user_id, target_type, target_id, custom_rate) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE custom_rate=?")->execute([$uid, $type, $target_id, $final_rate, $final_rate]);
                }
            }
            $db->commit();
            if ($rate_val == 0) {
                $_SESSION['flash_success'] = ucfirst($type)." custom rates removed for ".count($targets)." item(s).";
            } else {
                $_SESSION['flash_success'] = ucfirst($type)." custom rates applied to ".count($targets)." item(s).";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = "Database Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Please select at least one target (Checkbox) for " . $type;
    }
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// Flash Messages
if (isset($_SESSION['flash_success'])) { $success = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (isset($_SESSION['flash_error'])) { $error = $_SESSION['flash_error']; unset($_SESSION['flash_error']); }

// --- FETCH DATA ---
$stats = $db->query("SELECT COUNT(*) as total, SUM(balance) as wallet_total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status='banned' THEN 1 ELSE 0 END) as banned, SUM(CASE WHEN is_verified=0 THEN 1 ELSE 0 END) as unverified FROM users")->fetch();

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$where = "1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search; 
}
if ($role_filter) {
    $where .= " AND role = ?";
    $params[] = $role_filter;
}

$page = (int)($_GET['page'] ?? 1);
$per_page = 15; 
$offset = ($page - 1) * $per_page;
$total_count = $db->prepare("SELECT COUNT(id) FROM users WHERE $where");
$total_count->execute($params);
$total_rows = $total_count->fetchColumn();
$total_pages = ceil($total_rows / $per_page);

$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count, (SELECT SUM(total_price) FROM orders WHERE user_id = u.id) as total_spent FROM users u WHERE $where ORDER BY u.id DESC LIMIT $per_page OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=sf-pro-display:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --ios-bg: #F5F5F7;
        --ios-card: #FFFFFF;
        --ios-text: #1D1D1F;
        --ios-text-sec: #86868B;
        --ios-blue: #0071E3;
        --ios-blue-hover: #0077ED;
        --ios-red: #FF3B30;
        --ios-green: #34C759;
        --ios-orange: #FF9500;
        --ios-border: #D2D2D7;
        --ios-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        --ios-radius: 12px;
        --font-stack: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    * { box-sizing: border-box; }
    body {
        background-color: var(--ios-bg);
        font-family: var(--font-stack);
        color: var(--ios-text);
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
    }

    .ios-container { width: min(100%, 1200px); margin: 0 auto; padding: 30px 20px; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card {
        background: var(--ios-card); border-radius: var(--ios-radius); padding: 24px; box-shadow: var(--ios-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease; border: 1px solid rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: center;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
    .stat-label { font-size: 13px; font-weight: 600; color: var(--ios-text-sec); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .stat-value { font-size: 28px; font-weight: 700; color: var(--ios-text); letter-spacing: -0.5px; }
    .stat-icon { font-size: 24px; margin-bottom: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }

    .si-blue { background: rgba(0, 113, 227, 0.1); color: var(--ios-blue); }
    .si-green { background: rgba(52, 199, 89, 0.1); color: var(--ios-green); }
    .si-red { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    .si-orange { background: rgba(255, 149, 0, 0.1); color: var(--ios-orange); }

    .toolbar { display: flex; justify-content: space-between; align-items: center; background: var(--ios-card); padding: 16px; border-radius: var(--ios-radius); margin-bottom: 24px; box-shadow: var(--ios-shadow); flex-wrap: wrap; gap: 16px; }
    .search-wrap { display: flex; align-items: center; gap: 10px; flex: 1; max-width: 500px; }

    .ios-input, .ios-select { background: #F5F5F7; border: 1px solid transparent; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: var(--ios-text); outline: none; transition: all 0.2s; }
    .ios-input:focus, .ios-select:focus { background: #FFF; border-color: var(--ios-blue); box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15); }

    .ios-btn { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
    .btn-primary { background: var(--ios-blue); color: #fff; }
    .btn-primary:hover { background: var(--ios-blue-hover); transform: scale(1.02); }
    .btn-danger { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    .btn-danger:hover { background: var(--ios-red); color: #fff; transform: scale(1.02); }

    .table-responsive { background: var(--ios-card); border-radius: var(--ios-radius); box-shadow: var(--ios-shadow); overflow: hidden; border: 1px solid rgba(0,0,0,0.03); }
    .table-scroll { width: 100%; overflow-x: auto; }
    .ios-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    .ios-table th { text-align: left; padding: 16px 20px; font-size: 12px; font-weight: 600; color: var(--ios-text-sec); text-transform: uppercase; border-bottom: 1px solid var(--ios-border); background: rgba(245, 245, 247, 0.5); }
    .ios-table td { padding: 16px 20px; vertical-align: middle; border-bottom: 1px solid #F0F0F0; font-size: 14px; color: var(--ios-text); transition: background 0.1s; }
    .ios-table tr:hover td { background: #FAFAFC; }
    .ios-table tr:last-child td { border-bottom: none; }

    .user-info { display: flex; align-items: center; gap: 14px; }
    .avatar-circle { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #E0E7FF 0%, #F5F7FF 100%); color: var(--ios-blue); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .u-meta h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ios-text); }
    .u-meta span { display: block; font-size: 12px; color: var(--ios-text-sec); margin-top: 2px; }
    .search-highlight { background-color: #FFF2CC; color: #B45309; border-radius: 2px; padding: 0 2px; }

    .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
    .badge-user { background: #F2F2F7; color: #636366; }
    .badge-admin { background: #E4EFFF; color: #0071E3; }
    .badge-staff { background: #F0FDF4; color: #15803D; }

    .status-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .st-active { background: rgba(52, 199, 89, 0.1); color: var(--ios-green); }
    .st-banned { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    
    .action-row { display: flex; gap: 8px; justify-content: flex-end; }
    .icon-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #fff; border: 1px solid #D1D1D6; color: #636366; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .icon-btn:hover { background: #F2F2F7; transform: translateY(-1px); color: #000; }
    
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content { display: none; position: absolute; right: 0; top: 36px; background-color: white; min-width: 180px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); border-radius: 12px; z-index: 100; padding: 8px; border: 1px solid rgba(0,0,0,0.04); animation: fadein 0.2s; }
    @keyframes fadein { from { opacity:0; transform: translateY(5px); } to { opacity:1; transform:translateY(0); } }
    .dropdown-content a, .dropdown-content button { color: var(--ios-text); padding: 10px 12px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 13px; border-radius: 8px; width: 100%; text-align: left; background: none; border: none; cursor: pointer; }
    .dropdown-content a:hover, .dropdown-content button:hover { background-color: #F5F5F7; }
    .show { display: block; }

    .pagination { padding: 15px 20px; border-top: 1px solid #F0F0F0; display: flex; justify-content: space-between; align-items: center; }
    
    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal-content { background: #fff; padding: 30px; border-radius: 20px; width: 100%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); max-height: 90vh; overflow-y: auto; }
    @keyframes slideUp { from { transform: scale(0.95) translateY(10px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--ios-text-sec); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

    .toast { padding: 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .toast-success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
    .toast-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
    
    /* 🌟 MULTI-SELECT CUSTOM DROPDOWNS & UI */
    .ios-segmented-control { display: flex; background: #E5E5EA; padding: 3px; border-radius: 10px; margin-bottom: 20px; }
    .ios-segmented-control label { flex: 1; text-align: center; position: relative; cursor: pointer; margin: 0; }
    .ios-segmented-control input { position: absolute; opacity: 0; }
    .ios-segmented-control span { display: block; padding: 8px 0; font-size: 13px; font-weight: 600; color: #8E8E93; border-radius: 8px; transition: 0.2s; z-index: 2; position: relative; }
    .ios-segmented-control input:checked + span { background: #fff; color: #1D1D1F; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }

    .rate-step { background: #FAFAFC; border: 1px solid #E5E5EA; border-radius: 12px; padding: 16px; margin-bottom: 15px; position: relative; }

    .custom-dropdown { position: relative; width: 100%; }
    .custom-dropdown-header { background: #fff; border: 1px solid #D2D2D7; padding: 12px 14px; border-radius: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: var(--ios-text); transition: 0.2s; }
    .custom-dropdown-header:hover { border-color: var(--ios-blue); box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1); }
    .custom-dropdown-body { position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #D2D2D7; border-radius: 8px; margin-top: 6px; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.12); display: none; overflow: hidden; animation: fadein 0.2s; }
    .custom-dropdown-body.show { display: block; }
    .custom-dropdown-body input.ios-input { margin: 10px; width: calc(100% - 20px); border: 1px solid #E5E5EA; background: #F9F9FB; }
    .custom-dropdown-list { list-style: none; margin: 0; padding: 0; max-height: 220px; overflow-y: auto; }
    /* Make list items full click area */
    .custom-dropdown-list li { padding: 0; border-bottom: 1px solid #F5F5F7; transition: background 0.1s; }
    .custom-dropdown-list li label { padding: 12px 16px; display: flex; align-items: center; width: 100%; cursor: pointer; margin: 0; font-size: 13px; line-height: 1.4; }
    .custom-dropdown-list li:hover { background: #F0F7FF; }

    .preview-box { margin-top: 10px; font-size: 13px; color: var(--ios-text-sec); padding: 12px; background: #fff; border-radius: 8px; border: 1px dashed #D2D2D7; text-align: center; }

    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .ios-container { padding: 15px 10px; }
        .stat-card { padding: 16px; }
        .ios-segmented-control span { font-size: 12px; }
    }
</style>

<div class="ios-container">

    <?php if($success): ?><div class="toast toast-success"><i class="fa-solid fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="toast toast-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div><?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon si-blue"><i class="fa-solid fa-users"></i></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-green"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-label">Wallet Holdings</div>
            <div class="stat-value"><?= formatCurrency($stats['wallet_total']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-red"><i class="fa-solid fa-ban"></i></div>
            <div class="stat-label">Banned Users</div>
            <div class="stat-value"><?= number_format($stats['banned']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-orange"><i class="fa-solid fa-envelope"></i></div>
            <div class="stat-label">Pending Verify</div>
            <div class="stat-value"><?= number_format($stats['unverified']) ?></div>
        </div>
    </div>

    <div class="toolbar">
        <form class="search-wrap" method="GET" action="users.php">
            <input type="text" name="search" class="ios-input" placeholder="Search name, email, ID..." style="width:100%; flex: 1;" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="ios-btn btn-primary" style="padding: 10px 14px;"><i class="fa-solid fa-search"></i></button>
            <select name="role" class="ios-select" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="user" <?= $role_filter=='user'?'selected':'' ?>>User</option>
                <option value="admin" <?= $role_filter=='admin'?'selected':'' ?>>Admin</option>
            </select>
        </form>
        
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="whatsapp_leads.php" class="ios-btn" style="background: rgba(52, 199, 89, 0.1); color: var(--ios-green); border: 1px solid rgba(52,199,89,0.3);">
                <i class="fa-brands fa-whatsapp" style="font-size:16px;"></i> WhatsApp Leads
            </a>

            <?php if($stats['unverified'] > 0): ?>
            <form method="POST" onsubmit="return confirm('Send verification email to <?= $stats['unverified'] ?> users?');" style="margin:0;">
                <button type="submit" name="bulk_verify_mail" class="ios-btn btn-danger">
                    <i class="fa-solid fa-paper-plane"></i> Email Pending (<?= $stats['unverified'] ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <div class="table-scroll">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th>User Identity</th>
                        <th>Role / Global Rate</th>
                        <th>Wallet</th>
                        <th>Status</th>
                        <th>Activity</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:50px; color:#86868B;">No users matches your search.</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $u): 
                            $initial = !empty($u['name']) ? strtoupper(substr($u['name'], 0, 1)) : '?';
                            $role_cls = match($u['role']) { 'admin'=>'badge-admin', 'staff'=>'badge-staff', default=>'badge-user' };
                            $rate_display = "<span style='color:#86868B; font-size:11px;'>Standard</span>";
                            if($u['custom_rate'] < 0) $rate_display = "<span style='color:var(--ios-green); font-weight:700; font-size:11px;'>".abs($u['custom_rate'])."% OFF PROFIT</span>";
                            if($u['custom_rate'] > 0) $rate_display = "<span style='color:var(--ios-red); font-weight:700; font-size:11px;'>+".abs($u['custom_rate'])."% EXTRA PROFIT</span>";
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="avatar-circle"><?= $initial ?></div>
                                    <div class="u-meta">
                                        <h4>
                                            <?= highlight($u['name'], $search) ?>
                                            <?php if($u['is_verified_badge']): ?><i class="fa-solid fa-circle-check" style="color:#0071E3; font-size:12px; margin-left:4px;"></i><?php endif; ?>
                                        </h4>
                                        <span><?= highlight($u['email'], $search) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $role_cls ?>"><?= ucfirst($u['role']) ?></span>
                                <div style="margin-top:4px;"><?= $rate_display ?></div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:#1D1D1F;"><?= formatCurrency($u['balance']) ?></div>
                                <div style="font-size:11px; color:#86868B;">Spent: <?= formatCurrency($u['total_spent']??0) ?></div>
                            </td>
                            <td>
                                <?php if($u['status']=='active'): ?>
                                    <span class="status-pill st-active"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:6px;"></i> Active</span>
                                <?php else: ?>
                                    <span class="status-pill st-banned"><i class="fa-solid fa-circle" style="font-size:6px; margin-right:6px;"></i> Banned</span>
                                    <?php if($u['ban_reason']): ?><div style="font-size:10px; color:#FF3B30; margin-top:2px; max-width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($u['ban_reason']) ?>"><?= htmlspecialchars($u['ban_reason']) ?></div><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:12px; color:#1D1D1F;"><?= $u['last_login'] ? date('M j, H:i', strtotime($u['last_login'])) : 'Never' ?></div>
                                <div style="font-size:11px; color:#86868B;"><?= $u['last_ip'] ?? 'No IP' ?></div>
                            </td>
                            <td style="text-align:right;">
                                <div class="action-row">
                                    <?php if($u['is_verified'] == 0): ?>
                                        <a href="?send_verify=<?= $u['id'] ?>" class="icon-btn" style="color:var(--ios-orange); border-color:var(--ios-orange);" title="Send Verify Link" onclick="return confirm('Send verification email?')"><i class="fa-solid fa-paper-plane"></i></a>
                                    <?php endif; ?>
                                    
                                    <button class="icon-btn" onclick='openEdit(<?= json_encode($u) ?>)' title="Edit Global Rates & Info"><i class="fa-solid fa-pen"></i></button>
                                    <button class="icon-btn" onclick="openFunds(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" title="Manage Funds"><i class="fa-solid fa-wallet"></i></button>
                                    
                                    <?php if($u['status']=='active'): ?>
                                        <button class="icon-btn" style="color:var(--ios-red);" onclick="openBanModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" title="Ban User"><i class="fa-solid fa-ban"></i></button>
                                    <?php else: ?>
                                        <a href="?unban_id=<?= $u['id'] ?>" class="icon-btn" style="color:var(--ios-green); border-color:var(--ios-green);" title="Unban" onclick="return confirm('Unban and Verify user?')"><i class="fa-solid fa-check"></i></a>
                                    <?php endif; ?>

                                    <div class="dropdown">
                                        <button class="icon-btn" onclick="toggleDrop(this)"><i class="fa-solid fa-ellipsis"></i></button>
                                        <div class="dropdown-content">
                                            <button onclick="openSpecificRate(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" style="color:var(--ios-blue);"><i class="fa-solid fa-tags"></i> Specific Custom Rate</button>
                                            
                                            <a href="?login_as=<?= $u['id'] ?>" onclick="return confirm('Ghost Login as this user?')"><i class="fa-solid fa-mask"></i> Login as User</a>
                                            <button onclick="openMail(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>')"><i class="fa-solid fa-envelope"></i> Send Email</button>
                                            <button onclick="openPass(<?= $u['id'] ?>)"><i class="fa-solid fa-key"></i> Reset Password</button>
                                            <a href="?delete_id=<?= $u['id'] ?>" style="color:var(--ios-red);" onclick="return confirm('Irreversible Action. Delete User?')"><i class="fa-solid fa-trash"></i> Delete User</a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <span style="font-size:13px; color:var(--ios-text-sec);">Page <b><?= $page ?></b> of <?= $total_pages ?></span>
            <div style="display:flex; gap:8px;">
                <?php if($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>" class="ios-btn" style="background:#fff; border:1px solid #D1D1D6;">Previous</a><?php endif; ?>
                <?php if($page < $total_pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>" class="ios-btn" style="background:#fff; border:1px solid #D1D1D6;">Next</a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('editModal')" style="float:right; cursor:pointer; font-size:20px;">&times;</span>
        <h3 style="margin:0 0 20px 0;">Edit User (Global Rates)</h3>
        <form method="POST">
            <input type="hidden" name="edit_user" value="1"><input type="hidden" name="user_id" id="edit_uid">
            <div class="form-row">
                <div><label class="form-label">Full Name</label><input type="text" name="name" id="edit_name" class="ios-input" style="width:100%;" required></div>
                <div><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="ios-input" style="width:100%;" required></div>
            </div>
            <div class="form-group" style="margin-top:15px;">
                <label class="form-label">Role</label>
                <select name="role" id="edit_role" class="ios-select" style="width:100%;">
                    <option value="user">User</option><option value="admin">Admin</option><option value="staff">Staff</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Global Rates</label>
                <div style="display:flex; gap:10px;">
                    <select name="rate_type" id="edit_rate_type" class="ios-select"><option value="discount">Discount (-)</option><option value="premium">Surcharge (+)</option></select>
                    <input type="number" name="rate_value" id="edit_rate_val" class="ios-input" placeholder="%" step="0.01" style="flex:1;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Admin Note</label>
                <textarea name="admin_note" id="edit_note" class="ios-input" rows="2" style="width:100%;"></textarea>
            </div>
            <div style="margin-top:15px; display:flex; flex-direction:column; gap:10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="is_verified_badge" id="edit_badge"> <span style="font-size:14px; color:#0071E3;">Verified Badge (Blue Tick)</span></label>
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="is_verified" id="edit_email_verified"> <span style="font-size:14px; color:#34C759;">Email Verified Status</span></label>
            </div>
            <button type="submit" class="ios-btn btn-primary" style="width:100%; justify-content:center; margin-top:20px;">Save Changes</button>
        </form>
    </div>
</div>

<div id="specificRateModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('specificRateModal')" style="float:right; cursor:pointer; font-size:20px;">&times;</span>
        <h3 style="margin:0 0 10px 0;">Specific Custom Rate</h3>
        <p id="rateUserName" style="margin-bottom:15px; color:#86868B;"></p>
        
        <form method="POST">
            <input type="hidden" name="save_specific_rate" value="1">
            <input type="hidden" name="user_id" id="specific_rate_uid">
            
            <div class="ios-segmented-control">
                <label>
                    <input type="radio" name="target_type" value="provider" onchange="updateTargetLevel()">
                    <span>1. Provider</span>
                </label>
                <label>
                    <input type="radio" name="target_type" value="category" onchange="updateTargetLevel()">
                    <span>2. Categories</span>
                </label>
                <label>
                    <input type="radio" name="target_type" value="service" onchange="updateTargetLevel()" checked>
                    <span>3. Services</span>
                </label>
            </div>
            
            <div class="rate-step" id="wrap_provider" style="display:none;">
                <label class="form-label">Select Provider</label>
                <select name="provider_id" id="rate_provider" class="ios-select" style="width:100%; background:#fff; border:1px solid #D2D2D7;" onchange="loadCategoriesFromProvider()">
                    <option value="">-- Select Provider --</option>
                    <?php foreach($providers_data as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="rate-step" id="wrap_category">
                <label class="form-label">Select API Provider Categories (Multi-Select)</label>
                
                <div class="custom-dropdown" id="cat_dropdown_wrapper">
                    <div class="custom-dropdown-header" onclick="toggleCatDropdown()">
                        <span id="cat_dropdown_text">-- Select Categories (0) --</span>
                        <i class="fa-solid fa-chevron-down" style="color:#86868B;"></i>
                    </div>
                    <div class="custom-dropdown-body" id="cat_dropdown_body">
                        <input type="text" id="cat_search" class="ios-input" placeholder="Search category..." onkeyup="filterCategories()">
                        <ul class="custom-dropdown-list" id="cat_list">
                            <li style="padding:12px; color:#888;">Select a Provider First</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="rate-step" id="wrap_service">
                <label class="form-label">Select Services (My Rates) (Multi-Select)</label>
                
                <div class="custom-dropdown" id="service_dropdown_wrapper">
                    <div class="custom-dropdown-header" onclick="toggleServiceDropdown()">
                        <span id="service_dropdown_text">-- Select Services (0) --</span>
                        <i class="fa-solid fa-chevron-down" style="color:#86868B;"></i>
                    </div>
                    <div class="custom-dropdown-body" id="service_dropdown_body">
                        <input type="text" id="service_search" class="ios-input" placeholder="Search service by name or ID..." onkeyup="filterServices()">
                        <ul class="custom-dropdown-list" id="service_list">
                            <li style="padding:12px; color:#888;">Select categories first</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top:20px;">
                <label class="form-label">Discount or Surcharge (%)</label>
                <div style="display:flex; gap:10px;">
                    <select name="rate_type" id="m_rate_type" class="ios-select" style="background:#F5F5F7;" onchange="updatePreview()">
                        <option value="discount">Discount (-)</option>
                        <option value="premium">Surcharge (+)</option>
                    </select>
                    <input type="number" name="rate_value" id="m_rate_value" class="ios-input" placeholder="%" step="0.01" style="flex:1; background:#F5F5F7;" required oninput="updatePreview()">
                </div>
                
                <div id="rate_preview" class="preview-box">
                    Enter percentage to see real-time price preview.
                </div>
            </div>
            <button type="submit" class="ios-btn btn-primary" style="width:100%; justify-content:center; margin-top:10px; padding:14px; font-size:15px;">Save Custom Rate(s)</button>
        </form>
    </div>
</div>

<div id="banModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('banModal')" style="float:right; cursor:pointer;">&times;</span>
        <h3 style="margin:0 0 10px 0; color:var(--ios-red);">Ban User</h3>
        <p id="banUserName" style="margin-bottom:20px; color:#86868B;"></p>
        <form method="POST">
            <input type="hidden" name="ban_user_action" value="1"><input type="hidden" name="user_id" id="ban_uid">
            <label class="form-label">Reason</label>
            <textarea name="ban_reason" class="ios-input" rows="3" style="width:100%;" required placeholder="Reason for suspension..."></textarea>
            <div style="margin-top:15px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="show_contact" value="1"> <span style="font-size:14px;">Allow WhatsApp Contact Support</span></label>
            </div>
            <button type="submit" class="ios-btn btn-danger" style="width:100%; justify-content:center; margin-top:20px;">Confirm Ban</button>
        </form>
    </div>
</div>

<div id="fundsModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('fundsModal')" style="float:right; cursor:pointer;">&times;</span>
        <h3 style="margin:0 0 10px 0;">Manage Wallet</h3>
        <p id="fundUser" style="margin-bottom:20px; color:#86868B;"></p>
        <form method="POST">
            <input type="hidden" name="update_balance" value="1"><input type="hidden" name="user_id" id="fund_uid">
            <div class="form-group"><label class="form-label">Type</label><select name="type" class="ios-select" style="width:100%;"><option value="add">Add Funds (+)</option><option value="deduct">Deduct Funds (-)</option></select></div>
            <div class="form-group"><label class="form-label">Amount</label><input type="number" name="amount" class="ios-input" style="width:100%;" step="any" required></div>
            <div class="form-group"><label class="form-label">Note</label><input type="text" name="reason" class="ios-input" style="width:100%;" placeholder="e.g. Refund" required></div>
            <button type="submit" class="ios-btn btn-primary" style="width:100%; justify-content:center;">Process Transaction</button>
        </form>
    </div>
</div>

<div id="mailModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('mailModal')" style="float:right; cursor:pointer;">&times;</span>
        <h3 style="margin:0 0 10px 0;">Send Email</h3>
        <p id="mailUser" style="margin-bottom:20px; color:#86868B;"></p>
        <form method="POST">
            <input type="hidden" name="send_single_mail" value="1"><input type="hidden" name="user_id" id="mail_uid">
            <div class="form-group"><label class="form-label">Subject</label><input type="text" name="subject" class="ios-input" style="width:100%;" required></div>
            <div class="form-group"><label class="form-label">Message</label><textarea name="message" class="ios-input" rows="4" style="width:100%;" required></textarea></div>
            <button type="submit" class="ios-btn btn-primary" style="width:100%; justify-content:center;">Send Message</button>
        </form>
    </div>
</div>

<div id="passModal" class="modal-backdrop">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('passModal')" style="float:right; cursor:pointer;">&times;</span>
        <h3 style="margin:0 0 20px 0;">Reset Password</h3>
        <form method="POST">
            <input type="hidden" name="change_pass" value="1"><input type="hidden" name="user_id" id="pass_uid">
            <div class="form-group"><label class="form-label">New Password</label><input type="text" name="new_password" class="ios-input" style="width:100%;" minlength="6" required></div>
            <button type="submit" class="ios-btn btn-primary" style="width:100%; justify-content:center;">Update Password</button>
        </form>
    </div>
</div>

<script>
// JSON Data safely passed from PHP to JS
const servicesData = <?= json_encode($services_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// State Tracking
let providerCats = [];
let currentServices = [];

let selectedCategories = new Set();
let selectedServices = new Set();
let selectedServiceCosts = {}; // ADDED FOR PROFIT LOGIC
let selectedServiceRates = {}; 

// Stop Click inside dropdowns from closing them
document.getElementById('cat_dropdown_body').addEventListener('click', e => e.stopPropagation());
document.getElementById('service_dropdown_body').addEventListener('click', e => e.stopPropagation());

function updateTargetLevel() {
    let type = document.querySelector('input[name="target_type"]:checked').value;
    let pWrap = document.getElementById('wrap_provider');
    let cWrap = document.getElementById('wrap_category');
    let sWrap = document.getElementById('wrap_service');
    
    // Always show provider so they can filter
    pWrap.style.display = 'block'; 
    document.getElementById('rate_provider').required = true;
    
    if (type === 'provider') {
        cWrap.style.display = 'none';
        sWrap.style.display = 'none';
    } else if (type === 'category') {
        cWrap.style.display = 'block';
        sWrap.style.display = 'none';
    } else if (type === 'service') {
        cWrap.style.display = 'block';
        sWrap.style.display = 'block';
    }
    updatePreview();
}

function loadCategoriesFromProvider() {
    let provider = document.getElementById('rate_provider').value;
    
    // Clear old selections
    selectedCategories.clear();
    selectedServices.clear();
    selectedServiceCosts = {};
    selectedServiceRates = {};
    document.getElementById('cat_dropdown_text').innerText = '-- Select Categories (0) --';
    document.getElementById('service_dropdown_text').innerText = '-- Select Services (0) --';
    
    if (provider) {
        // Find Provider's Standard Categories directly from servicesData
        providerCats = [...new Set(servicesData.filter(s => s.provider_id == provider).map(s => s.category))];
        renderCatList(providerCats);
    } else {
        providerCats = [];
        document.getElementById('cat_list').innerHTML = '<li style="padding:12px; color:#888;">Select a Provider First</li>';
        document.getElementById('service_list').innerHTML = '<li style="padding:12px; color:#888;">Select categories first</li>';
    }
    updatePreview();
}

// App Icon Matcher
function getAppIcon(name) {
    let n = name.toLowerCase();
    if(n.includes('instagram') || n.includes('ig')) return '<i class="fa-brands fa-instagram" style="color:#E1306C; font-size:16px;"></i>';
    if(n.includes('facebook') || n.includes('fb')) return '<i class="fa-brands fa-facebook" style="color:#1877F2; font-size:16px;"></i>';
    if(n.includes('tiktok')) return '<i class="fa-brands fa-tiktok" style="color:#000000; font-size:16px;"></i>';
    if(n.includes('youtube') || n.includes('yt')) return '<i class="fa-brands fa-youtube" style="color:#FF0000; font-size:16px;"></i>';
    if(n.includes('twitter') || n.includes('x.com')) return '<i class="fa-brands fa-x-twitter" style="color:#000000; font-size:16px;"></i>';
    if(n.includes('telegram') || n.includes('tg')) return '<i class="fa-brands fa-telegram" style="color:#0088cc; font-size:16px;"></i>';
    if(n.includes('spotify')) return '<i class="fa-brands fa-spotify" style="color:#1DB954; font-size:16px;"></i>';
    if(n.includes('snapchat')) return '<i class="fa-brands fa-snapchat" style="color:#FFFC00; text-shadow:0 0 1px #000; font-size:16px;"></i>';
    return '<i class="fa-solid fa-folder-open" style="color:var(--ios-blue); font-size:16px;"></i>';
}

// === CUSTOM MULTI-SELECT CATEGORY DROPDOWN LOGIC ===
function toggleCatDropdown() {
    let body = document.getElementById('cat_dropdown_body');
    body.classList.toggle('show');
    if (body.classList.contains('show')) document.getElementById('cat_search').focus();
}

function filterCategories() {
    let q = document.getElementById('cat_search').value.toLowerCase();
    let filtered = providerCats.filter(c => c.toLowerCase().includes(q));
    renderCatList(filtered);
}

function renderCatList(arr) {
    let list = document.getElementById('cat_list');
    list.innerHTML = '';
    
    if (arr.length === 0) {
        list.innerHTML = '<li style="padding:12px; color:#888;">No categories found</li>';
        return;
    }
    
    let selectAllLi = document.createElement('li');
    selectAllLi.innerHTML = `<label style="font-weight:bold; color:var(--ios-blue);">
        <input type="checkbox" id="cat_select_all" onchange="toggleAllCats(this, '${arr.join('||')}')" style="margin-right:8px;"> Select / Deselect All Listed
    </label>`;
    list.appendChild(selectAllLi);

    arr.forEach(c => {
        let li = document.createElement('li');
        let isChecked = selectedCategories.has(c) ? 'checked' : '';
        li.innerHTML = `<label>
            <input type="checkbox" name="categories[]" value="${c}" class="cat-cb" ${isChecked} onchange="handleCatChange('${c.replace(/'/g, "\\'")}', this.checked)" style="margin-right:8px;">
            <span>${getAppIcon(c)} ${c}</span>
        </label>`;
        list.appendChild(li);
    });
}

function handleCatChange(cat, isChecked) {
    if(isChecked) selectedCategories.add(cat);
    else selectedCategories.remove(cat);
    updateCatUI();
}

function toggleAllCats(cb, arrStr) {
    let arr = arrStr.split('||');
    arr.forEach(c => {
        if(cb.checked) selectedCategories.add(c);
        else selectedCategories.delete(c);
    });
    let cbs = document.querySelectorAll('.cat-cb');
    cbs.forEach(el => el.checked = cb.checked);
    updateCatUI();
}

function updateCatUI() {
    document.getElementById('cat_dropdown_text').innerHTML = `<b style="color:var(--ios-blue);">${selectedCategories.size} Categories Selected</b>`;
    
    // Refresh Services based on selected Categories
    let provider = document.getElementById('rate_provider').value;
    currentServices = servicesData.filter(s => s.provider_id == provider && selectedCategories.has(s.category));
    
    // Remove unselected from Service Set
    let activeSvcIds = new Set(currentServices.map(s => s.id));
    selectedServices.forEach(id => {
        if(!activeSvcIds.has(id)) {
            selectedServices.delete(id);
            delete selectedServiceRates[id];
            delete selectedServiceCosts[id]; // UPDATED
        }
    });
    
    updateServiceUI();
    renderServiceList(currentServices);
}


// === CUSTOM MULTI-SELECT SERVICE DROPDOWN LOGIC ===
function toggleServiceDropdown() {
    let body = document.getElementById('service_dropdown_body');
    body.classList.toggle('show');
    if (body.classList.contains('show')) document.getElementById('service_search').focus();
}

function filterServices() {
    let q = document.getElementById('service_search').value.toLowerCase();
    let filtered = currentServices.filter(s => 
        s.name.toLowerCase().includes(q) || s.id.toString().includes(q)
    );
    renderServiceList(filtered);
}

function renderServiceList(arr) {
    let list = document.getElementById('service_list');
    list.innerHTML = '';
    
    if (arr.length === 0) {
        list.innerHTML = '<li style="padding:12px; color:#888;">No services found for selected categories</li>';
        return;
    }

    // Build array string of IDs including Base Price for profit calculation
    let idsArrStr = arr.map(s => `${s.id}|${s.base_price}|${s.service_rate}`).join('||');
    
    let selectAllLi = document.createElement('li');
    selectAllLi.innerHTML = `<label style="font-weight:bold; color:var(--ios-blue);">
        <input type="checkbox" id="svc_select_all" onchange="toggleAllSvcs(this, '${idsArrStr}')" style="margin-right:8px;"> Select / Deselect All Listed
    </label>`;
    list.appendChild(selectAllLi);

    arr.forEach(s => {
        let li = document.createElement('li');
        let isChecked = selectedServices.has(s.id) ? 'checked' : '';
        let price = parseFloat(s.service_rate).toFixed(2);
        
        li.innerHTML = `<label style="align-items:flex-start;">
            <input type="checkbox" name="services[]" value="${s.id}" class="svc-cb" style="margin-top:4px; margin-right:8px;" ${isChecked} onchange="handleSvcChange(${s.id}, ${s.base_price}, ${s.service_rate}, this.checked)">
            <div style="flex:1; display:flex; justify-content:space-between; width:100%;">
                <span><b>ID: ${s.id}</b> - ${s.name}</span>
                <span style="color:var(--ios-green); font-weight:700; white-space:nowrap; margin-left:10px;">Rs ${price}</span>
            </div>
        </label>`;
        list.appendChild(li);
    });
}

function handleSvcChange(id, cost, rate, isChecked) {
    if(isChecked) {
        selectedServices.add(id);
        selectedServiceCosts[id] = parseFloat(cost);
        selectedServiceRates[id] = parseFloat(rate);
    } else {
        selectedServices.delete(id);
        delete selectedServiceCosts[id];
        delete selectedServiceRates[id];
    }
    updateServiceUI();
}

function toggleAllSvcs(cb, arrStr) {
    let arr = arrStr.split('||');
    arr.forEach(item => {
        let parts = item.split('|');
        let id = parseInt(parts[0]);
        let cost = parseFloat(parts[1]);
        let rate = parseFloat(parts[2]);
        if(cb.checked) {
            selectedServices.add(id);
            selectedServiceCosts[id] = cost;
            selectedServiceRates[id] = rate;
        } else {
            selectedServices.delete(id);
            delete selectedServiceCosts[id];
            delete selectedServiceRates[id];
        }
    });
    let cbs = document.querySelectorAll('.svc-cb');
    cbs.forEach(el => el.checked = cb.checked);
    updateServiceUI();
}

function updateServiceUI() {
    document.getElementById('service_dropdown_text').innerHTML = `<b style="color:var(--ios-blue);">${selectedServices.size} Services Selected</b>`;
    updatePreview();
}


// === 🚀 LIVE REAL TIME PROFIT CALCULATOR LOGIC ===
function updatePreview() {
    let val = parseFloat(document.getElementById('m_rate_value').value) || 0;
    let type = document.getElementById('m_rate_type').value;
    let previewEl = document.getElementById('rate_preview');
    let targetType = document.querySelector('input[name="target_type"]:checked').value;
    
    if (val === 0) {
        previewEl.innerHTML = "Enter percentage to see real-time price preview.";
        previewEl.style.color = "var(--ios-text-sec)";
        return;
    }

    if (targetType === 'service') {
        if (selectedServices.size === 0) {
            previewEl.innerHTML = "Please select at least one service above to see preview.";
            previewEl.style.color = "var(--ios-orange)";
            return;
        }
        
        // Single Service Detail View
        if (selectedServices.size === 1) {
            let sId = Array.from(selectedServices)[0];
            let cost = selectedServiceCosts[sId];
            let rate = selectedServiceRates[sId];
            let profit = rate - cost;

            if (profit <= 0) {
                previewEl.innerHTML = `<span style="color:var(--ios-red);">Warning: Base Price is equal to or less than API Cost. Discount logic won't apply to avoid loss.</span>`;
                return;
            }

            let finalPercent = type === 'discount' ? -val : val;
            let adjustedProfit = profit * (1 + (finalPercent / 100));
            let finalPrice = cost + adjustedProfit;
            let actualProfit = (finalPrice - cost).toFixed(2);
            
            if (type === 'discount') {
                previewEl.innerHTML = `API Cost: Rs ${cost.toFixed(2)} | Current Profit: Rs ${profit.toFixed(2)}<br><br>Selling Rate: Rs ${rate.toFixed(2)} &nbsp;➔&nbsp; <b style="font-size:16px; color:var(--ios-green);">New Price: Rs ${finalPrice.toFixed(2)}</b> <br><small style="color:var(--ios-green); font-weight:600; display:block; margin-top:4px;"><i class="fa-solid fa-arrow-trend-down"></i> User gets discount, Your New Profit is safe: Rs ${actualProfit}</small>`;
            } else {
                previewEl.innerHTML = `API Cost: Rs ${cost.toFixed(2)} | Current Profit: Rs ${profit.toFixed(2)}<br><br>Selling Rate: Rs ${rate.toFixed(2)} &nbsp;➔&nbsp; <b style="font-size:16px; color:var(--ios-red);">New Price: Rs ${finalPrice.toFixed(2)}</b> <br><small style="color:var(--ios-red); font-weight:600; display:block; margin-top:4px;"><i class="fa-solid fa-arrow-trend-up"></i> Price increased, Your New Profit: Rs ${actualProfit}</small>`;
            }
        } else {
            // Multiple Services Summary
            let verb = type === 'discount' ? 'decrease' : 'increase';
            let clr = type === 'discount' ? 'var(--ios-green)' : 'var(--ios-red)';
            previewEl.innerHTML = `<b style="font-size:15px; color:${clr};">${selectedServices.size} Services Selected</b><br><small style="color:${clr}; display:block; margin-top:4px;">PROFIT MARGINS of selected services will ${verb} by ${val}% (API Costs remain safe 100%)</small>`;
        }

    } else if (targetType === 'category') {
        if (selectedCategories.size === 0) {
            previewEl.innerHTML = "Please select at least one category above to see preview.";
            previewEl.style.color = "var(--ios-orange)";
            return;
        }
        let verb = type === 'discount' ? 'decrease' : 'increase';
        let clr = type === 'discount' ? 'var(--ios-green)' : 'var(--ios-red)';
        previewEl.innerHTML = `<b style="font-size:15px; color:${clr};">${selectedCategories.size} Categories Selected</b><br><small style="color:${clr}; display:block; margin-top:4px;">Profit margins of all services inside these categories will ${verb} by ${val}%</small>`;
        
    } else {
        // Generic Provider Preview
        let verb = type === 'discount' ? 'decrease' : 'increase';
        let clr = type === 'discount' ? 'var(--ios-green)' : 'var(--ios-red)';
        let pVal = document.getElementById('rate_provider').value;
        if(pVal) {
            previewEl.innerHTML = `<b style="font-size:15px; color:${clr};">Entire Provider Selected</b><br><small style="color:${clr}; display:block; margin-top:4px;">Profit margins of ALL services linked to this provider will ${verb} by ${val}%</small>`;
        } else {
            previewEl.innerHTML = "Select a provider above.";
        }
    }
}

// Close Dropdowns on outside click
window.onclick = function(e) {
    if (!e.target.closest('#cat_dropdown_wrapper')) {
        let cBody = document.getElementById('cat_dropdown_body');
        if(cBody) cBody.classList.remove('show');
    }
    if (!e.target.closest('#service_dropdown_wrapper')) {
        let sBody = document.getElementById('service_dropdown_body');
        if(sBody) sBody.classList.remove('show');
    }
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content').forEach(el => el.classList.remove('show'));
    }
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
    }
}

// Modal Handlers
function openSpecificRate(id, name) { 
    document.getElementById('specific_rate_uid').value = id; 
    document.getElementById('rateUserName').innerText = name; 
    
    // Reset Everything
    document.querySelector('input[name="target_type"][value="service"]').checked = true;
    document.getElementById('rate_provider').value = '';
    document.getElementById('m_rate_value').value = '';
    
    selectedCategories.clear();
    selectedServices.clear();
    selectedServiceCosts = {};
    selectedServiceRates = {};
    
    document.getElementById('cat_dropdown_text').innerText = '-- Select Categories (0) --';
    document.getElementById('service_dropdown_text').innerText = '-- Select Services (0) --';
    
    document.getElementById('cat_list').innerHTML = '<li style="padding:12px; color:#888;">Select a Provider First</li>';
    document.getElementById('service_list').innerHTML = '<li style="padding:12px; color:#888;">Select categories first</li>';

    updateTargetLevel();
    openModal('specificRateModal'); 
}

function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function toggleDrop(btn) {
    let content = btn.nextElementSibling;
    let all = document.querySelectorAll('.dropdown-content');
    all.forEach(el => { if(el !== content) el.classList.remove('show'); });
    content.classList.toggle('show');
}

function openEdit(u) {
    document.getElementById('edit_uid').value = u.id;
    document.getElementById('edit_name').value = u.name;
    document.getElementById('edit_email').value = u.email;
    document.getElementById('edit_role').value = u.role || 'user';
    document.getElementById('edit_note').value = u.admin_note;
    document.getElementById('edit_badge').checked = (u.is_verified_badge == 1);
    document.getElementById('edit_email_verified').checked = (u.is_verified == 1);
    let rate = parseFloat(u.custom_rate);
    if(rate < 0) {
        document.getElementById('edit_rate_type').value = 'discount';
        document.getElementById('edit_rate_val').value = Math.abs(rate);
    } else {
        document.getElementById('edit_rate_type').value = 'premium';
        document.getElementById('edit_rate_val').value = rate;
    }
    openModal('editModal');
}

function openBanModal(id, name) { document.getElementById('ban_uid').value = id; document.getElementById('banUserName').innerText = name; openModal('banModal'); }
function openFunds(id, name) { document.getElementById('fund_uid').value = id; document.getElementById('fundUser').innerText = name; openModal('fundsModal'); }
function openMail(id, email) { document.getElementById('mail_uid').value = id; document.getElementById('mailUser').innerText = email; openModal('mailModal'); }
function openPass(id) { document.getElementById('pass_uid').value = id; openModal('passModal'); }
</script>

<?php include '_footer.php'; ?>