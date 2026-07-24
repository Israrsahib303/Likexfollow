<?php
include '_smm_header.php'; // Using SMM Header for consistent theme

$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

// --- 1. CREATE NEW TICKET ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_ticket'])) {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if ($subject && $message) {
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO tickets (user_id, subject, status, created_at) VALUES (?, ?, 'pending', NOW())")->execute([$user_id, $subject]);
            $ticket_id = $db->lastInsertId();
            $db->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")->execute([$ticket_id, $message]);
            $db->commit();
            $success = "Ticket #$ticket_id created successfully!";
            echo "<script>window.location.href='tickets.php?id=$ticket_id';</script>";
        } catch (Exception $e) { $db->rollBack(); $error = $e->getMessage(); }
    }
}

// --- 2. REPLY TO TICKET ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_ticket'])) {
    $tid = (int)$_POST['ticket_id'];
    $msg = sanitize($_POST['message']);
    if ($msg) {
        $db->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")->execute([$tid, $msg]);
        $db->prepare("UPDATE tickets SET status='pending', updated_at=NOW() WHERE id=? AND user_id=?")->execute([$tid, $user_id]);
        echo "<script>window.location.href='tickets.php?id=$tid';</script>";
    }
}

// Fetch All Tickets
$tickets = $db->prepare("SELECT * FROM tickets WHERE user_id=? ORDER BY updated_at DESC");
$tickets->execute([$user_id]);
$all_tickets = $tickets->fetchAll();

// Fetch Active Ticket Data
$active_ticket = null;
$messages = [];
if (isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    $t_stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND user_id=?");
    $t_stmt->execute([$tid, $user_id]);
    $active_ticket = $t_stmt->fetch();
    
    if ($active_ticket) {
        $msgs_stmt = $db->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
        $msgs_stmt->execute([$tid]);
        $messages = $msgs_stmt->fetchAll();
    }
}
?>

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
/* =========================================================
   PREMIUM SUPPORT CENTER — WHITE + VIOLET UI
   Frontend-only redesign. PHP, forms, links and JS stay intact.
   ========================================================= */

