<?php
// File: panel/expert_seo_workspace.php
// EXPERT SEO WORKSPACE - Developed for: Israr Liaqat
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '_header.php';

// Safe check for DB and Helpers
if (!isset($db) && file_exists('../includes/db.php')) { require_once '../includes/db.php'; }
if (!function_exists('sanitize') && file_exists('../includes/helpers.php')) { require_once '../includes/helpers.php'; }

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE & UPGRADE SEO TABLE ---
try {
    // Core Table Creation
    $db->exec("CREATE TABLE IF NOT EXISTS panel_seo_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(100) NOT NULL,
        page_slug VARCHAR(100) NOT NULL UNIQUE,
        seo_title VARCHAR(255),
        seo_desc TEXT,
        seo_keywords TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Upgrade Schema for Expert SEO (Silently adds columns if missing)
    try { $db->exec("ALTER TABLE panel_seo_settings ADD COLUMN og_image VARCHAR(255) DEFAULT '' AFTER seo_keywords"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE panel_seo_settings ADD COLUMN canonical_url VARCHAR(255) DEFAULT '' AFTER og_image"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE panel_seo_settings ADD COLUMN robots_meta VARCHAR(50) DEFAULT 'index, follow' AFTER canonical_url"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE panel_seo_settings ADD COLUMN page_content LONGTEXT DEFAULT NULL AFTER robots_meta"); } catch (Exception $e) {}

    // Insert Default Pages if table is empty
    $count = $db->query("SELECT COUNT(*) FROM panel_seo_settings")->fetchColumn();
    if ($count == 0) {
        $default_pages = [
            ['Home Page', 'index', 'LikexFollow - #1 SMM Panel', 'Best and cheapest SMM panel for TikTok, Instagram and YouTube.', 'smm panel, cheap smm panel, likexfollow', '', '', 'index, follow', ''],
            ['Services Page', 'services', 'Our Services - LikexFollow', 'Check out our high-quality, non-drop SMM services with lifetime guarantee.', 'smm services, buy instagram likes, tiktok followers', '', '', 'index, follow', ''],
            ['API Page', 'api', 'Developer API - LikexFollow', 'Integrate our powerful SMM Panel API into your website.', 'smm panel api, smm reseller api', '', '', 'index, follow', ''],
            ['Sign Up Page', 'signup', 'Sign Up - LikexFollow', 'Join the world\'s fastest SMM panel today.', 'smm panel signup, register smm', '', '', 'index, follow', ''],
            ['Login Page', 'login', 'Login - LikexFollow', 'Login to your SMM panel account.', 'smm login', '', '', 'index, follow', ''],
            ['FAQ Page', 'faq', 'FAQ - LikexFollow', 'Frequently asked questions about our SMM panel.', 'smm faq', '', '', 'index, follow', ''],
            ['Terms Page', 'terms', 'Terms & Conditions - LikexFollow', 'Read our terms of service.', 'smm terms', '', '', 'noindex, follow', '']
        ];
        $stmt = $db->prepare("INSERT INTO panel_seo_settings (page_name, page_slug, seo_title, seo_desc, seo_keywords, og_image, canonical_url, robots_meta, page_content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($default_pages as $dp) { $stmt->execute($dp); }
    }

    // Force check and insert missing public pages (like Products and Blog) if they don't exist
    $critical_pages = [
        ['Products Page', 'products', 'Premium Products - LikexFollow', 'Buy premium streaming accounts, Canva Pro, CapCut Pro and more at cheap prices.', 'premium accounts, netflix cheap, canva pro buy', '', '', 'index, follow', ''],
        ['Blog', 'blog', 'Blog & Updates - LikexFollow', 'Read our latest updates, social media growth tricks, and SEO optimized blogs.', 'smm blog, social media updates, likexfollow news', '', '', 'index, follow', '']
    ];
    $check_stmt = $db->prepare("SELECT COUNT(*) FROM panel_seo_settings WHERE page_slug = ?");
    $insert_stmt = $db->prepare("INSERT INTO panel_seo_settings (page_name, page_slug, seo_title, seo_desc, seo_keywords, og_image, canonical_url, robots_meta, page_content) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($critical_pages as $cp) {
        $check_stmt->execute([$cp[1]]);
        if ($check_stmt->fetchColumn() == 0) {
            $insert_stmt->execute($cp);
        }
    }

    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
    $message = "DB Error: " . $e->getMessage();
    $msg_type = "danger";
}

if(!function_exists('sanitize')){ 
    function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } 
}

// --- 1. HANDLE SEO UPDATE (EXPERT FIELDS + CONTENT INCLUDED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seo'])) {
    $id = (int)$_POST['page_id'];
    $title = sanitize($_POST['seo_title']);
    $desc = sanitize($_POST['seo_desc']);
    $keywords = sanitize($_POST['seo_keywords']);
    $og_image = sanitize($_POST['og_image']);
    $canonical = sanitize($_POST['canonical_url']);
    $robots = sanitize($_POST['robots_meta']);
    
    // NO STRIP TAGS for Page Content so SEO expert can use HTML formatting (H1, H2, Bold, etc.)
    $page_content = trim($_POST['page_content'] ?? '');

    $stmt = $db->prepare("UPDATE panel_seo_settings SET seo_title = ?, seo_desc = ?, seo_keywords = ?, og_image = ?, canonical_url = ?, robots_meta = ?, page_content = ? WHERE id = ?");
    if($stmt->execute([$title, $desc, $keywords, $og_image, $canonical, $robots, $page_content, $id])) {
        $message = "SEO Configuration & Page Content deployed successfully! 🚀";
        $msg_type = "success";
    } else {
        $message = "Failed to update SEO tags.";
        $msg_type = "danger";
    }
}

// --- FETCH ALL PAGES ---
$seo_pages = [];
if($table_exists) {
    $seo_pages = $db->query("SELECT * FROM panel_seo_settings ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Expert SEO Workspace";
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* ==============================================
       🔥 EXPERT SEO WORKSPACE UI/UX 🔥
       ============================================== */
    :root { 
        --p-purple: #6366f1; --l-purple: #eef2ff; --d-purple: #4f46e5;
        --bg-body: #f8fafc; --b-color: #e2e8f0; 
        --t-dark: #0f172a; --t-muted: #64748b; 
        --google-blue: #1a0dab; --google-green: #006621;
    }
    
    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .beast-container { width: 100%; max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    @keyframes slideIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .anim-slide { animation: slideIn 0.4s ease forwards; }

    .ui-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
    
    /* Clean Header layout */
    .workspace-header { 
        display: flex; justify-content: space-between; align-items: center; 
        padding-bottom: 0; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
    }
    
    .btn-seo-settings {
        background: #fff; color: var(--t-dark); border: 1px solid var(--b-color); 
        padding: 10px 20px; border-radius: 10px; font-weight: 600; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .btn-seo-settings:hover { background: #f8fafc; color: var(--p-purple); border-color: var(--p-purple); }

    .table-responsive { overflow-x: auto; border-radius: 12px; border: 1px solid var(--b-color); }
    .table { width: 100%; margin: 0; border-collapse: collapse; }
    .table th { background: #f8fafc; padding: 15px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; color: var(--t-muted); border-bottom: 1px solid var(--b-color); letter-spacing: 0.5px; }
    .table td { padding: 18px 15px; vertical-align: top; border-bottom: 1px solid var(--b-color); font-weight: 500; color: var(--t-dark); }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover td { background: #fbfbfc; }

    .slug-badge { background: var(--l-purple); color: var(--d-purple); padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-family: monospace; font-weight: 700; border: 1px solid rgba(99, 102, 241, 0.2); }
    
    /* Live SERP Preview Styling */
    .serp-card { background: #fff; border: 1px solid var(--b-color); padding: 12px 15px; border-radius: 10px; max-width: 450px; }
    .serp-url { font-size: 0.8rem; color: #202124; display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .serp-url span { background: #f1f3f4; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; }
    .serp-title-text { color: var(--google-blue); font-weight: 400; font-size: 1.1rem; text-decoration: none; margin-bottom: 3px; font-family: arial, sans-serif; display: block;}
    .serp-title-text:hover { text-decoration: underline; }
    .serp-desc-text { color: #4d5156; font-size: 0.85rem; font-family: arial, sans-serif; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .meta-tag-pill { background: #f1f5f9; color: var(--t-muted); padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; margin-right: 5px; margin-top: 5px; display: inline-block;}

    .btn-edit { background: #fff; color: var(--d-purple); border: 2px solid var(--p-purple); padding: 8px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; white-space: nowrap; box-shadow: 0 4px 6px rgba(99,102,241,0.1); }
    .btn-edit:hover { background: var(--p-purple); color: #fff; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(99,102,241,0.2); }

    /* Modal Layout (Scroll fixed for UI/UX) */
    .modal-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px); z-index: 9999; 
        display: none; align-items: center; justify-content: center; opacity: 0; transition: 0.3s;
        padding: 15px; 
    }
    .modal-overlay.show { display: flex; opacity: 1; }
    
    .seo-modal { 
        background: #fff; width: 100%; max-width: 850px; border-radius: 20px; 
        box-shadow: 0 25px 50px rgba(0,0,0,0.3); transform: scale(0.95); transition: 0.3s; 
        display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; /* Prevents going out of screen */
    }
    .modal-overlay.show .seo-modal { transform: scale(1); }
    
    .modal-header { padding: 20px 25px; border-bottom: 1px solid var(--b-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    
    /* Scrollable body */
    .modal-body { 
        padding: 25px; overflow-y: auto; background: #fff; 
        display: grid; grid-template-columns: 1fr 1fr; gap: 30px; 
        flex-grow: 1; -webkit-overflow-scrolling: touch;
    }
    
    .modal-footer { padding: 20px 25px; border-top: 1px solid var(--b-color); background: #f8fafc; display: flex; justify-content: flex-end; flex-shrink: 0; }
    
    .modal-close { background: none; border: none; font-size: 1.5rem; color: var(--t-muted); cursor: pointer; transition: 0.2s;}
    .modal-close:hover { color: #ef4444; transform: rotate(90deg); }

    .form-group { margin-bottom: 20px; }
    .form-label { display: flex; justify-content: space-between; font-weight: 700; color: var(--t-dark); margin-bottom: 8px; font-size: 0.9rem; }
    .char-count { font-size: 0.75rem; font-weight: 600; color: var(--t-muted); }
    .char-count.error { color: #ef4444; }
    .char-count.good { color: #10b981; }

    .form-input { width: 100%; padding: 12px 15px; border: 2px solid var(--b-color); border-radius: 10px; font-weight: 500; font-family: 'Inter', sans-serif; transition: 0.3s; outline: none; background: #f8fafc;}
    .form-input:focus { border-color: var(--p-purple); background: #fff; box-shadow: 0 0 0 4px var(--l-purple); }
    textarea.form-input { resize: vertical; min-height: 110px; }
    select.form-input { appearance: none; background-image: url('data:image/svg+xml;utf8,<svg fill="%2364748b" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>'); background-repeat: no-repeat; background-position: right 10px center; }

    .btn-save { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 800; cursor: pointer; font-size: 1rem; transition: 0.3s; display: flex; align-items: center; gap: 10px;}
    .btn-save:hover { box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); transform: translateY(-2px); }

    /* Responsive Grid inside Modal */
    @media (max-width: 768px) {
        .modal-body { grid-template-columns: 1fr; gap: 15px; padding: 15px; }
        .modal-header { padding: 15px; }
        .modal-footer { padding: 15px; }
    }
</style>

<div class="beast-container">
    <div class="workspace-header">
        <h2 class="fw-bolder text-dark mb-0"><i class="fas fa-chart-line text-indigo-500 me-2"></i> Expert SEO Workspace</h2>
        <a href="seo_settings.php" class="btn-seo-settings"><i class="fas fa-cogs"></i> General SEO Settings</a>
    </div>

    <?php if ($message): ?> 
        <div class="alert fw-bold rounded-4 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>;">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 me-3"></i> 
            <span style="font-size: 0.95rem;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <div class="ui-card anim-slide" style="animation-delay: 0.1s; padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Target Page</th>
                        <th style="width: 50%;">Google SERP Preview (Live)</th>
                        <th style="width: 15%;">Directives</th>
                        <th style="width: 15%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($seo_pages)): ?>
                        <tr><td colspan="4" class="text-center py-4">No pages found in Database.</td></tr>
                    <?php endif; ?>
                    
                    <?php foreach($seo_pages as $page): ?>
                    <tr>
                        <td>
                            <div class="fw-bolder text-dark mb-2"><?= htmlspecialchars($page['page_name']) ?></div>
                            <span class="slug-badge">/<?= htmlspecialchars($page['page_slug']) ?></span>
                        </td>
                        <td>
                            <div class="serp-card">
                                <div class="serp-url">https://likexfollow.com/<?= htmlspecialchars($page['page_slug']) ?> <span>⋮</span></div>
                                <a href="#" class="serp-title-text" onclick="return false;"><?= htmlspecialchars($page['seo_title'] ?: 'Untitled Page') ?></a>
                                <div class="serp-desc-text"><?= htmlspecialchars($page['seo_desc'] ?: 'No meta description provided for this page. Add one to improve CTR.') ?></div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $robots = $page['robots_meta'] ?: 'index, follow';
                                $rob_color = strpos($robots, 'noindex') !== false ? '#fee2e2; color:#ef4444;' : '#dcfce7; color:#10b981;';
                            ?>
                            <span class="meta-tag-pill" style="background: <?= $rob_color ?> border:1px solid currentColor;">🤖 <?= htmlspecialchars($robots) ?></span>
                            <?php if(!empty($page['canonical_url'])): ?>
                                <span class="meta-tag-pill"><i class="fas fa-link"></i> Canonical</span>
                            <?php endif; ?>
                            <?php if(!empty($page['page_content'])): ?>
                                <span class="meta-tag-pill"><i class="fas fa-file-alt"></i> Has Content</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <textarea id="raw_content_<?= $page['id'] ?>" style="display:none;"><?= htmlspecialchars($page['page_content'] ?? '') ?></textarea>
                            
                            <button class="btn-edit" onclick="openSeoModal(<?= $page['id'] ?>, '<?= htmlspecialchars(addslashes($page['page_name'])) ?>', '<?= htmlspecialchars(addslashes($page['page_slug'])) ?>', '<?= htmlspecialchars(addslashes($page['seo_title'])) ?>', '<?= htmlspecialchars(addslashes($page['seo_desc'])) ?>', '<?= htmlspecialchars(addslashes($page['seo_keywords'])) ?>', '<?= htmlspecialchars(addslashes($page['og_image'])) ?>', '<?= htmlspecialchars(addslashes($page['canonical_url'])) ?>', '<?= htmlspecialchars(addslashes($page['robots_meta'])) ?>')">
                                <i class="fas fa-sliders-h me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="seoModal">
    <div class="seo-modal">
        <div class="modal-header">
            <div>
                <h4 class="fw-bolder mb-1 text-dark" id="modalTitle" style="font-size: 1.2rem;">Optimize Page</h4>
                <div class="slug-badge" id="modalSlug" style="font-size: 0.75rem;">/url</div>
            </div>
            <button class="modal-close" onclick="closeSeoModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" id="seoForm" style="display:flex; flex-direction:column; flex-grow:1; overflow:hidden;">
            <input type="hidden" name="update_seo" value="1">
            <input type="hidden" name="page_id" id="m_page_id" value="">
            
            <div class="modal-body">
                <div>
                    <h6 class="fw-bolder text-muted mb-3 text-uppercase" style="font-size:0.75rem; letter-spacing:1px;"><i class="fas fa-search me-1"></i> Core Meta Tags</h6>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Meta Title 
                            <span class="char-count" id="count_title">0 / 60</span>
                        </label>
                        <input type="text" name="seo_title" id="m_seo_title" class="form-input" placeholder="Primary Keyword - Brand Name" onkeyup="updateCounts()" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Meta Description 
                            <span class="char-count" id="count_desc">0 / 160</span>
                        </label>
                        <textarea name="seo_desc" id="m_seo_desc" class="form-input" placeholder="Write a compelling description to boost CTR in SERPs..." onkeyup="updateCounts()" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Target Keywords <span class="fw-normal text-muted" style="font-size:0.75rem;">(Comma separated)</span></label>
                        <input type="text" name="seo_keywords" id="m_seo_keywords" class="form-input" placeholder="e.g. buy followers, smm panel">
                    </div>
                </div>

                <div>
                    <h6 class="fw-bolder text-muted mb-3 text-uppercase" style="font-size:0.75rem; letter-spacing:1px;"><i class="fas fa-cog me-1"></i> Technical Directives</h6>
                    
                    <div class="form-group">
                        <label class="form-label">Robots Meta (Crawling)</label>
                        <select name="robots_meta" id="m_robots_meta" class="form-input">
                            <option value="index, follow">Index, Follow (Recommended)</option>
                            <option value="noindex, follow">NoIndex, Follow</option>
                            <option value="noindex, nofollow">NoIndex, NoFollow (Private)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Canonical URL <span class="fw-normal text-muted" style="font-size:0.75rem;">(Optional)</span></label>
                        <input type="url" name="canonical_url" id="m_canonical_url" class="form-input" placeholder="https://likexfollow.com/...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Open Graph Image URL <span class="fw-normal text-muted" style="font-size:0.75rem;">(Social Media Share)</span></label>
                        <input type="url" name="og_image" id="m_og_image" class="form-input" placeholder="https://likexfollow.com/assets/img/og-banner.jpg">
                    </div>
                </div>

                <div style="grid-column: 1 / -1; border-top: 1px dashed var(--b-color); padding-top: 20px; margin-top: 5px;">
                    <h6 class="fw-bolder text-muted mb-3 text-uppercase" style="font-size:0.75rem; letter-spacing:1px;"><i class="fas fa-file-code me-1"></i> On-Page Content (HTML / Text)</h6>
                    <div class="form-group mb-0">
                        <label class="form-label">Full Page Content Editor <span class="fw-normal text-muted" style="font-size:0.75rem;">(HTML formatting supported for H1, H2, Paragraphs)</span></label>
                        <textarea name="page_content" id="m_page_content" class="form-input" style="min-height: 200px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; line-height: 1.6;" placeholder="<h1>Welcome to LikexFollow</h1>&#10;<p>We provide the best SMM services...</p>"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light fw-bold me-3 px-4 py-2 rounded-3 text-muted border" onclick="closeSeoModal()">Cancel</button>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Setup</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSeoModal(id, name, slug, title, desc, keywords, og, canonical, robots) {
        document.getElementById('modalTitle').innerText = 'Optimize: ' + name;
        document.getElementById('modalSlug').innerText = '/' + slug;
        
        // Populate fields
        document.getElementById('m_page_id').value = id;
        document.getElementById('m_seo_title').value = title;
        document.getElementById('m_seo_desc').value = desc;
        document.getElementById('m_seo_keywords').value = keywords;
        document.getElementById('m_og_image').value = og;
        document.getElementById('m_canonical_url').value = canonical;
        
        // Fetch raw HTML content safely from hidden textarea to prevent JS syntax crash
        let rawContent = document.getElementById('raw_content_' + id).value;
        document.getElementById('m_page_content').value = rawContent;
        
        let robSelect = document.getElementById('m_robots_meta');
        robSelect.value = robots ? robots : 'index, follow';
        
        updateCounts(); // Initial trigger

        const modal = document.getElementById('seoModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function closeSeoModal() {
        const modal = document.getElementById('seoModal');
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    // Live Character Counting & Quality Indicator
    function updateCounts() {
        const title = document.getElementById('m_seo_title').value;
        const desc = document.getElementById('m_seo_desc').value;
        
        const cTitle = document.getElementById('count_title');
        const cDesc = document.getElementById('count_desc');

        cTitle.innerText = title.length + ' / 60';
        cDesc.innerText = desc.length + ' / 160';

        // Title logic (Optimal: 40-60)
        cTitle.className = 'char-count';
        if(title.length > 60) cTitle.classList.add('error');
        else if(title.length >= 40) cTitle.classList.add('good');

        // Desc logic (Optimal: 120-160)
        cDesc.className = 'char-count';
        if(desc.length > 160) cDesc.classList.add('error');
        else if(desc.length >= 120) cDesc.classList.add('good');
    }
</script>

<?php require_once '_footer.php'; ?>
