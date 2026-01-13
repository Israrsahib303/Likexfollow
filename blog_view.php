<?php
// File: blog_view.php
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// 1. Get Slug & Sanitize
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug = htmlspecialchars(strip_tags($slug));

// 2. Fetch Blog from Database
try {
    $stmt = $db->prepare("SELECT * FROM blogs WHERE slug = ? AND status='published' LIMIT 1");
    $stmt->execute([$slug]);
    $blog = $stmt->fetch();
} catch (Exception $e) {
    $blog = false;
}

// 3. Handle 404
if (!$blog) {
    http_response_code(404);
    include 'user/_header.php';
    echo '<div class="min-h-screen flex flex-col items-center justify-center text-center p-6 bg-gray-50">
            <div class="text-8xl mb-4 animate-bounce">🤷‍♂️</div>
            <h1 class="text-4xl font-extrabold text-slate-800 mb-4">Article Not Found</h1>
            <a href="blog.php" class="px-8 py-3 bg-indigo-600 text-white rounded-full font-bold hover:bg-indigo-700 transition shadow-lg">Back to Blog</a>
          </div>';
    include 'user/_footer.php';
    exit;
}

// 4. Increment View Counter
$db->query("UPDATE blogs SET views = views + 1 WHERE id = " . $blog['id']);

// 5. SEO Header Injection
$page_title = htmlspecialchars($blog['title']);
$page_desc = htmlspecialchars($blog['meta_desc']);
$page_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$page_image = "https://likexfollow.com/assets/img/logo.png"; // Default Image

ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();

// Force Title & Meta Description Replacement
$header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$page_title</title>", $header_html);
$meta_tag = '<meta name="description" content="'.$page_desc.'">' . "\n" . 
            '<meta property="og:image" content="'.$page_image.'">';
$header_html = str_replace('</head>', $meta_tag . "\n</head>", $header_html);

echo $header_html;

// --- 🔥 ADVANCED SEO SCHEMA (JSON-LD) ---
$schemaData = [
    "@context" => "https://schema.org",
    "@type" => "BlogPosting",
    "mainEntityOfPage" => ["@type" => "WebPage", "@id" => $page_url],
    "headline" => $blog['title'],
    "description" => $blog['meta_desc'],
    "image" => $page_image,
    "author" => ["@type" => "Organization", "name" => "LikexFollow Team"],
    "publisher" => [
        "@type" => "Organization",
        "name" => "LikexFollow",
        "logo" => ["@type" => "ImageObject", "url" => $page_image]
    ],
    "datePublished" => date('c', strtotime($blog['created_at'])),
    "dateModified" => date('c', strtotime($blog['created_at'])) // Fallback since updated_at is missing
];
?>

<script type="application/ld+json">
    <?= json_encode($schemaData); ?>
