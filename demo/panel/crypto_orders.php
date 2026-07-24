<?php
// ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '_header.php';
requireAdmin();

// --- HELPER ---
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return htmlspecialchars(strip_tags(trim($str)));
    }
}

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. APPROVE
    if (isset($_POST['approve_order'])) {
        $id = (int)$_POST['order_id'];
        $txid = sanitize($_POST['trx_id']);
        $note = sanitize($_POST['admin_note']);
        
        $db->prepare("UPDATE crypto_orders SET status='completed', trx_id=?, admin_note=? WHERE id=?")
           ->execute([$txid, $note, $id]);
           
        echo "<script>Swal.fire({icon:'success', title:'Order Completed', text:'User notified with TXID.'});</script>";
    }

    // 2. CANCEL
    if (isset($_POST['cancel_order'])) {
        $id = (int)$_POST['order_id'];
        $reason = sanitize($_POST['cancel_reason']);
        
        // Fetch Order
        $order = $db->query("SELECT * FROM crypto_orders WHERE id=$id AND status='pending'")->fetch();
        
        if ($order) {
            $amount = $order['amount_pkr'];
            $uid = $order['user_id'];
            
            // Refund
            $db->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$amount, $uid]);
            $db->prepare("UPDATE crypto_orders SET status='cancelled', admin_note=? WHERE id=?")->execute([$reason, $id]);
            
            // Log
            $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, ref_type, ref_id, note) VALUES (?, 'credit', ?, 'refund', ?, ?)")
               ->execute([$uid, $amount, $id, "Refund: USDT Order #$id"]);
               
            echo "<script>Swal.fire({icon:'info', title:'Order Cancelled', text:'Amount refunded.'});</script>";
        }
    }
}

// --- FETCH ORDERS (FIXED QUERY) ---
try {
    // FIX: Changed u.username to u.name
    $orders = $db->query("
        SELECT o.*, u.name, u.email, e.name as method_name, e.icon 
        FROM crypto_orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN crypto_exchanges e ON o.exchange_id = e.id
        ORDER BY o.id DESC
    ")->fetchAll();

} catch (Exception $e) {
    // If table missing, show clean error
    if(strpos($e->getMessage(), 'crypto_orders') !== false) {
        echo "<div class='alert alert-danger m-4'>Error: 'crypto_orders' table missing. Please run the SQL command.</div>";
    } else {
        echo "<div class='alert alert-danger m-4'>DB Error: " . $e->getMessage() . "</div>";
    }
    $orders = [];
}
?>

<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="fa-brands fa-usdt text-success me-2"></i>Crypto Orders</h5>
                <p class="text-muted small mb-0">Manage USDT buying requests.</p>
            </div>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                <?= count($orders) ?> Requests
            </span>
        </div>
        
        <div class="card-body p-0">
            <?php if(empty($orders)): ?>
                <div class="text-center py-5">
                    <div style="font-size:50px; opacity:0.2;">📭</div>
                    <p class="text-muted mt-3">No orders placed yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Order ID</th>
                                <th>User</th>
                                <th>Wallet Details</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $o): 
                                $statusColor = match($o['status']) {
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                };
                                $icon = $o['icon'] ? "../assets/img/icons/".$o['icon'] : "../assets/img/usdt.png";
                                // Fallback name if empty
                                $userName = !empty($o['name']) ? htmlspecialchars($o['name']) : explode('@', $o['email'])[0];
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">#<?= $o['id'] ?></div>
                                    <div class="small text-muted"><?= date("d M, h:i A", strtotime($o['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-soft-primary text-primary rounded-circle fw-bold d-flex align-items-center justify-content-center me-2" style="width:35px;height:35px;">
                                            <?= strtoupper(substr($userName,0,1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= $userName ?></div>
                                            <small class="text-success fw-bold">$<?= number_format($o['amount_usdt'], 2) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <img src="<?= $icon ?>" width="20" class="rounded">
                                        <span class="fw-bold small text-dark"><?= htmlspecialchars($o['method_name'] ?? 'Unknown') ?></span>
                                    </div>
                                    <div class="bg-light p-2 rounded border small text-break user-select-all" style="max-width:250px; font-family:monospace; font-size:11px;">
                                        <?= htmlspecialchars($o['wallet_address']) ?>
                                    </div>
                                    <div class="mt-1" style="font-size:11px; color:#64748b;">
                                        <b>Total Paid:</b> Rs <?= number_format($o['amount_pkr']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-soft-<?= $statusColor ?> text-<?= $statusColor ?> px-3 py-2 rounded-pill text-uppercase" style="font-size:10px;">
                                        <?= $o['status'] ?>
                                    </span>
                                    <?php if($o['status']=='completed'): ?>
                                        <div class="mt-1 small text-muted" title="<?= $o['trx_id'] ?>">
                                            <i class="fa-solid fa-check-circle"></i> TXID Sent
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if($o['status']=='pending'): ?>
                                        <button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" onclick="openApprove(<?= $o['id'] ?>, '<?= $o['amount_usdt'] ?>')">
                                            <i class="fa-solid fa-paper-plane"></i> Send
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="openCancel(<?= $o['id'] ?>)">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light text-muted" disabled>Processed</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="order_id" id="app_oid">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Confirm Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-3">You are marking order <b id="disp_id">#0</b> as complete. Ensure you have sent <b class="text-success" id="disp_amt">$0</b>.</p>
                
                <div class="mb-3">
                    <label class="small fw-bold text-uppercase text-muted">Transaction ID (Proof)</label>
                    <input type="text" name="trx_id" class="form-control form-control-lg" placeholder="Paste Binance TXID here..." required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold text-uppercase text-muted">Admin Note</label>
                    <input type="text" name="admin_note" class="form-control" placeholder="e.g. Sent via Binance Pay">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="approve_order" class="btn btn-success px-4 fw-bold">Complete Order</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="order_id" id="can_oid">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">Cancel & Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">This will refund the PKR amount back to user's wallet.</p>
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Reason for Cancellation</label>
                    <textarea name="cancel_reason" class="form-control" rows="2" placeholder="e.g. Invalid Wallet Address" required></textarea>
                </div>
                <button type="submit" name="cancel_order" class="btn btn-danger w-100 fw-bold">Confirm Refund</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApprove(id, amt) {
    document.getElementById('app_oid').value = id;
    document.getElementById('disp_id').innerText = '#' + id;
    document.getElementById('disp_amt').innerText = '$' + amt;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function openCancel(id) {
    document.getElementById('can_oid').value = id;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>

<style>
.bg-soft-primary { background: rgba(79, 70, 229, 0.1); }
.bg-soft-success { background: rgba(16, 185, 129, 0.1); }
.bg-soft-warning { background: rgba(245, 158, 11, 0.1); }
.bg-soft-danger { background: rgba(239, 68, 68, 0.1); }
.table > :not(caption) > * > * { padding: 1rem 0.5rem; }
.btn-success { background-color: #10b981; border:none; }
.btn-success:hover { background-color: #059669; }
</style>

<?php include '_footer.php'; ?>