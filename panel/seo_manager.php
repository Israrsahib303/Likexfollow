<?php
// panel/seo_manager.php - ULTIMATE SEO SUITE V2
// Features: Meta, OG, Sitemap, Robots, .htaccess, Code Injection, Schema, Ping

include '_header.php';

$success = '';
$error = '';

// --- 1. SAVE DATABASE SETTINGS (Meta, Social, Schema, Code) ---
if (isset($_POST['save_general']) || isset($_POST['save_schema']) || isset($_POST['save_code'])) {
    $params = [
        // General
        'seo_title' => $_POST['seo_title'] ?? '',
        'seo_desc' => $_POST['seo_desc'] ?? '',
        'seo_keywords' => $_POST['seo_keywords'] ?? '',
        'seo_author' => $_POST['seo_author'] ?? '',
        // Social
        'seo_og_image' => $_POST['seo_og_image'] ?? '',
        'ga_tracking_id' => $_POST['ga_tracking_id'] ?? '',
        'fb_pixel_id' => $_POST['fb_pixel_id'] ?? '',
        // Schema
        'schema_org_name' => $_POST['schema_org_name'] ?? '',
        'schema_org_logo' => $_POST['schema_org_logo'] ?? '',
        'social_links' => $_POST['social_links'] ?? '',
        // Injection
        'site_header_code' => $_POST['site_header_code'] ?? '',
        'site_footer_code' => $_POST['site_footer_code'] ?? ''
    ];

    try {
        $db->beginTransaction();
        foreach ($params as $key => $val) {
            // Using logic to allow empty values update
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$key, $val]); // Removed trim to allow code spacing
        }
        $db->commit();
        $success = "✅ Settings Saved Successfully!";
        
        // Refresh Cache
        foreach($params as $k => $v) { $GLOBALS['settings'][$k] = $v; }
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "DB Error: " . $e->getMessage();
    }
}

// --- 2. FILE OPERATIONS (Sitemap, Robots, .htaccess) ---
$root_path = realpath(__DIR__ . '/../');
$site_url = defined('SITE_URL') ? SITE_URL : 'https://' . $_SERVER['HTTP_HOST'];

