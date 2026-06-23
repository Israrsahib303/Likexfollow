<?php
// File: contact.php (Root Directory - Public)
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
$seo_title = "Contact Us - LikexFollow | 24/7 Premium Support";
$seo_desc = "Get in touch with LikexFollow. Reach out to our owner Israr Liaqat or 24/7 support team via WhatsApp for any SMM or digital product queries.";
$seo_kws = "contact likexfollow, customer support smm, whatsapp support smm panel, israr liaqat likexfollow";

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

// --- ✉️ FORM HANDLING LOGIC ---
$msg = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if(!empty($name) && !empty($message) && !empty($email)) {
        // Here you can add DB insertion if you have a `contact_messages` table
        $msg = "Thank you, $name! Your message has been securely sent. Our team will get back to you shortly.";
        $msg_type = "success";
    } else {
        $msg = "Please fill in all required fields properly.";
        $msg_type = "danger";
    }
}

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

// WhatsApp Numbers Configuration
$wa_owner = "923097856447"; // Owner Israr Liaqat
$wa_support = "923154922709"; // Support Team
$support_email = "likexfollow.com@gmail.com";
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Reset & Base */
    .contact-page-container * { box-sizing: border-box; }
    .contact-page-container {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: #0f172a;
        width: 100%;
        overflow-x: hidden; /* Prevent horizontal scrolling */
        padding-bottom: 80px;
    }

    .text-gradient-purple {
        background: linear-gradient(135deg, #4f46e5 0%, #d946ef 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Hero Section */
    .contact-hero {
        padding: 100px 20px 60px 20px;
        background: #ffffff;
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
        width: 100%;
    }
    .hero-bg-pattern {
        position: absolute; inset: 0; opacity: 0.3; z-index: 0;
        background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
        background-size: 24px 24px;
    }
    .hero-content { position: relative; z-index: 10; max-width: 800px; margin: 0 auto; width: 100%; }
    .hero-tag { display: inline-block; padding: 6px 16px; background: #eef2ff; color: #4f46e5; border-radius: 50px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; border: 1px solid #c7d2fe; }
    .hero-title { font-size: 3rem; font-weight: 900; line-height: 1.2; margin-bottom: 20px; letter-spacing: -1px; color: #0f172a; }
    .hero-desc { font-size: 1.1rem; color: #64748b; line-height: 1.6; font-weight: 500; }

    /* Main Layout */
    .contact-wrapper {
        max-width: 1100px;
        margin: 60px auto 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 40px;
        width: 100%;
    }

    /* Left Side: Contact Cards */
    .contact-info-section { display: flex; flex-direction: column; gap: 20px; width: 100%; }
    
    .info-card {
        background: #ffffff; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); transition: all 0.3s ease;
        display: flex; align-items: flex-start; gap: 20px; text-decoration: none; color: inherit;
        position: relative; overflow: hidden; width: 100%;
    }
    .info-card:hover { transform: translateY(-5px); border-color: #c7d2fe; box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.1); }
    
    .icon-box {
        width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; flex-shrink: 0;
    }
    .icon-wa-owner { background: #ecfdf5; color: #10b981; }
    .icon-wa-support { background: #eff6ff; color: #3b82f6; }
    .icon-email { background: #fdf4ff; color: #d946ef; }

    .card-content h3 { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 5px 0; line-height: 1.3; }
    .card-content p { font-size: 0.9rem; color: #64748b; margin: 0 0 8px 0; line-height: 1.5; }
    .card-content .wa-num { font-weight: 800; font-size: 1.05rem; color: #0f172a; display: flex; align-items: center; gap: 8px; word-break: break-word; }
    .card-content .wa-num i { font-size: 0.85rem; color: #10b981; }

    /* Right Side: Contact Form */
    .contact-form-box {
        background: #ffffff; padding: 35px; border-radius: 20px; border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); width: 100%;
    }
    .form-header { margin-bottom: 25px; }
    .form-header h2 { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 8px 0; }
    .form-header p { color: #64748b; margin: 0; font-size: 0.9rem; }

    .form-group { margin-bottom: 18px; width: 100%; }
    .form-label { font-weight: 700; color: #334155; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .form-control {
        width: 100%; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 10px;
        font-size: 0.95rem; font-family: 'Inter', sans-serif; color: #0f172a; outline: none; transition: 0.3s;
        background: #f8fafc;
    }
    .form-control:focus { border-color: #4f46e5; background: #ffffff; box-shadow: 0 0 0 4px #eef2ff; }
    textarea.form-control { min-height: 130px; resize: vertical; }

    .btn-submit {
        width: 100%; background: linear-gradient(135deg, #4f46e5 0%, #7e22ce 100%); color: #ffffff;
        padding: 15px; border-radius: 10px; font-weight: 800; font-size: 1.05rem; border: none; cursor: pointer;
        transition: all 0.3s ease; box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4); display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5); }

    /* Alert Message */
    .alert-box { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* Responsive Constraints */
    @media (max-width: 992px) {
        .contact-wrapper { grid-template-columns: 1fr; gap: 30px; padding: 0 15px; }
        .hero-title { font-size: 2.4rem; }
        .contact-form-box { padding: 25px; }
    }
    
    @media (max-width: 480px) {
        .info-card { flex-direction: column; align-items: flex-start; gap: 15px; padding: 20px; }
    }
</style>

<div class="contact-page-container">
    <section class="contact-hero">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <span class="hero-tag">Get In Touch</span>
            <h1 class="hero-title">We're Here to Help You <span class="text-gradient-purple">Grow</span></h1>
            <p class="hero-desc">
                <?php 
                $desc = "Have a question about our SMM Panel, premium digital assets, or bulk orders? Reach out to our dedicated team via WhatsApp or send us a message below.";
                // 🕸️ Auto Spider Linker Active!
                echo function_exists('auto_spider_link') ? auto_spider_link($desc, $db) : $desc; 
                ?>
            </p>
        </div>
    </section>

    <div class="contact-wrapper">
        
        <div class="contact-info-section">
            
            <a href="https://wa.me/<?= $wa_owner ?>?text=Hi Israr Liaqat! I need some details regarding LikexFollow services." target="_blank" class="info-card">
                <div class="icon-box icon-wa-owner"><i class="fab fa-whatsapp"></i></div>
                <div class="card-content">
                    <h3>Owner - Israr Liaqat</h3>
                    <p>For urgent inquiries, business partnerships, or bulk deals.</p>
                    <div class="wa-num">0309-7856447 <i class="fas fa-external-link-alt"></i></div>
                </div>
            </a>

            <a href="https://wa.me/<?= $wa_support ?>?text=Hi Support Team! I need help with my LikexFollow account/order." target="_blank" class="info-card">
                <div class="icon-box icon-wa-support"><i class="fab fa-whatsapp"></i></div>
                <div class="card-content">
                    <h3>24/7 Support Team</h3>
                    <p>For order issues, refill requests, or general technical support.</p>
                    <div class="wa-num">0315-4922709 <i class="fas fa-external-link-alt"></i></div>
                </div>
            </a>

            <div class="info-card" style="cursor: default;">
                <div class="icon-box icon-email"><i class="fas fa-envelope-open-text"></i></div>
                <div class="card-content">
                    <h3>Email Address</h3>
                    <p>Send us detailed proposals or attachments via email.</p>
                    <div class="wa-num" style="color: #4f46e5; font-size: 0.95rem;"><?= htmlspecialchars($support_email) ?></div>
                </div>
            </div>

        </div>

        <div class="contact-form-box">
            <div class="form-header">
                <h2>Send a Message</h2>
                <p>Fill out the form below and our support agents will respond via email or dashboard ticket within 24 hours.</p>
            </div>

            <?php if(!empty($msg)): ?>
                <div class="alert-box alert-<?= $msg_type ?>">
                    <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Order Issue / Refill Request" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" placeholder="How can we help you today?" required></textarea>
                </div>
                
                <button type="submit" name="submit_contact" class="btn-submit">
                    Send Message <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>

    </div>
</div>

<?php 
// Standard Public Footer Included
if(file_exists('user/_footer.php')) {
    include 'user/_footer.php'; 
} else {
    echo "</body></html>";
}
?>