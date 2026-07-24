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
        die("<!DOCTYPE html><html><body style='background:#F5F2EA;color:#3D3929;display:flex;align-items:center;justify-content:center;height:100vh;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;font-size:1.4rem;text-align:center;margin:0;padding:20px;box-sizing:border-box;'>
        <div style='background:#fff; border: 1px solid #E8E4D9; padding: 48px 36px; border-radius: 16px; box-shadow: 0 8px 24px rgba(61,57,41,0.08); max-width:440px;'>
        🔒 System locked by Israr Liaqat.<br><br><span style='font-size:1rem; color:#83816D; font-weight:400;'>Too many attempts. Please try again in 15 minutes.</span></div></body></html>");
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
            $error = "Access denied. Check your credentials and try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Secure Access</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,500;8..60,600&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ==========================================================
           CLAUDE.AI-STYLE INTERFACE
           Warm cream surfaces, muted clay accent, calm serif headings,
           soft shadows, gentle rounded corners, quiet motion.
           ========================================================== */
        :root {
            --bg: #FAF9F5;
            --bg-panel: #F0EEE5;
            --surface: #FFFFFF;
            --border: #E5E2D9;
            --border-soft: #ECE9DF;
            --text-primary: #3D3929;
            --text-secondary: #6B6754;
            --text-muted: #93917E;
            --clay: #C96442;
            --clay-hover: #B85C3D;
            --clay-soft: #F3E7DE;
            --success: #6A8759;
            --danger: #BF4C3B;
            --danger-soft: #FBEEEC;
            --radius-lg: 20px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --ease: cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-font-smoothing: antialiased; }

        body {
            background: var(--bg);
            min-height: 100vh;
            width: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text-primary);
            display: flex;
            align-items: stretch;
            justify-content: center;
            overflow-x: hidden;
        }

        .shell {
            width: 100%;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ================= LEFT PANEL ================= */
        .side-panel {
            background: var(--bg-panel);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px;
            position: relative;
            border-right: 1px solid var(--border);
        }

        .side-content {
            max-width: 420px;
        }

        .mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--clay);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            box-shadow: 0 2px 6px rgba(201, 100, 66, 0.25);
        }

        .mark svg { width: 22px; height: 22px; }

        .side-content h1 {
            font-family: 'Source Serif 4', Georgia, serif;
            font-weight: 500;
            font-size: 2.4rem;
            line-height: 1.2;
            color: var(--text-primary);
            margin-bottom: 18px;
            letter-spacing: -0.01em;
        }

        .side-content p {
            font-size: 0.98rem;
            line-height: 1.65;
            color: var(--text-secondary);
            margin-bottom: 40px;
        }

        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .feature-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon svg { width: 16px; height: 16px; color: var(--clay); }

        .feature-text {
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.5;
            padding-top: 5px;
        }

        .feature-text strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .side-footer {
            position: absolute;
            bottom: 40px;
            left: 64px;
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .side-footer .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(106, 135, 89, 0.15);
        }

        /* ================= RIGHT: FORM PANEL ================= */
        .form-panel {
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
        }

        .form-card {
            width: 100%;
            max-width: 400px;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.6s var(--ease), transform 0.6s var(--ease);
        }

        .form-card.active {
            opacity: 1;
            transform: translateY(0);
        }

        .form-card h2 {
            font-family: 'Source Serif 4', Georgia, serif;
            font-weight: 500;
            font-size: 1.7rem;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }

        .form-sub {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 7px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 0.94rem;
            outline: none;
            transition: border-color 0.2s var(--ease), box-shadow 0.2s var(--ease);
            -webkit-appearance: none;
        }

        input::placeholder { color: var(--text-muted); }

        input:hover { border-color: #D6D2C4; }

        input:focus {
            border-color: var(--clay);
            box-shadow: 0 0 0 3px var(--clay-soft);
        }

        .pin-input {
            letter-spacing: 6px;
            font-size: 1rem;
            text-align: center;
            font-weight: 500;
            font-family: 'JetBrains Mono', monospace;
        }

        .pin-input::placeholder {
            letter-spacing: 3px;
            font-family: 'Inter', sans-serif;
            font-weight: 400;
        }

        button {
            width: 100%;
            padding: 13px;
            background: var(--clay);
            border: none;
            border-radius: var(--radius-sm);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.94rem;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s var(--ease), transform 0.15s var(--ease), box-shadow 0.2s var(--ease);
            box-shadow: 0 1px 2px rgba(201, 100, 66, 0.15);
        }

        button:hover {
            background: var(--clay-hover);
            box-shadow: 0 4px 12px rgba(201, 100, 66, 0.25);
        }

        button:active {
            transform: scale(0.98);
        }

        .error-msg {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid rgba(191, 76, 59, 0.2);
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 9px;
            line-height: 1.4;
        }

        .error-msg svg { flex-shrink: 0; }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 20px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 0.76rem;
            color: var(--text-muted);
        }

        .server-info {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .server-info .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .server-info .status-pill .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--success);
        }

        .hp-field { opacity: 0; position: absolute; height: 0; width: 0; pointer-events: none; }

        /* ================= SECURITY CHECK MODAL ================= */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(61, 57, 41, 0.35);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            animation: overlay-in 0.35s var(--ease) forwards;
        }

        @keyframes overlay-in { to { opacity: 1; } }

        .modal-box {
            background: var(--surface);
            width: 100%;
            max-width: 440px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(61, 57, 41, 0.15);
            padding: 32px;
            transform: scale(0.96) translateY(8px);
            opacity: 0;
            animation: modal-in 0.45s 0.05s var(--ease) forwards;
        }

        @keyframes modal-in { to { transform: scale(1) translateY(0); opacity: 1; } }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .modal-icon {
            width: 42px; height: 42px;
            border-radius: 11px;
            background: var(--clay-soft);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .modal-icon svg { width: 20px; height: 20px; color: var(--clay); }

        .modal-title {
            font-family: 'Source Serif 4', Georgia, serif;
            font-weight: 500;
            font-size: 1.15rem;
            color: var(--text-primary);
        }

        .modal-text {
            font-size: 0.88rem;
            line-height: 1.6;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .data-terminal {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.76rem;
            line-height: 1.7;
            padding: 16px 18px;
            margin-bottom: 22px;
            border-radius: var(--radius-md);
            min-height: 150px;
        }

        .data-terminal .accent { color: var(--clay); }

        .blinking-cursor::after { content: '|'; animation: blink 1s step-start infinite; margin-left: 2px; color: var(--clay); }
        @keyframes blink { 50% { opacity: 0; } }

        .modal-btn {
            width: 100%;
            background: var(--clay);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 13px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s var(--ease), transform 0.15s var(--ease);
        }

        .modal-btn:hover { background: var(--clay-hover); }
        .modal-btn:active { transform: scale(0.98); }

        /* ================= TRAP OVERLAY ================= */
        #trap-overlay {
            position: fixed; inset: 0;
            background: var(--bg);
            z-index: 9999; display: none;
            flex-direction: column; align-items: center; justify-content: center;
            padding: 20px; text-align: center;
            opacity: 0;
            transition: opacity 0.5s var(--ease);
        }

        #trap-overlay.show { opacity: 1; }

        .trap-icon {
            width: 64px; height: 64px;
            border-radius: 16px;
            background: var(--danger-soft);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px;
        }

        .trap-icon svg { width: 30px; height: 30px; color: var(--danger); }

        .meme-img {
            max-width: 90%; width: 340px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(61, 57, 41, 0.15);
            margin-bottom: 26px; display: none;
            opacity: 0;
            transform: scale(0.95) translateY(8px);
            transition: opacity 0.5s var(--ease), transform 0.5s var(--ease);
        }

        .meme-img.show { opacity: 1; transform: scale(1) translateY(0); }

        .troll-text {
            font-family: 'Source Serif 4', Georgia, serif;
            font-weight: 500;
            font-size: 1.9rem;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .trap-sub {
            color: var(--text-secondary);
            font-size: 0.94rem;
            max-width: 380px;
            line-height: 1.55;
        }

        #trap-log {
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-secondary);
            font-size: 0.78rem;
            text-align: left;
            width: 100%; max-width: 380px;
            background: var(--bg-panel);
            border: 1px solid var(--border);
            padding: 16px 18px;
            border-radius: var(--radius-md);
            margin-top: 22px;
            line-height: 1.8;
        }

        #trap-log div {
            opacity: 0;
            animation: log-in 0.4s var(--ease) forwards;
        }

        #trap-log div::before {
            content: '→ ';
            color: var(--clay);
        }

        @keyframes log-in {
            from { opacity: 0; transform: translateX(-6px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .side-panel { padding: 40px 32px; min-height: auto; border-right: none; border-bottom: 1px solid var(--border); }
            .side-content h1 { font-size: 1.7rem; }
            .side-content p { margin-bottom: 28px; font-size: 0.9rem; }
            .feature-list { gap: 14px; }
            .side-footer { display: none; }
            .form-panel { padding: 40px 24px 56px; }
        }

        @media (max-width: 480px) {
            .side-panel { padding: 32px 24px; }
            .mark { width: 38px; height: 38px; margin-bottom: 22px; }
            .side-content h1 { font-size: 1.5rem; }
            .form-card h2 { font-size: 1.4rem; }
            .modal-box { padding: 26px; }
            .troll-text { font-size: 1.5rem; }
            .data-terminal { min-height: 130px; padding: 14px; font-size: 0.72rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body oncontextmenu="return false;">

    <div class="shell">

        <!-- ============ LEFT SIDE PANEL ============ -->
        <div class="side-panel">
            <div class="side-content">
                <div class="mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 7.2H22l-6 4.4 2.3 7.2L12 16.4 5.7 20.8 8 13.6 2 9.2h7.6z"/></svg>
                </div>

                <h1>A secure space for admin access</h1>
                <p>This gateway is monitored and protected. Sign in with your administrator credentials to continue to your workspace.</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <div class="feature-text"><strong>Encrypted by default.</strong> Every session is protected end to end.</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="feature-text"><strong>Rate-limited access.</strong> Repeated failed attempts trigger a cooldown.</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <div class="feature-text"><strong>Logged in real time.</strong> All access attempts are recorded.</div>
                    </div>
                </div>
            </div>

            <div class="side-footer">
                <span class="pulse"></span>
                HOST: <?= strtoupper(explode('.', $_SERVER['SERVER_NAME'])[0] ?? 'LOCAL') ?> · SYSTEM ONLINE
            </div>
        </div>

        <!-- ============ RIGHT FORM PANEL ============ -->
        <div class="form-panel">

            <?php if(!$trigger_trap && empty($_POST)): ?>
            <div class="modal-overlay" id="securityModal">
                <div class="modal-box">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <h2 class="modal-title">Security check</h2>
                    </div>

                    <p class="modal-text">
                        This gateway is monitored. We're running a quick identity check before you continue — this only takes a moment.
                    </p>

                    <div class="data-terminal" id="terminal">
                        <span id="typewriter"></span><span class="blinking-cursor"></span>
                    </div>

                    <button class="modal-btn" onclick="closeModal()">Continue</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-card" id="loginPanel">
                <h2>Welcome back</h2>
                <p class="form-sub">Sign in to access the admin panel.</p>

                <?php if($error): ?>
                    <div class="error-msg">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">

                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" placeholder="you@domain.com" required>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <div class="field">
                        <label for="access_code">Access code</label>
                        <input id="access_code" type="password" name="access_code" class="pin-input" placeholder="4-digit code" maxlength="4" required>
                    </div>

                    <button type="submit">Sign in</button>
                </form>

                <div class="divider"><span>SESSION INFO</span></div>

                <div class="server-info">
                    <span>Encrypted connection</span>
                    <span class="status-pill"><span class="pulse"></span>Active</span>
                </div>
            </div>

        </div>
    </div>

    <div id="trap-overlay">
        <div class="trap-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <img src="https://media.tenor.com/x8v1oNUOmg4AAAAC/rickroll-roll.gif" class="meme-img" id="meme1">
        <img src="https://i.imgflip.com/2/3m6y60.jpg" class="meme-img" id="meme2">

        <h2 class="troll-text">Access denied</h2>
        <p class="trap-sub">That code wasn't right. This attempt has been logged and flagged by the system.</p>
        <div id="trap-log"></div>
    </div>

    <script>
        // --- Identity scan terminal text ---
        async function runScanner() {
            const terminal = document.getElementById('typewriter');
            if (!terminal) return;

            let ip = "Tracing...";
            let loc = "Unknown";

            try {
                const res = await fetch('https://ipapi.co/json/');
                const data = await res.json();
                ip = data.ip || "Hidden";
                loc = (data.city || "") + ", " + (data.country_name || "");
            } catch (e) {}

            const ua = navigator.userAgent;
            let device = "Desktop";
            if (ua.match(/Android/i)) device = "Android";
            else if (ua.match(/iPhone/i)) device = "iOS";
            else if (ua.match(/Windows/i)) device = "Windows";

            const lines = [
                "Initializing security protocol...",
                "Handshake established.",
                "Session identified.",
                "IP address:  " + ip,
                "Location:    " + loc,
                "Client:      " + device,
                "Clearance:   pending",
                "Awaiting confirmation..."
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
                        setTimeout(typeLine, 14);
                    } else {
                        currentText += "<br>";
                        terminal.innerHTML = currentText;
                        lineIndex++;
                        charIndex = 0;
                        setTimeout(typeLine, 220);
                    }
                }
            }
            typeLine();
        }

        window.onload = function () {
            runScanner();
            <?php if($_SERVER['REQUEST_METHOD'] === 'POST' || $trigger_trap): ?>
                document.getElementById('loginPanel').classList.add('active');
            <?php endif; ?>
        };

        function closeModal() {
            const modal = document.getElementById('securityModal');
            const panel = document.getElementById('loginPanel');
            if (modal) {
                modal.style.transition = 'opacity 0.35s ease';
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    panel.classList.add('active');
                }, 320);
            } else if (panel) {
                panel.classList.add('active');
            }
        }

        <?php if($trigger_trap || !empty($_POST)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('loginPanel');
            if (panel) panel.classList.add('active');
        });
        <?php endif; ?>

        // Block inspect
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.onkeydown = function (e) {
            if (e.keyCode == 123) return false;
            if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
            if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
        }

        // Trap logic
        <?php if($trigger_trap): ?>
        (function () {
            const loginPanel = document.getElementById('loginPanel');
            const secModal = document.getElementById('securityModal');
            if (loginPanel) loginPanel.style.display = 'none';
            if (secModal) secModal.style.display = 'none';

            const trap = document.getElementById('trap-overlay');
            const meme1 = document.getElementById('meme1');
            const meme2 = document.getElementById('meme2');

            trap.style.display = 'flex';
            requestAnimationFrame(() => {
                trap.classList.add('show');
                meme1.style.display = 'block';
                requestAnimationFrame(() => meme1.classList.add('show'));
            });

            let audio = new Audio('https://www.myinstants.com/media/sounds/error.mp3');
            audio.play().catch(e => {});

            const logs = document.getElementById('trap-log');
            let lines = [
                "Unauthorized access code detected",
                "Snapshot uploaded to server",
                "IP address flagged",
                "Report sent to administrator"
            ];
            let i = 0;

            const logInterval = setInterval(() => {
                if (i < lines.length) {
                    const div = document.createElement('div');
                    div.textContent = lines[i];
                    logs.appendChild(div);
                    i++;
                } else {
                    clearInterval(logInterval);
                }
            }, 950);

            setTimeout(() => {
                meme1.classList.remove('show');
                setTimeout(() => {
                    meme1.style.display = 'none';
                    meme2.style.display = 'block';
                    requestAnimationFrame(() => meme2.classList.add('show'));
                }, 380);
            }, 4000);

            setTimeout(() => {
                window.location.href = "https://www.google.com/search?q=jail+time+for+hacking";
            }, 8200);
        })();
        <?php endif; ?>
    </script>
</body>
</html>