// Generate Sitemap
if (isset($_POST['gen_sitemap'])) {
    try {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Static Pages List
        $pages = ['index.php', 'login.php', 'register.php', 'services.php', 'terms.php', 'api.php', 'blog.php'];
        foreach ($pages as $page) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $site_url . '/' . $page . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>daily</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.8</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }
        $xml .= '</urlset>';
        
        if (file_put_contents($root_path . '/sitemap.xml', $xml)) {
            $success = "✅ Sitemap Generated!";
            
            // Ping Google
            if(isset($_POST['ping_google'])) {
                $sitemapUrl = urlencode($site_url . '/sitemap.xml');
                $pingUrl = "http://www.google.com/ping?sitemap=" . $sitemapUrl;
                $response = @file_get_contents($pingUrl);
                if($response) $success .= " & Google Pinged 📡";
            }
        } else {
            $error = "❌ Permission Denied: Cannot write sitemap.xml";
        }
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// Save Robots.txt
if (isset($_POST['save_robots'])) {
    if (file_put_contents($root_path . '/robots.txt', $_POST['robots_content'])) {
        $success = "✅ Robots.txt updated!";
    } else { $error = "❌ Write failed for robots.txt"; }
}

// Save .htaccess
if (isset($_POST['save_htaccess'])) {
    if (file_put_contents($root_path . '/.htaccess', $_POST['htaccess_content'])) {
        $success = "✅ .htaccess updated! (Check site immediately)";
    } else { $error = "❌ Write failed for .htaccess"; }
}

// Load Data
$s = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM settings");
while($row = $stmt->fetch()){ $s[$row['setting_key']] = $row['setting_value']; }

$robots_file = $root_path . '/robots.txt';
$robots_content = file_exists($robots_file) ? file_get_contents($robots_file) : "User-agent: *\nAllow: /";

$htaccess_file = $root_path . '/.htaccess';
$htaccess_content = file_exists($htaccess_file) ? file_get_contents($htaccess_file) : "# No .htaccess found";
?>

<style>
/* --- ADVANCED UI STYLES --- */
:root { --primary: #4f46e5; --dark: #0f172a; --bg: #f1f5f9; --glass: rgba(255,255,255,0.95); }
.seo-wrapper { width: 95%; max-width: 1600px; margin: 2rem auto; font-family: 'Segoe UI', sans-serif; }

/* Tabs */
.tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.tab-btn { background: #e2e8f0; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 700; color: #64748b; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
.tab-btn.active { background: var(--primary); color: white; box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); }
.tab-btn:hover:not(.active) { background: #cbd5e1; }

.tab-content { display: none; animation: fadeIn 0.3s ease; }
.tab-content.active { display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 25px; }

/* Cards */
.card { background: var(--glass); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
.card-head { padding: 15px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; font-weight: 700; color: #334155; }
.card-head i { color: var(--primary); font-size: 1.1rem; }
.card-body { padding: 25px; }

/* Inputs */
.form-group { margin-bottom: 15px; }
.label { display: block; font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; }
.input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #fff; transition: 0.2s; }
.input:focus { border-color: var(--primary); outline: none; }
.code-editor { font-family: 'Courier New', monospace; background: #1e293b; color: #a5b4fc; border: 2px solid #334155; }

/* Buttons */
.btn { width: 100%; padding: 14px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; color: white; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-save { background: var(--primary); }
.btn-warn { background: #ea580c; }
.btn-act { background: #10b981; }
.btn:hover { transform: translateY(-2px); filter: brightness(1.1); }

/* Alerts */
.alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

@keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
</style>

<div class="seo-wrapper">

    <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color:white; padding:2rem; border-radius:16px; margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1 style="margin:0; font-size:1.8rem;">🚀 Ultimate SEO Manager</h1>
            <p style="margin:5px 0 0; opacity:0.8;">Meta, Schema, Code Injection & Server Config.</p>
        </div>
        <a href="system_controls.php" style="background:rgba(255,255,255,0.15); padding:10px 20px; border-radius:50px; color:white; text-decoration:none; font-weight:bold;">&larr; System</a>
    </div>

    <?php if($success): ?><div class="alert success"><i class="fa fa-check"></i> <?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert error"><i class="fa fa-exclamation-triangle"></i> <?= $error ?></div><?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" onclick="openTab(event, 'tab-general')"><i class="fa fa-globe"></i> Global SEO</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-schema')"><i class="fa fa-id-card"></i> Schema & Identity</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-code')"><i class="fa fa-code"></i> Code Injection</button>
        <button class="tab-btn" onclick="openTab(event, 'tab-tech')"><i class="fa fa-server"></i> Technical Files</button>
    </div>

    <form method="POST"> <div id="tab-general" class="tab-content active">
        <div class="card">
            <div class="card-head"><i class="fa fa-search"></i> Search Engine Listing</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="label">Meta Title</label>
                    <input type="text" name="seo_title" class="input" value="<?= sanitize($s['seo_title'] ?? '') ?>" placeholder="My Best SMM Panel">
                </div>
                <div class="form-group">
                    <label class="label">Meta Description</label>
                    <textarea name="seo_desc" class="input" rows="3"><?= sanitize($s['seo_desc'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="label">Keywords</label>
                    <input type="text" name="seo_keywords" class="input" value="<?= sanitize($s['seo_keywords'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><i class="fa fa-share-alt"></i> Social & Analytics</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="label">OG Image URL (Social Banner)</label>
                    <input type="text" name="seo_og_image" class="input" value="<?= sanitize($s['seo_og_image'] ?? '') ?>" placeholder="https://...">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label class="label">Google Analytics ID</label>
                        <input type="text" name="ga_tracking_id" class="input" value="<?= sanitize($s['ga_tracking_id'] ?? '') ?>" placeholder="G-XXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="label">Facebook Pixel ID</label>
                        <input type="text" name="fb_pixel_id" class="input" value="<?= sanitize($s['fb_pixel_id'] ?? '') ?>" placeholder="123456...">
                    </div>
                </div>
                <button type="submit" name="save_general" class="btn btn-save" style="margin-top:10px;">Save General Settings</button>
            </div>
        </div>
    </div>

    <div id="tab-schema" class="tab-content">
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-head"><i class="fa fa-project-diagram"></i> Organization Schema (Rich Snippets)</div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label class="label">Organization Name</label>
                        <input type="text" name="schema_org_name" class="input" value="<?= sanitize($s['schema_org_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Logo URL</label>
                        <input type="text" name="schema_org_logo" class="input" value="<?= sanitize($s['schema_org_logo'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Social Profiles (Comma separated)</label>
                    <input type="text" name="social_links" class="input" value="<?= sanitize($s['social_links'] ?? '') ?>" placeholder="https://facebook.com/me, https://twitter.com/me">
                </div>
                <div class="alert success" style="margin-top:10px; font-size:0.85rem;">
                    <i class="fa fa-info-circle"></i> This data generates JSON-LD code automatically to help Google understand your brand.
                </div>
                <button type="submit" name="save_schema" class="btn btn-save">Save Schema Data</button>
            </div>
        </div>
    </div>

    <div id="tab-code" class="tab-content">
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-head"><i class="fa fa-code"></i> Custom Code Injection</div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:25px;">
                    <div>
                        <label class="label">Header Code (&lt;head&gt;)</label>
                        <textarea name="site_header_code" class="input code-editor" rows="12" placeholder="<script>...Verification Tags...</script>"><?= htmlspecialchars($s['site_header_code'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="label">Footer Code (&lt;/body&gt;)</label>
                        <textarea name="site_footer_code" class="input code-editor" rows="12" placeholder="<script>...Live Chat / JS...</script>"><?= htmlspecialchars($s['site_footer_code'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" name="save_code" class="btn btn-save" style="margin-top:20px;">Save Injected Code</button>
            </div>
        </div>
    </div>

    </form> <div id="tab-tech" class="tab-content">
        
        <div class="card">
            <div class="card-head"><i class="fa fa-sitemap"></i> Sitemap Automation</div>
            <div class="card-body">
                <p style="color:#64748b; font-size:0.9rem; margin-bottom:15px;">Generate an XML sitemap for all your public pages.</p>
                <form method="POST">
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:15px; cursor:pointer;">
                        <input type="checkbox" name="ping_google" checked style="width:18px; height:18px;"> 
                        <span style="font-weight:600;">Ping Google automatically after generation?</span>
                    </label>
                    <button type="submit" name="gen_sitemap" class="btn btn-act">Generate & Ping</button>
                </form>
                <div style="margin-top:15px;">
                    <a href="<?= $site_url ?>/sitemap.xml" target="_blank" style="color:var(--primary); text-decoration:none; font-weight:700;">View Sitemap.xml &rarr;</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><i class="fa fa-robot"></i> Robots.txt</div>
            <div class="card-body">
                <form method="POST">
                    <textarea name="robots_content" class="input code-editor" rows="6"><?= htmlspecialchars($robots_content) ?></textarea>
                    <button type="submit" name="save_robots" class="btn btn-save" style="margin-top:10px; background:#334155;">Update Robots.txt</button>
                </form>
            </div>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-head" style="color:#ef4444;"><i class="fa fa-lock"></i> .htaccess Editor (Advanced)</div>
            <div class="card-body">
                <p style="color:#ef4444; font-size:0.85rem; background:#fef2f2; padding:10px; border-radius:6px; margin-bottom:15px; border:1px solid #fecaca;">
                    ⚠️ <b>WARNING:</b> Invalid code here will crash your website (500 Error). Only edit if you know what you are doing.
                </p>
                <form method="POST">
                    <textarea name="htaccess_content" class="input code-editor" rows="10"><?= htmlspecialchars($htaccess_content) ?></textarea>
                    <button type="submit" name="save_htaccess" class="btn btn-warn" style="margin-top:10px;">Save Server Config</button>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove('active');
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "grid";
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.className += " active";
}
</script>

<?php include '_footer.php'; ?>