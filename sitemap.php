<?php  
// File: sitemap.php  
require_once 'includes/db.php';  
header("Content-Type: application/xml; charset=utf-8");  
  
$baseUrl = "https://" . $_SERVER['HTTP_HOST'];  
  
echo '<?xml version="1.0" encoding="UTF-8"?>';  
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/services.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= $baseUrl ?>/blog.php</loc>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>

    <?php  
    // --- 1. Dynamic Blogs (Fixed Column Name) ---
    // Blogs table mein 'created_at' column hai, 'updated_at' nahi.
    $stmt = $db->query("SELECT slug, created_at FROM blogs WHERE status='published' ORDER BY created_at DESC");  
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {  
        $date = !empty($row['created_at']) ? date('c', strtotime($row['created_at'])) : date('c');  
        echo "  
    <url>  
        <loc>{$baseUrl}/blog_view.php?slug={$row['slug']}</loc>  
        <lastmod>{$date}</lastmod>  
        <changefreq>weekly</changefreq>  
        <priority>0.8</priority>  
    </url>";  
    }

    // --- 2. Dynamic Services (Added for SEO) ---
    // Har service ka link index hona zaroori hai ranking ke liye.
    $svc_stmt = $db->query("SELECT id FROM smm_services WHERE is_active=1");
    while($svc = $svc_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "
    <url>
        <loc>{$baseUrl}/service.php?id={$svc['id']}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>";
    }
    ?>

</urlset>