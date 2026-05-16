<?php
include '_header.php';
require_once __DIR__ . '/../includes/wallet.class.php';
require_once __DIR__ . '/../includes/smm_api.class.php';

$wallet = new Wallet($db);
$error = '';
$success = '';

// --- ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $order_id = (int)$_POST['order_id'];
    
    try {
        // 1. FETCH ORDER DETAILS
        $stmt = $db->prepare("
            SELECT o.*, s.service_id as provider_service_id, p.api_url, p.api_key, p.id as provider_id
            FROM smm_orders o
            JOIN smm_services s ON o.service_id = s.id
            LEFT JOIN smm_providers p ON s.provider_id = p.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order) { throw new Exception("Order not found."); }

        // --- A. RESEND ORDER (The Fixer) ---
        if ($_POST['action'] == 'resend_order') {
            if (empty($order['api_url']) || empty($order['api_key'])) {
                throw new Exception("Provider missing or invalid.");
            }

            // Call API
            $api = new SmmApi($order['api_url'], $order['api_key']);
            $api_result = $api->placeOrder($order['provider_service_id'], $order['link'], $order['quantity']);

            if (isset($api_result['order'])) {
                // Success: Update DB
                $stmt = $db->prepare("UPDATE smm_orders SET status = 'pending', provider_order_id = ?, api_error = NULL WHERE id = ?");
                $stmt->execute([$api_result['order'], $order_id]);
                $success = "✅ Order #$order_id resent successfully! New ID: " . $api_result['order'];
            } else {
                // Fail: Log Error
                $err_msg = $api_result['error'] ?? 'Unknown API Error';
                $stmt = $db->prepare("UPDATE smm_orders SET status = 'cancelled', api_error = ? WHERE id = ?");
                $stmt->execute([$err_msg, $order_id]);
                $error = "❌ Resend Failed: " . $err_msg;
            }
        }

        // --- B. REFUND USER (Cancel + Money Back) ---
        elseif ($_POST['action'] == 'refund_order') {
            if ($order['status'] != 'cancelled' && $order['status'] != 'refunded') {
                $db->beginTransaction();
                $db->prepare("UPDATE smm_orders SET status = 'refunded', api_error = 'Manually Refunded' WHERE id = ?")->execute([$order_id]);
                $wallet->addCredit($order['user_id'], (float)$order['charge'], 'refund', $order_id, "Refund: Order #$order_id");
                $db->commit();
                $success = "💸 Order #$order_id cancelled & refunded.";
            } else {
                $error = "Order already cancelled/refunded.";
            }
        }

        // --- C. CANCEL NO REFUND (Admin Power) ---
        elseif ($_POST['action'] == 'cancel_no_refund') {
            $db->prepare("UPDATE smm_orders SET status = 'cancelled', api_error = 'Cancelled by Admin (No Refund)' WHERE id = ?")->execute([$order_id]);
            $success = "⛔ Order #$order_id cancelled (No Refund given).";
        }

        // --- D. FORCE COMPLETE ---
        elseif ($_POST['action'] == 'force_complete') {
            $db->prepare("UPDATE smm_orders SET status = 'completed', api_error = NULL WHERE id = ?")->execute([$order_id]);
            $success = "✅ Order #$order_id marked as Completed.";
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// --- STATS CALCULATION ---
try {
    $stats = [
        'total' => $db->query("SELECT COUNT(*) FROM smm_orders")->fetchColumn(),
        'profit' => $db->query("SELECT SUM(charge) - SUM(cost) FROM smm_orders WHERE status!='cancelled'")->fetchColumn(),
        'failed' => $db->query("SELECT COUNT(*) FROM smm_orders WHERE status IN ('cancelled', 'fail')")->fetchColumn(),
        'active' => $db->query("SELECT COUNT(*) FROM smm_orders WHERE status IN ('pending', 'in_progress', 'processing')")->fetchColumn(),
        'partial' => $db->query("SELECT COUNT(*) FROM smm_orders WHERE status IN ('partial', 'refunded')")->fetchColumn()
    ];
} catch(Exception $e) { $stats = ['total'=>0, 'profit'=>0, 'failed'=>0, 'active'=>0, 'partial'=>0]; }

// --- FILTER & PAGINATION ---
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

$where_sql = "WHERE 1=1";
$params = [];

if ($search) {
    $where_sql .= " AND (o.id = ? OR o.link LIKE ? OR u.email LIKE ? OR u.name LIKE ?)";
    $params = array_merge($params, [$search, "%$search%", "%$search%", "%$search%"]);
}
if ($status_filter) {
    if ($status_filter == 'partial_refunded') {
        $where_sql .= " AND o.status IN ('partial', 'refunded')";
    } else {
        $where_sql .= " AND o.status = ?";
        $params[] = $status_filter;
    }
}

// Main Query
$sql = "
    SELECT o.*, u.email, u.name as username, s.name as service_name, p.name as provider_name 
    FROM smm_orders o
    JOIN users u ON o.user_id = u.id
    JOIN smm_services s ON o.service_id = s.id
    LEFT JOIN smm_providers p ON s.provider_id = p.id
    $where_sql
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Pagination Count
$count_sql = "SELECT COUNT(*) FROM smm_orders o JOIN users u ON o.user_id = u.id $where_sql";
$stmt_c = $db->prepare($count_sql);
$stmt_c->execute($params);
$total_rows = $stmt_c->fetchColumn();
$total_pages = ceil($total_rows / $limit);
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4f46e5;
        --secondary: #ec4899;
        --bg-light: #f3f4f6;
        --glass: rgba(255, 255, 255, 0.95);
        --radius: 16px;
        --shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
    }
    
    body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #1e293b; }

    /* --- DASHBOARD HEADER --- */
    .dashboard-header {
        background: white; padding: 25px; border-radius: var(--radius);
        box-shadow: var(--shadow); border: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 30px; position: relative; overflow: hidden;
    }
    .dashboard-header::before {
        content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 6px;
        background: linear-gradient(to bottom, var(--primary), var(--secondary));
    }
    .header-text h1 { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; }
    .header-text p { margin: 5px 0 0; color: #64748b; font-size: 0.95rem; font-weight: 500; }
    
    .btn-refresh {
        background: #f1f5f9; color: #334155; padding: 12px 25px;
        border-radius: 12px; font-weight: 700; text-decoration: none;
        display: flex; align-items: center; gap: 8px; transition: 0.3s;
        border: 1px solid #e2e8f0; font-size: 0.9rem;
    }
    .btn-refresh:hover {
        background: var(--primary); color: white; transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); border-color: var(--primary);
    }
    .btn-refresh i { transition: transform 0.6s ease; }
    .btn-refresh:hover i { transform: rotate(180deg); }

    /* --- STATS GRID --- */
    .stats-grid { 
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
        gap: 20px; margin-bottom: 30px; 
    }
    .stat-card {
        background: white; border-radius: var(--radius); padding: 25px;
        position: relative; overflow: hidden; box-shadow: var(--shadow);
        transition: transform 0.3s ease; border: 1px solid rgba(255,255,255,0.5);
        cursor: pointer;
    }
    .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); }
    .stat-card::before {
        content:''; position: absolute; top:0; left:0; width:100%; height:4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    .stat-val { font-size: 2.2rem; font-weight: 800; color: #1e293b; margin: 5px 0; }
    .stat-label { font-size: 0.85rem; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
    .stat-icon {
        position: absolute; right: 20px; bottom: 20px; font-size: 3rem; 
        opacity: 0.05; transform: rotate(-15deg); color: #000;
    }

    /* --- SEARCH BAR --- */
    .filter-bar {
        background: white; padding: 20px; border-radius: var(--radius);
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05); margin-bottom: 25px;
        border: 1px solid #e2e8f0; display: flex; gap: 15px; flex-wrap: wrap; align-items: center;
    }
    .form-input, .form-select {
        padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px;
        outline: none; transition: 0.3s; width: 100%; font-size: 0.95rem;
    }
    .form-input:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .btn-filter {
        background: var(--primary); color: white; padding: 12px 25px; border: none;
        border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); }

    /* --- ADVANCED TABLE (FIXED SCROLL) --- */
    .table-container {
        background: white; border-radius: var(--radius); box-shadow: var(--shadow);
        border: 1px solid #e2e8f0;
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
    }
    .custom-table { 
        width: 100%; 
        border-collapse: collapse; 
        min-width: 1000px; /* Forces scroll if screen is smaller */
    }
    .custom-table th {
        background: #f8fafc; text-align: left; padding: 18px 20px;
        font-size: 0.75rem; text-transform: uppercase; color: #64748b;
        font-weight: 700; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;
    }
    .custom-table td {
        padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem; vertical-align: middle; color: #334155;
        transition: background 0.2s;
    }
    .custom-table tr:last-child td { border-bottom: none; }
    
    /* ROW HIGHLIGHT LOGIC */
    .custom-table tr:hover td { background: #fcfcfc; }
    .custom-table tr.selected-row td { 
        background-color: #fff7ed !important; 
        border-top: 1px solid #fdba74; 
        border-bottom: 1px solid #fdba74; 
    }

    /* Column Styles */
    .id-badge { font-weight: 800; color: var(--primary); background: #eef2ff; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; }
    .date-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; display: block; }
    
    .user-row { display: flex; align-items: center; gap: 10px; }
    .user-avatar { 
        width: 35px; height: 35px; background: linear-gradient(135deg, #ddd6fe, #c4b5fd); 
        color: #5b21b6; border-radius: 50%; display: flex; align-items: center; 
        justify-content: center; font-weight: bold; font-size: 0.9rem;
    }
    .user-details strong { display: block; color: #1e293b; }
    .user-details span { font-size: 0.8rem; color: #64748b; }

    .service-name { font-weight: 600; color: #1e293b; display: block; margin-bottom: 4px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .provider-badge { font-size: 0.7rem; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; color: #475569; border: 1px solid #e2e8f0; }
    
    .link-wrap { 
        background: #f8fafc; padding: 6px 10px; border-radius: 8px; 
        border: 1px dashed #cbd5e1; font-family: monospace; font-size: 0.85rem;
        color: #475569; max-width: 200px; white-space: nowrap; overflow: hidden; 
        text-overflow: ellipsis; display: flex; align-items: center; justify-content: space-between;
    }
    .link-wrap a { color: var(--primary); text-decoration: none; transition: 0.2s; display:flex; align-items:center; gap:5px; }
    .link-wrap a:hover { color: #4338ca; text-decoration: underline; }
    .link-wrap i.fa-copy { cursor: pointer; color: #94a3b8; transition:0.2s; }
    .link-wrap i.fa-copy:hover { transform: scale(1.2); color: var(--primary); }

    /* Status Badges */
    .status { padding: 6px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
    .st-completed { background: #dcfce7; color: #166534; }
    .st-pending { background: #fef9c3; color: #854d0e; }
    .st-processing { background: #dbeafe; color: #1e40af; animation: pulse 2s infinite; }
    .st-cancelled { background: #fee2e2; color: #991b1b; }
    .st-fail { background: #ffe4e6; color: #be123c; }
    .st-partial, .st-refunded { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }

    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }

    /* --- ACTION BUTTONS (The Hub) --- */
    .actions-flex { display: flex; gap: 6px; }
    .act-btn {
        width: 34px; height: 34px; border-radius: 10px; border: none;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.2s; color: white; font-size: 0.9rem;
    }
    .act-btn:hover { transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.1); }
    
    .btn-resend { background: #3b82f6; }
    .btn-refund { background: #10b981; }
    .btn-cancel { background: #ef4444; }
    .btn-complete { background: #6366f1; }
    .btn-warn { background: #f59e0b; animation: bounce 1s infinite; }

    @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }

    /* --- CUSTOM POPUP (THE LEVISH MODAL) --- */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); 
        backdrop-filter: blur(8px); z-index: 9999; display: none;
        align-items: center; justify-content: center; opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal-overlay.active { display: flex; opacity: 1; }
    
    .levish-popup {
        background: white; width: 90%; max-width: 420px;
        border-radius: 24px; padding: 0; 
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
    }
    .modal-overlay.active .levish-popup { transform: scale(1); }

    /* Action Modal Specifics */
    .modal-icon-box {
        height: 80px; display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem; color: white;
    }
    .modal-body { padding: 25px; text-align: center; }
    .modal-h2 { font-size: 1.4rem; margin: 0 0 10px; color: #1e293b; font-weight: 800; }
    .modal-p { color: #64748b; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5; }
    
    .modal-actions { display: flex; gap: 10px; }
    .modal-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .modal-btn-cancel { background: #f1f5f9; color: #64748b; }
    .modal-btn-cancel:hover { background: #e2e8f0; }
    .modal-btn-confirm { background: #4f46e5; color: white; }
    .modal-btn-confirm:hover { background: #4338ca; }

    /* Colors for different modals */
    .theme-refund .modal-icon-box { background: linear-gradient(135deg, #10b981, #059669); }
    .theme-cancel .modal-icon-box { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .theme-resend .modal-icon-box { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .theme-complete .modal-icon-box { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .theme-error .modal-icon-box { background: linear-gradient(135deg, #f59e0b, #d97706); }
    
    /* Error Message Box inside Modal */
    .error-msg-box {
        background: #fef2f2; border: 2px dashed #fecaca; color: #b91c1c;
        padding: 15px; border-radius: 12px; font-family: monospace;
        margin-bottom: 20px; font-size: 0.95rem; line-height: 1.5;
        word-break: break-word;
    }
    
    .popup-btn {
        width: 100%; padding: 14px; background: #1e293b; color: white;
        border: none; border-radius: 14px; font-weight: 700; cursor: pointer;
        transition: 0.2s; font-size: 1rem;
    }
    .popup-btn:hover { background: #0f172a; transform: scale(1.02); }

    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

</style>

<div class="dashboard-header">
    <div class="header-text">
        <h1>🚀 SMM Command Center</h1>
        <p>Real-time Order Management & Tracking</p>
    </div>
    <a href="smm_orders.php" class="btn-refresh">
        <i class="fas fa-sync-alt"></i> Refresh Data
    </a>
</div>

<?php if ($error): ?>
<div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:12px; margin-bottom:20px; border:1px solid #fecaca; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-exclamation-circle"></i> <strong>System Alert:</strong> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div style="background:#dcfce7; color:#166534; padding:15px; border-radius:12px; margin-bottom:20px; border:1px solid #bbf7d0; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-check-circle"></i> <strong>Success:</strong> <?php echo $success; ?>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card" onclick="window.location.href='?status='">
        <div class="stat-val"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-label">Total Orders</div>
        <i class="fas fa-box stat-icon"></i>
    </div>
    <div class="stat-card" onclick="window.location.href='?status=completed'">
        <div class="stat-val" style="color:#10b981;"><?php echo formatCurrency($stats['profit']); ?></div>
        <div class="stat-label">Estimated Profit</div>
        <i class="fas fa-chart-line stat-icon"></i>
    </div>
    <div class="stat-card" onclick="window.location.href='?status=processing'">
        <div class="stat-val" style="color:#f59e0b;"><?php echo number_format($stats['active']); ?></div>
        <div class="stat-label">Active / Processing</div>
        <i class="fas fa-cog fa-spin stat-icon"></i>
    </div>
    <div class="stat-card" onclick="window.location.href='?status=partial_refunded'">
        <div class="stat-val" style="color:#9333ea;"><?php echo number_format($stats['partial']); ?></div>
        <div class="stat-label">Partial / Refunded</div>
        <i class="fas fa-undo-alt stat-icon"></i>
    </div>
    <div class="stat-card" onclick="window.location.href='?status=fail'">
        <div class="stat-val" style="color:#ef4444;"><?php echo number_format($stats['failed']); ?></div>
        <div class="stat-label">Failed / Issues</div>
        <i class="fas fa-exclamation-triangle stat-icon"></i>
    </div>
</div>

<form action="" method="GET" class="filter-bar">
    <div style="flex:2;">
        <label style="font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:5px; display:block;">SEARCH</label>
        <input type="text" name="search" class="form-input" placeholder="Order ID, Link, Username or Email..." value="<?php echo sanitize($search); ?>">
    </div>
    <div style="flex:1; min-width:150px;">
        <label style="font-size:0.8rem; font-weight:700; color:#64748b; margin-bottom:5px; display:block;">STATUS</label>
        <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>🟡 Pending</option>
            <option value="processing" <?php if($status_filter=='processing') echo 'selected'; ?>>🔵 Processing</option>
            <option value="completed" <?php if($status_filter=='completed') echo 'selected'; ?>>🟢 Completed</option>
            <option value="cancelled" <?php if($status_filter=='cancelled') echo 'selected'; ?>>🔴 Cancelled</option>
            <option value="fail" <?php if($status_filter=='fail') echo 'selected'; ?>>⚠️ Failed</option>
            <option value="partial_refunded" <?php if($status_filter=='partial_refunded') echo 'selected'; ?>>🟣 Partial/Refunded</option>
        </select>
    </div>
    <div style="align-self:flex-end;">
        <button type="submit" class="btn-filter">
            <i class="fas fa-filter"></i> Apply Filters
        </button>
    </div>
</form>

<div class="table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th width="120">ID / Date</th>
                <th>User Identity</th>
                <th>Service Details</th>
                <th>Target Link</th>
                <th>Status</th>
                <th width="160">Action Hub</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="6" style="text-align:center; padding: 50px; color: #94a3b8; font-weight:600;">No orders found matching your criteria.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr onclick="selectRow(this)">
                    <td>
                        <span class="id-badge">#<?php echo $o['id']; ?></span>
                        <span class="date-meta">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d M', strtotime($o['created_at'])); ?><br>
                            <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($o['created_at'])); ?>
                        </span>
                    </td>

                    <td>
                        <div class="user-row">
                            <div class="user-avatar"><?php echo strtoupper(substr($o['username'], 0, 1)); ?></div>
                            <div class="user-details">
                                <strong><?php echo sanitize($o['username']); ?></strong>
                                <span><?php echo sanitize($o['email']); ?></span>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="service-name" title="<?php echo sanitize($o['service_name']); ?>">
                            <?php echo sanitize($o['service_name']); ?>
                        </span>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:5px;">
                            <span class="provider-badge">
                                <i class="fas fa-server"></i> <?php echo !empty($o['provider_name']) ? sanitize($o['provider_name']) : 'Manual/API'; ?>
                            </span>
                            <span style="font-weight:700; font-size:0.85rem; color:#10b981;">
                                <?php echo formatCurrency($o['charge']); ?>
                            </span>
                        </div>
                    </td>

                    <td>
                        <div class="link-wrap">
                            <a href="<?php echo sanitize($o['link']); ?>" target="_blank" onclick="event.stopPropagation()" title="Open Link">
                                <?php echo substr(sanitize($o['link']), 0, 20) . '...'; ?> <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                            </a>
                            <i class="far fa-copy" onclick="event.stopPropagation(); copyToClipboard('<?php echo sanitize($o['link']); ?>')" title="Copy Link"></i>
                        </div>
                        <div style="font-size:0.75rem; color:#64748b; margin-top:6px;">
                            Start: <b><?php echo $o['start_count'] ?? 0; ?></b> • Rem: <b><?php echo $o['remains'] ?? 0; ?></b>
                        </div>
                    </td>

                    <td>
                        <?php 
                            $st_class = 'st-pending';
                            if(in_array($o['status'], ['completed'])) $st_class = 'st-completed';
                            if(in_array($o['status'], ['processing', 'in_progress'])) $st_class = 'st-processing';
                            if(in_array($o['status'], ['cancelled'])) $st_class = 'st-cancelled';
                            if(in_array($o['status'], ['fail'])) $st_class = 'st-fail';
                            if(in_array($o['status'], ['partial', 'refunded'])) $st_class = 'st-partial';
                        ?>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span class="status <?php echo $st_class; ?>">
                                <?php echo ucfirst($o['status']); ?>
                            </span>
                            
                            <?php if(!empty($o['api_error'])): ?>
                                <button class="act-btn btn-warn" onclick="openConfirmModal('error', <?php echo $o['id']; ?>, '<?php echo htmlspecialchars(addslashes($o['api_error'])); ?>')" title="View Error">
                                    <i class="fas fa-exclamation"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td>
                        <div class="actions-flex">
                            <button type="button" class="act-btn btn-resend" title="Resend Order" onclick="openConfirmModal('resend', <?php echo $o['id']; ?>)">
                                <i class="fas fa-redo"></i>
                            </button>

                            <?php if(!in_array($o['status'], ['cancelled', 'refunded'])): ?>
                            <button type="button" class="act-btn btn-refund" title="Refund & Cancel" onclick="openConfirmModal('refund', <?php echo $o['id']; ?>)">
                                <i class="fas fa-hand-holding-usd"></i>
                            </button>

                            <button type="button" class="act-btn btn-cancel" title="Cancel (No Refund)" onclick="openConfirmModal('cancel', <?php echo $o['id']; ?>)">
                                <i class="fas fa-ban"></i>
                            </button>
                            
                            <?php elseif($o['status'] != 'completed'): ?>
                            <button type="button" class="act-btn btn-complete" title="Mark Completed" onclick="openConfirmModal('complete', <?php echo $o['id']; ?>)">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top:25px; text-align:center;">
    <?php if($total_pages > 1): ?>
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $status_filter; ?>" 
               style="display:inline-block; padding:8px 16px; margin:0 4px; border-radius:8px; text-decoration:none; font-weight:700; transition:0.2s;
                      <?php echo ($i == $page) ? 'background:var(--primary); color:white; box-shadow:0 4px 10px rgba(79,70,229,0.3);' : 'background:white; color:#64748b; border:1px solid #e2e8f0;'; ?>">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="levish-popup" id="modalPopup">
        <div class="modal-icon-box" id="modalHeader">
            <i class="fas fa-question-circle" id="modalIcon"></i>
        </div>
        
        <div class="modal-body">
            <h2 class="modal-h2" id="modalTitle">Confirm Action</h2>
            <p class="modal-p" id="modalText">Are you sure you want to perform this action?</p>
            
            <form method="POST" id="modalForm">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="action" id="modalActionInput">
                
                <div id="errorContent" style="display:none;">
                    <div class="error-msg-box" id="modalErrorMsg"></div>
                </div>

                <div class="modal-actions" id="modalBtns">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="modal-btn modal-btn-confirm" id="modalConfirmBtn">Yes, Proceed</button>
                </div>
                
                <button type="button" class="popup-btn" id="modalErrorBtn" style="display:none;" onclick="closeModal()">Close Popup</button>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. Copy To Clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert("Link copied! 📋"); 
        });
    }

    // 2. Row Selection Highlight (Orange Color)
    function selectRow(row) {
        // Remove existing class from all rows
        document.querySelectorAll('.custom-table tr').forEach(r => r.classList.remove('selected-row'));
        // Add class to clicked row
        row.classList.add('selected-row');
    }

    // 3. Smart Modal Logic
    function openConfirmModal(type, id, extraMsg = '') {
        // Prevent row click event bubbling
        if(window.event) window.event.stopPropagation();

        const modal = document.getElementById('confirmModal');
        const popup = document.getElementById('modalPopup');
        const header = document.getElementById('modalHeader');
        const icon = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const text = document.getElementById('modalText');
        const orderInput = document.getElementById('modalOrderId');
        const actionInput = document.getElementById('modalActionInput');
        
        const btns = document.getElementById('modalBtns');
        const errBtn = document.getElementById('modalErrorBtn');
        const errContent = document.getElementById('errorContent');
        const errMsg = document.getElementById('modalErrorMsg');
        const confirmBtn = document.getElementById('modalConfirmBtn');

        // Reset Styles & Content
        popup.className = 'levish-popup'; 
        btns.style.display = 'flex';
        errBtn.style.display = 'none';
        errContent.style.display = 'none';
        text.style.display = 'block';
        orderInput.value = id;

        if (type === 'resend') {
            popup.classList.add('theme-resend');
            icon.className = 'fas fa-redo-alt';
            title.innerText = 'Resend Order?';
            text.innerText = 'This will send a FRESH request to the API provider. Make sure the previous one failed.';
            actionInput.value = 'resend_order';
            confirmBtn.innerText = 'Yes, Resend';
        } 
        else if (type === 'refund') {
            popup.classList.add('theme-refund');
            icon.className = 'fas fa-hand-holding-usd';
            title.innerText = 'Refund & Cancel?';
            text.innerText = 'Order will be marked Cancelled/Refunded and money will be returned to User Wallet.';
            actionInput.value = 'refund_order';
            confirmBtn.innerText = 'Yes, Refund';
        }
        else if (type === 'cancel') {
            popup.classList.add('theme-cancel');
            icon.className = 'fas fa-ban';
            title.innerText = 'Cancel (No Refund)?';
            text.innerText = 'Warning: Order will be cancelled but NO money will be returned to user. Use for spam or wrong links.';
            actionInput.value = 'cancel_no_refund';
            confirmBtn.innerText = 'Cancel Order';
        }
        else if (type === 'complete') {
            popup.classList.add('theme-complete');
            icon.className = 'fas fa-check-circle';
            title.innerText = 'Force Complete?';
            text.innerText = 'Mark this order as Completed manually. No API check will be done.';
            actionInput.value = 'force_complete';
            confirmBtn.innerText = 'Mark Completed';
        }
        else if (type === 'error') {
            popup.classList.add('theme-error');
            icon.className = 'fas fa-exclamation-triangle';
            title.innerText = 'Provider Error';
            text.style.display = 'none'; // Hide default text
            errContent.style.display = 'block';
            errMsg.innerText = extraMsg; // Show actual error
            btns.style.display = 'none';
            errBtn.style.display = 'block';
        }

        modal.classList.add('active');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.remove('active');
    }

    // Close on outside click
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

<?php include '_footer.php'; ?>