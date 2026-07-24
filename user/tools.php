<?php
include '_smm_header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* =========================================================
   LikeXFollow Advanced Free Tools
   Everything is scoped to #lfToolsApp so the website header,
   footer and existing theme remain untouched.
   ========================================================= */
#lfToolsApp {
    --lf-primary: #6d28d9;
    --lf-primary-2: #8b5cf6;
    --lf-primary-3: #a78bfa;
    --lf-primary-soft: #f5f3ff;
    --lf-primary-softer: #faf9ff;
    --lf-ink: #17122b;
    --lf-muted: #6b6680;
    --lf-line: #e9e5f3;
    --lf-white: #ffffff;
    --lf-success: #059669;
    --lf-danger: #dc2626;
    --lf-warning: #d97706;
    --lf-radius-xl: 28px;
    --lf-radius-lg: 20px;
    --lf-radius-md: 14px;
    --lf-shadow-sm: 0 8px 24px rgba(49, 32, 91, .06);
    --lf-shadow: 0 18px 55px rgba(76, 49, 137, .11);
    position: relative;
    isolation: isolate;
    min-height: 100vh;
    padding: 34px 0 80px;
    overflow: visible;
    color: var(--lf-ink);
    font-family: "Outfit", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background:
        radial-gradient(circle at 4% 3%, rgba(139, 92, 246, .14), transparent 25rem),
        radial-gradient(circle at 96% 18%, rgba(196, 181, 253, .22), transparent 26rem),
        linear-gradient(180deg, #fdfcff 0%, #f8f6fc 52%, #ffffff 100%);
}

#lfToolsPortal {
    --lf-primary: #6d28d9;
    --lf-primary-2: #8b5cf6;
    --lf-primary-3: #a78bfa;
    --lf-primary-soft: #f5f3ff;
    --lf-primary-softer: #faf9ff;
    --lf-ink: #17122b;
    --lf-muted: #6b6680;
    --lf-line: #e9e5f3;
    --lf-white: #ffffff;
    --lf-success: #059669;
    --lf-danger: #dc2626;
    --lf-warning: #d97706;
    position: relative;
    z-index: 2147483000;
    color: var(--lf-ink);
    font-family: "Outfit", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

#lfToolsApp,
#lfToolsApp *,
#lfToolsPortal,
#lfToolsPortal * {
    box-sizing: border-box;
}

#lfToolsApp button,
#lfToolsApp input,
#lfToolsApp textarea,
#lfToolsApp select,
#lfToolsPortal button,
#lfToolsPortal input,
#lfToolsPortal textarea,
#lfToolsPortal select {
    font: inherit;
}

#lfToolsApp button,
#lfToolsPortal button {
    -webkit-tap-highlight-color: transparent;
}

#lfToolsApp .lf-wrap {
    width: min(1200px, calc(100% - 32px));
    margin: 0 auto;
}

#lfToolsApp .lf-hero {
    position: relative;
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(330px, .92fr);
    gap: clamp(22px, 4vw, 52px);
    align-items: center;
    padding: clamp(30px, 5vw, 62px);
    overflow: hidden;
    border: 1px solid rgba(124, 58, 237, .13);
    border-radius: var(--lf-radius-xl);
    background: rgba(255, 255, 255, .88);
    box-shadow: var(--lf-shadow);
    text-align: left;
    backdrop-filter: blur(16px);
}

#lfToolsApp .lf-hero::before,
#lfToolsApp .lf-hero::after {
    position: absolute;
    content: "";
    border-radius: 999px;
    pointer-events: none;
}

#lfToolsApp .lf-hero::before {
    width: 260px;
    height: 260px;
    top: -165px;
    right: -80px;
    background: linear-gradient(145deg, rgba(124, 58, 237, .25), rgba(196, 181, 253, .06));
}

#lfToolsApp .lf-hero::after {
    width: 210px;
    height: 210px;
    bottom: -150px;
    left: -65px;
    border: 38px solid rgba(167, 139, 250, .12);
}

#lfToolsApp .lf-hero-copy {
    position: relative;
    z-index: 2;
    min-width: 0;
}

#lfToolsApp .lf-hero-art {
    position: relative;
    z-index: 1;
    display: grid;
    min-height: 360px;
    place-items: center;
}

#lfToolsApp .lf-hero-art::before {
    position: absolute;
    z-index: -1;
    width: min(95%, 440px);
    aspect-ratio: 1;
    content: "";
    border-radius: 44% 56% 58% 42% / 46% 43% 57% 54%;
    background:
        radial-gradient(circle at 32% 28%, rgba(255,255,255,.95), transparent 22%),
        linear-gradient(145deg, #ede9fe, #f8f5ff 60%, #ddd6fe);
    box-shadow: inset 0 0 0 1px rgba(109, 40, 217, .08), 0 30px 65px rgba(109, 40, 217, .13);
    transform: rotate(-4deg);
}

#lfToolsApp .lf-hero-image {
    display: block;
    width: min(100%, 540px);
    height: auto;
    filter: drop-shadow(0 24px 22px rgba(60, 29, 114, .18));
    animation: lfHeroFloat 5.6s ease-in-out infinite;
}

#lfToolsApp .lf-hero-art.is-missing {
    display: none;
}

@keyframes lfHeroFloat {
    0%, 100% { transform: translateY(0) rotate(.2deg); }
    50% { transform: translateY(-10px) rotate(-.5deg); }
}

#lfToolsApp .lf-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding: 8px 13px;
    border: 1px solid #ddd6fe;
    border-radius: 999px;
    color: var(--lf-primary);
    background: var(--lf-primary-soft);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .02em;
}

#lfToolsApp .lf-eyebrow-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, .13);
}

#lfToolsApp .lf-title {
    max-width: 650px;
    margin: 0;
    color: var(--lf-ink);
    font-size: clamp(2.25rem, 6vw, 4.6rem);
    font-weight: 800;
    line-height: .98;
    letter-spacing: -.052em;
}

#lfToolsApp .lf-gradient-text {
    color: var(--lf-primary);
    background: linear-gradient(125deg, #5b21b6 5%, #8b5cf6 55%, #a855f7 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

#lfToolsApp .lf-subtitle {
    max-width: 650px;
    margin: 20px 0 0;
    color: var(--lf-muted);
    font-size: clamp(1rem, 2.1vw, 1.17rem);
    line-height: 1.72;
}

#lfToolsApp .lf-hero-stats {
    display: flex;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 27px;
}

#lfToolsApp .lf-stat {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 13px;
    border: 1px solid var(--lf-line);
    border-radius: 12px;
    color: #4d4565;
    background: #fff;
    box-shadow: 0 5px 15px rgba(51, 36, 92, .04);
    font-size: 13px;
    font-weight: 600;
}

#lfToolsApp .lf-stat strong {
    color: var(--lf-primary);
    font-weight: 800;
}

#lfToolsApp .lf-toolbar {
    position: relative;
    z-index: 2;
    margin: 26px 0 24px;
    padding: 16px;
    border: 1px solid var(--lf-line);
    border-radius: 20px;
    background: rgba(255, 255, 255, .91);
    box-shadow: var(--lf-shadow-sm);
    backdrop-filter: blur(14px);
}

#lfToolsApp .lf-search-row {
    display: grid;
    grid-template-columns: minmax(230px, 1fr) auto;
    gap: 12px;
    align-items: center;
}

#lfToolsApp .lf-search {
    position: relative;
}

#lfToolsApp .lf-search-icon {
    position: absolute;
    top: 50%;
    left: 16px;
    color: #8a829c;
    transform: translateY(-50%);
    pointer-events: none;
}

#lfToolsApp .lf-search-input {
    width: 100%;
    height: 50px;
    padding: 0 45px;
    border: 1px solid var(--lf-line);
    border-radius: 14px;
    outline: none;
    color: var(--lf-ink);
    background: #fbfaff;
    transition: border-color .2s, box-shadow .2s, background .2s;
}

#lfToolsApp .lf-search-input:focus {
    border-color: var(--lf-primary-2);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, .11);
}

#lfToolsApp .lf-search-input::placeholder {
    color: #9690a7;
}

#lfToolsApp .lf-clear-search {
    position: absolute;
    top: 50%;
    right: 10px;
    width: 30px;
    height: 30px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    color: #756e85;
    background: transparent;
    cursor: pointer;
    transform: translateY(-50%);
}

#lfToolsApp .lf-clear-search:hover {
    color: var(--lf-primary);
    background: var(--lf-primary-soft);
}

#lfToolsApp .lf-result-count {
    min-width: 112px;
    color: var(--lf-muted);
    font-size: 14px;
    font-weight: 600;
    text-align: right;
}

#lfToolsApp .lf-cats {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-bottom: 2px;
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: #ddd6fe transparent;
}

#lfToolsApp .lf-cat {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 40px;
    padding: 8px 14px;
    border: 1px solid var(--lf-line);
    border-radius: 999px;
    color: #625b73;
    background: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .2s, border-color .2s, color .2s, background .2s, box-shadow .2s;
}

#lfToolsApp .lf-cat:hover {
    border-color: #c4b5fd;
    color: var(--lf-primary);
    transform: translateY(-1px);
}

#lfToolsApp .lf-cat.is-active {
    border-color: var(--lf-primary);
    color: #fff;
    background: linear-gradient(135deg, #6d28d9, #8b5cf6);
    box-shadow: 0 8px 20px rgba(109, 40, 217, .22);
}

#lfToolsApp .lf-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

#lfToolsApp .lf-card {
    position: relative;
    display: flex;
    min-width: 0;
    min-height: 226px;
    padding: 22px;
    overflow: hidden;
    border: 1px solid var(--lf-line);
    border-radius: var(--lf-radius-lg);
    background: rgba(255, 255, 255, .94);
    box-shadow: var(--lf-shadow-sm);
    cursor: pointer;
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}

#lfToolsApp .lf-card::after {
    position: absolute;
    width: 110px;
    height: 110px;
    right: -60px;
    bottom: -60px;
    content: "";
    border-radius: 50%;
    background: radial-gradient(circle, rgba(139, 92, 246, .14), transparent 68%);
    transition: transform .3s;
}

#lfToolsApp .lf-card:hover {
    border-color: #c9bbf4;
    box-shadow: 0 18px 40px rgba(71, 45, 126, .12);
    transform: translateY(-5px);
}

#lfToolsApp .lf-card:hover::after {
    transform: scale(1.65);
}

#lfToolsApp .lf-card:focus-visible {
    outline: 3px solid rgba(139, 92, 246, .28);
    outline-offset: 3px;
}

#lfToolsApp .lf-card-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
}

#lfToolsApp .lf-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

#lfToolsApp .lf-icon {
    display: grid;
    flex: 0 0 48px;
    width: 48px;
    height: 48px;
    place-items: center;
    border: 1px solid #e8e1fb;
    border-radius: 15px;
    color: #5b21b6;
    background: linear-gradient(145deg, #faf7ff, #eee8ff);
    box-shadow: inset 0 1px 0 #fff;
    font-size: 21px;
}

#lfToolsApp .lf-open-arrow {
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid var(--lf-line);
    border-radius: 50%;
    color: #817991;
    background: #fff;
    transition: color .2s, transform .2s, border-color .2s;
}

#lfToolsApp .lf-card:hover .lf-open-arrow {
    border-color: #c4b5fd;
    color: var(--lf-primary);
    transform: translate(2px, -2px);
}

#lfToolsApp .lf-card[data-category="social"] .lf-icon { color:#e11d48; background:linear-gradient(145deg,#fff7f8,#ffe4e6); border-color:#fecdd3; }
#lfToolsApp .lf-card[data-category="text"] .lf-icon { color:#7c3aed; background:linear-gradient(145deg,#fbf8ff,#eee7ff); }
#lfToolsApp .lf-card[data-category="developer"] .lf-icon { color:#2563eb; background:linear-gradient(145deg,#f7fbff,#e5efff); border-color:#dbeafe; }
#lfToolsApp .lf-card[data-category="calculator"] .lf-icon { color:#059669; background:linear-gradient(145deg,#f6fffb,#dcfce7); border-color:#bbf7d0; }
#lfToolsApp .lf-card[data-category="image"] .lf-icon { color:#c026d3; background:linear-gradient(145deg,#fff9ff,#fae8ff); border-color:#f5d0fe; }
#lfToolsApp .lf-card[data-category="utility"] .lf-icon { color:#d97706; background:linear-gradient(145deg,#fffdf6,#fef3c7); border-color:#fde68a; }

#lfToolsApp .lf-card-title {
    margin: 17px 0 7px;
    color: var(--lf-ink);
    font-size: 1.07rem;
    font-weight: 750;
    line-height: 1.25;
}

#lfToolsApp .lf-card-desc {
    display: -webkit-box;
    margin: 0;
    overflow: hidden;
    color: var(--lf-muted);
    font-size: .88rem;
    line-height: 1.55;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

#lfToolsApp .lf-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 17px;
}

#lfToolsApp .lf-badge {
    display: inline-flex;
    align-items: center;
    max-width: 72%;
    padding: 5px 9px;
    overflow: hidden;
    border-radius: 999px;
    color: #675d7d;
    background: #f7f5fb;
    font-size: 11px;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#lfToolsApp .lf-free {
    color: var(--lf-success);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .03em;
}

#lfToolsApp .lf-free.is-new {
    color: var(--lf-primary);
}

#lfToolsApp .lf-empty {
    padding: 55px 20px;
    border: 1px dashed #d8cfee;
    border-radius: 20px;
    color: var(--lf-muted);
    background: rgba(255, 255, 255, .75);
    text-align: center;
}

#lfToolsApp .lf-empty-icon {
    display: block;
    margin-bottom: 8px;
    font-size: 34px;
}

#lfToolsApp .lf-empty-image {
    display: block;
    width: min(170px, 48vw);
    height: auto;
    margin: 0 auto 10px;
    filter: drop-shadow(0 14px 18px rgba(69, 40, 124, .13));
}

#lfToolsApp .lf-empty-image.is-missing {
    display: none;
}

#lfToolsApp .lf-help-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 26px;
}

#lfToolsApp .lf-help-item {
    padding: 17px;
    border: 1px solid var(--lf-line);
    border-radius: 16px;
    color: #5f5870;
    background: rgba(255, 255, 255, .8);
    font-size: 13px;
    line-height: 1.5;
}

#lfToolsApp .lf-help-item strong {
    display: block;
    margin-bottom: 3px;
    color: var(--lf-ink);
    font-size: 14px;
}

/* Modal */
#lfToolsPortal .lf-modal {
    position: fixed;
    z-index: 2147483001;
    inset: 0;
    display: grid;
    width: 100%;
    min-width: 0;
    height: 100vh;
    height: 100dvh;
    padding:
        max(14px, env(safe-area-inset-top))
        max(14px, env(safe-area-inset-right))
        max(14px, env(safe-area-inset-bottom))
        max(14px, env(safe-area-inset-left));
    overflow: hidden;
    place-items: center;
    contain: layout;
}

#lfToolsPortal .lf-modal[hidden] {
    display: none !important;
}

#lfToolsPortal .lf-modal-backdrop {
    position: absolute;
    inset: 0;
    border: 0;
    background: rgba(20, 13, 37, .57);
    cursor: default;
    backdrop-filter: blur(8px);
}

#lfToolsPortal .lf-modal-panel {
    position: relative;
    z-index: 1;
    display: flex;
    width: min(840px, 100%);
    min-width: 0;
    max-height: calc(100vh - 28px);
    max-height: calc(100dvh - 28px);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .65);
    border-radius: 25px;
    background: #fff;
    box-shadow: 0 32px 90px rgba(22, 13, 45, .3);
    flex-direction: column;
    animation: lfModalIn .22s ease-out;
    overscroll-behavior: contain;
}

@keyframes lfModalIn {
    from { opacity: 0; transform: translateY(14px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

#lfToolsPortal .lf-modal-head {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 14px;
    padding: 19px 22px;
    border-bottom: 1px solid var(--lf-line);
    background: linear-gradient(180deg, #fff, #fdfcff);
}

#lfToolsPortal .lf-modal-heading {
    min-width: 0;
    flex: 1;
}

#lfToolsPortal .lf-modal-title {
    margin: 0;
    color: var(--lf-ink);
    font-size: 1.18rem;
    font-weight: 800;
}

#lfToolsPortal .lf-modal-desc {
    margin: 3px 0 0;
    display: -webkit-box;
    overflow: hidden;
    color: var(--lf-muted);
    font-size: 13px;
    line-height: 1.35;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

#lfToolsPortal .lf-close {
    display: grid;
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    padding: 0;
    place-items: center;
    border: 1px solid var(--lf-line);
    border-radius: 12px;
    color: #665f76;
    background: #fff;
    cursor: pointer;
    transition: .2s;
}

#lfToolsPortal .lf-close:hover {
    border-color: #c4b5fd;
    color: var(--lf-primary);
    background: var(--lf-primary-soft);
}

#lfToolsPortal .lf-modal-body {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    max-height: calc(100vh - 104px);
    max-height: calc(100dvh - 104px);
    padding: 22px 22px max(28px, env(safe-area-inset-bottom));
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    scrollbar-gutter: stable;
    scroll-padding: 18px 0 42px;
}

#lfToolsPortal .lf-tool-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

#lfToolsPortal .lf-tool-grid.lf-three {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

#lfToolsPortal .lf-span-2 {
    grid-column: 1 / -1;
}

#lfToolsPortal .lf-field {
    display: block;
    min-width: 0;
}

#lfToolsPortal .lf-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin: 0 0 7px;
    color: #4e465f;
    font-size: 12px;
    font-weight: 750;
}

#lfToolsPortal .lf-label-hint {
    color: #928ba0;
    font-weight: 500;
}

#lfToolsPortal .lf-input,
#lfToolsPortal .lf-select,
#lfToolsPortal .lf-textarea {
    width: 100%;
    border: 1px solid #ddd8e8;
    border-radius: 12px;
    outline: none;
    color: var(--lf-ink);
    background: #fbfaff;
    transition: border-color .2s, box-shadow .2s, background .2s;
}

#lfToolsPortal .lf-input,
#lfToolsPortal .lf-select {
    height: 46px;
    padding: 0 13px;
}

#lfToolsPortal .lf-textarea {
    min-height: 118px;
    padding: 12px 13px;
    resize: vertical;
    line-height: 1.55;
}

#lfToolsPortal .lf-input:focus,
#lfToolsPortal .lf-select:focus,
#lfToolsPortal .lf-textarea:focus {
    border-color: var(--lf-primary-2);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, .1);
}

#lfToolsPortal .lf-input::placeholder,
#lfToolsPortal .lf-textarea::placeholder {
    color: #aaa4b7;
}

#lfToolsPortal .lf-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 16px;
}

#lfToolsPortal .lf-btn {
    display: inline-flex;
    min-height: 44px;
    padding: 10px 16px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid transparent;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(135deg, #6d28d9, #8b5cf6);
    box-shadow: 0 8px 18px rgba(109, 40, 217, .19);
    font-size: 13px;
    font-weight: 750;
    cursor: pointer;
    transition: transform .2s, filter .2s, box-shadow .2s;
}

#lfToolsPortal .lf-btn:hover {
    filter: brightness(1.04);
    box-shadow: 0 11px 23px rgba(109, 40, 217, .25);
    transform: translateY(-1px);
}

#lfToolsPortal .lf-btn:active {
    transform: translateY(0);
}

#lfToolsPortal .lf-btn-secondary {
    border-color: #dcd4ec;
    color: #5d5470;
    background: #fff;
    box-shadow: none;
}

#lfToolsPortal .lf-btn-secondary:hover {
    border-color: #bdaaf0;
    color: var(--lf-primary);
    background: var(--lf-primary-soft);
    box-shadow: none;
}

#lfToolsPortal .lf-btn-success {
    background: linear-gradient(135deg, #047857, #10b981);
    box-shadow: 0 8px 18px rgba(5, 150, 105, .18);
}

#lfToolsPortal .lf-btn-danger {
    border-color: #fecaca;
    color: #b91c1c;
    background: #fff7f7;
    box-shadow: none;
}

#lfToolsPortal .lf-btn-small {
    min-height: 36px;
    padding: 7px 11px;
    border-radius: 10px;
    font-size: 12px;
}

#lfToolsPortal .lf-result {
    display: none;
    margin-top: 17px;
    padding: 16px;
    overflow-wrap: anywhere;
    border: 1px solid #ded6f0;
    border-radius: 15px;
    color: #40384f;
    background: linear-gradient(145deg, #fbfaff, #f6f1ff);
    font-size: 14px;
    line-height: 1.6;
}

#lfToolsPortal .lf-result.is-visible {
    display: block;
    animation: lfFade .2s ease-out;
}

#lfToolsPortal .lf-result.is-error {
    border-color: #fecaca;
    color: #991b1b;
    background: #fff7f7;
}

#lfToolsPortal .lf-result.is-success {
    border-color: #bbf7d0;
    color: #14532d;
    background: #f3fff7;
}

@keyframes lfFade {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

#lfToolsPortal .lf-output {
    width: 100%;
    min-height: 96px;
    margin-top: 10px;
    padding: 12px;
    border: 1px solid #ddd6ee;
    border-radius: 11px;
    outline: none;
    color: #332b42;
    background: #fff;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12px;
    line-height: 1.55;
    resize: vertical;
}

#lfToolsPortal .lf-output-row {
    display: flex;
    align-items: center;
    gap: 9px;
}

#lfToolsPortal .lf-output-row .lf-input {
    flex: 1;
}

#lfToolsPortal .lf-checks {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
}

#lfToolsPortal .lf-check {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 38px;
    padding: 8px 11px;
    border: 1px solid var(--lf-line);
    border-radius: 11px;
    color: #5d566d;
    background: #fff;
    font-size: 12px;
    font-weight: 650;
    cursor: pointer;
}

#lfToolsPortal .lf-check input {
    width: 16px;
    height: 16px;
    accent-color: var(--lf-primary);
}

#lfToolsPortal .lf-range-row {
    display: grid;
    grid-template-columns: 1fr 74px;
    gap: 10px;
    align-items: center;
}

#lfToolsPortal input[type="range"] {
    width: 100%;
    accent-color: var(--lf-primary);
}

#lfToolsPortal .lf-tip {
    display: flex;
    gap: 9px;
    margin-top: 15px;
    padding: 11px 12px;
    border: 1px solid #ede9fe;
    border-radius: 12px;
    color: #655b78;
    background: #faf9ff;
    font-size: 12px;
    line-height: 1.5;
}

#lfToolsPortal .lf-tip strong {
    color: #5b21b6;
}

#lfToolsPortal .lf-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 9px;
    margin-top: 14px;
}

#lfToolsPortal .lf-mini-stat {
    padding: 13px 8px;
    border: 1px solid var(--lf-line);
    border-radius: 12px;
    background: #fff;
    text-align: center;
}

#lfToolsPortal .lf-mini-stat strong {
    display: block;
    color: var(--lf-primary);
    font-size: 1.25rem;
    line-height: 1.15;
}

#lfToolsPortal .lf-mini-stat span {
    display: block;
    margin-top: 4px;
    color: var(--lf-muted);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

#lfToolsPortal .lf-list {
    display: grid;
    gap: 8px;
}

#lfToolsPortal .lf-list-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px;
    border: 1px solid var(--lf-line);
    border-radius: 11px;
    background: #fff;
}

#lfToolsPortal .lf-list-value {
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

#lfToolsPortal .lf-preview-img {
    display: block;
    width: 100%;
    max-height: 360px;
    border: 1px solid var(--lf-line);
    border-radius: 13px;
    background: #f4f1f8;
    object-fit: contain;
}

#lfToolsPortal .lf-canvas {
    display: block;
    max-width: 100%;
    margin: 0 auto;
    border: 1px solid var(--lf-line);
    border-radius: 13px;
    background-color: #fff;
    background-image:
        linear-gradient(45deg, #eee 25%, transparent 25%),
        linear-gradient(-45deg, #eee 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #eee 75%),
        linear-gradient(-45deg, transparent 75%, #eee 75%);
    background-position: 0 0, 0 8px, 8px -8px, -8px 0;
    background-size: 16px 16px;
}

#lfToolsPortal .lf-color-chip {
    display: inline-flex;
    min-height: 38px;
    padding: 6px 11px;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--lf-line);
    border-radius: 10px;
    background: #fff;
    font-family: ui-monospace, monospace;
    font-size: 12px;
}

#lfToolsPortal .lf-swatch {
    width: 24px;
    height: 24px;
    border: 1px solid rgba(0, 0, 0, .1);
    border-radius: 7px;
}

#lfToolsPortal .lf-palette {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    min-height: 130px;
    overflow: hidden;
    border: 1px solid var(--lf-line);
    border-radius: 14px;
}

#lfToolsPortal .lf-palette-color {
    display: flex;
    padding: 9px 5px;
    align-items: flex-end;
    justify-content: center;
    border: 0;
    color: inherit;
    cursor: pointer;
}

#lfToolsPortal .lf-palette-color span {
    padding: 4px 6px;
    border-radius: 7px;
    background: rgba(255, 255, 255, .8);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    color: #231c2e;
    font-family: ui-monospace, monospace;
    font-size: 10px;
}

#lfToolsPortal .lf-meter {
    height: 9px;
    margin: 11px 0 7px;
    overflow: hidden;
    border-radius: 999px;
    background: #ece9f1;
}

#lfToolsPortal .lf-meter-bar {
    width: 0;
    height: 100%;
    border-radius: inherit;
    background: #ef4444;
    transition: width .25s, background .25s;
}

#lfToolsPortal .lf-dropzone {
    display: block;
    padding: 25px 15px;
    border: 1.5px dashed #cfc3e8;
    border-radius: 15px;
    color: #6f667d;
    background: #fbf9ff;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}

#lfToolsPortal .lf-dropzone:hover {
    border-color: var(--lf-primary-2);
    background: var(--lf-primary-soft);
}

#lfToolsPortal .lf-dropzone input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
}

#lfToolsPortal .lf-file-name {
    display: block;
    margin-top: 5px;
    color: var(--lf-primary);
    font-size: 11px;
    font-weight: 700;
}

#lfToolsPortal .lf-stopwatch {
    padding: 24px;
    border: 1px solid var(--lf-line);
    border-radius: 17px;
    background: linear-gradient(145deg, #faf8ff, #fff);
    color: var(--lf-primary);
    font-variant-numeric: tabular-nums;
    font-size: clamp(2.2rem, 10vw, 4.6rem);
    font-weight: 800;
    letter-spacing: -.04em;
    text-align: center;
}

#lfToolsPortal .lf-design-preview {
    display: grid;
    min-height: 190px;
    overflow: hidden;
    place-items: center;
    border: 1px solid var(--lf-line);
    border-radius: 17px;
    background: linear-gradient(135deg, #6d28d9, #c4b5fd);
}

#lfToolsPortal .lf-shadow-stage {
    background:
        linear-gradient(45deg, #f5f3f9 25%, transparent 25%),
        linear-gradient(-45deg, #f5f3f9 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f5f3f9 75%),
        linear-gradient(-45deg, transparent 75%, #f5f3f9 75%),
        #fff;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0;
    background-size: 20px 20px;
}

#lfToolsPortal .lf-shadow-box {
    display: grid;
    width: 105px;
    height: 105px;
    place-items: center;
    border-radius: 25px;
    color: #fff;
    background: linear-gradient(145deg, #6d28d9, #8b5cf6);
    font-size: 28px;
    font-weight: 800;
}

#lfToolsPortal .lf-contrast-preview {
    display: flex;
    min-height: 150px;
    padding: 25px;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--lf-line);
    border-radius: 17px;
    color: #17122b;
    background: #fff;
    flex-direction: column;
    text-align: center;
}

#lfToolsPortal .lf-contrast-preview strong {
    font-size: clamp(1.5rem, 6vw, 2.4rem);
}

#lfToolsPortal .lf-contrast-preview span {
    margin-top: 4px;
    opacity: .76;
}

#lfToolsPortal .lf-data-table {
    width: 100%;
    overflow: hidden;
    border: 1px solid var(--lf-line);
    border-radius: 12px;
    background: #fff;
}

#lfToolsPortal .lf-data-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 84px 84px;
    gap: 8px;
    padding: 9px 11px;
    align-items: center;
    border-bottom: 1px solid var(--lf-line);
    font-size: 12px;
}

#lfToolsPortal .lf-data-row:last-child {
    border-bottom: 0;
}

#lfToolsPortal .lf-data-row.is-head {
    color: #5f5670;
    background: #f8f6fc;
    font-weight: 800;
}

#lfToolsPortal .lf-focus-timer {
    padding: 18px;
    border: 1px solid var(--lf-line);
    border-radius: 18px;
    background: linear-gradient(145deg, #faf8ff, #fff);
    text-align: center;
}

#lfToolsPortal .lf-focus-ring {
    --progress: 0deg;
    display: grid;
    width: min(230px, 68vw);
    aspect-ratio: 1;
    margin: 0 auto;
    padding: 15px;
    place-items: center;
    border-radius: 50%;
    background: conic-gradient(var(--lf-primary) var(--progress), #ebe7f2 0);
    box-shadow: 0 18px 40px rgba(109, 40, 217, .1);
}

#lfToolsPortal .lf-focus-ring::before {
    position: absolute;
    content: "";
}

#lfToolsPortal .lf-focus-ring > div {
    display: grid;
    width: 100%;
    height: 100%;
    place-items: center;
    align-content: center;
    border-radius: 50%;
    background: #fff;
}

#lfToolsPortal .lf-focus-ring strong {
    color: var(--lf-primary);
    font-size: clamp(2.4rem, 12vw, 4rem);
    font-variant-numeric: tabular-nums;
    line-height: 1;
}

#lfToolsPortal .lf-focus-ring span {
    margin-top: 8px;
    color: var(--lf-muted);
    font-size: 12px;
    font-weight: 700;
}

#lfToolsPortal code {
    padding: 2px 5px;
    border-radius: 5px;
    color: #5b21b6;
    background: #ede9fe;
    font-family: ui-monospace, monospace;
    font-size: .92em;
}

#lfToolsPortal .lf-toast {
    position: fixed;
    z-index: 1000000;
    right: 20px;
    bottom: 20px;
    max-width: min(360px, calc(100% - 40px));
    padding: 12px 15px;
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 12px;
    color: #fff;
    background: #21182e;
    box-shadow: 0 15px 45px rgba(19, 12, 30, .27);
    font-size: 13px;
    font-weight: 650;
    opacity: 0;
    pointer-events: none;
    transform: translateY(12px);
    transition: opacity .2s, transform .2s;
}

#lfToolsPortal .lf-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 960px) {
    #lfToolsApp .lf-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    #lfToolsApp .lf-help-strip { grid-template-columns: 1fr; }
}