:root {
    --support-bg: #f8f7fc;
    --support-surface: #ffffff;
    --support-surface-soft: #fbfaff;
    --support-purple-950: #2e1065;
    --support-purple-900: #3b167f;
    --support-purple-800: #5120a8;
    --support-purple-700: #6d35dc;
    --support-purple-600: #7c3aed;
    --support-purple-500: #8b5cf6;
    --support-purple-400: #a78bfa;
    --support-purple-300: #c4b5fd;
    --support-purple-200: #ddd6fe;
    --support-purple-100: #ede9fe;
    --support-purple-50: #f7f3ff;
    --support-pink: #ec4899;
    --support-pink-soft: #fce7f3;
    --support-green: #0f9f6e;
    --support-orange: #d96716;
    --support-text: #1e1830;
    --support-text-soft: #746d87;
    --support-line: #ece8f4;
    --support-shadow-xs: 0 2px 8px rgba(67, 35, 108, 0.05);
    --support-shadow-sm: 0 8px 24px rgba(67, 35, 108, 0.08);
    --support-shadow-md: 0 18px 50px rgba(76, 39, 128, 0.12);
    --support-shadow-purple: 0 14px 34px rgba(124, 58, 237, 0.28);
    --support-radius-sm: 12px;
    --support-radius-md: 18px;
    --support-radius-lg: 24px;
    --support-radius-xl: 32px;
    --support-ease: cubic-bezier(.22, 1, .36, 1);
    --support-font: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --support-display: 'Outfit', 'Inter', system-ui, sans-serif;

    /* Compatibility aliases used by the original inline markup */
    --primary-1: var(--support-purple-600);
    --muted: var(--support-text-soft);
    --text: var(--support-text);
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;
    overflow-x: hidden;
    color: var(--support-text);
    background:
        radial-gradient(circle at 7% 10%, rgba(167, 139, 250, .13), transparent 24rem),
        radial-gradient(circle at 94% 86%, rgba(236, 72, 153, .07), transparent 26rem),
        linear-gradient(180deg, #ffffff 0%, var(--support-bg) 48%, #faf9fd 100%);
    font-family: var(--support-font);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.app-screen,
.app-screen *,
.app-screen *::before,
.app-screen *::after,
#ticketModal,
#ticketModal *,
#ticketModal *::before,
#ticketModal *::after {
    box-sizing: border-box;
}

button,
input,
select,
textarea {
    font: inherit;
}

.app-screen {
    position: relative;
    isolation: isolate;
    width: min(100%, 1260px);
    min-width: 0;
    margin: 0 auto;
    padding: clamp(16px, 2.4vw, 30px) clamp(14px, 3vw, 30px) max(88px, env(safe-area-inset-bottom));
}

.app-screen::before,
.app-screen::after {
    content: "";
    position: fixed;
    z-index: -1;
    pointer-events: none;
    border-radius: 50%;
    filter: blur(4px);
    opacity: .75;
}

.app-screen::before {
    width: clamp(220px, 30vw, 440px);
    height: clamp(220px, 30vw, 440px);
    top: 10%;
    left: -12%;
    background: radial-gradient(circle, rgba(196, 181, 253, .22), rgba(196, 181, 253, 0) 68%);
    animation: supportBlobOne 12s ease-in-out infinite alternate;
}

.app-screen::after {
    width: clamp(240px, 34vw, 500px);
    height: clamp(240px, 34vw, 500px);
    right: -14%;
    bottom: -5%;
    background: radial-gradient(circle, rgba(244, 114, 182, .11), rgba(244, 114, 182, 0) 70%);
    animation: supportBlobTwo 14s ease-in-out infinite alternate;
}

/* HERO */
.app-screen .card-storage {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    min-height: 144px;
    margin: 0 0 clamp(18px, 2.2vw, 28px);
    padding: clamp(24px, 4vw, 42px);
    overflow: hidden;
    border: 1px solid rgba(139, 92, 246, .13);
    border-radius: var(--support-radius-xl);
    background:
        linear-gradient(115deg, rgba(255, 255, 255, .97) 0%, rgba(250, 247, 255, .97) 66%, rgba(244, 238, 255, .95) 100%);
    box-shadow: var(--support-shadow-md);
}

.app-screen .card-storage::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(124, 58, 237, .035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(124, 58, 237, .035) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: linear-gradient(90deg, transparent 0%, #000 54%, #000 100%);
    -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 54%, #000 100%);
}

.app-screen .card-storage::after {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -86px;
    top: -164px;
    pointer-events: none;
    border-radius: 50%;
    background:
        radial-gradient(circle at 35% 35%, rgba(255, 255, 255, .85), transparent 18%),
        linear-gradient(135deg, rgba(139, 92, 246, .34), rgba(236, 72, 153, .16));
    box-shadow: 0 0 0 34px rgba(139, 92, 246, .035);
    animation: supportHeroOrb 7s ease-in-out infinite;
}

.app-screen .card-storage > * {
    position: relative;
    z-index: 2;
}

.app-screen .hero-title {
    position: relative;
    margin: 0;
    color: var(--support-text);
    font-family: var(--support-display);
    font-size: clamp(26px, 3vw, 38px);
    font-weight: 800;
    line-height: 1.06;
    letter-spacing: -.035em;
}

.app-screen .hero-title::after {
    content: "";
    display: block;
    width: 52px;
    height: 4px;
    margin-top: 13px;
    border-radius: 99px;
    background: linear-gradient(90deg, var(--support-purple-600), var(--support-pink));
    box-shadow: 0 4px 12px rgba(124, 58, 237, .22);
}

.app-screen .hero-sub {
    margin: 11px 0 0;
    color: var(--support-text-soft);
    font-size: clamp(13px, 1.5vw, 15px);
    font-weight: 500;
    line-height: 1.6;
}

.app-screen .floating-cta {
    position: relative;
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-height: 50px;
    padding: 12px 23px;
    overflow: hidden;
    border: 0;
    border-radius: 999px;
    color: #fff;
    background: linear-gradient(135deg, var(--support-purple-700) 0%, var(--support-purple-500) 58%, #b15cf1 100%);
    box-shadow: var(--support-shadow-purple), inset 0 1px 0 rgba(255, 255, 255, .35);
    font-size: 14px;
    font-weight: 700;
    letter-spacing: .01em;
    text-decoration: none;
    cursor: pointer;
    transition: transform .3s var(--support-ease), box-shadow .3s var(--support-ease), filter .3s ease;
    -webkit-tap-highlight-color: transparent;
}

.app-screen .floating-cta::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -85%;
    width: 45%;
    height: 200%;
    transform: rotate(25deg);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .45), transparent);
    transition: left .65s var(--support-ease);
}

.app-screen .floating-cta i {
    font-size: 13px;
    transition: transform .3s var(--support-ease);
}

.app-screen .floating-cta:hover {
    transform: translateY(-3px);
    filter: saturate(1.08);
    box-shadow: 0 18px 40px rgba(124, 58, 237, .35), inset 0 1px 0 rgba(255, 255, 255, .38);
}

.app-screen .floating-cta:hover::before {
    left: 135%;
}

.app-screen .floating-cta:hover i {
    transform: rotate(90deg);
}

.app-screen .floating-cta:active {
    transform: translateY(0) scale(.97);
}

/* TWO-COLUMN SUPPORT WORKSPACE */
.app-screen .support-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: clamp(18px, 2.2vw, 28px);
    min-width: 0;
}

/* TICKET LIST */
.app-screen .ticket-list-card {
    position: relative;
    min-width: 0;
    overflow: hidden;
    border: 1px solid rgba(86, 51, 128, .09);
    border-radius: var(--support-radius-lg);
    background: rgba(255, 255, 255, .93);
    box-shadow: var(--support-shadow-sm);
}

