<?php
include '_header.php';

$action = $_GET['action'] ?? 'list';
$prize_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// --- Handle Form Submissions (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $label = sanitize($_POST['label']);
    $amount = (float)$_POST['amount'];
    $probability = (int)$_POST['probability'];
    $color = sanitize($_POST['color']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($probability <= 0) $probability = 1;
    if ($amount < 0) $amount = 0;

    try {
        if ($action == 'edit' && $prize_id) {
            // Update
            $stmt = $db->prepare("UPDATE wheel_prizes SET label = ?, amount = ?, probability = ?, color = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$label, $amount, $probability, $color, $is_active, $prize_id]);
            $success = 'Prize updated successfully!';
        } else {
            // Create
            $stmt = $db->prepare("INSERT INTO wheel_prizes (label, amount, probability, color, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$label, $amount, $probability, $color, $is_active]);
            $success = 'Prize created successfully!';
        }
        $action = 'list'; // Go back to list view
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// --- Handle Deletion ---
if ($action == 'delete' && $prize_id) {
    try {
        $stmt = $db->prepare("DELETE FROM wheel_prizes WHERE id = ?");
        $stmt->execute([$prize_id]);
        $success = 'Prize deleted successfully!';
        $action = 'list';
    } catch (PDOException $e) {
        $error = 'Failed to delete prize.';
    }
}

// --- Load Data for Views ---
$prize = null;
if (($action == 'edit') && $prize_id) {
    $stmt = $db->prepare("SELECT * FROM wheel_prizes WHERE id = ?");
    $stmt->execute([$prize_id]);
    $prize = $stmt->fetch();
}

$prizes = [];
$total_probability = 0;
if ($action == 'list') {
    $stmt = $db->query("SELECT * FROM wheel_prizes ORDER BY amount ASC");
    $prizes = $stmt->fetchAll();
    
    // Calculate total probability
    foreach($prizes as $p) {
        if ($p['is_active']) {
            $total_probability += $p['probability'];
        }
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=San+Francisco+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --ios-bg: #F5F5F7;
        --ios-card: #FFFFFF;
        --ios-text: #1D1D1F;
        --ios-text-sec: #86868B;
        --ios-blue: #0071E3;
        --ios-green: #34C759;
        --ios-red: #FF3B30;
        --ios-orange: #FF9500;
        --ios-border: #E5E5EA;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        --radius: 16px;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif;
        background-color: var(--ios-bg);
        color: var(--ios-text);
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
    }

    /* Layout */
    .ios-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
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
        margin: 5px 0 0 0;
        font-size: 15px;
        color: var(--ios-text-sec);
    }

    /* Button */
    .btn-ios {
        background: var(--ios-blue);
        color: white;
        padding: 10px 20px;
        border-radius: 99px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 113, 227, 0.3);
    }
    .btn-ios:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 113, 227, 0.4);
    }
    .btn-ios:active {
        transform: scale(0.96);
    }

    .btn-cancel {
        background: #E5E5EA;
        color: var(--ios-text);
        box-shadow: none;
    }
    .btn-cancel:hover {
        background: #D1D1D6;
        box-shadow: none;
    }

    /* Card */
    .ios-card {
        background: var(--ios-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(0,0,0,0.02);
        padding: 24px;
        margin-bottom: 24px;
    }

    /* Alert Box */
    .alert-box {
        background: rgba(0, 113, 227, 0.1);
        color: var(--ios-blue);
        padding: 16px;
        border-radius: 12px;
        font-size: 14px;
        margin-bottom: 24px;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .alert-box strong { font-weight: 600; }
    
    .msg-success { background: #ECFDF5; color: #047857; }
    .msg-error { background: #FEF2F2; color: #B91C1C; }

    /* Table */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .ios-table th {
        text-align: left;
        padding: 16px 20px;
        background: #FAFAFA;
        color: var(--ios-text-sec);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--ios-border);
    }

    .ios-table td {
        padding: 18px 20px;
        border-bottom: 1px solid var(--ios-border);
        vertical-align: middle;
        font-size: 14px;
        color: var(--ios-text);
        transition: background-color 0.1s ease;
    }

    .ios-table tr:last-child td { border-bottom: none; }
    .ios-table tr:hover td { background-color: #F9F9FB; }

    /* Elements */
    .color-dot {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-active { background: rgba(52, 199, 89, 0.15); color: var(--ios-green); }
    .badge-inactive { background: rgba(142, 142, 147, 0.15); color: var(--ios-text-sec); }

    /* Actions */
    .actions {
        display: flex;
        gap: 8px;
    }
    .btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-edit { background: rgba(0, 113, 227, 0.1); color: var(--ios-blue); }
    .btn-edit:hover { background: var(--ios-blue); color: white; transform: translateY(-1px); }
    
    .btn-delete { background: rgba(255, 59, 48, 0.1); color: var(--ios-red); }
    .btn-delete:hover { background: var(--ios-red); color: white; transform: translateY(-1px); }

    /* Form Styles */
    .form-group { margin-bottom: 20px; }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--ios-text-sec);
        margin-bottom: 8px;
    }
    .form-input {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--ios-border);
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    .form-input:focus {
        border-color: var(--ios-blue);
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1);
    }

    /* Toggle Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 30px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #E9E9EA;
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    input:checked + .slider { background-color: var(--ios-green); }
    input:checked + .slider:before { transform: translateX(20px); }

    .color-picker-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    input[type="color"] {
        -webkit-appearance: none;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        padding: 0;
        overflow: hidden;
    }
    input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
    input[type="color"]::-webkit-color-swatch { border: none; border-radius: 50%; }

</style>

<div class="ios-container">

    <div class="page-header">
        <div class="page-title">
            <h1>Wheel Prizes</h1>
            <p>Configure chances and rewards for the spin wheel.</p>
        </div>
        <?php if ($action == 'list'): ?>
        <a href="wheel_prizes.php?action=add" class="btn-ios">
            <i class="fa-solid fa-plus"></i> New Prize
        </a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
    <div class="alert-box msg-success">
        <i class="fa-solid fa-circle-check"></i> <span><?= $success ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box msg-error">
        <i class="fa-solid fa-triangle-exclamation"></i> <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>
        
        <div class="alert-box">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                <strong>How Chances Work:</strong> System sums up all "Probability" values (Total: <?= $total_probability ?>) to calculate win percentage.<br>
                Example: If "PKR 1" has probability 80 and "PKR 50" has 20, winning chance is 80% vs 20%.
            </div>
        </div>

        <div class="ios-card" style="padding:0;">
            <div class="table-responsive">
                <table class="ios-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Amount (PKR)</th>
                            <th>Color</th>
                            <th>Probability</th>
                            <th>Win % (Approx)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($prizes)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">No prizes configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($prizes as $p): ?>
                            <tr>
                                <td style="font-weight:600;"><?= sanitize($p['label']); ?></td>
                                <td style="font-family:'SF Mono', monospace;"><?= formatCurrency($p['amount']); ?></td>
                                <td>
                                    <div class="color-dot" style="background-color: <?= sanitize($p['color']); ?>;"></div>
                                </td>
                                <td><?= $p['probability']; ?></td>
                                <td style="color:var(--ios-blue); font-weight:600;">
                                    <?php if ($p['is_active'] && $total_probability > 0): ?>
                                        <?= number_format(($p['probability'] / $total_probability) * 100, 1); ?>%
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="wheel_prizes.php?action=edit&id=<?= $p['id']; ?>" class="btn-icon btn-edit" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="wheel_prizes.php?action=delete&id=<?= $p['id']; ?>" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Delete this prize?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action == 'add' || $action == 'edit'): ?>
        
        <div class="ios-card" style="max-width: 600px; margin: 0 auto;">
            <h2 style="margin-top:0; margin-bottom:20px; font-size:20px;"><?= ($action == 'edit') ? 'Edit Prize' : 'New Prize'; ?></h2>
            
            <form action="wheel_prizes.php?action=<?= $action; ?><?= $prize_id ? '&id='.$prize_id : ''; ?>" method="POST">
                
                <div class="form-group">
                    <label class="form-label">Label (Text on Wheel)</label>
                    <input type="text" name="label" class="form-input" value="<?= sanitize($prize['label'] ?? 'PKR 10'); ?>" placeholder="e.g. Winner!" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reward Amount (PKR)</label>
                    <input type="number" name="amount" class="form-input" value="<?= sanitize($prize['amount'] ?? '10.00'); ?>" step="0.01" required>
                    <small style="color:#86868B; display:block; margin-top:5px;">Set 0 for "Try Again" or non-monetary text.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Probability (Weight)</label>
                    <input type="number" name="probability" class="form-input" value="<?= sanitize($prize['probability'] ?? '10'); ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Slice Color</label>
                    <div class="color-picker-wrapper">
                        <input type="color" name="color" value="<?= sanitize($prize['color'] ?? '#0071E3'); ?>">
                        <input type="text" class="form-input" style="width:120px;" value="<?= sanitize($prize['color'] ?? '#0071E3'); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group" style="display:flex; align-items:center; justify-content:space-between; background:#FAFAFA; padding:15px; border-radius:12px;">
                    <span style="font-weight:600; font-size:14px;">Active Status</span>
                    <label class="switch">
                        <input type="checkbox" name="is_active" value="1" <?= (isset($prize['is_active']) && $prize['is_active']) ? 'checked' : ((!isset($prize)) ? 'checked' : ''); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div style="display:flex; gap:10px; margin-top:30px;">
                    <button type="submit" class="btn-ios" style="flex:1; justify-content:center; padding:14px;">
                        <?= ($action == 'edit') ? 'Save Changes' : 'Create Prize'; ?>
                    </button>
                    <a href="wheel_prizes.php" class="btn-ios btn-cancel" style="padding:14px;">Cancel</a>
                </div>

            </form>
        </div>

    <?php endif; ?>

</div>

<script>
// Sync text input with color picker if needed (optional enhancement)
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.querySelector('input[type="color"]');
    const colorText = document.querySelector('.color-picker-wrapper input[type="text"]');
    
    if(colorPicker && colorText) {
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value;
        });
    }
});
</script>

<?php include '_footer.php'; ?>
