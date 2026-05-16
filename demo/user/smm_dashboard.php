<?php
// Naye SMM Header aur Footer files istemal karein
include '_smm_header.php'; 

$error = '';
$user_id = (int)$_SESSION['user_id']; 

// --- 1. DASHBOARD STATS LOGIC (ORIGINAL + NEW FEATURES) ---
try {
    // A. Original Logic: Total Spend & Total Orders
    $stmt_stats = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(charge) as total_spend,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as progress_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count
        FROM smm_orders 
        WHERE user_id = ?
    ");
    $stmt_stats->execute([$user_id]);
    $stats = $stmt_stats->fetch();

    // Variables assign karein (Agar null ho to 0)
    $smm_total_orders = $stats['total_orders'] ?? 0;
    $smm_total_spend = $stats['total_spend'] ?? 0;
    $smm_pending = $stats['pending_count'] ?? 0;
    $smm_in_progress = $stats['progress_count'] ?? 0;
    $smm_completed = $stats['completed_count'] ?? 0;
    $smm_cancelled = $stats['cancelled_count'] ?? 0;
    $smm_partial = $stats['partial_count'] ?? 0;

    // B. New Feature: Recent 5 Orders
    $stmt_recent = $db->prepare("
        SELECT o.id, o.service_id, o.link, o.quantity, o.charge, o.status, o.created_at, s.name as service_name
        FROM smm_orders o
        LEFT JOIN smm_services s ON o.service_id = s.id
        WHERE o.user_id = ?
        ORDER BY o.id DESC 
        LIMIT 5
    ");
    $stmt_recent->execute([$user_id]);
    $recent_orders = $stmt_recent->fetchAll();

    // C. Original Logic: Graph Data (Last 7 Days)
    $stmt_graph = $db->prepare("
        SELECT 
            DATE(created_at) as order_date, 
            SUM(charge) as total_spend
        FROM smm_orders
        WHERE user_id = ? AND created_at >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(created_at)
        ORDER BY order_date ASC
    ");
    $stmt_graph->execute([$user_id]);
    $graph_data = $stmt_graph->fetchAll();
    
    // Graph Arrays Prepare
    $graph_labels = [];
    $graph_values = [];
    $dates = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $display_date = date('D, j M', strtotime($date));
        $dates[$date] = 0;
        $graph_labels[] = $display_date;
    }
    
    foreach ($graph_data as $data) {
        if (isset($dates[$data['order_date']])) {
            $dates[$data['order_date']] = (float)$data['total_spend'];
        }
    }
    $graph_values = array_values($dates);

    // D. New Feature: Refill Monitor (Point #4)
    // Count completed orders that have refill enabled in service settings
    $stmt_refill = $db->prepare("
        SELECT COUNT(o.id) 
        FROM smm_orders o 
        JOIN smm_services s ON o.service_id = s.id 
        WHERE o.user_id = ? 
        AND o.status = 'completed' 
        AND s.has_refill = 1
    ");
    $stmt_refill->execute([$user_id]);
    $refill_available_count = $stmt_refill->fetchColumn();

} catch (PDOException $e) {
    $error = "Failed to load stats: " . $e->getMessage();
    $smm_total_spend = $smm_total_orders = $smm_in_progress = $smm_completed = 0;
    $smm_pending = $smm_cancelled = $smm_partial = 0;
    $graph_labels = [];
    $graph_values = [];
    $recent_orders = [];
    $refill_available_count = 0;
}
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
/* 1) Design Tokens (CSS Variables) */
:root {
  --bg: #f6f3fb;
  --surface: #ffffff;
  
  /* Primary Colors */
  --primary-1: #8B5CF6;   /* deep purple */
  --primary-2: #C084FC;   /* pink-purple */
  --primary-solid: #7a3be8;
  
  /* Accents */
  --accent-green: #34D399;
  --accent-blue: #60A5FA;
  --accent-orange: #FBBF24;
  --accent-red: #F87171;
  
  --muted: #a6a0b8;
  --text: #151522;
  
  /* Shadows */
  --shadow-soft: 0 10px 30px rgba(139,92,246,0.12);
  --shadow-inset: inset 0 6px 18px rgba(139,92,246,0.06);
  --shadow-icon: 0 6px 20px rgba(20,17,34,0.06);
  
  /* Glassmorphism */
  --glass-bg: rgba(255,255,255,0.65);
  --glass-border: rgba(255,255,255,0.45);
  
  /* Spacing & Radius */
  --radius-xl: 28px;
  --radius-lg: 18px;
  --radius-md: 12px;
  --radius-sm: 8px;
  
  --space-1: 8px; --space-2: 12px; --space-3: 16px; --space-4: 24px;
  
  --base-font: 'Outfit', 'Inter', sans-serif;
}

/* 2) Layout & Typography */
body {
    background-color: var(--bg);
    background-image: 
        radial-gradient(at 0% 0%, rgba(139,92,246,0.05) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(192,132,252,0.05) 0px, transparent 50%);
    font-family: var(--base-font);
    color: var(--text);
    margin: 0; padding: 0;
    -webkit-font-smoothing: antialiased;
}

.app-screen {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 80px;
}

h3.h-md { font-size: 20px; font-weight: 700; color: var(--text); letter-spacing: -0.02em; margin-bottom: var(--space-2); }

/* 3) Component: Storage/Wallet Card */
.card-storage {
    background: var(--surface);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-soft);
    padding: var(--space-4);
    position: relative;
    overflow: hidden;
    margin-bottom: var(--space-4);
    border: 1px solid var(--glass-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: var(--space-3);
}

/* Gradient Top Highlight */
.card-storage::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0.8), rgba(255,255,255,0));
    opacity: 0.6; pointer-events: none;
}

