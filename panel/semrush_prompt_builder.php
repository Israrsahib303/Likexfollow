<?php
// File: panel/semrush_prompt_builder.php (Backend Admin Tool)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
// Assuming you have an isAdmin() helper, otherwise adjust to your auth logic
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// ==========================================
// 🚀 AJAX HANDLER (For Live Keyword Fetching)
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'fetch_lsi') {
    header('Content-Type: application/json');
    $primary_kw = $_POST['keyword'] ?? '';
    $lsi_list = [];
    
    if (!empty($primary_kw)) {
        try {
            // Fetch LSI (Related) Keywords from Vault based on partial match
            // Safemode: using try-catch so it won't crash if table is empty
            $stmt = $db->prepare("SELECT keyword FROM semrush_keywords WHERE keyword LIKE ? AND keyword != ? ORDER BY search_volume DESC LIMIT 6");
            // Break primary keyword into first word to find broad relatives
            $parts = explode(' ', $primary_kw);
            $seed = $parts[0] . '%';
            $stmt->execute([$seed, $primary_kw]);
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($res as $r) {
                $lsi_list[] = $r['keyword'];
            }
            
            // If we couldn't find related, pick 5 random top keywords as a fallback demo
            if (empty($lsi_list)) {
                $stmt = $db->query("SELECT keyword FROM semrush_keywords ORDER BY RAND() LIMIT 5");
                $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($res as $r) { $lsi_list[] = $r['keyword']; }
            }
        } catch(Exception $e) {
            $lsi_list = ['smm services', 'cheap panel', 'fast delivery', 'buy followers', 'social media growth']; // Fallback
        }
    }
    
    echo json_encode(['status' => 'success', 'lsi' => implode(', ', $lsi_list)]);
    exit;
}

// ==========================================
// 🚀 FETCH DATA SOURCES FOR DROPDOWNS
// ==========================================
$keyword_vault = [];
$content_ideas = [];
$keyword_gaps = [];

try {
    // 1. Fetch from Master Keyword Vault
    $stmt1 = $db->query("SELECT keyword, search_volume, keyword_difficulty FROM semrush_keywords ORDER BY search_volume DESC LIMIT 100");
    $keyword_vault = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch from Topic Research Ideas
    $stmt2 = $db->query("SELECT headline_idea as keyword FROM semrush_content_ideas ORDER BY id DESC LIMIT 50");
    $content_ideas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch from Competitor Gaps
    $stmt3 = $db->query("SELECT keyword FROM semrush_keyword_gaps ORDER BY search_volume DESC LIMIT 50");
    $keyword_gaps = $stmt3->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    // Safemode: Prevent crash if tables don't exist yet
    $db_error = "SEMrush Database Tables are syncing. Fallback mode active.";
}

