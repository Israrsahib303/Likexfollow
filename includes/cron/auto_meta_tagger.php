<?php
// File: includes/cron/auto_meta_tagger.php

// --- 1. CONFIGURATION & SETUP ---
// Adjust paths to reach root includes
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AiEngine.php';

// Helper for logging output
function logStatus($msg, $type = 'info') {
    $color = ($type == 'success') ? 'green' : (($type == 'error') ? 'red' : 'black');
    echo "<div style='color: $color; font-family: monospace; margin-bottom: 5px;'>[" . date('H:i:s') . "] $msg</div>";
}

echo "<h2>🕵️ AI SEO Meta Tagger Running...</h2>";

// --- 2. FIND A PAGE NEEDING SEO ---
// Aisa page dhoondo jiska Meta Title ya Description khali ho
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
    exit;
}

$pageName = $page['page_name'];
logStatus("Target Page Found: <strong>$pageName</strong>");

// --- 3. PREPARE INTELLIGENT PROMPT ---
$ai = new AiEngine($db);

// Page Context Logic (AI ko samjhane ke liye ke ye page kis bare mein hai)
$context = "General page of an SMM Panel";
if(strpos($pageName, 'login') !== false) $context = "Login page for existing users. Emphasize security.";
if(strpos($pageName, 'register') !== false) $context = "Registration page for new users. Emphasize 'Free Sign Up' and 'No Credit Card Needed'.";
if(strpos($pageName, 'services') !== false) $context = "List of all social media services (TikTok, IG, YouTube). Emphasize 'Cheap Rates' and 'Instant Delivery'.";
if(strpos($pageName, 'index') !== false || $pageName == 'home') $context = "Main Landing Page / Homepage. Emphasize 'Best SMM Panel in Pakistan', 'Trusted', 'API Support'.";
if(strpos($pageName, 'terms') !== false) $context = "Terms of Service page.";

$prompt = "
You are the SEO Specialist for 'LikexFollow.com'.
Your task is to write high-ranking Meta Tags for the file: '$pageName'.
Context: $context

REQUIREMENTS:
1. Meta Title: Max 60 chars. Must be catchy. Include Brand Name.
2. Meta Description: Max 155 chars. Persuasive copy to get clicks. Include keywords like 'Cheap', 'Real', 'Instant'.
3. Meta Keywords: 10 comma-separated high-volume keywords relevant to SMM.

OUTPUT FORMAT:
Provide ONLY a valid JSON object. Do not write anything else.
{
  \"title\": \"...\",
  \"description\": \"...\",
  \"keywords\": \"...\"
}
";

// --- 4. GENERATE CONTENT ---
logStatus("⏳ Contacting AI Brain...");
$response = $ai->generateContent($prompt);

// --- 5. CLEAN & DECODE JSON ---
// Kabhi kabhi AI ```json ``` laga deta hai, usay hatana padega
$cleanResponse = preg_replace('/```json|```/', '', $response);
$cleanResponse = trim($cleanResponse);

$data = json_decode($cleanResponse, true);

// --- 6. SAVE TO DATABASE ---
if (json_last_error() === JSON_ERROR_NONE && isset($data['title'])) {
    
    $title = $data['title'];
    $desc = $data['description'];
    $keys = $data['keywords'];

    $update = $db->prepare("UPDATE site_seo SET meta_title=?, meta_description=?, meta_keywords=? WHERE id=?");
    if ($update->execute([$title, $desc, $keys, $page['id']])) {
        
        logStatus("✅ SEO Updated Successfully!", "success");
        echo "<hr>";
        echo "<strong>New Title:</strong> $title <br>";
        echo "<strong>New Desc:</strong> $desc <br>";
        echo "<strong>Keywords:</strong> $keys <br>";
        
    } else {
        logStatus("❌ Database Update Failed.", "error");
    }

} else {
    logStatus("❌ AI Parsing Error. Response was not valid JSON.", "error");
    echo "<div style='background:#eee; padding:10px; border:1px solid #ccc;'>$response</div>";
}
?>