<?php
require_once __DIR__ . '/includes/helpers.php';

// 1. Security & Redirects
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/panel/index.php');
    } else {
        redirect(SITE_URL . '/user/index.php');
    }
    exit;
}

$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow';
$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <link rel="canonical" href="https://<?php echo $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>LikexFollow - Cheapest SMM Panel Pakistan & Digital Services</title>
    <meta property="og:site_name" content="LikexFollow">
<meta property="og:title" content="LikexFollow - #1 Cheapest SMM Panel Pakistan">
<meta property="og:description" content="Get instant Instagram followers, TikTok likes & YouTube views starting at Rs. 10. Join Pakistan's most trusted SMM reseller panel.">
<meta property="og:image" content="https://likexfollow.com/assets/img/site_logo_1764671832.png">
<meta property="og:url" content="https://likexfollow.com/">
<meta property="og:type" content="website">
<meta name="description" content="LikexFollow is Pakistan's #1 SMM Panel offering cheap Instagram followers, TikTok likes, YouTube views, and Netflix subscriptions. Instant delivery & wholesale rates.">
<meta name="keywords" content="likexfollow, likexfollow.com, like x follow, smm panel pakistan, cheapest smm panel, buy followers pakistan, digital store pakistan">
<meta name="author" content="LikexFollow Team">

<meta property="og:type" content="website">
<meta property="og:url" content="https://likexfollow.com/">
<meta property="og:title" content="LikexFollow - #1 SMM Panel & Digital Store">
<meta property="og:description" content="Boost your social media with LikexFollow. Get instant followers, likes, and premium subscriptions at wholesale prices.">
<meta property="og:image" content="https://likexfollow.com/assets/img/site_logo_1764671832.png">
    
    <link rel="shortcut icon" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="icon" type="image/jpeg" sizes="32x32" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="icon" type="image/jpeg" sizes="192x192" href="https://likexfollow.com/assets/img/favicon.jpg">
