<?php
// File: includes/cron/auto_meta_tagger.php

// --- LOGGING START ---
ob_start(); // Output capture shuru

// --- 1. CONFIGURATION & SETUP ---
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AiEngine.php';

// Helper for logging output
function logStatus($msg, $type = 'info') {
    $color = ($type == 'success') ? 'green' : (($type == 'error') ? 'red' : 'black');
    echo "[" . date('H:i:s') . "] $msg\n"; // HTML hataya taake log clean rahe
}

echo "--- CRON START: " . date('Y-m-d H:i:s') . " ---\n";
echo "🕵️ AI SEO Meta Tagger Running...\n";

// --- 2. FIND A PAGE NEEDING SEO ---
$stmt = $db->query("
    SELECT * FROM site_seo 
    WHERE (meta_title IS NULL OR meta_title = '') 
       OR (meta_description IS NULL OR meta_description = '')
    ORDER BY id ASC 
    LIMIT 1
");

$page = $stmt->fetch();

if (!$page) {
    logStatus("✅ All pages are fully optimized! No missing tags found.", "success");
} else {
    $pageName = $page['page_name'];
    logStatus("Target Page Found: $pageName");

    // --- 3. PREPARE INTELLIGENT PROMPT ---
    $ai = new AiEngine($db);

    $context = "General page of an SMM Panel";
    if(strpos($pageName, 'login') !== false) $context = "Login page. Security focus.";
    if(strpos($pageName, 'register') !== false) $context = "Register page. Free Sign Up.";
    if(strpos($pageName, 'services') !== false) $context = "Services list. Cheap Rates.";
    if(strpos($pageName, 'index') !== false) $context = "Homepage. Best SMM Panel.";

    $prompt = "
    You are SEO Specialist for 'LikexFollow.com'.
    Page: '$pageName'. Context: $context
    REQUIREMENTS:
    1. Meta Title: Max 60 chars.
    2. Meta Description: Max 155 chars.
    3. Meta Keywords: 10 keywords.
    OUTPUT: Valid JSON only. { \"title\": \"...\", \"description\": \"...\", \"keywords\": \"...\" }
    ";

    // --- 4. GENERATE CONTENT ---
    logStatus("⏳ Contacting AI Brain...");
    $response = $ai->generateContent($prompt);

    // --- 5. CLEAN & DECODE JSON ---
    $cleanResponse = preg_replace('/```json|```/', '', $response);
    $cleanResponse = trim($cleanResponse);
    $data = json_decode($cleanResponse, true);

    // --- 6. SAVE TO DATABASE ---
    if (json_last_error() === JSON_ERROR_NONE && isset($data['title'])) {
        $update = $db->prepare("UPDATE site_seo SET meta_title=?, meta_description=?, meta_keywords=? WHERE id=?");
        if ($update->execute([$data['title'], $data['description'], $data['keywords'], $page['id']])) {
            logStatus("✅ SEO Updated Successfully!", "success");
            echo "Title: " . $data['title'] . "\n";
        } else {
            logStatus("❌ Database Update Failed.", "error");
        }
    } else {
        logStatus("❌ AI Parsing Error.", "error");
        echo "Response: $response\n";
    }
}

// --- LOGGING END (SAVE TO FILE) ---
$output = ob_get_clean();
echo $output; // Screen par bhi dikhaye
$logFile = __DIR__ . '/../../assets/logs/auto_meta_tagger.log';
file_put_contents($logFile, $output . "\n", FILE_APPEND);
?>