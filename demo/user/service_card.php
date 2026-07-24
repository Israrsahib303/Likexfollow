<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Fetch Service
$svc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM smm_services WHERE id = ?");
$stmt->execute([$svc_id]);
$service = $stmt->fetch();

if (!$service) die("Service not found");

// --- CURRENCY LOGIC ---
$curr_code = $_COOKIE['site_currency'] ?? 'PKR';
$curr_rate = 1;
$curr_sym = 'Rs';

if ($curr_code != 'PKR') {
    $curr_rate = getCurrencyRate($curr_code);
    $symbols = ['PKR'=>'Rs','USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','SAR'=>'﷼','AED'=>'د.إ'];
    $curr_sym = $symbols[$curr_code] ?? $curr_code;
}

$raw_name = $service['name'];
$category = $service['category'];
$base_rate = (float)$service['service_rate']; 
$final_rate = $base_rate * $curr_rate; 

// Formatted Rate
if (floor($final_rate) == $final_rate) {
    $rate_display = number_format($final_rate, 0); 
} else {
    $rate_display = number_format($final_rate, 2); 
}

// Calculate Fake "Old Price" for Sale Mode (20% higher)
$old_price_display = number_format($final_rate * 1.3, 0);

// Fetch WhatsApp (Fixed logic)
$db_wa = $GLOBALS['settings']['whatsapp_number'] ?? '';
$user_wa = !empty($db_wa) ? $db_wa : '+92 300 1234567'; 

// --- SMART LOGIC ---
function cleanServiceName($name) {
    $remove = [
        '/\[.*?\]/', '/\(.*?\)/', 
        '/Speed:.*?/', '/Start:.*?/', '/Refill:.*?/', 
        '/Non Drop/', '/Guaranteed/', '/No Refill/', '/R30/', '/R60/', 
        '/Instant/', '/Fast/', '/Slow/', '/HQ/', '/LQ/', '/Real/', '/Mixed/',
        '/\s+-\s+/', '/\s+\|\s+/'
    ];
    $clean = preg_replace($remove, ' ', $name);
    return trim(preg_replace('/\s+/', ' ', $clean));
}
$clean_name = cleanServiceName($raw_name);

// Quality Logic
$quality_text = "✅ 100% Guaranteed\n✅ Real & Active\n✅ Non-Drop Service\n✅ Fast Delivery"; 
$bad_keywords = ['no refill', 'norefill', 'cheap', 'bot'];
foreach ($bad_keywords as $bk) {
    if (stripos($raw_name, $bk) !== false || stripos($category, $bk) !== false) {
        $quality_text = "⚡ Fast Speed\n📉 Cheap Rate\n⚠️ No Refill\n🚀 Instant Start";
        break; 
    }
}

// Icon Logic
$icon_file = 'smm.png';
$brands = [
    'Instagram' => 'Instagram.png', 'TikTok' => 'TikTok.png', 'YouTube' => 'Youtube.png',
    'Facebook' => 'Facebook.png', 'Twitter' => 'Twitter.png', 'Spotify' => 'Spotify.png', 
    'Netflix' => 'net-flix-ultra-4k-screens-69126007908d3.jpeg', 'Snapchat' => 'Snapchat.png',
    'WhatsApp' => 'Whatsapp.png', 'Pubg' => 'Pubg.png', 'Canva' => 'canva-pro-69125f3ff04b5.jpeg',
    'Telegram' => 'Telegram.png', 'Linkedin' => 'Linkedin.png', 'Twitch' => 'Twitch.png'
];
foreach ($brands as $key => $file) {
    if (stripos($raw_name, $key) !== false || stripos($category, $key) !== false) {
        $icon_file = $file;
        break;
    }
}
$icon_path = "../assets/img/icons/" . $icon_file;

