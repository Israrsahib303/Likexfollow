<?php
include '_smm_header.php';
$user_id = $_SESSION['user_id'];

// Fetch User's Crypto Orders
$orders = $db->query("
    SELECT o.*, e.name as method_name, e.icon 
    FROM crypto_orders o
    LEFT JOIN crypto_exchanges e ON o.exchange_id = e.id
    WHERE o.user_id = $user_id
    ORDER BY o.id DESC
")->fetchAll();

// --- LOGO FIX ---
// We use a root-relative path (starting with /) so it works from any folder
$logo_path = "/assets/img/logo.png";

// If database has a specific logo URL, use that instead
if (!empty($GLOBALS['settings']['site_logo'])) {
    $logo_path = $GLOBALS['settings']['site_logo'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crypto History | Beast9</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<style>
    :root {
        --primary: #6366f1; 
        --primary-dark: #4f46e5;
        --bg-color: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b; 
        --text-gray: #64748b;
        --success: #10b981; 
        --danger: #ef4444; 
        --warning: #f59e0b;
        --border-radius: 16px;
    }

    body { 
        background: var(--bg-color); 
        font-family: 'Outfit', sans-serif; 
        color: var(--text-dark); 
        padding-bottom: 80px; 
        margin: 0;
    }
    
    /* HEADER */
    .page-head {
        padding: 20px 25px; 
        display: flex; 
        align-items: center; 
        gap: 15px;
        background: rgba(255, 255, 255, 0.9); 
        backdrop-filter: blur(10px);
        position: sticky; 
        top: 0; 
        z-index: 50;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .page-title { font-size: 22px; font-weight: 800; margin: 0; }

    /* LIST STYLE */
    .history-list { padding: 25px; max-width: 800px; margin: 0 auto; }
    
    /* Animation Keyframes */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .order-card {
        background: var(--card-bg); 
        border-radius: var(--border-radius); 
        padding: 20px; 
        margin-bottom: 18px;
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        cursor: pointer; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        position: relative;
        overflow: hidden;
        animation: fadeInUp 0.5s ease backwards;
    }

    .order-card:hover { 
        transform: translateY(-4px) scale(1.01); 
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: var(--primary);
    }
    
    .order-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
        background: var(--text-gray);
        opacity: 0.3;
        transition: 0.3s;
    }
    .order-card:hover::before { background: var(--primary); opacity: 1; }

    .oc-left { display: flex; align-items: center; gap: 16px; }
    
    .oc-icon-box {
        width: 52px; height: 52px; 
        border-radius: 14px; 
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex; align-items: center; justify-content: center; 
        position: relative;
    }
    .oc-icon-img { width: 30px; height: 30px; object-fit: contain; }
    
    /* Live Pulse Dot */
    .status-dot {
        position: absolute; top: -2px; right: -2px;
        width: 12px; height: 12px; border-radius: 50%;
        border: 2px solid #fff;
    }
    .s-dot-completed { background: var(--success); box-shadow: 0 0 10px var(--success); }
    .s-dot-pending { background: var(--warning); animation: pulse 2s infinite; }
    .s-dot-cancelled { background: var(--danger); }

    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }

    .oc-info h4 { margin: 0; font-size: 17px; font-weight: 800; color: var(--text-dark); }
    .oc-info p { margin: 4px 0 0; font-size: 13px; color: var(--text-gray); font-weight: 500; }
    
    .oc-right { text-align: right; }
    .oc-price { font-size: 17px; font-weight: 800; color: var(--text-dark); display: block; margin-bottom: 5px; }
    
    /* BADGES */
    .st-badge { padding: 4px 12px; border-radius: 30px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .st-pending { background: #fff7ed; color: #c2410c; }
    .st-completed { background: #f0fdf4; color: #15803d; }
    .st-cancelled { background: #fef2f2; color: #b91c1c; }

    /* --- RECEIPT MODAL --- */
    .modal-overlay {
        position: fixed; inset: 0; 
        background: rgba(15, 23, 42, 0.7); 
        backdrop-filter: blur(8px);
        z-index: 1000; 
        display: none; 
        justify-content: center; align-items: center; 
        padding: 20px;
    }
    .modal-overlay.active { display: flex; animation: fadeIn 0.2s ease-out; }

    .receipt-card {
        width: 100%; max-width: 360px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        animation: ticketSlide 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    #captureArea { background: #fff; position: relative; }

    /* Receipt Header */
    .rh-top { 
        background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%); 
        padding: 30px 20px 45px; 
        text-align: center; color: white; 
        position: relative;
    }
    
    /* LOGO FIX: Added background and padding to make it visible on gradient */
    .rh-logo { 
        height: 50px; 
        width: auto;
        object-fit: contain;
        margin-bottom: 10px; 
        position: relative;
        z-index: 5;
        background: rgba(255,255,255,0.2); 
        padding: 8px 12px;
        border-radius: 8px;
        backdrop-filter: blur(4px);
    }

    /* Icon Bubble */
    .rh-icon-bubble {
        width: 70px; height: 70px;
        background: #fff;
        border-radius: 50%;
        position: absolute;
        left: 50%; bottom: -35px; transform: translateX(-50%);
        display: flex; align-items: center; justify-content: center;
        font-size: 32px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
        z-index: 10;
    }

    .rh-body { padding: 50px 25px 25px; text-align: center; }
    
    .rh-status-txt { font-size: 18px; font-weight: 800; margin-bottom: 5px; }
    .rh-date-txt { font-size: 12px; color: var(--text-gray); font-family: 'Space Mono', monospace; }

    .rh-amount {
        margin: 20px 0;
        font-size: 32px; font-weight: 800;
        color: var(--text-dark);
        letter-spacing: -1px;
    }
    .rh-amount small { font-size: 16px; color: var(--text-gray); font-weight: 600; }

    /* Tear Line */
    .tear-line {
        border-top: 2px dashed #e2e8f0;
        margin: 15px 0 20px;
        position: relative;
    }
    .tear-line::before, .tear-line::after {
        content: ''; position: absolute; top: -10px; 
        width: 20px; height: 20px; border-radius: 50%; background: #0f172a; 
    }
    .tear-line::before { left: -35px; }
    .tear-line::after { right: -35px; }

    /* Detail Grid */
    .detail-grid { display: flex; flex-direction: column; gap: 12px; }
    .d-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
    .d-label { color: var(--text-gray); font-weight: 500; }
    .d-val { font-weight: 700; color: var(--text-dark); font-family: 'Space Mono', monospace; text-align: right; }

    .note-container {
        margin-top: 20px; padding: 12px;
        border-radius: 12px; font-size: 12px; text-align: left; line-height: 1.5;
        display: none;
    }

    .rh-footer { padding: 15px; background: #f8fafc; display: flex; gap: 10px; }
    .btn-act {
        flex: 1; padding: 14px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;
        transition: 0.2s;
    }
    .btn-close { background: white; border: 1px solid #e2e8f0; color: var(--text-gray); }
    .btn-save { background: var(--text-dark); color: white; }
    .btn-save:hover { background: #000; transform: translateY(-2px); }

    @keyframes ticketSlide { from { transform: translateY(50px) scale(0.9); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
</head>
<body>

<div class="page-head">
    <a href="p2p_trading.php" style="color:var(--text-dark); font-size:20px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:#fff; border-radius:50%; box-shadow:0 2px 5px rgba(0,0,0,0.1);"><i class="fa-solid fa-arrow-left"></i></a>
    <h2 class="page-title">Transaction History</h2>
</div>

<div class="history-list">
    <?php if(empty($orders)): ?>
        <div style="text-align:center; padding:80px 20px; color:var(--text-gray);">
            <div style="font-size:60px; margin-bottom:20px; opacity:0.2;">🧾</div>
            <h3>No Transactions Found</h3>
            <p>Your crypto order history will appear here.</p>
        </div>
    <?php else: ?>
        <?php 
        $delay = 0; 
        foreach($orders as $o): 
            $delay += 0.1;
            $stClass = 'st-'.$o['status'];
            $dotClass = 's-dot-'.$o['status'];
            
            $statusLabel = ($o['status'] == 'completed') ? 'Success' : ucfirst($o['status']);
            $icon = !empty($o['icon']) ? "../assets/img/icons/".$o['icon'] : "../assets/img/usdt.png";
            $method = $o['method_name'] ?? 'USDT Transfer';
            $note = htmlspecialchars($o['admin_note'] ?? '', ENT_QUOTES);
        ?>
        
        <div class="order-card" style="animation-delay: <?= $delay ?>s"
             onclick="openReceipt(
                '<?= $o['id'] ?>', 
                '<?= $o['status'] ?>', 
                '<?= $o['amount_usdt'] ?>', 
                '<?= number_format($o['amount_pkr']) ?>', 
                '<?= date('d M Y • h:i A', strtotime($o['created_at'])) ?>', 
                '<?= addslashes($method) ?>', 
                '<?= $o['trx_id'] ?? 'Pending' ?>', 
                '<?= $note ?>'
             )">
            
            <div class="oc-left">
                <div class="oc-icon-box">
                    <img src="<?= $icon ?>" class="oc-icon-img" onerror="this.src='../assets/img/usdt.png'">
                    <div class="status-dot <?= $dotClass ?>"></div>
                </div>
                <div class="oc-info">
                    <h4>Buy USDT</h4>
                    <p><i class="fa-regular fa-calendar" style="font-size:11px;"></i> <?= date('d M, h:i A', strtotime($o['created_at'])) ?></p>
                </div>
            </div>
            
            <div class="oc-right">
                <span class="oc-price">+<?= number_format($o['amount_usdt'], 2) ?> USDT</span>
                <span class="st-badge <?= $stClass ?>"><?= $statusLabel ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="receiptModal">
    <div class="receipt-card">
        
        <div id="captureArea">
            <div class="rh-top">
                <img src="<?= $logo_path ?>" 
                     class="rh-logo" 
                     alt="Site Logo"
                     onerror="this.onerror=null; this.src='../assets/img/logo.png';">
                     
                <div style="font-size:11px; letter-spacing:2px; opacity:0.9; text-transform:uppercase;">Transaction Receipt</div>
                
                <div class="rh-icon-bubble">
                    <i class="fa-solid fa-check" id="ic_ok" style="color:var(--success); display:none;"></i>
                    <i class="fa-solid fa-xmark" id="ic_fail" style="color:var(--danger); display:none;"></i>
                    <i class="fa-solid fa-hourglass-half" id="ic_wait" style="color:var(--warning); display:none;"></i>
                </div>
            </div>

            <div class="rh-body">
                <div class="rh-status-txt" id="r_status_txt">Successful</div>
                <div class="rh-date-txt" id="r_date">...</div>

                <div class="rh-amount">
                    <span id="r_usdt">0.00</span> <small>USDT</small>
                </div>

                <div class="tear-line"></div>

                <div class="detail-grid">
                    <div class="d-row">
                        <span class="d-label">Order ID</span>
                        <span class="d-val" id="r_id">#---</span>
                    </div>
                    <div class="d-row">
                        <span class="d-label">Payment Method</span>
                        <span class="d-val" id="r_method">---</span>
                    </div>
                    <div class="d-row">
                        <span class="d-label">Total Amount</span>
                        <span class="d-val" id="r_pkr">Rs 0</span>
                    </div>
                    <div class="d-row" id="row_txid">
                        <span class="d-label">Transaction ID</span>
                        <span class="d-val" id="r_txid" style="font-size:11px;">---</span>
                    </div>
                </div>

                <div class="note-container" id="r_note_box">
                    <strong style="display:block; margin-bottom:4px; text-transform:uppercase; opacity:0.7;">Note:</strong>
                    <span id="r_note">...</span>
                </div>
                
                <div style="margin-top:25px; font-size:10px; color:#cbd5e1; font-weight:600; text-transform:uppercase; letter-spacing:1px;">
                    Authorized by Beast9 Team
                </div>
            </div>
        </div>
        
        <div class="rh-footer" data-html2canvas-ignore>
            <button class="btn-act btn-close" onclick="closeModal()">Close</button>
            <button class="btn-act btn-save" onclick="downloadReceipt()">
                <i class="fa-solid fa-image"></i> Save Receipt
            </button>
        </div>
    </div>
</div>

<script>
function openReceipt(id, status, usdt, pkr, date, method, txid, note) {
    document.getElementById('r_id').innerText = '#' + id;
    document.getElementById('r_usdt').innerText = parseFloat(usdt).toFixed(2);
    document.getElementById('r_pkr').innerText = 'Rs ' + pkr;
    document.getElementById('r_date').innerText = date;
    document.getElementById('r_method').innerText = method;

    const icOk = document.getElementById('ic_ok');
    const icFail = document.getElementById('ic_fail');
    const icWait = document.getElementById('ic_wait');
    const statusTxt = document.getElementById('r_status_txt');
    const noteBox = document.getElementById('r_note_box');
    const txRow = document.getElementById('row_txid');

    icOk.style.display = 'none';
    icFail.style.display = 'none';
    icWait.style.display = 'none';
    noteBox.style.display = 'none';

    if(status === 'completed') {
        icOk.style.display = 'block';
        statusTxt.innerText = "Payment Successful";
        statusTxt.style.color = "var(--success)";
        txRow.style.display = "flex";
        document.getElementById('r_txid').innerText = txid;
        
        if(note) {
            noteBox.style.display = 'block';
            noteBox.style.background = '#f0fdf4';
            noteBox.style.color = '#15803d';
            document.getElementById('r_note').innerText = note;
        }
    } 
    else if(status === 'cancelled') {
        icFail.style.display = 'block';
        statusTxt.innerText = "Order Cancelled";
        statusTxt.style.color = "var(--danger)";
        txRow.style.display = "none";
        
        noteBox.style.display = 'block';
        noteBox.style.background = '#fef2f2';
        noteBox.style.color = '#b91c1c';
        document.getElementById('r_note').innerText = note ? note : "No reason provided.";
    } 
    else {
        icWait.style.display = 'block';
        statusTxt.innerText = "Processing Order";
        statusTxt.style.color = "var(--warning)";
        txRow.style.display = "none";
    }

    document.getElementById('receiptModal').classList.add('active');
}

function closeModal() {
    document.getElementById('receiptModal').classList.remove('active');
}

function downloadReceipt() {
    const element = document.getElementById('captureArea');
    const originalRadius = element.parentNode.style.borderRadius;
    element.parentNode.style.borderRadius = "0";

    html2canvas(element, { 
        scale: 3, 
        backgroundColor: '#ffffff',
        useCORS: true, 
        allowTaint: true 
    }).then(canvas => {
        element.parentNode.style.borderRadius = originalRadius;
        let link = document.createElement('a');
        link.download = 'Receipt-' + document.getElementById('r_id').innerText + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}
</script>

</body>
</html>
<?php include '_smm_footer.php'; ?>