@media (max-width: 840px) {
    #lfToolsApp .lf-hero {
        grid-template-columns: 1fr;
        text-align: center;
    }
    #lfToolsApp .lf-title,
    #lfToolsApp .lf-subtitle { margin-right: auto; margin-left: auto; }
    #lfToolsApp .lf-hero-stats { justify-content: center; }
    #lfToolsApp .lf-hero-art {
        min-height: 270px;
        margin-top: -12px;
    }
    #lfToolsApp .lf-hero-image { width: min(82%, 450px); }
    #lfToolsPortal .lf-tool-grid.lf-three { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 680px) {
    #lfToolsApp { padding: 18px 0 55px; }
    #lfToolsApp .lf-wrap { width: min(100% - 20px, 1200px); }
    #lfToolsApp .lf-hero { gap: 12px; padding: 31px 18px 22px; border-radius: 21px; }
    #lfToolsApp .lf-title { font-size: clamp(2.05rem, 12vw, 3.1rem); }
    #lfToolsApp .lf-subtitle { margin-top: 15px; line-height: 1.55; }
    #lfToolsApp .lf-hero-art { min-height: 220px; margin-top: -10px; }
    #lfToolsApp .lf-hero-art::before { width: min(88%, 310px); }
    #lfToolsApp .lf-hero-image { width: min(88%, 355px); }
    #lfToolsApp .lf-toolbar { padding: 11px; border-radius: 16px; }
    #lfToolsApp .lf-search-row { grid-template-columns: 1fr; }
    #lfToolsApp .lf-result-count { min-width: 0; padding: 0 3px; text-align: left; }
    #lfToolsApp .lf-grid { grid-template-columns: 1fr; gap: 12px; }
    #lfToolsApp .lf-card { min-height: 202px; padding: 19px; }
    #lfToolsPortal .lf-modal {
        height: 100vh;
        height: 100dvh;
        padding: 0;
        place-items: stretch;
    }
    #lfToolsPortal .lf-modal-panel {
        width: 100%;
        height: 100vh;
        height: 100dvh;
        max-height: none;
        border: 0;
        border-radius: 0;
    }
    #lfToolsPortal .lf-modal-head {
        padding:
            max(13px, env(safe-area-inset-top))
            max(14px, env(safe-area-inset-right))
            13px
            max(14px, env(safe-area-inset-left));
    }
    #lfToolsPortal .lf-modal-desc { max-width: min(58vw, 320px); }
    #lfToolsPortal .lf-modal-body {
        max-height: none;
        padding: 16px max(14px, env(safe-area-inset-right)) max(34px, env(safe-area-inset-bottom)) max(14px, env(safe-area-inset-left));
    }
    #lfToolsPortal .lf-tool-grid,
    #lfToolsPortal .lf-tool-grid.lf-three { grid-template-columns: 1fr; }
    #lfToolsPortal .lf-span-2 { grid-column: auto; }
    #lfToolsPortal .lf-stats { grid-template-columns: repeat(2, 1fr); }
    #lfToolsPortal .lf-actions .lf-btn { flex: 1 1 auto; }
    #lfToolsPortal .lf-palette { min-height: 180px; grid-template-columns: 1fr; }
    #lfToolsPortal .lf-palette-color { min-height: 58px; align-items: center; justify-content: flex-end; }
}

