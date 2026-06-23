<?php
// File: panel/semrush_writing_assistant.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE DRAFT TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_content_drafts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        target_keywords TEXT,
        content LONGTEXT,
        seo_score INT DEFAULT 0,
        word_count INT DEFAULT 0,
        last_saved TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
    $message = "DB Error: " . $e->getMessage();
    $msg_type = "danger";
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// --- 1. HANDLE DRAFT SAVING ---
$draft_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_draft'])) {
    $title = sanitize($_POST['title']);
    $keywords = sanitize($_POST['target_keywords']);
    $content = $_POST['content']; // Allow HTML structure for formatting
    $score = (int)$_POST['live_seo_score'];
    $words = (int)$_POST['live_word_count'];
    $d_id = (int)$_POST['draft_id'];

    if($d_id > 0) {
        // Update Existing
        $stmt = $db->prepare("UPDATE semrush_content_drafts SET title=?, target_keywords=?, content=?, seo_score=?, word_count=? WHERE id=?");
        if($stmt->execute([$title, $keywords, $content, $score, $words, $d_id])) {
            $message = "Draft Blueprint Saved! 💾 Matrix Updated."; $msg_type = "success";
            $draft_id = $d_id;
        }
    } else {
        // Create New
        $stmt = $db->prepare("INSERT INTO semrush_content_drafts (title, target_keywords, content, seo_score, word_count) VALUES (?, ?, ?, ?, ?)");
        if($stmt->execute([$title, $keywords, $content, $score, $words])) {
            $message = "New Blueprint Initialized! 🚀"; $msg_type = "success";
            $draft_id = $db->lastInsertId();
            header("Location: semrush_writing_assistant.php?id=" . $draft_id . "&msg=created");
            exit;
        }
    }
}

// Check for redirect message
if(isset($_GET['msg']) && $_GET['msg'] == 'created') {
    $message = "New SEO Blueprint Initialized! 🚀"; $msg_type = "success";
}

// --- 2. LOAD EXISTING DRAFT ---
$curr_title = ''; $curr_kws = ''; $curr_content = ''; $curr_score = 0; $curr_words = 0;

if($draft_id > 0 && $table_exists) {
    $stmt = $db->prepare("SELECT * FROM semrush_content_drafts WHERE id = ?");
    $stmt->execute([$draft_id]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);
    if($draft) {
        $curr_title = $draft['title'];
        $curr_kws = $draft['target_keywords'];
        $curr_content = $draft['content'];
        $curr_score = (int)$draft['seo_score'];
        $curr_words = (int)$draft['word_count'];
    }
}

