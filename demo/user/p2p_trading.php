<?php
include '_smm_header.php';

// --- 1. FETCH LIVE DATA ---
$sell_rate = $db->query("SELECT setting_value FROM settings WHERE setting_key='usdt_sell_rate'")->fetchColumn() ?: 295.00;
$exchanges = $db->query("SELECT * FROM crypto_exchanges WHERE status=1 ORDER BY id DESC")->fetchAll();

// User Balance
$user_id = $_SESSION['user_id'];
$bal = $db->query("SELECT balance FROM users WHERE id=$user_id")->fetchColumn();
$usdt_eq = ($bal > 0 && $sell_rate > 0) ? ($bal / $sell_rate) : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>P2P Trading | Beast9</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary: #6b46ff; --primary-light: #eef2ff;
        --dark: #0f172a; --gray: #64748b; --border: #e2e8f0;
        --card-bg: #ffffff; --body-bg: #f8f9fc;
        --green: #10b981; --red: #ef4444;
    }
    body { background: var(--body-bg); font-family: 'Outfit', sans-serif; color: var(--dark); padding-bottom: 80px; overflow-x: hidden; }

    /* --- ANIMATIONS --- */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(107, 70, 255, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(107, 70, 255, 0); } 100% { box-shadow: 0 0 0 0 rgba(107, 70, 255, 0); } }
    
    .anim-item { opacity: 0; animation: fadeInUp 0.5s ease forwards; }
    .d-1 { animation-delay: 0.1s; } .d-2 { animation-delay: 0.2s; } .d-3 { animation-delay: 0.3s; }

    /* HEADER */
    .top-nav {
        background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
        padding: 15px 20px; position: sticky; top: 0; z-index: 50;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid var(--border);
    }
    .page-title { font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 12px; }
    .usdt-main-icon { width: 32px; height: 32px; border-radius: 50%; box-shadow: 0 4px 10px rgba(38, 161, 123, 0.2); }
    
    .btn-history {
        background: var(--primary-light); color: var(--primary); padding: 8px 15px; 
        border-radius: 50px; font-size: 13px; font-weight: 700; text-decoration: none;
        display: flex; align-items: center; gap: 6px; transition: 0.2s;
    }
    .btn-history:hover { background: var(--primary); color: white; transform: scale(1.05); }

    /* BALANCE CARD */
    .hero-wrap { padding: 20px; }
    .balance-card {
        background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white;
        border-radius: 24px; padding: 30px; position: relative; overflow: hidden;
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.5);
        transition: 0.3s;
    }
    .balance-card:hover { transform: translateY(-5px); }
    .balance-card::before {
        content: ''; position: absolute; right: -30px; top: -30px; width: 180px; height: 180px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        border-radius: 50%; animation: pulseGlow 3s infinite;
    }
    .bal-lbl { font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
    .bal-main { font-size: 38px; font-weight: 900; margin-top: 5px; letter-spacing: -1px; }
    .bal-eq { 
        font-size: 15px; font-weight: 700; margin-top: 10px; 
        background: rgba(255,255,255,0.2); display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 12px; backdrop-filter: blur(5px);
    }

    /* RATE BAR */
    .rate-bar {
        margin: 0 20px 25px; background: white; padding: 15px 20px; border-radius: 16px;
        border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .rate-val { font-size: 18px; font-weight: 800; color: var(--green); display: flex; align-items: center; gap: 5px; }

    /* EXCHANGE GRID */
    .grid-wrap { padding: 0 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); gap: 15px; }
    @media(min-width:768px) { .grid-wrap { grid-template-columns: repeat(2, 1fr); } }

    .exch-card {
        background: white; padding: 20px; border-radius: 20px; border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: space-between; cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); position: relative; overflow: hidden;
    }
    .exch-card:hover { 
        border-color: var(--primary); transform: translateY(-5px) scale(1.02); 
        box-shadow: 0 15px 30px rgba(107, 70, 255, 0.15); 
    }
    
    .ec-left { display: flex; align-items: center; gap: 15px; }
    .ec-icon { 
        width: 50px; height: 50px; object-fit: contain; border-radius: 12px; 
        background: #f8fafc; padding: 8px; border: 1px solid #f1f5f9; transition: 0.3s;
    }
    .exch-card:hover .ec-icon { background: white; border-color: var(--primary); transform: rotate(-10deg); }
    
    .ec-name { font-weight: 800; font-size: 17px; display: block; color: var(--dark); margin-bottom: 4px; }
    .ec-limit { font-size: 11px; color: var(--gray); background: var(--bg-body); padding: 4px 10px; border-radius: 6px; font-weight: 700; }
    .btn-arrow { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-body); display: flex; align-items: center; justify-content: center; color: var(--gray); transition: 0.3s; }
    .exch-card:hover .btn-arrow { background: var(--primary); color: white; transform: rotate(-45deg); }

    /* MODAL */
    .modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
        z-index: 1000; display: none; justify-content: center; align-items: flex-end;
    }
    .modal-overlay.active { display: flex; }
    
    .sheet-modal {
        background: white; width: 100%; max-width: 500px; border-radius: 30px 30px 0 0;
        padding: 30px 25px; box-shadow: 0 -10px 50px rgba(0,0,0,0.2); animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @media(min-width:600px) { 
        .modal-overlay { align-items: center; } 
        .sheet-modal { border-radius: 30px; animation: zoomIn 0.3s ease; } 
    }

    .sm-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .sm-title { font-size: 22px; font-weight: 800; color: var(--dark); display: flex; align-items: center; gap: 10px; }
    .sm-close { width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; border: none; cursor: pointer; color: var(--dark); font-size: 18px; transition:0.2s; }
    .sm-close:hover { background: #fee2e2; color: #ef4444; transform: rotate(90deg); }

    /* INPUTS */
    .field-box { margin-bottom: 25px; }
    .fb-label { display: block; font-size: 12px; font-weight: 700; color: var(--gray); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .fb-input-wrap { position: relative; }
    .fb-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; }
    .fb-input { 
        width: 100%; padding: 18px 18px 18px 50px; border-radius: 16px; border: 2px solid var(--border);
        font-size: 17px; font-weight: 700; outline: none; transition: 0.3s; box-sizing: border-box; color: var(--dark);
        background: #fcfcfc;
    }
    .fb-input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(107, 70, 255, 0.1); }
    
    /* Live Calc Box */
    .live-calc-box {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 15px; margin-top: 10px;
    }
    .calc-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--gray); margin-bottom: 5px; }
    .calc-val { font-weight: 800; color: var(--dark); }
    .calc-total-row { border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
    .ct-label { font-size: 14px; font-weight: 700; }
    .ct-amount { font-size: 20px; font-weight: 900; color: var(--primary); }
    
    .status-badge { font-size: 11px; padding: 3px 8px; border-radius: 6px; background: #e0e7ff; color: var(--primary); font-weight: 700; }

    .btn-main {
        width: 100%; padding: 18px; background: linear-gradient(135deg, var(--primary), #4f46e5); color: white; border: none;
        border-radius: 16px; font-size: 18px; font-weight: 800; cursor: pointer;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4); transition: 0.3s; position: relative; overflow: hidden;
    }
    .btn-main:hover { transform: translateY(-3px); box-shadow: 0 15px 35px -5px rgba(79, 70, 229, 0.5); }
    .btn-main:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; transform: none; filter: grayscale(1); }

    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    @keyframes zoomIn { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
</style>
</head>
<body>

<div class="top-nav">
    <div class="page-title">
        <a href="index.php" style="color:var(--dark);"><i class="fa-solid fa-arrow-left"></i></a>
        <img src="../assets/img/usdt.png" class="usdt-main-icon" onerror="this.src='https://cryptologos.cc/logos/tether-usdt-logo.png'"> 
        Buy USDT
    </div>
    <a href="crypto_history.php" class="btn-history">
        <i class="fa-solid fa-clock-rotate-left"></i> History
    </a>
</div>

<div class="hero-wrap anim-item d-1">
    <div class="balance-card">
        <div class="bal-lbl">Available Wallet Balance</div>
        <div class="bal-main"><?= number_format($bal, 2) ?> <small style="font-size:18px; opacity:0.8;">PKR</small></div>
        <div class="bal-eq">
            <img src="../assets/img/usdt.png" style="width:18px; height:18px;" onerror="this.style.display='none'">
            ≈ <?= number_format($usdt_eq, 2) ?> USDT
        </div>
    </div>
</div>

<div class="rate-bar anim-item d-2">
    <div>
        <span style="font-size:11px; font-weight:800; color:#94a3b8; letter-spacing:1px;">LIVE BUY RATE</span>
        <div style="font-size:12px; font-weight:600; color:#64748b;">Updated just now</div>
    </div>
    <div class="rate-val">
        1 USDT = <?= number_format($sell_rate, 2) ?> PKR
    </div>
</div>

<div class="grid-wrap anim-item d-3">
    <?php if(empty($exchanges)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:var(--gray);">
            <div style="font-size:50px; margin-bottom:15px; opacity:0.3;">📭</div>
            <h4 style="font-weight:700; margin-bottom:5px;">No Methods</h4>
            <p style="font-size:14px;">Payment methods are currently unavailable.</p>
        </div>
    <?php else: ?>
        <?php foreach($exchanges as $ex): 
            $icon = !empty($ex['icon']) ? "../assets/img/icons/".$ex['icon'] : "../assets/img/usdt.png";
        ?>
        <div class="exch-card" 
             onclick="openBuyModal(
                 '<?= $ex['id'] ?>', 
                 '<?= addslashes($ex['name']) ?>', 
                 '<?= addslashes($ex['input_label']) ?>', 
                 '<?= addslashes($ex['input_placeholder']) ?>',
                 <?= $ex['min_limit'] ?>, 
                 <?= $ex['max_limit'] ?>
             )">
            <div class="ec-left">
                <img src="<?= $icon ?>" class="ec-icon" onerror="this.src='../assets/img/usdt.png'">
                <div>
                    <span class="ec-name"><?= htmlspecialchars($ex['name']) ?></span>
                    <span class="ec-limit">Min: <?= $ex['min_limit'] ?> USDT</span>
                </div>
            </div>
            <div class="btn-arrow"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="buyModal">
    <form class="sheet-modal" method="POST" action="buy_usdt_action.php">
        <input type="hidden" name="exchange_id" id="m_id">
        <input type="hidden" name="rate" value="<?= $sell_rate ?>">
        
        <div class="sm-head">
            <div class="sm-title">
                Buy via <span id="m_name" style="color:var(--primary); margin-left:5px;">Method</span>
            </div>
            <button type="button" class="sm-close" onclick="closeModal()">✕</button>
        </div>

        <div class="field-box">
            <label class="fb-label">You Buy (USDT)</label>
            <div class="fb-input-wrap">
                <i class="fa-solid fa-coins fb-icon"></i>
                <input type="number" name="amount_usdt" id="inp_amt" class="fb-input" placeholder="Min 10" step="any" required>
            </div>
            
            <div class="live-calc-box">
                <div class="calc-row">
                    <span>Exchange Rate</span>
                    <span class="calc-val"><?= $sell_rate ?> PKR</span>
                </div>
                <div class="calc-row">
                    <span>Limit</span>
                    <span class="calc-val" id="limit_disp">10 - 1000 USDT</span>
                </div>
                <div class="calc-total-row">
                    <span class="ct-label">You Pay:</span>
                    <span class="ct-amount" id="cost_display">0.00 PKR</span>
                </div>
                <div style="text-align:right; margin-top:5px; font-size:12px; font-weight:700;">
                    Available: <span id="wallet_status" style="color:var(--green);"><?= number_format($bal, 2) ?> PKR</span>
                </div>
            </div>
        </div>

        <div class="field-box">
            <label class="fb-label">Your Account Name</label>
            <div class="fb-input-wrap">
                <i class="fa-solid fa-user fb-icon"></i>
                <input type="text" name="sender_name" class="fb-input" placeholder="For verification..." required>
            </div>
        </div>

        <div class="field-box">
            <label class="fb-label" id="m_label">Wallet Address</label>
            <div class="fb-input-wrap">
                <i class="fa-solid fa-wallet fb-icon"></i>
                <input type="text" name="wallet_address" id="m_input" class="fb-input" required>
            </div>
        </div>

        <button type="submit" class="btn-main" id="btnSub">
            Confirm & Pay <span id="btn_amt"></span>
        </button>
    </form>
</div>

<script>
    let rate = <?= $sell_rate ?>;
    let userBal = <?= $bal ?>;
    let min = 10, max = 1000;

    function openBuyModal(id, name, label, placeholder, minLimit, maxLimit) {
        document.getElementById('m_id').value = id;
        document.getElementById('m_name').innerText = name;
        document.getElementById('m_label').innerText = label;
        document.getElementById('m_input').placeholder = placeholder;
        
        // Limits
        min = parseFloat(minLimit); max = parseFloat(maxLimit);
        document.getElementById('limit_hint').innerText = `${min} - ${max} USDT`; // Fallback if calc box used logic
        document.getElementById('limit_disp').innerText = `${min} - ${max} USDT`;
        document.getElementById('inp_amt').placeholder = `Min ${min}`;
        
        // Reset Inputs
        document.getElementById('inp_amt').value = '';
        document.getElementById('cost_display').innerText = "0.00 PKR";
        document.getElementById('btnSub').disabled = true;
        
        document.getElementById('buyModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('buyModal').classList.remove('active');
    }

    // Live Math
    document.getElementById('inp_amt').addEventListener('input', function(e) {
        let usdt = parseFloat(e.target.value);
        let pkr = usdt * rate;
        let btn = document.getElementById('btnSub');
        let costTxt = document.getElementById('cost_display');
        let walStatus = document.getElementById('wallet_status');

        if(isNaN(usdt) || usdt <= 0) {
            costTxt.innerText = "0.00 PKR";
            btn.disabled = true;
            return;
        }

        costTxt.innerText = pkr.toFixed(2) + " PKR";
        document.getElementById('btn_amt').innerText = "(" + pkr.toFixed(0) + " PKR)";

        let valid = true;
        
        // 1. Balance Check
        if(pkr > userBal) {
            walStatus.innerHTML = `<span style="color:#ef4444">Insufficient (${(pkr-userBal).toFixed(0)} needed)</span>`;
            valid = false;
        } else {
            walStatus.innerHTML = `<span style="color:#10b981">${userBal.toFixed(2)} PKR</span>`;
        }

        // 2. Limits Check
        if(usdt < min || usdt > max) valid = false;

        btn.disabled = !valid;
    });
</script>

<div style="display:none;" id="limit_hint"></div>

</body>
</html>
<?php include '_smm_footer.php'; ?>