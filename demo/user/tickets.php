<?php
include '_smm_header.php'; // Using SMM Header for consistent theme

$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

// --- 1. CREATE NEW TICKET ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_ticket'])) {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if ($subject && $message) {
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO tickets (user_id, subject, status, created_at) VALUES (?, ?, 'pending', NOW())")->execute([$user_id, $subject]);
            $ticket_id = $db->lastInsertId();
            $db->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")->execute([$ticket_id, $message]);
            $db->commit();
            $success = "Ticket #$ticket_id created successfully!";
            echo "<script>window.location.href='tickets.php?id=$ticket_id';</script>";
        } catch (Exception $e) { $db->rollBack(); $error = $e->getMessage(); }
    }
}

// --- 2. REPLY TO TICKET ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_ticket'])) {
    $tid = (int)$_POST['ticket_id'];
    $msg = sanitize($_POST['message']);
    if ($msg) {
        $db->prepare("INSERT INTO ticket_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")->execute([$tid, $msg]);
        $db->prepare("UPDATE tickets SET status='pending', updated_at=NOW() WHERE id=? AND user_id=?")->execute([$tid, $user_id]);
        echo "<script>window.location.href='tickets.php?id=$tid';</script>";
    }
}

// Fetch All Tickets
$tickets = $db->prepare("SELECT * FROM tickets WHERE user_id=? ORDER BY updated_at DESC");
$tickets->execute([$user_id]);
$all_tickets = $tickets->fetchAll();

// Fetch Active Ticket Data
$active_ticket = null;
$messages = [];
if (isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    $t_stmt = $db->prepare("SELECT * FROM tickets WHERE id=? AND user_id=?");
    $t_stmt->execute([$tid, $user_id]);
    $active_ticket = $t_stmt->fetch();
    
    if ($active_ticket) {
        $msgs_stmt = $db->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
        $msgs_stmt->execute([$tid]);
        $messages = $msgs_stmt->fetchAll();
    }
}
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
/* 1) Design tokens */
:root {
  --bg: #f6f3fb;
  --surface: #ffffff;
  --primary-1: #8B5CF6;   /* deep purple */
  --primary-2: #C084FC;   /* pink-purple */
  --primary-solid: #7a3be8;
  --accent: #34D399;      /* soft green accents */
  --muted: #a6a0b8;
  --text: #151522;
  
  --shadow-soft: 0 10px 30px rgba(139,92,246,0.12);
  --shadow-inset: inset 0 6px 18px rgba(139,92,246,0.06);
  --shadow-icon: 0 6px 20px rgba(20,17,34,0.06);
  
  --glass-bg: rgba(255,255,255,0.65);
  --glass-border: rgba(255,255,255,0.45);
  
  --radius-xl: 28px;
  --radius-lg: 18px;
  --radius-md: 12px;
  --radius-sm: 8px;
  
  --space-0: 4px; --space-1:8px; --space-2:12px; --space-3:16px; --space-4:24px; --space-5:32px;
  --base-font: 'Outfit', 'Inter', system-ui, sans-serif;
}

/* 2) Global Layout */
body {
    background-color: var(--bg);
    background-image: 
        radial-gradient(at 0% 0%, rgba(139,92,246,0.05) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(192,132,252,0.05) 0px, transparent 50%);
    font-family: var(--base-font);
    color: var(--text);
    margin: 0; padding: 0;
    -webkit-font-smoothing: antialiased;
}

.app-screen {
    width: 100%; max-width: 1200px; margin: 0 auto;
    padding-bottom: 80px; /* Safe bottom spacing */
    padding-top: var(--space-3);
}

/* 7) Components: Hero / Header Card */
.card-storage {
    background: var(--surface);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-soft);
    padding: var(--space-4);
    position: relative; overflow: hidden;
    margin-bottom: var(--space-4);
    border: 1px solid var(--glass-border);
    display: flex; justify-content: space-between; align-items: center;
}
.card-storage::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0.8), rgba(255,255,255,0));
    opacity: 0.6; pointer-events: none;
}