.app-screen .ticket-list-card > div:first-child {
    position: relative;
    padding: 21px 22px !important;
    border-bottom-color: var(--support-line) !important;
    background: linear-gradient(180deg, #fff 0%, #fdfcff 100%);
}

.app-screen .ticket-list-card > div:first-child::after {
    content: "";
    position: absolute;
    right: 22px;
    top: 50%;
    width: 8px;
    height: 8px;
    transform: translateY(-50%);
    border-radius: 50%;
    background: var(--support-purple-500);
    box-shadow: 0 0 0 6px var(--support-purple-100), 0 0 18px rgba(139, 92, 246, .35);
    animation: supportPulse 2.2s ease-in-out infinite;
}

.app-screen .ticket-list-card h3 {
    color: var(--support-text);
    font-family: var(--support-display);
    font-size: 17px !important;
    font-weight: 800 !important;
    letter-spacing: -.01em;
}

.app-screen .list-scroll {
    display: flex;
    gap: 11px;
    min-width: 0;
    padding: 13px;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-snap-type: x proximity;
    overscroll-behavior-inline: contain;
    scrollbar-width: thin;
    scrollbar-color: var(--support-purple-300) transparent;
}

.app-screen .list-scroll::-webkit-scrollbar,
.app-screen .chat-messages::-webkit-scrollbar,
#ticketModal .modal-card::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.app-screen .list-scroll::-webkit-scrollbar-track,
.app-screen .chat-messages::-webkit-scrollbar-track,
#ticketModal .modal-card::-webkit-scrollbar-track {
    background: transparent;
}

.app-screen .list-scroll::-webkit-scrollbar-thumb,
.app-screen .chat-messages::-webkit-scrollbar-thumb,
#ticketModal .modal-card::-webkit-scrollbar-thumb {
    border-radius: 99px;
    background: linear-gradient(180deg, var(--support-purple-300), var(--support-purple-500));
}

.app-screen .list-item {
    position: relative;
    display: flex;
    flex: 0 0 clamp(245px, 76vw, 310px);
    align-items: center;
    gap: 12px;
    min-width: 0;
    min-height: 76px;
    margin: 0;
    padding: 13px;
    overflow: hidden;
    scroll-snap-align: start;
    border: 1px solid var(--support-line);
    border-radius: var(--support-radius-md);
    color: var(--support-text);
    background: var(--support-surface);
    box-shadow: var(--support-shadow-xs);
    text-decoration: none;
    transform: translateZ(0);
    transition:
        transform .3s var(--support-ease),
        border-color .3s ease,
        box-shadow .3s var(--support-ease),
        background .3s ease;
    -webkit-tap-highlight-color: transparent;
}

.app-screen .list-item::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0;
    background: linear-gradient(112deg, rgba(139, 92, 246, .08), transparent 56%, rgba(236, 72, 153, .05));
    transition: opacity .3s ease;
}

.app-screen .list-item:hover {
    z-index: 1;
    transform: translateY(-3px);
    border-color: rgba(139, 92, 246, .26);
    box-shadow: 0 13px 28px rgba(72, 38, 112, .11);
}

.app-screen .list-item:hover::before,
.app-screen .list-item.active::before {
    opacity: 1;
}

