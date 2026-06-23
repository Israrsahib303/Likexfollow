<?php
// proxy.php - CORS Bypass Proxy for SMM API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

$apiUrl = isset($_POST['apiUrl']) ? trim($_POST['apiUrl']) : '';
$apiKey = isset($_POST['apiKey']) ? trim($_POST['apiKey']) : '';

if (empty($apiUrl) || empty($apiKey)) {
    echo json_encode(['error' => 'API URL aur API Key dono zaroori hain.']);
    exit;
}

// Data to send to SMM Panel
$postFields = [
    'key' => $apiKey,
    'action' => 'services'
];

// Server-to-Server cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Agar SSL error aaye toh is line ko uncomment kar dena

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(['error' => 'cURL Error: ' . $error]);
} else {
    echo $response; // Return the exact data from the panel back to our JS
}
?>
