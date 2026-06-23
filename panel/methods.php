<?php
include '_header.php';

$action = $_GET['action'] ?? 'list';
$method_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// --- Handle Form Submissions (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic Details
    $name = sanitize($_POST['name']);
    $account_name = sanitize($_POST['account_name']);
    $account_number = sanitize($_POST['account_number']);
    $note = sanitize($_POST['note']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $icon_path = sanitize($_POST['icon_path'] ?? 'default.png'); 
    
    // --- NAYE MIN/MAX FIELDS ---
    $min_amount = (float)($_POST['min_amount'] ?? 0);
    $max_amount = (float)($_POST['max_amount'] ?? 0);

    // Auto Settings
    $is_auto = isset($_POST['is_auto']) ? 1 : 0;
    $auto_mail_server = sanitize($_POST['auto_mail_server']);
    $auto_email_user = sanitize($_POST['auto_email_user']);
    $auto_email_pass = sanitize($_POST['auto_email_pass']);

    try {
        if ($action == 'edit' && $method_id) {
            // Update
            $stmt = $db->prepare("
                UPDATE payment_methods 
                SET name = ?, icon_path = ?, account_name = ?, account_number = ?, note = ?, 
                    min_amount = ?, max_amount = ?, is_active = ?, 
                    is_auto = ?, auto_mail_server = ?, auto_email_user = ?, auto_email_pass = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $icon_path, $account_name, $account_number, $note, 
                $min_amount, $max_amount, $is_active, 
                $is_auto, $auto_mail_server, $auto_email_user, $auto_email_pass, 
                $method_id
            ]);
            $success = 'Payment method updated successfully! 🚀';
        } else {
            // Create
            $stmt = $db->prepare("
                INSERT INTO payment_methods (name, icon_path, account_name, account_number, note, 
                                           min_amount, max_amount, is_active, 
                                           is_auto, auto_mail_server, auto_email_user, auto_email_pass) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $icon_path, $account_name, $account_number, $note,
                $min_amount, $max_amount, $is_active,
                $is_auto, $auto_mail_server, $auto_email_user, $auto_email_pass
            ]);
            $success = 'New payment method created successfully! 🎉';
        }
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// --- Handle Deletion ---
if ($action == 'delete' && $method_id) {
    try {
        $stmt = $db->prepare("DELETE FROM payment_methods WHERE id = ?");
        $stmt->execute([$method_id]);
        $success = 'Payment method deleted permanently! 🗑️';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Failed to delete method.';
    }
}

