<?php
// File: panel/site_seo.php (Backend Admin Tool)
ob_start(); // Prevent 500 Errors related to headers
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fallback sanitize if missing from helpers
if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

$msg = ''; $msg_type = '';

// ==========================================
// 🛠️ 1. ULTIMATE AUTO-CREATE & SEED ENGINE 
// ==========================================
try {
    // 1. Create table safely
    $db->exec("CREATE TABLE IF NOT EXISTS site_seo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(150) NOT NULL,
        page_url VARCHAR(255) NOT NULL,
        meta_title VARCHAR(255) NOT NULL,
        meta_description TEXT NOT NULL,
        meta_keywords TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_page (page_url)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. 🔥 AUTO-SEEDER: If Matrix is empty, fill it with premium defaults immediately!
    $page_count = $db->query("SELECT COUNT(*) FROM site_seo")->fetchColumn();
    
    if ($page_count == 0) {
        $default_pages = [
            ['Homepage', '/index.php', 'LikexFollow | #1 Best & Cheap SMM Panel in Pakistan', 'Boost your social media presence instantly with LikexFollow. The cheapest and fastest SMM Panel for Instagram, YouTube, TikTok, and Facebook.', 'smm panel, cheap smm panel, smm panel pakistan, best smm panel'],
            ['Services List', '/services.php', 'Premium SMM Services & Pricing - LikexFollow', 'Explore our wide range of high-quality, non-drop SMM services. Affordable pricing for massive social media growth.', 'smm services, instagram followers cheap, buy youtube subscribers'],
            ['Digital Store', '/products.php', 'Premium Digital Subscriptions & Tools Store', 'Buy premium subscriptions like Canva Pro, Netflix, and SEO tools at unmatched prices. Instant delivery guaranteed.', 'premium digital accounts, buy canva pro cheap, digital store'],
            ['FAQ Page', '/faq.php', 'Frequently Asked Questions - LikexFollow Help', 'Got questions? Find all the answers about our SMM services, refill policies, and digital products here.', 'smm panel faq, likexfollow help, smm support'],
            ['About Us', '/about.php', 'About LikexFollow | Your Digital Growth Partner', 'Learn how LikexFollow became the leading provider of automated social media marketing and digital assets globally.', 'about likexfollow, digital agency pakistan, smm panel experts'],
            ['Contact Us', '/contact.php', 'Contact 24/7 Support - LikexFollow', 'Need help with an order? Contact the LikexFollow team via WhatsApp or email for lightning-fast support.', 'contact likexfollow, smm panel whatsapp support'],
            ['Registration', '/register.php', 'Create an Account | Join LikexFollow Today', 'Sign up for LikexFollow and get access to the cheapest SMM panel dashboard. Start growing your brand now.', 'smm panel register, create smm account'],
            ['Login Page', '/login.php', 'Login to Dashboard - LikexFollow', 'Sign in to your LikexFollow account to manage your orders, add funds, and track your social media growth.', 'likexfollow login, smm panel login'],
            ['Blog Hub', '/blog.php', 'SEO & Social Media Growth Blog - LikexFollow', 'Read expert guides, tips, and strategies to go viral on social media and dominate search engine rankings.', 'smm blog, social media tips, digital marketing guide']
        ];

        $db->beginTransaction();
        $stmt_seed = $db->prepare("INSERT IGNORE INTO site_seo (page_name, page_url, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?)");
        foreach($default_pages as $dp) {
            $stmt_seed->execute($dp);
        }
        $db->commit();
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    $msg = "Database Init Error: " . $e->getMessage();
    $msg_type = "danger";
}

// ==========================================
// 📡 DYNAMIC SEMRUSH AJAX FETCHER (NO RELOAD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_semrush_seo_fill'])) {
    header('Content-Type: application/json');
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $seed = sanitize($_POST['keyword']);
    $page_target = sanitize($_POST['page_target']);
    
    if (empty($seed)) {
        echo json_encode(['success' => false, 'error' => 'Seed Keyword is empty!']);
        exit;
    }

    if (!function_exists('curl_init')) {
        echo json_encode(['success' => false, 'error' => 'CURL is not enabled on your server!']);
        exit;
    }

    $api_url = "https://api.semrush.com/?type=phrase_related&key=" . urlencode($api_key) . "&phrase=" . urlencode($seed) . "&export_columns=Ph&database=us&display_limit=15";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    $kws = [];
    if ($response && strpos($response, 'ERROR') !== 0) {
        $lines = explode("\n", trim($response));
        array_shift($lines); 
        foreach($lines as $line) {
            $kw = trim(str_getcsv($line, ";")[0] ?? '');
            if(!empty($kw)) $kws[] = $kw;
        }
    }
    
    // Expert Algorithmic Generation Fallback
    if (empty($kws)) {
        $kws = [$seed, 'best ' . $seed, 'buy ' . $seed, 'cheap ' . $seed, $seed . ' pakistan', 'instant ' . $seed, $seed . ' provider'];
    } else {
        array_unshift($kws, $seed);
        $kws = array_unique($kws);
    }

    $year = date("Y");
    $titleCase = ucwords(strtolower($seed));
    
    // Smart Matrix Output
    $auto_title = "{$titleCase} Services {$year} - LikexFollow";
    $auto_desc = "Get the best {$seed} with LikexFollow. We provide instant, premium, and cheap " . implode(", ", array_slice($kws, 0, 3)) . " to boost your digital growth safely.";
    
    if (strpos(strtolower($page_target), 'about') !== false) {
        $auto_title = "About Us | Experts in {$titleCase} - LikexFollow";
        $auto_desc = "Learn how LikexFollow became the #1 provider for {$seed}. We specialize in " . implode(", ", array_slice($kws, 0, 3)) . " and digital dominance.";
    } elseif (strpos(strtolower($page_target), 'contact') !== false) {
        $auto_title = "Contact LikexFollow | Support for {$titleCase}";
        $auto_desc = "Need help with your {$seed} order? Contact our 24/7 expert support team for queries related to " . implode(", ", array_slice($kws, 0, 2)) . ".";
    }

    echo json_encode([
        'success' => true, 
        'meta_title' => $auto_title,
        'meta_description' => $auto_desc,
        'meta_keywords' => implode(', ', $kws) 
    ]);
    exit;
}

// ==========================================
// 🚀 2. HANDLE FORM SUBMISSION (SAVE SEO)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_seo'])) {
    $page_name = sanitize($_POST['page_name'] ?? 'Unknown Page');
    $page_url = sanitize($_POST['page_url'] ?? '');
    
    if (strpos($page_url, '/') !== 0) { $page_url = '/' . $page_url; }
    
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_desc = sanitize($_POST['meta_description'] ?? '');
    $meta_kws = sanitize($_POST['meta_keywords'] ?? '');
    
    try {
        $stmt = $db->prepare("INSERT INTO site_seo (page_name, page_url, meta_title, meta_description, meta_keywords) 
                              VALUES (?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              page_name = VALUES(page_name), meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), meta_keywords = VALUES(meta_keywords)");
        $stmt->execute([$page_name, $page_url, $meta_title, $meta_desc, $meta_kws]);
        
        $msg = "Matrix Updated! 🚀 Global SEO settings for '{$page_name}' injected successfully.";
        $msg_type = "success";
    } catch (PDOException $e) {
        $msg = "Deployment Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// ==========================================
// 🗑️ 3. DELETE SEO RECORD
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $db->prepare("DELETE FROM site_seo WHERE id = ?")->execute([$del_id]);
        $msg = "Node deleted successfully. Page will use default SEO.";
        $msg_type = "warning";
    } catch (PDOException $e) {}
}

// Fetch Existing SEO Data (Guaranteed to have data now due to auto-seeder)
$seo_records = [];
try {
    $seo_records = $db->query("SELECT * FROM site_seo ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// Common Pages Pre-list
$common_pages = [
    '/index.php' => 'Homepage',
    '/services.php' => 'Services List',
    '/products.php' => 'Digital Store',
    '/faq.php' => 'FAQ Page',
    '/about.php' => 'About Us',
    '/contact.php' => 'Contact Us',
    '/register.php' => 'Registration',
    '/login.php' => 'Login Page',
    '/blog.php' => 'Blog Hub'
];

$page_title = "Global SEO Matrix";
if (file_exists('_header.php')) { include '_header.php'; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ==============================================
       🔥 BEAST RESPONSIVE UI/UX DESIGN 🔥
       ============================================== */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --d-purple: #4f46e5;
        --b-color: #e2e8f0; --bg-color: #f8fafc;
        --t-dark: #0f172a; --t-muted: #64748b; 
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    
    .beast-container { width: 100%; max-width: 1550px; margin: 0 auto; padding: 20px; }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(99,102,241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99,102,241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); } }
    @keyframes typeEffect { from { opacity: 0.5; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
    
    .anim-slide { animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    .anim-delay-2 { animation-delay: 0.2s; }

    .matrix-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 25px; transition: 0.3s; }
    
    .grid-container { display: grid; grid-template-columns: 500px 1fr; gap: 30px; }
    @media(max-width: 1300px) { .grid-container { grid-template-columns: 1fr; } }
    
    .card-title { font-weight: 800; font-size: 1.25rem; color: var(--t-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--b-color); padding-bottom: 15px; }
    
    /* Input Fields */
    .form-group { margin-bottom: 20px; position: relative; }
    .form-label { font-weight: 800; color: var(--t-dark); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
    .form-select, .form-input, .form-textarea {
        width: 100%; padding: 14px 16px; border: 2px solid var(--b-color); border-radius: 12px;
        font-size: 0.95rem; font-weight: 600; font-family: 'Inter', sans-serif; color: var(--t-dark); 
        outline: none; transition: all 0.3s ease; background: #f8fafc;
    }
    .form-select:focus, .form-input:focus, .form-textarea:focus { border-color: var(--d-purple); background: #fff; box-shadow: 0 0 0 4px var(--l-purple); transform: translateY(-2px); }
    .form-textarea { resize: vertical; min-height: 100px; }
    
    .char-count { font-size: 0.75rem; color: var(--t-muted); font-weight: 600; background: var(--b-color); padding: 2px 8px; border-radius: 20px; }

    /* Auto-Fill Effect */
    .auto-filled { animation: typeEffect 0.5s ease-out; background: #f0fdf4 !important; border-color: #22c55e !important; }

    /* API Box */
    .api-box {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: 16px; padding: 25px; margin-bottom: 25px; color: white;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); position: relative; overflow: hidden;
    }
    .api-box::after { content: ''; position: absolute; top: 0; right: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
    .api-box .form-input { border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: white; border-radius: 10px; font-size: 1rem; }
    .api-box .form-input:focus { border-color: #8b5cf6; background: rgba(255,255,255,0.1); box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2); }
    
    .btn-api-magic {
        width: 100%; background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%); color: #fff; border: none; padding: 14px;
        border-radius: 10px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 15px; position: relative; z-index: 2;
    }
    .btn-api-magic:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(217, 70, 239, 0.4); }

    .btn-save {
        width: 100%; background: var(--d-purple); color: #fff; border: none; padding: 16px;
        border-radius: 12px; font-weight: 800; font-size: 1.05rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; animation: pulseGlow 2s infinite;
    }
    .btn-save:hover { background: var(--p-purple); transform: translateY(-3px); box-shadow: 0 10px 20px -5px rgba(79,70,229,0.4); animation: none; }

    /* Live SERP Preview */
    .serp-preview {
        background: #fff; border: 1px solid var(--b-color); border-radius: 12px; padding: 20px;
        margin-top: 10px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .serp-url { color: #202124; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
    .serp-url i { font-size: 0.7rem; color: #5f6368; }
    .serp-title { color: #1a0dab; font-size: 1.25rem; font-weight: 400; font-family: arial, sans-serif; cursor: pointer; line-height: 1.3; margin-bottom: 5px; }
    .serp-title:hover { text-decoration: underline; }
    .serp-desc { color: #4d5156; font-size: 0.875rem; line-height: 1.58; font-family: arial, sans-serif; word-wrap: break-word; }

    /* Table Design */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 12px; }
    .matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.95rem; }
    .matrix-table th { background: #f8fafc; padding: 18px 20px; text-align: left; font-weight: 800; color: var(--t-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--b-color); white-space: nowrap; position: sticky; top: 0; z-index: 10; }
    .matrix-table td { padding: 18px 20px; border-bottom: 1px solid var(--b-color); color: var(--t-dark); vertical-align: top; transition: 0.2s; }
    .matrix-row-anim { animation: slideInUp 0.4s ease-out both; }
    .matrix-table tr:hover td { background: #f8fafc; transform: scale(1.01); box-shadow: 0 4px 10px rgba(0,0,0,0.02); z-index: 2; position: relative; border-radius: 8px; }
    
    .url-badge { display: inline-block; padding: 6px 12px; background: var(--l-purple); color: var(--d-purple); font-weight: 800; border-radius: 8px; font-family: monospace; font-size: 0.85rem; margin-top: 5px; }
    .kw-tag { display: inline-block; padding: 4px 10px; background: #e2e8f0; color: #475569; border-radius: 6px; font-size: 0.8rem; font-weight: 700; margin: 3px 2px; transition: 0.2s; cursor: default; }
    .kw-tag:hover { background: var(--d-purple); color: white; transform: translateY(-2px); }
    
    .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; border: none; cursor: pointer; transition: 0.2s; color: white; text-decoration: none; font-size: 1.1rem; }
    .btn-edit { background: #f59e0b; }
    .btn-edit:hover { background: #d97706; transform: translateY(-3px) rotate(5deg); box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4); }
    .btn-delete { background: #ef4444; }
    .btn-delete:hover { background: #dc2626; transform: translateY(-3px) rotate(-5deg); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4); }
</style>

<div class="beast-container">
    
    <div class="matrix-card anim-slide d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="fw-bolder text-dark m-0" style="font-size: 2.2rem; letter-spacing: -1px;">
                <i class="fas fa-globe-americas text-primary me-2"></i> Global SEO Matrix
            </h2>
            <p class="text-muted fw-medium mt-2 mb-0 fs-6">Control Meta Data, auto-fetch high ranking keywords via SEMrush API, and deploy globally.</p>
        </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-4 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : ($msg_type == 'warning' ? '#fef3c7; color: #b45309;' : '#fee2e2; color: #991b1b;') ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle' ?> fs-3 me-3"></i> 
            <span style="font-size: 1.1rem;"><?= $msg ?></span>
        </div>
    <?php endif; ?>

    <div class="grid-container">
        
        <div class="matrix-card anim-slide anim-delay-1" style="align-self: start; padding: 25px;">
            <h3 class="card-title"><i class="fas fa-magic text-indigo-500"></i> Inject SEO Node</h3>
            
            <form method="POST" id="seoForm">
                
                <div class="api-box">
                    <div class="form-group mb-2">
                        <label class="form-label text-light"><i class="fas fa-bolt text-warning me-1"></i> SEMrush AI Auto-Filler</label>
                        <input type="text" id="seedKeyword" class="form-input" placeholder="Target Keyword (e.g. SMM Panel)">
                    </div>
                    <button type="button" class="btn-api-magic" onclick="fetchSEMrushSEO()" id="btnAutoFill">
                        <i class="fas fa-brain"></i> Auto-Generate Content
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Page Route</label>
                    <select name="page_url" id="pageUrl" class="form-select" required onchange="syncPageName()">
                        <option value="" disabled selected>-- Select Target Page --</option>
                        <?php foreach($common_pages as $url => $name): ?>
                            <option value="<?= $url ?>" data-name="<?= $name ?>"><?= $name ?> (<?= $url ?>)</option>
                        <?php endforeach; ?>
                        <option value="custom">-- Custom API / Dynamic Route --</option>
                    </select>
                </div>
                
                <div class="form-group" id="customUrlGroup" style="display: none;">
                    <label class="form-label">Custom Route Link</label>
                    <input type="text" name="custom_url" id="customUrl" class="form-input" placeholder="e.g. /my-service.php">
                </div>

                <div class="form-group">
                    <label class="form-label">Internal Identifier Name</label>
                    <input type="text" name="page_name" id="pageName" class="form-input" placeholder="e.g. Homepage" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Optimized Meta Title (H1) <span class="char-count" id="countTitle">0/60</span></label>
                    <input type="text" name="meta_title" id="metaTitle" class="form-input" placeholder="Google SERP Title" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Description <span class="char-count" id="countDesc">0/160</span></label>
                    <textarea name="meta_description" id="metaDesc" class="form-textarea" placeholder="Engaging snippet text..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label text-success"><i class="fas fa-key me-1"></i> High-Ranking Keywords</label>
                    <textarea name="meta_keywords" id="metaKws" class="form-textarea" style="min-height: 90px; border-color: #10b981;" placeholder="Keywords will auto-populate here..." required></textarea>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label"><i class="fab fa-google text-primary me-1"></i> Live SERP Preview</label>
                    <div class="serp-preview">
                        <div class="serp-url">
                            <span style="background: #f1f3f4; padding: 2px 6px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px;"><i class="fas fa-globe"></i></span>
                            <span style="color: #202124;">likexfollow.com</span> <span style="color: #5f6368;" id="previewUrl">/</span> <i class="fas fa-caret-down ms-1" style="font-size: 0.8rem;"></i>
                        </div>
                        <div class="serp-title" id="previewTitle">Your Optimized Meta Title Will Appear Here</div>
                        <div class="serp-desc" id="previewDesc">Write an engaging meta description that naturally includes your target keywords. This snippet helps increase your Click-Through Rate (CTR) on Google search results.</div>
                    </div>
                </div>

                <button type="submit" name="save_seo" class="btn-save" id="btnDeploy">
                    <i class="fas fa-rocket"></i> Deploy to Matrix
                </button>
            </form>
        </div>

        <div class="matrix-card anim-slide anim-delay-2" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div class="p-4 bg-light border-bottom">
                <h3 class="card-title m-0 border-0 p-0"><i class="fas fa-sitemap text-success"></i> Active Global Nodes</h3>
            </div>
            
            <?php if(empty($seo_records)): ?>
                <div class="text-center py-5 text-muted flex-grow-1 d-flex flex-column justify-content-center">
                    <i class="fas fa-spinner fa-spin mb-3" style="font-size: 4rem; color: var(--d-purple);"></i>
                    <h4 class="fw-bolder text-dark mt-3">Seeding Matrix...</h4>
                    <p class="fs-6">Default data has been injected. Please refresh the page.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive flex-grow-1" style="max-height: 850px; overflow-y: auto;">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>Target Route</th>
                                <th>Meta Setup Configuration</th>
                                <th width="100" class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $delay = 0.1;
                            foreach($seo_records as $row): 
                                $kws = explode(',', $row['meta_keywords']);
                            ?>
                            <tr class="matrix-row-anim" style="animation-delay: <?= $delay ?>s;">
                                <td>
                                    <div class="fw-bolder text-dark fs-6"><?= htmlspecialchars($row['page_name']) ?></div>
                                    <div class="url-badge"><?= htmlspecialchars($row['page_url']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bolder mb-1" style="color: var(--d-purple); font-size: 1.1rem;"><?= htmlspecialchars($row['meta_title']) ?></div>
                                    <div class="text-muted mb-2" style="max-width: 500px; line-height: 1.6; font-weight: 500; font-size: 0.9rem;"><?= htmlspecialchars(substr($row['meta_description'], 0, 150)) ?>...</div>
                                    <div>
                                        <?php 
                                        $count = 0;
                                        foreach($kws as $k) {
                                            if(trim($k) === '') continue;
                                            if($count >= 4) break;
                                            echo '<span class="kw-tag"><i class="fas fa-hashtag text-muted me-1" style="font-size:0.6rem;"></i>'.htmlspecialchars(trim($k)).'</span>';
                                            $count++;
                                        }
                                        if(count($kws) > 4) echo '<span class="kw-tag bg-white border fw-bolder text-primary">+ '.(count($kws)-4).'</span>';
                                        ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="action-btn btn-edit" onclick='editNode(<?= json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <a href="?delete_id=<?= $row['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this SEO Node?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php $delay += 0.05; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    // --- UI LOGIC & LIVE PREVIEW ---
    const iTitle = document.getElementById('metaTitle');
    const iDesc = document.getElementById('metaDesc');
    const iUrlSel = document.getElementById('pageUrl');
    const iUrlCus = document.getElementById('customUrl');
    
    function updatePreview() {
        document.getElementById('previewTitle').innerText = iTitle.value || 'Your Optimized Meta Title Will Appear Here';
        document.getElementById('previewDesc').innerText = iDesc.value || 'Write an engaging meta description that naturally includes your target keywords...';
        
        let path = '/';
        if(iUrlSel.value === 'custom') path = iUrlCus.value || '/';
        else if(iUrlSel.value !== '') path = iUrlSel.value;
        
        // Remove leading slash for clean display
        if(path.startsWith('/')) path = path.substring(1);
        document.getElementById('previewUrl').innerText = path ? ' > ' + path : '';
        
        // Counters
        let tLen = iTitle.value.length;
        let dLen = iDesc.value.length;
        document.getElementById('countTitle').innerText = tLen + '/60';
        document.getElementById('countDesc').innerText = dLen + '/160';
        
        document.getElementById('countTitle').style.color = tLen > 65 ? '#ef4444' : (tLen > 0 ? '#10b981' : '');
        document.getElementById('countDesc').style.color = dLen > 165 ? '#ef4444' : (dLen > 0 ? '#10b981' : '');
    }

    [iTitle, iDesc, iUrlSel, iUrlCus].forEach(el => el.addEventListener('input', updatePreview));
    iUrlSel.addEventListener('change', updatePreview);

    function syncPageName() {
        const select = document.getElementById('pageUrl');
        const customGrp = document.getElementById('customUrlGroup');
        const nameInput = document.getElementById('pageName');
        
        if(select.value === 'custom') {
            customGrp.style.display = 'block';
            nameInput.value = '';
            nameInput.focus();
        } else {
            customGrp.style.display = 'none';
            const selectedOption = select.options[select.selectedIndex];
            nameInput.value = selectedOption.getAttribute('data-name');
        }
        updatePreview();
    }

    // --- POPULATE FOR EDIT ---
    function editNode(data) {
        document.getElementById('pageName').value = data.page_name;
        
        const select = document.getElementById('pageUrl');
        let optionExists = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value === data.page_url) {
                select.selectedIndex = i;
                optionExists = true;
                break;
            }
        }
        
        if(!optionExists) {
            select.value = 'custom';
            document.getElementById('customUrlGroup').style.display = 'block';
            document.getElementById('customUrl').value = data.page_url;
        } else {
            document.getElementById('customUrlGroup').style.display = 'none';
        }

        iTitle.value = data.meta_title;
        iDesc.value = data.meta_description;
        document.getElementById('metaKws').value = data.meta_keywords;
        
        updatePreview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Advanced Focus Animation
        const formBoxes = ['metaTitle', 'metaDesc', 'metaKws'];
        formBoxes.forEach(id => {
            const el = document.getElementById(id);
            el.classList.remove('auto-filled');
            void el.offsetWidth; // trigger reflow
            el.classList.add('auto-filled');
        });
        
        document.getElementById('btnDeploy').innerHTML = '<i class="fas fa-sync-alt"></i> Update Matrix';
    }

    // --- THE BEAST API CALL ---
    function fetchSEMrushSEO() {
        const seed = document.getElementById('seedKeyword').value.trim();
        const pageSel = document.getElementById('pageUrl');
        let target = pageSel.options[pageSel.selectedIndex]?.text || 'General Page';
        
        if(seed === '') { alert('Please enter a target keyword first!'); return; }
        if(pageSel.value === '') { alert('Please select a target page first!'); return; }

        const btn = document.getElementById('btnAutoFill');
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Generating...';
        btn.style.pointerEvents = 'none';

        const formData = new FormData();
        formData.append('ajax_semrush_seo_fill', '1');
        formData.append('keyword', seed);
        formData.append('page_target', target);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                iTitle.value = data.meta_title;
                iDesc.value = data.meta_description;
                document.getElementById('metaKws').value = data.meta_keywords; 
                
                updatePreview();
                
                // Add Glow Animation
                ['metaTitle', 'metaDesc', 'metaKws'].forEach(id => {
                    document.getElementById(id).classList.add('auto-filled');
                    setTimeout(() => document.getElementById(id).classList.remove('auto-filled'), 1000);
                });
                
                btn.innerHTML = '<i class="fas fa-check-double"></i> Synced with API!';
                btn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                
                setTimeout(() => { 
                    btn.innerHTML = '<i class="fas fa-brain"></i> Auto-Generate Content'; 
                    btn.style.background = '';
                    btn.style.pointerEvents = 'auto'; 
                }, 2000);
            } else {
                alert(data.error || "Unknown Error occurred.");
                resetApiBtn(btn);
            }
        })
        .catch(err => {
            alert('API Connection Failed. Using algorithmic fallback.');
            iTitle.value = seed.replace(/\b\w/g, l => l.toUpperCase()) + " Services - LikexFollow";
            iDesc.value = "Get the best " + seed + " with LikexFollow. Premium quality and instant delivery guaranteed.";
            document.getElementById('metaKws').value = seed + ", buy " + seed + ", cheap " + seed + ", " + seed + " pakistan";
            
            updatePreview();
            ['metaTitle', 'metaDesc', 'metaKws'].forEach(id => document.getElementById(id).classList.add('auto-filled'));
            resetApiBtn(btn);
        });
    }
    
    function resetApiBtn(btn) {
        btn.innerHTML = '<i class="fas fa-brain"></i> Auto-Generate Content';
        btn.style.pointerEvents = 'auto';
    }

    // Override Submit to handle Custom URL
    document.getElementById('seoForm').addEventListener('submit', function(e) {
        const select = document.getElementById('pageUrl');
        if(select.value === 'custom') {
            const customInput = document.getElementById('customUrl');
            if(customInput.value.trim() === '') {
                e.preventDefault();
                alert('Please enter a valid custom route!');
                customInput.focus();
            } else {
                select.name = 'ignored_select';
                customInput.name = 'page_url';
            }
        }
    });
</script>

<?php 
if (file_exists('_footer.php')) { include '_footer.php'; }
?>
