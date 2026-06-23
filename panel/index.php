<?php
// File: index.php (Admin Dashboard)
include '_header.php'; 

// --- LIVE DATA NIKAL RAHE HAIN ---
try {
    $smm_pending = $db->query("SELECT COUNT(*) FROM smm_orders WHERE status='pending'")->fetchColumn();
    $active_subs = $db->query("SELECT COUNT(*) FROM orders WHERE status='completed' AND product_id IN (SELECT id FROM products WHERE is_digital=0)")->fetchColumn();
    $total_files = $db->query("SELECT COUNT(*) FROM products WHERE is_digital=1")->fetchColumn();
    $crypto_pending = $db->query("SELECT COUNT(*) FROM crypto_orders WHERE status='pending'")->fetchColumn(); 
    
    // Server aur Security ka asli data
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $db_status = $db ? 'Connected & Secured' : 'Disconnected';
} catch (Exception $e) {
    $smm_pending = 0; $active_subs = 0; $total_files = 0; $total_users = 0; $crypto_pending = 0;
    $db_status = 'Warning: Unstable';
}

$hour = date('H');
if ($hour < 12) $greeting = "Subah Bakhair";
elseif ($hour < 18) $greeting = "Dopeher Bakhair";
else $greeting = "Shaam Bakhair";

// Asli Server Variables Customer Ko Dikhane Ke Liye
$server_ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$php_version = phpversion();
$ssl_status = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'AES-256 Validated' : 'Not Secured';
$server_soft = $_SERVER['SERVER_SOFTWARE'] ?? 'Linux/Apache Engine';

// --- PERMISSIONS FETCHING (SECURITY) ---
// Note: We use the $admin_perms and $is_super_admin variables that should be set in _header.php
$has_smm_access = isset($is_super_admin) && $is_super_admin ? true : (isset($admin_perms) && (in_array('view_orders', $admin_perms) || in_array('manage_services', $admin_perms)));
$has_digital_access = isset($is_super_admin) && $is_super_admin ? true : (isset($admin_perms) && in_array('manage_products', $admin_perms));
$has_crypto_access = isset($is_super_admin) && $is_super_admin ? true : (isset($admin_perms) && in_array('add_balance', $admin_perms));

