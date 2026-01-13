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
    $stmt = $db->query("SELECT slug, updated_at FROM blogs WHERE status='published' ORDER BY updated_at DESC");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Handle NULL dates
        $date = !empty($row['updated_at']) ? date('c', strtotime($row['updated_at'])) : date('c');
        echo "
    <url>
        <loc>{$baseUrl}/blog/{$row['slug']}</loc>
        <lastmod>{$date}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>";
    }
    ?>
</urlset>