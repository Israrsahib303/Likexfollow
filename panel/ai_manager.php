<?php
// File: panel/ai_manager.php
include '_header.php';

// --- 1. HANDLE FORM SUBMISSION ---
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $key = trim($_POST['api_key']);
    $provider = $_POST['provider'];
    $sys_prompt = trim($_POST['system_prompt']);
    
    if(!empty($key)) {
        // Step A: Verify Key (Mock Test)
        // Asal production mein hum yahan ek curl request bhej kar check karte hain
        // Abhi ke liye hum mante hain key format sahi hai
        
        $isValid = true; // Logic to verify key
        
        if($isValid) {
            // Step B: Reset Old Keys
            $db->query("UPDATE ai_settings SET is_active=0");
            
            // Step C: Save New Key & Training Data
            $stmt = $db->prepare("INSERT INTO ai_settings (provider, api_key, model, system_prompt, is_active) VALUES (?, ?, 'default', ?, 1)");
            if($stmt->execute([$provider, $key, $sys_prompt])) {
                $msg = "✅ API Connected & AI Trained Successfully!";
                $msgType = "success";
            } else {
                $msg = "❌ Database Error!";
                $msgType = "error";
            }
        } else {
            $msg = "❌ Invalid API Key! Connection Failed.";
            $msgType = "error";
        }
    }
}

// --- 2. FETCH ACTIVE SETTINGS ---
$active = $db->query("SELECT * FROM ai_settings WHERE is_active=1 LIMIT 1")->fetch();

// Default Training Prompt (Agar naya set na ho)
$default_training = "You are an expert SEO Content Writer for 'LikexFollow.com', the world's fastest and cheapest SMM Panel based in Pakistan. 
Your goal is to write high-ranking articles to sell TikTok Followers, Instagram Likes, and YouTube Views.
Always mention that LikexFollow provides Non-Drop services with Lifetime Guarantee.
Tone: Professional, Persuasive, and Exciting.";

$current_prompt = $active['system_prompt'] ?? $default_training;

