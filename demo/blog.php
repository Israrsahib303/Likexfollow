<?php
// File: blog.php
require_once 'includes/db.php';
require_once 'includes/helpers.php';

// Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; 
$offset = ($page - 1) * $limit;

// Fetch Published Blogs
try {
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
}

// Set Page Title
$page_title = "Blog - Insights & Strategies";
ob_start();
include 'user/_header.php';
$header_html = ob_get_clean();
// Force Title Update
$header_html = preg_replace('/<title>(.*?)<\/title>/', "<title>$page_title</title>", $header_html);
echo $header_html;
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f5f5f7; /* Apple Grey BG */
        color: #1d1d1f;
    }

    /* Glass Header Effect */
    .glass-header {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: saturate(180%) blur(20px);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    /* Apple-style Gradient Text */
    .text-gradient-purple {
        background: linear-gradient(135deg, #a855f7 0%, #d946ef 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Blog Card Design - Text Focused */
    .blog-card {
        background: #ffffff;
        border-radius: 24px;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); /* Soft shadow */
        border: 1px solid rgba(0,0,0,0.06); /* Subtle border */
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
        padding: 40px; /* Generous padding for spacing */
    }

    .blog-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: linear-gradient(90deg, #a855f7, #d946ef); /* Top accent line */
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .blog-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        border-color: rgba(168, 85, 247, 0.3); /* Purple tint on border hover */
    }

    .blog-card:hover::before {
        opacity: 1;
    }

    /* Date Badge */
    .date-badge {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #86868b;
        margin-bottom: 1.5rem;
        display: block;
    }

    /* Typography */
    .blog-title {
        font-size: 1.75rem; /* Larger Title */
        font-weight: 800;
        line-height: 1.25;
        color: #1d1d1f;
        margin-bottom: 1.25rem;
        letter-spacing: -0.02em;
        transition: color 0.2s;
    }
    
    .blog-card:hover .blog-title {
        color: #7c3aed; /* Purple on hover */
    }

    .blog-desc {
        font-size: 1.05rem;
        color: #6e6e73;
        line-height: 1.7;
        margin-bottom: 2rem;
        flex-grow: 1; /* Pushes button to bottom */
    }

    /* Action Button */
    .btn-read {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 28px;
        background: #f5f5f7;
        color: #1d1d1f;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
        align-self: flex-start; /* Align left */
    }

    .btn-read:hover {
        background: #7c3aed;
        color: white;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        opacity: 0;
    }
</style>

<div class="relative pt-40 pb-20 overflow-hidden bg-white border-b border-gray-100">
    <div class="absolute inset-0 bg-[radial-gradient(#f3e8ff_1px,transparent_1px)] [background-size:20px_20px] opacity-30"></div>

    <div class="max-w-4xl mx-auto px-6 relative z-10 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-50 border border-gray-200 mb-8 animate-in" style="animation-delay: 0.1s">
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">LikexFollow Blog</span>
        </div>

        <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-[#1d1d1f] mb-8 animate-in" style="animation-delay: 0.2s">
            Written for <br>
            <span class="text-gradient-purple">Growth Hackers.</span>
        </h1>
        
        <p class="text-xl text-[#86868b] font-normal leading-relaxed animate-in" style="animation-delay: 0.3s">
            Deep dives into social media algorithms, monetization strategies, and viral trends. Pure insights, no fluff.
        </p>
    </div>
</div>

<div class="py-24 px-6 min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        <?php if(!empty($blogs)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                
                <?php foreach($blogs as $index => $blog): 
                    // Staggered Animation Delay
                    $delay = ($index % 3) * 0.1 + 0.4;
                ?>
                
                <article class="blog-card animate-in" style="animation-delay: <?= $delay ?>s">
                    
                    <span class="date-badge">
                        <?= date('F j, Y', strtotime($blog['created_at'])) ?>
                    </span>

                    <h2 class="blog-title">
                        <?= htmlspecialchars($blog['title']) ?>
                    </h2>

                    <p class="blog-desc">
                        <?= htmlspecialchars(substr($blog['meta_desc'], 0, 160)) ?>...
                    </p>

                    <a href="blog_view.php?slug=<?= $blog['slug'] ?>" class="btn-read group">
                        Read Article
                        <i data-lucide="arrow-right" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>

                </article>
                <?php endforeach; ?>

            </div>

            <?php if($total_pages > 1): ?>
            <div class="mt-24 flex justify-center gap-3 animate-in" style="animation-delay: 0.8s">
                <?php if($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>" class="w-12 h-12 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-600 hover:border-purple-500 hover:text-purple-600 transition shadow-sm">
                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>

                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="w-12 h-12 flex items-center justify-center rounded-xl font-semibold text-sm transition shadow-sm <?= ($page == $i) ? 'bg-[#1d1d1f] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="?page=<?= $page+1 ?>" class="w-12 h-12 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-600 hover:border-purple-500 hover:text-purple-600 transition shadow-sm">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-40 animate-in">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 text-gray-400 mb-6">
                    <i data-lucide="file-text" class="w-10 h-10"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">No Articles Found</h3>
                <p class="text-gray-500 max-w-md mx-auto">
                    The AI is currently researching new topics. Check back soon.
                </p>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    // Initialize Icons
    lucide.createIcons();
</script>

<?php include 'user/_footer.php'; ?>