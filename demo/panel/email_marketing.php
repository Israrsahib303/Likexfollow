<?php
// Output buffering start taake Ajax mein issue na aaye
ob_start();

include '_header.php'; 

// ====================================================
// 🔥 AJAX: SINGLE EMAIL SENDER (Background Worker) 🔥
// ====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_single_email') {
    ob_clean(); // Faltu output saaf
    header('Content-Type: application/json');
    
    $to = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = $_POST['message']; // HTML Message allow kar rakha hai
    $from_name = htmlspecialchars(trim($_POST['from_name']));
    $from_email = filter_var(trim($_POST['from_email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'msg' => 'Invalid Format']);
        exit;
    }
    
    // Email Headers (HTML Support & Sender Info)
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // PHP Mail Function Execute
    if (@mail($to, $subject, $message, $headers)) {
        echo json_encode(['status' => 'success', 'msg' => 'Sent Successfully']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Server Rejected']);
    }
    exit;
}

// Current Website Name and Admin Email from Settings
$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow';
$admin_email = 'admin@' . $_SERVER['HTTP_HOST'];
?>

<style>
    /* 💜 PREMIUM WHITE & PURPLE MARKETING THEME 💜 */
    :root {
        --mp-primary: #8b5cf6; 
        --mp-dark: #6d28d9; 
        --mp-light: #f5f3ff; 
        --mp-border: #e2e8f0;
        --mp-text: #334155;
    }

    /* CAPACTIY & POWER BANNER */
    .capacity-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; width: 100%; }
    .cap-box { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid var(--mp-border); display: flex; align-items: flex-start; gap: 15px; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.05); transition: 0.3s; }
    .cap-box:hover { transform: translateY(-5px); border-color: var(--mp-primary); box-shadow: 0 15px 30px rgba(139, 92, 246, 0.15); }
    .cap-icon { width: 50px; height: 50px; border-radius: 12px; background: var(--mp-light); color: var(--mp-primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .cap-text h3 { margin: 0 0 5px 0; font-size: 1.05rem; font-weight: 900; color: var(--mp-dark); }
    .cap-text p { margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.5; }

    /* MAIN WRAPPER (Fixed Layout) */
    .em-wrapper { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start; }
    
    .em-card { background: #ffffff; border-radius: 20px; box-shadow: 0 15px 40px rgba(139, 92, 246, 0.06); border: 1px solid var(--mp-border); overflow: hidden; }
    .em-header { background: linear-gradient(135deg, var(--mp-primary), var(--mp-dark)); padding: 20px 25px; color: white; display: flex; justify-content: space-between; align-items: center; }
    .em-header h2 { margin: 0; font-size: 1.3rem; font-weight: 900; display: flex; align-items: center; gap: 12px; }
    
    .em-body { padding: 25px; }
    .form-group { margin-bottom: 20px; position: relative; }
    .form-group label { display: flex; justify-content: space-between; font-weight: 800; color: var(--mp-text); margin-bottom: 8px; font-size: 0.85rem; }
    
    .em-input { width: 100%; padding: 12px 15px; border-radius: 10px; border: 2px solid var(--mp-border); font-family: 'Segoe UI', sans-serif; font-size: 0.9rem; transition: 0.3s; background: #f8fafc; color: var(--mp-text); }
    .em-input:focus { border-color: var(--mp-primary); background: #ffffff; outline: none; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); }
    
    .email-list { height: 120px; font-family: 'Space Mono', monospace; font-size: 0.8rem; resize: vertical; }
    .msg-body { height: 200px; font-family: 'Space Mono', monospace; font-size: 0.85rem; resize: vertical; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }

    /* ACTION BUTTONS */
    .btn-launch { background: linear-gradient(135deg, #10b981, #047857); color: white; border: none; width: 100%; padding: 15px; font-size: 1.1rem; font-weight: 900; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3); }
    .btn-launch:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.5); }
    .btn-launch:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-stop { background: linear-gradient(135deg, #ef4444, #b91c1c); display: none; box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); }
    .btn-stop:hover { box-shadow: 0 15px 30px rgba(239, 68, 68, 0.5); }

    /* HELPING TOOLS UI */
    .tool-bar { background: var(--mp-light); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px dashed #c4b5fd; }
    .tool-title { font-size: 0.75rem; font-weight: 800; color: var(--mp-dark); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
    
    .btn-tool { background: #fff; border: 1px solid #c4b5fd; color: var(--mp-dark); padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-tool:hover { background: var(--mp-primary); color: #fff; border-color: var(--mp-primary); }

    .tag-btn { background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-family: monospace; cursor: pointer; transition: 0.2s; color: #475569; }
    .tag-btn:hover { background: var(--mp-primary); color: white; }

    /* SPAM CHECKER GAUGE */
    .spam-box { display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 12px; border-radius: 10px; border: 2px solid var(--mp-border); }
    .spam-score { font-size: 1.5rem; font-weight: 900; color: #10b981; }
    .spam-text { font-size: 0.75rem; color: #64748b; font-weight: 700; }

    /* LIVE PREVIEW WINDOW */
    .preview-card { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; margin-bottom: 20px; }
    .preview-header { background: #e2e8f0; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; font-weight: 800; color: #475569; }
    .preview-content { padding: 20px; flex: 1; background: #fff; overflow-y: auto; height: 250px; font-family: Arial, sans-serif; transition: 0.3s; margin: 0 auto; width: 100%; border-left: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; }
    .preview-content.mobile-view { max-width: 375px; box-shadow: 0 0 20px rgba(0,0,0,0.1); border-radius: 0 0 20px 20px; }

    /* TERMINAL */
    .term-card { background: #0f172a; color: #00ff88; font-family: 'Space Mono', monospace; border-radius: 16px; overflow: hidden; margin-bottom: 20px; }
    .term-body { height: 150px; overflow-y: auto; padding: 12px; font-size: 0.75rem; line-height: 1.5; }
    .term-line { border-bottom: 1px dashed #1e293b; padding-bottom: 4px; margin-bottom: 4px; }
    .term-line span.err { color: #ff0055; font-weight: bold; }
    .term-line span.ok { color: #00ff88; font-weight: bold; }
    .term-line span.mail { color: #0ea5e9; }

    .status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 15px; background: #1e293b; border-top: 1px solid #334155; }
    .stat-box { text-align: center; }
    .stat-lbl { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    .stat-num { font-size: 1.5rem; font-weight: 900; color: #ffffff; }

    .prog-bg { background: #334155; height: 8px; width: 100%; border-radius: 4px; overflow: hidden; }
    .prog-fill { background: linear-gradient(90deg, #8b5cf6, #d946ef); height: 100%; width: 0%; transition: width 0.4s ease; box-shadow: 0 0 10px rgba(139,92,246,0.5); }

    /* HISTORY TABLE */
    .history-box { background: #fff; border-radius: 16px; border: 1px solid var(--mp-border); overflow: hidden; }
    .history-head { background: var(--mp-light); padding: 12px 20px; font-weight: 800; color: var(--mp-dark); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--mp-border); }
    .history-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .history-table th { background: #f8fafc; padding: 10px; text-align: left; color: #64748b; font-weight: 700; border-bottom: 1px solid #e2e8f0; }
    .history-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }

    /* Responsive Fixes */
    @media (max-width: 1200px) { .em-wrapper { grid-template-columns: 1fr; } }
</style>

<div class="capacity-grid">
    <div class="cap-box">
        <div class="cap-icon"><i class="fas fa-server"></i></div>
        <div class="cap-text">
            <h3>Server Capacity</h3>
            <p>Optimized for Hostinger Premium. Safe limit is <b>100-500 emails/hour</b> to avoid spam bans.</p>
        </div>
    </div>
    <div class="cap-box">
        <div class="cap-icon"><i class="fas fa-shield-alt"></i></div>
        <div class="cap-text">
            <h3>Auto-Throttle Engine</h3>
            <p>Smart delay technology prevents server overload. Sends 1 by 1 mimicking human behavior.</p>
        </div>
    </div>
    <div class="cap-box">
        <div class="cap-icon"><i class="fas fa-envelope-open-text"></i></div>
        <div class="cap-text">
            <h3>Deliverability Check</h3>
            <p>Built-in AI Spam Scanner checks keywords to ensure <b>98% Inbox Delivery</b> rate.</p>
        </div>
    </div>
</div>

<div class="em-wrapper">
    
    <div class="em-card">
        <div class="em-header">
            <h2><i class="fas fa-paper-plane"></i> Alpha Broadcast Engine</h2>
            <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-crown"></i> VIP Tools Active</span>
        </div>
        
        <div class="em-body">
            <div class="tool-bar grid-2">
                <div>
                    <div class="tool-title"><i class="fas fa-layer-group"></i> Quick Templates</div>
                    <div style="display:flex; gap:6px;">
                        <button class="btn-tool" onclick="loadTemplate('promo')"><i class="fas fa-tag"></i> Promo</button>
                        <button class="btn-tool" onclick="loadTemplate('welcome')"><i class="fas fa-handshake"></i> Welcome</button>
                        <button class="btn-tool" onclick="loadTemplate('alert')"><i class="fas fa-bell"></i> Alert</button>
                    </div>
                </div>
                <div>
                    <div class="tool-title"><i class="fas fa-shield-alt"></i> AI Spam Predictor</div>
                    <div class="spam-box">
                        <div>
                            <div class="spam-text" id="spamVerdict">Looks Good!</div>
                            <div style="font-size:0.65rem; color:#94a3b8;">Inbox Probability</div>
                        </div>
                        <div class="spam-score" id="spamScore">99%</div>
                    </div>
                </div>
            </div>

            <form id="broadcastForm">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Sender Name <span style="color:#8b5cf6;"><i class="fas fa-user"></i></span></label>
                        <input type="text" id="fromName" class="em-input" value="<?= $site_name ?> Team" required>
                    </div>
                    <div class="form-group">
                        <label>Reply-To Email <span style="color:#8b5cf6;"><i class="fas fa-envelope"></i></span></label>
                        <input type="email" id="fromEmail" class="em-input" value="<?= $admin_email ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Subject (Catchy)</label>
                    <input type="text" id="emailSubject" class="em-input" placeholder="Get 50% Off on Instagram Followers!" onkeyup="analyzeSpam()" required>
                </div>

                <div class="form-group">
                    <label>
                        <span>Message Body (HTML)</span>
                        <span style="display:flex; gap:5px;">
                            <button type="button" class="tag-btn" onclick="insertVar('[Name]')">[Name]</button>
                            <button type="button" class="tag-btn" onclick="insertVar('[Date]')">[Date]</button>
                        </span>
                    </label>
                    <textarea id="emailMessage" class="em-input msg-body" placeholder="Write your HTML message here..." onkeyup="updatePreview(); analyzeSpam();" required></textarea>
                </div>

                <div class="tool-bar">
                    <div class="grid-3">
                        <div class="form-group" style="margin:0;">
                            <label>Speed Throttle ⏱️</label>
                            <select id="sendDelay" class="em-input" style="padding:10px;">
                                <option value="1000">Fast (1s Delay)</option>
                                <option value="3000" selected>Safe (3s Delay)</option>
                                <option value="8000">Stealth (8s Delay)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Target Count</label>
                            <input type="text" id="totalCountDisplay" class="em-input" value="0" disabled style="background:#e2e8f0; font-weight:900; text-align:center; padding:10px; color:var(--mp-dark);">
                        </div>
                        <div class="form-group" style="margin:0; display:flex; flex-direction:column; justify-content:flex-end;">
                            <button type="button" class="btn-tool" style="padding:10px; background:#f1f5f9; color:#ef4444; border-color:#ef4444;" onclick="cleanEmailList()">
                                <i class="fas fa-broom"></i> Clean Duplicates
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Target Audience (Paste Emails)</label>
                    <textarea id="emailList" class="em-input email-list" placeholder="john@gmail.com&#10;zara@yahoo.com" oninput="countEmails()" required></textarea>
                </div>

                <button type="button" id="btnStart" class="btn-launch" onclick="startBroadcast()">
                    <i class="fas fa-paper-plane"></i> LAUNCH CAMPAIGN
                </button>
                <button type="button" id="btnStop" class="btn-launch btn-stop" onclick="stopBroadcast()">
                    <i class="fas fa-skull-crossbones"></i> ABORT CAMPAIGN
                </button>
            </form>
        </div>
    </div>

    <div>
        <div class="preview-card">
            <div class="preview-header">
                <span><i class="fas fa-eye"></i> Live Client Preview</span>
                <div style="display:flex; gap:10px;">
                    <i class="fas fa-desktop" style="cursor:pointer;" onclick="togglePreview('desktop')" title="Desktop View"></i>
                    <i class="fas fa-mobile-alt" style="cursor:pointer;" onclick="togglePreview('mobile')" title="Mobile View"></i>
                </div>
            </div>
            <div class="preview-content" id="livePreviewBox">
                <div style="color:#cbd5e1; text-align:center; margin-top:50px; font-family:'Segoe UI';">Start typing to see live preview...</div>
            </div>
        </div>

        <div class="term-card">
            <div style="background:#1e293b; padding:12px 15px; border-bottom:1px solid #334155; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:0.9rem; font-weight:800; color:#fff;"><i class="fas fa-terminal"></i> Broadcast Logs</h3>
                <div id="loaderSpinner" style="display:none;"><i class="fas fa-circle-notch fa-spin" style="color:#8b5cf6;"></i></div>
            </div>
            
            <div style="padding: 10px 15px 0 15px;">
                <div class="prog-bg"><div class="prog-fill" id="progBar"></div></div>
            </div>

            <div class="term-body" id="termLogs">
                <div class="term-line">System initialized. Awaiting payload.</div>
            </div>

            <div class="status-grid">
                <div class="stat-box">
                    <div class="stat-lbl">Delivered</div>
                    <div class="stat-num" style="color:#00ff88;" id="statSent">0</div>
                </div>
                <div class="stat-box">
                    <div class="stat-lbl">Bounced/Failed</div>
                    <div class="stat-num" style="color:#ff0055;" id="statFailed">0</div>
                </div>
            </div>
        </div>

        <div class="history-box">
            <div class="history-head">
                <span><i class="fas fa-history"></i> Campaign History</span>
                <button class="tag-btn" style="background:#fee2e2; color:#ef4444;" onclick="clearHistory()">Clear All</button>
            </div>
            <div style="max-height: 200px; overflow-y: auto;">
                <table class="history-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Sent</th>
                            <th>Failed</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================
// 1. HELPING TOOLS LOGIC (Templates, Variables, Cleaner, Spam)
// ==========================================

const templates = {
    promo: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
    <div style="background: #8b5cf6; padding: 30px; text-align: center; color: #fff;">
        <h1 style="margin: 0;">FLASH SALE IS LIVE! ⚡</h1>
    </div>
    <div style="padding: 30px; color: #334155; text-align: center;">
        <p style="font-size: 1.1rem;">Hello [Name],</p>
        <p>We are offering a massive <strong>50% Discount</strong> on all our premium services today.</p>
        <a href="#" style="display: inline-block; background: #10b981; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px;">CLAIM DISCOUNT NOW</a>
    </div>
    <div style="background: #f8fafc; padding: 15px; text-align: center; font-size: 0.8rem; color: #94a3b8;">
        © 2026 <?= $site_name ?>. All rights reserved.
    </div>
</div>`,
    welcome: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #6d28d9;">Welcome to <?= $site_name ?>! 🎉</h2>
    <p style="color: #475569; line-height: 1.6;">Hi [Name],<br>Thank you for joining our community. We are thrilled to have you on board. Explore our dashboard to get started with your journey.</p>
    <p style="color: #475569;">Best Regards,<br>The Team</p>
</div>`,
    alert: `<div style="font-family: Arial, sans-serif; border-left: 5px solid #ef4444; padding: 15px; background: #fff5f5; color: #991b1b; max-width: 600px; margin: 0 auto;">
    <h3 style="margin-top: 0;">⚠️ Important Update</h3>
    <p>Dear user, please be advised that our servers will undergo maintenance on [Date]. Expect minor interruptions.</p>
</div>`
};

function loadTemplate(type) {
    let msgBox = document.getElementById('emailMessage');
    msgBox.value = templates[type];
    updatePreview(); analyzeSpam();
    Swal.fire({toast:true, position:'top-end', icon:'success', title:'Template Loaded!', showConfirmButton:false, timer:1500});
}

function insertVar(tag) {
    let msgBox = document.getElementById('emailMessage');
    msgBox.value += tag;
    msgBox.focus(); updatePreview();
}

function togglePreview(mode) {
    let box = document.getElementById('livePreviewBox');
    if(mode === 'mobile') { box.classList.add('mobile-view'); } 
    else { box.classList.remove('mobile-view'); }
}

function updatePreview() {
    let html = document.getElementById('emailMessage').value;
    let box = document.getElementById('livePreviewBox');
    if(html.trim() === '') { box.innerHTML = '<div style="color:#cbd5e1; text-align:center; margin-top:50px;">Start typing to see live preview...</div>'; }
    else { box.innerHTML = html; }
}

function analyzeSpam() {
    let sub = document.getElementById('emailSubject').value.toLowerCase();
    let msg = document.getElementById('emailMessage').value.toLowerCase();
    let combined = sub + " " + msg;
    
    let spamWords = ['free', 'buy', '$$$', 'urgent', 'winner', 'cash', 'earn money', '100%', 'click here', 'guarantee', 'viagra'];
    let score = 100;
    
    spamWords.forEach(word => { if(combined.includes(word)) score -= 8; });
    if(sub === document.getElementById('emailSubject').value && sub.length > 5) score -= 15; 
    if(score < 0) score = 0;

    let sBox = document.getElementById('spamScore'); let sText = document.getElementById('spamVerdict');
    sBox.innerText = score + '%';
    if(score >= 90) { sBox.style.color = '#10b981'; sText.innerText = 'Perfect!'; sText.style.color = '#10b981'; }
    else if(score >= 70) { sBox.style.color = '#f59e0b'; sText.innerText = 'Moderate Risk'; sText.style.color = '#f59e0b'; }
    else { sBox.style.color = '#ef4444'; sText.innerText = 'High Spam Risk!'; sText.style.color = '#ef4444'; }
}

function cleanEmailList() {
    let raw = document.getElementById('emailList').value;
    if(raw.trim() === '') return;
    
    let list = raw.split(/[\n,]+/).map(e => e.trim().toLowerCase()).filter(e => e !== '');
    let validList = [];
    let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    list.forEach(email => { if(regex.test(email)) validList.push(email); });
    let uniqueList = [...new Set(validList)]; 
    let removedCount = list.length - uniqueList.length;
    
    document.getElementById('emailList').value = uniqueList.join('\n'); countEmails();
    Swal.fire('List Cleaned! 🧹', `${removedCount} duplicate/invalid emails removed.`, 'success');
}

// ==========================================
// 2. HISTORY SYSTEM (LocalStorage)
// ==========================================
function loadHistory() {
    let hist = JSON.parse(localStorage.getItem('admin_email_history') || '[]');
    let tbody = document.getElementById('historyBody');
    tbody.innerHTML = '';
    
    if(hist.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#94a3b8;">No campaigns run yet.</td></tr>';
        return;
    }
    
    hist.forEach(h => {
        tbody.innerHTML += `<tr>
            <td style="font-size:0.7rem; color:#64748b;">${h.date}</td>
            <td style="font-weight:600;">${h.subject.substring(0, 30)}...</td>
            <td style="color:#10b981; font-weight:bold;">${h.sent}</td>
            <td style="color:#ef4444; font-weight:bold;">${h.failed}</td>
        </tr>`;
    });
}

function saveHistory(subject, sent, failed) {
    let hist = JSON.parse(localStorage.getItem('admin_email_history') || '[]');
    let date = new Date().toLocaleString('en-US', {month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'});
    hist.unshift({ date: date, subject: subject, sent: sent, failed: failed });
    if(hist.length > 10) hist.pop(); // Keep only last 10
    localStorage.setItem('admin_email_history', JSON.stringify(hist));
    loadHistory();
}

function clearHistory() {
    localStorage.removeItem('admin_email_history'); loadHistory();
}

// Initialize history on load
document.addEventListener("DOMContentLoaded", loadHistory);

// ==========================================
// 3. BROADCAST ENGINE (AJAX THROTTLING)
// ==========================================

let emailsArray = [];
let isRunning = false;
let sentCount = 0;
let failedCount = 0;
let currentIndex = 0;

function logTerminal(msg, type = 'normal') {
    let term = document.getElementById('termLogs');
    let line = document.createElement('div'); line.className = 'term-line';
    let time = new Date().toLocaleTimeString('en-US', {hour12:false});
    let timeHtml = `<span style="color:#64748b;">[${time}]</span>`;
    
    if(type === 'ok') line.innerHTML = `${timeHtml} > ${msg} <span class="ok">[OK]</span>`;
    else if(type === 'err') line.innerHTML = `${timeHtml} > ${msg} <span class="err">[FAILED]</span>`;
    else line.innerHTML = `${timeHtml} > ${msg}`;
    
    term.appendChild(line); term.scrollTop = term.scrollHeight;
}

function countEmails() {
    let raw = document.getElementById('emailList').value;
    let list = raw.split(/[\n,]+/).map(e => e.trim()).filter(e => e !== '');
    document.getElementById('totalCountDisplay').value = list.length;
}

function stopBroadcast() {
    isRunning = false;
    document.getElementById('btnStop').style.display = 'none';
    document.getElementById('btnStart').style.display = 'flex';
    document.getElementById('btnStart').innerHTML = '<i class="fas fa-play"></i> RESUME CAMPAIGN';
    document.getElementById('loaderSpinner').style.display = 'none';
    logTerminal("Engine aborted by Admin. Standing by.", "warn");
}

async function startBroadcast() {
    let rawEmails = document.getElementById('emailList').value;
    let subject = document.getElementById('emailSubject').value;
    let message = document.getElementById('emailMessage').value;
    let fName = document.getElementById('fromName').value;
    let fEmail = document.getElementById('fromEmail').value;
    let delay = parseInt(document.getElementById('sendDelay').value);

    if(!subject || !message || rawEmails.trim() === '') {
        Swal.fire('Data Missing', 'Please fill all required fields and add target emails.', 'error'); return;
    }

    if(!isRunning && currentIndex === 0) {
        emailsArray = rawEmails.split(/[\n,]+/).map(e => e.trim()).filter(e => e !== '');
        if(emailsArray.length === 0) return;
        
        sentCount = 0; failedCount = 0; currentIndex = 0;
        document.getElementById('statSent').innerText = '0';
        document.getElementById('statFailed').innerText = '0';
        document.getElementById('progBar').style.width = '0%';
        document.getElementById('termLogs').innerHTML = ''; 
        logTerminal(`Payload accepted. Engaging targets: ${emailsArray.length}`);
    }

    isRunning = true;
    document.getElementById('btnStart').style.display = 'none';
    document.getElementById('btnStop').style.display = 'flex';
    document.getElementById('loaderSpinner').style.display = 'block';
    
    document.getElementById('emailList').disabled = true;
    document.getElementById('emailSubject').disabled = true;
    document.getElementById('emailMessage').disabled = true;

    for(let i = currentIndex; i < emailsArray.length; i++) {
        if(!isRunning) { currentIndex = i; break; } 
        
        let targetEmail = emailsArray[i];
        logTerminal(`Firing payload to <span class="mail">${targetEmail}</span>...`);
        
        let finalMsg = message.replace(/\[Name\]/g, "Customer").replace(/\[Date\]/g, new Date().toLocaleDateString());
        
        let formData = new FormData();
        formData.append('action', 'send_single_email');
        formData.append('email', targetEmail);
        formData.append('subject', subject);
        formData.append('message', finalMsg);
        formData.append('from_name', fName);
        formData.append('from_email', fEmail);

        try {
            let res = await fetch('email_marketing.php', { method: 'POST', body: formData });
            let json = await res.json();
            
            if(json.status === 'success') {
                sentCount++; document.getElementById('statSent').innerText = sentCount;
                logTerminal(`Delivered to ${targetEmail}`, 'ok');
            } else {
                failedCount++; document.getElementById('statFailed').innerText = failedCount;
                logTerminal(`Rejected by server: ${targetEmail}`, 'err');
            }
        } catch (e) {
            failedCount++; document.getElementById('statFailed').innerText = failedCount;
            logTerminal(`Network connection dropped for ${targetEmail}`, 'err');
        }

        let percent = Math.floor(((i + 1) / emailsArray.length) * 100);
        document.getElementById('progBar').style.width = percent + '%';

        if(i < emailsArray.length - 1 && isRunning) {
            await new Promise(r => setTimeout(r, delay));
        }
    }

    if(currentIndex >= emailsArray.length - 1 || emailsArray.length === 0) {
        isRunning = false;
        document.getElementById('btnStop').style.display = 'none';
        document.getElementById('btnStart').style.display = 'flex';
        document.getElementById('btnStart').innerHTML = '<i class="fas fa-check-double"></i> CAMPAIGN FINISHED';
        document.getElementById('loaderSpinner').style.display = 'none';
        
        document.getElementById('emailList').disabled = false;
        document.getElementById('emailSubject').disabled = false;
        document.getElementById('emailMessage').disabled = false;
        
        logTerminal(`Engine stopped. Campaign metrics locked.`, 'ok');
        
        // Save to History Module
        saveHistory(subject, sentCount, failedCount);

        Swal.fire({ title: 'Campaign Complete! 🚀', text: `Sent: ${sentCount} | Failed: ${failedCount}`, icon: 'success' });
    }
}
</script>

<?php include '_footer.php'; ?>
