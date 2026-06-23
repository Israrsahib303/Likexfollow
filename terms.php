<?php
// File: terms.php (Root Directory - Public)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Default SEO Fallbacks (Highly Optimized)
$seo_title = "Terms of Service - LikexFollow | Official Legal Policies";
$seo_desc = "Read the comprehensive Terms of Service for LikexFollow. Learn about our SMM panel rules, digital store policies, refund guidelines, and secure payment terms.";
$seo_kws = "terms of service, likexfollow policies, smm panel rules, refund policy, secure payments, digital marketing terms, buy followers safely";

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

// Force Meta Replacements
$header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$seo_title</title>", $header_html);
$meta_tags = '<meta name="description" content="'.$seo_desc.'">' . "\n" . 
             '<meta name="keywords" content="'.$seo_kws.'">' . "\n" . 
             '<meta property="og:title" content="'.$seo_title.'">' . "\n" . 
             '<meta property="og:description" content="'.$seo_desc.'">';
$header_html = str_replace('</head>', $meta_tags . "\n</head>", $header_html);

echo $header_html;
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
    /* Reset & Base Locks (To prevent horizontal scroll) */
    html { scroll-behavior: smooth; }
    body, html { margin: 0; padding: 0; overflow-x: hidden; background-color: #f8fafc; }
    
    .terms-page-container * { box-sizing: border-box; }
    .terms-page-container {
        font-family: 'Inter', sans-serif;
        color: #0f172a;
        width: 100vw;
        max-width: 100%;
        overflow-x: hidden;
        padding-bottom: 100px;
    }

    .text-gradient-purple {
        background: linear-gradient(135deg, #4f46e5 0%, #d946ef 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Animation Classes */
    .reveal { opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.5, 0, 0, 1); }
    .reveal.active { opacity: 1; transform: translateY(0); }

    /* Hero Section */
    .terms-hero {
        padding: 140px 20px 80px 20px;
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
    .hero-title { font-size: 3.5rem; font-weight: 900; line-height: 1.2; margin-bottom: 20px; letter-spacing: -1px; color: #0f172a; }
    .hero-desc { font-size: 1.15rem; color: #64748b; line-height: 1.7; font-weight: 500; }
    .last-updated { margin-top: 25px; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 20px; border-radius: 12px; font-size: 0.9rem; font-weight: 700; color: #475569; }

    /* Main Layout Grid */
    .terms-wrapper {
        max-width: 1300px;
        margin: 60px auto 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 50px;
        align-items: start;
        width: 100%;
    }

    /* Sidebar Navigation */
    .toc-sidebar {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        padding: 30px 25px;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px; 
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
    /* Hide scrollbar for sidebar */
    .toc-sidebar::-webkit-scrollbar { width: 4px; }
    .toc-sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    
    .toc-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; }
    .toc-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
    .toc-link {
        display: block; padding: 12px 16px; color: #64748b; font-weight: 600; font-size: 0.95rem;
        text-decoration: none; border-radius: 12px; transition: all 0.2s ease; border-left: 3px solid transparent;
        line-height: 1.4;
    }
    .toc-link:hover { background: #f8fafc; color: #4f46e5; border-left-color: #c7d2fe; }
    .toc-link.active { background: #eef2ff; color: #4f46e5; border-left-color: #4f46e5; font-weight: 800; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.05); }

    /* Content Area */
    .legal-content {
        background: #ffffff;
        padding: 50px 60px;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.04);
        width: 100%;
    }
    
    .legal-section { margin-bottom: 60px; scroll-margin-top: 120px; }
    .legal-section:last-child { margin-bottom: 0; }
    
    .legal-section h2 { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-bottom: 25px; padding-bottom: 12px; border-bottom: 2px solid #f8fafc; display: flex; align-items: center; gap: 12px; }
    .legal-section h2 i { color: #4f46e5; font-size: 1.5rem; }
    
    .legal-section p { font-size: 1.05rem; color: #475569; line-height: 1.85; margin-bottom: 20px; }
    .legal-section ul { padding-left: 25px; margin-bottom: 25px; }
    .legal-section li { font-size: 1.05rem; color: #475569; line-height: 1.8; margin-bottom: 12px; position: relative; list-style-type: disc; }
    .legal-section li::marker { color: #4f46e5; font-size: 1.2rem; }
    .legal-section strong { color: #1e293b; font-weight: 700; }

    .highlight-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 25px; border-radius: 0 16px 16px 0; margin: 30px 0; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.05); }
    .highlight-box p { margin: 0; color: #92400e; font-size: 1rem; font-weight: 600; line-height: 1.6; }

    .policy-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 16px; margin-bottom: 20px; }
    .policy-card h3 { margin: 0 0 10px 0; font-size: 1.2rem; color: #0f172a; font-weight: 800; }
    .policy-card p { margin: 0; font-size: 0.95rem; }

    /* Responsive Constraints */
    @media (max-width: 1024px) {
        .terms-wrapper { grid-template-columns: 280px 1fr; gap: 30px; }
        .legal-content { padding: 40px; }
    }
    
    @media (max-width: 850px) {
        .terms-wrapper { grid-template-columns: 1fr; display: flex; flex-direction: column; gap: 40px; }
        .toc-sidebar { position: static; top: 0; max-height: none; width: 100%; order: 1; margin-bottom: 20px; }
        .legal-content { order: 2; padding: 30px 25px; }
        .hero-title { font-size: 2.5rem; }
    }
    
    @media (max-width: 480px) {
        .hero-title { font-size: 2.2rem; }
        .legal-section h2 { font-size: 1.5rem; }
        .legal-section p, .legal-section li { font-size: 0.95rem; }
    }
</style>

<div class="terms-page-container">
    <section class="terms-hero reveal">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <span class="hero-tag">Legal Document</span>
            <h1 class="hero-title">Terms & <span class="text-gradient-purple">Conditions</span></h1>
            <p class="hero-desc">Welcome to LikexFollow. Please review our comprehensive legal policies, service guidelines, and user agreements carefully before utilizing our platform.</p>
            <div class="last-updated"><i class="far fa-calendar-check me-2"></i> Effective Date: May 2026</div>
        </div>
    </section>

    <div class="terms-wrapper">
        
        <aside class="toc-sidebar reveal">
            <div class="toc-title"><i class="fas fa-layer-group me-2"></i> Quick Navigation</div>
            <ul class="toc-list">
                <li><a href="#general" class="toc-link active">1. General Overview</a></li>
                <li><a href="#registration" class="toc-link">2. Account Registration</a></li>
                <li><a href="#smm-services" class="toc-link">3. SMM Panel Services</a></li>
                <li><a href="#digital-store" class="toc-link">4. Digital Store Policies</a></li>
                <li><a href="#payments" class="toc-link">5. Payments & Balances</a></li>
                <li><a href="#refund-policy" class="toc-link">6. Refund & Cancellation</a></li>
                <li><a href="#liability" class="toc-link">7. Limitation of Liability</a></li>
                <li><a href="#privacy" class="toc-link">8. Privacy & Data</a></li>
                <li><a href="#copyright" class="toc-link">9. Copyrights</a></li>
                <li><a href="#contact-legal" class="toc-link">10. Contact & Disputes</a></li>
            </ul>
        </aside>

        <div class="legal-content">
            
            <div id="general" class="legal-section reveal">
                <h2><i class="fas fa-file-contract"></i> 1. General Overview</h2>
                <p>By accessing, registering, and placing an order on <strong>LikexFollow</strong>, you confirm that you have read, understood, and agreed to all the terms listed on this page. If you do not agree with any of these terms, you are prohibited from using our services.</p>
                <p>We reserve the right to alter, modify, or update these terms of service at any time without prior notice. Continued use of the website following any changes constitutes your acceptance of the new terms.</p>
                <p>You agree to use LikexFollow only for lawful purposes and in a manner that complies with the individual Terms of Service of platforms like Instagram, Facebook, TikTok, YouTube, and others.</p>
            </div>

            <div id="registration" class="legal-section reveal">
                <h2><i class="fas fa-user-shield"></i> 2. Account Registration & Security</h2>
                <p>To use our platform, you must register for an account. By registering, you agree to the following:</p>
                <ul>
                    <li>You must provide accurate, current, and complete information during the registration process.</li>
                    <li>You are solely responsible for maintaining the confidentiality of your account credentials (email and password).</li>
                    <li>You agree to notify us immediately of any unauthorized use of your account.</li>
                    <li>LikexFollow will not be liable for any loss or damage arising from your failure to safeguard your account.</li>
                    <li>Creating multiple accounts to abuse free services or referral systems is strictly prohibited and will result in an IP ban.</li>
                </ul>
            </div>

            <div id="smm-services" class="legal-section reveal">
                <h2><i class="fas fa-chart-line"></i> 3. SMM Panel Services & Usage</h2>
                <p>LikexFollow provides automated social media growth services. Our rates are dynamic and subject to change based on market conditions without prior notice.</p>
                
                <div class="policy-card">
                    <h3>Delivery Estimations</h3>
                    <p>All delivery times mentioned on the service pages are purely estimates. We do not guarantee exact delivery times. Orders cannot be canceled or refunded simply because they are taking longer than estimated.</p>
                </div>

                <ul>
                    <li><strong>Account Privacy:</strong> Ensure your social media accounts are set to <strong>PUBLIC</strong> before ordering. Orders placed on private accounts will be marked as completed by the system, and no refund will be issued.</li>
                    <li><strong>Concurrent Orders:</strong> Do not place multiple orders for the exact same link simultaneously on our panel or any other panel. The system will count the start count incorrectly, and we will not refund for under-delivery in such cases.</li>
                    <li><strong>Refills & Drops:</strong> Services with a "Refill" tag come with a warranty. If a drop occurs within the warranty period, we will refill it. "No Refill" services are cheap but come with zero warranty if followers drop.</li>
                </ul>
            </div>

            <div id="digital-store" class="legal-section reveal">
                <h2><i class="fas fa-store"></i> 4. Premium Digital Store Policies</h2>
                <p>Our digital store offers premium subscriptions, software licenses, and digital assets. Purchases from the store are subject to specific rules:</p>
                <ul>
                    <li><strong>Instant Delivery:</strong> Assets are delivered automatically. Ensure your email address is correct.</li>
                    <li><strong>Warranty Constraints:</strong> Shared accounts (e.g., Shared Netflix, Shared Canva) come with strict instructions. If you attempt to change the password, email, or billing details of a shared account, your warranty will be voided instantly and access will be revoked.</li>
                    <li><strong>Updates:</strong> We are not responsible for third-party software updates that may break the functionality of provided APKs or scripts after the initial purchase period.</li>
                </ul>
            </div>

            <div id="payments" class="legal-section reveal">
                <h2><i class="fas fa-wallet"></i> 5. Payments, Deposits & Balances</h2>
                <p>You must fund your LikexFollow account before placing orders. We offer secure gateways like JazzCash, Easypaisa, Bank Transfer, and Crypto.</p>
                <div class="highlight-box">
                    <p><i class="fas fa-exclamation-circle me-2"></i> Important: Once funds are deposited into your LikexFollow account, they cannot be withdrawn or transferred back to your bank or card. Funds must be utilized for services on the platform.</p>
                </div>
                <p>Any attempt to file a fraudulent chargeback, dispute, or claim after receiving services will result in immediate termination of your account. Furthermore, we reserve the right to reverse the delivered followers/likes from your social media profiles.</p>
            </div>

            <div id="refund-policy" class="legal-section reveal">
                <h2><i class="fas fa-undo-alt"></i> 6. Refund & Cancellation Policy</h2>
                <p>We believe in a transparent ecosystem. Our refund rules are strict but fair:</p>
                <ul>
                    <li>Orders once placed cannot be canceled or modified manually by the user.</li>
                    <li>If an order cannot be delivered due to an internal server issue or API failure, the exact order amount will be refunded directly to your LikexFollow dashboard balance.</li>
                    <li>Misplaced orders (e.g., putting a YouTube link in an Instagram service) will not qualify for a refund. The system will attempt to process it and mark it as completed.</li>
                    <li>Partial deliveries will be partially refunded to your dashboard balance based on the delivered amount.</li>
                </ul>
            </div>

            <div id="liability" class="legal-section reveal">
                <h2><i class="fas fa-balance-scale"></i> 7. Limitation of Liability</h2>
                <p>LikexFollow acts strictly as an intermediary marketing platform.</p>
                <p>We are in no way liable for any account suspension, photo deletion, or shadow-banning done by Instagram, Twitter, Facebook, YouTube, TikTok, or any other platform. You utilize our services at your own risk. We simply deliver the requested traffic/metrics.</p>
            </div>

            <div id="privacy" class="legal-section reveal">
                <h2><i class="fas fa-user-secret"></i> 8. Privacy & Data Protection</h2>
                <p>Your privacy is paramount. We employ robust 256-bit encryption protocols.</p>
                <ul>
                    <li>We do not sell, rent, or trade your email address or order history to third-party marketing firms.</li>
                    <li>Payment details (Credit Card numbers) are processed securely via verified gateways and are never stored on LikexFollow servers.</li>
                    <li>We only utilize your data to fulfill your orders and send essential administrative updates.</li>
                </ul>
            </div>

            <div id="copyright" class="legal-section reveal">
                <h2><i class="fas fa-copyright"></i> 9. Intellectual Property</h2>
                <p>All content included on this site, such as text, graphics, logos, button icons, images, audio clips, and digital downloads is the property of LikexFollow and protected by international copyright laws. Unauthorized reproduction or scraping of our website layout or data is strictly prohibited.</p>
            </div>

            <div id="contact-legal" class="legal-section reveal">
                <h2><i class="fas fa-envelope"></i> 10. Contact & Disputes</h2>
                <p>If you have any questions regarding these Terms of Service, or if you wish to report a violation, please reach out to our legal and support team.</p>
                <p>Email: <strong>likexfollow.com@gmail.com</strong><br>
                WhatsApp Support: <strong>0315-4922709</strong></p>
                <p>Any legal disputes arising out of the use of our services will be governed by the applicable laws of Pakistan.</p>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- 1. Live Scroll Animations (Fade Up) ---
        const reveals = document.querySelectorAll('.reveal');
        
        const revealOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };
        
        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target); // Only animate once
                }
            });
        }, revealOptions);
        
        reveals.forEach(reveal => {
            revealOnScroll.observe(reveal);
        });

        // --- 2. ScrollSpy Logic for Sidebar Navigation ---
        const sections = document.querySelectorAll(".legal-section");
        const navLinks = document.querySelectorAll(".toc-link");

        window.addEventListener("scroll", () => {
            let current = "";

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 200)) {
                    current = section.getAttribute("id");
                }
            });

            navLinks.forEach(link => {
                link.classList.remove("active");
                if (link.getAttribute("href").includes(current)) {
                    link.classList.add("active");
                }
            });
        });

        // --- 3. Smooth Scroll offset for TOC links ---
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetSection = document.getElementById(targetId);
                
                if(targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 100, // Offset for top margin
                        behavior: "smooth"
                    });
                }
            });
        });
    });
</script>

<?php 
// Standard Public Footer Included
if(file_exists('user/_footer.php')) {
    include 'user/_footer.php'; 
} else {
    echo "</body></html>";
}
?>
