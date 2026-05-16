<?php
// File: includes/cron/ai_seo_worker.php

// --- LOGGING START ---
ob_start();

// Setup
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AiEngine.php';

// Helper for Logging DB
function logSeo($db, $action, $details) {
    $stmt = $db->prepare("INSERT INTO seo_logs (action, details) VALUES (?, ?)");
    $stmt->execute([$action, $details]);
}

echo "--- CRON START: " . date('Y-m-d H:i:s') . " ---\n";

// 1. Find ONE service that needs SEO
$stmt = $db->query("
    SELECT s.id, s.name, s.category 
    FROM smm_services s
    LEFT JOIN service_seo seo ON s.id = seo.service_id
    WHERE s.is_active = 1 AND seo.ai_content IS NULL
    LIMIT 1
");

$service = $stmt->fetch();

if (!$service) {
    echo "✅ All active services have SEO content. Good job!\n";
} else {
    $serviceId = $service['id'];
    $serviceName = $service['name'];
    $category = $service['category'];

    echo "Processing Service ID: $serviceId - $serviceName \n";

    // 2. Generate SEO Data via AI
    $ai = new AiEngine($db);
    $promptDesc = "Write a 300-word SEO description for '$serviceName' ($category). Focus on benefits. Use HTML <p>, <ul> tags.";
    
    $content = $ai->generateContent($promptDesc);
    $metaTitle = "Buy $serviceName | Instant & Cheap - LikexFollow";
    $metaDesc = "Get $serviceName instantly. 100% Safe. Boost your $category today!";

    if (strpos($content, 'Error') === false && !empty($content)) {
        // 3. Save to service_seo table
        $insert = $db->prepare("INSERT INTO service_seo (service_id, page_title, meta_desc, ai_content) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE page_title=?, meta_desc=?, ai_content=?");
        $insert->execute([$serviceId, $metaTitle, $metaDesc, $content, $metaTitle, $metaDesc, $content]);

        logSeo($db, "Service SEO", "Updated Service ID: $serviceId");
        echo "✅ Success: SEO Generated.\n";
    } else {
        logSeo($db, "Service SEO Failed", "Service ID: $serviceId - Error: $content");
        echo "❌ Failed: $content\n";
    }
}

// --- LOGGING END ---
$output = ob_get_clean();
echo $output;
$logFile = __DIR__ . '/../../assets/logs/ai_seo.log';
file_put_contents($logFile, $output . "\n", FILE_APPEND);
?>