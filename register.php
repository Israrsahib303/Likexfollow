<?php
// register.php - Premium Signup with WhatsApp Collection
// Beast9 Final: Consistent UI + Data Collection

ob_start(); // Buffering start for header redirects
session_start(); 

require_once __DIR__ . '/includes/helpers.php';
if (file_exists(__DIR__ . '/includes/google_config.php')) {
    require_once __DIR__ . '/includes/google_config.php';
}

if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$error = '';
$success = '';
$email = '';
$name = '';
$phone = ''; // Phone variable

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
    $phone = sanitize($_POST['phone']); // Capture Phone
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Captcha Answer from User
    $user_math_ans = isset($_POST['math_ans']) ? (int)$_POST['math_ans'] : 0;

    // --- 2. SECURITY & VALIDATION CHECKS ---
    if ($user_math_ans !== $math_solution) {
        $error = '⚠️ Incorrect Math Answer. Please try again.';
        // Reset Captcha on error
        $_SESSION['math_num1'] = rand(1, 9); 
        $_SESSION['math_num2'] = rand(1, 9);
    }
    elseif (!verifyCsrfToken($csrf_token)) {
        $error = 'Security check failed. Please refresh.';
    }
    elseif (preg_match('/(http|www|\.com|💳|\*|\$|RUB|BAM|link)/i', $name)) {
        $error = '⚠️ Spam detected in name. Please use your real name.';
    }
    // Check Empty Fields
    elseif (empty($name) || empty($email) || empty($phone) || empty($password) || empty($password_confirm)) {
        $error = 'Please fill in all fields, including WhatsApp number.';
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } 
    elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } 
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } 
    else {
        try {
            // Check if Email Exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                // --- 3. CREATE ACCOUNT ---
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(32)); 
                
                // Insert with Phone Number
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, is_admin, created_at, is_verified, verification_token) VALUES (?, ?, ?, ?, 0, NOW(), 1, ?)");
                
                if ($stmt->execute([$name, $email, $phone, $password_hash, $verification_token])) {
                    
                    // --- AUTO LOGIN ---
                    $new_user_id = $db->lastInsertId();
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['is_admin'] = 0; 
                    
                    // Cleanup Session
                    unset($_SESSION['math_num1']);
                    unset($_SESSION['math_num2']);

                    redirect(SITE_URL . '/index.php');
                    exit;

                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Register - <?php echo $GLOBALS['settings']['site_name'] ?? 'LikexFollow'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
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
            --radius-input: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background-color: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* --- ✨ SMOOTH REGISTER CARD --- */
        .auth-card {
            background: white;
            width: 100%;
            max-width: 420px; /* Slightly wider than login for more fields */
            padding: 30px;
            border-radius: var(--radius-box);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0,0,0,0.03);
            position: relative;
        }

        /* Header Area */
        .header { text-align: center; margin-bottom: 25px; }
        .logo { height: 40px; margin-bottom: 10px; object-fit: contain; }
        .brand-title { font-size: 1.4rem; font-weight: 800; color: var(--text-dark); margin: 0; letter-spacing: -0.5px; }
        .welcome-text { font-size: 0.9rem; color: var(--text-light); margin-top: 5px; font-weight: 500; }

        /* Form Groups */
        .form-group { position: relative; margin-bottom: 14px; }
        
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 1rem; transition: color 0.2s;
        }

        .form-input {
            width: 100%;
            padding: 12px 12px 12px 45px; 
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

        /* --- 🛡️ CAPTCHA BOX --- */
        .captcha-row {
            display: flex; gap: 10px; align-items: center; margin-bottom: 20px;
        }
        .math-display {
            background: #1e293b; color: #4ade80;
            padding: 10px 15px; border-radius: 12px;
            font-family: 'JetBrains Mono', monospace; font-size: 1.1rem;
            letter-spacing: 2px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.5);
            min-width: 80px; text-align: center;
        }
        .captcha-input {
            flex: 1; padding: 10px; text-align: center; font-weight: 700;
            border: 2px solid var(--border); border-radius: 12px;
            outline: none; color: var(--primary);
        }
        .captcha-input:focus { border-color: var(--primary); }

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
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary:hover { 
            background: var(--primary-hover); 
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        .divider {
            display: flex; align-items: center; color: #cbd5e1; 
            font-size: 0.75rem; margin: 20px 0; font-weight: 600; text-transform: uppercase;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .divider span { padding: 0 15px; color: #94a3b8; }

        .btn-google {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 10px;
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius-input);
            color: var(--text-dark); font-size: 0.9rem; font-weight: 600;
            text-decoration: none; transition: 0.2s;
        }
        .btn-google:hover { background: #f8fafc; border-color: #cbd5e1; }

        .card-footer {
            text-align: center; margin-top: 20px;
            font-size: 0.9rem; color: var(--text-light); font-weight: 500;
        }
        .card-footer a { color: var(--primary); font-weight: 700; text-decoration: none; }
        .card-footer a:hover { text-decoration: underline; }

        /* Error & Success Boxes */
        .status-box {
            padding: 10px; border-radius: 10px; font-size: 0.85rem; text-align: center;
            margin-bottom: 20px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

        /* --- 💡 SMART TOOLTIP CSS --- */
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 110%; left: 50%; transform: translateX(-50%);
            background: #1e293b; color: #fff;
            padding: 6px 12px; border-radius: 8px;
            font-size: 0.75rem; font-weight: 600;
            white-space: nowrap; opacity: 0; animation: tooltipFade 0.3s forwards;
            pointer-events: none; z-index: 10;
        }
        @keyframes tooltipFade { to { opacity: 1; transform: translateX(-50%) translateY(-5px); } }

    </style>
</head>
<body>

    <div class="auth-card">
        
        <div class="header">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo" class="logo">
            <?php else: ?>
                <h2 style="color:var(--primary);margin:0;">LikexFollow</h2>
            <?php endif; ?>
            <p class="welcome-text">Create your free account</p>
        </div>

        <?php if ($error): ?>
            <div class="status-box error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="status-box success"><i class="fa-solid fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="form-group" data-tooltip="Enter your real name">
                <i class="fa-regular fa-user input-icon"></i>
                <input type="text" name="name" class="form-input" placeholder="Full Name" value="<?php echo sanitize($name); ?>" required>
            </div>

            <div class="form-group" data-tooltip="We will send updates here">
                <i class="fa-regular fa-envelope input-icon"></i>
                <input type="email" name="email" class="form-input" placeholder="Email Address" value="<?php echo sanitize($email); ?>" required>
            </div>

            <div class="form-group" data-tooltip="For Order Updates (No Spam)">
                <i class="fa-brands fa-whatsapp input-icon" style="color:#25D366;font-weight:bold;"></i>
                <input type="text" name="phone" class="form-input" placeholder="WhatsApp Number (e.g. +92...)" value="<?php echo sanitize($phone); ?>" required>
            </div>

            <div class="form-group">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="password" class="form-input" placeholder="Password (Min 6 chars)" required>
            </div>

            <div class="form-group">
                <i class="fa-solid fa-shield-halved input-icon"></i>
                <input type="password" name="password_confirm" class="form-input" placeholder="Confirm Password" required>
            </div>

            <div class="captcha-row" data-tooltip="Prove you are human">
                <div class="math-display"><?php echo $num1; ?> + <?php echo $num2; ?></div>
                <input type="number" name="math_ans" class="captcha-input" placeholder="=" required>
            </div>

            <button type="submit" class="btn-primary">
                CREATE ACCOUNT <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <?php if (function_exists('getGoogleLoginUrl') && $gUrl = getGoogleLoginUrl()): ?>
            <div class="divider"><span>OR JOIN WITH</span></div>
            <a href="<?= $gUrl ?>" class="btn-google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="16"> Google
            </a>
        <?php endif; ?>

        <div class="card-footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>
