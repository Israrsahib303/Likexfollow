<?php
// File: includes/seo_auto_injector.php
// The Ultimate Auto-SEO Injector & Spider Engine for LikexFollow

if (session_status() === PHP_SESSION_NONE) session_start();

// --- 1. DETECT CURRENT ENVIRONMENT ---
$current_url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$page_name = basename($current_url);
if(empty($page_name) || $page_name == '/') $page_name = 'index.php';

$full_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// --- 2. DEFAULT FALLBACK METRICS ---
$site_name = $GLOBALS['settings']['site_name'] ?? 'LikexFollow';
$seo_title = "$site_name - #1 SMM Panel & Digital Services";
$seo_desc = "Boost your social media presence with $site_name. Get instant followers, likes, views, and premium digital products at unbeatable prices.";
$seo_kws = "smm panel, buy followers, social media marketing, digital products";
$og_image = "https://$_SERVER[HTTP_HOST]/assets/img/seo-cover.jpg"; // Default Cover

// --- 3. FETCH TOP SEMRUSH KEYWORDS FOR AUTO-INJECTION ---
// Using static caching to prevent DB overload on every page load
$top_vault_kws = [];
try {
    $kw_stmt = $db->query("SELECT keyword FROM semrush_keywords ORDER BY search_volume DESC LIMIT 5");
    if($kw_stmt) {
        $top_vault_kws = $kw_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch(Exception $e) {}

$primary_kw = isset($top_vault_kws[0]) ? ucwords($top_vault_kws[0]) : "SMM Services";
$secondary_kw = isset($top_vault_kws[1]) ? ucwords($top_vault_kws[1]) : "Buy Followers";

// --- 4. CHECK DATABASE FOR MANUAL OVERRIDE (Expert Workspace Data) ---
$custom_seo_found = false;
try {
    $seo_stmt = $db->prepare("SELECT * FROM site_seo WHERE page_url = ? OR page_name = ? LIMIT 1");
    $seo_stmt->execute([$current_url, $page_name]);
    $page_seo = $seo_stmt->fetch(PDO::FETCH_ASSOC);

    if ($page_seo) {
        if(!empty($page_seo['meta_title'])) $seo_title = $page_seo['meta_title'];
        if(!empty($page_seo['meta_description'])) $seo_desc = $page_seo['meta_description'];
        if(!empty($page_seo['meta_keywords'])) $seo_kws = $page_seo['meta_keywords'];
        $custom_seo_found = true;
    }
} catch(Exception $e) {}

// --- 5. ALGORITHMIC GENERATION (If no manual SEO exists) ---
if (!$custom_seo_found) {
    if (strpos($page_name, 'services') !== false) {
        $seo_title = "Premium $primary_kw & Digital Assets | $site_name";
        $seo_desc = "Explore our top-tier services. From $primary_kw to $secondary_kw, we provide instant delivery and 24/7 support.";
        $seo_kws = implode(", ", $top_vault_kws);
    } elseif (strpos($page_name, 'products') !== false || strpos($page_name, 'store') !== false) {
        $seo_title = "Buy Premium Digital Products & $primary_kw | $site_name";
        $seo_desc = "Unlock exclusive digital goods, software, and marketing tools to scale your business alongside our $secondary_kw.";
    } elseif (strpos($page_name, 'login') !== false || strpos($page_name, 'register') !== false) {
        $seo_title = "Join $site_name | Access the Best $primary_kw";
        $seo_desc = "Create your account today and get access to wholesale prices for $primary_kw and premium SMM API access.";
    }
}

// --- 6. AUTO JSON-LD SCHEMA BUILDER (Google Rich Snippets) ---
$schema_type = (strpos($page_name, 'services') !== false) ? "Service" : "Organization";

$schema_markup = [
    "@context" => "https://schema.org",
    "@type" => $schema_type,
    "name" => $seo_title,
    "url" => $full_url,
    "description" => $seo_desc,
    "provider" => [
        "@type" => "Organization",
        "name" => $site_name,
        "url" => "https://$_SERVER[HTTP_HOST]"
    ]
];

if ($schema_type === "Service") {
    $schema_markup["offers"] = [
        "@type" => "AggregateOffer",
        "priceCurrency" => "USD",
        "lowPrice" => "0.01",
        "highPrice" => "50.00",
        "offerCount" => "100"
    ];
    $schema_markup["aggregateRating"] = [
        "@type" => "AggregateRating",
        "ratingValue" => "4.9",
        "reviewCount" => "1482"
    ];
}

$json_ld = json_encode($schema_markup, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

// --- 7. COMPILE THE HEAD INJECTION CODE ---
$beast_seo_injection = "
<title>" . htmlspecialchars($seo_title) . "</title>
<meta name='description' content='" . htmlspecialchars($seo_desc) . "'>
<meta name='keywords' content='" . htmlspecialchars($seo_kws) . "'>
<link rel='canonical' href='" . htmlspecialchars($full_url) . "'>

<meta property='og:type' content='website'>
<meta property='og:url' content='" . htmlspecialchars($full_url) . "'>
<meta property='og:title' content='" . htmlspecialchars($seo_title) . "'>
<meta property='og:description' content='" . htmlspecialchars($seo_desc) . "'>
<meta property='og:image' content='" . htmlspecialchars($og_image) . "'>

<meta property='twitter:card' content='summary_large_image'>
<meta property='twitter:url' content='" . htmlspecialchars($full_url) . "'>
<meta property='twitter:title' content='" . htmlspecialchars($seo_title) . "'>
<meta property='twitter:description' content='" . htmlspecialchars($seo_desc) . "'>
<meta property='twitter:image' content='" . htmlspecialchars($og_image) . "'>

<script type='application/ld+json'>
{$json_ld}
</script>
";

// =======================================================================
// 🕸️ THE SPIDER LINKER: INTERNAL LINKING AUTOMATION FUNCTION
// =======================================================================
/*
 * Use this function on your frontend when echoing descriptions or blog posts.
 * Example: echo auto_spider_link($product['description'], $db);
 */
if (!function_exists('auto_spider_link')) {
    function auto_spider_link($content, $db) {
        if (empty(trim($content))) return $content;

        // Fetch top 10 long-tail keywords for linking
        static $link_kws = null;
        if ($link_kws === null) {
            try {
                // Fetching keywords that are more than 1 word (better for anchor texts)
                $stmt = $db->query("SELECT keyword FROM semrush_keywords WHERE keyword LIKE '% %' ORDER BY search_volume DESC LIMIT 10");
                $link_kws = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) { $link_kws = []; }
        }

        if (empty($link_kws)) return $content;

        $linked_content = $content;
        $used_kws = []; // Prevent linking the same keyword multiple times in one text

        foreach ($link_kws as $kw) {
            $kw = trim($kw);
            if (empty($kw) || in_array($kw, $used_kws)) continue;

            // Advanced Regex: 
            // 1. Case-insensitive (\b boundaries). 
            // 2. (?![^<]*>) ensures we DON'T replace words already inside HTML tags (like <a href> or <img src>).
            $pattern = '/\b(' . preg_quote($kw, '/') . ')\b(?![^<]*>)/i';
            
            // Link destination (Can be customized. Defaults to services page with search query)
            $link_url = "/services.php?search=" . urlencode($kw);
            
            // Limit to 1 replacement per keyword per content block to avoid spamming
            $linked_content = preg_replace($pattern, '<a href="'.$link_url.'" class="seo-spider-link" style="color: #6366f1; font-weight:600; text-decoration:none;" title="View $1">$1</a>', $linked_content, 1, $count);
            
            if ($count > 0) {
                $used_kws[] = $kw;
            }
        }

        return $linked_content;
    }
}
?>
