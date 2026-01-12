<?php
include '_header.php';

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

// Greeting
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");

// User Name
$stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_real_name = $stmt->fetchColumn();
$display_name = !empty($user_real_name) ? htmlspecialchars($user_real_name) : 'User';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --hover-shadow: 0 20px 40px -5px rgba(99, 102, 241, 0.3);
    }

    body { font-family: 'Outfit', sans-serif; background: #f1f5f9; overflow-x: hidden; }
    
    /* --- SKELETON STYLES --- */
    .skeleton {
        background: #e2e8f0;
        background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 12px;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    
    #realContent { display: none; animation: fadeIn 0.5s ease-out; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* --- HERO SECTION (Animated & Compact) --- */
    .hub-hero {
        padding: 35px 30px;
        background: linear-gradient(-45deg, #4f46e5, #7c3aed, #2563eb, #9333ea);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite; /* Moving Background */
        border-radius: 24px; 
        color: #fff; 
        margin-bottom: 40px;
        position: relative; 
        overflow: hidden;
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.6);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.2);
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* --- DECORATIVE LIGHTS (BULBS) --- */
    .light-string {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 30px;
        z-index: 5; display: flex;
        justify-content: space-around;
        padding: 0 20px;
        pointer-events: none;
    }
    
    .light-holder {
        position: relative;
        display: flex; flex-direction: column; align-items: center;
    }
    
    /* The Wire */
    .wire-line {
        position: absolute; top: -15px; width: 150%; height: 30px;
        border-bottom: 2px solid rgba(0,0,0,0.3);
        border-radius: 50%;
        z-index: 1;
    }
    
    /* The Bulb */
    .bulb {
        width: 12px; height: 12px; border-radius: 50%;
        margin-top: 15px; /* Distance from top */
        position: relative; z-index: 2;
        animation-duration: 1.5s;
        animation-iteration-count: infinite;
        animation-fill-mode: both;
    }
    
    /* Bulb Socket */
    .bulb::before {
        content: ''; position: absolute; top: -3px; left: 3px;
        width: 6px; height: 4px; background: #333;
    }

    /* Colors and Animations */
    .bulb.red { background: #ff4d4d; animation-name: flash-1; }
    .bulb.yellow { background: #f1c40f; animation-name: flash-2; }
    .bulb.green { background: #2ecc71; animation-name: flash-3; }
    .bulb.blue { background: #3498db; animation-name: flash-1; }

    @keyframes flash-1 { 
        0%, 100% { opacity: 1; box-shadow: 0 0 10px currentColor; } 
        50% { opacity: 0.4; box-shadow: none; } 
    }
    @keyframes flash-2 { 
        0%, 100% { opacity: 0.4; box-shadow: none; } 
        50% { opacity: 1; box-shadow: 0 0 10px currentColor; } 
    }
    @keyframes flash-3 { 
        0%, 50% { opacity: 0.3; } 
        100% { opacity: 1; box-shadow: 0 0 15px currentColor; transform: scale(1.1); } 
    }

    /* --- BACKGROUND FLOATING ANIMATIONS (Bubbles/Hearts) --- */
    .hero-shapes {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        overflow: hidden;
        z-index: 1; /* Behind text */
        pointer-events: none;
    }

    .shape {
        position: absolute;
        bottom: -50px;
        display: block;
        animation: floatUp linear infinite;
        opacity: 0;
    }

    /* Bubbles (Circles) */
    .shape.bubble {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
    }

    /* Emojis */
    .shape.emoji {
        font-size: 1.5rem;
        background: transparent;
    }

    @keyframes floatUp {
        0% { transform: translateY(0) rotate(0deg) scale(0.8); opacity: 0; }
        20% { opacity: 0.8; }
        80% { opacity: 0.6; }
        100% { transform: translateY(-400px) rotate(360deg) scale(1.2); opacity: 0; }
    }

    /* Positioning Specific Shapes */
    .s1 { left: 10%; width: 40px; height: 40px; animation-duration: 7s; animation-delay: 0s; } /* Bubble */
    .s2 { left: 20%; font-size: 2rem !important; animation-duration: 12s; animation-delay: 2s; } /* Heart */
    .s3 { left: 35%; width: 20px; height: 20px; animation-duration: 6s; animation-delay: 4s; } /* Bubble */
    .s4 { left: 50%; font-size: 1.8rem !important; animation-duration: 9s; animation-delay: 1s; } /* Star */
    .s5 { left: 65%; width: 50px; height: 50px; animation-duration: 8s; animation-delay: 3s; } /* Bubble */
    .s6 { left: 80%; font-size: 2rem !important; animation-duration: 11s; animation-delay: 0.5s; } /* Money */
    .s7 { left: 90%; width: 30px; height: 30px; animation-duration: 5s; animation-delay: 2s; } /* Bubble */


    /* Text & Buttons Z-Index */
    .hero-text, .hero-actions {
        position: relative; 
        z-index: 10; /* Above animations */
    }

    .hero-text { max-width: 60%; }
    
    .hero-badge {
        background: rgba(255,255,255,0.2); 
        padding: 5px 15px; border-radius: 20px;
        font-size: 0.85rem; font-weight: 700; 
        text-transform: uppercase; letter-spacing: 1px;
        backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3);
        display: inline-block; margin-bottom: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .hero-title { 
        font-size: 2.4rem; font-weight: 800; margin: 0; 
        line-height: 1.1;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2); 
    }
    .hero-sub { 
        font-size: 1.05rem; opacity: 0.9; margin-top: 8px; font-weight: 400; 
        margin-bottom: 0;
    }

    /* --- HERO ACTIONS (Right Side) --- */
    .hero-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: flex-end;
    }

    /* Balance Button */
    .balance-box {
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(255, 255, 255, 0.95); 
        color: #4f46e5; 
        padding: 10px 25px;
        border-radius: 16px; 
        font-weight: 800; font-size: 1.1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: 0.3s; text-decoration: none;
        white-space: nowrap;
        border: 2px solid transparent;
    }
    .balance-box:hover { 
        transform: translateY(-3px); 
        background: #fff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3); 
    }

    /* WhatsApp Button */
    .whatsapp-box {
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        background: #25D366; color: #fff; 
        padding: 10px 25px;
        border-radius: 16px; 
        font-weight: 700; font-size: 1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: 0.3s; text-decoration: none;
        white-space: nowrap;
        border: 2px solid rgba(255,255,255,0.3);
        position: relative; overflow: hidden;
    }

    /* Ripple Effect on Hover */
    .whatsapp-box::before {
        content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: 0.5s;
    }
    .whatsapp-box:hover::before { left: 100%; }

    .whatsapp-box:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 10px 25px rgba(37, 211, 102, 0.5); 
        background: #20bd5a;
    }
    
    /* UPDATED ICON SIZE */
    .whatsapp-box img {
        width: 34px; height: 34px; /* Big Size */
        object-fit: contain;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
    }

    /* Mobile Responsive for Hero */
    @media (max-width: 768px) {
        .hub-hero {
            flex-direction: column;
            text-align: center;
            padding: 40px 20px;
        }
        .hero-text { max-width: 100%; margin-bottom: 25px; }
        .hero-actions { align-items: center; width: 100%; }
        .balance-box, .whatsapp-box { width: 100%; }
        .light-string { padding: 0 5px; } /* Adjust lights on mobile */
    }

    /* --- GRID (CENTERED) --- */
    .hub-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px; 
        padding: 0 10px; 
        animation: fadeInUp 1s ease;
        justify-content: center; /* Center items */
    }

    /* --- CARD DESIGN (FIXED WITH BORDER) --- */
    .hub-card {
        border-radius: 24px; position: relative; overflow: hidden; text-decoration: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; flex-direction: column; justify-content: space-between;
        min-height: 280px;
        box-shadow: 0 30px 20px rgba(0,0,0,0.05);
        background-color: #fff;
        padding: 25px;
        max-width: 400px; /* Prevent too wide on large screens */
        margin: 0 auto; /* Center in grid cell */
        width: 100%;
        
        /* --- NEW BORDER ADDED --- */
        border: 4px solid #28282b; 
    }

    .hub-card:hover { 
        transform: translateY(-10px); 
        box-shadow: var(--hover-shadow); 
        
        /* --- HOVER BORDER COLOR (Primary Color) --- */
        border-color: #6366f1; 
    }

    /* Background Images (Full Fit, Higher Opacity) */
    .hub-card::before {
        content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-size: 100% 100%; /* Force Fit Full Card */
        background-position: center;
        background-repeat: no-repeat;
        transition: 0.5s; z-index: 0; 
        opacity: 0.; /* Increased Opacity */
        filter: grayscale(0%); /* Show Colors */
    }
    .hc-smm::before { background-image: url('../assets/img/icons/smm.png'); }
    .hc-store::before { background-image: url('../assets/img/icons/sub.png'); }
    .hc-dl::before { background-image: url('../assets/img/icons/down.png'); }
    .hc-ai::before { background-image: url('../assets/img/icons/ai.png'); }
    /* USDT BG Location */
    .hc-usdt::before { background-image: url('../assets/img/usdtbg.png'); }

    .hub-card:hover::before { transform: scale(1.05); opacity: 0.4; }

    /* Content Wrapper (Left Aligned) */
    .card-content {
        position: relative; z-index: 2; 
        display: flex; flex-direction: column; align-items: flex-start; 
        height: 100%; width: 100%;
    }

    /* Icon Box */
    .hc-icon-box {
        width: 60px; height: 60px; border-radius: 16px; 
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; margin-bottom: 20px; transition: 0.4s;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); color: #64748b;
    }
    
    .hc-icon-box img { width: 35px; height: 35px; object-fit: contain; }

    /* Icon Colors */
    .hc-smm .hc-icon-box { color: #3b82f6; }
    .hc-store .hc-icon-box { color: #f97316; }
    .hc-dl .hc-icon-box { color: #10b981; }
    .hc-ai .hc-icon-box { color: #a855f7; }
    .hc-usdt .hc-icon-box { color: #26a17b; }

    .hub-card:hover .hc-icon-box { background: #fff; transform: rotate(-10deg) scale(1.1); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

    /* Text Styling (White Background for Clarity) */
    .text-wrapper {
        background: rgba(255, 255, 255, 0.85); /* Stronger background */
        backdrop-filter: blur(8px);
        padding: 12px 15px; border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.6);
        margin-bottom: auto;
        width: 100%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .hc-title { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0 0 5px 0; }
    
    /* Scroller */
    .scroller { height: 24px; overflow: hidden; position: relative; margin-top: 5px; }
    .scroller-inner { display: flex; flex-direction: column; animation: scrollUp 6s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
    .scroller span { 
        height: 24px; display: flex; align-items: center; gap: 8px;
        font-size: 0.9rem; color: #475569; font-weight: 600;
    }
    .scroller i { color: #10b981; font-size: 0.8rem; }

    @keyframes scrollUp {
        0%, 20% { transform: translateY(0); }
        25%, 45% { transform: translateY(-24px); }
        50%, 70% { transform: translateY(-48px); }
        75%, 95% { transform: translateY(-72px); }
        100% { transform: translateY(0); }
    }

    /* Button */
    .hc-btn {
        background: #1e293b; color: #fff; padding: 12px 20px; border-radius: 12px;
        font-size: 0.95rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center;
        transition: 0.3s; width: 100%; margin-top: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .hub-card:hover .hc-btn { background: #4f46e5; padding-right: 15px; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4); }
    
    /* Animations */
    @keyframes fadeInDown { from { opacity:0; transform:translateY(-30px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeInUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }

    @media(max-width: 768px) {
        .hero-title { font-size: 2.2rem; }
        .hub-grid { padding: 0 5px; }
    }
</style>

<div class="main-content-wrapper">

    <div id="skeletonLoader">
        <div class="skeleton" style="height: 200px; border-radius: 30px; margin-bottom: 40px;"></div>
        
        <div class="hub-grid">
            <?php for($i=0; $i<5; $i++): ?>
            <div class="skeleton" style="height: 280px; border-radius: 24px; width: 100%; max-width: 400px; margin: 0 auto;"></div>
            <?php endfor; ?>
        </div>
    </div>

    <div id="realContent">

        <div class="hub-hero animate__animated animate__fadeInDown">
            
            <div class="light-string">
                <div class="light-holder"><div class="wire-line"></div><div class="bulb red"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb yellow"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb green"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb blue"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb red"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb yellow"></div></div>
                <div class="light-holder"><div class="wire-line"></div><div class="bulb green"></div></div>
            </div>

            <div class="hero-shapes">
                <span class="shape bubble s1"></span>
                <span class="shape emoji s2">❤️</span>
                <span class="shape bubble s3"></span>
                <span class="shape emoji s4">✨</span>
                <span class="shape bubble s5"></span>
                <span class="shape emoji s6">💸</span>
                <span class="shape bubble s7"></span>
            </div>

            <div class="hero-text animate__animated animate__fadeInLeft" style="animation-delay: 0.2s;">
                <span class="hero-badge">👋 <?= $greeting ?></span>
                <h1 class="hero-title"><?= $display_name ?></h1>
                <p class="hero-sub">Welcome back! Ready to grow?</p>
            </div>
            
            <div class="hero-actions animate__animated animate__fadeInRight" style="animation-delay: 0.3s;">
                <a href="add-funds.php" class="balance-box">
                    💰 <?= formatCurrency($user_balance) ?> <span style="font-size:0.8rem; opacity:0.7; margin-left:5px;">(Add Funds)</span>
                </a>

                <a href="https://wa.me/923154922709" target="_blank" class="whatsapp-box animate__animated animate__pulse animate__infinite" style="animation-duration: 2s;">
                    <img src="../assets/img/icons/Whatsapp.png" alt="WhatsApp"> WhatsApp Help
                </a>
            </div>
        </div>

        <?php include 'flash_banner.php'; ?>
        <div class="hub-grid">
            
            <a href="smm_order.php" class="hub-card hc-smm">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-rocket animate__animated animate__pulse animate__infinite"></i></div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">Social Media Panel</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Buy Instagram Followers</span>
                                <span><i class="fa-solid fa-check"></i> Buy TikTok Likes</span>
                                <span><i class="fa-solid fa-check"></i> Buy YouTube Views</span>
                                <span><i class="fa-solid fa-check"></i> Instant Delivery ⚡</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Start Boosting <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="sub_dashboard.php" class="hub-card hc-store">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-crown animate__animated animate__tada animate__infinite" style="animation-duration: 2s;"></i></div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">Premium Accounts</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Netflix 4K Private</span>
                                <span><i class="fa-solid fa-check"></i> ChatGPT Plus</span>
                                <span><i class="fa-solid fa-check"></i> Canva Pro Admin</span>
                                <span><i class="fa-solid fa-check"></i> Full Warranty 🛡️</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Buy Account <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="downloads.php" class="hub-card hc-dl">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-cloud-arrow-down animate__animated animate__bounce animate__infinite" style="animation-duration: 3s;"></i></div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">Digital Assets</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Video Editing Packs</span>
                                <span><i class="fa-solid fa-check"></i> Premium Softwares</span>
                                <span><i class="fa-solid fa-check"></i> Graphic Templates</span>
                                <span><i class="fa-solid fa-check"></i> Instant Link 📥</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Download Now <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="ai_tools.php" class="hub-card hc-ai">
                <div class="card-content">
                    <div class="hc-icon-box"><i class="fa-solid fa-wand-magic-sparkles animate__animated animate__pulse animate__infinite"></i></div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">AI Growth Tools</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Viral Hook Gen</span>
                                <span><i class="fa-solid fa-check"></i> Caption Writer</span>
                                <span><i class="fa-solid fa-check"></i> Profile Auditor</span>
                                <span><i class="fa-solid fa-check"></i> 100% Free Tools 🤖</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Use Tools <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>

            <a href="p2p_trading.php" class="hub-card hc-usdt">
                <div class="card-content">
                    <div class="hc-icon-box">
                        <img src="../assets/img/usdt.png" alt="USDT" class="animate__animated animate__flipInY animate__infinite" style="animation-duration: 4s;">
                    </div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">Buy/Sell USDT</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Buy USDT with PKR</span>
                                <span><i class="fa-solid fa-check"></i> Fastest Service ⚡</span>
                                <span><i class="fa-solid fa-check"></i> No Scam / Secure 🛡️</span>
                                <span><i class="fa-solid fa-check"></i> Best Rates 📈</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Trade Now <i class="fa-solid fa-arrow-right"></i></div>
                </div>
            </a>
            
             <a href="receipt_maker.php" class="hub-card hc-usdt">
                <div class="card-content">
                    <div class="hc-icon-box" style="color: #6366f1;">
                         <i class="fa-solid fa-receipt animate__animated animate__pulse animate__infinite"></i>
                    </div>
                    
                    <div class="text-wrapper">
                        <div class="hc-title">Receipt Maker</div>
                        <div class="scroller">
                            <div class="scroller-inner">
                                <span><i class="fa-solid fa-check"></i> Professional Invoices</span>
                                <span><i class="fa-solid fa-check"></i> WhatsApp Order Bill</span>
                                <span><i class="fa-solid fa-check"></i> HD Download 📥</span>
                                <span><i class="fa-solid fa-check"></i> Free Tool 🛠️</span>
                            </div>
                        </div>
                    </div>

                    <div class="hc-btn">Create Receipt <i class="fa-solid fa-arrow-right"></i></div>
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
    }, 800);
});
</script>

<?php include '_footer.php'; ?>