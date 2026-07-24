<?php
// register.php - Premium Signup with WhatsApp Trap for Google Users
// Beast9 Final: Consistent UI + Data Collection + Google Interception

ob_start(); // Buffering start for header redirects
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php'; // Ensure DB connection is active

if (file_exists(__DIR__ . '/includes/google_config.php')) {
    require_once __DIR__ . '/includes/google_config.php';
}

// --- 0. AUTO-FIX DATABASE (Ensure phone column exists) ---
try {
    $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(50) NULL AFTER email");
} catch (Exception $e) { /* Column likely exists, ignore error */ }

// --- 1. GOOGLE SIGNUP LOGIC (Intercept New Google Users) ---
// If google_callback returned a "new_google_user" session flag
$show_whatsapp_popup = false;
if (isset($_SESSION['new_google_user_data'])) {
    $show_whatsapp_popup = true;
}

if (isLoggedIn() && !$show_whatsapp_popup) {
    redirect('user/index.php');
}

$error = '';
$success = '';
$email = '';
$name = '';
$phone = ''; 

// --- 2. GENERATE MATH CHALLENGE ---
if (!isset($_SESSION['math_num1']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['math_num1'] = rand(1, 9); 
    $_SESSION['math_num2'] = rand(1, 9);
}
$num1 = $_SESSION['math_num1'];
$num2 = $_SESSION['math_num2'];
$math_solution = $num1 + $num2;

// --- 3. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CASE A: FINALIZE GOOGLE SIGNUP (WhatsApp Submission)
    if (isset($_POST['google_final_step']) && isset($_SESSION['new_google_user_data'])) {
        $phone = sanitize($_POST['phone']);
        
        if (empty($phone)) {
            $error = 'Please enter your WhatsApp number to continue.';
            $show_whatsapp_popup = true;
        } else {
            // Retrieve Google Data
            $gData = $_SESSION['new_google_user_data'];
            $name = $gData['name'];
            $email = $gData['email'];
            $google_id = $gData['google_id'];
            
            // Random Password for Google Users
            $random_pass = bin2hex(random_bytes(8)); 
            $hash = password_hash($random_pass, PASSWORD_DEFAULT);
            
            try {
                // Insert New User WITH Phone
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, google_id, is_admin, role, created_at, is_verified) VALUES (?, ?, ?, ?, ?, 0, 'user', NOW(), 1)");
                if ($stmt->execute([$name, $email, $phone, $hash, $google_id])) {
                    
                    $new_user_id = $db->lastInsertId();
                    
                    // Set Login Session
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['is_admin'] = 0;
                    $_SESSION['role'] = 'user';
                    
                    // Clear Temp Data
                    unset($_SESSION['new_google_user_data']);
                    
                    redirect('user/index.php?welcome=1');
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
            } catch (Exception $e) {
                $error = "System Error: " . $e->getMessage();
            }
        }
    }
    // CASE B: STANDARD REGISTRATION
    else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']); 
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $csrf_token = $_POST['csrf_token'] ?? '';
        $user_math_ans = isset($_POST['math_ans']) ? (int)$_POST['math_ans'] : 0;

        // Security & Validation
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
                    $error = 'Email is already registered. Please Login.';
                } else {
                    // Create Account
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $verification_token = bin2hex(random_bytes(32)); 
                    
                    $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, is_admin, created_at, is_verified, verification_token) VALUES (?, ?, ?, ?, 0, NOW(), 1, ?)");
                    
                    if ($stmt->execute([$name, $email, $phone, $password_hash, $verification_token])) {
                        
                        $new_user_id = $db->lastInsertId();
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['email'] = $email;
                        $_SESSION['name'] = $name;
                        $_SESSION['is_admin'] = 0; 
                        
                        unset($_SESSION['math_num1']);
                        unset($_SESSION['math_num2']);

                        redirect('user/index.php');
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sign Up - <?php echo $GLOBALS['settings']['site_name'] ?? 'Account'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4F46E5; /* Purple */
            --primary-dark: #4338ca;
            --whatsapp-green: #25D366;
            --dark-text: #1e293b;
            --gray-text: #64748b;
            --border-color: #cbd5e1;
            --page-bg: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Manrope', sans-serif; }

        body {
            background-color: var(--page-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px; /* Reduced padding */
        }

        /* --- COMPACT CARD DESIGN --- */
        .register-card {
            background: white;
            width: 100%;
            max-width: 400px; /* Narrower */
            padding: 25px 20px; /* Reduced padding */
            border-radius: 16px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid white;
        }

        /* Header */
        .card-header { text-align: center; margin-bottom: 20px; }
        .logo-img { height: 40px; margin-bottom: 8px; object-fit: contain; }
        .title { font-size: 1.4rem; font-weight: 800; color: var(--dark-text); letter-spacing: -0.5px; }
        .subtitle { font-size: 0.85rem; color: var(--gray-text); font-weight: 500; }

        /* Inputs - COMPACT & HIGH CONTRAST */
        .input-group { position: relative; margin-bottom: 12px; /* Reduced gap */ }
        
        /* CIRCLE ICONS */
        .field-icon {
            position: absolute; 
            left: 8px; 
            top: 50%; 
            transform: translateY(-50%);
            width: 30px; height: 30px;
            background: var(--primary); /* Purple Circle */
            color: white; /* White Icon */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; 
            z-index: 2;
            pointer-events: none;
            box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3);
        }

        /* Special Exception for WhatsApp */
        .wa-field .field-icon { background: var(--whatsapp-green); box-shadow: 0 2px 5px rgba(37, 211, 102, 0.3); }

        .clean-input {
            width: 100%;
            height: 46px; /* Reduced height */
            padding: 0 15px 0 48px; /* Padding left for icon space */
            background: #fff;
            border: 2px solid #e2e8f0; 
            border-radius: 10px;
            font-size: 0.95rem; font-weight: 600; color: var(--dark-text);
            transition: all 0.2s ease;
            outline: none;
        }

        .clean-input::placeholder { color: #94a3b8; font-weight: 500; opacity: 1; }

        .clean-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        /* Math Captcha - Compact & Animated */
        .captcha-row {
            display: flex; gap: 8px; margin-bottom: 15px; 
            width: 100%;
        }
        .math-box {
            background: #f1f5f9; color: var(--dark-text);
            padding: 0 15px; border-radius: 10px;
            font-weight: 800; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #e2e8f0;
            white-space: nowrap; height: 46px;
        }
        
        /* Input Wrapper for validation icon */
        .math-input-wrap { position: relative; flex-grow: 1; }
        
        .captcha-input {
            width: 100%; text-align: center;
            border: 2px solid #e2e8f0; border-radius: 10px;
            height: 46px; font-weight: 700; font-size: 1rem;
            outline: none;
            padding-right: 30px; /* Space for validation icon */
            transition: all 0.3s ease;
        }
        .captcha-input:focus { border-color: var(--primary); }

        /* VALIDATION STYLES */
        .validation-icon {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%) scale(0);
            font-size: 1.1rem;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
        }

        /* Main Button */
        .btn-main {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 14px; /* Reduced */
            border: none;
            border-radius: 10px;
            font-size: 1rem; font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px -2px rgba(79, 70, 229, 0.4);
            transition: transform 0.2s;
        }
        .btn-main:active { transform: scale(0.98); }

        /* Divider */
        .divider {
            display: flex; align-items: center; margin: 15px 0; 
            color: #94a3b8; font-size: 0.75rem; font-weight: 700;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
        .divider span { padding: 0 10px; }

        /* Google Button */
        .btn-google {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px;
            background: white; border: 2px solid #e2e8f0;
            border-radius: 10px;
            color: var(--dark-text); font-weight: 700; font-size: 0.9rem;
            text-decoration: none; transition: 0.2s;
        }
        .btn-google:hover { background: #f8fafc; border-color: #cbd5e1; }

        /* Footer */
        .footer { margin-top: 20px; text-align: center; font-size: 0.85rem; color: var(--gray-text); font-weight: 500; }
        .footer a { color: var(--primary); text-decoration: none; font-weight: 700; }

        /* Alerts */
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }

        /* POPUP */
        .popup-overlay {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px);
            display: flex; align-items: center; justify-content: center; z-index: 100;
        }
        .popup-content {
            background: white; width: 90%; max-width: 350px; padding: 25px;
            border-radius: 20px; text-align: center;
        }

    </style>
</head>
<body>

    <div class="register-card">
        
        <div class="card-header">
            <?php if (!empty($GLOBALS['settings']['site_logo'])): ?>
                <img src="assets/img/<?php echo sanitize($GLOBALS['settings']['site_logo']); ?>" alt="Logo" class="logo-img">
            <?php else: ?>
                <h1 class="title"><?php echo $GLOBALS['settings']['site_name'] ?? 'LikexFollow'; ?></h1>
            <?php endif; ?>
            <p class="subtitle">Join our community in seconds</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= $success ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="input-group">
                <div class="field-icon"><i class="fa-solid fa-user"></i></div>
                <input type="text" name="name" id="inp-name" class="clean-input" placeholder="Enter your name..." value="<?php echo sanitize($name); ?>" required>
            </div>

            <div class="input-group">
                <div class="field-icon"><i class="fa-solid fa-envelope"></i></div>
                <input type="email" name="email" id="inp-email" class="clean-input" placeholder="Enter your email..." value="<?php echo sanitize($email); ?>" required>
            </div>

            <div class="input-group wa-field">
                <div class="field-icon"><i class="fa-brands fa-whatsapp"></i></div>
                <input type="text" name="phone" id="inp-phone" class="clean-input" placeholder="Enter WhatsApp number..." value="<?php echo sanitize($phone); ?>" required>
            </div>

            <div class="input-group">
                <div class="field-icon"><i class="fa-solid fa-lock"></i></div>
                <input type="password" name="password" id="inp-pass" class="clean-input" placeholder="Create password..." required>
            </div>

            <div class="input-group">
                <div class="field-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <input type="password" name="password_confirm" class="clean-input" placeholder="Confirm password..." required>
            </div>

            <div class="captcha-row">
                <div class="math-box"><?php echo $num1; ?> + <?php echo $num2; ?> = ?</div>
                <div class="math-input-wrap">
                    <input type="number" name="math_ans" id="inp-math" class="captcha-input" placeholder="Answer" required>
                    <i id="math-val-icon" class="fa-solid validation-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn-main">
                Create Account
            </button>
        </form>

        <?php if (function_exists('getGoogleLoginUrl') && $gUrl = getGoogleLoginUrl()): ?>
            <div class="divider"><span>OR CONTINUE WITH</span></div>
            <a href="<?= $gUrl ?>" class="btn-google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" alt="G"> 
                Google
            </a>
        <?php endif; ?>

        <div class="footer">
            Have an account? <a href="login.php">Log In</a>
        </div>
    </div>

    <?php if ($show_whatsapp_popup): ?>
    <div class="popup-overlay">
        <div class="popup-content">
            <div style="width:50px; height:50px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                <i class="fa-brands fa-whatsapp" style="font-size: 28px; color: #25D366;"></i>
            </div>
            <h2 style="margin-bottom:8px; font-size:1.3rem; color:#1e293b;">Final Step</h2>
            <p style="color:#64748b; margin-bottom:15px; font-size:0.9rem;">Confirm WhatsApp for order updates.</p>

            <form action="register.php" method="POST">
                <input type="hidden" name="google_final_step" value="1">
                <div class="input-group wa-field" style="text-align:left;">
                    <div class="field-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <input type="text" name="phone" class="clean-input" placeholder="+1 234 567 890" style="background:#f0fdf4; border-color:#25D366;" required autofocus>
                </div>
                <button type="submit" class="btn-main" style="background:#25D366; box-shadow:none; margin-top:5px;">
                    Confirm & Finish
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- 1. SMART TYPING ANIMATION ---
            const animConfig = [
                { id: 'inp-name',  text: 'Enter your name...' },
                { id: 'inp-email', text: 'Enter your email...' },
                { id: 'inp-phone', text: 'Enter WhatsApp number...' },
                { id: 'inp-pass',  text: 'Create password...' }
            ];

            let currentFieldIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            let timer = null;
            let activeElementId = null;

            document.addEventListener('focus', (e) => { activeElementId = e.target.id; }, true);
            document.addEventListener('blur', () => { activeElementId = null; }, true);

            document.addEventListener('input', (e) => {
                if(animConfig[currentFieldIndex] && e.target.id === animConfig[currentFieldIndex].id) {
                    clearTimeout(timer);
                    e.target.setAttribute('placeholder', '');
                    moveToNextField();
                }
            }, true);

            function moveToNextField() {
                let found = -1;
                for(let i = 0; i < animConfig.length; i++) {
                    const el = document.getElementById(animConfig[i].id);
                    if(el && el.value.length === 0 && el.id !== document.activeElement.id) {
                        found = i;
                        break;
                    }
                }
                if(found !== -1) {
                    currentFieldIndex = found;
                    charIndex = 0;
                    isDeleting = false;
                    typeWriter();
                } else {
                    clearTimeout(timer);
                }
            }

            function typeWriter() {
                const currentObj = animConfig[currentFieldIndex];
                if(!currentObj) return;

                const inputEl = document.getElementById(currentObj.id);
                if(!inputEl) return;
                if(document.activeElement === inputEl) return;
                if(inputEl.value.length > 0) { moveToNextField(); return; }

                const fullText = currentObj.text;
                let currentText = fullText.substring(0, charIndex);
                inputEl.setAttribute('placeholder', currentText + '|');

                let typeSpeed = 80;
                if (isDeleting) { charIndex--; typeSpeed = 40; } else { charIndex++; }

                if (!isDeleting && charIndex === fullText.length + 1) {
                    isDeleting = true; typeSpeed = 1200;
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false; typeSpeed = 300;
                }
                timer = setTimeout(typeWriter, typeSpeed);
            }
            moveToNextField();


            // --- 2. MATH VALIDATION LOGIC ---
            const mathInp = document.getElementById('inp-math');
            const mathIcon = document.getElementById('math-val-icon');
            const solution = <?php echo $math_solution; ?>; // PHP Value to JS

            mathInp.addEventListener('input', function() {
                const val = parseInt(this.value);
                
                if (val === solution) {
                    // CORRECT: Green Tick Animation
                    this.style.borderColor = '#25D366';
                    this.style.backgroundColor = '#f0fdf4';
                    mathIcon.className = 'fa-solid fa-circle-check validation-icon';
                    mathIcon.style.color = '#25D366';
                    mathIcon.style.transform = 'translateY(-50%) scale(1)';
                } 
                else if (this.value.length > 0) {
                    // WRONG: Red Alert
                    this.style.borderColor = '#ef4444';
                    this.style.backgroundColor = '#fef2f2';
                    mathIcon.className = 'fa-solid fa-circle-xmark validation-icon';
                    mathIcon.style.color = '#ef4444';
                    mathIcon.style.transform = 'translateY(-50%) scale(1)';
                } 
                else {
                    // EMPTY: Reset
                    this.style.borderColor = '#e2e8f0';
                    this.style.backgroundColor = '#fff';
                    mathIcon.style.transform = 'translateY(-50%) scale(0)';
                }
            });

        });
    </script>

</body>
</html>
