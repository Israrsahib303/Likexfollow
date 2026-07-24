<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Fetch Pricing
|--------------------------------------------------------------------------
*/

$child_price = 10;
$rental_price = 25;

try {
    $stmt_set = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt_set->fetch(PDO::FETCH_ASSOC);

    if ($settings) {
        $child_price = $settings['child_panel_price'] ?? 10;
        $rental_price = $settings['rental_panel_price'] ?? 25;
    }
} catch (Exception $e) {
}

/*
|--------------------------------------------------------------------------
| Fetch User Panels
|--------------------------------------------------------------------------
*/

$my_panels = [];

try {
    $stmt_panels = $db->prepare("
        SELECT *
        FROM panel_rentals
        WHERE user_id = ?
        ORDER BY id DESC
    ");

    $stmt_panels->execute([$user_id]);
    $my_panels = $stmt_panels->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
}

// include '_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <meta name="theme-color" content="#f5f5f7">

    <title>Rent A Panel - LikexFollow</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ================================================================
           ROOT DESIGN TOKENS
        ================================================================= */

        :root {
            --page-bg: #f7f5ff;
            --page-bg-soft: #fbfaff;

            --surface: rgba(255, 255, 255, 0.86);
            --surface-strong: #ffffff;
            --surface-soft: #faf8ff;
            --surface-purple: #f5efff;

            --purple-25: #fdfbff;
            --purple-50: #f5f9ff;
            --purple-100: #f3e8ff;
            --purple-150: #eee2ff;
            --purple-200: #e9d5ff;
            --purple-300: #d8b4fe;
            --purple-400: #c084fc;
            --purple-500: #a855f7;
            --purple-600: #9333ea;
            --purple-700: #7e22ce;
            --purple-800: #6b21a8;
            --purple-900: #581c87;

            --primary: #0071e3;
            --primary-light: #9f67ff;
            --primary-dark: #5b21b6;

            --text-main: #19131f;
            --text-secondary: #5f5668;
            --text-muted: #8b8394;
            --text-light: #aaa2b3;

            --border: rgba(74, 42, 104, 0.10);
            --border-medium: rgba(124, 58, 237, 0.17);
            --border-strong: rgba(124, 58, 237, 0.30);

            --success: #16a34a;
            --success-bg: #ecfdf3;

            --warning: #d97706;
            --warning-bg: #fff8e8;

            --danger: #dc2626;
            --danger-bg: #fff1f2;

            --info: #2563eb;
            --info-bg: #eff6ff;

            --shadow-xs:
                0 1px 2px rgba(42, 22, 68, 0.04);

            --shadow-sm:
                0 8px 24px rgba(58, 29, 92, 0.06);

            --shadow-md:
                0 18px 48px rgba(63, 33, 99, 0.10);

            --shadow-lg:
                0 30px 80px rgba(72, 33, 120, 0.16);

            --shadow-purple:
                0 20px 48px rgba(124, 58, 237, 0.23);

            --radius-xs: 9px;
            --radius-sm: 12px;
            --radius-md: 17px;
            --radius-lg: 22px;
            --radius-xl: 27px;

            --container-width: 1080px;

            --ease-smooth:
                cubic-bezier(0.16, 1, 0.3, 1);

            --ease-bounce:
                cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* ================================================================
           RESET
        ================================================================= */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            width: 100%;
            min-height: 100%;
            scroll-behavior: smooth;
            background: var(--page-bg);
        }

        body {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            overflow-x: hidden;

            color: var(--text-main);

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;

            font-size: 16px;
            line-height: 1.5;
            letter-spacing: -0.015em;

            background:
                radial-gradient(
                    circle at 5% 0%,
                    rgba(168, 85, 247, 0.14),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 96% 14%,
                    rgba(124, 58, 237, 0.11),
                    transparent 31%
                ),
                linear-gradient(
                    180deg,
                    #fcfbff 0%,
                    #f7f4ff 45%,
                    #fbfaff 100%
                );

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            -webkit-tap-highlight-color: transparent;
        }

        body.menu-locked {
            overflow: hidden;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        button {
            border: 0;
        }

        a {
            color: inherit;
        }

        img,
        svg {
            display: block;
            max-width: 100%;
        }

        ::selection {
            color: #ffffff;
            background: var(--primary);
        }

        /* ================================================================
           ANIMATED BACKGROUND
        ================================================================= */

        .ambient-background {
            position: fixed;
            inset: 0;
            z-index: 0;

            overflow: hidden;
            pointer-events: none;
        }

        .ambient-grid {
            position: absolute;
            inset: 0;

            opacity: 0.62;

            background-image:
                linear-gradient(
                    rgba(124, 58, 237, 0.027) 1px,
                    transparent 1px
                ),
                linear-gradient(
                    90deg,
                    rgba(124, 58, 237, 0.027) 1px,
                    transparent 1px
                );

            background-size: 44px 44px;

            -webkit-mask-image:
                linear-gradient(
                    to bottom,
                    black 0%,
                    rgba(0, 0, 0, 0.55) 50%,
                    transparent 92%
                );

            mask-image:
                linear-gradient(
                    to bottom,
                    black 0%,
                    rgba(0, 0, 0, 0.55) 50%,
                    transparent 92%
                );
        }

        .ambient-orb {
            position: absolute;

            border-radius: 50%;

            filter: blur(10px);
            opacity: 0.75;

            will-change: transform;
        }

        .ambient-orb.orb-a {
            width: 420px;
            height: 420px;

            top: -210px;
            left: -180px;

            background:
                radial-gradient(
                    circle,
                    rgba(192, 132, 252, 0.32),
                    rgba(168, 85, 247, 0.03) 68%,
                    transparent 72%
                );

            animation:
                orbDriftA 16s ease-in-out infinite alternate;
        }

        .ambient-orb.orb-b {
            width: 500px;
            height: 500px;

            top: 160px;
            right: -290px;

            background:
                radial-gradient(
                    circle,
                    rgba(124, 58, 237, 0.24),
                    rgba(124, 58, 237, 0.02) 69%,
                    transparent 73%
                );

            animation:
                orbDriftB 20s ease-in-out infinite alternate;
        }

        .ambient-orb.orb-c {
            width: 360px;
            height: 360px;

            bottom: 5%;
            left: 7%;

            background:
                radial-gradient(
                    circle,
                    rgba(216, 180, 254, 0.28),
                    rgba(192, 132, 252, 0.02) 70%,
                    transparent 74%
                );

            animation:
                orbDriftC 22s ease-in-out infinite alternate;
        }

        .ambient-ring {
            position: absolute;

            width: 240px;
            height: 240px;

            top: 46%;
            right: 8%;

            border: 1px solid rgba(124, 58, 237, 0.08);
            border-radius: 50%;

            box-shadow:
                0 0 0 34px rgba(124, 58, 237, 0.025),
                0 0 0 72px rgba(124, 58, 237, 0.014);

            animation:
                ringRotate 28s linear infinite;
        }

        @keyframes orbDriftA {
            from {
                transform:
                    translate3d(0, 0, 0)
                    scale(1);
            }

            to {
                transform:
                    translate3d(120px, 85px, 0)
                    scale(1.15);
            }
        }

        @keyframes orbDriftB {
            from {
                transform:
                    translate3d(0, 0, 0)
                    scale(1.02);
            }

            to {
                transform:
                    translate3d(-130px, 95px, 0)
                    scale(0.91);
            }
        }

        @keyframes orbDriftC {
            from {
                transform:
                    translate3d(0, 0, 0)
                    rotate(0deg);
            }

            to {
                transform:
                    translate3d(150px, -75px, 0)
                    rotate(16deg);
            }
        }

        @keyframes ringRotate {
            to {
                transform: rotate(360deg);
            }
        }

        /* ================================================================
           PAGE SHELL
        ================================================================= */

        .page-shell {
            position: relative;
            z-index: 1;

            width: min(100%, var(--container-width));
            margin: 0 auto;

            padding:
                max(16px, env(safe-area-inset-top))
                18px
                max(52px, env(safe-area-inset-bottom));
        }

        /* ================================================================
           REVEAL ANIMATION
        ================================================================= */

        .reveal {
            opacity: 0;
            transform: translateY(24px);

            transition:
                opacity 0.8s var(--ease-smooth),
                transform 0.8s var(--ease-smooth);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1 {
            transition-delay: 0.08s;
        }

        .reveal-delay-2 {
            transition-delay: 0.16s;
        }

        .reveal-delay-3 {
            transition-delay: 0.24s;
        }

        /* ================================================================
           TOP NAVBAR
        ================================================================= */

        .top-navigation {
            position: relative;
            z-index: 10;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            min-height: 62px;
            margin-bottom: 24px;
            padding: 8px 4px;
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 11px;

            min-width: 0;
        }

        .brand-mark {
            position: relative;

            display: grid;
            place-items: center;

            width: 43px;
            height: 43px;
            flex: 0 0 43px;

            color: #ffffff;
            font-size: 1.02rem;

            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 12px 25px rgba(124, 58, 237, 0.27),
                inset 0 1px 0 rgba(255, 255, 255, 0.42);
        }

        .brand-mark::after {
            content: "";

            position: absolute;
            inset: 4px;

            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 10px;
        }

        .brand-mark i {
            position: relative;
            z-index: 1;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-name {
            display: block;

            color: var(--text-main);
            font-size: 0.96rem;
            font-weight: 850;
            letter-spacing: -0.035em;
            white-space: nowrap;
        }

        .brand-subtitle {
            display: block;

            margin-top: 1px;

            color: var(--text-muted);
            font-size: 0.67rem;
            font-weight: 650;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .navigation-actions {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .system-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            min-height: 36px;
            padding: 7px 12px;

            color: #4e4259;
            font-size: 0.7rem;
            font-weight: 750;
            white-space: nowrap;

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 999px;

            background: rgba(255, 255, 255, 0.68);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            box-shadow: var(--shadow-xs);
        }

        .status-dot {
            position: relative;

            width: 7px;
            height: 7px;
            flex: 0 0 7px;

            border-radius: 50%;

            background: #22c55e;

            box-shadow:
                0 0 0 4px rgba(34, 197, 94, 0.13);
        }

        .status-dot::after {
            content: "";

            position: absolute;
            inset: -4px;

            border: 1px solid rgba(34, 197, 94, 0.42);
            border-radius: 50%;

            animation:
                statusPulse 2s ease-out infinite;
        }

        .help-button {
            display: grid;
            place-items: center;

            width: 36px;
            height: 36px;

            color: var(--text-secondary);
            font-size: 0.78rem;

            border: 1px solid var(--border);
            border-radius: 11px;

            background: rgba(255, 255, 255, 0.72);

            cursor: pointer;

            transition:
                color 0.2s ease,
                border-color 0.2s ease,
                background-color 0.2s ease,
                transform 0.25s var(--ease-smooth);
        }

        .help-button:hover {
            color: var(--primary);
            border-color: var(--border-medium);
            background: var(--purple-50);
            transform: translateY(-2px);
        }

        .help-button:active {
            transform: translateY(0) scale(0.94);
        }

        @keyframes statusPulse {
            0% {
                opacity: 0.9;
                transform: scale(0.75);
            }

            80%,
            100% {
                opacity: 0;
                transform: scale(1.9);
            }
        }

        /* ================================================================
           HERO
        ================================================================= */

        .hero-section {
            position: relative;
            overflow: hidden;

            display: grid;
            grid-template-columns:
                minmax(0, 1.15fr)
                minmax(270px, 0.85fr);
            align-items: center;
            gap: 30px;

            padding: 34px 36px;

            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: var(--radius-xl);

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.94),
                    rgba(249, 246, 255, 0.84)
                );

            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);

            box-shadow: var(--shadow-md);
        }

        .hero-section::before {
            content: "";

            position: absolute;

            width: 290px;
            height: 290px;

            top: -160px;
            right: 8%;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(168, 85, 247, 0.24),
                    transparent 69%
                );

            pointer-events: none;
        }

        .hero-section::after {
            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            bottom: -120px;
            left: 32%;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(124, 58, 237, 0.14),
                    transparent 72%
                );

            pointer-events: none;
        }

        .hero-content,
        .hero-dashboard {
            position: relative;
            z-index: 1;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 13px;
            padding: 6px 10px;

            color: var(--primary-dark);
            font-size: 0.67rem;
            font-weight: 850;
            letter-spacing: 0.05em;
            text-transform: uppercase;

            border: 1px solid rgba(124, 58, 237, 0.13);
            border-radius: 999px;

            background: var(--purple-50);
        }

        .hero-eyebrow i {
            font-size: 0.62rem;
        }

        .hero-title {
            max-width: 610px;

            color: #17111d;
            font-size: clamp(2.05rem, 5vw, 3.75rem);
            line-height: 0.99;
            font-weight: 880;
            letter-spacing: -0.064em;
        }

        .hero-title-gradient {
            display: inline-block;

            color: transparent;

            background:
                linear-gradient(
                    105deg,
                    var(--purple-600),
                    var(--purple-800),
                    var(--purple-500)
                );

            background-clip: text;
            -webkit-background-clip: text;

            background-size: 200% 100%;

            animation:
                textGradient 6s ease-in-out infinite alternate;
        }

        @keyframes textGradient {
            from {
                background-position: 0% 50%;
            }

            to {
                background-position: 100% 50%;
            }
        }

        .hero-description {
            max-width: 595px;

            margin-top: 16px;

            color: var(--text-secondary);
            font-size: clamp(0.88rem, 1.5vw, 1rem);
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;

            margin-top: 21px;
        }

        .hero-feature {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            min-height: 34px;
            padding: 7px 10px;

            color: #55495f;
            font-size: 0.7rem;
            font-weight: 750;

            border: 1px solid var(--border);
            border-radius: 10px;

            background: rgba(255, 255, 255, 0.74);
        }

        .hero-feature i {
            color: var(--primary);
            font-size: 0.68rem;
        }

        /* ================================================================
           HERO DASHBOARD
        ================================================================= */

        .hero-dashboard {
            display: flex;
            align-items: center;
            justify-content: center;

            min-height: 235px;
        }

        .dashboard-window {
            position: relative;

            width: min(100%, 305px);
            padding: 14px;

            border: 1px solid rgba(124, 58, 237, 0.15);
            border-radius: 23px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.94),
                    rgba(245, 239, 255, 0.84)
                );

            box-shadow:
                0 25px 65px rgba(75, 35, 120, 0.16),
                inset 0 1px 0 rgba(255, 255, 255, 0.92);

            transform: rotate(1.8deg);

            animation:
                dashboardFloat 5.8s ease-in-out infinite;
        }

        @keyframes dashboardFloat {
            0%,
            100% {
                transform:
                    rotate(1.8deg)
                    translateY(0);
            }

            50% {
                transform:
                    rotate(-0.8deg)
                    translateY(-9px);
            }
        }

        .window-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 12px;
        }

        .window-dots {
            display: flex;
            gap: 5px;
        }

        .window-dot {
            width: 7px;
            height: 7px;

            border-radius: 50%;
        }

        .window-dot:nth-child(1) {
            background: #fb7185;
        }

        .window-dot:nth-child(2) {
            background: #fbbf24;
        }

        .window-dot:nth-child(3) {
            background: #4ade80;
        }

        .window-online {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            color: var(--success);
            font-size: 0.58rem;
            font-weight: 800;
        }

        .window-online::before {
            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;
            background: currentColor;
        }

        .dashboard-main-card {
            position: relative;
            overflow: hidden;

            padding: 16px;

            color: #ffffff;

            border-radius: 17px;

            background:
                linear-gradient(
                    135deg,
                    #8b5cf6,
                    #6d28d9 55%,
                    #4c1d95
                );

            box-shadow:
                0 15px 32px rgba(109, 40, 217, 0.27);
        }

        .dashboard-main-card::after {
            content: "";

            position: absolute;

            width: 92px;
            height: 92px;

            top: -38px;
            right: -25px;

            border: 17px solid rgba(255, 255, 255, 0.09);
            border-radius: 50%;
        }

        .dashboard-label {
            position: relative;
            z-index: 1;

            color: rgba(255, 255, 255, 0.78);
            font-size: 0.62rem;
            font-weight: 750;
        }

        .dashboard-value {
            position: relative;
            z-index: 1;

            margin-top: 4px;

            font-size: 1.85rem;
            line-height: 1;
            font-weight: 880;
            letter-spacing: -0.055em;
        }

        .dashboard-change {
            position: relative;
            z-index: 1;

            display: flex;
            align-items: center;
            gap: 5px;

            margin-top: 6px;

            color: rgba(255, 255, 255, 0.74);
            font-size: 0.61rem;
            font-weight: 700;
        }

        .dashboard-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px;

            margin-top: 10px;
        }

        .dashboard-mini-card {
            padding: 11px;

            border: 1px solid rgba(124, 58, 237, 0.09);
            border-radius: 13px;

            background: rgba(255, 255, 255, 0.78);
        }

        .mini-card-icon {
            display: grid;
            place-items: center;

            width: 27px;
            height: 27px;

            color: var(--primary);
            font-size: 0.63rem;

            border-radius: 8px;
            background: var(--purple-100);
        }

        .mini-card-value {
            margin-top: 8px;

            color: #362d3e;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .mini-card-label {
            margin-top: 1px;

            color: var(--text-muted);
            font-size: 0.55rem;
            font-weight: 650;
        }

        .floating-notification {
            position: absolute;
            z-index: 4;

            display: flex;
            align-items: center;
            gap: 7px;

            padding: 8px 10px;

            color: #4e4259;
            font-size: 0.61rem;
            font-weight: 800;

            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: 11px;

            background: rgba(255, 255, 255, 0.93);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);

            box-shadow: var(--shadow-sm);
        }

        .floating-notification i {
            color: var(--primary);
        }

        .floating-notification.notice-a {
            top: 14px;
            right: -19px;

            animation:
                notificationFloatA 4.7s ease-in-out infinite;
        }

        .floating-notification.notice-b {
            bottom: 24px;
            left: -23px;

            animation:
                notificationFloatB 5.2s ease-in-out infinite;
        }

        @keyframes notificationFloatA {
            0%,
            100% {
                transform:
                    translateY(0)
                    rotate(1deg);
            }

            50% {
                transform:
                    translateY(-8px)
                    rotate(-1deg);
            }
        }

        @keyframes notificationFloatB {
            0%,
            100% {
                transform:
                    translateY(0)
                    rotate(-2deg);
            }

            50% {
                transform:
                    translateY(8px)
                    rotate(1deg);
            }
        }

        /* ================================================================
           METRIC STRIP
        ================================================================= */

        .metric-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 11px;

            margin-top: 13px;
        }

        .metric-box {
            display: flex;
            align-items: center;
            gap: 11px;

            min-width: 0;
            padding: 13px 15px;

            border: 1px solid var(--border);
            border-radius: 15px;

            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            box-shadow: var(--shadow-xs);

            transition:
                border-color 0.25s ease,
                transform 0.3s var(--ease-smooth),
                box-shadow 0.3s ease;
        }

        .metric-box:hover {
            border-color: var(--border-medium);
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
        }

        .metric-icon {
            display: grid;
            place-items: center;

            width: 36px;
            height: 36px;
            flex: 0 0 36px;

            color: var(--primary);
            font-size: 0.78rem;

            border-radius: 11px;
            background: var(--purple-100);
        }

        .metric-content {
            min-width: 0;
        }

        .metric-value {
            color: #342b3b;
            font-size: 0.83rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .metric-label {
            margin-top: 2px;

            color: var(--text-muted);
            font-size: 0.62rem;
            font-weight: 650;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ================================================================
           PROCESS STEPPER
        ================================================================= */

        .process-stepper {
            position: relative;

            margin: 36px auto 26px;
            padding: 0 32px;

            max-width: 720px;
        }

        .stepper-track {
            position: absolute;

            top: 18px;
            left: 70px;
            right: 70px;

            height: 3px;

            border-radius: 999px;
            background: #e8e1ef;

            overflow: hidden;
        }

        .stepper-progress {
            width: 0%;
            height: 100%;

            border-radius: inherit;

            background:
                linear-gradient(
                    90deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 0 14px rgba(124, 58, 237, 0.45);

            transition:
                width 0.7s var(--ease-smooth);
        }

        .stepper-items {
            position: relative;
            z-index: 1;

            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;

            color: var(--text-muted);
            text-align: center;

            transition: color 0.3s ease;
        }

        .stepper-circle {
            display: grid;
            place-items: center;

            width: 38px;
            height: 38px;

            color: #988fa1;
            font-size: 0.72rem;
            font-weight: 850;

            border: 1px solid #dfd7e6;
            border-radius: 12px;

            background: #ffffff;

            box-shadow: var(--shadow-xs);

            transition:
                color 0.35s ease,
                border-color 0.35s ease,
                background 0.35s ease,
                box-shadow 0.35s ease,
                transform 0.35s var(--ease-bounce);
        }

        .stepper-label {
            margin-top: 8px;

            font-size: 0.67rem;
            font-weight: 800;
        }

        .stepper-caption {
            margin-top: 2px;

            font-size: 0.56rem;
            font-weight: 600;
        }

        .stepper-item.active {
            color: var(--primary);
        }

        .stepper-item.active .stepper-circle {
            color: #ffffff;
            border-color: var(--primary);

            background:
                linear-gradient(
                    145deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 10px 22px rgba(124, 58, 237, 0.24);

            transform: scale(1.05);
        }

        .stepper-item.completed .stepper-circle {
            color: #ffffff;
            border-color: var(--primary);

            background: var(--primary);
        }

        /* ================================================================
           SECTION HEADER
        ================================================================= */

        .section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;

            margin: 36px 3px 17px;
        }

        .section-header-copy h2 {
            color: var(--text-main);
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            line-height: 1.15;
            font-weight: 860;
            letter-spacing: -0.048em;
        }

        .section-header-copy p {
            max-width: 590px;

            margin-top: 6px;

            color: var(--text-muted);
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .section-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            min-height: 33px;
            padding: 7px 10px;

            color: var(--primary);
            font-size: 0.66rem;
            font-weight: 850;
            white-space: nowrap;

            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: 10px;

            background: var(--purple-50);
        }

        /* ================================================================
           PLAN CARDS
        ================================================================= */

        .plans-grid {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 405px));
            justify-content: center;
            gap: 17px;

            margin-bottom: 24px;
        }

        .plan-card {
            --pointer-x: 50%;
            --pointer-y: 50%;

            position: relative;
            overflow: hidden;

            display: flex;
            flex-direction: column;

            min-width: 0;
            min-height: 455px;
            padding: 23px;

            text-align: left;

            border: 1px solid var(--border);
            border-radius: 24px;

            background:
                radial-gradient(
                    circle at var(--pointer-x) var(--pointer-y),
                    rgba(168, 85, 247, 0.095),
                    transparent 34%
                ),
                linear-gradient(
                    155deg,
                    rgba(255, 255, 255, 0.97),
                    rgba(251, 249, 255, 0.93)
                );

            box-shadow: var(--shadow-sm);

            cursor: pointer;
            outline: none;

            transition:
                transform 0.45s var(--ease-smooth),
                border-color 0.35s ease,
                box-shadow 0.45s var(--ease-smooth);
        }

        .plan-card::before {
            content: "";

            position: absolute;
            inset: 0;

            border-radius: inherit;

            background:
                linear-gradient(
                    125deg,
                    rgba(255, 255, 255, 0.76),
                    transparent 34%,
                    rgba(168, 85, 247, 0.045)
                );

            pointer-events: none;
        }

        .plan-card::after {
            content: "";

            position: absolute;

            width: 175px;
            height: 175px;

            right: -104px;
            bottom: -110px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(168, 85, 247, 0.15),
                    transparent 70%
                );

            transition:
                transform 0.6s var(--ease-smooth),
                opacity 0.4s ease;

            pointer-events: none;
        }

        .plan-card:hover {
            border-color: rgba(124, 58, 237, 0.24);

            box-shadow:
                0 26px 58px rgba(70, 35, 108, 0.13),
                0 0 0 1px rgba(124, 58, 237, 0.035);

            transform: translateY(-7px);
        }

        .plan-card:hover::after {
            transform: scale(1.48);
        }

        .plan-card:focus-visible {
            border-color: var(--primary);

            box-shadow:
                0 0 0 5px rgba(124, 58, 237, 0.14),
                var(--shadow-md);
        }

        .plan-card:active {
            transform:
                translateY(-2px)
                scale(0.991);
        }

        .plan-card.active-card {
            border-color: var(--primary);

            background:
                radial-gradient(
                    circle at var(--pointer-x) var(--pointer-y),
                    rgba(168, 85, 247, 0.15),
                    transparent 35%
                ),
                linear-gradient(
                    155deg,
                    #ffffff,
                    #f5f9ff
                );

            box-shadow:
                0 0 0 4px rgba(124, 58, 237, 0.11),
                0 27px 62px rgba(86, 41, 139, 0.16);

            transform: translateY(-4px);
        }

        .plan-card.active-card .plan-selection {
            color: #ffffff;
            border-color: var(--primary);
            background: var(--primary);
        }

        .plan-card.active-card .plan-selection i {
            opacity: 1;
            transform: scale(1);
        }

        .plan-card.active-card .plan-icon {
            color: #ffffff;

            background:
                linear-gradient(
                    145deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 14px 28px rgba(124, 58, 237, 0.24);
        }

        .plan-card.active-card .plan-action {
            color: #ffffff;
            background: var(--primary);
        }

        .plan-card-content {
            position: relative;
            z-index: 1;

            display: flex;
            flex: 1;
            flex-direction: column;
        }

        .plan-top-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;

            margin-bottom: 19px;
        }

        .plan-icon-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-icon {
            display: grid;
            place-items: center;

            width: 50px;
            height: 50px;
            flex: 0 0 50px;

            color: var(--primary);
            font-size: 1.08rem;

            border: 1px solid rgba(124, 58, 237, 0.1);
            border-radius: 15px;

            background:
                linear-gradient(
                    145deg,
                    var(--purple-50),
                    var(--purple-100)
                );

            transition:
                color 0.35s ease,
                background 0.35s ease,
                transform 0.4s var(--ease-smooth),
                box-shadow 0.4s ease;
        }

        .plan-card:hover .plan-icon {
            transform:
                rotate(-4deg)
                scale(1.05);
        }

        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 8px;

            color: var(--primary-dark);
            font-size: 0.58rem;
            font-weight: 850;
            letter-spacing: 0.035em;
            text-transform: uppercase;

            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: 999px;

            background: var(--purple-50);
        }

        .plan-badge.recommended {
            color: #ffffff;
            border-color: transparent;

            background:
                linear-gradient(
                    115deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 8px 17px rgba(124, 58, 237, 0.21);
        }

        .plan-selection {
            display: grid;
            place-items: center;

            width: 26px;
            height: 26px;
            flex: 0 0 26px;

            color: transparent;

            border: 2px solid #d8d0df;
            border-radius: 50%;

            background: rgba(255, 255, 255, 0.88);

            transition:
                color 0.3s ease,
                border-color 0.3s ease,
                background-color 0.3s ease,
                transform 0.3s var(--ease-bounce);
        }

        .plan-selection i {
            font-size: 0.61rem;

            opacity: 0;
            transform: scale(0.4);

            transition:
                opacity 0.25s ease,
                transform 0.3s var(--ease-bounce);
        }

        .plan-name {
            color: var(--text-main);
            font-size: 1.35rem;
            line-height: 1.15;
            font-weight: 860;
            letter-spacing: -0.045em;
        }

        .plan-description {
            min-height: 42px;

            margin-top: 7px;

            color: var(--text-secondary);
            font-size: 0.77rem;
            line-height: 1.58;
        }

        .plan-price-row {
            display: flex;
            align-items: flex-end;
            gap: 6px;

            margin-top: 19px;
            padding-bottom: 18px;

            border-bottom: 1px solid var(--border);
        }

        .price-symbol {
            align-self: flex-start;

            margin-top: 5px;

            color: var(--text-main);
            font-size: 0.94rem;
            font-weight: 850;
        }

        .plan-price {
            color: #1d1622;
            font-size: clamp(2.35rem, 5.5vw, 3.45rem);
            line-height: 0.94;
            font-weight: 900;
            letter-spacing: -0.068em;
        }

        .price-period {
            margin-bottom: 4px;

            color: var(--text-muted);
            font-size: 0.69rem;
            font-weight: 700;
        }

        .api-rule {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            margin-top: 17px;
            padding: 11px;

            border: 1px solid rgba(124, 58, 237, 0.10);
            border-radius: 13px;

            background:
                linear-gradient(
                    145deg,
                    rgba(250, 247, 255, 0.96),
                    rgba(246, 240, 255, 0.77)
                );
        }

        .api-rule-icon {
            display: grid;
            place-items: center;

            width: 29px;
            height: 29px;
            flex: 0 0 29px;

            color: var(--primary);
            font-size: 0.68rem;

            border-radius: 9px;

            background: #ffffff;
            box-shadow: var(--shadow-xs);
        }

        .api-rule strong {
            display: block;

            color: #403548;
            font-size: 0.7rem;
            font-weight: 850;
        }

        .api-rule span {
            display: block;

            margin-top: 2px;

            color: var(--text-muted);
            font-size: 0.63rem;
            line-height: 1.44;
        }

        .plan-features {
            display: grid;
            gap: 10px;

            margin-top: 17px;

            list-style: none;
        }

        .plan-features li {
            display: flex;
            align-items: flex-start;
            gap: 9px;

            color: #4e4456;
            font-size: 0.73rem;
            font-weight: 650;
            line-height: 1.42;
        }

        .feature-check {
            display: grid;
            place-items: center;

            width: 19px;
            height: 19px;
            flex: 0 0 19px;

            color: var(--primary);
            font-size: 0.5rem;

            border-radius: 6px;
            background: var(--purple-100);
        }

        .plan-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin-top: auto;
            padding-top: 21px;
        }

        .plan-footer-label {
            color: #5d5267;
            font-size: 0.71rem;
            font-weight: 850;

            transition: color 0.3s ease;
        }

        .plan-action {
            position: relative;
            overflow: hidden;

            display: grid;
            place-items: center;

            width: 33px;
            height: 33px;

            color: var(--primary);

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 10px;

            background: var(--purple-50);

            transition:
                color 0.3s ease,
                background-color 0.3s ease,
                transform 0.35s var(--ease-smooth);
        }

        .plan-card:hover .plan-action {
            color: #ffffff;
            background: var(--primary);
            transform: translateX(3px);
        }

        /* ================================================================
           SELECTED PLAN PREVIEW BAR
        ================================================================= */

        .selected-plan-bar {
            display: grid;
            grid-template-rows: 0fr;

            max-width: 827px;
            margin: 0 auto 0;

            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);

            transition:
                grid-template-rows 0.55s var(--ease-smooth),
                opacity 0.4s ease,
                visibility 0.4s ease,
                transform 0.45s var(--ease-smooth);
        }

        .selected-plan-bar.show {
            grid-template-rows: 1fr;

            margin-bottom: 18px;

            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .selected-plan-overflow {
            min-height: 0;
            overflow: hidden;
        }

        .selected-plan-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;

            padding: 11px 13px;

            border: 1px solid rgba(124, 58, 237, 0.13);
            border-radius: 14px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.9),
                    rgba(248, 244, 255, 0.87)
                );

            box-shadow: var(--shadow-xs);
        }

        .selected-plan-info {
            display: flex;
            align-items: center;
            gap: 10px;

            min-width: 0;
        }

        .selected-plan-icon {
            display: grid;
            place-items: center;

            width: 34px;
            height: 34px;
            flex: 0 0 34px;

            color: #ffffff;
            font-size: 0.73rem;

            border-radius: 10px;

            background:
                linear-gradient(
                    145deg,
                    var(--purple-500),
                    var(--purple-800)
                );

            box-shadow:
                0 9px 18px rgba(124, 58, 237, 0.21);
        }

        .selected-plan-copy {
            min-width: 0;
        }

        .selected-plan-copy strong {
            display: block;

            color: #1d1d1f;
            font-size: 0.75rem;
            font-weight: 850;
        }

        .selected-plan-copy span {
            display: block;

            margin-top: 1px;

            color: var(--text-muted);
            font-size: 0.61rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .selected-plan-price {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .selected-plan-price strong {
            padding: 5px 8px;

            color: #ffffff;

            border-radius: 8px;
            background: var(--primary);
        }

        /* ================================================================
           CHECKOUT SECTION
        ================================================================= */

        .checkout-section {
            display: grid;
            grid-template-rows: 0fr;

            opacity: 0;
            visibility: hidden;
            transform: translateY(17px);

            transition:
                grid-template-rows 0.65s var(--ease-smooth),
                opacity 0.45s ease,
                visibility 0.45s ease,
                transform 0.55s var(--ease-smooth);
        }

        .checkout-section.show {
            grid-template-rows: 1fr;

            margin-bottom: 28px;

            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .checkout-overflow {
            min-height: 0;
            overflow: hidden;
        }

        .checkout-card {
            display: grid;
            grid-template-columns:
                minmax(230px, 0.62fr)
                minmax(0, 1.38fr);

            overflow: hidden;

            border: 1px solid rgba(124, 58, 237, 0.13);
            border-radius: 25px;

            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow: var(--shadow-md);
        }

        /* ================================================================
           CHECKOUT SIDEBAR
        ================================================================= */

        .checkout-summary {
            position: relative;
            overflow: hidden;

            display: flex;
            flex-direction: column;

            min-height: 100%;
            padding: 27px;

            color: #ffffff;

            background:
                linear-gradient(
                    145deg,
                    #8b5cf6 0%,
                    #6d28d9 52%,
                    #4c1d95 100%
                );
        }

        .checkout-summary::before {
            content: "";

            position: absolute;

            width: 205px;
            height: 205px;

            top: -110px;
            right: -96px;

            border: 29px solid rgba(255, 255, 255, 0.075);
            border-radius: 50%;
        }

        .checkout-summary::after {
            content: "";

            position: absolute;

            width: 165px;
            height: 165px;

            bottom: -100px;
            left: -78px;

            border: 24px solid rgba(255, 255, 255, 0.055);
            border-radius: 50%;
        }

        .summary-inner {
            position: relative;
            z-index: 1;

            display: flex;
            flex: 1;
            flex-direction: column;
        }

        .summary-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            width: fit-content;
            padding: 6px 9px;

            font-size: 0.59rem;
            font-weight: 850;
            letter-spacing: 0.045em;
            text-transform: uppercase;

            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;

            background: rgba(255, 255, 255, 0.10);
        }

        .summary-plan-icon {
            display: grid;
            place-items: center;

            width: 51px;
            height: 51px;

            margin-top: 28px;

            font-size: 1.1rem;

            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 16px;

            background: rgba(255, 255, 255, 0.13);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);

            box-shadow:
                0 15px 32px rgba(33, 10, 72, 0.18);
        }

        .summary-plan-name {
            margin-top: 17px;

            font-size: 1.48rem;
            line-height: 1.1;
            font-weight: 880;
            letter-spacing: -0.05em;
        }

        .summary-description {
            margin-top: 7px;

            color: rgba(255, 255, 255, 0.73);
            font-size: 0.71rem;
            line-height: 1.57;
        }

        .summary-price-row {
            display: flex;
            align-items: flex-end;
            gap: 4px;

            margin-top: 20px;
        }

        .summary-price-symbol {
            align-self: flex-start;

            margin-top: 5px;

            font-size: 0.89rem;
            font-weight: 850;
        }

        .summary-price-value {
            font-size: 3rem;
            line-height: 0.94;
            font-weight: 900;
            letter-spacing: -0.068em;
        }

        .summary-price-period {
            margin-bottom: 4px;

            color: rgba(255, 255, 255, 0.67);
            font-size: 0.66rem;
            font-weight: 700;
        }

        .summary-points {
            display: grid;
            gap: 9px;

            margin-top: 24px;

            list-style: none;
        }

        .summary-points li {
            display: flex;
            align-items: center;
            gap: 8px;

            color: rgba(255, 255, 255, 0.81);
            font-size: 0.67rem;
            font-weight: 650;
        }

        .summary-points i {
            display: grid;
            place-items: center;

            width: 18px;
            height: 18px;

            color: #ffffff;
            font-size: 0.49rem;

            border-radius: 6px;

            background: rgba(255, 255, 255, 0.13);
        }

        .summary-security {
            display: flex;
            align-items: center;
            gap: 8px;

            margin-top: auto;
            padding-top: 27px;

            color: rgba(255, 255, 255, 0.59);
            font-size: 0.6rem;
            font-weight: 650;
            line-height: 1.45;
        }

        /* ================================================================
           CHECKOUT FORM AREA
        ================================================================= */

        .checkout-form-area {
            min-width: 0;
            padding: 29px;
        }

        .checkout-form-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;

            margin-bottom: 23px;
            padding-bottom: 18px;

            border-bottom: 1px solid var(--border);
        }

        .checkout-form-title h3 {
            color: var(--text-main);
            font-size: 1.17rem;
            line-height: 1.2;
            font-weight: 860;
            letter-spacing: -0.04em;
        }

        .checkout-form-title p {
            margin-top: 5px;

            color: var(--text-muted);
            font-size: 0.7rem;
            line-height: 1.5;
        }

        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            min-height: 31px;
            padding: 6px 9px;

            color: var(--success);
            font-size: 0.6rem;
            font-weight: 850;
            white-space: nowrap;

            border: 1px solid rgba(22, 163, 74, 0.13);
            border-radius: 9px;

            background: var(--success-bg);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-field {
            min-width: 0;
            margin-bottom: 15px;
        }

        .form-field.full-width {
            grid-column: 1 / -1;
        }

        .field-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            margin-bottom: 7px;
        }

        .field-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            color: #3d3445;
            font-size: 0.7rem;
            font-weight: 850;
        }

        .field-label i {
            color: var(--primary);
            font-size: 0.62rem;
        }

        .field-required {
            color: var(--text-light);
            font-size: 0.57rem;
            font-weight: 700;
        }

        .input-shell {
            position: relative;

            display: flex;
            align-items: center;

            min-width: 0;
            min-height: 49px;

            border: 1px solid var(--border);
            border-radius: 13px;

            background: #fbfaff;

            transition:
                border-color 0.23s ease,
                background-color 0.23s ease,
                box-shadow 0.23s ease,
                transform 0.23s var(--ease-smooth);
        }

        .input-shell:hover {
            border-color: rgba(124, 58, 237, 0.20);
            background: #ffffff;
        }

        .input-shell:focus-within {
            border-color: var(--primary);
            background: #ffffff;

            box-shadow:
                0 0 0 4px rgba(124, 58, 237, 0.10),
                0 8px 19px rgba(91, 33, 182, 0.045);

            transform: translateY(-1px);
        }

        .input-icon {
            display: grid;
            place-items: center;

            width: 43px;
            height: 47px;
            flex: 0 0 43px;

            color: #9a8fa5;
            font-size: 0.73rem;

            transition: color 0.23s ease;
        }

        .input-shell:focus-within .input-icon {
            color: var(--primary);
        }

        .apple-input {
            width: 100%;
            min-width: 0;
            height: 47px;

            padding: 0 12px 0 0;

            color: var(--text-main);
            font-size: 0.79rem;
            font-weight: 650;

            border: 0;
            outline: 0;

            background: transparent;

            -webkit-appearance: none;
            appearance: none;
        }

        .apple-input::placeholder {
            color: #b0a8b8;
            font-weight: 500;
        }

        select.apple-input {
            cursor: pointer;

            background-image:
                linear-gradient(
                    45deg,
                    transparent 50%,
                    var(--text-muted) 50%
                ),
                linear-gradient(
                    135deg,
                    var(--text-muted) 50%,
                    transparent 50%
                );

            background-position:
                calc(100% - 17px) 20px,
                calc(100% - 12px) 20px;

            background-size:
                5px 5px,
                5px 5px;

            background-repeat: no-repeat;

            padding-right: 34px;
        }

        .domain-prefix {
            color: var(--text-muted);
            font-size: 0.7rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .domain-input {
            padding-left: 3px;
        }

        .domain-suffix {
            display: inline-flex;
            align-items: center;

            height: 31px;
            margin-right: 8px;
            padding: 0 9px;

            color: var(--primary-dark);
            font-size: 0.66rem;
            font-weight: 850;

            border: 1px solid rgba(124, 58, 237, 0.1);
            border-radius: 8px;

            background: var(--purple-100);
        }

        .field-help {
            display: flex;
            align-items: flex-start;
            gap: 6px;

            margin-top: 7px;

            color: var(--text-muted);
            font-size: 0.6rem;
            line-height: 1.46;
        }

        .field-help i {
            margin-top: 2px;

            color: var(--primary);
            font-size: 0.56rem;
        }

        .password-toggle {
            display: grid;
            place-items: center;

            width: 37px;
            height: 37px;
            flex: 0 0 37px;

            margin-right: 5px;

            color: var(--text-muted);
            font-size: 0.71rem;

            border-radius: 9px;
            background: transparent;

            cursor: pointer;

            transition:
                color 0.2s ease,
                background-color 0.2s ease,
                transform 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: var(--purple-100);
        }

        .password-toggle:active {
            transform: scale(0.91);
        }

        /* ================================================================
           LIVE DOMAIN PREVIEW
        ================================================================= */

        .domain-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            margin-top: 8px;
            padding: 9px 10px;

            border: 1px solid rgba(124, 58, 237, 0.09);
            border-radius: 10px;

            background: var(--purple-50);
        }

        .domain-preview-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            color: var(--text-muted);
            font-size: 0.58rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .domain-preview-label i {
            color: var(--primary);
        }

        .domain-preview-value {
            min-width: 0;

            color: var(--primary-dark);
            font-size: 0.61rem;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ================================================================
           PASSWORD STRENGTH
        ================================================================= */

        .password-strength {
            display: none;

            margin-top: 8px;
        }

        .password-strength.visible {
            display: block;
        }

        .strength-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            margin-bottom: 6px;
        }

        .strength-header span {
            color: var(--text-muted);
            font-size: 0.57rem;
            font-weight: 700;
        }

        .strength-header strong {
            color: var(--text-muted);
            font-size: 0.57rem;
            font-weight: 850;
        }

        .strength-track {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
        }

        .strength-segment {
            height: 4px;

            border-radius: 999px;
            background: #e7e1eb;

            transition:
                background-color 0.3s ease,
                transform 0.3s var(--ease-bounce);
        }

        .password-strength.level-1 .strength-segment:nth-child(1) {
            background: var(--danger);
            transform: scaleY(1.2);
        }

        .password-strength.level-2 .strength-segment:nth-child(-n+2) {
            background: var(--warning);
            transform: scaleY(1.2);
        }

        .password-strength.level-3 .strength-segment:nth-child(-n+3) {
            background: #84cc16;
            transform: scaleY(1.2);
        }

        .password-strength.level-4 .strength-segment {
            background: var(--success);
            transform: scaleY(1.2);
        }

        /* ================================================================
           CHECKOUT NOTICE
        ================================================================= */

        .checkout-notice {
            display: flex;
            align-items: flex-start;
            gap: 9px;

            margin-top: 1px;
            padding: 11px;

            border: 1px solid rgba(37, 99, 235, 0.09);
            border-radius: 12px;

            background: var(--info-bg);
        }

        .checkout-notice-icon {
            display: grid;
            place-items: center;

            width: 28px;
            height: 28px;
            flex: 0 0 28px;

            color: var(--info);
            font-size: 0.66rem;

            border-radius: 8px;
            background: #ffffff;
        }

        .checkout-notice strong {
            display: block;

            color: #34445f;
            font-size: 0.65rem;
            font-weight: 850;
        }

        .checkout-notice span {
            display: block;

            margin-top: 2px;

            color: #68768d;
            font-size: 0.59rem;
            line-height: 1.45;
        }

        /* ================================================================
           MAIN ACTION BUTTON
        ================================================================= */

        .action-button {
            position: relative;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            width: 100%;
            min-height: 52px;

            margin-top: 17px;
            padding: 12px 18px;

            color: #ffffff;
            font-size: 0.79rem;
            font-weight: 850;
            letter-spacing: -0.01em;

            border-radius: 14px;

            background:
                linear-gradient(
                    105deg,
                    #8b5cf6,
                    #0071e3 47%,
                    #5b21b6
                );

            background-size: 185% 100%;

            box-shadow:
                0 15px 30px rgba(124, 58, 237, 0.26),
                inset 0 1px 0 rgba(255, 255, 255, 0.26);

            cursor: pointer;

            transition:
                transform 0.3s var(--ease-smooth),
                box-shadow 0.3s ease,
                background-position 0.45s ease,
                opacity 0.25s ease;
        }

        .action-button::before {
            content: "";

            position: absolute;

            width: 105px;
            height: 210px;

            top: -78px;
            left: -145px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    rgba(255, 255, 255, 0.34),
                    transparent
                );

            transform: rotate(24deg);

            transition:
                left 0.68s var(--ease-smooth);
        }

        .action-button:hover {
            background-position: 100% 0;

            transform: translateY(-2px);

            box-shadow:
                0 19px 38px rgba(124, 58, 237, 0.33),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .action-button:hover::before {
            left: calc(100% + 65px);
        }

        .action-button:active {
            transform:
                translateY(0)
                scale(0.985);
        }

        .action-button:focus-visible {
            outline: none;

            box-shadow:
                0 0 0 5px rgba(124, 58, 237, 0.15),
                0 18px 38px rgba(124, 58, 237, 0.3);
        }

        .action-button:disabled {
            opacity: 0.67;
            cursor: not-allowed;
            transform: none;
        }

        .action-price {
            display: inline-flex;
            align-items: center;
            gap: 2px;

            padding: 4px 7px;

            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
        }

        .button-ripple {
            position: absolute;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.38);

            pointer-events: none;

            transform: scale(0);

            animation:
                rippleAnimation 0.7s ease-out forwards;
        }

        @keyframes rippleAnimation {
            to {
                opacity: 0;
                transform: scale(2.8);
            }
        }

        /* ================================================================
           HISTORY SECTION
        ================================================================= */

        .history-section {
            margin-top: 36px;
        }

        .history-card {
            overflow: hidden;

            border: 1px solid var(--border);
            border-radius: 25px;

            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);

            box-shadow: var(--shadow-md);
        }

        .history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;

            padding: 21px 23px;

            border-bottom: 1px solid var(--border);

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, 0.96),
                    rgba(250, 247, 255, 0.88)
                );
        }

        .history-title-group {
            display: flex;
            align-items: center;
            gap: 11px;

            min-width: 0;
        }

        .history-icon {
            display: grid;
            place-items: center;

            width: 38px;
            height: 38px;
            flex: 0 0 38px;

            color: var(--primary);
            font-size: 0.83rem;

            border: 1px solid rgba(124, 58, 237, 0.1);
            border-radius: 12px;

            background: var(--purple-100);
        }

        .history-title-group h2 {
            color: var(--text-main);
            font-size: 1rem;
            line-height: 1.2;
            font-weight: 860;
            letter-spacing: -0.038em;
        }

        .history-title-group p {
            margin-top: 3px;

            color: var(--text-muted);
            font-size: 0.63rem;
        }

        .history-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            min-height: 31px;
            padding: 6px 9px;

            color: var(--primary-dark);
            font-size: 0.63rem;
            font-weight: 850;
            white-space: nowrap;

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 9px;

            background: var(--purple-50);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .clean-table {
            width: 100%;
            min-width: 710px;

            border-collapse: collapse;
        }

        .clean-table th {
            padding: 12px 16px;

            color: var(--text-muted);
            font-size: 0.58rem;
            font-weight: 850;
            letter-spacing: 0.055em;
            text-align: left;
            text-transform: uppercase;

            border-bottom: 1px solid var(--border);

            background: rgba(250, 248, 253, 0.75);
        }

        .clean-table td {
            padding: 14px 16px;

            color: #4a414f;
            font-size: 0.72rem;
            font-weight: 650;
            vertical-align: middle;

            border-bottom: 1px solid rgba(74, 42, 104, 0.07);

            transition:
                background-color 0.2s ease,
                color 0.2s ease;
        }

        .clean-table tbody tr {
            transition:
                background-color 0.2s ease,
                transform 0.25s var(--ease-smooth);
        }

        .clean-table tbody tr:hover {
            background: rgba(250, 247, 255, 0.78);
        }

        .clean-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .panel-domain-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            max-width: 230px;

            color: #44384d;
            font-weight: 760;
            text-decoration: none;

            transition:
                color 0.2s ease,
                transform 0.2s ease;
        }

        .panel-domain-link:hover {
            color: var(--primary);
            transform: translateX(2px);
        }

        .domain-link-icon {
            display: grid;
            place-items: center;

            width: 29px;
            height: 29px;
            flex: 0 0 29px;

            color: var(--primary);
            font-size: 0.61rem;

            border-radius: 9px;
            background: var(--purple-100);
        }

        .domain-link-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .panel-type-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 5px 8px;

            color: #51415e;
            font-size: 0.6rem;
            font-weight: 850;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: var(--surface-soft);
        }

        .panel-type-pill i {
            color: var(--primary);
            font-size: 0.53rem;
        }

        .panel-price-cell {
            color: #2f2636;
            font-weight: 850;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;

            padding: 5px 9px;

            font-size: 0.59rem;
            font-weight: 850;

            border-radius: 999px;
        }

        .status-pill::before {
            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;

            background: currentColor;
            box-shadow:
                0 0 0 3px currentColor;

            opacity: 0.75;
        }

        .status-active {
            color: var(--success);
            background: var(--success-bg);
        }

        .status-pending {
            color: var(--warning);
            background: var(--warning-bg);
        }

        .status-suspended {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .details-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            min-height: 31px;
            padding: 6px 9px;

            color: var(--primary);
            font-size: 0.59rem;
            font-weight: 850;

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 9px;

            background: var(--purple-50);

            cursor: pointer;

            transition:
                color 0.2s ease,
                background-color 0.2s ease,
                border-color 0.2s ease,
                transform 0.25s var(--ease-smooth),
                box-shadow 0.25s ease;
        }

        .details-button:hover {
            color: #ffffff;
            border-color: var(--primary);
            background: var(--primary);

            box-shadow:
                0 8px 18px rgba(124, 58, 237, 0.2);

            transform: translateY(-2px);
        }

        .details-button:active {
            transform:
                translateY(0)
                scale(0.96);
        }

        /* ================================================================
           EMPTY STATE
        ================================================================= */

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            min-height: 235px;
            padding: 34px 20px;

            text-align: center;
        }

        .empty-icon {
            position: relative;

            display: grid;
            place-items: center;

            width: 66px;
            height: 66px;

            color: var(--primary);
            font-size: 1.22rem;

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 20px;

            background:
                linear-gradient(
                    145deg,
                    var(--purple-50),
                    var(--purple-100)
                );

            box-shadow: var(--shadow-sm);
        }

        .empty-icon::after {
            content: "";

            position: absolute;
            inset: -8px;

            border: 1px dashed rgba(124, 58, 237, 0.16);
            border-radius: 25px;

            animation:
                emptyRotate 17s linear infinite;
        }

        @keyframes emptyRotate {
            to {
                transform: rotate(360deg);
            }
        }

        .empty-state h3 {
            margin-top: 20px;

            color: var(--text-main);
            font-size: 0.92rem;
            font-weight: 860;
            letter-spacing: -0.035em;
        }

        .empty-state p {
            max-width: 360px;

            margin-top: 6px;

            color: var(--text-muted);
            font-size: 0.7rem;
            line-height: 1.55;
        }

        /* ================================================================
           FOOTER
        ================================================================= */

        .page-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            margin-top: 24px;

            color: var(--text-muted);
            font-size: 0.61rem;
            font-weight: 650;
            text-align: center;
        }

        .page-footer i {
            color: var(--primary);
        }

        /* ================================================================
           SWEETALERT
        ================================================================= */

        .swal2-popup {
            width: min(92vw, 420px) !important;

            padding: 27px !important;

            border: 1px solid rgba(124, 58, 237, 0.13) !important;
            border-radius: 22px !important;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif !important;

            box-shadow:
                0 35px 90px rgba(50, 22, 80, 0.24) !important;
        }

        .swal2-title {
            color: var(--text-main) !important;
            font-size: 1.18rem !important;
            font-weight: 880 !important;
            letter-spacing: -0.04em !important;
        }

        .swal2-html-container {
            color: var(--text-secondary) !important;
            font-size: 0.76rem !important;
            line-height: 1.6 !important;
        }

        .swal2-confirm,
        .swal2-cancel {
            min-height: 40px !important;
            padding: 9px 15px !important;

            font-size: 0.7rem !important;
            font-weight: 850 !important;

            border-radius: 11px !important;
            box-shadow: none !important;
        }

        .swal2-confirm {
            background:
                linear-gradient(
                    105deg,
                    var(--purple-500),
                    var(--purple-800)
                ) !important;
        }

        .admin-modal-grid {
            display: grid;
            gap: 10px;

            margin-top: 12px;
        }

        .admin-detail-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            padding: 11px;

            text-align: left;

            border: 1px solid rgba(124, 58, 237, 0.11);
            border-radius: 12px;

            background: var(--purple-50);
        }

        .admin-detail-copy {
            min-width: 0;
        }

        .admin-detail-copy span {
            display: block;

            color: var(--text-muted);
            font-size: 0.56rem;
            font-weight: 850;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .admin-detail-copy strong {
            display: block;

            margin-top: 3px;

            color: #342b3b;
            font-size: 0.72rem;
            font-weight: 800;
            word-break: break-all;
        }

        .copy-detail-button {
            display: grid;
            place-items: center;

            width: 32px;
            height: 32px;
            flex: 0 0 32px;

            color: var(--primary);
            font-size: 0.66rem;

            border: 1px solid rgba(124, 58, 237, 0.12);
            border-radius: 9px;

            background: #ffffff;

            cursor: pointer;

            transition:
                color 0.2s ease,
                background-color 0.2s ease,
                transform 0.2s ease;
        }

        .copy-detail-button:hover {
            color: #ffffff;
            background: var(--primary);
            transform: scale(1.05);
        }

        /* ================================================================
           SCROLLBAR
        ================================================================= */

        .table-responsive::-webkit-scrollbar {
            height: 7px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f7f3fa;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            border: 2px solid #f7f3fa;
            border-radius: 999px;
            background: #d5c8e1;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #bca6d0;
        }

        /* ================================================================
           TABLET
        ================================================================= */

        @media (max-width: 900px) {
            .page-shell {
                padding-left: 15px;
                padding-right: 15px;
            }

            .hero-section {
                grid-template-columns: 1fr;
                gap: 28px;

                padding: 31px;
            }

            .hero-content {
                text-align: center;
            }

            .hero-title,
            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-dashboard {
                min-height: 215px;
            }

            .metric-strip {
                gap: 9px;
            }

            .checkout-card {
                grid-template-columns: 1fr;
            }

            .checkout-summary {
                min-height: auto;
            }

            .summary-inner {
                display: grid;
                grid-template-columns:
                    auto
                    minmax(0, 1fr)
                    auto;
                align-items: center;
                gap: 16px;
            }

            .summary-badge {
                grid-column: 1 / -1;
            }

            .summary-plan-icon {
                margin-top: 0;
            }

            .summary-plan-name {
                margin-top: 0;
            }

            .summary-description {
                max-width: 460px;
            }

            .summary-price-row {
                margin-top: 0;
            }

            .summary-points {
                grid-column: 1 / -1;
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));

                margin-top: 4px;
            }

            .summary-security {
                grid-column: 1 / -1;

                margin-top: 0;
                padding-top: 3px;
            }
        }

        /* ================================================================
           MOBILE
        ================================================================= */

        @media (max-width: 700px) {
            .page-shell {
                padding-left: 11px;
                padding-right: 11px;
                padding-bottom: 38px;
            }

            .top-navigation {
                min-height: 55px;
                margin-bottom: 18px;
            }

            .brand-subtitle {
                display: none;
            }

            .system-status {
                max-width: 155px;
                padding: 6px 9px;

                font-size: 0.61rem;
            }

            .hero-section {
                gap: 25px;

                padding: 26px 17px;

                border-radius: 22px;
            }

            .hero-title {
                font-size: clamp(2.18rem, 13vw, 3.2rem);
            }

            .hero-description {
                font-size: 0.8rem;
            }

            .hero-actions {
                gap: 7px;
            }

            .hero-feature {
                padding: 6px 8px;

                font-size: 0.61rem;
            }

            .hero-dashboard {
                min-height: 205px;
            }

            .dashboard-window {
                width: min(88%, 295px);
            }

            .floating-notification.notice-a {
                right: -11px;
            }

            .floating-notification.notice-b {
                left: -11px;
            }

            .metric-strip {
                grid-template-columns: 1fr;
            }

            .metric-box {
                padding: 11px 13px;
            }

            .process-stepper {
                margin-top: 30px;
                padding: 0 8px;
            }

            .stepper-track {
                left: 53px;
                right: 53px;
            }

            .stepper-caption {
                display: none;
            }

            .section-header {
                align-items: flex-start;

                margin-top: 31px;
            }

            .section-header-copy h2 {
                font-size: 1.3rem;
            }

            .section-header-copy p {
                max-width: 250px;
            }

            .plans-grid {
                grid-template-columns: minmax(0, 1fr);

                max-width: 430px;
                margin-left: auto;
                margin-right: auto;
            }

            .plan-card {
                min-height: 0;
                padding: 21px 18px;

                border-radius: 22px;
            }

            .plan-description {
                min-height: 0;
            }

            .plan-price {
                font-size: 3rem;
            }

            .selected-plan-inner {
                align-items: flex-start;
            }

            .selected-plan-copy span {
                white-space: normal;
            }

            .checkout-card,
            .history-card {
                border-radius: 22px;
            }

            .summary-inner {
                display: flex;
                align-items: flex-start;
                gap: 0;
            }

            .checkout-summary {
                padding: 24px 19px;
            }

            .summary-plan-icon {
                margin-top: 24px;
            }

            .summary-plan-name {
                margin-top: 16px;
            }

            .summary-price-row {
                margin-top: 19px;
            }

            .summary-points {
                display: grid;
                grid-template-columns: 1fr;
                gap: 8px;

                margin-top: 21px;
            }

            .summary-security {
                margin-top: 0;
                padding-top: 22px;
            }

            .checkout-form-area {
                padding: 24px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-field.full-width {
                grid-column: auto;
            }

            .history-header {
                padding: 18px 16px;
            }

            .history-title-group p {
                max-width: 190px;
            }

            /*
            |--------------------------------------------------------------------------
            | Mobile Responsive Table Cards
            |--------------------------------------------------------------------------
            */

            .table-responsive {
                overflow: visible;
            }

            .clean-table {
                min-width: 0;
            }

            .clean-table thead {
                display: none;
            }

            .clean-table,
            .clean-table tbody,
            .clean-table tr,
            .clean-table td {
                display: block;
                width: 100%;
            }

            .clean-table tbody {
                padding: 11px;
            }

            .clean-table tr {
                overflow: hidden;

                margin-bottom: 10px;

                border: 1px solid var(--border);
                border-radius: 15px;

                background: var(--surface-strong);
                box-shadow: var(--shadow-xs);
            }

            .clean-table tr:last-child {
                margin-bottom: 0;
            }

            .clean-table td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;

                padding: 11px 12px;

                text-align: right;

                border-bottom: 1px solid rgba(74, 42, 104, 0.065);
            }

            .clean-table td::before {
                content: attr(data-label);

                color: var(--text-muted);
                font-size: 0.58rem;
                font-weight: 850;
                letter-spacing: 0.03em;
                text-align: left;
                text-transform: uppercase;
            }

            .clean-table td:last-child {
                border-bottom: 0;
            }

            .panel-domain-link {
                max-width: 210px;
            }

            .page-footer {
                padding: 0 12px;

                line-height: 1.5;
            }
        }

        /* ================================================================
           SMALL MOBILE
        ================================================================= */

        @media (max-width: 420px) {
            .brand-mark {
                width: 40px;
                height: 40px;
                flex-basis: 40px;

                border-radius: 13px;
            }

            .brand-name {
                font-size: 0.86rem;
            }

            .help-button {
                display: none;
            }

            .system-status {
                max-width: 143px;
            }

            .hero-section {
                padding: 24px 14px;
            }

            .hero-eyebrow {
                font-size: 0.58rem;
            }

            .hero-title {
                font-size: clamp(2.05rem, 13.1vw, 2.85rem);
            }

            .hero-feature {
                flex: 1 1 calc(50% - 7px);
                justify-content: center;
            }

            .dashboard-window {
                width: 87%;
                padding: 12px;
            }

            .floating-notification {
                font-size: 0.54rem;
            }

            .section-header {
                gap: 9px;
            }

            .section-chip span {
                display: none;
            }

            .plan-card {
                padding: 20px 16px;
            }

            .api-rule {
                padding: 10px;
            }

            .selected-plan-inner {
                padding: 10px;
            }

            .selected-plan-price span {
                display: none;
            }

            .checkout-form-header {
                display: block;
            }

            .secure-badge {
                width: fit-content;
                margin-top: 10px;
            }

            .checkout-form-area {
                padding: 22px 14px;
            }

            .domain-prefix {
                display: none;
            }

            .domain-preview {
                display: block;
            }

            .domain-preview-value {
                margin-top: 3px;
                text-align: left;
            }

            .history-count span {
                display: none;
            }

            .clean-table td {
                align-items: flex-start;
            }

            .panel-domain-link {
                max-width: 190px;
            }
        }

        /* ================================================================
           REDUCED MOTION
        ================================================================= */

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            .reveal {
                opacity: 1;
                transform: none;
            }
        }

        /* ================================================================
           APPLE macOS + iOS REDESIGN OVERRIDES
           Visual layer only — existing data and functionality preserved.
        ================================================================= */

        :root {
            color-scheme: light;

            --page-bg: #f5f5f7;
            --page-bg-soft: #fbfbfd;

            --surface: rgba(255, 255, 255, 0.82);
            --surface-strong: #ffffff;
            --surface-soft: #f7f7f9;
            --surface-purple: #f2f7ff;

            --purple-25: #fbfdff;
            --purple-50: #f5f9ff;
            --purple-100: #eaf3ff;
            --purple-150: #deedff;
            --purple-200: #cfe4ff;
            --purple-300: #a8ceff;
            --purple-400: #70adf7;
            --purple-500: #2788e8;
            --purple-600: #0071e3;
            --purple-700: #0064c8;
            --purple-800: #0055aa;
            --purple-900: #003f80;

            --primary: #0071e3;
            --primary-light: #2997ff;
            --primary-dark: #0058b0;

            --text-main: #1d1d1f;
            --text-secondary: #515154;
            --text-muted: #6e6e73;
            --text-light: #98989d;

            --border: rgba(29, 29, 31, 0.10);
            --border-medium: rgba(29, 29, 31, 0.16);
            --border-strong: rgba(0, 113, 227, 0.42);

            --success: #248a3d;
            --success-bg: #edf8ef;
            --warning: #b25000;
            --warning-bg: #fff5e8;
            --danger: #d70015;
            --danger-bg: #fff0f1;
            --info: #0071e3;
            --info-bg: #f0f7ff;

            --shadow-xs:
                0 1px 2px rgba(0, 0, 0, 0.04);

            --shadow-sm:
                0 4px 16px rgba(0, 0, 0, 0.06);

            --shadow-md:
                0 12px 36px rgba(0, 0, 0, 0.08);

            --shadow-lg:
                0 24px 70px rgba(0, 0, 0, 0.12);

            --shadow-purple:
                0 14px 34px rgba(0, 113, 227, 0.20);

            --radius-xs: 10px;
            --radius-sm: 14px;
            --radius-md: 18px;
            --radius-lg: 22px;
            --radius-xl: 30px;

            --container-width: 1180px;

            --ease-smooth:
                cubic-bezier(0.25, 0.1, 0.25, 1);

            --ease-bounce:
                cubic-bezier(0.2, 0.8, 0.2, 1);

            --motion-fast: 160ms;
            --motion-standard: 200ms;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        html {
            background: var(--page-bg);
            scroll-padding-top: 88px;
        }

        body {
            color: var(--text-main);

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "SF Pro Display",
                "SF Pro Text",
                "Helvetica Neue",
                Helvetica,
                Arial,
                sans-serif;

            font-size: 16px;
            line-height: 1.5;
            letter-spacing: -0.011em;

            background:
                radial-gradient(
                    circle at 50% -15%,
                    rgba(255, 255, 255, 0.98) 0,
                    rgba(255, 255, 255, 0) 42%
                ),
                linear-gradient(
                    180deg,
                    #fbfbfd 0%,
                    #f5f5f7 38%,
                    #f5f5f7 100%
                );
        }

        body.menu-locked {
            overflow: hidden;
        }

        button,
        input,
        select,
        textarea {
            max-width: 100%;
            color: inherit;
        }

        button,
        [role="button"],
        a,
        input,
        select,
        textarea {
            touch-action: manipulation;
        }

        img,
        svg,
        video,
        canvas {
            max-width: 100%;
            height: auto;
        }

        :where(
            a,
            button,
            input,
            select,
            textarea,
            [role="button"],
            [tabindex]
        ):focus-visible {
            outline: 3px solid rgba(0, 113, 227, 0.48);
            outline-offset: 3px;
        }

        ::selection {
            color: #ffffff;
            background: var(--primary);
        }

        .ambient-background {
            display: none;
        }

        .page-shell {
            width: min(100%, var(--container-width));
            max-width: 100%;
            margin: 0 auto;

            padding:
                max(12px, env(safe-area-inset-top))
                clamp(14px, 3vw, 28px)
                max(48px, env(safe-area-inset-bottom));
        }

        .page-shell > *,
        .hero-section > *,
        .checkout-card > *,
        .form-grid > *,
        .plans-grid > *,
        .metric-strip > * {
            min-width: 0;
            max-width: 100%;
        }

        .reveal {
            opacity: 0;
            transform: translateY(8px);

            transition:
                opacity var(--motion-standard) ease,
                transform var(--motion-standard) ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-delay-1,
        .reveal-delay-2,
        .reveal-delay-3 {
            transition-delay: 0ms;
        }

        /* Top navigation */

        .top-navigation {
            position: sticky;
            top: max(10px, env(safe-area-inset-top));
            z-index: 50;

            min-height: 58px;
            margin-bottom: 18px;
            padding: 8px 10px;

            border: 1px solid rgba(29, 29, 31, 0.09);
            border-radius: 18px;

            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: saturate(180%) blur(24px);
            -webkit-backdrop-filter: saturate(180%) blur(24px);

            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.72) inset,
                0 8px 28px rgba(0, 0, 0, 0.06);
        }

        .brand-group {
            gap: 10px;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            flex: 0 0 40px;

            font-size: 0.94rem;
            border-radius: 12px;

            background:
                linear-gradient(180deg, #2997ff 0%, #0071e3 100%);

            box-shadow:
                0 5px 14px rgba(0, 113, 227, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.42);
        }

        .brand-mark::after {
            inset: 1px;
            border-color: rgba(255, 255, 255, 0.24);
            border-radius: 11px;
        }

        .brand-name {
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 650;
            letter-spacing: -0.025em;
        }

        .brand-subtitle {
            margin-top: 0;
            color: var(--text-muted);
            font-size: 0.68rem;
            font-weight: 500;
        }

        .navigation-actions {
            min-width: 0;
            gap: 8px;
        }

        .system-status {
            min-width: 0;
            max-width: 220px;
            min-height: 36px;
            padding: 7px 11px;

            color: var(--text-secondary);
            font-size: 0.72rem;
            font-weight: 560;

            border: 1px solid var(--border);
            background: rgba(245, 245, 247, 0.82);
            box-shadow: none;
        }

        .system-status span:last-child {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            flex-basis: 7px;
            background: #30d158;
            box-shadow: 0 0 0 3px rgba(48, 209, 88, 0.14);
        }

        .status-dot::after {
            display: none;
        }

        .help-button {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;

            color: var(--text-secondary);
            border-color: var(--border);
            border-radius: 50%;
            background: rgba(245, 245, 247, 0.90);

            transition:
                color var(--motion-fast) ease,
                border-color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                transform var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease;
        }

        .help-button:hover {
            color: var(--primary);
            border-color: rgba(0, 113, 227, 0.20);
            background: #ffffff;
            transform: translateY(-1px) scale(1.01);
            box-shadow: var(--shadow-sm);
        }

        .help-button:active {
            transform: translateY(0) scale(0.99);
            background: #ececf0;
        }

        /* Hero */

        .hero-section {
            isolation: isolate;
            grid-template-columns:
                minmax(0, 1.18fr)
                minmax(290px, 0.82fr);
            gap: clamp(28px, 5vw, 58px);

            padding: clamp(32px, 6vw, 68px);

            border: 1px solid rgba(29, 29, 31, 0.09);
            border-radius: 32px;

            background:
                linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.98),
                    rgba(250, 250, 252, 0.96)
                );

            backdrop-filter: none;
            -webkit-backdrop-filter: none;

            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.92) inset,
                0 18px 50px rgba(0, 0, 0, 0.07);
        }

        .hero-section::before {
            width: 440px;
            height: 440px;
            top: -260px;
            right: -90px;

            background:
                radial-gradient(
                    circle,
                    rgba(41, 151, 255, 0.15),
                    rgba(41, 151, 255, 0) 68%
                );
        }

        .hero-section::after {
            width: 300px;
            height: 300px;
            left: -180px;
            bottom: -210px;

            background:
                radial-gradient(
                    circle,
                    rgba(0, 113, 227, 0.08),
                    rgba(0, 113, 227, 0) 70%
                );
        }

        .hero-eyebrow {
            margin-bottom: 18px;
            padding: 7px 11px;

            color: var(--primary-dark);
            font-size: 0.72rem;
            font-weight: 650;
            letter-spacing: 0.01em;
            text-transform: none;

            border: 1px solid rgba(0, 113, 227, 0.13);
            background: rgba(234, 243, 255, 0.82);
        }

        .hero-title {
            max-width: 660px;
            color: var(--text-main);

            font-size: clamp(2.6rem, 6vw, 4.65rem);
            line-height: 0.98;
            font-weight: 720;
            letter-spacing: -0.065em;
        }

        .hero-title-gradient {
            color: transparent;
            background:
                linear-gradient(100deg, #0071e3, #2997ff 58%, #0058b0);
            background-clip: text;
            -webkit-background-clip: text;
            background-size: 100% 100%;
            animation: none;
        }

        .hero-description {
            max-width: 610px;
            margin-top: 20px;

            color: var(--text-secondary);
            font-size: clamp(0.98rem, 1.7vw, 1.12rem);
            line-height: 1.65;
            letter-spacing: -0.015em;
        }

        .hero-actions {
            gap: 8px;
            margin-top: 26px;
        }

        .hero-feature {
            min-height: 36px;
            padding: 8px 11px;

            color: var(--text-secondary);
            font-size: 0.74rem;
            font-weight: 560;

            border-color: var(--border);
            border-radius: 999px;
            background: rgba(245, 245, 247, 0.90);
        }

        .hero-feature i {
            color: var(--primary);
        }

        /* macOS window illustration */

        .hero-dashboard {
            min-height: 270px;
        }

        .dashboard-window {
            width: min(100%, 350px);
            padding: 13px;

            border: 1px solid rgba(29, 29, 31, 0.12);
            border-radius: 22px;

            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: saturate(160%) blur(24px);
            -webkit-backdrop-filter: saturate(160%) blur(24px);

            box-shadow:
                0 26px 60px rgba(0, 0, 0, 0.14),
                0 1px 0 rgba(255, 255, 255, 0.90) inset;

            transform: none;
            animation: none;
        }

        .window-toolbar {
            min-height: 22px;
            margin-bottom: 10px;
            padding: 0 2px;
        }

        .window-dot {
            width: 10px;
            height: 10px;
        }

        .window-online {
            color: var(--success);
            font-size: 0.66rem;
            font-weight: 650;
        }

        .dashboard-main-card {
            padding: 20px;
            border-radius: 16px;

            background:
                linear-gradient(145deg, #2997ff 0%, #0071e3 52%, #0058b0 100%);

            box-shadow:
                0 16px 34px rgba(0, 113, 227, 0.24),
                inset 0 1px 0 rgba(255, 255, 255, 0.24);
        }

        .dashboard-main-card::after {
            border-color: rgba(255, 255, 255, 0.11);
        }

        .dashboard-label,
        .dashboard-change {
            color: rgba(255, 255, 255, 0.78);
        }

        .dashboard-label {
            font-size: 0.69rem;
            font-weight: 560;
        }

        .dashboard-value {
            margin-top: 6px;
            font-size: 2.2rem;
            font-weight: 720;
        }

        .dashboard-change {
            margin-top: 9px;
            font-size: 0.67rem;
            font-weight: 550;
        }

        .dashboard-mini-grid {
            gap: 9px;
            margin-top: 10px;
        }

        .dashboard-mini-card {
            padding: 13px;
            border-color: var(--border);
            border-radius: 14px;
            background: rgba(245, 245, 247, 0.88);
        }

        .mini-card-icon {
            width: 30px;
            height: 30px;
            color: var(--primary);
            border-radius: 9px;
            background: #ffffff;
            box-shadow: var(--shadow-xs);
        }

        .mini-card-value {
            margin-top: 9px;
            color: var(--text-main);
            font-size: 0.84rem;
            font-weight: 650;
        }

        .mini-card-label {
            color: var(--text-muted);
            font-size: 0.62rem;
            font-weight: 500;
        }

        .floating-notification {
            padding: 9px 11px;
            color: var(--text-secondary);
            font-size: 0.66rem;
            font-weight: 620;

            border-color: var(--border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.90);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.10);

            animation: none !important;
            transform: none !important;
        }

        .floating-notification.notice-a {
            top: 12px;
            right: -14px;
        }

        .floating-notification.notice-b {
            bottom: 21px;
            left: -14px;
        }

        /* Metrics */

        .metric-strip {
            gap: 12px;
            margin-top: 14px;
        }

        .metric-box {
            gap: 12px;
            padding: 16px;

            border-color: var(--border);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: saturate(160%) blur(20px);
            -webkit-backdrop-filter: saturate(160%) blur(20px);
            box-shadow: var(--shadow-xs);

            transition:
                border-color var(--motion-standard) ease,
                transform var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                background-color var(--motion-standard) ease;
        }

        .metric-box:hover {
            border-color: rgba(0, 113, 227, 0.18);
            background: #ffffff;
            transform: translateY(-2px) scale(1.01);
            box-shadow: var(--shadow-sm);
        }

        .metric-box:active {
            transform: translateY(0) scale(0.99);
        }

        .metric-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;

            color: var(--primary);
            border-radius: 12px;
            background: var(--purple-100);
        }

        .metric-value {
            color: var(--text-main);
            font-size: 0.88rem;
            font-weight: 650;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 0.69rem;
            font-weight: 500;
        }

        /* Stepper */

        .process-stepper {
            max-width: 760px;
            margin: 42px auto 30px;
            padding: 0 34px;
        }

        .stepper-track {
            top: 20px;
            left: 74px;
            right: 74px;
            height: 2px;
            background: #d9d9de;
        }

        .stepper-progress {
            background: var(--primary);
            box-shadow: none;
            transition: width 200ms ease;
        }

        .stepper-item {
            color: var(--text-muted);
            transition: color var(--motion-standard) ease;
        }

        .stepper-circle {
            width: 40px;
            height: 40px;

            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 650;

            border-color: rgba(29, 29, 31, 0.14);
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 2px 7px rgba(0, 0, 0, 0.05);

            transition:
                color var(--motion-standard) ease,
                border-color var(--motion-standard) ease,
                background-color var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                transform var(--motion-standard) ease;
        }

        .stepper-label {
            margin-top: 9px;
            color: inherit;
            font-size: 0.74rem;
            font-weight: 620;
        }

        .stepper-caption {
            margin-top: 2px;
            font-size: 0.64rem;
            font-weight: 500;
        }

        .stepper-item.active .stepper-circle,
        .stepper-item.completed .stepper-circle {
            color: #ffffff;
            border-color: var(--primary);
            background: var(--primary);
            box-shadow: 0 5px 14px rgba(0, 113, 227, 0.20);
            transform: scale(1.01);
        }

        /* Section headings */

        .section-header {
            align-items: flex-end;
            gap: 18px;
            margin: 42px 2px 18px;
        }

        .section-header-copy h2 {
            color: var(--text-main);
            font-size: clamp(1.55rem, 3.2vw, 2.15rem);
            line-height: 1.12;
            font-weight: 680;
            letter-spacing: -0.045em;
        }

        .section-header-copy p {
            max-width: 640px;
            margin-top: 7px;
            color: var(--text-muted);
            font-size: 0.86rem;
            line-height: 1.58;
        }

        .section-chip {
            min-height: 34px;
            padding: 7px 11px;

            color: var(--primary);
            font-size: 0.70rem;
            font-weight: 620;

            border-color: rgba(0, 113, 227, 0.14);
            border-radius: 999px;
            background: var(--purple-50);
        }

        /* Plan cards */

        .plans-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            width: min(100%, 930px);
            max-width: 100%;
            margin: 0 auto 26px;
        }

        .plan-card {
            min-height: 480px;
            padding: clamp(22px, 3vw, 30px);

            border-color: var(--border);
            border-radius: 26px;

            background:
                linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);

            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.95) inset,
                0 10px 32px rgba(0, 0, 0, 0.06);

            transition:
                transform var(--motion-standard) ease,
                border-color var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                background-color var(--motion-standard) ease;
        }

        .plan-card::before {
            display: none;
        }

        .plan-card::after {
            width: 240px;
            height: 240px;
            right: -150px;
            bottom: -160px;
            background:
                radial-gradient(
                    circle,
                    rgba(0, 113, 227, 0.08),
                    rgba(0, 113, 227, 0) 70%
                );
            transform: none;
            opacity: 0.8;
        }

        .plan-card:hover {
            border-color: rgba(0, 113, 227, 0.24);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.96) inset,
                0 18px 44px rgba(0, 0, 0, 0.09);
            transform: translateY(-2px) scale(1.01);
        }

        .plan-card:hover::after {
            transform: none;
        }

        .plan-card:active {
            transform: translateY(0) scale(0.99);
            background: #f7f7f9;
        }

        .plan-card:focus-visible {
            border-color: var(--primary);
            outline: 3px solid rgba(0, 113, 227, 0.34);
            outline-offset: 3px;
            box-shadow: var(--shadow-md);
        }

        .plan-card.active-card {
            border-color: var(--primary);
            background:
                linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
            box-shadow:
                0 0 0 3px rgba(0, 113, 227, 0.10),
                0 18px 46px rgba(0, 78, 156, 0.12);
            transform: translateY(-1px) scale(1.005);
        }

        .plan-top-row {
            margin-bottom: 22px;
        }

        .plan-icon-group {
            min-width: 0;
            gap: 10px;
        }

        .plan-icon {
            width: 52px;
            height: 52px;
            flex-basis: 52px;

            color: var(--primary);
            font-size: 1rem;

            border-color: rgba(0, 113, 227, 0.10);
            border-radius: 15px;
            background: var(--purple-100);

            transition:
                color var(--motion-standard) ease,
                background-color var(--motion-standard) ease,
                transform var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease;
        }

        .plan-card:hover .plan-icon {
            transform: translateY(-1px) scale(1.01);
        }

        .plan-card.active-card .plan-icon {
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 8px 20px rgba(0, 113, 227, 0.20);
        }

        .plan-badge {
            max-width: 100%;
            padding: 6px 9px;

            color: var(--primary-dark);
            font-size: 0.62rem;
            font-weight: 650;
            letter-spacing: 0.01em;

            border-color: rgba(0, 113, 227, 0.12);
            background: var(--purple-50);
        }

        .plan-badge.recommended {
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 6px 15px rgba(0, 113, 227, 0.18);
        }

        .plan-selection {
            width: 28px;
            height: 28px;
            flex-basis: 28px;

            border-width: 1.5px;
            border-color: #c7c7cc;
            background: #ffffff;
        }

        .plan-card.active-card .plan-selection {
            border-color: var(--primary);
            background: var(--primary);
        }

        .plan-name {
            color: var(--text-main);
            font-size: 1.5rem;
            font-weight: 680;
            letter-spacing: -0.04em;
        }

        .plan-description {
            min-height: 48px;
            margin-top: 8px;
            color: var(--text-secondary);
            font-size: 0.84rem;
            line-height: 1.6;
        }

        .plan-price-row {
            gap: 6px;
            margin-top: 22px;
            padding-bottom: 20px;
            border-color: var(--border);
        }

        .price-symbol {
            color: var(--text-main);
            font-size: 1rem;
            font-weight: 650;
        }

        .plan-price {
            color: var(--text-main);
            font-size: clamp(2.8rem, 5vw, 3.7rem);
            font-weight: 720;
            letter-spacing: -0.065em;
        }

        .price-period {
            color: var(--text-muted);
            font-size: 0.74rem;
            font-weight: 520;
        }

        .api-rule {
            gap: 11px;
            margin-top: 19px;
            padding: 13px;

            border-color: var(--border);
            border-radius: 15px;
            background: #f5f5f7;
        }

        .api-rule-icon {
            width: 32px;
            height: 32px;
            flex-basis: 32px;
            color: var(--primary);
            border-radius: 10px;
            background: #ffffff;
            box-shadow: var(--shadow-xs);
        }

        .api-rule strong {
            color: var(--text-main);
            font-size: 0.75rem;
            font-weight: 640;
        }

        .api-rule span {
            color: var(--text-muted);
            font-size: 0.69rem;
            line-height: 1.5;
        }

        .plan-features {
            gap: 11px;
            margin-top: 19px;
        }

        .plan-features li {
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-weight: 520;
            line-height: 1.45;
        }

        .feature-check {
            width: 20px;
            height: 20px;
            flex-basis: 20px;
            color: var(--primary);
            border-radius: 50%;
            background: var(--purple-100);
        }

        .plan-footer {
            padding-top: 24px;
        }

        .plan-footer-label {
            color: var(--text-main);
            font-size: 0.76rem;
            font-weight: 630;
        }

        .plan-action {
            width: 36px;
            height: 36px;
            color: var(--primary);
            border-color: rgba(0, 113, 227, 0.13);
            border-radius: 50%;
            background: var(--purple-50);

            transition:
                color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                transform var(--motion-fast) ease;
        }

        .plan-card:hover .plan-action {
            color: #ffffff;
            background: var(--primary);
            transform: translateY(-1px) scale(1.01);
        }

        .plan-card.active-card .plan-action {
            color: #ffffff;
            background: var(--primary);
        }

        /* Selected plan */

        .selected-plan-bar {
            width: min(100%, 930px);
            max-width: 100%;
            margin-inline: auto;
            transform: translateY(6px);

            transition:
                grid-template-rows var(--motion-standard) ease,
                opacity var(--motion-standard) ease,
                visibility var(--motion-standard) ease,
                transform var(--motion-standard) ease;
        }

        .selected-plan-inner {
            padding: 13px 15px;
            border-color: rgba(0, 113, 227, 0.14);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: saturate(160%) blur(18px);
            -webkit-backdrop-filter: saturate(160%) blur(18px);
            box-shadow: var(--shadow-sm);
        }

        .selected-plan-icon {
            width: 38px;
            height: 38px;
            flex-basis: 38px;
            border-radius: 12px;
            background: var(--primary);
            box-shadow: 0 8px 20px rgba(0, 113, 227, 0.18);
        }

        .selected-plan-copy strong {
            color: var(--text-main);
            font-size: 0.78rem;
            font-weight: 650;
        }

        .selected-plan-copy span {
            color: var(--text-muted);
            font-size: 0.68rem;
        }

        .selected-plan-price {
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 620;
        }

        .selected-plan-price strong {
            border-radius: 999px;
            background: var(--primary);
        }

        /* Checkout */

        .checkout-section {
            transform: translateY(8px);

            transition:
                grid-template-rows var(--motion-standard) ease,
                opacity var(--motion-standard) ease,
                visibility var(--motion-standard) ease,
                transform var(--motion-standard) ease;
        }

        .checkout-card {
            grid-template-columns:
                minmax(250px, 0.70fr)
                minmax(0, 1.30fr);

            border-color: var(--border);
            border-radius: 28px;
            background: #ffffff;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: var(--shadow-md);
        }

        .checkout-summary {
            padding: clamp(26px, 4vw, 38px);
            color: #ffffff;
            background:
                radial-gradient(
                    circle at 95% 5%,
                    rgba(41, 151, 255, 0.30),
                    rgba(41, 151, 255, 0) 38%
                ),
                linear-gradient(155deg, #2c2c2e 0%, #1d1d1f 62%, #111113 100%);
        }

        .checkout-summary::before,
        .checkout-summary::after {
            border-color: rgba(255, 255, 255, 0.055);
        }

        .summary-badge {
            padding: 7px 10px;
            font-size: 0.64rem;
            font-weight: 620;
            letter-spacing: 0.01em;
            text-transform: none;
            border-color: rgba(255, 255, 255, 0.14);
            background: rgba(255, 255, 255, 0.08);
        }

        .summary-plan-icon {
            width: 54px;
            height: 54px;
            margin-top: 31px;
            border-color: rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.10);
            box-shadow: none;
        }

        .summary-plan-name {
            margin-top: 19px;
            font-size: 1.65rem;
            font-weight: 680;
            letter-spacing: -0.045em;
        }

        .summary-description {
            margin-top: 8px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 0.78rem;
            line-height: 1.6;
        }

        .summary-price-row {
            margin-top: 24px;
        }

        .summary-price-symbol {
            font-weight: 620;
        }

        .summary-price-value {
            font-size: 3.25rem;
            font-weight: 720;
        }

        .summary-price-period {
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.70rem;
            font-weight: 500;
        }

        .summary-points {
            gap: 10px;
            margin-top: 28px;
        }

        .summary-points li {
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.72rem;
            font-weight: 520;
        }

        .summary-points i {
            width: 20px;
            height: 20px;
            color: #ffffff;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.10);
        }

        .summary-security {
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.66rem;
            font-weight: 500;
        }

        .checkout-form-area {
            min-width: 0;
            padding: clamp(24px, 4vw, 40px);
        }

        .checkout-form-header {
            gap: 16px;
            margin-bottom: 26px;
            padding-bottom: 20px;
            border-color: var(--border);
        }

        .checkout-form-title h3 {
            color: var(--text-main);
            font-size: 1.3rem;
            font-weight: 680;
            letter-spacing: -0.04em;
        }

        .checkout-form-title p {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .secure-badge {
            min-height: 33px;
            padding: 7px 10px;
            color: var(--success);
            font-size: 0.66rem;
            font-weight: 620;
            border-color: rgba(36, 138, 61, 0.14);
            border-radius: 999px;
            background: var(--success-bg);
        }

        .form-grid {
            gap: 16px;
        }

        .form-field {
            min-width: 0;
            margin-bottom: 17px;
        }

        .field-label-row {
            margin-bottom: 8px;
        }

        .field-label {
            color: var(--text-main);
            font-size: 0.75rem;
            font-weight: 620;
        }

        .field-label i {
            color: var(--primary);
        }

        .field-required {
            color: var(--text-light);
            font-size: 0.62rem;
            font-weight: 520;
        }

        .input-shell {
            min-height: 50px;
            border-color: rgba(29, 29, 31, 0.12);
            border-radius: 12px;
            background: #f5f5f7;

            transition:
                border-color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease,
                transform var(--motion-fast) ease;
        }

        .input-shell:hover {
            border-color: rgba(29, 29, 31, 0.20);
            background: #f8f8fa;
            transform: translateY(-1px);
        }

        .input-shell:focus-within {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.14);
            transform: translateY(-1px);
        }

        .input-icon {
            width: 44px;
            height: 48px;
            flex-basis: 44px;
            color: var(--text-light);
        }

        .apple-input {
            height: 48px;
            color: var(--text-main);
            font-size: 0.84rem;
            font-weight: 520;
        }

        .apple-input::placeholder {
            color: #a1a1a6;
            opacity: 1;
        }

        select.apple-input {
            background-position:
                calc(100% - 18px) 21px,
                calc(100% - 13px) 21px;
        }

        .domain-prefix {
            color: var(--text-muted);
            font-size: 0.76rem;
            font-weight: 500;
        }

        .domain-suffix {
            height: 32px;
            margin-right: 8px;
            padding: 0 10px;
            color: var(--text-secondary);
            font-size: 0.72rem;
            font-weight: 600;
            border-color: var(--border);
            border-radius: 8px;
            background: #ffffff;
        }

        .field-help {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 0.66rem;
            line-height: 1.48;
        }

        .password-toggle {
            width: 38px;
            height: 38px;
            flex-basis: 38px;
            color: var(--text-muted);
            border-radius: 10px;

            transition:
                color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                transform var(--motion-fast) ease;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: var(--purple-100);
            transform: scale(1.01);
        }

        .password-toggle:active {
            transform: scale(0.99);
        }

        .domain-preview {
            margin-top: 9px;
            padding: 10px 11px;
            border-color: rgba(0, 113, 227, 0.10);
            border-radius: 11px;
            background: var(--purple-50);
        }

        .domain-preview-label {
            color: var(--text-muted);
            font-size: 0.64rem;
            font-weight: 560;
        }

        .domain-preview-value {
            max-width: 70%;
            color: var(--primary-dark);
            font-size: 0.68rem;
            font-weight: 620;
        }

        .strength-header span,
        .strength-header strong {
            font-size: 0.63rem;
            font-weight: 560;
        }

        .strength-segment {
            background: #dedee3;
        }

        .checkout-notice {
            gap: 10px;
            padding: 13px;
            border-color: rgba(0, 113, 227, 0.10);
            border-radius: 14px;
            background: var(--info-bg);
        }

        .checkout-notice-icon {
            width: 30px;
            height: 30px;
            flex-basis: 30px;
            color: var(--primary);
            border-radius: 9px;
        }

        .checkout-notice strong {
            color: var(--text-main);
            font-size: 0.70rem;
            font-weight: 640;
        }

        .checkout-notice span {
            color: var(--text-muted);
            font-size: 0.65rem;
            line-height: 1.5;
        }

        .action-button {
            min-height: 52px;
            margin-top: 19px;
            padding: 13px 19px;

            color: #ffffff;
            font-size: 0.84rem;
            font-weight: 640;

            border-radius: 14px;
            background: var(--primary);
            background-size: 100% 100%;

            box-shadow:
                0 10px 22px rgba(0, 113, 227, 0.20),
                inset 0 1px 0 rgba(255, 255, 255, 0.24);

            transition:
                transform var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                opacity var(--motion-fast) ease;
        }

        .action-button::before {
            display: none;
        }

        .action-button:hover {
            background: #0077ed;
            transform: translateY(-2px) scale(1.01);
            box-shadow:
                0 14px 28px rgba(0, 113, 227, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.28);
        }

        .action-button:active {
            background: #0067cf;
            transform: translateY(0) scale(0.99);
        }

        .action-button:focus-visible {
            outline: 3px solid rgba(0, 113, 227, 0.42);
            outline-offset: 3px;
            box-shadow: 0 10px 24px rgba(0, 113, 227, 0.22);
        }

        .action-price {
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
        }

        /* History */

        .history-section {
            margin-top: 42px;
        }

        .history-card {
            overflow: hidden;
            border-color: var(--border);
            border-radius: 28px;
            background: #ffffff;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: var(--shadow-md);
        }

        .history-header {
            padding: 22px 24px;
            border-color: var(--border);
            background: #fbfbfd;
        }

        .history-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            color: var(--primary);
            border-color: rgba(0, 113, 227, 0.10);
            border-radius: 12px;
            background: var(--purple-100);
        }

        .history-title-group h2 {
            color: var(--text-main);
            font-size: 1.04rem;
            font-weight: 660;
            letter-spacing: -0.03em;
        }

        .history-title-group p {
            color: var(--text-muted);
            font-size: 0.69rem;
        }

        .history-count {
            min-height: 32px;
            padding: 7px 10px;
            color: var(--primary);
            font-size: 0.68rem;
            font-weight: 620;
            border-color: rgba(0, 113, 227, 0.12);
            border-radius: 999px;
            background: var(--purple-50);
        }

        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .clean-table {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            table-layout: fixed;
        }

        .clean-table th,
        .clean-table td {
            min-width: 0;
            max-width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .clean-table th {
            padding: 13px 15px;
            color: var(--text-muted);
            font-size: 0.63rem;
            font-weight: 620;
            letter-spacing: 0.04em;
            border-color: var(--border);
            background: #f5f5f7;
        }

        .clean-table td {
            padding: 15px;
            color: var(--text-secondary);
            font-size: 0.76rem;
            border-color: rgba(29, 29, 31, 0.075);
        }

        .clean-table tbody tr {
            transition:
                background-color var(--motion-fast) ease,
                transform var(--motion-fast) ease;
        }

        .clean-table tbody tr:hover {
            background: #fbfbfd;
        }

        .panel-domain-link,
        .panel-type-pill,
        .panel-price-cell,
        .status-pill,
        .details-button {
            max-width: 100%;
        }

        .panel-domain-link {
            color: var(--primary);
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .domain-link-text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .domain-link-icon {
            color: var(--primary);
            background: var(--purple-100);
        }

        .panel-type-pill {
            color: var(--text-secondary);
            border-color: var(--border);
            background: #f5f5f7;
        }

        .status-pill {
            font-size: 0.65rem;
            font-weight: 620;
        }

        .status-pill::before {
            box-shadow: none;
        }

        .details-button {
            min-height: 34px;
            padding: 7px 10px;
            color: var(--primary);
            font-size: 0.65rem;
            font-weight: 620;
            border-color: rgba(0, 113, 227, 0.14);
            border-radius: 999px;
            background: var(--purple-50);

            transition:
                color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                border-color var(--motion-fast) ease,
                transform var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease;
        }

        .details-button:hover {
            color: #ffffff;
            border-color: var(--primary);
            background: var(--primary);
            box-shadow: 0 8px 18px rgba(0, 113, 227, 0.18);
            transform: translateY(-2px) scale(1.01);
        }

        .details-button:active {
            transform: translateY(0) scale(0.99);
        }

        .empty-state {
            min-height: 260px;
            padding: 38px 20px;
        }

        .empty-icon {
            width: 68px;
            height: 68px;
            color: var(--primary);
            border-color: rgba(0, 113, 227, 0.11);
            border-radius: 20px;
            background: var(--purple-100);
            box-shadow: var(--shadow-sm);
        }

        .empty-icon::after {
            display: none;
        }

        .empty-state h3 {
            margin-top: 20px;
            color: var(--text-main);
            font-size: 1rem;
            font-weight: 660;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 0.76rem;
        }

        .page-footer {
            margin-top: 28px;
            color: var(--text-muted);
            font-size: 0.68rem;
            font-weight: 500;
        }

        /* Dialogs */

        .swal2-popup {
            width: min(calc(100vw - 28px), 430px) !important;
            max-width: 100% !important;
            padding: 28px !important;

            border: 1px solid var(--border) !important;
            border-radius: 24px !important;

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "SF Pro Text",
                "Helvetica Neue",
                Helvetica,
                Arial,
                sans-serif !important;

            background: rgba(255, 255, 255, 0.94) !important;
            backdrop-filter: saturate(180%) blur(24px) !important;
            -webkit-backdrop-filter: saturate(180%) blur(24px) !important;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.20) !important;
        }

        .swal2-title {
            color: var(--text-main) !important;
            font-size: 1.28rem !important;
            font-weight: 680 !important;
            letter-spacing: -0.04em !important;
        }

        .swal2-html-container {
            color: var(--text-secondary) !important;
            font-size: 0.82rem !important;
        }

        .swal2-confirm,
        .swal2-cancel {
            min-height: 42px !important;
            padding: 9px 17px !important;
            font-size: 0.76rem !important;
            font-weight: 620 !important;
            border-radius: 999px !important;

            transition:
                transform var(--motion-fast) ease,
                filter var(--motion-fast) ease !important;
        }

        .swal2-confirm {
            background: var(--primary) !important;
        }

        .swal2-confirm:hover,
        .swal2-cancel:hover {
            transform: translateY(-1px) scale(1.01);
        }

        .swal2-confirm:active,
        .swal2-cancel:active {
            transform: translateY(0) scale(0.99);
        }

        .admin-detail-box {
            padding: 12px;
            border-color: var(--border);
            border-radius: 14px;
            background: #f5f5f7;
        }

        .admin-detail-copy span {
            color: var(--text-muted);
            font-size: 0.62rem;
            font-weight: 620;
        }

        .admin-detail-copy strong {
            color: var(--text-main);
            font-size: 0.77rem;
            font-weight: 600;
        }

        .copy-detail-button {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            color: var(--primary);
            border-color: rgba(0, 113, 227, 0.14);
            border-radius: 10px;
            background: #ffffff;

            transition:
                color var(--motion-fast) ease,
                background-color var(--motion-fast) ease,
                transform var(--motion-fast) ease;
        }

        .copy-detail-button:hover {
            color: #ffffff;
            background: var(--primary);
            transform: translateY(-1px) scale(1.01);
        }

        .copy-detail-button:active {
            transform: scale(0.99);
        }

        /* Tablet */

        @media (max-width: 960px) {
            .hero-section {
                grid-template-columns: 1fr;
                gap: 34px;
                padding: clamp(32px, 6vw, 56px);
            }

            .hero-content {
                text-align: center;
            }

            .hero-title,
            .hero-description {
                margin-inline: auto;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-dashboard {
                min-height: 250px;
            }

            .checkout-card {
                grid-template-columns: 1fr;
            }

            .checkout-summary {
                min-height: auto;
            }

            .summary-inner {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                align-items: center;
                gap: 16px;
            }

            .summary-badge {
                grid-column: 1 / -1;
            }

            .summary-plan-icon,
            .summary-plan-name,
            .summary-price-row {
                margin-top: 0;
            }

            .summary-points {
                grid-column: 1 / -1;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                margin-top: 5px;
            }

            .summary-security {
                grid-column: 1 / -1;
                margin-top: 0;
                padding-top: 3px;
            }

            .clean-table th,
            .clean-table td {
                padding-inline: 11px;
            }
        }

        /* Mobile-first safe stacking */

        @media (max-width: 760px) {
            .page-shell {
                padding-left: max(12px, env(safe-area-inset-left));
                padding-right: max(12px, env(safe-area-inset-right));
                padding-bottom: max(40px, env(safe-area-inset-bottom));
            }

            .top-navigation {
                top: max(8px, env(safe-area-inset-top));
                min-height: 56px;
                margin-bottom: 14px;
                padding: 7px 8px;
                border-radius: 16px;
            }

            .brand-subtitle {
                display: none;
            }

            .system-status {
                max-width: 165px;
                padding-inline: 9px;
                font-size: 0.66rem;
            }

            .hero-section {
                gap: 30px;
                padding: 32px 18px;
                border-radius: 26px;
            }

            .hero-title {
                font-size: clamp(2.45rem, 12.5vw, 3.55rem);
            }

            .hero-description {
                font-size: 0.94rem;
            }

            .hero-actions {
                gap: 7px;
            }

            .hero-feature {
                padding: 7px 10px;
                font-size: 0.70rem;
            }

            .hero-dashboard {
                min-height: 230px;
            }

            .dashboard-window {
                width: min(88%, 340px);
            }

            .floating-notification.notice-a {
                right: -8px;
            }

            .floating-notification.notice-b {
                left: -8px;
            }

            .metric-strip {
                grid-template-columns: 1fr;
            }

            .metric-box {
                padding: 14px;
            }

            .process-stepper {
                margin-top: 34px;
                padding-inline: 8px;
            }

            .stepper-track {
                left: 50px;
                right: 50px;
            }

            .stepper-caption {
                display: none;
            }

            .section-header {
                align-items: flex-start;
                margin-top: 36px;
            }

            .section-header-copy h2 {
                font-size: 1.55rem;
            }

            .section-header-copy p {
                max-width: 100%;
                font-size: 0.80rem;
            }

            .plans-grid {
                grid-template-columns: 1fr;
                width: min(100%, 500px);
            }

            .plan-card {
                min-height: 0;
                padding: 23px 19px;
                border-radius: 23px;
            }

            .plan-description {
                min-height: 0;
            }

            .selected-plan-inner {
                align-items: flex-start;
                padding: 12px;
            }

            .selected-plan-copy span {
                white-space: normal;
            }

            .checkout-card,
            .history-card {
                border-radius: 24px;
            }

            .summary-inner {
                display: flex;
                align-items: flex-start;
                gap: 0;
            }

            .checkout-summary {
                padding: 26px 20px;
            }

            .summary-plan-icon {
                margin-top: 26px;
            }

            .summary-plan-name {
                margin-top: 18px;
            }

            .summary-price-row {
                margin-top: 21px;
            }

            .summary-points {
                grid-template-columns: 1fr;
                gap: 9px;
                margin-top: 23px;
            }

            .summary-security {
                padding-top: 24px;
            }

            .checkout-form-area {
                padding: 25px 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-field.full-width {
                grid-column: auto;
            }

            .history-header {
                padding: 18px 16px;
            }

            /* Convert table to native-feeling cards; never scroll sideways. */

            .table-responsive {
                overflow: hidden;
            }

            .clean-table {
                min-width: 0;
                table-layout: auto;
            }

            .clean-table thead {
                display: none;
            }

            .clean-table,
            .clean-table tbody,
            .clean-table tr,
            .clean-table td {
                display: block;
                width: 100%;
                max-width: 100%;
            }

            .clean-table tbody {
                padding: 12px;
            }

            .clean-table tr {
                overflow: hidden;
                margin-bottom: 11px;
                border: 1px solid var(--border);
                border-radius: 17px;
                background: #ffffff;
                box-shadow: var(--shadow-xs);
            }

            .clean-table tr:last-child {
                margin-bottom: 0;
            }

            .clean-table td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                padding: 12px 13px;
                text-align: right;
                border-bottom: 1px solid rgba(29, 29, 31, 0.07);
            }

            .clean-table td::before {
                content: attr(data-label);
                flex: 0 0 min(40%, 120px);
                color: var(--text-muted);
                font-size: 0.62rem;
                font-weight: 620;
                letter-spacing: 0.03em;
                text-align: left;
                text-transform: uppercase;
            }

            .clean-table td > * {
                min-width: 0;
                max-width: 60%;
            }

            .clean-table td:last-child {
                border-bottom: 0;
            }

            .panel-domain-link {
                justify-content: flex-end;
                max-width: 60%;
            }

            .domain-link-text {
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .page-footer {
                padding-inline: 10px;
                line-height: 1.5;
            }
        }

        @media (max-width: 480px) {
            .brand-mark {
                width: 38px;
                height: 38px;
                flex-basis: 38px;
                border-radius: 11px;
            }

            .brand-name {
                font-size: 0.88rem;
            }

            .system-status {
                width: 36px;
                height: 36px;
                padding: 0;
                justify-content: center;
                border-radius: 50%;
            }

            .system-status span:last-child {
                display: none;
            }

            .help-button {
                display: grid;
            }

            .hero-section {
                padding: 29px 15px;
            }

            .hero-eyebrow {
                font-size: 0.66rem;
            }

            .hero-title {
                font-size: clamp(2.25rem, 13vw, 3rem);
            }

            .hero-feature {
                flex: 1 1 calc(50% - 7px);
                justify-content: center;
                min-width: 0;
            }

            .dashboard-window {
                width: 91%;
                padding: 11px;
            }

            .floating-notification {
                max-width: 135px;
                font-size: 0.58rem;
            }

            .section-header {
                gap: 10px;
            }

            .section-chip span {
                display: none;
            }

            .plan-card {
                padding: 21px 16px;
            }

            .plan-icon-group {
                flex-wrap: wrap;
            }

            .plan-badge {
                white-space: normal;
            }

            .selected-plan-price span {
                display: none;
            }

            .checkout-form-header {
                display: block;
            }

            .secure-badge {
                width: fit-content;
                margin-top: 11px;
            }

            .checkout-form-area {
                padding: 23px 15px;
            }

            .domain-prefix {
                display: none;
            }

            .domain-preview {
                display: block;
            }

            .domain-preview-value {
                max-width: 100%;
                margin-top: 4px;
                text-align: left;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .history-count span {
                display: none;
            }

            .clean-table td {
                align-items: flex-start;
            }

            .clean-table td > *,
            .panel-domain-link {
                max-width: 58%;
            }

            .action-button {
                gap: 7px;
                padding-inline: 13px;
                font-size: 0.78rem;
            }
        }

        @media (max-width: 350px) {
            .page-shell {
                padding-inline: 9px;
            }

            .top-navigation {
                padding-inline: 6px;
            }

            .brand-text {
                max-width: 118px;
            }

            .brand-name {
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .hero-title {
                font-size: 2.05rem;
            }

            .hero-feature {
                flex-basis: 100%;
            }

            .floating-notification {
                display: none;
            }

            .plan-top-row {
                gap: 8px;
            }

            .plan-selection {
                width: 26px;
                height: 26px;
                flex-basis: 26px;
            }

            .selected-plan-inner {
                gap: 9px;
            }

            .selected-plan-price strong {
                padding-inline: 7px;
            }

            .action-button > i:last-child {
                display: none;
            }
        }

        @media (hover: none) {
            .help-button:hover,
            .metric-box:hover,
            .plan-card:hover,
            .plan-card:hover .plan-icon,
            .plan-card:hover .plan-action,
            .input-shell:hover,
            .password-toggle:hover,
            .action-button:hover,
            .details-button:hover,
            .copy-detail-button:hover,
            .swal2-confirm:hover,
            .swal2-cancel:hover {
                transform: none;
            }

            .plan-card:hover {
                border-color: var(--border);
                box-shadow:
                    0 1px 0 rgba(255, 255, 255, 0.95) inset,
                    0 10px 32px rgba(0, 0, 0, 0.06);
            }

            .plan-card.active-card:hover {
                border-color: var(--primary);
                box-shadow:
                    0 0 0 3px rgba(0, 113, 227, 0.10),
                    0 18px 46px rgba(0, 78, 156, 0.12);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto !important;
            }

            *,
            *::before,
            *::after {
                animation: none !important;
                transition-duration: 0.01ms !important;
                transition-delay: 0ms !important;
            }

            .reveal,
            .reveal.visible,
            .plan-card,
            .plan-card.active-card,
            .dashboard-window,
            .floating-notification {
                opacity: 1 !important;
                transform: none !important;
            }
        }


        /* Final interaction and edge-safety normalization */

        .floating-notification.notice-a {
            right: 8px;
        }

        .floating-notification.notice-b {
            left: 8px;
        }

        .button-ripple {
            animation-duration: 200ms;
        }

        :where(
            .help-button,
            .metric-box,
            .plan-card,
            .plan-icon,
            .plan-action,
            .input-shell,
            .password-toggle,
            .action-button,
            .details-button,
            .copy-detail-button,
            .panel-domain-link,
            .swal2-confirm,
            .swal2-cancel
        ) {
            transition-duration: 180ms;
        }

        @media (max-width: 760px) {
            .floating-notification.notice-a {
                right: 5px;
            }

            .floating-notification.notice-b {
                left: 5px;
            }
        }
    
        /* ================================================================
           PURPLE ORBIT REDESIGN — FINAL VISUAL OVERRIDE
           Narrower cards, stronger type, calmer hierarchy, richer controls.
        ================================================================= */

        :root {
            color-scheme: light;

            --page-bg: #f6f2ff;
            --page-bg-soft: #fbf9ff;
            --surface: rgba(255, 255, 255, 0.84);
            --surface-strong: #ffffff;
            --surface-soft: #faf8ff;
            --surface-purple: #f1eaff;

            --purple-25: #fcfaff;
            --purple-50: #f8f4ff;
            --purple-100: #efe7ff;
            --purple-150: #e8dcff;
            --purple-200: #dccaff;
            --purple-300: #c8a8ff;
            --purple-400: #a978ff;
            --purple-500: #8b5cf6;
            --purple-600: #7c3aed;
            --purple-700: #6d28d9;
            --purple-800: #5520ad;
            --purple-900: #32125f;

            --primary: #7c3aed;
            --primary-light: #a978ff;
            --primary-dark: #5520ad;

            --text-main: #20182d;
            --text-secondary: #5e556b;
            --text-muted: #82788f;
            --text-light: #aaa2b3;

            --border: rgba(67, 38, 101, 0.11);
            --border-medium: rgba(124, 58, 237, 0.22);
            --border-strong: rgba(124, 58, 237, 0.48);

            --success: #168a4b;
            --success-bg: #edf9f2;
            --info: #6d28d9;
            --info-bg: #f3edff;

            --shadow-xs: 0 2px 8px rgba(47, 25, 75, 0.05);
            --shadow-sm: 0 10px 28px rgba(49, 25, 79, 0.08);
            --shadow-md: 0 20px 55px rgba(52, 27, 84, 0.12);
            --shadow-lg: 0 34px 90px rgba(45, 21, 76, 0.17);
            --shadow-purple: 0 18px 42px rgba(124, 58, 237, 0.24);

            --radius-xs: 10px;
            --radius-sm: 14px;
            --radius-md: 18px;
            --radius-lg: 24px;
            --radius-xl: 30px;

            --container-width: 1120px;
            --content-width: 920px;
            --narrow-width: 760px;
            --motion-fast: 160ms;
            --motion-standard: 200ms;
        }

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        body {
            font-size: 16px;
            color: var(--text-main);
            background:
                radial-gradient(circle at 12% 3%, rgba(167, 139, 250, 0.26), transparent 27%),
                radial-gradient(circle at 92% 18%, rgba(124, 58, 237, 0.13), transparent 27%),
                linear-gradient(180deg, #fbf9ff 0%, #f5f0ff 44%, #faf8ff 100%);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            opacity: 0.42;
            background-image:
                linear-gradient(rgba(124, 58, 237, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124, 58, 237, 0.025) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: linear-gradient(to bottom, #000 0%, transparent 76%);
            -webkit-mask-image: linear-gradient(to bottom, #000 0%, transparent 76%);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        img,
        svg,
        video,
        canvas,
        iframe {
            max-width: 100%;
            height: auto;
        }

        button,
        input,
        select,
        textarea {
            max-width: 100%;
        }

        :where(a, button, input, select, textarea, [role="button"], [tabindex]):focus-visible {
            outline: 3px solid rgba(124, 58, 237, 0.42);
            outline-offset: 3px;
        }

        .ambient-background {
            display: block;
            opacity: 0.55;
        }

        .ambient-grid,
        .ambient-ring {
            display: none;
        }

        .ambient-orb {
            filter: blur(30px);
            opacity: 0.28;
        }

        .page-shell {
            width: min(100%, var(--container-width));
            max-width: 100%;
            margin: 0 auto;
            padding:
                max(14px, env(safe-area-inset-top))
                clamp(14px, 3vw, 28px)
                max(54px, env(safe-area-inset-bottom));
        }

        .top-navigation,
        .hero-section,
        .metric-strip,
        .process-stepper,
        .section-header,
        .plans-grid,
        .selected-plan-bar,
        .checkout-section,
        .history-section,
        .page-footer {
            width: min(100%, var(--content-width));
            max-width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .plans-grid,
        .selected-plan-bar,
        .section-header {
            width: min(100%, var(--narrow-width));
        }

        .reveal {
            transform: translateY(8px);
            transition:
                opacity var(--motion-standard) ease,
                transform var(--motion-standard) ease;
        }

        /* Floating navigation */

        .top-navigation {
            top: max(10px, env(safe-area-inset-top));
            min-height: 64px;
            margin-bottom: 18px;
            padding: 9px 11px;
            border: 1px solid rgba(90, 51, 130, 0.13);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: blur(28px) saturate(150%);
            -webkit-backdrop-filter: blur(28px) saturate(150%);
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.88),
                0 12px 36px rgba(57, 27, 88, 0.09);
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            border-radius: 15px;
            font-size: 1rem;
            background:
                radial-gradient(circle at 28% 20%, rgba(255,255,255,0.42), transparent 30%),
                linear-gradient(145deg, #a978ff 0%, #7c3aed 50%, #4c1d95 100%);
            box-shadow:
                0 10px 24px rgba(124, 58, 237, 0.27),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .brand-name {
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 600;
        }

        .system-status {
            min-height: 38px;
            padding: 8px 12px;
            color: #51465f;
            font-size: 0.72rem;
            font-weight: 750;
            border-color: rgba(124, 58, 237, 0.12);
            background: rgba(248, 244, 255, 0.8);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            flex-basis: 8px;
            background: #24a865;
        }

        .help-button {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: var(--primary);
            border-color: rgba(124, 58, 237, 0.13);
            background: var(--purple-50);
            transition:
                transform var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease,
                background-color var(--motion-fast) ease;
        }

        .help-button:hover {
            transform: translateY(-2px) scale(1.01);
            background: var(--purple-100);
            box-shadow: 0 9px 20px rgba(124, 58, 237, 0.14);
        }

        .help-button:active {
            transform: scale(0.97);
        }

        /* Hero: deep purple launch surface */

        .hero-section {
            position: relative;
            isolation: isolate;
            grid-template-columns: minmax(0, 1.12fr) minmax(280px, 0.88fr);
            gap: clamp(28px, 4vw, 48px);
            min-height: 480px;
            padding: clamp(32px, 5vw, 54px);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 34px;
            background:
                radial-gradient(circle at 84% 16%, rgba(196, 164, 255, 0.35), transparent 27%),
                radial-gradient(circle at 12% 92%, rgba(139, 92, 246, 0.28), transparent 32%),
                linear-gradient(135deg, #26103f 0%, #40156c 42%, #6d28d9 100%);
            box-shadow:
                0 34px 90px rgba(52, 23, 86, 0.24),
                inset 0 1px 0 rgba(255, 255, 255, 0.16);
        }

        .hero-section::before {
            width: 280px;
            height: 280px;
            top: -130px;
            right: -70px;
            border: 1px solid rgba(255, 255, 255, 0.11);
            background: transparent;
            box-shadow:
                0 0 0 34px rgba(255, 255, 255, 0.035),
                0 0 0 76px rgba(255, 255, 255, 0.018);
        }

        .hero-section::after {
            width: 130px;
            height: 130px;
            left: auto;
            right: 39%;
            bottom: -75px;
            background: radial-gradient(circle, rgba(194, 159, 255, 0.34), transparent 68%);
        }

        .hero-eyebrow {
            margin-bottom: 18px;
            padding: 8px 12px;
            color: #f2eaff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.065em;
            border-color: rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.09);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .hero-title {
            max-width: 620px;
            color: #ffffff;
            font-size: clamp(2.75rem, 6vw, 5rem);
            line-height: 0.94;
            font-weight: 900;
            letter-spacing: -0.07em;
        }

        .hero-title-gradient {
            display: block;
            margin-top: 6px;
            color: #d9c4ff;
            background: linear-gradient(90deg, #ffffff 0%, #dcc7ff 42%, #b795ff 100%);
            background-clip: text;
            -webkit-background-clip: text;
        }

        .hero-description {
            max-width: 610px;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.74);
            font-size: 1rem;
            line-height: 1.72;
        }

        .hero-actions {
            gap: 9px;
            margin-top: 25px;
        }

        .hero-feature {
            min-height: 38px;
            padding: 8px 12px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 0.74rem;
            font-weight: 750;
            border-color: rgba(255, 255, 255, 0.13);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .hero-feature i {
            color: #d8c0ff;
            font-size: 0.76rem;
        }

        .hero-dashboard {
            min-height: 310px;
        }

        .dashboard-window {
            width: min(100%, 330px);
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow:
                0 28px 64px rgba(15, 4, 33, 0.34),
                inset 0 1px 0 rgba(255, 255, 255, 0.96);
            transform: rotate(1.2deg);
        }

        .dashboard-main-card {
            min-height: 132px;
            padding: 20px;
            border-radius: 20px;
            background:
                radial-gradient(circle at 86% 14%, rgba(255,255,255,0.25), transparent 24%),
                linear-gradient(135deg, #a978ff 0%, #7c3aed 52%, #4c1d95 100%);
            box-shadow: 0 18px 36px rgba(76, 29, 149, 0.28);
        }

        .dashboard-label {
            font-size: 0.72rem;
        }

        .dashboard-value {
            margin-top: 9px;
            font-size: 2.35rem;
        }

        .dashboard-change {
            margin-top: 9px;
            font-size: 0.68rem;
        }

        .dashboard-mini-grid {
            gap: 10px;
            margin-top: 11px;
        }

        .dashboard-mini-card {
            padding: 13px;
            border-color: rgba(124, 58, 237, 0.1);
            border-radius: 16px;
            background: #ffffff;
        }

        .mini-card-icon {
            width: 32px;
            height: 32px;
            font-size: 0.72rem;
            border-radius: 10px;
            background: var(--purple-100);
        }

        .mini-card-value {
            font-size: 0.9rem;
        }

        .mini-card-label {
            font-size: 0.64rem;
        }

        .floating-notification {
            padding: 10px 12px;
            color: #4d405b;
            font-size: 0.68rem;
            border-color: rgba(124, 58, 237, 0.14);
            border-radius: 13px;
            box-shadow: 0 12px 30px rgba(35, 18, 55, 0.13);
        }

        .floating-notification.notice-a {
            right: -6px;
        }

        .floating-notification.notice-b {
            left: -6px;
        }

        /* Compact bento metrics */

        .metric-strip {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .metric-box {
            min-height: 82px;
            padding: 15px 16px;
            border-color: rgba(90, 51, 130, 0.11);
            border-radius: 19px;
            background: rgba(255, 255, 255, 0.79);
            box-shadow: 0 10px 28px rgba(50, 25, 82, 0.07);
            transition:
                transform var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                border-color var(--motion-standard) ease;
        }

        .metric-box:hover {
            transform: translateY(-2px) scale(1.01);
            border-color: var(--border-medium);
            box-shadow: 0 16px 36px rgba(62, 31, 97, 0.11);
        }

        .metric-box:active {
            transform: scale(0.985);
        }

        .metric-icon {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
            border-radius: 14px;
            color: #ffffff;
            font-size: 0.92rem;
            background: linear-gradient(145deg, #a978ff, #6d28d9);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.2);
        }

        .metric-value {
            font-size: 0.94rem;
            font-weight: 850;
        }

        .metric-label {
            margin-top: 3px;
            font-size: 0.7rem;
        }

        /* Stepper becomes a segmented launch path */

        .process-stepper {
            max-width: 720px;
            margin-top: 30px;
            margin-bottom: 24px;
            padding: 10px;
            border: 1px solid rgba(93, 55, 132, 0.11);
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.7);
            box-shadow: 0 10px 30px rgba(52, 27, 83, 0.07);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .stepper-track {
            display: none;
        }

        .stepper-items {
            gap: 8px;
        }

        .stepper-item {
            min-width: 0;
            min-height: 74px;
            justify-content: center;
            padding: 10px 8px;
            border: 1px solid transparent;
            border-radius: 16px;
            transition:
                transform var(--motion-standard) ease,
                background-color var(--motion-standard) ease,
                border-color var(--motion-standard) ease;
        }

        .stepper-circle {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.72rem;
        }

        .stepper-label {
            margin-top: 7px;
            font-size: 0.72rem;
        }

        .stepper-caption {
            font-size: 0.61rem;
        }

        .stepper-item.active {
            color: var(--primary-dark);
            border-color: rgba(124, 58, 237, 0.13);
            background: var(--purple-50);
        }

        .stepper-item.active .stepper-circle,
        .stepper-item.completed .stepper-circle {
            background: linear-gradient(145deg, #a978ff, #6d28d9);
            border-color: transparent;
            box-shadow: 0 9px 18px rgba(124, 58, 237, 0.22);
        }

        /* Section heading */

        .section-header {
            align-items: center;
            margin-top: 38px;
            margin-bottom: 18px;
            padding: 0 4px;
        }

        .section-header-copy h2 {
            font-size: clamp(1.65rem, 3.6vw, 2.25rem);
            font-weight: 900;
            letter-spacing: -0.055em;
        }

        .section-header-copy p {
            max-width: 580px;
            margin-top: 8px;
            font-size: 0.86rem;
            line-height: 1.62;
        }

        .section-chip {
            min-height: 38px;
            padding: 8px 11px;
            color: var(--primary-dark);
            font-size: 0.72rem;
            border-radius: 12px;
            background: var(--purple-100);
        }

        /* Narrow plan stage */

        .plans-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            align-items: stretch;
            margin-bottom: 22px;
        }

        .plan-card {
            min-height: 500px;
            padding: 24px;
            border: 1px solid rgba(82, 48, 116, 0.13);
            border-radius: 27px;
            background:
                radial-gradient(circle at 92% 3%, rgba(169, 120, 255, 0.17), transparent 26%),
                linear-gradient(160deg, #ffffff 0%, #fbf9ff 100%);
            box-shadow:
                0 18px 45px rgba(51, 26, 80, 0.09),
                inset 0 1px 0 rgba(255, 255, 255, 0.96);
            transition:
                transform var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                border-color var(--motion-standard) ease;
        }

        .plan-card::before {
            inset: 0 auto auto 0;
            width: 100%;
            height: 5px;
            border-radius: 27px 27px 0 0;
            background: linear-gradient(90deg, #a978ff, #7c3aed 54%, #4c1d95);
            opacity: 1;
        }

        .plan-card::after {
            width: 180px;
            height: 180px;
            right: -98px;
            bottom: -104px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.16), transparent 68%);
        }

        .plan-card:hover {
            transform: translateY(-2px) scale(1.01);
            border-color: rgba(124, 58, 237, 0.3);
            box-shadow:
                0 24px 54px rgba(53, 25, 86, 0.14),
                inset 0 1px 0 rgba(255, 255, 255, 0.96);
        }

        .plan-card:active {
            transform: scale(0.988);
        }

        .plan-card.active-card {
            transform: none;
            border-color: var(--primary);
            box-shadow:
                0 0 0 4px rgba(124, 58, 237, 0.11),
                0 24px 55px rgba(72, 33, 116, 0.16);
        }

        .plan-top-row {
            margin-bottom: 22px;
        }

        .plan-icon-group {
            gap: 11px;
        }

        .plan-icon {
            width: 56px;
            height: 56px;
            flex-basis: 56px;
            color: #ffffff;
            font-size: 1.18rem;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(145deg, #b58cff, #7c3aed 58%, #5b21b6);
            box-shadow: 0 13px 28px rgba(124, 58, 237, 0.24);
        }

        .plan-card:hover .plan-icon {
            transform: rotate(-2deg) scale(1.01);
        }

        .plan-badge {
            min-height: 28px;
            padding: 6px 9px;
            color: var(--primary-dark);
            font-size: 0.65rem;
            border-color: rgba(124, 58, 237, 0.13);
            background: var(--purple-50);
        }

        .plan-badge.recommended {
            background: linear-gradient(110deg, #9d6cff, #6d28d9);
            box-shadow: 0 9px 20px rgba(124, 58, 237, 0.2);
        }

        .plan-selection {
            width: 30px;
            height: 30px;
            flex-basis: 30px;
            border-width: 1.5px;
            border-color: rgba(105, 78, 128, 0.28);
            background: rgba(255, 255, 255, 0.86);
        }

        .plan-name {
            font-size: 1.55rem;
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .plan-description {
            min-height: 50px;
            margin-top: 9px;
            font-size: 0.86rem;
            line-height: 1.62;
        }

        .plan-price-row {
            margin-top: 22px;
            padding-bottom: 20px;
        }

        .price-symbol {
            font-size: 1.05rem;
        }

        .plan-price {
            font-size: clamp(3.1rem, 6vw, 4rem);
            font-weight: 900;
        }

        .price-period {
            margin-bottom: 7px;
            font-size: 0.76rem;
        }

        .api-rule {
            margin-top: 19px;
            padding: 13px;
            border-radius: 16px;
            background: linear-gradient(145deg, #f7f2ff, #f1e8ff);
        }

        .api-rule-icon {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
            font-size: 0.78rem;
            border-radius: 11px;
        }

        .api-rule strong {
            font-size: 0.77rem;
        }

        .api-rule span {
            margin-top: 3px;
            font-size: 0.7rem;
            line-height: 1.5;
        }

        .plan-features {
            gap: 12px;
            margin-top: 19px;
        }

        .plan-features li {
            gap: 10px;
            font-size: 0.78rem;
            line-height: 1.48;
        }

        .feature-check {
            width: 22px;
            height: 22px;
            flex-basis: 22px;
            font-size: 0.58rem;
            border-radius: 7px;
            background: var(--purple-100);
        }

        .plan-footer {
            padding-top: 24px;
        }

        .plan-footer-label {
            font-size: 0.78rem;
        }

        .plan-action {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            color: #ffffff;
            background: linear-gradient(145deg, #9f70ff, #6d28d9);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.2);
        }

        .plan-card:hover .plan-action {
            transform: translateX(2px) scale(1.01);
        }

        /* Rental card gets a distinct night-mode identity */

        #card-rental {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.12);
            background:
                radial-gradient(circle at 90% 5%, rgba(192, 132, 252, 0.3), transparent 26%),
                linear-gradient(150deg, #211031 0%, #351451 45%, #57209a 100%);
            box-shadow: 0 22px 56px rgba(42, 16, 70, 0.24);
        }

        #card-rental::before {
            background: linear-gradient(90deg, #e2ccff, #b48bff 50%, #8b5cf6);
        }

        #card-rental .plan-name,
        #card-rental .price-symbol,
        #card-rental .plan-price {
            color: #ffffff;
        }

        #card-rental .plan-description,
        #card-rental .price-period,
        #card-rental .plan-features li,
        #card-rental .plan-footer-label {
            color: rgba(255, 255, 255, 0.76);
        }

        #card-rental .plan-price-row {
            border-color: rgba(255, 255, 255, 0.12);
        }

        #card-rental .plan-icon {
            color: #4c1d95;
            background: linear-gradient(145deg, #ffffff, #d9c3ff);
            box-shadow: 0 14px 30px rgba(16, 3, 30, 0.27);
        }

        #card-rental .plan-badge.recommended {
            color: #3c1769;
            background: #e6d5ff;
            box-shadow: none;
        }

        #card-rental .plan-selection {
            border-color: rgba(255, 255, 255, 0.34);
            background: rgba(255, 255, 255, 0.09);
        }

        #card-rental .api-rule {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.09);
        }

        #card-rental .api-rule-icon {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        #card-rental .api-rule strong {
            color: #ffffff;
        }

        #card-rental .api-rule span {
            color: rgba(255, 255, 255, 0.65);
        }

        #card-rental .feature-check {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
        }

        #card-rental .plan-action {
            color: #4c1d95;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(16, 3, 30, 0.22);
        }

        #card-rental.active-card {
            border-color: #d9c4ff;
            box-shadow:
                0 0 0 4px rgba(197, 164, 255, 0.17),
                0 28px 64px rgba(42, 16, 70, 0.3);
        }

        #card-rental.active-card .plan-selection {
            color: #4c1d95;
            border-color: #ffffff;
            background: #ffffff;
        }

        /* Selection preview */

        .selected-plan-inner {
            padding: 13px 15px;
            border-color: rgba(124, 58, 237, 0.15);
            border-radius: 17px;
            background: rgba(255, 255, 255, 0.84);
            box-shadow: 0 12px 30px rgba(56, 29, 88, 0.08);
        }

        .selected-plan-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            font-size: 0.84rem;
            border-radius: 13px;
            background: linear-gradient(145deg, #a978ff, #6d28d9);
        }

        .selected-plan-copy strong,
        .selected-plan-price {
            font-size: 0.8rem;
        }

        .selected-plan-copy span {
            font-size: 0.68rem;
        }

        .selected-plan-price strong {
            border-radius: 10px;
            background: var(--primary);
        }

        /* Checkout */

        .checkout-section {
            width: min(100%, 860px);
        }

        .checkout-card {
            grid-template-columns: minmax(250px, 0.7fr) minmax(0, 1.3fr);
            border-color: rgba(84, 49, 119, 0.13);
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 25px 68px rgba(48, 23, 78, 0.14);
        }

        .checkout-summary {
            padding: 30px;
            background:
                radial-gradient(circle at 88% 10%, rgba(210, 184, 255, 0.3), transparent 25%),
                linear-gradient(150deg, #241037 0%, #42156d 52%, #6d28d9 100%);
        }

        .summary-badge {
            padding: 7px 10px;
            font-size: 0.65rem;
        }

        .summary-plan-icon {
            width: 58px;
            height: 58px;
            margin-top: 30px;
            font-size: 1.22rem;
            border-radius: 18px;
        }

        .summary-plan-name {
            font-size: 1.65rem;
        }

        .summary-description {
            font-size: 0.78rem;
        }

        .summary-price-value {
            font-size: 3.35rem;
        }

        .summary-points li {
            font-size: 0.72rem;
        }

        .checkout-form-area {
            padding: 32px;
        }

        .checkout-form-title h3 {
            font-size: 1.34rem;
        }

        .checkout-form-title p {
            font-size: 0.76rem;
        }

        .secure-badge {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 0.66rem;
        }

        .field-label {
            font-size: 0.76rem;
        }

        .field-required,
        .field-help {
            font-size: 0.64rem;
        }

        .input-shell {
            min-height: 54px;
            border-color: rgba(70, 43, 97, 0.12);
            border-radius: 15px;
            background: #faf8ff;
            transition:
                transform var(--motion-fast) ease,
                border-color var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease,
                background-color var(--motion-fast) ease;
        }

        .input-shell:hover {
            transform: translateY(-1px);
            border-color: rgba(124, 58, 237, 0.24);
        }

        .input-shell:focus-within {
            transform: translateY(-1px);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.11);
        }

        .input-icon {
            width: 47px;
            height: 52px;
            flex-basis: 47px;
            font-size: 0.8rem;
        }

        .apple-input {
            height: 52px;
            font-size: 0.86rem;
        }

        .domain-suffix {
            height: 34px;
            font-size: 0.7rem;
            background: var(--purple-100);
        }

        .domain-preview,
        .checkout-notice {
            border-radius: 13px;
            background: var(--purple-50);
        }

        .action-button {
            min-height: 56px;
            margin-top: 19px;
            border-radius: 16px;
            font-size: 0.86rem;
            background:
                radial-gradient(circle at 25% 0%, rgba(255,255,255,0.24), transparent 30%),
                linear-gradient(105deg, #9c6dff, #7c3aed 48%, #5520ad);
            box-shadow: 0 17px 36px rgba(124, 58, 237, 0.28);
            transition:
                transform var(--motion-standard) ease,
                box-shadow var(--motion-standard) ease,
                opacity var(--motion-standard) ease;
        }

        .action-button:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 22px 44px rgba(124, 58, 237, 0.34);
        }

        .action-button:active {
            transform: scale(0.985);
        }

        /* History */

        .history-section {
            width: min(100%, 860px);
            margin-top: 40px;
        }

        .history-card {
            border-color: rgba(76, 44, 110, 0.12);
            border-radius: 29px;
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 22px 58px rgba(48, 23, 77, 0.11);
        }

        .history-header {
            padding: 22px 24px;
            background: linear-gradient(145deg, #ffffff, #f8f3ff);
        }

        .history-icon {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
            color: #ffffff;
            font-size: 0.94rem;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(145deg, #a978ff, #6d28d9);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.2);
        }

        .history-title-group h2 {
            font-size: 1.15rem;
        }

        .history-title-group p {
            font-size: 0.7rem;
        }

        .history-count {
            min-height: 34px;
            padding: 7px 10px;
            color: var(--primary-dark);
            font-size: 0.7rem;
            border-radius: 11px;
            background: var(--purple-100);
        }

        .clean-table th {
            padding: 14px 17px;
            color: var(--text-muted);
            font-size: 0.64rem;
            background: #faf8ff;
        }

        .clean-table td {
            padding: 16px 17px;
            font-size: 0.76rem;
        }

        .panel-type-pill,
        .details-button,
        .copy-detail-button {
            border-radius: 10px;
        }

        .details-button,
        .copy-detail-button,
        .panel-domain-link {
            transition:
                transform var(--motion-fast) ease,
                box-shadow var(--motion-fast) ease,
                background-color var(--motion-fast) ease;
        }

        .details-button:hover,
        .copy-detail-button:hover,
        .panel-domain-link:hover {
            transform: translateY(-2px) scale(1.01);
        }

        .empty-state {
            padding: 44px 20px;
        }

        .empty-icon {
            width: 68px;
            height: 68px;
            border-radius: 21px;
            color: var(--primary);
            background: var(--purple-100);
            box-shadow: 0 14px 30px rgba(124, 58, 237, 0.13);
        }

        .page-footer {
            margin-top: 28px;
            color: var(--text-muted);
            font-size: 0.7rem;
        }

        /* SweetAlert follows the same system */

        .swal2-popup {
            border: 1px solid rgba(83, 47, 118, 0.12) !important;
            border-radius: 24px !important;
            box-shadow: 0 28px 80px rgba(39, 17, 66, 0.2) !important;
        }

        .swal2-confirm {
            background: linear-gradient(105deg, #9d6cff, #6d28d9) !important;
            border-radius: 12px !important;
        }

        .swal2-cancel {
            border-radius: 12px !important;
        }

        /* Tablet */

        @media (max-width: 900px) {
            .hero-section {
                grid-template-columns: 1fr;
                min-height: 0;
                text-align: center;
            }

            .hero-content {
                max-width: 680px;
                margin: 0 auto;
            }

            .hero-title-gradient {
                display: inline;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-dashboard {
                min-height: 280px;
            }

            .checkout-card {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile: deliberately narrower cards and larger readable controls */

        @media (max-width: 760px) {
            .page-shell {
                padding-left: max(12px, env(safe-area-inset-left));
                padding-right: max(12px, env(safe-area-inset-right));
            }

            .top-navigation {
                width: min(calc(100% - 4px), 440px);
                min-height: 58px;
                padding: 7px 8px;
                border-radius: 18px;
            }

            .brand-mark {
                width: 40px;
                height: 40px;
                flex-basis: 40px;
                border-radius: 13px;
            }

            .brand-name {
                font-size: 0.94rem;
            }

            .hero-section,
            .metric-strip,
            .process-stepper,
            .section-header,
            .plans-grid,
            .selected-plan-bar,
            .checkout-section,
            .history-section,
            .page-footer {
                width: min(calc(100% - 12px), 560px);
            }

            .hero-section {
                gap: 28px;
                padding: 34px 21px 28px;
                border-radius: 29px;
                text-align: left;
            }

            .hero-content {
                margin: 0;
            }

            .hero-title {
                font-size: clamp(2.65rem, 13vw, 3.55rem);
                line-height: 0.96;
            }

            .hero-title-gradient {
                display: block;
            }

            .hero-description {
                font-size: 0.94rem;
                line-height: 1.68;
            }

            .hero-actions {
                justify-content: flex-start;
            }

            .hero-feature {
                flex: 1 1 calc(50% - 9px);
                justify-content: flex-start;
                min-width: 0;
                font-size: 0.72rem;
            }

            .hero-dashboard {
                min-height: 245px;
            }

            .dashboard-window {
                width: min(92%, 350px);
                transform: none;
            }

            .floating-notification {
                display: none;
            }

            .metric-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .metric-box {
                min-height: 104px;
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
                gap: 10px;
                padding: 15px;
            }

            .metric-box:first-child {
                grid-column: 1 / -1;
                min-height: 84px;
                flex-direction: row;
                align-items: center;
            }

            .metric-label {
                white-space: normal;
            }

            .process-stepper {
                margin-top: 25px;
                padding: 8px;
                border-radius: 19px;
            }

            .stepper-item {
                min-height: 70px;
                padding: 9px 5px;
            }

            .stepper-caption {
                display: none;
            }

            .section-header {
                align-items: flex-start;
                margin-top: 34px;
                padding-inline: 2px;
            }

            .section-header-copy h2 {
                font-size: 1.75rem;
            }

            .section-header-copy p {
                font-size: 0.86rem;
            }

            .plans-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .plan-card {
                width: min(100%, 420px);
                min-height: 0;
                margin-inline: auto;
                padding: 24px 21px;
                border-radius: 26px;
            }

            .plan-card::before {
                border-radius: 26px 26px 0 0;
            }

            .plan-name {
                font-size: 1.5rem;
            }

            .plan-description {
                min-height: 0;
                font-size: 0.86rem;
            }

            .plan-price {
                font-size: 3.45rem;
            }

            .api-rule {
                padding: 13px;
            }

            .plan-features li {
                font-size: 0.79rem;
            }

            .checkout-card,
            .history-card {
                border-radius: 27px;
            }

            .checkout-summary,
            .checkout-form-area {
                padding: 25px 21px;
            }

            .summary-inner {
                display: flex;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .history-header {
                padding: 19px 17px;
            }

            .table-responsive {
                overflow: hidden;
            }

            .clean-table {
                min-width: 0;
            }

            .clean-table tbody {
                padding: 12px;
            }

            .clean-table tr {
                border-color: rgba(83, 48, 117, 0.11);
                border-radius: 18px;
                box-shadow: 0 9px 24px rgba(45, 22, 74, 0.07);
            }

            .clean-table td {
                padding: 13px 14px;
            }
        }

        @media (max-width: 480px) {
            .system-status {
                width: 38px;
                height: 38px;
            }

            .hero-section,
            .metric-strip,
            .process-stepper,
            .section-header,
            .plans-grid,
            .selected-plan-bar,
            .checkout-section,
            .history-section,
            .page-footer {
                width: min(calc(100% - 16px), 420px);
            }

            .hero-section {
                padding: 31px 18px 25px;
            }

            .hero-eyebrow {
                font-size: 0.68rem;
            }

            .hero-title {
                font-size: clamp(2.35rem, 13.2vw, 3rem);
            }

            .hero-feature {
                flex-basis: 100%;
                min-height: 40px;
                font-size: 0.75rem;
            }

            .dashboard-window {
                width: 100%;
                padding: 12px;
            }

            .dashboard-value {
                font-size: 2.12rem;
            }

            .metric-strip {
                width: min(calc(100% - 16px), 380px);
            }

            .metric-icon {
                width: 42px;
                height: 42px;
                flex-basis: 42px;
            }

            .stepper-circle {
                width: 32px;
                height: 32px;
            }

            .stepper-label {
                font-size: 0.67rem;
            }

            .section-chip span {
                display: none;
            }

            .plan-card {
                width: min(100%, 360px);
                padding: 23px 19px;
            }

            .plan-icon {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
            }

            .plan-badge {
                font-size: 0.62rem;
            }

            .selected-plan-inner {
                align-items: flex-start;
            }

            .selected-plan-price span {
                display: none;
            }

            .checkout-form-area,
            .checkout-summary {
                padding: 23px 18px;
            }

            .input-shell {
                min-height: 52px;
            }

            .apple-input {
                height: 50px;
                font-size: 0.84rem;
            }

            .action-button {
                min-height: 54px;
                font-size: 0.82rem;
            }
        }

        @media (max-width: 350px) {
            .page-shell {
                padding-inline: 9px;
            }

            .hero-section,
            .metric-strip,
            .process-stepper,
            .section-header,
            .plans-grid,
            .selected-plan-bar,
            .checkout-section,
            .history-section,
            .page-footer {
                width: min(calc(100% - 8px), 330px);
            }

            .hero-title {
                font-size: 2.15rem;
            }

            .metric-strip {
                grid-template-columns: 1fr;
            }

            .metric-box:first-child {
                grid-column: auto;
            }

            .metric-box {
                min-height: 80px;
                flex-direction: row;
                align-items: center;
            }

            .plan-card {
                padding-inline: 16px;
            }

            .plan-icon-group {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (hover: none) {
            .help-button:hover,
            .metric-box:hover,
            .plan-card:hover,
            .plan-card:hover .plan-icon,
            .plan-card:hover .plan-action,
            .input-shell:hover,
            .action-button:hover,
            .details-button:hover,
            .copy-detail-button:hover,
            .panel-domain-link:hover {
                transform: none;
            }

            .plan-card:hover {
                border-color: rgba(82, 48, 116, 0.13);
                box-shadow:
                    0 18px 45px rgba(51, 26, 80, 0.09),
                    inset 0 1px 0 rgba(255, 255, 255, 0.96);
            }

            #card-rental:hover {
                border-color: rgba(255, 255, 255, 0.12);
                box-shadow: 0 22px 56px rgba(42, 16, 70, 0.24);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto !important;
            }

            *,
            *::before,
            *::after {
                animation: none !important;
                transition-duration: 0.01ms !important;
                transition-delay: 0ms !important;
            }

            .reveal,
            .reveal.visible,
            .dashboard-window,
            .floating-notification,
            .plan-card,
            .plan-card.active-card {
                opacity: 1 !important;
                transform: none !important;
            }
        }

    </style>
</head>

<body>

<!-- =====================================================================
     BACKGROUND
====================================================================== -->

<div class="ambient-background" aria-hidden="true">
    <div class="ambient-grid"></div>
    <div class="ambient-orb orb-a"></div>
    <div class="ambient-orb orb-b"></div>
    <div class="ambient-orb orb-c"></div>
    <div class="ambient-ring"></div>
</div>

<main class="page-shell">

    <!-- =================================================================
         TOP NAVIGATION
    ================================================================== -->

    <header class="top-navigation reveal">
        <div class="brand-group">
            <div class="brand-mark">
                <i class="fa-solid fa-bolt"></i>
            </div>

            <div class="brand-text">
                <span class="brand-name">LikexFollow</span>
                <span class="brand-subtitle">Panel Marketplace</span>
            </div>
        </div>

        <div class="navigation-actions">
            <div class="system-status">
                <span class="status-dot"></span>
                <span>All systems operational</span>
            </div>

            <button
                type="button"
                class="help-button"
                id="helpButton"
                aria-label="Panel information"
            >
                <i class="fa-solid fa-circle-question"></i>
            </button>
        </div>
    </header>

    <!-- =================================================================
         HERO
    ================================================================== -->

    <section class="hero-section reveal reveal-delay-1">
        <div class="hero-content">
            <div class="hero-eyebrow">
                <i class="fa-solid fa-sparkles"></i>
                Launch your SMM business
            </div>

            <h1 class="hero-title">
                Build a powerful
                <span class="hero-title-gradient">
                    selling panel.
                </span>
            </h1>

            <p class="hero-description">
                Start your own SMM panel with a professional domain,
                secure admin access and flexible business controls. Choose
                the setup that matches your API requirements.
            </p>

            <div class="hero-actions">
                <div class="hero-feature">
                    <i class="fa-solid fa-shield-halved"></i>
                    Secure setup
                </div>

                <div class="hero-feature">
                    <i class="fa-solid fa-gauge-high"></i>
                    Fast activation
                </div>

                <div class="hero-feature">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    Mobile optimized
                </div>

                <div class="hero-feature">
                    <i class="fa-solid fa-headset"></i>
                    Managed support
                </div>
            </div>
        </div>

        <div class="hero-dashboard" aria-hidden="true">
            <div class="dashboard-window">
                <div class="window-toolbar">
                    <div class="window-dots">
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>
                        <span class="window-dot"></span>
                    </div>

                    <div class="window-online">
                        Live
                    </div>
                </div>

                <div class="dashboard-main-card">
                    <div class="dashboard-label">
                        Monthly business growth
                    </div>

                    <div class="dashboard-value">
                        +38.6%
                    </div>

                    <div class="dashboard-change">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        Performance increasing
                    </div>
                </div>

                <div class="dashboard-mini-grid">
                    <div class="dashboard-mini-card">
                        <div class="mini-card-icon">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>

                        <div class="mini-card-value">
                            1,284
                        </div>

                        <div class="mini-card-label">
                            Total orders
                        </div>
                    </div>

                    <div class="dashboard-mini-card">
                        <div class="mini-card-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div class="mini-card-value">
                            842
                        </div>

                        <div class="mini-card-label">
                            Active users
                        </div>
                    </div>
                </div>

                <div class="floating-notification notice-a">
                    <i class="fa-solid fa-circle-check"></i>
                    Panel active
                </div>

                <div class="floating-notification notice-b">
                    <i class="fa-solid fa-bolt"></i>
                    Services live
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         METRICS
    ================================================================== -->

    <section class="metric-strip reveal reveal-delay-2">
        <article class="metric-box">
            <div class="metric-icon">
                <i class="fa-solid fa-server"></i>
            </div>

            <div class="metric-content">
                <div class="metric-value">99.9% uptime</div>
                <div class="metric-label">Reliable panel hosting</div>
            </div>
        </article>

        <article class="metric-box">
            <div class="metric-icon">
                <i class="fa-solid fa-lock"></i>
            </div>

            <div class="metric-content">
                <div class="metric-value">Secure access</div>
                <div class="metric-label">Protected admin account</div>
            </div>
        </article>

        <article class="metric-box">
            <div class="metric-icon">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>

            <div class="metric-content">
                <div class="metric-value">Quick deployment</div>
                <div class="metric-label">Simple guided setup</div>
            </div>
        </article>
    </section>

    <!-- =================================================================
         PROCESS STEPPER
    ================================================================== -->

    <section class="process-stepper reveal reveal-delay-3">
        <div class="stepper-track">
            <div
                class="stepper-progress"
                id="stepperProgress"
            ></div>
        </div>

        <div class="stepper-items">
            <div
                class="stepper-item active"
                id="stepItem1"
            >
                <div class="stepper-circle">
                    <span>1</span>
                </div>

                <div class="stepper-label">
                    Choose plan
                </div>

                <div class="stepper-caption">
                    Select panel type
                </div>
            </div>

            <div
                class="stepper-item"
                id="stepItem2"
            >
                <div class="stepper-circle">
                    <span>2</span>
                </div>

                <div class="stepper-label">
                    Configure
                </div>

                <div class="stepper-caption">
                    Enter panel details
                </div>
            </div>

            <div
                class="stepper-item"
                id="stepItem3"
            >
                <div class="stepper-circle">
                    <span>3</span>
                </div>

                <div class="stepper-label">
                    Launch
                </div>

                <div class="stepper-caption">
                    Create your panel
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         PLAN SECTION HEADER
    ================================================================== -->

    <div class="section-header reveal">
        <div class="section-header-copy">
            <h2>Choose your panel type</h2>

            <p>
                Child Panel uses only our LikexFollow API. Rental Panel
                allows you to connect any API provider you prefer.
            </p>
        </div>

        <div class="section-chip">
            <i class="fa-solid fa-arrow-pointer"></i>
            <span>Select one plan</span>
        </div>
    </div>

    <!-- =================================================================
         PLAN CARDS
    ================================================================== -->

    <section class="plans-grid">

        <!-- CHILD PANEL -->

        <article
            class="plan-card reveal reveal-delay-1"
            id="card-child"
            role="button"
            tabindex="0"
            aria-pressed="false"
            onclick="selectPanel(
                'Child',
                <?php echo (float) $child_price; ?>
            )"
            onkeydown="handlePlanKeyboard(
                event,
                'Child',
                <?php echo (float) $child_price; ?>
            )"
        >
            <div class="plan-card-content">
                <div class="plan-top-row">
                    <div class="plan-icon-group">
                        <div class="plan-icon">
                            <i class="fa-solid fa-cube"></i>
                        </div>

                        <div class="plan-badge">
                            <i class="fa-solid fa-seedling"></i>
                            Starter
                        </div>
                    </div>

                    <div class="plan-selection">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>

                <h3 class="plan-name">
                    Child Panel
                </h3>

                <p class="plan-description">
                    An affordable managed panel connected exclusively to
                    the LikexFollow API.
                </p>

                <div class="plan-price-row">
                    <span class="price-symbol">$</span>

                    <span class="plan-price">
                        <?php echo htmlspecialchars($child_price); ?>
                    </span>

                    <span class="price-period">
                        / month
                    </span>
                </div>

                <div class="api-rule">
                    <div class="api-rule-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>

                    <div>
                        <strong>LikexFollow API only</strong>

                        <span>
                            This panel remains connected only to our API.
                            External APIs cannot be added.
                        </span>
                    </div>
                </div>

                <ul class="plan-features">
                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Exclusively connected to LikexFollow API
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Low-cost setup for new business owners
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Automated services and pricing updates
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Full order and customer management access
                    </li>
                </ul>

                <div class="plan-footer">
                    <span class="plan-footer-label">
                        Select Child Panel
                    </span>

                    <span class="plan-action">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </div>
        </article>

        <!-- RENTAL PANEL -->

        <article
            class="plan-card reveal reveal-delay-2"
            id="card-rental"
            role="button"
            tabindex="0"
            aria-pressed="false"
            onclick="selectPanel(
                'Rental',
                <?php echo (float) $rental_price; ?>
            )"
            onkeydown="handlePlanKeyboard(
                event,
                'Rental',
                <?php echo (float) $rental_price; ?>
            )"
        >
            <div class="plan-card-content">
                <div class="plan-top-row">
                    <div class="plan-icon-group">
                        <div class="plan-icon">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>

                        <div class="plan-badge recommended">
                            <i class="fa-solid fa-crown"></i>
                            Full control
                        </div>
                    </div>

                    <div class="plan-selection">
                        <i class="fa-solid fa-check"></i>
                    </div>
                </div>

                <h3 class="plan-name">
                    Rental Panel
                </h3>

                <p class="plan-description">
                    A flexible professional panel with full control over
                    APIs, services and profit margins.
                </p>

                <div class="plan-price-row">
                    <span class="price-symbol">$</span>

                    <span class="plan-price">
                        <?php echo htmlspecialchars($rental_price); ?>
                    </span>

                    <span class="price-period">
                        / month
                    </span>
                </div>

                <div class="api-rule">
                    <div class="api-rule-icon">
                        <i class="fa-solid fa-unlock-keyhole"></i>
                    </div>

                    <div>
                        <strong>Use any API provider</strong>

                        <span>
                            Add LikexFollow or connect any third-party API
                            provider according to your business needs.
                        </span>
                    </div>
                </div>

                <ul class="plan-features">
                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Add and manage unlimited API providers
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Complete administrative panel control
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Configure custom prices and profit margins
                    </li>

                    <li>
                        <span class="feature-check">
                            <i class="fa-solid fa-check"></i>
                        </span>

                        Flexible setup for advanced panel businesses
                    </li>
                </ul>

                <div class="plan-footer">
                    <span class="plan-footer-label">
                        Select Rental Panel
                    </span>

                    <span class="plan-action">
                        <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </div>
        </article>

    </section>

    <!-- =================================================================
         SELECTED PLAN BAR
    ================================================================== -->

    <section
        class="selected-plan-bar"
        id="selectedPlanBar"
    >
        <div class="selected-plan-overflow">
            <div class="selected-plan-inner">
                <div class="selected-plan-info">
                    <div
                        class="selected-plan-icon"
                        id="selectedBarIcon"
                    >
                        <i class="fa-solid fa-cube"></i>
                    </div>

                    <div class="selected-plan-copy">
                        <strong id="selectedBarTitle">
                            Child Panel selected
                        </strong>

                        <span id="selectedBarDescription">
                            Connected exclusively to LikexFollow API.
                        </span>
                    </div>
                </div>

                <div class="selected-plan-price">
                    <span>Monthly price</span>

                    <strong>
                        $<span id="selectedBarPrice">0</span>
                    </strong>
                </div>
            </div>
        </div>
    </section>

    <!-- =================================================================
         CHECKOUT
    ================================================================== -->

    <section
        class="checkout-section"
        id="checkout-section"
    >
        <div class="checkout-overflow">
            <div class="checkout-card">

                <!-- =====================================================
                     SELECTED PLAN SUMMARY
                ====================================================== -->

                <aside class="checkout-summary">
                    <div class="summary-inner">
                        <div class="summary-badge">
                            <i class="fa-solid fa-circle-check"></i>
                            Selected plan
                        </div>

                        <div
                            class="summary-plan-icon"
                            id="summaryPlanIcon"
                        >
                            <i class="fa-solid fa-cube"></i>
                        </div>

                        <div>
                            <h3
                                class="summary-plan-name"
                                id="summaryPlanName"
                            >
                                Child Panel
                            </h3>

                            <p
                                class="summary-description"
                                id="summaryPlanDescription"
                            >
                                Connected exclusively to the LikexFollow API.
                            </p>
                        </div>

                        <div class="summary-price-row">
                            <span class="summary-price-symbol">$</span>

                            <span
                                class="summary-price-value"
                                id="summaryPlanPrice"
                            >
                                0
                            </span>

                            <span class="summary-price-period">
                                / month
                            </span>
                        </div>

                        <ul class="summary-points">
                            <li>
                                <i class="fa-solid fa-check"></i>
                                Secure admin account
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>
                                Custom domain setup
                            </li>

                            <li>
                                <i class="fa-solid fa-check"></i>
                                Monthly subscription
                            </li>
                        </ul>

                        <div class="summary-security">
                            <i class="fa-solid fa-shield-halved"></i>

                            Your configuration is securely processed.
                        </div>
                    </div>
                </aside>

                <!-- =====================================================
                     CONFIGURATION FORM
                ====================================================== -->

                <div class="checkout-form-area">
                    <div class="checkout-form-header">
                        <div class="checkout-form-title">
                            <h3>
                                Configure your
                                <span
                                    id="selected-type-text"
                                    style="color:var(--primary);"
                                ></span>
                            </h3>

                            <p>
                                Enter your domain and administrator account
                                details.
                            </p>
                        </div>

                        <div class="secure-badge">
                            <i class="fa-solid fa-lock"></i>
                            Secure checkout
                        </div>
                    </div>

                    <form id="rentalForm">
                        <input
                            type="hidden"
                            id="panel_type"
                            name="panel_type"
                            value=""
                        >

                        <!-- DOMAIN -->

                        <div class="form-field full-width">
                            <div class="field-label-row">
                                <label
                                    class="field-label"
                                    for="domainInput"
                                >
                                    <i class="fa-solid fa-globe"></i>
                                    Domain name
                                </label>

                                <span class="field-required">
                                    Required
                                </span>
                            </div>

                            <div class="input-shell">
                                <span class="input-icon">
                                    <i class="fa-solid fa-link"></i>
                                </span>

                                <span class="domain-prefix">
                                    https://
                                </span>

                                <input
                                    type="text"
                                    id="domainInput"
                                    name="domain"
                                    class="apple-input domain-input"
                                    placeholder="yourdomain"
                                    autocomplete="off"
                                    autocapitalize="none"
                                    spellcheck="false"
                                    required
                                >

                                <span class="domain-suffix">
                                    .com
                                </span>
                            </div>

                            <div class="domain-preview">
                                <span class="domain-preview-label">
                                    <i class="fa-solid fa-eye"></i>
                                    Live preview
                                </span>

                                <span
                                    class="domain-preview-value"
                                    id="domainPreview"
                                >
                                    https://yourdomain.com
                                </span>
                            </div>

                            <div class="field-help">
                                <i class="fa-solid fa-circle-info"></i>

                                <span>
                                    Point your domain nameservers to our server
                                    before purchasing.
                                </span>
                            </div>
                        </div>

                        <div class="form-grid">

                            <!-- EMAIL -->

                            <div class="form-field">
                                <div class="field-label-row">
                                    <label
                                        class="field-label"
                                        for="adminEmail"
                                    >
                                        <i class="fa-solid fa-envelope"></i>
                                        Admin email
                                    </label>

                                    <span class="field-required">
                                        Required
                                    </span>
                                </div>

                                <div class="input-shell">
                                    <span class="input-icon">
                                        <i class="fa-solid fa-at"></i>
                                    </span>

                                    <input
                                        type="email"
                                        id="adminEmail"
                                        name="admin_email"
                                        class="apple-input"
                                        placeholder="admin@domain.com"
                                        autocomplete="email"
                                        required
                                    >
                                </div>
                            </div>

                            <!-- PASSWORD -->

                            <div class="form-field">
                                <div class="field-label-row">
                                    <label
                                        class="field-label"
                                        for="adminPassword"
                                    >
                                        <i class="fa-solid fa-key"></i>
                                        Admin password
                                    </label>

                                    <span class="field-required">
                                        Required
                                    </span>
                                </div>

                                <div class="input-shell">
                                    <span class="input-icon">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input
                                        type="password"
                                        id="adminPassword"
                                        name="admin_pass"
                                        class="apple-input"
                                        placeholder="Secure password"
                                        autocomplete="new-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        id="passwordToggle"
                                        aria-label="Show password"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div
                                    class="password-strength"
                                    id="passwordStrength"
                                >
                                    <div class="strength-header">
                                        <span>Password strength</span>

                                        <strong id="strengthText">
                                            Weak
                                        </strong>
                                    </div>

                                    <div class="strength-track">
                                        <span class="strength-segment"></span>
                                        <span class="strength-segment"></span>
                                        <span class="strength-segment"></span>
                                        <span class="strength-segment"></span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- CURRENCY -->

                        <div class="form-field">
                            <div class="field-label-row">
                                <label
                                    class="field-label"
                                    for="currencySelect"
                                >
                                    <i class="fa-solid fa-coins"></i>
                                    Default currency
                                </label>

                                <span class="field-required">
                                    Required
                                </span>
                            </div>

                            <div class="input-shell">
                                <span class="input-icon">
                                    <i class="fa-solid fa-wallet"></i>
                                </span>

                                <select
                                    id="currencySelect"
                                    name="currency"
                                    class="apple-input"
                                    required
                                >
                                    <option value="USD">
                                        USD - US Dollar
                                    </option>

                                    <option value="PKR">
                                        PKR - Pakistani Rupee
                                    </option>

                                    <option value="INR">
                                        INR - Indian Rupee
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- NOTICE -->

                        <div class="checkout-notice">
                            <div class="checkout-notice-icon">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>

                            <div>
                                <strong>
                                    Verify your information
                                </strong>

                                <span>
                                    Check the domain spelling, email address
                                    and password before creating your panel.
                                </span>
                            </div>
                        </div>

                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="action-button"
                            id="submitBtn"
                        >
                            <i class="fa-solid fa-rocket"></i>

                            <span>Create Panel for</span>

                            <span class="action-price">
                                $<span id="btn-price">0</span>
                            </span>

                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- =================================================================
         HISTORY SECTION
    ================================================================== -->

    <section class="history-section reveal">
        <div class="section-header">
            <div class="section-header-copy">
                <h2>Your rented panels</h2>

                <p>
                    View your domain, panel type, status and administrator
                    login information.
                </p>
            </div>

            <div class="section-chip">
                <i class="fa-solid fa-server"></i>
                <span>Panel management</span>
            </div>
        </div>

        <div class="history-card">
            <div class="history-header">
                <div class="history-title-group">
                    <div class="history-icon">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>

                    <div>
                        <h2>My Panels</h2>

                        <p>
                            Panels connected to your account
                        </p>
                    </div>
                </div>

                <div class="history-count">
                    <i class="fa-solid fa-cube"></i>

                    <span>
                        <?php echo count($my_panels); ?>
                        panel<?php echo count($my_panels) === 1 ? '' : 's'; ?>
                    </span>
                </div>
            </div>

            <?php if (count($my_panels) > 0): ?>

                <div class="table-responsive">
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Panel Type</th>
                                <th>Monthly Price</th>
                                <th>Status</th>
                                <th>Login Details</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($my_panels as $p): ?>
                            <tr>
                                <td data-label="Domain">
                                    <a
                                        class="panel-domain-link"
                                        href="https://<?php echo htmlspecialchars($p['domain']); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <span class="domain-link-icon">
                                            <i class="fa-solid fa-globe"></i>
                                        </span>

                                        <span class="domain-link-text">
                                            <?php echo htmlspecialchars($p['domain']); ?>
                                        </span>
                                    </a>
                                </td>

                                <td data-label="Panel Type">
                                    <span class="panel-type-pill">
                                        <i class="fa-solid fa-cube"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $p['panel_type']
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td data-label="Monthly Price">
                                    <span class="panel-price-cell">
                                        $<?php
                                        echo htmlspecialchars(
                                            $p['price_per_month']
                                        );
                                        ?>
                                    </span>
                                </td>

                                <td data-label="Status">
                                    <?php
                                    $status = strtolower($p['status']);

                                    if ($status === 'active') {
                                        echo '
                                            <span class="
                                                status-pill
                                                status-active
                                            ">
                                                Active
                                            </span>
                                        ';
                                    } elseif ($status === 'suspended') {
                                        echo '
                                            <span class="
                                                status-pill
                                                status-suspended
                                            ">
                                                Suspended
                                            </span>
                                        ';
                                    } else {
                                        echo '
                                            <span class="
                                                status-pill
                                                status-pending
                                            ">
                                                Pending
                                            </span>
                                        ';
                                    }
                                    ?>
                                </td>

                                <td data-label="Login Details">
                                    <button
                                        type="button"
                                        class="details-button admin-details-button"
                                        data-email="<?php
                                            echo htmlspecialchars(
                                                $p['admin_user'],
                                                ENT_QUOTES
                                            );
                                        ?>"
                                        data-password="<?php
                                            echo htmlspecialchars(
                                                $p['admin_pass'],
                                                ENT_QUOTES
                                            );
                                        ?>"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                        View details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>

                    <h3>No rented panels yet</h3>

                    <p>
                        Select a plan above and complete the configuration
                        form to launch your first panel.
                    </p>
                </div>

            <?php endif; ?>
        </div>
    </section>

    <footer class="page-footer">
        <i class="fa-solid fa-shield-halved"></i>

        Secure panel configuration powered by LikexFollow
    </footer>

</main>

<script>
    const prefersReducedMotion =
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /*
    |--------------------------------------------------------------------------
    | Plan Information
    |--------------------------------------------------------------------------
    */

    const planInformation = {
        Child: {
            name: 'Child Panel',

            description:
                'Connected exclusively to the LikexFollow API. External APIs cannot be added.',

            shortDescription:
                'Only the LikexFollow API can be used.',

            icon:
                '<i class="fa-solid fa-cube"></i>'
        },

        Rental: {
            name: 'Rental Panel',

            description:
                'Connect LikexFollow or any third-party API provider according to your business requirements.',

            shortDescription:
                'Use LikexFollow or any API provider you prefer.',

            icon:
                '<i class="fa-solid fa-layer-group"></i>'
        }
    };


    /*
    |--------------------------------------------------------------------------
    | Element References
    |--------------------------------------------------------------------------
    */

    const childCard =
        document.getElementById('card-child');

    const rentalCard =
        document.getElementById('card-rental');

    const checkoutSection =
        document.getElementById('checkout-section');

    const selectedPlanBar =
        document.getElementById('selectedPlanBar');

    const stepperProgress =
        document.getElementById('stepperProgress');

    const stepItem1 =
        document.getElementById('stepItem1');

    const stepItem2 =
        document.getElementById('stepItem2');

    const stepItem3 =
        document.getElementById('stepItem3');

    const domainInput =
        document.getElementById('domainInput');

    const domainPreview =
        document.getElementById('domainPreview');

    const passwordInput =
        document.getElementById('adminPassword');

    const passwordToggle =
        document.getElementById('passwordToggle');

    const passwordStrength =
        document.getElementById('passwordStrength');

    const strengthText =
        document.getElementById('strengthText');


    /*
    |--------------------------------------------------------------------------
    | Select Plan
    |--------------------------------------------------------------------------
    */

    function selectPanel(type, price) {
        const selectedCard =
            type === 'Child'
                ? childCard
                : rentalCard;

        const unselectedCard =
            type === 'Child'
                ? rentalCard
                : childCard;

        selectedCard.classList.add('active-card');
        selectedCard.setAttribute('aria-pressed', 'true');

        unselectedCard.classList.remove('active-card');
        unselectedCard.setAttribute('aria-pressed', 'false');

        const formattedPrice =
            formatPrice(price);

        document.getElementById('panel_type').value =
            type;

        document.getElementById('selected-type-text').textContent =
            planInformation[type].name;

        animateNumberText(
            document.getElementById('btn-price'),
            formattedPrice
        );

        document.getElementById('summaryPlanName').textContent =
            planInformation[type].name;

        document.getElementById('summaryPlanDescription').textContent =
            planInformation[type].description;

        document.getElementById('summaryPlanIcon').innerHTML =
            planInformation[type].icon;

        animateNumberText(
            document.getElementById('summaryPlanPrice'),
            formattedPrice
        );

        document.getElementById('selectedBarTitle').textContent =
            planInformation[type].name + ' selected';

        document.getElementById('selectedBarDescription').textContent =
            planInformation[type].shortDescription;

        document.getElementById('selectedBarIcon').innerHTML =
            planInformation[type].icon;

        animateNumberText(
            document.getElementById('selectedBarPrice'),
            formattedPrice
        );

        selectedPlanBar.classList.add('show');
        checkoutSection.classList.add('show');

        updateStepper(2);

        window.setTimeout(() => {
            selectedPlanBar.scrollIntoView({
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                block: 'center'
            });
        }, 210);
    }


    /*
    |--------------------------------------------------------------------------
    | Keyboard Plan Selection
    |--------------------------------------------------------------------------
    */

    function handlePlanKeyboard(event, type, price) {
        if (
            event.key === 'Enter' ||
            event.key === ' '
        ) {
            event.preventDefault();
            selectPanel(type, price);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Format Price
    |--------------------------------------------------------------------------
    */

    function formatPrice(price) {
        const number = Number(price);

        if (Number.isNaN(number)) {
            return String(price);
        }

        return Number.isInteger(number)
            ? number.toString()
            : number.toFixed(2);
    }


    /*
    |--------------------------------------------------------------------------
    | Animate Price Text
    |--------------------------------------------------------------------------
    */

    function animateNumberText(element, finalValue) {
        if (
            prefersReducedMotion ||
            typeof element.animate !== 'function'
        ) {
            element.textContent = finalValue;
            return;
        }

        element.animate(
            [
                {
                    opacity: 0,
                    transform: 'translateY(6px)'
                },
                {
                    opacity: 1,
                    transform: 'translateY(0)'
                }
            ],
            {
                duration: 320,
                easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
            }
        );

        element.textContent = finalValue;
    }


    /*
    |--------------------------------------------------------------------------
    | Stepper
    |--------------------------------------------------------------------------
    */

    function updateStepper(step) {
        const items = [
            stepItem1,
            stepItem2,
            stepItem3
        ];

        items.forEach((item, index) => {
            const itemStep = index + 1;

            item.classList.remove(
                'active',
                'completed'
            );

            const circle =
                item.querySelector('.stepper-circle');

            if (itemStep < step) {
                item.classList.add('completed');

                circle.innerHTML =
                    '<i class="fa-solid fa-check"></i>';
            } else if (itemStep === step) {
                item.classList.add('active');
                circle.innerHTML =
                    `<span>${itemStep}</span>`;
            } else {
                circle.innerHTML =
                    `<span>${itemStep}</span>`;
            }
        });

        if (step === 1) {
            stepperProgress.style.width = '0%';
        }

        if (step === 2) {
            stepperProgress.style.width = '50%';
        }

        if (step === 3) {
            stepperProgress.style.width = '100%';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Plan Card Pointer Glow
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.plan-card').forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const rect =
                card.getBoundingClientRect();

            const x =
                (
                    (event.clientX - rect.left) /
                    rect.width
                ) * 100;

            const y =
                (
                    (event.clientY - rect.top) /
                    rect.height
                ) * 100;

            card.style.setProperty(
                '--pointer-x',
                `${x}%`
            );

            card.style.setProperty(
                '--pointer-y',
                `${y}%`
            );
        });

        card.addEventListener('pointerleave', () => {
            card.style.setProperty(
                '--pointer-x',
                '50%'
            );

            card.style.setProperty(
                '--pointer-y',
                '50%'
            );
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Live Domain Preview
    |--------------------------------------------------------------------------
    */

    function cleanDomainValue(value) {
        return value
            .toLowerCase()
            .trim()
            .replace(/^https?:\/\//, '')
            .replace(/^www\./, '')
            .replace(/\.com$/i, '')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/--+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    domainInput.addEventListener('input', () => {
        const cleanedValue =
            cleanDomainValue(domainInput.value);

        if (domainInput.value !== cleanedValue) {
            domainInput.value = cleanedValue;
        }

        const previewDomain =
            cleanedValue || 'yourdomain';

        domainPreview.textContent =
            `https://${previewDomain}.com`;
    });


    /*
    |--------------------------------------------------------------------------
    | Password Visibility
    |--------------------------------------------------------------------------
    */

    passwordToggle.addEventListener('click', () => {
        const passwordIsHidden =
            passwordInput.type === 'password';

        passwordInput.type =
            passwordIsHidden
                ? 'text'
                : 'password';

        passwordToggle.innerHTML =
            passwordIsHidden
                ? '<i class="fa-solid fa-eye-slash"></i>'
                : '<i class="fa-solid fa-eye"></i>';

        passwordToggle.setAttribute(
            'aria-label',
            passwordIsHidden
                ? 'Hide password'
                : 'Show password'
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Password Strength
    |--------------------------------------------------------------------------
    */

    passwordInput.addEventListener('input', () => {
        const password =
            passwordInput.value;

        passwordStrength.className =
            'password-strength';

        if (!password.length) {
            return;
        }

        passwordStrength.classList.add('visible');

        let score = 0;

        if (password.length >= 6) {
            score++;
        }

        if (password.length >= 10) {
            score++;
        }

        if (
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password)
        ) {
            score++;
        }

        if (
            /\d/.test(password) &&
            /[^A-Za-z0-9]/.test(password)
        ) {
            score++;
        }

        score = Math.max(1, Math.min(score, 4));

        passwordStrength.classList.add(
            `level-${score}`
        );

        const strengthNames = {
            1: 'Weak',
            2: 'Fair',
            3: 'Strong',
            4: 'Excellent'
        };

        strengthText.textContent =
            strengthNames[score];
    });


    /*
    |--------------------------------------------------------------------------
    | Reveal Animations
    |--------------------------------------------------------------------------
    */

    const revealElements =
        document.querySelectorAll('.reveal');

    const revealObserver =
        new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                });
            },
            {
                threshold: 0.12,
                rootMargin: '0px 0px -30px 0px'
            }
        );

    revealElements.forEach((element) => {
        revealObserver.observe(element);
    });


    /*
    |--------------------------------------------------------------------------
    | Button Ripple
    |--------------------------------------------------------------------------
    */

    function createRipple(event) {
        if (prefersReducedMotion) {
            return;
        }

        const button =
            event.currentTarget;

        const rect =
            button.getBoundingClientRect();

        const diameter =
            Math.max(
                button.clientWidth,
                button.clientHeight
            );

        const radius =
            diameter / 2;

        const ripple =
            document.createElement('span');

        ripple.className =
            'button-ripple';

        ripple.style.width =
            ripple.style.height =
                `${diameter}px`;

        ripple.style.left =
            `${event.clientX - rect.left - radius}px`;

        ripple.style.top =
            `${event.clientY - rect.top - radius}px`;

        const previousRipple =
            button.querySelector('.button-ripple');

        if (previousRipple) {
            previousRipple.remove();
        }

        button.appendChild(ripple);

        window.setTimeout(() => {
            ripple.remove();
        }, 750);
    }

    document
        .querySelectorAll('.action-button')
        .forEach((button) => {
            button.addEventListener(
                'click',
                createRipple
            );
        });


    /*
    |--------------------------------------------------------------------------
    | Help Modal
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('helpButton')
        .addEventListener('click', () => {
            Swal.fire({
                title: 'Panel Types',

                html: `
                    <div style="
                        display:grid;
                        gap:10px;
                        margin-top:12px;
                        text-align:left;
                    ">
                        <div style="
                            padding:12px;
                            border:1px solid rgba(0,113,227,.12);
                            border-radius:12px;
                            background:#f5f9ff;
                        ">
                            <strong style="
                                display:block;
                                color:#1d1d1f;
                                font-size:.75rem;
                            ">
                                Child Panel
                            </strong>

                            <span style="
                                display:block;
                                margin-top:4px;
                                color:#6e6e73;
                                font-size:.68rem;
                                line-height:1.55;
                            ">
                                Uses only the LikexFollow API.
                                Third-party APIs cannot be connected.
                            </span>
                        </div>

                        <div style="
                            padding:12px;
                            border:1px solid rgba(0,113,227,.12);
                            border-radius:12px;
                            background:#f5f9ff;
                        ">
                            <strong style="
                                display:block;
                                color:#1d1d1f;
                                font-size:.75rem;
                            ">
                                Rental Panel
                            </strong>

                            <span style="
                                display:block;
                                margin-top:4px;
                                color:#6e6e73;
                                font-size:.68rem;
                                line-height:1.55;
                            ">
                                Allows LikexFollow and third-party API
                                providers with full administrative control.
                            </span>
                        </div>
                    </div>
                `,

                icon: 'info',
                confirmButtonText: 'Understood',
                confirmButtonColor: '#7c3aed'
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    /*
    |--------------------------------------------------------------------------
    | Copy Text
    |--------------------------------------------------------------------------
    */

    async function copyText(value, button) {
        try {
            await navigator.clipboard.writeText(value);

            const oldContent =
                button.innerHTML;

            button.innerHTML =
                '<i class="fa-solid fa-check"></i>';

            window.setTimeout(() => {
                button.innerHTML =
                    oldContent;
            }, 1300);

        } catch (error) {
            Swal.showValidationMessage(
                'Unable to copy automatically.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Details Buttons
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.admin-details-button')
        .forEach((button) => {
            button.addEventListener('click', () => {
                const email =
                    button.dataset.email || '';

                const password =
                    button.dataset.password || '';

                Swal.fire({
                    title: 'Admin Details',

                    html: `
                        <div class="admin-modal-grid">
                            <div class="admin-detail-box">
                                <div class="admin-detail-copy">
                                    <span>Admin Email</span>

                                    <strong>
                                        ${escapeHtml(email)}
                                    </strong>
                                </div>

                                <button
                                    type="button"
                                    class="copy-detail-button"
                                    id="copyEmailButton"
                                    aria-label="Copy email"
                                >
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>

                            <div class="admin-detail-box">
                                <div class="admin-detail-copy">
                                    <span>Admin Password</span>

                                    <strong>
                                        ${escapeHtml(password)}
                                    </strong>
                                </div>

                                <button
                                    type="button"
                                    class="copy-detail-button"
                                    id="copyPasswordButton"
                                    aria-label="Copy password"
                                >
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    `,

                    icon: 'info',
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#7c3aed',

                    didOpen: () => {
                        const copyEmailButton =
                            document.getElementById(
                                'copyEmailButton'
                            );

                        const copyPasswordButton =
                            document.getElementById(
                                'copyPasswordButton'
                            );

                        copyEmailButton.addEventListener(
                            'click',
                            () => {
                                copyText(
                                    email,
                                    copyEmailButton
                                );
                            }
                        );

                        copyPasswordButton.addEventListener(
                            'click',
                            () => {
                                copyText(
                                    password,
                                    copyPasswordButton
                                );
                            }
                        );
                    }
                });
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Form Submission
    |--------------------------------------------------------------------------
    */

    document
        .getElementById('rentalForm')
        .addEventListener('submit', function (event) {
            event.preventDefault();

            const selectedPanel =
                document.getElementById('panel_type').value;

            if (!selectedPanel) {
                Swal.fire({
                    title: 'Select a panel',

                    text:
                        'Please select Child Panel or Rental Panel before continuing.',

                    icon: 'warning',
                    confirmButtonColor: '#7c3aed'
                });

                return;
            }

            const submitButton =
                document.getElementById('submitBtn');

            const originalButtonContent =
                submitButton.innerHTML;

            const selectedPrice =
                document.getElementById('btn-price').textContent;

            const selectedDomain =
                domainInput.value.trim();

            Swal.fire({
                title: 'Confirm your order',

                html: `
                    <div style="
                        display:grid;
                        gap:9px;
                        margin-top:10px;
                        text-align:left;
                    ">
                        <div style="
                            display:flex;
                            justify-content:space-between;
                            gap:12px;
                            padding:10px 11px;
                            border:1px solid rgba(124,58,237,.11);
                            border-radius:11px;
                            background:#f5f9ff;
                        ">
                            <span style="
                                color:#8b8394;
                                font-size:.66rem;
                                font-weight:750;
                            ">
                                Panel
                            </span>

                            <strong style="
                                color:#1d1d1f;
                                font-size:.7rem;
                            ">
                                ${escapeHtml(selectedPanel)} Panel
                            </strong>
                        </div>

                        <div style="
                            display:flex;
                            justify-content:space-between;
                            gap:12px;
                            padding:10px 11px;
                            border:1px solid rgba(124,58,237,.11);
                            border-radius:11px;
                            background:#f5f9ff;
                        ">
                            <span style="
                                color:#8b8394;
                                font-size:.66rem;
                                font-weight:750;
                            ">
                                Domain
                            </span>

                            <strong style="
                                color:#1d1d1f;
                                font-size:.7rem;
                                word-break:break-all;
                            ">
                                ${escapeHtml(selectedDomain)}.com
                            </strong>
                        </div>

                        <div style="
                            display:flex;
                            justify-content:space-between;
                            gap:12px;
                            padding:10px 11px;
                            border:1px solid rgba(124,58,237,.11);
                            border-radius:11px;
                            background:#f5f9ff;
                        ">
                            <span style="
                                color:#8b8394;
                                font-size:.66rem;
                                font-weight:750;
                            ">
                                Monthly price
                            </span>

                            <strong style="
                                color:#0071e3;
                                font-size:.72rem;
                            ">
                                $${escapeHtml(selectedPrice)}
                            </strong>
                        </div>
                    </div>
                `,

                icon: 'question',
                showCancelButton: true,

                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#8e8e93',

                confirmButtonText: 'Confirm purchase',
                cancelButtonText: 'Cancel',

                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                updateStepper(3);

                submitButton.innerHTML = `
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Creating your panel...</span>
                `;

                submitButton.disabled = true;

                const formData =
                    new FormData(this);

                fetch('rent_panel_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async (response) => {
                    const contentType =
                        response.headers.get(
                            'content-type'
                        ) || '';

                    if (
                        !contentType.includes(
                            'application/json'
                        )
                    ) {
                        throw new Error(
                            'The server returned an invalid response.'
                        );
                    }

                    const data =
                        await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message ||
                            'The request could not be completed.'
                        );
                    }

                    return data;
                })
                .then((data) => {
                    submitButton.innerHTML =
                        originalButtonContent;

                    submitButton.disabled =
                        false;

                    if (data.status === 'success') {
                        updateStepper(3);

                        Swal.fire({
                            title: 'Panel order created',

                            text:
                                data.message ||
                                'Your panel order has been placed successfully.',

                            icon: 'success',

                            confirmButtonColor:
                                '#7c3aed',

                            confirmButtonText:
                                'Continue'
                        }).then(() => {
                            window.location.reload();
                        });

                        return;
                    }

                    updateStepper(2);

                    Swal.fire({
                        title: 'Unable to create panel',

                        text:
                            data.message ||
                            'Something went wrong while processing your request.',

                        icon: 'error',
                        confirmButtonColor: '#7c3aed'
                    });
                })
                .catch((error) => {
                    submitButton.innerHTML =
                        originalButtonContent;

                    submitButton.disabled =
                        false;

                    updateStepper(2);

                    Swal.fire({
                        title: 'Request failed',

                        text:
                            error.message ||
                            'The server could not process your request.',

                        icon: 'error',
                        confirmButtonColor: '#7c3aed'
                    });
                });
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    updateStepper(1);
</script>

</body>
</html>