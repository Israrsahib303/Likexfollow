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

} catch (Exception $e) { /* Silent */ }

// --- 🤖 FIXED: AUTO-BAN LOGIC (Send Mail FIRST, Then Ban) ---
// 1. Find candidates
$ban_msg = "Account Suspended: Unverified for 30+ Days. Check Spam Folder.";
$stale_users = $db->query("SELECT id, name, email FROM users WHERE is_verified = 0 AND created_at < (NOW() - INTERVAL 30 DAY) AND status = 'active'")->fetchAll();

if (!empty($stale_users)) {
    foreach ($stale_users as $su) {
        // 2. Send Email
        $subject = "Account Suspended - Action Required";
        $body = "Hi " . $su['name'] . ",<br><br>Your account has been suspended because it remained unverified for over 30 days.<br>Please contact support or verify your email to reactivate.";
        sendEmail($su['email'], $su['name'], $subject, $body);

        // 3. Ban User
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

// F. UNBAN USER (FIXED: Sets Verified=1 to prevent auto-ban loop)
if (isset($_GET['unban_id'])) {
    $uid = (int)$_GET['unban_id'];
    // Setting is_verified = 1 ensures the auto-ban script doesn't re-ban them immediately
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

// J. LOGIN AS USER (FIXED: Added logic)
if (isset($_GET['login_as'])) {
    $target_uid = (int)$_GET['login_as'];
    $target_user = $db->query("SELECT * FROM users WHERE id = $target_uid")->fetch();
    
    if ($target_user) {
        // Log them in as the user
        $_SESSION['user_id'] = $target_user['id'];
        $_SESSION['role'] = $target_user['role'];
        $_SESSION['email'] = $target_user['email'];
        // Redirect to User Dashboard
        echo "<script>window.location.href='../user/index.php';</script>"; exit;
    } else {
        $_SESSION['flash_error'] = "User not found.";
        echo "<script>window.location.href='users.php';</script>"; exit;
    }
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

    /* GLOBAL RESET & LAYOUT SAFETY */
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

    /* CONTAINER SAFETY */
    .ios-container {
        width: min(100%, 1200px);
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* STATS ROW */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        padding: 24px;
        box-shadow: var(--ios-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0,0,0,0.02);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    .stat-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--ios-text-sec);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--ios-text);
        letter-spacing: -0.5px;
    }
    
    .stat-icon {
        font-size: 24px;
        margin-bottom: 10px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .si-blue { background: rgba(0, 113, 227, 0.1); color: var(--ios-blue); }
    .si-green { background: rgba(52, 199, 89, 0.1); color: var(--ios-green); }
    .si-red { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    .si-orange { background: rgba(255, 149, 0, 0.1); color: var(--ios-orange); }

    /* TOOLBAR */
    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--ios-card);
        padding: 16px;
        border-radius: var(--ios-radius);
        margin-bottom: 24px;
        box-shadow: var(--ios-shadow);
        flex-wrap: wrap;
        gap: 16px;
    }

    .search-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        max-width: 500px;
    }

    .ios-input, .ios-select {
        background: #F5F5F7;
        border: 1px solid transparent;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        color: var(--ios-text);
        outline: none;
        transition: all 0.2s;
    }
    
    .ios-input:focus, .ios-select:focus {
        background: #FFF;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
    }

    .ios-btn {
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-primary { background: var(--ios-blue); color: #fff; }
    .btn-primary:hover { background: var(--ios-blue-hover); transform: scale(1.02); }
    
    .btn-danger { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    .btn-danger:hover { background: var(--ios-red); color: #fff; transform: scale(1.02); }

    /* DATA TABLE */
    .table-responsive {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        box-shadow: var(--ios-shadow);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .table-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .ios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px; /* Prevent crunching */
    }

    .ios-table th {
        text-align: left;
        padding: 16px 20px;
        font-size: 12px;
        font-weight: 600;
        color: var(--ios-text-sec);
        text-transform: uppercase;
        border-bottom: 1px solid var(--ios-border);
        background: rgba(245, 245, 247, 0.5);
    }

    .ios-table td {
        padding: 16px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #F0F0F0;
        font-size: 14px;
        color: var(--ios-text);
        transition: background 0.1s;
    }

    .ios-table tr:hover td {
        background: #FAFAFC;
    }

    .ios-table tr:last-child td { border-bottom: none; }

    /* TABLE COMPONENTS */
    .user-info { display: flex; align-items: center; gap: 14px; }
    .avatar-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #E0E7FF 0%, #F5F7FF 100%);
        color: var(--ios-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .u-meta h4 { margin: 0; font-size: 14px; font-weight: 600; color: var(--ios-text); }
    .u-meta span { display: block; font-size: 12px; color: var(--ios-text-sec); margin-top: 2px; }
    .search-highlight { background-color: #FFF2CC; color: #B45309; border-radius: 2px; padding: 0 2px; }

    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }
    
    .badge-user { background: #F2F2F7; color: #636366; }
    .badge-admin { background: #E4EFFF; color: #0071E3; }
    .badge-staff { background: #F0FDF4; color: #15803D; }

    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .st-active { background: rgba(52, 199, 89, 0.1); color: var(--ios-green); }
    .st-banned { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    
    .action-row { display: flex; gap: 8px; justify-content: flex-end; }
    .icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 1px solid #D1D1D6;
        color: #636366;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .icon-btn:hover { background: #F2F2F7; transform: translateY(-1px); color: #000; }
    .btn-login-ghost { color: #8E8E93; } .btn-login-ghost:hover { color: var(--ios-blue); border-color: var(--ios-blue); }
    
    /* DROPDOWN */
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 36px;
        background-color: white;
        min-width: 180px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        border-radius: 12px;
        z-index: 100;
        padding: 8px;
        border: 1px solid rgba(0,0,0,0.04);
        animation: fadein 0.2s;
    }
    @keyframes fadein { from { opacity:0; transform: translateY(5px); } to { opacity:1; transform:translateY(0); } }
    .dropdown-content a, .dropdown-content button {
        color: var(--ios-text);
        padding: 10px 12px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        border-radius: 8px;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
    }
    .dropdown-content a:hover, .dropdown-content button:hover { background-color: #F5F5F7; }
    .show { display: block; }

    /* PAGINATION */
    .pagination {
        padding: 15px 20px;
        border-top: 1px solid #F0F0F0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* MODALS */
    .modal-backdrop {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(8px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: #fff;
        padding: 30px;
        border-radius: 20px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        position: relative;
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes slideUp { from { transform: scale(0.95) translateY(10px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--ios-text-sec); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

    /* TOASTS */
    .toast {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toast-success { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
    .toast-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
    
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .ios-container { padding: 15px 10px; }
        .stat-card { padding: 16px; }
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
        <div class="search-wrap">
            <input type="text" class="ios-input" placeholder="Search name, email, ID..." id="searchInput" style="width:100%;" value="<?= htmlspecialchars($search) ?>" onkeypress="if(event.key==='Enter') window.location.href='?search='+this.value">
            <select class="ios-select" onchange="window.location.href='?role='+this.value">
                <option value="">All Roles</option>
                <option value="user" <?= $role_filter=='user'?'selected':'' ?>>User</option>
                <option value="admin" <?= $role_filter=='admin'?'selected':'' ?>>Admin</option>
            </select>
        </div>
        
        <?php if($stats['unverified'] > 0): ?>
        <form method="POST" onsubmit="return confirm('Send verification email to <?= $stats['unverified'] ?> users?');" style="margin:0;">
            <button type="submit" name="bulk_verify_mail" class="ios-btn btn-danger">
                <i class="fa-solid fa-paper-plane"></i> Email All Pending (<?= $stats['unverified'] ?>)
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <div class="table-scroll">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th>User Identity</th>
                        <th>Role / Rate</th>
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
                            if($u['custom_rate'] < 0) $rate_display = "<span style='color:var(--ios-green); font-weight:700; font-size:11px;'>".abs($u['custom_rate'])."% OFF</span>";
                            if($u['custom_rate'] > 0) $rate_display = "<span style='color:var(--ios-red); font-weight:700; font-size:11px;'>+".abs($u['custom_rate'])."% UP</span>";
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
                                    
                                    <button class="icon-btn" onclick='openEdit(<?= json_encode($u) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                                    <button class="icon-btn" onclick="openFunds(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" title="Funds"><i class="fa-solid fa-wallet"></i></button>
                                    
                                    <?php if($u['status']=='active'): ?>
                                        <button class="icon-btn" style="color:var(--ios-red);" onclick="openBanModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')" title="Ban"><i class="fa-solid fa-ban"></i></button>
                                    <?php else: ?>
                                        <a href="?unban_id=<?= $u['id'] ?>" class="icon-btn" style="color:var(--ios-green); border-color:var(--ios-green);" title="Unban" onclick="return confirm('Unban and Verify user?')"><i class="fa-solid fa-check"></i></a>
                                    <?php endif; ?>

                                    <div class="dropdown">
                                        <button class="icon-btn" onclick="toggleDrop(this)"><i class="fa-solid fa-ellipsis"></i></button>
                                        <div class="dropdown-content">
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
        <h3 style="margin:0 0 20px 0;">Edit User</h3>
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
                <label class="form-label">VIP Rates</label>
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
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function toggleDrop(btn) {
    let content = btn.nextElementSibling;
    let all = document.querySelectorAll('.dropdown-content');
    all.forEach(el => { if(el !== content) el.classList.remove('show'); });
    content.classList.toggle('show');
}

// Close Dropdowns on outside click
window.onclick = function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content').forEach(el => el.classList.remove('show'));
    }
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
    }
}

// Fill Modals
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