// Default Color
$default_color = '#4f46e5';
if(stripos($clean_name, 'Netflix')!==false) $default_color='#E50914';
if(stripos($clean_name, 'TikTok')!==false) $default_color='#000000';
if(stripos($clean_name, 'Instagram')!==false) $default_color='#E1306C';
if(stripos($clean_name, 'YouTube')!==false) $default_color='#FF0000';
if(stripos($clean_name, 'Snapchat')!==false) $default_color='#FFFC00';
if(stripos($clean_name, 'Spotify')!==false) $default_color='#1DB954';
if(stripos($clean_name, 'Whatsapp')!==false) $default_color='#25D366';
if(stripos($clean_name, 'Facebook')!==false) $default_color='#1877F2';
if(stripos($clean_name, 'Twitter')!==false) $default_color='#1DA1F2';
if(stripos($clean_name, 'Telegram')!==false) $default_color='#0088cc';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SMM Card Creator Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;600;800&family=Poppins:wght@400;500;700;900&family=Oswald:wght@700&family=Orbitron:wght@700&family=Playfair+Display:wght@700&family=Montserrat:wght@800&family=Righteous&family=Lobster&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root { --card-bg: <?= $default_color ?>; --card-text: #0f172a; --card-surface: #ffffff; }
        * { box-sizing: border-box; }
        
        body { 
            margin: 0; padding: 0; font-family: 'Inter', sans-serif; 
            background: #f1f5f9; height: 100vh; display: flex; overflow: hidden; 
        }

        /* --- LEFT PANEL (Controls) --- */
        .editor-panel { 
            width: 380px; background: #fff; height: 100%; padding: 20px; 
            overflow-y: auto; border-right: 1px solid #e2e8f0; z-index: 10; 
            display:flex; flex-direction:column; box-shadow: 5px 0 25px rgba(0,0,0,0.05);
            flex-shrink: 0;
        }
        
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-title { margin: 0; font-family: 'Poppins', sans-serif; font-weight: 800; color: #1e293b; font-size: 1.2rem; }
        
        .control-group { margin-bottom: 15px; }
        .control-label { font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 5px; display:block; }
        
        .form-control { 
            width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 10px; 
            font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: 0.2s;
        }
        .form-control:focus { border-color: var(--card-bg); outline: none; }

        /* THEME SLIDER */
        .theme-scroll-box {
            display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;
            scroll-behavior: smooth;
        }
        .theme-scroll-box::-webkit-scrollbar { height: 6px; }
        .theme-scroll-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        
        .theme-btn {
            flex: 0 0 auto;
            width: 80px; height: 60px;
            background: #f8fafc; border: 2px solid #e2e8f0;
            border-radius: 8px; cursor: pointer;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            font-size: 0.7rem; font-weight: 600; color: #64748b;
            transition: all 0.2s;
        }
        .theme-btn:hover { border-color: var(--card-bg); color: var(--card-bg); }
        .theme-btn.active { background: var(--card-bg); color: white; border-color: var(--card-bg); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .theme-btn i { font-size: 1.2rem; margin-bottom: 5px; }


        .color-grid { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 5px; }
        .color-btn { 
            width: 32px; height: 32px; border-radius: 50%; border: 2px solid #fff; 
            box-shadow: 0 0 0 1px #cbd5e1; cursor: pointer; transition: transform 0.2s; 
        }
        .color-btn.active { transform: scale(1.15); box-shadow: 0 0 0 2px #0f172a; }

        .toggle-row { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; background: #f8fafc; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .toggle-row input { width: 16px; height: 16px; accent-color: var(--card-bg); }
        .toggle-row label { font-size: 0.85rem; font-weight: 600; color: #334155; cursor: pointer; flex: 1; }

        /* --- RIGHT PREVIEW AREA --- */
        .preview-area { 
            flex: 1; display: flex; align-items: center; justify-content: center; 
            background-color: #e2e8f0; position: relative;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px; overflow: hidden;
            padding: 20px;
        }

        /* --- 📱 MOBILE RESPONSIVENESS --- */
        @media screen and (max-width: 900px) {
            body { flex-direction: column; overflow-y: auto; height: auto; }
            
            .editor-panel { 
                width: 100%; height: auto; border-right: none; 
                order: 2; 
                box-shadow: 0 -5px 25px rgba(0,0,0,0.05);
            }
            
            .preview-area { 
                width: 100%; height: 500px;
                order: 1; 
                padding: 10px;
            }

            #card-wrapper { transform: scale(0.85); }
        }
        
        /* --- 🎨 CARD BASE --- */
        #card-wrapper { transition: 0.3s; }

        #card-node {
            width: 320px; height: 500px; /* FIXED DIMENSIONS FOR EXPORT */
            background: var(--card-surface); 
            border-radius: 20px; position: relative; overflow: hidden;
            box-shadow: 0 40px 80px -20px rgba(0,0,0,0.4);
            display: flex; flex-direction: column; 
            transition: 0.3s;
            text-align: center;
        }

        /* --- ICON FIXES (Universal) --- */
        .svc-icon-box {
            display: flex; justify-content: center; align-items: center;
            overflow: hidden; z-index: 20; background: #fff;
            position: relative;
        }
        .svc-icon {
            width: 100% !important; height: 100% !important; 
            object-fit: contain !important; /* Ensures icon is never cut */
            display: block;
            padding: 5px; /* Safety padding */
        }

        /* --- COMMON ELEMENTS --- */
        .card-body { 
            flex: 1; 
            padding: 15px; 
            text-align: center; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            width: 100%; 
            overflow: hidden; 
        }
        
        .card-title {
            font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 800;
            color: var(--card-text); margin-bottom: 10px; line-height: 1.2;
            word-wrap: break-word; /* Prevent text overflow */
            z-index: 4; position: relative;
        }
        
        .price-box {
            display: flex; justify-content: center; align-items: center; gap: 10px;
            margin-bottom: 10px; position: relative; width: 100%; z-index: 4;
        }
        .price-val { font-family: 'Oswald'; font-size: 1.8rem; color: var(--card-text); line-height: 1; }
        
        .sale-badge {
            background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; 
            font-size: 0.6rem; font-weight: 800; transform: rotate(10deg); 
            position: absolute; top: -12px; right: 20px; display: none;
        }
        .old-price { text-decoration: line-through; color: #94a3b8; font-size: 0.9rem; display: none; }

        .features-list {
            font-size: 0.85rem; font-weight: 600; color: #475569;
            text-align: left; white-space: pre-line; line-height: 1.6;
            background: rgba(0,0,0,0.03); padding: 12px; border-radius: 8px;
            width: 100%; z-index: 4;
        }

        /* FIXED: FOOTER STICKINESS AND VISIBILITY */
        .card-footer {
            padding: 12px 15px;
            background: #1e293b; 
            color: white; 
            position: relative;
            display: flex; 
            align-items: center; 
            justify-content: center; /* Centered since QR removed */
            width: 100%; 
            margin-top: auto; 
            z-index: 100; /* High Z-Index ensures visibility */
            min-height: 55px; /* Fixed height prevents collapse */
            flex-shrink: 0; /* Prevents footer from being squeezed */
        }
        
        /* Updated WhatsApp Button Style */
        .wa-btn {
            background: #25D366; color: white; padding: 8px 25px; border-radius: 50px;
            font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .verified { display: none; } /* Hidden by default */

        /* =========================================
           30 UNIQUE THEMES CSS - ALL ICONS & FOOTER FIXED
           ========================================= */

        /* 1. Modern Minimal */
        .tmpl-1 .card-header { height: 130px; background: var(--card-bg); border-radius: 0 0 50% 50%; position: relative; width: 100%; flex-shrink: 0;}
        .tmpl-1 .svc-icon-box { width: 90px; height: 90px; border-radius: 50%; position: absolute; bottom: -45px; left: 50%; transform: translateX(-50%); box-shadow: 0 10px 20px rgba(0,0,0,0.1); z-index: 20; }
        .tmpl-1 .card-body { padding-top: 65px; } 

        /* 2. Corporate Card */
        .tmpl-2 .card-header { height: 90px; background: #f8fafc; border-bottom: 5px solid var(--card-bg); display:flex; align-items:center; justify-content:center; width: 100%; flex-shrink: 0;}
        .tmpl-2 .svc-icon-box { width: 70px; height: 70px; background: transparent; }
        .tmpl-2 .card-title { text-transform: uppercase; letter-spacing: 1px; margin-top: 15px; }

        /* 3. Dark Neon */
        .tmpl-3 { background: #0a0a0a !important; color: white; border: 2px solid var(--card-bg); }
        .tmpl-3 .card-header { height: 110px; background: linear-gradient(180deg, var(--card-bg), transparent); width: 100%; position: relative; flex-shrink: 0;}
        .tmpl-3 .svc-icon-box { width: 80px; height: 80px; border-radius: 15px; background: #000; border: 2px solid var(--card-bg); position: absolute; top: 60px; left: 50%; transform: translateX(-50%); z-index: 20; }
        .tmpl-3 .card-body { padding-top: 55px; }
        .tmpl-3 .card-title, .tmpl-3 .price-val { color: #fff !important; }
        .tmpl-3 .features-list { background: #1a1a1a; color: #ddd; border: 1px solid #333; }

        /* 4. Glassmorphism */
        .tmpl-4 { background: linear-gradient(135deg, var(--card-bg), #6366f1) !important; }
        .tmpl-4 .card-body { background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); margin: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37); z-index: 2; position: relative;}
        .tmpl-4 .svc-icon-box { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 10px; background: rgba(255,255,255,0.95); box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 20; display: flex !important; }
        .tmpl-4 .card-title, .tmpl-4 .price-val, .tmpl-4 .features-list { color: #fff !important; text-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        .tmpl-4 .card-header { height: 10px; opacity: 0; }
        .tmpl-4 .features-list { background: rgba(0,0,0,0.2); border: none; }

        /* 5. Luxury Gold */
        .tmpl-5 { background: #050505 !important; color: #d4af37; border: 3px double #d4af37; }
        .tmpl-5 .card-header { height: 100px; background: repeating-linear-gradient(45deg, #111, #111 10px, #222 10px, #222 20px); border-bottom: 2px solid #d4af37; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-5 .svc-icon-box { width: 70px; height: 70px; border-radius: 50%; border: 2px solid #d4af37; background: #000; }
        .tmpl-5 .card-title { font-family: 'Playfair Display'; color: #d4af37 !important; font-style: italic; }
        .tmpl-5 .price-val { color: #fff !important; }
        .tmpl-5 .features-list { color: #d4af37; border-left: 2px solid #d4af37; background: transparent; }
        .tmpl-5 .card-footer { background: #d4af37; } .tmpl-5 .wa-btn { background: #000; color: #d4af37; }

        /* 6. Bold Impact */
        .tmpl-6 .card-header { height: 160px; background: var(--card-bg); clip-path: polygon(0 0, 100% 0, 100% 70%, 0 100%); width: 100%; position: relative; flex-shrink: 0;}
        .tmpl-6 .svc-icon-box { position: absolute; top: 20px; right: 20px; width: 70px; height: 70px; background: #fff; border-radius: 12px; box-shadow: 5px 5px 0 rgba(0,0,0,0.2); z-index: 20; }
        .tmpl-6 .card-title { text-align: left; font-size: 1.5rem; margin-top: -30px; padding-left: 20px; font-weight: 900; text-transform: uppercase; color: #000; z-index: 2; position: relative; }
        
        /* 7. Soft Aesthetic */
        .tmpl-7 { background: #fff0f5 !important; }
        .tmpl-7 .card-header { height: 110px; background: #ffdee9; border-radius: 20px 20px 0 0; margin: 10px 10px 0 10px; width: calc(100% - 20px); position: relative; flex-shrink: 0; }
        .tmpl-7 .svc-icon-box { width: 85px; height: 85px; border-radius: 50%; background: #fff; border: 4px solid #ffdee9; position: absolute; top: 65px; left: 50%; transform: translateX(-50%); z-index: 20; }
        .tmpl-7 .card-body { padding-top: 60px; }
        .tmpl-7 .card-title { font-family: 'Lobster', cursive; font-size: 1.5rem; color: #555; }

        /* 8. Instagram Style */
        .tmpl-8 { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888, #8a3ab9) !important; padding: 6px; border-radius: 24px; }
        .tmpl-8 .card-header { background: #fff; height: 110px; display: flex; justify-content: center; align-items: center; border-radius: 18px 18px 0 0; width: 100%; flex-shrink: 0;}
        .tmpl-8 .card-body { background: #fff; width: 100%; border-radius: 0 0 18px 18px; }
        .tmpl-8 .svc-icon-box { width: 80px; height: 80px; border-radius: 50%; padding: 4px; border: 3px solid #fff; outline: 3px dashed #cc2366; }
        .tmpl-8 .card-footer { background: #fff; color: #000; border-radius: 0 0 18px 18px; border-top: 1px solid #eee; }
        .tmpl-8 .wa-btn { background: #cc2366; }

        /* 9. Outline Wireframe */
        .tmpl-9 { background: #fff; border: 3px solid #000; }
        .tmpl-9 .card-header { height: 100px; border-bottom: 3px solid #000; background: #f0f0f0; display: flex; justify-content: center; align-items: center; width: 100%; flex-shrink: 0;}
        .tmpl-9 .svc-icon-box { width: 65px; height: 65px; border: 3px solid #000; background: #fff; border-radius: 0; }
        .tmpl-9 .card-footer { background: #000; border-top: 3px solid #000; }

        /* 10. Gradient Wave */
        .tmpl-10 .card-header { height: 150px; background: linear-gradient(to bottom right, var(--card-bg), #a855f7); border-radius: 0 0 50% 0; width: 100%; position: relative; flex-shrink: 0;}
        .tmpl-10 .svc-icon-box { width: 75px; height: 75px; border-radius: 50%; position: absolute; top: 30px; left: 30px; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 20; }
        .tmpl-10 .card-title { text-align: right; padding-right: 20px; margin-top: -50px; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.3); position: relative; z-index: 5; }

        /* 11. Cyber Glitch */
        .tmpl-11 { background: #050505 !important; color: #0f0; border: 1px solid #0f0; }
        .tmpl-11 .card-header { height: 90px; background: repeating-linear-gradient(90deg, #000, #000 2px, #0f0 2px, #0f0 4px); opacity: 0.3; width: 100%; position: relative; flex-shrink: 0;}
        .tmpl-11 .svc-icon-box { position: absolute; top: 50px; left: 50%; transform: translateX(-50%); width: 80px; height: 80px; background: #000; border: 2px solid #0f0; box-shadow: 0 0 10px #0f0; z-index: 20;}
        .tmpl-11 .card-body { padding-top: 60px; }
        .tmpl-11 .card-title { font-family: 'Orbitron'; color: #0f0 !important; letter-spacing: 2px; }
        .tmpl-11 .price-val { color: #fff !important; text-shadow: 0 0 5px #0f0; }
        .tmpl-11 .features-list { color: #0f0; border: 1px dashed #0f0; background: transparent; }

        /* 12. Paper Card */
        .tmpl-12 { background: #fff; box-shadow: 12px 12px 0 #000; border: 2px solid #000; border-radius: 0 !important; }
        .tmpl-12 .card-header { height: 110px; background: var(--card-bg); border-bottom: 2px solid #000; display:flex; justify-content: center; align-items: center; width: 100%; flex-shrink: 0;}
        .tmpl-12 .svc-icon-box { width: 70px; height: 70px; background: #fff; border: 2px solid #000; border-radius: 0; }
        .tmpl-12 .card-footer { background: #fff; border-top: 2px solid #000; color: #000; }
        .tmpl-12 .wa-btn { background: #000; color: #fff; border-radius: 0; }

        /* 13. Circle Top */
        .tmpl-13 { overflow: visible !important; margin-top: 50px; height: 450px !important; }
        .tmpl-13 .card-header { height: 0; width: 100%; position: relative; }
        .tmpl-13 .svc-icon-box { width: 110px; height: 110px; border-radius: 50%; background: var(--card-bg); position: absolute; top: -60px; left: 50%; transform: translateX(-50%); border: 6px solid #fff; box-shadow: 0 5px 20px rgba(0,0,0,0.15); z-index: 20; }
        .tmpl-13 .card-body { padding-top: 70px; overflow: hidden; }

        /* 14. Split Vertical */
        .tmpl-14 { background: linear-gradient(to bottom, var(--card-bg) 45%, #fff 45%) !important; }
        .tmpl-14 .card-header { display: none; }
        .tmpl-14 .card-body { padding: 0; }
        .tmpl-14 .svc-icon-box { width: 110px; height: 110px; margin: 40px auto 15px; background: #fff; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 20; }
        .tmpl-14 .card-title { color: #fff !important; margin-bottom: 30px; padding: 0 10px; }
        .tmpl-14 .features-list { background: transparent; margin: 20px; }
        .tmpl-14 .price-box { margin-top: auto; }

        /* 15. The Stripe */
        .tmpl-15 .card-header { height: 160px; background: #f1f5f9; display: flex; justify-content: center; align-items: center; position: relative; width: 100%; flex-shrink: 0;}
        .tmpl-15 .card-header::after { content: ''; width: 100%; height: 25px; background: var(--card-bg); position: absolute; bottom: 25px; transform: skewY(-4deg); }
        .tmpl-15 .svc-icon-box { width: 90px; height: 90px; background: #fff; border-radius: 12px; z-index: 20; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        /* 16. Midnight Blue */
        .tmpl-16 { background: #0f172a !important; color: #fff; }
        .tmpl-16 .card-header { height: 110px; background: #1e293b; border-bottom: 1px solid #334155; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-16 .svc-icon-box { width: 70px; height: 70px; background: #334155; border-radius: 50%; }
        .tmpl-16 .card-title, .tmpl-16 .price-val { color: #fff !important; }
        .tmpl-16 .features-list { background: #1e293b; color: #94a3b8; }

        /* 17. Vibrant Pop */
        .tmpl-17 { background: #ffeb3b !important; }
        .tmpl-17 .card-header { height: 120px; background: #ff9800; border-radius: 0 0 100% 100% / 40px; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-17 .svc-icon-box { width: 80px; height: 80px; background: #fff; border-radius: 50%; border: 4px solid #ffeb3b; }
        .tmpl-17 .card-title { color: #d84315; font-weight: 900; font-size: 1.4rem; }

        /* 18. Badge Style */
        .tmpl-18 .card-header { height: 90px; background: var(--card-bg); width: 100%; position: relative; flex-shrink: 0;}
        .tmpl-18 .svc-icon-box { width: 80px; height: 100px; background: #fff; position: absolute; top: 20px; left: 30px; border-radius: 0 0 40px 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); z-index: 20; }
        .tmpl-18 .card-title { text-align: right; margin-top: 15px; padding-right: 20px; font-size: 1.2rem; }
        .tmpl-18 .card-body { padding-top: 30px; }

        /* 19. Neumorphism */
        .tmpl-19 { background: #e0e5ec !important; box-shadow: inset 5px 5px 10px #bcbec4, inset -5px -5px 10px #ffffff; }
        .tmpl-19 .card-header { height: 10px; opacity: 0; }
        .tmpl-19 .svc-icon-box { width: 100px; height: 100px; border-radius: 50%; background: #e0e5ec; box-shadow: 8px 8px 16px #bcbec4, -8px -8px 16px #ffffff; margin: 30px auto; display: flex !important; z-index: 20; }
        .tmpl-19 .features-list { background: transparent; box-shadow: inset 5px 5px 10px #bcbec4, inset -5px -5px 10px #ffffff; }

        /* 20. Comic Book */
        .tmpl-20 { border: 4px solid #000; background: #fff; }
        .tmpl-20 .card-header { height: 120px; background: radial-gradient(circle, var(--card-bg) 20%, transparent 20%) 0 0, radial-gradient(circle, var(--card-bg) 20%, transparent 20%) 10px 10px; background-size: 20px 20px; border-bottom: 4px solid #000; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-20 .svc-icon-box { width: 80px; height: 80px; background: #fff; border: 4px solid #000; border-radius: 50%; }
        .tmpl-20 .card-title { font-family: 'Anton', sans-serif; letter-spacing: 1px; font-size: 1.6rem; text-transform: uppercase; }

        /* 21. Pure Text */
        .tmpl-21 .card-header { height: 60px; background: transparent; position: relative; width: 100%; flex-shrink: 0;}
        .tmpl-21 .svc-icon-box { display: flex !important; width: 50px; height: 50px; position: absolute; right: 20px; top: 10px; background: transparent; z-index: 20; }
        .tmpl-21 .card-title { font-size: 2.2rem; font-weight: 900; line-height: 1; text-align: center; color: var(--card-bg); margin-top: 20px; }
        .tmpl-21 .card-body { align-items: center; text-align: center; }

        /* 22. Side Bar (FIXED) */
        .tmpl-22 { flex-direction: row !important; }
        .tmpl-22 .card-header { width: 90px; height: 100%; background: var(--card-bg); display: flex; flex-direction: column; justify-content: center; align-items: center; flex-shrink: 0; }
        .tmpl-22 .svc-icon-box { width: 60px; height: 60px; background: #fff; border-radius: 50%; }
        .tmpl-22 .card-body { text-align: left; padding: 15px; width: calc(100% - 90px) !important; padding-bottom: 60px; }
        /* Footer fixed to bottom right */
        .tmpl-22 .card-footer { position: absolute; bottom: 0; right: 0; width: calc(100% - 90px); background: #1e293b; z-index: 200; } 

        /* 23. Overlay */
        .tmpl-23 { background: #222 !important; }
        .tmpl-23 .card-header { height: 280px; background: var(--card-bg); opacity: 0.9; clip-path: circle(80% at 50% 0); position: absolute; width: 100%; top: 0; }
        .tmpl-23 .svc-icon-box { position: relative; width: 90px; height: 90px; background: #fff; border-radius: 50%; margin: 60px auto 20px; z-index: 20; }
        .tmpl-23 .card-body { z-index: 5; color: #fff; padding-top: 10px; }
        .tmpl-23 .card-title, .tmpl-23 .price-val { color: #fff !important; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .tmpl-23 .features-list { background: rgba(255,255,255,0.1); color: #ddd; }

        /* 24. Hexagon */
        .tmpl-24 .card-header { height: 130px; background: #333; clip-path: polygon(50% 100%, 100% 80%, 100% 0, 0 0, 0 80%); display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-24 .svc-icon-box { width: 80px; height: 80px; clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%); background: var(--card-bg); padding: 5px; }
        .tmpl-24 .svc-icon { filter: brightness(0) invert(1); } 

        /* 25. Elegant Serif */
        .tmpl-25 { background: #fdfbf7 !important; border: 1px solid #e2d9c8; }
        .tmpl-25 .card-header { height: 100px; border-bottom: 1px solid #e2d9c8; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-25 .svc-icon-box { width: 60px; height: 60px; background: transparent; }
        .tmpl-25 .card-title { font-family: 'Playfair Display'; color: #5a4a42; font-size: 1.5rem; }
        
        /* 26. Gamer */
        .tmpl-26 { background: #2b1055 !important; color: #fff; }
        .tmpl-26 .card-header { height: 120px; background: #7597de; clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%); display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-26 .svc-icon-box { width: 70px; height: 70px; background: #fff; border-radius: 10px; transform: rotate(45deg); display:flex; justify-content:center; align-items:center; border: 3px solid #7597de; overflow: visible; z-index: 20; }
        .tmpl-26 .svc-icon { transform: rotate(-45deg); width: 80% !important; height: 80% !important; }
        .tmpl-26 .card-title { color: #fff !important; margin-top: 30px; font-weight: 800; }
        .tmpl-26 .features-list { background: rgba(0,0,0,0.3); color: #bbb; border: 1px solid rgba(255,255,255,0.1); }
        
        /* 27. Spotlight */
        .tmpl-27 .card-header { height: 100%; position: absolute; width: 100%; background: radial-gradient(circle at top, var(--card-bg), transparent 65%); z-index: 0; }
        .tmpl-27 .svc-icon-box { position: relative; z-index: 20; width: 90px; height: 90px; background: #fff; border-radius: 50%; margin: 30px auto; box-shadow: 0 0 30px var(--card-bg); }
        .tmpl-27 .card-body { position: relative; z-index: 5; }
        
        /* 28. Dashed */
        .tmpl-28 { border: 2px dashed #999; background: #fff; }
        .tmpl-28 .card-header { height: 110px; background: #f0f0f0; border-bottom: 2px dashed #999; display:flex; justify-content:center; align-items:center; width: 100%; flex-shrink: 0;}
        .tmpl-28 .svc-icon-box { width: 70px; height: 70px; border: 2px dashed var(--card-bg); border-radius: 50%; background: #fff; padding: 8px; }
        
        /* 29. App Icon Focus */
        .tmpl-29 { background: var(--card-bg) !important; }
        .tmpl-29 .card-header { height: 50px; width: 100%; flex-shrink: 0;}
        .tmpl-29 .svc-icon-box { width: 130px; height: 130px; background: #fff; border-radius: 25px; margin: 0 auto; box-shadow: 0 15px 35px rgba(0,0,0,0.2); z-index: 20; }
        .tmpl-29 .card-body { background: #fff; margin: 25px; border-radius: 15px; flex: 1; padding-top: 50px; width: auto; z-index: 5; }
        .tmpl-29 .card-title { margin-top: -20px; }
        
        /* 30. Professional Blue */
        .tmpl-30 { background: #eeffff !important; border-top: 10px solid #00aabb; }
        .tmpl-30 .card-header { display: flex; justify-content: space-between; padding: 20px; align-items: center; width: 100%; height: 80px; flex-shrink: 0;}
        .tmpl-30 .svc-icon-box { width: 50px; height: 50px; background: transparent; }
        .tmpl-30 .verified { display: block; background: #00aabb; color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; }
        .tmpl-30 .card-title { text-align: left; padding-left: 20px; color: #005566; }
        .tmpl-30 .features-list { background: #ccffff; color: #004455; }
        .tmpl-30 .price-val { color: #00aabb; }

    </style>
</head>
<body>

    <div class="editor-panel">
        <div class="panel-header">
            <h3 class="panel-title">🎨 Card Creator</h3>
            <a href="smm_order.php" style="text-decoration:none; color:#64748b; font-size:0.9rem;">✕ Close</a>
        </div>

        <div class="control-group">
            <label class="control-label">Select Theme</label>
            <div class="theme-scroll-box">
                <div class="theme-btn active" onclick="setTheme('tmpl-1', this)"><i class="fa-solid fa-layer-group"></i> 1</div>
                <div class="theme-btn" onclick="setTheme('tmpl-2', this)"><i class="fa-solid fa-briefcase"></i> 2</div>
                <div class="theme-btn" onclick="setTheme('tmpl-3', this)"><i class="fa-solid fa-bolt"></i> 3</div>
                <div class="theme-btn" onclick="setTheme('tmpl-4', this)"><i class="fa-solid fa-cube"></i> 4</div>
                <div class="theme-btn" onclick="setTheme('tmpl-5', this)"><i class="fa-solid fa-gem"></i> 5</div>
                <div class="theme-btn" onclick="setTheme('tmpl-6', this)"><i class="fa-solid fa-explosion"></i> 6</div>
                <div class="theme-btn" onclick="setTheme('tmpl-7', this)"><i class="fa-solid fa-heart"></i> 7</div>
                <div class="theme-btn" onclick="setTheme('tmpl-8', this)"><i class="fa-brands fa-instagram"></i> 8</div>
                <div class="theme-btn" onclick="setTheme('tmpl-9', this)"><i class="fa-regular fa-square"></i> 9</div>
                <div class="theme-btn" onclick="setTheme('tmpl-10', this)"><i class="fa-solid fa-wave-square"></i> 10</div>
                <div class="theme-btn" onclick="setTheme('tmpl-11', this)"><i class="fa-solid fa-bug"></i> 11</div>
                <div class="theme-btn" onclick="setTheme('tmpl-12', this)"><i class="fa-solid fa-note-sticky"></i> 12</div>
                <div class="theme-btn" onclick="setTheme('tmpl-13', this)"><i class="fa-solid fa-circle-notch"></i> 13</div>
                <div class="theme-btn" onclick="setTheme('tmpl-14', this)"><i class="fa-solid fa-table-columns"></i> 14</div>
                <div class="theme-btn" onclick="setTheme('tmpl-15', this)"><i class="fa-solid fa-slash"></i> 15</div>
                <div class="theme-btn" onclick="setTheme('tmpl-16', this)"><i class="fa-solid fa-moon"></i> 16</div>
                <div class="theme-btn" onclick="setTheme('tmpl-17', this)"><i class="fa-solid fa-sun"></i> 17</div>
                <div class="theme-btn" onclick="setTheme('tmpl-18', this)"><i class="fa-solid fa-certificate"></i> 18</div>
                <div class="theme-btn" onclick="setTheme('tmpl-19', this)"><i class="fa-solid fa-circle"></i> 19</div>
                <div class="theme-btn" onclick="setTheme('tmpl-20', this)"><i class="fa-solid fa-comment-dots"></i> 20</div>
                <div class="theme-btn" onclick="setTheme('tmpl-21', this)"><i class="fa-solid fa-font"></i> 21</div>
                <div class="theme-btn" onclick="setTheme('tmpl-22', this)"><i class="fa-solid fa-arrow-right"></i> 22</div>
                <div class="theme-btn" onclick="setTheme('tmpl-23', this)"><i class="fa-solid fa-image"></i> 23</div>
                <div class="theme-btn" onclick="setTheme('tmpl-24', this)"><i class="fa-solid fa-vector-square"></i> 24</div>
                <div class="theme-btn" onclick="setTheme('tmpl-25', this)"><i class="fa-solid fa-pen-nib"></i> 25</div>
                <div class="theme-btn" onclick="setTheme('tmpl-26', this)"><i class="fa-solid fa-gamepad"></i> 26</div>
                <div class="theme-btn" onclick="setTheme('tmpl-27', this)"><i class="fa-solid fa-lightbulb"></i> 27</div>
                <div class="theme-btn" onclick="setTheme('tmpl-28', this)"><i class="fa-solid fa-scissors"></i> 28</div>
                <div class="theme-btn" onclick="setTheme('tmpl-29', this)"><i class="fa-solid fa-mobile-screen"></i> 29</div>
                <div class="theme-btn" onclick="setTheme('tmpl-30', this)"><i class="fa-solid fa-user-tie"></i> 30</div>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Service Name</label>
            <input type="text" id="inp-name" class="form-control" value="<?= htmlspecialchars($clean_name) ?>" oninput="updateCard()">
        </div>

        <div class="control-group">
            <label class="control-label">WhatsApp Number</label>
            <input type="text" id="inp-wa" class="form-control" value="<?= $user_wa ?>" oninput="updateCard()">
        </div>

        <div class="toggle-row">
            <input type="checkbox" id="chk-sale" onchange="toggleSale()">
            <label for="chk-sale">Show Discount / Sale</label>
        </div>

        <div class="control-group" id="sale-inputs" style="display:none;">
            <label class="control-label">Original Price</label>
            <input type="text" id="inp-old" class="form-control" value="<?= $old_price_display ?>" oninput="updateCard()">
        </div>

        <div class="control-group">
            <label class="control-label">Current Price (<?= $curr_sym ?>)</label>
            <input type="text" id="inp-price" class="form-control" value="<?= $rate_display ?>" oninput="updateCard()" style="font-weight:bold;">
        </div>

        <div class="control-group">
            <label class="control-label">Features</label>
            <textarea id="inp-feat" class="form-control" rows="4" oninput="updateCard()"><?= $quality_text ?></textarea>
        </div>

        <div class="control-group">
            <label class="control-label">Accent Color</label>
            <div class="color-grid">
                <div class="color-btn active" style="background:<?=$default_color?>" onclick="setColor('<?=$default_color?>', this)"></div>
                <div class="color-btn" style="background:#ef4444" onclick="setColor('#ef4444', this)"></div>
                <div class="color-btn" style="background:#f59e0b" onclick="setColor('#f59e0b', this)"></div>
                <div class="color-btn" style="background:#10b981" onclick="setColor('#10b981', this)"></div>
                <div class="color-btn" style="background:#06b6d4" onclick="setColor('#06b6d4', this)"></div>
                <div class="color-btn" style="background:#8b5cf6" onclick="setColor('#8b5cf6', this)"></div>
                <div class="color-btn" style="background:#ec4899" onclick="setColor('#ec4899', this)"></div>
                <div class="color-btn" style="background:#111827" onclick="setColor('#111827', this)"></div>
            </div>
        </div>

        <button class="form-control" style="background:var(--card-bg); color:#fff; border:none; padding:15px; margin-top:auto; font-weight:800; cursor:pointer;" onclick="downloadCard()">
            <i class="fa-solid fa-download"></i> Download HD Image
        </button>
    </div>

    <div class="preview-area">
        <div id="card-wrapper">
            <div id="card-node" class="tmpl-1">
                
                <div class="card-header">
                    <div class="verified"><i class="fa-solid fa-check"></i> PRO</div>
                    <div class="svc-icon-box">
                        <img src="<?= $icon_path ?>" class="svc-icon" crossorigin="anonymous">
                    </div>
                </div>

                <div class="card-body">
                    <div class="card-title" id="out-name"><?= htmlspecialchars($clean_name) ?></div>

                    <div class="price-box">
                        <span class="old-price" id="out-old"></span>
                        <span class="price-val"><?= $curr_sym ?> <span id="out-price"><?= $rate_display ?></span></span>
                        <div class="sale-badge" id="out-badge">SALE</div>
                    </div>

                    <div class="features-list" id="out-feat">
                        <?= nl2br($quality_text) ?>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="wa-btn">
                        <i class="fa-brands fa-whatsapp"></i> <span id="wa-display"><?= $user_wa ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            updateCard();
            adjustScale(); 
        });

        window.addEventListener('resize', adjustScale);

        // 0. AUTO SCALE FOR MOBILE
        function adjustScale() {
            const wrapper = document.getElementById('card-wrapper');
            const preview = document.querySelector('.preview-area');
            
            if (window.innerWidth < 900) {
                const availableWidth = preview.clientWidth - 20; 
                const scale = Math.min(availableWidth / 320, 1);
                wrapper.style.transform = `scale(${scale})`;
            } else {
                wrapper.style.transform = `scale(1)`;
            }
        }

        // 1. UPDATE CARD
        function updateCard() {
            document.getElementById('out-name').innerText = document.getElementById('inp-name').value;
            document.getElementById('out-price').innerText = document.getElementById('inp-price').value;
            
            // Update WA Display
            document.getElementById('wa-display').innerText = document.getElementById('inp-wa').value;

            let feat = document.getElementById('inp-feat').value;
            document.getElementById('out-feat').innerHTML = feat.replace(/\n/g, "<br>");
            
            if(document.getElementById('chk-sale').checked) {
                document.getElementById('out-old').innerText = '<?= $curr_sym ?> ' + document.getElementById('inp-old').value;
            }
        }

        // 2. TEMPLATES
        function setTheme(tmplName, btn) {
            document.getElementById('card-node').className = tmplName;
            
            // Toggle active state
            document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // 3. COLOR
        function setColor(color, btn) {
            document.documentElement.style.setProperty('--card-bg', color);
            document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // 4. SALE TOGGLE
        function toggleSale() {
            let on = document.getElementById('chk-sale').checked;
            document.getElementById('sale-inputs').style.display = on ? 'block' : 'none';
            document.getElementById('out-badge').style.display = on ? 'block' : 'none';
            document.getElementById('out-old').style.display = on ? 'inline' : 'none';
            updateCard();
        }

        // 5. DOWNLOAD
        function downloadCard() {
            const btn = document.querySelector('button[onclick="downloadCard()"]');
            const oldText = btn.innerHTML;
            btn.innerHTML = '⚙️ Generating...';
            
            const wrapper = document.getElementById('card-wrapper');
            const oldTransform = wrapper.style.transform;
            wrapper.style.transform = 'scale(1)';

            const element = document.getElementById('card-node');
            
            html2canvas(element, { 
                scale: 3, 
                useCORS: true, 
                allowTaint: true, 
                backgroundColor:null 
            }).then(canvas => {
                let a = document.createElement('a');
                a.download = 'Service-Card-' + Date.now() + '.png';
                a.href = canvas.toDataURL('image/png');
                a.click();
                btn.innerHTML = oldText;
                wrapper.style.transform = oldTransform;
            }).catch(err => {
                alert("Download Failed: " + err);
                btn.innerHTML = oldText;
                wrapper.style.transform = oldTransform;
            });
        }
    </script>
</body>
</html>