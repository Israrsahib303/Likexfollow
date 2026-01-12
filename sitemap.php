<?php
// File: sitemap.php
require_once 'includes/db.php';

// Set Headers
header("Content-Type: application/xml; charset=utf-8");

// Base URL (Apni website ka URL yahan check karein)
$base_url = "https://likexfollow.com/"; 
if (defined('SITE_URL')) {
    $base_url = rtrim(SITE_URL, '/') . '/';
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// 1. Static Pages
$static_pages = [
    '' => '1.0',
    'login.php' => '0.8',
    'register.php' => '0.8',
    'services.php' => '0.9',
    'blog.php' => '0.9' // Agar blog listing page banaya to
];

foreach ($static_pages as $page => $priority) {
    echo "
    <url>
        <loc>{$base_url}{$page}</loc>
        <changefreq>daily</changefreq>
        <priority>{$priority}</priority>
    </url>";
}

// 2. Dynamic Blogs
$stmt = $db->query("SELECT slug, created_at FROM blogs WHERE status='published' ORDER BY id DESC");
while ($row = $stmt->fetch()) {
    $date = date('Y-m-d', strtotime($row['created_at']));
    echo "
    <url>
        <loc>{$base_url}blog_view.php?slug={$row['slug']}</loc>
        <lastmod>{$date}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>";
}

echo '</urlset>';
?>