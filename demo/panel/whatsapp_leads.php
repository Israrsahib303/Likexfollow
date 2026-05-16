<?php
// panel/whatsapp_leads.php - Beast9 Premium UI
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// 1. Admin Check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// 2. EXPORT LOGIC (Text File Download)
if (isset($_GET['export_txt'])) {
    $stmt = $db->query("SELECT phone, name FROM users WHERE phone IS NOT NULL AND phone != '' ORDER BY id DESC");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "whatsapp_leads_" . date('Y-m-d') . ".txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    foreach ($leads as $lead) {
        // Format: Phone (Name)
        echo $lead['phone'] . " | " . $lead['name'] . "\n";
    }
    exit;
}

// 3. FETCH DATA (For Table)
$search = $_GET['search'] ?? '';
$sql = "SELECT id, name, email, phone, created_at, google_id FROM users WHERE phone IS NOT NULL AND phone != ''";

if ($search) {
    $sql .= " AND (name LIKE :s OR email LIKE :s OR phone LIKE :s)";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
if ($search) $stmt->bindValue(':s', "%$search%");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_leads = count($users);

// Include Header
require_once '_header.php'; 
?>

<style>
    :root {
        --wa-primary: #25D366;
        --wa-dark: #075E54;
        --dark-bg: #1e293b;
        --card-bg: #ffffff;
        --text-main: #334155;
        --text-muted: #64748b;
    }

    body {
        background-color: #f1f5f9; /* Softer background */
    }

    /* ANIMATIONS */
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* SCROLLBAR STYLING (The Fix) */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9; 
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1; 
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8; 
    }

    /* STATS CARD */
    .wa-stats-card {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        color: white; 
        border-radius: 20px; 
        padding: 30px;
        box-shadow: 0 20px 40px -10px rgba(37, 211, 102, 0.5);
        display: flex; 
        align-items: center; 
        justify-content: space-between;
        margin-bottom: 30px; 
        position: relative; 
        overflow: hidden;
        animation: slideUp 0.5s ease-out;
    }
    .wa-stats-card h2 { margin:0; font-size: 3.5rem; font-weight: 800; line-height: 1; text-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .wa-stats-card p { margin:0; opacity: 0.9; font-weight: 600; font-size: 1.1rem; letter-spacing: 0.5px; }
    .wa-stats-card i { 
        font-size: 8rem; 
        opacity: 0.15; 
        position: absolute; 
        right: -20px; 
        bottom: -30px; 
        transform: rotate(-15deg); 
    }
    
    /* TABLE CONTAINER & SCROLLING */
    .glass-table-container {
        background: white; 
        border-radius: 20px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
        padding: 0; /* Remove padding to let table hit edges */
        overflow: hidden;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.6s ease-out;
        /* SCROLL FIX: Ensure container has height constraints if needed, or let table scroll */
    }
    
    .table-scroll-wrap {
        overflow-y: auto;
        overflow-x: auto;
        max-height: 65vh; /* Fixed height for sticky header effect */
    }

    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    
    /* STICKY HEADER */
    .modern-table thead th { 
        position: sticky; 
        top: 0; 
        z-index: 10;
        background: #f8fafc;
        text-align: left; 
        padding: 20px 25px; 
        color: var(--text-muted); 
        font-weight: 700; 
        font-size: 0.8rem; 
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }

    .modern-table td { 
        padding: 18px 25px; 
        border-bottom: 1px solid #f1f5f9; 
        color: var(--text-main); 
        font-size: 0.95rem; 
        vertical-align: middle; 
        transition: background 0.2s;
    }
    
    .modern-table tr:last-child td { border-bottom: none; }
    .modern-table tbody tr:hover td { background: #f0fdf4; /* Light green hover */ }

    /* USER AVATAR */
    .user-avatar { 
        width: 45px; height: 45px; 
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); 
        color: #475569;
        border-radius: 12px; 
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.2rem; margin-right: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .user-info-flex { display: flex; align-items: center; }
    
    /* BADGES */
    .wa-badge {
        background: #dcfce7; color: #16a34a; 
        padding: 8px 15px; border-radius: 50px;
        font-weight: 700; font-size: 0.9rem; 
        display: inline-flex; align-items: center; gap: 8px;
        text-decoration: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
    }
    .wa-badge:hover { 
        background: #25D366; color: white; 
        transform: translateY(-2px); 
        box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
    }

    .source-badge { font-size: 0.75rem; padding: 5px 10px; border-radius: 8px; font-weight: 700; letter-spacing: 0.5px; }
    .src-google { background: #e0f2fe; color: #0284c7; }
    .src-manual { background: #f1f5f9; color: #64748b; }

    /* ACTION BAR */
    .action-bar { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 25px; flex-wrap: wrap; gap: 15px; 
    }
    
    .search-inp { 
        padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 12px; 
        width: 320px; outline: none; transition: 0.3s;
        font-size: 0.95rem; background: white;
    }
    .search-inp:focus { border-color: #25D366; box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.1); }
    
    .btn-export {
        background: #1e293b; color: white; padding: 14px 24px; border-radius: 12px;
        text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 10px;
        transition: 0.3s; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .btn-export:hover { background: #0f172a; transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); }
    
    .btn-chat {
        background: linear-gradient(135deg, #25D366 0%, #22c55e 100%);
        padding: 10px 20px; font-size: 0.85rem; border-radius: 8px;
    }

    @media (max-width: 768px) {
        .wa-stats-card { flex-direction: column; text-align: left; align-items: flex-start; }
        .wa-stats-card i { right: -10px; bottom: 10px; font-size: 5rem; }
        .action-bar { flex-direction: column; align-items: stretch; }
        .search-inp { width: 100%; }
        .btn-export { justify-content: center; }
    }
</style>

<div class="container-fluid" style="padding: 30px; max-width: 1400px; margin: 0 auto;">

    <div class="wa-stats-card">
        <div style="z-index: 2;">
            <h2><?= number_format($total_leads) ?></h2>
            <p>Total WhatsApp Leads Collected</p>
        </div>
        <i class="fa-brands fa-whatsapp"></i>
    </div>

    <div class="action-bar">
        <form method="GET" style="display:flex; align-items:center; gap:10px; flex: 1;">
            <input type="text" name="search" class="search-inp" placeholder="Search name, number or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-export" style="background:#4f46e5; padding:15px 20px;"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        <a href="?export_txt=1" class="btn-export">
            <i class="fa-solid fa-file-arrow-down"></i> Export .TXT
        </a>
    </div>

    <div class="glass-table-container">
        <div class="table-scroll-wrap">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>User Profile</th>
                        <th>WhatsApp Number</th>
                        <th>Source</th>
                        <th>Joined Date</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:60px; color: #94a3b8;">
                            <i class="fa-regular fa-folder-open" style="font-size: 3rem; margin-bottom: 15px; display:block;"></i>
                            No leads found.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach($users as $u): 
                            $initial = strtoupper(substr($u['name'], 0, 1));
                            $is_google = !empty($u['google_id']);
                            $clean_phone = preg_replace('/[^0-9]/', '', $u['phone']);
                        ?>
                        <tr>
                            <td>
                                <div class="user-info-flex">
                                    <div class="user-avatar"><?= $initial ?></div>
                                    <div>
                                        <div style="font-weight:700; color: #1e293b;"><?= htmlspecialchars($u['name']) ?></div>
                                        <div style="font-size:0.85rem; color:#94a3b8;"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="https://wa.me/<?= $clean_phone ?>" target="_blank" class="wa-badge">
                                    <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($u['phone']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if($is_google): ?>
                                    <span class="source-badge src-google"><i class="fa-brands fa-google"></i> Google</span>
                                <?php else: ?>
                                    <span class="source-badge src-manual"><i class="fa-regular fa-envelope"></i> Manual</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600; color: #475569;"><?= date('d M, Y', strtotime($u['created_at'])) ?></div>
                                <div style="font-size:0.8rem; color:#94a3b8;"><?= date('h:i A', strtotime($u['created_at'])) ?></div>
                            </td>
                            <td style="text-align:right;">
                                <a href="https://wa.me/<?= $clean_phone ?>?text=Hi <?= urlencode($u['name']) ?>, Welcome to <?= urlencode($GLOBALS['settings']['site_name']) ?>!" target="_blank" class="btn-export btn-chat">
                                    Chat Now <i class="fa-solid fa-paper-plane"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once '_footer.php'; ?>
