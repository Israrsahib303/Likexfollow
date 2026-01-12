<?php
include '_header.php';
include '_auth_check.php';

// ==========================================
// --- 1. BACKEND LOGIC ---
// ==========================================

// --- HANDLE ACTIONS (Refund/Cancel) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // REFUND & CANCEL ORDER
    if (isset($_POST['refund_order'])) {
        $order_id = (int)$_POST['order_id'];
        
        try {
            $db->beginTransaction();

            // Get Order Details
            $stmt = $db->prepare("SELECT o.*, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if ($order && $order['status'] !== 'cancelled') {
                $amount = $order['total_price'];
                $uid = $order['user_id'];

                // A. Refund Balance to User
                $upd = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $upd->execute([$amount, $uid]);

                // B. Add to Ledger
                $led = $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, ref_type, ref_id, note, created_at) VALUES (?, 'credit', ?, 'refund', ?, 'Refund for Order #$order_id', NOW())");
                $led->execute([$uid, $amount, $order_id]);

                // C. Mark Order as Cancelled
                $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);

                $db->commit();
                echo "<script>Swal.fire('Refunded!', 'Order cancelled and money returned.', 'success');</script>";
            } else {
                echo "<script>Swal.fire('Error', 'Order already cancelled.', 'error');</script>";
            }

        } catch (Exception $e) {
            $db->rollBack();
            echo "<script>Swal.fire('Error', '".$e->getMessage()."', 'error');</script>";
        }
    }

    // DELETE ORDER
    if (isset($_POST['delete_order'])) {
        $order_id = (int)$_POST['order_id'];
        $db->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
        echo "<script>Swal.fire('Deleted', 'Order record removed.', 'success');</script>";
    }
}

// --- GET GLOBAL STATS (Before Filtering) ---
// 1. Total Digital Orders
$total_orders_stmt = $db->query("SELECT COUNT(*) FROM orders o JOIN products p ON o.product_id = p.id WHERE p.is_digital = 1");
$total_orders_count = $total_orders_stmt->fetchColumn();

// 2. Total Refunded Amount
$total_refunded_stmt = $db->query("SELECT SUM(total_price) FROM orders o JOIN products p ON o.product_id = p.id WHERE p.is_digital = 1 AND o.status = 'cancelled'");
$total_refunded_amount = $total_refunded_stmt->fetchColumn() ?: 0;


// --- SEARCH & FILTER LOGIC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];

$query = "SELECT o.*, p.name as product_name, p.icon, u.name as user_name, u.email 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          JOIN users u ON o.user_id = u.id 
          WHERE p.is_digital = 1";

