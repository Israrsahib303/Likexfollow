<?php
include '_header.php';

$user_id = $_SESSION['user_id'];

// --- STATS FETCHING (FIXED) ---
// 1. Total Spent (Sirf Tools/Subscriptions ke liye)
$stmt_spent = $db->prepare("
    SELECT SUM(o.total_price) as total_spent 
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ? 
    AND (o.status = 'completed' OR o.status = 'expired')
    AND (p.is_digital = 0 OR p.is_digital IS NULL)
");
$stmt_spent->execute([$user_id]);
$total_spent = $stmt_spent->fetchColumn() ?? 0;

// 2. Total Orders (Sirf Tools/Subscriptions ke liye)
$stmt_orders = $db->prepare("
    SELECT COUNT(o.id) as total_orders 
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ?
    AND (p.is_digital = 0 OR p.is_digital IS NULL)
");
$stmt_orders->execute([$user_id]);
$total_orders = $stmt_orders->fetchColumn() ?? 0;

// 3. Active Subscriptions (Sirf Tools/Subscriptions ke liye)
$stmt_active = $db->prepare("
    SELECT COUNT(o.id) as total_active 
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ? 
    AND o.status = 'completed'
    AND (p.is_digital = 0 OR p.is_digital IS NULL)
");
$stmt_active->execute([$user_id]);
$total_active = $stmt_active->fetchColumn() ?? 0;

// --- FILTER LOGIC ---
$category_filter = $_GET['category_id'] ?? 'all';
$sql_where = "";
if ($category_filter != 'all' && is_numeric($category_filter)) {
    $sql_where = " AND p.category_id = " . (int)$category_filter;
}

$stmt_cats = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt_cats->fetchAll();// // 1. Fetch ALL active products first
$stmt_prods = $db->query("SELECT p.* FROM products p WHERE p.is_active = 1 $sql_where ORDER BY p.name ASC");
$all_products = $stmt_prods->fetchAll();

// 2. Filter: Remove 'Digital Downloads' (Keep only if is_digital is NOT 1)
$products = array_filter($all_products, function($p) {
    // Show if is_digital is 0, NULL, or empty. Only hide if it's strictly 1.
    return !isset($p['is_digital']) || $p['is_digital'] != 1;
});
?>

<!-- Modern Fonts & Tools -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>

<style>
/* --- 🎨 FORCED ZOMO/BLOGIE THEME OVERRIDES --- */
body {
    background: linear-gradient(135deg, #f5f3ff 0%, #e0e7ff 100%) !important;
    font-family: 'Outfit', sans-serif !important;
    color: #1e1b4b !important;
    min-height: 100vh;
}

/* Fix Header Background to blend with glass theme */
header, .navbar, .top-bar {
    background: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(12px) !important;
    border-bottom: 1px solid rgba(255,255,255,0.5) !important;
    box-shadow: none !important;
}

/* --- 💳 1. 3D GLASS WALLET CARD --- */
.dashboard-wrapper {
    padding: 20px 0;
    position: relative;
    z-index: 1;
}

.glass-wallet {
    background: linear-gradient(120deg, #7c3aed 0%, #4f46e5 100%);
    border-radius: 32px;
    padding: 30px;
    color: white;
    box-shadow: 0 25px 50px -12px rgba(124, 58, 237, 0.5);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.2);
    margin-bottom: 40px;
}

/* Abstract Background Shapes */
.glass-wallet::before {
    content: ''; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.glass-wallet::after {
    content: ''; position: absolute; bottom: -50px; left: -50px; width: 150px; height: 150px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%; pointer-events: none;
}

.gw-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 30px; position: relative; z-index: 2;
}

.gw-balance-label {
    font-size: 14px; font-weight: 500; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;
}
.gw-balance-value {
    font-size: 42px; font-weight: 800; letter-spacing: -1.5px; line-height: 1.1;
    text-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.gw-topup-btn {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.4);
    padding: 12px 24px; border-radius: 50px;
    color: white; font-weight: 700; text-decoration: none;
    backdrop-filter: blur(10px); transition: 0.3s;
    display: flex; align-items: center; gap: 8px;
}
.gw-topup-btn:hover { background: white; color: #7c3aed; transform: translateY(-3px); }

/* Internal Stats Bar (Frosted) */
.gw-stats-bar {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 24px;
    padding: 15px;
    display: flex; justify-content: space-between;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255,255,255,0.1);
}
.gw-stat { text-align: center; flex: 1; border-right: 1px solid rgba(255,255,255,0.15); }
.gw-stat:last-child { border-right: none; }
.gw-stat-val { display: block; font-size: 18px; font-weight: 700; }
.gw-stat-lbl { font-size: 11px; opacity: 0.8; text-transform: uppercase; }


/* --- 🔍 2. SEARCH & FILTER (PILL SHAPES) --- */
.controls-container {
    display: flex; gap: 15px; margin-bottom: 40px; flex-wrap: wrap;
}

.glass-search {
    flex: 2;
    background: #fff; padding: 8px 8px 8px 25px;
    border-radius: 50px;
    display: flex; align-items: center;
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
    border: 2px solid white;
    min-width: 280px;
}
.gs-input { flex: 1; border: none; outline: none; font-size: 15px; background: transparent; }
.gs-btn {
    background: #1e1b4b; color: white; border: none; padding: 12px 25px;
    border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.3s;
}
.gs-btn:hover { background: #4338ca; transform: scale(1.05); }

.glass-select-wrapper {
    flex: 1; min-width: 200px; position: relative;
}
.glass-select {
    width: 100%; padding: 18px 25px;
    border-radius: 50px; border: 2px solid white;
    background: #fff; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
    font-size: 15px; color: #1e1b4b; cursor: pointer; appearance: none;
    font-weight: 500; outline: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%234338ca'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3e%3c/path%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 20px center; background-size: 16px;
}

/* --- 📦 3. PRODUCT CARDS (FLOATING) --- */
.products-grid {
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); 
    gap: 30px;
}

.float-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-radius: 30px;
    border: 2px solid white;
    overflow: hidden;
    position: relative;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: 0 10px 20px -5px rgba(0,0,0,0.03);
    display: flex; flex-direction: column;
}

.float-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 50px -12px rgba(124, 58, 237, 0.2);
    border-color: #c4b5fd;
    z-index: 5;
}

.fc-header {
    height: 140px;
    position: relative;
    display: flex; justify-content: center; align-items: center;
    background: radial-gradient(circle, #fff, #f5f3ff);
}
.fc-icon {
    width: 80px; height: 80px; object-fit: contain;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
    transition: 0.5s; z-index: 2;
}
.float-card:hover .fc-icon { transform: scale(1.2) rotate(5deg); }

/* Info Button */
.fc-info-btn {
    position: absolute; top: 15px; right: 15px;
    width: 40px; height: 40px; border-radius: 50%;
    background: white; border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    display: flex; justify-content: center; align-items: center;
    cursor: pointer; color: #64748b; z-index: 5; transition: 0.3s;
}
.fc-info-btn:hover { background: #7c3aed; color: white; transform: rotate(90deg); }

.fc-body { padding: 25px; flex: 1; display: flex; flex-direction: column; }
.fc-title { font-size: 18px; font-weight: 700; margin-bottom: 10px; color: #1e1b4b; }

.fc-desc-box { 
    height: 45px; overflow: hidden; font-size: 13px; color: #64748b; margin-bottom: 20px; 
    line-height: 1.6;
}

.fc-footer {
    margin-top: auto; display: flex; justify-content: space-between; align-items: center;
    padding-top: 15px; border-top: 1px dashed #e2e8f0;
}
.fc-price-lbl { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.fc-price { font-size: 20px; font-weight: 800; color: #1e1b4b; }

.fc-buy-btn {
    background: #1e1b4b; color: white;
    padding: 10px 25px; border-radius: 50px; text-decoration: none;
    font-weight: 600; font-size: 13px; transition: 0.3s;
}
.float-card:hover .fc-buy-btn { background: #7c3aed; box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4); }


/* --- 🛑 MODALS FIXES --- */
/* Universal Modal Overlay */
.custom-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    z-index: 100000; /* Highest Priority */
    display: none; /* Hidden by default */
    justify-content: center; align-items: center;
    padding: 20px;
}
.custom-modal-overlay.open { display: flex; animation: fadeIn 0.3s forwards; }

.custom-modal-box {
    background: white; width: 100%; max-width: 500px; max-height: 85vh;
    border-radius: 30px; overflow: hidden;
    box-shadow: 0 50px 100px -20px rgba(0,0,0,0.3);
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
}

@keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
@keyframes slideUp { from{transform:translateY(50px);} to{transform:translateY(0);} }

.cm-header {
    padding: 25px; background: white; border-bottom: 1px solid #f1f5f9;
    display: flex; justify-content: space-between; align-items: center;
}
.cm-title { font-size: 20px; font-weight: 800; color: #1e1b4b; margin: 0; }
.cm-close {
    background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%;
    cursor: pointer; font-size: 20px; color: #64748b; display: flex; align-items: center; justify-content: center;
}
.cm-close:hover { background: #ef4444; color: white; }

.cm-body { padding: 30px; overflow-y: auto; font-size: 15px; line-height: 1.7; color: #475569; }

/* Currency Modal Override (If bootstrap fails) */
#currencyModal.forced-open {
    display: flex !important;
    opacity: 1 !important;
    background: rgba(0,0,0,0.5);
    align-items: center; justify-content: center;
    position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 100000;
}
#currencyModal.forced-open .modal-dialog { margin: 0; }
</style>

<div class="dashboard-wrapper">
    
    <!-- 1. 3D GLASS WALLET -->
    <div class="glass-wallet">
        <div class="gw-header">
            <div>
                <div class="gw-balance-label">Total Balance</div>
                <div class="gw-balance-value"><?php echo formatCurrency($user_balance); ?></div>
            </div>
            <a href="add-funds.php" class="gw-topup-btn">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>
                Top Up
            </a>
        </div>
        <div class="gw-stats-bar">
            <div class="gw-stat">
                <span class="gw-stat-val"><?php echo formatCurrency($total_spent); ?></span>
                <span class="gw-stat-lbl">Spent</span>
            </div>
            <div class="gw-stat">
                <span class="gw-stat-val"><?php echo $total_orders; ?></span>
                <span class="gw-stat-lbl">Orders</span>
            </div>
            <div class="gw-stat">
                <span class="gw-stat-val"><?php echo $total_active; ?></span>
                <span class="gw-stat-lbl">Active</span>
            </div>
        </div>
    </div>

    <!-- 2. SEARCH & CONTROLS -->
    <div class="controls-container">
        <div class="glass-search">
            <input type="text" id="tool-req" class="gs-input" placeholder="Search for tools or services...">
            <button id="btn-req" class="gs-btn" data-wa="<?php echo sanitize($GLOBALS['settings']['whatsapp_number'] ?? ''); ?>">Request</button>
        </div>
        <div class="glass-select-wrapper">
            <form action="" method="GET" id="cat-form">
                <select name="category_id" class="glass-select" onchange="document.getElementById('cat-form').submit();">
                    <option value="all" <?php echo ($category_filter == 'all') ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- 3. FLOATING PRODUCT GRID -->
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <div style="grid-column:1/-1; text-align:center; padding:80px; color:#94a3b8;">
                <p style="font-size:18px;">No services found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <?php 
                    $descRaw = $product['description'];
                    if (empty($descRaw) || trim(strip_tags($descRaw)) == '') {
                        $descRaw = "<p>Premium service. Click 'Purchase' to proceed.</p>";
                    }
                    $cleanDesc = strip_tags($descRaw);
                ?>
                <div class="float-card" id="prod-<?php echo $product['id']; ?>">
                    
                    <!-- Info Button triggers JS Modal -->
                    <button class="fc-info-btn" onclick="openDescModal(<?php echo $product['id']; ?>)">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>

                    <!-- Hidden Description Data -->
                    <div id="desc-content-<?php echo $product['id']; ?>" style="display:none;">
                        <?php echo $descRaw; ?>
                    </div>
                    <div id="title-content-<?php echo $product['id']; ?>" style="display:none;">
                        <?php echo sanitize($product['name']); ?>
                    </div>

                    <div class="fc-header">
                        <div class="prod-bg-blur" style="background-image: url('../assets/img/icons/<?php echo sanitize($product['icon']); ?>');"></div>
                        <img src="../assets/img/icons/<?php echo sanitize($product['icon']); ?>" class="fc-icon" alt="icon">
                    </div>

                    <div class="fc-body">
                        <div class="fc-title"><?php echo sanitize($product['name']); ?></div>
                        <div class="fc-desc-box"><?php echo $cleanDesc; ?></div>
                        
                        <div class="fc-footer">
                            <div>
                                <span class="fc-price-lbl">Starting</span>
                                <div class="fc-price">View</div>
                            </div>
                            <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="fc-buy-btn">Purchase</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- 🛑 CUSTOM MODAL (For Product Descriptions) -->
<div class="custom-modal-overlay" id="productDescModal">
    <div class="custom-modal-box">
        <div class="cm-header">
            <h3 class="cm-title" id="cmTitle">Service Details</h3>
            <button class="cm-close" onclick="closeDescModal()">×</button>
        </div>
        <div class="cm-body" id="cmBody">
            <!-- Content -->
        </div>
    </div>
</div>

<script>
// --- 1. PRODUCT DESCRIPTION MODAL LOGIC ---
const descModal = document.getElementById('productDescModal');
const cmTitle = document.getElementById('cmTitle');
const cmBody = document.getElementById('cmBody');

function openDescModal(productId) {
    // Get content from hidden divs using unique ID
    const title = document.getElementById(`title-content-${productId}`).innerText;
    const desc = document.getElementById(`desc-content-${productId}`).innerHTML;

    cmTitle.innerText = title;
    cmBody.innerHTML = desc;

    descModal.classList.add('open');
    document.body.style.overflow = 'hidden'; // Stop scrolling
}

function closeDescModal() {
    descModal.classList.remove('open');
    document.body.style.overflow = 'auto'; // Resume scrolling
}

// Close on outside click
descModal.addEventListener('click', (e) => {
    if(e.target === descModal) closeDescModal();
});


// --- 2. 🔧 CURRENCY MODAL FIX (FORCE OPEN) ---
document.addEventListener('DOMContentLoaded', () => {
    
    // Attempt to find the currency button in header
    // Selectors match common implementations
    const currencyTriggers = document.querySelectorAll('.currency-btn, #currency-selector, [data-target="#currencyModal"], .nav-link i.fa-globe');
    const currencyModal = document.getElementById('currencyModal');

    if(currencyTriggers.length > 0 && currencyModal) {
        console.log("Currency button found. Attaching fix.");
        
        currencyTriggers.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault(); // Stop default bootstrap behavior if failing
                e.stopPropagation();
                
                // Force Open
                currencyModal.classList.add('forced-open');
                currencyModal.classList.add('show');
                currencyModal.style.display = 'flex';
                currencyModal.style.opacity = '1';
            });
        });

        // Force Close Logic for Currency Modal
        currencyModal.addEventListener('click', (e) => {
            if(e.target === currencyModal || e.target.getAttribute('data-dismiss') === 'modal') {
                currencyModal.classList.remove('forced-open');
                currencyModal.classList.remove('show');
                currencyModal.style.display = 'none';
            }
        });
    } else {
        console.log("Currency elements not found in header.");
    }
});


// --- 3. WHATSAPP REQUEST ---
document.getElementById('btn-req').addEventListener('click', function() {
    const text = document.getElementById('tool-req').value;
    const phone = this.getAttribute('data-wa');
    if(text.trim() === '') return;
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent("Request: " + text)}`, '_blank');
});
</script>

<?php include '_footer.php'; ?>