<?php
// File: user/ai_helper.php
session_start();
require_once '../includes/db.php';
require_once '../includes/AiEngine.php';

header('Content-Type: application/json');

// --- 1. RATE LIMITING (3s) ---
if (isset($_SESSION['last_ai_req']) && (time() - $_SESSION['last_ai_req'] < 3)) {
    echo json_encode(['status' => 'error', 'message' => 'Please wait a few seconds... (Cooling Down 🧊)']);
    exit;
}
$_SESSION['last_ai_req'] = time();

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Login required.']);
    exit;
}

$ai = new AiEngine($db);

// --- ACTION A: GENERATE COMMENTS ---
if ($action == 'generate_comments') {
    $service_name = $_POST['service_name'] ?? 'Post';
    $link = $_POST['link'] ?? '';
    $mood = $_POST['mood'] ?? 'Positive';
    
    // Short & Strict Prompt to save tokens
    $prompt = "
    Task: Write 10 short $mood comments for: '$service_name'.
    Format: One per line. No numbers. No quotes. Hinglish/English.
    Example:
    Wow amazing content 🔥
    Bohot ala yar ❤️
    ";

    $response = $ai->generateContent($prompt, 'text');
    
    // Cleanup
    $clean_comments = trim($response);
    $clean_comments = preg_replace('/^[\d-]+\.\s*/m', '', $clean_comments); 
    
    echo json_encode(['status' => 'success', 'data' => $clean_comments]);
    exit;
}

// --- ACTION B: SMART CHAT ASSISTANT ---
if ($action == 'ask_assistant') {
    $query = trim($_POST['query'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty query.']);
        exit;
    }

    // 🔥 SMART CONTEXT LOOKUP (Fixes Token Limit)
    // Extract keywords (e.g. "tiktok", "likes")
    $keywords = explode(' ', preg_replace('/[^a-zA-Z0-9 ]/', '', $query));
    $relevant_services = "";
    
    if(count($keywords) > 0) {
        // Construct Dynamic SQL: name LIKE %key1% OR name LIKE %key2%
        $sql = "SELECT id, name, service_rate FROM smm_services WHERE is_active=1 AND (";
        $params = [];
        foreach($keywords as $k) {
            if(strlen($k) > 2) { // Skip "is", "of" etc
                $sql .= "name LIKE ? OR category LIKE ? OR ";
                $params[] = "%$k%";
                $params[] = "%$k%";
            }
        }
        $sql = rtrim($sql, " OR ") . ") LIMIT 5"; // Fetch only Top 5 relevant services
        
        if(!empty($params)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($results as $s) {
                $relevant_services .= "[ID:{$s['id']} | {$s['name']} | {$s['service_rate']}]\n";
            }
        }
    }
    
    // Fallback if no specific match
    if(empty($relevant_services)) {
        $relevant_services = "No specific services found for this query. Suggest general categories.";
    }

    // Final Prompt (Small Size)
    $system_prompt = "
    You are 'Israr Liaqat Ai' (SMM Assistant).
    User Query: '$query'
    
    Relevant Services found in DB:
    $relevant_services
    
    Reply in User's Language (Hinglish/Urdu/English). Keep it short.
    ";

    $response = $ai->generateContent($system_prompt, 'text');
    
    echo json_encode(['status' => 'success', 'reply' => $response]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
?>