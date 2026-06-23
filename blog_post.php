<?php
// File: blog_post.php (Root Directory - Public)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// 1. Get Slug & Sanitize
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug = htmlspecialchars(strip_tags($slug));

// 2. Fetch Blog from NEW Database (seo_blogs) + Category
try {
    $stmt = $db->prepare("
        SELECT b.*, c.name as category_name 
        FROM seo_blogs b 
        LEFT JOIN seo_blog_categories c ON b.category_id = c.id 
        WHERE b.slug = ? AND b.status = 1 
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $blog = false;
}

// 3. Handle 404
if (!$blog) {
    http_response_code(404);
    ob_start(); include 'user/_header.php'; $header_html = ob_get_clean();
    $header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>Article Not Found</title>", $header_html);
    echo $header_html;
    echo '<div class="min-h-screen flex flex-col items-center justify-center text-center p-6" style="background:#f8fafc;">
            <div class="text-8xl mb-4" style="animation: bounce 2s infinite;">🤷‍♂️</div>
            <h1 class="text-4xl font-extrabold text-slate-800 mb-4" style="font-family:\'Inter\', sans-serif;">Article Not Found</h1>
            <p class="text-slate-500 mb-8">The master piece you are looking for has been moved or doesn\'t exist.</p>
            <a href="blog.php" class="px-8 py-3 rounded-full font-bold text-white transition shadow-lg" style="background:#4f46e5; text-decoration:none;">Back to Blog Hub</a>
          </div>';
    include 'user/_smm_footer.php';
    exit;
}

// 4. Increment View Counter
try {
    $db->query("UPDATE seo_blogs SET views = views + 1 WHERE id = " . (int)$blog['id']);
} catch(Exception $e) {}

// --- 🚀 ADVANCED 2-WAY SEO ENGINE STARTS ---
$current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

try {
    // SEMrush Bot & Traffic Logger
    $log_stmt = $db->prepare("INSERT IGNORE INTO semrush_server_logs (ip_address, crawl_url, status_code, user_agent, crawl_date) VALUES (?, ?, ?, ?, ?)");
    $log_stmt->execute([$user_ip, $current_url, 200, $user_agent, date('Y-m-d H:i:s')]);
} catch (Exception $e) {}

// Setup SEO Data for Injector (Checking if Expert Workspace has Override)
$page_title = !empty($blog['meta_title']) ? htmlspecialchars($blog['meta_title']) : htmlspecialchars($blog['title']);
$page_desc = htmlspecialchars($blog['meta_description'] ?? strip_tags(substr($blog['content'], 0, 160)));
$page_keywords = htmlspecialchars(($blog['primary_keyword'] ?? '') . ', ' . ($blog['lsi_keywords'] ?? ''));

// Overwrite from Expert Workspace (site_seo) if configured for this specific URL
try {
    $workspace_stmt = $db->prepare("SELECT meta_title, meta_description, meta_keywords FROM site_seo WHERE page_url = ? LIMIT 1");
    $workspace_stmt->execute([$current_url]);
    $workspace_data = $workspace_stmt->fetch(PDO::FETCH_ASSOC);
    if ($workspace_data) {
        if (!empty($workspace_data['meta_title'])) $page_title = htmlspecialchars($workspace_data['meta_title']);
        if (!empty($workspace_data['meta_description'])) $page_desc = htmlspecialchars($workspace_data['meta_description']);
        if (!empty($workspace_data['meta_keywords'])) $page_keywords = htmlspecialchars($workspace_data['meta_keywords']);
    }
} catch (Exception $e) {}

// 5. Smart Image Resolver
$img_name = $blog['featured_image'] ?? '';
$page_image = "https://" . $_SERVER['HTTP_HOST'] . "/assets/img/logo.png";
$bg_image_style = "";

if (!empty($img_name)) {
    $p = 'assets/img/blog/' . sanitize($img_name);
    if(file_exists(__DIR__ . '/' . $p)) {
        $page_image = "https://" . $_SERVER['HTTP_HOST'] . "/" . $p;
        $bg_image_style = "background-image: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.95)), url('/" . $p . "'); background-size: cover; background-position: center;";
    }
}

// --- 6. Header Logic & AUTO-SEO INJECTION ---
ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();

// 🚀 INTEGRATING BEAST SEO AUTO-INJECTOR 🚀
if (file_exists(__DIR__ . '/seo_auto_injector.php')) {
    require_once __DIR__ . '/seo_auto_injector.php';
    
    // Remove old static tags
    $header_html = preg_replace('/<title>.*?<\/title>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']description["\'].*?>/i', '', $header_html);
    $header_html = preg_replace('/<meta name=["\']keywords["\'].*?>/i', '', $header_html);
    
    // Custom Blog Schema Generation
    $blog_schema = [
        "@context" => "https://schema.org",
        "@type" => "BlogPosting",
        "mainEntityOfPage" => ["@type" => "WebPage", "@id" => $current_url],
        "headline" => $page_title,
        "description" => $page_desc,
        "image" => $page_image,
        "keywords" => $page_keywords,
        "author" => ["@type" => "Organization", "name" => "LikexFollow Experts"],
        "publisher" => [
            "@type" => "Organization",
            "name" => "LikexFollow",
            "logo" => ["@type" => "ImageObject", "url" => "https://" . $_SERVER['HTTP_HOST'] . "/assets/img/logo.png"]
        ],
        "datePublished" => date('c', strtotime($blog['created_at'])),
        "dateModified" => date('c', strtotime($blog['created_at']))
    ];
    $schema_json = "<script type='application/ld+json'>\n" . json_encode($blog_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>";
    
    // Inject the fully automated API SEO Tags + JSON Schema
    $custom_meta = "<title>{$page_title}</title>\n" .
                   "<meta name='description' content='{$page_desc}'>\n" .
                   "<meta name='keywords' content='{$page_keywords}'>\n" .
                   "<meta property='og:title' content='{$page_title}'>\n" .
                   "<meta property='og:description' content='{$page_desc}'>\n" .
                   "<meta property='og:image' content='{$page_image}'>\n" .
                   "<meta property='og:url' content='{$current_url}'>\n" .
                   "<meta name='twitter:card' content='summary_large_image'>\n" .
                   $schema_json;
                   
    $header_html = str_ireplace('</head>', $custom_meta . "\n</head>", $header_html);
} else {
    $header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$page_title</title>", $header_html);
}
echo $header_html;
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; }
    #scrollProgress { position: fixed; top: 0; left: 0; width: 0%; height: 5px; background: linear-gradient(90deg, #4f46e5, #d946ef); z-index: 99999; transition: width 0.1s; }
    .blog-hero { padding: 140px 20px 80px 20px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #ffffff; text-align: center; position: relative; overflow: hidden; }
    .prose-content { font-family: 'Georgia', serif; font-size: 1.15rem; line-height: 1.85; color: #334155; }
    .prose-content h2 { font-family: 'Inter', sans-serif; font-size: 2.2rem; font-weight: 800; color: #0f172a; margin-top: 3.5rem; margin-bottom: 1.5rem; }
    .prose-content p { margin-bottom: 1.8rem; }
    .prose-content a { color: #4f46e5; text-decoration: none; border-bottom: 2px solid #e0e7ff; font-weight: 600; }
    .cta-box { background: linear-gradient(135deg, #4f46e5 0%, #7e22ce 100%); padding: 3rem; border-radius: 1.5rem; color: white; text-align: center; margin: 4rem 0; box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.4); }
    .cta-btn { background: white; color: #4f46e5; padding: 1rem 2.5rem; border-radius: 99px; font-weight: 800; transition: 0.3s; display: inline-block; text-decoration: none; margin-top: 15px; }
    .cat-badge { display: inline-block; padding: 6px 16px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; }
</style>

<div id="scrollProgress"></div>

<div class="blog-hero" style="<?= $bg_image_style ?>">
    <div class="max-w-4xl mx-auto relative z-10">
        <span class="cat-badge"><?= htmlspecialchars($blog['category_name'] ?? 'Growth Hack') ?></span>
        <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-8"><?= htmlspecialchars($blog['title']) ?></h1>
        <div class="inline-flex items-center gap-4 bg-white/10 backdrop-blur-md px-6 py-3 rounded-full border border-white/20">
            <p class="text-sm font-bold text-white mb-0"><i class="far fa-calendar-alt mr-1"></i> <?= date('M d, Y', strtotime($blog['created_at'])) ?></p>
        </div>
    </div>
</div>

<div class="bg-slate-50 min-h-screen pb-20 -mt-10 relative z-20">
    <div class="max-w-3xl mx-auto px-4 md:px-6">
        <div class="bg-white p-8 md:p-14 rounded-[2.5rem] shadow-xl border border-slate-100">
            <article class="prose-content mt-8">
                <?php 
                // 🕸️ SPIDER LINKER ENGINE ACTIVE 🕸️
                $content = $blog['content'];
                
                // Inject CTA after 2nd paragraph
                $paragraphs = explode('</p>', $content);
                $new_content = '';
                foreach ($paragraphs as $index => $paragraph) {
                    $new_content .= $paragraph . '</p>';
                    if ($index == 1) { // After 2nd paragraph
                        $new_content .= '<div class="cta-box"><h3>🚀 Ready to Dominate?</h3><a href="services.php" class="cta-btn">View Packages</a></div>';
                    }
                }
                
                // Run Spider Linker on Final Content
                echo function_exists('auto_spider_link') ? auto_spider_link($new_content, $db) : $new_content; 
                ?>
            </article>
        </div>
    </div>
</div>

<script>
    window.onscroll = function() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        document.getElementById("scrollProgress").style.width = (winScroll / height) * 100 + "%";
    };
</script>

<?php include 'user/_smm_footer.php'; ?>