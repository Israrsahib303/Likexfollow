<?php
include '_header.php'; 
requireAdmin();

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
    
    // 🔥 NEW: WhatsApp Contact Permission for Banned Users
    if (!in_array('ban_show_contact', $cols)) $db->exec("ALTER TABLE users ADD COLUMN ban_show_contact TINYINT(1) DEFAULT 0");

    // Role
    if (!in_array('role', $cols)) $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin','staff') NOT NULL DEFAULT 'user'");

} catch (Exception $e) { /* Silent */ }

// --- 🤖 AUTO-BAN LOGIC (30 Days Unverified) ---
$ban_msg = "Account Suspended kindly vierfy to unban check spam folder";
$db->prepare("UPDATE users SET status = 'banned', ban_reason = ? WHERE is_verified = 0 AND created_at < (NOW() - INTERVAL 30 DAY) AND status = 'active'")->execute([$ban_msg]);

// --- 🤖 AUTO-UNBAN LOGIC ---
$db->exec("UPDATE users SET status = 'active', ban_reason = NULL WHERE is_verified = 1 AND status = 'banned' AND ban_reason = '$ban_msg'");


$error = '';
$success = '';

// --- 1. ACTION HANDLERS (With White Page Fix) ---

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
        $body .= "<a href='$verify_link' style='background:#6366f1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Verify Now</a>";
        
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

// E. MANUAL BAN (With WhatsApp Toggle)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ban_user_action'])) {
    $uid = (int)$_POST['user_id'];
    $reason = sanitize($_POST['ban_reason']);
    $show_contact = isset($_POST['show_contact']) ? 1 : 0; 
    
    // Force Logout (Clear token) & Ban
    $db->prepare("UPDATE users SET status = 'banned', ban_reason = ?, ban_show_contact = ?, remember_token = NULL WHERE id = ?")->execute([$reason, $show_contact, $uid]);
    
    $_SESSION['flash_success'] = "User BANNED. Reason: $reason";
    echo "<script>window.location.href='users.php';</script>"; exit;
}

