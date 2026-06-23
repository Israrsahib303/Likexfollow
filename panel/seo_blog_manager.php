<?php
// File: panel/seo_blog_manager.php (Backend Admin Tool)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// ==========================================
// 📡 DYNAMIC SEMRUSH AJAX FETCHER (NO RELOAD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_semrush_fetch'])) {
    header('Content-Type: application/json');
    $api_key = '0730bcb9667631f6d70e461adead1ad8'; // LikexFollow Official API
    $seed = sanitize($_POST['keyword']);
    
    if (empty($seed)) {
        echo json_encode(['success' => false, 'error' => 'Keyword is empty']);
        exit;
    }

    $api_url = "https://api.semrush.com/?type=phrase_related&key=" . urlencode($api_key) . "&phrase=" . urlencode($seed) . "&export_columns=Ph&database=us&display_limit=15";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    curl_close($ch);

    $lsis = [];
    if ($response && strpos($response, 'ERROR') !== 0) {
        $lines = explode("\n", trim($response));
        array_shift($lines); // Remove Header (Ph)
        foreach($lines as $line) {
            $kw = trim(str_getcsv($line, ";")[0] ?? '');
            if(!empty($kw) && strtolower($kw) !== strtolower($seed)) {
                $lsis[] = $kw;
            }
        }
        echo json_encode(['success' => true, 'lsis' => implode(', ', $lsis)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'API Error or No Data Found']);
    }
    exit;
}

$msg = ''; $msg_type = '';

// ==========================================
// 🛠️ 1. AUTO-CREATE TABLES (BULLETPROOF)
// ==========================================
try {
    $db->exec("CREATE TABLE IF NOT EXISTS seo_blog_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("CREATE TABLE IF NOT EXISTS seo_blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        category_id INT DEFAULT 0,
        content LONGTEXT,
        meta_title VARCHAR(255),
        meta_description VARCHAR(500),
        primary_keyword VARCHAR(255),
        lsi_keywords TEXT,
        featured_image VARCHAR(255),
        views INT DEFAULT 0,
        status INT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Insert default category if empty
    $cat_check = $db->query("SELECT COUNT(*) FROM seo_blog_categories")->fetchColumn();
    if ($cat_check == 0) {
        $db->exec("INSERT INTO seo_blog_categories (name, slug) VALUES ('Digital Marketing', 'digital-marketing'), ('Social Media Growth', 'social-media-growth'), ('Premium Subscriptions', 'premium-subscriptions')");
    }
} catch (PDOException $e) {
    $msg = "Database Error: " . $e->getMessage();
    $msg_type = "danger";
}

// ==========================================
// 🚀 2. HANDLE FORM SUBMISSION (PUBLISH BLOG)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_blog'])) {
    $title = sanitize($_POST['title']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
    $category_id = (int)$_POST['category_id'];
    $content = $_POST['content']; // Allowing HTML from ChatGPT
    $meta_title = sanitize($_POST['meta_title']);
    $meta_desc = sanitize($_POST['meta_description']);
    $primary_kw = sanitize($_POST['primary_keyword']);
    $lsi_kws = sanitize($_POST['lsi_keywords']);
    $status = (int)$_POST['status'];
    
    // Image Upload Logic
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'webp'];
        $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = '../assets/img/blog/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true); // Auto create folder
            $featured_image = 'blog_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $featured_image);
        }
    }

    try {
        $stmt = $db->prepare("INSERT INTO seo_blogs (title, slug, category_id, content, meta_title, meta_description, primary_keyword, lsi_keywords, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $category_id, $content, $meta_title, $meta_desc, $primary_kw, $lsi_kws, $featured_image, $status]);
        
        $msg = "Masterpiece Published! 🚀 Your highly optimized blog is now live.";
        $msg_type = "success";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $msg = "Error: A blog with this Slug already exists. Please change the slug.";
        } else {
            $msg = "Publishing Error: " . $e->getMessage();
        }
        $msg_type = "danger";
    }
}