.app-screen .list-item.active {
    border-color: rgba(124, 58, 237, .55);
    background: linear-gradient(135deg, #fff 0%, #faf7ff 100%);
    box-shadow: 0 10px 28px rgba(124, 58, 237, .13), inset 3px 0 0 var(--support-purple-600);
}

.app-screen .item-icon {
    position: relative;
    z-index: 1;
    display: grid;
    flex: 0 0 46px;
    width: 46px;
    height: 46px;
    place-items: center;
    border: 1px solid rgba(139, 92, 246, .12);
    border-radius: 15px;
    color: var(--support-purple-700);
    background: linear-gradient(145deg, #fbf9ff, var(--support-purple-100));
    box-shadow: inset 0 1px 0 #fff, 0 7px 16px rgba(124, 58, 237, .1);
    font-size: 17px;
    transition: transform .3s var(--support-ease), color .3s ease, background .3s ease;
}

.app-screen .list-item:hover .item-icon,
.app-screen .list-item.active .item-icon {
    color: #fff;
    background: linear-gradient(135deg, var(--support-purple-700), var(--support-purple-500));
    transform: rotate(-5deg) scale(1.04);
    box-shadow: 0 9px 20px rgba(124, 58, 237, .24);
}

.app-screen .item-content {
    position: relative;
    z-index: 1;
    flex: 1 1 auto;
    min-width: 0;
}

.app-screen .item-title {
    overflow: hidden;
    color: var(--support-text);
    font-size: 14px;
    font-weight: 750;
    line-height: 1.35;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-screen .item-meta {
    margin-top: 5px;
    color: var(--support-text-soft);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .015em;
}

.app-screen .status-pill {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
    padding: 6px 8px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .055em;
    line-height: 1;
    text-transform: uppercase;
}

.app-screen .sp-pending {
    border-color: #fed7aa;
    color: var(--support-orange);
    background: #fff8ee;
}

.app-screen .sp-answered {
    border-color: #a7f3d0;
    color: var(--support-green);
    background: #ecfdf5;
}

.app-screen .sp-closed {
    border-color: #e5e7eb;
    color: #697386;
    background: #f6f7f9;
}

/* CHAT PANEL */
.app-screen .chat-container {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
    height: clamp(560px, 70dvh, 700px);
    min-height: 0;
    overflow: hidden;
    border: 1px solid rgba(86, 51, 128, .11);
    border-radius: var(--support-radius-lg);
    background: rgba(255, 255, 255, .84);
    box-shadow: var(--support-shadow-md);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.app-screen .chat-header {
    position: relative;
    z-index: 2;
    flex: 0 0 auto;
    padding: clamp(19px, 3vw, 26px);
    overflow: hidden;
    border-bottom: 1px solid var(--support-line);
    background: rgba(255, 255, 255, .94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.app-screen .chat-header::before {
    content: "";
    position: absolute;
    top: -48px;
    right: -20px;
    width: 160px;
    height: 110px;
    pointer-events: none;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(167, 139, 250, .22), rgba(167, 139, 250, 0) 70%);
}

.app-screen .chat-header::after {
    content: "";
    position: absolute;
    top: 50%;
    right: clamp(20px, 3vw, 28px);
    width: 10px;
    height: 10px;
    transform: translateY(-50%);
    border: 3px solid #fff;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px #dcfce7, 0 0 16px rgba(34, 197, 94, .42);
}

.app-screen .ch-title {
    position: relative;
    z-index: 1;
    max-width: calc(100% - 42px);
    overflow: hidden;
    color: var(--support-text);
    font-family: var(--support-display);
    font-size: clamp(18px, 2vw, 21px);
    font-weight: 800;
    letter-spacing: -.018em;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-screen .ch-sub {
    position: relative;
    z-index: 1;
    margin-top: 5px;
    color: var(--support-text-soft);
    font-size: 12px;
    font-weight: 550;
    line-height: 1.5;
}

.app-screen .chat-messages {
    position: relative;
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    gap: 18px;
    min-width: 0;
    min-height: 0;
    padding: clamp(20px, 3.3vw, 32px);
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    scroll-behavior: smooth;
    scrollbar-color: var(--support-purple-300) transparent;
    background-color: #faf9fd;
    background-image:
        radial-gradient(circle at 1px 1px, rgba(124, 58, 237, .09) 1px, transparent 0),
        linear-gradient(145deg, rgba(255, 255, 255, .7), rgba(247, 243, 255, .54));
    background-size: 23px 23px, 100% 100%;
}

.app-screen .msg-row {
    display: flex;
    flex: 0 0 auto;
    flex-direction: column;
    max-width: min(78%, 580px);
    animation: supportMessageIn .46s var(--support-ease) both;
}

.app-screen .msg-row.user {
    align-self: flex-end;
    align-items: flex-end;
}

.app-screen .msg-row.admin {
    align-self: flex-start;
    align-items: flex-start;
}

.app-screen .bubble {
    position: relative;
    max-width: 100%;
    padding: 13px 17px;
    overflow-wrap: anywhere;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.65;
    transition: transform .25s var(--support-ease), box-shadow .25s ease;
}

.app-screen .bubble:hover {
    transform: translateY(-2px);
}

.app-screen .bubble-user {
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: 20px 20px 5px 20px;
    color: #fff;
    background: linear-gradient(135deg, var(--support-purple-700), var(--support-purple-500) 64%, #a855f7);
    box-shadow: 0 11px 26px rgba(124, 58, 237, .22), inset 0 1px 0 rgba(255, 255, 255, .23);
}

.app-screen .bubble-admin {
    border: 1px solid rgba(86, 51, 128, .1);
    border-radius: 20px 20px 20px 5px;
    color: var(--support-text);
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 10px 24px rgba(64, 39, 95, .08), inset 0 1px 0 #fff;
}

.app-screen .bubble-user::after,
.app-screen .bubble-admin::after {
    content: "";
    position: absolute;
    bottom: 0;
    width: 11px;
    height: 11px;
    pointer-events: none;
}

.app-screen .bubble-user::after {
    right: -4px;
    border-radius: 0 0 0 10px;
    background: #a855f7;
    clip-path: polygon(0 0, 100% 100%, 0 100%);
}

.app-screen .bubble-admin::after {
    left: -4px;
    border-radius: 0 0 10px;
    background: #fff;
    clip-path: polygon(100% 0, 100% 100%, 0 100%);
}

.app-screen .msg-time {
    margin-top: 7px;
    padding-inline: 3px;
    color: #8c859b;
    font-size: 10px;
    font-weight: 650;
    letter-spacing: .015em;
}

.app-screen .msg-row.user .msg-time::before {
    content: "You • ";
}

.app-screen .msg-row.admin .msg-time::before {
    content: "Support • ";
}

/* MESSAGE COMPOSER */
.app-screen .chat-footer {
    position: relative;
    z-index: 3;
    flex: 0 0 auto;
    padding: clamp(13px, 2vw, 18px);
    border-top: 1px solid var(--support-line);
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 -10px 28px rgba(64, 39, 95, .035);
}

.app-screen .chat-footer form {
    width: 100%;
    margin: 0;
}

.app-screen .input-wrapper {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 100%;
    min-width: 0;
    min-height: 54px;
    padding: 5px 6px 5px 18px;
    border: 1px solid #e4deef;
    border-radius: 999px;
    background: linear-gradient(180deg, #fbfaff, #f8f6fc);
    box-shadow: inset 0 2px 6px rgba(53, 31, 82, .045);
    transition: border-color .25s ease, box-shadow .25s ease, background .25s ease;
}

.app-screen .input-wrapper:focus-within {
    border-color: rgba(124, 58, 237, .55);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .09), 0 10px 26px rgba(80, 45, 123, .08);
}

.app-screen .chat-field {
    flex: 1 1 auto;
    width: 100%;
    min-width: 0;
    padding: 10px 0;
    border: 0;
    outline: 0;
    color: var(--support-text);
    background: transparent;
    caret-color: var(--support-purple-600);
    font-size: 14px;
    font-weight: 500;
}

.app-screen .chat-field::placeholder {
    color: #9b95a8;
    opacity: 1;
}

.app-screen .send-btn {
    position: relative;
    display: grid;
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    padding: 0;
    place-items: center;
    overflow: hidden;
    border: 0;
    border-radius: 50%;
    color: #fff;
    background: linear-gradient(135deg, var(--support-purple-700), var(--support-purple-500));
    box-shadow: 0 8px 18px rgba(124, 58, 237, .27), inset 0 1px 0 rgba(255, 255, 255, .28);
    cursor: pointer;
    transition: transform .28s var(--support-ease), box-shadow .28s ease;
    -webkit-tap-highlight-color: transparent;
}

.app-screen .send-btn::before {
    content: "";
    position: absolute;
    inset: 0;
    opacity: 0;
    background: radial-gradient(circle at center, rgba(255, 255, 255, .35), transparent 66%);
    transition: opacity .25s ease;
}

.app-screen .send-btn i {
    position: relative;
    z-index: 1;
    transform: translateX(-1px);
    font-size: 14px;
    transition: transform .28s var(--support-ease);
}

.app-screen .send-btn:hover {
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 12px 24px rgba(124, 58, 237, .34);
}

.app-screen .send-btn:hover::before {
    opacity: 1;
}

.app-screen .send-btn:hover i {
    transform: translate(2px, -2px) rotate(-7deg);
}

.app-screen .send-btn:active {
    transform: scale(.92);
}

/* EMPTY STATES */
.app-screen .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: min(100%, 470px);
    min-height: 300px;
    margin: auto;
    padding: clamp(28px, 6vw, 52px);
    color: var(--support-text-soft);
    text-align: center;
}

.app-screen .empty-state .empty-icon {
    position: relative;
    display: grid;
    width: 92px;
    height: 92px;
    margin-bottom: 5px;
    place-items: center;
    border: 1px solid rgba(139, 92, 246, .15);
    border-radius: 28px;
    color: var(--support-purple-600);
    background: linear-gradient(145deg, #fff, var(--support-purple-100));
    box-shadow: 0 17px 35px rgba(124, 58, 237, .14), inset 0 1px 0 #fff;
    font-size: 34px;
    transform: rotate(-4deg);
    animation: supportEmptyFloat 4s ease-in-out infinite;
}

.app-screen .empty-state .empty-icon::after {
    content: "";
    position: absolute;
    inset: -10px;
    z-index: -1;
    border: 1px dashed rgba(139, 92, 246, .2);
    border-radius: 34px;
    animation: supportSpin 16s linear infinite;
}

.app-screen .empty-state h3 {
    margin: 19px 0 7px;
    font-family: var(--support-display);
    font-size: 21px;
    letter-spacing: -.015em;
}

.app-screen .empty-state p {
    max-width: 360px;
    margin: 0;
    font-size: 13px;
    line-height: 1.7;
}

.app-screen .list-scroll > div[style*="text-align:center"] {
    width: 100%;
    color: var(--support-text-soft) !important;
    border: 1px dashed var(--support-purple-200);
    border-radius: var(--support-radius-md);
    background: var(--support-purple-50);
}

/* NEW TICKET MODAL — SAFE ON NARROW AND SHORT SCREENS */
#ticketModal.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2147483000;
    display: none;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 100%;
    padding:
        max(16px, env(safe-area-inset-top))
        max(14px, env(safe-area-inset-right))
        max(16px, env(safe-area-inset-bottom))
        max(14px, env(safe-area-inset-left));
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    opacity: 0;
    visibility: hidden;
    background:
        radial-gradient(circle at 50% 10%, rgba(139, 92, 246, .22), transparent 38%),
        rgba(28, 17, 45, .56);
    backdrop-filter: blur(14px) saturate(1.1);
    -webkit-backdrop-filter: blur(14px) saturate(1.1);
    transition: opacity .3s ease, visibility .3s ease;
}

#ticketModal.modal-backdrop.open {
    display: flex;
    opacity: 1;
    visibility: visible;
}

body:has(#ticketModal.open) {
    overflow: hidden;
}

#ticketModal .modal-card {
    position: relative;
    width: min(100%, 500px);
    max-height: calc(100dvh - 32px - env(safe-area-inset-top) - env(safe-area-inset-bottom));
    margin: auto;
    padding: clamp(23px, 5vw, 35px);
    overflow-x: hidden;
    overflow-y: auto;
    border: 1px solid rgba(255, 255, 255, .78);
    border-radius: clamp(24px, 5vw, 32px);
    background: rgba(255, 255, 255, .98);
    box-shadow: 0 28px 90px rgba(28, 12, 52, .34), inset 0 1px 0 #fff;
    opacity: 0;
    transform: translateY(24px) scale(.95);
    transition: transform .38s var(--support-ease), opacity .3s ease;
}

#ticketModal .modal-card::before {
    content: "";
    position: absolute;
    top: -110px;
    right: -90px;
    width: 240px;
    height: 240px;
    pointer-events: none;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(167, 139, 250, .27), rgba(167, 139, 250, 0) 70%);
}

#ticketModal.open .modal-card {
    opacity: 1;
    transform: translateY(0) scale(1);
}

#ticketModal .modal-card > div:first-child {
    position: relative;
    z-index: 1;
    align-items: center;
    margin-bottom: 25px !important;
}

