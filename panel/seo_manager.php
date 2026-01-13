<?php
// File: panel/seo_manager.php
require_once '_header.php';

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Update General SEO Settings
    if (isset($_POST['update_seo'])) {
        $seo_title = sanitize($_POST['seo_title']);
        $seo_desc = sanitize($_POST['seo_desc']);
        $seo_keywords = sanitize($_POST['seo_keywords']);
        $header_code = $_POST['header_code']; // Allow HTML/JS
        $footer_code = $_POST['footer_code']; // Allow HTML/JS

        // Update Settings Table
        updateSetting('seo_title', $seo_title);
        updateSetting('seo_desc', $seo_desc);
        updateSetting('seo_keywords', $seo_keywords);
        updateSetting('header_code', $header_code);
        updateSetting('footer_code', $footer_code);

        // Update Main Page in site_seo table too (for consistency)
        $db->prepare("UPDATE site_seo SET meta_title=?, meta_description=?, meta_keywords=? WHERE page_name='index.php'")
           ->execute([$seo_title, $seo_desc, $seo_keywords]);

        // B. Handle Favicon Upload
        if (!empty($_FILES['site_favicon']['name'])) {
            $allowed = ['png', 'jpg', 'jpeg', 'ico', 'svg'];
            $filename = $_FILES['site_favicon']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newName = "favicon." . $ext;
                $target = "../assets/img/" . $newName;
                if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $target)) {
                    updateSetting('site_favicon', $newName);
                }
            } else {
                echo "<script>alert('Invalid Image Format!');</script>";
            }
        }

        echo "<script>window.location.href='seo_manager.php?success=SEO Settings Updated Successfully';</script>";
    }
}

// --- 2. FETCH CURRENT DATA ---
// Fetch Global Settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Defaults
$title = $settings['seo_title'] ?? 'LikexFollow - Best SMM Panel';
$desc = $settings['seo_desc'] ?? 'Cheap SMM Panel for Instagram, TikTok, and YouTube services.';
$keys = $settings['seo_keywords'] ?? 'smm panel, cheap followers, likes';
$favicon = $settings['site_favicon'] ?? 'favicon.png';
$head_code = $settings['header_code'] ?? '';
$foot_code = $settings['footer_code'] ?? '';

// Helper to update DB
function updateSetting($key, $val) {
    global $db;
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
    $stmt->execute([$key, $val, $val]);
}
?>

