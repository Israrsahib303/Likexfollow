<?php
// File: panel/test_ai.php
include '_header.php';
require_once '../includes/AiEngine.php';

// --- 1. SYSTEM HEALTH CHECK (Database Tables) ---
$tables = ['ai_settings', 'blogs', 'service_seo', 'seo_logs'];
$health = [];
foreach ($tables as $table) {
    try {
        $check = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $health[$table] = ['status' => 'OK', 'count' => $check, 'class' => 'bg-green-100 text-green-700'];
    } catch (Exception $e) {
        $health[$table] = ['status' => 'MISSING', 'count' => 0, 'class' => 'bg-red-100 text-red-700'];
    }
}

// --- 2. AI CONNECTION TEST ---
$ai_response = '';
$ai_status = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prompt = trim($_POST['test_prompt']);
    if (!empty($prompt)) {
        $ai = new AiEngine($db);
        
        // Measure Time
        $start_time = microtime(true);
        $content = $ai->generateContent($prompt);
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);

        if (strpos($content, 'Error') !== false || strpos($content, 'Curl Error') !== false) {
            $ai_status = '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg font-bold">❌ Failed</span>';
            $ai_response = '<div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-800">'.$content.'</div>';
        } else {
            $ai_status = '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg font-bold">✅ Success ('.$duration.'s)</span>';
            $ai_response = '<div class="p-4 bg-green-50 border border-green-200 rounded-xl text-slate-700 prose max-w-none">'.$content.'</div>';
        }
        
        // Debug Data
        $debug_info = htmlspecialchars($content);
    }
}

// Fetch Active Brain Info
$activeSettings = $db->query("SELECT * FROM ai_settings WHERE is_active=1")->fetch();
?>

<div class="max-w-6xl mx-auto px-4 py-8">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">🧪 AI Diagnostics Lab</h1>
            <p class="text-slate-500">Test your API connection and check SEO system health.</p>
        </div>
        <a href="ai_manager.php" class="px-5 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition">
            ⚙️ Configure AI
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-8">
            
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">⚡ Live Connection Test</h3>
                    <?php if($activeSettings): ?>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">
                            Using: <?= htmlspecialchars($activeSettings['provider']) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-xs font-bold text-red-500">NO BRAIN CONNECTED</span>
                    <?php endif; ?>
                </div>
                
                <div class="p-6">
                    <form method="POST">
                        <label class="block text-sm font-bold text-slate-600 mb-2">Enter Test Prompt</label>
                        <div class="flex gap-3">
                            <input type="text" name="test_prompt" value="Write a short poem about SMM Panels" class="flex-1 px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none" required>
                            <button type="submit" class="px-6 py-3 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-900 transition">
                                Run Test 🚀
                            </button>
                        </div>
                    </form>

                    <?php if($ai_status): ?>
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-bold text-slate-500">Result:</span>
                            <?= $ai_status ?>
                        </div>
                        <?= $ai_response ?>
                        
                        <div class="mt-4">
                            <details class="text-xs text-slate-400 cursor-pointer">
                                <summary>View Raw Output</summary>
                                <pre class="mt-2 p-3 bg-slate-900 text-green-400 rounded-lg overflow-x-auto font-mono"><?= $debug_info ?></pre>
                            </details>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-heart-pulse text-red-500"></i> SEO System Health
                </h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full <?= $health['ai_settings']['class'] ?> flex items-center justify-center">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">AI Engine</p>
                                <p class="text-xs text-slate-400">Settings Table</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-bold rounded <?= $health['ai_settings']['class'] ?>">
                            <?= $health['ai_settings']['status'] ?>
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full <?= $health['blogs']['class'] ?> flex items-center justify-center">
                                <i class="fa-solid fa-newspaper"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">Articles</p>
                                <p class="text-xs text-slate-400"><?= $health['blogs']['count'] ?> Generated</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-bold rounded <?= $health['blogs']['class'] ?>">
                            <?= $health['blogs']['status'] ?>
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full <?= $health['service_seo']['class'] ?> flex items-center justify-center">
                                <i class="fa-solid fa-tags"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">Service SEO</p>
                                <p class="text-xs text-slate-400"><?= $health['service_seo']['count'] ?> Optimized</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-bold rounded <?= $health['service_seo']['class'] ?>">
                            <?= $health['service_seo']['status'] ?>
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full <?= $health['seo_logs']['class'] ?> flex items-center justify-center">
                                <i class="fa-solid fa-list"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">Activity Logs</p>
                                <p class="text-xs text-slate-400"><?= $health['seo_logs']['count'] ?> Entries</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-bold rounded <?= $health['seo_logs']['class'] ?>">
                            <?= $health['seo_logs']['status'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-indigo-900 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 bg-white/10 rounded-full blur-xl"></div>
                <h3 class="font-bold mb-2">🤖 Auto-Pilot Status</h3>
                <p class="text-xs text-indigo-200 mb-4">Ensure your cron jobs are running for automation.</p>
                <a href="cron_jobs.php" class="inline-block w-full text-center py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-bold transition">
                    Check Crons →
                </a>
            </div>

        </div>

    </div>
</div>

<?php include '_footer.php'; ?>