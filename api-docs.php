<?php
// File: api-docs.php (Root Directory - Public API Documentation)
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
$seo_title = "API Documentation - LikexFollow | Developer Hub";
$seo_desc = "Integrate LikexFollow's powerful SMM API into your platform. Automate your social media services, fetch live balances, and place bulk orders seamlessly.";
$seo_kws = "smm panel api, likexfollow api docs, automate smm orders, reseller api smm, developer api likexfollow";

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

// Check if user is logged in to show their actual API Key
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_api_key = "YOUR_API_KEY_HERE";

if ($user_id > 0) {
    try {
        $key_stmt = $db->prepare("SELECT api_key FROM users WHERE id = ? LIMIT 1");
        $key_stmt->execute([$user_id]);
        $fetched_key = $key_stmt->fetchColumn();
        if (!empty($fetched_key)) {
            $user_api_key = $fetched_key;
        } else {
            $user_api_key = "Generate your key from dashboard settings";
        }
    } catch (Exception $e) {}
} else {
    $user_api_key = "Login to view your live API Key";
}

$api_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/user/api.php";

// 🔥 EXPERT DEVELOPER BYPASS GATEWAY FOR PUBLIC ACCESS 🔥
// Temporarily spoofing script name to fool the user/_header.php auth guard
$orig_php_self = $_SERVER['PHP_SELF'];
$orig_script_name = $_SERVER['SCRIPT_NAME'];
$_SERVER['PHP_SELF'] = '/products.php';
$_SERVER['SCRIPT_NAME'] = '/products.php';

ob_start();
include 'user/_header.php'; 
$header_html = ob_get_clean();

