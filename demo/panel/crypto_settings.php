<?php
include '_header.php';
requireAdmin();

// Update Rates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='usdt_sell_rate'")->execute([$_POST['sell_rate']]);
    $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='usdt_cost_rate'")->execute([$_POST['cost_rate']]);
    $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='usdt_status'")->execute([$_POST['status']]);
    
    // Update global cache
    echo "<script>Swal.fire('Updated', 'Rates updated successfully!', 'success');</script>";
}

// Fetch Current
$sell = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_sell_rate'")->fetchColumn();
$cost = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_cost_rate'")->fetchColumn();
$status = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_status'")->fetchColumn();
$profit = $sell - $cost;
?>

<div class="container" style="max-width:600px;">
    <div class="card p-4">
        <h3>⚙️ USDT Rate Manager</h3>
        <p class="text-muted">Set your profit margins here.</p>
        
        <div class="alert alert-success">
            <strong>Expected Profit:</strong> <?= $profit ?> PKR per USDT 🤑
        </div>

        <form method="POST">
            <div class="mb-3">
                <label>Selling Rate (User Price)</label>
                <input type="number" step="0.01" name="sell_rate" value="<?= $sell ?>" class="form-control" style="font-size:20px; font-weight:bold; color:green;">
                <small>User will buy at this rate.</small>
            </div>

            <div class="mb-3">
                <label>Your Cost (Market Rate)</label>
                <input type="number" step="0.01" name="cost_rate" value="<?= $cost ?>" class="form-control">
                <small>Used to calculate your profit report.</small>
            </div>

            <div class="mb-3">
                <label>Module Status</label>
                <select name="status" class="form-control">
                    <option value="1" <?= $status==1?'selected':'' ?>>✅ Active (Buying Enabled)</option>
                    <option value="0" <?= $status==0?'selected':'' ?>>❌ Disabled (Maintenance)</option>
                </select>
            </div>

            <button class="btn btn-primary w-100 p-3">Update Rates</button>
        </form>
    </div>
</div>

<?php include '_footer.php'; ?>