<?php
// --- DEBUG TOOL: RAW API INSPECTOR ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/smm_api.class.php';

// 1. Get Active Provider
$stmt = $db->query("SELECT * FROM smm_providers WHERE is_active = 1 LIMIT 1");
$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    die("❌ Koi Active Provider nahi mila Database mein.");
}

echo "<h1>🔍 Debugging Provider: " . htmlspecialchars($provider['name']) . "</h1>";
echo "<p><strong>API URL:</strong> " . htmlspecialchars($provider['api_url']) . "</p>";

// 2. Manual cURL Request (Bypass Class to see RAW Data)
function getRawResponse($url, $key, $action) {
    $post = [
        'key' => $key,
        'action' => $action,
        'details' => 1, // Trick 1
        'description' => 1 // Trick 2
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 3. Check 'services' Action
echo "<h3>1️⃣ Testing Action: 'services'</h3>";
$raw = getRawResponse($provider['api_url'], $provider['api_key'], 'services');
$json = json_decode($raw, true);

if ($json) {
    echo "<p>✅ API Connected Successfully. Showing First Service Data:</p>";
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>";
    // Sirf pehli service dikhayenge taake page bhare na
    print_r($json[0] ?? $json['services'][0] ?? $json); 
    echo "</pre>";
    
    // Check specific keys
    $s = $json[0] ?? $json['services'][0] ?? [];
    echo "<ul>";
    echo "<li><strong>Name Key:</strong> " . ($s['name'] ?? '❌ Missing') . "</li>";
    echo "<li><strong>Description Key:</strong> " . ($s['description'] ?? $s['desc'] ?? $s['info'] ?? '❌ MISSING (Provider data nahi bhej raha)') . "</li>";
    echo "<li><strong>Time Key:</strong> " . ($s['average_time'] ?? $s['avg_time'] ?? $s['time'] ?? '❌ MISSING') . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ Failed to get JSON. Raw Output:</p>";
    echo "<textarea style='width:100%; height:100px;'>$raw</textarea>";
}

?>