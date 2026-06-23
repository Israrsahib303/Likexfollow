<?php
// ==========================================
// 🚀 MASTER REDIRECT - SMM ONLY MODE 🚀
// ==========================================
// Jo bhi user is page par aayega, seedha SMM Order par redirect ho jayega!
// Iske baad ka koi code run nahi hoga
// ==========================================

include '_header.php';

// Safe check for user_id (Taake header wali file ka masla na bane)
if (!isset($user_id) && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
} elseif (!isset($user_id)) {
    $user_id = 0;
}

try {
    // --- FETCH STATS ---
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ? AND o.status = 'completed' AND p.is_digital = 0");
    $stmt->execute([$user_id]);
    $active_subs = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM smm_orders WHERE user_id = ? AND status IN ('pending','processing','in_progress')");
    $stmt->execute([$user_id]);
    $smm_active = $stmt->fetchColumn();

    $total_spent = $db->prepare("SELECT SUM(amount) FROM wallet_ledger WHERE user_id = ? AND type='debit'");
    $total_spent->execute([$user_id]);
    $total_spent_amount = $total_spent->fetchColumn() ?? 0;
} catch (Exception $e) {
    // Prevent White Page if any stats table is missing
    $active_subs = 0; $smm_active = 0; $total_spent_amount = 0;
}

// Greeting
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");

