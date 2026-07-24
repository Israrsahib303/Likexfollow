<?php
// Error Debugging On (Yeh Blank Screen aane se rokega aur asli error dikhayega)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database Path Auto-Detector
$db_paths = ['../includes/db.php', 'includes/db.php', '../../includes/db.php', '../db.php'];
$db_loaded = false;
foreach ($db_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $db_loaded = true;
        break;
    }
}

if (!$db_loaded) {
    die("<b>Error:</b> Database file (db.php) not found. Please check your folder structure.");
}

// 1. AUTO CREATE TABLES & COLUMNS IF MISSING
try {
    // Create panel_rentals table
    $db->exec("CREATE TABLE IF NOT EXISTS `panel_rentals` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `panel_type` VARCHAR(20) NOT NULL,
        `domain` VARCHAR(255) NOT NULL,
        `admin_user` VARCHAR(255) NOT NULL,
        `admin_pass` VARCHAR(255) NOT NULL,
        `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
        `price_per_month` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
        `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
        `expiry_date` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check & Add settings columns
    $settings_cols = $db->query("SHOW COLUMNS FROM `settings`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('child_panel_price', $settings_cols)) {
        $db->exec("ALTER TABLE `settings` ADD `child_panel_price` DECIMAL(10,2) NOT NULL DEFAULT '10.00'");
    }
    if (!in_array('rental_panel_price', $settings_cols)) {
        $db->exec("ALTER TABLE `settings` ADD `rental_panel_price` DECIMAL(10,2) NOT NULL DEFAULT '25.00'");
    }
} catch (Exception $e) {
    // Silent fail if table/columns already exist
}

$msg_success = '';
$msg_error = '';

// 2. HANDLE PRICING UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_prices') {
    $child_price  = (float)($_POST['child_panel_price'] ?? 10);
    $rental_price = (float)($_POST['rental_panel_price'] ?? 25);

    try {
        $stmt_check = $db->query("SELECT id FROM settings LIMIT 1");
        if ($stmt_check->rowCount() > 0) {
            $stmt_up = $db->prepare("UPDATE settings SET child_panel_price = ?, rental_panel_price = ? LIMIT 1");
            $stmt_up->execute([$child_price, $rental_price]);
        } else {
            $stmt_in = $db->prepare("INSERT INTO settings (child_panel_price, rental_panel_price) VALUES (?, ?)");
            $stmt_in->execute([$child_price, $rental_price]);
        }
        $msg_success = "Panel pricing updated successfully!";
    } catch (Exception $e) {
        $msg_error = "Error updating pricing: " . $e->getMessage();
    }
}

// 3. HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $rental_id  = (int)($_POST['rental_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');

    if ($rental_id > 0 && in_array($new_status, ['Pending', 'Active', 'Suspended', 'Canceled'])) {
        try {
            if ($new_status === 'Active') {
                $new_expiry = date('Y-m-d H:i:s', strtotime('+1 month'));
                $stmt_st = $db->prepare("UPDATE panel_rentals SET status = ?, expiry_date = ? WHERE id = ?");
                $stmt_st->execute([$new_status, $new_expiry, $rental_id]);
            } else {
                $stmt_st = $db->prepare("UPDATE panel_rentals SET status = ? WHERE id = ?");
                $stmt_st->execute([$new_status, $rental_id]);
            }
            $msg_success = "Panel status updated to '{$new_status}' successfully!";
        } catch (Exception $e) {
            $msg_error = "Error updating status: " . $e->getMessage();
        }
    } else {
        $msg_error = "Invalid parameters provided.";
    }
}

// 4. FETCH CURRENT SETTINGS
$child_price = 10.00;
$rental_price = 25.00;
try {
    $stmt_set = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt_set->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        $child_price = $settings['child_panel_price'] ?? 10.00;
        $rental_price = $settings['rental_panel_price'] ?? 25.00;
    }
} catch (Exception $e) {}

