<?php
// File: faq.php (Root Directory - Public)
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
$seo_title = "FAQ - LikexFollow | Frequently Asked Questions";
$seo_desc = "Find answers to all your questions about LikexFollow SMM services, premium digital subscriptions, payment methods, and account security.";
$seo_kws = "faq likexfollow, smm panel help, how to buy followers, likexfollow support, digital store help";

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

// --- 📚 FAQ DATA (Auto-Generates Google Schema & HTML) ---
$faqs = [
    [
        "category" => "General",
        "question" => "What is LikexFollow?",
        "answer" => "LikexFollow is Pakistan's leading digital growth agency. We provide top-tier Social Media Marketing (SMM) services to boost your online presence, alongside a premium digital store for subscriptions like Netflix, Canva Pro, and premium SEO tools."
    ],
    [
        "category" => "SMM Services",
        "question" => "Are the followers and likes real?",
        "answer" => "We offer a variety of services ranging from high-quality bot accounts to 100% real and active targeted users. You can choose the exact quality you need from the service description before placing an order."
    ],
    [
        "category" => "SMM Services",
        "question" => "What does 'Refill' mean?",
        "answer" => "Social media platforms sometimes update their algorithms, causing a slight drop in followers. Services with a 'Refill Guarantee' mean that if your count drops within the specified period (e.g., 30 Days), we will restore them for free."
    ],
    [
        "category" => "Digital Store",
        "question" => "How will I receive my premium subscription?",
        "answer" => "Once your payment is confirmed, your premium subscription details (Email & Password or Invite Link) will be automatically sent to your registered email address and displayed in your LikexFollow dashboard."
    ],
    [
        "category" => "Digital Store",
        "question" => "What if my digital product stops working?",
        "answer" => "All our premium digital subscriptions come with a full warranty for the purchased duration. If you face any issues, simply contact our WhatsApp support team with your Order ID, and we will replace or fix it immediately."
    ],
    [
        "category" => "Payments",
        "question" => "What payment methods do you accept?",
        "answer" => "We accept a wide range of local and international payment methods including JazzCash, Easypaisa, Bank Transfers, SadaPay, Nayapay, PerfectMoney, Binance (Crypto), and major Credit/Debit Cards."
    ],
    [
        "category" => "General",
        "question" => "Is my account safe? Do you need my password?",
        "answer" => "Your account is 100% safe. We NEVER ask for your social media passwords for any SMM service. We only need your public profile link or post URL to deliver the services."
    ]
];