#ticketModal .modal-card h2 {
    margin: 0;
    color: var(--support-text);
    font-family: var(--support-display);
    font-size: clamp(22px, 4vw, 27px) !important;
    font-weight: 800 !important;
    letter-spacing: -.025em;
}

#ticketModal .modal-card > div:first-child > button {
    display: grid;
    flex: 0 0 42px;
    width: 42px;
    height: 42px;
    padding: 0;
    place-items: center;
    border: 1px solid var(--support-purple-100) !important;
    border-radius: 50%;
    color: var(--support-purple-700) !important;
    background: var(--support-purple-50) !important;
    font-size: 25px !important;
    line-height: 1;
    transition: transform .25s var(--support-ease), color .25s ease, background .25s ease;
}

#ticketModal .modal-card > div:first-child > button:hover {
    color: #fff !important;
    background: linear-gradient(135deg, var(--support-purple-700), var(--support-purple-500)) !important;
    transform: rotate(90deg);
}

#ticketModal form {
    position: relative;
    z-index: 1;
    margin: 0;
}

#ticketModal .input-group {
    margin-bottom: 18px;
}

#ticketModal .label {
    display: block;
    margin: 0 0 8px;
    color: #51485f;
    font-size: 12px;
    font-weight: 750;
    letter-spacing: .02em;
}

