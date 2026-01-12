<?php
// File: blog.php
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Pagination Setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; 
$offset = ($page - 1) * $limit;

// Database Check & Fetch
try {
    // Check if table exists
    $check = $db->query("SHOW TABLES LIKE 'blogs'");
    if($check->rowCount() == 0) {
        throw new Exception("Database setup pending.");
    }

    $stmt = $db->prepare("SELECT * FROM blogs WHERE status='published' ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $blogs = $stmt->fetchAll();

    $total_blogs = $db->query("SELECT COUNT(*) FROM blogs WHERE status='published'")->fetchColumn();
    $total_pages = ceil($total_blogs / $limit);

} catch (Exception $e) {
    $blogs = [];
    $total_pages = 0;
    $error_msg = "System Initialization: AI Module is preparing content.";
}

// Page Title
$page_title = "Latest Social Media Trends & Tips - LikexFollow";

// Header Load
ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();
$header_html = str_replace('<title>LikexFollow | The Crazy SMM Panel</title>', "<title>$page_title</title>", $header_html);
echo $header_html;
?>

<div class="relative bg-slate-900 py-24 overflow-hidden">
    <div class="absolute inset-0 bg-[url('assets/img/grid.svg')] opacity-10"></div>
    <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
        <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-sm font-bold border border-indigo-500/30 mb-4 inline-block">
            🚀 VIRAL STRATEGIES
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 tracking-tight">
            LikexFollow <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Blog</span>
        </h1>
        <p class="text-lg text-slate-400 max-w-2xl mx-auto">
            Unlock the secrets of social media growth with our AI-powered insights.
        </p>
    </div>
</div>

<div class="bg-slate-50 py-20 min-h-screen">
    <div class="max-w-7xl mx-auto px-4">
        
        <?php if(!empty($blogs)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($blogs as $blog): 
                    $colors = ['from-purple-500 to-indigo-600', 'from-blue-500 to-cyan-500', 'from-pink-500 to-rose-500'];
                    $bg = $colors[array_rand($colors)];
                ?>
                <a href="blog_view.php?slug=<?= $blog['slug'] ?>" class="group bg-white rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-slate-100 flex flex-col h-full">
                    
                    <div class="h-48 bg-gradient-to-br <?= $bg ?> p-6 flex items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition"></div>
                        <h3 class="text-white font-bold text-xl text-center leading-tight drop-shadow-md relative z-10">
                            <?= htmlspecialchars($blog['title']) ?>
                        </h3>
                    </div>

                    <div class="p-6 flex-1 flex flex-col">
                        <p class="text-slate-500 text-sm mb-4 line-clamp-3 flex-1">
                            <?= htmlspecialchars($blog['meta_desc'] ?? 'Read full article to know more...') ?>
                        </p>
                        <div class="pt-4 border-t border-slate-100 flex justify-between items-center mt-auto">
                            <span class="text-xs font-bold text-slate-400 uppercase"><?= date('M d, Y', strtotime($blog['created_at'])) ?></span>
                            <span class="text-indigo-600 font-bold text-sm group-hover:translate-x-1 transition">Read Now →</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="mt-12 flex justify-center gap-2">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-lg font-bold border transition <?= ($page == $i) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20">
                <div class="text-6xl mb-4">🤖</div>
                <h3 class="text-2xl font-bold text-slate-700">AI Writer is Working...</h3>
                <p class="text-slate-500 max-w-md mx-auto">
                    <?= isset($error_msg) ? $error_msg : "No articles published yet. Please run the AI Cron Job or generate manually from Admin Panel." ?>
                </p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'user/_footer.php'; ?>