// --- CRASH-PROOF USER NAME FETCHING ---
$display_name = 'User'; // Ultimate Default
try {
    // Select all taake koi missing column ka error na aaye
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u_row) {
        if (!empty($u_row['name'])) {
            $display_name = $u_row['name'];
        } elseif (!empty($u_row['first_name'])) {
            $display_name = $u_row['first_name'] . ' ' . ($u_row['last_name'] ?? '');
        } elseif (!empty($u_row['username'])) {
            $display_name = $u_row['username'];
        } elseif (!empty($u_row['email'])) {
            // Agar naam na mile toh email ka aagay wala hissa nikal lo (e.g., ali@gmail.com -> Ali)
            $parts = explode('@', $u_row['email']);
            $display_name = ucfirst($parts[0]);
        }
    }
} catch (Exception $e) {
    // Agar DB mein koi bhi masla ho toh page white nahi hoga, bas 'User' show karega
}
$display_name = htmlspecialchars(trim($display_name));
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --lx-primary: #6366f1;
        --lx-primary-dark: #4f46e5;
        --lx-primary-light: #eef2ff;
        --bg-body: #f8fafc;
        --card-bg: #ffffff;
        --text-dark: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    body { font-family: 'Outfit', sans-serif; background: var(--bg-body); overflow-x: hidden; color: var(--text-dark); }
    
    /* --- SKELETON STYLES --- */
    .skeleton {
        background: #e2e8f0;
        background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 16px;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    
    #realContent { display: none; animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* --- HERO SECTION (Clean White & Purple) --- */
    .hub-hero {
        padding: 35px;
        background: var(--card-bg);
        border-radius: 20px; 
        margin-bottom: 30px;
        position: relative; 
        overflow: hidden;
        /* Custom Animated Inner Purple Glow added from bottom to top (Increased Capacity) */
        box-shadow: inset 0 -40px 80px -20px rgba(99,102,241,0.3), 0 10px 30px -10px rgba(99,102,241,0.2);
        animation: heroPurpleGlow 3s infinite ease-in-out;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid var(--border-color);
    }

    /* Keyframes for Hero Inner Glow (More Intense Purple) */
    @keyframes heroPurpleGlow {
        0% { box-shadow: inset 0 -40px 80px -20px rgba(99,102,241,0.3), 0 10px 30px -10px rgba(99,102,241,0.2); }
        50% { box-shadow: inset 0 -120px 150px -30px rgba(99,102,241,0.65), 0 15px 40px -10px rgba(99,102,241,0.3); }
        100% { box-shadow: inset 0 -40px 80px -20px rgba(99,102,241,0.3), 0 10px 30px -10px rgba(99,102,241,0.2); }
    }

    /* Subtle Purple Glow in Background */
    .hub-hero::after {
        content: ''; position: absolute; right: -10%; top: -50%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, var(--lx-primary-light) 0%, transparent 70%);
        border-radius: 50%; pointer-events: none; z-index: 0;
    }

    .hero-text { position: relative; z-index: 10; max-width: 60%; }
    
    .hero-badge {
        background: var(--lx-primary-light); 
        color: var(--lx-primary-dark);
        padding: 6px 14px; border-radius: 10px;
        font-size: 0.85rem; font-weight: 700; 
        text-transform: uppercase; letter-spacing: 0.5px;
        display: inline-block; margin-bottom: 12px;
        border: 1px solid rgba(99,102,241,0.2);
    }
    
    .hero-title { font-size: 2.2rem; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; color: var(--text-dark);}
    .hero-sub { font-size: 1rem; color: var(--text-muted); margin-top: 5px; font-weight: 500; margin-bottom: 0; }

    /* --- HERO ACTIONS --- */
    .hero-actions { display: flex; flex-direction: column; gap: 12px; align-items: flex-end; position: relative; z-index: 10; }

    .balance-box {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; /* Added gap for icon spacing */
        background: linear-gradient(135deg, var(--lx-primary), var(--lx-primary-dark)); color: #fff; 
        padding: 12px 25px; border-radius: 12px; 
        font-weight: 700; font-size: 1rem;
        box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        transition: 0.2s; text-decoration: none; white-space: nowrap; border: none;
    }
    .balance-box:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99,102,241,0.4); color: #fff;}

    .whatsapp-box {
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        background: #22c55e; color: #fff; 
        padding: 10px 25px; border-radius: 12px; 
        font-weight: 700; font-size: 0.95rem;
        box-shadow: 0 4px 15px rgba(34,197,94,0.2);
        transition: 0.2s; text-decoration: none; white-space: nowrap;
    }
    .whatsapp-box:hover { transform: translateY(-2px); background: #16a34a; box-shadow: 0 8px 20px rgba(34,197,94,0.3); color: #fff;}
    .whatsapp-box img { width: 24px; height: 24px; object-fit: contain; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.1)); }

    /* LIVE ICON ANIMATIONS */
    @keyframes livePulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.25); }
        100% { transform: scale(1); }
    }
    @keyframes liveWiggle {
        0%, 100% { transform: rotate(0deg); }
        20% { transform: rotate(-15deg); }
        40% { transform: rotate(15deg); }
        60% { transform: rotate(-15deg); }
        80% { transform: rotate(15deg); }
    }
    .balance-box i { animation: livePulse 1.5s infinite ease-in-out; }
    .whatsapp-box i.fa-whatsapp { animation: liveWiggle 2.5s infinite ease-in-out; }

    /* Mobile Responsive for Hero */
    @media (max-width: 768px) {
        .hub-hero { flex-direction: column; text-align: left; padding: 25px 20px; align-items: flex-start; }
        .hero-text { max-width: 100%; margin-bottom: 25px; }
        .hero-actions { align-items: stretch; width: 100%; flex-direction: column; gap: 10px; }
        .balance-box, .whatsapp-box { width: 100%; padding: 12px 10px; font-size: 0.9rem; justify-content: center;}
    }

    /* --- COMPACT GRID --- */
    .hub-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px; 
        padding: 0; 
        animation: fadeInUp 0.6s ease;
    }

    /* --- WHITE & PURPLE CARDS --- */
    .hub-card {
        border-radius: 18px; position: relative; overflow: hidden; text-decoration: none;
        transition: all 0.3s ease;
        display: flex; flex-direction: column; justify-content: space-between;
        min-height: 220px; /* COMPACT HEIGHT */
        background-color: var(--card-bg); /* Clean White */
        padding: 22px;
        width: 100%;
        border: 2px solid var(--border-color); /* Light grey border */
    }

    .hub-card:hover { 
        transform: translateY(-5px); 
        border-color: var(--lx-primary); 
        box-shadow: 0 15px 35px rgba(99,102,241,0.12); 
    }

    /* Faint Background Images for Texture */
    .hub-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-size: cover; 
        background-position: right center;
        background-repeat: no-repeat;
        transition: 0.4s ease; z-index: 0; 
        opacity: 0.7; 
        -webkit-mask-image: linear-gradient(to right, transparent 30%, black 100%);
        mask-image: linear-gradient(to right, transparent 30%, black 100%);
    }

    .hc-smm::before { background-image: url('../assets/img/icons/smm.png'); }
    .hc-store::before { background-image: url('../assets/img/icons/sub.png'); }
    .hc-dl::before { background-image: url('../assets/img/icons/down.png'); }
    .hc-usdt::before { background-image: url('../assets/img/usdtbg.png'); }
    .hc-support::before { background-image: url('../assets/img/icons/down.png'); }

    .hub-card:hover::before { opacity: 1; transform: scale(1.05); }

    /* Content Wrapper */
    .card-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: flex-start; height: 100%; width: 100%; }

    /* Sleek Icon Box (Purple Themed) */
    .hc-icon-box {
        width: 50px; height: 50px; border-radius: 14px; 
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 15px; transition: 0.3s;
        background: var(--lx-primary-light); 
        color: var(--lx-primary);
    }
    .hc-icon-box img { width: 26px; height: 26px; object-fit: contain; }

    .hub-card:hover .hc-icon-box { background: var(--lx-primary); color: #fff; transform: scale(1.1); }

    /* Text Styling */
    .hc-title { font-size: 1.25rem; font-weight: 800; color: var(--text-dark); margin: 0 0 8px 0; letter-spacing: -0.3px;}
    
    /* Compact Scroller */
    .scroller { height: 22px; overflow: hidden; position: relative; margin-top: 0px; width: 100%; }
    .scroller-inner { display: flex; flex-direction: column; animation: scrollUp 6s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
    .scroller span { 
        height: 22px; display: flex; align-items: center; gap: 8px;
        font-size: 0.85rem; color: var(--text-muted); font-weight: 500;
    }
    .scroller i { color: var(--lx-primary); font-size: 0.75rem; }

    @keyframes scrollUp {
        0%, 20% { transform: translateY(0); }
        25%, 45% { transform: translateY(-22px); }
        50%, 70% { transform: translateY(-44px); }
        75%, 95% { transform: translateY(-66px); }
        100% { transform: translateY(0); }
    }

    /* Sleek Purple Button */
    .hc-btn {
        background: var(--lx-primary-light); color: var(--lx-primary-dark); 
        padding: 10px 16px; border-radius: 10px;
        font-size: 0.9rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center;
        transition: 0.3s; width: 100%; margin-top: auto;
    }
    .hub-card:hover .hc-btn { background: var(--lx-primary); color: #fff; padding-right: 12px; }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

</style>

<div class="main-content-wrapper">

    <div id="skeletonLoader">
        <div class="skeleton" style="height: 180px; border-radius: 20px; margin-bottom: 30px;"></div>
        <div class="hub-grid">
            <?php for($i=0; $i<4; $i++): ?>
            <div class="skeleton" style="height: 220px; border-radius: 16px; width: 100%;"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div id="realContent">

        <div class="hub-hero">
            <div class="hero-text animate__animated animate__fadeInLeft">
                <span class="hero-badge"><i class="fa-regular fa-clock me-1"></i> <?= $greeting ?></span>
                <h1 class="hero-title"><?= $display_name ?></h1>
                <p class="hero-sub">Welcome back! Ready to scale your presence?</p>
            </div>
            
            <div class="hero-actions animate__animated animate__fadeInRight">
                <!-- Icon aur text ke darmiyan spacing properly adjust ho chuki hai -->
                <a href="add-funds.php" class="balance-box">
                    <i class="fa-solid fa-plus-circle"></i> <?= formatCurrency($user_balance) ?>
                </a>

                <a href="https://wa.me/923154922709" target="_blank" class="whatsapp-box">
                    <i class="fa-brands fa-whatsapp fs-5"></i> Live Support
                </a>
            </div>
        </div>

        <div class="hub-grid">
            
            <a href="smm_order.php" class="hub-card hc-smm">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-rocket"></i></div>
                    
                    <div class="hc-title">Social Media Panel</div>
                    <div class="scroller">
                        <div class="scroller-inner">
                            <span><i class="fa-solid fa-check"></i> Buy Instagram Followers</span>
                            <span><i class="fa-solid fa-check"></i> Buy TikTok Likes</span>
                            <span><i class="fa-solid fa-check"></i> Buy YouTube Views</span>
                            <span><i class="fa-solid fa-check"></i> Instant Delivery ⚡</span>
                        </div>
                    </div>

                    <div class="hc-btn mt-4">Start Boosting <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="sub_dashboard.php" class="hub-card hc-store">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-crown"></i></div>
                    
                    <div class="hc-title">Premium Accounts</div>
                    <div class="scroller">
                        <div class="scroller-inner">
                            <span><i class="fa-solid fa-check"></i> Netflix 4K Private</span>
                            <span><i class="fa-solid fa-check"></i> ChatGPT Plus</span>
                            <span><i class="fa-solid fa-check"></i> Canva Pro Admin</span>
                            <span><i class="fa-solid fa-check"></i> Full Warranty 🛡️</span>
                        </div>
                    </div>

                    <div class="hc-btn mt-4">Buy Account <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="downloads.php" class="hub-card hc-dl">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    
                    <div class="hc-title">Digital Assets</div>
                    <div class="scroller">
                        <div class="scroller-inner">
                            <span><i class="fa-solid fa-check"></i> Video Editing Packs</span>
                            <span><i class="fa-solid fa-check"></i> Premium Softwares</span>
                            <span><i class="fa-solid fa-check"></i> Graphic Templates</span>
                            <span><i class="fa-solid fa-check"></i> Instant Link 📥</span>
                        </div>
                    </div>

                    <div class="hc-btn mt-4">Download Now <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="p2p_trading.php" class="hub-card hc-usdt">
                <div class="card-content">
                    <div class="hc-icon-box"><img src="../assets/img/usdt.png" alt="USDT"></div>
                    
                    <div class="hc-title">Buy/Sell USDT</div>
                    <div class="scroller">
                        <div class="scroller-inner">
                            <span><i class="fa-solid fa-check"></i> Buy USDT with PKR</span>
                            <span><i class="fa-solid fa-check"></i> Fastest Service ⚡</span>
                            <span><i class="fa-solid fa-check"></i> Secure Escrow 🛡️</span>
                            <span><i class="fa-solid fa-check"></i> Best Rates 📈</span>
                        </div>
                    </div>

                    <div class="hc-btn mt-4">Trade Now <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>
            
             <a href="tickets.php" class="hub-card hc-support">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-headset"></i></div>
                    
                    <div class="hc-title">Help & Support</div>
                    <div class="scroller">
                        <div class="scroller-inner">
                            <span><i class="fa-solid fa-check"></i> 24/7 Customer Care</span>
                            <span><i class="fa-solid fa-check"></i> Fast Order Refills</span>
                            <span><i class="fa-solid fa-check"></i> Payment Issues</span>
                            <span><i class="fa-solid fa-check"></i> VIP Assistance 👑</span>
                        </div>
                    </div>

                    <div class="hc-btn mt-4">Get Help <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

        </div>

    </div>

</div>

<script>
// Skeleton Loader Logic
window.addEventListener('load', function() {
    setTimeout(function() {
        document.getElementById('skeletonLoader').style.display = 'none';
        document.getElementById('realContent').style.display = 'block';
    }, 400); // Super fast load
});
</script>

<?php include '_footer.php'; ?>
