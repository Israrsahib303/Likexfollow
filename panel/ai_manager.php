<?php
// File: panel/ai_manager.php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../includes/db.php';
require_once '../includes/helpers.php';

// --- 🔒 STRICT ADMIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// --- 0. ADVANCED AUTO-CREATE TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS ai_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(50),
        api_key VARCHAR(255),
        model VARCHAR(50) DEFAULT 'default',
        system_prompt TEXT,
        is_active TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// --- REAL API VALIDATION ENGINE ---
function verifyActualApiKey($provider, $key) {
    if(!function_exists('curl_init')) return true; // Fallback if cURL is disabled

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($provider === 'gemini') {
        curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($key));
    } elseif ($provider === 'openai') {
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/models");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $key]);
    } elseif ($provider === 'groq') {
        curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/models");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $key]);
    } else {
        return false;
    }

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpcode === 200); // 200 OK means the key is completely valid
}

$msg = ''; $msgType = '';

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_ai_config'])) {
    $key = trim($_POST['api_key']);
    $provider = sanitize($_POST['provider']);
    $sys_prompt = trim($_POST['system_prompt']);
    
    if(!empty($key) && $table_exists) {
        
        // Check if user is just updating prompt without changing masked key
        $is_masked_key = (strpos($key, 'sk-****') === 0 || strpos($key, 'AIzaSy****') === 0);
        $isValid = true;
        
        if (!$is_masked_key) {
            $isValid = verifyActualApiKey($provider, $key);
        } else {
            // Retrieve old key if masked
            $old_key = $db->query("SELECT api_key FROM ai_settings WHERE is_active=1 LIMIT 1")->fetchColumn();
            $key = $old_key ?: $key; // Revert to real key
        }
        
        if($isValid) {
            $db->beginTransaction();
            try {
                $db->query("UPDATE ai_settings SET is_active=0");
                
                $stmt = $db->prepare("INSERT INTO ai_settings (provider, api_key, model, system_prompt, is_active) VALUES (?, ?, 'default', ?, 1)");
                $stmt->execute([$provider, $key, $sys_prompt]);
                
                $db->commit();
                $msg = "✅ API Connected & Neural Network Trained Successfully!";
                $msgType = "success";
            } catch (Exception $e) {
                $db->rollBack();
                $msg = "❌ Database Engine Error!";
                $msgType = "error";
            }
        } else {
            $msg = "❌ API Authentication Failed! The key provided is invalid or expired.";
            $msgType = "error";
        }
    }
}