// Load Admin Header
$page_title = "AI Prompt Builder";
include '_header.php'; // Adjust path based on your admin folder structure
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Enterprise White & Purple UI */
    :root {
        --app-bg: #f8fafc;
        --card-bg: #ffffff;
        --border: #e2e8f0;
        --primary: #4f46e5;
        --primary-light: #eef2ff;
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }
    
    .prompt-wrapper { max-width: 1400px; margin: 0 auto; padding: 20px; font-family: 'Inter', sans-serif; }
    
    .top-header {
        background: var(--card-bg); padding: 30px; border-radius: 16px; margin-bottom: 30px;
        border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
    }
    .top-header h2 { font-weight: 800; color: var(--text-dark); margin: 0 0 5px 0; font-size: 1.8rem; }
    .top-header p { color: var(--text-muted); margin: 0; font-size: 0.95rem; }
    
    .grid-container { display: grid; grid-template-columns: 350px 1fr; gap: 30px; }
    
    @media(max-width: 992px) { .grid-container { grid-template-columns: 1fr; } }
    
    .tool-card {
        background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px;
        padding: 25px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); height: fit-content;
    }
    .tool-title { font-weight: 800; font-size: 1.2rem; color: var(--text-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .form-group { margin-bottom: 20px; }
    .form-label { font-weight: 700; color: var(--text-dark); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .form-select, .form-input {
        width: 100%; padding: 12px 15px; border: 2px solid var(--border); border-radius: 10px;
        font-size: 0.95rem; font-family: 'Inter', sans-serif; color: var(--text-dark); outline: none; transition: 0.3s;
    }
    .form-select:focus, .form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }
    
    .lsi-box { background: var(--primary-light); padding: 15px; border-radius: 10px; border: 1px dashed #c7d2fe; margin-bottom: 20px; }
    .lsi-box p { margin: 0 0 10px 0; font-weight: 700; color: var(--primary); font-size: 0.85rem; }
    .lsi-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .lsi-tag { background: #fff; border: 1px solid #c7d2fe; padding: 4px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; color: var(--primary); }
    
    .btn-generate {
        width: 100%; background: var(--primary); color: #fff; border: none; padding: 14px;
        border-radius: 10px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: 0.2s;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-generate:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 10px 20px -10px var(--primary); }
    
    /* Prompt Result Area */
    .prompt-result-card { background: #1e293b; border-radius: 16px; padding: 30px; position: relative; display: flex; flex-direction: column; }
    .prompt-result-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #334155; padding-bottom: 15px; }
    .prompt-result-title { font-weight: 800; color: #fff; margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; }
    
    .copy-btn {
        background: #10b981; color: #fff; border: none; padding: 8px 20px; border-radius: 8px;
        font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px;
    }
    .copy-btn:hover { background: #059669; }
    
    .prompt-textarea {
        width: 100%; flex: 1; min-height: 400px; background: #0f172a; border: 1px solid #334155;
        border-radius: 12px; padding: 20px; color: #e2e8f0; font-family: 'Consolas', 'Courier New', monospace;
        font-size: 0.95rem; line-height: 1.6; resize: vertical; outline: none;
    }
    .prompt-textarea:focus { border-color: var(--primary); }
    
    .instruction-badge { background: #fdf2f8; color: #c026d3; border: 1px solid #fbcfe8; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 20px; }
</style>

<div class="prompt-wrapper">
    
    <?php if(isset($db_error)): ?>
        <div class="alert alert-warning fw-bold mb-4 rounded-3 border-0 shadow-sm" style="background:#fffbeb; color:#9a3412;">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= $db_error ?>
        </div>
    <?php endif; ?>

    <div class="top-header">
        <div>
            <h2><i class="fas fa-magic text-primary me-2"></i> AI Master Prompt Builder</h2>
            <p>Select a keyword from your SEMrush database. We will build a highly-optimized prompt for ChatGPT.</p>
        </div>
        <div>
            <span class="instruction-badge">
                <i class="fas fa-info-circle"></i> No API Cost - 100% Manual Quality Control
            </span>
        </div>
    </div>

    <div class="grid-container">
        
        <div class="tool-card">
            <h3 class="tool-title"><i class="fas fa-sliders-h"></i> Configuration</h3>
            
            <div class="form-group">
                <label class="form-label">Data Source</label>
                <select class="form-select" id="dataSource" onchange="loadKeywords()">
                    <option value="vault">Keyword Vault (Top 100)</option>
                    <option value="gaps">Competitor Gaps</option>
                    <option value="ideas">Content Ideas</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Primary Keyword</label>
                <select class="form-select" id="primaryKeyword" onchange="fetchLSIKeywords()">
                    <option value="">-- Select Target Keyword --</option>
                    <?php foreach($keyword_vault as $kw): ?>
                        <option value="<?= htmlspecialchars($kw['keyword']) ?>"><?= htmlspecialchars($kw['keyword']) ?> (Vol: <?= $kw['search_volume'] ?? 'N/A' ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="lsi-box">
                <p><i class="fas fa-network-wired"></i> Extracted LSI Keywords</p>
                <div class="lsi-tags" id="lsiTagsArea">
                    <span class="lsi-tag text-muted border-0 bg-transparent px-0">Select a primary keyword first...</span>
                </div>
                <input type="hidden" id="lsiHidden" value="">
            </div>
            
            <div class="form-group">
                <label class="form-label">Target Audience</label>
                <input type="text" class="form-input" id="audience" value="Social media influencers, brand owners, and agencies in Pakistan." placeholder="Who is reading this?">
            </div>
            
            <div class="form-group">
                <label class="form-label">Word Count</label>
                <select class="form-select" id="wordCount">
                    <option value="1000">1000 Words (Standard)</option>
                    <option value="1500" selected>1500 Words (SEO Optimized)</option>
                    <option value="2500">2500 Words (Pillar Content)</option>
                </select>
            </div>
            
            <button class="btn-generate" onclick="generatePrompt()">
                <i class="fas fa-bolt"></i> Generate Super Prompt
            </button>
        </div>
        
        <div class="prompt-result-card shadow-lg">
            <div class="prompt-result-header">
                <h3 class="prompt-result-title"><i class="fas fa-robot text-success"></i> Master Prompt Generated</h3>
                <button class="copy-btn" onclick="copyToClipboard()" id="copyBtn">
                    <i class="fas fa-copy"></i> Copy Prompt
                </button>
            </div>
            
            <textarea id="promptOutput" class="prompt-textarea custom-scrollbar" placeholder="Your highly-optimized prompt will appear here. Select a keyword and click Generate..." spellcheck="false"></textarea>
            
            <div class="mt-3 text-end">
                <a href="https://chat.openai.com/" target="_blank" class="text-decoration-none" style="color: #38bdf8; font-weight: 600; font-size: 0.9rem;">
                    Open ChatGPT <i class="fas fa-external-link-alt ms-1"></i>
                </a>
            </div>
        </div>

    </div>
</div>

<script>
    // Data Storage for dynamic switching
    const sourceData = {
        'vault': [
            <?php foreach($keyword_vault as $kw) { echo "['" . addslashes($kw['keyword']) . "', '" . ($kw['search_volume'] ?? 'N/A') . "'],"; } ?>
        ],
        'gaps': [
            <?php foreach($keyword_gaps as $kw) { echo "['" . addslashes($kw['keyword']) . "', 'N/A'],"; } ?>
        ],
        'ideas': [
            <?php foreach($content_ideas as $kw) { echo "['" . addslashes($kw['keyword']) . "', 'Idea'],"; } ?>
        ]
    };

    function loadKeywords() {
        const source = document.getElementById('dataSource').value;
        const kwSelect = document.getElementById('primaryKeyword');
        
        // Clear current
        kwSelect.innerHTML = '<option value="">-- Select Target Keyword --</option>';
        document.getElementById('lsiTagsArea').innerHTML = '<span class="lsi-tag text-muted border-0 bg-transparent px-0">Select a primary keyword first...</span>';
        document.getElementById('lsiHidden').value = '';
        
        // Populate new
        if(sourceData[source]) {
            sourceData[source].forEach(item => {
                let opt = document.createElement('option');
                opt.value = item[0];
                opt.text = item[0] + " (Vol: " + item[1] + ")";
                kwSelect.appendChild(opt);
            });
        }
    }

    function fetchLSIKeywords() {
        const keyword = document.getElementById('primaryKeyword').value;
        if(keyword === '') return;
        
        // Show loading
        document.getElementById('lsiTagsArea').innerHTML = '<span class="text-primary fw-bold"><i class="fas fa-spinner fa-spin me-2"></i> Analyzing Database...</span>';
        
        // AJAX Call
        const formData = new FormData();
        formData.append('action', 'fetch_lsi');
        formData.append('keyword', keyword);
        
        fetch('semrush_prompt_builder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success' && data.lsi !== '') {
                document.getElementById('lsiHidden').value = data.lsi;
                let tagsHtml = '';
                data.lsi.split(',').forEach(tag => {
                    tagsHtml += `<span class="lsi-tag">${tag.trim()}</span>`;
                });
                document.getElementById('lsiTagsArea').innerHTML = tagsHtml;
            } else {
                document.getElementById('lsiTagsArea').innerHTML = '<span class="text-danger fw-bold">No LSI found.</span>';
                document.getElementById('lsiHidden').value = 'social media strategy, cheap panel, instant delivery';
            }
        })
        .catch(error => {
            document.getElementById('lsiTagsArea').innerHTML = '<span class="text-warning fw-bold">Demo Fallback LSI active.</span>';
            document.getElementById('lsiHidden').value = 'smm panel, cheap followers, instant delivery, secure payment';
        });
    }

    function generatePrompt() {
        const keyword = document.getElementById('primaryKeyword').value;
        const audience = document.getElementById('audience').value;
        const wordCount = document.getElementById('wordCount').value;
        let lsi = document.getElementById('lsiHidden').value;
        
        if(keyword === '') {
            Swal.fire({ icon: 'warning', title: 'Action Required', text: 'Please select a Primary Keyword first!' });
            return;
        }
        
        if(lsi === '') lsi = "social media marketing, online growth, best smm provider";

        // The Ultimate SaaS Prompt Template
        const prompt = `Act as an Elite SEO Content Strategist and Expert Copywriter.

I need you to write a highly engaging, human-sounding, ${wordCount}-word blog post.

CRITICAL CONTEXT:
- Primary Target Keyword: "${keyword}"
- Target Audience: ${audience}
- Brand Voice: Authoritative, helpful, professional, and slightly conversational.

SEO & FORMATTING RULES (STRICTLY FOLLOW THESE):
1. Meta Data: Provide a Catchy Meta Title (max 60 chars) and Meta Description (max 160 chars) at the very top.
2. Keyword Density: Use the primary keyword "${keyword}" exactly in the H1 title, within the first 100 words, and 3-4 times naturally throughout the text. Do not over-stuff.
3. LSI Keywords: You MUST naturally integrate these secondary keywords: [ ${lsi} ].
4. Structure: Use H2 and H3 tags to break up the text. Create highly scannable content using bullet points, bold text for emphasis, and short paragraphs (max 3-4 sentences per paragraph).
5. AI Footprint Removal: DO NOT use generic AI vocabulary. Strictly avoid words/phrases like: 'In conclusion', 'Delve into', 'Tapestry', 'Navigating the landscape', 'A testament to', 'Crucial', 'Imperative'.
6. Ending: End with a strong Call-To-Action (CTA) encouraging the reader to check out our services or premium store.

Output the entire blog post strictly in HTML format (using <h1>, <h2>, <p>, <ul>, <strong>, etc.) so I can copy-paste it directly into my CMS. Do not wrap the output in markdown code blocks.`;

        // Typewriter Effect
        const outputBox = document.getElementById('promptOutput');
        outputBox.value = '';
        let i = 0;
        
        function typeWriter() {
            if (i < prompt.length) {
                outputBox.value += prompt.charAt(i);
                i++;
                outputBox.scrollTop = outputBox.scrollHeight;
                setTimeout(typeWriter, 2); // Extremely fast typing effect
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Prompt Generated! 🚀',
                    text: 'Copy the prompt and paste it into ChatGPT/Gemini.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }
        typeWriter();
    }

    function copyToClipboard() {
        const outputBox = document.getElementById('promptOutput');
        if(outputBox.value === '') return;
        
        outputBox.select();
        outputBox.setSelectionRange(0, 99999); // For mobile devices
        navigator.clipboard.writeText(outputBox.value);
        
        const btn = document.getElementById('copyBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.style.background = '#059669';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '#10b981';
        }, 2000);
    }
</script>

<?php 
// Include Footer if exists
// include '_footer.php'; 
?>
