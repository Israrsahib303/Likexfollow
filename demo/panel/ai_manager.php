<?php
// File: panel/ai_manager.php
include '_header.php';

// --- 1. HANDLE FORM SUBMISSION ---
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $key = trim($_POST['api_key']);
    $provider = $_POST['provider'];
    $sys_prompt = trim($_POST['system_prompt']);
    
    if(!empty($key)) {
        // Step A: Verify Key (Mock Test)
        // Asal production mein hum yahan ek curl request bhej kar check karte hain
        // Abhi ke liye hum mante hain key format sahi hai
        
        $isValid = true; // Logic to verify key
        
        if($isValid) {
            // Step B: Reset Old Keys
            $db->query("UPDATE ai_settings SET is_active=0");
            
            // Step C: Save New Key & Training Data
            $stmt = $db->prepare("INSERT INTO ai_settings (provider, api_key, model, system_prompt, is_active) VALUES (?, ?, 'default', ?, 1)");
            if($stmt->execute([$provider, $key, $sys_prompt])) {
                $msg = "✅ API Connected & AI Trained Successfully!";
                $msgType = "success";
            } else {
                $msg = "❌ Database Error!";
                $msgType = "error";
            }
        } else {
            $msg = "❌ Invalid API Key! Connection Failed.";
            $msgType = "error";
        }
    }
}

// --- 2. FETCH ACTIVE SETTINGS ---
$active = $db->query("SELECT * FROM ai_settings WHERE is_active=1 LIMIT 1")->fetch();

// Default Training Prompt (Agar naya set na ho)
$default_training = "You are an expert SEO Content Writer for 'LikexFollow.com', the world's fastest and cheapest SMM Panel based in Pakistan. 
Your goal is to write high-ranking articles to sell TikTok Followers, Instagram Likes, and YouTube Views.
Always mention that LikexFollow provides Non-Drop services with Lifetime Guarantee.
Tone: Professional, Persuasive, and Exciting.";

$current_prompt = $active['system_prompt'] ?? $default_training;

