<?php
// Output Buffering ON (Prevents Header Errors)
ob_start();

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security_headers.php';

if (file_exists(__DIR__ . '/includes/google_config.php')) {
    require_once __DIR__ . '/includes/google_config.php';
}

// --- 1. AUTO-LOGIN CHECK ---
if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    if (ctype_xdigit($token)) {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if ($user) {
                // FORCE LOGOUT ON BAN
                if(isset($user['status']) && $user['status'] === 'banned') {
                    setcookie('remember_me', '', time() - 3600, '/');
                } 
                // SECURITY: Admin cannot auto-login via this page
                elseif ($user['is_admin'] == 1) {
                     setcookie('remember_me', '', time() - 3600, '/'); // Kill cookie immediately
                }
                else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = 0; // Force 0 just in case
                    // Refresh Cookie
                    setcookie('remember_me', $token, time() + (86400 * 30), "/");
                    
                    // SAVE SESSION & REDIRECT
                    session_write_close();
                    
                    // Standard User Redirect
                    if (!headers_sent()) {
                        header("Location: user/index.php");
                    }
                    echo "<script>window.location.href='user/index.php';</script>";
                    exit;
                }
            }
        } catch (Exception $e) { }
    }
}

function getLoginAttempts($db, $ip) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn();
}
function logFailedAttempt($db, $ip) {
    $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
}
function clearLoginAttempts($db, $ip) {
    $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
}