<style>
    :root { --google-blue: #1a0dab; --google-green: #006621; --google-gray: #545454; }
    
    .seo-preview-card {
        background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 2rem;
    }
    
    /* Google Search Result Simulation */
    .g-result { font-family: arial, sans-serif; max-width: 600px; }
    .g-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
    .g-favicon { width: 26px; height: 26px; background: #f1f3f4; border-radius: 50%; padding: 4px; object-fit: contain; }
    .g-site-name { font-size: 14px; color: #202124; margin-bottom: 2px; }
    .g-url { font-size: 12px; color: #5f6368; line-height: 1.3; }
    .g-title { color: var(--google-blue); font-size: 20px; line-height: 1.3; cursor: pointer; text-decoration: none; display: block; margin-bottom: 3px; }
    .g-title:hover { text-decoration: underline; }
    .g-desc { color: var(--google-gray); font-size: 14px; line-height: 1.58; word-wrap: break-word; }
    .g-date { color: #70757a; font-size: 14px; }

    /* Inputs */
    .form-label { font-weight: 600; color: #334155; font-size: 0.9rem; margin-bottom: 0.5rem; }
    .form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 15px; font-size: 0.95rem; }
    .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
    
    .code-editor { font-family: 'Courier New', monospace; font-size: 13px; background: #1e293b; color: #a5f3fc; border: 1px solid #334155; }

    /* AI Button */
    .btn-ai {
        background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%); color: white; border: none;
        padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;
        transition: transform 0.2s; cursor: pointer;
    }
    .btn-ai:hover { transform: translateY(-2px); color: white; box-shadow: 0 10px 20px -5px rgba(236, 72, 153, 0.4); }
    .btn-ai i { font-size: 1rem; }

    .img-upload-box {
        border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center;
        cursor: pointer; transition: 0.2s; background: #f8fafc;
    }
    .img-upload-box:hover { border-color: #6366f1; background: #eef2ff; }
</style>

<div class="container-fluid p-4" style="max-width: 1200px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">SEO Master Control</h2>
            <p class="text-muted mb-0">Manage how your website appears on Google.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('mainForm').submit()">
            <i class="fas fa-save me-2"></i> Save Changes
        </button>
    </div>

    <form id="mainForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_seo" value="1">

        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0"><i class="fas fa-sliders-h text-primary me-2"></i> Meta Configuration</h5>
                            <button type="button" class="btn-ai" onclick="generateAISEO()">
                                <i class="fas fa-magic"></i> Auto-Generate with AI
                            </button>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Meta Title (Google Headline)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="seo_title" id="seo_title" value="<?= htmlspecialchars($title) ?>" oninput="updatePreview()" maxlength="60">
                                <span class="input-group-text text-muted" id="title_count">60</span>
                            </div>
                            <small class="text-muted">Recommended: 50-60 Characters. Include your main keyword.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" name="seo_desc" id="seo_desc" rows="3" oninput="updatePreview()" maxlength="160"><?= htmlspecialchars($desc) ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Recommended: 150-160 Characters.</small>
                                <small class="text-muted" id="desc_count">160</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Meta Keywords (Comma Separated)</label>
                            <input type="text" class="form-control" name="seo_keywords" id="seo_keywords" value="<?= htmlspecialchars($keys) ?>">
                            <div id="ai_keywords_badge" class="mt-2 d-none">
                                <span class="badge bg-light text-dark border me-1">✨ AI Suggestion</span>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <label class="form-label">Website Favicon (Icon)</label>
                        <div class="d-flex gap-3 align-items-center">
                            <div class="img-upload-box flex-grow-1" onclick="document.getElementById('fav_input').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-0 small fw-bold text-secondary">Click to Upload New Icon</p>
                                <p class="mb-0 x-small text-muted">PNG, JPG, ICO (Max 2MB)</p>
                                <input type="file" name="site_favicon" id="fav_input" hidden onchange="previewImage(this)">
                            </div>
                            <div style="width: 80px; height: 80px; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px; display: flex; align-items: center; justify-content: center; background: #fff;">
                                <img src="../assets/img/<?= htmlspecialchars($favicon) ?>" id="fav_preview" style="max-width: 100%; max-height: 100%;">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h6 class="fw-bold text-muted text-uppercase small ls-1"><i class="fab fa-google me-2"></i> Live Search Preview</h6>
                    </div>
                    <div class="card-body">
                        <div class="seo-preview-card">
                            <div class="g-result">
                                <div class="g-header">
                                    <img src="../assets/img/<?= htmlspecialchars($favicon) ?>" class="g-favicon" id="g_fav_preview">
                                    <div>
                                        <div class="g-site-name"><?= $_SERVER['HTTP_HOST'] ?></div>
                                        <div class="g-url">https://<?= $_SERVER['HTTP_HOST'] ?> › home</div>
                                    </div>
                                </div>
                                <a href="#" class="g-title" id="g_title_preview"><?= htmlspecialchars($title) ?></a>
                                <div class="g-desc">
                                    <span class="g-date"><?= date('M d, Y') ?> — </span>
                                    <span id="g_desc_preview"><?= htmlspecialchars($desc) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info d-flex align-items-center small py-2">
                            <i class="fas fa-info-circle me-2"></i> This is exactly how your site looks on Google.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white pt-4 pb-0">
                        <h6 class="fw-bold text-dark"><i class="fas fa-code me-2"></i> Advanced Code Injection</h6>
                    </div>
                    <div class="card-body">
                        
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Header Code <small class="text-muted">(&lt;head&gt;)</small></span>
                                <i class="fab fa-google text-muted" title="Paste Google Analytics / Search Console Here"></i>
                            </label>
                            <textarea class="form-control code-editor" name="header_code" rows="4" placeholder="<script> Google Analytics... </script>"><?= htmlspecialchars($head_code) ?></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Footer Code <small class="text-muted">(&lt;/body&gt;)</small></label>
                            <textarea class="form-control code-editor" name="footer_code" rows="4" placeholder="<script> Chat Plugin... </script>"><?= htmlspecialchars($foot_code) ?></textarea>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
    // 1. Live Preview Logic
    function updatePreview() {
        const title = document.getElementById('seo_title').value;
        const desc = document.getElementById('seo_desc').value;
        
        document.getElementById('g_title_preview').innerText = title || "Your Page Title";
        document.getElementById('g_desc_preview').innerText = desc || "Your page meta description will appear here...";
        
        // Character Counters
        document.getElementById('title_count').innerText = 60 - title.length;
        document.getElementById('desc_count').innerText = 160 - desc.length;
    }

    // 2. Image Preview Logic
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('fav_preview').src = e.target.result;
                document.getElementById('g_fav_preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 3. AI Generation Logic (Simulated with fallback or actual AJAX if API endpoint exists)
    function generateAISEO() {
        const btn = document.querySelector('.btn-ai');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        // Use the existing test_ai.php logic via AJAX
        const formData = new FormData();
        formData.append('prompt', 'Write a catchy SEO Title (max 60 chars), Description (max 150 chars), and 10 Keywords for an SMM Panel named LikexFollow. Return JSON: {"title":"...","desc":"...","keys":"..."}');

        fetch('test_ai.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Clean Markdown code blocks if AI adds them
            let cleanJson = data.replace(/```json|```/g, '').trim();
            
            try {
                // Try to find JSON in the response
                const jsonMatch = cleanJson.match(/\{[\s\S]*\}/);
                if (jsonMatch) {
                    const result = JSON.parse(jsonMatch[0]);
                    
                    document.getElementById('seo_title').value = result.title;
                    document.getElementById('seo_desc').value = result.desc;
                    document.getElementById('seo_keywords').value = result.keys || result.keywords;
                    
                    updatePreview();
                    
                    // Show success
                    const badge = document.getElementById('ai_keywords_badge');
                    badge.classList.remove('d-none');
                    badge.innerHTML = `<span class="badge bg-success text-white border">✅ AI Generated</span>`;
                } else {
                    alert("AI Response was raw text. Check console.");
                    console.log(data);
                }
            } catch (e) {
                console.error("Parsing Error:", e);
                // Fallback for demo if API fails
                document.getElementById('seo_title').value = "LikexFollow - #1 SMM Panel for Resellers & Influencers";
                document.getElementById('seo_desc').value = "Boost your social media with the cheapest and fastest SMM Panel. Get TikTok followers, Instagram likes, and YouTube views instantly. Safe & Secure.";
                document.getElementById('seo_keywords').value = "smm panel, buy followers, cheap likes, instagram growth, tiktok viral";
                updatePreview();
            }
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert("AI Error. Please check API Key in Settings.");
        });
    }

    // Init
    updatePreview();
</script>

<?php require_once '_footer.php'; ?>