</script>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
    /* Custom Scrollbar */
    #scrollProgress { position: fixed; top: 0; left: 0; width: 0%; height: 5px; background: linear-gradient(90deg, #6366f1, #ec4899); z-index: 99999; transition: width 0.1s; }
    
    /* Typography */
    .prose h2 { font-size: 2rem; font-weight: 800; color: #1e293b; margin-top: 3rem; margin-bottom: 1.5rem; letter-spacing: -0.02em; }
    .prose h3 { font-size: 1.5rem; font-weight: 700; color: #334155; margin-top: 2.5rem; margin-bottom: 1rem; }
    .prose p { margin-bottom: 1.5rem; line-height: 1.8; color: #475569; font-size: 1.125rem; }
    .prose ul { list-style: none; padding-left: 0; margin-bottom: 2rem; }
    .prose li { position: relative; padding-left: 2rem; margin-bottom: 0.8rem; color: #475569; font-size: 1.1rem; }
    .prose li::before { content: "✨"; position: absolute; left: 0; top: 2px; }
    .prose a { color: #4f46e5; text-decoration: none; border-bottom: 2px solid #e0e7ff; transition: 0.2s; font-weight: 600; }
    .prose a:hover { background: #e0e7ff; border-color: #4f46e5; color: #4338ca; }

    /* CTA Box Animation */
    .cta-box {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        padding: 3rem; border-radius: 1.5rem; color: white; text-align: center;
        margin: 4rem 0; position: relative; overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255,255,255,0.1);
        transform: perspective(1000px) rotateX(0deg);
        transition: transform 0.3s;
    }
    .cta-box:hover { transform: perspective(1000px) rotateX(2deg); }
    .cta-btn {
        background: white; color: #0f172a; padding: 1rem 2.5rem; border-radius: 99px;
        font-weight: 800; font-size: 1.1rem; transition: all 0.3s; display: inline-block;
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
    }
    .cta-btn:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 20px 30px rgba(255, 255, 255, 0.2); }
</style>

<div id="scrollProgress"></div>

<div class="bg-slate-50 min-h-screen pt-28 pb-20">
    
    <div class="max-w-4xl mx-auto px-6 text-center mb-16 relative">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-64 h-64 bg-indigo-500/20 rounded-full blur-[100px] -z-10"></div>
        
        <nav class="flex justify-center items-center gap-2 text-sm font-semibold text-slate-400 mb-8 uppercase tracking-widest">
            <a href="index.php" class="hover:text-indigo-600 transition">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="blog.php" class="hover:text-indigo-600 transition">Blog</a>
        </nav>

        <h1 class="text-4xl md:text-6xl font-extrabold text-slate-900 leading-tight mb-8">
            <?= htmlspecialchars($blog['title']) ?>
        </h1>

        <div class="inline-flex items-center gap-4 bg-white px-6 py-3 rounded-full shadow-md border border-slate-100">
            <img src="assets/img/logo.png" class="w-8 h-8 rounded-full bg-slate-100 p-1" alt="Author">
            <div class="text-left">
                <p class="text-xs text-slate-400 font-bold uppercase">Written By</p>
                <p class="text-sm font-bold text-slate-900">LikexFollow AI</p>
            </div>
            <div class="w-px h-8 bg-slate-200 mx-2"></div>
            <div class="text-left">
                <p class="text-xs text-slate-400 font-bold uppercase">Published</p>
                <p class="text-sm font-bold text-slate-900"><?= date('M d, Y', strtotime($blog['created_at'])) ?></p>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-6">
        <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
            
            <article class="prose max-w-none relative z-10">
                <?php 
                $content = $blog['content'];
                
                // --- SMART CTA INJECTION ---
                $paragraphs = explode('</p>', $content);
                $new_content = '';
                $cta_inserted = false;
                
                foreach ($paragraphs as $index => $paragraph) {
                    $new_content .= $paragraph;
                    if (!empty(trim($paragraph))) {
                        $new_content .= '</p>';
                    }
                    // Insert after 2nd paragraph
                    if ($index == 1 && !$cta_inserted) {
                        $new_content .= '
                        <div class="cta-box">
                            <h3 style="color:white !important; margin-top:0;">🚀 Boost Your Social Media?</h3>
                            <p style="color:#cbd5e1 !important;">Don\'t wait for luck. Get real followers and likes instantly.</p>
                            <a href="services.php" class="cta-btn">View Prices & Packages</a>
                        </div>';
                        $cta_inserted = true;
                    }
                }
                echo $new_content; 
                ?>
            </article>

            <div class="mt-16 pt-8 border-t border-slate-100 flex flex-col items-center">
                <p class="text-slate-900 font-bold mb-4">Share this article</p>
                <div class="flex gap-4">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($blog['title'] . " " . $page_url) ?>" target="_blank" class="w-12 h-12 flex items-center justify-center rounded-full bg-green-100 text-green-600 hover:scale-110 transition"><i data-lucide="message-circle"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($page_url) ?>" target="_blank" class="w-12 h-12 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 hover:scale-110 transition"><i data-lucide="facebook"></i></a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($blog['title']) ?>&url=<?= urlencode($page_url) ?>" target="_blank" class="w-12 h-12 flex items-center justify-center rounded-full bg-sky-100 text-sky-500 hover:scale-110 transition"><i data-lucide="twitter"></i></a>
                </div>
            </div>

        </div>
        
        <div class="text-center mt-12">
            <a href="blog.php" class="text-slate-500 font-bold hover:text-indigo-600 transition flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> View All Articles
            </a>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
    // Scroll Progress
    window.onscroll = function() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled = (winScroll / height) * 100;
        document.getElementById("scrollProgress").style.width = scrolled + "%";
    };
</script>

<?php include 'user/_footer.php'; ?>