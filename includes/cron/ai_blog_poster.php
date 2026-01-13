<?php
// File: includes/cron/ai_blog_poster.php
// Author: LikexFollow Automation
// Logic: Hybrid Content Strategy + Smart Interlinking + Error Fix

// --- 1. SETUP & CONNECTIONS ---
if (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
} elseif (file_exists(__DIR__ . '/../../includes/db.php')) {
    require_once __DIR__ . '/../../includes/db.php';
}

require_once __DIR__ . '/../AiEngine.php';

echo "<h2>🤖 AI Auto-Blogger: Hybrid Engine Started...</h2>";

function cleanSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    return trim($string, '-');
}

// =================================================================
// STEP 1: CHOOSE A TOPIC STRATEGY
// =================================================================

// Priority: Check for Manual Draft
$stmt = $db->query("SELECT * FROM blogs WHERE status='draft' AND (content IS NULL OR content = '') LIMIT 1");
$draft = $stmt->fetch();

$userTopic = "";
$promptContext = "";
$blogId = 0;
$slug = "";

if ($draft) {
    // --- MODE 1: MANUAL DRAFT ---
    echo "📌 Mode: Processing Manual Draft...<br>";
    $blogId = $draft['id'];
    $userTopic = $draft['title'];
    $slug = $draft['slug'];
    $promptContext = "Write specific content matching this exact title.";

} else {
    // --- MODE 2: AUTO GENERATION (HYBRID) ---
    $chance = rand(1, 100);
    
    if ($chance <= 60) {
        // [60%] STRATEGY A: SERVICE PROMOTION
        echo "🛒 Strategy: Service Promotion (Selling Mode)<br>";
        
        $svcStmt = $db->query("SELECT name, category, service_rate FROM smm_services WHERE is_active=1 ORDER BY RAND() LIMIT 1");
        $service = $svcStmt->fetch();
        
        if ($service) {
            $serviceName = $service['name'];
            $price = $service['service_rate'];
            $cat = $service['category'];
            
            $templates = [
                "Buy $serviceName Cheap - Best Price in 2026",
                "How to Grow your $cat with $serviceName",
                "Why Buying $serviceName is Safe on LikexFollow",
                "Boost Your $cat Engagement: $serviceName Review",
                "Best SMM Panel for $serviceName in Pakistan",
                "Get $serviceName Instantly - No Password Required"
            ];
            $userTopic = $templates[array_rand($templates)];
            // 🔥 Advanced Instruction: Interlinking
            $promptContext = "Focus on selling '$serviceName' ($cat). Price: Rs $price. 
            IMPORTANT: When you mention '$serviceName' or '$cat', format it as an HTML link like <a href='../../services.php'>$serviceName</a>. 
            Emphasize instant delivery and safety.";
        } else {
            $userTopic = "How to Become Famous on Social Media 2026";
        }
        
    } elseif ($chance <= 80) {
        // [20%] STRATEGY B: PANEL BRANDING
        echo "🏢 Strategy: Panel Branding<br>";
        
        $panelTopics = [
            "How to Start Your Own SMM Business in Pakistan",
            "LikexFollow API Integration: Developer Guide 2026",
            "Why LikexFollow is the Safest SMM Panel?",
            "How to Resell SMM Services and Earn Money Online",
            "Top 5 Reasons Influencers Choose LikexFollow",
            "How to Use JazzCash/Easypaisa to Buy Followers"
        ];
        $userTopic = $panelTopics[array_rand($panelTopics)];
        $promptContext = "Focus on 'LikexFollow.com'. Mention API support, 24/7 WhatsApp support, Local Payments. 
        IMPORTANT: Link the word 'Register' to '../../register.php' and 'Services' to '../../services.php'.";
        
    } else {
        // [20%] STRATEGY C: VIRAL TRENDS
        echo "🔥 Strategy: Viral Trends & Tips<br>";
        
        $trendTopics = [
            "Secret TikTok Algorithm Hacks for 2026",
            "How to Get Verified on Instagram (Blue Tick Guide)",
            "5 Ways to Monetize Your YouTube Channel Fast",
            "Best Time to Post on TikTok in Pakistan",
            "How to Go Viral on Instagram Reels Instantly"
        ];
        $userTopic = $trendTopics[array_rand($trendTopics)];
        $promptContext = "Write an educational guide. Mention 'LikexFollow' naturally as a recommended tool.
        IMPORTANT: If you mention 'Buy Followers' or 'SMM Panel', link it to '../../index.php'.";
    }

    // --- CHECK DUPLICATION ---
    $check = $db->prepare("SELECT id FROM blogs WHERE title LIKE ?");
    $check->execute(["%$userTopic%"]);
    if ($check->rowCount() > 0) {
        $userTopic .= " (Updated Guide " . rand(2026, 2030) . ")";
    }

    // Slug Create & DB Entry
    $slug = cleanSlug($userTopic) . '-' . rand(100, 9999);
    $insert = $db->prepare("INSERT INTO blogs (title, slug, status, created_at) VALUES (?, ?, 'draft', NOW())");
    $insert->execute([$userTopic, $slug]);
    $blogId = $db->lastInsertId();
}

// =================================================================
// STEP 2: GENERATE CONTENT
// =================================================================

$ai = new AiEngine($db);

$systemInstruction = "You are the Senior Content Writer for 'LikexFollow.com' - The #1 SMM Panel.
TASK: Write a comprehensive, SEO-optimized blog post.

TOPIC: $userTopic
CONTEXT: $promptContext

STRUCTURE & RULES:
1. **Introduction**: Hook the reader. Define the problem.
2. **Body**: Use HTML tags <h2>, <h3> for headings. Use <ul>, <li> for lists.
3. **Internal Links**: Use the links requested in context.
4. **Tone**: Professional, exciting, and persuasive.
5. **Formatting**: Return ONLY valid HTML body content. Do NOT use Markdown.
6. **Call to Action**: End with a strong reason to sign up.

Make it long (600+ words), engaging, and human-like.";

echo "⏳ Generating Content for: <strong>$userTopic</strong>...<br>";
$content = $ai->generateContent($systemInstruction);

// Validation
if (strpos($content, 'Error') !== false || strlen($content) < 200) {
    echo "❌ AI Generation Failed: " . htmlspecialchars($content);
    $db->query("DELETE FROM blogs WHERE id = $blogId");
    exit;
}

// =================================================================
// STEP 3: GENERATE META DESC
// =================================================================
$metaPrompt = "Write a 155-character persuasive SEO meta description for a blog post titled '$userTopic'.";
$metaDesc = $ai->generateContent($metaPrompt);
$metaDesc = strip_tags($metaDesc);

// =================================================================
// STEP 4: PUBLISH (FIXED SQL)
// =================================================================
// Fix: Removed 'updated_at' column from query
$update = $db->prepare("UPDATE blogs SET content=?, meta_desc=?, status='published' WHERE id=?");
$update->execute([$content, $metaDesc, $blogId]);

echo "<div style='background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-top:20px; border:1px solid #bbf7d0;'>
        ✅ <strong>Success!</strong> New Article Published.<br>
        <strong>Title:</strong> $userTopic<br>
        <hr style='margin:10px 0; border-color:#bbf7d0;'>
        <a href='../../blog_view.php?slug=$slug' target='_blank' style='display:inline-block; padding:8px 15px; background:#166534; color:white; text-decoration:none; border-radius:5px; font-weight:bold;'>View Article &rarr;</a>
      </div>";
?>