.hero-title { font-size: 24px; font-weight: 800; color: var(--text); z-index: 2; position: relative; }
.hero-sub { font-size: 14px; color: var(--muted); font-weight: 500; z-index: 2; position: relative; }

/* 6) Floating CTA */
.floating-cta {
    background: linear-gradient(135deg, var(--primary-1) 0%, var(--primary-2) 100%);
    color: white; font-weight: 600; padding: 12px 24px; border-radius: 28px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 10px 20px rgba(139,92,246,0.3); border: none; cursor: pointer;
    transition: transform 0.2s; position: relative; z-index: 2;
}
.floating-cta:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(139,92,246,0.4); }
.floating-cta:active { transform: scale(0.96); }

/* --- MAIN LAYOUT (Split for Desktop, Stack for Mobile) --- */
.support-layout {
    display: grid; grid-template-columns: 1fr; gap: var(--space-4);
}
@media(min-width: 900px) {
    .support-layout { grid-template-columns: 320px 1fr; align-items: start; }
}

/* --- TICKET LIST --- */
.ticket-list-card {
    background: var(--surface); border-radius: var(--radius-lg);
    box-shadow: var(--shadow-soft); border: 1px solid var(--glass-border);
    overflow: hidden; max-height: 600px; display: flex; flex-direction: column;
}
.list-scroll { overflow-y: auto; padding: var(--space-2); }

/* 7) Component: List Item */
.list-item {
    display: flex; align-items: center; gap: var(--space-3);
    padding: var(--space-3); border-radius: var(--radius-md);
    background: var(--surface); margin-bottom: var(--space-2);
    box-shadow: 0 2px 5px rgba(0,0,0,0.02); border: 1px solid transparent;
    text-decoration: none; color: var(--text); transition: all 0.2s;
}
.list-item:hover { transform: translateY(-2px) scale(1.01); box-shadow: var(--shadow-soft); }
.list-item.active {
    background: linear-gradient(135deg, rgba(139,92,246,0.05), rgba(192,132,252,0.05));
    border-color: var(--primary-1);
}

