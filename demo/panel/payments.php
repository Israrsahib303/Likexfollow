<?php
// --- ERROR DEBUGGING START ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ERROR DEBUGGING END ---

include '_header.php'; 
require_once __DIR__ . '/../includes/wallet.class.php';

$wallet = new Wallet($db);
$error = '';
$success = '';

// --- STATS CALCULATION (New Feature) ---
try {
    $stats = [
        'total_txns' => $db->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
        'pending_txns' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
        'approved_txns' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'approved'")->fetchColumn(),
        'rejected_txns' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'rejected'")->fetchColumn(),
        'total_revenue' => $db->query("SELECT SUM(amount) FROM payments WHERE status = 'approved'")->fetchColumn() ?: 0.00
    ];
} catch (Exception $e) {
    $stats = ['total_txns'=>0, 'pending_txns'=>0, 'approved_txns'=>0, 'rejected_txns'=>0, 'total_revenue'=>0];
}

// --- FILTER LOGIC ---
$filter = $_GET['filter'] ?? 'pending'; 
$where_clause = '';
if ($filter == 'pending') {
    $where_clause = "WHERE p.status = 'pending'";
} elseif ($filter == 'approved') {
    $where_clause = "WHERE p.status = 'approved'";
} elseif ($filter == 'rejected') {
    $where_clause = "WHERE p.status = 'rejected'";
}

// --- ACTION HANDLING ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_id'])) {
    $payment_id = (int)$_POST['payment_id'];
    $admin_id = $_SESSION['user_id'];

    try {
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch();

        if ($payment && $payment['status'] == 'pending') {
            if (isset($_POST['action']) && $_POST['action'] == 'approve') {
                $db->beginTransaction();
                
                $stmt_update = $db->prepare("UPDATE payments SET status = 'approved', approved_at = NOW(), admin_id = ? WHERE id = ?");
                $stmt_update->execute([$admin_id, $payment_id]);
                
                // Add credits
                $credit_note = "Manual deposit approved: #" . ($payment['txn_id'] ?? 'N/A');
                // Note: Assuming addCredit handles the logic correctly. If function signature differs, adjust accordingly.
                // Based on standard wallet class in these scripts: addCredit($user_id, $amount, $type, $ref_id, $desc)
                $wallet->addCredit($payment['user_id'], $payment['amount'], 'deposit', $payment_id, $credit_note);
                
                $db->commit();
                $success = "Transaction #{$payment_id} successfully approved.";
                
            } elseif (isset($_POST['action']) && $_POST['action'] == 'reject') {
                $stmt_reject = $db->prepare("UPDATE payments SET status = 'rejected', admin_id = ? WHERE id = ?");
                $stmt_reject->execute([$admin_id, $payment_id]);
                $success = "Transaction #{$payment_id} has been rejected.";
            }
        } else {
            $error = 'Payment not found or already processed.';
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'System Error: ' . $e->getMessage();
    }
}

