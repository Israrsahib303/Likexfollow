<?php
// Output Buffering ON (Prevents Header Errors)
ob_start();

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/security_headers.php';

if (file_exists(__DIR__ . '/includes/google_config.php')) {
    require_once __DIR__ . '/includes/google_config.php';
}

// Variables initialization
$error = '';
$ban_reason = ''; 
$ban_contact = 0; 
$ip_address = $_SERVER['REMOTE_ADDR'];

// --- 0. GOOGLE/SESSION BAN CHECK (Fix for Google Users) ---
// Case A: User is redirected from Google with ?banned=1
if (isset($_GET['banned']) && $_GET['banned'] == '1' && isset($_GET['email'])) {
    $b_email = $_GET['email']; // Email passed from google_callback
    $stmt = $db->prepare("SELECT ban_reason, ban_show_contact FROM users WHERE email = ? AND status = 'banned'");
    $stmt->execute([$b_email]);
    $banInfo = $stmt->fetch();
    
    if ($banInfo) {
        $ban_reason = !empty($banInfo['ban_reason']) ? $banInfo['ban_reason'] : "Your account has been suspended.";
        $ban_contact = $banInfo['ban_show_contact'] ?? 0;
        $error = "Account Suspended";
    }
}
// Case B: User has a session but is banned (e.g. forced logout)
elseif (isset($_SESSION['user_id'])) {
    $chk = $db->prepare("SELECT status, ban_reason, ban_show_contact FROM users WHERE id = ?");
    $chk->execute([$_SESSION['user_id']]);
    $uData = $chk->fetch();

    if ($uData && $uData['status'] === 'banned') {
        session_unset();
        session_destroy();
        
        $ban_reason = !empty($uData['ban_reason']) ? $uData['ban_reason'] : "Your account has been suspended.";
        $ban_contact = $uData['ban_show_contact'] ?? 0;
        $error = "Account Suspended";
    }
}

