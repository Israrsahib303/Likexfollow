<?php
// File: about.php (Root Directory - Public)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Default SEO Fallbacks
$seo_title = "About Us - LikexFollow | Pakistan's #1 Digital Agency";
$seo_desc = "Discover the story behind LikexFollow. We provide premium SMM services, digital assets, and growth strategies to scale your brand to the next level.";
$seo_kws = "about likexfollow, digital agency pakistan, smm panel experts, best smm provider, social media growth";

if (isset($db)) {
    try {
        $seo_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_name = ? OR page_url = ? LIMIT 1");
        $seo_stmt->execute([$current_public_page, $current_url]);
        $seo_data = $seo_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seo_data) {
            if (!empty($seo_data['meta_title'])) $seo_title = $seo_data['meta_title'];
            if (!empty($seo_data['meta_description'])) $seo_desc = $seo_data['meta_description'];
            if (!empty($seo_data['meta_keywords'])) $seo_kws = $seo_data['meta_keywords'];
        }
        
        // Traffic Logger
        $log_stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$user_ip, $current_url, 200, $user_agent, date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {}
}
// --- 🚀 ADVANCED 2-WAY SEO ENGINE ENDS ---

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

ob_start();
include 'user/_header.php'; 
$header_html = ob_get_clean();

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR 🚀
if (file_exists(__DIR__ . '/seo_auto_injector.php')) {
    require_once __DIR__ . '/seo_auto_injector.php';
    
    // Clean old meta tags
    $header_html = preg_replace('/<title>.*?<\/title>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']description["\'].*?>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']keywords["\'].*?>/i', '', $header_html);
    
    // Inject the fully automated API SEO Tags + JSON Schema
    $header_html = str_ireplace('</head>', $beast_seo_injection . "\n</head>", $header_html);
} else {
    $header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$seo_title</title>", $header_html);
}
echo $header_html;

// Currency Setup (If header needs it)
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = 1; 
$curr_symbol = 'Rs';
if ($curr_code != 'PKR') {
    if(function_exists('getCurrencyRate')) $curr_rate = getCurrencyRate($curr_code);
    $symbols = ['PKR'=>'Rs','USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','SAR'=>'﷼','AED'=>'د.إ'];
    $curr_symbol = $symbols[$curr_code] ?? $curr_code;
}
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let metaDesc = document.querySelector('meta[name="description"]');
        if(!metaDesc) {
            let meta = document.createElement('meta');
            meta.name = "description"; meta.content = "<?= addslashes($seo_desc) ?>";
            document.head.appendChild(meta);
        }
        let metaKws = document.querySelector('meta[name="keywords"]');
        if(!metaKws) {
            let meta = document.createElement('meta');
            meta.name = "keywords"; meta.content = "<?= addslashes($seo_kws) ?>";
            document.head.appendChild(meta);
        }
    });
