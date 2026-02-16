<?php
include '_header.php';
requireAdmin();

// --- LOGIC: CLOSE TICKET ---
if(isset($_GET['close'])) {
    $db->prepare("UPDATE tickets SET status='closed' WHERE id=?")->execute([(int)$_GET['close']]);
    // Redirect to remove query param
    echo "<script>window.location.href='tickets.php';</script>";
    exit;
}

// --- LOGIC: REPLY ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['reply'])) {
    $msg = $_POST['message']; 
    $tid = $_POST['id'];
    
    // Insert Admin Message
    $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender, message, created_at) VALUES (?, 'admin', ?, NOW())");
    $stmt->execute([$tid, $msg]);
    
    // Update Ticket Status
    $db->prepare("UPDATE tickets SET status='answered', updated_at=NOW() WHERE id=?")->execute([$tid]);
    
    // Refresh
    echo "<script>window.location.href='tickets.php';</script>";
    exit;
}

// --- FETCH DATA ---
try {
    $tickets = $db->query("SELECT t.*, u.email FROM tickets t JOIN users u ON t.user_id=u.id ORDER BY t.updated_at DESC")->fetchAll();
} catch (Exception $e) {
    $tickets = [];
}
?>

<link href="https://fonts.googleapis.com/css2?family=San+Francisco+Pro+Display:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --ios-bg: #F5F5F7;
        --ios-card: #FFFFFF;
        --ios-text: #1D1D1F;
        --ios-text-sec: #86868B;
        --ios-blue: #0071E3;
        --ios-green: #34C759;
        --ios-red: #FF3B30;
        --ios-yellow: #FF9F0A;
        --ios-gray: #8E8E93;
        --ios-border: #E5E5EA;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        --radius: 16px;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif;
        background-color: var(--ios-bg);
        color: var(--ios-text);
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
    }

    /* Container */
    .ios-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .page-title h1 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
    }
    .page-title p {
        margin: 5px 0 0 0;
        font-size: 15px;
        color: var(--ios-text-sec);
    }

    /* Card Table */
    .ios-card {
        background: var(--ios-card);
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .ios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .ios-table th {
        text-align: left;
        padding: 16px 24px;
        background: #FAFAFA;
        color: var(--ios-text-sec);
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--ios-border);
    }

    .ios-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--ios-border);
        vertical-align: middle;
        font-size: 14px;
        color: var(--ios-text);
        transition: background-color 0.1s ease;
    }

    .ios-table tr:last-child td {
        border-bottom: none;
    }

    .ios-table tr:hover td {
        background-color: #F9F9FB;
    }

    /* Columns */
    .col-id {
        font-family: 'SF Mono', monospace;
        color: var(--ios-text-sec);
        font-size: 13px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #E5E5EA;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        color: #555;
    }
    .user-email {
        font-weight: 500;
    }

    .subject-text {
        font-weight: 500;
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .status-pending { background: rgba(255, 159, 10, 0.15); color: var(--ios-yellow); }
    .status-answered { background: rgba(52, 199, 89, 0.15); color: var(--ios-green); }
    .status-closed { background: rgba(142, 142, 147, 0.15); color: var(--ios-gray); }

    /* Actions */
    .actions {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-view {
        background: rgba(0, 113, 227, 0.1);
        color: var(--ios-blue);
    }
    .btn-view:hover {
        background: var(--ios-blue);
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-close {
        background: rgba(142, 142, 147, 0.1);
        color: var(--ios-text-sec);
    }
    .btn-close:hover {
        background: #E5E5EA;
        color: var(--ios-text);
        transform: translateY(-1px);
    }

    /* MODAL */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(5px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal-backdrop.active {
        display: flex;
        opacity: 1;
    }

    .modal-card {
        background: #fff;
        width: 600px;
        max-width: 90%;
        border-radius: 24px;
        box-shadow: var(--shadow-md);
        transform: scale(0.95);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        flex-direction: column;
        max-height: 85vh;
    }
    .modal-backdrop.active .modal-card {
        transform: scale(1);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--ios-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-title { font-size: 18px; font-weight: 700; margin: 0; }
    
    .modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
        background: #FAFAFA;
    }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--ios-border);
        background: #fff;
    }

    .input-reply {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--ios-border);
        border-radius: 12px;
        font-family: inherit;
        font-size: 14px;
        resize: vertical;
        min-height: 100px;
        margin-bottom: 15px;
        transition: 0.2s;
        box-sizing: border-box;
    }
    .input-reply:focus {
        outline: none;
        border-color: var(--ios-blue);
        box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.1);
    }

    .btn-send {
        background: var(--ios-blue);
        color: white;
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-send:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Loading Spinner */
    .spinner {
        display: none;
        width: 24px;
        height: 24px;
        border: 3px solid rgba(0,0,0,0.1);
        border-left-color: var(--ios-blue);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    
    /* Messages UI in Modal */
    .chat-bubble {
        padding: 12px 16px;
        border-radius: 16px;
        margin-bottom: 10px;
        max-width: 80%;
        font-size: 14px;
        line-height: 1.4;
    }
    .chat-user {
        background: #E5E5EA;
        color: #000;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        margin-right: auto;
    }
    .chat-admin {
        background: var(--ios-blue);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        margin-left: auto;
    }
    .chat-container {
        display: flex;
        flex-direction: column;
    }
</style>

<div class="ios-container">

    <div class="page-header">
        <div class="page-title">
            <h1>Support Tickets</h1>
            <p>Manage and resolve customer inquiries.</p>
        </div>
    </div>

    <div class="ios-card">
        <div class="table-responsive">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>User</th>
                        <th>Subject</th>
                        <th width="120">Status</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($tickets)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:50px; color:var(--ios-text-sec);">
                                <i class="fa-regular fa-folder-open" style="font-size:32px; opacity:0.3; margin-bottom:10px;"></i><br>
                                No tickets found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($tickets as $t): ?>
                        <tr>
                            <td><span class="col-id">#<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($t['email'], 0, 1)) ?>
                                    </div>
                                    <span class="user-email"><?= htmlspecialchars($t['email']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="subject-text"><?= htmlspecialchars($t['subject']) ?></div>
                            </td>
                            <td>
                                <?php
                                    $sClass = 'status-pending';
                                    if($t['status'] == 'answered') $sClass = 'status-answered';
                                    if($t['status'] == 'closed') $sClass = 'status-closed';
                                ?>
                                <span class="badge <?= $sClass ?>"><?= ucfirst($t['status']) ?></span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button onclick="openChat(<?= $t['id'] ?>, '<?= addslashes(htmlspecialchars($t['subject'])) ?>')" class="btn-action btn-view">
                                        <i class="fa-regular fa-comment-dots"></i> View
                                    </button>
                                    
                                    <?php if($t['status'] != 'closed'): ?>
                                    <a href="tickets.php?close=<?= $t['id'] ?>" class="btn-action btn-close" onclick="return confirm('Close this ticket?')">
                                        Close
                                    </a>
                                    <?php else: ?>
                                    <span style="color:var(--ios-text-sec); font-size:12px; padding:8px;">Closed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="ticketModal">
    <div class="modal-card">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalSubject">Ticket Details</h3>
                <span id="modalId" style="font-size:12px; color:var(--ios-text-sec);">#0000</span>
            </div>
            <button onclick="closeChat()" style="background:transparent; border:none; cursor:pointer; font-size:18px; color:var(--ios-text-sec);">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="modal-body" id="modalContent">
            <div class="spinner" id="modalSpinner"></div>
            <div class="chat-container" id="chatContainer">
                <div style="text-align:center; color:#999; margin-top:20px;">
                    <i class="fa-solid fa-lock"></i> Secure Conversation
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <form method="POST">
                <input type="hidden" name="reply" value="1">
                <input type="hidden" name="id" id="replyTicketId">
                <textarea name="message" class="input-reply" placeholder="Type your reply here..." required></textarea>
                <button type="submit" class="btn-send">
                    <i class="fa-solid fa-paper-plane"></i> Send Reply
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('ticketModal');
    const modalSubject = document.getElementById('modalSubject');
    const modalId = document.getElementById('modalId');
    const replyTicketId = document.getElementById('replyTicketId');
    const chatContainer = document.getElementById('chatContainer');
    const spinner = document.getElementById('modalSpinner');

    function openChat(id, subject) {
        modalSubject.innerText = subject;
        modalId.innerText = '#' + String(id).padStart(4, '0');
        replyTicketId.value = id;
        
        // Show Modal
        modal.style.display = 'flex';
        // Small delay for transition
        setTimeout(() => modal.classList.add('active'), 10);
        
        // --- Optional: Fetch Messages Logic if backend supported ---
        // fetchMessages(id); 
    }

    function closeChat() {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if(e.target === modal) closeChat();
    });

    /* // Example fetch function if you implement the API endpoint later
    function fetchMessages(id) {
        spinner.style.display = 'block';
        chatContainer.innerHTML = '';
        fetch('?get_messages=1&id=' + id)
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                // Render data...
            })
            .catch(err => {
                spinner.style.display = 'none';
            });
    } 
    */
</script>

<?php include '_footer.php'; ?>