.balance-label { font-size: 14px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); font-weight: 600; }
.balance-value { 
    font-size: 42px; font-weight: 800; 
    background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    margin: 5px 0;
}
.spend-info { font-size: 14px; color: var(--muted); background: rgba(139,92,246,0.05); padding: 6px 12px; border-radius: 20px; }

/* 4) Floating CTA Button */
.floating-cta {
    background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 100%);
    color: white; font-weight: 600; padding: 14px 32px; border-radius: 28px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 10px;
    box-shadow: 0 10px 20px rgba(139,92,246,0.3); border: 1px solid rgba(255,255,255,0.2);
    transition: transform 0.2s; position: relative; z-index: 2;
}
.floating-cta:active { transform: scale(0.96); }
.floating-cta i { font-size: 18px; }

/* 5) News Ticker (Soft Glass) */
.news-ticker {
    background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.1);
    border-radius: var(--radius-md); padding: 10px; margin-bottom: var(--space-4);
    overflow: hidden; white-space: nowrap; color: var(--primary-solid); font-weight: 600; font-size: 14px;
}
.ticker-text { display: inline-block; animation: scrollTicker 20s linear infinite; }
@keyframes scrollTicker { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }

/* 6) Refill Monitor Widget (New) */
.refill-monitor {
    background: linear-gradient(135deg, #FFF7ED 0%, #FFF 100%);
    border: 1px solid #FFEDD5;
    border-radius: var(--radius-lg);
    padding: 15px;
    margin-bottom: var(--space-4);
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.08);
}
.rm-left { display: flex; align-items: center; gap: 12px; }
.rm-icon {
    width: 40px; height: 40px; border-radius: 50%;
    background: #FFF7ED; color: #F97316;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
.rm-text { display: flex; flex-direction: column; }
.rm-text strong { font-size: 16px; color: #9A3412; }
.rm-text span { font-size: 12px; color: #C2410C; }
.rm-btn {
    background: #F97316; color: white; padding: 8px 16px;
    border-radius: 20px; font-size: 12px; font-weight: 700;
    text-decoration: none; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.2);
}

/* 7) Quick Actions (Scrollable) */
.quick-actions {
    display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: 5px; margin-bottom: var(--space-4);
    scrollbar-width: none;
}
.quick-actions::-webkit-scrollbar { display: none; }

.action-pill {
    background: var(--surface); padding: 12px 20px; border-radius: 50px;
    display: flex; align-items: center; gap: 8px; color: var(--text);
    text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid rgba(139,92,246,0.1);
    transition: transform 0.2s;
}
.action-pill i { color: var(--primary-1); }
.action-pill:active { transform: scale(0.95); background: var(--bg); }

/* 8) Stats Grid (2x3 or 4x2) */
.stats-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-3); margin-bottom: var(--space-4);
}
@media(min-width: 768px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }

