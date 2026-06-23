<?php
// File: panel/semrush_topic_research.php
// Error Reporting ON to catch the 500 error
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '_header.php';

$message = ''; $msg_type = '';

// --- 0. ADVANCED AUTO-CREATE TOPIC TABLE ---
try {
    $db->exec("CREATE TABLE IF NOT EXISTS semrush_content_ideas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        core_topic VARCHAR(255),
        headline_idea VARCHAR(500),
        search_volume INT DEFAULT 0,
        difficulty INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Idea',
        data_date DATE,
        UNIQUE KEY unique_headline (core_topic, headline_idea)
    )");
    $table_exists = true;
} catch (PDOException $e) {
    $table_exists = false;
}

if(!function_exists('sanitize')){ function sanitize($str) { return htmlspecialchars(strip_tags(trim($str))); } }

// --- 1. ACTION: SEND TO WRITER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_writer'])) {
    $idea_id = (int)$_POST['idea_id'];
    $stmt = $db->prepare("UPDATE semrush_content_ideas SET status = 'Writing' WHERE id = ?");
    if($stmt->execute([$idea_id])) {
        $message = "Content Idea sent to the Writing pipeline! ✍️"; 
        $msg_type = "success";
    }
}

// --- 2. UNIVERSAL CSV PARSER (SERVER-SAFE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['topic_file'])) {
    $file = $_FILES['topic_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $handle = fopen($file['tmp_name'], "r");
        if ($handle !== FALSE) {
            $headers = fgetcsv($handle, 10000, ",");
            if(isset($headers[0])) $headers[0] = preg_replace('/[\xEF\xBB\xBF]/', '', $headers[0]);
            
            $topic_idx = -1; $head_idx = -1; $vol_idx = -1; $diff_idx = -1;
            foreach ($headers as $index => $col) {
                $c = strtolower(trim($col));
                if ($c == 'card' || $c == 'topic' || $c == 'core topic') $topic_idx = $index;
                if ($c == 'headline' || $c == 'subtopic' || $c == 'idea') $head_idx = $index;
                if (strpos($c, 'volume') !== false) $vol_idx = $index;
                if (strpos($c, 'difficulty') !== false || strpos($c, 'kd') !== false) $diff_idx = $index;
            }

            if ($topic_idx === -1 || $head_idx === -1) {
                $message = "⚠️ Could not find required columns. Ensure file is CSV format.";
                $msg_type = "danger";
            } else {
                $inserted = 0; $updated = 0;
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("INSERT INTO semrush_content_ideas (core_topic, headline_idea, search_volume, difficulty, data_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE search_volume=?, difficulty=?");
                    $current_date = date('Y-m-d');
                    
                    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                        if (count($data) <= max($topic_idx, $head_idx)) continue;
                        $topic = sanitize($data[$topic_idx]);
                        $headline = sanitize($data[$head_idx] ?? '');
                        if(empty($topic) || empty($headline)) continue;

                        $vol = ($vol_idx !== -1 && isset($data[$vol_idx])) ? (int)str_replace(',', '', $data[$vol_idx]) : 0;
                        $diff = ($diff_idx !== -1 && isset($data[$diff_idx])) ? (int)$data[$diff_idx] : 0;
                        
                        $check = $db->prepare("SELECT id FROM semrush_content_ideas WHERE core_topic = ? AND headline_idea = ?");
                        $check->execute([$topic, $headline]);
                        if($check->fetchColumn()) $updated++; else $inserted++;
                        $stmt->execute([$topic, $headline, $vol, $diff, $current_date, $vol, $diff]);
                    }
                    $db->commit();
                    $message = "Synced! Added $inserted new headlines, updated $updated metrics.";
                    $msg_type = "success";
                } catch (Exception $e) {
                    $db->rollBack();
                    $message = "DB Error: " . $e->getMessage(); $msg_type = "danger";
                }
            }
            fclose($handle);
        } else {
            $message = "File access error."; $msg_type = "danger";
        }
    }
}