// Generate JSON-LD Schema for Google Rich Snippets
$schema_items = [];
foreach ($faqs as $faq) {
    $schema_items[] = [
        "@type" => "Question",
        "name" => $faq['question'],
        "acceptedAnswer" => [
            "@type" => "Answer",
            "text" => strip_tags($faq['answer'])
        ]
    ];
}
$faq_schema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => $schema_items
];

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
    
    $schema_json = "<script type='application/ld+json'>\n" . json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>";
    
    // Inject the fully automated API SEO Tags + Local JSON Schema
    $header_html = str_ireplace('</head>', $beast_seo_injection . "\n" . $schema_json . "\n</head>", $header_html);
} else {
    $header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$seo_title</title>", $header_html);
}
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
    /* Reset & Base */
    .faq-page-container * { box-sizing: border-box; }
    .faq-page-container {
        font-family: 'Inter', sans-serif;
        background-color: #f8fafc;
        color: #0f172a;
        width: 100%;
        overflow-x: hidden;
        padding-bottom: 100px;
    }

    .text-gradient-purple {
        background: linear-gradient(135deg, #4f46e5 0%, #d946ef 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Hero Section */
    .faq-hero {
        padding: 120px 20px 80px 20px;
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
    .hero-title { font-size: 3.2rem; font-weight: 900; line-height: 1.2; margin-bottom: 20px; letter-spacing: -1px; color: #0f172a; }
    .hero-desc { font-size: 1.15rem; color: #64748b; line-height: 1.6; font-weight: 500; margin-bottom: 40px; }

    /* Live Search Box */
    .search-box-wrapper { max-width: 600px; margin: 0 auto; position: relative; }
    .search-input {
        width: 100%; padding: 18px 25px 18px 55px; border: 2px solid #e2e8f0;
        border-radius: 50px; background: #ffffff; font-size: 1.1rem; font-weight: 600;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); transition: all 0.3s ease; color: #0f172a;
    }
    .search-input:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 4px #eef2ff; }
    .search-icon { position: absolute; left: 22px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.2rem; }

    /* FAQ Layout */
    .faq-wrapper { max-width: 850px; margin: 60px auto 0 auto; padding: 0 20px; width: 100%; }

    .faq-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        margin-bottom: 15px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .faq-item.active { border-color: #c7d2fe; box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.1); }

    .faq-question {
        padding: 22px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
        background: #ffffff;
        transition: background 0.3s ease;
    }
    .faq-item.active .faq-question { background: #f8fafc; }
    
    .faq-question h3 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.4; padding-right: 20px; }
    
    .faq-toggle-icon {
        width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; color: #4f46e5;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .faq-item.active .faq-toggle-icon { transform: rotate(180deg); background: #4f46e5; color: #ffffff; }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), padding 0.4s ease;
        padding: 0 25px;
        background: #ffffff;
    }
    .faq-item.active .faq-answer { padding: 0 25px 25px 25px; }
    .faq-answer p { margin: 0; color: #475569; font-size: 1rem; line-height: 1.7; border-top: 1px dashed #e2e8f0; padding-top: 15px; }

    /* Badge inside FAQ */
    .faq-cat-badge { font-size: 0.7rem; font-weight: 800; color: #8b5cf6; background: #f3e8ff; padding: 3px 10px; border-radius: 50px; margin-bottom: 8px; display: inline-block; text-transform: uppercase; }

    /* No Results */
    .no-results { display: none; text-align: center; padding: 40px; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1; }
    .no-results i { font-size: 3rem; color: #94a3b8; margin-bottom: 15px; }
    .no-results h3 { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
    .no-results p { color: #64748b; margin: 0; }

    /* Responsive */
    @media (max-width: 768px) {
        .hero-title { font-size: 2.4rem; }
        .faq-question h3 { font-size: 1.05rem; }
    }
</style>

<div class="faq-page-container">
    <section class="faq-hero">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <span class="hero-tag">Help Center</span>
            <h1 class="hero-title">Frequently Asked <span class="text-gradient-purple">Questions</span></h1>
            <p class="hero-desc">
                <?php 
                $desc = "Everything you need to know about our services, digital store, and billing. Can't find the answer? Feel free to contact our support team.";
                echo function_exists('auto_spider_link') ? auto_spider_link($desc, $db) : $desc; 
                ?>
            </p>
            
            <div class="search-box-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="faqSearchInput" class="search-input" placeholder="Type your question here...">
            </div>
        </div>
    </section>

    <div class="faq-wrapper" id="faqContainer">
        
        <?php foreach($faqs as $index => $faq): ?>
        <div class="faq-item faq-searchable">
            <div class="faq-question" onclick="toggleFaq(this)">
                <div>
                    <span class="faq-cat-badge"><?= htmlspecialchars($faq['category']) ?></span>
                    <h3 class="faq-q-text"><?= htmlspecialchars($faq['question']) ?></h3>
                </div>
                <div class="faq-toggle-icon">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="faq-answer">
                <p class="faq-a-text">
                    <?php 
                    $ans = htmlspecialchars($faq['answer']);
                    echo function_exists('auto_spider_link') ? auto_spider_link($ans, $db) : $ans; 
                    ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="no-results" id="noResultsBox">
            <i class="far fa-frown-open"></i>
            <h3>No results found</h3>
            <p>We couldn't find any FAQs matching your search.</p>
        </div>

    </div>
</div>

<script>
    // Smooth Accordion Logic
    function toggleFaq(element) {
        const item = element.parentElement;
        const answer = element.nextElementSibling;
        const isActive = item.classList.contains('active');

        // Close all other FAQs
        document.querySelectorAll('.faq-item').forEach(faq => {
            faq.classList.remove('active');
            faq.querySelector('.faq-answer').style.maxHeight = null;
        });

        // Toggle current FAQ
        if (!isActive) {
            item.classList.add('active');
            answer.style.maxHeight = answer.scrollHeight + 30 + "px"; // +30 for padding
        }
    }

    // Live AI Search Filter
    document.getElementById('faqSearchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        let visibleCount = 0;
        
        document.querySelectorAll('.faq-searchable').forEach(item => {
            const questionText = item.querySelector('.faq-q-text').innerText.toLowerCase();
            const answerText = item.querySelector('.faq-a-text').innerText.toLowerCase();
            
            if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
                // Close if it was open while hidden
                item.classList.remove('active');
                item.querySelector('.faq-answer').style.maxHeight = null;
            }
        });

        const noResults = document.getElementById('noResultsBox');
        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
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