// --- 1. AUTO-LOGIN CHECK ---
if (empty($ban_reason) && !isLoggedIn() && isset($_COOKIE['remember_me'])) {
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
                    $ban_reason = !empty($user['ban_reason']) ? $user['ban_reason'] : "Your account has been suspended.";
                    $ban_contact = $user['ban_show_contact'] ?? 0;
                    $error = "Account Suspended";
                } 
                // SECURITY: Admin cannot auto-login via this page
                elseif ($user['is_admin'] == 1) {
                     setcookie('remember_me', '', time() - 3600, '/'); 
                }
                else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = 0; 
                    setcookie('remember_me', $token, time() + (86400 * 30), "/");
                    session_write_close();
                    if (!headers_sent()) header("Location: user/index.php");
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $attempts = getLoginAttempts($db, $ip_address);
    if ($attempts >= 5) {
        $error = "⛔ Too many failed attempts. Blocked for 15 mins.";
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $csrf_token = $_POST['csrf_token'] ?? '';
        $enable_bio = isset($_POST['enable_bio']); 
        $is_bio_login = isset($_POST['bio_auth_success']) && $_POST['bio_auth_success'] == '1';

        if (!$is_bio_login && !verifyCsrfToken($csrf_token)) {
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
                    }
                    // --- 🚫 BAN CHECK (Manual Login) ---
                    elseif (isset($user['status']) && $user['status'] === 'banned') {
                        $error = 'Account Suspended'; 
                        $ban_reason = !empty($user['ban_reason']) ? $user['ban_reason'] : "Your account has been suspended by the administrator.";
                        $ban_contact = isset($user['ban_show_contact']) ? $user['ban_show_contact'] : 0;
                        session_unset();
                        session_destroy();
                        
                    } else {
                        // User Login Success
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['is_admin'] = 0; 
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

                        session_write_close();
                        if (!headers_sent()) header("Location: user/index.php");
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
    <title>Login - <?php echo $GLOBALS['settings']['site_name'] ?? 'LikexFollow'; ?></title>
    
    <link rel="shortcut icon" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="icon" type="image/jpeg" sizes="32x32" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="icon" type="image/jpeg" sizes="192x192" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="apple-touch-icon" href="https://likexfollow.com/assets/img/favicon.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --radius-box: 24px;
            --radius-input: 14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* --- ✨ SMOOTH LOGIN CARD --- */
        .login-card {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 35px 30px;
            border-radius: var(--radius-box);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0,0,0,0.03);
            position: relative;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        /* Header Area */
        .header { text-align: center; margin-bottom: 30px; }
        .logo { height: 42px; margin-bottom: 12px; object-fit: contain; transition: transform 0.3s; }
        .logo:hover { transform: scale(1.05); }
        .brand-title { font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px; }
        .welcome-text { font-size: 0.9rem; color: var(--text-light); margin-top: 5px; font-weight: 500; }

        /* Form Groups */
        .form-group { position: relative; margin-bottom: 16px; }
        
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 1rem; transition: color 0.2s;
        }

        .toggle-password {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; cursor: pointer; font-size: 1rem; transition: color 0.2s;
        }
        .toggle-password:hover { color: var(--primary); }

        /* Smooth Inputs */
        .form-input {
            width: 100%;
            padding: 14px 45px; /* Comfortable padding */
            border: 2px solid var(--border);
            border-radius: var(--radius-input);
            font-size: 0.95rem; font-weight: 500; color: var(--text-dark);
            outline: none; background: #fff;
            transition: all 0.2s ease-in-out;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .form-input:focus + .input-icon { color: var(--primary); }

        /* Options Row */
        .options-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.85rem; margin-bottom: 20px;
        }
        
        .remember-label {
            display: flex; align-items: center; gap: 8px; color: var(--text-light);
            cursor: pointer; font-weight: 500;
        }
        .remember-label input {
            accent-color: var(--primary); width: 16px; height: 16px; cursor: pointer;
        }
        
        .forgot-link { color: var(--primary); text-decoration: none; font-weight: 600; transition: 0.2s; }
        .forgot-link:hover { text-decoration: underline; color: var(--primary-hover); }

        /* --- 🧬 THE PREMIUM EDGE (Biometric Strip) --- */
        .bio-container { position: relative; margin-bottom: 20px; }
        .bio-strip {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border: 1px dashed #c7d2fe;
            color: var(--primary);
            padding: 12px;
            border-radius: var(--radius-input);
            font-size: 0.9rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bio-strip:hover { 
            background: #e0e7ff; 
            border-color: var(--primary); 
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(79, 70, 229, 0.15);
        }
        .bio-strip i { font-size: 1.2rem; }

        /* --- 💡 SMART TOOLTIP CSS --- */
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 115%; left: 50%; transform: translateX(-50%);
            background: #1e293b; color: #fff;
            padding: 6px 12px; border-radius: 8px;
            font-size: 0.75rem; font-weight: 600;
            white-space: nowrap; opacity: 0; animation: tooltipFade 0.3s forwards;
            pointer-events: none; z-index: 10;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        [data-tooltip]:hover::before { /* Tiny Arrow */
            content: ''; position: absolute; bottom: 105%; left: 50%; transform: translateX(-50%);
            border: 5px solid transparent; border-top-color: #1e293b;
            opacity: 0; animation: tooltipFade 0.3s forwards;
        }
        @keyframes tooltipFade { to { opacity: 1; transform: translateX(-50%) translateY(-5px); } }

        /* Buttons */
        .btn-primary {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 14px;
            border: none;
            border-radius: var(--radius-input);
            font-weight: 700; font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover { 
            background: var(--primary-hover); 
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .divider {
            display: flex; align-items: center; color: #cbd5e1; 
            font-size: 0.8rem; margin: 25px 0; font-weight: 600; text-transform: uppercase;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .divider span { padding: 0 15px; color: #94a3b8; }

        .btn-google {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            width: 100%; padding: 12px;
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius-input);
            color: var(--text-dark); font-size: 0.95rem; font-weight: 600;
            text-decoration: none; transition: 0.2s;
        }
        .btn-google:hover { background: #f8fafc; border-color: #cbd5e1; }

        /* Footer */
        .card-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }
        .card-footer a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }

        /* Error Box */
        .error-msg {
            background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c;
            padding: 10px; border-radius: 10px; font-size: 0.85rem; text-align: center;
            margin-bottom: 20px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        /* --- CUSTOM GLASS POPUP (FOR BANNED USER) --- */
        .glass-popup-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 9999; animation: fadeIn 0.3s ease; }
        .glass-popup { background: rgba(255, 255, 255, 0.9); width: 90%; max-width: 350px; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .gp-icon { width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 15px; }
        .gp-title { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0 0 10px; }
        .gp-text { font-size: 0.95rem; color: #64748b; line-height: 1.5; margin-bottom: 25px; }
        
        .gp-btn { width: 100%; padding: 12px; background: #334155; color: white; font-weight: 700; border: none; border-radius: 12px; cursor: pointer; transition: 0.2s; }
        .gp-btn:hover { background: #1e293b; transform: translateY(-2px); }
        
        .gp-btn-wa { background: #25D366; color: white; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; border-radius: 12px; font-weight: 700; padding: 12px; transition: 0.2s; }
        .gp-btn-wa:hover { background: #1ebc59; transform: translateY(-2px); }

        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes zoomIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="header">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="LikexFollow" class="logo">
            <?php else: ?>
                <h2 class="brand-title"><?php echo $GLOBALS['settings']['site_name'] ?? 'LikexFollow'; ?></h2>
            <?php endif; ?>
            <p class="welcome-text">Welcome back! Please login to continue.</p>
        </div>

        <?php if ($error && empty($ban_reason)): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group" data-tooltip="Enter your registered email">
                <i class="fa-regular fa-envelope input-icon"></i>
                <input type="email" name="email" id="email" class="form-input" placeholder="Email Address" required value="<?= sanitize($email ?? '') ?>">
            </div>

            <div class="form-group" data-tooltip="Enter your secure password">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="password" id="password" class="form-input" placeholder="Password" required>
                <i class="fa-regular fa-eye toggle-password" onclick="togglePass()"></i>
            </div>

            <div class="options-row">
                <label class="remember-label" data-tooltip="Keeps you logged in for 30 days">
                    <input type="checkbox" name="remember_me" checked> Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <div class="bio-container" data-tooltip="⚡ 1-Click Login with Fingerprint">
                <div class="bio-strip" id="bioBtn" onclick="triggerAuth()">
                    <i class="fa-solid fa-fingerprint"></i> Login with Fingerprint
                </div>
            </div>

            <button type="submit" class="btn-primary" data-tooltip="Click to access your dashboard">LOGIN NOW</button>
        </form>

        <?php if (function_exists('getGoogleLoginUrl') && $gUrl = getGoogleLoginUrl()): ?>
            <div class="divider"><span>OR CONTINUE WITH</span></div>
            <a href="<?= $gUrl ?>" class="btn-google" data-tooltip="Fast login via Google account">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18"> Google
            </a>
        <?php endif; ?>

        <div class="card-footer">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>

    <div id="banPopup" class="glass-popup-overlay">
        <div class="glass-popup">
            <div class="gp-icon"><i class="fa-solid fa-ban"></i></div>
            <h2 class="gp-title">Account Suspended</h2>
            <p class="gp-text" id="banMsg">Your account has been restricted.</p>
            
            <a href="#" id="banWaBtn" target="_blank" class="gp-btn-wa" style="display:none;">
                <i class="fa-brands fa-whatsapp"></i> Contact Support
            </a>
            
            <button class="gp-btn" onclick="document.getElementById('banPopup').style.display='none'">Understand</button>
        </div>
    </div>

    <script>
        // --- HANDLE BAN POPUP ---
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

        // Toggle Password Visibility
        function togglePass() {
            const passInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Biometric Logic
        async function triggerAuth() {
            const btn = document.getElementById('bioBtn');
            const originalHTML = btn.innerHTML;

            // Simple check if browser supports WebAuthn
            if (!window.PublicKeyCredential) { 
                btn.style.borderColor = '#fca5a5';
                btn.style.color = '#ef4444';
                btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Not Supported on Device';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style = '';
                }, 2000);
                return; 
            }

            // Check if user has saved credentials locally
            const savedCreds = localStorage.getItem('beast_bio_auth');
            if(!savedCreds) {
                btn.innerHTML = '<i class="fa-solid fa-circle-info"></i> Login manually once to enable';
                setTimeout(() => btn.innerHTML = originalHTML, 3000);
                return;
            }

            try {
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Scanning...';
                
                // Simulate WebAuthn Challenge
                const challenge = new Uint8Array(32);
                window.crypto.getRandomValues(challenge);
                const publicKey = {
                    challenge: challenge,
                    rp: { name: "LikexFollow" },
                    user: { id: new Uint8Array(16), name: "User", displayName: "User" },
                    pubKeyCredParams: [{ alg: -7, type: "public-key" }],
                    authenticatorSelection: { authenticatorAttachment: "platform", userVerification: "required" },
                    timeout: 60000
                };

                await navigator.credentials.create({ publicKey });

                // Success Animation
                btn.style.background = '#dcfce7';
                btn.style.color = '#16a34a';
                btn.style.borderColor = '#bbf7d0';
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Identity Verified!';

                // Decode & Submit
                setTimeout(() => {
                    try {
                        const decoded = atob(savedCreds).split('|||');
                        document.getElementById('email').value = decoded[0];
                        document.getElementById('password').value = decoded[1];
                        
                        // Add hidden flag for backend
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'bio_auth_success';
                        hiddenInput.value = '1';
                        document.getElementById('loginForm').appendChild(hiddenInput);
                        
                        document.getElementById('loginForm').submit();
                    } catch(e) {
                        location.reload();
                    }
                }, 600);

            } catch (e) {
                // If user cancels or fails
                btn.style.background = '#fee2e2';
                btn.style.borderColor = '#fecaca';
                btn.style.color = '#b91c1c';
                btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Scan Failed / Cancelled';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style = '';
                }, 2000);
            }
        }

        // Save Credentials on Successful Manual Login (For next time)
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            const u = document.getElementById('email').value;
            const p = document.getElementById('password').value;
            // Only save if "Remember Me" is checked for privacy
            const remember = document.querySelector('input[name="remember_me"]').checked;
            
            if(u && p && remember) {
                // Simple encoding
                const encoded = btoa(u + '|||' + p);
                localStorage.setItem('beast_bio_auth', encoded);
            }
        });
    </script>

</body>
</html>