// 5. FETCH ALL RENTAL ORDERS
$rentals = [];
try {
    $stmt_rent = $db->query("SELECT * FROM panel_rentals ORDER BY id DESC");
    $rentals = $stmt_rent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Panel Manager - Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --admin-purple: #4f46e5;
            --admin-purple-dark: #4338ca;
            --bg-color: #f4f3fb;
            --card-bg: #ffffff;
            --text-dark: #1c1c1e;
            --text-muted: #8e8e93;
            --border-color: #e5e5ea;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Inter", sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            line-height: 1.5;
            padding: 30px 20px;
        }

        .admin-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h1 i { color: var(--admin-purple); }

        .alert-box {
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .pricing-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid var(--border-color);
            margin-bottom: 35px;
        }

        .card-heading {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 18px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pricing-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        @media (max-width: 768px) {
            .pricing-form { grid-template-columns: 1fr; }
        }

        .input-wrap label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text-dark);
        }

        .admin-input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            background: #fafafa;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            outline: none;
            transition: 0.2s;
        }

        .admin-input:focus {
            border-color: var(--admin-purple);
            background: #fff;
        }

        .btn-save {
            background: var(--admin-purple);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.2s;
            height: 46px;
        }

        .btn-save:hover {
            background: var(--admin-purple-dark);
        }

        .table-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .admin-table th {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 14px 10px;
            border-bottom: 2px solid var(--border-color);
            text-align: left;
        }

        .admin-table td {
            padding: 16px 10px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .domain-link {
            color: var(--admin-purple);
            font-weight: 700;
            text-decoration: none;
        }

        .domain-link:hover { text-decoration: underline; }

        .type-badge {
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            background: #eef2ff;
            color: var(--admin-purple);
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 0.85rem;
            outline: none;
            cursor: pointer;
        }

        .btn-info {
            background: #f3f4f6;
            color: var(--text-dark);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-info:hover {
            background: var(--admin-purple);
            color: #fff;
        }
    </style>
</head>
<body>

<div class="admin-container">

    <div class="header-title">
        <h1><i class="fa-solid fa-sliders"></i> Rental & Child Panel Manager</h1>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($msg_success)): ?>
        <div class="alert-box alert-success">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg_success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($msg_error)): ?>
        <div class="alert-box alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($msg_error); ?>
        </div>
    <?php endif; ?>

    <!-- PRICING CONFIGURATION FORM -->
    <div class="pricing-card">
        <div class="card-heading">
            <i class="fa-solid fa-tags" style="color:var(--admin-purple);"></i> Monthly Rental Rates Settings
        </div>
        <form method="POST" class="pricing-form">
            <input type="hidden" name="action" value="update_prices">
            
            <div class="input-wrap">
                <label>Child Panel Monthly Rate ($)</label>
                <input type="number" step="0.01" name="child_panel_price" class="admin-input" value="<?php echo htmlspecialchars($child_price); ?>" required>
            </div>

            <div class="input-wrap">
                <label>Rental Panel Monthly Rate ($)</label>
                <input type="number" step="0.01" name="rental_panel_price" class="admin-input" value="<?php echo htmlspecialchars($rental_price); ?>" required>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-floppy-disk"></i> Save Rates
            </button>
        </form>
    </div>

    <!-- ORDERS MANAGEMENT TABLE -->
    <div class="table-card">
        <div class="card-heading">
            <i class="fa-solid fa-list-check" style="color:var(--admin-purple);"></i> Customer Panel Requests
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Domain</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Credentials</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rentals) > 0): ?>
                    <?php foreach ($rentals as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><strong>User #<?php echo $row['user_id']; ?></strong></td>
                            <td>
                                <a href="https://<?php echo htmlspecialchars($row['domain']); ?>" target="_blank" class="domain-link">
                                    <i class="fa-solid fa-globe"></i> <?php echo htmlspecialchars($row['domain']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="type-badge"><?php echo htmlspecialchars($row['panel_type']); ?></span>
                            </td>
                            <td>$<?php echo number_format($row['price_per_month'], 2); ?></td>
                            <td>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="rental_id" value="<?php echo $row['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="Pending" <?php echo ($row['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Active" <?php echo ($row['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="Suspended" <?php echo ($row['status'] === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                                        <option value="Canceled" <?php echo ($row['status'] === 'Canceled') ? 'selected' : ''; ?>>Canceled</option>
                                    </select>
                                </form>
                            </td>
                            <td style="color:var(--text-muted); font-size:0.85rem;">
                                <?php echo !empty($row['expiry_date']) ? date('d M Y, h:i A', strtotime($row['expiry_date'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <button class="btn-info" onclick="showCredentials('<?php echo htmlspecialchars($row['domain']); ?>', '<?php echo htmlspecialchars($row['admin_user']); ?>', '<?php echo htmlspecialchars($row['admin_pass']); ?>', '<?php echo htmlspecialchars($row['currency']); ?>')">
                                    <i class="fa-solid fa-key"></i> View Info
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:30px; color:var(--text-muted);">
                            No panel rental requests found in database.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function showCredentials(domain, email, pass, currency) {
    Swal.fire({
        title: 'Panel Credentials',
        html: `
            <div style="text-align:left; font-size:0.95rem; line-height:1.8;">
                <strong>Domain:</strong> <span style="color:#4f46e5;">${domain}</span><br>
                <strong>Admin Email:</strong> ${email}<br>
                <strong>Admin Pass:</strong> ${pass}<br>
                <strong>Currency:</strong> ${currency}
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#4f46e5'
    });
}
</script>

</body>
</html>