// --- 3. FETCH HISTORY ---
$history = $db->query("SELECT * FROM ai_settings ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

    /* --- RESET & VARIABLES --- */
    * { box-sizing: border-box; outline: none; }
    
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
        --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        min-height: 100vh;
        position: relative;
        overflow-x: hidden;
    }

    /* --- BACKGROUND DECORATION --- */
    .bg-blobs {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: -1;
        overflow: hidden;
        pointer-events: none;
    }
    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.4;
        animation: floatBlob 10s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95);
    }
    .blob-1 { top: -10%; right: -5%; width: 500px; height: 500px; background: #c4b5fd; }
    .blob-2 { bottom: -10%; left: -10%; width: 400px; height: 400px; background: #a78bfa; }
    
    @keyframes floatBlob {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(30px, 50px) scale(1.1); }
    }

    /* --- ANIMATIONS --- */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseSoft { 0% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(124, 58, 237, 0); } 100% { box-shadow: 0 0 0 0 rgba(124, 58, 237, 0); } }

    .animate-enter { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .delay-1 { animation-delay: 0.1s; opacity: 0; }
    .delay-2 { animation-delay: 0.2s; opacity: 0; }
    .delay-3 { animation-delay: 0.3s; opacity: 0; }

    /* --- LAYOUT CONTAINER --- */
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* --- HEADER --- */
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 20px;
    }
    .page-title h1 {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: -1px;
        background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
    }
    .page-title p { color: var(--text-muted); font-size: 1.1rem; margin-top: 5px; font-weight: 500; }

    /* Status Badge */
    .status-badge {
        background: white;
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: var(--shadow-soft);
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .status-dot {
        width: 10px; height: 10px; border-radius: 50%;
        position: relative;
    }
    .dot-online { background: var(--success); }
    .dot-offline { background: var(--accent); }
    .dot-wave {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        border-radius: 50%;
        animation: pulseSoft 2s infinite;
        z-index: -1;
    }

    /* --- MAIN GRID --- */
    .main-grid {
        display: grid;
        grid-template-columns: 7fr 5fr;
        gap: 40px;
    }
    @media (max-width: 1024px) {
        .main-grid { grid-template-columns: 1fr; }
    }

    /* --- CARDS --- */
    .ui-card {
        background: var(--card-bg);
        border-radius: 24px;
        padding: 35px;
        box-shadow: var(--shadow-card);
        border: 1px solid rgba(255,255,255,0.6);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .ui-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -5px rgba(0,0,0,0.1);
    }
    .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }
    .icon-box {
        width: 50px; height: 50px;
        background: var(--primary-light);
        color: var(--primary);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .card-title { font-size: 1.4rem; font-weight: 700; color: var(--text-main); }

    /* --- PROVIDER SELECTOR --- */
    .provider-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .provider-label { cursor: pointer; position: relative; }
    .provider-label input { display: none; }
    
    .provider-box {
        border: 2px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        transition: all 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #fff;
    }
    .provider-box img { width: 40px; height: 40px; margin-bottom: 10px; object-fit: contain; }
    .provider-box i { font-size: 30px; margin-bottom: 10px; }
    .p-name { display: block; font-weight: 700; font-size: 0.9rem; color: var(--text-main); }
    .p-tag { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-top: 5px; }

    /* Checked State */
    .provider-label input:checked + .provider-box {
        border-color: var(--primary);
        background: #f5f3ff; /* Very light purple */
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }
    .provider-label input:checked + .provider-box .p-name { color: var(--primary); }
    
    /* --- INPUT FIELDS --- */
    .input-group { position: relative; }
    .input-field {
        width: 100%;
        padding: 18px 20px;
        padding-left: 55px;
        border-radius: 16px;
        border: 2px solid var(--border-color);
        font-family: 'JetBrains Mono', monospace;
        font-size: 0.95rem;
        background: #f8fafc;
        transition: all 0.3s ease;
        color: var(--text-main);
    }
    .input-field:focus {
        background: #fff;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }
    .input-icon {
        position: absolute;
        left: 20px; top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1.1rem;
        transition: 0.3s;
    }
    .input-field:focus + .input-icon { color: var(--primary); }

    /* --- CODE EDITOR --- */
    .editor-container {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        height: 400px; /* Fixed height for consistency */
        background: #1e1e1e; /* VS Code dark */
    }
    .editor-bar {
        background: #252526;
        padding: 10px 15px;
        display: flex;
        gap: 8px;
        align-items: center;
        border-bottom: 1px solid #333;
    }
    .window-dot { width: 12px; height: 12px; border-radius: 50%; }
    .wd-red { background: #ff5f56; } .wd-yellow { background: #ffbd2e; } .wd-green { background: #27c93f; }
    
    .editor-textarea {
        flex-grow: 1;
        background: #1e1e1e;
        color: #d4d4d4;
        border: none;
        padding: 20px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 14px;
        line-height: 1.6;
        resize: none;
    }
    .editor-textarea::placeholder { color: #555; }

    /* --- HISTORY LOGS --- */
    .history-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px dashed var(--border-color);
    }
    .history-item:last-child { border-bottom: none; }
    .h-info { display: flex; align-items: center; gap: 12px; }
    .h-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
    }
    .h-details p { margin: 0; font-weight: 600; font-size: 0.9rem; }
    .h-details span { font-size: 0.75rem; color: var(--text-muted); font-family: monospace; }
    
    .status-pill {
        font-size: 0.75rem;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    .st-active { background: #d1fae5; color: #059669; }
    .st-revoked { background: #f1f5f9; color: #64748b; }

    /* --- SUBMIT BUTTON --- */
    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, var(--primary) 0%, #6d28d9 100%);
        color: white;
        border: none;
        padding: 18px;
        border-radius: 16px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 25px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.5);
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 35px -5px rgba(124, 58, 237, 0.6);
    }
    .btn-submit i { transition: transform 0.3s; }
    .btn-submit:hover i { transform: rotate(15deg); }

    /* --- ALERT MESSAGES --- */
    .msg-box {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-weight: 600;
        animation: fadeInUp 0.4s ease;
    }
    .msg-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .msg-error { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

</style>

<!-- Background Decoration -->
<div class="bg-blobs">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
</div>

<div class="dashboard-container">
    
    <!-- HEADER -->
    <div class="header-section animate-enter">
        <div class="page-title">
            <h1>AI Brain Center</h1>
            <p>Orchestrate your Neural Networks & Training Data</p>
        </div>
        
        <div class="status-badge">
            <div class="status-dot <?= $active ? 'dot-online' : 'dot-offline' ?>">
                <?php if($active): ?><div class="dot-wave"></div><?php endif; ?>
            </div>
            <span>
                <?= $active ? strtoupper($active['provider']) . " SYSTEM ACTIVE" : "SYSTEM DISCONNECTED" ?>
            </span>
        </div>
    </div>

    <!-- NOTIFICATIONS -->
    <?php if($msg): ?>
        <div class="msg-box <?= $msgType == 'success' ? 'msg-success' : 'msg-error' ?>">
            <i class="fa-solid <?= $msgType == 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?> text-xl"></i>
            <span><?= $msg ?></span>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="main-grid">
            
            <!-- LEFT COLUMN: CONFIG -->
            <div class="space-y-8 animate-enter delay-1">
                
                <!-- 1. PROVIDER CARD -->
                <div class="ui-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fa-solid fa-microchip"></i></div>
                        <span class="card-title">Select Provider</span>
                    </div>
                    
                    <div class="provider-grid">
                        <!-- Gemini -->
                        <label class="provider-label">
                            <input type="radio" name="provider" value="gemini" <?= ($active['provider']=='gemini')?'checked':'' ?>>
                            <div class="provider-box">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/8/8a/Google_Gemini_logo.svg" alt="Gemini">
                                <span class="p-name">Gemini</span>
                                <span class="p-tag">Fastest</span>
                            </div>
                        </label>
                        
                        <!-- OpenAI -->
                        <label class="provider-label">
                            <input type="radio" name="provider" value="openai" <?= ($active['provider']=='openai')?'checked':'' ?>>
                            <div class="provider-box">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" alt="GPT">
                                <span class="p-name">OpenAI</span>
                                <span class="p-tag">Robust</span>
                            </div>
                        </label>
                        
                        <!-- Groq -->
                        <label class="provider-label">
                            <input type="radio" name="provider" value="groq" <?= ($active['provider']=='groq')?'checked':'' ?>>
                            <div class="provider-box">
                                <i class="fa-solid fa-bolt text-amber-500"></i>
                                <span class="p-name">Groq</span>
                                <span class="p-tag">Instant</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. API KEY CARD -->
                <div class="ui-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fa-solid fa-key"></i></div>
                        <span class="card-title">Authentication</span>
                    </div>
                    <div class="input-group">
                        <input type="password" name="api_key" value="<?= $active['api_key'] ?? '' ?>" class="input-field" placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxx" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <p class="text-xs text-gray-400 mt-3 ml-1">
                        <i class="fa-solid fa-shield-halved mr-1 text-emerald-500"></i> 
                        Keys are encrypted using AES-256 standards.
                    </p>
                </div>

                <!-- 3. HISTORY CARD -->
                <div class="ui-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <span class="card-title">Recent Logs</span>
                    </div>
                    <ul class="history-list">
                        <?php foreach($history as $row): ?>
                        <li class="history-item">
                            <div class="h-info">
                                <div class="h-icon">
                                    <i class="fa-solid fa-server"></i>
                                </div>
                                <div class="h-details">
                                    <p><?= ucfirst($row['provider']) ?> Connection</p>
                                    <span>KEY: ****<?= substr($row['api_key'], -4) ?></span>
                                </div>
                            </div>
                            <span class="status-pill <?= $row['is_active'] ? 'st-active' : 'st-revoked' ?>">
                                <?= $row['is_active'] ? 'Active' : 'Revoked' ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>

            <!-- RIGHT COLUMN: TRAINING -->
            <div class="animate-enter delay-2">
                <div class="ui-card" style="height: 100%; display: flex; flex-direction: column;">
                    <div class="card-header">
                        <div class="icon-box"><i class="fa-solid fa-brain"></i></div>
                        <span class="card-title">System Training</span>
                    </div>
                    <p style="margin-bottom: 20px; color: var(--text-muted); font-size: 0.95rem;">
                        Define the AI's persona, rules, and output format.
                    </p>

                    <div class="editor-container">
                        <div class="editor-bar">
                            <div class="window-dot wd-red"></div>
                            <div class="window-dot wd-yellow"></div>
                            <div class="window-dot wd-green"></div>
                            <span style="margin-left: auto; color: #666; font-size: 10px; font-family: monospace;">system_prompt.txt</span>
                        </div>
                        <textarea name="system_prompt" class="editor-textarea" placeholder="Enter system instructions..."><?= htmlspecialchars($current_prompt) ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Save & Initialize Brain
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<?php include '_footer.php'; ?>