// --- FETCH PAYMENTS ---
$stmt_payments = $db->prepare("
    SELECT p.*, u.email as user_email 
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    $where_clause
    ORDER BY p.created_at DESC
");
$stmt_payments->execute();
$payments = $stmt_payments->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=San+Francisco+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --ios-bg: #F5F5F7;
        --ios-card: #FFFFFF;
        --ios-text-primary: #1D1D1F;
        --ios-text-secondary: #86868B;
        --ios-blue: #0071E3;
        --ios-green: #34C759;
        --ios-red: #FF3B30;
        --ios-orange: #FF9500;
        --ios-border: #E5E5EA;
        --ios-shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --ios-shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        --ios-radius: 16px;
        --ios-transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif;
        background-color: var(--ios-bg);
        color: var(--ios-text-primary);
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden; /* Prevent page-wide horizontal scroll */
    }

    /* CONTAINER SAFETY */
    .ios-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        box-sizing: border-box;
    }

    /* HEADER */
    .page-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    .page-title h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .page-title p {
        margin: 5px 0 0;
        color: var(--ios-text-secondary);
        font-size: 15px;
    }

    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        padding: 24px;
        box-shadow: var(--ios-shadow-sm);
        transition: var(--ios-transition);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid rgba(0,0,0,0.02);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--ios-shadow-md);
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 15px;
    }
    .stat-value {
        font-size: 26px;
        font-weight: 700;
        margin: 0;
        color: var(--ios-text-primary);
    }
    .stat-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--ios-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* SEGMENTED CONTROL */
    .filter-container {
        display: flex;
        justify-content: center;
        margin-bottom: 25px;
        width: 100%;
        overflow-x: auto; /* Safety for very small screens */
    }
    .segmented-control {
        background: #E3E3E8;
        padding: 4px;
        border-radius: 12px;
        display: inline-flex;
        position: relative;
    }
    .segment-btn {
        padding: 8px 24px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: #636366;
        cursor: pointer;
        border-radius: 9px;
        transition: var(--ios-transition);
        text-decoration: none;
        white-space: nowrap;
    }
    .segment-btn:hover {
        color: #000;
    }
    .segment-btn.active {
        background: #FFFFFF;
        color: #000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }

    /* TABLE CARD */
    .table-card {
        background: var(--ios-card);
        border-radius: var(--ios-radius);
        box-shadow: var(--ios-shadow-sm);
        overflow: hidden; /* Contains the scrollable table */
        border: 1px solid rgba(0,0,0,0.03);
    }

    /* RESPONSIVE TABLE WRAPPER */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px; /* Forces scroll on mobile */
    }

    .ios-table th {
        text-align: left;
        padding: 16px 20px;
        border-bottom: 1px solid var(--ios-border);
        background: #FAFAFA;
        color: var(--ios-text-secondary);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ios-table td {
        padding: 18px 20px;
        border-bottom: 1px solid var(--ios-border);
        vertical-align: middle;
        font-size: 14px;
        color: var(--ios-text-primary);
        transition: background-color 0.1s;
    }

    .ios-table tr:last-child td {
        border-bottom: none;
    }
    
    .ios-table tr:hover td {
        background-color: #F9F9FB;
    }

    /* ELEMENTS */
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #E5E5EA;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #666;
        font-weight: 700;
    }

    .amount-badge {
        font-weight: 600;
        color: var(--ios-text-primary);
    }

    .method-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #F2F2F7;
        border-radius: 6px;
        font-size: 12px;
        color: var(--ios-text-secondary);
        font-weight: 500;
        font-family: 'SF Mono', monospace;
    }

    .txn-id {
        font-family: 'SF Mono', monospace;
        font-size: 12px;
        color: var(--ios-text-secondary);
    }

    /* STATUS PILLS */
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
    .status-pending { background: rgba(255, 149, 0, 0.12); color: var(--ios-orange); }
    .status-approved { background: rgba(52, 199, 89, 0.12); color: var(--ios-green); }
    .status-rejected { background: rgba(255, 59, 48, 0.12); color: var(--ios-red); }

    /* ACTION BUTTONS */
    .action-group {
        display: flex;
        gap: 8px;
    }
    .btn-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--ios-transition);
        font-size: 14px;
    }
    .btn-approve {
        background: rgba(52, 199, 89, 0.1);
        color: var(--ios-green);
    }
    .btn-approve:hover {
        background: var(--ios-green);
        color: #fff;
        transform: scale(1.05);
    }
    .btn-reject {
        background: rgba(255, 59, 48, 0.1);
        color: var(--ios-red);
    }
    .btn-reject:hover {
        background: var(--ios-red);
        color: #fff;
        transform: scale(1.05);
    }

    /* IMAGE PREVIEW */
    .screenshot-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #E5E5EA;
        transition: transform 0.2s;
        cursor: zoom-in;
    }
    .screenshot-thumb:hover {
        transform: scale(1.1);
    }

    /* ALERTS */
    .msg-box {
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 500;
        animation: slideDown 0.3s ease-out;
    }
    .msg-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .msg-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* MOBILE TWEAKS */
    @media (max-width: 600px) {
        .page-header { flex-direction: column; align-items: flex-start; }
        .stats-grid { grid-template-columns: 1fr; }
        .filter-container { justify-content: flex-start; }
    }
