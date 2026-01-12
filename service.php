<?php
// File: service.php
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Get Service ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Service Details + AI SEO Content
$stmt = $db->prepare("
    SELECT s.*, seo.page_title, seo.meta_desc, seo.ai_content 
    FROM smm_services s 
    LEFT JOIN service_seo seo ON s.id = seo.service_id 
    WHERE s.id = ? AND s.is_active = 1
");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    header("Location: services.php"); // Redirect if not found
    exit;
}

// Fallback SEO if AI hasn't run yet
$seoTitle = $service['page_title'] ?? $service['name'] . " - Best Price";
$seoDesc = $service['meta_desc'] ?? "Buy " . $service['name'] . " instantly at cheap rates.";
$seoContent = $service['ai_content'] ?? "<p>Get the best quality <strong>{$service['name']}</strong> with instant delivery. Our services are 100% safe and secure.</p>";

// Clean up Service Name for Display
$displayName = str_replace(['|', '🔥', '⚡', '✅', '⭐'], '', $service['name']);

ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();
// SEO Injection
$header_html = str_replace('<title>LikexFollow | The Crazy SMM Panel</title>', "<title>$seoTitle</title>", $header_html);
$header_html = str_replace('</head>', '<meta name="description" content="'.$seoDesc.'"></head>', $header_html);
echo $header_html;
?>

<div class="bg-slate-50 min-h-screen py-10 pt-24">
    <div class="max-w-6xl mx-auto px-4">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3 shadow-inner">
                            🛍️
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 leading-tight"><?= htmlspecialchars($displayName) ?></h2>
                        <div class="mt-2 inline-block bg-green-100 text-green-700 px-3 py-1 rounded-lg text-sm font-bold">
                            Rate: <?= formatCurrency($service['service_rate']) ?> / 1K
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-6">
                        <ul class="space-y-3 text-sm text-slate-600">
                            <li class="flex justify-between"><span>Minimum:</span> <span class="font-bold"><?= $service['min'] ?></span></li>
                            <li class="flex justify-between"><span>Maximum:</span> <span class="font-bold"><?= number_format($service['max']) ?></span></li>
                            <li class="flex justify-between"><span>Speed:</span> <span class="font-bold text-indigo-600">Super Fast ⚡</span></li>
                        </ul>
                    </div>

                    <a href="user/smm_order.php?service_id=<?= $service['id'] ?>" class="block w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-center rounded-xl shadow-lg shadow-indigo-500/30 transition-transform hover:-translate-y-1">
                        Buy Now 🚀
                    </a>
                    <p class="text-xs text-center text-slate-400 mt-4"><i class="fas fa-lock"></i> SSL Secured Payment</p>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 md:p-12">
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-6 leading-tight">
                        <?= $seoTitle ?>
                    </h1>

                    <div class="prose prose-lg prose-indigo max-w-none text-slate-600">
                        <?= $seoContent ?>
                    </div>

                    <div class="mt-12 pt-10 border-t border-slate-100">
                        <h3 class="text-2xl font-bold text-slate-800 mb-6">Frequently Asked Questions</h3>
                        
                        <div class="space-y-4">
                            <details class="group bg-slate-50 rounded-xl p-4 cursor-pointer">
                                <summary class="font-bold text-slate-700 list-none flex justify-between items-center">
                                    Is this service safe for my account?
                                    <span class="transition group-open:rotate-180">▼</span>
                                </summary>
                                <p class="text-slate-500 mt-3 text-sm leading-relaxed">
                                    Yes! We use 100% safe methods. Your account security is our top priority. We never ask for your password.
                                </p>
                            </details>

                            <details class="group bg-slate-50 rounded-xl p-4 cursor-pointer">
                                <summary class="font-bold text-slate-700 list-none flex justify-between items-center">
                                    How long does delivery take?
                                    <span class="transition group-open:rotate-180">▼</span>
                                </summary>
                                <p class="text-slate-500 mt-3 text-sm leading-relaxed">
                                    Most orders start instantly or within a few minutes. Completion time depends on the quantity ordered.
                                </p>
                            </details>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'user/_footer.php'; ?>