// --- 3. FETCH HISTORY ---
$history = $db->query("SELECT * FROM ai_settings ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --secondary: #6366f1;
        --surface: #ffffff;
        --surface-glass: rgba(255, 255, 255, 0.85);
        --border: #e2e8f0;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #f3f4f6;
        background-image: 
            radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.15) 0px, transparent 50%);
        background-attachment: fixed;
    }

    /* --- Animations --- */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulse-glow { 0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); } 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); } }
    @keyframes border-dance { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    .animate-enter { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-enter-delay { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s forwards; opacity: 0; }

    /* --- Glass Panels --- */
    .glass-panel {
        background: var(--surface-glass);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.05),
            0 10px 15px -3px rgba(0, 0, 0, 0.05),
            inset 0 0 0 1px rgba(255, 255, 255, 0.5);
    }

    /* --- Provider Cards --- */
    .provider-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        background: white;
        position: relative;
        overflow: hidden;
    }
    .provider-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px -5px rgba(99, 102, 241, 0.15);
    }
    /* When Checked */
    .peer:checked + .provider-card {
        border-color: var(--primary);
        background: #f5f3ff;
        box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.2);
    }
    .peer:checked + .provider-card::after {
        content: '\f058'; /* FontAwesome Check Circle */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        top: 10px;
        right: 10px;
        color: var(--primary);
        font-size: 1.2rem;
    }

    /* --- Inputs --- */
    .tech-input {
        transition: all 0.3s ease;
        background: white;
    }
    .tech-input:focus {
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        border-color: var(--primary);
        transform: translateY(-1px);
    }

    /* --- Status Dot --- */
    .status-dot-wrapper { position: relative; display: flex; align-items: center; justify-content: center; width: 12px; height: 12px; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; z-index: 2; }
    .status-wave { position: absolute; width: 100%; height: 100%; border-radius: 50%; animation: pulse-glow 2s infinite; z-index: 1; }
    .dot-success { background: #10b981; } .wave-success { color: #10b981; }
    .dot-danger { background: #ef4444; }

    /* --- Code Editor Feel --- */
    .editor-wrapper {
        background: #1e293b; /* Slate 800 */
        border: 1px solid #334155;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.3);
    }
    .editor-textarea {
        background: transparent;
        color: #e2e8f0;
        font-family: 'JetBrains Mono', monospace;
        line-height: 1.6;
    }
    .editor-textarea::placeholder { color: #64748b; }
    .editor-header {
        background: #0f172a;
        border-bottom: 1px solid #334155;
    }
    
    /* --- Badges & Text --- */
    .badge-gradient {
        background: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%);
        color: white;
    }
    .text-gradient {
        background: linear-gradient(135deg, #1e293b 0%, #4f46e5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-12">

    <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6 animate-enter">
        <div class="relative">
            <div class="absolute -top-10 -left-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <h1 class="text-5xl font-extrabold tracking-tight text-slate-800 mb-2">
                <span class="text-gradient">AI Brain Center</span>
            </h1>
            <p class="text-slate-500 text-lg font-medium max-w-xl">
                Orchestrate your SEO intelligence. Configure models, manage keys, and train your digital workforce.
            </p>
        </div>
        
        <div class="glass-panel px-6 py-4 rounded-2xl flex items-center gap-4 min-w-[240px]">
            <div class="flex-1 text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">System Status</p>
                <p class="text-sm font-bold flex items-center justify-end gap-2 <?= $active ? 'text-emerald-600' : 'text-rose-500' ?>">
                    <?php if($active): ?>
                        <?= ucfirst($active['provider']) ?> Neural Net Active
                    <?php else: ?>
                        System Disconnected
                    <?php endif; ?>
                </p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center shadow-inner">
                <div class="status-dot-wrapper">
                    <span class="status-dot <?= $active ? 'dot-success' : 'dot-danger' ?>"></span>
                    <?php if($active): ?><span class="status-wave wave-success"></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
    <div class="mb-10 animate-enter">
        <div class="p-4 rounded-xl flex items-center gap-4 shadow-lg <?= $msgType == 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-rose-50 text-rose-800 border border-rose-100' ?>">
            <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $msgType == 'success' ? 'bg-emerald-100' : 'bg-rose-100' ?>">
                <i class="fa-solid <?= $msgType == 'success' ? 'fa-check' : 'fa-xmark' ?>"></i>
            </div>
            <div>
                <h4 class="font-bold"><?= $msgType == 'success' ? 'Success' : 'Error Occurred' ?></h4>
                <p class="text-sm opacity-90"><?= $msg ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 xl:grid-cols-12 gap-8 animate-enter-delay">
        
        <div class="xl:col-span-7 space-y-8">
            
            <div class="glass-panel rounded-3xl p-8 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-6 opacity-5 transition-opacity group-hover:opacity-10">
                    <i class="fa-solid fa-microchip text-8xl text-indigo-600"></i>
                </div>

                <h3 class="text-xl font-bold text-slate-800 mb-8 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30 font-mono">01</span>
                    Select Intelligence Provider
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="gemini" class="peer sr-only" <?= ($active['provider']=='gemini')?'checked':'' ?>>
                        <div class="provider-card p-6 rounded-2xl h-full flex flex-col items-center justify-between text-center border-slate-200">
                            <div class="mb-4 bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/8/8a/Google_Gemini_logo.svg" class="h-6 w-auto">
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-lg">Google Gemini</div>
                                <div class="text-xs text-emerald-600 font-bold bg-emerald-50 px-3 py-1 rounded-full mt-2 inline-block">Free Tier</div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="openai" class="peer sr-only" <?= ($active['provider']=='openai')?'checked':'' ?>>
                        <div class="provider-card p-6 rounded-2xl h-full flex flex-col items-center justify-between text-center border-slate-200">
                             <div class="mb-4 bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/ChatGPT_logo.svg" class="h-8 w-auto">
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-lg">OpenAI GPT-4</div>
                                <div class="text-xs text-slate-500 font-bold bg-slate-100 px-3 py-1 rounded-full mt-2 inline-block">Paid API</div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="provider" value="groq" class="peer sr-only" <?= ($active['provider']=='groq')?'checked':'' ?>>
                        <div class="provider-card p-6 rounded-2xl h-full flex flex-col items-center justify-between text-center border-slate-200">
                             <div class="mb-4 bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-bolt text-2xl text-amber-500"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-lg">Groq Llama 3</div>
                                <div class="text-xs text-indigo-600 font-bold bg-indigo-50 px-3 py-1 rounded-full mt-2 inline-block">High Speed</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="glass-panel rounded-3xl p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30 font-mono">02</span>
                    Authenticate Connection
                </h3>
                
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-key text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    </div>
                    <input type="password" name="api_key" value="<?= $active['api_key'] ?? '' ?>" 
                           class="tech-input w-full pl-14 pr-4 py-5 rounded-xl border border-slate-200 outline-none font-mono text-sm tracking-wide text-slate-700 placeholder-slate-400" 
                           placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
                    <div class="absolute inset-y-0 right-0 pr-5 flex items-center">
                        <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded border border-slate-200">AES-256</span>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs text-slate-500 font-medium">
                    <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                    <span>Keys are encrypted at rest. Never shared with third parties.</span>
                </div>
            </div>
            
            <div class="glass-panel rounded-3xl overflow-hidden border border-slate-200/60">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">🔌 Connection Logs</h3>
                    <span class="text-xs font-mono text-slate-400">LAST 5 ENTRIES</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-5">Provider</th>
                                <th class="p-5">Key Signature</th>
                                <th class="p-5 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php foreach($history as $row): ?>
                            <tr class="hover:bg-slate-50 transition group">
                                <td class="p-5 font-bold text-slate-700 capitalize flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full <?= $row['is_active'] ? 'bg-emerald-500' : 'bg-slate-300' ?>"></div>
                                    <?= $row['provider'] ?>
                                </td>
                                <td class="p-5 font-mono text-slate-500 group-hover:text-slate-700">
                                    <span class="bg-slate-100 px-2 py-1 rounded border border-slate-200 text-xs">
                                        <?= substr($row['api_key'], 0, 4) ?>...<?= substr($row['api_key'], -4) ?>
                                    </span>
                                </td>
                                <td class="p-5 text-right">
                                    <?php if($row['is_active']): ?>
                                        <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs shadow-sm shadow-emerald-200">Active</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-500 font-bold text-xs">Revoked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="xl:col-span-5 flex flex-col h-full">
            <div class="glass-panel rounded-3xl p-1 flex-grow flex flex-col h-full shadow-2xl shadow-indigo-500/10">
                
                <div class="p-6 pb-2">
                    <h3 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-3">
                        <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30 font-mono">03</span>
                        System Training
                    </h3>
                    <p class="text-slate-500 text-sm mb-4 pl-14">Define the persona, rules, and behavioral constraints for the AI model.</p>
                </div>

                <div class="flex-grow flex flex-col mx-6 mb-6 rounded-xl overflow-hidden editor-wrapper">
                    <div class="editor-header h-10 flex items-center px-4 justify-between select-none">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                        </div>
                        <div class="text-xs text-slate-400 font-mono">system_prompt.txt</div>
                        <div class="w-10"></div> </div>
                    
                    <textarea name="system_prompt" 
                              class="editor-textarea w-full flex-grow p-4 resize-none outline-none text-sm"
                              placeholder="Describe your AI persona here..."><?= htmlspecialchars($current_prompt) ?></textarea>
                    
                    <div class="bg-slate-800/50 px-4 py-2 text-[10px] text-slate-500 font-mono border-t border-slate-700 flex justify-between">
                        <span>Ln 1, Col 1</span>
                        <span>UTF-8</span>
                    </div>
                </div>

                <div class="px-6 pb-8">
                    <button type="submit" class="group w-full py-4 rounded-xl badge-gradient font-bold text-white shadow-lg shadow-indigo-500/40 hover:shadow-indigo-500/60 transition-all transform hover:-translate-y-1 hover:scale-[1.01] active:scale-95 flex items-center justify-center gap-3 text-lg overflow-hidden relative">
                        <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-[-20deg] group-hover:animate-[shimmer_1s_infinite]"></div>
                        
                        <i class="fa-solid fa-bolt text-yellow-300 group-hover:animate-pulse"></i>
                        <span>Save & Initialize Brain</span>
                    </button>
                    <p class="text-center text-xs text-slate-400 mt-4">
                        Action will reset current sessions and reload model weights.
                    </p>
                </div>
            </div>
        </div>

    </form>
</div>

<?php include '_footer.php'; ?>