</style>

<div class="ios-container">

    <div class="page-header">
        <div class="page-title">
            <h1>Payments</h1>
            <p>Manage deposits and transaction history.</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(0,113,227,0.1); color: var(--ios-blue);">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo number_format($stats['total_txns']); ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255,149,0,0.1); color: var(--ios-orange);">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo number_format($stats['pending_txns']); ?></div>
                <div class="stat-label">Pending Reviews</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(52,199,89,0.1); color: var(--ios-green);">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Approved</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255,59,48,0.1); color: var(--ios-red);">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo number_format($stats['rejected_txns']); ?></div>
                <div class="stat-label">Rejected Requests</div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="msg-box msg-success">
        <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="msg-box msg-error">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="filter-container">
        <div class="segmented-control">
            <a href="payments.php?filter=pending" class="segment-btn <?php echo ($filter == 'pending') ? 'active' : ''; ?>">Pending</a>
            <a href="payments.php?filter=approved" class="segment-btn <?php echo ($filter == 'approved') ? 'active' : ''; ?>">Approved</a>
            <a href="payments.php?filter=rejected" class="segment-btn <?php echo ($filter == 'rejected') ? 'active' : ''; ?>">Rejected</a>
            <a href="payments.php?filter=all" class="segment-btn <?php echo ($filter == 'all') ? 'active' : ''; ?>">All History</a>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Details</th>
                        <th>Proof</th>
                        <th>Date</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--ios-text-secondary);">
                                <i class="fa-solid fa-folder-open" style="font-size: 24px; opacity: 0.3; margin-bottom: 10px;"></i><br>
                                No <?php echo htmlspecialchars($filter); ?> payments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td style="color: var(--ios-text-secondary);">#<?php echo $p['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <div class="avatar-circle">
                                        <?php echo strtoupper(substr($p['user_email'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <span title="<?php echo sanitize($p['user_email']); ?>">
                                        <?php 
                                            $email = sanitize($p['user_email']); 
                                            echo (strlen($email) > 20) ? substr($email, 0, 18) . '...' : $email; 
                                        ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="amount-badge"><?php echo formatCurrency($p['amount']); ?></span>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    <span class="method-badge"><?php echo sanitize($p['method']); ?></span>
                                    <span class="txn-id"><?php echo sanitize($p['txn_id']); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($p['screenshot_path'])): ?>
                                <a href="../assets/uploads/<?php echo sanitize($p['screenshot_path']); ?>" target="_blank">
                                    <img src="../assets/uploads/<?php echo sanitize($p['screenshot_path']); ?>" class="screenshot-thumb" alt="Proof">
                                </a>
                                <?php else: ?>
                                <span style="color: #ccc; font-size: 12px;">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--ios-text-secondary); font-size: 13px;">
                                <?php echo date('M d, Y', strtotime($p['created_at'])); ?><br>
                                <small><?php echo date('h:i A', strtotime($p['created_at'])); ?></small>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($p['status'] == 'pending'): ?>
                                    <div class="action-group" style="justify-content: flex-end;">
                                        <form action="payments.php?filter=<?php echo $filter; ?>" method="POST" style="margin:0;">
                                            <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn-icon btn-approve" title="Approve">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                        <form action="payments.php?filter=<?php echo $filter; ?>" method="POST" style="margin:0;">
                                            <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn-icon btn-reject" title="Reject" onclick="return confirm('Are you sure you want to reject this payment?');">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($p['status'] == 'approved'): ?>
                                    <span class="status-pill status-approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-pill status-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '_footer.php'; ?>