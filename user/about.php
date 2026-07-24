<?php include '_header.php'; ?>  <!DOCTYPE html>  <html lang="en">  
<head>  
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Founder Profile - Israr Liaqat</title>  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">  
  
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  
  
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">  
  
<style>  
    * {  
        margin: 0;  
        padding: 0;  
        box-sizing: border-box;  
    }  

    :root {  
        --accent-1: #667eea;  
        --accent-2: #764ba2;  
        --glass-bg: rgba(255, 255, 255, 0.55);  
        --glass-border: rgba(255, 255, 255, 0.75);  
        --shadow-soft: 0 20px 60px rgba(31, 38, 135, 0.12);  
    }  

    html {  
        scroll-behavior: smooth;  
    }  

    body {  
        font-family: 'Outfit', sans-serif;  
        background: #eef1f8;  
        color: #333;  
        min-height: 100vh;  
        overflow-x: hidden;  
        position: relative;  
    }  

    /* ================= Animated macOS-style Background ================= */  
    .animated-bg {  
        position: fixed;  
        inset: 0;  
        z-index: 0;  
        background: linear-gradient(135deg, #eef1f8 0%, #dfe6f3 45%, #e8ecfb 100%);  
        overflow: hidden;  
    }  

    .animated-bg::before {  
        content: '';  
        position: absolute;  
        width: 650px;  
        height: 650px;  
        background: radial-gradient(circle, rgba(102, 126, 234, 0.22) 0%, transparent 70%);  
        border-radius: 50%;  
        top: -220px;  
        left: -200px;  
        filter: blur(10px);  
        animation: float 22s infinite ease-in-out;  
    }  

    .animated-bg::after {  
        content: '';  
        position: absolute;  
        width: 550px;  
        height: 550px;  
        background: radial-gradient(circle, rgba(118, 75, 162, 0.18) 0%, transparent 70%);  
        border-radius: 50%;  
        bottom: -180px;  
        right: -160px;  
        filter: blur(10px);  
        animation: float 18s infinite ease-in-out reverse;  
    }  

    .bg-blob {  
        position: absolute;  
        width: 420px;  
        height: 420px;  
        background: radial-gradient(circle, rgba(29, 161, 242, 0.14) 0%, transparent 70%);  
        border-radius: 50%;  
        top: 40%;  
        left: 45%;  
        filter: blur(8px);  
        animation: float 26s infinite ease-in-out;  
    }  

    @keyframes float {  
        0%, 100% { transform: translate(0, 0) scale(1); }  
        50% { transform: translate(60px, 40px) scale(1.12); }  
    }  

    /* Mesh grid overlay for a subtle macOS depth feel */  
    .mesh-grid {  
        position: fixed;  
        inset: 0;  
        z-index: 0;  
        background-image:  
            linear-gradient(rgba(102, 126, 234, 0.05) 1px, transparent 1px),  
            linear-gradient(90deg, rgba(102, 126, 234, 0.05) 1px, transparent 1px);  
        background-size: 48px 48px;  
        mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 100%);  
        pointer-events: none;  
    }  

    /* Particles */  
    .particle {  
        position: absolute;  
        width: 5px;  
        height: 5px;  
        background: rgba(102, 126, 234, 0.55);  
        border-radius: 50%;  
        box-shadow: 0 0 8px rgba(102, 126, 234, 0.6);  
        animation: particle-float 12s infinite;  
    }  

    @keyframes particle-float {  
        0% { transform: translateY(0) translateX(0); opacity: 0; }  
        10% { opacity: 1; }  
        90% { opacity: 1; }  
        100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }  
    }  

    /* ================= Hero Section ================= */  
    .hero-section {  
        position: relative;  
        width: 100%;  
        height: 420px;  
        background: linear-gradient(135deg, #667eea 0%, #764ba2 55%, #5b3d99 100%);  
        clip-path: polygon(0 0, 100% 0, 100% 82%, 0 100%);  
        display: flex;  
        align-items: center;  
        justify-content: center;  
        overflow: hidden;  
        box-shadow: 0 20px 50px rgba(102, 126, 234, 0.25);  
        perspective: 1200px;  
    }  

    .hero-section::before {  
        content: '';  
        position: absolute;  
        width: 100%;  
        height: 100%;  
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,50 Q300,100 600,50 T1200,50 L1200,120 L0,120 Z" fill="rgba(255,255,255,0.12)"/></svg>') repeat-x;  
        animation: wave 10s linear infinite;  
        opacity: 0.5;  
    }  

    .hero-section::after {  
        content: '';  
        position: absolute;  
        inset: 0;  
        background:  
            radial-gradient(circle at 20% 20%, rgba(255,255,255,0.18) 0%, transparent 35%),  
            radial-gradient(circle at 80% 70%, rgba(255,255,255,0.12) 0%, transparent 40%);  
        pointer-events: none;  
    }  

    @keyframes wave {  
        0% { background-position: 0 0; }  
        100% { background-position: 1200px 0; }  
    }  

    .hero-content {  
        text-align: center;  
        color: white;  
        z-index: 1;  
        transform-style: preserve-3d;  
        animation: heroFloat 6s ease-in-out infinite;  
    }  

    @keyframes heroFloat {  
        0%, 100% { transform: translateY(0) rotateX(0deg); }  
        50% { transform: translateY(-8px) rotateX(2deg); }  
    }  

    .hero-title {  
        font-size: 52px;  
        font-weight: 800;  
        margin-bottom: 12px;  
        letter-spacing: -0.5px;  
        text-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);  
        animation: glow 3s ease-in-out infinite;  
    }  

    @keyframes glow {  
        0%, 100% { text-shadow: 0 4px 15px rgba(255, 255, 255, 0.25), 0 8px 25px rgba(0,0,0,0.25); }  
        50% { text-shadow: 0 4px 30px rgba(255, 255, 255, 0.65), 0 8px 25px rgba(0,0,0,0.25); }  
    }  

    .hero-subtitle {  
        font-size: 19px;  
        font-weight: 400;  
        opacity: 0.95;  
        letter-spacing: 0.6px;  
    }  

    .hero-subtitle .dot {  
        display: inline-block;  
        width: 6px;  
        height: 6px;  
        background: #fff;  
        border-radius: 50%;  
        margin: 0 10px;  
        opacity: 0.7;  
        vertical-align: middle;  
        animation: pulseDot 1.8s ease-in-out infinite;  
    }  

    @keyframes pulseDot {  
        0%, 100% { opacity: 0.4; transform: scale(1); }  
        50% { opacity: 1; transform: scale(1.4); }  
    }  

    /* ================= Main Container ================= */  
    .profile-wrapper {  
        position: relative;  
        z-index: 1;  
        max-width: 1300px;  
        margin: -130px auto 60px;  
        padding: 0 30px;  
        perspective: 1800px;  
    }  

    .profile-grid {  
        display: grid;  
        grid-template-columns: 400px 1fr;  
        gap: 35px;  
    }  

    /* ================= Modern macOS Glass Card ================= */  
    .modern-card {  
        background: var(--glass-bg);  
        backdrop-filter: blur(30px) saturate(180%);  
        -webkit-backdrop-filter: blur(30px) saturate(180%);  
        border-radius: 28px;  
        border: 1px solid var(--glass-border);  
        padding: 40px;  
        position: relative;  
        overflow: hidden;  
        box-shadow:  
            var(--shadow-soft),  
            inset 0 1px 0 rgba(255, 255, 255, 0.8),  
            inset 0 -1px 0 rgba(255, 255, 255, 0.2);  
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;  
        transform-style: preserve-3d;  
        will-change: transform;  
    }  

    /* macOS traffic-light dots for extra "app window" feel */  
    .modern-card::before {  
        content: '';  
        position: absolute;  
        top: 18px;  
        left: 22px;  
        width: 12px;  
        height: 12px;  
        border-radius: 50%;  
        background: #ff5f57;  
        box-shadow: 20px 0 0 #febc2e, 40px 0 0 #28c840;  
        opacity: 0.85;  
    }  

    .modern-card:hover {  
        box-shadow:  
            0 30px 70px rgba(102, 126, 234, 0.22),  
            inset 0 1px 0 rgba(255, 255, 255, 0.9),  
            inset 0 -1px 0 rgba(255, 255, 255, 0.25);  
    }  

    /* Sheen sweep on hover */  
    .modern-card .sheen {  
        position: absolute;  
        top: 0;  
        left: -150%;  
        width: 60%;  
        height: 100%;  
        background: linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent);  
        transform: skewX(-20deg);  
        transition: left 0.9s ease;  
        pointer-events: none;  
    }  

    .modern-card:hover .sheen {  
        left: 150%;  
    }  

    /* ================= Profile Card ================= */  
    .profile-main {  
        position: sticky;  
        top: 20px;  
        padding-top: 55px;  
    }  

    .avatar-section {  
        position: relative;  
        width: 180px;  
        height: 180px;  
        margin: 0 auto 30px;  
        transform: translateZ(40px);  
    }  

    .avatar-ring {  
        position: absolute;  
        inset: -6px;  
        border-radius: 50%;  
        background: conic-gradient(from 0deg, #667eea, #764ba2, #1da1f2, #667eea);  
        animation: rotate 6s linear infinite;  
        filter: blur(0.3px);  
    }  

    @keyframes rotate {  
        100% { transform: rotate(360deg); }  
    }  

    .avatar-inner {  
        position: absolute;  
        inset: 5px;  
        border-radius: 50%;  
        background: white;  
        display: flex;  
        align-items: center;  
        justify-content: center;  
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);  
    }  

    .avatar-img {  
        width: 160px;  
        height: 160px;  
        border-radius: 50%;  
        object-fit: cover;  
        border: 4px solid white;  
    }  

    .verified-icon {  
        position: absolute;  
        bottom: 5px;  
        right: 5px;  
        width: 45px;  
        height: 45px;  
        background: linear-gradient(135deg, #1da1f2, #0d7cc1);  
        border-radius: 50%;  
        display: flex;  
        align-items: center;  
        justify-content: center;  
        border: 3px solid white;  
        color: white;  
        font-size: 20px;  
        box-shadow: 0 6px 15px rgba(29, 161, 242, 0.5);  
        animation: bounce 2.4s ease-in-out infinite;  
        z-index: 2;  
    }  

    @keyframes bounce {  
        0%, 100% { transform: scale(1) translateZ(0); }  
        50% { transform: scale(1.18) translateZ(10px); }  
    }  

    .name-title {  
        font-size: 32px;  
        font-weight: 800;  
        color: #1a1a2e;  
        text-align: center;  
        margin-bottom: 8px;  
        transform: translateZ(25px);  
        letter-spacing: -0.3px;  
    }  

    .role-badge {  
        font-size: 16px;  
        font-weight: 600;  
        text-align: center;  
        color: var(--accent-1);  
        margin-bottom: 25px;  
        background: rgba(102, 126, 234, 0.1);  
        border: 1px solid rgba(102, 126, 234, 0.15);  
        padding: 7px 15px;  
        border-radius: 20px;  
        display: block;  
        width: fit-content;  
        margin-left: auto;  
        margin-right: auto;  
        transform: translateZ(20px);  
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.12);  
    }  

    .bio-text {  
        font-size: 15px;  
        line-height: 1.8;  
        color: #555;  
        text-align: center;  
        margin-bottom: 35px;  
        font-weight: 400;  
        transform: translateZ(10px);  
    }  

    /* Social Links */  
    .social-links {  
        display: flex;  
        gap: 15px;  
        justify-content: center;  
        margin-bottom: 30px;  
        transform: translateZ(30px);  
    }  

    .social-icon {  
        width: 55px;  
        height: 55px;  
        border-radius: 50%;  
        display: flex;  
        align-items: center;  
        justify-content: center;  
        color: white;  
        font-size: 22px;  
        text-decoration: none;  
        position: relative;  
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);  
        box-shadow: 0 8px 18px rgba(0,0,0,0.15);  
    }  

    .social-icon:hover {  
        transform: translateY(-6px) scale(1.12) rotate(-6deg);  
        box-shadow: 0 15px 28px rgba(0,0,0,0.22);  
    }  

    .whatsapp-link { background: linear-gradient(135deg, #25D366, #128C7E); }  
    .instagram-link { background: linear-gradient(135deg, #f09433, #dc2743, #bc1888); }  
    .website-link { background: linear-gradient(135deg, #667eea, #764ba2); }  

    /* CTA Button */  
    .cta-btn {  
        width: 100%;  
        padding: 18px;  
        background: linear-gradient(135deg, #667eea, #764ba2);  
        background-size: 200% 200%;  
        color: white;  
        border: none;  
        border-radius: 15px;  
        font-size: 17px;  
        font-weight: 700;  
        font-family: 'Outfit', sans-serif;  
        cursor: pointer;  
        position: relative;  
        overflow: hidden;  
        transition: all 0.35s ease;  
        box-shadow: 0 14px 25px rgba(102, 126, 234, 0.35);  
        transform: translateZ(25px);  
        animation: gradientShift 5s ease infinite;  
    }  

    @keyframes gradientShift {  
        0%, 100% { background-position: 0% 50%; }  
        50% { background-position: 100% 50%; }  
    }  

    .cta-btn:hover {  
        transform: translateZ(25px) scale(1.03) translateY(-3px);  
        box-shadow: 0 20px 35px rgba(102, 126, 234, 0.45);  
    }  

    .cta-btn:active {  
        transform: translateZ(25px) scale(0.98);  
    }  

    /* ================= Right Column ================= */  
    .details-section {  
        display: flex;  
        flex-direction: column;  
        gap: 30px;  
    }  

    .section-header {  
        font-size: 24px;  
        font-weight: 700;  
        color: #1a1a2e;  
        margin-bottom: 25px;  
        margin-top: 10px;  
        display: flex;  
        align-items: center;  
        gap: 12px;  
        transform: translateZ(15px);  
    }  

    .section-header i {  
        color: var(--accent-1);  
        background: rgba(102, 126, 234, 0.12);  
        padding: 12px;  
        border-radius: 14px;  
        box-shadow: inset 0 1px 3px rgba(255,255,255,0.6);  
    }  

    /* Stats Grid */  
    .stats-grid {  
        display: grid;  
        grid-template-columns: repeat(3, 1fr);  
        gap: 20px;  
    }  

    .stat-box {  
        background: rgba(255, 255, 255, 0.7);  
        backdrop-filter: blur(10px);  
        border: 1px solid rgba(255,255,255,0.8);  
        border-radius: 20px;  
        padding: 25px;  
        text-align: center;  
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);  
        position: relative;  
        overflow: hidden;  
        box-shadow: 0 8px 20px rgba(0,0,0,0.04);  
        transform-style: preserve-3d;  
    }  

    .stat-box:hover {  
        transform: translateY(-8px) translateZ(15px) rotateX(4deg);  
        border-color: var(--accent-1);  
        box-shadow: 0 20px 35px rgba(102, 126, 234, 0.18);  
    }  

    .stat-icon {  
        font-size: 34px;  
        color: var(--accent-1);  
        margin-bottom: 15px;  
        filter: drop-shadow(0 4px 8px rgba(102,126,234,0.3));  
    }  

    .stat-value {  
        font-size: 28px;  
        font-weight: 800;  
        color: #1a1a2e;  
        margin-bottom: 8px;  
    }  

    .stat-label {  
        font-size: 13px;  
        color: #666;  
        font-weight: 600;  
        text-transform: uppercase;  
        letter-spacing: 0.5px;  
    }  

    /* Vision Text */  
    .vision-text {  
        font-size: 16px;  
        line-height: 1.9;  
        color: #555;  
        transform: translateZ(10px);  
    }  

    .vision-text strong {  
        color: var(--accent-1);  
        font-weight: 700;  
    }  

    /* Expertise Tags */  
    .tag-container {  
        display: flex;  
        flex-wrap: wrap;  
        gap: 15px;  
    }  

    .skill-tag {  
        padding: 12px 20px;  
        background: rgba(255, 255, 255, 0.75);  
        border: 1px solid rgba(102, 126, 234, 0.18);  
        border-radius: 12px;  
        color: var(--accent-1);  
        font-size: 14px;  
        font-weight: 600;  
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);  
        cursor: pointer;  
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);  
    }  

    .skill-tag:hover {  
        background: linear-gradient(135deg, #667eea, #764ba2);  
        color: white;  
        transform: translateY(-4px) scale(1.06);  
        border-color: transparent;  
        box-shadow: 0 14px 28px rgba(102, 126, 234, 0.35);  
    }  

    /* ================= Responsive ================= */  
    @media (max-width: 1024px) {  
        .profile-grid {  
            grid-template-columns: 1fr;  
            gap: 30px;  
        }  

        .profile-main {  
            position: relative;  
            top: 0;  
        }  
    }  

    @media (max-width: 768px) {  
        .hero-section {  
            height: 300px;  
        }  
        .profile-wrapper {  
            margin-top: -80px;  
            padding: 0 20px;  
        }  
        .modern-card {  
            padding: 30px;  
        }  
        .stats-grid {  
            grid-template-columns: 1fr;  
            gap: 15px;  
        }  
        .hero-title {  
            font-size: 34px;  
        }  
    }  
</style>

</head>  
<body>  
    <div class="animated-bg"><div class="bg-blob"></div></div>  
    <div class="mesh-grid"></div>  <div class="particle" style="left: 10%; animation-delay: 0s;"></div>  
<div class="particle" style="left: 25%; animation-delay: 2s;"></div>  
<div class="particle" style="left: 45%; animation-delay: 4s;"></div>  
<div class="particle" style="left: 65%; animation-delay: 1s;"></div>  
<div class="particle" style="left: 85%; animation-delay: 3s;"></div>  

<div class="hero-section">  
    <div class="hero-content animate__animated animate__fadeInDown">  
        <h1 class="hero-title">Meet The Visionary</h1>  
        <p class="hero-subtitle">Transforming Ideas <span class="dot"></span> Into Digital Reality</p>  
    </div>  
</div>  

<div class="profile-wrapper">  
    <div class="profile-grid">  
          
        <div class="modern-card profile-main animate__animated animate__fadeInLeft" data-tilt>  
            <div class="sheen"></div>  
            <div class="avatar-section">  
                <div class="avatar-ring"></div>  
                <div class="avatar-inner">  
                    <img src="../assets/img/Mypic.jpeg" alt="Israr Liaqat" class="avatar-img">  
                </div>  
                <div class="verified-icon">  
                    <i class="fas fa-check"></i>  
                </div>  
            </div>  

            <h1 class="name-title">Israr Liaqat</h1>  
            <div class="role-badge">🚀 Founder & CEO</div>  
            <p class="bio-text">  
                Tech Enthusiast, Full Stack Developer & SMM Expert. Building digital empires and helping creators grow since 2020.  
            </p>  

            <div class="social-links">  
                <a href="https://wa.me/" class="social-icon whatsapp-link">  
                    <i class="fab fa-whatsapp"></i>  
                </a>  
                <a href="https://instagram.com/" class="social-icon instagram-link">  
                    <i class="fab fa-instagram"></i>  
                </a>  
                <a href="https://likexfollow.com" class="social-icon website-link">  
                    <i class="fas fa-globe"></i>  
                </a>  
            </div>  

            <button class="cta-btn">  
                <span><i class="fas fa-comment-dots"></i> Chat with Founder</span>  
            </button>  
        </div>  

        <div class="details-section">  
              
            <div class="modern-card animate__animated animate__fadeInRight" data-tilt>  
                <div class="sheen"></div>  
                <h2 class="section-header">  
                    <i class="fas fa-trophy"></i>  
                    Achievements  
                </h2>  
                <div class="stats-grid">  
                    <div class="stat-box">  
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>  
                        <div class="stat-value">5+</div>  
                        <div class="stat-label">Years Exp.</div>  
                    </div>  
                    <div class="stat-box">  
                        <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>  
                        <div class="stat-value">10K+</div>  
                        <div class="stat-label">Orders</div>  
                    </div>  
                    <div class="stat-box">  
                        <div class="stat-icon"><i class="fas fa-users"></i></div>  
                        <div class="stat-value">500+</div>  
                        <div class="stat-label">Happy Clients</div>  
                    </div>  
                </div>  
            </div>  

            <div class="modern-card animate__animated animate__fadeInRight" style="animation-delay: 0.2s;" data-tilt>  
                <div class="sheen"></div>  
                <h2 class="section-header">  
                    <i class="fas fa-lightbulb"></i>  
                    My Vision  
                </h2>  
                <p class="vision-text">  
                    My goal is to provide affordable, high-quality digital tools to everyone. Whether you are a creator or business owner, <strong>LikexFollow</strong> is built to accelerate your growth.  
                </p>  
            </div>  

            <div class="modern-card animate__animated animate__fadeInRight" style="animation-delay: 0.4s;" data-tilt>  
                <div class="sheen"></div>  
                <h2 class="section-header">  
                    <i class="fas fa-code"></i>  
                    Expertise  
                </h2>  
                <div class="tag-container">  
                    <span class="skill-tag"><i class="fas fa-chart-line"></i> SMM Growth</span>  
                    <span class="skill-tag"><i class="fas fa-laptop-code"></i> Web Development</span>  
                    <span class="skill-tag"><i class="fas fa-rocket"></i> LikexFollow Panel</span>  
                    <span class="skill-tag"><i class="fas fa-robot"></i> Automation</span>  
                    <span class="skill-tag"><i class="fas fa-palette"></i> Graphic Design</span>  
                    <span class="skill-tag"><i class="fas fa-briefcase"></i> Business Strategy</span>  
                </div>  
            </div>  

        </div>  
    </div>  
</div>  

<script type="text/javascript">  
    // Enhanced 3D Tilt effect (macOS-style depth)  
    document.querySelectorAll('[data-tilt]').forEach((element) => {  
        element.addEventListener('mousemove', (e) => {  
            const rect = element.getBoundingClientRect();  
            const x = e.clientX - rect.left;  
            const y = e.clientY - rect.top;  

            const centerX = rect.width / 2;  
            const centerY = rect.height / 2;  

            const rotateX = ((y - centerY) / centerY) * -6; // Max rotation 6deg  
            const rotateY = ((x - centerX) / centerX) * 6;  

            element.style.transform = `perspective(1200px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.015) translateY(-4px)`;  
        });  

        element.addEventListener('mouseleave', () => {  
            element.style.transform = 'perspective(1200px) rotateX(0) rotateY(0) scale(1) translateY(0)';  
        });  
    });  

    // Randomize particle horizontal drift + duration slightly for a more organic feel  
    document.querySelectorAll('.particle').forEach((p) => {  
        const duration = 9 + Math.random() * 6;  
        p.style.animationDuration = duration + 's';  
    });  
</script>

</body>  
</html>  <?php include '_footer.php'; ?>