if (!empty($search)) {
    // Search by Product Name, User Name, Email, or Order Code/Key
    $query .= " AND (p.name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.code LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$query .= " ORDER BY o.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- DESIGN TOKENS --- */
        :root {
            --bg: #f6f3fb;
            --surface: #ffffff;
            --primary-1: #8B5CF6;
            --primary-2: #C084FC;
            --accent: #34D399;
            --danger: #ef4444;
            --muted: #a6a0b8;
            --text: #151522;
            --text-light: #64748b;
            
            --shadow-soft: 0 10px 30px rgba(139,92,246,0.12);
            --shadow-inset: inset 0 4px 12px rgba(139,92,246,0.06);
            --radius-xl: 28px;
            --radius-lg: 18px;
            --radius-md: 12px;
            --space-2: 12px; --space-3: 16px; --space-4: 24px;
            --base-font: 'Inter', sans-serif;
        }

        /* --- GLOBAL RESET --- */
        * { box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body {
            margin: 0; padding: 0;
            font-family: var(--base-font);
            background: linear-gradient(180deg, rgba(139,92,246,0.06), rgba(192,132,252,0.03)), var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex; justify-content: center;
        }

        .phone-frame { width: 100%; max-width: 500px; padding-bottom: 40px; }
        .app-screen { padding: var(--space-3); }

        /* --- HERO & STATS SECTION --- */
        .hero-section {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: var(--space-4);
            margin-bottom: var(--space-4);
            box-shadow: var(--shadow-soft);
            position: relative; overflow: hidden;
        }
        
        /* Top Gradient Line */
        .hero-section::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 6px;
            background: linear-gradient(90deg, var(--primary-1), var(--primary-2));
        }

        .page-title {
            font-size: 24px; font-weight: 700; margin: 0 0 4px 0;
            background: linear-gradient(135deg, var(--text) 0%, #4c1d95 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .page-subtitle { color: var(--muted); font-size: 13px; margin-bottom: 20px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(145deg, #ffffff, #f9f9fc);
            padding: 15px;
            border-radius: var(--radius-lg);
            border: 1px solid rgba(139,92,246,0.1);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
        }
        
        .val-purple { color: var(--primary-1); }
        .val-red { color: var(--danger); }

        /* --- SEARCH BAR --- */
        .search-container {
            position: relative;
        }

        .search-input {
            width: 100%;
            height: 50px;
            padding: 0 20px 0 50px;
            border-radius: 25px;
            border: none;
            background: #f1f5f9;
            box-shadow: var(--shadow-inset);
            font-size: 15px;
            color: var(--text);
            outline: none;
            transition: all 0.3s;
        }

        .search-input:focus {
            background: #fff;
            box-shadow: 0 0 0 2px rgba(139,92,246,0.2), var(--shadow-soft);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }
        
        .clear-search {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            color: var(--danger); text-decoration: none; font-size: 12px; font-weight: 600;
        }

        /* --- ORDER CARD --- */
        .order-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: var(--space-3);
            margin-bottom: var(--space-3);
            border: 1px solid rgba(255,255,255,0.6);
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .card-meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 11px; color: var(--muted); font-weight: 500; }
        
        .card-content { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }
        
        .product-icon-box {
            width: 50px; height: 50px; border-radius: 12px; background: var(--bg);
            overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        }
        .product-icon-box img { width: 100%; height: 100%; object-fit: cover; }
        .product-icon-placeholder { color: var(--primary-2); font-size: 20px; }

        .info-col { flex: 1; }
        .product-name { font-weight: 700; font-size: 15px; margin-bottom: 3px; }
        .user-details { font-size: 12px; color: var(--text-light); }
        .code-pill {
            display: inline-block; background: rgba(139,92,246,0.08); color: var(--primary-1);
            padding: 3px 8px; border-radius: 6px; font-size: 11px; font-family: monospace; margin-top: 5px;
        }

        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #f1f5f9; }
        
        .price { font-size: 16px; font-weight: 700; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .st-active { background: #dcfce7; color: #16a34a; }
        .st-cancel { background: #fee2e2; color: #ef4444; }

        /* Buttons */
        .actions { display: flex; gap: 8px; }
        .btn-icon {
            width: 34px; height: 34px; border-radius: 50%; border: none;
            display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;
        }
        .btn-refund { background: linear-gradient(135deg, #f87171, #ef4444); color: white; box-shadow: 0 4px 10px rgba(239,68,68,0.3); }
        .btn-delete { background: #fff; border: 1px solid #e2e8f0; color: #94a3b8; }
        .btn-delete:hover { border-color: var(--danger); color: var(--danger); }
        .btn-icon:active { transform: scale(0.92); }

    </style>
</head>
<body>

<div class="phone-frame">
    <div class="app-screen">
        
        <div class="hero-section">
            <h1 class="page-title">Digital Orders</h1>
            <p class="page-subtitle">Manage licenses & transactions</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value val-purple"><?= number_format($total_orders_count) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Refunded</div>
                    <div class="stat-value val-red">Rs <?= number_format($total_refunded_amount) ?></div>
                </div>
            </div>

            <form method="GET" class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" 
                       placeholder="Search email, name or code..." 
                       value="<?= htmlspecialchars($search) ?>">
                <?php if(!empty($search)): ?>
                    <a href="?" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="orders-list">
            
            <?php foreach($orders as $o): ?>
            <div class="order-card">
                
                <div class="card-meta">
                    <span>ID: #<?= $o['id'] ?></span>
                    <span><?= date('d M, Y', strtotime($o['created_at'])) ?></span>
                </div>

                <div class="card-content">
                    <div class="product-icon-box">
                        <?php if(!empty($o['icon'])): ?>
                            <img src="../assets/img/<?= $o['icon'] ?>">
                        <?php else: ?>
                            <i class="fas fa-cube product-icon-placeholder"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-col">
                        <div class="product-name"><?= htmlspecialchars($o['product_name']) ?></div>
                        <div class="user-details">
                            <?= htmlspecialchars($o['user_name']) ?> <br>
                            <span style="color:var(--muted);"><?= htmlspecialchars($o['email']) ?></span>
                        </div>
                        <?php if(!empty($o['code'])): ?>
                            <div class="code-pill">Key: <?= $o['code'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-footer">
                    <div>
                        <div class="price">Rs <?= number_format($o['total_price']) ?></div>
                        <?php if($o['status'] == 'completed'): ?>
                            <span class="status-badge st-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge st-cancel">Cancelled</span>
                        <?php endif; ?>
                    </div>

                    <div class="actions">
                        <?php if($o['status'] == 'completed'): ?>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Refund Rs <?= $o['total_price'] ?> to wallet?');">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="refund_order" class="btn-icon btn-refund" title="Refund">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete permanently?');">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <button type="submit" name="delete_order" class="btn-icon btn-delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if(empty($orders)): ?>
                <div style="text-align:center; padding:40px; color:var(--muted);">
                    <i class="fas fa-search" style="font-size:32px; margin-bottom:10px; opacity:0.3;"></i>
                    <p>No orders found.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '_footer.php'; ?>
</body>
</html>