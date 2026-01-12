<?php
include '_header.php';

// --- FIX: Check if user is logged in to prevent "Undefined index: user_id" error ---
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='../login.php';</script>";
    exit;
}

// --- PHP LOGIC (Subscriptions Only) ---
try {
    // p.is_digital = 0 matlab sirf subscriptions dikhayega
    $stmt = $db->prepare("
        SELECT o.*, p.name as product_name, p.icon as product_icon
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.user_id = ? AND p.is_digital = 0
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* --- 📱 ULTRA RESPONSIVE THEME --- */
:root {
    --primary: #007AFF;       /* Apple Blue */
    --bg-body: #F5F5F7;       /* iOS Light Grey */
    --card-bg: #FFFFFF;
    --text-main: #1D1D1F;
    --text-sub: #86868B;
    --border: rgba(0,0,0,0.05);
    --shadow-card: 0 10px 30px -10px rgba(0,0,0,0.1);
    --shadow-hover: 0 20px 40px -10px rgba(0,0,0,0.15);
    --radius: 20px;
}

/* --- BODY FIX --- */
body {
    background-color: var(--bg-body);
    font-family: 'Outfit', sans-serif;
    color: var(--text-main);
    margin: 0; 
    padding-top: 100px !important; /* Heading visibility fix */
    padding-bottom: 80px;
}

/* --- HEADER --- */
.page-header {
    max-width: 1100px; margin: 0 auto; padding: 0 20px 30px 20px;
    animation: fadeDown 0.6s ease-out;
}
.page-title {
    font-size: clamp(24px, 5vw, 32px); 
    font-weight: 800; color: var(--text-main); margin: 0;
    letter-spacing: -0.5px;
}
.page-subtitle { font-size: 14px; color: var(--text-sub); margin-top: 5px; }

/* --- RESPONSIVE GRID --- */
.orders-container {
    max-width: 1100px; margin: 0 auto; padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 25px;
}

/* --- ORDER CARD --- */
.order-card {
    background: var(--card-bg); border-radius: var(--radius);
    box-shadow: var(--shadow-card); border: 1px solid var(--border);
    overflow: hidden; position: relative;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    animation: zoomIn 0.5s ease-out forwards;
    opacity: 0; transform: scale(0.95);
}
.order-card:nth-child(1) { animation-delay: 0.1s; }
.order-card:nth-child(2) { animation-delay: 0.2s; }
.order-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }

/* Card Head */
.card-head {
    padding: 20px; border-bottom: 1px solid #F5F5F7;
    display: flex; justify-content: space-between; align-items: center;
}
.order-id { 
    font-weight: 700; color: var(--text-sub); font-size: 0.85rem; 
    background: #F5F5F7; padding: 4px 10px; border-radius: 8px;
}
.status-badge {
    padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 5px;
}
.st-active { background: #E1FCEF; color: #14803D; }
.st-active::before { content:'●'; color:#14803D; font-size:10px; }
.st-pending { background: #FFF4CE; color: #A16207; }
.st-expired { background: #FEE2E2; color: #B91C1C; }

/* Card Body */
.card-body { padding: 25px; }

.prod-flex { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
.prod-icon { 
    width: 60px; height: 60px; border-radius: 16px; object-fit: cover; 
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #eee;
    background: #f9fafb;
}
.prod-info h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); line-height: 1.3; }
.prod-info span { font-size: 0.85rem; color: var(--text-sub); display: block; margin-top: 4px; }

/* Info Stats */
.stats-row { display: flex; gap: 10px; margin-bottom: 20px; }
.stat-box {
    flex: 1; background: #F9FAFB; border-radius: 12px; padding: 12px; text-align: center;
    border: 1px solid #F3F4F6; transition: 0.2s;
}
.stat-box:hover { background: #fff; border-color: var(--primary); }
.stat-label { font-size: 0.7rem; font-weight: 700; color: var(--text-sub); text-transform: uppercase; display: block; margin-bottom: 5px; }
.stat-val { font-size: 0.95rem; font-weight: 700; color: var(--text-main); }
.price-val { color: var(--primary); }

/* --- NEW WHITE CREDENTIALS BOX UI --- */
.creds-area {
    background: #ffffff; /* White Background */
    color: #334155; /* Dark Text */
    padding: 0; border-radius: 16px; margin-top: 15px; overflow: hidden;
    max-height: 0; transition: max-height 0.4s ease-out, padding 0.4s ease;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
}
.creds-area.open { max-height: 600px; padding: 20px; }

.cred-row { 
    display: flex; align-items: center; gap: 15px; background: #f8fafc; 
    padding: 12px 15px; border-radius: 12px; border: 1px solid #f1f5f9;
    margin-bottom: 12px; transition: 0.2s;
}
.cred-row:hover { border-color: #cbd5e1; background: #fff; }

.cred-icon-box {
    width: 35px; height: 35px; border-radius: 10px; background: #e0e7ff; 
    color: var(--primary); display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
/* Icon Animation */
.animate-icon { animation: pulse 2s infinite; }
@keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }

.cred-details { flex: 1; min-width: 0; }
.cred-label { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px; }
.cred-value { 
    font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 0.95rem; 
    color: #1e293b; background: transparent; border: none; width: 100%; outline: none;
}

.copy-btn {
    width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0;
    color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: 0.2s;
}
.copy-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }

/* Admin Note Box */
.admin-note-box {
    background: #fff7ed; border: 1px dashed #fdba74; border-radius: 12px;
    padding: 15px; margin-top: 15px; display: flex; gap: 12px; align-items: flex-start;
}
.note-icon { color: #f97316; font-size: 1.2rem; margin-top: 2px; }
.note-content { flex: 1; }
.note-title { font-size: 0.8rem; font-weight: 700; color: #9a3412; text-transform: uppercase; margin-bottom: 4px; display: block; }
.note-text { font-size: 0.9rem; color: #7c2d12; line-height: 1.5; }

/* Toggle Button */
.btn-view {
    width: 100%; padding: 14px; background: #F5F5F7; color: var(--text-main);
    border: none; border-radius: 14px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: 0.2s; font-size: 0.9rem;
}
.btn-view:hover { background: #E5E5EA; }
.btn-view.active { background: var(--primary); color: #fff; }
.btn-arrow { transition: transform 0.3s; }
.btn-view.active .btn-arrow { transform: rotate(180deg); }

/* Empty State */
.empty-box {
    grid-column: 1 / -1; text-align: center; padding: 80px 20px;
    background: #fff; border-radius: 24px; border: 2px dashed #E5E5EA;
}

@keyframes fadeDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
@keyframes zoomIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }

@media (max-width: 480px) {
    .orders-container { grid-template-columns: 1fr; }
    .page-title { font-size: 24px; }
}
</style>

<div class="page-header">
    <h1 class="page-title">My Subscriptions</h1>
    <p class="page-subtitle">Manage your active accounts & services.</p>
</div>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <div class="empty-box">
            <div style="font-size: 50px; margin-bottom: 15px;">📭</div>
            <h3 style="margin:0; color:#111;">No Subscriptions Found</h3>
            <p style="color:#888; margin-bottom:25px; margin-top:5px;">You haven't purchased any subscriptions yet.</p>
            <a href="index.php" style="background:var(--primary); color:#fff; padding:12px 30px; border-radius:50px; text-decoration:none; font-weight:600; display:inline-block;">Browse Shop</a>
        </div>
    <?php else: ?>
        
        <?php foreach ($orders as $order): 
            $statusClass = 'st-pending';
            $statusText = ucfirst($order['status']);
            if($order['status']=='completed') { $statusClass = 'st-active'; $statusText = 'Active'; }
            if($order['status']=='cancelled') { $statusClass = 'st-expired'; $statusText = 'Cancelled'; }
        ?>
            <div class="order-card">
                
                <div class="card-head">
                    <span class="order-id">#<?php echo $order['code']; ?></span>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="prod-flex">
                        <img src="../assets/img/icons/<?php echo sanitize($order['product_icon']); ?>" class="prod-icon" onerror="this.src='../assets/img/default.png'">
                        <div class="prod-info">
                            <h3><?php echo sanitize($order['product_name']); ?></h3>
                            <span>Purchased: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="stats-row">
                        <div class="stat-box">
                            <span class="stat-label">Cost</span>
                            <span class="stat-val price-val"><?php echo formatCurrency($order['total_price']); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label">Expires In</span>
                            <span class="stat-val">
                                <?php if ($order['status'] == 'completed' && !empty($order['end_at'])): ?>
                                    <span class="countdown" data-end-at="<?php echo $order['end_at']; ?>">Calculating...</span>
                                <?php else: ?>
                                    --
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($order['service_username'])): ?>
                        <button class="btn-view" onclick="toggleCreds(this, 'creds-<?php echo $order['id']; ?>')">
                            <span>View Credentials</span>
                            <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                        </button>

                        <div id="creds-<?php echo $order['id']; ?>" class="creds-area">
                            
                            <div class="cred-row">
                                <div class="cred-icon-box animate-icon"><i class="fa-regular fa-user"></i></div>
                                <div class="cred-details">
                                    <span class="cred-label">Username / Email</span>
                                    <input type="text" class="cred-value" value="<?php echo sanitize($order['service_username']); ?>" readonly id="u-<?php echo $order['id']; ?>">
                                </div>
                                <button class="copy-btn" onclick="copyText('u-<?php echo $order['id']; ?>')">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>

                            <?php if (!empty($order['service_password'])): ?>
                            <div class="cred-row">
                                <div class="cred-icon-box animate-icon"><i class="fa-solid fa-key"></i></div>
                                <div class="cred-details">
                                    <span class="cred-label">Password / PIN</span>
                                    <input type="text" class="cred-value" value="<?php echo sanitize($order['service_password']); ?>" readonly id="p-<?php echo $order['id']; ?>">
                                </div>
                                <button class="copy-btn" onclick="copyText('p-<?php echo $order['id']; ?>')">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($order['account_details'])): ?>
                                <div class="admin-note-box">
                                    <div class="note-icon animate-icon"><i class="fa-solid fa-circle-info"></i></div>
                                    <div class="note-content">
                                        <span class="note-title">Instructions & Details</span>
                                        <div class="note-text"><?php echo nl2br(sanitize($order['account_details'])); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php else: ?>
                        <div class="btn-view" style="background:#FFF7ED; color:#C2410C; cursor:default;">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> &nbsp; Processing Credentials...
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<script src="../assets/js/countdown.js"></script>
<script>
function toggleCreds(btn, id) {
    const box = document.getElementById(id);
    if (box.classList.contains('open')) {
        box.classList.remove('open');
        btn.classList.remove('active');
        btn.querySelector('span').innerText = 'View Credentials';
    } else {
        box.classList.add('open');
        btn.classList.add('active');
        btn.querySelector('span').innerText = 'Hide Credentials';
    }
}

function copyText(id) {
    const el = document.getElementById(id);
    el.select();
    document.execCommand('copy');
    
    // Visual Feedback (Button color change)
    const btn = el.parentElement.parentElement.querySelector('.copy-btn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    btn.style.color = '#10b981';
    btn.style.borderColor = '#10b981';
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.style.color = '';
        btn.style.borderColor = '';
    }, 1500);
}
</script>

<?php include '_footer.php'; ?>