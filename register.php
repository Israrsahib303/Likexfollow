<?php
// register.php - Secured with Vertical Math CAPTCHA & Auto-Login
// Beast9 Final: Bot Protection + Instant Access

session_start(); 

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/google_config.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$error = '';
$success = '';
$email = '';
$name = '';

// --- 1. GENERATE MATH CHALLENGE ---
if (!isset($_SESSION['math_num1']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['math_num1'] = rand(1, 9); 
    $_SESSION['math_num2'] = rand(1, 9);
}
$num1 = $_SESSION['math_num1'];
$num2 = $_SESSION['math_num2'];
$math_solution = $num1 + $num2;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Captcha Answer from User
    $user_math_ans = isset($_POST['math_ans']) ? (int)$_POST['math_ans'] : 0;

    // --- 2. SECURITY CHECKS ---
    if ($user_math_ans !== $math_solution) {
        $error = '⚠️ Incorrect Math Answer. Please try again.';
        $_SESSION['math_num1'] = rand(1, 9);
        $_SESSION['math_num2'] = rand(1, 9);
    }
    elseif (!verifyCsrfToken($csrf_token)) {
        $error = 'Security check failed. Please refresh.';
    }
    elseif (preg_match('/(http|www|\.com|💳|\*|\$|RUB|BAM|link)/i', $name)) {
        $error = '⚠️ Spam detected in name. Please use your real name.';
    }
    // --- 3. STANDARD VALIDATIONS ---
    elseif (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                // --- 4. CREATE ACCOUNT ---
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(32)); 
                
                $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, is_admin, created_at, is_verified, verification_token) VALUES (?, ?, ?, 0, NOW(), 1, ?)");
                
                if ($stmt->execute([$name, $email, $password_hash, $verification_token])) {
                    
                    // --- AUTO LOGIN ---
                    $new_user_id = $db->lastInsertId();
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['is_admin'] = 0; 
                    
                    unset($_SESSION['math_num1']);
                    unset($_SESSION['math_num2']);

                    redirect(SITE_URL . '/index.php');
                    exit;

                } else {
                    $error = 'Failed to create account.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sign Up - <?php echo $GLOBALS['settings']['site_name'] ?? 'SubHub'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- 🎨 ULTRA PRIME THEME (Optimized) --- */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent-grad: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.6);
            --text-dark: #1e293b;
            --text-gray: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: #eef2ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
            position: relative;
        }

        /* --- OPTIMIZED BLOBS (No Lag) --- */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            /* Heavy blur removed, Opacity lowered for performance */
            opacity: 0.4;
            filter: blur(50px);
        }
        .blob-1 { top: -10%; left: -10%; width: 400px; height: 400px; background: #c7d2fe; }
        .blob-2 { bottom: -10%; right: -10%; width: 350px; height: 350px; background: #e0e7ff; }

        .auth-card {
            width: 100%;
            max-width: 400px; /* Limits width so it doesn't stretch ugly */
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px 25px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        .logo-area { text-align: center; margin-bottom: 25px; }
        .site-title { font-size: 1.6rem; font-weight: 800; background: var(--accent-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .input-group { position: relative; margin-bottom: 16px; }
        .input-field {
            width: 100%; padding: 14px 15px 14px 45px; 
            border: 2px solid #e2e8f0; border-radius: 14px; 
            background: rgba(255,255,255,0.8);
            font-size: 0.95rem; color: var(--text-dark);
            transition: 0.3s;
        }
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 1.1rem;
        }
        .input-field:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); outline: none; }
        .input-field:focus + .input-icon { color: var(--primary-dark); }

        /* --- 🛡️ FIXED PREMIUM CAPTCHA --- */
        .captcha-container {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            padding: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 6px -2px rgba(0,0,0,0.05);
        }
        
        /* The Math Box - Compact & Vertical */
        .math-box {
            background: #1e293b;
            color: #4ade80;
            width: 70px;
            flex-shrink: 0; /* Prevents squishing */
            padding: 8px 0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 1.1rem;
            line-height: 1.2;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
        }
        .math-line { width: 40px; height: 2px; background: #64748b; margin: 2px 0; }
        
        /* Input Area - Flexible */
        .captcha-input-wrap {
            flex-grow: 1;
            position: relative;
        }
        .captcha-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            display: block;
        }
        .captcha-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            outline: none;
            color: #334155;
            transition: 0.2s;
        }
        .captcha-input:focus { border-color: var(--primary); }

        /* Buttons */
        .btn-main {
            width: 100%; padding: 14px; background: var(--accent-grad); color: white;
            border: none; border-radius: 14px; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: 0.3s;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(99, 102, 241, 0.5); }
        
        .btn-google {
            margin-top: 15px; width: 100%; padding: 12px; background: #fff;
            color: #374151; border: 1px solid #e2e8f0; border-radius: 14px;
            font-weight: 600; text-decoration: none; display: flex;
            align-items: center; justify-content: center; gap: 10px;
            transition: 0.2s;
        }
        .btn-google:hover { background: #f8fafc; border-color: #cbd5e1; }

        .divider { display: flex; align-items: center; color: #94a3b8; font-size: 0.8rem; margin: 20px 0; font-weight: 600; text-transform: uppercase; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .divider span { padding: 0 10px; }

        .status-box { padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; font-weight: 500; }
        .error { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
        .success { background: #ecfdf5; color: #10b981; border: 1px solid #d1fae5; }
        
        .footer { text-align: center; margin-top: 20px; color: var(--text-gray); font-size: 0.9rem; }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 700; }

    </style>
</head>
<body>

    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="auth-card">
        
        <div class="logo-area">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo" style="height: 45px;">
            <?php else: ?>
                <h1 class="site-title"><?php echo sanitize($GLOBALS['settings']['site_name']); ?></h1>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="status-box success"><?php echo $success; ?></div>
        <?php elseif ($error): ?>
            <div class="status-box error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="input-group">
                <input type="text" name="name" class="input-field" placeholder="Full Name" value="<?php echo sanitize($name); ?>" required>
                <i class="fa-regular fa-user input-icon"></i>
            </div>

            <div class="input-group">
                <input type="email" name="email" class="input-field" placeholder="Email Address" value="<?php echo sanitize($email); ?>" required>
                <i class="fa-regular fa-envelope input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password" class="input-field" placeholder="Password (Min 6 chars)" required>
                <i class="fa-solid fa-lock input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" name="password_confirm" class="input-field" placeholder="Confirm Password" required>
                <i class="fa-solid fa-shield-halved input-icon"></i>
            </div>

            <div class="captcha-container">
                <div class="math-box">
                    <span><?php echo $num1; ?></span>
                    <span>+<?php echo $num2; ?></span>
                    <div class="math-line"></div>
                </div>
                
                <div class="captcha-input-wrap">
                    <span class="captcha-label">Security Check</span>
                    <input type="number" name="math_ans" class="captcha-input" placeholder="Sum?" required>
                </div>
            </div>

            <button type="submit" class="btn-main">
                Create Account <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <?php if (function_exists('getGoogleLoginUrl') && $gUrl = getGoogleLoginUrl()): ?>
            <div class="divider"><span>Or join with</span></div>
            <a href="<?= $gUrl ?>" class="btn-google">
                <i class="fab fa-google" style="color:#EA4335"></i> Continue with Google
            </a>
        <?php endif; ?>
        <?php endif; ?>

        <div class="footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>