// Fetch all drafts for sidebar
$all_drafts = [];
if($table_exists) {
    $all_drafts = $db->query("SELECT id, title, seo_score, last_saved FROM semrush_content_drafts ORDER BY last_saved DESC")->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "SEO Content Assistant";
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
        --c-success: #10b981; --c-warning: #f59e0b; --c-danger: #ef4444;
    }
    
    body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    .beast-container { width: 100%; max-width: 1600px; margin: 0 auto; padding: 20px; }
    
    @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(99,102,241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99,102,241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99,102,241, 0); } }
    
    .anim-slide { animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .anim-delay-1 { animation-delay: 0.1s; }
    
    .swa-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); transition: 0.3s; }
    
    /* Buttons */
    .btn-save { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; white-space: nowrap; animation: pulseGlow 2s infinite; }
    .btn-save:hover { box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5); transform: translateY(-2px); color: #fff; animation: none; }

    .btn-new { background: #f8fafc; color: var(--t-dark); border: 2px solid var(--b-color); padding: 10px 20px; border-radius: 12px; font-weight: 800; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-new:hover { background: var(--l-purple); border-color: var(--d-purple); color: var(--d-purple); }

    /* Inputs & Editor */
    .form-label { font-weight: 800; color: var(--t-dark); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
    
    .swa-input { border: 2px solid var(--b-color); border-radius: 12px; padding: 14px 18px; font-weight: 600; color: var(--t-dark); width: 100%; outline: none; transition: 0.3s; background: #f8fafc; font-size: 1rem; }
    .swa-input:focus { border-color: var(--d-purple); background: #fff; box-shadow: 0 0 0 4px var(--l-purple); }
    
    .swa-editor { width: 100%; height: 550px; border: 2px solid var(--b-color); border-radius: 12px; padding: 25px; font-size: 1.1rem; line-height: 1.8; color: var(--t-dark); resize: vertical; outline: none; transition: 0.3s; background: #fff; font-family: 'Georgia', serif; }
    .swa-editor:focus { border-color: var(--d-purple); box-shadow: 0 0 0 4px var(--l-purple); }

    /* Sidebar Drafts */
    .sidebar-wrapper { background: #f8fafc; border-right: 1px solid var(--b-color); max-height: 800px; overflow-y: auto; border-top-left-radius: 20px; border-bottom-left-radius: 20px; }
    .draft-item { display: block; padding: 15px 20px; border-bottom: 1px solid var(--b-color); text-decoration: none; color: var(--t-dark); transition: 0.2s; border-left: 4px solid transparent; }
    .draft-item:hover { background: #fff; border-left-color: var(--b-color); }
    .draft-active { background: #fff; border-left-color: var(--p-purple); box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); }

    /* SEO Matrix Panel */
    .seo-panel { background: #0f172a; border-top-right-radius: 20px; border-bottom-right-radius: 20px; height: 100%; padding: 2rem; color: #fff; position: relative; overflow: hidden; }
    .seo-panel::after { content: ''; position: absolute; top: 0; right: 0; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
    
    .score-circle { width: 140px; height: 140px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; font-weight: 900; margin: 0 auto 20px auto; border: 10px solid rgba(255,255,255,0.1); color: #fff; transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); background: rgba(0,0,0,0.2); position: relative; z-index: 2; box-shadow: inset 0 0 20px rgba(0,0,0,0.5); }
    
    /* Dynamic Color Classes for JS */
    .dial-red { border-color: var(--c-danger); color: var(--c-danger); box-shadow: 0 0 30px rgba(239, 68, 68, 0.3); }
    .dial-orange { border-color: var(--c-warning); color: var(--c-warning); box-shadow: 0 0 30px rgba(245, 158, 11, 0.3); }
    .dial-green { border-color: var(--c-success); color: var(--c-success); box-shadow: 0 0 30px rgba(16, 185, 129, 0.3); }

    .metric-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .metric-label { font-weight: 700; color: #94a3b8; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .metric-val { font-weight: 900; color: #fff; font-size: 1.1rem; }

    /* Live NLP Keyword Pills */
    .kw-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
    .kw-pill { display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; font-weight: 800; transition: 0.3s; background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1); }
    .kw-pill i { font-size: 0.7rem; margin-right: 6px; }
    
    .kw-missing { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border-color: rgba(239, 68, 68, 0.3); }
    .kw-found { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.5); transform: scale(1.05); }

    /* Typo Animation */
    .typing-pulse { animation: blink 1.5s infinite; color: #a855f7; font-weight: bold; }
    @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }

    @media (max-width: 992px) {
        .sidebar-wrapper { max-height: 250px; border-radius: 20px 20px 0 0; border-right: none; border-bottom: 1px solid var(--b-color); }
        .seo-panel { border-radius: 0 0 20px 20px; }
    }
</style>

<div class="beast-container">
    
    <div class="swa-card p-4 p-md-5 mb-4 anim-slide d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div>
            <h2 class="fw-bolder text-dark mb-2" style="font-size: 2.2rem; letter-spacing: -1px;">
                <i class="fas fa-feather-alt me-2 text-indigo-500"></i> Content SEO Engine
            </h2>
            <p class="text-muted fw-medium mb-0 fs-6">Defeat competitors using Live NLP Scoring & Semantic Keyword Tracking.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
            <a href="semrush_writing_assistant.php" class="btn-new w-100 w-md-auto justify-content-center"><i class="fas fa-plus"></i> New Draft</a>
            <button type="button" class="btn-save w-100 w-md-auto" onclick="document.getElementById('editorForm').submit()">
                <i class="fas fa-save"></i> Save Blueprint
            </button>
        </div>
    </div>

    <?php if ($message): ?> 
        <div class="alert alert-<?= $msg_type ?> fw-bold rounded-4 border-0 shadow-sm anim-slide p-3 mb-4 d-flex align-items-center" style="background: <?= $msg_type == 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> fs-4 me-3"></i> 
            <span style="font-size: 1.05rem;"><?= $message ?></span>
        </div> 
    <?php endif; ?>

    <div class="row g-0 swa-card anim-slide anim-delay-1">
        
        <div class="col-lg-2 sidebar-wrapper">
            <div class="p-4 bg-white border-bottom d-flex align-items-center justify-content-between sticky-top">
                <span class="fw-bolder text-dark text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;"><i class="fas fa-archive me-2 text-muted"></i> Vault</span>
                <span class="badge bg-light text-dark border"><?= count($all_drafts) ?></span>
            </div>
            <?php if(empty($all_drafts)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="fas fa-folder-open fa-2x mb-2 opacity-25"></i>
                    <div class="small fw-bold">Vault Empty</div>
                </div>
            <?php endif; ?>
            <?php foreach($all_drafts as $d): 
                $is_active = $d['id'] == $draft_id ? 'draft-active' : '';
                $score_color = $d['seo_score'] >= 80 ? 'text-success' : ($d['seo_score'] >= 50 ? 'text-warning' : 'text-danger');
            ?>
                <a href="?id=<?= $d['id'] ?>" class="draft-item <?= $is_active ?>">
                    <div class="fw-bolder text-dark text-truncate mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($d['title'] ?: 'Untitled Blueprint') ?></div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="small text-muted fw-bold" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i><?= date('d M, H:i', strtotime($d['last_saved'])) ?></span>
                        <span class="fw-bolder <?= $score_color ?>" style="font-size: 0.85rem;"><i class="fas fa-leaf me-1"></i><?= $d['seo_score'] ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-7 p-4 p-md-5 bg-white">
            <form method="POST" id="editorForm">
                <input type="hidden" name="save_draft" value="1">
                <input type="hidden" name="draft_id" value="<?= $draft_id ?>">
                <input type="hidden" name="live_seo_score" id="inputScore" value="<?= $curr_score ?>">
                <input type="hidden" name="live_word_count" id="inputWords" value="<?= $curr_words ?>">

                <div class="mb-4">
                    <label class="form-label">H1 Title 
                        <span class="badge bg-light text-muted border fw-bold" id="titleCount">0/60</span>
                    </label>
                    <input type="text" name="title" id="seoTitle" class="swa-input fs-5 fw-bolder" placeholder="Enter a highly engaging H1 title..." value="<?= htmlspecialchars($curr_title) ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Golden Target Keywords
                        <span class="small text-muted text-transform-none fw-normal"><i class="fas fa-info-circle text-primary me-1"></i>Comma separated</span>
                    </label>
                    <input type="text" id="targetKeywords" name="target_keywords" class="swa-input" placeholder="e.g. smm panel pakistan, cheapest smm panel, likexfollow" value="<?= htmlspecialchars($curr_kws) ?>">
                </div>

                <div class="mb-0">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Content Body (NLP Matrix)</span>
                        <span id="typingIndicator" class="typing-pulse" style="display:none; font-size: 0.8rem;"><i class="fas fa-pen-nib me-1"></i> Engine Analyzing...</span>
                    </label>
                    <textarea name="content" id="liveEditor" class="swa-editor" placeholder="Start writing your top-ranking SEO optimized content here..."><?= htmlspecialchars($curr_content) ?></textarea>
                </div>
            </form>
        </div>

        <div class="col-lg-3 seo-panel">
            <div class="text-center mb-5 border-bottom pb-4" style="border-color: rgba(255,255,255,0.1) !important;">
                <h6 class="fw-bolder text-white text-uppercase mb-4" style="letter-spacing: 1px;"><i class="fas fa-robot text-indigo-400 me-2"></i> NLP Quality Score</h6>
                <div class="score-circle dial-red" id="uiScore"><?= $curr_score ?></div>
                <div class="badge px-4 py-2 mt-3 text-uppercase fw-bolder shadow-sm" id="scoreLabel" style="font-size: 0.85rem; letter-spacing: 0.5px;">Waiting for data...</div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bolder text-white text-uppercase mb-3"><i class="fas fa-chart-pie text-muted me-2"></i> Real-time Metrics</h6>
                
                <div class="metric-row">
                    <span class="metric-label">Word Count</span>
                    <span class="metric-val" id="uiWords"><?= $curr_words ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Reading Time</span>
                    <span class="metric-val" id="uiTime">0 min</span>
                </div>
                <div class="metric-row" style="border-bottom: none;">
                    <span class="metric-label">Title Check</span>
                    <span class="metric-val" id="uiTitleCheck"><i class="fas fa-times text-danger"></i></span>
                </div>
            </div>

            <div>
                <h6 class="fw-bolder text-white text-uppercase mt-2"><i class="fas fa-crosshairs text-danger me-2"></i> Semantic Tracking</h6>
                <p class="small text-muted mb-2 lh-sm">Include these phrases naturally in your text to beat competitors.</p>
                <div class="kw-container" id="kwPillContainer">
                    <div class="small text-muted fst-italic">Enter keywords above to start tracking.</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // --- LIVE SEO ENGINE ALGORITHM ---
    const titleInput = document.getElementById('seoTitle');
    const kwInput = document.getElementById('targetKeywords');
    const editor = document.getElementById('liveEditor');
    
    // UI Elements
    const uiScore = document.getElementById('uiScore');
    const scoreLabel = document.getElementById('scoreLabel');
    const uiWords = document.getElementById('uiWords');
    const uiTime = document.getElementById('uiTime');
    const uiTitleCheck = document.getElementById('uiTitleCheck');
    const kwContainer = document.getElementById('kwPillContainer');
    const typingIndicator = document.getElementById('typingIndicator');
    
    // Hidden inputs for form
    const inputScore = document.getElementById('inputScore');
    const inputWords = document.getElementById('inputWords');

    let typingTimer;

    function runSEOAnalysis() {
        typingIndicator.style.display = 'block';
        clearTimeout(typingTimer);
        
        typingTimer = setTimeout(() => {
            const title = titleInput.value.trim();
            const text = editor.value.trim();
            const rawKws = kwInput.value.split(',').map(k => k.trim().toLowerCase()).filter(k => k.length > 0);
            
            // 1. Word Count & Read Time
            const wordsArray = text.match(/\b[-?(\w+)?]+\b/gi);
            const wordCount = wordsArray ? wordsArray.length : 0;
            uiWords.innerText = wordCount;
            inputWords.value = wordCount;
            
            const readTime = Math.max(1, Math.ceil(wordCount / 200)); // Approx 200 words/min
            uiTime.innerText = wordCount > 0 ? readTime + ' min' : '0 min';

            // 2. Title Analysis
            document.getElementById('titleCount').innerText = title.length + '/60';
            let titleHasKw = false;
            if(rawKws.length > 0 && title.length > 0) {
                const titleLower = title.toLowerCase();
                // Check if at least the Primary (First) Keyword is in Title
                if(titleLower.includes(rawKws[0])) { titleHasKw = true; }
            }
            uiTitleCheck.innerHTML = titleHasKw ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';

            // 3. Keyword Tracking (Pills generation & matching)
            let kwsFoundCount = 0;
            if(rawKws.length > 0) {
                kwContainer.innerHTML = '';
                const textLower = text.toLowerCase();
                
                rawKws.forEach(kw => {
                    const found = textLower.includes(kw);
                    if(found) kwsFoundCount++;
                    
                    const pill = document.createElement('span');
                    pill.className = 'kw-pill ' + (found ? 'kw-found' : 'kw-missing');
                    pill.innerHTML = (found ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-circle-notch fa-spin"></i>') + kw;
                    kwContainer.appendChild(pill);
                });
            } else {
                kwContainer.innerHTML = '<div class="small text-muted fst-italic">Enter keywords above to start tracking.</div>';
            }

            // 4. THE MASTER SCORING ALGORITHM (0-100)
            let score = 0;
            
            // A. Content Length (Max 40 points) - 800 words is considered optimal for basic rank
            let lengthScore = Math.min(40, (wordCount / 800) * 40);
            score += lengthScore;

            // B. Keyword Density/Presence (Max 40 points)
            if(rawKws.length > 0) {
                let kwRatio = kwsFoundCount / rawKws.length;
                score += (kwRatio * 40);
            }

            // C. Title Optimization (Max 20 points)
            if(title.length >= 20 && title.length <= 65) score += 10;
            if(titleHasKw) score += 10;

            score = Math.round(score);
            if(wordCount === 0) score = 0; // Absolute zero if empty
            
            // 5. Update UI Dials
            uiScore.innerText = score;
            inputScore.value = score;

            // Color Logic
            uiScore.className = 'score-circle'; // reset
            scoreLabel.className = 'badge px-4 py-2 mt-3 text-uppercase fw-bolder shadow-sm';
            
            if(score >= 80) {
                uiScore.classList.add('dial-green');
                scoreLabel.classList.add('bg-success', 'text-white');
                scoreLabel.innerText = 'Rank Ready 🚀';
            } else if (score >= 50) {
                uiScore.classList.add('dial-orange');
                scoreLabel.classList.add('bg-warning', 'text-dark');
                scoreLabel.innerText = 'Needs Optimization ⚠️';
            } else {
                uiScore.classList.add('dial-red');
                scoreLabel.classList.add('bg-danger', 'text-white');
                scoreLabel.innerText = 'Critical Errors ❌';
            }

            typingIndicator.style.display = 'none';
        }, 800); // 800ms debounce to prevent freezing while typing
    }

    // Event Listeners for Live Interaction
    titleInput.addEventListener('input', runSEOAnalysis);
    kwInput.addEventListener('input', runSEOAnalysis);
    editor.addEventListener('input', runSEOAnalysis);

    // Initial Run on Load
    document.addEventListener('DOMContentLoaded', runSEOAnalysis);

</script>

<?php 
if (file_exists('_footer.php')) { include '_footer.php'; }
?>