$error = '';
$ban_reason = ''; 
$ban_contact = 0; 
$ip_address = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $attempts = getLoginAttempts($db, $ip_address);
    if ($attempts >= 5) {
        $error = "⛔ Too many failed attempts. Blocked for 15 mins.";
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $csrf_token = $_POST['csrf_token'] ?? '';
        $enable_bio = isset($_POST['enable_bio']); 

        if (!verifyCsrfToken($csrf_token)) {
            $error = 'Session Expired. Refresh page.';
        } else {
            
            if(empty($error)) {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    
                    // --- 🚫 SECURITY: BLOCK ADMIN FROM HERE ---
                    if ($user['is_admin'] == 1) {
                        $error = "🚫 Access Denied! Admins must use the Secure Portal.";
                        // Do NOT start session for admin here
                    }
                    // --- 🚫 BAN CHECK ---
                    elseif (isset($user['status']) && $user['status'] === 'banned') {
                        $error = 'Account Suspended'; 
                        $ban_reason = !empty($user['ban_reason']) ? $user['ban_reason'] : "Your account has been suspended by the administrator.";
                        $ban_contact = isset($user['ban_show_contact']) ? $user['ban_show_contact'] : 0;
                        
                    } else {
                        // User Login Success
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['is_admin'] = 0; // Explicitly 0
                        clearLoginAttempts($db, $ip_address);

                        if (isset($_POST['remember_me'])) {
                            $t = bin2hex(random_bytes(32));
                            $db->prepare("UPDATE users SET remember_token=? WHERE id=?")->execute([$t, $user['id']]);
                            setcookie('remember_me', $t, time() + (86400 * 30), "/");
                        }
                        
                        if ($enable_bio) {
                            setcookie('bio_enabled', '1', time() + (86400 * 365), "/");
                        } else {
                            setcookie('bio_enabled', '', time() - 3600, "/");
                        }

                        // --- USER REDIRECT ---
                        session_write_close();
                        
                        if (!headers_sent()) {
                            header("Location: user/index.php");
                        }
                        echo "<script>window.location.href='user/index.php';</script>";
                        exit;
                    }
                } else {
                    logFailedAttempt($db, $ip_address);
                    $error = "Invalid email or password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary: #4f46e5; --bg-grad: #f3f4f6; --text: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body { background: var(--bg-grad); height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; z-index: -1; }
        .b1 { top: -10%; left: -10%; width: 300px; height: 300px; background: #818cf8; animation: float 6s infinite alternate; }
        .b2 { bottom: -10%; right: -10%; width: 300px; height: 300px; background: #c084fc; animation: float 6s infinite alternate-reverse; }
        @keyframes float { 0% { transform: translateY(0); } 100% { transform: translateY(30px); } }

        .login-wrapper { width: 100%; max-width: 400px; padding: 20px; display: flex; justify-content: center; }
        .login-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); width: 100%; border-radius: 24px; padding: 35px 25px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); border: 1px solid #fff; position: relative; z-index: 10; }

        .header { text-align: center; margin-bottom: 25px; }
        .logo { height: 45px; margin-bottom: 10px; }
        .title { font-size: 1.6rem; font-weight: 800; color: var(--text); margin: 0; }
        .sub { color: #64748b; font-size: 0.9rem; }

        .input-box { position: relative; margin-bottom: 15px; }
        .input-field { width: 100%; padding: 14px 14px 14px 45px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.95rem; outline: none; transition: 0.2s; background: #f8fafc; color: var(--text); }
        .input-field:focus { background: #fff; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        .options { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; margin-bottom: 20px; width: 100%; }
        .chk { display: flex; align-items: center; gap: 6px; cursor: pointer; color: #64748b; }
        .chk input { accent-color: var(--primary); width: 16px; height: 16px; }
        .forgot { color: var(--primary); font-weight: 600; text-decoration: none; transition: 0.2s; }
        .forgot:hover { text-decoration: underline; }

        .bio-switch { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: 0.2s; }
        .bio-switch:hover { background: #dcfce7; }
        .bio-txt { display: flex; align-items: center; gap: 10px; font-weight: 600; color: #166534; font-size: 0.9rem; }

        .btn-main { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-main:hover { background: #4338ca; transform: translateY(-2px); }

        .btn-google { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.9rem; font-weight: 600; color: #334155; text-decoration: none; margin-top: 15px; transition: 0.2s; }
        .btn-google:hover { background: #f8fafc; }

        .alert-err { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; font-weight: 600; border: 1px solid #fecaca; }

        #bioPopup { display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); align-items: flex-end; justify-content: center; animation: fadeIn 0.3s ease; }
        .bio-sheet { background: #fff; width: 100%; max-width: 400px; border-radius: 24px 24px 0 0; padding: 30px 20px 40px 20px; text-align: center; position: relative; animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.2); }
        @media(min-width: 450px) { #bioPopup { align-items: center; } .bio-sheet { border-radius: 24px; margin: 20px; padding-bottom: 30px; } }
        .sheet-handle { width: 40px; height: 5px; background: #e2e8f0; border-radius: 10px; margin: 0 auto 20px; }
        .scan-circle { width: 90px; height: 90px; border-radius: 50%; margin: 0 auto 20px; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 3rem; position: relative; cursor: pointer; border: 2px solid transparent; transition: 0.3s; }
        .scan-circle.active { border-color: var(--primary); background: #fff; animation: pulse 1.5s infinite; }
        .bio-h3 { font-size: 1.2rem; font-weight: 800; color: var(--text); margin: 0 0 5px; }
        .bio-p { color: #64748b; font-size: 0.9rem; margin: 0 0 25px; }
        .btn-close-bio { background: #f1f5f9; color: #64748b; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; cursor: pointer; }
        .btn-close-bio:hover { background: #e2e8f0; color: var(--text); }
        #verifyScreen { display: none; position: fixed; inset: 0; background: rgba(255,255,255,0.96); z-index: 3000; flex-direction: column; align-items: center; justify-content: center; }
        .loader { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 15px; }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.3); } 70% { box-shadow: 0 0 0 20px rgba(79, 70, 229, 0); } 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); } }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* --- CUSTOM GLASS POPUP --- */
        .glass-popup-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease; }
        .glass-popup { background: rgba(255, 255, 255, 0.9); width: 90%; max-width: 350px; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .gp-icon { width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 15px; }
        .gp-title { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0 0 10px; }
        .gp-text { font-size: 0.95rem; color: #64748b; line-height: 1.5; margin-bottom: 25px; }
        
        .gp-btn { width: 100%; padding: 12px; background: #334155; color: white; font-weight: 700; border: none; border-radius: 12px; cursor: pointer; transition: 0.2s; }
        .gp-btn:hover { background: #1e293b; transform: translateY(-2px); }
        
        .gp-btn-wa { background: #25D366; color: white; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .gp-btn-wa:hover { background: #1ebc59; }

        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="blob b1"></div>
    <div class="blob b2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="header">
                <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                    <img src="assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo" class="logo">
                <?php else: ?>
                    <h1 style="margin:0;">⚡</h1>
                <?php endif; ?>
                <h2 class="title">Welcome Back</h2>
                <p class="sub">Login to continue</p>
            </div>

            <?php if ($error && empty($ban_reason)): ?>
                <div class="alert-err"><?= $error ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="input-box">
                    <i class="fa-regular fa-envelope icon"></i>
                    <input type="email" name="email" id="email" class="input-field" placeholder="Email Address" required value="<?= sanitize($email ?? '') ?>">
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock icon"></i>
                    <input type="password" name="password" id="password" class="input-field" placeholder="Password" required>
                </div>

                <div class="options">
                    <label class="chk">
                        <input type="checkbox" name="remember_me" checked> Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot">Forgot Password?</a>
                </div>

                <label class="bio-switch" id="bioArea">
                    <div class="bio-txt">
                        <i class="fa-solid fa-fingerprint"></i> Enable Fingerprint
                    </div>
                    <div class="chk">
                        <input type="checkbox" name="enable_bio" id="bioCheck">
                    </div>
                </label>

                <button type="submit" class="btn-main">Log In</button>
            </form>

            <?php if (function_exists('getGoogleLoginUrl') && $gUrl = getGoogleLoginUrl()): ?>
                <div class="divider" style="text-align:center; margin:15px 0; color:#aaa; font-size:0.8rem;">OR</div>
                <a href="<?= $gUrl ?>" class="btn-google">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18"> Continue with Google
                </a>
            <?php endif; ?>

            <p style="text-align:center; margin-top:20px; font-size:0.9rem; color:#64748b;">
                New user? <a href="register.php" class="forgot">Register</a>
            </p>
        </div>
    </div>

    <div id="bioPopup">
        <div class="bio-sheet">
            <div class="sheet-handle"></div>
            <div class="scan-circle" id="scanIcon" onclick="triggerAuth()">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h3 class="bio-h3" id="bioTitle">Biometric Login</h3>
            <p class="bio-p" id="bioDesc">Verify your identity to login</p>
            <button class="btn-close-bio" onclick="closePopup()">Use Password</button>
        </div>
    </div>

    <div id="verifyScreen">
        <div class="loader"></div>
        <h4 style="color:#1e293b;">Authenticating...</h4>
    </div>

    <div id="banPopup" class="glass-popup-overlay">
        <div class="glass-popup">
            <div class="gp-icon"><i class="fa-solid fa-ban"></i></div>
            <h2 class="gp-title">Account Suspended</h2>
            <p class="gp-text" id="banMsg">Your account has been restricted.</p>
            
            <a href="#" id="banWaBtn" target="_blank" class="gp-btn gp-btn-wa" style="display:none;">
                <i class="fa-brands fa-whatsapp"></i> Contact Support
            </a>
            
            <button class="gp-btn" onclick="document.getElementById('banPopup').style.display='none'">Understand</button>
        </div>
    </div>

    <script>
        <?php if(!empty($ban_reason)): ?>
            const reason = "<?= addslashes($ban_reason) ?>";
            const allowContact = <?= $ban_contact ?>;
            const adminWa = "<?= $GLOBALS['settings']['whatsapp_number'] ?? '' ?>";

            document.getElementById('banMsg').innerText = reason;
            
            if(allowContact == 1 && adminWa !== '') {
                const btn = document.getElementById('banWaBtn');
                btn.style.display = 'flex';
                btn.href = "https://wa.me/" + adminWa.replace(/[^0-9]/g, '');
            }
            
            document.getElementById('banPopup').style.display = 'flex';
        <?php endif; ?>

        document.addEventListener("DOMContentLoaded", () => {
            const savedCreds = localStorage.getItem('beast_bio_auth');
            const bioCookie = document.cookie.split(';').some((item) => item.trim().startsWith('bio_enabled='));

            if(savedCreds) { document.getElementById('bioCheck').checked = true; }

            if(savedCreds && bioCookie && window.PublicKeyCredential) {
                document.getElementById('bioPopup').style.display = 'flex';
                setTimeout(() => { triggerAuth(); }, 500);
            }
        });

        function closePopup() { document.getElementById('bioPopup').style.display = 'none'; }

        async function triggerAuth() {
            const icon = document.getElementById('scanIcon');
            const title = document.getElementById('bioTitle');
            
            icon.classList.add('active');
            title.innerText = "Scanning...";

            if (!window.PublicKeyCredential) { failAuth("Biometrics not supported."); return; }

            try {
                const challenge = new Uint8Array(32);
                window.crypto.getRandomValues(challenge);
                const publicKey = {
                    challenge: challenge,
                    rp: { name: "<?php echo $GLOBALS['settings']['site_name'] ?? 'App'; ?>" },
                    user: { id: new Uint8Array(16), name: "User", displayName: "User" },
                    pubKeyCredParams: [{ alg: -7, type: "public-key" }],
                    authenticatorSelection: { authenticatorAttachment: "platform", userVerification: "required" },
                    timeout: 60000
                };

                await navigator.credentials.create({ publicKey });

                icon.style.background = '#dcfce7';
                icon.style.color = '#16a34a';
                icon.innerHTML = '<i class="fa-solid fa-check"></i>';
                icon.classList.remove('active');
                title.innerText = "Verified!";
                
                setTimeout(() => { doLogin(); }, 500);

            } catch (e) {
                icon.classList.remove('active');
                if (e.name !== 'NotAllowedError') { failAuth("Not Recognized."); } 
                else { title.innerText = "Biometric Login"; }
            }
        }

        function failAuth(msg) {
            const title = document.getElementById('bioTitle');
            title.innerText = msg;
            title.style.color = '#ef4444';
            setTimeout(() => { title.style.color = '#1e293b'; title.innerText="Biometric Login"; }, 2000);
        }

        function doLogin() {
            document.getElementById('bioPopup').style.display = 'none';
            document.getElementById('verifyScreen').style.display = 'flex';

            const savedCreds = localStorage.getItem('beast_bio_auth');
            if (!savedCreds) { location.reload(); return; }

            try {
                const decoded = atob(savedCreds).split('|||');
                document.getElementById('email').value = decoded[0];
                document.getElementById('password').value = decoded[1];
                document.getElementById('bioCheck').checked = true; 
                setTimeout(() => { document.getElementById('loginForm').submit(); }, 500);
            } catch (e) {
                localStorage.removeItem('beast_bio_auth');
                location.reload();
            }
        }

        document.getElementById('loginForm').addEventListener('submit', (e) => {
            const u = document.getElementById('email').value;
            const p = document.getElementById('password').value;
            if (document.getElementById('bioCheck').checked) {
                if(u.trim() !== "" && p.trim() !== "") {
                    const encoded = btoa(u + '|||' + p);
                    localStorage.setItem('beast_bio_auth', encoded);
                }
            } else { localStorage.removeItem('beast_bio_auth'); }
        });
    </script>

</body>
</html>