<?php
// File: user/ai_helper.php
session_start();
require_once '../includes/db.php';
require_once '../includes/AiEngine.php';

header('Content-Type: application/json');

// --- 1. RATE LIMITING ---
if (isset($_SESSION['last_ai_req']) && (time() - $_SESSION['last_ai_req'] < 2)) {
    echo json_encode(['status' => 'error', 'message' => 'Please wait a moment... (Thinking 🧠)']);
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

// --- ACTION A: GENERATE COMMENTS (MOOD FIX) ---
if ($action == 'generate_comments') {
    $service_name = $_POST['service_name'] ?? 'Post';
    $link = $_POST['link'] ?? '';
    $mood = $_POST['mood'] ?? 'Positive';
    
    $prompt = "
    Task: Write 10 short, engaging $mood social media comments for: '$service_name'.
    Context Link: '$link'.
    
    RULES:
    1. Language: Hinglish (Urdu + English Mix) + Emojis.
    2. Format: Strictly ONE comment per line.
    3. NO numbering (1. 2.). NO quotes.
    4. NO Intro/Outro. Just the list.
    ";

    $response = $ai->generateContent($prompt, 'text');
    $clean_comments = trim(preg_replace('/^[\d-]+\.\s*/m', '', $response)); 
    
    echo json_encode(['status' => 'success', 'data' => $clean_comments]);
    exit;
}

// --- ACTION B: SMART CHAT ASSISTANT ---
if ($action == 'ask_assistant') {
    $query = trim($_POST['query'] ?? '');
    $currCode = $_POST['curr_code'] ?? 'USD';
    $currRate = (float)($_POST['curr_rate'] ?? 1);
    $currSym  = $_POST['curr_sym'] ?? '$';
    
    if (empty($query)) {
        echo json_encode(['status' => 'error', 'message' => 'Empty query.']);
        exit;
    }

    // 🔥 SMART CONTEXT LOOKUP
    $keywords = explode(' ', preg_replace('/[^a-zA-Z0-9 ]/', '', $query));
    $relevant_services = "";
    
    if(count($keywords) > 0) {
        $sql = "SELECT id, name, category, service_rate FROM smm_services WHERE is_active=1 AND (";
        $params = [];
        foreach($keywords as $k) {
            if(strlen($k) > 2) { 
                $sql .= "name LIKE ? OR category LIKE ? OR ";
                $params[] = "%$k%";
                $params[] = "%$k%";
            }
        }
        $sql = rtrim($sql, " OR ") . ") LIMIT 5"; 
        
        if(!empty($params)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($results as $s) {
                // Convert Price for Context
                $localPrice = number_format($s['service_rate'] * $currRate, 2);
                $relevant_services .= "[ID:{$s['id']} | Name:{$s['name']} | Price: $currSym $localPrice]\n";
            }
        }
    }
    
    if(empty($relevant_services)) {
        $relevant_services = "No specific services found. Guide user generally.";
    }

    // FINAL PROMPT (UPDATED AS REQUESTED)
    $system_prompt = "
    You are 'Israr Liaqat Ai' (SMM Expert) — assistant of Israr Liaqat (not Israr Liaqat himself), working strictly inside LikexFollow.com.

    User Query: '$query'
    User Currency: $currCode

    RELEVANT SERVICES (Database):
    $relevant_services

    CRITICAL CLARIFICATIONS:
    - Any wording like '5k / 10k / 20k speed' in a service name refers to DELIVERY SPEED, not quantity.
      Example: 'Speed 5k/day' means delivery rate up to 5,000 per day, not a total quantity of 5,000.
    - All service prices are PER 1000 units unless clearly stated otherwise.

    INSTRUCTIONS:
    1. Reply in English, Hinglish, Urdu, detect user language.
    2. Answer ONLY what the customer asked. No extra explanations, no upselling, no unrelated services.
    3. Show ONLY the services the customer explicitly asked for. Do not include any other services.
    4. If the user asks for an SMM tip or trick (not a service), give a short, actionable answer strictly within the LikexFollow.com context.
    5. Format the output cleanly using <b>Bold</b> for labels.
    6. If recommending a service, strictly follow this exact format (no changes allowed):
       <br><b>Service:</b> Name  
       <br><b>Price:</b> Price per 1000 ($currCode)  
       <br><b>ID:</b> ID  
       <br><b>View Service:</b> [VIEW:ID]
    7. The '[VIEW:ID]' tag is CRITICAL and MUST be included for every listed service to show a clickable button.
    8. If the requested service is NOT found in the database list provided above, reply only:
       <b>Status:</b> Requested service is not available at the moment.
       (Do NOT suggest alternatives unless the user explicitly asks.)
    9. Keep the response direct, concise, and strictly on-topic. No emojis. No marketing language.
    ";

    // Use 'chat' mode to allow <b> tags
    $response = $ai->generateContent($system_prompt, 'chat');
    
    echo json_encode(['status' => 'success', 'reply' => $response]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
?>