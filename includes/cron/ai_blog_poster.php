<?php
// File: includes/cron/ai_blog_poster.php
// Setup
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AiEngine.php';

// Helper for Logging
function logSeo($db, $action, $details) {
    $stmt = $db->prepare("INSERT INTO seo_logs (action, details) VALUES (?, ?)");
    $stmt->execute([$action, $details]);
}

// 1. Trending Topics Bank (AI inme se randomly pick karega)
$topics = [
    "How to get 1000 TikTok followers fast",
    "Best time to post on Instagram 2026",
    "How to viral YouTube Shorts instantly",
    "Get more views on Telegram channel",
    "Increase Facebook page likes organically",
    "Best SMM Panel for resellers in Pakistan",
    "How to monetize TikTok account in 2026",
    "Secret to Instagram Algorithm exposed"
];

$topic = $topics[array_rand($topics)];

// 2. Check if topic already exists to avoid duplicate
$check = $db->prepare("SELECT id FROM blogs WHERE title = ?");
$check->execute([$topic]);
if ($check->rowCount() > 0) {
    die("Topic already covered today.");
}

// 3. Generate Content
$ai = new AiEngine($db);
$content = $ai->generateContent($topic);

if (strpos($content, 'Error') === false) {
    
    // Create Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $topic)));
    $slug .= '-' . rand(1000, 9999);
    
    // Meta Desc
    $meta_desc = substr(strip_tags($content), 0, 155) . '...';

    // Insert into DB
    $stmt = $db->prepare("INSERT INTO blogs (title, slug, content, meta_desc, status, created_at) VALUES (?, ?, ?, ?, 'published', NOW())");
    $stmt->execute([$topic, $slug, $content, $meta_desc]);

    logSeo($db, "Auto Blog", "Published: $topic");
    echo "Success: $topic published.";

} else {
    logSeo($db, "Auto Blog Failed", "AI Error: $content");
    echo "Failed: $content";
}
?>