.tile {
    background: var(--surface); border-radius: var(--radius-lg); padding: 20px;
    box-shadow: var(--shadow-soft); position: relative; overflow: hidden;
    display: flex; flex-direction: column; justify-content: space-between; height: 140px;
    border: 1px solid rgba(255,255,255,0.5); transition: transform 0.2s;
}
.tile:hover { transform: translateY(-3px); }

/* 3D Icon Container */
.icon-holder {
    width: 44px; height: 44px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 18px; box-shadow: var(--shadow-icon);
    margin-bottom: 10px;
}

/* Stat Tile Colors */
.t-purple .icon-holder { background: linear-gradient(135deg, #C084FC, #8B5CF6); }
.t-blue .icon-holder { background: linear-gradient(135deg, #60A5FA, #3B82F6); }
.t-green .icon-holder { background: linear-gradient(135deg, #34D399, #10B981); }
.t-orange .icon-holder { background: linear-gradient(135deg, #FBBF24, #F59E0B); }
.t-red .icon-holder { background: linear-gradient(135deg, #F87171, #EF4444); }
.t-cyan .icon-holder { background: linear-gradient(135deg, #22d3ee, #06b6d4); }

.tile h4 { font-size: 13px; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
.tile span { font-size: 24px; font-weight: 800; color: var(--text); }

/* 9) Recent Orders Table (Glass Card) */
.glass-card {
    background: var(--surface); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft); padding: var(--space-4);
    margin-bottom: var(--space-4); border: 1px solid var(--glass-border);
}

.table-responsive { width: 100%; overflow-x: auto; }
.ro-table { width: 100%; border-collapse: collapse; min-width: 500px; }
.ro-table th { text-align: left; color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }
.ro-table td { padding: 15px 0; color: var(--text); font-size: 14px; font-weight: 500; border-bottom: 1px solid #f9f9f9; }
.ro-table tr:last-child td { border-bottom: none; }

/* Status Badges */
.badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.b-pending { background: #FFF7ED; color: #C2410C; }
.b-processing, .b-in_progress { background: #EFF6FF; color: #2563EB; }
.b-completed { background: #ECFDF5; color: #059669; }
.b-cancelled { background: #FEF2F2; color: #DC2626; }
.b-partial { background: #F5F3FF; color: #7C3AED; }

/* Animation */
.fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
.d-1 { animation-delay: 0.1s; }
.d-2 { animation-delay: 0.2s; }
.d-3 { animation-delay: 0.3s; }

@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>

<div class="app-screen">

    <?php if ($error): ?>
        <div class="news-ticker" style="background:#FEF2F2; color:#DC2626; border-color:#FECACA;">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card-storage fade-in-up">
        <div class="hero-content">
            <div>
                <div class="balance-label">Available Balance</div>
                <div class="balance-value"><?php echo formatCurrency($user_balance); ?></div>
                <span class="spend-info">Total Spent: <?php echo formatCurrency($smm_total_spend); ?></span>
            </div>
            
            <a href="add-funds.php" class="floating-cta">
                <i class="fas fa-wallet"></i> <span>Add Funds</span>
            </a>
        </div>
    </div>

    <div class="news-ticker fade-in-up d-1">
        <div class="ticker-text">
            🔥 New TikTok Services Added! &nbsp;&nbsp; • &nbsp;&nbsp; 🚀 Instagram Followers price dropped to $0.001 &nbsp;&nbsp; • &nbsp;&nbsp; ⚡ YouTube Views are now Instant! &nbsp;&nbsp; • &nbsp;&nbsp; 💎 Join our WhatsApp Channel for Updates!
        </div>
    </div>

    <div class="refill-monitor fade-in-up d-1">
        <div class="rm-left">
            <div class="rm-icon">
                <i class="fas fa-sync-alt fa-spin" style="--fa-animation-duration: 3s;"></i>
            </div>
            <div class="rm-text">
                <strong>Refill Monitor</strong>
                <span><?php echo $refill_available_count; ?> Orders available for refill</span>
            </div>
        </div>
        <a href="smm_history.php?filter=refill" class="rm-btn">Check Now</a>
    </div>

    <div class="quick-actions fade-in-up d-1">
        <a href="smm_order.php" class="action-pill"><i class="fas fa-plus-circle"></i> New Order</a>
        <a href="mass_order.php" class="action-pill"><i class="fas fa-layer-group"></i> Mass Order</a>
        <a href="services.php" class="action-pill"><i class="fas fa-list"></i> Services</a>
        <a href="tickets.php" class="action-pill"><i class="fas fa-headset"></i> Support</a>
    </div>

    <h3 class="h-md fade-in-up d-2">Overview</h3>
    <div class="stats-grid fade-in-up d-2">
        
        <div class="tile t-purple">
            <div class="icon-holder"><i class="fas fa-box"></i></div>
            <div>
                <h4>Total Orders</h4>
                <span><?php echo number_format($smm_total_orders); ?></span>
            </div>
        </div>

        <div class="tile t-orange">
            <div class="icon-holder"><i class="fas fa-clock"></i></div>
            <div>
                <h4>Pending</h4>
                <span><?php echo number_format($smm_pending); ?></span>
            </div>
        </div>

        <div class="tile t-blue">
            <div class="icon-holder"><i class="fas fa-spinner fa-spin"></i></div>
            <div>
                <h4>In Progress</h4>
                <span><?php echo number_format($smm_in_progress); ?></span>
            </div>
        </div>

        <div class="tile t-green">
            <div class="icon-holder"><i class="fas fa-check-double"></i></div>
            <div>
                <h4>Completed</h4>
                <span><?php echo number_format($smm_completed); ?></span>
            </div>
        </div>

        <div class="tile t-red">
            <div class="icon-holder"><i class="fas fa-ban"></i></div>
            <div>
                <h4>Cancelled</h4>
                <span><?php echo number_format($smm_cancelled); ?></span>
            </div>
        </div>

        <div class="tile t-cyan">
            <div class="icon-holder"><i class="fas fa-adjust"></i></div>
            <div>
                <h4>Partial</h4>
                <span><?php echo number_format($smm_partial); ?></span>
            </div>
        </div>

    </div>

    <div class="glass-card fade-in-up d-3">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 class="h-md" style="margin:0;">Recent Orders</h3>
            <a href="smm_history.php" style="color:var(--primary-1); font-weight:600; font-size:13px; text-decoration:none;">View All</a>
        </div>
        
        <div class="table-responsive">
            <table class="ro-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Charge</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <div style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($order['service_name'] ?? 'Unknown Service'); ?>
                                </div>
                            </td>
                            <td style="font-weight:700;"><?php echo formatCurrency($order['charge']); ?></td>
                            <td>
                                <span class="badge b-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--muted);">No recent orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card fade-in-up d-3">
        <h3 class="h-md">Spending Analytics</h3>
        <div style="position: relative; height: 250px; width:100%;">
            <canvas id="smm-spending-chart"></canvas>
        </div>
    </div>

</div>

<script>
    window.smmGraphLabels = <?php echo json_encode($graph_labels); ?>;
    window.smmGraphValues = <?php echo json_encode($graph_values); ?>;
    
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById('smm-spending-chart');
        if(canvas) { canvas.style.maxHeight = "250px"; }
    });
</script>

<?php include '_smm_footer.php'; ?>