// F. UNBAN USER
if (isset($_GET['unban_id'])) {
    $uid = (int)$_GET['unban_id'];
    $db->prepare("UPDATE users SET status = 'active', ban_reason = NULL, ban_show_contact = 0 WHERE id = ?")->execute([$uid]);
    $_SESSION['flash_success'] = "User Activated successfully.";
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

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { --primary: #6366f1; --bg-body: #f1f5f9; --card: #ffffff; --text: #0f172a; --border: #e2e8f0; --danger: #ef4444; --success: #10b981; }
    body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text); }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-box { background: var(--card); padding: 20px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; border: 1px solid var(--border); transition: 0.3s; }
    .s-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .s-blue { background: #e0e7ff; color: #4338ca; } .s-green { background: #dcfce7; color: #166534; } .s-red { background: #fee2e2; color: #991b1b; } .s-orange { background: #ffedd5; color: #9a3412; }
    .controls-wrap { background: var(--card); padding: 15px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .search-group { display: flex; gap: 10px; flex: 1; max-width: 500px; }
    .inp-modern { padding: 10px 15px; border: 1px solid var(--border); border-radius: 10px; width: 100%; outline: none; transition: 0.2s; font-size: 0.9rem; }
    .btn-x { padding: 10px 20px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
    .bx-primary { background: var(--primary); color: white; } .bx-white { background: white; border: 1px solid var(--border); color: #64748b; } .bx-danger { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
    .table-card { background: var(--card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .x-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    .x-table th { background: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 15px 20px; text-align: left; border-bottom: 1px solid var(--border); }
    .x-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-size: 0.9rem; }
    .user-flex { display: flex; align-items: center; gap: 12px; }
    .u-avatar { width: 42px; height: 42px; background: #e0e7ff; color: #4338ca; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .u-details h4 { margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 5px; }
    .u-details span { font-size: 0.8rem; color: #64748b; display: block; }
    .badge-vip { color: #0ea5e9; font-size: 0.9rem; } .badge-email-verified { color: #10b981; font-size: 0.8rem; margin-left: 4px; } .badge-email-unverified { color: #cbd5e1; font-size: 0.8rem; margin-left: 4px; }
    .act-group { display: flex; gap: 6px; }
    .act-btn { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); background: white; color: #64748b; cursor: pointer; transition: 0.2s; text-decoration: none; }
    .act-btn:hover { background: #f1f5f9; color: var(--primary); border-color: var(--primary); }
    .act-ban { color: #ef4444; border-color: #fecaca; } .act-ban:hover { background: #ef4444; color: white; }
    .act-unban { color: #10b981; border-color: #a7f3d0; } .act-unban:hover { background: #10b981; color: white; }
    .act-mail { color: #f59e0b; border-color: #fcd34d; } .act-mail:hover { background: #f59e0b; color: white; }
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    .modal-box { background: white; width: 100%; max-width: 500px; padding: 30px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); position: relative; animation: slideUp 0.3s ease; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .close-modal { position: absolute; top: 20px; right: 20px; font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
    .close-modal:hover { color: #ef4444; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    .dot-green { background: #10b981; } .dot-red { background: #ef4444; }
</style>

<div class="stats-row">
    <div class="stat-box"><div class="s-icon s-blue"><i class="fa-solid fa-users"></i></div><div><h3><?= $stats['total'] ?></h3><p>Total Users</p></div></div>
    <div class="stat-box"><div class="s-icon s-green"><i class="fa-solid fa-wallet"></i></div><div><h3><?= formatCurrency($stats['wallet_total']) ?></h3><p>Total Funds</p></div></div>
    <div class="stat-box"><div class="s-icon s-red"><i class="fa-solid fa-ban"></i></div><div><h3><?= $stats['banned'] ?></h3><p>Banned</p></div></div>
    <div class="stat-box"><div class="s-icon s-orange"><i class="fa-solid fa-envelope"></i></div><div><h3><?= $stats['unverified'] ?></h3><p>Unverified</p></div></div>
</div>

<div class="controls-wrap">
    <div class="search-group">
        <input type="text" class="inp-modern" placeholder="🔍 Search user..." id="searchInput" value="<?= htmlspecialchars($search) ?>" onkeypress="if(event.key==='Enter') window.location.href='?search='+this.value">
        <select class="inp-modern" style="width:150px;" onchange="window.location.href='?role='+this.value">
            <option value="">All Roles</option>
            <option value="user" <?= $role_filter=='user'?'selected':'' ?>>User</option>
            <option value="admin" <?= $role_filter=='admin'?'selected':'' ?>>Admin</option>
        </select>
    </div>
    <?php if($stats['unverified'] > 0): ?>
    <form method="POST" onsubmit="return confirm('Send verification email to <?= $stats['unverified'] ?> pending users?');" style="margin:0;">
        <button type="submit" name="bulk_verify_mail" class="btn-x bx-danger"><i class="fa-solid fa-paper-plane"></i> Send Bulk Verify (<?= $stats['unverified'] ?>)</button>
    </form>
    <?php endif; ?>
</div>

<?php if($success): ?><div style="padding:15px; background:#ecfdf5; color:#065f46; border-radius:12px; margin-bottom:20px; border:1px solid #a7f3d0;"><i class="fa-solid fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if($error): ?><div style="padding:15px; background:#fef2f2; color:#991b1b; border-radius:12px; margin-bottom:20px; border:1px solid #fecaca;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div><?php endif; ?>

<div class="table-card">
    <div style="overflow-x:auto;">
    <table class="x-table">
        <thead><tr><th>User Profile</th><th>Role / Rate</th><th>Wallet Balance</th><th>Status</th><th>Last Seen</th><th style="text-align:right;">Actions</th></tr></thead>
        <tbody>
            <?php if(empty($users)): ?><tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">No users found.</td></tr><?php else: ?>
                <?php foreach($users as $u): 
                    $initial = !empty($u['name']) ? strtoupper(substr($u['name'], 0, 1)) : '<i class="fa-solid fa-user"></i>';
                    $status_dot = ($u['status']=='active') ? 'dot-green' : 'dot-red';
                    $rate_display = "Standard";
                    if($u['custom_rate'] < 0) $rate_display = "<span style='color:#10b981; font-weight:700;'>".abs($u['custom_rate'])."% OFF</span>";
                    if($u['custom_rate'] > 0) $rate_display = "<span style='color:#ef4444; font-weight:700;'>+".abs($u['custom_rate'])."% High</span>";
                    $role_safe = $u['role'] ?? 'user';
                    $email_icon = ($u['is_verified'] == 1) ? '<i class="fa-solid fa-envelope-circle-check badge-email-verified" title="Email Verified"></i>' : '<i class="fa-solid fa-envelope badge-email-unverified" title="Email Unverified"></i>';
                ?>
                <tr>
                    <td>
                        <div class="user-flex"><div class="u-avatar"><?= $initial ?></div><div class="u-details"><h4><?= htmlspecialchars($u['name'] ?? 'No Name') ?> <?php if($u['is_verified_badge']): ?><i class="fa-solid fa-circle-check badge-vip" title="VIP Badge"></i><?php endif; ?> <?= $email_icon ?></h4><span><?= htmlspecialchars($u['email']) ?></span><?php if(!empty($u['admin_note'])): ?><small style="color:#f59e0b; display:block; margin-top:2px;"><i class="fa-solid fa-note-sticky"></i> <?= htmlspecialchars($u['admin_note']) ?></small><?php endif; ?></div></div>
                    </td>
                    <td><div style="font-weight:600; font-size:0.85rem; margin-bottom:2px; text-transform:uppercase; color:#64748b;"><?= ucfirst($role_safe) ?></div><div style="font-size:0.8rem;"><?= $rate_display ?></div></td>
                    <td><div style="font-weight:800; color:#059669; font-size:1rem;"><?= formatCurrency($u['balance']) ?></div><small style="color:#94a3b8;">Spent: <?= formatCurrency($u['total_spent']??0) ?></small></td>
                    <td><span class="status-dot <?= $status_dot ?>"></span> <?= ucfirst($u['status']) ?> <?php if($u['status'] == 'banned' && !empty($u['ban_reason'])): ?><div style="font-size:0.7rem; color:#ef4444; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($u['ban_reason']) ?>"><?= htmlspecialchars($u['ban_reason']) ?></div><?php endif; ?></td>
                    <td><div style="font-size:0.85rem; color:#334155;"><?= $u['last_login'] ? date('d M, h:i A', strtotime($u['last_login'])) : 'Never' ?></div><small style="color:#94a3b8;"><?= $u['last_ip'] ?? 'No IP' ?></small></td>
                    <td style="text-align:right;">
                        <div class="act-group" style="justify-content:flex-end;">
                            <?php if($u['is_verified'] == 0): ?><a href="?send_verify=<?= $u['id'] ?>" class="act-btn act-mail" title="Send Verification Link" onclick="return confirm('Send verification email?')"><i class="fa-solid fa-paper-plane"></i></a><?php endif; ?>
                            <button type="button" class="act-btn" title="Edit User" onclick='openEdit(<?= json_encode($u) ?>)'><i class="fa-solid fa-pen"></i></button>
                            <button type="button" class="act-btn" title="Manage Funds" onclick="openFunds(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')"><i class="fa-solid fa-coins"></i></button>
                            <?php if($u['status']=='active'): ?><button type="button" class="act-btn act-ban" title="Ban User" onclick="openBanModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')"><i class="fa-solid fa-ban"></i></button><?php else: ?><a href="?unban_id=<?= $u['id'] ?>" class="act-btn act-unban" title="Activate User" onclick="return confirm('Activate user?')"><i class="fa-solid fa-check"></i></a><?php endif; ?>
                            <div class="action-dropdown" style="position:relative;"><button class="act-btn" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display=='block'?'none':'block'"><i class="fa-solid fa-ellipsis-vertical"></i></button><div class="dropdown-menu" style="display:none; position:absolute; right:0; top:40px; background:white; border:1px solid #ddd; border-radius:8px; width:160px; z-index:50; box-shadow:0 5px 15px rgba(0,0,0,0.1);"><button onclick="openMail(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>')" style="display:block; width:100%; text-align:left; border:none; background:none; padding:10px; cursor:pointer; color:#333; font-size:0.85rem;">✉️ Send Custom Mail</button><a href="?login_as=<?= $u['id'] ?>" style="display:block; padding:10px; text-decoration:none; color:#333; font-size:0.85rem; hover:background:#f5f5f5;" onclick="return confirm('Login as user?')">👻 Login As User</a><a href="#" onclick="openPass(<?= $u['id'] ?>)" style="display:block; padding:10px; text-decoration:none; color:#333; font-size:0.85rem;">🔑 Change Pass</a><a href="?delete_id=<?= $u['id'] ?>" style="display:block; padding:10px; text-decoration:none; color:red; font-size:0.85rem;" onclick="return confirm('DELETE PERMANENTLY?')">🗑️ Delete User</a></div></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($total_pages > 1): ?>
    <div style="padding: 15px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fcfcfd;">
        <div style="font-size: 0.85rem; color: #64748b;">Page <b><?= $page ?></b> of <b><?= $total_pages ?></b></div>
        <div style="display: flex; gap: 8px;">
            <?php if($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>" class="btn-x bx-white" style="padding: 6px 12px; font-size: 0.8rem;"><i class="fa-solid fa-chevron-left"></i> Prev</a><?php endif; ?>
            <?php if($page < $total_pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>" class="btn-x bx-white" style="padding: 6px 12px; font-size: 0.8rem;">Next <i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    </div>
</div>

<div id="editModal" class="modal-overlay"><div class="modal-box"><span class="close-modal" onclick="closeModal('editModal')">&times;</span><h3 style="margin-top:0; margin-bottom:20px;">✏️ Edit User Details</h3><form method="POST"><input type="hidden" name="edit_user" value="1"><input type="hidden" name="user_id" id="edit_uid"><div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;"><div><label style="font-size:0.85rem; font-weight:600;">Full Name</label><input type="text" name="name" id="edit_name" class="inp-modern" required></div><div><label style="font-size:0.85rem; font-weight:600;">Email Address</label><input type="email" name="email" id="edit_email" class="inp-modern" required></div></div><br><div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;"><div><label style="font-size:0.85rem; font-weight:600;">Role</label><select name="role" id="edit_role" class="inp-modern"><option value="user">User</option><option value="admin">Admin</option><option value="staff">Staff</option></select></div></div><br><label style="font-size:0.85rem; font-weight:600; color:#4f46e5;">💸 Custom Rate (VIP Settings)</label><div style="display:flex; gap:10px; margin-top:5px; background:#f8fafc; padding:10px; border-radius:10px; border:1px solid #e2e8f0;"><select name="rate_type" id="edit_rate_type" class="inp-modern" style="width:40%;"><option value="discount">Give Discount (-)</option><option value="premium">Increase Price (+)</option></select><input type="number" name="rate_value" id="edit_rate_val" class="inp-modern" placeholder="Percentage (e.g. 10)" step="0.01"></div><br><label style="font-size:0.85rem; font-weight:600;">Admin Note (Private)</label><textarea name="admin_note" id="edit_note" class="inp-modern" rows="2" placeholder="Only admins can see this..."></textarea><div style="margin-top:15px; display:flex; flex-direction:column; gap:8px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="is_verified_badge" id="edit_badge" style="width:18px; height:18px;"><span style="font-weight:600; color:#0ea5e9;">Give Blue Tick (VIP Badge)</span></label><label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="is_verified" id="edit_email_verified" style="width:18px; height:18px;"><span style="font-weight:600; color:#10b981;">Mark Email as Verified (Manual)</span></label></div><button type="submit" class="bx-primary btn-x" style="width:100%; justify-content:center; margin-top:20px;">Save Changes</button></form></div></div>

<div id="banModal" class="modal-overlay"><div class="modal-box"><span class="close-modal" onclick="closeModal('banModal')">&times;</span><h3 style="margin-top:0; color:#ef4444;"><i class="fa-solid fa-ban"></i> Ban User</h3><p id="banUserName" style="color:#64748b; font-size:0.9rem; margin-bottom:20px;"></p><form method="POST"><input type="hidden" name="ban_user_action" value="1"><input type="hidden" name="user_id" id="ban_uid"><label style="font-size:0.85rem; font-weight:600;">Reason for Ban</label><textarea name="ban_reason" class="inp-modern" rows="3" placeholder="e.g. Spamming, Suspicious Activity..." required></textarea><div style="margin-top:10px;"><label style="display:flex; align-items:center; gap:8px; cursor:pointer;"><input type="checkbox" name="show_contact" value="1" style="width:18px; height:18px;"><span style="font-weight:600; color:#166534;"><i class="fa-brands fa-whatsapp"></i> Allow WhatsApp Contact</span></label></div><button type="submit" class="btn-x bx-danger" style="width:100%; justify-content:center; margin-top:15px;">Confirm Ban 🚫</button></form></div></div>

<div id="fundsModal" class="modal-overlay"><div class="modal-box"><span class="close-modal" onclick="closeModal('fundsModal')">&times;</span><h3 style="margin-top:0;">💰 Wallet Manager</h3><p id="fundUser" style="color:#64748b; font-size:0.9rem; margin-bottom:20px;"></p><form method="POST"><input type="hidden" name="update_balance" value="1"><input type="hidden" name="user_id" id="fund_uid"><label style="font-size:0.85rem; font-weight:600;">Action</label><select name="type" class="inp-modern" style="margin-bottom:15px;"><option value="add">➕ Add Money (Credit)</option><option value="deduct">➖ Remove Money (Debit)</option></select><label style="font-size:0.85rem; font-weight:600;">Amount</label><input type="number" name="amount" class="inp-modern" placeholder="e.g. 500" step="any" required style="margin-bottom:15px;"><label style="font-size:0.85rem; font-weight:600;">Reason (For Logs)</label><input type="text" name="reason" class="inp-modern" placeholder="e.g. Bonus / Refund" required style="margin-bottom:20px;"><button type="submit" class="bx-primary btn-x" style="width:100%; justify-content:center;">Update Balance</button></form></div></div>
<div id="mailModal" class="modal-overlay"><div class="modal-box"><span class="close-modal" onclick="closeModal('mailModal')">&times;</span><h3 style="margin-top:0;">✉️ Send Quick Email</h3><p id="mailUser" style="color:#64748b; font-size:0.9rem; margin-bottom:20px;"></p><form method="POST"><input type="hidden" name="send_single_mail" value="1"><input type="hidden" name="user_id" id="mail_uid"><label style="font-size:0.85rem; font-weight:600;">Subject</label><input type="text" name="subject" class="inp-modern" placeholder="Important Notice..." required style="margin-bottom:15px;"><label style="font-size:0.85rem; font-weight:600;">Message</label><textarea name="message" class="inp-modern" rows="4" placeholder="Type your message here..." required style="margin-bottom:20px;"></textarea><button type="submit" class="bx-primary btn-x" style="width:100%; justify-content:center;">Send Email 🚀</button></form></div></div>
<div id="passModal" class="modal-overlay"><div class="modal-box"><span class="close-modal" onclick="closeModal('passModal')">&times;</span><h3 style="margin-top:0;">🔑 Reset Password</h3><form method="POST"><input type="hidden" name="change_pass" value="1"><input type="hidden" name="user_id" id="pass_uid"><label style="font-size:0.85rem; font-weight:600;">New Password</label><input type="text" name="new_password" class="inp-modern" placeholder="Enter new strong password" required minlength="6" style="margin-bottom:20px;"><button type="submit" class="bx-primary btn-x" style="width:100%; justify-content:center;">Update Password</button></form></div></div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openEdit(u) {
    document.getElementById('edit_uid').value = u.id; document.getElementById('edit_name').value = u.name; document.getElementById('edit_email').value = u.email; document.getElementById('edit_role').value = u.role || 'user'; document.getElementById('edit_note').value = u.admin_note; document.getElementById('edit_badge').checked = (u.is_verified_badge == 1); document.getElementById('edit_email_verified').checked = (u.is_verified == 1);
    let rate = parseFloat(u.custom_rate); if(rate < 0) { document.getElementById('edit_rate_type').value = 'discount'; document.getElementById('edit_rate_val').value = Math.abs(rate); } else { document.getElementById('edit_rate_type').value = 'premium'; document.getElementById('edit_rate_val').value = rate; }
    openModal('editModal');
}
function openBanModal(id, name) { document.getElementById('ban_uid').value = id; document.getElementById('banUserName').innerText = 'User: ' + name; openModal('banModal'); }
function openFunds(id, name) { document.getElementById('fund_uid').value = id; document.getElementById('fundUser').innerText = 'For: ' + name; openModal('fundsModal'); }
function openMail(id, email) { document.getElementById('mail_uid').value = id; document.getElementById('mailUser').innerText = 'To: ' + email; openModal('mailModal'); }
function openPass(id) { document.getElementById('pass_uid').value = id; openModal('passModal'); }
window.onclick = function(event) { if (!event.target.matches('.act-btn') && !event.target.matches('.fa-ellipsis-vertical')) { var dropdowns = document.getElementsByClassName("dropdown-menu"); for (var i = 0; i < dropdowns.length; i++) { var openDropdown = dropdowns[i]; if (openDropdown.style.display === 'block') { openDropdown.style.display = 'none'; } } } if(event.target.classList.contains('modal-overlay')) { event.target.style.display = 'none'; } }
</script>
<?php include '_footer.php'; ?>