// --- Load Data for Views ---
$method = null;
if (($action == 'edit' || $action == 'add') && $method_id) {
    $stmt = $db->prepare("SELECT * FROM payment_methods WHERE id = ?");
    $stmt->execute([$method_id]);
    $method = $stmt->fetch();
}
if ($action == 'list') {
    $stmt = $db->query("SELECT * FROM payment_methods ORDER BY name ASC");
    $methods = $stmt->fetchAll();
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ==============================================
       🔥 NEW APPLE iOS SETTINGS THEME (STABLE) 🔥
       ============================================== */
    * {
        box-sizing: border-box; /* YEH FIX KAREGA OVERFLOW ISSUE KO */
        margin: 0;
        padding: 0;
    }
       
    body { 
        background-color: #f2f2f7; /* iOS background color */
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
        overflow-x: hidden; 
        color: #1c1c1e;
    }
    
    .ios-container { 
        width: 100%; 
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 20px; 
    }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .anim-fade { animation: fadeIn 0.4s ease-out both; }
    
    /* Headers & Text */
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    .page-title { font-size: 2rem; font-weight: 800; letter-spacing: -0.5px; color: #000; margin: 0; }
    .page-subtitle { color: #8e8e93; font-size: 0.95rem; font-weight: 500; margin-top: 4px; }

    /* Cards */
    .ios-card { 
        background: #ffffff; 
        border-radius: 16px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
        padding: 25px; 
        margin-bottom: 25px;
        border: 1px solid #e5e5ea;
    }
    
    /* Alerts */
    .ios-alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 12px; font-size: 0.95rem; }
    .alert-success { background: #e8f5e9; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* Buttons */
    .btn-ios-primary { background: #007aff; color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-size: 0.95rem; }
    .btn-ios-primary:hover { background: #005bb5; transform: scale(0.98); }

    .btn-ios-secondary { background: #e5e5ea; color: #1c1c1e; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; }
    .btn-ios-secondary:hover { background: #d1d1d6; }

    .btn-action-edit { background: #f2f2f7; color: #007aff; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px; border: 1px solid #e5e5ea; }
    .btn-action-edit:hover { background: #007aff; color: #fff; }

    .btn-action-del { background: #f2f2f7; color: #ff3b30; padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px; border: 1px solid #e5e5ea; margin-left: 5px; }
    .btn-action-del:hover { background: #ff3b30; color: #fff; }

    /* Tables */
    .table-wrapper { width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid #e5e5ea; background: #fff; }
    .table-ios { width: 100%; border-collapse: collapse; min-width: 800px; }
    .table-ios th { background: #f2f2f7; padding: 15px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; color: #8e8e93; text-align: left; border-bottom: 1px solid #d1d1d6; }
    .table-ios td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #e5e5ea; font-weight: 500; color: #1c1c1e; }
    .table-ios tr:last-child td { border-bottom: none; }
    .table-ios tr:hover td { background: #fafafa; }

    .method-icon { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid #e5e5ea; background: #fff; padding: 4px; }
    
    .status-pill { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .pill-green { background: #e8f5e9; color: #34c759; }
    .pill-grey { background: #f2f2f7; color: #8e8e93; }
    .pill-blue { background: #e3f2fd; color: #007aff; }

    /* Form Layout (Ultra Stable Grid) */
    .form-section { margin-bottom: 30px; }
    .section-title { font-size: 1.1rem; font-weight: 700; color: #000; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
    
    .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
    .form-col { flex: 1; min-width: 0; /* min-width:0 is the secret to stop flex items from overflowing */ }
    
    .input-label { display: block; font-weight: 600; color: #3a3a3c; margin-bottom: 6px; font-size: 0.9rem; }
    
    .ios-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #d1d1d6; background: #f2f2f7; font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 500; color: #1c1c1e; transition: 0.2s; outline: none; }
    .ios-input:focus { background: #fff; border-color: #007aff; box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15); }
    textarea.ios-input { resize: vertical; min-height: 100px; }

    /* Apple Style Toggle Switch */
    .toggle-container { display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none; margin-top: 5px; padding: 10px; background: #f2f2f7; border-radius: 12px; border: 1px solid #e5e5ea; }
    .toggle-container:hover { background: #e5e5ea; }
    .ios-switch { position: relative; width: 50px; height: 28px; background: #d1d1d6; border-radius: 30px; transition: 0.3s; flex-shrink: 0; }
    .ios-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 24px; height: 24px; background: #fff; border-radius: 50%; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    input[type="checkbox"] { display: none; }
    input[type="checkbox"]:checked + .ios-switch { background: #34c759; }
    input[type="checkbox"]:checked + .ios-switch::after { transform: translateX(22px); }
    .toggle-text { font-weight: 600; color: #1c1c1e; font-size: 0.95rem; flex-grow: 1; }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .form-row { flex-direction: column; gap: 15px; margin-bottom: 15px; }
        .page-header { flex-direction: column; align-items: flex-start; }
        .btn-ios-primary, .btn-ios-secondary { width: 100%; justify-content: center; }
        .ios-card { padding: 15px; }
    }
</style>

<div class="ios-container anim-fade">
    
    <div class="page-header">
        <div>
            <h1 class="page-title">Payment Gateways</h1>
            <p class="page-subtitle">Manage automated and manual billing methods.</p>
        </div>
        
        <?php if ($action == 'list'): ?>
            <div>
                <a href="methods.php?action=add" class="btn-ios-primary">
                    <i class="fas fa-plus"></i> Add New Method
                </a>
            </div>
        <?php else: ?>
            <div>
                <a href="methods.php?action=list" class="btn-ios-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="ios-alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="ios-alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        <div class="table-wrapper">
            <table class="table-ios">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">Gateway</th>
                        <th>Account Info</th>
                        <th>Limits</th>
                        <th>Engine</th>
                        <th>Status</th>
                        <th style="text-align: right; padding-right: 20px;">Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($methods)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #8e8e93;">No payment methods found.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach ($methods as $m): ?>
                    <tr>
                        <td style="padding-left: 20px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../assets/img/methods/<?php echo sanitize($m['icon_path']); ?>" alt="icon" class="method-icon">
                                <span style="font-weight: 700;"><?php echo sanitize($m['name']); ?></span>
                            </div>
                        </td>
                        <td>
                            <div style="line-height: 1.4;">
                                <div style="font-weight: 600;"><?php echo sanitize($m['account_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #8e8e93; font-family: monospace;"><?php echo sanitize($m['account_number']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.8rem; color: #3a3a3c;">
                                Min: <b><?php echo formatCurrency($m['min_amount']); ?></b><br>
                                Max: <b><?php echo ($m['max_amount'] > 0) ? formatCurrency($m['max_amount']) : 'No Limit'; ?></b>
                            </div>
                        </td>
                        <td>
                            <?php if ($m['is_auto']): ?>
                                <span class="status-pill pill-blue"><i class="fas fa-robot"></i> Auto</span>
                            <?php else: ?>
                                <span class="status-pill pill-grey"><i class="fas fa-hand-paper"></i> Manual</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['is_active']): ?>
                                <span class="status-pill pill-green">Active</span>
                            <?php else: ?>
                                <span class="status-pill pill-grey">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; padding-right: 20px;">
                            <a href="methods.php?action=edit&id=<?php echo $m['id']; ?>" class="btn-action-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="methods.php?action=delete&id=<?php echo $m['id']; ?>" class="btn-action-del" onclick="return confirm('Delete this method permanently?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($action == 'add' || $action == 'edit'): ?>
        <div class="ios-card">
            <form action="methods.php?action=<?php echo $action; ?><?php echo $method_id ? '&id='.$method_id : ''; ?>" method="POST">
                
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-info-circle text-primary"></i> Basic Details</h3>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="name" class="input-label">Gateway Name (e.g., NayaPay)</label>
                            <input type="text" id="name" name="name" class="ios-input" value="<?php echo sanitize($method['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="icon_path" class="input-label">Icon Filename (e.g., nayapay.png)</label>
                            <input type="text" id="icon_path" name="icon_path" class="ios-input" value="<?php echo sanitize($method['icon_path'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="account_name" class="input-label">Account Holder Title</label>
                            <input type="text" id="account_name" name="account_name" class="ios-input" value="<?php echo sanitize($method['account_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="account_number" class="input-label">Account Number / IBAN</label>
                            <input type="text" id="account_number" name="account_number" class="ios-input" value="<?php echo sanitize($method['account_number'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="min_amount" class="input-label">Min Deposit Amount (0 = No limit)</label>
                            <input type="number" id="min_amount" name="min_amount" class="ios-input" value="<?php echo sanitize($method['min_amount'] ?? '0.00'); ?>" step="0.01">
                        </div>
                        <div class="form-col">
                            <label for="max_amount" class="input-label">Max Deposit Amount (0 = No limit)</label>
                            <input type="number" id="max_amount" name="max_amount" class="ios-input" value="<?php echo sanitize($method['max_amount'] ?? '0.00'); ?>" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="note" class="input-label">Instruction Note (Shown to users)</label>
                            <textarea id="note" name="note" class="ios-input" placeholder="e.g. Please send minimum 500 PKR to this account."><?php echo sanitize($method['note'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <label class="toggle-container">
                        <input type="checkbox" name="is_active" value="1" <?php echo (isset($method['is_active']) && $method['is_active']) ? 'checked' : ''; ?>>
                        <div class="ios-switch"></div>
                        <span class="toggle-text">Active (Visible to users)</span>
                    </label>
                </div>

                <hr style="border: none; border-top: 1px solid #e5e5ea; margin: 30px 0;">

                <div class="form-section">
                    <h3 class="section-title" style="color: #007aff;"><i class="fas fa-robot"></i> Automation Settings (IMAP)</h3>
                    
                    <label class="toggle-container" style="background: #e3f2fd; border-color: #bae6fd; margin-bottom: 20px;">
                        <input type="checkbox" name="is_auto" value="1" <?php echo (isset($method['is_auto']) && $method['is_auto']) ? 'checked' : ''; ?>>
                        <div class="ios-switch"></div>
                        <span class="toggle-text" style="color: #005bb5;">Enable Auto-Verification (Email Parsing)</span>
                    </label>

                    <div class="form-row">
                        <div class="form-col">
                            <label for="auto_mail_server" class="input-label">IMAP Mail Server</label>
                            <input type="text" id="auto_mail_server" name="auto_mail_server" class="ios-input" placeholder="e.g. imap.hostinger.com" value="<?php echo sanitize($method['auto_mail_server'] ?? ''); ?>">
                        </div>
                        <div class="form-col">
                            <label for="auto_email_user" class="input-label">Email Address</label>
                            <input type="text" id="auto_email_user" name="auto_email_user" class="ios-input" placeholder="payments@domain.com" value="<?php echo sanitize($method['auto_email_user'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label for="auto_email_pass" class="input-label">Email App Password</label>
                            <input type="password" id="auto_email_pass" name="auto_email_pass" class="ios-input" value="<?php echo sanitize($method['auto_email_pass'] ?? ''); ?>">
                        </div>
                        <div class="form-col">
                            </div>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 30px;">
                    <button type="submit" class="btn-ios-primary" style="width: 100%; max-width: 300px; padding: 16px;">
                        <i class="fas fa-save"></i> <?php echo ($action == 'edit') ? 'Update Gateway' : 'Create Gateway'; ?>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php include '_footer.php'; ?>
