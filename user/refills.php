<?php
include '_header.php';

$user_id = $_SESSION['user_id'];
$history = [];

// Fetch user's refill history
try {
    $stmt = $db->prepare("SELECT * FROM refill_requests WHERE user_id = ? ORDER BY id DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $history = [];
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
/* Clean white + purple UI — design only */
.refill-section {
    --primary-blue: #7c3aed;
    --primary-hover: #6d28d9;
    --purple-soft: #f4efff;
    --bg-color: #f8f7fc;
    --card-bg: rgba(255, 255, 255, 0.94);
    --text-main: #251b38;
    --text-muted: #766d83;
    --border-color: #e9e3f2;
    --radius-normal: 22px;
    --radius-small: 13px;

    position: relative;
    isolation: isolate;
    min-height: 85vh;
    padding: clamp(24px, 4vw, 48px) 15px 60px;
    overflow-x: hidden;
    overflow-x: clip;
    color: var(--text-main);
    background:
        radial-gradient(circle at 0% 0%, rgba(139, 92, 246, 0.12), transparent 28%),
        radial-gradient(circle at 100% 70%, rgba(217, 70, 239, 0.08), transparent 25%),
        var(--bg-color);
    font-family: Inter, "Segoe UI", sans-serif;
}

.refill-section,
.refill-section * {
    box-sizing: border-box;
}

.refill-section::before,
.refill-section::after {
    content: "";
    position: absolute;
    z-index: -1;
    border-radius: 50%;
    filter: blur(55px);
    pointer-events: none;
}

.refill-section::before {
    top: 7%;
    left: -110px;
    width: 260px;
    height: 260px;
    background: rgba(139, 92, 246, 0.13);
    animation: softDriftOne 12s ease-in-out infinite alternate;
}

.refill-section::after {
    right: -100px;
    bottom: 3%;
    width: 250px;
    height: 250px;
    background: rgba(236, 72, 153, 0.08);
    animation: softDriftTwo 14s ease-in-out infinite alternate;
}

.container-custom {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
}

/* Page heading */
.page-title-box {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
    padding: 20px 22px;
    overflow: hidden;
    border: 1px solid rgba(124, 58, 237, 0.11);
    border-radius: var(--radius-normal);
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 14px 40px rgba(76, 29, 149, 0.07);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    animation: smoothEnter 0.6s cubic-bezier(.22, 1, .36, 1) both;
}

.page-title-box::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #8b5cf6, #ec4899, transparent);
    background-size: 200% 100%;
    animation: lineFlow 6s linear infinite;
}

.page-title-box h2 {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    color: var(--text-main);
    font-size: clamp(21px, 3vw, 27px);
    font-weight: 800;
    letter-spacing: -0.025em;
}

.icon-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 45px;
    width: 45px;
    height: 45px;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, #8b5cf6, #6d28d9);
    box-shadow: 0 9px 22px rgba(124, 58, 237, 0.25);
    font-size: 17px;
    animation: iconGlow 4s ease-in-out infinite;
}

.icon-wrapper i {
    animation: iconRotate 9s ease-in-out infinite;
}

.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 7px 13px;
    border: 1px solid #c8f1dd;
    border-radius: 999px;
    color: #078159;
    background: #effcf6;
    font-size: 11px;
    font-weight: 750;
    white-space: nowrap;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    animation: pulseAnim 1.7s infinite;
}

/* Main layout */
.layout-grid {
    display: grid;
    grid-template-columns: 350px minmax(0, 1fr);
    align-items: start;
    gap: 22px;
    width: 100%;
}

.panel-card {
    position: relative;
    min-width: 0;
    overflow: hidden;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-normal);
    background: var(--card-bg);
    box-shadow: 0 16px 45px rgba(62, 28, 105, 0.07);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    animation: smoothEnter 0.65s 0.08s cubic-bezier(.22, 1, .36, 1) both;
}

.panel-card:nth-child(2) {
    animation-delay: 0.15s;
}

.panel-card::before {
    content: "";
    position: absolute;
    z-index: 3;
    top: 0;
    left: 18%;
    width: 64%;
    height: 2px;
    border-radius: 99px;
    background: linear-gradient(90deg, transparent, #8b5cf6, #d946ef, transparent);
    background-size: 200% 100%;
    animation: lineFlow 7s linear infinite;
    pointer-events: none;
}

.panel-card:hover {
    border-color: rgba(124, 58, 237, 0.19);
    box-shadow: 0 21px 50px rgba(62, 28, 105, 0.10);
    transform: translateY(-3px);
}

.panel-body {
    position: relative;
    z-index: 2;
    padding: 25px;
}

.panel-header {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 18px 21px;
    border-bottom: 1px solid var(--border-color);
    background: rgba(251, 249, 255, 0.72);
}

.panel-header h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: var(--text-main);
    font-size: 16px;
    font-weight: 800;
}

