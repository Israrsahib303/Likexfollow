<?php
include '_header.php';
requireAdmin();

// --- 1. HANDLE ACTIONS (With Redirect to prevent Resubmission) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ADD EXCHANGE
    if (isset($_POST['add_exchange'])) {
        $name = sanitize($_POST['name']);
        $type = sanitize($_POST['input_type']); 
        $label = sanitize($_POST['input_label']); 
        $placeholder = sanitize($_POST['input_placeholder']);
        $min = (float)$_POST['min_limit'];
        $max = (float)$_POST['max_limit'];
        
        $icon = 'default.png';
        if (!empty($_FILES['icon']['name'])) {
            $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
            $icon = 'exch_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['icon']['tmp_name'], '../assets/img/icons/' . $icon);
        }

        $stmt = $db->prepare("INSERT INTO crypto_exchanges (name, icon, input_type, input_label, input_placeholder, min_limit, max_limit, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $icon, $type, $label, $placeholder, $min, $max]);
        
        // Redirect to self to clear POST data
        header("Location: crypto_exchanges.php?msg=added");
        exit;
    }

    // DELETE EXCHANGE
    if (isset($_POST['delete_id'])) {
        $db->prepare("DELETE FROM crypto_exchanges WHERE id=?")->execute([$_POST['delete_id']]);
        header("Location: crypto_exchanges.php?msg=deleted");
        exit;
    }

    // TOGGLE STATUS
    if (isset($_POST['toggle_id'])) {
        $id = (int)$_POST['toggle_id'];
        $current = (int)$_POST['current_status'];
        $new = $current ? 0 : 1;
        $db->prepare("UPDATE crypto_exchanges SET status=? WHERE id=?")->execute([$new, $id]);
        header("Location: crypto_exchanges.php?msg=updated");
        exit;
    }
}

// Fetch Data
$exchanges = $db->query("SELECT * FROM crypto_exchanges ORDER BY id DESC")->fetchAll();
?>

<?php if(isset($_GET['msg'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const msg = "<?= $_GET['msg'] ?>";
        if(msg === 'added') Swal.fire({icon:'success', title:'Success', text:'New payment method added!', timer:2000, showConfirmButton:false});
        if(msg === 'deleted') Swal.fire({icon:'success', title:'Deleted', text:'Exchange removed successfully.', timer:2000, showConfirmButton:false});
        if(msg === 'updated') const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000}); Toast.fire({icon: 'success', title: 'Status Updated'});
        
        // Clean URL
        window.history.replaceState(null, null, window.location.pathname);
    });
</script>
<?php endif; ?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-gradient-primary text-white border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>New Method</h5>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-25">
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="small text-muted fw-bold text-uppercase">Exchange Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Binance Pay" required>
                            </div>
                            <div class="col-4">
                                <label class="small text-muted fw-bold text-uppercase">Icon</label>
                                <input type="file" name="icon" class="form-control form-control-sm" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold text-uppercase">Verification Type</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="input_type" id="typeID" value="id" checked onclick="updLbl('id')">
                                <label class="btn btn-outline-primary w-50" for="typeID"><i class="fa-solid fa-id-badge"></i> User ID</label>

                                <input type="radio" class="btn-check" name="input_type" id="typeAddr" value="address" onclick="updLbl('addr')">
                                <label class="btn btn-outline-primary w-50" for="typeAddr"><i class="fa-solid fa-wallet"></i> Wallet</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold text-uppercase">Input Heading</label>
                            <input type="text" name="input_label" id="lblInput" class="form-control" value="Enter Binance Pay ID">
                            <small class="text-xs text-muted">User will see this above input box.</small>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted fw-bold text-uppercase">Placeholder Hint</label>
                            <input type="text" name="input_placeholder" class="form-control" placeholder="e.g. 123456789">
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="small text-muted fw-bold text-uppercase">Min ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">$</span>
                                    <input type="number" step="any" name="min_limit" class="form-control border-start-0" value="10">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted fw-bold text-uppercase">Max ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">$</span>
                                    <input type="number" step="any" name="max_limit" class="form-control border-start-0" value="1000">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="add_exchange" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-check-circle me-2"></i> Create Exchange
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">Active Exchanges</h5>
                        <p class="text-muted small mb-0">Manage your P2P payment gateways.</p>
                    </div>
                    <div class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">
                        Total: <?= count($exchanges) ?>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if(empty($exchanges)): ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="80" style="opacity:0.3">
                            <p class="text-muted mt-3">No payment methods found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-3">Method</th>
                                        <th>Limits</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <?php foreach($exchanges as $ex): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 position-relative">
                                                    <img src="../assets/img/icons/<?= $ex['icon'] ?>" class="rounded-3 shadow-sm" style="width:45px; height:45px; object-fit:contain; background:#fff; padding:4px;">
                                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-<?= $ex['status']?'success':'danger' ?> border border-light rounded-circle"></span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($ex['name']) ?></h6>
                                                    <small class="text-muted" style="font-size:11px;">
                                                        <i class="fa-solid <?= $ex['input_type']=='id'?'fa-id-card':'fa-wallet' ?> me-1"></i>
                                                        <?= $ex['input_label'] ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small class="fw-bold text-dark">$<?= $ex['min_limit'] ?> <span class="text-muted fw-normal">min</span></small>
                                                <small class="fw-bold text-dark">$<?= $ex['max_limit'] ?> <span class="text-muted fw-normal">max</span></small>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="toggle_id" value="<?= $ex['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $ex['status'] ?>">
                                                <?php if($ex['status']): ?>
                                                    <button class="badge border-0 bg-soft-success text-success px-3 py-2 cursor-pointer">
                                                        Active
                                                    </button>
                                                <?php else: ?>
                                                    <button class="badge border-0 bg-soft-danger text-danger px-3 py-2 cursor-pointer">
                                                        Disabled
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this method permanently?');">
                                                <input type="hidden" name="delete_id" value="<?= $ex['id'] ?>">
                                                <button class="btn btn-icon btn-sm btn-soft-danger text-danger rounded-circle" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
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

    </div>
</div>

<script>
function updLbl(type) {
    const el = document.getElementById('lblInput');
    if(type === 'id') {
        el.value = "Enter Binance Pay ID";
        el.placeholder = "e.g. Enter User ID";
    } else {
        el.value = "Enter USDT (TRC20) Address";
        el.placeholder = "e.g. Paste Wallet Address";
    }
}
</script>

<style>
/* Beast UI Helpers */
.bg-gradient-primary { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
.bg-soft-primary { background-color: rgba(79, 70, 229, 0.1); }
.bg-soft-success { background-color: rgba(16, 185, 129, 0.1); }
.bg-soft-danger { background-color: rgba(239, 68, 68, 0.1); }
.text-xs { font-size: 0.75rem; }
.btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; transition:0.2s; }
.btn-icon:hover { transform: scale(1.1); }
.cursor-pointer { cursor: pointer; }
.card { transition: all 0.3s ease; }
</style>

<?php include '_footer.php'; ?>