</script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; overflow-x: hidden; }

    .text-gradient-purple {
        background: linear-gradient(135deg, #4f46e5 0%, #d946ef 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Hero Section */
    .about-hero {
        padding: 140px 20px 80px 20px;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
    }
    .hero-bg-pattern {
        position: absolute; inset: 0; opacity: 0.4; z-index: 0;
        background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
        background-size: 24px 24px;
    }
    .hero-glow {
        position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
        width: 600px; height: 600px; background: radial-gradient(circle, rgba(79,70,229,0.08) 0%, transparent 60%);
        border-radius: 50%; z-index: 0; pointer-events: none;
    }

    .hero-content { position: relative; z-index: 10; max-width: 800px; margin: 0 auto; }
    .hero-tag { display: inline-block; padding: 6px 16px; background: #eef2ff; color: #4f46e5; border-radius: 50px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border: 1px solid #c7d2fe; }
    .hero-title { font-size: 4rem; font-weight: 900; line-height: 1.1; margin-bottom: 25px; letter-spacing: -1px; color: #0f172a; }
    .hero-desc { font-size: 1.2rem; color: #64748b; line-height: 1.7; margin-bottom: 40px; font-weight: 400; }

    /* Stats Section */
    .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-top: 40px; }
    .stat-box { background: #ffffff; padding: 30px; border-radius: 24px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; transition: transform 0.3s ease; }
    .stat-box:hover { transform: translateY(-5px); border-color: #c7d2fe; }
    .stat-num { font-size: 3rem; font-weight: 900; color: #4f46e5; margin-bottom: 5px; line-height: 1; }
    .stat-label { font-size: 1rem; font-weight: 600; color: #64748b; }

    /* Story Section */
    .story-section { padding: 100px 20px; max-width: 1200px; margin: 0 auto; }
    .story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
    .story-content h2 { font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; letter-spacing: -0.5px; }
    .story-content p { font-size: 1.1rem; color: #475569; line-height: 1.8; margin-bottom: 20px; }
    .story-image-wrap { position: relative; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }
    .story-image { width: 100%; height: auto; display: block; border-radius: 24px; }
    .story-image-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(79,70,229,0.2) 0%, rgba(217,70,239,0.2) 100%); mix-blend-mode: overlay; }

    /* Values Section */
    .values-section { background: #0f172a; padding: 100px 20px; color: #ffffff; text-align: center; }
    .values-title { font-size: 2.8rem; font-weight: 800; margin-bottom: 60px; }
    .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
    .value-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 40px 30px; border-radius: 24px; transition: 0.3s; backdrop-filter: blur(10px); }
    .value-card:hover { background: rgba(255,255,255,0.08); transform: translateY(-10px); border-color: rgba(255,255,255,0.2); }
    .value-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #4f46e5, #d946ef); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #fff; margin: 0 auto 25px auto; }
    .value-card h3 { font-size: 1.3rem; font-weight: 700; margin-bottom: 15px; color: #f8fafc; }
    .value-card p { color: #94a3b8; line-height: 1.6; font-size: 0.95rem; margin: 0; }

    /* CTA Section */
    .cta-section { padding: 100px 20px; text-align: center; background: #ffffff; }
    .cta-box { max-width: 900px; margin: 0 auto; background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); padding: 60px 40px; border-radius: 32px; border: 1px solid #e0e7ff; box-shadow: 0 20px 40px -15px rgba(79,70,229,0.1); }
    .cta-box h2 { font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; letter-spacing: -0.5px; }
    .cta-box p { font-size: 1.15rem; color: #475569; margin-bottom: 40px; }
    .cta-buttons { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
    
    .btn-primary { background: #4f46e5; color: #ffffff; padding: 16px 36px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; text-decoration: none; transition: 0.3s; box-shadow: 0 10px 20px -5px rgba(79,70,229,0.4); display: inline-flex; align-items: center; gap: 10px; }
    .btn-primary:hover { background: #4338ca; transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(79,70,229,0.5); color: #fff; }
    
    .btn-secondary { background: #ffffff; color: #0f172a; padding: 16px 36px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; text-decoration: none; transition: 0.3s; border: 2px solid #e2e8f0; display: inline-flex; align-items: center; gap: 10px; }
    .btn-secondary:hover { border-color: #0f172a; transform: translateY(-3px); color: #0f172a; }

    @media (max-width: 992px) {
        .hero-title { font-size: 3rem; }
        .story-grid { grid-template-columns: 1fr; gap: 40px; }
        .story-image-wrap { order: -1; } /* Image on top for mobile */
    }
    @media (max-width: 768px) {
        .hero-title { font-size: 2.2rem; }
        .cta-buttons { flex-direction: column; width: 100%; }
        .cta-buttons a { width: 100%; justify-content: center; }
    }
</style>

<section class="about-hero">
    <div class="hero-bg-pattern"></div>
    <div class="hero-glow"></div>
    
    <div class="hero-content">
        <span class="hero-tag">We Are LikexFollow</span>
        <h1 class="hero-title">Turning Ambition Into <br><span class="text-gradient-purple">Digital Dominance</span></h1>
        <p class="hero-desc">
            <?php 
            $hero_desc = "We are not just another agency. We are an elite team of growth hackers, developers, and digital strategists dedicated to scaling your online presence at lightning speed.";
            echo function_exists('auto_spider_link') ? auto_spider_link($hero_desc, $db) : $hero_desc;
            ?>
        </p>
        
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-num">50K+</div>
                <div class="stat-label">Happy Clients</div>
            </div>
            <div class="stat-box">
                <div class="stat-num">2M+</div>
                <div class="stat-label">Orders Completed</div>
            </div>
            <div class="stat-box">
                <div class="stat-num">24/7</div>
                <div class="stat-label">Expert Support</div>
            </div>
        </div>
    </div>
</section>

<section class="story-section">
    <div class="story-grid">
        <div class="story-content">
            <h2>The Vision Behind <span class="text-gradient-purple">LikexFollow</span></h2>
            <p>
                <?php 
                $story_1 = "In a world where digital presence dictates success, standing out is harder than ever. That's why we built LikexFollow—to level the playing field.";
                echo function_exists('auto_spider_link') ? auto_spider_link($story_1, $db) : $story_1;
                ?>
            </p>
            <p>
                <?php 
                $story_2 = "Our journey started with a simple goal: provide <strong>high-quality, ultra-fast, and secure</strong> Social Media Marketing (SMM) and Digital Assets to creators and brands globally. Today, we are proud to be a trusted partner for influencers, startups, and massive enterprises.";
                echo function_exists('auto_spider_link') ? auto_spider_link($story_2, $db) : $story_2;
                ?>
            </p>
            <p>
                <?php 
                $story_3 = "From organic growth strategies to premium digital subscriptions, we automate the heavy lifting so you can focus on creating.";
                echo function_exists('auto_spider_link') ? auto_spider_link($story_3, $db) : $story_3;
                ?>
            </p>
            
            <ul style="list-style: none; padding: 0; margin-top: 25px;">
                <li style="margin-bottom: 12px; font-weight: 600; color: #334155;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> 100% Genuine Digital Services</li>
                <li style="margin-bottom: 12px; font-weight: 600; color: #334155;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> State-of-the-Art API Integrations</li>
                <li style="font-weight: 600; color: #334155;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 10px;"></i> Advanced Data-Driven SEO Tools</li>
            </ul>
        </div>
        <div class="story-image-wrap">
            <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?q=80&w=2070&auto=format&fit=crop" alt="LikexFollow Team Workspace" class="story-image">
            <div class="story-image-overlay"></div>
        </div>
    </div>
</section>

<section class="values-section">
    <h2 class="values-title">Our <span class="text-gradient-purple">Core Values</span></h2>
    
    <div class="values-grid">
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-rocket"></i></div>
            <h3>Lightning Fast Delivery</h3>
            <p>Time is money. Our automated backend systems ensure that your orders and subscriptions are delivered within seconds, not days.</p>
        </div>
        
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
            <h3>Unmatched Security</h3>
            <p>Your data and accounts are safe with us. We use enterprise-grade encryption and secure protocols to protect our clients.</p>
        </div>
        
        <div class="value-card">
            <div class="value-icon"><i class="fas fa-gem"></i></div>
            <h3>Premium Quality</h3>
            <p>No bots, no drops. We pride ourselves on providing the highest quality non-drop followers, likes, and genuine premium assets.</p>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="cta-box">
        <h2>Ready to Hack Your Growth? 🚀</h2>
        <p>Join thousands of successful brands and influencers who trust LikexFollow for their digital dominance.</p>
        <div class="cta-buttons">
            <a href="services.php" class="btn-primary">
                Explore SMM Services <i class="fas fa-arrow-right"></i>
            </a>
            <a href="products.php" class="btn-secondary">
                Visit Premium Store <i class="fas fa-store"></i>
            </a>
        </div>
    </div>
</section>

<?php include 'user/_smm_footer.php'; ?>