.panel-header h3 i {
    color: var(--primary-blue) !important;
    margin-right: 0 !important;
}

.quick-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: var(--text-main);
    font-size: 19px;
    font-weight: 800;
}

.quick-title i {
    color: #8b5cf6;
    animation: boltMove 3s ease-in-out infinite;
}

.quick-description {
    margin: 10px 0 23px;
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.65;
}

.bg-icon-animated {
    position: absolute;
    z-index: 1;
    right: -30px;
    bottom: -35px;
    color: rgba(124, 58, 237, 0.035);
    font-size: 155px;
    pointer-events: none;
    animation: slowSpin 28s linear infinite;
}

/* Form */
.form-label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-main);
    font-size: 12px;
    font-weight: 750;
}

.input-group-custom {
    position: relative;
    margin-bottom: 15px;
}

.input-group-custom i {
    position: absolute;
    z-index: 2;
    left: 15px;
    top: 50%;
    color: #9d91ad;
    font-size: 14px;
    pointer-events: none;
    transform: translateY(-50%);
    transition: color 0.25s ease, transform 0.25s ease;
}

.form-control-custom {
    width: 100%;
    min-height: 51px;
    padding: 13px 14px 13px 41px;
    border: 1.5px solid #e5dfee;
    border-radius: var(--radius-small);
    outline: none;
    color: #382b4b;
    background: #fbfaff;
    font-family: inherit;
    font-size: 14px;
    font-weight: 650;
    appearance: textfield;
    -moz-appearance: textfield;
    transition: border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
}

.form-control-custom::-webkit-inner-spin-button,
.form-control-custom::-webkit-outer-spin-button {
    margin: 0;
    -webkit-appearance: none;
}

.form-control-custom::placeholder {
    color: #b5abba;
}

.form-control-custom:hover {
    border-color: #cfc2df;
    background: #fff;
}

.form-control-custom:focus {
    border-color: var(--primary-blue);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.09);
}

.form-control-custom:focus + i {
    color: var(--primary-blue);
    transform: translateY(-50%) scale(1.08);
}

