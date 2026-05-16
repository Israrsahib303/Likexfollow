<?php
include '_smm_header.php';

// --- 1. LOGIC: PROCESS ORDER ---
$error = '';
$success = false;
$order_data = [];

// Helper to convert image to Base64 (Fixes Logo Missing in Download)
function imageToBase64($path) {
    if (!file_exists($path)) return '';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount_usdt'])) {
    
    $uid = $_SESSION['user_id'];
    $exch_id = (int)$_POST['exchange_id'];
    $usdt_amount = (float)$_POST['amount_usdt'];
    $addr = sanitize($_POST['wallet_address']);
    $sender_name = sanitize($_POST['sender_name']);
    
    // Combine details for Admin
    $full_details = "ID/Addr: $addr | Name: $sender_name";

    try {
        // Fetch Rates & Method
        $sell_rate = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_sell_rate'")->fetchColumn() ?: 295.00;
        $cost_rate = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_cost_rate'")->fetchColumn() ?: 285.00;
        
        $exch = $db->prepare("SELECT * FROM crypto_exchanges WHERE id=? AND status=1");
        $exch->execute([$exch_id]);
        $exchange = $exch->fetch();

        if (!$exchange) throw new Exception("Payment method unavailable.");

        // Calculations
        $pkr_amount = $usdt_amount * $sell_rate;
        $profit = ($sell_rate - $cost_rate) * $usdt_amount;

        // Validations
        if ($usdt_amount < $exchange['min_limit']) throw new Exception("Minimum order is {$exchange['min_limit']} USDT.");
        if ($usdt_amount > $exchange['max_limit']) throw new Exception("Maximum order is {$exchange['max_limit']} USDT.");
        
        $user_bal = $db->query("SELECT balance FROM users WHERE id=$uid")->fetchColumn();
        if ($user_bal < $pkr_amount) throw new Exception("Insufficient wallet balance.");

        // TRANSACTION START
        $db->beginTransaction();

        // 1. Deduct Money
        $db->prepare("UPDATE users SET balance = balance - ? WHERE id=?")->execute([$pkr_amount, $uid]);

        // 2. Insert Order
        $stmt = $db->prepare("INSERT INTO crypto_orders (user_id, exchange_id, amount_usdt, amount_pkr, wallet_address, rate_applied, profit, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$uid, $exch_id, $usdt_amount, $pkr_amount, $full_details, $sell_rate, $profit]);
        $order_id = $db->lastInsertId();

        // 3. Log
        $db->prepare("INSERT INTO wallet_ledger (user_id, type, amount, ref_type, ref_id, note) VALUES (?, 'debit', ?, 'crypto_order', ?, 'Bought USDT')")->execute([$uid, $pkr_amount, $order_id]);

        $db->commit();
        
        // PREPARE DATA FOR RECEIPT
        $success = true;
        
        // Get Logo in Base64 for Canvas
        $logo_path = "../assets/img/" . ($GLOBALS['settings']['site_logo'] ?? 'logo.png');
        $logo_base64 = imageToBase64($logo_path);
        
        // Get Icon in Base64
        $icon_path = "../assets/img/icons/" . ($exchange['icon'] ?? 'usdt.png');
        $icon_base64 = imageToBase64($icon_path);

        $order_data = [
            'id' => $order_id,
            'usdt' => $usdt_amount,
            'pkr' => $pkr_amount,
            'rate' => $sell_rate,
            'method' => $exchange['name'],
            'logo' => $logo_base64,
            'icon' => $icon_base64,
            'date' => date("d M Y, h:i A")
        ];

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $e->getMessage();
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&display=swap" rel="stylesheet">

<style>
    /* Page Layout Fix */
    .success-wrapper {
        min-height: 80vh; display: flex; align-items: center; justify-content: center;
        padding: 20px; font-family: 'Outfit', sans-serif;
    }

    .status-card {
        background: white; width: 100%; max-width: 400px; border-radius: 24px;
        padding: 30px; text-align: center; position: relative; overflow: hidden;
        box-shadow: 0 20px 60px -10px rgba(0,0,0,0.1); border: 1px solid #eef2ff;
        animation: popUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Animated Icon */
    .anim-icon {
        width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px;
        display: flex; align-items: center; justify-content: center; font-size: 32px;
        position: relative;
    }
    .s-ok { background: #dcfce7; color: #16a34a; }
    .s-err { background: #fee2e2; color: #dc2626; }
    
    .anim-icon::after {
        content: ''; position: absolute; inset: 0; border-radius: 50%;
        border: 2px solid currentColor; animation: ripple 1.5s infinite;
    }

    .st-head { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 5px; }
    .st-sub { color: #64748b; font-size: 13px; margin-bottom: 25px; }

    /* RECEIPT DESIGN (Clean & Minimal) */
    .receipt-box {
        background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 16px; padding: 20px;
        margin-bottom: 25px; position: relative; text-align: left;
    }
    .rec-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
    .rec-logo { height: 30px; object-fit: contain; }
    
    .rec-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
    .rec-lbl { color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase; }
    .rec-val { color: #0f172a; font-weight: 700; }
    
    .rec-total { 
        margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1;
        display: flex; justify-content: space-between; align-items: center;
    }
    .total-txt { color: #6366f1; font-size: 18px; font-weight: 900; }

    /* Action Buttons */
    .btn-row { display: flex; gap: 10px; }
    .btn-act { flex: 1; padding: 12px; border-radius: 12px; font-weight: 700; font-size: 13px; border: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none; }
    .btn-dl { background: #0f172a; color: white; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.3); }
    .btn-back { background: #f1f5f9; color: #64748b; }
    .btn-act:hover { transform: translateY(-2px); }

    @keyframes popUp { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
    @keyframes ripple { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(1.5); opacity: 0; } }
</style>

<div class="success-wrapper">

<?php if($success): ?>
    
    <div class="status-card">
        <div class="anim-icon s-ok"><i class="fa-solid fa-check"></i></div>
        <div class="st-head">Order Placed!</div>
        <div class="st-sub">Your USDT request has been submitted.</div>

        <div id="receiptNode" style="background:#fff; padding:5px;">
            <div class="receipt-box">
                <div class="rec-header">
                    <?php if(!empty($order_data['logo'])): ?>
                        <img src="<?= $order_data['logo'] ?>" class="rec-logo">
                    <?php else: ?>
                        <span style="font-weight:800; font-size:18px; color:#0f172a;">RECEIPT</span>
                    <?php endif; ?>
                    <span style="font-size:10px; background:#dcfce7; color:#166534; padding:3px 8px; border-radius:50px; font-weight:700;">PAID</span>
                </div>

                <div class="rec-row">
                    <span class="rec-lbl">Order ID</span>
                    <span class="rec-val">#<?= $order_data['id'] ?></span>
                </div>
                <div class="rec-row">
                    <span class="rec-lbl">Date</span>
                    <span class="rec-val"><?= $order_data['date'] ?></span>
                </div>
                <div class="rec-row">
                    <span class="rec-lbl">Method</span>
                    <span class="rec-val" style="display:flex; align-items:center; gap:5px;">
                        <?php if(!empty($order_data['icon'])): ?>
                            <img src="<?= $order_data['icon'] ?>" style="width:14px;">
                        <?php endif; ?>
                        <?= htmlspecialchars($order_data['method']) ?>
                    </span>
                </div>
                <div class="rec-row">
                    <span class="rec-lbl">Rate</span>
                    <span class="rec-val"><?= $order_data['rate'] ?> PKR</span>
                </div>

                <div class="rec-total">
                    <div>
                        <span class="rec-lbl" style="display:block;">You Receive</span>
                        <span style="color:#10b981; font-weight:800;"><?= number_format($order_data['usdt'], 2) ?> USDT</span>
                    </div>
                    <div class="total-txt">
                        <?= number_format($order_data['pkr']) ?> <small style="font-size:10px;">PKR</small>
                    </div>
                </div>
                
                <div style="text-align:center; margin-top:15px; font-size:10px; color:#cbd5e1; font-weight:600;">
                    Generated by <?= $GLOBALS['settings']['site_name'] ?? 'Beast9' ?>
                </div>
            </div>
        </div>
        <div class="btn-row">
            <a href="p2p_trading.php" class="btn-act btn-back">Close</a>
            <button onclick="downloadReceipt()" class="btn-act btn-dl">
                <i class="fa-solid fa-download"></i> Receipt
            </button>
        </div>
    </div>

    <script>
        // Confetti Blast
        window.onload = function() {
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, colors: ['#6b46ff', '#10b981'] });
        };

        // HD Receipt Download
        function downloadReceipt() {
            const node = document.getElementById('receiptNode');
            
            html2canvas(node, { 
                scale: 3, // High Resolution
                backgroundColor: '#ffffff',
                useCORS: true // Fix Cross-Origin Images
            }).then(canvas => {
                let link = document.createElement('a');
                link.download = 'USDT-Order-<?= $order_data['id'] ?>.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
            });
        }
    </script>

<?php else: ?>

    <div class="status-card">
        <div class="anim-icon s-err"><i class="fa-solid fa-xmark"></i></div>
        <div class="st-head" style="color:#dc2626;">Order Failed</div>
        <div class="st-sub" style="color:#ef4444; background:#fef2f2; padding:10px; border-radius:10px;">
            <?= $error ?: "Unknown error occurred." ?>
        </div>
        
        <div class="btn-row" style="margin-top:20px;">
            <a href="p2p_trading.php" class="btn-act btn-back">Try Again</a>
            <a href="add-funds.php" class="btn-act btn-dl" style="background:#dc2626;">Add Funds</a>
        </div>
    </div>

<?php endif; ?>

</div>

<?php include '_smm_footer.php'; ?>