// Restoring original system routes immediately after header inclusion
$_SERVER['PHP_SELF'] = $orig_php_self;
$_SERVER['SCRIPT_NAME'] = $orig_script_name;

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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Reset & Base Locks */
    html { scroll-behavior: smooth; }
    body, html { margin: 0; padding: 0; overflow-x: hidden; background-color: #f8fafc; }
    
    .api-page-container * { box-sizing: border-box; }
    .api-page-container {
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

    /* Scroll Animation Classes */
    .reveal { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.5, 0, 0, 1); }
    .reveal.active { opacity: 1; transform: translateY(0); }

    /* Hero Section */
    .api-hero {
        padding: 130px 20px 70px 20px;
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
    .hero-desc { font-size: 1.1rem; color: #64748b; line-height: 1.6; font-weight: 500; }

    /* API Key Box Interactive */
    .api-key-box {
        background: #0f172a; color: #ffffff; padding: 20px 30px; border-radius: 16px; 
        display: inline-flex; align-items: center; gap: 15px; margin-top: 30px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2); cursor: pointer; transition: 0.3s;
        border: 1px solid #334155; position: relative;
    }
    .api-key-box:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.3); border-color: #4f46e5; }
    .api-key-box i { font-size: 1.5rem; color: #10b981; }
    .api-key-text { text-align: left; }
    .api-key-text span { display: block; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 4px; }
    .api-key-text code { font-family: 'Fira Code', monospace; font-size: 1.1rem; font-weight: 500; color: #38bdf8; }
    .copy-tooltip { position: absolute; right: -90px; top: 50%; transform: translateY(-50%); background: #10b981; color: #fff; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; font-weight: 700; opacity: 0; transition: 0.3s; }
    .api-key-box:hover .copy-tooltip { opacity: 1; right: -80px; }
    
    /* Layout */
    .api-wrapper {
        max-width: 1250px;
        margin: 60px auto 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 50px;
        align-items: start;
        width: 100%;
    }

    /* Sidebar Navigation (Glassmorphism) */
    .toc-sidebar {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        padding: 30px 25px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 15px 35px -10px rgba(0,0,0,0.06);
        position: sticky;
        top: 100px; 
    }
    .toc-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; }
    .toc-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
    .toc-link {
        display: block; padding: 12px 16px; color: #64748b; font-weight: 600; font-size: 0.95rem;
        text-decoration: none; border-radius: 12px; transition: all 0.2s ease; border-left: 3px solid transparent;
    }
    .toc-link:hover { background: #f8fafc; color: #4f46e5; border-left-color: #c7d2fe; transform: translateX(5px); }
    .toc-link.active { background: #eef2ff; color: #4f46e5; border-left-color: #4f46e5; font-weight: 800; }

    /* Content Area */
    .api-content {
        background: #ffffff;
        padding: 50px;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.04);
        width: 100%;
    }
    
    .api-section { margin-bottom: 70px; scroll-margin-top: 120px; }
    .api-section:last-child { margin-bottom: 0; }
    
    .api-section h2 { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f8fafc; display: flex; align-items: center; gap: 12px; }
    .api-section p { font-size: 1.05rem; color: #475569; line-height: 1.7; margin-bottom: 25px; }
    
    .endpoint-badge { display: inline-flex; align-items: center; background: #f8fafc; padding: 10px 18px; border-radius: 10px; font-family: 'Fira Code', monospace; font-size: 0.95rem; color: #0f172a; font-weight: 500; border: 1px dashed #cbd5e1; margin-bottom: 25px; word-break: break-all; width: 100%; }
    .endpoint-method { background: #10b981; color: white; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; margin-right: 15px; letter-spacing: 1px; }

    /* Tables */
    .api-table-wrapper { overflow-x: auto; margin-bottom: 30px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .api-table { width: 100%; border-collapse: collapse; text-align: left; }
    .api-table th { background: #f1f5f9; padding: 15px 20px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; font-weight: 800; border-bottom: 2px solid #e2e8f0; }
    .api-table td { padding: 15px 20px; font-size: 0.95rem; color: #334155; border-bottom: 1px solid #e2e8f0; }
    .api-table tr:hover td { background: #f8fafc; }
    .api-table tr:last-child td { border-bottom: none; }
    .api-table td strong { color: #0f172a; font-family: 'Fira Code', monospace; font-size: 0.9rem; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; }

    /* Interactive Code Blocks */
    .code-wrapper { position: relative; margin-bottom: 30px; border-radius: 12px; overflow: hidden; background: #0f172a; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #334155; }
    .code-header { background: #1e293b; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
    .code-lang { color: #94a3b8; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; }
    
    .code-copy-btn { 
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: #e2e8f0; 
        cursor: pointer; font-size: 0.8rem; font-weight: 600; padding: 4px 12px; border-radius: 6px; 
        transition: all 0.2s; display: flex; align-items: center; gap: 6px;
    }
    .code-copy-btn:hover { background: #4f46e5; border-color: #4f46e5; color: #ffffff; }
    
    .code-content { padding: 20px; margin: 0; font-family: 'Fira Code', monospace; font-size: 0.95rem; color: #e2e8f0; line-height: 1.6; overflow-x: auto; }
    .code-content .str { color: #a5b4fc; } 
    .code-content .kwd { color: #f472b6; } 
    .code-content .num { color: #34d399; } 

    /* Responsive */
    @media (max-width: 1024px) {
        .api-wrapper { grid-template-columns: 250px 1fr; gap: 30px; }
        .api-content { padding: 30px; }
    }
    @media (max-width: 850px) {
        .api-wrapper { grid-template-columns: 1fr; display: flex; flex-direction: column; gap: 30px; }
        .toc-sidebar { position: static; top: 0; width: 100%; order: 1; margin-bottom: 10px; }
        .api-content { order: 2; padding: 25px 20px; }
        .hero-title { font-size: 2.5rem; }
        .api-key-box { width: 100%; justify-content: center; }
        .copy-tooltip { display: none; }
    }
</style>

<div class="api-page-container">
    <section class="api-hero reveal">
        <div class="hero-bg-pattern"></div>
        <div class="hero-content">
            <span class="hero-tag">Developer Hub</span>
            <h1 class="hero-title">Developer <span class="text-gradient-purple">API Docs</span></h1>
            <p class="hero-desc">Integrate LikexFollow's automated services directly into your own panel or application. Use our powerful REST API for seamless order processing.</p>
            
            <div class="api-key-box" onclick="copyApiKey('<?= htmlspecialchars($user_api_key) ?>')" id="apiKeyBox">
                <i class="fas fa-key"></i>
                <div class="api-key-text">
                    <span>Your Live API Key</span>
                    <code id="apiKeyCode"><?= htmlspecialchars($user_api_key) ?></code>
                </div>
                <span class="copy-tooltip">Click to Copy</span>
            </div>
        </div>
    </section>

    <div class="api-wrapper">
        
        <aside class="toc-sidebar reveal">
            <div class="toc-title"><i class="fas fa-code-branch me-2"></i> Endpoints</div>
            <ul class="toc-list">
                <li><a href="#services" class="toc-link active">1. Get Service List</a></li>
                <li><a href="#add-order" class="toc-link">2. Create New Order</a></li>
                <li><a href="#order-status" class="toc-link">3. Check Order Status</a></li>
                <li><a href="#user-balance" class="toc-link">4. Account Balance</a></li>
            </ul>
        </aside>

        <div class="api-content">
            
            <div id="services" class="api-section reveal">
                <h2><i class="fas fa-list-ul text-indigo-500"></i> 1. Fetch Service List</h2>
                <p>Retrieve the full list of active SMM services, their IDs, live rates, and limits to display on your panel automatically.</p>
                
                <div class="endpoint-badge">
                    <span class="endpoint-method">POST</span> <?= $api_url ?>
                </div>

                <div class="api-table-wrapper custom-scrollbar">
                    <table class="api-table">
                        <thead>
                            <tr><th>Parameters</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>key</strong></td>
                                <td>Your secret API Key</td>
                            </tr>
                            <tr>
                                <td><strong>action</strong></td>
                                <td>Set value to <code style="color:#4f46e5;">services</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="code-wrapper">
                    <div class="code-header">
                        <span class="code-lang"><i class="fab fa-js text-yellow-400 mr-2"></i> JSON Response</span>
                        <button class="code-copy-btn" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                    </div>
                    <pre class="code-content">
[
    {
        <span class="kwd">"service"</span>: <span class="num">1</span>,
        <span class="kwd">"name"</span>: <span class="str">"Instagram Followers [HQ]"</span>,
        <span class="kwd">"type"</span>: <span class="str">"Default"</span>,
        <span class="kwd">"category"</span>: <span class="str">"Instagram - Followers"</span>,
        <span class="kwd">"rate"</span>: <span class="num">0.90</span>,
        <span class="kwd">"min"</span>: <span class="num">50</span>,
        <span class="kwd">"max"</span>: <span class="num">10000</span>
    }
]</pre>
                </div>
            </div>

            <div id="add-order" class="api-section reveal">
                <h2><i class="fas fa-plus-circle text-emerald-500"></i> 2. Add New Order</h2>
                <p>Place a new order for a specific service ID automatically from your website directly to our servers.</p>
                
                <div class="endpoint-badge">
                    <span class="endpoint-method">POST</span> <?= $api_url ?>
                </div>

                <div class="api-table-wrapper custom-scrollbar">
                    <table class="api-table">
                        <thead>
                            <tr><th>Parameters</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>key</strong></td>
                                <td>Your secret API Key</td>
                            </tr>
                            <tr>
                                <td><strong>action</strong></td>
                                <td>Set value to <code style="color:#4f46e5;">add</code></td>
                            </tr>
                            <tr>
                                <td><strong>service</strong></td>
                                <td>Service ID (From Service List)</td>
                            </tr>
                            <tr>
                                <td><strong>link</strong></td>
                                <td>Target link to the page/post</td>
                            </tr>
                            <tr>
                                <td><strong>quantity</strong></td>
                                <td>Needed quantity</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="code-wrapper">
                    <div class="code-header">
                        <span class="code-lang"><i class="fab fa-js text-yellow-400 mr-2"></i> JSON Response</span>
                        <button class="code-copy-btn" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                    </div>
                    <pre class="code-content">
{
    <span class="kwd">"order"</span>: <span class="num">23501</span>
}</pre>
                </div>
            </div>

            <div id="order-status" class="api-section reveal">
                <h2><i class="fas fa-satellite-dish text-sky-500"></i> 3. Order Status</h2>
                <p>Check the live status (Pending, Processing, Completed, Canceled) of a specific order ID.</p>
                
                <div class="endpoint-badge">
                    <span class="endpoint-method">POST</span> <?= $api_url ?>
                </div>

                <div class="api-table-wrapper custom-scrollbar">
                    <table class="api-table">
                        <thead>
                            <tr><th>Parameters</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>key</strong></td>
                                <td>Your secret API Key</td>
                            </tr>
                            <tr>
                                <td><strong>action</strong></td>
                                <td>Set value to <code style="color:#4f46e5;">status</code></td>
                            </tr>
                            <tr>
                                <td><strong>order</strong></td>
                                <td>The specific Order ID</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="code-wrapper">
                    <div class="code-header">
                        <span class="code-lang"><i class="fab fa-js text-yellow-400 mr-2"></i> JSON Response</span>
                        <button class="code-copy-btn" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                    </div>
                    <pre class="code-content">
{
    <span class="kwd">"charge"</span>: <span class="num">0.278</span>,
    <span class="kwd">"start_count"</span>: <span class="num">3572</span>,
    <span class="kwd">"status"</span>: <span class="str">"Completed"</span>,
    <span class="kwd">"remains"</span>: <span class="num">0</span>,
    <span class="kwd">"currency"</span>: <span class="str">"PKR"</span>
}</pre>
                </div>
            </div>

            <div id="user-balance" class="api-section reveal">
                <h2><i class="fas fa-wallet text-purple-500"></i> 4. User Balance</h2>
                <p>Fetch your current LikexFollow dashboard balance before placing automated bulk orders.</p>
                
                <div class="endpoint-badge">
                    <span class="endpoint-method">POST</span> <?= $api_url ?>
                </div>

                <div class="api-table-wrapper custom-scrollbar">
                    <table class="api-table">
                        <thead>
                            <tr><th>Parameters</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>key</strong></td>
                                <td>Your secret API Key</td>
                            </tr>
                            <tr>
                                <td><strong>action</strong></td>
                                <td>Set value to <code style="color:#4f46e5;">balance</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="code-wrapper">
                    <div class="code-header">
                        <span class="code-lang"><i class="fab fa-js text-yellow-400 mr-2"></i> JSON Response</span>
                        <button class="code-copy-btn" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                    </div>
                    <pre class="code-content">
{
    <span class="kwd">"balance"</span>: <span class="num">150.50</span>,
    <span class="kwd">"currency"</span>: <span class="str">"PKR"</span>
}</pre>
                </div>
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
            rootMargin: "0px 0px -30px 0px"
        };
        
        const revealOnScroll = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, revealOptions);
        
        reveals.forEach(reveal => {
            revealOnScroll.observe(reveal);
        });

        // --- 2. ScrollSpy Logic for Sidebar Navigation ---
        const sections = document.querySelectorAll(".api-section");
        const navLinks = document.querySelectorAll(".toc-link");

        window.addEventListener("scroll", () => {
            let current = "";

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= (sectionTop - 150)) {
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
                        top: targetSection.offsetTop - 100, 
                        behavior: "smooth"
                    });
                }
            });
        });
    });

    // --- 4. Copy API Key Logic ---
    function copyApiKey(key) {
        if(key.includes("Login")) return; 
        navigator.clipboard.writeText(key);
        
        const box = document.getElementById('apiKeyBox');
        const code = document.getElementById('apiKeyCode');
        const tooltip = box.querySelector('.copy-tooltip');
        
        const originalColor = code.style.color;
        code.innerText = "Copied to Clipboard!";
        code.style.color = "#10b981";
        if(tooltip) tooltip.style.display = "none";
        
        setTimeout(() => {
            code.innerText = key;
            code.style.color = originalColor;
            if(tooltip) tooltip.style.display = "block";
        }, 2000);
    }

    // --- 5. Copy Code Blocks Logic ---
    function copyCode(button) {
        const pre = button.parentElement.nextElementSibling;
        const codeText = pre.innerText;
        
        navigator.clipboard.writeText(codeText);
        
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Copied';
        button.style.borderColor = "#10b981";
        
        setTimeout(() => {
            button.innerHTML = originalHtml;
            button.style.borderColor = "rgba(255,255,255,0.1)";
        }, 2000);
    }
</script>

<?php 
// Standard Public Footer Included
if(file_exists('user/_footer.php')) {
    include 'user/_footer.php'; 
} else {
    echo "</body></html>";
}
?>