.item-icon {
    width: 44px; height: 44px; border-radius: 14px;
    background: linear-gradient(135deg, #F3F0FF, #E9D5FF); color: var(--primary-1);
    display: flex; align-items: center; justify-content: center; font-size: 18px;
    box-shadow: var(--shadow-inset); flex-shrink: 0;
}
.item-content { flex: 1; min-width: 0; }
.item-title { font-size: 15px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }

/* Status Badges */
.status-pill { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 12px; text-transform: uppercase; }
.sp-pending { background: #FFF7ED; color: #EA580C; }
.sp-answered { background: #ECFDF5; color: #059669; }
.sp-closed { background: #F3F4F6; color: #6B7280; }

/* --- CHAT AREA --- */
.chat-container {
    background: var(--glass-bg); backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px); border-radius: var(--radius-lg);
    border: 1px solid var(--glass-border); box-shadow: var(--shadow-soft);
    display: flex; flex-direction: column; height: 600px; overflow: hidden;
}

.chat-header {
    padding: var(--space-4); border-bottom: 1px solid rgba(0,0,0,0.05);
    background: rgba(255,255,255,0.5);
}
.ch-title { font-size: 18px; font-weight: 800; color: var(--text); }
.ch-sub { font-size: 13px; color: var(--muted); }

.chat-messages {
    flex: 1; padding: var(--space-4); overflow-y: auto;
    display: flex; flex-direction: column; gap: var(--space-3);
    background-image: radial-gradient(#8B5CF6 0.5px, transparent 0.5px);
    background-size: 20px 20px; background-color: rgba(246, 243, 251, 0.5);
}

/* Chat Bubbles */
.msg-row { display: flex; flex-direction: column; max-width: 75%; }
.msg-row.user { align-self: flex-end; align-items: flex-end; }
.msg-row.admin { align-self: flex-start; align-items: flex-start; }

.bubble {
    padding: 14px 20px; font-size: 15px; line-height: 1.5;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative;
}
.bubble-user {
    background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
    color: white; border-radius: 20px 20px 4px 20px;
}
.bubble-admin {
    background: var(--surface); color: var(--text);
    border-radius: 20px 20px 20px 4px; border: 1px solid rgba(0,0,0,0.05);
}
.msg-time { font-size: 11px; color: var(--muted); margin-top: 5px; font-weight: 500; }

/* Input Area */
.chat-footer {
    padding: var(--space-3); background: var(--surface);
    border-top: 1px solid rgba(0,0,0,0.05);
}
.input-wrapper {
    position: relative; display: flex; align-items: center; gap: 10px;
    background: #F8FAFC; border-radius: 30px; padding: 5px 10px 5px 20px;
    border: 1px solid #E2E8F0; box-shadow: var(--shadow-inset);
}
.chat-field {
    width: 100%; border: none; background: transparent; padding: 10px 0;
    font-size: 15px; color: var(--text); outline: none;
}
.send-btn {
    width: 42px; height: 42px; border-radius: 50%; border: none;
    background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
    color: white; display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 4px 10px rgba(139,92,246,0.3);
    transition: transform 0.2s;
}
.send-btn:hover { transform: scale(1.1); }

/* --- MODAL --- */
.modal-backdrop {
    display: none; position: fixed; inset: 0; z-index: 999;
    background: rgba(21, 21, 34, 0.4); backdrop-filter: blur(8px);
    align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s;
}
.modal-backdrop.open { display: flex; opacity: 1; }

.modal-card {
    background: var(--surface); width: 90%; max-width: 400px;
    border-radius: var(--radius-xl); padding: var(--space-5);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2); transform: scale(0.9); transition: transform 0.3s;
}
.modal-backdrop.open .modal-card { transform: scale(1); }

/* Form Elements */
.input-group { margin-bottom: var(--space-3); }
.label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
.input-box {
    width: 100%; height: 48px; border-radius: var(--radius-md);
    border: 1px solid var(--glass-border); background: #F8FAFC;
    padding: 0 16px; font-size: 15px; color: var(--text);
    box-shadow: var(--shadow-inset); transition: 0.2s;
}
.input-box:focus {
    box-shadow: 0 6px 20px rgba(139,92,246,0.12); border-color: var(--primary-1); outline: none;
}
.textarea { height: 120px; padding-top: 12px; resize: none; }

.btn-primary {
    width: 100%; height: 50px; border-radius: 25px; border: none;
    background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
    color: white; font-weight: 700; font-size: 16px; cursor: pointer;
    box-shadow: 0 10px 20px rgba(139,92,246,0.25); margin-top: 10px;
    transition: 0.2s;
}
.btn-primary:active { transform: scale(0.98); }

/* Animation */
.animate-up { animation: slideUp 0.5s ease-out forwards; opacity: 0; }
.d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

/* Empty State */
.empty-state {
    text-align: center; color: var(--muted); padding: 40px;
    display: flex; flex-direction: column; align-items: center; gap: 15px;
}
.empty-icon {
    font-size: 40px; color: #E9D5FF; background: #F3F0FF;
    width: 80px; height: 80px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
</style>

<div class="app-screen">

    <div class="card-storage animate-up">
        <div>
            <h1 class="hero-title">Support Center</h1>
            <p class="hero-sub">We are here to help you 24/7</p>
        </div>
        <button onclick="openModal()" class="floating-cta">
            <i class="fas fa-plus"></i> New Ticket
        </button>
    </div>

    <div class="support-layout">
        
        <div class="ticket-list-card animate-up d-1">
            <div style="padding:20px; border-bottom:1px solid rgba(0,0,0,0.05);">
                <h3 style="font-size:16px; font-weight:700; margin:0;">Your Tickets</h3>
            </div>
            <div class="list-scroll">
                <?php if(empty($all_tickets)): ?>
                    <div style="padding:40px; text-align:center; color:var(--muted); font-size:14px;">No tickets yet.</div>
                <?php else: ?>
                    <?php foreach($all_tickets as $t): ?>
                    <a href="?id=<?= $t['id'] ?>" class="list-item <?= ($active_ticket['id']??0)==$t['id']?'active':'' ?>">
                        <div class="item-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title"><?= sanitize($t['subject']) ?></div>
                            <div class="item-meta">
                                #<?= $t['id'] ?> • <?= date('M d', strtotime($t['updated_at'])) ?>
                            </div>
                        </div>
                        <div class="status-pill sp-<?= $t['status'] ?>"><?= $t['status'] ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-container animate-up d-2">
            <?php if ($active_ticket): ?>
                
                <div class="chat-header">
                    <div class="ch-title"><?= sanitize($active_ticket['subject']) ?></div>
                    <div class="ch-sub">Ticket #<?= $active_ticket['id'] ?> • <span style="color:var(--primary-1); font-weight:600; text-transform:uppercase;"><?= $active_ticket['status'] ?></span></div>
                </div>

                <div class="chat-messages" id="chatBox">
                    <?php foreach($messages as $m): ?>
                    <div class="msg-row <?= $m['sender'] ?>">
                        <div class="bubble bubble-<?= $m['sender'] ?>">
                            <?= nl2br(sanitize($m['message'])) ?>
                        </div>
                        <span class="msg-time"><?= date('h:i A', strtotime($m['created_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-footer">
                    <?php if($active_ticket['status'] != 'closed'): ?>
                    <form method="POST">
                        <input type="hidden" name="reply_ticket" value="1">
                        <input type="hidden" name="ticket_id" value="<?= $active_ticket['id'] ?>">
                        <div class="input-wrapper">
                            <input type="text" name="message" class="chat-field" placeholder="Type your message..." required autocomplete="off">
                            <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div style="text-align:center; color:var(--muted); font-size:14px; font-weight:600;">
                            <i class="fas fa-lock"></i> This ticket is closed.
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="empty-state" style="margin:auto;">
                    <div class="empty-icon"><i class="fas fa-comments"></i></div>
                    <h3 style="color:var(--text); font-weight:700;">Select a conversation</h3>
                    <p>Choose a ticket from the left to view details or start a new one.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<div class="modal-backdrop" id="ticketModal">
    <div class="modal-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h2 style="font-size:20px; font-weight:800;">New Ticket</h2>
            <button onclick="closeModal()" style="background:none; border:none; font-size:24px; color:var(--muted); cursor:pointer;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="new_ticket" value="1">
            
            <div class="input-group">
                <label class="label">Subject</label>
                <select name="subject" class="input-box" style="padding-right:10px;">
                    <option value="Order Issue">📦 Order Issue</option>
                    <option value="Refill Request">🔄 Refill Request</option>
                    <option value="Payment Issue">💳 Payment Issue</option>
                    <option value="Other">❓ Other</option>
                </select>
            </div>

            <div class="input-group">
                <label class="label">Message</label>
                <textarea name="message" class="input-box textarea" placeholder="Describe your issue..." required></textarea>
            </div>

            <button class="btn-primary">Submit Ticket <i class="fas fa-arrow-right" style="margin-left:5px;"></i></button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('ticketModal');
    
    function openModal() {
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('open'), 10);
    }
    
    function closeModal() {
        modal.classList.remove('open');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    // Close on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Auto Scroll Chat
    const chatBox = document.getElementById('chatBox');
    if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php include '_smm_footer.php'; ?>