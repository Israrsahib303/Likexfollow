<?php
// api_preview.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = filter_var(trim($_POST['url']), FILTER_VALIDATE_URL);
    
    if (!$url) {
        echo json_encode(['success' => false, 'error' => 'Invalid URL']);
        exit;
    }

    // 🚀 MASTER TRICK: Use Facebook's Bot User-Agent. Platforms like Instagram, TikTok, 
    // and YouTube NEVER block Facebook/WhatsApp bots because they want link previews!
    $userAgent = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Support GZIP 
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept-Language: en-US,en;q=0.9',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Cache-Control: max-age=0'
    ]);
    
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        echo json_encode(['success' => false, 'error' => 'Fetch failed']);
        exit;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $tags = $doc->getElementsByTagName('meta');
    
    $title = '';
    $image = '';
    $description = '';

    // Extract Standard OG Tags
    foreach ($tags as $tag) {
        $property = strtolower($tag->getAttribute('property'));
        $name = strtolower($tag->getAttribute('name'));
        $content = $tag->getAttribute('content');

        if (($property === 'og:title' || $name === 'twitter:title') && empty($title)) {
            $title = $content;
        }
        if (($property === 'og:image' || $name === 'twitter:image') && empty($image)) {
            $image = $content;
        }
        if (($property === 'og:description' || $name === 'twitter:description' || $name === 'description') && empty($description)) {
            $description = $content;
        }
    }
    
    if (empty($title)) {
        $titles = $doc->getElementsByTagName('title');
        if ($titles->length > 0) {
            $title = $titles->item(0)->nodeValue;
        }
    }

    // --- 🚀 ADVANCED DATA EXTRACTION (Fixing the "-" Issue) 🚀 ---
    $domain = strtolower(parse_url($url, PHP_URL_HOST));

    // YOUTUBE ADVANCED SCRAPE (Force adding Views and Likes)
    if (strpos($domain, 'youtube.com') !== false || strpos($domain, 'youtu.be') !== false) {
        if (preg_match('/"viewCount":"(\d+)"/', $html, $matches) || preg_match('/<meta itemprop="interactionCount" content="(\d+)"/', $html, $matches)) {
            $v = number_format((float)$matches[1]);
            $description .= " " . $v . " Views";
        }
        if (preg_match('/"defaultText":\{"accessibility":\{"accessibilityData":\{"label":"([\d\.,KMBkmb]+)\s*likes"/', $html, $matches)) {
            $description .= " " . $matches[1] . " Likes";
        }
    }

    // Output JSON
    if ($title || $image || $description) {
        echo json_encode([
            'success' => true, 
            'title' => trim($title), 
            'image' => trim($image),
            'description' => trim($description)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No preview data found']);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
