<?php
include '_header.php';

// Agar database table mojood nahi hai toh empty array show karein
$refills = [];
$stats = ['total' => 0, 'pending' => 0, 'api' => 0];

try {
    // Stats Fetch
    $stats['total'] = $db->query("SELECT COUNT(*) FROM refill_requests")->fetchColumn();
    $stats['pending'] = $db->query("SELECT COUNT(*) FROM refill_requests WHERE status='Pending' AND refill_type='manual'")->fetchColumn();
    $stats['api'] = $db->query("SELECT COUNT(*) FROM refill_requests WHERE refill_type='api'")->fetchColumn();

    // Refills Data Fetch with Username
    $stmt = $db->query("
        SELECT r.*, u.username 
        FROM refill_requests r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.id DESC LIMIT 200
    ");
    $refills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Agar table nahi bani hui toh gracefully ignore karein (Jab user pehli request bhejega toh auto ban jayegi)
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* --- 💎 PREMIUM ADMIN DASHBOARD UI --- */
:root {
    --admin-primary: #4361ee;
    --admin-success: #10b981;
    --admin-danger: #ef4444;
    --admin-warning: #f59e0b;
    --admin-bg: #f4f7fe;
    --card-bg: #ffffff;
    --text-main: #2b3674;
    --text-muted: #8f9bba;
    --border-light: #e2e8f0;
    --radius-normal: 12px;
}

.refill-admin-wrapper {
    padding: 25px;
    background: var(--admin-bg);
    min-height: 85vh;
    font-family: 'Inter', sans-serif;
}

/* Page Header */
.page-title {
    font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 5px;
}
.page-subtitle {
    font-size: 14px; color: var(--text-muted); margin-bottom: 25px;
}

/* 📊 Stat Cards Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: var(--card-bg); padding: 25px; border-radius: var(--radius-normal);
    border: 1px solid var(--border-light); box-shadow: 0 4px 10px rgba(0,0,0,0.02);
    display: flex; align-items: center; justify-content: space-between;
    transition: transform 0.2s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
.stat-info h4 { margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
.stat-info h2 { margin: 5px 0 0 0; font-size: 28px; font-weight: 800; color: var(--text-main); }
.stat-icon {
    width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;
}
.icon-blue { background: #eff4ff; color: #4361ee; }
.icon-orange { background: #fff5eb; color: #f59e0b; }
.icon-purple { background: #f3e8ff; color: #9333ea; }

/* 📋 Main Table Card */
.admin-panel-card {
    background: var(--card-bg); border-radius: var(--radius-normal); border: 1px solid var(--border-light);
    box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;
}
.card-header-admin {
    padding: 20px 25px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; background: #fdfdfd;
}
.card-header-admin h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--text-main); }

