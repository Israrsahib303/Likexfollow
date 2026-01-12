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

// 3. Handle 404 (Not Found)
if (!$blog) {
    http_response_code(404);
    include 'user/_header.php';
    echo '<div class="min-h-screen flex flex-col items-center justify-center text-center p-6">
            <div class="text-6xl mb-4">🤷‍♂️</div>
            <h1 class="text-3xl font-bold text-slate-800">Article Not Found</h1>
            <p class="text-slate-500 mb-6">The article you are looking for does not exist or has been moved.</p>
            <a href="blog.php" class="px-6 py-3 bg-indigo-600 text-white rounded-full font-bold hover:bg-indigo-700 transition">Back to Blog</a>
          </div>';
    include 'user/_footer.php';
    exit;
}

// 4. Increment View Counter
$db->query("UPDATE blogs SET views = views + 1 WHERE id = " . $blog['id']);

// 5. SEO Header Injection
// Hum user header ko buffer karke modify karenge taake SEO tags inject kar sakein
$page_title = htmlspecialchars($blog['title']) . " - LikexFollow";
$page_desc = htmlspecialchars($blog['meta_desc']);

ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();

// Replace Default Title
$header_html = str_replace('<title>LikexFollow | The Crazy SMM Panel</title>', "<title>$page_title</title>", $header_html);
// Inject Meta Description
$meta_tag = '<meta name="description" content="'.$page_desc.'">';
$header_html = str_replace('</head>', $meta_tag . "\n</head>", $header_html);

echo $header_html;
?>

<style>
    .prose h2 { font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-top: 2.5rem; margin-bottom: 1rem; line-height: 1.3; }
    .prose h3 { font-size: 1.4rem; font-weight: 700; color: #334155; margin-top: 2rem; margin-bottom: 0.8rem; }
    .prose p { margin-bottom: 1.25rem; line-height: 1.8; color: #475569; font-size: 1.1rem; }
    .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1.5rem; color: #475569; }
    .prose li { margin-bottom: 0.5rem; }
    .prose strong { color: #0f172a; font-weight: 700; }
    .prose a { color: #4f46e5; text-decoration: underline; font-weight: 600; }
    
    /* CTA Box Style */
    .cta-box {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        padding: 2rem;
        border-radius: 1rem;
        color: white;
        text-align: center;
        margin: 3rem 0;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        transform: rotate(-1deg);
    }
    .cta-box h3 { color: white !important; margin-top: 0 !important; font-size: 1.5rem !important; }
    .cta-box p { color: #e0e7ff !important; font-size: 1rem !important; margin-bottom: 1.5rem !important; }
    .cta-btn {
        background: white; color: #4f46e5; padding: 0.75rem 2rem; border-radius: 99px;
        font-weight: 800; text-decoration: none !important; display: inline-block;
        transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 15px rgba(0,0,0,0.2); }
</style>

<div class="bg-white min-h-screen pt-32 pb-20">
    
    <div class="max-w-3xl mx-auto px-6">
        
        <nav class="flex text-sm font-medium text-slate-400 mb-8">
            <a href="index.php" class="hover:text-indigo-600 transition">Home</a>
            <span class="mx-2">/</span>
            <a href="blog.php" class="hover:text-indigo-600 transition">Blog</a>
            <span class="mx-2">/</span>
            <span class="text-slate-600 truncate max-w-[200px]"><?= htmlspecialchars($blog['title']) ?></span>
        </nav>

        <header class="mb-10 text-center md:text-left">
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight mb-6 tracking-tight">
                <?= htmlspecialchars($blog['title']) ?>
            </h1>
            
            <div class="flex items-center gap-4 text-sm text-slate-500 border-b border-slate-100 pb-8">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg">L</div>
                    <div class="text-left">
                        <p class="font-bold text-slate-900 leading-none">LikexFollow Team</p>
                        <p class="text-xs">SMM Experts</p>
                    </div>
                </div>
                <div class="hidden md:block w-px h-8 bg-slate-200 mx-2"></div>
                <div class="flex gap-4">
                    <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-4 h-4"></i> <?= date('d M, Y', strtotime($blog['created_at'])) ?></span>
                    <span class="flex items-center gap-1"><i data-lucide="eye" class="w-4 h-4"></i> <?= number_format($blog['views']) ?> Views</span>
                </div>
            </div>
        </header>

        <article class="prose max-w-none">
            <?php 
            $content = $blog['content'];
            
            // --- SMART CTA INJECTION ---
            // 2nd Paragraph ke baad "Order Now" box lagayen
            $paragraphs = explode('</p>', $content);
            $new_content = '';
            $cta_inserted = false;
            
            foreach ($paragraphs as $index => $paragraph) {
                $new_content .= $paragraph;
                if (!empty(trim($paragraph))) {
                    $new_content .= '</p>';
                }
                
                // Insert CTA after 2nd paragraph
                if ($index == 1 && !$cta_inserted) {
                    $new_content .= '
                    <div class="cta-box">
                        <h3>🚀 Boost Your Social Media Today!</h3>
                        <p>Get real followers, likes, and views instantly. Cheapest rates in Pakistan starting at Rs 10.</p>
                        <a href="services.php" class="cta-btn">View Services & Pricing</a>
                    </div>';
                    $cta_inserted = true;
                }
            }
            
            echo $new_content; 
            ?>
        </article>

        <div class="mt-16 pt-10 border-t border-slate-200">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <p class="font-bold text-slate-900 mb-2">Share this article</p>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                            <i data-lucide="facebook" class="w-5 h-5"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($blog['title']) ?>&url=<?= urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" target="_blank" class="w-10 h-10 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center hover:bg-sky-500 hover:text-white transition-all">
                            <i data-lucide="twitter" class="w-5 h-5"></i>
                        </a>
                        <a href="whatsapp://send?text=<?= urlencode($blog['title'] . " - Read here: https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all">
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
                
                <div class="text-center md:text-right">
                    <a href="blog.php" class="text-indigo-600 font-bold hover:underline">← Back to All Articles</a>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Initialize Icons
    if(typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php include 'user/_footer.php'; ?>