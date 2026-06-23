<?php
// File: blog.php (Root Directory - Public)
session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
global $db;
$current_public_page = basename($_SERVER['PHP_SELF']);
$current_url = $_SERVER['REQUEST_URI'];
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$seo_title = "Blog - SMM Strategies & Digital Marketing Insights";
$seo_desc = "Learn the latest social media growth hacks, SMM panel strategies, and digital marketing insights to scale your business.";
$seo_kws = "smm blog, social media marketing tips, buy followers guide, digital growth strategies";

if (isset($db)) {
    try {
        $seo_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_name = ? OR page_url = ? LIMIT 1");
        $seo_stmt->execute([$current_public_page, $current_url]);
        $seo_data = $seo_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seo_data) {
            if (!empty($seo_data['meta_title'])) $seo_title = $seo_data['meta_title'];
            if (!empty($seo_data['meta_description'])) $seo_desc = $seo_data['meta_description'];
            if (!empty($seo_data['meta_keywords'])) $seo_kws = $seo_data['meta_keywords'];
        }
        
        // Traffic Logger
        $log_stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$user_ip, $current_url, 200, $user_agent, date('Y-m-d H:i:s')]);
    } catch (PDOException $e) {}
}
// --- 🚀 ADVANCED 2-WAY SEO ENGINE ENDS ---

// --- 1. Header Logic & AUTO-SEO INJECTION ---
ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR 🚀
if (file_exists(__DIR__ . '/seo_auto_injector.php')) {
    require_once __DIR__ . '/seo_auto_injector.php';
    $header_html = preg_replace('/<title>.*?<\/title>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']description["\'].*?>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']keywords["\'].*?>/i', '', $header_html);
    $header_html = str_ireplace('</head>', $beast_seo_injection . "\n</head>", $header_html);
} else {
    $header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$seo_title</title>", $header_html);
}
echo $header_html;

// --- BLOG FETCHING LOGIC ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; 
$offset = ($page - 1) * $limit;

// Filter by Category
$cat_slug = isset($_GET['cat']) ? sanitize($_GET['cat']) : '';
$where_sql = "WHERE status = 1";
$params = [];

