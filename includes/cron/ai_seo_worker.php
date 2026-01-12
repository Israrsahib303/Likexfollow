<?php
// File: includes/cron/ai_seo_worker.php
// Setup
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AiEngine.php';

// Helper for Logging
function logSeo($db, $action, $details) {
    $stmt = $db->prepare("INSERT INTO seo_logs (action, details) VALUES (?, ?)");
    $stmt->execute([$action, $details]);
}

// 1. Find ONE service that needs SEO (Content is NULL)
// We join smm_services with service_seo to find missing ones
$stmt = $db->query("
    SELECT s.id, s.name, s.category 
    FROM smm_services s
    LEFT JOIN service_seo seo ON s.id = seo.service_id
    WHERE s.is_active = 1 AND seo.ai_content IS NULL
    LIMIT 1
");

$service = $stmt->fetch();

if (!$service) {
    die("All active services have SEO content. Good job!");
}

$serviceId = $service['id'];
$serviceName = $service['name'];
$category = $service['category'];

echo "Processing Service ID: $serviceId - $serviceName <br>";

// 2. Generate SEO Data via AI
$ai = new AiEngine($db);

// Generate Description
$promptDesc = "Write a 300-word SEO description for a social media service named '$serviceName' in category '$category'. Focus on benefits, safety, and why users should buy it. Use HTML <p>, <ul> tags.";
$content = $ai->generateContent($promptDesc);

// Generate Meta Title (Short)
// Note: Normally we'd ask AI, but let's script it to save tokens
$metaTitle = "Buy $serviceName | Instant & Cheap - LikexFollow";
$metaDesc = "Get $serviceName instantly. 100% Safe, Non-Drop, and Best Price in the market. Boost your $category today!";

if (strpos($content, 'Error') === false) {
    
    // 3. Save to service_seo table
    $insert = $db->prepare("INSERT INTO service_seo (service_id, page_title, meta_desc, ai_content) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE page_title=?, meta_desc=?, ai_content=?");
    $insert->execute([$serviceId, $metaTitle, $metaDesc, $content, $metaTitle, $metaDesc, $content]);

    logSeo($db, "Service SEO", "Updated Service ID: $serviceId ($serviceName)");
    echo "Success: SEO Generated.";

} else {
    logSeo($db, "Service SEO Failed", "Service ID: $serviceId - Error: $content");
    echo "Failed: $content";
}
?>