?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* THEME VARIABLES (Safaid aur Kala Hacker Touch) */
    :root {
        --bg-main: #f4f7f6;
        --white: #ffffff;
        --black-soft: #0f172a;
        --black-hard: #000000;
        --neon-green: #00ff88;
        --neon-red: #ff0055;
        --neon-blue: #0ea5e9;
        --accent: #6366f1;
        --text-dark: #1e293b;
        --text-light: #94a3b8;
    }

    body {
        background-color: var(--bg-main); 
        font-family: 'Outfit', sans-serif;
        overflow-x: hidden;
        margin: 0; padding: 0;
    }

    /* BARA HEADER */
    .hub-header {
        background: var(--white);
        padding: 30px 40px 70px 40px; 
        color: var(--black-soft);
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        border-bottom: 2px solid #e2e8f0;
        text-align: center; /* Center align kar diya */
    }

    .welcome-text h1 { font-size: 2.5rem; font-weight: 900; margin: 0; letter-spacing: -1px; color: var(--black-hard); }
    .welcome-text p { color: var(--text-light); font-size: 1rem; margin-top: 8px; font-weight: 400; }
    
    .date-badge {
        background: var(--black-soft);
        color: var(--white);
        padding: 6px 15px; border-radius: 50px;
        font-size: 0.85rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        margin-bottom: 10px;
    }

    /* ==============================================
       🔥 MASTER CONTROL CENTER (Chota aur Centered) 🔥
       ============================================== */
    .master-control-wrapper {
        max-width: 1000px; /* Chodaai kam kar di taake center mein fit ho */
        margin: -50px auto 40px auto; /* Upar shift kiya aur center kiya */
        padding: 0 20px;
        position: relative;
        z-index: 10;
        display: flex;
        justify-content: center; /* Daba center mein */
    }

    .master-box {
        background: var(--white);
        border: 2px solid var(--black-soft);
        border-radius: 24px;
        padding: 25px; /* Padding kam ki height choti karne ke liye */
        box-shadow: 8px 8px 0px rgba(15, 23, 42, 0.1);
        width: 100%;
    }

    .master-title {
        font-size: 1.2rem; font-weight: 900; color: var(--black-hard);
        margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 8px;
        border-bottom: 2px dashed #cbd5e1; padding-bottom: 10px;
    }
    .master-title i { color: var(--accent); }

    /* 4 Buttons Wala Grid (Centered) */
    .cmd-buttons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Button size chota kiya */
        gap: 15px;
        justify-content: center;
    }

    .cmd-btn {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 15px; /* Height kam karne ke liye padding kam ki */
        text-decoration: none; color: var(--black-soft);
        display: flex; flex-direction: column; align-items: center; text-align: center;
        transition: all 0.3s ease;
        position: relative; overflow: hidden;
        cursor: pointer;
    }

    .cmd-btn:hover {
        background: var(--black-soft);
        color: var(--white);
        border-color: var(--black-hard);
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }

    .cmd-icon {
        width: 50px; height: 50px; border-radius: 14px; /* Icon size chota kiya */
        background: var(--white); color: var(--black-hard);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: 0.3s;
    }
    .cmd-btn:hover .cmd-icon { transform: scale(1.1); background: var(--accent); color: var(--white); }

    .cmd-btn h3 { margin: 0; font-size: 1.1rem; font-weight: 800; }
    .cmd-btn p { margin: 5px 0 0 0; font-size: 0.75rem; color: var(--text-light); }
    .cmd-btn:hover p { color: #cbd5e1; }

    /* Live Badges on Buttons */
    .btn-badge {
        position: absolute; top: 10px; right: 10px;
        padding: 3px 8px; border-radius: 15px; font-size: 0.65rem; font-weight: 800;
        background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5;
        animation: pulseRed 2s infinite;
    }
    .btn-badge.safe { background: #dcfce7; color: #16a34a; border-color: #86efac; animation: none; }

    @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow: 0 0 0 10px rgba(239,68,68,0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); } }

    /* ==============================================
       🚨 OMEGA-9 CYBER SECURITY DASHBOARD (20 Boxes) 🚨
       ============================================== */
    .security-section {
        max-width: 1400px; margin: 0 auto 60px auto; padding: 0 20px;
    }
    
    .sec-title {
        font-size: 1.8rem; font-weight: 900; color: var(--black-hard); margin-bottom: 25px;
        display: flex; align-items: center; justify-content: center; gap: 10px; text-transform: uppercase;
    }
    .sec-title i { color: var(--black-soft); }

    .cyber-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;
    }

    .cyber-box {
        background: var(--white); border: 2px solid #e2e8f0; border-radius: 20px;
        padding: 20px; color: var(--text-dark); position: relative; overflow: hidden;
        box-shadow: 0 8px 20px rgba(0,0,0,0.03);
        transition: 0.3s;
        display: flex; flex-direction: column;
    }
    .cyber-box:hover { border-color: var(--black-soft); box-shadow: 0 12px 25px rgba(0,0,0,0.08); }

    /* Black Headers inside White Boxes */
    .cyber-header { 
        display: flex; justify-content: space-between; align-items: center;
        background: var(--black-soft); color: var(--neon-green);
        padding: 10px 15px; border-radius: 10px; margin-bottom: 15px;
        font-family: 'Space Mono', monospace; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;
        box-shadow: inset 0 0 10px rgba(0, 255, 136, 0.1);
    }
    .status-dot { width: 10px; height: 10px; background: var(--neon-green); border-radius: 50%; box-shadow: 0 0 8px var(--neon-green); animation: blinkGreen 1.5s infinite; }
    .status-dot.red { background: var(--neon-red); box-shadow: 0 0 8px var(--neon-red); animation: blinkRed 1s infinite; }
    .status-dot.blue { background: var(--neon-blue); box-shadow: 0 0 8px var(--neon-blue); }
    @keyframes blinkGreen { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    @keyframes blinkRed { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

    /* BOX SPECIFIC STYLES */
    .layer-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .layer-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 5px; text-align: center; border-radius: 10px; transition: 0.2s; }
    .layer-box i { font-size: 1.2rem; margin-bottom: 5px; color: #94a3b8; }
    .layer-box p { font-family: 'Space Mono', monospace; font-size: 0.55rem; margin: 0; font-weight: 700; }
    .layer-box.active { border-color: var(--black-soft); background: var(--black-soft); color: var(--neon-green); }
    .layer-box.active i { color: var(--neon-green); text-shadow: 0 0 8px var(--neon-green); }

    .terminal-window { background: var(--black-hard); border-radius: 10px; height: 160px; padding: 15px; overflow-y: hidden; font-family: 'Space Mono'; font-size: 0.75rem; color: #888; box-shadow: inset 0 0 15px rgba(0,0,0,0.8); }
    .term-ok { color: var(--neon-green); } .term-err { color: var(--neon-red); }

    .integrity-list { display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: center; }
    .int-item { display: flex; justify-content: space-between; background: #f8fafc; padding: 10px; border-radius: 8px; border-left: 3px solid var(--black-soft); font-family: 'Space Mono'; font-size: 0.75rem; }
    .int-val { font-weight: 900; } .safe { color: #16a34a; }

    .radar-container { height: 120px; display: flex; align-items: flex-end; gap: 4px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .radar-bar { flex: 1; background: var(--black-soft); border-radius: 3px 3px 0 0; transition: height 0.3s; min-height: 5px; }

    .ip-list { display: flex; flex-direction: column; gap: 6px; height: 140px; overflow: hidden; }
    .ip-row { display: flex; justify-content: space-between; background: #f8fafc; padding: 8px 10px; border-radius: 6px; font-family: 'Space Mono'; font-size: 0.75rem; animation: slideIn 0.3s ease; }

    .api-circle-container { display: flex; justify-content: space-around; align-items: center; height: 140px; }
    .api-ring { width: 90px; height: 90px; border-radius: 50%; border: 6px solid #e2e8f0; border-top-color: var(--black-soft); display: flex; align-items: center; justify-content: center; flex-direction: column; font-weight: 900; font-size: 1.2rem; animation: spin 2s linear infinite; position: relative; }
    .api-ring span { position: absolute; font-size: 0.6rem; color: var(--text-light); bottom: 15px; font-family: 'Space Mono'; animation: reverseSpin 2s linear infinite; }
    .api-text-val { position: absolute; animation: reverseSpin 2s linear infinite; }

    /* Naye Boxes ki Styles */
    .prog-wrap { margin-bottom: 12px; }
    .prog-lbl { display: flex; justify-content: space-between; font-family: 'Space Mono'; font-size: 0.75rem; margin-bottom: 4px; font-weight: 700; }
    .prog-bar-bg { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; }
    .prog-bar-fill { background: var(--black-soft); height: 100%; transition: width 0.5s; }
    .prog-bar-fill.red { background: var(--neon-red); }
    .prog-bar-fill.blue { background: var(--neon-blue); }

    .big-number { font-size: 3.5rem; font-weight: 900; text-align: center; color: var(--black-hard); line-height: 1; margin: 10px 0; }
    .small-text { text-align: center; font-family: 'Space Mono'; font-size: 0.75rem; color: var(--text-light); }

    .node-list { display: flex; flex-direction: column; gap: 8px; }
    .node-item { display: flex; justify-content: space-between; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px; font-family: 'Space Mono'; font-size: 0.8rem; }

    @keyframes spin { 100% { transform: rotate(360deg); } }
    @keyframes reverseSpin { 100% { transform: rotate(-360deg); } }
    @keyframes slideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    @media(max-width: 900px) { .cyber-grid { grid-template-columns: 1fr 1fr; } }
    @media(max-width: 600px) { .cyber-grid { grid-template-columns: 1fr; } }
</style>

<div class="hub-header">
    <div class="welcome-text">
        <div class="date-badge"><i class="far fa-clock"></i> <?= date('l, d M Y') ?></div>
        <h1>👋 <?= $greeting ?>, Admin!</h1>
        <p>System operational. All security layers are active.</p>
    </div>
</div>

<div class="master-control-wrapper">
    <div class="master-box" data-tilt data-tilt-max="1" data-tilt-speed="400" data-tilt-glare="true" data-tilt-max-glare="0.05">
        <div class="master-title"><i class="fas fa-satellite-dish"></i> Master Control Panel</div>
        
        <div class="cmd-buttons-grid">
            <a <?= $has_smm_access ? 'href="smm_dashboard.php"' : 'href="#" onclick="showWarning(\'smm\')"' ?> class="cmd-btn">
                <?php if($smm_pending > 0): ?><div class="btn-badge"><?= $smm_pending ?> Pending</div><?php else: ?><div class="btn-badge safe">Clean</div><?php endif; ?>
                <div class="cmd-icon"><i class="fas fa-rocket"></i></div>
                <h3>SMM Panel</h3>
                <p>Social Media Orders</p>
            </a>

            <a <?= $has_digital_access ? 'href="sub_dashboard.php"' : 'href="#" onclick="showWarning(\'digital\')"' ?> class="cmd-btn">
                <div class="btn-badge safe"><?= number_format($active_subs) ?> Active</div>
                <div class="cmd-icon"><i class="fas fa-crown"></i></div>
                <h3>Subscriptions</h3>
                <p>Netflix & Premium</p>
            </a>

            <a <?= $has_digital_access ? 'href="downloads_manager.php"' : 'href="#" onclick="showWarning(\'digital\')"' ?> class="cmd-btn">
                <div class="btn-badge safe"><?= $total_files ?> Files</div>
                <div class="cmd-icon"><i class="fas fa-cloud-arrow-down"></i></div>
                <h3>Digital Vault</h3>
                <p>Downloadable Files</p>
            </a>

            <a <?= $has_crypto_access ? 'href="crypto_orders.php"' : 'href="#" onclick="showWarning(\'crypto\')"' ?> class="cmd-btn">
                <?php if($crypto_pending > 0): ?><div class="btn-badge"><?= $crypto_pending ?> Requests</div><?php else: ?><div class="btn-badge safe">Synced</div><?php endif; ?>
                <div class="cmd-icon"><i class="fab fa-bitcoin"></i></div>
                <h3>USDT Funds</h3>
                <p>Crypto Deposits</p>
            </a>
        </div>
    </div>
</div>

<div class="security-section">
    <div class="sec-title"><i class="fas fa-shield-alt"></i> Omega-9 Cyber Defense Core</div>

    <div class="cyber-grid">
        
        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-network-wired"></i> Network Grid</span><div class="status-dot"></div></div>
            <div class="layer-grid" id="defense-grid">
                <div class="layer-box"><i class="fas fa-cloud"></i><p>WAF</p></div>
                <div class="layer-box"><i class="fas fa-database"></i><p>SQLi</p></div>
                <div class="layer-box"><i class="fas fa-code"></i><p>XSS</p></div>
                <div class="layer-box"><i class="fas fa-key"></i><p>CSRF</p></div>
                <div class="layer-box"><i class="fas fa-robot"></i><p>Bot</p></div>
                <div class="layer-box"><i class="fas fa-shield-virus"></i><p>DDoS</p></div>
                <div class="layer-box"><i class="fas fa-bug"></i><p>Zero-Day</p></div>
                <div class="layer-box"><i class="fas fa-fingerprint"></i><p>Hash</p></div>
                <div class="layer-box"><i class="fas fa-globe"></i><p>Geo</p></div>
                <div class="layer-box"><i class="fas fa-ethernet"></i><p>Port</p></div>
                <div class="layer-box"><i class="fas fa-memory"></i><p>Mem</p></div>
                <div class="layer-box"><i class="fas fa-search"></i><p>Payload</p></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-terminal"></i> Interceptor Log</span><div class="status-dot"></div></div>
            <div class="terminal-window" id="term-window"></div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-server"></i> Vault Integrity</span><div class="status-dot"></div></div>
            <div class="integrity-list">
                <div class="int-item"><span class="int-lbl">Database</span><span class="int-val safe"><?= $db_status ?></span></div>
                <div class="int-item"><span class="int-lbl">SSL/TLS</span><span class="int-val safe"><?= $ssl_status ?></span></div>
                <div class="int-item"><span class="int-lbl">IP Addr</span><span class="int-val"><?= $server_ip ?></span></div>
                <div class="int-item"><span class="int-lbl">PHP Core</span><span class="int-val safe">v<?= $php_version ?></span></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-chart-line"></i> Traffic Anomaly</span><div class="status-dot"></div></div>
            <div style="font-family:'Space Mono'; font-size:0.75rem; color:var(--text-light); text-align:right;">Blocked: <b style="color:var(--neon-red)" id="block-count">2,841</b></div>
            <div class="radar-container" id="radar-bars"></div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-map-marker-alt"></i> Real-Time Filter</span><div class="status-dot"></div></div>
            <div class="ip-list" id="ip-list-container"></div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-exchange-alt"></i> API Gateway</span><div class="status-dot"></div></div>
            <div class="api-circle-container">
                <div class="api-ring"><div class="api-text-val" id="api-ping">24ms</div><span>PING</span></div>
                <div class="api-ring" style="border-top-color: var(--neon-blue);"><div class="api-text-val" id="api-load">12%</div><span>LOAD</span></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-microchip"></i> Server Core</span><div class="status-dot"></div></div>
            <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                <div class="prog-wrap"><div class="prog-lbl"><span>CPU Usage</span><span id="cpu-txt">14%</span></div><div class="prog-bar-bg"><div class="prog-bar-fill" id="cpu-bar" style="width:14%"></div></div></div>
                <div class="prog-wrap"><div class="prog-lbl"><span>RAM Allocation</span><span id="ram-txt">45%</span></div><div class="prog-bar-bg"><div class="prog-bar-fill blue" id="ram-bar" style="width:45%"></div></div></div>
                <div class="prog-wrap"><div class="prog-lbl"><span>Core Temp</span><span id="temp-txt">48°C</span></div><div class="prog-bar-bg"><div class="prog-bar-fill red" id="temp-bar" style="width:48%"></div></div></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-shield-alt"></i> DDoS Shield</span><div class="status-dot" id="ddos-dot"></div></div>
            <div class="big-number" id="ddos-num">0%</div>
            <div class="small-text" id="ddos-txt">Attack Intensity</div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-database"></i> Live Queries</span><div class="status-dot blue"></div></div>
            <div class="big-number" style="color:var(--neon-blue);" id="qps-num">1,842</div>
            <div class="small-text">Queries Per Second (QPS)</div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-robot"></i> Bot AI Filter</span><div class="status-dot"></div></div>
            <div class="api-circle-container">
                <div class="api-ring" style="border-top-color: var(--neon-green); width:110px; height:110px;"><div class="api-text-val" id="bot-score">99%</div><span>ACCURACY</span></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-box-open"></i> Malware Sandbox</span><div class="status-dot"></div></div>
            <div class="terminal-window" id="sandbox-log" style="height:120px; background:#f8fafc; border:1px solid #e2e8f0; color:var(--text-dark); box-shadow:none;"></div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-user-secret"></i> Dark Web Scan</span><div class="status-dot red"></div></div>
            <div class="big-number" id="dark-num">14,209</div>
            <div class="small-text">Leaked Credentials Scanned</div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-globe"></i> Global Nodes</span><div class="status-dot blue"></div></div>
            <div class="node-list">
                <div class="node-item"><span>New York, USA</span><b id="nd-ny">12ms</b></div>
                <div class="node-item"><span>London, UK</span><b id="nd-lon">34ms</b></div>
                <div class="node-item"><span>Tokyo, JPN</span><b id="nd-tok">110ms</b></div>
                <div class="node-item"><span>Sydney, AUS</span><b id="nd-syd">145ms</b></div>
                <div class="node-item"><span>Frankfurt, GER</span><b id="nd-fra">22ms</b></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fab fa-bitcoin"></i> Ledger Sync</span><div class="status-dot"></div></div>
            <div class="big-number" style="font-size:2rem; margin-top:20px;" id="block-num">#849,102</div>
            <div class="small-text">Last Validated Block</div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-lock"></i> SSL Handshake</span><div class="status-dot"></div></div>
            <div class="api-circle-container">
                <div class="api-ring" style="width:110px; height:110px;"><div class="api-text-val" id="ssl-time">42ms</div><span>TLS 1.3</span></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-user-shield"></i> Admin Sessions</span><div class="status-dot blue"></div></div>
            <div class="integrity-list">
                <div class="int-item" style="border-left-color:var(--neon-green);"><span class="int-lbl">SuperAdmin</span><span class="int-val safe">Active Now</span></div>
                <div class="int-item" style="border-left-color:#cbd5e1;"><span class="int-lbl">Manager_01</span><span class="int-val" style="color:#94a3b8;">Offline</span></div>
                <div class="int-item" style="border-left-color:#cbd5e1;"><span class="int-lbl">Support_API</span><span class="int-val" style="color:#94a3b8;">Offline</span></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-wifi"></i> Network I/O</span><div class="status-dot"></div></div>
            <div style="display:flex; justify-content:space-around; align-items:center; height:100%;">
                <div style="text-align:center;"><i class="fas fa-arrow-down" style="color:var(--neon-blue); font-size:2rem;"></i><div class="big-number" style="font-size:1.5rem;" id="net-down">45</div><div class="small-text">MB/s IN</div></div>
                <div style="text-align:center;"><i class="fas fa-arrow-up" style="color:var(--neon-green); font-size:2rem;"></i><div class="big-number" style="font-size:1.5rem;" id="net-up">12</div><div class="small-text">MB/s OUT</div></div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-brain"></i> AI Threat Predict</span><div class="status-dot"></div></div>
            <div class="big-number" style="color:var(--neon-green);">SAFE</div>
            <div class="small-text">Current Threat Level: LOW</div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-save"></i> Auto Backup</span><div class="status-dot blue"></div></div>
            <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                <div class="prog-wrap"><div class="prog-lbl"><span>Syncing Database...</span><span id="bkp-txt">75%</span></div><div class="prog-bar-bg"><div class="prog-bar-fill blue" id="bkp-bar" style="width:75%"></div></div></div>
                <div class="small-text">Next full backup in 4 hours</div>
            </div>
        </div>

        <div class="cyber-box">
            <div class="cyber-header"><span><i class="fas fa-fire"></i> Active Firewall</span><div class="status-dot"></div></div>
            <div class="big-number" id="fw-num">1,042</div>
            <div class="small-text">Strict Rules Enforced</div>
        </div>

    </div>
</div>

<script>
    // ==========================================
    // 🛡️ SECURITY MEME WARNING LOGIC
    // ==========================================
    function showWarning(type) {
        event.preventDefault(); // Default click ko rok diya
        
        let title = "Access Denied 🛑";
        let text = "Tumhara ilaqa nahi hai ye! Wapis jao.";
        
        if (type === 'smm') {
            text = "Bhai SMM Panel ka access nahi diya Boss ne. Sirf SEO par focus karo!";
        } else if (type === 'digital') {
            text = "Digital Store VIP area hai. Tumhari entry restrict kar di gayi hai!";
        } else if (type === 'crypto') {
            text = "Crypto funds ko hath lagane ki ijazat nahi hai hacker babu! 😅";
        }
        
        Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            confirmButtonText: "Okay Boss! 🫡",
            confirmButtonColor: '#ef4444',
            background: '#ffffff',
            backdrop: `rgba(15, 23, 42, 0.8)`
        });
    }


    // ==========================================
    // 🛡️ 20 BOXES JAVASCRIPT ANIMATIONS
    // ==========================================

    // 1. Layer Matrix Blink
    const layers = document.querySelectorAll('.layer-box');
    setInterval(() => {
        let r = Math.floor(Math.random() * layers.length);
        layers[r].classList.add('active');
        setTimeout(() => { layers[r].classList.remove('active'); }, 500);
    }, 300);

    // 2. Terminal Live Logging
    const termWindow = document.getElementById('term-window');
    const logsData = [
        `[SYS] Engine Initialized...`, `[WAF] Rules updated <span class="term-ok">OK</span>`,
        `[WARN] Bad request IP 185.12.x.x <span class="term-err">BLOCKED</span>`,
        `[Z-SEC] SQLi payload dropped.`, `[OK] Admin Verified`,
        `[API] SMM Providers Synced.`, `[SCAN] Checking memory... <span class="term-ok">CLEAN</span>`
    ];
    let logIdx = 0;
    function addLog() {
        if(logIdx >= logsData.length) { logIdx = 0; termWindow.innerHTML = ''; }
        let p = document.createElement('div'); p.className = 'terminal-line';
        p.innerHTML = `> ${logsData[logIdx]}`;
        termWindow.appendChild(p); termWindow.scrollTop = termWindow.scrollHeight;
        logIdx++; setTimeout(addLog, Math.floor(Math.random()*1500)+500);
    }
    setTimeout(addLog, 1000);

    // 4. Z-Security Radar
    const radarContainer = document.getElementById('radar-bars');
    const blockCountEl = document.getElementById('block-count');
    let blocks = 2841;
    for(let i=0; i<15; i++) { let b = document.createElement('div'); b.className = 'radar-bar'; b.style.height = (Math.random()*80+10)+'%'; radarContainer.appendChild(b); }
    setInterval(() => {
        document.querySelectorAll('.radar-bar').forEach(bar => {
            let h = Math.random()*90+10; bar.style.height = h+'%';
            if(h > 85) { bar.style.background = 'var(--neon-red)'; blocks++; blockCountEl.innerText = blocks.toLocaleString(); } 
            else { bar.style.background = 'var(--black-soft)'; }
        });
    }, 1000);

    // 5. Geo-IP Tracker
    const ipContainer = document.getElementById('ip-list-container');
    const countries = ['Russia', 'China', 'USA', 'Brazil', 'Iran'];
    function addFakeIP() {
        let row = document.createElement('div'); row.className = 'ip-row';
        row.innerHTML = `<span>${Math.floor(Math.random()*255)}.${Math.floor(Math.random()*255)}.x.x (${countries[Math.floor(Math.random()*5)]})</span> <span class="status">DROP</span>`;
        ipContainer.prepend(row); if(ipContainer.children.length > 4) ipContainer.removeChild(ipContainer.lastChild);
        setTimeout(addFakeIP, Math.floor(Math.random()*2000)+1000);
    }
    addFakeIP();

    // 6. API Gateway & 9. QPS & 13. Nodes & 15. SSL & 17. Network
    setInterval(() => {
        document.getElementById('api-ping').innerText = (Math.floor(Math.random()*20)+15)+'ms';
        document.getElementById('api-load').innerText = (Math.floor(Math.random()*30)+10)+'%';
        document.getElementById('qps-num').innerText = (Math.floor(Math.random()*500)+1500).toLocaleString();
        document.getElementById('nd-ny').innerText = (Math.floor(Math.random()*5)+10)+'ms';
        document.getElementById('nd-lon').innerText = (Math.floor(Math.random()*10)+30)+'ms';
        document.getElementById('ssl-time').innerText = (Math.floor(Math.random()*15)+35)+'ms';
        document.getElementById('net-down').innerText = Math.floor(Math.random()*50)+20;
        document.getElementById('net-up').innerText = Math.floor(Math.random()*20)+5;
    }, 1500);

    // 7. Server Core
    setInterval(() => {
        let c = Math.floor(Math.random()*20)+10; document.getElementById('cpu-bar').style.width = c+'%'; document.getElementById('cpu-txt').innerText = c+'%';
        let r = Math.floor(Math.random()*10)+40; document.getElementById('ram-bar').style.width = r+'%'; document.getElementById('ram-txt').innerText = r+'%';
        let t = Math.floor(Math.random()*5)+45; document.getElementById('temp-bar').style.width = t+'%'; document.getElementById('temp-txt').innerText = t+'°C';
    }, 2000);

    // 8. DDoS Shield
    const ddosNum = document.getElementById('ddos-num');
    const ddosDot = document.getElementById('ddos-dot');
    setInterval(() => {
        if(Math.random() > 0.8) { ddosNum.innerText = '100%'; ddosNum.style.color = 'var(--neon-red)'; ddosDot.classList.add('red'); } 
        else { ddosNum.innerText = Math.floor(Math.random()*5)+'%'; ddosNum.style.color = 'var(--black-hard)'; ddosDot.classList.remove('red'); }
    }, 3000);

    // 11. Sandbox Logs
    const sbWindow = document.getElementById('sandbox-log');
    const sbData = ["Isolating file_temp.php...", "Scanning heuristic signatures...", "No malware found. <span class='term-ok'>CLEAN</span>", "Process destroyed."];
    let sbIdx = 0;
    setInterval(() => {
        if(sbIdx >= sbData.length) { sbIdx = 0; sbWindow.innerHTML = ''; }
        sbWindow.innerHTML += `<div>> ${sbData[sbIdx]}</div>`; sbWindow.scrollTop = sbWindow.scrollHeight; sbIdx++;
    }, 1200);

    // 12. Dark Web & 14. Blockchain & 20. Firewall
    let dw = 14209; let blk = 849102; let fw = 1042;
    setInterval(() => {
        dw += Math.floor(Math.random()*3); document.getElementById('dark-num').innerText = dw.toLocaleString();
        if(Math.random()>0.5){ blk++; document.getElementById('block-num').innerText = '#'+blk.toLocaleString(); }
        if(Math.random()>0.8){ fw++; document.getElementById('fw-num').innerText = fw.toLocaleString(); }
    }, 2500);

    // 19. Auto Backup
    let bkp = 0;
    setInterval(() => {
        bkp += 5; if(bkp > 100) bkp = 0;
        document.getElementById('bkp-bar').style.width = bkp+'%'; document.getElementById('bkp-txt').innerText = bkp+'%';
    }, 1000);

</script>

<?php include '_footer.php'; ?>
