<?php
// user/api.php - PRO DEVELOPER API (Fixed Layout & Purple Theme)
// Compatible with: Smart Panel, Perfect Panel
// Features: Auto-Resize Text, Purple UI, Secure Key Gen

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/_header.php';

// 1. AUTH CHECK
if (!isLoggedIn()) {
    redirect('../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// 2. GENERATE NEW API KEY LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_key'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security Token Expired. Please refresh.";
    } else {
        try {
            // Generate Strong Key
            $new_key = bin2hex(random_bytes(32)); 
            
            $stmt = $db->prepare("UPDATE users SET api_key = ? WHERE id = ?");
            $stmt->execute([$new_key, $user_id]);
            
            $success = "✅ New API Key Generated!";
            
            // Log Action
            logActivity($user_id, "Regenerated API Key", $db);
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// 3. FETCH DATA (Always fetch fresh key)
$stmt = $db->prepare("SELECT api_key FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$api_key = $user['api_key'] ?? 'No Key Generated';

// API URL
$api_url = SITE_URL . '/api_v2.php';
?>

<style>
    /* --- 🟣 PURPLE THEME & ANIMATIONS --- */
    :root {
        --primary: #6366f1; /* Indigo */
        --primary-dark: #4f46e5;
        --bg-body: #f5f3ff; /* Light Purple Bg */
        --card-bg: #ffffff;
        --text-main: #1e1b4b;
        --text-muted: #64748b;
        --border: #e0e7ff;
    }

    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; }

    /* Layout Fixes */
    .container-fluid { max-width: 1400px; margin: 0 auto; padding-bottom: 80px; }

    /* Cards with Hover Animation */
    .api-card {
        background: var(--card-bg);
        border-radius: 20px;
        box-shadow: 0 4px 20px -5px rgba(99, 102, 241, 0.1); 
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .api-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.2);
        border-color: #c7d2fe;
    }

    .api-header {
        padding: 25px 30px;
        background: linear-gradient(135deg, #ffffff 0%, #eef2ff 100%);
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 15px;
    }
    
    .api-body { padding: 30px; }

    /* 🔥 FIXED KEY BOX (No Overflow) */
    .key-box { 
        display: flex; align-items: center; justify-content: space-between;
        background: #1e1b4b; /* Dark Purple for Contrast */
        border: 1px solid #4338ca; 
        border-radius: 14px; padding: 18px 25px; 
        position: relative; transition: 0.3s;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);
        gap: 15px;
    }
    
    .api-key-text { 
        font-family: 'Courier New', monospace; 
        font-size: 1rem; 
        color: #a5b4fc; /* Light Neon Purple Text */
        font-weight: 700; 
        letter-spacing: 1px;
        
        /* --- CRITICAL FIX FOR OVERFLOW --- */
        word-break: break-all;
        white-space: normal;
        overflow-wrap: break-word;
        max-width: 100%;
    }

    .key-actions { display: flex; gap: 10px; flex-shrink: 0; }

    .btn-icon { 
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); 
        width: 42px; height: 42px; border-radius: 10px; 
        display: flex; align-items: center; justify-content: center; 
        cursor: pointer; color: #fff; transition: 0.2s;
    }
    .btn-icon:hover { background: var(--primary); transform: scale(1.1); border-color: var(--primary); }

    /* Generate Button */
    .btn-gen {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); 
        color: white; padding: 12px 25px; border-radius: 12px; 
        font-weight: 700; border: none; cursor: pointer; 
        display: flex; align-items: center; gap: 8px;
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3); transition: 0.3s;
    }
    .btn-gen:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(79, 70, 229, 0.5); filter: brightness(1.1); }
    .spin-hover { transition: 0.5s; }
    .btn-gen:hover .spin-hover { transform: rotate(180deg); }

    /* Tabs (Purple Style) */
    .doc-tabs { display: flex; gap: 10px; border-bottom: 2px solid #e0e7ff; margin-bottom: 25px; overflow-x: auto; padding-bottom: 5px; }
    .doc-tab {
        padding: 10px 20px; cursor: pointer; font-weight: 600; color: #64748b;
        border-radius: 10px; transition: 0.2s; white-space: nowrap; font-size: 0.95rem;
    }
    .doc-tab:hover { background: #eef2ff; color: var(--primary); }
    .doc-tab.active { 
        color: white; background: var(--primary); 
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }
    
    .doc-content { display: none; animation: fadeIn 0.4s ease; }
    .doc-content.active { display: block; }

    /* Tables */
    .param-table { width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 25px; }
    .param-table th { text-align: left; padding: 15px; background: #f8fafc; color: #475569; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; font-weight: 700; }
    .param-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: 0.95rem; background: #fff; }
    .param-table tr:last-child td { border-bottom: none; }
    
    .code-badge { background: #eef2ff; padding: 4px 8px; border-radius: 6px; font-family: monospace; color: #4f46e5; font-size: 0.9rem; border: 1px solid #c7d2fe; }

    /* Code Block (Dark Theme) */
    .code-block { 
        background: #0f172a; color: #e2e8f0; padding: 25px; border-radius: 16px; 
        font-family: 'Courier New', monospace; font-size: 0.9rem; overflow-x: auto; 
        line-height: 1.6; border: 1px solid #334155; box-shadow: inset 0 2px 10px rgba(0,0,0,0.3);
    }
    .comment { color: #64748b; font-style: italic; }
    .var { color: #f472b6; }
    .str { color: #a5b4fc; }
    .func { color: #38bdf8; }
    .key { color: #fbbf24; }

    /* Header Title */
    .page-title-box { margin-bottom: 35px; border-left: 5px solid var(--primary); padding-left: 20px; }
    .page-title-box h1 { font-weight: 900; color: #1e1b4b; margin: 0; font-size: 2.2rem; letter-spacing: -1px; }
    .page-title-box p { color: #64748b; margin-top: 5px; font-size: 1rem; }

    @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    
    /* Mobile Responsive Fixes */
    @media (max-width: 768px) {
        .key-box { flex-direction: column; align-items: flex-start; gap: 15px; }
        .key-actions { width: 100%; justify-content: flex-end; }
        .api-header { flex-direction: column; align-items: flex-start; }
        .btn-gen { width: 100%; justify-content: center; }
    }
</style>

<div class="container-fluid">

    <div class="page-title-box">
        <h1>Developer API</h1>
        <p>Connect your panel, app, or custom script with our high-speed API.</p>
    </div>

    <?php if($success): ?><div class="alert alert-success" style="border-left:4px solid #10b981;"><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger" style="border-left:4px solid #ef4444;"><?= $error ?></div><?php endif; ?>

    <div class="api-card">
        <div class="api-header">
            <div>
                <h3 style="margin:0; font-size:1.3rem; font-weight:800; color:var(--text-main);">Your Secret Key</h3>
                <span style="font-size:0.85rem; color:var(--text-muted);">Used to authenticate your requests</span>
            </div>
            
            <form method="POST" onsubmit="return confirm('⚠️ Warning: Generating a new key will stop all current scripts using the old key. Continue?');">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" name="generate_key" class="btn-gen">
                    <i class="fa fa-sync spin-hover"></i> Generate New Key
                </button>
            </form>
        </div>
        
        <div class="api-body">
            <div class="key-box">
                <div class="api-key-text" id="apiKeyDisplay">
                    <?php 
                        // Mask the key initially
                        echo str_repeat('•', 45); 
                    ?>
                </div>
                <div class="key-actions">
                    <div class="btn-icon" onclick="toggleKey()" title="Show/Hide">
                        <i class="fa fa-eye" id="eyeIcon"></i>
                    </div>
                    <div class="btn-icon" onclick="copyKey()" title="Copy to Clipboard">
                        <i class="fa fa-copy"></i>
                    </div>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px; align-items:center; color:#64748b; font-size:0.9rem;">
                <i class="fa fa-shield-alt" style="color:var(--primary);"></i> 
                <span><b>Security Note:</b> Never share this key. If you suspect a leak, regenerate it immediately.</span>
            </div>
        </div>
    </div>

    <div class="api-card">
        <div class="api-header" style="display:block; padding-bottom:0; background:none;">
            <div class="doc-tabs">
                <div class="doc-tab active" onclick="openTab('tab-intro', this)">Overview</div>
                <div class="doc-tab" onclick="openTab('tab-services', this)">Services List</div>
                <div class="doc-tab" onclick="openTab('tab-add', this)">Place Order</div>
                <div class="doc-tab" onclick="openTab('tab-status', this)">Order Status</div>
                <div class="doc-tab" onclick="openTab('tab-balance', this)">Balance</div>
                <div class="doc-tab" onclick="openTab('tab-code', this)">PHP Example</div>
            </div>
        </div>
        
        <div class="api-body">
            
            <div id="tab-intro" class="doc-content active">
                <h3 style="margin-top:0; color:var(--text-main);">API Endpoint URL</h3>
                <div class="key-box" style="margin-bottom: 25px; background:#f1f5f9; border-color:#e2e8f0; color:#334155;">
                    <div class="api-key-text" style="color:#4f46e5;"><?= $api_url ?></div>
                    <div class="key-actions">
                        <div class="btn-icon" onclick="navigator.clipboard.writeText('<?= $api_url ?>');alert('URL Copied!')" style="color:#64748b; border-color:#cbd5e1;">
                            <i class="fa fa-copy"></i>
                        </div>
                    </div>
                </div>
                
                <h4 style="color:var(--text-main);">Request Format</h4>
                <ul style="color:#475569; line-height:2; list-style:none; padding:0;">
                    <li><i class="fa fa-check-circle" style="color:#10b981; margin-right:8px;"></i> HTTP Method: <b class="code-badge">POST</b></li>
                    <li><i class="fa fa-check-circle" style="color:#10b981; margin-right:8px;"></i> Response: <b class="code-badge">JSON</b></li>
                    <li><i class="fa fa-check-circle" style="color:#10b981; margin-right:8px;"></i> Required Params: <b class="code-badge">key</b>, <b class="code-badge">action</b></li>
                </ul>
            </div>

            <div id="tab-services" class="doc-content">
                <h3 style="color:var(--text-main);">Get Service List</h3>
                <table class="param-table">
                    <tr><th>Parameter</th><th>Description</th></tr>
                    <tr><td><span class="code-badge">key</span></td><td>Your API Key</td></tr>
                    <tr><td><span class="code-badge">action</span></td><td>Value: <b>services</b></td></tr>
                </table>
                <h4>Response Example</h4>
                <div class="code-block">
[
    {
        <span class="key">"service"</span>: <span class="var">1</span>,
        <span class="key">"name"</span>: <span class="str">"Instagram Followers [Real]"</span>,
        <span class="key">"type"</span>: <span class="str">"Default"</span>,
        <span class="key">"category"</span>: <span class="str">"Instagram"</span>,
        <span class="key">"rate"</span>: <span class="str">"0.50"</span>,
        <span class="key">"min"</span>: <span class="str">"100"</span>,
        <span class="key">"max"</span>: <span class="str">"10000"</span>,
        <span class="key">"refill"</span>: <span class="var">true</span>
    },
    ...
]
                </div>
            </div>

            <div id="tab-add" class="doc-content">
                <h3 style="color:var(--text-main);">Place New Order</h3>
                <table class="param-table">
                    <tr><th>Parameter</th><th>Description</th></tr>
                    <tr><td><span class="code-badge">key</span></td><td>Your API Key</td></tr>
                    <tr><td><span class="code-badge">action</span></td><td>Value: <b>add</b></td></tr>
                    <tr><td><span class="code-badge">service</span></td><td>Service ID</td></tr>
                    <tr><td><span class="code-badge">link</span></td><td>Link to page/post</td></tr>
                    <tr><td><span class="code-badge">quantity</span></td><td>Amount needed</td></tr>
                    <tr><td><span class="code-badge">runs</span></td><td>(Optional) For drip-feed</td></tr>
                    <tr><td><span class="code-badge">interval</span></td><td>(Optional) For drip-feed (minutes)</td></tr>
                </table>
                <h4>Response Example</h4>
                <div class="code-block">
{
    <span class="key">"order"</span>: <span class="var">23501</span>
}
                </div>
            </div>

            <div id="tab-status" class="doc-content">
                <h3 style="color:var(--text-main);">Check Order Status</h3>
                <table class="param-table">
                    <tr><th>Parameter</th><th>Description</th></tr>
                    <tr><td><span class="code-badge">key</span></td><td>Your API Key</td></tr>
                    <tr><td><span class="code-badge">action</span></td><td>Value: <b>status</b></td></tr>
                    <tr><td><span class="code-badge">order</span></td><td>Order ID</td></tr>
                </table>
                <h4>Response Example</h4>
                <div class="code-block">
{
    <span class="key">"charge"</span>: <span class="str">"0.20"</span>,
    <span class="key">"start_count"</span>: <span class="str">"1540"</span>,
    <span class="key">"status"</span>: <span class="str">"Completed"</span>,
    <span class="key">"remains"</span>: <span class="str">"0"</span>,
    <span class="key">"currency"</span>: <span class="str">"USD"</span>
}
                </div>
            </div>

            <div id="tab-balance" class="doc-content">
                <h3 style="color:var(--text-main);">Check User Balance</h3>
                <table class="param-table">
                    <tr><th>Parameter</th><th>Description</th></tr>
                    <tr><td><span class="code-badge">key</span></td><td>Your API Key</td></tr>
                    <tr><td><span class="code-badge">action</span></td><td>Value: <b>balance</b></td></tr>
                </table>
                <h4>Response Example</h4>
                <div class="code-block">
{
    <span class="key">"balance"</span>: <span class="str">"150.50"</span>,
    <span class="key">"currency"</span>: <span class="str">"USD"</span>
}
                </div>
            </div>

            <div id="tab-code" class="doc-content">
                <h3 style="color:var(--text-main);">PHP Example (cURL)</h3>
                <div class="code-block">
<span class="func">&lt;?php</span>
<span class="var">$api_url</span> = <span class="str">'<?= $api_url ?>'</span>;
<span class="var">$api_key</span> = <span class="str">'YOUR_API_KEY'</span>;

<span class="var">$post</span> = [
    <span class="str">'key'</span> => <span class="var">$api_key</span>,
    <span class="str">'action'</span> => <span class="str">'add'</span>,
    <span class="str">'service'</span> => <span class="str">1</span>,
    <span class="str">'link'</span> => <span class="str">'https://instagram.com/user'</span>,
    <span class="str">'quantity'</span> => <span class="str">1000</span>
];

<span class="var">$ch</span> = curl_init();
curl_setopt(<span class="var">$ch</span>, CURLOPT_URL, <span class="var">$api_url</span>);
curl_setopt(<span class="var">$ch</span>, CURLOPT_POST, 1);
curl_setopt(<span class="var">$ch</span>, CURLOPT_POSTFIELDS, <span class="var">$post</span>);
curl_setopt(<span class="var">$ch</span>, CURLOPT_RETURNTRANSFER, 1);
<span class="var">$result</span> = curl_exec(<span class="var">$ch</span>);
curl_close(<span class="var">$ch</span>);

echo <span class="var">$result</span>;
<span class="func">?></span>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    // Real Key Store (From PHP)
    const realKey = "<?= $api_key ?>";
    let isHidden = true;

    function toggleKey() {
        const display = document.getElementById('apiKeyDisplay');
        const icon = document.getElementById('eyeIcon');
        
        if (isHidden) {
            display.innerText = realKey;
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            isHidden = false;
        } else {
            display.innerText = "•".repeat(45);
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            isHidden = true;
        }
    }

    function copyKey() {
        navigator.clipboard.writeText(realKey).then(() => {
            // Simple toast logic
            const btn = document.querySelector('.fa-copy');
            const originalClass = btn.className;
            btn.className = 'fa fa-check';
            setTimeout(() => { btn.className = originalClass; }, 2000);
        });
    }

    function openTab(id, btnElement) {
        // Hide all
        document.querySelectorAll('.doc-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.doc-tab').forEach(el => el.classList.remove('active'));
        
        // Show current
        document.getElementById(id).classList.add('active');
        btnElement.classList.add('active');
    }
</script>

<?php include '_footer.php'; ?>