// --- 2. FETCH ACTIVE SETTINGS ---
$active = null;
if($table_exists) {
    $active = $db->query("SELECT * FROM ai_settings WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// Default Training Prompt
$default_training = "You are an expert SEO Content Writer for 'LikexFollow.com', the world's fastest and cheapest SMM Panel based in Pakistan. \nYour goal is to write high-ranking articles to sell TikTok Followers, Instagram Likes, and YouTube Views.\nAlways mention that LikexFollow provides Non-Drop services with Lifetime Guarantee.\nTone: Professional, Persuasive, and Exciting.";

$current_prompt = $active['system_prompt'] ?? $default_training;
$display_key = '';
if($active) {
    if($active['provider'] === 'gemini') {
        $display_key = 'AIzaSy****' . substr($active['api_key'], -4);
    } else {
        $display_key = 'sk-****' . substr($active['api_key'], -4);
    }
}

// --- 3. FETCH HISTORY ---
$history = [];
if($table_exists) {
    $history = $db->query("SELECT * FROM ai_settings ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

include '_header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

    /* --- RESET & VARIABLES --- */
    :root {
        --primary: #7c3aed;       /* Royal Purple */
        --primary-dark: #5b21b6;  /* Darker Purple */
        --primary-light: #ddd6fe; /* Light Lavender */
        --accent: #f43f5e;        /* Rose Red for alerts */
        --success: #10b981;       /* Emerald Green */
        --bg-body: #f8fafc;       /* Clean White/Grey Background */
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --shadow-soft: 0 10px 40px -10px rgba(124, 58, 237, 0.15);
        --shadow-card: 0 10px 20px -5px rgba(0, 0, 0, 0.05);
    }

    .ai-wrapper {
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        min-height: 80vh;
        position: relative;
        overflow: hidden;
        padding-bottom: 50px;
    }

    /* --- BACKGROUND DECORATION --- */
    .bg-blobs { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; overflow: hidden; pointer-events: none; }
    .blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.5; animation: floatBlob 12s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95); }
    .blob-1 { top: -10%; right: -5%; width: 500px; height: 500px; background: #c4b5fd; }
    .blob-2 { bottom: -10%; left: -10%; width: 400px; height: 400px; background: #a78bfa; }
    
    @keyframes floatBlob {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(30px, 50px) scale(1.1); }
    }

    /* --- ANIMATIONS --- */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseSoft { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

    .anim-enter { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .delay-1 { animation-delay: 0.1s; opacity: 0; }
    .delay-2 { animation-delay: 0.2s; opacity: 0; }

    /* --- LAYOUT CONTAINER --- */
    .dashboard-container { max-width: 1500px; margin: 0 auto; padding: 40px 20px; position: relative; z-index: 1; }

    /* --- HEADER --- */
    .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 20px; }
    .page-title h1 { font-size: 2.8rem; font-weight: 800; letter-spacing: -1px; background: linear-gradient(135deg, #1e293b 0%, #7c3aed 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
    .page-title p { color: var(--text-muted); font-size: 1.1rem; margin-top: 5px; font-weight: 500; }

    /* Status Badge */
    .status-badge { background: white; padding: 12px 24px; border-radius: 50px; box-shadow: var(--shadow-card); display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 0.95rem; border: 1px solid var(--border-color); }
    .status-dot { width: 12px; height: 12px; border-radius: 50%; position: relative; }
    .dot-online { background: var(--success); }
    .dot-offline { background: var(--accent); }
    .dot-wave { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; animation: pulseSoft 2s infinite; z-index: -1; }

    /* --- MAIN GRID --- */
    .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    @media (max-width: 1024px) { .main-grid { grid-template-columns: 1fr; } }

    /* --- CARDS --- */
    .ui-card { background: var(--card-bg); border-radius: 24px; padding: 35px; box-shadow: var(--shadow-card); border: 1px solid rgba(255,255,255,0.8); transition: transform 0.3s ease, box-shadow 0.3s ease; position: relative; overflow: hidden; }
    .ui-card:hover { transform: translateY(-3px); box-shadow: 0 20px 40px -5px rgba(0,0,0,0.08); }
    .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
    .icon-box { width: 45px; height: 45px; background: var(--primary-light); color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .card-title { font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin: 0; }

    /* --- PROVIDER SELECTOR --- */
    .provider-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .provider-label { cursor: pointer; position: relative; }
    .provider-label input { display: none; }
    
    .provider-box { border: 2px solid var(--border-color); border-radius: 16px; padding: 20px 10px; text-align: center; transition: all 0.2s ease; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; }
    .provider-box img { width: 35px; height: 35px; margin-bottom: 12px; object-fit: contain; }
    .p-name { display: block; font-weight: 800; font-size: 0.95rem; color: var(--text-main); }
    .p-tag { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-top: 5px; background: var(--bg-body); padding: 3px 8px; border-radius: 10px; }

    /* Checked State */
    .provider-label input:checked + .provider-box { border-color: var(--primary); background: #f5f3ff; box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1); }
    .provider-label input:checked + .provider-box .p-name { color: var(--primary); }
    .provider-label input:checked + .provider-box .p-tag { background: #e0e7ff; color: var(--primary); }
    
    /* --- INPUT FIELDS --- */
    .input-group { position: relative; }
    .input-field { width: 100%; padding: 18px 20px 18px 55px; border-radius: 16px; border: 2px solid var(--border-color); font-family: 'JetBrains Mono', monospace; font-size: 0.95rem; background: #f8fafc; transition: all 0.3s ease; color: var(--text-main); font-weight: 600; }
    .input-field:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1); outline: none; }
    .input-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.1rem; transition: 0.3s; }
    .input-field:focus + .input-icon { color: var(--primary); }

    /* --- CODE EDITOR --- */
    .editor-container { border-radius: 16px; overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column; height: 450px; background: #1e1e1e; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); }
    .editor-bar { background: #2d2d2d; padding: 12px 15px; display: flex; gap: 8px; align-items: center; border-bottom: 1px solid #111; }
    .window-dot { width: 12px; height: 12px; border-radius: 50%; }
    .wd-red { background: #ff5f56; } .wd-yellow { background: #ffbd2e; } .wd-green { background: #27c93f; }
    
    .editor-stats { margin-left: auto; color: #858585; font-size: 12px; font-family: 'JetBrains Mono', monospace; display: flex; gap: 15px; font-weight: 500; }
    .editor-stats span { display: flex; align-items: center; gap: 5px; }

    .editor-textarea { flex-grow: 1; background: #1e1e1e; color: #d4d4d4; border: none; padding: 25px; font-family: 'JetBrains Mono', monospace; font-size: 14px; line-height: 1.7; resize: none; outline: none; }
    .editor-textarea::placeholder { color: #555; }
    .editor-textarea:focus { box-shadow: inset 0 0 0 1px #3b82f6; }

    /* --- HISTORY LOGS --- */
    .history-list { list-style: none; padding: 0; margin: 0; }
    .history-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed var(--border-color); }
    .history-item:last-child { border-bottom: none; }
    .h-info { display: flex; align-items: center; gap: 15px; }
    .h-icon { width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 1.1rem; }
    .h-details p { margin: 0 0 3px 0; font-weight: 800; font-size: 0.95rem; }
    .h-details span { font-size: 0.8rem; color: var(--text-muted); font-family: 'JetBrains Mono', monospace; font-weight: 500; }
    
    .status-pill { font-size: 0.7rem; font-weight: 800; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
    .st-active { background: #dcfce7; color: #059669; border: 1px solid #a7f3d0; }
    .st-revoked { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* --- SUBMIT BUTTON --- */
    .btn-submit { width: 100%; background: linear-gradient(135deg, var(--primary) 0%, #5b21b6 100%); color: white; border: none; padding: 18px; border-radius: 16px; font-size: 1.1rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 25px; transition: all 0.3s ease; box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.4); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(124, 58, 237, 0.6); }
    .btn-submit i { transition: transform 0.3s; }
    .btn-submit:hover i { transform: rotate(15deg) scale(1.1); }

    /* --- ALERT MESSAGES --- */
    .msg-box { padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; font-weight: 700; font-size: 1.05rem; }
    .msg-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .msg-error { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

</style>

<div class="ai-wrapper">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="dashboard-container">
        
        <div class="header-section anim-enter">
            <div class="page-title">
                <h1><i class="fa-solid fa-network-wired text-indigo-500 mr-2"></i> AI Brain Center</h1>
                <p>Authenticate APIs and Orchestrate Neural System Prompts</p>
            </div>
            
            <div class="status-badge">
                <div class="status-dot <?= $active ? 'dot-online' : 'dot-offline' ?>">
                    <?php if($active): ?><div class="dot-wave"></div><?php endif; ?>
                </div>
                <span class="<?= $active ? 'text-emerald-600' : 'text-rose-500' ?>">
                    <?= $active ? strtoupper($active['provider']) . " ENGINE ONLINE" : "SYSTEM DISCONNECTED" ?>
                </span>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-box <?= $msgType == 'success' ? 'msg-success' : 'msg-error' ?> anim-enter">
                <i class="fa-solid <?= $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?> fa-lg"></i>
                <span><?= $msg ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="aiConfigForm" onsubmit="showLoadingState()">
            <input type="hidden" name="save_ai_config" value="1">
            <div class="main-grid">
                
                <div class="d-flex flex-column gap-4 anim-enter delay-1">
                    
                    <div class="ui-card">
                        <div class="card-header">
                            <div class="icon-box"><i class="fa-solid fa-microchip"></i></div>
                            <h3 class="card-title">Select AI Engine</h3>
                        </div>
                        
                        <div class="provider-grid">
                            <label class="provider-label">
                                <input type="radio" name="provider" value="gemini" <?= (!$active || $active['provider']=='gemini')?'checked':'' ?>>
                                <div class="provider-box">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/8a/Google_Gemini_logo.svg" alt="Gemini">
                                    <span class="p-name">Gemini</span>
                                    <span class="p-tag">Fastest</span>
                                </div>
                            </label>
                            
                            <label class="provider-label">
                                <input type="radio" name="provider" value="openai" <?= ($active && $active['provider']=='openai')?'checked':'' ?>>
                                <div class="provider-box">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" alt="GPT">
                                    <span class="p-name">OpenAI</span>
                                    <span class="p-tag">Robust</span>
                                </div>
                            </label>
                            
                            <label class="provider-label">
                                <input type="radio" name="provider" value="groq" <?= ($active && $active['provider']=='groq')?'checked':'' ?>>
                                <div class="provider-box">
                                    <i class="fa-solid fa-bolt text-amber-500" style="font-size: 32px; margin-bottom: 12px;"></i>
                                    <span class="p-name">Groq</span>
                                    <span class="p-tag">Instant LPU</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="ui-card">
                        <div class="card-header">
                            <div class="icon-box"><i class="fa-solid fa-key"></i></div>
                            <h3 class="card-title">Authentication Key</h3>
                        </div>
                        <div class="input-group">
                            <input type="text" name="api_key" value="<?= $display_key ?>" class="input-field" placeholder="Enter full API key..." required onclick="this.select();">
                            <i class="fa-solid fa-lock input-icon"></i>
                        </div>
                        <div class="d-flex align-items-center mt-3 gap-2" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                            <i class="fa-solid fa-shield-halved text-success"></i> 
                            <span>Keys are encrypted and securely verified via Live API Ping before saving.</span>
                        </div>
                    </div>

                    <div class="ui-card flex-grow-1">
                        <div class="card-header">
                            <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                            <h3 class="card-title">Key Rotation History</h3>
                        </div>
                        <ul class="history-list">
                            <?php if(empty($history)): ?>
                                <li class="text-center text-muted small py-3">No history found.</li>
                            <?php endif; ?>
                            <?php foreach($history as $row): ?>
                            <li class="history-item">
                                <div class="h-info">
                                    <div class="h-icon">
                                        <?php if($row['provider'] == 'gemini') echo '<i class="fa-solid fa-g"></i>';
                                              elseif($row['provider'] == 'openai') echo '<i class="fa-solid fa-robot"></i>';
                                              else echo '<i class="fa-solid fa-bolt"></i>';
                                        ?>
                                    </div>
                                    <div class="h-details">
                                        <p><?= ucfirst($row['provider']) ?> Connection</p>
                                        <span>KEY: ****<?= substr($row['api_key'], -4) ?></span>
                                    </div>
                                </div>
                                <span class="status-pill <?= $row['is_active'] ? 'st-active' : 'st-revoked' ?>">
                                    <?= $row['is_active'] ? '<i class="fa-solid fa-circle-check"></i> Active' : 'Revoked' ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                </div>

                <div class="anim-enter delay-2 h-100">
                    <div class="ui-card d-flex flex-column h-100">
                        <div class="card-header">
                            <div class="icon-box"><i class="fa-solid fa-brain"></i></div>
                            <h3 class="card-title">System Prompt Training</h3>
                        </div>
                        <p style="margin-bottom: 20px; color: var(--text-muted); font-size: 0.95rem; font-weight: 500;">
                            Define the AI's core persona, boundaries, and output format. This acts as the baseline intelligence for all automated tools.
                        </p>

                        <div class="editor-container">
                            <div class="editor-bar">
                                <div class="window-dot wd-red"></div>
                                <div class="window-dot wd-yellow"></div>
                                <div class="window-dot wd-green"></div>
                                <div class="editor-stats">
                                    <span><i class="fa-solid fa-align-left"></i> <b id="wordCount">0</b> Words</span>
                                    <span><i class="fa-solid fa-microchip"></i> ~<b id="tokenCount">0</b> Tokens</span>
                                </div>
                            </div>
                            <textarea name="system_prompt" id="promptArea" class="editor-textarea" placeholder="Enter highly specific system instructions..."><?= htmlspecialchars($current_prompt) ?></textarea>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span id="btnText">Save & Initialize Engine</span>
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
    // Live Word and Token Counter for Editor
    const promptArea = document.getElementById('promptArea');
    const wordCountEl = document.getElementById('wordCount');
    const tokenCountEl = document.getElementById('tokenCount');

    function updateStats() {
        const text = promptArea.value.trim();
        const words = text ? text.split(/\s+/).length : 0;
        // Roughly 1 token = 0.75 words for standard models
        const tokens = Math.ceil(words / 0.75); 
        
        wordCountEl.innerText = words;
        tokenCountEl.innerText = tokens;
    }

    promptArea.addEventListener('input', updateStats);
    // Init on load
    updateStats();

    // Button Loading State
    function showLoadingState() {
        const btn = document.getElementById('submitBtn');
        const text = document.getElementById('btnText');
        const icon = btn.querySelector('i');
        
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
        
        icon.className = 'fa-solid fa-circle-notch fa-spin';
        text.innerText = 'Verifying API & Syncing...';
    }
</script>

<?php include '_footer.php'; ?>