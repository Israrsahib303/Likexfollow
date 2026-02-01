<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- 1. REQUIRED FILES ---
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php'; 
}

// --- 🔒 1.5 REAL-TIME BAN CHECK ---
if (isset($_SESSION['user_id'])) {
    // Check status directly from DB
    $chk_stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
    $chk_stmt->execute([$_SESSION['user_id']]);
    $uStatus = $chk_stmt->fetchColumn();

    if ($uStatus === 'banned') {
        // Destroy session and redirect to login for Popup
        session_unset();
        session_destroy();
        header("Location: ../login.php"); 
        exit;
    }
}

// --- 🚨 1.6 MANDATORY WHATSAPP COLLECTION TRAP (START) ---
// Logic: If user is logged in BUT phone number is empty -> Show Uncloseable Popup
$show_wa_trap = false;
$wa_error = '';

if (isset($_SESSION['user_id'])) {
    
    // A. Handle Form Submission (Jab user number daal kar button dabaye)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_wa_trap'])) {
        $phone_input = sanitize($_POST['wa_phone']);
        
        // Basic Validation (Length check)
        if (!empty($phone_input) && strlen($phone_input) >= 10) {
            // Update DB
            $upd = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $upd->execute([$phone_input, $_SESSION['user_id']]);
            
            // Reload Page to Unlock
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $wa_error = "Invalid Number! Please enter correct WhatsApp number.";
            $show_wa_trap = true; // Show popup again with error
        }
    }
    
    // B. Check Database if Phone is Missing
    if (!$show_wa_trap) {
        $stmt_ph = $db->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt_ph->execute([$_SESSION['user_id']]);
        $uPhone = $stmt_ph->fetchColumn();

        // Agar phone column khali hai ya NULL hai -> TRAP ACTIVE
        if (empty($uPhone)) {
            $show_wa_trap = true;
        }
    }
}
// ---------------------------------------------------------

// --- 2. USER BALANCE LOGIC ---
$user_id = $_SESSION['user_id'] ?? 0;
$user_balance = 0.00;

if ($user_id > 0) {
    if (function_exists('getUserBalance')) {
        $user_balance = getUserBalance($user_id);
    } else {
        $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_balance = $stmt->fetchColumn() ?? 0.00;
    }
}

// --- 3. SITE SETTINGS ---
$site_name = $GLOBALS['settings']['site_name'] ?? 'SubHub';
$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
$primary_color = '#2563eb'; 

// --- 4. CURRENCY SETUP ---
$curr_list = function_exists('getCurrencyList') ? getCurrencyList() : ['PKR' => ['rate'=>1, 'symbol'=>'Rs', 'flag'=>'🇵🇰', 'name'=>'Pakistani Rupee']];
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
if (!isset($curr_list[$curr_code])) $curr_code = 'PKR';

$curr_data = $curr_list[$curr_code];
$curr_flag = $curr_data['flag'];

// --- 5. UNIVERSAL AI SEO LOADER ---
// Ye code Database se wo SEO data uthayega jo AI ne Cron Job ke zariye likha hai
$current_page = basename($_SERVER['PHP_SELF']);

// Database Fetch
$stmt = $db->prepare("SELECT * FROM site_seo WHERE page_name = ?");
$stmt->execute([$current_page]);
$seo = $stmt->fetch();

// Agar AI ne abhi tak data nahi likha, to Default Settings use hongi
$meta_title = !empty($seo['meta_title']) ? $seo['meta_title'] : ($GLOBALS['settings']['seo_title'] ?? $site_name);
$meta_desc = !empty($seo['meta_description']) ? $seo['meta_description'] : ($GLOBALS['settings']['seo_desc'] ?? '');
$meta_keys = !empty($seo['meta_keywords']) ? $seo['meta_keywords'] : ($GLOBALS['settings']['seo_keywords'] ?? '');

