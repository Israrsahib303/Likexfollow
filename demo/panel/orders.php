<?php
include '_header.php';
require_once __DIR__ . '/../includes/wallet.class.php';

$wallet = new Wallet($db);
$error = '';
$success = '';

// --- 1. APPROVE ORDER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve') {
    $order_id = (int)$_POST['order_id'];
    try {
        $stmt_ord = $db->prepare("SELECT duration_months FROM orders WHERE id = ? AND status = 'pending'");
        $stmt_ord->execute([$order_id]);
        $order = $stmt_ord->fetch();

        if ($order) {
            $duration_months = $order['duration_months'];
            $stmt_approve = $db->prepare("UPDATE orders SET status = 'completed', start_at = NOW(), end_at = DATE_ADD(NOW(), INTERVAL ? MONTH) WHERE id = ?");
            $stmt_approve->execute([$duration_months, $order_id]);
            $success = 'Order approved & activated successfully!';
        }
    } catch (PDOException $e) { $error = $e->getMessage(); }
}

// --- 2. CANCEL & REFUND (Smart Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel') {
    $order_id = (int)$_POST['order_id'];

    try {
        $stmt_ord = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt_ord->execute([$order_id]);
        $order = $stmt_ord->fetch();

        if ($order && ($order['status'] == 'completed' || $order['status'] == 'pending')) {
            
            $refund_amount = 0;
            $total_paid = (float)$order['total_price'];

            // CASE A: Pending Order (Not started yet) -> 100% Refund
            if ($order['status'] == 'pending') {
                $refund_amount = $total_paid;
            } 
            // CASE B: Active Order -> Pro-Rata Refund
            else {
                $duration_months = (int)$order['duration_months'];
                $total_days = $duration_months * 30;
                if ($total_days <= 0) $total_days = 1;
                
                $per_day_cost = $total_paid / $total_days;
                $seconds_elapsed = time() - strtotime($order['start_at']);

                // Grace Period (24 Hours) -> Full Refund
                if ($seconds_elapsed < 86400) {
                    $refund_amount = $total_paid;
                } else {
                    $days_used = ceil($seconds_elapsed / (60 * 60 * 24));
                    $cost_to_cut = $per_day_cost * $days_used;
                    $refund_amount = $total_paid - $cost_to_cut;
                }
            }

            if ($refund_amount < 0) $refund_amount = 0;

            $db->beginTransaction();
            
            // Cancel Order
            $db->prepare("UPDATE orders SET status = 'cancelled', end_at = NOW() WHERE id = ?")->execute([$order_id]);
            
            // Process Refund
            if ($refund_amount > 0) {
                $note = "Refund for Order #" . $order['code'];
                $wallet->addCredit($order['user_id'], $refund_amount, 'admin_adjust', $_SESSION['user_id'], $note);
            }
            
            $db->commit();
            $success = 'Order cancelled. Refunded: ' . formatCurrency($refund_amount);
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

// --- 3. UPDATE DETAILS (Via Modal) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_details') {
    $order_id = (int)$_POST['order_id'];
    $u = sanitize($_POST['service_username']);
    $p = sanitize($_POST['service_password']);
    $d = sanitize($_POST['account_details']); // Extra details box
    
    // --- FIX: TRY-CATCH ADDED TO PREVENT WHITE PAGE ---
    try {
        $db->prepare("UPDATE orders SET service_username=?, service_password=?, account_details=? WHERE id=?")->execute([$u, $p, $d, $order_id]);
        $success = 'Credentials updated successfully!';
    } catch (Exception $e) {
        $error = 'Update Failed: ' . $e->getMessage();
    }
}

// --- PAGINATION & DATA ---
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Filter: Only Subscriptions (is_digital = 0)
$orders = $db->prepare("
    SELECT o.*, u.email as user_email, p.name as product_name, p.icon as product_icon
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    WHERE p.is_digital = 0
    ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset
");
$orders->execute();
$all_orders = $orders->fetchAll();

$total_pages = ceil($db->query("SELECT COUNT(o.id) FROM orders o JOIN products p ON o.product_id=p.id WHERE p.is_digital=0")->fetchColumn() / $limit);
?>

<style>
:root {
    --primary-color: #4f46e5;
    --bg-surface: #ffffff;
    --text-primary: #111827;
    --text-secondary: #6b7280;
    --border-light: #e5e7eb;
}

body { background-color: #f3f4f6; }

.page-header {
    background: white; padding: 25px 30px; border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 25px;
}
.page-title h2 { margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
.page-title p { margin: 5px 0 0; color: var(--text-secondary); font-size: 0.9rem; }

/* Table Design */
.order-table-card {
    background: var(--bg-surface); border-radius: 16px; overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); border: 1px solid var(--border-light);
}
.modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.modern-table th {
    background: #f9fafb; padding: 16px 24px; text-align: left;
    font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--text-secondary); border-bottom: 1px solid var(--border-light);
}
.modern-table td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--border-light); color: var(--text-primary); }
.modern-table tr:last-child td { border-bottom: none; }
.modern-table tr:hover { background-color: #f9fafb; }

/* Components */
.product-flex { display: flex; align-items: center; gap: 12px; }
.p-icon { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; background: #f3f4f6; }
.p-name { font-weight: 600; display: block; font-size: 0.95rem; }
.order-ref { font-size: 0.75rem; color: var(--primary-color); background: #eef2ff; padding: 2px 6px; border-radius: 4px; }

.user-meta { display: flex; flex-direction: column; }
.u-email { font-size: 0.85rem; color: var(--text-secondary); }

.badge-status {
    padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    display: inline-flex; align-items: center; gap: 6px;
}
.badge-status::before { content:''; width:6px; height:6px; border-radius:50%; }
.bs-completed { background: #ecfdf5; color: #059669; } .bs-completed::before { background: #059669; }
.bs-pending { background: #fffbeb; color: #d97706; } .bs-pending::before { background: #d97706; }
.bs-cancelled { background: #fef2f2; color: #dc2626; } .bs-cancelled::before { background: #dc2626; }

/* Buttons */
.btn-icon {
    width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; transition: 0.2s;
    color: white; margin-right: 4px;
}
.btn-approve { background: #10b981; } .btn-approve:hover { background: #059669; }
.btn-cancel { background: #ef4444; } .btn-cancel:hover { background: #dc2626; }
.btn-creds { background: #4f46e5; width: auto; padding: 0 12px; font-size: 0.8rem; font-weight: 600; }
.btn-creds:hover { background: #4338ca; }

/* Modal Tweaks */
.modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
.modal-header { border-bottom: 1px solid var(--border-light); padding: 20px 24px; background: #f9fafb; border-radius: 16px 16px 0 0; }
.modal-body { padding: 24px; }
.form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 6px; }
.form-control { border-radius: 8px; padding: 10px 12px; border-color: var(--border-light); }
.form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
</style>

<div class="container-fluid" style="padding: 30px;">

    <div class="page-header">
        <div class="page-title">
            <h2>Subscription Orders</h2>
            <p>Manage active services, approvals & refunds.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="order-table-card">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Product Info</th>
                        <th>Customer</th>
                        <th>Price / Term</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No subscription orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $o): ?>
                        <tr>
                            <td>
                                <div class="product-flex">
                                    <img src="../assets/img/icons/<?= !empty($o['product_icon']) ? htmlspecialchars($o['product_icon']) : 'default.png' ?>" 
                                         class="p-icon" 
                                         onerror="this.src='../assets/img/logo.png'">
                                    <div>
                                        <span class="p-name"><?= htmlspecialchars($o['product_name']) ?></span>
                                        <span class="order-ref">#<?= $o['code'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-meta">
                                    <span style="font-weight:600;">User #<?= $o['user_id'] ?></span>
                                    <span class="u-email"><?= htmlspecialchars($o['user_email']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700; color:#111;"><?= formatCurrency($o['total_price']) ?></div>
                                <small class="text-muted"><?= $o['duration_months'] ?> Month(s)</small>
                            </td>
                            <td>
                                <?php 
                                    $st = $o['status'];
                                    $cls = ($st=='completed')?'bs-completed':(($st=='cancelled')?'bs-cancelled':'bs-pending');
                                ?>
                                <span class="badge-status <?= $cls ?>"><?= ucfirst($st) ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($st == 'pending'): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-icon btn-approve" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($st == 'pending' || $st == 'completed'): ?>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Cancel Order & Refund?');">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn-icon btn-cancel" title="Cancel & Refund">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <button class="btn-icon btn-creds" onclick='openCredsModal(<?= json_encode($o) ?>)'>
                                        <i class="fas fa-key me-1"></i> Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<div class="modal fade" id="credsModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Manage Access Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_details">
                <input type="hidden" name="order_id" id="modal_order_id">
                
                <div class="mb-3">
                    <label class="form-label">Service Email / Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-user"></i></span>
                        <input type="text" name="service_username" id="modal_username" class="form-control" placeholder="user@email.com">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password / PIN</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-lock"></i></span>
                        <input type="text" name="service_password" id="modal_password" class="form-control" placeholder="Secret123">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Additional Notes / Instructions</label>
                    <textarea name="account_details" id="modal_notes" class="form-control" rows="3" placeholder="Profile 1, Pin 0000..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary fw-bold">Update & Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCredsModal(data) {
    document.getElementById('modal_order_id').value = data.id;
    document.getElementById('modal_username').value = data.service_username || '';
    document.getElementById('modal_password').value = data.service_password || '';
    document.getElementById('modal_notes').value = data.account_details || '';
    
    var modal = new bootstrap.Modal(document.getElementById('credsModal'));
    modal.show();
}
</script>

<?php include '_footer.php'; ?>