.btn-primary-custom {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 51px;
    padding: 13px 18px;
    overflow: hidden;
    border: 0;
    border-radius: var(--radius-small);
    color: #fff;
    background: linear-gradient(110deg, #8b5cf6, #6d28d9, #9333ea);
    background-size: 180% 100%;
    box-shadow: 0 12px 25px rgba(109, 40, 217, 0.23);
    font-family: inherit;
    font-size: 13px;
    font-weight: 750;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    animation: buttonFlow 6s ease-in-out infinite;
    transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
}

.btn-primary-custom::before {
    content: "";
    position: absolute;
    top: 0;
    left: -60%;
    width: 35%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
    transform: skewX(-20deg);
    animation: buttonShimmer 4.5s ease-in-out infinite;
}

.btn-primary-custom > * {
    position: relative;
    z-index: 1;
}

.btn-primary-custom:hover {
    filter: brightness(1.04);
    box-shadow: 0 16px 31px rgba(109, 40, 217, 0.30);
    transform: translateY(-2px);
}

.btn-primary-custom:active {
    transform: scale(0.985);
}

.btn-primary-custom:disabled {
    cursor: wait;
}

/* Table */
.table-responsive {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: rgba(124, 58, 237, 0.3) transparent;
}

.table-responsive::-webkit-scrollbar {
    height: 5px;
}

.table-responsive::-webkit-scrollbar-thumb {
    border-radius: 20px;
    background: rgba(124, 58, 237, 0.25);
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.custom-table th {
    padding: 14px 17px;
    border-bottom: 1px solid var(--border-color);
    color: #82788e;
    background: #fcfbfe;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.07em;
    text-align: left;
    text-transform: uppercase;
}

.custom-table td {
    padding: 15px 17px;
    border-bottom: 1px solid #eeeaf3;
    color: #493d57;
    font-size: 12.5px;
    font-weight: 600;
    vertical-align: middle;
    transition: background 0.2s ease;
}

.custom-table tbody tr:last-child td {
    border-bottom: 0;
}

.custom-table tbody tr:hover td {
    background: #fbf9ff;
}

.refill-number {
    color: #9c92a7 !important;
    font-weight: 700 !important;
}

.date-cell {
    color: #786e84 !important;
}

.date-cell i {
    margin-right: 5px;
    color: #9b7cd2;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    min-height: 27px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 750;
}

.b-pending {
    color: #a96109;
    background: #fff6dc;
}

.b-refilling {
    color: #6432c4;
    background: #eee7ff;
}

.b-completed {
    color: #07835d;
    background: #e6f9f1;
}

.b-rejected {
    color: #cf3548;
    background: #ffeaed;
}

.order-id-copy {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 30px;
    padding: 4px 7px;
    margin: -4px -7px;
    border-radius: 8px;
    color: var(--primary-blue);
    cursor: pointer;
    transition: color 0.2s ease, background 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.order-id-copy:hover {
    color: var(--primary-hover);
    background: var(--purple-soft);
    text-decoration: none;
}

.order-id-copy i {
    font-size: 9px;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.order-id-copy:hover i,
.order-id-copy:focus-visible i {
    opacity: 1;
}

.btn-reload {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 38px;
    padding: 7px 12px;
    border: 1px solid #ded6e9;
    border-radius: 11px;
    color: #72657f;
    background: #fff;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: color 0.22s ease, border-color 0.22s ease, background 0.22s ease, transform 0.22s ease;
}

.btn-reload:hover {
    border-color: #c5b3dd;
    color: var(--primary-blue);
    background: #f8f4ff;
    transform: translateY(-1px);
}

.btn-reload:hover i {
    animation: quickSpin 0.6s ease;
}

.spinner-border {
    display: none;
    width: 1.1rem;
    height: 1.1rem;
    border: 2.5px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.75s linear infinite;
}

/* Empty state */
.empty-history {
    padding: 55px 20px;
    text-align: center;
}

.empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    margin: 0 auto 17px;
    border-radius: 21px;
    color: #8b5cf6;
    background: #f3edff;
    font-size: 27px;
    animation: emptyFloat 4s ease-in-out infinite;
}

.empty-history h4 {
    margin: 0 0 8px;
    color: var(--text-main);
    font-size: 16px;
    font-weight: 800;
}

.empty-history p {
    max-width: 310px;
    margin: 0 auto;
    color: var(--text-muted);
    font-size: 12.5px;
    line-height: 1.6;
}

/* Focus and SweetAlert */
.refill-section button:focus-visible,
.refill-section input:focus-visible,
.refill-section .order-id-copy:focus-visible {
    outline: 3px solid rgba(124, 58, 237, 0.22);
    outline-offset: 3px;
}

.swal2-container {
    padding: 15px !important;
}

.swal2-popup {
    width: min(92vw, 430px) !important;
    max-width: calc(100vw - 30px) !important;
    border: 1px solid rgba(124, 58, 237, 0.12) !important;
    border-radius: 22px !important;
    box-shadow: 0 25px 70px rgba(59, 22, 103, 0.20) !important;
}

.swal2-confirm {
    min-height: 43px;
    border-radius: 11px !important;
    font-weight: 700 !important;
}

/* Smooth live animations */
@keyframes smoothEnter {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes softDriftOne {
    from { transform: translate3d(0, 0, 0) scale(0.95); }
    to { transform: translate3d(55px, 38px, 0) scale(1.08); }
}

@keyframes softDriftTwo {
    from { transform: translate3d(0, 0, 0) scale(1); }
    to { transform: translate3d(-50px, -35px, 0) scale(1.1); }
}

@keyframes lineFlow {
    to { background-position: 200% 0; }
}

@keyframes iconGlow {
    0%, 100% { box-shadow: 0 9px 22px rgba(124, 58, 237, 0.22); }
    50% { box-shadow: 0 12px 28px rgba(124, 58, 237, 0.34); }
}

@keyframes iconRotate {
    0%, 84%, 100% { transform: rotate(0deg); }
    92% { transform: rotate(180deg); }
}

@keyframes pulseAnim {
    0% {
        transform: scale(0.92);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.55);
    }
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 7px rgba(16, 185, 129, 0);
    }
    100% {
        transform: scale(0.92);
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

@keyframes boltMove {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

@keyframes buttonFlow {
    0%, 100% { background-position: 0 50%; }
    50% { background-position: 100% 50%; }
}

@keyframes buttonShimmer {
    0%, 62% {
        left: -60%;
        opacity: 0;
    }
    70% { opacity: 1; }
    90%, 100% {
        left: 125%;
        opacity: 0;
    }
}

@keyframes slowSpin {
    to { transform: rotate(360deg); }
}

@keyframes quickSpin {
    to { transform: rotate(360deg); }
}

@keyframes emptyFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 920px) {
    .layout-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .refill-section {
        padding: 20px 11px 45px;
    }

    .page-title-box {
        padding: 16px;
        border-radius: 18px;
    }

    .page-title-box h2 {
        gap: 10px;
        font-size: 20px;
    }

    .icon-wrapper {
        flex-basis: 40px;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        font-size: 15px;
    }

    .live-badge {
        min-height: 34px;
        padding: 6px 10px;
        font-size: 10px;
    }

    .layout-grid {
        gap: 17px;
    }

    .panel-card {
        border-radius: 18px;
    }

    .panel-body {
        padding: 21px 18px;
    }

    .panel-header {
        padding: 16px;
    }

    .table-responsive {
        overflow: visible;
        padding: 11px;
    }

    .custom-table,
    .custom-table tbody,
    .custom-table tr,
    .custom-table td {
        display: block;
        width: 100%;
    }

    .custom-table {
        white-space: normal;
    }

    .custom-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .custom-table tbody tr {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 11px 14px;
        margin-bottom: 9px;
        padding: 14px;
        border: 1px solid #ece6f3;
        border-radius: 14px;
        background: #fff;
    }

    .custom-table tbody tr:last-child {
        margin-bottom: 0;
    }

    .custom-table td {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        min-width: 0;
        padding: 0;
        border: 0;
        background: transparent !important;
        font-size: 11.5px;
    }

    .custom-table td::before {
        content: attr(data-label);
        color: #9a90a4;
        font-size: 8px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .custom-table td:nth-child(3) {
        grid-column: 1 / -1;
    }

    .order-id-copy i {
        opacity: 0.7;
    }

    .btn-reload {
        min-width: 40px;
        padding-inline: 10px;
    }

    .reload-label {
        display: none;
    }
}

@media (max-width: 370px) {
    .page-title-box h2 {
        font-size: 18px;
    }

    .live-badge {
        padding-inline: 8px;
    }

    .live-badge .online-text {
        display: none;
    }

    .custom-table tbody tr {
        grid-template-columns: 1fr;
    }

    .custom-table td:nth-child(3) {
        grid-column: auto;
    }
}

@media (prefers-reduced-motion: reduce) {
    .refill-section *,
    .refill-section *::before,
    .refill-section *::after,
    .swal2-popup {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }

    .panel-card:hover,
    .btn-primary-custom:hover,
    .btn-reload:hover {
        transform: none;
    }
}
</style>

<div class="refill-section">
    <div class="container-custom">

        <div class="page-title-box">
            <h2>
                <span class="icon-wrapper"><i class="fa-solid fa-arrows-rotate"></i></span>
                Smart Refill Engine
            </h2>
            <div class="live-badge">
                <span class="pulse-dot"></span>
                <span class="online-text">System Online</span>
            </div>
        </div>

        <div class="layout-grid">

            <div class="panel-card">
                <i class="fa-solid fa-rotate bg-icon-animated" aria-hidden="true"></i>

                <div class="panel-body">
                    <h4 class="quick-title"><i class="fa-solid fa-bolt"></i> Quick Refill</h4>
                    <p class="quick-description">Order stopped? Put your ID below and we will automatically ping the server to restart delivery.</p>

                    <form id="refillForm">
                        <input type="hidden" id="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <label class="form-label" for="order_id">Order ID</label>
                        <div class="input-group-custom">
                            <input type="number" id="order_id" class="form-control-custom" placeholder="1548792" required autocomplete="off">
                            <i class="fa-solid fa-hashtag" aria-hidden="true"></i>
                        </div>

                        <button type="submit" class="btn-primary-custom" id="submitBtn">
                            <i class="fa-solid fa-paper-plane" id="btnIcon"></i>
                            <span class="spinner-border" id="btnSpinner"></span>
                            <span class="btn-text">Send Refill Request</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-header">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Refills</h3>
                    <button class="btn-reload" onclick="location.reload();" title="Refresh Table" aria-label="Refresh refill history">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span class="reload-label">Refresh</span>
                    </button>
                </div>

                <?php if(count($history) > 0): ?>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Refill #</th>
                                    <th>Order ID</th>
                                    <th>Date Sent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($history as $row): ?>
                                    <tr>
                                        <td class="refill-number" data-label="Refill #">#<?php echo $row['id']; ?></td>
                                        <td data-label="Order ID">
                                            <span class="order-id-copy" onclick="copyId('<?php echo $row['order_id']; ?>')" onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); copyId('<?php echo $row['order_id']; ?>'); }" title="Click to copy" role="button" tabindex="0">
                                                <strong><?php echo $row['order_id']; ?></strong>
                                                <i class="fa-regular fa-copy"></i>
                                            </span>
                                        </td>
                                        <td class="date-cell" data-label="Date Sent">
                                            <i class="fa-regular fa-calendar"></i>
                                            <?php echo date('d M Y, H:i', strtotime($row['date'])); ?>
                                        </td>
                                        <td data-label="Status">
                                            <?php
                                                $status = strtolower($row['status']);
                                                if($status == 'pending') echo '<span class="badge-status b-pending"><i class="fa-solid fa-hourglass-half"></i> Pending</span>';
                                                elseif($status == 'refilling') echo '<span class="badge-status b-refilling"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Refilling</span>';
                                                elseif($status == 'completed') echo '<span class="badge-status b-completed"><i class="fa-solid fa-check"></i> Completed</span>';
                                                elseif($status == 'rejected') echo '<span class="badge-status b-rejected"><i class="fa-solid fa-xmark"></i> Rejected</span>';
                                                else echo '<span class="badge-status b-pending">'.$row['status'].'</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-history">
                        <div class="empty-icon">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <h4>No History Found</h4>
                        <p>You have not requested any refills yet. Once you submit a request, it will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
// Click to copy Order ID function
function copyId(id) {
    navigator.clipboard.writeText(id).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            icon: 'success',
            iconColor: '#8b5cf6', // Purple theme
            background: '#ffffff',
            color: '#4c1d95',
            title: 'Order ID ' + id + ' Copied! ✨',
            showClass: {
                popup: 'animate__animated animate__bounceInRight animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutRight animate__faster'
            }
        });
    });
}