#ticketModal .input-box {
    display: block;
    width: 100%;
    min-width: 0;
    height: 54px;
    padding: 0 16px;
    border: 1px solid #e5dfef;
    border-radius: 16px;
    outline: 0;
    color: var(--support-text);
    background-color: #faf9fd;
    box-shadow: inset 0 2px 5px rgba(52, 31, 80, .035);
    caret-color: var(--support-purple-600);
    font-size: 14px;
    font-weight: 550;
    transition: border-color .25s ease, box-shadow .25s ease, background .25s ease, transform .25s var(--support-ease);
}

#ticketModal select.input-box {
    padding-right: 46px !important;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    background-image:
        linear-gradient(45deg, transparent 50%, var(--support-purple-600) 50%),
        linear-gradient(135deg, var(--support-purple-600) 50%, transparent 50%),
        linear-gradient(135deg, var(--support-purple-50), var(--support-purple-50));
    background-position:
        calc(100% - 20px) 23px,
        calc(100% - 15px) 23px,
        calc(100% - 43px) 0;
    background-size: 5px 5px, 5px 5px, 43px 100%;
    background-repeat: no-repeat;
}

#ticketModal .input-box:hover {
    border-color: rgba(139, 92, 246, .38);
    background-color: #fff;
}

#ticketModal .input-box:focus {
    border-color: var(--support-purple-500);
    background-color: #fff;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .09), 0 10px 24px rgba(75, 43, 116, .08);
    transform: translateY(-1px);
}

#ticketModal .input-box::placeholder {
    color: #9a94a6;
    opacity: 1;
}

#ticketModal textarea.input-box,
#ticketModal .textarea {
    height: 134px;
    min-height: 112px;
    max-height: 240px;
    padding-top: 15px;
    padding-bottom: 15px;
    resize: vertical;
    line-height: 1.55;
}

#ticketModal .btn-primary {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: 100%;
    min-height: 54px;
    margin-top: 7px;
    padding: 12px 20px;
    overflow: hidden;
    border: 0;
    border-radius: 999px;
    color: #fff;
    background: linear-gradient(135deg, var(--support-purple-700), var(--support-purple-500) 62%, #ad56ee);
    box-shadow: var(--support-shadow-purple), inset 0 1px 0 rgba(255, 255, 255, .3);
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .015em;
    cursor: pointer;
    transition: transform .28s var(--support-ease), box-shadow .28s ease, filter .28s ease;
    -webkit-tap-highlight-color: transparent;
}

#ticketModal .btn-primary::before {
    content: "";
    position: absolute;
    top: -70%;
    left: -28%;
    width: 25%;
    height: 240%;
    transform: rotate(24deg);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .42), transparent);
    animation: supportButtonShine 3.8s ease-in-out infinite;
}

#ticketModal .btn-primary i {
    position: relative;
    transition: transform .28s var(--support-ease);
}

#ticketModal .btn-primary:hover {
    transform: translateY(-3px);
    filter: saturate(1.08);
    box-shadow: 0 18px 38px rgba(124, 58, 237, .35);
}

#ticketModal .btn-primary:hover i {
    transform: translateX(5px);
}