try {
    if (!empty($cat_slug)) {
        $c_stmt = $db->prepare("SELECT id, name FROM seo_blog_categories WHERE slug = ?");
        $c_stmt->execute([$cat_slug]);
        $active_cat = $c_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($active_cat) {
            $where_sql .= " AND category_id = ?";
            $params[] = $active_cat['id'];
        }
    }

    $query = "SELECT * FROM seo_blogs $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_query = "SELECT COUNT(*) FROM seo_blogs $where_sql";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($params);
    $total_blogs = $count_stmt->fetchColumn();
    $total_pages = ceil($total_blogs / $limit);
    
    $categories = $db->query("SELECT * FROM seo_blog_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $blogs = [];
    $categories = [];
    $total_pages = 0;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    body { font-family: 'Inter', sans-serif; background-color: #f5f5f7; color: #1d1d1f; }
    .text-gradient-purple { background: linear-gradient(135deg, #a855f7 0%, #d946ef 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

    /* Category Filter */
    .cat-scroll-wrap { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 15px; margin-bottom: 30px; scrollbar-width: none; }
    .cat-scroll-wrap::-webkit-scrollbar { display: none; }
    .cat-pill { padding: 8px 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 50px; font-size: 0.9rem; font-weight: 600; color: #4b5563; text-decoration: none; white-space: nowrap; transition: 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .cat-pill:hover, .cat-pill.active { background: #1d1d1f; color: #fff; border-color: #1d1d1f; transform: translateY(-2px); }

    /* Blog Card Design */
    .blog-card { background: #ffffff; border-radius: 24px; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); border: 1px solid rgba(0,0,0,0.06); overflow: hidden; display: flex; flex-direction: column; height: 100%; position: relative; }
    .blog-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: linear-gradient(90deg, #a855f7, #d946ef); opacity: 0; transition: opacity 0.3s ease; z-index: 10; }
    .blog-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: rgba(168, 85, 247, 0.3); }
    .blog-card:hover::before { opacity: 1; }
    .card-img-wrap { width: 100%; height: 200px; overflow: hidden; background: #f1f5f9; position: relative; }
    .card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .blog-card:hover .card-img { transform: scale(1.05); }
    .cat-floating-badge { position: absolute; bottom: 15px; left: 15px; background: rgba(255,255,255,0.9); backdrop-filter: blur(5px); padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; color: #7c3aed; z-index: 5; }
    .card-content { padding: 30px; display: flex; flex-direction: column; flex-grow: 1; }
    .date-badge { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #86868b; margin-bottom: 1rem; display: block; }
    .blog-title { font-size: 1.5rem; font-weight: 800; line-height: 1.3; color: #1d1d1f; margin-bottom: 1rem; letter-spacing: -0.02em; transition: color 0.2s; }
    .blog-card:hover .blog-title { color: #7c3aed; }
    .blog-desc { font-size: 1rem; color: #6e6e73; line-height: 1.6; margin-bottom: 2rem; flex-grow: 1; }
    .btn-read { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; background: #f5f5f7; color: #1d1d1f; font-weight: 600; font-size: 0.9rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none; align-self: flex-start; }
    .btn-read:hover { background: #7c3aed; color: white; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }

    .animate-in { animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="relative pt-40 pb-20 overflow-hidden bg-white border-b border-gray-100">
    <div class="absolute inset-0 bg-[radial-gradient(#f3e8ff_1px,transparent_1px)] [background-size:20px_20px] opacity-30"></div>
    <div class="max-w-4xl mx-auto px-6 relative z-10 text-center">
        <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-[#1d1d1f] mb-8 animate-in" style="animation-delay: 0.2s">Written for <br><span class="text-gradient-purple">Growth Hackers.</span></h1>
        <p class="text-xl text-[#86868b] font-normal leading-relaxed animate-in" style="animation-delay: 0.3s">Deep dives into social media algorithms, monetization strategies, and viral trends. Pure insights, no fluff.</p>
    </div>
</div>

<div class="py-16 px-6 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <?php if(!empty($categories)): ?>
        <div class="cat-scroll-wrap animate-in" style="animation-delay: 0.4s">
            <a href="blog.php" class="cat-pill <?= empty($cat_slug) ? 'active' : '' ?>">All Topics</a>
            <?php foreach($categories as $c): ?>
                <a href="blog.php?cat=<?= $c['slug'] ?>" class="cat-pill <?= ($cat_slug == $c['slug']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if(!empty($blogs)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($blogs as $index => $blog): 
                    $delay = ($index % 3) * 0.1 + 0.5;
                    $blog_url = "blog_post.php?slug=" . urlencode($blog['slug']); 
                    $cat_name = 'Insight';
                    foreach($categories as $c) { if($c['id'] == $blog['category_id']) { $cat_name = $c['name']; break; } }
                    $img_src = '';
                    if(!empty($blog['featured_image'])) {
                        $p = 'assets/img/blog/' . $blog['featured_image'];
                        if(file_exists(__DIR__ . '/' . $p)) { $img_src = $p; }
                    }
                ?>
                <article class="blog-card animate-in" style="animation-delay: <?= $delay ?>s">
                    <?php if(!empty($img_src)): ?>
                        <div class="card-img-wrap">
                            <span class="cat-floating-badge"><?= htmlspecialchars($cat_name) ?></span>
                            <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($blog['title']) ?>" class="card-img" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <span class="date-badge"><i class="far fa-calendar-alt mr-1"></i> <?= date('F j, Y', strtotime($blog['created_at'])) ?></span>
                        <h2 class="blog-title"><?= htmlspecialchars($blog['title']) ?></h2>
                        <p class="blog-desc">
                            <?php 
                                $desc = $blog['meta_description'] ?? strip_tags($blog['content']);
                                // 🕸️ SPIDER LINKER APPLIED
                                echo function_exists('auto_spider_link') ? auto_spider_link(substr($desc, 0, 120), $db) : substr($desc, 0, 120);
                            ?>...
                        </p>
                        <a href="<?= $blog_url ?>" class="btn-read group">Read Article<i class="fas fa-arrow-right ml-2 text-sm group-hover:translate-x-1 transition-transform"></i></a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-40"><h3>No Articles Found</h3></div>
        <?php endif; ?>
    </div>
</div>

<?php include 'user/_smm_footer.php'; ?>