<link rel="apple-touch-icon" href="https://likexfollow.com/assets/img/favicon.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://api.fontshare.com/v2/css?f[]=clash-display@500,600,700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Clash Display', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#4338ca',
                        dark: '#0f172a',
                    },
                    animation: {
                        'float': 'floating 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        floating: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* --- CORE STYLES --- */
        body {
            background-color: #f8fafc;
            color: #0f172a;
            overflow-x: hidden; /* Header overflow fix */
            width: 100%;
        }
        
        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #4f46e5; }

        /* WhatsApp Button Pulse */
        .wa-pulse {
            animation: waPulse 2s infinite;
        }
        @keyframes waPulse {
            0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(37, 211, 102, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
        }

        /* 3D Card Effect */
        .card-tilt {
            transition: transform 0.1s ease;
            transform-style: preserve-3d;
        }
        
        /* Text Gradient */
        .text-gradient {
            background: linear-gradient(to right, #4f46e5, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Vertical Slider (Animation) */
        .vertical-slider { height: 50px; overflow: hidden; position: relative; }
        .slider-track { animation: scrollVertical 20s linear infinite; }
        .slider-item { 
            height: 50px; 
            display: flex; 
            align-items: center; 
            font-weight: 800; 
            white-space: nowrap; 
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem; /* Bigger Text */
        }
        .slider-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
            margin-right: 12px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
            border-radius: 6px;
        }

        @keyframes scrollVertical { 
            0% { transform: translateY(0); } 
            100% { transform: translateY(-50%); } 
        }

        /* Marquee Strip */
        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
        }
        .animate-scroll {
            animation: scroll 30s linear infinite;
        }
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
    </style>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://likexfollow.com/#organization",
      "name": "LikexFollow",
      "url": "https://likexfollow.com/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://likexfollow.com/assets/img/site_logo_1764671832.png",
        "width": 600,
        "height": 60
      },
      "sameAs": [
        "https://www.instagram.com/likexfollow",
        "https://www.facebook.com/likexfollow",
        "https://twitter.com/likexfollow"
      ],
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+92-3097856447", 
        "contactType": "customer support",
        "areaServed": ["PK", "US", "GB"],
        "availableLanguage": ["en", "ur"]
      }
    },
    {
      "@type": "WebSite",
      "@id": "https://likexfollow.com/#website",
      "url": "https://likexfollow.com/",
      "name": "LikexFollow",
      "alternateName": "LikexFollow SMM Panel",
      "description": "LikexFollow is the cheapest SMM Panel in Pakistan for Instagram followers, TikTok likes, and YouTube views.",
      "publisher": {
        "@id": "https://likexfollow.com/#organization"
      }
    }
  ]
}
</script>
</head>
<body class="antialiased selection:bg-indigo-500 selection:text-white">

    <div id="live-notification" class="fixed bottom-4 left-4 z-50 transform translate-x-[-150%] transition-transform duration-500">
        <div class="bg-white/95 backdrop-blur border border-indigo-100 p-4 rounded-xl shadow-2xl flex items-center gap-4 max-w-xs hover:scale-105 transition-transform cursor-pointer">
            <div class="relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center text-white shadow-lg">
                    <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                </div>
                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider" id="notify-time">Just now</p>
                <p class="text-sm font-bold text-slate-800 leading-tight"><span id="notify-name">Ali</span> purchased <br><span class="text-indigo-600" id="notify-service">1k Followers</span> 🔥</p>
            </div>
        </div>
    </div>

    <a href="https://wa.me/923097856447" target="_blank" class="fixed bottom-6 right-6 z-50 group">
        <div class="relative">
             <img src="assets/img/icons/Whatsapp.png" class="w-14 h-14 md:w-16 md:h-16 wa-pulse hover:scale-110 transition-transform duration-300" alt="Chat on WhatsApp">
            <span class="absolute right-full mr-4 top-1/2 -translate-y-1/2 bg-white px-3 py-1 rounded-lg text-sm font-bold shadow-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none text-slate-800">Support</span>
        </div>
    </a>

    <nav class="fixed top-0 w-full z-50 bg-white/90 backdrop-blur-lg border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <div class="flex-shrink-0 cursor-pointer" onclick="window.location.href='index.php'">
                    <img src="assets/img/logo.png" alt="LikexFollow" class="h-10 md:h-12 w-auto object-contain hover:scale-105 transition-transform">
                </div>

                <div class="flex items-center">
                    <a href="login.php" class="flex items-center gap-2 px-6 py-2.5 rounded-full bg-slate-900 text-white font-bold text-sm hover:bg-primary transition-all shadow-lg hover:shadow-indigo-500/30 transform hover:-translate-y-0.5">
                        <i data-lucide="user-circle" class="w-5 h-5"></i> Login Area
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                
                <div data-aos="fade-right">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-sm font-bold mb-6">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                        </span>
                        Father of All SMM Panels 👑
                    </div>
                    
                    <h1 class="font-display font-bold text-4xl lg:text-7xl leading-[1.1] text-slate-900 mb-4">
                        The World's Most <br>
                        <span class="text-gradient">Advanced Panel.</span>
                    </h1>
                    
                    <div class="h-[50px] overflow-hidden mb-6">
                        <div class="vertical-slider">
                            <div class="slider-track" id="hero-slider-list">
                                </div>
                        </div>
                    </div>
                    
                    <p class="text-lg text-slate-600 mb-8 max-w-lg leading-relaxed font-medium">
                        <strong>LikexFollow</strong> is a <strong>Direct Provider</strong>. We control the servers. Get <strong>Instant Followers</strong>, <strong>Non-Drop Likes</strong>, and <strong>Real Views</strong> at factory prices.
                    </p>
                    
                    <div class="flex flex-col gap-4 max-w-md">
                        <a href="register.php" onclick="confettiBlast()" class="px-8 py-4 rounded-xl bg-slate-900 text-white font-bold text-lg hover:bg-primary transition-all shadow-xl hover:shadow-indigo-500/25 flex items-center justify-center gap-2 w-full transform hover:-translate-y-1">
                            Create Account<i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </a>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <a href="services.php" class="px-4 py-3 rounded-xl border-2 border-indigo-100 bg-white text-indigo-700 font-bold text-center hover:border-indigo-600 hover:text-indigo-700 transition-all text-sm md:text-base flex items-center justify-center gap-2 shadow-sm hover:shadow-md">
                                <i data-lucide="list" class="w-4 h-4"></i> Check Services
                            </a>
                            
                            <a href="premium_store.php" class="px-4 py-3 rounded-xl bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-bold text-center shadow-lg hover:shadow-orange-500/30 transition-all flex items-center justify-center gap-2 text-sm md:text-base">
                                <i data-lucide="crown" class="w-4 h-4"></i> Premium Tools
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-8 grid grid-cols-2 gap-4 text-sm text-slate-500 font-medium">
                        <div class="flex items-center gap-2"><i data-lucide="shield-check" class="w-5 h-5 text-green-500"></i> Non-Drop Guarantee</div>
                        <div class="flex items-center gap-2"><i data-lucide="zap" class="w-5 h-5 text-green-500"></i> Instant Start (0-1 Min)</div>
                    </div>
                </div>

                <div class="relative h-[500px] w-full flex items-center justify-center perspective-[2000px]" data-aos="zoom-in" data-aos-duration="1000">
                    
                    <div class="absolute w-[90%] max-w-md bg-white/80 backdrop-blur-xl rounded-[2.5rem] p-8 shadow-[0_40px_80px_-20px_rgba(0,0,0,0.15)] border border-white z-20 animate-float card-tilt">
                        
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <p class="text-sm font-bold text-slate-400 uppercase tracking-wider">Total Orders</p>
                                <h3 class="text-3xl font-display font-bold text-slate-900">5.2 Million+</h3>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6"></i>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-purple-500 to-pink-500 text-white flex items-center justify-center">
                                    <i data-lucide="instagram" class="w-6 h-6"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-900">Instagram Followers</h4>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                                        <div class="bg-pink-500 h-1.5 rounded-full" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold text-green-600">Completed</span>
                                    <span class="text-xs text-slate-400">0.01s</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <div class="w-12 h-12 rounded-xl bg-black text-white flex items-center justify-center">
                                    <i data-lucide="music-2" class="w-6 h-6"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-900">TikTok Likes</h4>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                                        <div class="bg-black h-1.5 rounded-full" style="width: 90%"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold text-green-600">Active</span>
                                    <span class="text-xs text-slate-400">Instant</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="absolute top-10 -right-4 bg-white p-5 rounded-2xl shadow-xl border border-slate-100 z-30 animate-pulse-slow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                <i data-lucide="crown" class="w-6 h-6 fill-current"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 font-bold uppercase">Quality</p>
                                <p class="font-bold text-lg text-slate-900">Premium Only</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <div class="w-full bg-slate-900 overflow-hidden py-5 border-y border-indigo-500">
        <div class="flex w-[200%] animate-scroll">
            <div class="marquee-container flex items-center gap-12 whitespace-nowrap text-white/90 font-outfit font-bold text-xl uppercase tracking-widest px-6">
                <span>🔥 YouTube Monetization</span> <span class="text-indigo-500">•</span>
                <span>🚀 TikTok Viral</span> <span class="text-indigo-500">•</span>
                <span>💎 Instagram Verified</span> <span class="text-indigo-500">•</span>
                <span>🎨 Canva Pro</span> <span class="text-indigo-500">•</span>
                <span>🍿 Netflix Premium</span> <span class="text-indigo-500">•</span>
                <span>🤖 Chat GPT Plus</span> <span class="text-indigo-500">•</span>
                <span>🔥 YouTube Monetization</span> <span class="text-indigo-500">•</span>
                <span>🚀 TikTok Viral</span> <span class="text-indigo-500">•</span>
                <span>💎 Instagram Verified</span> <span class="text-indigo-500">•</span>
                <span>🎨 Canva Pro</span> <span class="text-indigo-500">•</span>
                <span>🤖 Gemini Pro</span> <span class="text-indigo-500">•</span>
                <span>🎬 CapCut Pro</span> <span class="text-indigo-500">•</span>
            </div>
        </div>
    </div>

    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16" data-aos="fade-up">
                <h2 class="font-display font-bold text-4xl text-slate-900 mb-4">Complete Social Dominance</h2>
                <p class="text-slate-600 text-lg max-w-2xl mx-auto">We cover every platform. One account, unlimited power.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <a href="services.php" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg transition-all hover:-translate-y-2 group text-center">
                    <div class="w-16 h-16 mx-auto bg-pink-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="instagram" class="w-8 h-8 text-pink-500"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-900">Instagram</h3>
                    <p class="text-sm text-slate-500 mt-2">Followers, Likes, Views</p>
                </a>
                
                <a href="services.php" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg transition-all hover:-translate-y-2 group text-center">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="music-2" class="w-8 h-8 text-black"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-900">TikTok</h3>
                    <p class="text-sm text-slate-500 mt-2">Likes, Followers, Viral Views</p>
                </a>
                
                <a href="services.php" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg transition-all hover:-translate-y-2 group text-center">
                    <div class="w-16 h-16 mx-auto bg-red-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="youtube" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-900">YouTube</h3>
                    <p class="text-sm text-slate-500 mt-2">Subscribers, Watchtime</p>
                </a>
                
                <a href="services.php" class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg transition-all hover:-translate-y-2 group text-center">
                    <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i data-lucide="facebook" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <h3 class="font-bold text-lg text-slate-900">Facebook</h3>
                    <p class="text-sm text-slate-500 mt-2">Page Likes, Followers</p>
                </a>
            </div>
            
            <div class="mt-12 text-center">
                <a href="services.php" class="inline-flex items-center gap-2 text-primary font-bold hover:underline">
                    View All 1000+ Services <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <div data-aos="fade-right">
                    <div class="inline-block px-3 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-full mb-4">MARKET LEADER</div>
                    <h2 class="font-display font-bold text-4xl text-slate-900 mb-6">Why We Are The <span class="text-primary">Father</span> of All Panels?</h2>
                    <p class="text-lg text-slate-600 mb-8">Most SMM panels are just "Resellers". They buy from us and sell to you at higher prices. <strong>LikexFollow is the Source.</strong> When you buy here, you cut the middleman.</p>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0"><i data-lucide="server" class="w-5 h-5"></i></div>
                            <div>
                                <h4 class="font-bold text-slate-900">Direct Server Access</h4>
                                <p class="text-sm text-slate-500">Orders start instantly because we control the API.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0"><i data-lucide="shield-check" class="w-5 h-5"></i></div>
                            <div>
                                <h4 class="font-bold text-slate-900">Safe & Secure</h4>
                                <p class="text-sm text-slate-500">We never ask for passwords. 100% safe for accounts.</p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 shrink-0"><i data-lucide="coins" class="w-5 h-5"></i></div>
                            <div>
                                <h4 class="font-bold text-slate-900">Wholesale Pricing</h4>
                                <p class="text-sm text-slate-500">Get rates starting from $0.001 per 1k.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative" data-aos="fade-left">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-3xl rotate-3 opacity-20"></div>
                    <div class="bg-slate-900 rounded-3xl p-8 relative shadow-2xl text-white overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                        
                        <h3 class="font-bold text-2xl mb-8 flex items-center gap-2 relative z-10"><i data-lucide="zap" class="text-yellow-400"></i> Speed Comparison</h3>
                        
                        <div class="space-y-8 relative z-10">
                            <div>
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span>LikexFollow API</span>
                                    <span class="text-green-400">0.05s (Instant)</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                                    <div class="bg-gradient-to-r from-green-400 to-green-600 h-4 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span class="text-slate-400">Other Panels (Resellers)</span>
                                    <span class="text-red-400">Delayed</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                                    <div class="bg-red-500 h-4 rounded-full" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-10 pt-8 border-t border-slate-700 relative z-10">
                            <p class="text-slate-300 italic">"I switched from another panel and my sales doubled because orders actually start instantly here."</p>
                            <div class="flex items-center gap-3 mt-4">
                                <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold">TR</div>
                                <p class="text-sm font-bold text-white">Top Reseller Review</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white pt-20 pb-10 border-t border-slate-200">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h2 class="font-display font-bold text-4xl mb-6">Ready to dominate?</h2>
            <p class="text-slate-500 mb-8">Join the elite club of successful influencers and agencies.</p>
            
            <div class="flex justify-center gap-4 mb-12">
                 <button onclick="window.location.href='register.php'" class="px-10 py-4 bg-slate-900 text-white font-bold rounded-full hover:bg-primary transition-all shadow-xl hover:-translate-y-1">Create Free Account</button>
            </div>
            
            <div class="flex flex-wrap justify-center gap-8 text-sm text-slate-500 font-medium mb-8">
                <a href="services.php" class="hover:text-primary transition-colors">Services</a>
                <a href="premium_store.php" class="hover:text-primary transition-colors">Premium Store</a>
                <a href="api_docs.php" class="hover:text-primary transition-colors">API Docs</a>
                <a href="terms.php" class="hover:text-primary transition-colors">Terms</a>
                <a href="blog.php" class="hover:text-primary transition-colors">Blog</a>
            </div>

            <p class="text-slate-400 mt-10 text-sm">© <?php echo date("Y"); ?> LikexFollow. Made for Winners.</p>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, offset: 50, duration: 800 });
        lucide.createIcons();

        // --- DATA WITH ICONS & COLORS ---
        // Format: [Text, IconFilename, ColorHex]
        const sliderData = [
            ["Buy Instagram Followers", "Instagram.png", "#E1306C"],
            ["Buy TikTok Likes", "TikTok.png", "#000000"],
            ["Buy YouTube Views", "Youtube.png", "#FF0000"],
            ["Buy Facebook Followers", "Facebook.png", "#1877F2"],
            ["Cheap SMM Panel", "chatgpt-plus-6910e1072ea3c.png", "#4f46e5"], // Specific icon for this
            ["Instant Delivery", "chatgpt-plus-6910e1072ea3c.png", "#10b981"], // Specific icon for this
            ["Buy ChatGPT Plus", "chatgpt-plus-69710e3c51486.png", "#10a37f"],
            ["Buy Gemini Pro", "veo-3-flow-unlimited-videos-69710bfcb3d84.png", "#4E8CF7"],
            ["Buy Veo 3 AI", "veo-3-flow-unlimited-videos-69710bfcb3d84.png", "#6366f1"],
            ["Buy Canva Pro", "canva-pro-69710e32556d5.png", "#00C4CC"],
            ["Buy CapCut Pro", "capcut-pro---private-account-697104a8c509e.png", "#000000"],
            ["Buy Netflix Premium", "net-flix-ultra-4k-screens-69126007908d3.jpeg", "#E50914"],
            ["Buy Spotify Plays", "Spotify.png", "#1DB954"],
            ["Buy Twitter Likes", "Twitter.png", "#1DA1F2"],
            ["Buy Telegram Members", "Telegram.png", "#229ED9"],
            ["Buy Snapchat Followers", "Snapchat.png", "#FFFC00"],
            ["Buy WhatsApp Channel Members", "Whatsapp.png", "#25D366"]
        ];

        // --- POPULATE SLIDER ---
        const sliderList = document.getElementById('hero-slider-list');
        const iconPath = "assets/img/icons/";

        // Function to create item
        function createItem(item) {
            const div = document.createElement('div');
            div.className = "slider-item mr-8";
            
            // Check if icon name is full path or just name
            let imgSrc = iconPath + item[1];
            
            div.innerHTML = `
                <img src="${imgSrc}" class="slider-icon" onerror="this.src='${iconPath}smm.png'"> 
                <span style="color:${item[2]}">${item[0]}</span>
            `;
            return div;
        }

        // Add items twice for infinite loop
        [...sliderData, ...sliderData].forEach(item => {
            sliderList.appendChild(createItem(item));
        });
        
        lucide.createIcons(); 

        // Confetti Blast on Register Button
        function confettiBlast() {
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#4f46e5', '#ec4899', '#eab308']
            });
        }

        // Smooth Card Tilt
        const tiltCard = document.querySelector('.card-tilt');
        if(tiltCard) {
            tiltCard.addEventListener('mousemove', (e) => {
                const rect = tiltCard.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const xRot = -((y - rect.height/2) / 25); 
                const yRot = (x - rect.width/2) / 25;
                tiltCard.style.transform = `perspective(1000px) rotateX(${xRot}deg) rotateY(${yRot}deg) scale(1.02)`;
            });
            tiltCard.addEventListener('mouseleave', () => {
                tiltCard.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
            });
        }

        // Live Notifications Simulation
        const nNames = ["Ali", "Sarah", "John", "Mike", "David", "Ahmed", "Zara", "Bilal", "Usman", "Ayesha"];
        const nServices = ["1k Followers", "Canva Pro", "Watchtime", "5k Views", "Netflix 1M", "Blue Tick Service"];
        
        function loopNotify() {
            setTimeout(() => {
                const toast = document.getElementById('live-notification');
                if(toast) {
                    document.getElementById('notify-name').innerText = nNames[Math.floor(Math.random()*nNames.length)];
                    document.getElementById('notify-service').innerText = nServices[Math.floor(Math.random()*nServices.length)];
                    
                    toast.classList.remove('translate-x-[-150%]'); // Show
                    setTimeout(() => toast.classList.add('translate-x-[-150%]'), 4000); // Hide
                }
                loopNotify();
            }, Math.random() * 8000 + 4000);
        }
        setTimeout(loopNotify, 3000);

    </script>
</body>
</html>