#ticketModal .btn-primary:active {
    transform: scale(.98);
}

/* KEYBOARD ACCESSIBILITY */
.app-screen a:focus-visible,
.app-screen button:focus-visible,
#ticketModal button:focus-visible {
    outline: 3px solid rgba(124, 58, 237, .35);
    outline-offset: 3px;
}

.app-screen input:focus-visible,
#ticketModal input:focus-visible,
#ticketModal select:focus-visible,
#ticketModal textarea:focus-visible {
    outline: none;
}

/* ENTRANCE AND LIVE UI ANIMATIONS */
.app-screen .animate-up {
    opacity: 0;
    animation: supportSlideUp .7s var(--support-ease) forwards;
}

.app-screen .d-1 {
    animation-delay: .11s;
}

.app-screen .d-2 {
    animation-delay: .2s;
}

@keyframes supportSlideUp {
    from {
        opacity: 0;
        transform: translateY(24px) scale(.985);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes supportMessageIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes supportPulse {
    0%, 100% {
        transform: translateY(-50%) scale(1);
        box-shadow: 0 0 0 6px var(--support-purple-100), 0 0 14px rgba(139, 92, 246, .25);
    }
    50% {
        transform: translateY(-50%) scale(.82);
        box-shadow: 0 0 0 9px rgba(237, 233, 254, .65), 0 0 22px rgba(139, 92, 246, .4);
    }
}

@keyframes supportHeroOrb {
    0%, 100% {
        transform: translate3d(0, 0, 0) rotate(0);
    }
    50% {
        transform: translate3d(-9px, 12px, 0) rotate(8deg);
    }
}

@keyframes supportEmptyFloat {
    0%, 100% {
        transform: translateY(0) rotate(-4deg);
    }
    50% {
        transform: translateY(-8px) rotate(2deg);
    }
}

@keyframes supportSpin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes supportButtonShine {
    0%, 28% {
        left: -28%;
    }
    55%, 100% {
        left: 125%;
    }
}

@keyframes supportBlobOne {
    to {
        transform: translate3d(48px, 30px, 0) scale(1.08);
    }
}

@keyframes supportBlobTwo {
    to {
        transform: translate3d(-42px, -25px, 0) scale(.92);
    }
}

/* DESKTOP */
@media (min-width: 900px) {
    .app-screen .support-layout {
        grid-template-columns: minmax(290px, 340px) minmax(0, 1fr);
        align-items: start;
    }

    .app-screen .ticket-list-card {
        display: flex;
        flex-direction: column;
        height: clamp(560px, 70dvh, 700px);
    }

    .app-screen .list-scroll {
        display: block;
        flex: 1 1 auto;
        padding: 13px;
        overflow-x: hidden;
        overflow-y: auto;
        scroll-snap-type: none;
    }

    .app-screen .list-item {
        width: 100%;
        min-width: 0;
        margin-bottom: 10px;
    }

    .app-screen .list-item:last-child {
        margin-bottom: 0;
    }
}

/* TABLET + MOBILE */
@media (max-width: 899px) {
    .app-screen .ticket-list-card {
        max-height: none;
    }

    .app-screen .chat-container {
        height: min(680px, 72dvh);
        min-height: 520px;
    }
}

@media (max-width: 600px) {
    .app-screen {
        padding-top: 12px;
    }

    .app-screen .card-storage {
        min-height: 0;
        padding: 22px 20px;
        border-radius: 25px;
    }

    .app-screen .card-storage::after {
        width: 210px;
        height: 210px;
        right: -105px;
        top: -130px;
    }

    .app-screen .hero-title::after {
        width: 42px;
        height: 3px;
        margin-top: 10px;
    }

    .app-screen .floating-cta {
        min-height: 46px;
        padding: 11px 17px;
        font-size: 12px;
    }

    .app-screen .ticket-list-card,
    .app-screen .chat-container {
        border-radius: 21px;
    }

    .app-screen .list-item {
        flex-basis: min(82vw, 290px);
    }

    .app-screen .chat-container {
        height: 68dvh;
        min-height: 500px;
    }

    .app-screen .chat-messages {
        gap: 15px;
    }

    .app-screen .msg-row {
        max-width: 88%;
    }

    .app-screen .bubble {
        padding: 12px 15px;
        font-size: 13px;
        line-height: 1.6;
    }

    .app-screen .chat-header::after {
        right: 19px;
    }

    .app-screen .input-wrapper {
        min-height: 52px;
        padding-left: 15px;
    }

    .app-screen .chat-field,
    #ticketModal .input-box {
        font-size: 16px;
    }

    #ticketModal .modal-card {
        padding: 23px 18px;
    }
}

