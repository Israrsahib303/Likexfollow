<?php
// boss_login.php - GOD MODE + SCANNER + MEMES + AURA
// Developed for: Israr Liaqat
// Security Level: MAX
declare(strict_types=1);

// 1. IRON DOME SECURITY HEADERS
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/iron_core.php';

// Secure Session Start
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', '1');
    session_start();
}

// 2. REDIRECT IF GENUINE ADMIN
if (function_exists('isAdmin') && isAdmin()) {
    header("Location: panel/index.php");
    exit;
}

// 3. RATE LIMITING (Anti-Brute Force)
$lockout_time = 900; // 15 Minutes
$max_attempts = 3;

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $time_since_last = time() - $_SESSION['last_attempt_time'];
    if ($time_since_last < $lockout_time) {
        die("<!DOCTYPE html><html><body style='background:#f4f7fc;color:#d32f2f;display:flex;align-items:center;justify-content:center;height:100vh;font-family:monospace;font-size:2rem;text-align:center;letter-spacing:1px;margin:0;padding:20px;box-sizing:border-box;'>
        <div style='background:#fff; border: 2px solid #d32f2f; padding: 40px 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(211,47,47,0.15); max-width:90%;'>
        🚫 SYSTEM LOCKED BY ISRAR LIAQAT.<br><br><span style='font-size:1.2rem; color:#666;'>WE HAVE YOUR LOCATION. EXECUTING COUNTER-MEASURES.</span></div></body></html>");
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

// 4. CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$trigger_trap = false; // Flag for the Fake Panel

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Honeypot Check
    if (!empty($_POST['website'])) {
        die("BOT DETECTED. TERMINATING.");
    }

    // B. CSRF Check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Session invalid. Refresh page.";
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $code = $_POST['access_code'];

        $stmt = $db->prepare("SELECT id, email, password_hash, role, is_admin FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // C. VERIFICATION LOGIC
        if ($user && ($user['is_admin'] == 1 || $user['role'] === 'admin') && password_verify($password, $user['password_hash'])) {
            
            // Credentials are Correct. Now Check Code.
            if ($code === '7860') {
                // --- GENUINE ACCESS ---
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = 1;
                $_SESSION['login_attempts'] = 0;
                $_SESSION['admin_lock_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['admin_lock_ua'] = md5($_SERVER['HTTP_USER_AGENT']);
                
                header("Location: panel/index.php");
                exit;
            } else {
                // --- WRONG CODE -> ACTIVATE TRAP ---
                $trigger_trap = true;
                error_log("Trap Triggered by IP: " . $_SERVER['REMOTE_ADDR']);
            }

        } else {
            // Wrong Email/Pass
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            usleep(rand(200000, 500000)); // Slow down response
            $error = "ACCESS DENIED. ISRAR IS WATCHING.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SECURE ACCESS // LEVEL 9</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Orbitron:wght@700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* THEME: PREMIUM WHITE & PURPLE MINIMALIST */
        :root {
            --primary: #6a00ff;
            --accent: #bc13fe;
            --bg-color: #f4f7fc;
            --surface: rgba(255, 255, 255, 0.85);
            --surface-border: rgba(106, 0, 255, 0.1);
            --text-main: #1e1e2d;
            --text-muted: #7e8299;
            --danger: #f1416c;
            --success: #50cd89;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh; /* Changed for Mobile Fix */
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden; 
            overflow-y: auto; /* Allows scrolling if keyboard opens on small mobiles */
            color: var(--text-main);
            position: relative;
            padding: 20px; /* Safe padding for mobile */
        }

        /* --- LIVE BACKGROUND ORBS --- */
        .bg-orb {
            position: fixed; /* Fixed so they don't stretch page */
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.5;
            animation: pulse-orb 8s infinite alternate cubic-bezier(0.4, 0, 0.2, 1);
        }
        .orb-1 {
            width: 50vw; height: 50vw; max-width: 400px; max-height: 400px;
            background: var(--primary);
            top: -10%; left: -10%;
        }
        .orb-2 {
            width: 40vw; height: 40vw; max-width: 300px; max-height: 300px;
            background: var(--accent);
            bottom: -5%; right: -5%;
            animation-delay: -4s;
        }

        @keyframes pulse-orb {
            0% { transform: scale(1) translate(0, 0); opacity: 0.3; }
            100% { transform: scale(1.3) translate(30px, -30px); opacity: 0.6; }
        }

        /* --- LIVE PARTICLES (Dust Effect) --- */
        #particles-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 1; pointer-events: none; overflow: hidden;
        }
        .particle {
            position: absolute;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            opacity: 0;
            box-shadow: 0 0 10px var(--accent);
            animation: float-particle linear infinite;
        }
        @keyframes float-particle {
            0% { transform: translateY(0) scale(0.5); opacity: 0; }
            30% { opacity: 0.8; transform: translateY(-30vh) scale(1); }
            100% { transform: translateY(-100vh) scale(0.5); opacity: 0; }
        }

        /* --- LOGIN PANEL --- */
        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 420px;
            z-index: 10;
            opacity: 0; 
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            margin: auto;
        }

        .login-wrapper.active {
            opacity: 1;
            transform: translateY(0);
        }

        .glass-panel {
            background: var(--surface);
            backdrop-filter: blur(25px) saturate(200%);
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid var(--surface-border);
            border-radius: 24px;
            padding: 45px 35px;
            box-shadow: 0 25px 50px rgba(106, 0, 255, 0.08), 0 0 0 1px rgba(255,255,255,0.5) inset;
            text-align: center;
            width: 100%;
        }

        .aura-badge {
            background: rgba(106, 0, 255, 0.05);
            color: var(--primary);
            border: 1px solid rgba(106, 0, 255, 0.15);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            margin-bottom: 25px;
            text-transform: uppercase;
        }
        
        .aura-badge span {
            width: 6px; height: 6px;
            background: var(--primary);
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 8px var(--primary);
            animation: pulse-dot 1.5s infinite alternate;
        }

        @keyframes pulse-dot {
            0% { opacity: 1; transform: scale(1); box-shadow: 0 0 12px var(--primary); }
            100% { opacity: 0.3; transform: scale(0.7); box-shadow: 0 0 2px var(--primary); }
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            letter-spacing: 1px;
            font-size: 1.8rem;
            margin: 0 0 30px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-group {
            margin-bottom: 18px;
            position: relative;
        }

        input {
            width: 100%;
            padding: 15px 20px;
            background: #ffffff;
            border: 2px solid #e1e3ea;
            border-radius: 12px;
            color: var(--text-main);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            -webkit-appearance: none; /* Mobile rendering fix */
        }

        input::placeholder { color: #a1a5b7; }

        input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(106, 0, 255, 0.1);
        }

        .pin-input {
            letter-spacing: 8px;
            font-size: 1.2rem;
            text-align: center;
            font-weight: 700;
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
        }
        
        .pin-input::placeholder {
            letter-spacing: 2px;
            font-size: 0.9rem;
            font-weight: normal;
        }

        /* LIVE BUTTON ANIMATION */
        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            background-size: 200% 200%;
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(106, 0, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: gradient-shift 3s infinite alternate;
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        /* Button Live Shimmer Effect */
        button::after {
            content: '';
            position: absolute;
            top: 0; left: -150%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: skewX(-20deg);
            animation: button-shimmer 3s infinite;
        }

        @keyframes button-shimmer {
            0% { left: -150%; }
            50% { left: 150%; }
            100% { left: 150%; }
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(106, 0, 255, 0.3);
        }

        .error-msg {
            color: var(--danger);
            background: rgba(241, 65, 108, 0.1);
            border: 1px dashed rgba(241, 65, 108, 0.3);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .server-info {
            margin-top: 30px;
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: 'JetBrains Mono', monospace;
            border-top: 1px solid #eff2f5;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        /* --- HIGH-END WHITE SECURITY HUD MODAL --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: opacity 0.5s ease;
            padding: 15px; /* Mobile safe padding */
        }

        .modal-box {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(241, 65, 108, 0.2);
            box-shadow: 0 30px 60px rgba(0,0,0,0.1), 0 0 0 4px rgba(241, 65, 108, 0.05);
            position: relative;
            overflow: hidden;
        }

        /* Purple Scanner Line */
        .laser-scanner {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
            box-shadow: 0 0 15px var(--primary);
            animation: scan-hud 2.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
            z-index: 2;
        }
        @keyframes scan-hud { 
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .modal-icon { 
            width: 45px; height: 45px;
            background: rgba(241, 65, 108, 0.1);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: var(--danger);
            font-size: 1.4rem;
        }
        
        .modal-title { 
            font-family: 'Orbitron'; font-size: 1.2rem; color: var(--text-main); 
            text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; margin: 0;
        }

        .modal-text {
            font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 25px;
        }

        /* DARK TERMINAL INSIDE LIGHT MODAL */
        .data-terminal {
            background: #111118;
            border: 1px solid #2a2a35;
            color: #00ff88;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            padding: 20px;
            text-align: left;
            margin-bottom: 25px;
            border-radius: 12px;
            min-height: 140px;
            box-shadow: inset 0 5px 15px rgba(0,0,0,0.5);
        }
        
        .blinking-cursor::after { content: '█'; animation: blink 1s infinite; margin-left: 5px; opacity: 0.8;}
        @keyframes blink { 0%, 100% { opacity: 0; } 50% { opacity: 0.8; } }

        .modal-btn {
            background: var(--bg-color); color: var(--text-main); 
            padding: 14px 25px; border-radius: 10px;
            border: 1px solid #e1e3ea; 
            cursor: pointer; font-weight: 600; font-size: 0.9rem;
            transition: all 0.3s; width: 100%; font-family: 'Poppins', sans-serif;
        }
        .modal-btn:hover { 
            background: #fff; 
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 5px 15px rgba(106, 0, 255, 0.1);
        }

        /* --- TRAP OVERLAY & MEMES --- */
        #trap-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 9999; display: none;
            flex-direction: column; align-items: center; justify-content: center;
            color: var(--danger);
            padding: 20px; text-align: center;
        }
        .meme-img {
            max-width: 90%; width: 350px; border: 4px solid var(--danger); 
            border-radius: 16px; box-shadow: 0 0 50px rgba(241, 65, 108, 0.4); 
            margin-bottom: 30px; display: none;
        }
        .troll-text { font-family: 'Orbitron'; font-size: 2rem; color: var(--danger); letter-spacing: 2px; margin-bottom: 10px; text-shadow: 0 0 20px rgba(241,65,108,0.5);}
        .hp-field { opacity: 0; position: absolute; height: 0; width: 0; pointer-events: none; }

        /* --- MOBILE RESPONSIVE TWEAKS --- */
        @media (max-width: 480px) {
            .glass-panel { padding: 35px 20px; }
            h1 { font-size: 1.5rem; margin-bottom: 20px; }
            input { padding: 14px 15px; font-size: 0.9rem; }
            .pin-input { font-size: 1.1rem; letter-spacing: 5px; }
            .server-info { flex-direction: column; gap: 8px; text-align: center; }
            .modal-box { padding: 25px; }
            .troll-text { font-size: 1.5rem; }
            .data-terminal { min-height: 120px; padding: 15px; font-size: 0.7rem; }
        }
    </style>
</head>
<body oncontextmenu="return false;">

    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    
    <div id="particles-container"></div>

    <?php if(!$trigger_trap && empty($_POST)): ?>
    <div class="modal-overlay" id="securityModal">
        <div class="modal-box">
            <div class="laser-scanner"></div>
            
            <div class="modal-header">
                <div class="modal-icon">🛡️</div>
                <h2 class="modal-title">Security Check</h2>
            </div>
            
            <p class="modal-text">
                <strong>Identity Scan Active.</strong><br>
                This gateway is strictly monitored under Israr Liaqat's protocols. Unauthorized requests are logged.
            </p>

            <div class="data-terminal" id="terminal">
                <span id="typewriter"></span><span class="blinking-cursor"></span>
            </div>

            <button class="modal-btn" onclick="closeModal()">Acknowledge</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="login-wrapper" id="loginPanel">
        <div class="glass-panel">
            <div class="aura-badge"><span></span>SECURED BY ISRAR</div>
            <h1>SYSTEM ACCESS</h1>
            
            <?php if($error): ?>
                <div class="error-msg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">

                <div class="input-group">
                    <input type="email" name="email" placeholder="Admin ID" required>
                </div>
                
                <div class="input-group">
                    <input type="password" name="password" placeholder="Passphrase" required>
                </div>

                <div class="input-group">
                    <input type="password" name="access_code" class="pin-input" placeholder="PIN CODE" maxlength="4" required>
                </div>

                <button type="submit">Authenticate</button>
            </form>
            
            <div class="server-info">
                <span>HOST: <?= strtoupper(explode('.', $_SERVER['SERVER_NAME'])[0] ?? 'LOCAL') ?></span>
                <span style="color: var(--primary); font-weight:600;">ACTIVE</span>
            </div>
        </div>
    </div>

    <div id="trap-overlay">
        <img src="https://media.tenor.com/x8v1oNUOmg4AAAAC/rickroll-roll.gif" class="meme-img" id="meme1">
        <img src="https://i.imgflip.com/2/3m6y60.jpg" class="meme-img" id="meme2">
        
        <h2 class="troll-text">ACCESS DENIED</h2>
        <p style="color: #fff; font-family: 'JetBrains Mono', monospace; font-size: 0.9rem;">Israr's Firewall has detected your breach attempt.</p>
        <div style="margin-top:20px; font-family:'JetBrains Mono', monospace; color:var(--danger); font-size:0.8rem; text-align:left; width: 100%; max-width:400px; background: rgba(241, 65, 108, 0.1); padding: 15px; border-radius: 12px; border: 1px dashed rgba(241, 65, 108, 0.4);" id="trap-log"></div>
    </div>

    <script>
        // --- 1. LIVE PARTICLES GENERATOR ---
        function createParticles() {
            const container = document.getElementById('particles-container');
            const particleCount = window.innerWidth < 600 ? 15 : 30; // Fewer particles on mobile for performance
            
            for(let i=0; i<particleCount; i++) {
                let p = document.createElement('div');
                p.classList.add('particle');
                
                let size = Math.random() * 4 + 2; // 2px to 6px
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                
                p.style.left = Math.random() * 100 + 'vw';
                p.style.top = Math.random() * 100 + 'vh';
                
                p.style.animationDuration = (Math.random() * 5 + 3) + 's';
                p.style.animationDelay = (Math.random() * 5) + 's';
                
                container.appendChild(p);
            }
        }

        // --- 2. LIVE TYPING & TRACKING EFFECT ---
        async function runScanner() {
            const terminal = document.getElementById('typewriter');
            if(!terminal) return;

            // Fetch IP
            let ip = "Tracing...";
            let loc = "Unknown Node";
            
            try {
                const res = await fetch('https://ipapi.co/json/');
                const data = await res.json();
                ip = data.ip || "Hidden";
                loc = (data.city || "") + ", " + (data.country_name || "");
            } catch(e) {}

            // Detect Device
            const ua = navigator.userAgent;
            let device = "Desktop Node";
            if(ua.match(/Android/i)) device = "Android Mobile";
            else if(ua.match(/iPhone/i)) device = "iOS Device";
            else if(ua.match(/Windows/i)) device = "Windows Workstation";

            const lines = [
                "> INIT: SECURITY_PROTOCOL",
                "> HANDSHAKE ESTABLISHED.",
                "> GUEST TARGET ACQUIRED.",
                "> IP_ADDR  : " + ip,
                "> LOCATION : " + loc,
                "> CLIENT   : " + device,
                "> CLEARANCE: PENDING",
                "> AWAITING OVERRIDE..."
            ];

            let lineIndex = 0;
            let charIndex = 0;
            let currentText = "";

            function typeLine() {
                if (lineIndex < lines.length) {
                    if (charIndex < lines[lineIndex].length) {
                        currentText += lines[lineIndex].charAt(charIndex);
                        terminal.innerHTML = currentText.replace(/\n/g, "<br>");
                        charIndex++;
                        setTimeout(typeLine, 15); 
                    } else {
                        currentText += "<br>";
                        terminal.innerHTML = currentText;
                        lineIndex++;
                        charIndex = 0;
                        setTimeout(typeLine, 250);
                    }
                }
            }
            typeLine();
        }

        // Run functions on load
        window.onload = function() {
            createParticles();
            runScanner();
            <?php if($_SERVER['REQUEST_METHOD'] === 'POST' || $trigger_trap): ?>
                document.getElementById('loginPanel').classList.add('active');
            <?php endif; ?>
        };

        // 3. POPUP CONTROL
        function closeModal() {
            const modal = document.getElementById('securityModal');
            const panel = document.getElementById('loginPanel');
            if(modal) {
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    panel.classList.add('active');
                }, 400);
            }
        }

        // 4. SECURITY: Block Inspect
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.onkeydown = function(e) {
            if(e.keyCode == 123) return false; 
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
        }

        // 5. TRAP LOGIC (Memes & Trolling)
        <?php if($trigger_trap): ?>
        (function() {
            document.getElementById('loginPanel').style.display = 'none';
            if(document.getElementById('securityModal')) document.getElementById('securityModal').style.display = 'none';
            
            const trap = document.getElementById('trap-overlay');
            const meme1 = document.getElementById('meme1');
            const meme2 = document.getElementById('meme2');
            
            trap.style.display = 'flex';
            meme1.style.display = 'block'; 

            // Audio
            let audio = new Audio('https://www.myinstants.com/media/sounds/error.mp3');
            audio.play().catch(e=>{});

            // Log Text
            const logs = document.getElementById('trap-log');
            let lines = [
                "CRITICAL_ALERT: UNAUTHORIZED PIN.", 
                "UPLOADING_SNAPSHOT_TO_SERVER...", 
                "BLOCKING_IP_ADDRESS...", 
                "DISPATCHING_LOGS_TO_ADMIN..."
            ];
            let i = 0;
            
            setInterval(() => {
                if(i < lines.length) {
                    logs.innerHTML += "<div>> " + lines[i] + "</div>";
                    i++;
                }
            }, 1000);

            // Switch to Hacker Meme after 4 seconds
            setTimeout(() => {
                meme1.style.display = 'none';
                meme2.style.display = 'block';
            }, 4000);

            // Redirect
            setTimeout(() => { window.location.href = "https://www.google.com/search?q=jail+time+for+hacking"; }, 8000);
        })();
        <?php endif; ?>
    </script>
</body>
</html>
