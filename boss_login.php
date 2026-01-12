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
        die("<!DOCTYPE html><html><body style='background:#000;color:red;display:flex;align-items:center;justify-content:center;height:100vh;font-family:monospace;font-size:2rem;text-align:center;'>
        🚫 SYSTEM LOCKED BY ISRAR LIAQAT.<br>WE HAVE YOUR LOCATION.<br>POLICE ARE ON THE WAY.</body></html>");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SECURE ACCESS // LEVEL 9</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <style>
        /* THEME: WHITE & PURPLE GLASS */
        :root {
            --primary: #6a00ff;
            --accent: #bc13fe;
            --bg-color: #f4f7fc;
            --glass: rgba(255, 255, 255, 0.95);
            --text-dark: #1a1a2e;
            --shadow: 0 15px 50px rgba(106, 0, 255, 0.2);
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(106, 0, 255, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(188, 19, 254, 0.1) 0%, transparent 20%);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
            color: var(--text-dark);
        }

        /* --- LOGIN PANEL --- */
        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            z-index: 1;
            opacity: 0; 
            transition: opacity 0.8s ease;
        }

        .glass-panel {
            background: var(--glass);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
        }

        .aura-badge {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.6rem;
            font-weight: 800;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(106, 0, 255, 0.3);
        }

        h1 {
            margin: 0 0 25px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            letter-spacing: 1px;
            font-size: 1.8rem;
            background: -webkit-linear-gradient(var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 12px;
            background: rgba(240, 240, 250, 0.8);
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            color: var(--text-dark);
            font-family: 'Poppins';
            font-weight: 600;
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
            text-align: center;
        }

        input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(106, 0, 255, 0.1);
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(106, 0, 255, 0.25);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(106, 0, 255, 0.4);
        }

        .error-msg {
            color: #d32f2f;
            background: #ffebee;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* --- SECURITY WARNING POPUP --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(15px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.5s ease;
        }

        .modal-box {
            background: #0a0a0a;
            width: 85%;
            max-width: 380px;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            border: 2px solid #ff0000;
            box-shadow: 0 0 80px rgba(255, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        /* SCANNING ANIMATION */
        .laser-scanner {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 10px;
            background: linear-gradient(to bottom, rgba(255,0,0,0), rgba(255,0,0,0.8), rgba(255,0,0,0));
            box-shadow: 0 0 15px red;
            animation: scan 3s linear infinite;
            z-index: 2;
            pointer-events: none;
        }
        @keyframes scan { 
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .modal-icon { font-size: 3rem; margin-bottom: 10px; animation: pulse 1s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        
        .modal-title { 
            font-family: 'Orbitron'; font-size: 1.4rem; color: #ff3333; 
            margin-bottom: 10px; text-transform: uppercase; font-weight: 900;
            text-shadow: 0 0 10px red;
        }

        .modal-text {
            font-size: 0.85rem; color: #ccc; line-height: 1.5; margin-bottom: 20px;
        }

        /* DATA BOX */
        .data-terminal {
            background: #000;
            border: 1px solid #333;
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            padding: 15px;
            text-align: left;
            margin-bottom: 20px;
            border-radius: 6px;
            min-height: 100px;
        }
        
        .data-line { display: block; margin-bottom: 5px; border-bottom: 1px dashed #222; }
        .blinking-cursor::after { content: '|'; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0; } }

        .modal-btn {
            background: #ff0000; color: #fff; padding: 14px 25px; border-radius: 50px;
            border: none; cursor: pointer; font-weight: bold; font-size: 0.9rem;
            transition: 0.3s; width: 100%; letter-spacing: 1px;
            box-shadow: 0 0 20px rgba(255,0,0,0.4);
        }
        .modal-btn:hover { background: #cc0000; transform: scale(1.02); }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* --- TRAP OVERLAY & MEMES --- */
        #trap-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 9999; display: none;
            flex-direction: column; align-items: center; justify-content: center;
            color: #0f0;
        }
        .meme-img {
            max-width: 90%; width: 300px; border: 5px solid red; 
            box-shadow: 0 0 50px red; margin-bottom: 20px;
            display: none; /* Controlled by JS */
        }
        .troll-text { font-family: 'Orbitron'; font-size: 2rem; color: red; text-shadow: 0 0 20px red; text-align: center;}
        .hp-field { opacity: 0; position: absolute; height: 0; width: 0; }

    </style>
</head>
<body oncontextmenu="return false;">

    <?php if(!$trigger_trap && empty($_POST)): ?>
    <div class="modal-overlay" id="securityModal">
        <div class="modal-box">
            <div class="laser-scanner"></div>
            <div class="modal-icon">👁️</div>
            <h2 class="modal-title">SECURITY WARNING</h2>
            
            <p class="modal-text">
                <strong>Identity Scan In Progress...</strong><br>
                This area is protected by the Aura of <strong>Israr Liaqat</strong>. 
                Any unauthorized attempt will be tracked.
            </p>

            <div class="data-terminal" id="terminal">
                <span id="typewriter"></span><span class="blinking-cursor"></span>
            </div>

            <button class="modal-btn" onclick="closeModal()">Proceed with Caution</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="login-wrapper" id="loginPanel">
        <div class="glass-panel">
            <div class="aura-badge">✨ SECURED BY ISRAR LIAQAT</div>
            <h1>BOSS ACCESS</h1>
            
            <?php if($error): ?>
                <div class="error-msg">❌ <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">

                <input type="email" name="email" placeholder="Commander ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="access_code" placeholder="PIN (????)" maxlength="4" style="letter-spacing: 5px; font-weight: bold; color: var(--primary);">

                <button type="submit">AUTHENTICATE</button>
            </form>
            
            <div style="margin-top: 25px; font-size: 0.7rem; color: #888;">
                Server ID: <?= $_SERVER['SERVER_NAME'] ?> <br> Protocol: OMEGA-9
            </div>
        </div>
    </div>

    <div id="trap-overlay">
        <img src="https://media.tenor.com/x8v1oNUOmg4AAAAC/rickroll-roll.gif" class="meme-img" id="meme1">
        <img src="https://i.imgflip.com/2/3m6y60.jpg" class="meme-img" id="meme2">
        
        <h2 class="troll-text">ACCESS DENIED</h2>
        <p style="color: #fff; font-family: monospace;">Israr's Aura is too strong for you.</p>
        <div style="margin-top:20px; font-family:monospace; color:#0f0; text-align:left; width: 80%; max-width:400px;" id="trap-log"></div>
    </div>

    <script>
        // --- 1. LIVE TYPING & TRACKING EFFECT ---
        async function runScanner() {
            const terminal = document.getElementById('typewriter');
            if(!terminal) return;

            // Fetch IP
            let ip = "Tracing...";
            let loc = "Unknown";
            
            try {
                const res = await fetch('https://ipapi.co/json/');
                const data = await res.json();
                ip = data.ip || "Hidden";
                loc = (data.city || "") + ", " + (data.country_name || "");
            } catch(e) {}

            // Detect Device
            const ua = navigator.userAgent;
            let device = "PC";
            if(ua.match(/Android/i)) device = "Android Mobile";
            else if(ua.match(/iPhone/i)) device = "Apple iPhone";
            else if(ua.match(/Windows/i)) device = "Windows System";

            const lines = [
                "Initializing Security Protocol...",
                "Detecting Intruder...",
                "IP Address: " + ip,
                "Location: " + loc,
                "Device: " + device,
                "Status: SUSPICIOUS",
                "Logging Identity to Server..."
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
                        setTimeout(typeLine, 30);
                    } else {
                        currentText += "<br>";
                        terminal.innerHTML = currentText;
                        lineIndex++;
                        charIndex = 0;
                        setTimeout(typeLine, 400);
                    }
                }
            }
            typeLine();
        }

        // Run scanner on load
        window.onload = function() {
            runScanner();
        };

        // 2. POPUP CONTROL
        function closeModal() {
            const modal = document.getElementById('securityModal');
            const panel = document.getElementById('loginPanel');
            if(modal) {
                modal.style.opacity = '0';
                setTimeout(() => modal.style.display = 'none', 400);
            }
            panel.style.opacity = '1';
        }

        // Auto-Run if no modal
        <?php if($_SERVER['REQUEST_METHOD'] === 'POST' && !$trigger_trap): ?>
            document.getElementById('loginPanel').style.opacity = '1';
        <?php endif; ?>

        // 3. SECURITY: Block Inspect
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.onkeydown = function(e) {
            if(e.keyCode == 123) return false; 
            if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
            if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
        }

        // 4. TRAP LOGIC (Memes & Trolling)
        <?php if($trigger_trap): ?>
        (function() {
            document.getElementById('loginPanel').style.display = 'none';
            if(document.getElementById('securityModal')) document.getElementById('securityModal').style.display = 'none';
            
            const trap = document.getElementById('trap-overlay');
            const meme1 = document.getElementById('meme1');
            const meme2 = document.getElementById('meme2');
            
            trap.style.display = 'flex';
            meme1.style.display = 'block'; // Show Rickroll immediately

            // Audio
            let audio = new Audio('https://www.myinstants.com/media/sounds/error.mp3');
            audio.play().catch(e=>{});

            // Log Text
            const logs = document.getElementById('trap-log');
            let lines = ["⚠️ TRAP ACTIVATED", "📸 UPLOADING PHOTO...", "🛑 BLOCKING IP...", "🚓 CONTACTING CYBER POLICE..."];
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