@media (max-width: 420px) {
    .app-screen .card-storage {
        align-items: flex-start;
        flex-direction: column;
        gap: 19px;
    }

    .app-screen .floating-cta {
        align-self: stretch;
        width: 100%;
    }

    .app-screen .list-scroll {
        padding: 11px;
    }

    .app-screen .list-item {
        flex-basis: calc(100vw - 58px);
    }

    .app-screen .status-pill {
        max-width: 62px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .app-screen .chat-container {
        min-height: 480px;
    }

    .app-screen .chat-header {
        padding: 18px;
    }

    .app-screen .chat-messages {
        padding: 18px 15px;
    }

    .app-screen .msg-row {
        max-width: 92%;
    }

    .app-screen .chat-footer {
        padding: 11px;
    }
}

/* SHORT VIEWPORT: MODAL NEVER GETS CUT OFF */
@media (max-height: 620px) {
    #ticketModal.modal-backdrop {
        align-items: flex-start;
    }

    #ticketModal .modal-card {
        margin-block: 0;
    }

    #ticketModal textarea.input-box,
    #ticketModal .textarea {
        height: 105px;
        min-height: 90px;
    }
}

/* RESPECT REDUCED-MOTION SETTINGS */
@media (prefers-reduced-motion: reduce) {
    html {
        scroll-behavior: auto;
    }

    .app-screen *,
    .app-screen *::before,
    .app-screen *::after,
    #ticketModal *,
    #ticketModal *::before,
    #ticketModal *::after {
        scroll-behavior: auto !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}
</style>

<div class="app-screen">

    <div class="card-storage animate-up">
        <div>
            <h1 class="hero-title">Support Center</h1>
            <p class="hero-sub">We are here to help you 24/7</p>
        </div>
        <button onclick="openModal()" class="floating-cta">
            <i class="fas fa-plus"></i> New Ticket
        </button>
    </div>

    <div class="support-layout">
        
        <div class="ticket-list-card animate-up d-1">
            <div style="padding:20px; border-bottom:1px solid rgba(0,0,0,0.05);">
                <h3 style="font-size:16px; font-weight:700; margin:0;">Your Tickets</h3>
            </div>
            <div class="list-scroll">
                <?php if(empty($all_tickets)): ?>
                    <div style="padding:40px; text-align:center; color:var(--muted); font-size:14px;">No tickets yet.</div>
                <?php else: ?>
                    <?php foreach($all_tickets as $t): ?>
                    <a href="?id=<?= $t['id'] ?>" class="list-item <?= ($active_ticket['id']??0)==$t['id']?'active':'' ?>">
                        <div class="item-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title"><?= sanitize($t['subject']) ?></div>
                            <div class="item-meta">
                                #<?= $t['id'] ?> • <?= date('M d', strtotime($t['updated_at'])) ?>
                            </div>
                        </div>
                        <div class="status-pill sp-<?= $t['status'] ?>"><?= $t['status'] ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-container animate-up d-2">
            <?php if ($active_ticket): ?>
                
                <div class="chat-header">
                    <div class="ch-title"><?= sanitize($active_ticket['subject']) ?></div>
                    <div class="ch-sub">Ticket #<?= $active_ticket['id'] ?> • <span style="color:var(--primary-1); font-weight:600; text-transform:uppercase;"><?= $active_ticket['status'] ?></span></div>
                </div>

                <div class="chat-messages" id="chatBox">
                    <?php foreach($messages as $m): ?>
                    <div class="msg-row <?= $m['sender'] ?>">
                        <div class="bubble bubble-<?= $m['sender'] ?>">
                            <?= nl2br(sanitize($m['message'])) ?>
                        </div>
                        <span class="msg-time"><?= date('h:i A', strtotime($m['created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-footer">
                    <?php if($active_ticket['status'] != 'closed'): ?>
                    <form method="POST">
                        <input type="hidden" name="reply_ticket" value="1">
                        <input type="hidden" name="ticket_id" value="<?= $active_ticket['id'] ?>">
                        <div class="input-wrapper">
                            <input type="text" name="message" class="chat-field" placeholder="Type your message..." required autocomplete="off">
                            <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div style="text-align:center; color:var(--muted); font-size:14px; font-weight:600;">
                            <i class="fas fa-lock"></i> This ticket is closed.
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="empty-state" style="margin:auto;">
                    <div class="empty-icon"><i class="fas fa-comments"></i></div>
                    <h3 style="color:var(--text); font-weight:700;">Select a conversation</h3>
                    <p>Choose a ticket from the left to view details or start a new one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<div class="modal-backdrop" id="ticketModal">
    <div class="modal-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="font-size:20px; font-weight:800;">New Ticket</h2>
            <button onclick="closeModal()" style="background:none; border:none; font-size:24px; color:var(--muted); cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="new_ticket" value="1">
            
            <div class="input-group">
                <label class="label">Subject</label>
                <select name="subject" class="input-box" style="padding-right:10px;">
                    <option value="Order Issue">📦 Order Issue</option>
                    <option value="Refill Request">🔄 Refill Request</option>
                    <option value="Payment Issue">💳 Payment Issue</option>
                    <option value="Other">❓ Other</option>
                </select>
            </div>

            <div class="input-group">
                <label class="label">Message</label>
                <textarea name="message" class="input-box textarea" placeholder="Describe your issue..." required></textarea>
            </div>

            <button class="btn-primary">Submit Ticket <i class="fas fa-arrow-right" style="margin-left:5px;"></i></button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('ticketModal');
    
    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('open'), 10);
    }
    
    function closeModal() {
        modal.classList.remove('open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Auto Scroll Chat
    const chatBox = document.getElementById('chatBox');
    if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include '_smm_footer.php'; ?>