@media (prefers-reduced-motion: reduce) {
    #lfToolsApp *,
    #lfToolsApp *::before,
    #lfToolsApp *::after,
    #lfToolsPortal *,
    #lfToolsPortal *::before,
    #lfToolsPortal *::after {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<section class="lf-tools-app" id="lfToolsApp" aria-labelledby="lfPageTitle">
    <div class="lf-wrap">
        <header class="lf-hero">
            <div class="lf-hero-copy">
                <div class="lf-eyebrow"><span class="lf-eyebrow-dot"></span> No signup • Instant results</div>
                <h1 class="lf-title" id="lfPageTitle">Your <span class="lf-gradient-text">Free Tools</span> Powerhouse</h1>
                <p class="lf-subtitle">Social media, text, developer, images aur daily calculations — ek fast, powerful aur fully mobile-friendly toolkit mein.</p>
                <div class="lf-hero-stats" aria-label="Toolkit features">
                    <span class="lf-stat"><strong id="lfTotalTools">68+</strong> working tools</span>
                    <span class="lf-stat">⚡ Instant browser results</span>
                    <span class="lf-stat">🔒 Privacy-friendly</span>
                    <span class="lf-stat">📱 Full-screen mobile tools</span>
                </div>
            </div>
            <div class="lf-hero-art">
                <img class="lf-hero-image" src="assets/likexfollow-tools/tools-hero.webp" alt="White and purple 3D toolbox filled with social media, calculator, image, QR and security tools" width="1066" height="838" decoding="async" fetchpriority="high" data-lf-generated-art>
            </div>
        </header>

        <div class="lf-toolbar">
            <div class="lf-search-row">
                <div class="lf-search">
                    <span class="lf-search-icon" aria-hidden="true">⌕</span>
                    <input class="lf-search-input" id="lfToolSearch" type="search" autocomplete="off" placeholder="Search tools — QR, password, age, image..." aria-label="Search free tools">
                    <button class="lf-clear-search" id="lfClearSearch" type="button" title="Clear search" aria-label="Clear search">×</button>
                </div>
                <div class="lf-result-count" id="lfResultCount" aria-live="polite"></div>
            </div>
            <nav class="lf-cats" id="lfCategories" aria-label="Tool categories">
                <button class="lf-cat is-active" type="button" data-category="all">✨ All Tools</button>
                <button class="lf-cat" type="button" data-category="social">🔥 Social</button>
                <button class="lf-cat" type="button" data-category="text">✍️ Text</button>
                <button class="lf-cat" type="button" data-category="developer">💻 Web & Dev</button>
                <button class="lf-cat" type="button" data-category="calculator">🧮 Calculators</button>
                <button class="lf-cat" type="button" data-category="image">🖼️ Images</button>
                <button class="lf-cat" type="button" data-category="utility">🧰 Utilities</button>
            </nav>
        </div>

        <main>
            <div class="lf-grid" id="lfToolsGrid"></div>
            <div class="lf-empty" id="lfEmpty" hidden>
                <img class="lf-empty-image" src="assets/likexfollow-tools/tools-search.webp" alt="" width="440" height="414" loading="lazy" decoding="async" data-lf-generated-art>
                <strong>Koi tool match nahi hua.</strong><br>
                Search spelling change karein ya “All Tools” select karein.
            </div>
        </main>

        <div class="lf-help-strip">
            <div class="lf-help-item"><strong>🔒 Browser-first privacy</strong>Zyada tools aapke device par hi process hote hain. API use karne wale tools par clear note diya gaya hai.</div>
            <div class="lf-help-item"><strong>💡 Quick tip</strong>Kisi tool ko naam, category ya kaam se search karein. Result ke saath copy/download controls milenge.</div>
            <div class="lf-help-item"><strong>📲 Mobile ready</strong>Cards, forms aur tool window choti screens aur touch controls ke liye optimized hain.</div>
        </div>

        <noscript>
            <div class="lf-empty" style="margin-top:20px">In tools ko chalane ke liye JavaScript enable karna zaroori hai.</div>
        </noscript>
    </div>
</section>

<div id="lfToolsPortal">
    <div class="lf-modal" id="lfToolModal" hidden>
        <button class="lf-modal-backdrop" type="button" data-close-modal tabindex="-1" aria-label="Close tool"></button>
        <section class="lf-modal-panel" role="dialog" aria-modal="true" aria-labelledby="lfModalTitle" aria-describedby="lfModalDesc">
            <header class="lf-modal-head">
                <div class="lf-icon" id="lfModalIcon" aria-hidden="true">✨</div>
                <div class="lf-modal-heading">
                    <h2 class="lf-modal-title" id="lfModalTitle">Tool</h2>
                    <p class="lf-modal-desc" id="lfModalDesc"></p>
                </div>
                <button class="lf-close" type="button" data-close-modal aria-label="Close tool">✕</button>
            </header>
            <div class="lf-modal-body" id="lfModalBody"></div>
        </section>
    </div>

    <div class="lf-toast" id="lfToast" role="status" aria-live="polite"></div>
</div>

<script>
(() => {
    "use strict";

    const app = document.getElementById("lfToolsApp");
    const portal = document.getElementById("lfToolsPortal");
    if (!app || !portal) return;

    const grid = app.querySelector("#lfToolsGrid");
    const searchInput = app.querySelector("#lfToolSearch");
    const clearSearch = app.querySelector("#lfClearSearch");
    const resultCount = app.querySelector("#lfResultCount");
    const emptyState = app.querySelector("#lfEmpty");
    const categories = app.querySelector("#lfCategories");
    const modal = portal.querySelector("#lfToolModal");
    const modalBody = portal.querySelector("#lfModalBody");
    const modalTitle = portal.querySelector("#lfModalTitle");
    const modalDesc = portal.querySelector("#lfModalDesc");
    const modalIcon = portal.querySelector("#lfModalIcon");
    const toastNode = portal.querySelector("#lfToast");

    let activeCategory = "all";
    let activeTool = null;
    let lastFocusedElement = null;
    let toastTimer = null;
    let modalCleanup = [];
    let imageDownloadUrl = "";
    let stopwatchState = null;
    let pomodoroState = null;
    let speechUtterance = null;

    const tools = [
        { id:"yt-thumb", category:"social", icon:"▶", title:"YouTube Thumbnail Downloader", desc:"Video link se available HD thumbnails preview aur download karein.", tag:"YouTube • HD" },
        { id:"wa-link", category:"social", icon:"☏", title:"WhatsApp Link Generator", desc:"Number save kiye baghair custom message ke saath direct chat link banayen.", tag:"WhatsApp • Link" },
        { id:"hashtags", category:"social", icon:"#", title:"Smart Hashtag Generator", desc:"Keyword, niche aur quantity ke mutabiq clean hashtag sets banayen.", tag:"Instagram • TikTok" },
        { id:"bio-maker", category:"social", icon:"☺", title:"Social Bio Maker", desc:"Instagram, TikTok ya business profile ke liye ready-to-copy bios.", tag:"Profile • Bio" },
        { id:"caption-maker", category:"social", icon:"✦", title:"Caption Idea Generator", desc:"Topic aur tone se engaging caption ideas aur CTAs hasil karein.", tag:"Caption • Ideas" },
        { id:"engagement", category:"social", icon:"↗", title:"Engagement Rate Calculator", desc:"Likes, comments, saves aur followers se engagement rate nikalein.", tag:"Analytics" },
        { id:"yt-timestamp", category:"social", icon:"◷", title:"YouTube Timestamp Link", desc:"Video ke exact time se shareable timestamp URL create karein.", tag:"YouTube • Share" },
        { id:"username", category:"social", icon:"@", title:"Username Idea Generator", desc:"Name aur niche se clean, memorable username combinations banayen.", tag:"Branding • Ideas" },
        { id:"yt-title", category:"social", icon:"YT", title:"YouTube Title Generator", desc:"Topic aur style se clickable, clean YouTube title ideas banayen.", tag:"YouTube • Ideas", isNew:true },
        { id:"hook-maker", category:"social", icon:"⚡", title:"Viral Hook Generator", desc:"Reels, Shorts aur posts ke opening hooks generate karein.", tag:"Reels • Hooks", isNew:true },

        { id:"fancy-font", category:"text", icon:"𝓐", title:"Fancy Font Generator", desc:"Normal text ko Unicode bold, mono, circle aur fullwidth styles mein badlein.", tag:"Unicode • Copy" },
        { id:"case-converter", category:"text", icon:"Aa", title:"Advanced Case Converter", desc:"UPPER, lower, Title, Sentence, camel, snake aur kebab case.", tag:"Writing • Format" },
        { id:"word-counter", category:"text", icon:"123", title:"Word & Reading Counter", desc:"Words, characters, sentences, paragraphs aur reading time live check karein.", tag:"Content • SEO" },
        { id:"line-tools", category:"text", icon:"☷", title:"Line Organizer", desc:"Duplicate/blank lines remove, sort, reverse ya shuffle karein.", tag:"Cleanup • Lists" },
        { id:"text-cleaner", category:"text", icon:"⌁", title:"Text Cleaner", desc:"Extra spaces, blank lines, invisible characters aur messy text saaf karein.", tag:"Clean • Normalize" },
        { id:"find-replace", category:"text", icon:"⌕", title:"Find & Replace", desc:"Case-sensitive option ke saath text ko safely find aur replace karein.", tag:"Edit • Replace" },
        { id:"slug-maker", category:"text", icon:"/", title:"SEO Slug Generator", desc:"Titles ko clean URL slugs mein convert karein.", tag:"SEO • URL" },
        { id:"lorem", category:"text", icon:"¶", title:"Lorem Ipsum Generator", desc:"Design mockups ke liye words, sentences ya paragraphs generate karein.", tag:"Placeholder" },
        { id:"extractor", category:"text", icon:"⇥", title:"Data Extractor", desc:"Text se emails, URLs, hashtags, mentions ya numbers extract karein.", tag:"Extract • Filter" },
        { id:"keyword-density", category:"text", icon:"K%", title:"Keyword Density Analyzer", desc:"Content ke repeated words aur keyword density locally analyze karein.", tag:"SEO • Content", isNew:true },
        { id:"readability", category:"text", icon:"R", title:"Readability Analyzer", desc:"Reading ease, average sentence length aur content difficulty estimate karein.", tag:"Writing • Score", isNew:true },
        { id:"text-speech", category:"text", icon:"🔊", title:"Text to Speech", desc:"Browser voices se text ko aloud play, pause aur stop karein.", tag:"Speech • Browser", isNew:true },

        { id:"ip-info", category:"developer", icon:"◎", title:"My IP & Browser Info", desc:"Public IP, approximate location aur browser/device details dekhein.", tag:"Network • Device" },
        { id:"password-gen", category:"developer", icon:"✱", title:"Secure Password Generator", desc:"Crypto-secure customizable passwords aur passphrases generate karein.", tag:"Security • Crypto" },
        { id:"password-check", category:"developer", icon:"✓", title:"Password Strength Checker", desc:"Password ki approximate strength, entropy aur improvement tips check karein.", tag:"Security • Check" },
        { id:"qr-maker", category:"developer", icon:"▦", title:"QR Code Generator", desc:"Text, link, email ya contact detail ka custom-size QR banayen.", tag:"QR • Download" },
        { id:"url-codec", category:"developer", icon:"%</>", title:"URL Encoder / Decoder", desc:"URL ya component ko safely encode aur decode karein.", tag:"URL • Encode" },
        { id:"base64", category:"developer", icon:"64", title:"Base64 Encoder / Decoder", desc:"Unicode text ko Base64 mein encode/decode karein.", tag:"Base64 • Unicode" },
        { id:"json-tool", category:"developer", icon:"{ }", title:"JSON Formatter & Validator", desc:"JSON beautify, minify, validate aur sort keys karein.", tag:"JSON • Dev" },
        { id:"html-entities", category:"developer", icon:"&;", title:"HTML Entity Tool", desc:"HTML special characters encode ya decode karein.", tag:"HTML • Escape" },
        { id:"utm-builder", category:"developer", icon:"↗", title:"UTM Campaign Builder", desc:"Marketing links ke liye UTM parameters safely add karein.", tag:"Marketing • URL" },
        { id:"meta-tags", category:"developer", icon:"</>", title:"Meta Tag Generator", desc:"SEO aur social sharing ke essential meta tags ready karein.", tag:"SEO • Open Graph" },
        { id:"regex-tester", category:"developer", icon:".*", title:"Regex Tester", desc:"JavaScript regular expression ko flags aur live matches ke saath test karein.", tag:"Regex • Dev" },
        { id:"timestamp", category:"developer", icon:"⏱", title:"Unix Timestamp Converter", desc:"Unix seconds/milliseconds aur human date ko convert karein.", tag:"Time • Dev" },
        { id:"jwt-decoder", category:"developer", icon:"JWT", title:"JWT Decoder", desc:"JWT header aur payload locally decode karein—signature verification ke baghair.", tag:"Token • Local", isNew:true },
        { id:"gradient-maker", category:"developer", icon:"◒", title:"CSS Gradient Generator", desc:"Visual controls se linear gradient aur ready CSS banayen.", tag:"CSS • Design", isNew:true },
        { id:"shadow-maker", category:"developer", icon:"▣", title:"CSS Box Shadow Generator", desc:"Live box-shadow preview aur copyable CSS generate karein.", tag:"CSS • UI", isNew:true },
        { id:"contrast-checker", category:"developer", icon:"Aa", title:"Color Contrast Checker", desc:"Foreground/background contrast aur WCAG AA/AAA result check karein.", tag:"A11y • WCAG", isNew:true },
        { id:"http-status", category:"developer", icon:"HTTP", title:"HTTP Status Lookup", desc:"Common HTTP status codes ka meaning aur category instantly dekhein.", tag:"Web • Reference", isNew:true },

        { id:"age", category:"calculator", icon:"🎂", title:"Exact Age Calculator", desc:"Years, months, days, total days aur next birthday countdown.", tag:"Date • Age" },
        { id:"discount", category:"calculator", icon:"%", title:"Discount Calculator", desc:"Final price, saved amount aur stacked discount calculate karein.", tag:"Shopping • Sale" },
        { id:"cpm", category:"calculator", icon:"CPM", title:"CPM Calculator", desc:"Cost, impressions aur CPM mein se missing value calculate karein.", tag:"Ads • Marketing" },
        { id:"percentage", category:"calculator", icon:"％", title:"Percentage Calculator", desc:"Percent of, what percent aur percentage change calculate karein.", tag:"Math • Percent" },
        { id:"bmi", category:"calculator", icon:"BMI", title:"BMI Calculator", desc:"Metric height/weight se BMI aur general category check karein.", tag:"Health • General" },
        { id:"loan", category:"calculator", icon:"₨", title:"Loan / EMI Calculator", desc:"Monthly installment, total payment aur interest estimate karein.", tag:"Finance • EMI" },
        { id:"tax", category:"calculator", icon:"+%", title:"GST / VAT Calculator", desc:"Tax add ya tax-inclusive price se original amount nikaalein.", tag:"Business • Tax" },
        { id:"profit", category:"calculator", icon:"↗", title:"Profit & Margin Calculator", desc:"Cost/revenue se profit, margin aur markup calculate karein.", tag:"Business • Profit" },
        { id:"date-diff", category:"calculator", icon:"📅", title:"Date Difference Calculator", desc:"Do dates ke darmiyan calendar aur total difference nikalein.", tag:"Date • Duration" },
        { id:"tip", category:"calculator", icon:"÷", title:"Tip & Bill Splitter", desc:"Tip, grand total aur per-person bill calculate karein.", tag:"Food • Split" },
        { id:"unit", category:"calculator", icon:"⇄", title:"Universal Unit Converter", desc:"Length, weight, temperature, area, speed aur data units convert karein.", tag:"Units • Convert" },
        { id:"fuel", category:"calculator", icon:"⛽", title:"Fuel Cost Calculator", desc:"Distance, mileage aur fuel price se trip cost estimate karein.", tag:"Travel • Cost" },
        { id:"compound-interest", category:"calculator", icon:"CI", title:"Compound Interest Calculator", desc:"Investment growth, contributions aur earned interest estimate karein.", tag:"Finance • Growth", isNew:true },
        { id:"savings-goal", category:"calculator", icon:"◎", title:"Savings Goal Calculator", desc:"Goal aur deadline se required monthly saving calculate karein.", tag:"Money • Goal", isNew:true },
        { id:"aspect-ratio", category:"calculator", icon:"16:9", title:"Aspect Ratio Calculator", desc:"Width/height resize karein aur common screen ratios identify karein.", tag:"Video • Design", isNew:true },
        { id:"time-duration", category:"calculator", icon:"Δt", title:"Time Duration Calculator", desc:"Do times ke darmiyan duration aur overnight shift calculate karein.", tag:"Time • Hours", isNew:true },

        { id:"image-studio", category:"image", icon:"▧", title:"Image Compressor & Resizer", desc:"Image ko browser mein resize, compress aur PNG/JPEG/WebP banayen.", tag:"Private • Canvas" },
        { id:"image-base64", category:"image", icon:"64", title:"Image to Base64", desc:"Image file ka preview aur Base64 data URL banayen.", tag:"Image • Dev" },
        { id:"image-color", category:"image", icon:"◉", title:"Image Color Picker", desc:"Uploaded image par click karke exact HEX/RGB color pick karein.", tag:"Design • Color" },
        { id:"favicon-maker", category:"image", icon:"ICO", title:"Favicon Maker", desc:"Letter, colors aur shape se downloadable favicon PNG banayen.", tag:"Brand • Canvas", isNew:true },

        { id:"uuid", category:"utility", icon:"ID", title:"UUID Generator", desc:"Secure UUID v4 values bulk mein generate aur copy karein.", tag:"ID • Random" },
        { id:"random-number", category:"utility", icon:"🎲", title:"Random Number Generator", desc:"Custom range mein one ya multiple unique random numbers banayen.", tag:"Random • Secure" },
        { id:"random-picker", category:"utility", icon:"☝", title:"Random Name Picker", desc:"Names/options ki list se fair random winners choose karein.", tag:"Giveaway • Picker" },
        { id:"color-converter", category:"utility", icon:"◐", title:"HEX / RGB / HSL Converter", desc:"Color formats convert karein aur live preview dekhein.", tag:"Color • CSS" },
        { id:"palette", category:"utility", icon:"▰", title:"Color Palette Generator", desc:"Base color se complementary, analogous ya triadic palette banayen.", tag:"Design • Palette" },
        { id:"file-hash", category:"utility", icon:"#", title:"SHA Hash Generator", desc:"Text ya file ka SHA-256/384/512 hash locally generate karein.", tag:"Security • Local" },
        { id:"stopwatch", category:"utility", icon:"◷", title:"Stopwatch & Laps", desc:"Accurate browser stopwatch with pause, reset aur lap timing.", tag:"Time • Timer" },
        { id:"pomodoro", category:"utility", icon:"25", title:"Pomodoro Focus Timer", desc:"Custom focus/break sessions, progress ring aur sound alert.", tag:"Focus • Timer", isNew:true },
        { id:"coin-dice", category:"utility", icon:"⚄", title:"Coin Flip & Dice Roller", desc:"Crypto-random coin flips aur multi-dice rolls instantly generate karein.", tag:"Games • Random", isNew:true }
    ];

    const byId = Object.fromEntries(tools.map(tool => [tool.id, tool]));
    app.querySelector("#lfTotalTools").textContent = tools.length + "+";

    const esc = (value) => String(value ?? "").replace(/[&<>"']/g, char => ({
        "&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#039;"
    })[char]);

    const q = (selector) => modalBody.querySelector(selector);
    const qa = (selector) => [...modalBody.querySelectorAll(selector)];
    const value = (id) => {
        const node = q("#" + id);
        return node ? node.value : "";
    };
    const numberValue = (id) => {
        const raw = value(id).trim();
        return raw === "" ? NaN : Number(raw);
    };

    function toast(message) {
        clearTimeout(toastTimer);
        toastNode.textContent = message;
        toastNode.classList.add("is-visible");
        toastTimer = setTimeout(() => toastNode.classList.remove("is-visible"), 2200);
    }

    async function copyText(text) {
        const content = String(text ?? "");
        if (!content) {
            toast("Copy karne ke liye result empty hai.");
            return;
        }
        try {
            await navigator.clipboard.writeText(content);
        } catch (_) {
            const temp = document.createElement("textarea");
            temp.value = content;
            temp.setAttribute("readonly", "");
            temp.style.position = "fixed";
            temp.style.opacity = "0";
            document.body.appendChild(temp);
            temp.select();
            document.execCommand("copy");
            temp.remove();
        }
        toast("Copied to clipboard!");
    }

    function showResult(id, content, type = "") {
        const box = q("#" + id);
        if (!box) return null;
        box.classList.remove("is-error", "is-success");
        if (type) box.classList.add("is-" + type);
        if (content !== undefined) box.textContent = content;
        box.classList.add("is-visible");
        return box;
    }

    function showError(id, message) {
        showResult(id, message, "error");
    }

    function hideResult(id) {
        const box = q("#" + id);
        if (box) box.classList.remove("is-visible", "is-error", "is-success");
    }

    function formatNumber(num, maxDigits = 2) {
        return new Intl.NumberFormat(undefined, { maximumFractionDigits: maxDigits }).format(num);
    }

    function currency(num) {
        return formatNumber(num, 2);
    }

    function secureRandomInt(max) {
        if (!Number.isSafeInteger(max) || max <= 0) return 0;
        const range = 0x100000000;
        const limit = range - (range % max);
        const array = new Uint32Array(1);
        do crypto.getRandomValues(array); while (array[0] >= limit);
        return array[0] % max;
    }

    function shuffle(array) {
        const copy = [...array];
        for (let i = copy.length - 1; i > 0; i--) {
            const j = secureRandomInt(i + 1);
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    }

    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    function downloadTextFile(text, filename, mime = "text/plain;charset=utf-8") {
        downloadBlob(new Blob([text], { type: mime }), filename);
    }

    async function downloadRemote(url, filename) {
        try {
            const response = await fetch(url, { mode: "cors" });
            if (!response.ok) throw new Error("Download failed");
            downloadBlob(await response.blob(), filename);
        } catch (_) {
            window.open(url, "_blank", "noopener,noreferrer");
            toast("Image new tab mein khul gayi — wahan se save karein.");
        }
    }

    function debounce(fn, wait = 180) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), wait);
        };
    }

    function renderTools() {
        const term = searchInput.value.trim().toLowerCase();
        const filtered = tools.filter(tool => {
            const inCategory = activeCategory === "all" || tool.category === activeCategory;
            const haystack = `${tool.title} ${tool.desc} ${tool.tag} ${tool.category}`.toLowerCase();
            return inCategory && (!term || haystack.includes(term));
        });

        grid.textContent = "";
        const fragment = document.createDocumentFragment();
        filtered.forEach(tool => {
            const card = document.createElement("article");
            card.className = "lf-card";
            card.tabIndex = 0;
            card.setAttribute("role", "button");
            card.setAttribute("aria-label", `Open ${tool.title}`);
            card.dataset.tool = tool.id;
            card.dataset.category = tool.category;
            card.innerHTML = `
                <div class="lf-card-inner">
                    <div class="lf-card-top">
                        <div class="lf-icon" aria-hidden="true">${esc(tool.icon)}</div>
                        <span class="lf-open-arrow" aria-hidden="true">↗</span>
                    </div>
                    <h2 class="lf-card-title">${esc(tool.title)}</h2>
                    <p class="lf-card-desc">${esc(tool.desc)}</p>
                    <div class="lf-card-footer">
                        <span class="lf-badge">${esc(tool.tag)}</span>
                        <span class="lf-free${tool.isNew ? " is-new" : ""}">${tool.isNew ? "NEW • FREE" : "FREE"}</span>
                    </div>
                </div>`;
            fragment.appendChild(card);
        });
        grid.appendChild(fragment);
        resultCount.textContent = `${filtered.length} of ${tools.length} tools`;
        emptyState.hidden = filtered.length !== 0;
    }

    const templates = {
        "yt-thumb": () => `
            <div class="lf-field">
                <label class="lf-label" for="yt-url">YouTube video URL ya ID</label>
                <input class="lf-input" id="yt-url" type="text" inputmode="url" placeholder="https://youtube.com/watch?v=..." autocomplete="off">
            </div>
            <div class="lf-actions">
                <button class="lf-btn" type="button" data-action="yt-thumbnail">Find thumbnails</button>
            </div>
            <div class="lf-tip"><span>💡</span><span>Watch, Shorts, Live, Embed aur youtu.be links supported hain. Highest available resolution automatically select hogi.</span></div>
            <div class="lf-result" id="yt-result"></div>`,

        "wa-link": () => `
            <div class="lf-tool-grid">
                <div class="lf-field">
                    <label class="lf-label" for="wa-number">Phone number <span class="lf-label-hint">country code ke saath</span></label>
                    <input class="lf-input" id="wa-number" type="tel" inputmode="tel" placeholder="923001234567" autocomplete="tel">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="wa-message">Pre-filled message <span class="lf-label-hint">optional</span></label>
                    <input class="lf-input" id="wa-message" type="text" placeholder="Assalam-o-Alaikum..." maxlength="1000">
                </div>
            </div>
            <div class="lf-actions">
                <button class="lf-btn" type="button" data-action="wa-link">Generate link</button>
            </div>
            <div class="lf-result" id="wa-result"></div>`,

        "hashtags": () => `
            <div class="lf-tool-grid">
                <div class="lf-field">
                    <label class="lf-label" for="hash-keyword">Main keyword</label>
                    <input class="lf-input" id="hash-keyword" type="text" placeholder="e.g. Pakistan travel" maxlength="60">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="hash-category">Niche</label>
                    <select class="lf-select" id="hash-category">
                        <option value="travel">Travel</option><option value="food">Food</option>
                        <option value="fitness">Fitness</option><option value="tech">Tech & Coding</option>
                        <option value="business">Business</option><option value="fashion">Fashion</option>
                        <option value="beauty">Beauty</option><option value="gaming">Gaming</option>
                        <option value="photography">Photography</option><option value="education">Education</option>
                        <option value="motivation">Motivation</option><option value="funny">Funny & Memes</option>
                    </select>
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="hash-count">Number of hashtags <span class="lf-label-hint">5–30</span></label>
                    <input class="lf-input" id="hash-count" type="number" min="5" max="30" value="20">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="hash-style">Mix style</label>
                    <select class="lf-select" id="hash-style">
                        <option value="balanced">Balanced reach</option>
                        <option value="broad">Mostly broad</option>
                        <option value="niche">Mostly niche</option>
                    </select>
                </div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="hashtags">Generate hashtags</button></div>
            <div class="lf-result" id="hash-result">
                <textarea class="lf-output" id="hash-output" readonly aria-label="Generated hashtags"></textarea>
                <div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#hash-output">Copy hashtags</button></div>
            </div>`,

        "bio-maker": () => `
            <div class="lf-tool-grid">
                <div class="lf-field">
                    <label class="lf-label" for="bio-name">Name / brand</label>
                    <input class="lf-input" id="bio-name" type="text" placeholder="e.g. Ali / LikeXFollow" maxlength="45">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="bio-niche">Niche / profession</label>
                    <input class="lf-input" id="bio-niche" type="text" placeholder="e.g. Digital Marketer" maxlength="55">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="bio-tone">Tone</label>
                    <select class="lf-select" id="bio-tone">
                        <option value="professional">Professional</option><option value="cool">Cool / Attitude</option>
                        <option value="creator">Creator</option><option value="minimal">Minimal</option>
                        <option value="funny">Funny</option><option value="motivational">Motivational</option>
                    </select>
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="bio-cta">Call to action <span class="lf-label-hint">optional</span></label>
                    <input class="lf-input" id="bio-cta" type="text" placeholder="DM for collaborations" maxlength="55">
                </div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="bio">Create bio ideas</button></div>
            <div class="lf-result" id="bio-result"><div class="lf-list" id="bio-list"></div></div>`,

        "caption-maker": () => `
            <div class="lf-tool-grid">
                <div class="lf-field">
                    <label class="lf-label" for="caption-topic">Post topic</label>
                    <input class="lf-input" id="caption-topic" type="text" placeholder="e.g. Northern areas trip" maxlength="90">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="caption-tone">Caption tone</label>
                    <select class="lf-select" id="caption-tone">
                        <option value="engaging">Engaging</option><option value="professional">Professional</option>
                        <option value="funny">Funny</option><option value="inspiring">Inspiring</option>
                        <option value="minimal">Short & minimal</option><option value="story">Story style</option>
                    </select>
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="caption-platform">Platform</label>
                    <select class="lf-select" id="caption-platform">
                        <option>Instagram</option><option>TikTok</option><option>Facebook</option><option>LinkedIn</option><option>YouTube</option>
                    </select>
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="caption-emoji">Emoji style</label>
                    <select class="lf-select" id="caption-emoji">
                        <option value="light">Light emojis</option><option value="more">More emojis</option><option value="none">No emojis</option>
                    </select>
                </div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="caption">Generate ideas</button></div>
            <div class="lf-result" id="caption-result"><div class="lf-list" id="caption-list"></div></div>`,

        "engagement": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="eng-followers">Followers / reach</label><input class="lf-input" id="eng-followers" type="number" min="1" placeholder="10000"></div>
                <div class="lf-field"><label class="lf-label" for="eng-likes">Likes</label><input class="lf-input" id="eng-likes" type="number" min="0" placeholder="650"></div>
                <div class="lf-field"><label class="lf-label" for="eng-comments">Comments</label><input class="lf-input" id="eng-comments" type="number" min="0" placeholder="35"></div>
                <div class="lf-field"><label class="lf-label" for="eng-saves">Saves</label><input class="lf-input" id="eng-saves" type="number" min="0" placeholder="20"></div>
                <div class="lf-field"><label class="lf-label" for="eng-shares">Shares</label><input class="lf-input" id="eng-shares" type="number" min="0" placeholder="15"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="engagement">Calculate rate</button></div>
            <div class="lf-result" id="eng-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>Formula: total interactions ÷ followers/reach × 100. Platform benchmarks account size aur content type ke mutabiq vary karte hain.</span></div>`,

        "yt-timestamp": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2">
                    <label class="lf-label" for="time-url">YouTube URL</label>
                    <input class="lf-input" id="time-url" type="url" placeholder="https://youtu.be/VIDEO_ID">
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="time-value">Start time</label>
                    <input class="lf-input" id="time-value" type="text" placeholder="1:25 or 01:02:30">
                </div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="yt-timestamp">Create timestamp link</button></div>
            <div class="lf-result" id="time-result"></div>`,

        "username": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="user-name">Name / brand</label><input class="lf-input" id="user-name" type="text" placeholder="e.g. Hamza" maxlength="35"></div>
                <div class="lf-field"><label class="lf-label" for="user-keyword">Niche keyword</label><input class="lf-input" id="user-keyword" type="text" placeholder="e.g. fitness" maxlength="30"></div>
                <div class="lf-field">
                    <label class="lf-label" for="user-style">Style</label>
                    <select class="lf-select" id="user-style"><option value="clean">Clean</option><option value="pro">Professional</option><option value="fun">Fun</option><option value="short">Short</option></select>
                </div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="username">Generate usernames</button></div>
            <div class="lf-result" id="user-result"><div class="lf-list" id="user-list"></div></div>
            <div class="lf-tip"><span>💡</span><span>Availability platform par separately check karein; generator kisi username ko reserve nahi karta.</span></div>`,

        "fancy-font": () => `
            <div class="lf-field">
                <label class="lf-label" for="font-input">Your text</label>
                <input class="lf-input" id="font-input" type="text" placeholder="Type something..." maxlength="120">
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="fancy-font">Generate styles</button></div>
            <div class="lf-result" id="font-result"><div class="lf-list" id="font-list"></div></div>
            <div class="lf-tip"><span>ℹ️</span><span>Ye Unicode characters hain; kuch older devices/apps har style ko same tarah display nahi karte.</span></div>`,

        "case-converter": () => `
            <div class="lf-field">
                <label class="lf-label" for="case-input">Text</label>
                <textarea class="lf-textarea" id="case-input" placeholder="Paste or type your text..."></textarea>
            </div>
            <div class="lf-actions">
                <button class="lf-btn lf-btn-small" type="button" data-action="case-upper">UPPERCASE</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-lower">lowercase</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-title">Title Case</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-sentence">Sentence case</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-camel">camelCase</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-snake">snake_case</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-kebab">kebab-case</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="case-invert">iNVERT</button>
            </div>
            <div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#case-input">Copy text</button></div>`,

        "word-counter": () => `
            <div class="lf-field">
                <label class="lf-label" for="word-input">Your content <span class="lf-label-hint">live analysis</span></label>
                <textarea class="lf-textarea" id="word-input" style="min-height:180px" placeholder="Start typing or paste content..."></textarea>
            </div>
            <div class="lf-stats">
                <div class="lf-mini-stat"><strong id="stat-words">0</strong><span>Words</span></div>
                <div class="lf-mini-stat"><strong id="stat-chars">0</strong><span>Characters</span></div>
                <div class="lf-mini-stat"><strong id="stat-sentences">0</strong><span>Sentences</span></div>
                <div class="lf-mini-stat"><strong id="stat-reading">0m</strong><span>Read time</span></div>
                <div class="lf-mini-stat"><strong id="stat-nospace">0</strong><span>No spaces</span></div>
                <div class="lf-mini-stat"><strong id="stat-paragraphs">0</strong><span>Paragraphs</span></div>
                <div class="lf-mini-stat"><strong id="stat-lines">0</strong><span>Lines</span></div>
                <div class="lf-mini-stat"><strong id="stat-speaking">0m</strong><span>Speak time</span></div>
            </div>`,

        "line-tools": () => `
            <div class="lf-field"><label class="lf-label" for="line-input">One item per line</label><textarea class="lf-textarea" id="line-input" style="min-height:200px" placeholder="Apple&#10;Banana&#10;Apple"></textarea></div>
            <div class="lf-actions">
                <button class="lf-btn lf-btn-small" type="button" data-action="line-dedupe">Remove duplicates</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="line-blank">Remove blanks</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="line-sort">A → Z</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="line-sort-desc">Z → A</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="line-reverse">Reverse</button>
                <button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="line-shuffle">Shuffle</button>
            </div>
            <div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#line-input">Copy list</button><span class="lf-label-hint" id="line-count"></span></div>`,

        "text-cleaner": () => `
            <div class="lf-field"><label class="lf-label" for="clean-input">Messy text</label><textarea class="lf-textarea" id="clean-input" style="min-height:190px" placeholder="Paste text with extra spaces or blank lines..."></textarea></div>
            <div class="lf-checks">
                <label class="lf-check"><input type="checkbox" id="clean-spaces" checked> Collapse spaces</label>
                <label class="lf-check"><input type="checkbox" id="clean-lines" checked> Remove extra blank lines</label>
                <label class="lf-check"><input type="checkbox" id="clean-invisible" checked> Remove invisible chars</label>
                <label class="lf-check"><input type="checkbox" id="clean-trim" checked> Trim every line</label>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="text-clean">Clean text</button><button class="lf-btn lf-btn-secondary" type="button" data-copy="#clean-input">Copy</button></div>`,

        "find-replace": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="find-value">Find</label><input class="lf-input" id="find-value" type="text" placeholder="Old text"></div>
                <div class="lf-field"><label class="lf-label" for="replace-value">Replace with</label><input class="lf-input" id="replace-value" type="text" placeholder="New text"></div>
                <div class="lf-field lf-span-2"><label class="lf-label" for="replace-input">Content</label><textarea class="lf-textarea" id="replace-input" style="min-height:180px" placeholder="Paste your content..."></textarea></div>
            </div>
            <div class="lf-checks"><label class="lf-check"><input type="checkbox" id="find-case"> Case sensitive</label><label class="lf-check"><input type="checkbox" id="find-first"> Replace first match only</label></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="find-replace">Replace</button><button class="lf-btn lf-btn-secondary" type="button" data-copy="#replace-input">Copy</button></div>
            <div class="lf-result" id="replace-result"></div>`,

        "slug-maker": () => `
            <div class="lf-field"><label class="lf-label" for="slug-input">Page title / text</label><input class="lf-input" id="slug-input" type="text" placeholder="10 Best Social Media Tips in 2026"></div>
            <div class="lf-tool-grid" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="slug-separator">Separator</label><select class="lf-select" id="slug-separator"><option value="-">Hyphen (-)</option><option value="_">Underscore (_)</option></select></div>
                <div class="lf-field"><label class="lf-label" for="slug-max">Max length <span class="lf-label-hint">optional</span></label><input class="lf-input" id="slug-max" type="number" min="10" max="200" placeholder="No limit"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="slug">Generate slug</button></div>
            <div class="lf-result" id="slug-result"></div>`,

        "lorem": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="lorem-type">Generate</label><select class="lf-select" id="lorem-type"><option value="paragraphs">Paragraphs</option><option value="sentences">Sentences</option><option value="words">Words</option></select></div>
                <div class="lf-field"><label class="lf-label" for="lorem-count">Quantity</label><input class="lf-input" id="lorem-count" type="number" min="1" max="50" value="3"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="lorem">Generate text</button></div>
            <div class="lf-result" id="lorem-result"><textarea class="lf-output" id="lorem-output" style="min-height:190px" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#lorem-output">Copy text</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="download-lorem">Download .txt</button></div></div>`,

        "extractor": () => `
            <div class="lf-field"><label class="lf-label" for="extract-input">Source text</label><textarea class="lf-textarea" id="extract-input" style="min-height:180px" placeholder="Paste mixed text here..."></textarea></div>
            <div class="lf-tool-grid" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="extract-type">Extract</label><select class="lf-select" id="extract-type"><option value="emails">Email addresses</option><option value="urls">URLs</option><option value="hashtags">Hashtags</option><option value="mentions">@mentions</option><option value="numbers">Numbers</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="extract">Extract data</button></div>
            <div class="lf-result" id="extract-result"><textarea class="lf-output" id="extract-output" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#extract-output">Copy results</button></div></div>`,

        "ip-info": () => `
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="ip-info">Check my IP info</button></div>
            <div class="lf-result" id="ip-result"></div>
            <div class="lf-tip"><span>🔒</span><span>Public IP/location result third-party IP service se aata hai aur location approximate hoti hai. Browser/device details local hain.</span></div>`,

        "password-gen": () => `
            <div class="lf-tool-grid">
                <div class="lf-field">
                    <label class="lf-label" for="pass-mode">Generator type</label>
                    <select class="lf-select" id="pass-mode"><option value="password">Random password</option><option value="phrase">Memorable passphrase</option></select>
                </div>
                <div class="lf-field">
                    <label class="lf-label" for="pass-length">Length / word count</label>
                    <input class="lf-input" id="pass-length" type="number" min="4" max="128" value="20">
                </div>
            </div>
            <div class="lf-checks" style="margin-top:14px" id="pass-options">
                <label class="lf-check"><input type="checkbox" id="pass-lower" checked> a–z</label>
                <label class="lf-check"><input type="checkbox" id="pass-upper" checked> A–Z</label>
                <label class="lf-check"><input type="checkbox" id="pass-number" checked> 0–9</label>
                <label class="lf-check"><input type="checkbox" id="pass-symbol" checked> Symbols</label>
                <label class="lf-check"><input type="checkbox" id="pass-ambiguous"> Remove ambiguous</label>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="password-gen">Generate securely</button></div>
            <div class="lf-result" id="pass-result"></div>
            <div class="lf-tip"><span>🔐</span><span>Random values browser Crypto API se banti hain. Password kahin send ya store nahi hota.</span></div>`,

        "password-check": () => `
            <div class="lf-field"><label class="lf-label" for="check-password">Password to analyze</label><input class="lf-input" id="check-password" type="password" autocomplete="new-password" placeholder="Type a password..."></div>
            <div class="lf-meter"><div class="lf-meter-bar" id="strength-bar"></div></div>
            <div class="lf-label"><span id="strength-label">Waiting for password</span><span id="strength-entropy">0 bits</span></div>
            <div class="lf-result" id="strength-result"></div>
            <div class="lf-tip"><span>🔒</span><span>Analysis is page par locally hoti hai. Ye estimate hai, breach-database check nahi.</span></div>`,

        "qr-maker": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2"><label class="lf-label" for="qr-text">Text or URL</label><textarea class="lf-textarea" id="qr-text" placeholder="https://likexfollow.com" style="min-height:90px"></textarea></div>
                <div class="lf-field"><label class="lf-label" for="qr-size">Size</label><select class="lf-select" id="qr-size"><option value="200">200 × 200</option><option value="300" selected>300 × 300</option><option value="500">500 × 500</option><option value="800">800 × 800</option></select></div>
                <div class="lf-field"><label class="lf-label" for="qr-margin">Quiet margin</label><select class="lf-select" id="qr-margin"><option value="10">Normal</option><option value="20">Wide</option><option value="0">None</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="qr">Generate QR</button></div>
            <div class="lf-result" id="qr-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>QR image generation ke liye free external QR endpoint use hota hai; sensitive secrets encode na karein.</span></div>`,

        "url-codec": () => `
            <div class="lf-field"><label class="lf-label" for="url-input">URL or text</label><textarea class="lf-textarea" id="url-input" style="min-height:170px" placeholder="https://example.com/search?q=hello world"></textarea></div>
            <div class="lf-actions"><button class="lf-btn lf-btn-small" type="button" data-action="url-encode-component">Encode component</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="url-encode">Encode full URI</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="url-decode">Decode</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#url-input">Copy</button></div>
            <div class="lf-result" id="url-result"></div>`,

        "base64": () => `
            <div class="lf-field"><label class="lf-label" for="b64-input">Unicode text / Base64</label><textarea class="lf-textarea" id="b64-input" style="min-height:190px" placeholder="English, اردو, emoji — all supported"></textarea></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="base64-encode">Encode</button><button class="lf-btn lf-btn-secondary" type="button" data-action="base64-decode">Decode</button><button class="lf-btn lf-btn-secondary" type="button" data-copy="#b64-input">Copy</button></div>
            <div class="lf-result" id="b64-result"></div>`,

        "json-tool": () => `
            <div class="lf-field"><label class="lf-label" for="json-input">JSON data</label><textarea class="lf-textarea" id="json-input" style="min-height:260px;font-family:ui-monospace,monospace" spellcheck="false" placeholder='{"name":"LikeXFollow","free":true}'></textarea></div>
            <div class="lf-actions"><button class="lf-btn lf-btn-small" type="button" data-action="json-format">Format</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="json-minify">Minify</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="json-sort">Sort keys</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="json-validate">Validate</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#json-input">Copy</button></div>
            <div class="lf-result" id="json-result"></div>`,

        "html-entities": () => `
            <div class="lf-field"><label class="lf-label" for="html-input">HTML or text</label><textarea class="lf-textarea" id="html-input" style="min-height:210px" placeholder='<div class="hello">Hello & welcome</div>'></textarea></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="html-encode">Encode entities</button><button class="lf-btn lf-btn-secondary" type="button" data-action="html-decode">Decode entities</button><button class="lf-btn lf-btn-secondary" type="button" data-copy="#html-input">Copy</button></div>`,

        "utm-builder": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2"><label class="lf-label" for="utm-url">Website URL</label><input class="lf-input" id="utm-url" type="url" placeholder="https://likexfollow.com/page"></div>
                <div class="lf-field"><label class="lf-label" for="utm-source">Campaign source *</label><input class="lf-input" id="utm-source" type="text" placeholder="instagram"></div>
                <div class="lf-field"><label class="lf-label" for="utm-medium">Campaign medium *</label><input class="lf-input" id="utm-medium" type="text" placeholder="social"></div>
                <div class="lf-field"><label class="lf-label" for="utm-campaign">Campaign name *</label><input class="lf-input" id="utm-campaign" type="text" placeholder="summer_sale"></div>
                <div class="lf-field"><label class="lf-label" for="utm-term">Campaign term</label><input class="lf-input" id="utm-term" type="text" placeholder="optional"></div>
                <div class="lf-field lf-span-2"><label class="lf-label" for="utm-content">Campaign content</label><input class="lf-input" id="utm-content" type="text" placeholder="reel_a / button_top"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="utm">Build campaign URL</button></div>
            <div class="lf-result" id="utm-result"></div>`,

        "meta-tags": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2"><label class="lf-label" for="meta-title">Page title <span class="lf-label-hint" id="meta-title-count">0/60</span></label><input class="lf-input" id="meta-title" type="text" maxlength="120" placeholder="Your page title"></div>
                <div class="lf-field lf-span-2"><label class="lf-label" for="meta-desc">Description <span class="lf-label-hint" id="meta-desc-count">0/160</span></label><textarea class="lf-textarea" id="meta-desc" style="min-height:90px" maxlength="300" placeholder="Short, useful page description..."></textarea></div>
                <div class="lf-field"><label class="lf-label" for="meta-url">Canonical URL</label><input class="lf-input" id="meta-url" type="url" placeholder="https://example.com/page"></div>
                <div class="lf-field"><label class="lf-label" for="meta-image">Social image URL</label><input class="lf-input" id="meta-image" type="url" placeholder="https://example.com/image.jpg"></div>
                <div class="lf-field"><label class="lf-label" for="meta-site">Site name</label><input class="lf-input" id="meta-site" type="text" placeholder="LikeXFollow"></div>
                <div class="lf-field"><label class="lf-label" for="meta-type">Open Graph type</label><select class="lf-select" id="meta-type"><option value="website">website</option><option value="article">article</option><option value="product">product</option><option value="profile">profile</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="meta-tags">Generate tags</button></div>
            <div class="lf-result" id="meta-result"><textarea class="lf-output" id="meta-output" style="min-height:230px" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#meta-output">Copy meta tags</button></div></div>`,

        "regex-tester": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="regex-pattern">Pattern <span class="lf-label-hint">without / /</span></label><input class="lf-input" id="regex-pattern" type="text" placeholder="\\b[A-Z]\\w+"></div>
                <div class="lf-field"><label class="lf-label" for="regex-flags">Flags</label><input class="lf-input" id="regex-flags" type="text" value="gi" placeholder="gim"></div>
                <div class="lf-field lf-span-2"><label class="lf-label" for="regex-input">Test text</label><textarea class="lf-textarea" id="regex-input" style="min-height:190px" placeholder="Paste test text..."></textarea></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="regex">Test regex</button></div>
            <div class="lf-result" id="regex-result"></div>
            <div class="lf-tip"><span>⚠️</span><span>Untrusted ya extremely complex patterns browser ko slow kar sakte hain.</span></div>`,

        "timestamp": () => `
            <div class="lf-stats">
                <div class="lf-mini-stat" style="grid-column:span 2"><strong id="timestamp-now" style="font-size:1rem">—</strong><span>Current Unix seconds</span></div>
                <div class="lf-mini-stat" style="grid-column:span 2"><strong id="timestamp-local" style="font-size:1rem">—</strong><span>Your local time</span></div>
            </div>
            <div class="lf-tool-grid" style="margin-top:16px">
                <div class="lf-field"><label class="lf-label" for="timestamp-input">Unix timestamp</label><input class="lf-input" id="timestamp-input" type="text" inputmode="numeric" placeholder="1710000000"></div>
                <div class="lf-field"><label class="lf-label" for="datetime-input">Date & time</label><input class="lf-input" id="datetime-input" type="datetime-local"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="timestamp-to-date">Timestamp → date</button><button class="lf-btn lf-btn-secondary" type="button" data-action="date-to-timestamp">Date → timestamp</button></div>
            <div class="lf-result" id="timestamp-result"></div>`,

        "age": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="age-dob">Date of birth</label><input class="lf-input" id="age-dob" type="date"></div>
                <div class="lf-field"><label class="lf-label" for="age-on">Calculate age on</label><input class="lf-input" id="age-on" type="date"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="age">Calculate exact age</button></div>
            <div class="lf-result" id="age-result"></div>`,

        "discount": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="disc-price">Original price</label><input class="lf-input" id="disc-price" type="number" min="0" step="any" placeholder="5000"></div>
                <div class="lf-field"><label class="lf-label" for="disc-one">First discount %</label><input class="lf-input" id="disc-one" type="number" min="0" max="100" step="any" placeholder="20"></div>
                <div class="lf-field"><label class="lf-label" for="disc-two">Extra discount % <span class="lf-label-hint">optional</span></label><input class="lf-input" id="disc-two" type="number" min="0" max="100" step="any" placeholder="5"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="discount">Calculate sale price</button></div>
            <div class="lf-result" id="disc-result"></div>
            <div class="lf-tip"><span>💡</span><span>Stacked discounts add nahi hote; second discount pehle discounted amount par lagta hai.</span></div>`,

        "cpm": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="cpm-cost">Total ad cost</label><input class="lf-input" id="cpm-cost" type="number" min="0" step="any" placeholder="5000"></div>
                <div class="lf-field"><label class="lf-label" for="cpm-impressions">Impressions</label><input class="lf-input" id="cpm-impressions" type="number" min="0" step="1" placeholder="250000"></div>
                <div class="lf-field"><label class="lf-label" for="cpm-rate">CPM</label><input class="lf-input" id="cpm-rate" type="number" min="0" step="any" placeholder="leave one blank"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="cpm">Calculate missing value</button></div>
            <div class="lf-result" id="cpm-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>Kisi ek field ko blank rakhein; baqi do values se woh calculate ho jayegi.</span></div>`,

        "percentage": () => `
            <div class="lf-field">
                <label class="lf-label" for="percent-mode">Calculation</label>
                <select class="lf-select" id="percent-mode">
                    <option value="of">X% of Y</option>
                    <option value="what">X is what % of Y</option>
                    <option value="change">Percentage change from X to Y</option>
                    <option value="add">Add X% to Y</option>
                    <option value="subtract">Subtract X% from Y</option>
                </select>
            </div>
            <div class="lf-tool-grid" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="percent-x" id="percent-x-label">Percentage (X)</label><input class="lf-input" id="percent-x" type="number" step="any" placeholder="20"></div>
                <div class="lf-field"><label class="lf-label" for="percent-y" id="percent-y-label">Value (Y)</label><input class="lf-input" id="percent-y" type="number" step="any" placeholder="500"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="percentage">Calculate</button></div>
            <div class="lf-result" id="percent-result"></div>`,

        "bmi": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="bmi-height">Height (cm)</label><input class="lf-input" id="bmi-height" type="number" min="50" max="280" step="any" placeholder="175"></div>
                <div class="lf-field"><label class="lf-label" for="bmi-weight">Weight (kg)</label><input class="lf-input" id="bmi-weight" type="number" min="10" max="500" step="any" placeholder="70"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="bmi">Calculate BMI</button></div>
            <div class="lf-result" id="bmi-result"></div>
            <div class="lf-tip"><span>⚕️</span><span>BMI general screening estimate hai—diagnosis nahi. Athletes, pregnancy aur children ke liye interpretation different ho sakti hai.</span></div>`,

        "loan": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="loan-amount">Loan amount</label><input class="lf-input" id="loan-amount" type="number" min="0" step="any" placeholder="1000000"></div>
                <div class="lf-field"><label class="lf-label" for="loan-rate">Annual rate %</label><input class="lf-input" id="loan-rate" type="number" min="0" step="any" placeholder="18"></div>
                <div class="lf-field"><label class="lf-label" for="loan-years">Term (years)</label><input class="lf-input" id="loan-years" type="number" min="0.08" max="100" step="any" placeholder="5"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="loan">Calculate EMI</button></div>
            <div class="lf-result" id="loan-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>Estimate reducing-balance formula par based hai; bank fees, insurance aur changing rates included nahi.</span></div>`,

        "tax": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="tax-amount">Amount</label><input class="lf-input" id="tax-amount" type="number" min="0" step="any" placeholder="1000"></div>
                <div class="lf-field"><label class="lf-label" for="tax-rate">Tax rate %</label><input class="lf-input" id="tax-rate" type="number" min="0" step="any" placeholder="18"></div>
                <div class="lf-field"><label class="lf-label" for="tax-mode">Amount type</label><select class="lf-select" id="tax-mode"><option value="exclusive">Tax exclusive — add tax</option><option value="inclusive">Tax inclusive — remove tax</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="tax">Calculate tax</button></div>
            <div class="lf-result" id="tax-result"></div>`,

        "profit": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="profit-cost">Total cost</label><input class="lf-input" id="profit-cost" type="number" min="0" step="any" placeholder="700"></div>
                <div class="lf-field"><label class="lf-label" for="profit-revenue">Selling price / revenue</label><input class="lf-input" id="profit-revenue" type="number" min="0" step="any" placeholder="1000"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="profit">Calculate profit</button></div>
            <div class="lf-result" id="profit-result"></div>`,

        "date-diff": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="date-start">Start date</label><input class="lf-input" id="date-start" type="date"></div>
                <div class="lf-field"><label class="lf-label" for="date-end">End date</label><input class="lf-input" id="date-end" type="date"></div>
            </div>
            <div class="lf-checks" style="margin-top:14px"><label class="lf-check"><input type="checkbox" id="date-inclusive"> Include end date</label></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="date-diff">Calculate difference</button></div>
            <div class="lf-result" id="date-result"></div>`,

        "tip": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="tip-bill">Bill amount</label><input class="lf-input" id="tip-bill" type="number" min="0" step="any" placeholder="5000"></div>
                <div class="lf-field"><label class="lf-label" for="tip-rate">Tip %</label><input class="lf-input" id="tip-rate" type="number" min="0" step="any" value="10"></div>
                <div class="lf-field"><label class="lf-label" for="tip-people">People</label><input class="lf-input" id="tip-people" type="number" min="1" step="1" value="2"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="tip">Split bill</button></div>
            <div class="lf-result" id="tip-result"></div>`,

        "unit": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field">
                    <label class="lf-label" for="unit-category">Category</label>
                    <select class="lf-select" id="unit-category">
                        <option value="length">Length</option><option value="weight">Weight</option>
                        <option value="temperature">Temperature</option><option value="area">Area</option>
                        <option value="speed">Speed</option><option value="data">Digital data</option>
                    </select>
                </div>
                <div class="lf-field"><label class="lf-label" for="unit-from">From</label><select class="lf-select" id="unit-from"></select></div>
                <div class="lf-field"><label class="lf-label" for="unit-to">To</label><select class="lf-select" id="unit-to"></select></div>
                <div class="lf-field lf-span-2"><label class="lf-label" for="unit-value">Value</label><input class="lf-input" id="unit-value" type="number" step="any" placeholder="1"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="unit">Convert</button><button class="lf-btn lf-btn-secondary" type="button" data-action="unit-swap">Swap units</button></div>
            <div class="lf-result" id="unit-result"></div>`,

        "fuel": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="fuel-distance">Trip distance (km)</label><input class="lf-input" id="fuel-distance" type="number" min="0" step="any" placeholder="500"></div>
                <div class="lf-field"><label class="lf-label" for="fuel-mileage">Vehicle average (km/L)</label><input class="lf-input" id="fuel-mileage" type="number" min="0.01" step="any" placeholder="12"></div>
                <div class="lf-field"><label class="lf-label" for="fuel-price">Fuel price per litre</label><input class="lf-input" id="fuel-price" type="number" min="0" step="any" placeholder="280"></div>
                <div class="lf-field"><label class="lf-label" for="fuel-people">Cost split people</label><input class="lf-input" id="fuel-people" type="number" min="1" step="1" value="1"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="fuel">Estimate trip cost</button></div>
            <div class="lf-result" id="fuel-result"></div>`,

        "image-studio": () => `
            <label class="lf-dropzone" for="studio-file">
                <span style="font-size:28px">🖼️</span><br><strong>Choose an image</strong><br>
                <span>JPG, PNG, WebP — processed in your browser</span>
                <span class="lf-file-name" id="studio-file-name">No file selected</span>
                <input id="studio-file" type="file" accept="image/*">
            </label>
            <div class="lf-tool-grid lf-three" style="margin-top:15px">
                <div class="lf-field"><label class="lf-label" for="studio-width">Max width (px)</label><input class="lf-input" id="studio-width" type="number" min="1" max="12000" placeholder="Original"></div>
                <div class="lf-field"><label class="lf-label" for="studio-format">Output format</label><select class="lf-select" id="studio-format"><option value="image/webp">WebP</option><option value="image/jpeg">JPEG</option><option value="image/png">PNG</option></select></div>
                <div class="lf-field"><label class="lf-label" for="studio-quality">Quality <span id="studio-quality-label">82%</span></label><input id="studio-quality" type="range" min="10" max="100" value="82"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="image-studio">Resize & compress</button></div>
            <div class="lf-result" id="studio-result"></div>
            <div class="lf-tip"><span>🔒</span><span>Image Canvas API se locally process hoti hai; upload nahi hoti. PNG quality slider ko ignore kar sakta hai.</span></div>`,

        "image-base64": () => `
            <label class="lf-dropzone" for="base64-file">
                <span style="font-size:28px">📁</span><br><strong>Choose an image</strong><br>
                <span>Small web assets ke liye useful</span>
                <span class="lf-file-name" id="base64-file-name">No file selected</span>
                <input id="base64-file" type="file" accept="image/*">
            </label>
            <div class="lf-result" id="image-b64-result">
                <img class="lf-preview-img" id="image-b64-preview" alt="Selected image preview">
                <textarea class="lf-output" id="image-b64-output" style="min-height:130px" readonly></textarea>
                <div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#image-b64-output">Copy data URL</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="download-base64">Download .txt</button></div>
            </div>
            <div class="lf-tip"><span>💡</span><span>Base64 file size aam tor par original se bari hoti hai; large images ko CSS/HTML mein embed na karein.</span></div>`,

        "image-color": () => `
            <label class="lf-dropzone" for="color-file">
                <span style="font-size:28px">🎨</span><br><strong>Choose an image</strong><br>
                <span>Phir canvas par kisi point ko tap/click karein</span>
                <span class="lf-file-name" id="color-file-name">No file selected</span>
                <input id="color-file" type="file" accept="image/*">
            </label>
            <div id="color-canvas-wrap" style="display:none;margin-top:15px">
                <canvas class="lf-canvas" id="color-canvas"></canvas>
                <div class="lf-result is-visible" id="picked-result">
                    <div class="lf-checks">
                        <button class="lf-color-chip" type="button" id="picked-hex" data-copy-self><span class="lf-swatch" id="picked-swatch"></span><span>#FFFFFF</span></button>
                        <button class="lf-color-chip" type="button" id="picked-rgb" data-copy-self>rgb(255, 255, 255)</button>
                    </div>
                </div>
            </div>
            <div class="lf-tip"><span>🔒</span><span>Pixel reading aapke browser mein hoti hai; image server par upload nahi hoti.</span></div>`,

        "uuid": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="uuid-count">How many UUIDs?</label><input class="lf-input" id="uuid-count" type="number" min="1" max="100" value="5"></div>
                <div class="lf-field"><label class="lf-label" for="uuid-case">Letter case</label><select class="lf-select" id="uuid-case"><option value="lower">lowercase</option><option value="upper">UPPERCASE</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="uuid">Generate UUIDs</button></div>
            <div class="lf-result" id="uuid-result"><textarea class="lf-output" id="uuid-output" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#uuid-output">Copy all</button></div></div>`,

        "random-number": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="rand-min">Minimum</label><input class="lf-input" id="rand-min" type="number" step="1" value="1"></div>
                <div class="lf-field"><label class="lf-label" for="rand-max">Maximum</label><input class="lf-input" id="rand-max" type="number" step="1" value="100"></div>
                <div class="lf-field"><label class="lf-label" for="rand-count">How many?</label><input class="lf-input" id="rand-count" type="number" min="1" max="1000" value="1"></div>
            </div>
            <div class="lf-checks" style="margin-top:14px"><label class="lf-check"><input type="checkbox" id="rand-unique"> Unique numbers only</label><label class="lf-check"><input type="checkbox" id="rand-sort"> Sort result</label></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="random-number">Generate numbers</button></div>
            <div class="lf-result" id="rand-result"></div>`,

        "random-picker": () => `
            <div class="lf-field"><label class="lf-label" for="picker-input">Names / choices <span class="lf-label-hint">one per line</span></label><textarea class="lf-textarea" id="picker-input" style="min-height:210px" placeholder="Ali&#10;Sara&#10;Usman&#10;Ayesha"></textarea></div>
            <div class="lf-tool-grid" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="picker-count">Number of winners</label><input class="lf-input" id="picker-count" type="number" min="1" max="100" value="1"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="random-picker">Pick winner</button></div>
            <div class="lf-result" id="picker-result"></div>`,

        "color-converter": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="color-hex">HEX</label><input class="lf-input" id="color-hex" type="text" value="#6D28D9" placeholder="#6D28D9"></div>
                <div class="lf-field"><label class="lf-label" for="color-native">Color picker</label><input class="lf-input" id="color-native" type="color" value="#6d28d9" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="color-r">Red (0–255)</label><input class="lf-input" id="color-r" type="number" min="0" max="255" value="109"></div>
                <div class="lf-field"><label class="lf-label" for="color-g">Green (0–255)</label><input class="lf-input" id="color-g" type="number" min="0" max="255" value="40"></div>
                <div class="lf-field"><label class="lf-label" for="color-b">Blue (0–255)</label><input class="lf-input" id="color-b" type="number" min="0" max="255" value="217"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="color-from-hex">Convert HEX</button><button class="lf-btn lf-btn-secondary" type="button" data-action="color-from-rgb">Convert RGB</button></div>
            <div class="lf-result is-visible" id="color-result"></div>`,

        "palette": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="palette-base">Base color</label><input class="lf-input" id="palette-base" type="color" value="#6d28d9" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="palette-mode">Harmony</label><select class="lf-select" id="palette-mode"><option value="analogous">Analogous</option><option value="complementary">Complementary</option><option value="triadic">Triadic</option><option value="monochrome">Monochromatic</option><option value="split">Split complementary</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="palette">Generate palette</button></div>
            <div class="lf-result is-visible"><div class="lf-palette" id="palette-result"></div><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="copy-palette">Copy HEX values</button></div></div>`,

        "file-hash": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="hash-algo">Algorithm</label><select class="lf-select" id="hash-algo"><option value="SHA-256">SHA-256</option><option value="SHA-384">SHA-384</option><option value="SHA-512">SHA-512</option></select></div>
                <div class="lf-field"><label class="lf-label" for="hash-source">Source</label><select class="lf-select" id="hash-source"><option value="text">Text</option><option value="file">File</option></select></div>
                <div class="lf-field lf-span-2" id="hash-text-field"><label class="lf-label" for="hash-text">Text to hash</label><textarea class="lf-textarea" id="hash-text" placeholder="Type or paste text..."></textarea></div>
                <div class="lf-field lf-span-2" id="hash-file-field" style="display:none"><label class="lf-dropzone" for="hash-file"><strong>Choose a file</strong><span class="lf-file-name" id="hash-file-name">No file selected</span><input id="hash-file" type="file"></label></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="file-hash">Generate hash</button></div>
            <div class="lf-result" id="hash-digest-result"></div>
            <div class="lf-tip"><span>🔒</span><span>Hash locally Web Crypto API se banta hai; selected file upload nahi hoti.</span></div>`,

        "stopwatch": () => `
            <div class="lf-stopwatch" id="stopwatch-display">00:00.000</div>
            <div class="lf-actions" style="justify-content:center">
                <button class="lf-btn" type="button" data-action="stopwatch-toggle" id="stopwatch-toggle">Start</button>
                <button class="lf-btn lf-btn-secondary" type="button" data-action="stopwatch-lap">Lap</button>
                <button class="lf-btn lf-btn-danger" type="button" data-action="stopwatch-reset">Reset</button>
            </div>
            <div class="lf-result" id="stopwatch-result"><div class="lf-list" id="stopwatch-laps"></div></div>`,

        "yt-title": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2"><label class="lf-label" for="ytt-topic">Video topic</label><input class="lf-input" id="ytt-topic" type="text" placeholder="e.g. Beginner Instagram growth tips" maxlength="100"></div>
                <div class="lf-field"><label class="lf-label" for="ytt-audience">Audience</label><input class="lf-input" id="ytt-audience" type="text" placeholder="e.g. New creators" maxlength="60"></div>
                <div class="lf-field"><label class="lf-label" for="ytt-keyword">Main keyword <span class="lf-label-hint">optional</span></label><input class="lf-input" id="ytt-keyword" type="text" placeholder="Instagram growth" maxlength="50"></div>
                <div class="lf-field"><label class="lf-label" for="ytt-style">Title style</label><select class="lf-select" id="ytt-style"><option value="benefit">Benefit driven</option><option value="howto">How-to</option><option value="curiosity">Curiosity</option><option value="list">List / numbers</option><option value="mistakes">Mistakes / warning</option></select></div>
                <div class="lf-field"><label class="lf-label" for="ytt-year">Add year</label><select class="lf-select" id="ytt-year"><option value="">No year</option><option value="current">Current year</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="yt-title">Generate 10 titles</button></div>
            <div class="lf-result" id="ytt-result"><div class="lf-list" id="ytt-list"></div></div>
            <div class="lf-tip"><span>💡</span><span>Title ideas templates hain—video jo promise karta hai sirf wahi claim use karein.</span></div>`,

        "hook-maker": () => `
            <div class="lf-tool-grid">
                <div class="lf-field lf-span-2"><label class="lf-label" for="hook-topic">Content topic / offer</label><input class="lf-input" id="hook-topic" type="text" placeholder="e.g. Grow an Instagram page from zero" maxlength="110"></div>
                <div class="lf-field"><label class="lf-label" for="hook-platform">Format</label><select class="lf-select" id="hook-platform"><option>Reel / Short</option><option>Carousel</option><option>Post caption</option><option>Ad</option><option>YouTube intro</option></select></div>
                <div class="lf-field"><label class="lf-label" for="hook-tone">Tone</label><select class="lf-select" id="hook-tone"><option value="bold">Bold</option><option value="curious">Curiosity</option><option value="helpful">Helpful</option><option value="story">Story</option><option value="urgent">Urgent</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="hook-maker">Generate hooks</button></div>
            <div class="lf-result" id="hook-result"><div class="lf-list" id="hook-list"></div></div>`,

        "keyword-density": () => `
            <div class="lf-field"><label class="lf-label" for="density-input">Article / caption text</label><textarea class="lf-textarea" id="density-input" style="min-height:250px" placeholder="Paste your content..."></textarea></div>
            <div class="lf-tool-grid" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="density-keyword">Target keyword <span class="lf-label-hint">optional</span></label><input class="lf-input" id="density-keyword" type="text" placeholder="social media growth"></div>
                <div class="lf-field"><label class="lf-label" for="density-min">Minimum word length</label><input class="lf-input" id="density-min" type="number" min="1" max="12" value="3"></div>
            </div>
            <div class="lf-checks" style="margin-top:14px"><label class="lf-check"><input type="checkbox" id="density-stop" checked> Ignore common stop words</label></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="keyword-density">Analyze density</button></div>
            <div class="lf-result" id="density-result"></div>`,

        "readability": () => `
            <div class="lf-field"><label class="lf-label" for="read-input">English content</label><textarea class="lf-textarea" id="read-input" style="min-height:280px" placeholder="Paste English text for readability analysis..."></textarea></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="readability">Analyze readability</button></div>
            <div class="lf-result" id="read-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>Flesch Reading Ease English text ke liye estimate hai; Urdu/Roman Urdu par accurate nahi hoga.</span></div>`,

        "text-speech": () => `
            <div class="lf-field"><label class="lf-label" for="speech-text">Text to speak</label><textarea class="lf-textarea" id="speech-text" style="min-height:220px" maxlength="5000" placeholder="Type English, Urdu ya kisi supported language ka text..."></textarea></div>
            <div class="lf-tool-grid lf-three" style="margin-top:14px">
                <div class="lf-field"><label class="lf-label" for="speech-voice">Browser voice</label><select class="lf-select" id="speech-voice"><option>Loading voices...</option></select></div>
                <div class="lf-field"><label class="lf-label" for="speech-rate">Speed <span id="speech-rate-label">1.0×</span></label><input id="speech-rate" type="range" min="0.5" max="2" step="0.1" value="1"></div>
                <div class="lf-field"><label class="lf-label" for="speech-pitch">Pitch <span id="speech-pitch-label">1.0</span></label><input id="speech-pitch" type="range" min="0.5" max="2" step="0.1" value="1"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="speech-play">▶ Speak</button><button class="lf-btn lf-btn-secondary" type="button" data-action="speech-pause">Pause / Resume</button><button class="lf-btn lf-btn-danger" type="button" data-action="speech-stop">Stop</button></div>
            <div class="lf-result" id="speech-result"></div>
            <div class="lf-tip"><span>🔊</span><span>Available voices device/browser par depend karti hain. Audio file download browser Speech API provide nahi karti.</span></div>`,

        "jwt-decoder": () => `
            <div class="lf-field"><label class="lf-label" for="jwt-input">JSON Web Token</label><textarea class="lf-textarea" id="jwt-input" style="min-height:150px;font-family:ui-monospace,monospace" spellcheck="false" placeholder="eyJhbGciOi..."></textarea></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="jwt-decode">Decode token</button></div>
            <div class="lf-result" id="jwt-result">
                <label class="lf-label" for="jwt-header">Header</label><textarea class="lf-output" id="jwt-header" readonly></textarea>
                <label class="lf-label" for="jwt-payload" style="margin-top:12px">Payload</label><textarea class="lf-output" id="jwt-payload" style="min-height:170px" readonly></textarea>
                <div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#jwt-payload">Copy payload</button></div>
            </div>
            <div class="lf-tip"><span>⚠️</span><span>Decoder signature verify nahi karta. Sensitive production tokens untrusted websites par paste na karein; processing yahan local hai.</span></div>`,

        "gradient-maker": () => `
            <div class="lf-design-preview" id="gradient-preview"></div>
            <div class="lf-tool-grid lf-three" style="margin-top:15px">
                <div class="lf-field"><label class="lf-label" for="gradient-one">Color 1</label><input class="lf-input" id="gradient-one" type="color" value="#6d28d9" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="gradient-two">Color 2</label><input class="lf-input" id="gradient-two" type="color" value="#c4b5fd" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="gradient-type">Type</label><select class="lf-select" id="gradient-type"><option value="linear">Linear</option><option value="radial">Radial</option></select></div>
                <div class="lf-field lf-span-2" id="gradient-angle-field"><label class="lf-label" for="gradient-angle">Angle <span id="gradient-angle-label">135°</span></label><input id="gradient-angle" type="range" min="0" max="360" value="135"></div>
            </div>
            <div class="lf-result is-visible"><textarea class="lf-output" id="gradient-output" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#gradient-output">Copy CSS</button><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-action="gradient-swap">Swap colors</button></div></div>`,

        "shadow-maker": () => `
            <div class="lf-design-preview lf-shadow-stage"><div class="lf-shadow-box" id="shadow-preview">Aa</div></div>
            <div class="lf-tool-grid lf-three" style="margin-top:15px">
                <div class="lf-field"><label class="lf-label" for="shadow-x">X <span id="shadow-x-label">0px</span></label><input id="shadow-x" type="range" min="-50" max="50" value="0"></div>
                <div class="lf-field"><label class="lf-label" for="shadow-y">Y <span id="shadow-y-label">18px</span></label><input id="shadow-y" type="range" min="-50" max="50" value="18"></div>
                <div class="lf-field"><label class="lf-label" for="shadow-blur">Blur <span id="shadow-blur-label">40px</span></label><input id="shadow-blur" type="range" min="0" max="100" value="40"></div>
                <div class="lf-field"><label class="lf-label" for="shadow-spread">Spread <span id="shadow-spread-label">-10px</span></label><input id="shadow-spread" type="range" min="-50" max="50" value="-10"></div>
                <div class="lf-field"><label class="lf-label" for="shadow-color">Color</label><input class="lf-input" id="shadow-color" type="color" value="#6d28d9" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="shadow-opacity">Opacity <span id="shadow-opacity-label">25%</span></label><input id="shadow-opacity" type="range" min="0" max="100" value="25"></div>
            </div>
            <div class="lf-checks" style="margin-top:14px"><label class="lf-check"><input type="checkbox" id="shadow-inset"> Inset shadow</label></div>
            <div class="lf-result is-visible"><textarea class="lf-output" id="shadow-output" readonly></textarea><div class="lf-actions"><button class="lf-btn lf-btn-secondary lf-btn-small" type="button" data-copy="#shadow-output">Copy CSS</button></div></div>`,

        "contrast-checker": () => `
            <div class="lf-contrast-preview" id="contrast-preview"><strong>Readable text Aa</strong><span>Accessibility preview</span></div>
            <div class="lf-tool-grid" style="margin-top:15px">
                <div class="lf-field"><label class="lf-label" for="contrast-fg">Text color</label><input class="lf-input" id="contrast-fg" type="color" value="#17122b" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="contrast-bg">Background</label><input class="lf-input" id="contrast-bg" type="color" value="#ffffff" style="padding:5px"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn lf-btn-secondary" type="button" data-action="contrast-swap">Swap colors</button></div>
            <div class="lf-result is-visible" id="contrast-result"></div>`,

        "http-status": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="http-code">HTTP status code</label><input class="lf-input" id="http-code" type="number" min="100" max="599" placeholder="404" list="http-common"></div>
                <datalist id="http-common"><option value="200"><option value="201"><option value="301"><option value="302"><option value="400"><option value="401"><option value="403"><option value="404"><option value="409"><option value="422"><option value="429"><option value="500"><option value="502"><option value="503"></datalist>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="http-status">Look up status</button></div>
            <div class="lf-result" id="http-result"></div>`,

        "compound-interest": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="ci-principal">Starting amount</label><input class="lf-input" id="ci-principal" type="number" min="0" step="any" placeholder="100000"></div>
                <div class="lf-field"><label class="lf-label" for="ci-monthly">Monthly contribution</label><input class="lf-input" id="ci-monthly" type="number" min="0" step="any" value="0"></div>
                <div class="lf-field"><label class="lf-label" for="ci-rate">Annual return %</label><input class="lf-input" id="ci-rate" type="number" step="any" placeholder="10"></div>
                <div class="lf-field"><label class="lf-label" for="ci-years">Years</label><input class="lf-input" id="ci-years" type="number" min="0.1" max="100" step="any" placeholder="10"></div>
                <div class="lf-field"><label class="lf-label" for="ci-frequency">Compounding</label><select class="lf-select" id="ci-frequency"><option value="12">Monthly</option><option value="4">Quarterly</option><option value="2">Semi-annually</option><option value="1">Annually</option><option value="365">Daily</option></select></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="compound-interest">Calculate growth</button></div>
            <div class="lf-result" id="ci-result"></div>
            <div class="lf-tip"><span>ℹ️</span><span>Estimate fixed rate assume karta hai; taxes, fees aur market variation included nahi.</span></div>`,

        "savings-goal": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="save-target">Goal amount</label><input class="lf-input" id="save-target" type="number" min="0" step="any" placeholder="1000000"></div>
                <div class="lf-field"><label class="lf-label" for="save-current">Already saved</label><input class="lf-input" id="save-current" type="number" min="0" step="any" value="0"></div>
                <div class="lf-field"><label class="lf-label" for="save-rate">Annual return %</label><input class="lf-input" id="save-rate" type="number" step="any" value="0"></div>
                <div class="lf-field"><label class="lf-label" for="save-years">Time (years)</label><input class="lf-input" id="save-years" type="number" min="0.08" max="100" step="any" placeholder="3"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="savings-goal">Calculate monthly saving</button></div>
            <div class="lf-result" id="save-result"></div>`,

        "aspect-ratio": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="ratio-width">Original width</label><input class="lf-input" id="ratio-width" type="number" min="1" step="1" placeholder="1920"></div>
                <div class="lf-field"><label class="lf-label" for="ratio-height">Original height</label><input class="lf-input" id="ratio-height" type="number" min="1" step="1" placeholder="1080"></div>
                <div class="lf-field"><label class="lf-label" for="ratio-new-width">New width <span class="lf-label-hint">or leave blank</span></label><input class="lf-input" id="ratio-new-width" type="number" min="1" step="1" placeholder="1280"></div>
                <div class="lf-field"><label class="lf-label" for="ratio-new-height">New height <span class="lf-label-hint">or leave blank</span></label><input class="lf-input" id="ratio-new-height" type="number" min="1" step="1" placeholder="auto"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="aspect-ratio">Calculate size</button><button class="lf-btn lf-btn-secondary" type="button" data-action="ratio-swap">Swap original</button></div>
            <div class="lf-result" id="ratio-result"></div>`,

        "time-duration": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="duration-start">Start time</label><input class="lf-input" id="duration-start" type="time" value="09:00"></div>
                <div class="lf-field"><label class="lf-label" for="duration-end">End time</label><input class="lf-input" id="duration-end" type="time" value="17:30"></div>
                <div class="lf-field"><label class="lf-label" for="duration-break">Break minutes</label><input class="lf-input" id="duration-break" type="number" min="0" step="1" value="30"></div>
            </div>
            <div class="lf-checks" style="margin-top:14px"><label class="lf-check"><input type="checkbox" id="duration-overnight" checked> End can be next day</label></div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="time-duration">Calculate duration</button></div>
            <div class="lf-result" id="duration-result"></div>`,

        "favicon-maker": () => `
            <div class="lf-tool-grid">
                <div class="lf-field"><label class="lf-label" for="fav-letter">Letter / symbol</label><input class="lf-input" id="fav-letter" type="text" value="L" maxlength="2"></div>
                <div class="lf-field"><label class="lf-label" for="fav-shape">Shape</label><select class="lf-select" id="fav-shape"><option value="rounded">Rounded square</option><option value="circle">Circle</option><option value="square">Square</option></select></div>
                <div class="lf-field"><label class="lf-label" for="fav-bg">Background</label><input class="lf-input" id="fav-bg" type="color" value="#6d28d9" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="fav-fg">Text color</label><input class="lf-input" id="fav-fg" type="color" value="#ffffff" style="padding:5px"></div>
                <div class="lf-field"><label class="lf-label" for="fav-size">Download size</label><select class="lf-select" id="fav-size"><option value="32">32 × 32</option><option value="64">64 × 64</option><option value="180">180 × 180</option><option value="512" selected>512 × 512</option></select></div>
            </div>
            <div class="lf-result is-visible"><canvas class="lf-canvas" id="fav-canvas" width="256" height="256" style="width:min(256px,100%)"></canvas><div class="lf-actions" style="justify-content:center"><button class="lf-btn lf-btn-success" type="button" data-action="favicon-download">Download PNG</button></div></div>
            <div class="lf-tip"><span>💡</span><span>PNG ko <code>&lt;link rel="icon" href="..."&gt;</code> ke saath use kar sakte hain.</span></div>`,

        "pomodoro": () => `
            <div class="lf-focus-timer">
                <div class="lf-focus-ring" id="pomo-ring"><div><strong id="pomo-display">25:00</strong><span id="pomo-phase">Focus session</span></div></div>
                <div class="lf-label" style="justify-content:center;margin-top:12px"><span id="pomo-round">Round 1 of 4</span></div>
            </div>
            <div class="lf-tool-grid lf-three" style="margin-top:15px">
                <div class="lf-field"><label class="lf-label" for="pomo-focus">Focus minutes</label><input class="lf-input" id="pomo-focus" type="number" min="1" max="180" value="25"></div>
                <div class="lf-field"><label class="lf-label" for="pomo-break">Break minutes</label><input class="lf-input" id="pomo-break" type="number" min="1" max="60" value="5"></div>
                <div class="lf-field"><label class="lf-label" for="pomo-rounds">Rounds</label><input class="lf-input" id="pomo-rounds" type="number" min="1" max="12" value="4"></div>
            </div>
            <div class="lf-actions" style="justify-content:center"><button class="lf-btn" type="button" data-action="pomo-toggle" id="pomo-toggle">Start focus</button><button class="lf-btn lf-btn-secondary" type="button" data-action="pomo-skip">Skip</button><button class="lf-btn lf-btn-danger" type="button" data-action="pomo-reset">Reset</button></div>
            <div class="lf-tip"><span>🔔</span><span>Timer tab open rehne par best kaam karta hai. Session change par short browser-generated beep bajti hai.</span></div>`,

        "coin-dice": () => `
            <div class="lf-tool-grid lf-three">
                <div class="lf-field"><label class="lf-label" for="random-mode">Mode</label><select class="lf-select" id="random-mode"><option value="coin">Coin flip</option><option value="dice">Dice roll</option></select></div>
                <div class="lf-field"><label class="lf-label" for="dice-sides">Dice sides</label><select class="lf-select" id="dice-sides"><option value="4">D4</option><option value="6" selected>D6</option><option value="8">D8</option><option value="10">D10</option><option value="12">D12</option><option value="20">D20</option><option value="100">D100</option></select></div>
                <div class="lf-field"><label class="lf-label" for="dice-count">How many?</label><input class="lf-input" id="dice-count" type="number" min="1" max="50" value="1"></div>
            </div>
            <div class="lf-actions"><button class="lf-btn" type="button" data-action="coin-dice">Generate random result</button></div>
            <div class="lf-result" id="coin-result"></div>`
    };

    const hashtagBank = {
        travel: ["travel","travelgram","wanderlust","adventure","explore","vacation","trip","nature","mountains","roadtrip","travelphotography","beautifuldestinations","discoverearth","backpacking","tourism","traveldiaries","travelreels","pakistantravel","northernareas","explorepakistan","hiddenplaces","weekendgetaway","landscape","sunset","travelguide","bucketlist","journey","outdoors","stayandwander","passportready"],
        food: ["food","foodie","instafood","foodphotography","yummy","delicious","homemade","foodlover","dinner","lunch","breakfast","recipe","cooking","streetfood","dessert","tasty","foodblogger","pakistanifood","desifood","karachifood","lahorefood","islamabadfood","spicy","restaurant","foodreels","chef","eatlocal","comfortfood","foodstagram","hungry"],
        fitness: ["fitness","gym","workout","fit","motivation","bodybuilding","health","training","fitnessmotivation","fitlife","exercise","strength","cardio","gymmotivation","personaltrainer","weightloss","muscle","healthy","fitnessjourney","homeworkout","legday","fitfam","nutrition","wellness","discipline","progress","active","mindset","trainhard","recovery"],
        tech: ["tech","technology","coding","programming","developer","software","webdevelopment","javascript","php","ai","innovation","gadgets","computer","cybersecurity","code","programmer","webdesign","startup","digital","machinelearning","appdevelopment","opensource","devlife","codinglife","tipsandtricks","frontend","backend","automation","productivity","futuretech"],
        business: ["business","entrepreneur","marketing","success","startup","smallbusiness","businessowner","digitalmarketing","branding","sales","leadership","money","growth","mindset","ecommerce","onlinebusiness","strategy","socialmediamarketing","contentmarketing","personalbrand","hustle","goals","productivity","networking","investment","clientwork","businessideas","founder","pakistanbusiness","worksmart"],
        fashion: ["fashion","style","ootd","fashionblogger","outfit","streetstyle","fashionstyle","instafashion","clothing","styleinspo","trend","fashionista","mensfashion","womensfashion","modestfashion","pakistanifashion","designer","wardrobe","lookbook","accessories","summerstyle","winterstyle","casualstyle","formalwear","outfitideas","fashionreels","dailylook","styleguide","newcollection","shoplocal"],
        beauty: ["beauty","makeup","skincare","makeupartist","beautytips","glow","selfcare","cosmetics","hairstyle","nails","makeuptutorial","skin","beautyblogger","naturalbeauty","skincareroutine","haircare","glowup","beautyproducts","pakistanibeauty","bridalmakeup","lipstick","eyemakeup","wellness","salon","beautyreels","makeuplook","skinhealth","beautyroutine","selflove","fragrance"],
        gaming: ["gaming","gamer","games","videogames","gamingcommunity","pcgaming","mobilegaming","playstation","xbox","esports","streamer","gameplay","gamingclips","gamingsetup","pubg","freefire","valorant","minecraft","fortnite","gamers","livestream","gaminglife","proplayer","ranked","multiplayer","gamingreels","console","pcbuild","pakistangamers","gg"],
        photography: ["photography","photooftheday","photographer","photo","portrait","naturephotography","streetphotography","landscapephotography","travelphotography","photoshoot","mobilephotography","camera","creative","visuals","editing","lightroom","canon","nikon","sonyalpha","composition","goldenhour","blackandwhite","urbanphotography","portraitphotography","photographytips","instagood","art","moodygrams","visualstorytelling","pakistanphotography"],
        education: ["education","learning","student","study","knowledge","teacher","school","university","studytips","onlinelearning","skills","career","books","motivation","exam","studymotivation","elearning","course","learnonline","productivity","students","teaching","notes","science","languagelearning","selflearning","growthmindset","educationmatters","pakistanstudents","future"],
        motivation: ["motivation","inspiration","success","mindset","goals","positivevibes","quotes","motivationalquotes","selflove","life","believe","discipline","growth","positivity","hustle","dreambig","focus","nevergiveup","hardwork","confidence","personaldevelopment","dailyinspiration","strong","gratitude","purpose","consistency","betteryou","successmindset","keepgoing","progress"],
        funny: ["funny","memes","meme","comedy","lol","jokes","fun","humor","funnymemes","viral","laugh","relatable","desimemes","pakistanimemes","memesdaily","comedyvideos","funnyvideos","reels","trending","entertainment","sarcasm","dankmemes","justforfun","laughoutloud","mood","desihumor","reelitfeelit","instafunny","comic","goodvibes"]
    };

    const passphraseWords = [
        "amber","anchor","apple","atlas","bamboo","beacon","berry","blaze","bloom","brave","breeze","brick",
        "cactus","calm","cedar","cloud","cobalt","comet","coral","cosmic","crystal","dawn","delta","dream",
        "eagle","ember","falcon","fern","flame","forest","frost","galaxy","garden","globe","gold","harbor",
        "honey","indigo","island","jade","jungle","kiwi","lake","lemon","light","lotus","lucky","lunar",
        "maple","meadow","meteor","mint","moon","moss","mountain","neon","nova","oasis","ocean","olive",
        "orbit","panda","pearl","pepper","phoenix","pine","pixel","planet","plum","quartz","quiet","rain",
        "raven","river","rocket","rose","ruby","sage","shadow","silver","sky","solar","spark","star",
        "stone","storm","sunny","tiger","trail","tulip","velvet","violet","wave","willow","winter","wolf"
    ];

    const loremWords = "lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua enim ad minim veniam quis nostrud exercitation ullamco laboris nisi aliquip ex ea commodo consequat duis aute irure dolor reprehenderit voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt culpa qui officia deserunt mollit anim id est laborum integer facilisis libero vitae augue faucibus pellentesque habitant morbi tristique senectus netus malesuada fames turpis egestas vivamus dignissim massa sapien efficitur posuere".split(" ");

    const unitDefinitions = {
        length: {
            units: { m:["Meter",1], km:["Kilometer",1000], cm:["Centimeter",.01], mm:["Millimeter",.001], mi:["Mile",1609.344], yd:["Yard",.9144], ft:["Foot",.3048], in:["Inch",.0254], nmi:["Nautical mile",1852] }
        },
        weight: {
            units: { kg:["Kilogram",1], g:["Gram",.001], mg:["Milligram",.000001], lb:["Pound",.45359237], oz:["Ounce",.028349523125], ton:["Metric tonne",1000], st:["Stone",6.35029318] }
        },
        area: {
            units: { sqm:["Square meter",1], sqkm:["Square kilometer",1000000], sqft:["Square foot",.09290304], sqyd:["Square yard",.83612736], acre:["Acre",4046.8564224], hectare:["Hectare",10000], sqmi:["Square mile",2589988.110336] }
        },
        speed: {
            units: { mps:["Meter/second",1], kph:["Kilometer/hour",.2777777778], mph:["Mile/hour",.44704], knot:["Knot",.5144444444], fps:["Foot/second",.3048] }
        },
        data: {
            units: { B:["Byte",1], KB:["Kilobyte (1000)",1000], MB:["Megabyte (1000²)",1000000], GB:["Gigabyte (1000³)",1000000000], TB:["Terabyte (1000⁴)",1000000000000], KiB:["Kibibyte (1024)",1024], MiB:["Mebibyte (1024²)",1048576], GiB:["Gibibyte (1024³)",1073741824], bit:["Bit",.125] }
        },
        temperature: {
            units: { C:["Celsius"], F:["Fahrenheit"], K:["Kelvin"] }
        }
    };

    const englishStopWords = new Set(("a an and are as at be been but by can could did do does for from had has have he her hers him his how i if in into is it its may me might more most my no not of on one or our ours out over she should so some than that the their theirs them then there these they this those through to too under up us very was we were what when where which who why will with would you your yours").split(" "));

    const httpStatuses = {
        100:["Continue","Server ne initial request headers receive kar liye; client request body continue kar sakta hai."],
        101:["Switching Protocols","Server requested protocol par switch kar raha hai."],
        200:["OK","Request successful hui aur expected response return hua."],
        201:["Created","Request successful hui aur naya resource create hua."],
        202:["Accepted","Request processing ke liye accept hui, lekin abhi complete nahi."],
        204:["No Content","Request successful hui, response body nahi hai."],
        206:["Partial Content","Range request ka sirf requested hissa return hua."],
        301:["Moved Permanently","Resource permanently naye URL par move ho gaya."],
        302:["Found","Temporary redirect; resource filhal dusre URL par hai."],
        304:["Not Modified","Cached version abhi valid hai; new body ki zaroorat nahi."],
        307:["Temporary Redirect","Temporary redirect jo original HTTP method preserve karta hai."],
        308:["Permanent Redirect","Permanent redirect jo original HTTP method preserve karta hai."],
        400:["Bad Request","Malformed ya invalid request ko server process nahi kar saka."],
        401:["Unauthorized","Authentication required hai ya credentials invalid hain."],
        403:["Forbidden","Server request samajhta hai lekin access allow nahi karta."],
        404:["Not Found","Requested resource server par nahi mila."],
        405:["Method Not Allowed","Is resource par used HTTP method supported nahi."],
        408:["Request Timeout","Request complete hone mein allowed time se zyada lag gaya."],
        409:["Conflict","Current resource state ke saath request conflict karti hai."],
        410:["Gone","Resource permanently remove ho chuka hai."],
        413:["Content Too Large","Request payload server limit se zyada hai."],
        415:["Unsupported Media Type","Request content type server support nahi karta."],
        418:["I'm a Teapot","April Fools status; server coffee brew karne se inkar karta hai."],
        422:["Unprocessable Content","Syntax valid hai lekin validation/semantic errors ki wajah se process nahi ho sakti."],
        429:["Too Many Requests","Rate limit exceed ho gayi; retry se pehle wait karein."],
        500:["Internal Server Error","Server par unexpected error hui."],
        501:["Not Implemented","Server requested functionality support nahi karta."],
        502:["Bad Gateway","Gateway/upstream server se invalid response mila."],
        503:["Service Unavailable","Server temporarily overloaded ya maintenance mein hai."],
        504:["Gateway Timeout","Gateway ko upstream server se time par response nahi mila."]
    };

    function addCleanup(callback) {
        modalCleanup.push(callback);
    }

    function cleanupActiveTool() {
        modalCleanup.forEach(callback => {
            try { callback(); } catch (_) {}
        });
        modalCleanup = [];
        if (imageDownloadUrl) {
            URL.revokeObjectURL(imageDownloadUrl);
            imageDownloadUrl = "";
        }
        if (stopwatchState?.timer) cancelAnimationFrame(stopwatchState.timer);
        stopwatchState = null;
        if (pomodoroState?.timer) clearInterval(pomodoroState.timer);
        pomodoroState = null;
        if ("speechSynthesis" in window) window.speechSynthesis.cancel();
        speechUtterance = null;
    }

    let bodyLock = null;

    function openTool(id) {
        const tool = byId[id];
        const template = templates[id];
        if (!tool || !template) return;

        cleanupActiveTool();
        activeTool = id;
        lastFocusedElement = document.activeElement;
        modalIcon.textContent = tool.icon;
        modalTitle.textContent = tool.title;
        modalDesc.textContent = tool.desc;
        modalBody.innerHTML = template();
        modal.hidden = false;
        const scrollY = window.scrollY || document.documentElement.scrollTop || 0;
        bodyLock = {
            scrollY,
            overflow:document.body.style.overflow,
            position:document.body.style.position,
            top:document.body.style.top,
            width:document.body.style.width
        };
        document.body.style.overflow = "hidden";
        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = "100%";
        initTool(id);
        modalBody.scrollTop = 0;

        requestAnimationFrame(() => {
            const first = modalBody.querySelector("input:not([type=file]), textarea, select, button");
            (first || modal.querySelector("[data-close-modal]"))?.focus({ preventScroll:true });
        });
    }

    function closeTool() {
        if (modal.hidden) return;
        cleanupActiveTool();
        modal.hidden = true;
        modalBody.textContent = "";
        activeTool = null;
        if (bodyLock) {
            document.body.style.overflow = bodyLock.overflow;
            document.body.style.position = bodyLock.position;
            document.body.style.top = bodyLock.top;
            document.body.style.width = bodyLock.width;
            window.scrollTo(0, bodyLock.scrollY);
            bodyLock = null;
        }
        lastFocusedElement?.focus?.({ preventScroll:true });
    }

    function createCopyList(container, items, prefix = "") {
        container.textContent = "";
        items.forEach((item, index) => {
            const row = document.createElement("div");
            row.className = "lf-list-item";
            const valueNode = document.createElement("div");
            valueNode.className = "lf-list-value";
            valueNode.style.whiteSpace = "pre-wrap";
            valueNode.textContent = prefix ? `${prefix} ${item}` : item;
            const button = document.createElement("button");
            button.type = "button";
            button.className = "lf-btn lf-btn-secondary lf-btn-small";
            button.textContent = "Copy";
            button.dataset.copyValue = item;
            button.setAttribute("aria-label", `Copy item ${index + 1}`);
            row.append(valueNode, button);
            container.appendChild(row);
        });
    }

    function makeLinkResult(boxId, url, label = "Open link") {
        const box = showResult(boxId);
        box.textContent = "";
        const row = document.createElement("div");
        row.className = "lf-output-row";
        const input = document.createElement("input");
        input.className = "lf-input";
        input.value = url;
        input.readOnly = true;
        input.setAttribute("aria-label", "Generated URL");
        const copy = document.createElement("button");
        copy.className = "lf-btn lf-btn-secondary lf-btn-small";
        copy.type = "button";
        copy.textContent = "Copy";
        copy.dataset.copyValue = url;
        row.append(input, copy);
        const actions = document.createElement("div");
        actions.className = "lf-actions";
        const open = document.createElement("a");
        open.className = "lf-btn lf-btn-success";
        open.href = url;
        open.target = "_blank";
        open.rel = "noopener noreferrer";
        open.textContent = label;
        actions.appendChild(open);
        box.append(row, actions);
    }

    function formatBytes(bytes) {
        if (!Number.isFinite(bytes) || bytes < 0) return "—";
        if (bytes === 0) return "0 B";
        const units = ["B","KB","MB","GB","TB"];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${formatNumber(bytes / (1024 ** index), 2)} ${units[index]}`;
    }

    function todayInputValue() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - offset).toISOString().slice(0, 10);
    }

    function initTool(id) {
        if (id === "word-counter") {
            const input = q("#word-input");
            const update = () => updateWordStats(input.value);
            input.addEventListener("input", update);
            update();
        }

        if (id === "line-tools") {
            const input = q("#line-input");
            const update = () => {
                const lines = input.value ? input.value.split(/\r?\n/) : [];
                q("#line-count").textContent = `${lines.length} line${lines.length === 1 ? "" : "s"}`;
            };
            input.addEventListener("input", update);
            update();
        }

        if (id === "password-check") {
            const input = q("#check-password");
            input.addEventListener("input", () => analyzePassword(input.value));
            analyzePassword("");
        }

        if (id === "password-gen") {
            const mode = q("#pass-mode");
            const length = q("#pass-length");
            const options = q("#pass-options");
            const update = () => {
                const phrase = mode.value === "phrase";
                options.style.display = phrase ? "none" : "flex";
                length.min = phrase ? "3" : "4";
                length.max = phrase ? "12" : "128";
                length.value = phrase ? "5" : "20";
                q('label[for="pass-length"]').textContent = phrase ? "Number of words" : "Password length";
            };
            mode.addEventListener("change", update);
        }

        if (id === "meta-tags") {
            const title = q("#meta-title");
            const desc = q("#meta-desc");
            const update = () => {
                q("#meta-title-count").textContent = `${title.value.length}/60`;
                q("#meta-desc-count").textContent = `${desc.value.length}/160`;
            };
            title.addEventListener("input", update);
            desc.addEventListener("input", update);
            update();
        }

        if (id === "timestamp") {
            const update = () => {
                const now = new Date();
                q("#timestamp-now").textContent = Math.floor(now.getTime() / 1000);
                q("#timestamp-local").textContent = now.toLocaleString();
            };
            update();
            const timer = setInterval(update, 1000);
            addCleanup(() => clearInterval(timer));
        }

        if (id === "age") {
            q("#age-on").value = todayInputValue();
            q("#age-dob").max = q("#age-on").value;
        }

        if (id === "date-diff") {
            q("#date-end").value = todayInputValue();
        }

        if (id === "percentage") {
            q("#percent-mode").addEventListener("change", updatePercentLabels);
            updatePercentLabels();
        }

        if (id === "unit") {
            q("#unit-category").addEventListener("change", populateUnits);
            populateUnits();
        }

        if (id === "image-studio") initImageStudio();
        if (id === "image-base64") initImageBase64();
        if (id === "image-color") initImageColorPicker();

        if (id === "color-converter") {
            q("#color-native").addEventListener("input", event => {
                q("#color-hex").value = event.target.value.toUpperCase();
                convertColorFromHex();
            });
            convertColorFromHex();
        }

        if (id === "palette") {
            q("#palette-base").addEventListener("input", generatePalette);
            q("#palette-mode").addEventListener("change", generatePalette);
            generatePalette();
        }

        if (id === "file-hash") {
            const source = q("#hash-source");
            const file = q("#hash-file");
            const updateSource = () => {
                q("#hash-text-field").style.display = source.value === "text" ? "block" : "none";
                q("#hash-file-field").style.display = source.value === "file" ? "block" : "none";
            };
            source.addEventListener("change", updateSource);
            file.addEventListener("change", () => {
                q("#hash-file-name").textContent = file.files[0]?.name || "No file selected";
            });
            updateSource();
        }

        if (id === "stopwatch") initStopwatch();
        if (id === "text-speech") initSpeechTool();
        if (id === "gradient-maker") {
            ["#gradient-one","#gradient-two","#gradient-type","#gradient-angle"].forEach(selector => {
                q(selector).addEventListener("input", updateGradient);
                q(selector).addEventListener("change", updateGradient);
            });
            updateGradient();
        }
        if (id === "shadow-maker") {
            ["#shadow-x","#shadow-y","#shadow-blur","#shadow-spread","#shadow-color","#shadow-opacity","#shadow-inset"].forEach(selector => {
                q(selector).addEventListener("input", updateShadow);
                q(selector).addEventListener("change", updateShadow);
            });
            updateShadow();
        }
        if (id === "contrast-checker") {
            q("#contrast-fg").addEventListener("input", updateContrast);
            q("#contrast-bg").addEventListener("input", updateContrast);
            updateContrast();
        }
        if (id === "favicon-maker") {
            ["#fav-letter","#fav-shape","#fav-bg","#fav-fg"].forEach(selector => {
                q(selector).addEventListener("input", drawFavicon);
                q(selector).addEventListener("change", drawFavicon);
            });
            drawFavicon();
        }
        if (id === "pomodoro") initPomodoro();
        if (id === "coin-dice") {
            const mode = q("#random-mode");
            const update = () => q('label[for="dice-sides"]').parentElement.style.display = mode.value === "dice" ? "block" : "none";
            mode.addEventListener("change", update);
            update();
        }
    }

    function updateWordStats(text) {
        const trimmed = text.trim();
        const words = trimmed ? (trimmed.match(/[\p{L}\p{N}]+(?:['’_-][\p{L}\p{N}]+)*/gu) || []).length : 0;
        const sentences = trimmed ? (trimmed.match(/[^.!?؟]+[.!?؟]+|[^.!?؟]+$/g) || []).length : 0;
        const paragraphs = trimmed ? trimmed.split(/\n\s*\n/).filter(Boolean).length : 0;
        const lines = text ? text.split(/\r?\n/).length : 0;
        q("#stat-words").textContent = words;
        q("#stat-chars").textContent = text.length;
        q("#stat-sentences").textContent = sentences;
        q("#stat-reading").textContent = words ? `${Math.max(1, Math.ceil(words / 225))}m` : "0m";
        q("#stat-nospace").textContent = text.replace(/\s/g, "").length;
        q("#stat-paragraphs").textContent = paragraphs;
        q("#stat-lines").textContent = lines;
        q("#stat-speaking").textContent = words ? `${Math.max(1, Math.ceil(words / 130))}m` : "0m";
    }

    function updatePercentLabels() {
        const mode = value("percent-mode");
        const labels = {
            of:["Percentage (X)","Value (Y)"],
            what:["Part (X)","Whole (Y)"],
            change:["Old value (X)","New value (Y)"],
            add:["Percentage (X)","Starting value (Y)"],
            subtract:["Percentage (X)","Starting value (Y)"]
        };
        q("#percent-x-label").textContent = labels[mode][0];
        q("#percent-y-label").textContent = labels[mode][1];
    }

    function populateUnits() {
        const category = value("unit-category");
        const definition = unitDefinitions[category];
        const from = q("#unit-from");
        const to = q("#unit-to");
        from.textContent = "";
        to.textContent = "";
        Object.entries(definition.units).forEach(([key, info], index) => {
            const one = new Option(`${info[0]} (${key})`, key);
            const two = new Option(`${info[0]} (${key})`, key);
            from.add(one);
            to.add(two);
            if (index === 1) to.value = key;
        });
        if (category === "temperature") {
            from.value = "C";
            to.value = "F";
        }
    }

    function initSpeechTool() {
        const voiceSelect = q("#speech-voice");
        const rate = q("#speech-rate");
        const pitch = q("#speech-pitch");
        const updateLabels = () => {
            q("#speech-rate-label").textContent = `${Number(rate.value).toFixed(1)}×`;
            q("#speech-pitch-label").textContent = Number(pitch.value).toFixed(1);
        };
        rate.addEventListener("input", updateLabels);
        pitch.addEventListener("input", updateLabels);
        updateLabels();

        if (!("speechSynthesis" in window) || !("SpeechSynthesisUtterance" in window)) {
            voiceSelect.innerHTML = "<option>Not supported</option>";
            qa('[data-action^="speech-"]').forEach(button => button.disabled = true);
            showError("speech-result", "Is browser mein Text-to-Speech API supported nahi.");
            return;
        }

        const loadVoices = () => {
            const selected = voiceSelect.value;
            const voices = window.speechSynthesis.getVoices();
            voiceSelect.textContent = "";
            if (!voices.length) {
                voiceSelect.add(new Option("Default browser voice", ""));
                return;
            }
            voices.forEach((voice,index) => {
                voiceSelect.add(new Option(`${voice.name} — ${voice.lang}${voice.default ? " (default)" : ""}`, String(index)));
            });
            if ([...voiceSelect.options].some(option => option.value === selected)) voiceSelect.value = selected;
        };
        loadVoices();
        window.speechSynthesis.addEventListener?.("voiceschanged", loadVoices);
        addCleanup(() => window.speechSynthesis.removeEventListener?.("voiceschanged", loadVoices));
    }

    function updateGradient() {
        const one = value("gradient-one");
        const two = value("gradient-two");
        const type = value("gradient-type");
        const angle = Number(value("gradient-angle")) || 0;
        const gradient = type === "radial"
            ? `radial-gradient(circle, ${one} 0%, ${two} 100%)`
            : `linear-gradient(${angle}deg, ${one} 0%, ${two} 100%)`;
        q("#gradient-preview").style.background = gradient;
        q("#gradient-output").value = `background: ${gradient};`;
        q("#gradient-angle-label").textContent = `${angle}°`;
        q("#gradient-angle-field").style.opacity = type === "linear" ? "1" : ".45";
        q("#gradient-angle").disabled = type !== "linear";
    }

    function updateShadow() {
        const x = Number(value("shadow-x"));
        const y = Number(value("shadow-y"));
        const blur = Number(value("shadow-blur"));
        const spread = Number(value("shadow-spread"));
        const opacity = Number(value("shadow-opacity")) / 100;
        const rgb = hexToRgb(value("shadow-color")) || { r:0,g:0,b:0 };
        const inset = q("#shadow-inset").checked ? "inset " : "";
        const shadow = `${inset}${x}px ${y}px ${blur}px ${spread}px rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${opacity.toFixed(2)})`;
        q("#shadow-preview").style.boxShadow = shadow;
        q("#shadow-output").value = `box-shadow: ${shadow};`;
        q("#shadow-x-label").textContent = `${x}px`;
        q("#shadow-y-label").textContent = `${y}px`;
        q("#shadow-blur-label").textContent = `${blur}px`;
        q("#shadow-spread-label").textContent = `${spread}px`;
        q("#shadow-opacity-label").textContent = `${Math.round(opacity * 100)}%`;
    }

    function relativeLuminance(rgb) {
        const channels = [rgb.r,rgb.g,rgb.b].map(value => {
            const normalized = value / 255;
            return normalized <= .03928 ? normalized / 12.92 : ((normalized + .055) / 1.055) ** 2.4;
        });
        return .2126 * channels[0] + .7152 * channels[1] + .0722 * channels[2];
    }

    function updateContrast() {
        const fgHex = value("contrast-fg");
        const bgHex = value("contrast-bg");
        const foreground = hexToRgb(fgHex);
        const background = hexToRgb(bgHex);
        if (!foreground || !background) return;
        const one = relativeLuminance(foreground);
        const two = relativeLuminance(background);
        const ratio = (Math.max(one,two) + .05) / (Math.min(one,two) + .05);
        const preview = q("#contrast-preview");
        preview.style.color = fgHex;
        preview.style.background = bgHex;
        const normalAA = ratio >= 4.5;
        const normalAAA = ratio >= 7;
        const largeAA = ratio >= 3;
        const largeAAA = ratio >= 4.5;
        const box = q("#contrast-result");
        box.innerHTML = `<div class="lf-stats">
            <div class="lf-mini-stat" style="grid-column:span 2"><strong>${ratio.toFixed(2)}:1</strong><span>Contrast ratio</span></div>
            <div class="lf-mini-stat"><strong style="color:${normalAA ? "#059669" : "#dc2626"}">${normalAA ? "PASS" : "FAIL"}</strong><span>Normal AA</span></div>
            <div class="lf-mini-stat"><strong style="color:${normalAAA ? "#059669" : "#dc2626"}">${normalAAA ? "PASS" : "FAIL"}</strong><span>Normal AAA</span></div>
            <div class="lf-mini-stat"><strong style="color:${largeAA ? "#059669" : "#dc2626"}">${largeAA ? "PASS" : "FAIL"}</strong><span>Large AA</span></div>
            <div class="lf-mini-stat"><strong style="color:${largeAAA ? "#059669" : "#dc2626"}">${largeAAA ? "PASS" : "FAIL"}</strong><span>Large AAA</span></div>
        </div>`;
        box.classList.add("is-visible");
    }

    function roundedRectPath(context,x,y,width,height,radius) {
        const r = Math.min(radius,width/2,height/2);
        context.beginPath();
        context.moveTo(x+r,y);
        context.arcTo(x+width,y,x+width,y+height,r);
        context.arcTo(x+width,y+height,x,y+height,r);
        context.arcTo(x,y+height,x,y,r);
        context.arcTo(x,y,x+width,y,r);
        context.closePath();
    }

    function renderFaviconCanvas(canvas,size) {
        const context = canvas.getContext("2d");
        canvas.width = size;
        canvas.height = size;
        context.clearRect(0,0,size,size);
        context.fillStyle = value("fav-bg");
        const shape = value("fav-shape");
        if (shape === "circle") {
            context.beginPath();
            context.arc(size/2,size/2,size*.47,0,Math.PI*2);
            context.fill();
        } else if (shape === "rounded") {
            roundedRectPath(context,size*.04,size*.04,size*.92,size*.92,size*.22);
            context.fill();
        } else {
            context.fillRect(size*.04,size*.04,size*.92,size*.92);
        }
        const letters = Array.from(value("fav-letter").trim() || "L").slice(0,2).join("");
        context.fillStyle = value("fav-fg");
        context.font = `800 ${Math.round(size * (letters.length > 1 ? .47 : .61))}px Outfit, Arial, sans-serif`;
        context.textAlign = "center";
        context.textBaseline = "middle";
        context.fillText(letters.toUpperCase(),size/2,size*.52);
    }

    function drawFavicon() {
        renderFaviconCanvas(q("#fav-canvas"),256);
    }

    function initPomodoro() {
        pomodoroState = { running:false, phase:"focus", round:1, remaining:1500, total:1500, timer:null, deadline:0, audio:null };
        const refresh = () => {
            if (!pomodoroState?.running) resetPomodoro();
        };
        ["#pomo-focus","#pomo-break","#pomo-rounds"].forEach(selector => q(selector).addEventListener("change",refresh));
        resetPomodoro();
    }

    function resetPomodoro() {
        if (!pomodoroState) return;
        clearInterval(pomodoroState.timer);
        const focus = Math.max(1,Math.min(180,Number(value("pomo-focus")) || 25));
        pomodoroState = { ...pomodoroState, running:false, phase:"focus", round:1, remaining:focus*60, total:focus*60, timer:null, deadline:0 };
        q("#pomo-toggle").textContent = "Start focus";
        updatePomodoroDisplay();
    }

    function updatePomodoroDisplay() {
        if (!pomodoroState) return;
        const seconds = Math.max(0,Math.ceil(pomodoroState.remaining));
        q("#pomo-display").textContent = `${String(Math.floor(seconds/60)).padStart(2,"0")}:${String(seconds%60).padStart(2,"0")}`;
        q("#pomo-phase").textContent = pomodoroState.phase === "focus" ? "Focus session" : pomodoroState.phase === "break" ? "Break time" : "All rounds complete";
        q("#pomo-round").textContent = `Round ${pomodoroState.round} of ${Math.max(1,Number(value("pomo-rounds")) || 4)}`;
        const progress = pomodoroState.total ? (1 - pomodoroState.remaining/pomodoroState.total) * 360 : 0;
        q("#pomo-ring").style.setProperty("--progress", `${Math.max(0,Math.min(360,progress))}deg`);
    }

    function pomodoroBeep() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;
            const context = pomodoroState.audio || new AudioContextClass();
            pomodoroState.audio = context;
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.frequency.value = 740;
            gain.gain.setValueAtTime(.0001,context.currentTime);
            gain.gain.exponentialRampToValueAtTime(.18,context.currentTime+.02);
            gain.gain.exponentialRampToValueAtTime(.0001,context.currentTime+.28);
            oscillator.connect(gain).connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime+.3);
        } catch (_) {}
    }

    function advancePomodoro() {
        if (!pomodoroState) return;
        pomodoroBeep();
        const maxRounds = Math.max(1,Math.min(12,Number(value("pomo-rounds")) || 4));
        if (pomodoroState.phase === "focus") {
            if (pomodoroState.round >= maxRounds) {
                clearInterval(pomodoroState.timer);
                pomodoroState.running = false;
                pomodoroState.remaining = 0;
                pomodoroState.phase = "complete";
                q("#pomo-toggle").textContent = "Completed";
                updatePomodoroDisplay();
                return;
            }
            pomodoroState.phase = "break";
            pomodoroState.total = Math.max(1,Math.min(60,Number(value("pomo-break")) || 5))*60;
        } else {
            pomodoroState.phase = "focus";
            pomodoroState.round++;
            pomodoroState.total = Math.max(1,Math.min(180,Number(value("pomo-focus")) || 25))*60;
        }
        pomodoroState.remaining = pomodoroState.total;
        pomodoroState.deadline = Date.now()+pomodoroState.remaining*1000;
        updatePomodoroDisplay();
    }

    function pomodoroTick() {
        if (!pomodoroState?.running) return;
        pomodoroState.remaining = Math.max(0,(pomodoroState.deadline-Date.now())/1000);
        if (pomodoroState.remaining <= 0) advancePomodoro();
        else updatePomodoroDisplay();
    }

    function parseYouTubeId(input) {
        const raw = input.trim();
        if (/^[A-Za-z0-9_-]{11}$/.test(raw)) return raw;
        try {
            const url = new URL(/^https?:\/\//i.test(raw) ? raw : `https://${raw}`);
            const host = url.hostname.replace(/^www\./, "").toLowerCase();
            if (host === "youtu.be") {
                const id = url.pathname.split("/").filter(Boolean)[0];
                return /^[A-Za-z0-9_-]{11}$/.test(id || "") ? id : null;
            }
            if (host.endsWith("youtube.com") || host.endsWith("youtube-nocookie.com")) {
                const queryId = url.searchParams.get("v");
                if (/^[A-Za-z0-9_-]{11}$/.test(queryId || "")) return queryId;
                const parts = url.pathname.split("/").filter(Boolean);
                const marker = parts.findIndex(part => ["shorts","embed","live","v"].includes(part));
                const id = marker >= 0 ? parts[marker + 1] : null;
                return /^[A-Za-z0-9_-]{11}$/.test(id || "") ? id : null;
            }
        } catch (_) {}
        const match = raw.match(/(?:v=|youtu\.be\/|shorts\/|embed\/|live\/)([A-Za-z0-9_-]{11})/);
        return match ? match[1] : null;
    }

    function imageExists(url) {
        return new Promise(resolve => {
            const img = new Image();
            const timer = setTimeout(() => resolve(null), 6500);
            img.onload = () => {
                clearTimeout(timer);
                resolve(img.naturalWidth > 120 ? { url, width:img.naturalWidth, height:img.naturalHeight } : null);
            };
            img.onerror = () => {
                clearTimeout(timer);
                resolve(null);
            };
            img.src = `${url}?lf=${Date.now()}`;
        });
    }

    function parseTimeToSeconds(input) {
        const raw = input.trim();
        if (/^\d+$/.test(raw)) return Number(raw);
        const parts = raw.split(":").map(Number);
        if (!parts.length || parts.length > 3 || parts.some(part => !Number.isFinite(part) || part < 0)) return NaN;
        if (parts.slice(1).some(part => part >= 60)) return NaN;
        return parts.reduce((total, part) => total * 60 + part, 0);
    }

    function normalizeTag(text) {
        return text.normalize("NFKD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^\p{L}\p{N}]+/gu, "")
            .toLowerCase();
    }

    function asciiWords(text) {
        return text.normalize("NFKD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/([a-z0-9])([A-Z])/g, "$1 $2")
            .replace(/[^\p{L}\p{N}]+/gu, " ")
            .trim()
            .split(/\s+/)
            .filter(Boolean);
    }

    function toTitleCase(text) {
        const small = new Set(["a","an","and","as","at","but","by","for","in","nor","of","on","or","the","to","up","via","with"]);
        return text.toLowerCase().replace(/\b[\p{L}\p{N}][\p{L}\p{N}'’_-]*/gu, (word, offset) => {
            if (offset > 0 && small.has(word)) return word;
            return word.charAt(0).toUpperCase() + word.slice(1);
        });
    }

    function toSentenceCase(text) {
        return text.toLowerCase().replace(/(^\s*[\p{L}])|([.!?؟]\s*[\p{L}])/gu, match => match.toUpperCase());
    }

    function slugify(text, separator = "-") {
        const safeSeparator = separator === "_" ? "_" : "-";
        return text.normalize("NFKD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .replace(/&/g, " and ")
            .replace(/[^\p{L}\p{N}]+/gu, safeSeparator)
            .replace(new RegExp(`\\${safeSeparator}+`, "g"), safeSeparator)
            .replace(new RegExp(`^\\${safeSeparator}|\\${safeSeparator}$`, "g"), "");
    }

    function unicodeRange(text, upperStart, lowerStart, digitStart = null) {
        return Array.from(text).map(char => {
            const code = char.codePointAt(0);
            if (code >= 65 && code <= 90) return String.fromCodePoint(upperStart + code - 65);
            if (code >= 97 && code <= 122) return String.fromCodePoint(lowerStart + code - 97);
            if (digitStart !== null && code >= 48 && code <= 57) return String.fromCodePoint(digitStart + code - 48);
            return char;
        }).join("");
    }

    function fancyStyles(text) {
        const circledDigits = ["⓪","①","②","③","④","⑤","⑥","⑦","⑧","⑨"];
        const circled = Array.from(text).map(char => {
            const code = char.codePointAt(0);
            if (code >= 65 && code <= 90) return String.fromCodePoint(0x24B6 + code - 65);
            if (code >= 97 && code <= 122) return String.fromCodePoint(0x24D0 + code - 97);
            if (code >= 48 && code <= 57) return circledDigits[code - 48];
            return char;
        }).join("");
        const fullwidth = Array.from(text).map(char => {
            const code = char.codePointAt(0);
            if (code >= 33 && code <= 126) return String.fromCodePoint(code + 0xFEE0);
            if (char === " ") return "　";
            return char;
        }).join("");
        const smallCapsMap = {
            a:"ᴀ",b:"ʙ",c:"ᴄ",d:"ᴅ",e:"ᴇ",f:"ꜰ",g:"ɢ",h:"ʜ",i:"ɪ",j:"ᴊ",k:"ᴋ",l:"ʟ",m:"ᴍ",
            n:"ɴ",o:"ᴏ",p:"ᴘ",q:"ǫ",r:"ʀ",s:"ꜱ",t:"ᴛ",u:"ᴜ",v:"ᴠ",w:"ᴡ",x:"x",y:"ʏ",z:"ᴢ"
        };
        const smallCaps = Array.from(text).map(char => smallCapsMap[char.toLowerCase()] || char).join("");
        return [
            ["Bold", unicodeRange(text, 0x1D400, 0x1D41A, 0x1D7CE)],
            ["Sans", unicodeRange(text, 0x1D5A0, 0x1D5BA, 0x1D7E2)],
            ["Monospace", unicodeRange(text, 0x1D670, 0x1D68A, 0x1D7F6)],
            ["Circled", circled],
            ["Fullwidth", fullwidth],
            ["Small caps", smallCaps]
        ];
    }

    function unicodeToBase64(text) {
        const bytes = new TextEncoder().encode(text);
        let binary = "";
        bytes.forEach(byte => binary += String.fromCharCode(byte));
        return btoa(binary);
    }

    function base64ToUnicode(encoded) {
        const clean = encoded.replace(/\s+/g, "");
        const binary = atob(clean);
        const bytes = Uint8Array.from(binary, char => char.charCodeAt(0));
        return new TextDecoder("utf-8", { fatal:true }).decode(bytes);
    }

    function sortJsonKeys(valueToSort) {
        if (Array.isArray(valueToSort)) return valueToSort.map(sortJsonKeys);
        if (valueToSort && typeof valueToSort === "object") {
            return Object.keys(valueToSort).sort().reduce((output, key) => {
                output[key] = sortJsonKeys(valueToSort[key]);
                return output;
            }, {});
        }
        return valueToSort;
    }

    function escapeRegex(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    function analyzePassword(password) {
        const bar = q("#strength-bar");
        const label = q("#strength-label");
        const entropyNode = q("#strength-entropy");
        const result = q("#strength-result");
        if (!password) {
            bar.style.width = "0";
            label.textContent = "Waiting for password";
            entropyNode.textContent = "0 bits";
            result.classList.remove("is-visible");
            return;
        }

        let pool = 0;
        if (/[a-z]/.test(password)) pool += 26;
        if (/[A-Z]/.test(password)) pool += 26;
        if (/\d/.test(password)) pool += 10;
        if (/[^A-Za-z0-9]/.test(password)) pool += 33;
        if (/[^\x00-\x7F]/.test(password)) pool += 50;
        let entropy = password.length * Math.log2(Math.max(pool, 1));
        const warnings = [];
        if (password.length < 12) warnings.push("12+ characters use karein.");
        if (!/[a-z]/.test(password) || !/[A-Z]/.test(password)) warnings.push("Upper aur lowercase mix karein.");
        if (!/\d/.test(password)) warnings.push("Numbers add karein.");
        if (!/[^A-Za-z0-9]/.test(password)) warnings.push("Symbol add karein.");
        if (/(.)\1{2,}/.test(password)) { entropy -= 12; warnings.push("Repeated characters avoid karein."); }
        if (/1234|abcd|qwerty|password|admin|letmein|pakistan/i.test(password)) { entropy -= 30; warnings.push("Common word/pattern avoid karein."); }
        entropy = Math.max(0, entropy);

        const score = entropy < 35 ? 1 : entropy < 55 ? 2 : entropy < 75 ? 3 : entropy < 100 ? 4 : 5;
        const labels = ["","Very weak","Weak","Fair","Strong","Very strong"];
        const colors = ["","#ef4444","#f97316","#eab308","#22c55e","#059669"];
        bar.style.width = `${score * 20}%`;
        bar.style.background = colors[score];
        label.textContent = labels[score];
        entropyNode.textContent = `${Math.round(entropy)} bits est.`;
        result.textContent = warnings.length ? warnings.join(" ") : "Strong mix detected. Is password ko kisi aur account par reuse na karein.";
        result.classList.add("is-visible");
        result.classList.toggle("is-success", score >= 4);
    }

    function parseDateOnly(raw) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);
        if (!match) return null;
        const parts = match.slice(1).map(Number);
        const date = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        if (date.getUTCFullYear() !== parts[0] || date.getUTCMonth() !== parts[1] - 1 || date.getUTCDate() !== parts[2]) return null;
        return { year:parts[0], month:parts[1], day:parts[2], date };
    }

    function daysInMonth(year, month) {
        return new Date(Date.UTC(year, month, 0)).getUTCDate();
    }

    function addMonthsClamped(parts, monthsToAdd) {
        const absoluteMonth = parts.year * 12 + (parts.month - 1) + monthsToAdd;
        const year = Math.floor(absoluteMonth / 12);
        const monthIndex = ((absoluteMonth % 12) + 12) % 12;
        const month = monthIndex + 1;
        const day = Math.min(parts.day, daysInMonth(year, month));
        return { year, month, day, date:new Date(Date.UTC(year, month - 1, day)) };
    }

    function calendarDifference(start, end) {
        let totalMonths = (end.year - start.year) * 12 + (end.month - start.month);
        let anchor = addMonthsClamped(start, totalMonths);
        if (anchor.date > end.date) {
            totalMonths--;
            anchor = addMonthsClamped(start, totalMonths);
        }
        const years = Math.floor(totalMonths / 12);
        const months = totalMonths % 12;
        const days = Math.floor((end.date - anchor.date) / 86400000);
        return { years, months, days };
    }

    function rgbToHex(r, g, b) {
        return "#" + [r,g,b].map(component => Math.round(Math.max(0, Math.min(255, component))).toString(16).padStart(2, "0")).join("").toUpperCase();
    }

    function hexToRgb(hex) {
        let clean = hex.trim().replace(/^#/, "");
        if (/^[0-9a-f]{3}$/i.test(clean)) clean = clean.split("").map(char => char + char).join("");
        if (!/^[0-9a-f]{6}$/i.test(clean)) return null;
        const number = parseInt(clean, 16);
        return { r:(number >> 16) & 255, g:(number >> 8) & 255, b:number & 255 };
    }

    function rgbToHsl(r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h = 0, s = 0;
        const l = (max + min) / 2;
        if (max !== min) {
            const delta = max - min;
            s = l > .5 ? delta / (2 - max - min) : delta / (max + min);
            switch (max) {
                case r: h = (g - b) / delta + (g < b ? 6 : 0); break;
                case g: h = (b - r) / delta + 2; break;
                default: h = (r - g) / delta + 4;
            }
            h /= 6;
        }
        return { h:Math.round(h * 360), s:Math.round(s * 100), l:Math.round(l * 100) };
    }

    function hslToRgb(h, s, l) {
        h = ((h % 360) + 360) % 360 / 360;
        s /= 100; l /= 100;
        if (s === 0) {
            const value = Math.round(l * 255);
            return { r:value, g:value, b:value };
        }
        const hue2rgb = (p, qValue, tValue) => {
            let t = tValue;
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (qValue - p) * 6 * t;
            if (t < 1/2) return qValue;
            if (t < 2/3) return p + (qValue - p) * (2/3 - t) * 6;
            return p;
        };
        const qValue = l < .5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - qValue;
        return {
            r:Math.round(hue2rgb(p, qValue, h + 1/3) * 255),
            g:Math.round(hue2rgb(p, qValue, h) * 255),
            b:Math.round(hue2rgb(p, qValue, h - 1/3) * 255)
        };
    }

    function colorResultMarkup(rgb) {
        const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
        const hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
        return `
            <div style="height:78px;border-radius:12px;background:${hex};border:1px solid rgba(0,0,0,.08);margin-bottom:12px"></div>
            <div class="lf-checks">
                <button class="lf-color-chip" type="button" data-copy-value="${hex}"><span class="lf-swatch" style="background:${hex}"></span>${hex}</button>
                <button class="lf-color-chip" type="button" data-copy-value="rgb(${rgb.r}, ${rgb.g}, ${rgb.b})">rgb(${rgb.r}, ${rgb.g}, ${rgb.b})</button>
                <button class="lf-color-chip" type="button" data-copy-value="hsl(${hsl.h}, ${hsl.s}%, ${hsl.l}%)">hsl(${hsl.h}, ${hsl.s}%, ${hsl.l}%)</button>
            </div>`;
    }

    function convertColorFromHex() {
        const rgb = hexToRgb(value("color-hex"));
        if (!rgb) {
            showError("color-result", "Valid 3 ya 6 digit HEX color enter karein.");
            return;
        }
        q("#color-r").value = rgb.r;
        q("#color-g").value = rgb.g;
        q("#color-b").value = rgb.b;
        const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
        q("#color-hex").value = hex;
        q("#color-native").value = hex.toLowerCase();
        const box = q("#color-result");
        box.innerHTML = colorResultMarkup(rgb);
        box.classList.add("is-visible");
        box.classList.remove("is-error");
    }

    function convertColorFromRgb() {
        const rgb = { r:numberValue("color-r"), g:numberValue("color-g"), b:numberValue("color-b") };
        if (Object.values(rgb).some(item => !Number.isFinite(item) || item < 0 || item > 255)) {
            showError("color-result", "RGB values 0 se 255 ke darmiyan honi chahiye.");
            return;
        }
        const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
        q("#color-hex").value = hex;
        q("#color-native").value = hex.toLowerCase();
        const box = q("#color-result");
        box.innerHTML = colorResultMarkup(rgb);
        box.classList.add("is-visible");
        box.classList.remove("is-error");
    }

    function generatePalette() {
        const rgb = hexToRgb(value("palette-base"));
        if (!rgb) return;
        const base = rgbToHsl(rgb.r, rgb.g, rgb.b);
        const mode = value("palette-mode");
        let colors;
        if (mode === "complementary") colors = [
            [base.h, base.s, Math.max(18, base.l - 22)], [base.h, base.s, base.l],
            [base.h, Math.max(25, base.s - 22), Math.min(88, base.l + 24)],
            [base.h + 180, base.s, base.l], [base.h + 180, base.s, Math.min(78, base.l + 18)]
        ];
        else if (mode === "triadic") colors = [[base.h,base.s,base.l],[base.h+120,base.s,base.l],[base.h+240,base.s,base.l],[base.h+120,Math.max(25,base.s-25),Math.min(80,base.l+20)],[base.h+240,Math.max(25,base.s-25),Math.min(80,base.l+20)]];
        else if (mode === "monochrome") colors = [[base.h,base.s,Math.max(12,base.l-32)],[base.h,base.s,Math.max(20,base.l-16)],[base.h,base.s,base.l],[base.h,Math.max(15,base.s-18),Math.min(85,base.l+18)],[base.h,Math.max(10,base.s-35),Math.min(94,base.l+34)]];
        else if (mode === "split") colors = [[base.h,base.s,base.l],[base.h+150,base.s,base.l],[base.h+210,base.s,base.l],[base.h+150,Math.max(25,base.s-25),Math.min(80,base.l+18)],[base.h+210,Math.max(25,base.s-25),Math.min(80,base.l+18)]];
        else colors = [[base.h-60,base.s,base.l],[base.h-30,base.s,base.l],[base.h,base.s,base.l],[base.h+30,base.s,base.l],[base.h+60,base.s,base.l]];
        const output = q("#palette-result");
        output.textContent = "";
        colors.map(([h,s,l]) => hslToRgb(h,s,l)).map(color => rgbToHex(color.r,color.g,color.b)).forEach(hex => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "lf-palette-color";
            button.style.background = hex;
            button.dataset.copyValue = hex;
            const label = document.createElement("span");
            label.textContent = hex;
            button.appendChild(label);
            output.appendChild(button);
        });
    }

    function initImageStudio() {
        const input = q("#studio-file");
        const quality = q("#studio-quality");
        quality.addEventListener("input", () => q("#studio-quality-label").textContent = `${quality.value}%`);
        input.addEventListener("change", () => {
            const file = input.files[0];
            q("#studio-file-name").textContent = file ? `${file.name} • ${formatBytes(file.size)}` : "No file selected";
            hideResult("studio-result");
        });
    }

    function initImageBase64() {
        const input = q("#base64-file");
        input.addEventListener("change", () => {
            const file = input.files[0];
            q("#base64-file-name").textContent = file ? `${file.name} • ${formatBytes(file.size)}` : "No file selected";
            if (!file) {
                hideResult("image-b64-result");
                return;
            }
            if (!file.type.startsWith("image/")) {
                showError("image-b64-result", "Please image file select karein.");
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                q("#image-b64-preview").src = reader.result;
                q("#image-b64-output").value = reader.result;
                showResult("image-b64-result");
            };
            reader.onerror = () => showError("image-b64-result", "File read nahi ho saki.");
            reader.readAsDataURL(file);
        });
    }

    function initImageColorPicker() {
        const input = q("#color-file");
        const canvas = q("#color-canvas");
        const context = canvas.getContext("2d", { willReadFrequently:true });
        input.addEventListener("change", () => {
            const file = input.files[0];
            q("#color-file-name").textContent = file ? `${file.name} • ${formatBytes(file.size)}` : "No file selected";
            if (!file || !file.type.startsWith("image/")) return;
            const objectUrl = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                const maxDisplay = 900;
                const scale = Math.min(1, maxDisplay / img.naturalWidth);
                canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
                context.drawImage(img, 0, 0, canvas.width, canvas.height);
                q("#color-canvas-wrap").style.display = "block";
                URL.revokeObjectURL(objectUrl);
            };
            img.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                toast("Image load nahi ho saki.");
            };
            img.src = objectUrl;
        });
        canvas.addEventListener("click", event => {
            const rect = canvas.getBoundingClientRect();
            const x = Math.min(canvas.width - 1, Math.max(0, Math.floor((event.clientX - rect.left) * canvas.width / rect.width)));
            const y = Math.min(canvas.height - 1, Math.max(0, Math.floor((event.clientY - rect.top) * canvas.height / rect.height)));
            const [r,g,b] = context.getImageData(x,y,1,1).data;
            const hex = rgbToHex(r,g,b);
            q("#picked-swatch").style.background = hex;
            q("#picked-hex").querySelector("span:last-child").textContent = hex;
            q("#picked-rgb").textContent = `rgb(${r}, ${g}, ${b})`;
        });
    }

    function formatStopwatch(ms) {
        const total = Math.max(0, ms);
        const minutes = Math.floor(total / 60000);
        const seconds = Math.floor((total % 60000) / 1000);
        const millis = Math.floor(total % 1000);
        return `${String(minutes).padStart(2,"0")}:${String(seconds).padStart(2,"0")}.${String(millis).padStart(3,"0")}`;
    }

    function initStopwatch() {
        stopwatchState = { running:false, startTime:0, elapsed:0, timer:null, laps:[] };
    }

    function stopwatchTick(now) {
        if (!stopwatchState?.running) return;
        const current = stopwatchState.elapsed + (now - stopwatchState.startTime);
        q("#stopwatch-display").textContent = formatStopwatch(current);
        stopwatchState.timer = requestAnimationFrame(stopwatchTick);
    }

    async function runAction(action) {
        switch (action) {
            case "yt-thumbnail": {
                const videoId = parseYouTubeId(value("yt-url"));
                if (!videoId) {
                    showError("yt-result", "Valid YouTube URL ya 11-character video ID enter karein.");
                    return;
                }
                const result = showResult("yt-result", "Available thumbnail quality check ho rahi hai...");
                const qualities = [
                    ["maxresdefault","Maximum HD"],["sddefault","Standard"],["hqdefault","High"],
                    ["mqdefault","Medium"],["default","Default"]
                ];
                const checked = await Promise.all(qualities.map(async ([file,label]) => {
                    const found = await imageExists(`https://img.youtube.com/vi/${videoId}/${file}.jpg`);
                    return found ? { ...found, file, label } : null;
                }));
                if (activeTool !== "yt-thumb") return;
                const available = checked.filter(Boolean);
                if (!available.length) {
                    showError("yt-result", "Thumbnail nahi mili. Video private, deleted ya ID invalid ho sakti hai.");
                    return;
                }
                const best = available[0];
                result.textContent = "";
                const preview = document.createElement("img");
                preview.className = "lf-preview-img";
                preview.src = best.url;
                preview.alt = "YouTube thumbnail preview";
                const note = document.createElement("p");
                note.style.margin = "10px 0 0";
                note.textContent = `Best available: ${best.label} • ${best.width}×${best.height}`;
                const list = document.createElement("div");
                list.className = "lf-list";
                list.style.marginTop = "12px";
                available.forEach(item => {
                    const row = document.createElement("div");
                    row.className = "lf-list-item";
                    const text = document.createElement("div");
                    text.className = "lf-list-value";
                    text.textContent = `${item.label} — ${item.width}×${item.height}`;
                    const button = document.createElement("button");
                    button.className = "lf-btn lf-btn-secondary lf-btn-small";
                    button.type = "button";
                    button.textContent = "Download";
                    button.dataset.downloadUrl = item.url;
                    button.dataset.filename = `youtube-${videoId}-${item.file}.jpg`;
                    row.append(text, button);
                    list.appendChild(row);
                });
                result.append(preview, note, list);
                break;
            }

            case "wa-link": {
                let phone = value("wa-number").replace(/[^\d]/g, "");
                if (phone.startsWith("00")) phone = phone.slice(2);
                if (phone.length < 8 || phone.length > 15) {
                    showError("wa-result", "Country code ke saath 8–15 digit valid number enter karein.");
                    return;
                }
                const message = value("wa-message").trim();
                const url = `https://wa.me/${phone}${message ? `?text=${encodeURIComponent(message)}` : ""}`;
                makeLinkResult("wa-result", url, "Open WhatsApp");
                break;
            }

            case "hashtags": {
                const category = value("hash-category");
                const count = Math.max(5, Math.min(30, Number(value("hash-count")) || 20));
                const keyword = value("hash-keyword").trim();
                const keywordWords = keyword.split(/\s+/).map(normalizeTag).filter(Boolean);
                const custom = [];
                keywordWords.forEach(word => {
                    if (word.length > 1) custom.push(word);
                });
                if (keywordWords.length > 1) custom.unshift(normalizeTag(keywordWords.join("")));
                const bank = hashtagBank[category] || [];
                const style = value("hash-style");
                let selected;
                if (style === "broad") selected = [...bank.slice(0, 15), ...shuffle(bank.slice(15)), ...custom];
                else if (style === "niche") selected = [...custom, ...shuffle(bank.slice(14)), ...bank.slice(0, 14)];
                else selected = [...custom, ...shuffle(bank.slice(0, 12)), ...shuffle(bank.slice(12))];
                selected = [...new Set(selected.filter(Boolean))].slice(0, count);
                q("#hash-output").value = selected.map(tag => "#" + tag).join(" ");
                showResult("hash-result");
                break;
            }

            case "bio": {
                const name = value("bio-name").trim() || "Your Name";
                const niche = value("bio-niche").trim() || "Digital Creator";
                const cta = value("bio-cta").trim() || "DM for collaborations";
                const tone = value("bio-tone");
                const sets = {
                    professional: [
                        `${name}\n💼 ${niche}\nHelping ideas grow into results\n📩 ${cta}`,
                        `${niche} | ${name}\nStrategy • Growth • Results\nBased in Pakistan 🇵🇰\n↓ ${cta}`,
                        `${name} — ${niche}\nBuilding value, one project at a time.\n✉️ ${cta}`
                    ],
                    cool: [
                        `${name} ⚡\n${niche} by day, dreamer by night\nNo limits. Just levels.\n👇 ${cta}`,
                        `Not everyone gets the vibe ✦\n${name} | ${niche}\nCreating my own lane\n${cta}`,
                        `${name} 😎\nToo focused to compete\n${niche} • Good energy only\n↘ ${cta}`
                    ],
                    creator: [
                        `${name} 🎬\nCreating around ${niche}\nNew ideas • real stories • useful content\n📩 ${cta}`,
                        `Content by ${name} ✨\n${niche} made simple\nFollow for the next upload\n↓ ${cta}`,
                        `${name} | Creator\nSharing my ${niche} journey\nLearn • create • repeat\n${cta}`
                    ],
                    minimal: [
                        `${name}\n${niche}\n${cta}`,
                        `${niche} by ${name}.\nPakistan 🇵🇰`,
                        `${name} — creating with purpose.\n${cta}`
                    ],
                    funny: [
                        `${name} 😅\nProfessional overthinker\nSometimes I do ${niche}\n${cta} (I might reply)`,
                        `${niche}, memes & chai ☕\nRun by ${name}\nCurrently pretending to be productive`,
                        `${name}\n99% loading… 1% ${niche}\nSend snacks, then ${cta}`
                    ],
                    motivational: [
                        `${name} ✨\n${niche} with purpose\nSmall steps. Big vision.\n${cta}`,
                        `Becoming better every day.\n${name} | ${niche}\nDiscipline over mood 💜\n${cta}`,
                        `${name}\nDream it • plan it • do it\n${niche}\n↓ ${cta}`
                    ]
                };
                const list = q("#bio-list");
                createCopyList(list, sets[tone]);
                showResult("bio-result");
                break;
            }

            case "caption": {
                const topic = value("caption-topic").trim();
                if (!topic) {
                    showError("caption-result", "Post topic enter karein.");
                    return;
                }
                const tone = value("caption-tone");
                const platform = value("caption-platform");
                const emojiStyle = value("caption-emoji");
                const emoji = emojiStyle === "none" ? "" : emojiStyle === "more" ? " ✨🔥💜" : " ✨";
                const cleanTopic = topic.replace(/[.!?]+$/, "");
                const ideas = {
                    engaging: [
                        `${cleanTopic}${emoji}\nWhat do you think—would you try this? Tell me below 👇`,
                        `A little moment from ${cleanTopic}${emoji}\nSave this for later and share it with someone who needs it.`,
                        `${cleanTopic}, but make it unforgettable${emoji}\nWhich part is your favorite?`
                    ],
                    professional: [
                        `${cleanTopic}: a practical look at what matters, what works, and what to do next.\n\nWhat would you add to the list?`,
                        `Today’s focus: ${cleanTopic}.\nClear strategy, consistent action, measurable progress.`,
                        `${cleanTopic} is not about shortcuts—it is about making the right move consistently.\n\nSave this insight for later.`
                    ],
                    funny: [
                        `Me: I’ll keep it simple.\nAlso me: turns ${cleanTopic} into a full production${emoji}`,
                        `${cleanTopic} was a good idea… until reality joined the chat 😂`,
                        `POV: You said “just one quick post” and now it’s about ${cleanTopic}${emoji}`
                    ],
                    inspiring: [
                        `${cleanTopic}${emoji}\nA reminder that progress can be quiet and still be powerful.`,
                        `Start where you are. Use what you have. Keep moving toward ${cleanTopic}${emoji}`,
                        `${cleanTopic} taught me this: consistency will take you places motivation cannot.`
                    ],
                    minimal: [
                        `${cleanTopic}.${emoji}`,
                        `Current mood: ${cleanTopic}${emoji}`,
                        `${cleanTopic} — worth remembering.`
                    ],
                    story: [
                        `It started with one simple idea: ${cleanTopic}.\nI did not have every answer, but I took the first step—and that changed everything.${emoji}`,
                        `Behind this post is a story about ${cleanTopic}.\nThe result looks simple; the journey was anything but.`,
                        `A few days ago, I almost gave up on ${cleanTopic}.\nToday, I’m glad I kept going.${emoji}`
                    ]
                };
                const withPlatform = ideas[tone].map(item => platform === "LinkedIn" ? item.replace(/[👇🔥💜]/g, "") : item);
                createCopyList(q("#caption-list"), withPlatform);
                showResult("caption-result");
                break;
            }

            case "engagement": {
                const followers = numberValue("eng-followers");
                const likes = Math.max(0, numberValue("eng-likes") || 0);
                const comments = Math.max(0, numberValue("eng-comments") || 0);
                const saves = Math.max(0, numberValue("eng-saves") || 0);
                const shares = Math.max(0, numberValue("eng-shares") || 0);
                if (!Number.isFinite(followers) || followers <= 0) {
                    showError("eng-result", "Followers ya reach 0 se zyada enter karein.");
                    return;
                }
                const interactions = likes + comments + saves + shares;
                const rate = interactions / followers * 100;
                const box = showResult("eng-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${formatNumber(rate, 3)}%</strong><span>Engagement</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(interactions, 0)}</strong><span>Interactions</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(followers, 0)}</strong><span>Base</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(interactions / Math.max(1, followers) * 1000, 2)}</strong><span>Per 1K</span></div>
                </div>`;
                break;
            }

            case "yt-timestamp": {
                const rawUrl = value("time-url").trim();
                const seconds = parseTimeToSeconds(value("time-value"));
                if (!parseYouTubeId(rawUrl) || !Number.isFinite(seconds) || seconds < 0) {
                    showError("time-result", "Valid YouTube URL aur time (e.g. 1:25) enter karein.");
                    return;
                }
                try {
                    const url = new URL(rawUrl);
                    url.searchParams.set("t", `${Math.floor(seconds)}s`);
                    makeLinkResult("time-result", url.toString(), "Open video");
                } catch (_) {
                    showError("time-result", "Valid full YouTube URL enter karein.");
                }
                break;
            }

            case "username": {
                const rawName = normalizeTag(value("user-name"));
                const rawKeyword = normalizeTag(value("user-keyword"));
                if (!rawName) {
                    showError("user-result", "Name ya brand enter karein.");
                    return;
                }
                const style = value("user-style");
                const suffixes = style === "pro" ? ["official","hq","studio","media","digital","co","pro","works"]
                    : style === "fun" ? ["vibes","verse","zone","daily","world","xo","hub","magic"]
                    : style === "short" ? ["x","io","pk","hq","go","up","lab","one"]
                    : ["online","real","the","its","iam","daily","pk","official"];
                const numbers = [secureRandomInt(90) + 10, new Date().getFullYear().toString().slice(-2), secureRandomInt(900) + 100];
                const ideas = [
                    rawName, `the${rawName}`, `${rawName}${rawKeyword}`, `${rawName}.${rawKeyword}`,
                    `${rawName}_${rawKeyword}`, `${rawKeyword}by${rawName}`, `${rawName}${suffixes[secureRandomInt(suffixes.length)]}`,
                    `${rawName}.${suffixes[secureRandomInt(suffixes.length)]}`, `${rawName}_${suffixes[secureRandomInt(suffixes.length)]}`,
                    `${rawName}${numbers[0]}`, `${rawKeyword || "its"}${rawName}${numbers[1]}`, `${rawName}${numbers[2]}`
                ].filter(item => item && item.length >= 3).map(item => item.replace(/^[._]+|[._]+$/g, "").slice(0, 30));
                createCopyList(q("#user-list"), [...new Set(ideas)]);
                showResult("user-result");
                break;
            }

            case "fancy-font": {
                const text = value("font-input");
                if (!text) {
                    showError("font-result", "Kuch text type karein.");
                    return;
                }
                const list = q("#font-list");
                list.textContent = "";
                fancyStyles(text).forEach(([name, output]) => {
                    const row = document.createElement("div");
                    row.className = "lf-list-item";
                    const content = document.createElement("div");
                    content.className = "lf-list-value";
                    const label = document.createElement("strong");
                    label.textContent = name + ": ";
                    const styled = document.createElement("span");
                    styled.style.fontSize = "1.05rem";
                    styled.textContent = output;
                    content.append(label, styled);
                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = "lf-btn lf-btn-secondary lf-btn-small";
                    button.textContent = "Copy";
                    button.dataset.copyValue = output;
                    row.append(content, button);
                    list.appendChild(row);
                });
                showResult("font-result");
                break;
            }

            case "case-upper": q("#case-input").value = value("case-input").toLocaleUpperCase(); break;
            case "case-lower": q("#case-input").value = value("case-input").toLocaleLowerCase(); break;
            case "case-title": q("#case-input").value = toTitleCase(value("case-input")); break;
            case "case-sentence": q("#case-input").value = toSentenceCase(value("case-input")); break;
            case "case-camel": {
                const words = asciiWords(value("case-input"));
                q("#case-input").value = words.map((word,index) => index ? word.charAt(0).toUpperCase() + word.slice(1).toLowerCase() : word.toLowerCase()).join("");
                break;
            }
            case "case-snake": q("#case-input").value = asciiWords(value("case-input")).map(word => word.toLowerCase()).join("_"); break;
            case "case-kebab": q("#case-input").value = asciiWords(value("case-input")).map(word => word.toLowerCase()).join("-"); break;
            case "case-invert": q("#case-input").value = Array.from(value("case-input")).map(char => char === char.toUpperCase() ? char.toLowerCase() : char.toUpperCase()).join(""); break;

            case "line-dedupe": {
                const seen = new Set();
                q("#line-input").value = value("line-input").split(/\r?\n/).filter(line => {
                    const key = line.trim().toLocaleLowerCase();
                    if (!key || seen.has(key)) return false;
                    seen.add(key); return true;
                }).join("\n");
                q("#line-input").dispatchEvent(new Event("input"));
                break;
            }
            case "line-blank": q("#line-input").value = value("line-input").split(/\r?\n/).filter(line => line.trim()).join("\n"); q("#line-input").dispatchEvent(new Event("input")); break;
            case "line-sort": q("#line-input").value = value("line-input").split(/\r?\n/).sort((a,b) => a.localeCompare(b, undefined, { sensitivity:"base", numeric:true })).join("\n"); break;
            case "line-sort-desc": q("#line-input").value = value("line-input").split(/\r?\n/).sort((a,b) => b.localeCompare(a, undefined, { sensitivity:"base", numeric:true })).join("\n"); break;
            case "line-reverse": q("#line-input").value = value("line-input").split(/\r?\n/).reverse().join("\n"); break;
            case "line-shuffle": q("#line-input").value = shuffle(value("line-input").split(/\r?\n/)).join("\n"); break;

            case "text-clean": {
                let text = value("clean-input").replace(/\r\n?/g, "\n");
                if (q("#clean-invisible").checked) text = text.replace(/[\u200B-\u200D\u2060\uFEFF]/g, "");
                if (q("#clean-trim").checked) text = text.split("\n").map(line => line.trim()).join("\n");
                if (q("#clean-spaces").checked) text = text.replace(/[ \t]+/g, " ");
                if (q("#clean-lines").checked) text = text.replace(/\n{3,}/g, "\n\n");
                q("#clean-input").value = text.trim();
                toast("Text cleaned!");
                break;
            }

            case "find-replace": {
                const find = value("find-value");
                const replacement = value("replace-value");
                const source = value("replace-input");
                if (!find) {
                    showError("replace-result", "Find field empty nahi ho sakti.");
                    return;
                }
                const flags = q("#find-first").checked ? (q("#find-case").checked ? "" : "i") : (q("#find-case").checked ? "g" : "gi");
                const regex = new RegExp(escapeRegex(find), flags);
                const matches = source.match(new RegExp(escapeRegex(find), q("#find-case").checked ? "g" : "gi")) || [];
                q("#replace-input").value = source.replace(regex, () => replacement);
                showResult("replace-result", `${matches.length} match${matches.length === 1 ? "" : "es"} found; ${q("#find-first").checked ? Math.min(1,matches.length) : matches.length} replaced.`, "success");
                break;
            }

            case "slug": {
                let output = slugify(value("slug-input"), value("slug-separator"));
                const max = numberValue("slug-max");
                if (Number.isFinite(max) && max > 0 && output.length > max) {
                    output = output.slice(0, max).replace(/[-_]+[^-_]*$/, "");
                }
                if (!output) {
                    showError("slug-result", "Valid title/text enter karein.");
                    return;
                }
                makeLinkResult("slug-result", output, "Copy-ready slug");
                const open = q("#slug-result a");
                if (open) open.remove();
                break;
            }

            case "lorem": {
                const type = value("lorem-type");
                const limit = type === "words" ? 500 : type === "sentences" ? 50 : 20;
                const count = Math.max(1, Math.min(limit, Number(value("lorem-count")) || 1));
                const makeSentence = (length = 14) => {
                    const words = Array.from({ length }, (_, index) => loremWords[(index + secureRandomInt(loremWords.length)) % loremWords.length]);
                    const sentence = words.join(" ");
                    return sentence.charAt(0).toUpperCase() + sentence.slice(1) + ".";
                };
                let output = "";
                if (type === "words") output = Array.from({ length:count }, (_,index) => loremWords[index % loremWords.length]).join(" ");
                else if (type === "sentences") output = Array.from({ length:count }, () => makeSentence(10 + secureRandomInt(10))).join(" ");
                else output = Array.from({ length:count }, () => Array.from({ length:4 + secureRandomInt(3) }, () => makeSentence(10 + secureRandomInt(10))).join(" ")).join("\n\n");
                q("#lorem-output").value = output;
                showResult("lorem-result");
                break;
            }

            case "download-lorem": {
                const text = value("lorem-output");
                if (text) downloadTextFile(text, "lorem-ipsum.txt");
                break;
            }

            case "extract": {
                const source = value("extract-input");
                const type = value("extract-type");
                const patterns = {
                    emails:/[\w.!#$%&'*+/=?^`{|}~-]+@[\w-]+(?:\.[\w-]+)+/g,
                    urls:/https?:\/\/[^\s<>"']+/gi,
                    hashtags:/(?:^|\s)#[\p{L}\p{N}_]+/gu,
                    mentions:/(?:^|\s)@[\p{L}\p{N}_.]+/gu,
                    numbers:/[-+]?(?:\d[\d,]*\.?\d*|\.\d+)/g
                };
                const matches = (source.match(patterns[type]) || []).map(item => item.trim().replace(/[.,;:!?]+$/, ""));
                const unique = [...new Set(matches)];
                q("#extract-output").value = unique.join("\n");
                showResult("extract-result");
                const box = q("#extract-result");
                const prior = box.querySelector(".lf-extract-count");
                if (prior) prior.remove();
                const count = document.createElement("p");
                count.className = "lf-extract-count";
                count.style.margin = "0";
                count.textContent = `${unique.length} unique result${unique.length === 1 ? "" : "s"} found.`;
                box.prepend(count);
                break;
            }

            case "ip-info": {
                const result = showResult("ip-result", "Public IP information load ho rahi hai...");
                const browserInfo = {
                    "Browser language": navigator.language || "Unknown",
                    "Platform": navigator.userAgentData?.platform || navigator.platform || "Unknown",
                    "Screen": `${screen.width} × ${screen.height}`,
                    "Viewport": `${window.innerWidth} × ${window.innerHeight}`,
                    "Time zone": Intl.DateTimeFormat().resolvedOptions().timeZone || "Unknown",
                    "Online": navigator.onLine ? "Yes" : "No"
                };
                try {
                    const response = await fetch("https://ipapi.co/json/", { headers:{ "Accept":"application/json" } });
                    if (!response.ok) throw new Error("IP service unavailable");
                    const data = await response.json();
                    if (activeTool !== "ip-info") return;
                    const rows = {
                        "Public IP": data.ip || "Unknown",
                        "Approx. city": [data.city, data.region, data.country_name].filter(Boolean).join(", ") || "Unknown",
                        "Network / ISP": data.org || "Unknown",
                        ...browserInfo
                    };
                    result.textContent = "";
                    const list = document.createElement("div");
                    list.className = "lf-list";
                    Object.entries(rows).forEach(([label,text]) => {
                        const row = document.createElement("div");
                        row.className = "lf-list-item";
                        const key = document.createElement("strong");
                        key.style.minWidth = "115px";
                        key.textContent = label;
                        const val = document.createElement("span");
                        val.className = "lf-list-value";
                        val.textContent = text;
                        row.append(key,val);
                        list.appendChild(row);
                    });
                    result.appendChild(list);
                } catch (_) {
                    result.textContent = "";
                    result.classList.remove("is-error");
                    const title = document.createElement("p");
                    title.style.margin = "0 0 10px";
                    title.textContent = "Public IP service respond nahi kar rahi; local browser info available hai:";
                    const list = document.createElement("div");
                    list.className = "lf-list";
                    Object.entries(browserInfo).forEach(([label,text]) => {
                        const row = document.createElement("div");
                        row.className = "lf-list-item";
                        row.innerHTML = `<strong style="min-width:115px">${esc(label)}</strong><span class="lf-list-value">${esc(text)}</span>`;
                        list.appendChild(row);
                    });
                    result.append(title,list);
                }
                break;
            }

            case "password-gen": {
                const mode = value("pass-mode");
                const amount = Number(value("pass-length"));
                let output = "";
                if (mode === "phrase") {
                    const count = Math.max(3, Math.min(12, Number.isFinite(amount) ? Math.round(amount) : 5));
                    const words = Array.from({ length:count }, () => passphraseWords[secureRandomInt(passphraseWords.length)]);
                    words[0] = words[0].charAt(0).toUpperCase() + words[0].slice(1);
                    output = words.join("-") + String(secureRandomInt(90) + 10) + "!";
                } else {
                    const length = Math.max(4, Math.min(128, Number.isFinite(amount) ? Math.round(amount) : 20));
                    const groups = [];
                    if (q("#pass-lower").checked) groups.push("abcdefghijkmnopqrstuvwxyz");
                    if (q("#pass-upper").checked) groups.push("ABCDEFGHJKLMNPQRSTUVWXYZ");
                    if (q("#pass-number").checked) groups.push("23456789");
                    if (q("#pass-symbol").checked) groups.push("!@#$%^&*()-_=+[]{};:,.?");
                    if (!groups.length) {
                        showError("pass-result", "Kam az kam ek character group select karein.");
                        return;
                    }
                    if (!q("#pass-ambiguous").checked) {
                        if (q("#pass-lower").checked) groups[groups.indexOf("abcdefghijkmnopqrstuvwxyz")] = "abcdefghijklmnopqrstuvwxyz";
                        if (q("#pass-upper").checked) groups[groups.indexOf("ABCDEFGHJKLMNPQRSTUVWXYZ")] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                        if (q("#pass-number").checked) groups[groups.indexOf("23456789")] = "0123456789";
                    }
                    const pool = groups.join("");
                    const chars = groups.slice(0, Math.min(groups.length, length)).map(group => group[secureRandomInt(group.length)]);
                    while (chars.length < length) chars.push(pool[secureRandomInt(pool.length)]);
                    output = shuffle(chars).join("");
                }
                const box = showResult("pass-result");
                box.textContent = "";
                const row = document.createElement("div");
                row.className = "lf-output-row";
                const input = document.createElement("input");
                input.className = "lf-input";
                input.readOnly = true;
                input.value = output;
                input.style.fontFamily = "ui-monospace,monospace";
                const copy = document.createElement("button");
                copy.type = "button";
                copy.className = "lf-btn lf-btn-secondary lf-btn-small";
                copy.textContent = "Copy";
                copy.dataset.copyValue = output;
                row.append(input,copy);
                const note = document.createElement("p");
                note.style.margin = "10px 0 0";
                note.textContent = mode === "phrase" ? `${output.split("-").length} word passphrase generated.` : `${output.length} character password generated.`;
                box.append(row,note);
                break;
            }

            case "qr": {
                const text = value("qr-text").trim();
                if (!text) {
                    showError("qr-result", "QR ke liye text ya URL enter karein.");
                    return;
                }
                if (text.length > 2000) {
                    showError("qr-result", "QR content ko 2000 characters se kam rakhein.");
                    return;
                }
                const size = value("qr-size");
                const margin = value("qr-margin");
                const url = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=${margin}&format=png&data=${encodeURIComponent(text)}`;
                const box = showResult("qr-result");
                box.textContent = "";
                const img = document.createElement("img");
                img.src = url;
                img.alt = "Generated QR code";
                img.style.display = "block";
                img.style.width = `min(${size}px, 100%)`;
                img.style.margin = "0 auto";
                img.style.borderRadius = "10px";
                const actions = document.createElement("div");
                actions.className = "lf-actions";
                actions.style.justifyContent = "center";
                const download = document.createElement("button");
                download.className = "lf-btn lf-btn-success";
                download.type = "button";
                download.textContent = "Download PNG";
                download.dataset.downloadUrl = url;
                download.dataset.filename = "qr-code.png";
                actions.appendChild(download);
                box.append(img,actions);
                break;
            }

            case "url-encode-component": {
                q("#url-input").value = encodeURIComponent(value("url-input"));
                showResult("url-result", "Component encoded successfully.", "success");
                break;
            }
            case "url-encode": {
                q("#url-input").value = encodeURI(value("url-input"));
                showResult("url-result", "Full URI encoded successfully.", "success");
                break;
            }
            case "url-decode": {
                try {
                    q("#url-input").value = decodeURIComponent(value("url-input").replace(/\+/g, "%20"));
                    showResult("url-result", "Decoded successfully.", "success");
                } catch (_) {
                    showError("url-result", "Invalid percent-encoded input. Incomplete % sequence check karein.");
                }
                break;
            }

            case "base64-encode": {
                try {
                    q("#b64-input").value = unicodeToBase64(value("b64-input"));
                    showResult("b64-result", "Unicode text Base64 mein encode ho gaya.", "success");
                } catch (_) {
                    showError("b64-result", "Text encode nahi ho saka.");
                }
                break;
            }
            case "base64-decode": {
                try {
                    q("#b64-input").value = base64ToUnicode(value("b64-input"));
                    showResult("b64-result", "Base64 Unicode text mein decode ho gaya.", "success");
                } catch (_) {
                    showError("b64-result", "Invalid Base64 ya non-UTF-8 data.");
                }
                break;
            }

            case "json-format":
            case "json-minify":
            case "json-sort":
            case "json-validate": {
                try {
                    const parsed = JSON.parse(value("json-input"));
                    if (action === "json-format") q("#json-input").value = JSON.stringify(parsed, null, 2);
                    if (action === "json-minify") q("#json-input").value = JSON.stringify(parsed);
                    if (action === "json-sort") q("#json-input").value = JSON.stringify(sortJsonKeys(parsed), null, 2);
                    showResult("json-result", `Valid JSON ✓${action === "json-validate" ? "" : " Output updated."}`, "success");
                } catch (error) {
                    showError("json-result", `Invalid JSON: ${error.message}`);
                }
                break;
            }

            case "html-encode": {
                q("#html-input").value = value("html-input").replace(/[&<>"']/g, char => ({
                    "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
                })[char]);
                break;
            }
            case "html-decode": {
                const textarea = document.createElement("textarea");
                textarea.innerHTML = value("html-input");
                q("#html-input").value = textarea.value;
                break;
            }

            case "utm": {
                const raw = value("utm-url").trim();
                const source = value("utm-source").trim();
                const medium = value("utm-medium").trim();
                const campaign = value("utm-campaign").trim();
                if (!raw || !source || !medium || !campaign) {
                    showError("utm-result", "URL, source, medium aur campaign required hain.");
                    return;
                }
                try {
                    const url = new URL(raw);
                    if (!/^https?:$/.test(url.protocol)) throw new Error();
                    const params = {
                        utm_source:source, utm_medium:medium, utm_campaign:campaign,
                        utm_term:value("utm-term").trim(), utm_content:value("utm-content").trim()
                    };
                    Object.entries(params).forEach(([key,text]) => text ? url.searchParams.set(key,text) : url.searchParams.delete(key));
                    makeLinkResult("utm-result", url.toString(), "Open campaign URL");
                } catch (_) {
                    showError("utm-result", "http:// ya https:// ke saath valid website URL enter karein.");
                }
                break;
            }

            case "meta-tags": {
                const title = value("meta-title").trim();
                const description = value("meta-desc").trim();
                if (!title || !description) {
                    showError("meta-result", "Page title aur description required hain.");
                    return;
                }
                const url = value("meta-url").trim();
                const image = value("meta-image").trim();
                const site = value("meta-site").trim();
                const type = value("meta-type");
                const lines = [
                    `<title>${esc(title)}</title>`,
                    `<meta name="description" content="${esc(description)}">`,
                    `<meta name="robots" content="index, follow">`,
                    url ? `<link rel="canonical" href="${esc(url)}">` : "",
                    "",
                    `<meta property="og:type" content="${esc(type)}">`,
                    `<meta property="og:title" content="${esc(title)}">`,
                    `<meta property="og:description" content="${esc(description)}">`,
                    url ? `<meta property="og:url" content="${esc(url)}">` : "",
                    site ? `<meta property="og:site_name" content="${esc(site)}">` : "",
                    image ? `<meta property="og:image" content="${esc(image)}">` : "",
                    "",
                    `<meta name="twitter:card" content="${image ? "summary_large_image" : "summary"}">`,
                    `<meta name="twitter:title" content="${esc(title)}">`,
                    `<meta name="twitter:description" content="${esc(description)}">`,
                    image ? `<meta name="twitter:image" content="${esc(image)}">` : ""
                ].filter((line,index,array) => line || (array[index - 1] && array[index + 1])).join("\n");
                q("#meta-output").value = lines;
                showResult("meta-result");
                break;
            }

            case "regex": {
                const patternText = value("regex-pattern");
                let flags = value("regex-flags").replace(/[^dgimsuvy]/g, "");
                flags = [...new Set(flags)].join("");
                if (!patternText) {
                    showError("regex-result", "Regex pattern enter karein.");
                    return;
                }
                try {
                    const regex = new RegExp(patternText, flags.includes("g") ? flags : flags + "g");
                    const source = value("regex-input");
                    const matches = [];
                    let match;
                    let guard = 0;
                    while ((match = regex.exec(source)) !== null && guard < 500) {
                        matches.push({ text:match[0], index:match.index, groups:match.slice(1) });
                        if (match[0] === "") regex.lastIndex++;
                        guard++;
                    }
                    const box = showResult("regex-result");
                    box.textContent = "";
                    const summary = document.createElement("p");
                    summary.style.margin = "0 0 10px";
                    summary.textContent = `${matches.length}${guard >= 500 ? "+" : ""} match${matches.length === 1 ? "" : "es"} found.`;
                    box.appendChild(summary);
                    if (matches.length) {
                        const list = document.createElement("div");
                        list.className = "lf-list";
                        matches.slice(0,50).forEach((item,index) => {
                            const row = document.createElement("div");
                            row.className = "lf-list-item";
                            const node = document.createElement("div");
                            node.className = "lf-list-value";
                            node.textContent = `${index + 1}. “${item.text || "(empty match)"}” at index ${item.index}${item.groups.length ? ` • groups: ${item.groups.map(group => group ?? "undefined").join(" | ")}` : ""}`;
                            row.appendChild(node);
                            list.appendChild(row);
                        });
                        box.appendChild(list);
                    }
                } catch (error) {
                    showError("regex-result", `Regex error: ${error.message}`);
                }
                break;
            }

            case "timestamp-to-date": {
                const raw = value("timestamp-input").trim();
                if (!/^-?\d+(?:\.\d+)?$/.test(raw)) {
                    showError("timestamp-result", "Valid Unix timestamp enter karein.");
                    return;
                }
                let numeric = Number(raw);
                if (Math.abs(numeric) < 100000000000) numeric *= 1000;
                const date = new Date(numeric);
                if (Number.isNaN(date.getTime())) {
                    showError("timestamp-result", "Timestamp valid date range mein nahi.");
                    return;
                }
                const box = showResult("timestamp-result");
                box.innerHTML = `<strong>Local:</strong> ${esc(date.toLocaleString())}<br><strong>UTC:</strong> ${esc(date.toUTCString())}<br><strong>ISO:</strong> ${esc(date.toISOString())}<br><strong>Milliseconds:</strong> ${date.getTime()}`;
                break;
            }

            case "date-to-timestamp": {
                const raw = value("datetime-input");
                if (!raw) {
                    showError("timestamp-result", "Date aur time select karein.");
                    return;
                }
                const date = new Date(raw);
                if (Number.isNaN(date.getTime())) {
                    showError("timestamp-result", "Invalid date/time.");
                    return;
                }
                const seconds = Math.floor(date.getTime() / 1000);
                const box = showResult("timestamp-result");
                box.innerHTML = `<strong>Unix seconds:</strong> ${seconds}<br><strong>Unix milliseconds:</strong> ${date.getTime()}<br><strong>UTC:</strong> ${esc(date.toUTCString())}`;
                break;
            }

            case "age": {
                const birth = parseDateOnly(value("age-dob"));
                const onDate = parseDateOnly(value("age-on"));
                if (!birth || !onDate || birth.date > onDate.date) {
                    showError("age-result", "Valid date of birth aur uske baad ki calculation date select karein.");
                    return;
                }
                const diff = calendarDifference(birth, onDate);
                const totalDays = Math.floor((onDate.date - birth.date) / 86400000);
                const totalMonths = diff.years * 12 + diff.months;
                const birthdayDay = Math.min(birth.day, daysInMonth(onDate.year, birth.month));
                let nextBirthday = new Date(Date.UTC(onDate.year, birth.month - 1, birthdayDay));
                if (nextBirthday <= onDate.date) {
                    const nextYear = onDate.year + 1;
                    nextBirthday = new Date(Date.UTC(nextYear, birth.month - 1, Math.min(birth.day, daysInMonth(nextYear, birth.month))));
                }
                const nextDays = Math.ceil((nextBirthday - onDate.date) / 86400000);
                const box = showResult("age-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${diff.years}</strong><span>Years</span></div>
                    <div class="lf-mini-stat"><strong>${diff.months}</strong><span>Months</span></div>
                    <div class="lf-mini-stat"><strong>${diff.days}</strong><span>Days</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(totalDays,0)}</strong><span>Total days</span></div>
                </div>
                <p style="margin:13px 0 0"><strong>Exact age:</strong> ${diff.years} years, ${diff.months} months, ${diff.days} days<br>
                <strong>Total months:</strong> ${formatNumber(totalMonths,0)} complete months<br>
                <strong>Total weeks:</strong> ${formatNumber(Math.floor(totalDays / 7),0)} weeks + ${totalDays % 7} days<br>
                <strong>Next birthday:</strong> ${nextDays} day${nextDays === 1 ? "" : "s"} remaining (${nextBirthday.toLocaleDateString(undefined,{timeZone:"UTC",year:"numeric",month:"long",day:"numeric"})})</p>`;
                break;
            }

            case "discount": {
                const price = numberValue("disc-price");
                const first = numberValue("disc-one");
                const secondRaw = value("disc-two").trim();
                const second = secondRaw === "" ? 0 : Number(secondRaw);
                if (![price,first,second].every(Number.isFinite) || price < 0 || first < 0 || first > 100 || second < 0 || second > 100) {
                    showError("disc-result", "Price 0+ aur discount 0–100% ke darmiyan enter karein.");
                    return;
                }
                const afterFirst = price * (1 - first / 100);
                const finalPrice = afterFirst * (1 - second / 100);
                const saved = price - finalPrice;
                const effective = price === 0 ? 0 : saved / price * 100;
                const box = showResult("disc-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${currency(finalPrice)}</strong><span>Final price</span></div>
                    <div class="lf-mini-stat"><strong>${currency(saved)}</strong><span>You save</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(effective,2)}%</strong><span>Effective off</span></div>
                    <div class="lf-mini-stat"><strong>${currency(afterFirst)}</strong><span>After first</span></div>
                </div>`;
                break;
            }

            case "cpm": {
                const cost = numberValue("cpm-cost");
                const impressions = numberValue("cpm-impressions");
                const rate = numberValue("cpm-rate");
                const values = [cost,impressions,rate];
                const missing = values.map((item,index) => Number.isNaN(item) ? index : -1).filter(index => index >= 0);
                if (missing.length !== 1 || values.filter(Number.isFinite).some(item => item < 0)) {
                    showError("cpm-result", "Exactly ek field blank rakhein aur baqi do non-negative values enter karein.");
                    return;
                }
                let message;
                if (missing[0] === 0) {
                    if (impressions <= 0) return showError("cpm-result", "Impressions 0 se zyada honi chahiye.");
                    const answer = rate * impressions / 1000;
                    q("#cpm-cost").value = answer.toFixed(2);
                    message = `Required total cost: ${currency(answer)}`;
                } else if (missing[0] === 1) {
                    if (rate <= 0) return showError("cpm-result", "CPM 0 se zyada honi chahiye.");
                    const answer = cost / rate * 1000;
                    q("#cpm-impressions").value = Math.round(answer);
                    message = `Estimated impressions: ${formatNumber(answer,0)}`;
                } else {
                    if (impressions <= 0) return showError("cpm-result", "Impressions 0 se zyada honi chahiye.");
                    const answer = cost / impressions * 1000;
                    q("#cpm-rate").value = answer.toFixed(4);
                    message = `CPM: ${currency(answer)} per 1,000 impressions`;
                }
                showResult("cpm-result", message, "success");
                break;
            }

            case "percentage": {
                const x = numberValue("percent-x");
                const y = numberValue("percent-y");
                const mode = value("percent-mode");
                if (!Number.isFinite(x) || !Number.isFinite(y)) {
                    showError("percent-result", "Dono numeric values enter karein.");
                    return;
                }
                let result, formula;
                if (mode === "of") { result = x / 100 * y; formula = `${x}% of ${y}`; }
                else if (mode === "what") {
                    if (y === 0) return showError("percent-result", "Whole value 0 nahi ho sakti.");
                    result = x / y * 100; formula = `${x} is what % of ${y}`;
                } else if (mode === "change") {
                    if (x === 0) return showError("percent-result", "Old value 0 ho to percentage change undefined hoti hai.");
                    result = (y - x) / Math.abs(x) * 100; formula = `Change from ${x} to ${y}`;
                } else if (mode === "add") { result = y * (1 + x / 100); formula = `${y} plus ${x}%`; }
                else { result = y * (1 - x / 100); formula = `${y} minus ${x}%`; }
                showResult("percent-result", `${formula} = ${formatNumber(result,6)}${["what","change"].includes(mode) ? "%" : ""}`, "success");
                break;
            }

            case "bmi": {
                const height = numberValue("bmi-height");
                const weight = numberValue("bmi-weight");
                if (!Number.isFinite(height) || !Number.isFinite(weight) || height <= 0 || weight <= 0) {
                    showError("bmi-result", "Valid positive height aur weight enter karein.");
                    return;
                }
                const bmi = weight / ((height / 100) ** 2);
                const category = bmi < 18.5 ? "Underweight" : bmi < 25 ? "Healthy range" : bmi < 30 ? "Overweight" : "Obesity range";
                const minWeight = 18.5 * ((height / 100) ** 2);
                const maxWeight = 24.9 * ((height / 100) ** 2);
                const box = showResult("bmi-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat" style="grid-column:span 2"><strong>${formatNumber(bmi,1)}</strong><span>BMI</span></div>
                    <div class="lf-mini-stat" style="grid-column:span 2"><strong style="font-size:1rem">${esc(category)}</strong><span>General category</span></div>
                </div><p style="margin:12px 0 0">Is height ke liye general BMI 18.5–24.9 weight range: <strong>${formatNumber(minWeight,1)}–${formatNumber(maxWeight,1)} kg</strong>.</p>`;
                break;
            }

            case "loan": {
                const principal = numberValue("loan-amount");
                const annualRate = numberValue("loan-rate");
                const years = numberValue("loan-years");
                if (![principal,annualRate,years].every(Number.isFinite) || principal <= 0 || annualRate < 0 || years <= 0) {
                    showError("loan-result", "Valid loan amount, non-negative rate aur positive term enter karein.");
                    return;
                }
                const months = Math.max(1, Math.round(years * 12));
                const monthlyRate = annualRate / 100 / 12;
                const payment = monthlyRate === 0 ? principal / months : principal * monthlyRate * ((1 + monthlyRate) ** months) / (((1 + monthlyRate) ** months) - 1);
                const total = payment * months;
                const interest = total - principal;
                const box = showResult("loan-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${currency(payment)}</strong><span>Monthly EMI</span></div>
                    <div class="lf-mini-stat"><strong>${currency(total)}</strong><span>Total paid</span></div>
                    <div class="lf-mini-stat"><strong>${currency(interest)}</strong><span>Total interest</span></div>
                    <div class="lf-mini-stat"><strong>${months}</strong><span>Payments</span></div>
                </div>`;
                break;
            }

            case "tax": {
                const amount = numberValue("tax-amount");
                const rate = numberValue("tax-rate");
                if (![amount,rate].every(Number.isFinite) || amount < 0 || rate < 0) {
                    showError("tax-result", "Amount aur tax rate non-negative enter karein.");
                    return;
                }
                let base, tax, total;
                if (value("tax-mode") === "exclusive") {
                    base = amount; tax = amount * rate / 100; total = base + tax;
                } else {
                    total = amount; base = rate === 0 ? amount : amount / (1 + rate / 100); tax = total - base;
                }
                const box = showResult("tax-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${currency(base)}</strong><span>Before tax</span></div>
                    <div class="lf-mini-stat"><strong>${currency(tax)}</strong><span>Tax amount</span></div>
                    <div class="lf-mini-stat" style="grid-column:span 2"><strong>${currency(total)}</strong><span>Total incl. tax</span></div>
                </div>`;
                break;
            }

            case "profit": {
                const cost = numberValue("profit-cost");
                const revenue = numberValue("profit-revenue");
                if (![cost,revenue].every(Number.isFinite) || cost < 0 || revenue < 0) {
                    showError("profit-result", "Valid non-negative cost aur revenue enter karein.");
                    return;
                }
                const profit = revenue - cost;
                const margin = revenue === 0 ? NaN : profit / revenue * 100;
                const markup = cost === 0 ? NaN : profit / cost * 100;
                const roi = cost === 0 ? NaN : profit / cost * 100;
                const box = showResult("profit-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong style="color:${profit >= 0 ? "#059669" : "#dc2626"}">${currency(profit)}</strong><span>${profit >= 0 ? "Profit" : "Loss"}</span></div>
                    <div class="lf-mini-stat"><strong>${Number.isFinite(margin) ? formatNumber(margin,2)+"%" : "—"}</strong><span>Margin</span></div>
                    <div class="lf-mini-stat"><strong>${Number.isFinite(markup) ? formatNumber(markup,2)+"%" : "—"}</strong><span>Markup</span></div>
                    <div class="lf-mini-stat"><strong>${Number.isFinite(roi) ? formatNumber(roi,2)+"%" : "—"}</strong><span>ROI</span></div>
                </div>`;
                break;
            }

            case "date-diff": {
                let start = parseDateOnly(value("date-start"));
                let end = parseDateOnly(value("date-end"));
                if (!start || !end) {
                    showError("date-result", "Dono valid dates select karein.");
                    return;
                }
                let reversed = false;
                if (start.date > end.date) { [start,end] = [end,start]; reversed = true; }
                const calendar = calendarDifference(start,end);
                let totalDays = Math.floor((end.date - start.date) / 86400000);
                if (q("#date-inclusive").checked) totalDays++;
                const box = showResult("date-result");
                box.innerHTML = `${reversed ? "<p style='margin:0 0 10px'><strong>Note:</strong> Dates reverse order mein thin; absolute difference shown.</p>" : ""}
                <div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${calendar.years}</strong><span>Years</span></div>
                    <div class="lf-mini-stat"><strong>${calendar.months}</strong><span>Months</span></div>
                    <div class="lf-mini-stat"><strong>${calendar.days}</strong><span>Days</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(totalDays,0)}</strong><span>Total days</span></div>
                </div><p style="margin:12px 0 0"><strong>${formatNumber(Math.floor(totalDays / 7),0)}</strong> complete weeks + <strong>${totalDays % 7}</strong> days.</p>`;
                break;
            }

            case "tip": {
                const bill = numberValue("tip-bill");
                const rate = numberValue("tip-rate");
                const people = numberValue("tip-people");
                if (![bill,rate,people].every(Number.isFinite) || bill < 0 || rate < 0 || people < 1 || !Number.isInteger(people)) {
                    showError("tip-result", "Valid bill, tip % aur whole-number people count enter karein.");
                    return;
                }
                const tipAmount = bill * rate / 100;
                const total = bill + tipAmount;
                const box = showResult("tip-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${currency(tipAmount)}</strong><span>Total tip</span></div>
                    <div class="lf-mini-stat"><strong>${currency(total)}</strong><span>Grand total</span></div>
                    <div class="lf-mini-stat"><strong>${currency(total / people)}</strong><span>Each pays</span></div>
                    <div class="lf-mini-stat"><strong>${currency(tipAmount / people)}</strong><span>Tip each</span></div>
                </div>`;
                break;
            }

            case "unit-swap": {
                const from = q("#unit-from");
                const to = q("#unit-to");
                [from.value,to.value] = [to.value,from.value];
                if (value("unit-value")) await runAction("unit");
                break;
            }

            case "unit": {
                const category = value("unit-category");
                const input = numberValue("unit-value");
                const from = value("unit-from");
                const to = value("unit-to");
                if (!Number.isFinite(input)) {
                    showError("unit-result", "Numeric value enter karein.");
                    return;
                }
                let output;
                if (category === "temperature") {
                    let celsius = from === "C" ? input : from === "F" ? (input - 32) * 5/9 : input - 273.15;
                    output = to === "C" ? celsius : to === "F" ? celsius * 9/5 + 32 : celsius + 273.15;
                    if (to === "K" && output < 0) return showError("unit-result", "Temperature absolute zero se neeche nahi ho sakti.");
                } else {
                    const units = unitDefinitions[category].units;
                    output = input * units[from][1] / units[to][1];
                }
                showResult("unit-result", `${formatNumber(input,8)} ${from} = ${formatNumber(output,10)} ${to}`, "success");
                break;
            }

            case "fuel": {
                const distance = numberValue("fuel-distance");
                const mileage = numberValue("fuel-mileage");
                const price = numberValue("fuel-price");
                const people = numberValue("fuel-people");
                if (![distance,mileage,price,people].every(Number.isFinite) || distance < 0 || mileage <= 0 || price < 0 || people < 1 || !Number.isInteger(people)) {
                    showError("fuel-result", "Valid distance, mileage, price aur whole-number people enter karein.");
                    return;
                }
                const litres = distance / mileage;
                const total = litres * price;
                const box = showResult("fuel-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${formatNumber(litres,2)} L</strong><span>Fuel needed</span></div>
                    <div class="lf-mini-stat"><strong>${currency(total)}</strong><span>Total cost</span></div>
                    <div class="lf-mini-stat"><strong>${currency(total / people)}</strong><span>Per person</span></div>
                    <div class="lf-mini-stat"><strong>${distance ? currency(total / distance) : "0"}</strong><span>Cost / km</span></div>
                </div>`;
                break;
            }

            case "image-studio": {
                const file = q("#studio-file").files[0];
                if (!file || !file.type.startsWith("image/")) {
                    showError("studio-result", "Pehle valid image file select karein.");
                    return;
                }
                if (file.size > 40 * 1024 * 1024) {
                    showError("studio-result", "Browser safety ke liye image 40 MB se choti rakhein.");
                    return;
                }
                const box = showResult("studio-result", "Image process ho rahi hai...");
                try {
                    let bitmap;
                    if ("createImageBitmap" in window) {
                        bitmap = await createImageBitmap(file);
                    } else {
                        bitmap = await new Promise((resolve,reject) => {
                            const url = URL.createObjectURL(file);
                            const image = new Image();
                            image.onload = () => { URL.revokeObjectURL(url); resolve(image); };
                            image.onerror = () => { URL.revokeObjectURL(url); reject(new Error("Image load failed")); };
                            image.src = url;
                        });
                    }
                    if (activeTool !== "image-studio") {
                        bitmap.close?.();
                        return;
                    }
                    const requested = numberValue("studio-width");
                    const maxWidth = Number.isFinite(requested) && requested > 0 ? Math.min(12000, Math.round(requested)) : bitmap.width;
                    const width = Math.min(bitmap.width, maxWidth);
                    const height = Math.max(1, Math.round(bitmap.height * width / bitmap.width));
                    if (width * height > 50000000) {
                        bitmap.close?.();
                        showError("studio-result", "Output dimensions bohat bari hain. Max width kam karein.");
                        return;
                    }
                    const canvas = document.createElement("canvas");
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext("2d");
                    const format = value("studio-format");
                    if (format === "image/jpeg") {
                        context.fillStyle = "#ffffff";
                        context.fillRect(0,0,width,height);
                    }
                    context.imageSmoothingEnabled = true;
                    context.imageSmoothingQuality = "high";
                    context.drawImage(bitmap,0,0,width,height);
                    bitmap.close?.();
                    const quality = Math.max(.1, Math.min(1, Number(value("studio-quality")) / 100));
                    const blob = await new Promise(resolve => canvas.toBlob(resolve,format,quality));
                    if (!blob) throw new Error("Output format unsupported");
                    if (imageDownloadUrl) URL.revokeObjectURL(imageDownloadUrl);
                    imageDownloadUrl = URL.createObjectURL(blob);
                    const extension = format === "image/png" ? "png" : format === "image/jpeg" ? "jpg" : "webp";
                    const baseName = file.name.replace(/\.[^.]+$/, "").replace(/[^\w-]+/g,"-") || "image";
                    box.textContent = "";
                    const preview = document.createElement("img");
                    preview.className = "lf-preview-img";
                    preview.src = imageDownloadUrl;
                    preview.alt = "Compressed image preview";
                    const stats = document.createElement("div");
                    stats.className = "lf-stats";
                    const saved = file.size ? (1 - blob.size / file.size) * 100 : 0;
                    stats.innerHTML = `<div class="lf-mini-stat"><strong>${formatBytes(file.size)}</strong><span>Original</span></div>
                        <div class="lf-mini-stat"><strong>${formatBytes(blob.size)}</strong><span>Output</span></div>
                        <div class="lf-mini-stat"><strong>${saved >= 0 ? formatNumber(saved,1)+"%" : "Larger"}</strong><span>${saved >= 0 ? "Reduced" : "Result"}</span></div>
                        <div class="lf-mini-stat"><strong>${width}×${height}</strong><span>Dimensions</span></div>`;
                    const actions = document.createElement("div");
                    actions.className = "lf-actions";
                    const download = document.createElement("a");
                    download.className = "lf-btn lf-btn-success";
                    download.href = imageDownloadUrl;
                    download.download = `${baseName}-optimized.${extension}`;
                    download.textContent = `Download ${extension.toUpperCase()}`;
                    actions.appendChild(download);
                    box.append(preview,stats,actions);
                } catch (error) {
                    showError("studio-result", `Image process nahi ho saki: ${error.message}`);
                }
                break;
            }

            case "download-base64": {
                const output = value("image-b64-output");
                if (output) downloadTextFile(output, "image-base64.txt");
                break;
            }

            case "uuid": {
                const count = Math.max(1, Math.min(100, Math.round(numberValue("uuid-count") || 1)));
                const upper = value("uuid-case") === "upper";
                const fallbackUuid = () => {
                    const bytes = crypto.getRandomValues(new Uint8Array(16));
                    bytes[6] = (bytes[6] & 0x0f) | 0x40;
                    bytes[8] = (bytes[8] & 0x3f) | 0x80;
                    const hex = [...bytes].map(byte => byte.toString(16).padStart(2,"0")).join("");
                    return `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`;
                };
                const output = Array.from({ length:count }, () => crypto.randomUUID ? crypto.randomUUID() : fallbackUuid()).map(item => upper ? item.toUpperCase() : item).join("\n");
                q("#uuid-output").value = output;
                showResult("uuid-result");
                break;
            }

            case "random-number": {
                const min = numberValue("rand-min");
                const max = numberValue("rand-max");
                const count = numberValue("rand-count");
                if (![min,max,count].every(Number.isSafeInteger) || min > max || count < 1 || count > 1000) {
                    showError("rand-result", "Safe whole-number range aur count 1–1000 enter karein.");
                    return;
                }
                const span = max - min + 1;
                if (span <= 0 || span > 0x100000000) {
                    showError("rand-result", "Range maximum 4,294,967,296 integers tak rakhein.");
                    return;
                }
                const unique = q("#rand-unique").checked;
                if (unique && count > span) {
                    showError("rand-result", "Unique count available range se zyada hai.");
                    return;
                }
                const output = [];
                const seen = new Set();
                while (output.length < count) {
                    const generated = min + secureRandomInt(span);
                    if (!unique || !seen.has(generated)) {
                        seen.add(generated);
                        output.push(generated);
                    }
                }
                if (q("#rand-sort").checked) output.sort((a,b) => a-b);
                const box = showResult("rand-result");
                box.textContent = "";
                const text = document.createElement("textarea");
                text.className = "lf-output";
                text.readOnly = true;
                text.value = output.join(count > 30 ? "\n" : ", ");
                const actions = document.createElement("div");
                actions.className = "lf-actions";
                const copy = document.createElement("button");
                copy.type = "button";
                copy.className = "lf-btn lf-btn-secondary lf-btn-small";
                copy.textContent = "Copy numbers";
                copy.dataset.copyValue = output.join("\n");
                actions.appendChild(copy);
                box.append(text,actions);
                break;
            }

            case "random-picker": {
                const options = [...new Set(value("picker-input").split(/\r?\n/).map(item => item.trim()).filter(Boolean))];
                const count = Math.max(1, Math.round(numberValue("picker-count") || 1));
                if (!options.length || count > options.length) {
                    showError("picker-result", "Options add karein aur winner count unique options se zyada na rakhein.");
                    return;
                }
                const winners = shuffle(options).slice(0,count);
                const box = showResult("picker-result");
                box.textContent = "";
                const heading = document.createElement("p");
                heading.style.margin = "0 0 10px";
                heading.innerHTML = `<strong>🎉 Winner${winners.length === 1 ? "" : "s"}</strong>`;
                const list = document.createElement("div");
                list.className = "lf-list";
                winners.forEach((winner,index) => {
                    const row = document.createElement("div");
                    row.className = "lf-list-item";
                    const rank = document.createElement("strong");
                    rank.textContent = `#${index + 1}`;
                    const text = document.createElement("span");
                    text.className = "lf-list-value";
                    text.textContent = winner;
                    row.append(rank,text);
                    list.appendChild(row);
                });
                box.append(heading,list);
                break;
            }

            case "color-from-hex": convertColorFromHex(); break;
            case "color-from-rgb": convertColorFromRgb(); break;
            case "palette": generatePalette(); break;
            case "copy-palette": {
                const colors = qa("#palette-result .lf-palette-color").map(node => node.dataset.copyValue);
                copyText(colors.join(", "));
                break;
            }

            case "file-hash": {
                if (!crypto.subtle) {
                    showError("hash-digest-result", "Is browser mein Web Crypto hashing available nahi.");
                    return;
                }
                let buffer;
                if (value("hash-source") === "file") {
                    const file = q("#hash-file").files[0];
                    if (!file) {
                        showError("hash-digest-result", "Hash ke liye file select karein.");
                        return;
                    }
                    showResult("hash-digest-result", "File hash calculate ho raha hai...");
                    buffer = await file.arrayBuffer();
                } else {
                    buffer = new TextEncoder().encode(value("hash-text"));
                }
                const digest = await crypto.subtle.digest(value("hash-algo"), buffer);
                if (activeTool !== "file-hash") return;
                const hex = [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2,"0")).join("");
                const box = showResult("hash-digest-result");
                box.textContent = "";
                const output = document.createElement("textarea");
                output.className = "lf-output";
                output.readOnly = true;
                output.value = hex;
                const actions = document.createElement("div");
                actions.className = "lf-actions";
                const copy = document.createElement("button");
                copy.className = "lf-btn lf-btn-secondary lf-btn-small";
                copy.type = "button";
                copy.textContent = "Copy hash";
                copy.dataset.copyValue = hex;
                actions.appendChild(copy);
                box.append(output,actions);
                break;
            }

            case "stopwatch-toggle": {
                if (!stopwatchState) return;
                const button = q("#stopwatch-toggle");
                if (!stopwatchState.running) {
                    stopwatchState.running = true;
                    stopwatchState.startTime = performance.now();
                    button.textContent = "Pause";
                    stopwatchState.timer = requestAnimationFrame(stopwatchTick);
                } else {
                    stopwatchState.elapsed += performance.now() - stopwatchState.startTime;
                    stopwatchState.running = false;
                    cancelAnimationFrame(stopwatchState.timer);
                    button.textContent = "Resume";
                    q("#stopwatch-display").textContent = formatStopwatch(stopwatchState.elapsed);
                }
                break;
            }

            case "stopwatch-lap": {
                if (!stopwatchState) return;
                const current = stopwatchState.elapsed + (stopwatchState.running ? performance.now() - stopwatchState.startTime : 0);
                if (current <= 0) return;
                const previous = stopwatchState.laps.length ? stopwatchState.laps[stopwatchState.laps.length - 1] : 0;
                stopwatchState.laps.push(current);
                const row = document.createElement("div");
                row.className = "lf-list-item";
                row.innerHTML = `<strong>Lap ${stopwatchState.laps.length}</strong><span class="lf-list-value">${formatStopwatch(current)} <small style="color:#857d93">(+${formatStopwatch(current - previous)})</small></span>`;
                q("#stopwatch-laps").prepend(row);
                showResult("stopwatch-result");
                break;
            }

            case "stopwatch-reset": {
                if (stopwatchState?.timer) cancelAnimationFrame(stopwatchState.timer);
                stopwatchState = { running:false, startTime:0, elapsed:0, timer:null, laps:[] };
                q("#stopwatch-display").textContent = "00:00.000";
                q("#stopwatch-toggle").textContent = "Start";
                q("#stopwatch-laps").textContent = "";
                hideResult("stopwatch-result");
                break;
            }

            case "yt-title": {
                const topic = value("ytt-topic").trim();
                if (!topic) {
                    showError("ytt-result", "Video topic enter karein.");
                    return;
                }
                const audience = value("ytt-audience").trim() || "Beginners";
                const keyword = value("ytt-keyword").trim() || topic;
                const year = value("ytt-year") === "current" ? ` (${new Date().getFullYear()})` : "";
                const style = value("ytt-style");
                const banks = {
                    benefit:[
                        `${topic}: The Simple Way to Get Better Results`,
                        `Master ${keyword} Without Wasting Time`,
                        `${topic} Made Easy for ${audience}`,
                        `The Fastest Practical Way to Improve ${keyword}`,
                        `From Zero to Confident: ${topic}`,
                        `Get Better at ${keyword} With This Simple System`,
                        `${topic}: What Actually Works`,
                        `A Smarter Approach to ${keyword}`,
                        `${topic} Step by Step for ${audience}`,
                        `Transform Your Results With ${keyword}`
                    ],
                    howto:[
                        `How to ${topic} (Step-by-Step Guide)`,
                        `How I Approach ${keyword} From Scratch`,
                        `How to Get Started With ${topic}`,
                        `How to Improve ${keyword} Without Overcomplicating It`,
                        `How to ${topic}: Beginner to Pro Roadmap`,
                        `How ${audience} Can Master ${keyword}`,
                        `How to Fix Your ${keyword} Strategy`,
                        `How to Make ${topic} Easier`,
                        `How to Get Consistent Results With ${keyword}`,
                        `${topic}: A Complete How-To Guide`
                    ],
                    curiosity:[
                        `Nobody Tells ${audience} This About ${keyword}`,
                        `I Wish I Knew This Before ${topic}`,
                        `The Truth About ${keyword}`,
                        `This Changed How I Think About ${topic}`,
                        `What Happens When You Fix Your ${keyword}?`,
                        `The Overlooked Secret Behind ${topic}`,
                        `Why Your ${keyword} Is Not Working Yet`,
                        `The ${topic} Method I Did Not Expect to Work`,
                        `Before You Try ${keyword}, Watch This`,
                        `One Small Change That Improves ${topic}`
                    ],
                    list:[
                        `7 ${topic} Tips You Can Use Today`,
                        `10 Ways to Improve ${keyword}`,
                        `5 Simple ${topic} Ideas for ${audience}`,
                        `9 ${keyword} Lessons I Learned the Hard Way`,
                        `12 Practical Ways to Master ${topic}`,
                        `6 Things ${audience} Should Know About ${keyword}`,
                        `8 ${topic} Strategies That Save Time`,
                        `11 Quick Wins for Better ${keyword}`,
                        `5 Steps to Build a Strong ${keyword} System`,
                        `7 Examples of ${topic} Done Right`
                    ],
                    mistakes:[
                        `Stop Making These ${keyword} Mistakes`,
                        `7 ${topic} Mistakes Holding You Back`,
                        `Why Most ${audience} Get ${keyword} Wrong`,
                        `Do Not Try ${topic} Before Knowing This`,
                        `The Biggest ${keyword} Mistake to Avoid`,
                        `${topic}: What Not to Do`,
                        `Your ${keyword} Is Failing for These Reasons`,
                        `5 Warning Signs Your ${keyword} Needs Work`,
                        `Avoid These Beginner ${topic} Traps`,
                        `Fix These ${keyword} Errors Today`
                    ]
                };
                const titles = banks[style].map(title => `${title}${year}`.replace(/\s+/g," ").trim().slice(0,100));
                createCopyList(q("#ytt-list"),titles);
                showResult("ytt-result");
                break;
            }

            case "hook-maker": {
                const topic = value("hook-topic").trim();
                if (!topic) {
                    showError("hook-result", "Content topic ya offer enter karein.");
                    return;
                }
                const format = value("hook-platform");
                const tone = value("hook-tone");
                const banks = {
                    bold:[
                        `Stop scrolling—this is what you need to know about ${topic}.`,
                        `Your current approach to ${topic} is probably costing you results.`,
                        `Here is the no-fluff truth about ${topic}.`,
                        `If you care about ${topic}, do not ignore this.`,
                        `${topic} is easier when you stop doing this one thing.`
                    ],
                    curious:[
                        `What if everything you knew about ${topic} was incomplete?`,
                        `Nobody talks about this part of ${topic}.`,
                        `I tested a different approach to ${topic}—here is what happened.`,
                        `There is one detail about ${topic} most people miss.`,
                        `Can one small change really improve ${topic}?`
                    ],
                    helpful:[
                        `Save this before you work on ${topic}.`,
                        `Here is a simple way to make ${topic} less confusing.`,
                        `Three things I would do first for ${topic}.`,
                        `Use this quick checklist for better ${topic}.`,
                        `If you are stuck with ${topic}, start here.`
                    ],
                    story:[
                        `I almost gave up on ${topic}—then this happened.`,
                        `A few months ago, I knew nothing about ${topic}.`,
                        `The mistake that changed how I approach ${topic}.`,
                        `Here is what nobody saw behind my ${topic} journey.`,
                        `One ordinary day completely changed my view of ${topic}.`
                    ],
                    urgent:[
                        `Before you post again, fix this part of ${topic}.`,
                        `Do this today if you want better ${topic}.`,
                        `You are running out of time to ignore ${topic}.`,
                        `Check this before your next ${topic} move.`,
                        `Do not spend another hour on ${topic} without this.`
                    ]
                };
                const extras = [
                    `${format} idea: “The fastest way to understand ${topic} in 30 seconds.”`,
                    `POV: You finally discover what works for ${topic}.`,
                    `You do not need more motivation for ${topic}—you need this system.`,
                    `Most people start ${topic} from the wrong place.`,
                    `Watch until the end if ${topic} matters to you.`
                ];
                createCopyList(q("#hook-list"),shuffle([...banks[tone],...extras]).slice(0,10));
                showResult("hook-result");
                break;
            }

            case "keyword-density": {
                const source = value("density-input").trim();
                if (!source) {
                    showError("density-result", "Analyze karne ke liye content paste karein.");
                    return;
                }
                const minLength = Math.max(1,Math.min(12,Number(value("density-min")) || 3));
                const words = (source.toLocaleLowerCase().match(/[\p{L}\p{N}]+(?:['’_-][\p{L}\p{N}]+)*/gu) || []);
                const filtered = words.filter(word => word.length >= minLength && (!q("#density-stop").checked || !englishStopWords.has(word)));
                const counts = filtered.reduce((map,word) => map.set(word,(map.get(word)||0)+1),new Map());
                const ranked = [...counts.entries()].sort((a,b) => b[1]-a[1] || a[0].localeCompare(b[0])).slice(0,15);
                const target = value("density-keyword").trim().toLocaleLowerCase();
                let targetCount = 0, targetDensity = 0;
                if (target) {
                    const pattern = new RegExp(escapeRegex(target).replace(/\s+/g,"\\s+"),"giu");
                    targetCount = (source.match(pattern)||[]).length;
                    targetDensity = words.length ? targetCount * Math.max(1,target.split(/\s+/).length) / words.length * 100 : 0;
                }
                const box = showResult("density-result");
                box.textContent = "";
                const stats = document.createElement("div");
                stats.className = "lf-stats";
                stats.innerHTML = `<div class="lf-mini-stat"><strong>${words.length}</strong><span>Total words</span></div>
                    <div class="lf-mini-stat"><strong>${counts.size}</strong><span>Unique filtered</span></div>
                    <div class="lf-mini-stat"><strong>${target ? targetCount : "—"}</strong><span>Target uses</span></div>
                    <div class="lf-mini-stat"><strong>${target ? formatNumber(targetDensity,2)+"%" : "—"}</strong><span>Target density</span></div>`;
                const table = document.createElement("div");
                table.className = "lf-data-table";
                table.style.marginTop = "12px";
                table.innerHTML = '<div class="lf-data-row is-head"><span>Keyword</span><span>Count</span><span>Density</span></div>';
                ranked.forEach(([word,count]) => {
                    const row = document.createElement("div");
                    row.className = "lf-data-row";
                    const term = document.createElement("span");
                    term.textContent = word;
                    const uses = document.createElement("span");
                    uses.textContent = count;
                    const density = document.createElement("span");
                    density.textContent = `${formatNumber(count/Math.max(1,words.length)*100,2)}%`;
                    row.append(term,uses,density);
                    table.appendChild(row);
                });
                box.append(stats,table);
                break;
            }

            case "readability": {
                const source = value("read-input").trim();
                const words = source.match(/[A-Za-z]+(?:['’-][A-Za-z]+)*/g) || [];
                const sentences = source.match(/[^.!?]+[.!?]+|[^.!?]+$/g) || [];
                if (words.length < 10) {
                    showError("read-result", "Meaningful score ke liye kam az kam 10 English words add karein.");
                    return;
                }
                const syllablesInWord = raw => {
                    let word = raw.toLowerCase().replace(/[^a-z]/g,"");
                    if (word.length <= 3) return 1;
                    word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/,"").replace(/^y/,"");
                    return Math.max(1,(word.match(/[aeiouy]{1,2}/g)||[]).length);
                };
                const syllables = words.reduce((total,word) => total+syllablesInWord(word),0);
                const sentenceCount = Math.max(1,sentences.length);
                const ease = 206.835 - 1.015*(words.length/sentenceCount) - 84.6*(syllables/words.length);
                const grade = .39*(words.length/sentenceCount)+11.8*(syllables/words.length)-15.59;
                const label = ease >= 80 ? "Easy" : ease >= 60 ? "Standard" : ease >= 40 ? "Difficult" : "Very difficult";
                const box = showResult("read-result");
                box.innerHTML = `<div class="lf-stats">
                    <div class="lf-mini-stat"><strong>${formatNumber(Math.max(0,Math.min(100,ease)),1)}</strong><span>Reading ease</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(Math.max(0,grade),1)}</strong><span>Grade level</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(words.length/sentenceCount,1)}</strong><span>Words / sentence</span></div>
                    <div class="lf-mini-stat"><strong>${formatNumber(syllables/words.length,2)}</strong><span>Syllables / word</span></div>
                </div><p style="margin:12px 0 0"><strong>Overall:</strong> ${label}. Shorter sentences aur familiar words readability improve karte hain.</p>`;
                break;
            }

            case "speech-play": {
                if (!("speechSynthesis" in window)) return;
                const text = value("speech-text").trim();
                if (!text) return showError("speech-result","Speak karne ke liye text enter karein.");
                window.speechSynthesis.cancel();
                speechUtterance = new SpeechSynthesisUtterance(text);
                const voices = window.speechSynthesis.getVoices();
                const index = Number(value("speech-voice"));
                if (Number.isInteger(index) && voices[index]) speechUtterance.voice = voices[index];
                speechUtterance.rate = Number(value("speech-rate")) || 1;
                speechUtterance.pitch = Number(value("speech-pitch")) || 1;
                speechUtterance.onstart = () => activeTool === "text-speech" && showResult("speech-result","Speaking…","success");
                speechUtterance.onend = () => activeTool === "text-speech" && showResult("speech-result","Speech complete.","success");
                speechUtterance.onerror = () => activeTool === "text-speech" && showError("speech-result","Speech play nahi ho saki. Dusri voice try karein.");
                window.speechSynthesis.speak(speechUtterance);
                break;
            }
            case "speech-pause": {
                if (!("speechSynthesis" in window)) return;
                if (window.speechSynthesis.paused) {
                    window.speechSynthesis.resume();
                    showResult("speech-result","Speech resumed.","success");
                } else if (window.speechSynthesis.speaking) {
                    window.speechSynthesis.pause();
                    showResult("speech-result","Speech paused.");
                }
                break;
            }
            case "speech-stop": {
                if ("speechSynthesis" in window) window.speechSynthesis.cancel();
                showResult("speech-result","Speech stopped.");
                break;
            }

            case "jwt-decode": {
                const token = value("jwt-input").trim();
                const parts = token.split(".");
                if (parts.length < 2) return showError("jwt-result","Valid JWT mein kam az kam header.payload hota hai.");
                const decodePart = part => {
                    const normalized = part.replace(/-/g,"+").replace(/_/g,"/");
                    const padded = normalized+"=".repeat((4-normalized.length%4)%4);
                    const binary = atob(padded);
                    return new TextDecoder().decode(Uint8Array.from(binary,char=>char.charCodeAt(0)));
                };
                try {
                    const header = JSON.parse(decodePart(parts[0]));
                    const payload = JSON.parse(decodePart(parts[1]));
                    q("#jwt-header").value = JSON.stringify(header,null,2);
                    q("#jwt-payload").value = JSON.stringify(payload,null,2);
                    const box = showResult("jwt-result");
                    let existing = box.querySelector(".lf-jwt-note");
                    if (existing) existing.remove();
                    const note = document.createElement("p");
                    note.className = "lf-jwt-note";
                    note.style.margin = "0 0 12px";
                    const now = Math.floor(Date.now()/1000);
                    note.textContent = payload.exp ? `Expiry claim: ${new Date(payload.exp*1000).toLocaleString()} — ${payload.exp < now ? "token appears expired" : "not expired by local clock"}.` : "No exp (expiry) claim found.";
                    box.prepend(note);
                } catch (_) {
                    showError("jwt-result","JWT Base64URL/JSON decode nahi ho saka.");
                }
                break;
            }

            case "gradient-swap": {
                const one=q("#gradient-one"),two=q("#gradient-two");
                [one.value,two.value]=[two.value,one.value];
                updateGradient();
                break;
            }

            case "contrast-swap": {
                const one=q("#contrast-fg"),two=q("#contrast-bg");
                [one.value,two.value]=[two.value,one.value];
                updateContrast();
                break;
            }

            case "http-status": {
                const code = Number(value("http-code"));
                if (!Number.isInteger(code) || code < 100 || code > 599) return showError("http-result","100–599 ke darmiyan valid HTTP code enter karein.");
                const known = httpStatuses[code];
                const group = code < 200 ? "Informational" : code < 300 ? "Success" : code < 400 ? "Redirection" : code < 500 ? "Client error" : "Server error";
                const box=showResult("http-result");
                box.innerHTML=`<div class="lf-stats"><div class="lf-mini-stat"><strong>${code}</strong><span>Status code</span></div><div class="lf-mini-stat" style="grid-column:span 3"><strong style="font-size:1rem">${esc(known?.[0]||"Unassigned / uncommon")}</strong><span>${esc(group)}</span></div></div><p style="margin:12px 0 0">${esc(known?.[1]||"Is code ke liye toolkit mein detailed description available nahi.")}</p>`;
                break;
            }

            case "compound-interest": {
                const principal=numberValue("ci-principal"),monthly=numberValue("ci-monthly"),rate=numberValue("ci-rate"),years=numberValue("ci-years"),frequency=numberValue("ci-frequency");
                if (![principal,monthly,rate,years,frequency].every(Number.isFinite)||principal<0||monthly<0||rate<=-100||years<=0) return showError("ci-result","Valid non-negative amounts, rate greater than -100%, aur positive years enter karein.");
                const annual=rate/100,months=Math.max(1,Math.round(years*12));
                const principalFuture=principal*(1+annual/frequency)**(frequency*years);
                const monthlyRate=annual/12;
                const contributionsFuture=monthlyRate===0?monthly*months:monthly*((1+monthlyRate)**months-1)/monthlyRate;
                const finalAmount=principalFuture+contributionsFuture;
                const contributed=principal+monthly*months;
                const interest=finalAmount-contributed;
                const box=showResult("ci-result");
                box.innerHTML=`<div class="lf-stats"><div class="lf-mini-stat"><strong>${currency(finalAmount)}</strong><span>Future value</span></div><div class="lf-mini-stat"><strong>${currency(contributed)}</strong><span>Contributed</span></div><div class="lf-mini-stat"><strong>${currency(interest)}</strong><span>Growth earned</span></div><div class="lf-mini-stat"><strong>${months}</strong><span>Months</span></div></div>`;
                break;
            }

            case "savings-goal": {
                const target=numberValue("save-target"),current=numberValue("save-current"),rate=numberValue("save-rate"),years=numberValue("save-years");
                if (![target,current,rate,years].every(Number.isFinite)||target<=0||current<0||rate<=-100||years<=0) return showError("save-result","Valid goal, saved amount, rate greater than -100%, aur positive time enter karein.");
                const months=Math.max(1,Math.round(years*12)),monthlyRate=rate/100/12;
                const currentFuture=current*(1+monthlyRate)**months;
                const gap=Math.max(0,target-currentFuture);
                const monthly=gap===0?0:monthlyRate===0?gap/months:gap*monthlyRate/((1+monthlyRate)**months-1);
                const totalNew=monthly*months;
                const box=showResult("save-result");
                box.innerHTML=`<div class="lf-stats"><div class="lf-mini-stat" style="grid-column:span 2"><strong>${currency(monthly)}</strong><span>Required monthly saving</span></div><div class="lf-mini-stat"><strong>${currency(totalNew)}</strong><span>New contributions</span></div><div class="lf-mini-stat"><strong>${months}</strong><span>Months</span></div></div><p style="margin:12px 0 0">${gap===0?"Current savings ki projected value goal ko cover karti hai.":"Contribution har month ke end par assume ki gayi hai."}</p>`;
                break;
            }

            case "ratio-swap": {
                const width=q("#ratio-width"),height=q("#ratio-height");
                [width.value,height.value]=[height.value,width.value];
                break;
            }

            case "aspect-ratio": {
                const width=numberValue("ratio-width"),height=numberValue("ratio-height"),newWidth=numberValue("ratio-new-width"),newHeight=numberValue("ratio-new-height");
                if (![width,height].every(Number.isFinite)||width<=0||height<=0) return showError("ratio-result","Valid positive original width aur height enter karein.");
                const gcd=(a,b)=>{a=Math.round(a);b=Math.round(b);while(b)[a,b]=[b,a%b];return Math.abs(a)||1};
                const divisor=gcd(width,height),ratioW=Math.round(width)/divisor,ratioH=Math.round(height)/divisor;
                let targetText="New width ya height enter karein to resized dimension milegi.";
                if (Number.isFinite(newWidth)&&newWidth>0) targetText=`${formatNumber(newWidth,0)} × ${formatNumber(newWidth*height/width,0)} px`;
                else if (Number.isFinite(newHeight)&&newHeight>0) targetText=`${formatNumber(newHeight*width/height,0)} × ${formatNumber(newHeight,0)} px`;
                const decimal=width/height;
                const orientation=decimal>1?"Landscape":decimal<1?"Portrait":"Square";
                const box=showResult("ratio-result");
                box.innerHTML=`<div class="lf-stats"><div class="lf-mini-stat"><strong>${ratioW}:${ratioH}</strong><span>Simplified ratio</span></div><div class="lf-mini-stat"><strong>${formatNumber(decimal,4)}</strong><span>Decimal ratio</span></div><div class="lf-mini-stat" style="grid-column:span 2"><strong style="font-size:1rem">${orientation}</strong><span>Orientation</span></div></div><p style="margin:12px 0 0"><strong>Proportional output:</strong> ${targetText}</p>`;
                break;
            }

            case "time-duration": {
                const toMinutes=raw=>{const parts=raw.split(":").map(Number);return parts.length===2&&parts.every(Number.isFinite)?parts[0]*60+parts[1]:NaN};
                const start=toMinutes(value("duration-start")),endRaw=toMinutes(value("duration-end")),breakMinutes=numberValue("duration-break");
                if (![start,endRaw,breakMinutes].every(Number.isFinite)||breakMinutes<0) return showError("duration-result","Valid start/end time aur non-negative break enter karein.");
                let end=endRaw;
                if (end<start&&q("#duration-overnight").checked) end+=1440;
                if (end<start) return showError("duration-result","End time start se pehle hai—overnight option enable karein.");
                const gross=end-start,net=gross-breakMinutes;
                if (net<0) return showError("duration-result","Break duration total time se zyada hai.");
                const fmt=minutes=>`${Math.floor(minutes/60)}h ${Math.round(minutes%60)}m`;
                const box=showResult("duration-result");
                box.innerHTML=`<div class="lf-stats"><div class="lf-mini-stat"><strong>${fmt(gross)}</strong><span>Gross duration</span></div><div class="lf-mini-stat"><strong>${fmt(breakMinutes)}</strong><span>Break</span></div><div class="lf-mini-stat" style="grid-column:span 2"><strong>${fmt(net)}</strong><span>Net duration</span></div></div><p style="margin:12px 0 0"><strong>Decimal hours:</strong> ${formatNumber(net/60,2)}</p>`;
                break;
            }

            case "favicon-download": {
                const size=Math.max(16,Math.min(1024,Number(value("fav-size"))||512));
                const canvas=document.createElement("canvas");
                renderFaviconCanvas(canvas,size);
                canvas.toBlob(blob=>{
                    if (blob) downloadBlob(blob,`favicon-${size}x${size}.png`);
                    else toast("Favicon export nahi ho saka.");
                },"image/png");
                break;
            }

            case "pomo-toggle": {
                if (!pomodoroState||pomodoroState.phase==="complete") resetPomodoro();
                if (!pomodoroState.running) {
                    pomodoroState.running=true;
                    pomodoroState.deadline=Date.now()+pomodoroState.remaining*1000;
                    q("#pomo-toggle").textContent="Pause";
                    clearInterval(pomodoroState.timer);
                    pomodoroState.timer=setInterval(pomodoroTick,250);
                    pomodoroTick();
                } else {
                    pomodoroState.remaining=Math.max(0,(pomodoroState.deadline-Date.now())/1000);
                    pomodoroState.running=false;
                    clearInterval(pomodoroState.timer);
                    q("#pomo-toggle").textContent="Resume";
                    updatePomodoroDisplay();
                }
                break;
            }
            case "pomo-skip": {
                if (!pomodoroState) return;
                advancePomodoro();
                if (pomodoroState.running) pomodoroState.deadline=Date.now()+pomodoroState.remaining*1000;
                break;
            }
            case "pomo-reset": resetPomodoro(); break;

            case "coin-dice": {
                const mode=value("random-mode"),count=Math.max(1,Math.min(50,Math.round(numberValue("dice-count")||1)));
                let results,label;
                if (mode==="coin") {
                    results=Array.from({length:count},()=>secureRandomInt(2)?"Heads":"Tails");
                    label=`${results.filter(item=>item==="Heads").length} Heads • ${results.filter(item=>item==="Tails").length} Tails`;
                } else {
                    const sides=Math.max(2,Number(value("dice-sides"))||6);
                    results=Array.from({length:count},()=>secureRandomInt(sides)+1);
                    label=`Total ${results.reduce((sum,item)=>sum+item,0)} • Average ${formatNumber(results.reduce((sum,item)=>sum+item,0)/count,2)}`;
                }
                const box=showResult("coin-result");
                box.textContent="";
                const heading=document.createElement("p");heading.style.margin="0 0 10px";heading.innerHTML=`<strong>${esc(label)}</strong>`;
                const chips=document.createElement("div");chips.className="lf-checks";
                results.forEach(result=>{const chip=document.createElement("span");chip.className="lf-color-chip";chip.style.fontSize="1rem";chip.style.fontWeight="800";chip.textContent=result;chips.appendChild(chip)});
                box.append(heading,chips);
                break;
            }
        }
    }

    grid.addEventListener("click", event => {
        const card = event.target.closest("[data-tool]");
        if (card) openTool(card.dataset.tool);
    });

    grid.addEventListener("keydown", event => {
        const card = event.target.closest("[data-tool]");
        if (card && (event.key === "Enter" || event.key === " ")) {
            event.preventDefault();
            openTool(card.dataset.tool);
        }
    });

    categories.addEventListener("click", event => {
        const button = event.target.closest("[data-category]");
        if (!button) return;
        activeCategory = button.dataset.category;
        categories.querySelectorAll(".lf-cat").forEach(item => item.classList.toggle("is-active", item === button));
        renderTools();
    });

    searchInput.addEventListener("input", debounce(renderTools, 100));
    clearSearch.addEventListener("click", () => {
        searchInput.value = "";
        searchInput.focus();
        renderTools();
    });

    modal.addEventListener("click", async event => {
        if (event.target.closest("[data-close-modal]")) {
            closeTool();
            return;
        }

        const copyTarget = event.target.closest("[data-copy]");
        if (copyTarget) {
            const target = q(copyTarget.dataset.copy);
            await copyText(target ? ("value" in target ? target.value : target.textContent) : "");
            return;
        }

        const directCopy = event.target.closest("[data-copy-value]");
        if (directCopy) {
            await copyText(directCopy.dataset.copyValue);
            return;
        }

        const selfCopy = event.target.closest("[data-copy-self]");
        if (selfCopy) {
            const lastLabel = selfCopy.querySelector("span:last-child");
            await copyText(lastLabel?.textContent || selfCopy.textContent.trim());
            return;
        }

        const remoteDownload = event.target.closest("[data-download-url]");
        if (remoteDownload) {
            remoteDownload.disabled = true;
            const oldText = remoteDownload.textContent;
            remoteDownload.textContent = "Preparing...";
            await downloadRemote(remoteDownload.dataset.downloadUrl, remoteDownload.dataset.filename || "download");
            remoteDownload.disabled = false;
            remoteDownload.textContent = oldText;
            return;
        }

        const actionButton = event.target.closest("[data-action]");
        if (actionButton) {
            try {
                await runAction(actionButton.dataset.action);
            } catch (error) {
                console.error("LikeXFollow tool error:", error);
                toast("Unexpected error — input check karke dobara try karein.");
            }
        }
    });

    document.addEventListener("keydown", event => {
        if (modal.hidden) return;
        if (event.key === "Escape") {
            event.preventDefault();
            closeTool();
            return;
        }
        if (event.key === "Tab") {
            const focusable = [...modal.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter(node => node.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    app.querySelectorAll("[data-lf-generated-art]").forEach(image => {
        const markMissing = () => {
            const hero = image.closest(".lf-hero-art");
            if (hero) hero.classList.add("is-missing");
            else image.classList.add("is-missing");
        };
        image.addEventListener("error", markMissing, { once:true });
        if (image.complete && image.naturalWidth === 0) markMissing();
    });

    renderTools();
})();
</script>

<?php include '_smm_footer.php'; ?>