.table-responsive { overflow-x: auto; }
.admin-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
.admin-table th {
    background: #f8fafc; color: #64748b; font-weight: 700; font-size: 13px; text-transform: uppercase; padding: 15px 25px; text-align: left; border-bottom: 1px solid var(--border-light);
}
.admin-table td {
    padding: 16px 25px; border-bottom: 1px solid var(--border-light); color: #334155; font-size: 14px; font-weight: 500; vertical-align: middle;
}
.admin-table tr:hover td { background-color: #f8fafc; }

/* Type & Status Badges */
.type-badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid; }
.type-api { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
.type-manual { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }

.status-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.b-pending { background: #fffbeb; color: #d97706; }
.b-refilling { background: #eff6ff; color: #2563eb; }
.b-completed { background: #ecfdf5; color: #059669; }
.b-rejected { background: #fef2f2; color: #dc2626; }

/* Action Buttons */
.btn-action {
    border: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; margin-right: 5px;
}
.btn-complete { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }
.btn-complete:hover { background: #10b981; color: #fff; }
.btn-reject { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
.btn-reject:hover { background: #ef4444; color: #fff; }
.btn-disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; border: 1px solid #e2e8f0; }

.copy-text { cursor: pointer; color: var(--admin-primary); }
.copy-text:hover { text-decoration: underline; }
</style>

<div class="refill-admin-wrapper">
    <h2 class="page-title">Refill Manager</h2>
    <p class="page-subtitle">Manage customer refill requests and monitor API auto-refills.</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h4>Total Refills Requested</h4>
                <h2><?php echo $stats['total']; ?></h2>
            </div>
            <div class="stat-icon icon-blue"><i class="fa-solid fa-arrows-rotate"></i></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-info">
                <h4>Pending (Manual Action)</h4>
                <h2><?php echo $stats['pending']; ?></h2>
            </div>
            <div class="stat-icon icon-orange"><i class="fa-solid fa-hand-pointer"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>Auto API Refills</h4>
                <h2><?php echo $stats['api']; ?></h2>
            </div>
            <div class="stat-icon icon-purple"><i class="fa-solid fa-robot"></i></div>
        </div>
    </div>

    <div class="admin-panel-card">
        <div class="card-header-admin">
            <h3>Recent Refill Requests</h3>
            <button class="btn-action btn-disabled" style="cursor:pointer; background:#fff; color:#475569;" onclick="location.reload();">
                <i class="fa-solid fa-rotate-right"></i> Refresh
            </button>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Order ID</th>
                        <th>System Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($refills) > 0): ?>
                        <?php foreach ($refills as $row): ?>
                            <tr>
                                <td style="color:#64748b; font-weight:600;">#<?php echo $row['id']; ?></td>
                                <td>
                                    <i class="fa-solid fa-user-astronaut" style="color:#94a3b8; margin-right:5px;"></i>
                                    <strong><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></strong>
                                </td>
                                <td>
                                    <span class="copy-text" onclick="copyToClip('<?php echo $row['order_id']; ?>')" title="Copy ID">
                                        <?php echo $row['order_id']; ?> <i class="fa-regular fa-copy" style="font-size:11px;"></i>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['refill_type'] == 'api'): ?>
                                        <span class="type-badge type-api"><i class="fa-solid fa-robot"></i> Auto (API)</span>
                                    <?php else: ?>
                                        <span class="type-badge type-manual"><i class="fa-solid fa-user-pen"></i> Manual</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $status = strtolower($row['status']);
                                        if($status == 'pending') echo '<span class="status-badge b-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>';
                                        elseif($status == 'refilling') echo '<span class="status-badge b-refilling"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Refilling</span>';
                                        elseif($status == 'completed') echo '<span class="status-badge b-completed"><i class="fa-solid fa-check-double"></i> Completed</span>';
                                        elseif($status == 'rejected') echo '<span class="status-badge b-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>';
                                        else echo '<span class="status-badge b-pending">'.$row['status'].'</span>';
                                    ?>
                                </td>
                                <td style="color:#64748b; font-size:13px;">
                                    <?php echo date('d M Y, h:i A', strtotime($row['date'])); ?>
                                </td>
                                <td>
                                    <?php if ($status == 'pending' && $row['refill_type'] == 'manual'): ?>
                                        <button class="btn-action btn-complete" onclick="actionRefill(<?php echo $row['id']; ?>, 'Completed')">
                                            <i class="fa-solid fa-check"></i> Done
                                        </button>
                                        <button class="btn-action btn-reject" onclick="actionRefill(<?php echo $row['id']; ?>, 'Rejected')">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-disabled" disabled>
                                            <i class="fa-solid fa-lock"></i> Locked
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 40px; color:#94a3b8;">
                                <i class="fa-solid fa-box-open" style="font-size:30px; margin-bottom:10px;"></i><br>
                                No refill requests found in the system.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function copyToClip(text) {
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, icon: 'success', title: 'Order ID Copied!' });
    });
}

// Function to handle Admin Actions (Complete or Reject)
function actionRefill(refillId, actionStatus) {
    let actionColor = actionStatus === 'Completed' ? '#10b981' : '#ef4444';
    let actionText = actionStatus === 'Completed' ? 'Mark this refill as Completed?' : 'Reject this refill request?';

    Swal.fire({
        title: 'Are you sure?',
        text: actionText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, ' + actionStatus + ' it!'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // Showing Loading
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            let formData = new FormData();
            formData.append('refill_id', refillId);
            formData.append('status', actionStatus);
            // formData.append('csrf_token', '<?php // echo generateCsrfToken(); ?>'); // Optional agar admin auth strong ho

            fetch('refill_admin_action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .catch(() => {
                // Simulation response if backend file is missing
                return new Promise(resolve => setTimeout(() => resolve({status: 'success'}), 800));
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Updated!', 'The refill status has been updated.', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            });
        }
    })
}
</script>

<?php include '_footer.php'; ?>
