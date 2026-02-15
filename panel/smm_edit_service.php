<?php
include '_header.php';

$is_category_edit = isset($_GET['category']);
$id = (int)($_GET['id'] ?? 0);
$category_name = sanitize($_GET['category'] ?? '');
$error = ''; $message = ''; $service = null;

if ($is_category_edit) {
    // Category Edit Mode
    $page_title = "Edit Category";
    $current_name = $category_name;
} elseif ($id) {
    // Service Edit Mode
    $page_title = "Edit Service";
    try {
        $stmt = $db->prepare("
            SELECT s.*, p.name as provider_name
            FROM smm_services s
            JOIN smm_providers p ON s.provider_id = p.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $service = $stmt->fetch();
        if (!$service) {
            $error = "Service not found.";
        }
    } catch (PDOException $e) {
        $error = "Database Error.";
    }
} else {
    $error = "Invalid request.";
}

// --- Form Submission Handle Karein ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_category_edit) {
        // Category Name Update
        $new_category_name = sanitize($_POST['new_category_name'] ?? '');
        if (empty($new_category_name)) {
            $error = "New category name cannot be empty.";
        } else {
            try {
                // Existing category mein jitni bhi services hain, un sab ka naam badal do
                $stmt = $db->prepare("UPDATE smm_services SET category = ? WHERE category = ?");
                $stmt->execute([$new_category_name, $category_name]);
                $message = "Category name updated successfully to '{$new_category_name}'.";
                
                // Nayi category naam ke saath redirect karein
                echo "<script>window.location.href='smm_services.php?success=".urlencode($message)."';</script>";
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update category name: " . $e->getMessage();
            }
        }
    } elseif ($service) {
        // Single Service Update
        $new_name = sanitize($_POST['name'] ?? '');
        $new_category = sanitize($_POST['category'] ?? '');
        $new_service_rate = (float)($_POST['service_rate'] ?? 0);
        $new_description = sanitize($_POST['description'] ?? '');
        $new_is_active = (int)($_POST['is_active'] ?? 0);

        if (empty($new_name) || empty($new_category) || $new_service_rate <= 0) {
            $error = "Service name, Category, and User Rate are required.";
        } else {
            try {
                $stmt = $db->prepare("
                    UPDATE smm_services SET 
                    name = ?, category = ?, service_rate = ?, description = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_name, $new_category, $new_service_rate, $new_description, $new_is_active, $id]);
                $message = "Service updated successfully! 🚀";
                
                // Updated data ko refresh karein
                $stmt_refresh = $db->prepare("SELECT s.*, p.name as provider_name FROM smm_services s JOIN smm_providers p ON s.provider_id = p.id WHERE s.id = ?");
                $stmt_refresh->execute([$id]);
                $service = $stmt_refresh->fetch(); 
            } catch (PDOException $e) {
                $error = "Failed to update service: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
    :root {
        --ios-bg: #F2F2F7;
        --ios-card: #FFFFFF;
        --ios-text: #1C1C1E;
        --ios-blue: #007AFF;
        --ios-gray: #8E8E93;
        --ios-border: #E5E5EA;
        --ios-danger: #FF3B30;
        --ios-success: #34C759;
    }

    body {
        background-color: var(--ios-bg);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: var(--ios-text);
        margin: 0;
        padding: 0;
    }

    .ios-container {
        max-width: 700px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 20px;
        text-align: center;
    }

    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
    }

    .page-header p {
        color: var(--ios-gray);
        font-size: 15px;
        margin-top: 5px;
    }

    .ios-card {
        background: var(--ios-card);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        margin-bottom: 25px;
        transition: transform 0.2s ease;
    }

    .card-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--ios-gray);
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .form-control {
        width: 100%;
        background: var(--ios-bg);
        border: none;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 16px;
        color: var(--ios-text);
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        background: #fff;
        box-shadow: 0 0 0 2px var(--ios-blue);
        outline: none;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .btn-ios {
        display: block;
        width: 100%;
        background-color: var(--ios-blue);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        transition: transform 0.1s, opacity 0.2s;
        margin-top: 10px;
    }

    .btn-ios:active {
        transform: scale(0.98);
        opacity: 0.9;
    }

    .btn-secondary {
        background-color: transparent;
        color: var(--ios-blue);
        margin-top: 15px;
        font-weight: 500;
        padding: 10px;
    }

    .btn-secondary:hover {
        background-color: rgba(0, 122, 255, 0.05);
    }

    /* Alerts */
    .ios-alert {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 15px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-error {
        background-color: rgba(255, 59, 48, 0.1);
        color: var(--ios-danger);
    }

    .alert-success {
        background-color: rgba(52, 199, 89, 0.1);
        color: var(--ios-success);
    }

    /* Details Table */
    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .details-table th, .details-table td {
        padding: 12px 0;
        border-bottom: 1px solid var(--ios-border);
        font-size: 15px;
    }

    .details-table th {
        text-align: left;
        color: var(--ios-gray);
        font-weight: 500;
        width: 40%;
    }

    .details-table td {
        text-align: right;
        font-weight: 600;
    }

    .details-table tr:last-child th, 
    .details-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-provider {
        background: #E5E5EA;
        color: #000;
    }

    small {
        display: block;
        margin-top: 6px;
        font-size: 13px;
        color: var(--ios-gray);
    }
</style>

<div class="ios-container">

    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <?php if($is_category_edit): ?>
            <p>Rename category for all services.</p>
        <?php elseif($service): ?>
            <p>Modify service details and pricing.</p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="ios-alert alert-error">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="ios-alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($is_category_edit && !$error): ?>
        <div class="ios-card">
            <div class="card-body">
                <form action="smm_edit_service.php?category=<?php echo urlencode($category_name); ?>" method="POST">
                    <div class="form-group">
                        <label class="form-label">Current Name</label>
                        <input type="text" class="form-control" value="<?php echo sanitize($category_name); ?>" disabled style="opacity: 0.6;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="new_category_name">New Name</label>
                        <input type="text" id="new_category_name" name="new_category_name" class="form-control" placeholder="Enter new category name" value="<?php echo sanitize($category_name); ?>" required>
                        <small>⚠️ Warning: This will move all services from "<?php echo sanitize($category_name); ?>" to the new name.</small>
                    </div>

                    <button type="submit" class="btn-ios">Save Changes</button>
                    <a href="smm_services.php" class="btn-ios btn-secondary">Cancel</a>
                </form>
            </div>
        </div>

    <?php elseif ($service): ?>
        <div class="ios-card">
            <div class="card-body">
                <form action="smm_edit_service.php?id=<?php echo $service['id']; ?>" method="POST">
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Service Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($service['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <input type="text" id="category" name="category" class="form-control" value="<?php echo sanitize($service['category']); ?>" required>
                    </div>

                    <div style="background: #f9f9fa; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #eaeaeb;">
                        <div class="form-group" style="margin-bottom: 5px;">
                            <label class="form-label" for="service_rate">Your Selling Rate (PKR)</label>
                            <input type="number" id="service_rate" name="service_rate" class="form-control" step="0.0001" value="<?php echo (float)$service['service_rate']; ?>" style="font-weight: bold; color: var(--ios-blue);" required>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-top: 8px; padding: 0 5px;">
                            <span style="color: var(--ios-gray);">Cost: <b><?php echo formatCurrency($service['base_price'] ?? 0); ?></b></span>
                            <span style="color: var(--ios-success);">Profit: <b><?php echo formatCurrency($service['service_rate'] - ($service['base_price'] ?? 0)); ?></b></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" placeholder="Add service details here..."><?php echo sanitize($service['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="is_active">Visibility</label>
                        <select id="is_active" name="is_active" class="form-control">
                            <option value="1" <?php echo $service['is_active'] ? 'selected' : ''; ?>>🟢 Active (Visible)</option>
                            <option value="0" <?php echo !$service['is_active'] ? 'selected' : ''; ?>>🔴 Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div style="margin-top: 30px;">
                        <label class="form-label">Provider Information</label>
                        <div style="background: var(--ios-bg); border-radius: 12px; padding: 15px;">
                            <table class="details-table">
                                <tr>
                                    <th>Source</th>
                                    <td><span class="badge badge-provider"><?php echo sanitize($service['provider_name']); ?></span></td>
                                </tr>
                                <tr>
                                    <th>Service ID</th>
                                    <td>#<?php echo sanitize($service['service_id'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Min Order</th>
                                    <td><?php echo number_format($service['min']); ?></td>
                                </tr>
                                <tr>
                                    <th>Max Order</th>
                                    <td><?php echo number_format($service['max']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn-ios">Update Service</button>
                    <a href="smm_services.php" class="btn-ios btn-secondary">Discard Changes</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include '_footer.php'; ?>