// Open Graph URL
$og_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <meta name="theme-color" content="<?php echo $primary_color; ?>">
    <meta name="msapplication-navbutton-color" content="<?php echo $primary_color; ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title><?= htmlspecialchars($meta_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keys) ?>">
    
    <meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
    <meta property="og:url" content="<?= $og_url ?>">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/style.css">

    <?php if (!empty($GLOBALS['settings']['onesignal_app_id'])): ?>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      
      // Save ID to DB Helper
      function syncOneSignalId(playerId) {
          if(!playerId) return;
          console.log("Syncing ID to DB:", playerId);
          fetch('save_device.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'player_id=' + playerId
          }).then(res => res.text())
            .then(data => console.log("DB Response:", data))
            .catch(err => console.error("DB Sync Error:", err));
      }

      OneSignalDeferred.push(async function(OneSignal) {
        OneSignal.Debug.setLogLevel("trace");

        await OneSignal.init({
          appId: "<?php echo $GLOBALS['settings']['onesignal_app_id']; ?>",
          <?php if(!empty($GLOBALS['settings']['onesignal_safari_id'])): ?>
          safari_web_id: "<?php echo $GLOBALS['settings']['onesignal_safari_id']; ?>",
          <?php endif; ?>
          notifyButton: { enable: false },
          allowLocalhostAsSecureOrigin: true,
          serviceWorkerPath: "OneSignalSDKWorker.js", 
          serviceWorkerParam: { scope: "/" }
        });

        if (!OneSignal.User.PushSubscription.optedIn) {
            try { await OneSignal.Slidedown.promptPush(); } catch(e) { console.error("Prompt Error:", e); }
        } else {
            syncOneSignalId(OneSignal.User.PushSubscription.id);
        }

        OneSignal.User.PushSubscription.addEventListener("change", function(event) {
            if (event.current.optedIn) {
                syncOneSignalId(OneSignal.User.PushSubscription.id);
            }
        });
      });
    </script>
    <?php endif; ?>

    <style>
        :root {
            /* --- Theme Colors --- */
            --primary: <?= $GLOBALS['settings']['theme_primary'] ?? '#4f46e5' ?>;
            --secondary: <?= $GLOBALS['settings']['theme_secondary'] ?? '#7c3aed' ?>;
            --bg-body: <?= $GLOBALS['settings']['theme_bg'] ?? '#f8fafc' ?>;
            --card-bg: <?= $GLOBALS['settings']['theme_card_bg'] ?? '#ffffff' ?>;
            --text-main: <?= $GLOBALS['settings']['theme_text'] ?? '#0f172a' ?>;
            --radius: <?= $GLOBALS['settings']['theme_radius'] ?? '16' ?>px;
            --shadow-opacity: <?= $GLOBALS['settings']['theme_shadow'] ?? '0.05' ?>;
            
            /* --- Layout Dimensions --- */
            --nav-height: -10px;         
            --container-width: 700px;
        }

        /* --- CSS Reset --- */
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            font-size: 15px;
            line-height: 1.6;
            padding-top: calc(var(--nav-height) + 20px);
            min-height: 100vh;
            overflow-x: hidden; 
        }

        a { text-decoration: none; color: inherit; transition: 0.2s ease-in-out; }
        ul { list-style: none; }
        button { font-family: inherit; cursor: pointer; }
        img { max-width: 100%; display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .main-content-wrapper {
            animation: fadeIn 0.5s ease-out forwards;
            width: 100%;
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 20px; 
        }
        
        /* Apply Glassmorphism if enabled */
        <?php if(($GLOBALS['settings']['enable_glass'] ?? '1') == '1'): ?>
        .card, .modern-card, .tool-card {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        <?php endif; ?>
    
        /* Apply Radius & Shadow */
        .card, .btn, .form-control, .modern-card {
            border-radius: var(--radius) !important;
            box-shadow: 0 10px 30px -5px rgba(0,0,0, var(--shadow-opacity)) !important;
        }
    
        /* Custom CSS from Admin */
        <?= $GLOBALS['settings']['custom_css'] ?? '' ?>

        /* --- Custom Scrollbar --- */
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* --- 🚨 WHATSAPP TRAP CSS (FLYING ICONS + BLUR) --- */
        <?php if($show_wa_trap): ?>
        /* Locks the screen */
        body { overflow: hidden; height: 100vh; }
        
        .wa-trap-overlay {
            position: fixed; inset: 0; 
            background: rgba(255, 255, 255, 0.6); 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            z-index: 99999; 
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        
        .wa-trap-modal {
            background: white; width: 90%; max-width: 420px; padding: 40px 30px;
            border-radius: 24px; text-align: center; position: relative; z-index: 10;
            box-shadow: 0 30px 60px -15px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.05);
            animation: bounceIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .wa-float-container { position: absolute; inset: 0; pointer-events: none; overflow: hidden; z-index: 1; }
        
        .wa-fly-icon {
            position: absolute; color: #25D366; opacity: 0;
            font-size: 2rem; animation: floatUp linear infinite;
        }

        /* Flying Animation Keyframes */
        @keyframes floatUp { 
            0% { transform: translateY(110vh) rotate(0deg); opacity:0; } 
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity:0; } 
        }
        @keyframes bounceIn { from{ transform: scale(0.8); opacity:0; } to{ transform: scale(1); opacity:1; } }
        
        /* Modal Elements */
        .wt-icon { 
            width: 80px; height: 80px; background: #dcfce7; color: #16a34a; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-size: 40px; margin: 0 auto 20px; 
            box-shadow: 0 0 0 10px rgba(37, 211, 102, 0.1);
        }
        .wt-title { font-size: 1.6rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .wt-desc { color: #64748b; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5; }
        
        .wt-input {
            width: 100%; padding: 16px; border: 2px solid #cbd5e1; border-radius: 14px;
            font-size: 1.1rem; font-weight: 600; outline: none; transition: 0.2s;
            margin-bottom: 15px; background: #f8fafc; text-align: center; color: #334155;
        }
        .wt-input:focus { border-color: #25D366; background: white; box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.15); }
        .wt-input::placeholder { color: #94a3b8; font-weight: 500; }

        .wt-btn {
            width: 100%; padding: 16px; background: #25D366; color: white;
            font-weight: 700; border: none; border-radius: 14px; cursor: pointer;
            font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: 0.2s; box-shadow: 0 10px 20px -5px rgba(37, 211, 102, 0.3);
        }
        .wt-btn:hover { background: #1ebc59; transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(37, 211, 102, 0.4); }
        
        .wt-error { 
            background: #fef2f2; color: #ef4444; padding: 10px; border-radius: 10px;
            font-size: 0.85rem; margin-bottom: 15px; font-weight: 600; border: 1px solid #fee2e2;
        }
        <?php endif; ?>
    </style>
</head>
<body>

<?php if($show_wa_trap): ?>
    <div class="wa-trap-overlay">
        <div class="wa-float-container">
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 10%; animation-duration: 7s; font-size: 3rem;"></i>
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 25%; animation-duration: 11s; font-size: 2rem;"></i>
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 45%; animation-duration: 9s; font-size: 4rem;"></i>
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 70%; animation-duration: 6s; font-size: 2.5rem;"></i>
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 85%; animation-duration: 10s; font-size: 3.5rem;"></i>
            <i class="fa-brands fa-whatsapp wa-fly-icon" style="left: 60%; animation-duration: 13s; font-size: 1.5rem;"></i>
        </div>

        <div class="wa-trap-modal">
            <div class="wt-icon"><i class="fa-brands fa-whatsapp"></i></div>
            <h2 class="wt-title">One Last Step!</h2>
            <p class="wt-desc">
                Please enter your <b>WhatsApp Number</b> to activate your account. We use this for urgent updates only.
            </p>

            <?php if(!empty($wa_error)): ?>
                <div class="wt-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $wa_error ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="submit_wa_trap" value="1">
                
                <input type="text" name="wa_phone" class="wt-input" placeholder="e.g. +92 300 1234567" required autofocus autocomplete="tel">
                
                <button type="submit" class="wt-btn">
                    Save & Continue <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include '_nav.php'; ?>

<div class="main-content-wrapper">