// Fetch Categories for Dropdown
$categories = $db->query("SELECT * FROM seo_blog_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Load Admin Header
$page_title = "SEO Blog Manager";
if (file_exists('_header.php')) { include '_header.php'; }
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --app-bg: #f8fafc;
        --card-bg: #ffffff;
        --border: #e2e8f0;
        --primary: #4f46e5;
        --primary-light: #eef2ff;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }
    
    .editor-wrapper { max-width: 1600px; margin: 0 auto; padding: 20px; font-family: 'Inter', sans-serif; }
    
    .top-header {
        background: var(--card-bg); padding: 25px 30px; border-radius: 16px; margin-bottom: 25px;
        border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
    }
    .top-header h2 { font-weight: 800; color: var(--text-dark); margin: 0 0 5px 0; font-size: 1.8rem; }
    .top-header p { color: var(--text-muted); margin: 0; font-size: 0.95rem; }
    
    .grid-container { display: grid; grid-template-columns: 1fr 400px; gap: 25px; }
    @media(max-width: 1200px) { .grid-container { grid-template-columns: 1fr; } }
    
    .editor-card, .sidebar-card {
        background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px;
        padding: 25px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
    }
    
    .card-title { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
    
    .form-group { margin-bottom: 20px; position: relative; }
    .form-label { font-weight: 700; color: var(--text-dark); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
    .form-select, .form-input {
        width: 100%; padding: 12px 15px; border: 2px solid var(--border); border-radius: 10px;
        font-size: 0.95rem; font-family: 'Inter', sans-serif; color: var(--text-dark); outline: none; transition: 0.3s;
    }
    .form-select:focus, .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
    
    .content-textarea {
        width: 100%; height: 600px; border: 2px solid var(--border); border-radius: 12px;
        padding: 20px; font-size: 1rem; line-height: 1.6; color: var(--text-dark); outline: none; resize: vertical;
        background: #fafafa; font-family: 'Georgia', serif;
    }
    .content-textarea:focus { border-color: var(--primary); background: #fff; }

    /* SEO Progress Bars */
    .char-bar-wrapper { width: 100%; height: 6px; background: var(--border); border-radius: 10px; margin-top: 8px; overflow: hidden; }
    .char-bar { height: 100%; background: var(--success); transition: width 0.3s, background 0.3s; border-radius: 10px; width: 0%; }

    /* Live Scanner Box */
    .scanner-box { background: var(--primary-light); border: 1px dashed #c7d2fe; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .scanner-title { font-weight: 800; color: var(--primary); font-size: 0.9rem; margin-bottom: 15px; text-transform: uppercase; }
    
    .kw-pill {
        display: flex; justify-content: space-between; align-items: center;
        background: #fff; border: 1px solid var(--border); padding: 8px 12px;
        border-radius: 8px; margin-bottom: 10px; font-size: 0.85rem; font-weight: 700; transition: 0.3s;
    }
    .kw-missing { border-color: #fecaca; color: var(--danger); background: #fef2f2; }
    .kw-found { border-color: #a7f3d0; color: var(--success); background: #ecfdf5; box-shadow: 0 2px 5px rgba(16,185,129,0.1); }
    
    .kw-count { background: rgba(0,0,0,0.05); padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; }
    .kw-found .kw-count { background: #d1fae5; color: #065f46; }

    .btn-publish {
        width: 100%; background: var(--primary); color: #fff; border: none; padding: 16px;
        border-radius: 12px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 20px -5px rgba(79,70,229,0.4);
    }
    .btn-publish:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(79,70,229,0.5); }

    .btn-api-fetch { background: #0f172a; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-api-fetch:hover { background: var(--primary); transform: translateY(-1px); }
</style>

<div class="editor-wrapper">
    
    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> fw-bold mb-4 rounded-3 border-0 shadow-sm" style="padding:15px; border-radius:10px; background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="top-header">
        <div>
            <h2><i class="fas fa-feather-alt text-primary me-2"></i> Expert Publishing Studio</h2>
            <p>Paste your ChatGPT generated blog here. Use the <strong>SEMrush API Engine</strong> to auto-fetch LSI keywords and generate Meta Tags.</p>
        </div>
        <a href="blog_list.php" class="btn btn-publish" style="width: auto; padding: 10px 20px;">
            <i class="fas fa-list me-2"></i> Manage Blogs
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="grid-container">
            
            <div class="editor-card">
                <h3 class="card-title">
                    <span><i class="fas fa-edit text-indigo-500 me-2"></i> Core Content Setup</span>
                </h3>
                
                <div class="row" style="display:flex; gap:15px; margin-bottom:15px;">
                    <div class="col-md-6" style="flex:1;">
                        <div class="form-group mb-0">
                            <label class="form-label">Blog Title (H1)</label>
                            <input type="text" name="title" id="liveTitle" class="form-input" placeholder="e.g. 10 Best SMM Panels in Pakistan" required onkeyup="generateSlug()">
                        </div>
                    </div>
                    <div class="col-md-6" style="flex:1;">
                        <div class="form-group mb-0">
                            <label class="form-label">URL Slug</label>
                            <input type="text" name="slug" id="liveSlug" class="form-input" placeholder="e.g. 10-best-smm-panels-pakistan" required>
                        </div>
                    </div>
                </div>

                <div class="row" style="display:flex; gap:15px;">
                    <div class="col-md-6" style="flex:1;">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6" style="flex:1;">
                        <div class="form-group">
                            <label class="form-label">Featured Image</label>
                            <input type="file" name="featured_image" class="form-input bg-light" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label">Optimized Blog Content (Paste HTML from ChatGPT)</label>
                    <textarea name="content" id="liveContent" class="content-textarea" placeholder="<h1>Main Heading</h1><p>Start your masterpiece here...</p>" required></textarea>
                </div>
            </div>

            <div class="sidebar-card">
                <h3 class="card-title">
                    <span><i class="fas fa-satellite-dish text-success me-2"></i> Live API Scanner</span>
                </h3>
                
                <div class="form-group">
                    <label class="form-label text-primary">
                        Primary Keyword
                        <button type="button" class="btn-api-fetch" onclick="fetchSEMrushLSI()" id="btnFetchApi">
                            <i class="fas fa-bolt text-warning"></i> Fetch API LSI
                        </button>
                    </label>
                    <input type="text" name="primary_keyword" id="kwPrimary" class="form-input" placeholder="e.g. cheap smm panel" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label text-info">LSI Keywords (Comma Separated)</label>
                    <input type="text" name="lsi_keywords" id="kwLSI" class="form-input" placeholder="buy followers, instant delivery, jazzcash">
                </div>

                <div class="scanner-box">
                    <div class="scanner-title">Keyword Density Check</div>
                    <div id="scannerResults">
                        <div class="text-center text-muted small py-3 fw-bold">Enter keywords and paste content to start live scan...</div>
                    </div>
                </div>

                <hr style="border-top:1px dashed #cbd5e1; margin: 25px 0;">
                
                <h3 class="card-title">
                    <span><i class="fas fa-search text-warning me-2"></i> Search Snippet (Meta)</span>
                    <button type="button" class="btn-api-fetch bg-indigo-600" onclick="autoGenerateMeta()">
                        <i class="fas fa-magic"></i> Auto Generate
                    </button>
                </h3>

                <div class="form-group">
                    <label class="form-label">Meta Title <span id="metaTitleCount">0/60</span></label>
                    <input type="text" name="meta_title" id="metaTitle" class="form-input" placeholder="Catchy title for Google SERP" required>
                    <div class="char-bar-wrapper"><div class="char-bar" id="metaTitleBar"></div></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Meta Description <span id="metaDescCount">0/160</span></label>
                    <textarea name="meta_description" id="metaDesc" class="form-input" style="height: 100px; resize: none;" placeholder="Write a compelling snippet to increase CTR..." required></textarea>
                    <div class="char-bar-wrapper"><div class="char-bar" id="metaDescBar"></div></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Publish Status</label>
                    <select name="status" class="form-select fw-bold">
                        <option value="1" style="color: #10b981;">🟢 Publish Immediately</option>
                        <option value="0" style="color: #f59e0b;">🟠 Save as Draft</option>
                    </select>
                </div>

                <button type="submit" name="publish_blog" class="btn-publish mt-4">
                    <i class="fas fa-paper-plane"></i> Publish Masterpiece
                </button>
            </div>

        </div>
    </form>
</div>

<script>
    // --- 1. DYNAMIC API FETCHER (SEMRUSH HYBRID ENGINE) ---
    function fetchSEMrushLSI() {
        const seed = document.getElementById('kwPrimary').value.trim();
        const btn = document.getElementById('btnFetchApi');
        const lsiInput = document.getElementById('kwLSI');
        
        if(seed === '') { alert("Please enter a Primary Keyword first!"); return; }
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
        btn.style.pointerEvents = 'none';

        const formData = new FormData();
        formData.append('ajax_semrush_fetch', '1');
        formData.append('keyword', seed);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                lsiInput.value = data.lsis;
                runScanner(); // re-scan immediately
                
                btn.innerHTML = '<i class="fas fa-check text-success"></i> Fetched!';
                setTimeout(() => { btn.innerHTML = '<i class="fas fa-bolt text-warning"></i> Fetch API LSI'; btn.style.pointerEvents = 'auto'; }, 2000);
            } else {
                alert(data.error);
                btn.innerHTML = '<i class="fas fa-bolt text-warning"></i> Fetch API LSI';
                btn.style.pointerEvents = 'auto';
            }
        })
        .catch(err => {
            alert("Connection Error. Ensure internet is active.");
            btn.innerHTML = '<i class="fas fa-bolt text-warning"></i> Fetch API LSI';
            btn.style.pointerEvents = 'auto';
        });
    }

    // --- 2. ALGORITHMIC META GENERATOR ---
    function autoGenerateMeta() {
        const seed = document.getElementById('kwPrimary').value.trim();
        const lsis = document.getElementById('kwLSI').value.split(',').filter(x => x.trim() !== '');
        
        if(seed === '') { alert("Please enter Primary Keyword to generate Meta Tags."); return; }
        
        // Capitalize Words
        const titleCase = str => str.replace(/\b\w/g, l => l.toUpperCase());
        const year = new Date().getFullYear();
        
        // Algorithmic Title Injection
        document.getElementById('metaTitle').value = `Top ${titleCase(seed)} Strategies & Guide [${year}]`;
        
        // Algorithmic Desc Injection
        let lsiText = '';
        if(lsis.length >= 2) { lsiText = `, ${lsis[0].trim()}, and ${lsis[1].trim()}`; }
        else if (lsis.length === 1) { lsiText = ` and ${lsis[0].trim()}`; }
        
        document.getElementById('metaDesc').value = `Discover the ultimate guide to ${seed.toLowerCase()}. Learn expert tips${lsiText} to dominate your digital presence today.`;
        
        updateMetaCounters();
        runScanner();
    }

    // --- 3. SLUG GENERATOR ---
    function generateSlug() {
        const title = document.getElementById('liveTitle').value;
        const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        document.getElementById('liveSlug').value = slug;
        runScanner(); 
    }

    // --- 4. META COUNTERS & PROGRESS BARS ---
    const mTitle = document.getElementById('metaTitle');
    const mDesc = document.getElementById('metaDesc');

    function updateMetaCounters() {
        let tLen = mTitle.value.length;
        let dLen = mDesc.value.length;
        
        document.getElementById('metaTitleCount').innerText = tLen + '/60';
        document.getElementById('metaDescCount').innerText = dLen + '/160';

        let tBar = document.getElementById('metaTitleBar');
        let dBar = document.getElementById('metaDescBar');

        tBar.style.width = Math.min((tLen / 60) * 100, 100) + '%';
        if(tLen > 65) tBar.style.background = 'var(--danger)';
        else if(tLen > 50) tBar.style.background = 'var(--success)';
        else tBar.style.background = 'var(--warning)';

        dBar.style.width = Math.min((dLen / 160) * 100, 100) + '%';
        if(dLen > 165) dBar.style.background = 'var(--danger)';
        else if(dLen > 140) dBar.style.background = 'var(--success)';
        else dBar.style.background = 'var(--warning)';
    }

    mTitle.addEventListener('input', () => { updateMetaCounters(); runScanner(); });
    mDesc.addEventListener('input', () => { updateMetaCounters(); runScanner(); });

    // --- 5. THE LIVE SEMANTIC SCANNER (Core Magic) ---
    const contentBox = document.getElementById('liveContent');
    const kwPrimary = document.getElementById('kwPrimary');
    const kwLSI = document.getElementById('kwLSI');
    const resultsBox = document.getElementById('scannerResults');

    [contentBox, kwPrimary, kwLSI].forEach(el => {
        el.addEventListener('input', runScanner);
    });

    function runScanner() {
        const fullText = (
            document.getElementById('liveTitle').value + " " + 
            mTitle.value + " " + 
            mDesc.value + " " + 
            contentBox.value
        ).toLowerCase();

        let primary = kwPrimary.value.toLowerCase().trim();
        let lsiRaw = kwLSI.value.toLowerCase().split(',');

        let html = '';

        // Check Primary Keyword
        if(primary !== '') {
            let count = (fullText.match(new RegExp(primary.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), "g")) || []).length;
            let statusClass = count >= 2 ? 'kw-found' : 'kw-missing';
            let icon = count >= 2 ? 'fa-check-circle' : 'fa-times-circle';
            
            html += `
                <div class="kw-pill ${statusClass}">
                    <span><i class="fas ${icon} me-1"></i> [Primary] ${primary}</span>
                    <span class="kw-count">${count} found (Aim: 2+)</span>
                </div>
            `;
        }

        // Check LSI Keywords
        lsiRaw.forEach(lsi => {
            let kw = lsi.trim();
            if(kw !== '') {
                let count = (fullText.match(new RegExp(kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), "g")) || []).length;
                let statusClass = count >= 1 ? 'kw-found' : 'kw-missing';
                let icon = count >= 1 ? 'fa-check' : 'fa-times';
                
                html += `
                    <div class="kw-pill ${statusClass}" style="opacity: 0.9;">
                        <span><i class="fas ${icon} me-1"></i> [LSI] ${kw}</span>
                        <span class="kw-count">${count} found</span>
                    </div>
                `;
            }
        });

        if(html === '') {
            html = '<div class="text-center text-muted small py-3 fw-bold">Enter keywords and paste content to start live scan...</div>';
        }

        resultsBox.innerHTML = html;
    }
</script>

<?php 
if (file_exists('_footer.php')) { include '_footer.php'; }
?>