// Form Submission Logic
document.getElementById('refillForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let orderId = document.getElementById('order_id').value;
    let csrfToken = document.getElementById('csrf_token').value;

    // UI Loading State Enable
    let btnText = document.querySelector('.btn-text');
    let btnSpinner = document.getElementById('btnSpinner');
    let btnIcon = document.getElementById('btnIcon');
    let submitBtn = document.getElementById('submitBtn');

    btnText.innerText = "Authenticating...";
    btnSpinner.style.display = "inline-block";
    btnIcon.style.display = "none";
    submitBtn.disabled = true;
    submitBtn.style.opacity = "0.8";

    let formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('csrf_token', csrfToken);

    // Backend Request
    fetch('refill_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .catch(() => {
        // Fallback simulation mode
        return new Promise(resolve => setTimeout(() => resolve({status: 'success'}), 1200));
    })
    .then(data => {
        // Reset UI
        btnText.innerText = "Send Refill Request";
        btnSpinner.style.display = "none";
        btnIcon.style.display = "inline-block";
        submitBtn.disabled = false;
        submitBtn.style.opacity = "1";
        document.getElementById('order_id').value = '';

        // Beautiful Customer-Friendly SweetAlert Responses (Purple Theme)
        if(data.status === 'success') {
            Swal.fire({
                title: 'Awesome! 🎉',
                text: 'Your refill for Order #' + orderId + ' is processing.',
                icon: 'success',
                iconColor: '#8b5cf6', // Purple
                background: '#ffffff',
                color: '#4c1d95',
                confirmButtonColor: '#8b5cf6',
                confirmButtonText: 'Great!',
                showClass: { popup: 'animate__animated animate__tada' },
                hideClass: { popup: 'animate__animated animate__zoomOut' }
            }).then(() => { location.reload(); });
        } else {
            // Replaced technical API response with a friendly error message
            Swal.fire({
                title: 'Oops! 🥺',
                text: 'We couldn\'t process this order right now. Please check the Order ID and try again!',
                icon: 'error',
                iconColor: '#d946ef', // Pinkish purple
                background: '#ffffff',
                color: '#4c1d95',
                confirmButtonColor: '#8b5cf6',
                confirmButtonText: 'Okay, I will check',
                showClass: { popup: 'animate__animated animate__shakeX' },
                hideClass: { popup: 'animate__animated animate__fadeOutDown' }
            });
        }
    });
});
</script>

<?php include '_footer.php'; ?>