// --- 3. DATA FETCHING ---
$total_ideas = 0; $total_topics = 0; $in_writing = 0; $grouped_topics = [];
if($table_exists) {
    $total_ideas = $db->query("SELECT COUNT(*) FROM semrush_content_ideas WHERE status = 'Idea'")->fetchColumn() ?: 0;
    $total_topics = $db->query("SELECT COUNT(DISTINCT core_topic) FROM semrush_content_ideas WHERE status = 'Idea'")->fetchColumn() ?: 0;
    $in_writing = $db->query("SELECT COUNT(*) FROM semrush_content_ideas WHERE status = 'Writing'")->fetchColumn() ?: 0;
    $ideas_query = $db->query("SELECT * FROM semrush_content_ideas WHERE status = 'Idea' ORDER BY search_volume DESC LIMIT 500");
    $all_ideas = $ideas_query->fetchAll(PDO::FETCH_ASSOC);
    foreach($all_ideas as $idea) {
        $core = $idea['core_topic'];
        if(!isset($grouped_topics[$core])) $grouped_topics[$core] = ['total_volume' => 0, 'ideas' => []];
        $grouped_topics[$core]['ideas'][] = $idea;
        $grouped_topics[$core]['total_volume'] += $idea['search_volume'];
    }
    uasort($grouped_topics, function($a, $b) { return $b['total_volume'] <=> $a['total_volume']; });
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    :root { --p-purple: #6366f1; --p-purple-dark: #4f46e5; --l-purple: #eef2ff; --b-color: #e2e8f0; --bg-light: #f8fafc; --t-dark: #0f172a; --t-muted: #64748b; }
    body { background-color: #f1f5f9; font-family: 'Outfit', sans-serif; }
    .topic-board-card { background: #fff; border-radius: 20px; border: 1px solid var(--b-color); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02); }
    .btn-action { background: linear-gradient(135deg, var(--p-purple) 0%, var(--p-purple-dark) 100%); color: #fff; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; }
    .masonry-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 2rem; }
    .topic-card { background: #fff; border-radius: 16px; border: 1px solid var(--b-color); overflow: hidden; }
    .topic-header { background: var(--l-purple); padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; }
    .idea-item { padding: 1.2rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; }
    .search-input { width: 100%; padding: 12px 20px; border: 2px solid var(--b-color); border-radius: 12px; }
</style>

<div class="container-fluid p-4" style="max-width: 1600px;">
    <div class="topic-board-card p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div>
            <h2>Topic Ideation Matrix</h2>
            <p>Upload CSV exports. If you have Excel (.xlsx), please "Save As" .csv in Excel first.</p>
        </div>
        <form method="POST" enctype="multipart/form-data" id="topicForm">
            <input type="file" name="topic_file" id="topic_file" accept=".csv" class="d-none" onchange="this.form.submit()">
            <button type="button" class="btn-action" onclick="document.getElementById('topic_file').click()">Import CSV</button>
        </form>
    </div>

    <?php if ($message): ?> <div class="alert alert-<?= $msg_type ?> p-3 mb-4 fw-bold rounded-4 border-0 shadow-sm"><?= $message ?></div> <?php endif; ?>

    <div class="masonry-grid" id="topicGrid">
        <?php foreach($grouped_topics as $core => $data): ?>
        <div class="topic-card">
            <div class="topic-header">
                <h3 class="topic-title"><?= htmlspecialchars($core) ?></h3>
            </div>
            <ul class="idea-list">
                <?php foreach($data['ideas'] as $idea): ?>
                <li class="idea-item">
                    <span><?= htmlspecialchars($idea['headline_idea']) ?></span>
                    <form method="POST"><input type="hidden" name="idea_id" value="<?= $idea['id'] ?>"><button type="submit" name="send_to_writer" class="btn btn-sm btn-outline-primary">Share</button></form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '_footer.php'; ?>