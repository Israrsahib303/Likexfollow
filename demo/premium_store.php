<?php
// --- PREMIUM STORE ENGINE v2.4 (Smart UI & Pricing Layout) ---

// 1. Database Connection Safety Check
if (!isset($db)) {
    if (file_exists('includes/db.php')) {
        require_once 'includes/db.php';
    } elseif (file_exists('../includes/db.php')) {
        require_once '../includes/db.php';
    }
}

// 2. Fetch Products with Variations & Duration Logic
$products = [];
try {
    if (isset($db)) {
        // Fetches Price AND Duration of the cheapest active variation
        $sql = "SELECT p.*, 
                (SELECT MIN(price) FROM product_variations WHERE product_id = p.id AND is_active = 1) as var_min_price,
                (SELECT MAX(original_price) FROM product_variations WHERE product_id = p.id AND is_active = 1) as var_orig_price,
                (SELECT duration_months FROM product_variations WHERE product_id = p.id AND is_active = 1 ORDER BY price ASC LIMIT 1) as var_duration
                FROM products p 
                WHERE p.is_active = 1 
                ORDER BY p.id DESC";
        $stmt = $db->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Silent Error
}

// 3. Admin Settings
$admin_whatsapp = '923097856447'; 
$wa_clean = preg_replace('/[^0-9]/', '', $admin_whatsapp);
$base_currency_rate = $GLOBALS['settings']['currency_conversion_rate'] ?? 280; // PKR to USD base

// 4. Site Logo Logic
$site_logo = $GLOBALS['settings']['site_logo'] ?? 'logo.png';
$logo_path = 'assets/img/' . $site_logo;
if (!file_exists($logo_path)) {
    $logo_path = 'assets/img/logo.png';
}

// 5. Currency Configuration
$currencies = [
    'PKR' => ['rate' => 1, 'symbol' => '₨', 'flag' => '🇵🇰', 'name' => 'PKR'],
    'USD' => ['rate' => 1 / $base_currency_rate, 'symbol' => '$', 'flag' => '🇺🇸', 'name' => 'USD'],
    'EUR' => ['rate' => (1 / $base_currency_rate) * 0.92, 'symbol' => '€', 'flag' => '🇪🇺', 'name' => 'EUR'],
    'GBP' => ['rate' => (1 / $base_currency_rate) * 0.79, 'symbol' => '£', 'flag' => '🇬🇧', 'name' => 'GBP'],
    'AED' => ['rate' => (1 / $base_currency_rate) * 3.67, 'symbol' => 'AED', 'flag' => '🇦🇪', 'name' => 'AED'],
    'SAR' => ['rate' => (1 / $base_currency_rate) * 3.75, 'symbol' => 'SAR', 'flag' => '🇸🇦', 'name' => 'SAR'],
    'INR' => ['rate' => (1 / $base_currency_rate) * 83.50, 'symbol' => '₹', 'flag' => '🇮🇳', 'name' => 'INR'],
];
$currencies_json = json_encode($currencies);

// 6. Dynamic Color Themes
$color_themes = [
    0 => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'text' => 'text-blue-600', 'btn' => 'bg-blue-600', 'shadow' => 'shadow-blue-200'],
    1 => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'text' => 'text-emerald-600', 'btn' => 'bg-emerald-600', 'shadow' => 'shadow-emerald-200'],
    2 => ['bg' => 'bg-purple-50', 'border' => 'border-purple-100', 'text' => 'text-purple-600', 'btn' => 'bg-purple-600', 'shadow' => 'shadow-purple-200'],
    3 => ['bg' => 'bg-rose-50', 'border' => 'border-rose-100', 'text' => 'text-rose-600', 'btn' => 'bg-rose-600', 'shadow' => 'shadow-rose-200'],
    4 => ['bg' => 'bg-amber-50', 'border' => 'border-amber-100', 'text' => 'text-amber-600', 'btn' => 'bg-amber-600', 'shadow' => 'shadow-amber-200'],
    5 => ['bg' => 'bg-cyan-50', 'border' => 'border-cyan-100', 'text' => 'text-cyan-600', 'btn' => 'bg-cyan-600', 'shadow' => 'shadow-cyan-200'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Smart Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    animation: {
                        'float-icon': 'floatIcon 4s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        floatIcon: {
                            '0%, 100%': { transform: 'translateY(0) scale(1)' },
                            '50%': { transform: 'translateY(-6px) scale(1.05)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #f3f4f6;
            overflow-x: hidden;
        }

        .smart-card {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .smart-card:hover {
            transform: translateY(-8px);
        }

        .line-clamp-custom {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Custom Scroll */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c084fc; border-radius: 10px; }
    </style>
</head>
<body class="antialiased bg-slate-50">

<section id="premium-store" class="relative py-12 min-h-screen">
    
    <div class="fixed inset-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-200/50 rounded-full blur-[100px] opacity-60"></div>
        <div class="absolute top-40 -left-20 w-80 h-80 bg-blue-200/50 rounded-full blur-[100px] opacity-60"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10 max-w-7xl">

        <div class="flex flex-col items-center justify-center mb-10 text-center">
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="h-20 w-auto object-contain mb-4 animate-float-icon drop-shadow-md">
            
            <h2 class="text-4xl md:text-5xl font-black text-slate-800 tracking-tight">
                Premium <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-indigo-600">Accounts</span>
            </h2>
            <p class="text-slate-500 font-medium mt-2 text-sm md:text-base">Best Prices • Instant Delivery • Full Warranty</p>
        </div>

        <div class="flex flex-row justify-between items-center mb-8 gap-4 px-2">
            
            <div class="flex items-center gap-2 bg-white/80 backdrop-blur-md border border-slate-200 px-4 py-2 rounded-full shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                </span>
                <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Live Store</span>
            </div>

            <button onclick="toggleCurrencyModal()" class="group relative flex items-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 pl-3 pr-4 py-2 rounded-full shadow-sm transition-all hover:shadow-md hover:border-purple-300">
                <div class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-white animate-pulse"></div>
                <span class="text-xl" id="current-flag">🇵🇰</span>
                <div class="flex flex-col items-start leading-none">
                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wide">Currency</span>
                    <span class="text-xs font-black text-slate-800" id="current-code">PKR</span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400 group-hover:text-purple-500 transition-colors ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 justify-items-center">
            
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $index => $product): ?>
                    <?php 
                        // Pricing Logic
                        $final_price = ($product['var_min_price'] > 0) ? $product['var_min_price'] : $product['price'];
                        
                        // Fake Original Price
                        if ($product['var_orig_price'] > $final_price) {
                            $orig_price = $product['var_orig_price'];
                        } elseif ($product['original_price'] > $final_price) {
                            $orig_price = $product['original_price'];
                        } else {
                            $orig_price = $final_price * 1.3; 
                        }

                        // Icon Check
                        $icon_file = 'assets/img/icons/' . $product['icon'];
                        if (!file_exists($icon_file) || empty($product['icon'])) { 
                            $icon_file = 'assets/img/logo.png'; 
                        }

                        // Duration Logic
                        $duration_label = "Lifetime"; 
                        $months = $product['var_duration'] ?? 0;
                        if ($months > 0) {
                            if ($months == 1) { $duration_label = "1 Month"; }
                            elseif ($months == 12) { $duration_label = "1 Year"; }
                            else { $duration_label = $months . " Months"; }
                        }

                        // Theme Logic
                        $theme_id = $index % count($color_themes);
                        $theme = $color_themes[$theme_id];
                    ?>
                    
                    <div class="smart-card relative w-full max-w-[19rem] bg-white/90 rounded-[24px] border border-slate-100 p-5 flex flex-col hover:shadow-2xl hover:shadow-purple-100/50">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div class="h-[70px] w-[70px] <?= $theme['bg'] ?> rounded-2xl p-3.5 border border-white shadow-sm flex items-center justify-center relative">
                                <img src="<?= htmlspecialchars($icon_file) ?>" 
                                     alt="icon" 
                                     class="w-full h-full object-contain animate-float-icon drop-shadow-sm">
                            </div>
                            
                            <div class="flex items-center gap-1.5 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full">
                                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wide">Stock</span>
                            </div>
                        </div>

                        <div class="mb-4 min-h-[80px]">
                            <h3 class="text-lg font-extrabold text-slate-800 leading-snug mb-1.5 line-clamp-1" title="<?= htmlspecialchars($product['name']) ?>">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            
                            <div class="relative">
                                <p id="desc-<?= $product['id'] ?>" class="text-xs text-slate-500 font-medium leading-relaxed line-clamp-custom transition-all duration-300">
                                    <?= htmlspecialchars(strip_tags($product['description'])) ?>
                                </p>
                                <button onclick="toggleDescription(<?= $product['id'] ?>)" 
                                        id="btn-<?= $product['id'] ?>"
                                        class="text-[10px] font-bold <?= $theme['text'] ?> hover:underline mt-1 focus:outline-none">
                                    Read More
                                </button>
                            </div>
                        </div>

                        <div class="h-px w-full bg-gradient-to-r from-transparent via-slate-200 to-transparent mb-4"></div>

                        <div class="mt-auto">
                            
                            <div class="flex items-center gap-1 mb-0.5">
                                <span class="text-[10px] font-bold text-slate-400">Original:</span>
                                <span class="text-[10px] font-bold text-slate-400 line-through price-strike" data-base="<?= $orig_price ?>">
                                    ₨ <?= number_format($orig_price) ?>
                                </span>
                            </div>

                            <div class="flex justify-between items-center mb-4">
                                <span class="text-2xl font-black <?= $theme['text'] ?> price-main" data-base="<?= $final_price ?>">
                                    ₨ <?= number_format($final_price) ?>
                                </span>
                                
                                <div class="flex items-center gap-1 bg-purple-600 text-white px-2.5 py-1 rounded-lg shadow-sm shadow-purple-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-[10px] font-bold uppercase tracking-wide"><?= $duration_label ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <a href="https://wa.me/<?= $wa_clean ?>?text=<?= urlencode("Hi Admin! I want to buy *" . $product['name'] . "* (" . $duration_label . ") Price: " . $final_price) ?>" 
                                   target="_blank"
                                   class="h-11 w-14 bg-green-50 hover:bg-green-500 border border-green-200 rounded-xl flex items-center justify-center transition-all group shrink-0">
                                    <img src="assets/img/icons/Whatsapp.png" class="w-6 h-6 group-hover:brightness-0 group-hover:invert">
                                </a>

                                <a href="login.php" class="h-11 w-full <?= $theme['btn'] ?> hover:opacity-90 text-white rounded-xl flex items-center justify-center text-sm font-bold shadow-lg shadow-slate-200 hover:shadow-xl transition-all">
                                    Buy Now
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center text-slate-400">
                    <div class="text-6xl mb-4 grayscale opacity-50">🛍️</div>
                    <p class="font-bold text-lg">Store Empty</p>
                    <p class="text-xs">Check back later</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div id="currencyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="toggleCurrencyModal()"></div>
        <div class="bg-white rounded-3xl w-full max-w-xs relative z-10 shadow-2xl p-0 transform transition-all scale-95 opacity-0 overflow-hidden" id="modalPanel">
            
            <div class="bg-slate-50 px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-lg">Select Currency</h3>
                <button onclick="toggleCurrencyModal()" class="h-8 w-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-red-500 hover:border-red-200 transition-all">✕</button>
            </div>
            
            <div class="p-3 grid gap-2 max-h-[60vh] overflow-y-auto custom-scroll" id="currencyList">
                </div>
        </div>
    </div>

</section>

<script>
    const rates = <?= $currencies_json ?>;
    let selectedCurrency = 'PKR';

    // 1. Initialize
    document.addEventListener('DOMContentLoaded', () => {
        renderCurrencyList();
        updatePrices('PKR');
    });

    // 2. Toggle Modal
    function toggleCurrencyModal() {
        const modal = document.getElementById('currencyModal');
        const panel = document.getElementById('modalPanel');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            setTimeout(() => { panel.classList.remove('scale-95', 'opacity-0'); panel.classList.add('scale-100', 'opacity-100'); }, 10);
        } else {
            panel.classList.remove('scale-100', 'opacity-100'); panel.classList.add('scale-95', 'opacity-0');
            setTimeout(() => { modal.classList.add('hidden'); }, 200);
        }
    }

    // 3. Render List
    function renderCurrencyList() {
        const list = document.getElementById('currencyList');
        let html = '';
        for (const [code, data] of Object.entries(rates)) {
            const active = code === selectedCurrency ? 'bg-purple-50 border-purple-200 ring-1 ring-purple-100' : 'hover:bg-slate-50 border-slate-100';
            const check = code === selectedCurrency ? '<span class="text-purple-600 text-lg">●</span>' : '';
            
            html += `<button onclick="selectCurrency('${code}')" class="flex items-center justify-between p-3 rounded-xl border w-full ${active} transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">${data.flag}</span>
                            <div class="text-left">
                                <p class="text-sm font-bold text-slate-700">${code}</p>
                                <p class="text-[10px] text-slate-400 font-medium group-hover:text-purple-500 transition-colors">${data.name}</p>
                            </div>
                        </div>
                        ${check}
                     </button>`;
        }
        list.innerHTML = html;
    }

    // 4. Select Currency
    function selectCurrency(code) {
        selectedCurrency = code;
        document.getElementById('current-flag').innerText = rates[code].flag;
        document.getElementById('current-code').innerText = code;
        updatePrices(code);
        renderCurrencyList();
        toggleCurrencyModal();
    }

    // 5. Update Prices
    function updatePrices(code) {
        const data = rates[code];
        
        // Main Price
        document.querySelectorAll('.price-main').forEach(el => {
            el.innerText = `${data.symbol} ${formatMoney(el.dataset.base * data.rate)}`;
        });
        
        // Strike Price
        document.querySelectorAll('.price-strike').forEach(el => {
            el.innerText = `${data.symbol} ${formatMoney(el.dataset.base * data.rate)}`;
        });
    }

    // 6. Formatter
    function formatMoney(amount) {
        return amount < 10 ? amount.toFixed(2) : (amount < 100 ? amount.toFixed(1) : Math.round(amount).toLocaleString());
    }

    // 7. Toggle Description
    function toggleDescription(id) {
        const desc = document.getElementById(`desc-${id}`);
        const btn = document.getElementById(`btn-${id}`);
        
        if (desc.classList.contains('line-clamp-custom')) {
            desc.classList.remove('line-clamp-custom');
            btn.innerText = "Show Less";
        } else {
            desc.classList.add('line-clamp-custom');
            btn.innerText = "Read More";
        }
    }
</script>

</body>
</html>
