<?php
include '_header.php';

// --- SETTINGS ---
$site_name = $GLOBALS['settings']['site_name'] ?? 'SubHub';
$site_logo = $GLOBALS['settings']['site_logo'] ?? '';
$admin_wa = $GLOBALS['settings']['whatsapp_number'] ?? '';
$logo_url = !empty($site_logo) ? "../assets/img/$site_logo" : "";

// --- ICONS LIST (Dropdown ke liye) ---
$icon_options = [
    'smm.png' => 'Default (SMM)',
    'Instagram.png' => 'Instagram',
    'TikTok.png' => 'TikTok',
    'Youtube.png' => 'YouTube',
    'Facebook.png' => 'Facebook',
    'Twitter.png' => 'Twitter',
    'Spotify.png' => 'Spotify',
    'Netflix.png' => 'Netflix',
    'Snapchat.png' => 'Snapchat',
    'Whatsapp.png' => 'WhatsApp',
    'Pubg.png' => 'Pubg',
    'Canva.png' => 'Canva',
    'Website-Traffic.png' => 'Website Traffic'
];
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

<style>
    /* --- PAGE LAYOUT --- */
    body { background: #f8fafc; font-family: 'Outfit', sans-serif; color: #1e293b; }
    
    .maker-wrapper {
        display: flex; gap: 40px; max-width: 1300px; margin: 40px auto; padding: 0 20px;
        align-items: flex-start;
    }

    /* --- LEFT SIDE: EDITOR --- */
    .editor-card {
        flex: 1; background: #fff; padding: 30px; border-radius: 24px;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    }
    
    .section-head {
        font-size: 0.9rem; font-weight: 800; color: #64748b; text-transform: uppercase; 
        letter-spacing: 1px; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;
        display: flex; align-items: center; gap: 8px;
    }
    .section-head i { color: #4f46e5; }

    /* Input Styling */
    .input-row { display: flex; gap: 15px; margin-bottom: 15px; }
    .input-grp { flex: 1; }
    .lbl { display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    
    .inp {
        width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px;
        font-size: 0.95rem; outline: none; transition: 0.2s; background: #f8fafc; color: #0f172a;
    }
    .inp:focus { border-color: #4f46e5; background: #fff; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }

    /* Item Manager */
    .item-box { background: #f1f5f9; padding: 15px; border-radius: 16px; margin-bottom: 15px; }
    .item-single { 
        display: grid; grid-template-columns: 50px 3fr 1fr 1.5fr 30px; gap: 10px; 
        align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;
    }
    .item-single:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    
    .icon-select {
        width: 100%; padding: 0; border: none; background: transparent; cursor: pointer;
        height: 40px; display: flex; align-items: center; justify-content: center;
    }
    .icon-preview { width: 35px; height: 35px; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; }

    .del-btn { color: #ef4444; cursor: pointer; text-align: center; font-size: 1.1rem; transition: 0.2s; }
    .del-btn:hover { transform: scale(1.2); }

    .btn-action {
        width: 100%; padding: 14px; border-radius: 14px; font-weight: 700; cursor: pointer; border: none;
        display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 1rem; margin-top: 15px;
    }
    .btn-add { background: #e0e7ff; color: #4338ca; }
    .btn-add:hover { background: #c7d2fe; }

    .btn-dl { background: linear-gradient(135deg, #0f172a 0%, #334155 100%); color: #fff; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3); }
    .btn-dl:hover { transform: translateY(-3px); box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.4); }

    /* --- RIGHT SIDE: PREVIEW --- */
    .preview-card {
        flex: 1; background: #cbd5e1; padding: 40px; border-radius: 24px;
        display: flex; justify-content: center; align-items: flex-start;
        position: sticky; top: 20px; min-height: 800px;
        background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 20px 20px;
    }

    /* --- 🔥 RECEIPT DESIGN (SMM STYLE) 🔥 --- */
    #receipt {
        width: 450px; min-height: 700px; background: #fff;
        box-shadow: 0 25px 60px -15px rgba(0,0,0,0.3);
        position: relative; overflow: hidden;
        display: flex; flex-direction: column;
    }

    /* Header */
    .rec-head {
        background: #fff; padding: 40px 30px 20px 30px;
        display: flex; justify-content: space-between; align-items: flex-start;
        border-bottom: 2px solid #f1f5f9;
    }
    /* Logo Fix: Specific Dimensions */
    .rec-logo { width: auto; height: 60px; object-fit: contain; display: block; } 
    .rec-brand-name { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1; }
    
    .rec-inv-label { font-size: 3rem; font-weight: 900; color: #f1f5f9; line-height: 0.8; letter-spacing: -2px; text-transform: uppercase; }

    /* Client Info */
    .rec-meta { padding: 25px 30px; display: flex; justify-content: space-between; align-items: flex-end; }
    .rec-to-lbl { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; }
    .rec-client-name { font-size: 1.3rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .rec-client-phone { font-size: 0.95rem; color: #64748b; font-weight: 500; }
    
    .rec-date-box { text-align: right; }
    .rec-date { font-weight: 700; color: #334155; font-size: 0.95rem; }
    .rec-id { font-family: 'JetBrains Mono', monospace; color: #64748b; font-size: 0.85rem; margin-top: 4px; }

    /* Table */
    .rec-table { flex: 1; padding: 10px 30px; }
    .t-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px dashed #e2e8f0; }
    .t-head { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 5px; }
    .t-h-col { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
    
    .t-icon { width: 35px; height: 35px; border-radius: 10px; object-fit: cover; margin-right: 15px; border: 1px solid #f1f5f9; }
    .t-desc { flex: 1; }
    .t-name { font-weight: 700; font-size: 0.95rem; color: #1e293b; display: block; line-height: 1.3; }
    .t-sub { font-size: 0.8rem; color: #64748b; font-weight: 500; }
    .t-price { font-weight: 800; font-size: 1rem; color: #0f172a; font-family: 'JetBrains Mono', monospace; }

    /* Totals */
    .rec-total { background: #f8fafc; padding: 25px 30px; margin-top: 20px; border-top: 2px dashed #cbd5e1; }
    .rt-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; color: #64748b; font-weight: 600; }
    .rt-final { border-top: 2px solid #e2e8f0; padding-top: 15px; margin-top: 10px; align-items: center; }
    .rt-final span:first-child { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
    .rt-final span:last-child { font-size: 1.8rem; font-weight: 900; color: #4f46e5; }

    /* Footer */
    .rec-foot { background: #1e293b; color: #fff; padding: 20px 30px; text-align: center; font-size: 0.85rem; font-weight: 500; opacity: 1; }

    /* Badge/Stamp */
    .stamp {
        position: absolute; top: 160px; right: 30px;
        border: 4px solid; padding: 10px 25px;
        font-size: 2rem; font-weight: 900; text-transform: uppercase;
        transform: rotate(-15deg); opacity: 0.15; letter-spacing: 5px; pointer-events: none;
    }
    .s-paid { color: #16a34a; border-color: #16a34a; }
    .s-unpaid { color: #ef4444; border-color: #ef4444; }

    /* Dropdown Custom */
    .icon-dropdown {
        position: absolute; background: #fff; border: 1px solid #e2e8f0; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 12px; 
        width: 200px; max-height: 250px; overflow-y: auto; z-index: 100;
        display: none; padding: 5px;
    }
    .icon-opt {
        display: flex; align-items: center; gap: 10px; padding: 8px 10px;
        cursor: pointer; border-radius: 8px; transition: 0.1s; font-size: 0.9rem;
    }
    .icon-opt:hover { background: #f1f5f9; }
    .icon-opt img { width: 20px; height: 20px; object-fit: contain; }

    @media(max-width: 1000px) {
        .maker-wrapper { flex-direction: column; }
        .editor-card, .preview-card { width: 100%; }
        #receipt { width: 100%; max-width: 480px; margin: 0 auto; }
    }
</style>

<div class="maker-wrapper">
    
    <div class="editor-card">
        <h2 style="margin-top:0; font-size:1.8rem; margin-bottom:5px;">🧾 Receipt Studio</h2>
        <p style="color:#64748b; margin-bottom:30px; font-size:0.95rem;">Create professional invoices for manual orders.</p>

        <div class="section-head"><i class="fa-regular fa-user"></i> Customer Details</div>
        <div class="input-row">
            <div class="input-grp">
                <label class="lbl">Customer Name</label>
                <input type="text" id="i-name" class="inp" value="Valued Customer" oninput="updateRec()">
            </div>
            <div class="input-grp">
                <label class="lbl">Phone / Ref</label>
                <input type="text" id="i-phone" class="inp" value="+92 300 1234567" oninput="updateRec()">
            </div>
        </div>

        <div class="input-row">
            <div class="input-grp">
                <label class="lbl">Invoice #</label>
                <input type="text" id="i-inv" class="inp" value="INV-<?= rand(100,999) ?>" oninput="updateRec()">
            </div>
            <div class="input-grp">
                <label class="lbl">Date</label>
                <input type="date" id="i-date" class="inp" value="<?= date('Y-m-d') ?>" oninput="updateRec()">
            </div>
        </div>

        <div class="section-head" style="margin-top:30px;"><i class="fa-solid fa-list-check"></i> Order Items</div>
        <div class="item-box" id="item-container">
            </div>
        
        <button class="btn-action btn-add" onclick="addItem()">
            <i class="fa-solid fa-plus"></i> Add Service / Product
        </button>

        <div class="section-head" style="margin-top:30px;"><i class="fa-solid fa-calculator"></i> Totals</div>
        <div class="input-row">
            <div class="input-grp">
                <label class="lbl">Discount</label>
                <input type="number" id="i-disc" class="inp" value="0" oninput="updateRec()">
            </div>
            <div class="input-grp">
                <label class="lbl">Currency</label>
                <select id="i-curr" class="inp" onchange="updateRec()">
                    <option value="PKR">PKR (Rs)</option>
                    <option value="USD">USD ($)</option>
                    <option value="INR">INR (₹)</option>
                </select>
            </div>
        </div>
        
        <div class="input-grp">
            <label class="lbl">Status Stamp</label>
            <select id="i-status" class="inp" onchange="updateRec()">
                <option value="paid">✅ PAID</option>
                <option value="unpaid">❌ UNPAID</option>
            </select>
        </div>

        <button class="btn-action btn-dl" onclick="downloadHD()">
            <i class="fa-solid fa-download"></i> Download Receipt
        </button>
    </div>

    <div class="preview-card">
        <div id="receipt">
            
            <div class="rec-head">
                <div>
                    <?php if(!empty($logo_url)): ?>
                        <img src="<?= $logo_url ?>" class="rec-logo">
                    <?php else: ?>
                        <h2 class="rec-brand-name"><?= $site_name ?></h2>
                    <?php endif; ?>
                    <div style="font-size:0.8rem; color:#64748b; margin-top:5px; font-weight:600;">
                        Official Receipt
                    </div>
                </div>
                <div class="rec-inv-label">INV</div>
            </div>

            <div class="rec-meta">
                <div>
                    <div class="rec-to-lbl">Billed To</div>
                    <div class="rec-client-name" id="o-name">Customer Name</div>
                    <div class="rec-client-phone" id="o-phone">+92 ...</div>
                </div>
                <div class="rec-date-box">
                    <div class="rec-date" id="o-date">Oct 24, 2024</div>
                    <div class="rec-id" id="o-inv">#INV-001</div>
                </div>
            </div>

            <div class="rec-table">
                <div class="t-row t-head">
                    <div class="t-h-col" style="flex:1">Service Details</div>
                    <div class="t-h-col" style="width:80px; text-align:right;">Amount</div>
                </div>
                
                <div id="o-items">
                    </div>
            </div>

            <div id="o-stamp" class="stamp s-paid">PAID</div>

            <div class="rec-total">
                <div class="rt-row">
                    <span>Subtotal</span>
                    <span id="o-sub">0.00</span>
                </div>
                <div class="rt-row">
                    <span>Discount</span>
                    <span id="o-disc" style="color:#ef4444;">-0.00</span>
                </div>
                <div class="rt-row rt-final">
                    <span>Grand Total</span>
                    <span id="o-total">0.00</span>
                </div>
            </div>

            <div class="rec-foot">
                Thank you for your business! <br>
                For support: <b><?= $admin_wa ?></b>
            </div>

        </div>
    </div>

</div>

<div id="icon-dropdown-template" class="icon-dropdown">
    <?php foreach($icon_options as $file => $name): ?>
    <div class="icon-opt" onclick="selectIcon(this, '<?= $file ?>')">
        <img src="../assets/img/icons/<?= $file ?>" onerror="this.src='../assets/img/icons/smm.png'">
        <span><?= $name ?></span>
    </div>
    <?php endforeach; ?>
</div>

<script>
let rowCount = 0;
const brandPath = '../assets/img/icons/';
let activeIconBtn = null;

// Init
window.onload = function() {
    addItem('Instagram Followers', 1000, 150, 'Instagram.png');
    updateRec();
    
    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.icon-select') && !e.target.closest('.icon-dropdown')) {
            document.querySelectorAll('.icon-dropdown').forEach(el => el.style.display = 'none');
        }
    });
};

function addItem(name='', qty=1, price=0, icon='smm.png') {
    rowCount++;
    const id = rowCount;
    
    const div = document.createElement('div');
    div.className = 'item-single';
    div.id = `row-${id}`;
    div.innerHTML = `
        <button class="icon-select" onclick="toggleIconMenu(this, ${id})">
            <img src="${brandPath}${icon}" class="icon-preview" id="icon-prev-${id}">
            <input type="hidden" class="inp-icon" value="${icon}">
        </button>
        
        <input type="text" class="inp inp-name" placeholder="Service Name" value="${name}" oninput="updateRec()">
        <input type="number" class="inp inp-qty" placeholder="Qty" value="${qty}" oninput="updateRec()">
        <input type="number" class="inp inp-price" placeholder="Price" value="${price}" oninput="updateRec()">
        <div class="del-btn" onclick="delItem(${id})"><i class="fa-solid fa-trash"></i></div>
        
        <div class="icon-menu-container" style="position:relative;"></div>
    `;
    
    document.getElementById('item-container').appendChild(div);
    updateRec();
}

function delItem(id) {
    document.getElementById(`row-${id}`).remove();
    updateRec();
}

function toggleIconMenu(btn, id) {
    // Hide all others
    document.querySelectorAll('.icon-dropdown').forEach(el => el.style.display = 'none');
    
    // Check if dropdown exists, else clone template
    let menu = btn.parentElement.querySelector('.icon-dropdown');
    if (!menu) {
        menu = document.getElementById('icon-dropdown-template').cloneNode(true);
        menu.id = '';
        // Add click handlers for this specific row
        menu.querySelectorAll('.icon-opt').forEach(opt => {
            const val = opt.getAttribute('onclick').match(/'([^']+)'/)[1];
            opt.onclick = function() { setIcon(id, val); };
        });
        btn.parentElement.appendChild(menu);
        
        // Positioning
        menu.style.top = "45px";
        menu.style.left = "0";
    }
    
    menu.style.display = 'block';
}

function setIcon(id, iconFile) {
    const row = document.getElementById(`row-${id}`);
    row.querySelector('.inp-icon').value = iconFile;
    row.querySelector('.icon-preview').src = brandPath + iconFile;
    row.querySelector('.icon-dropdown').style.display = 'none';
    updateRec();
}

function updateRec() {
    // Basic Info
    document.getElementById('o-name').innerText = document.getElementById('i-name').value;
    document.getElementById('o-phone').innerText = document.getElementById('i-phone').value;
    document.getElementById('o-inv').innerText = '#' + document.getElementById('i-inv').value;
    
    const d = new Date(document.getElementById('i-date').value);
    document.getElementById('o-date').innerText = d.toLocaleDateString('en-US', { day:'numeric', month:'short', year:'numeric' });

    // Status
    const status = document.getElementById('i-status').value;
    const stamp = document.getElementById('o-stamp');
    stamp.className = `stamp s-${status}`;
    stamp.innerText = status;

    // Currency
    const cur = document.getElementById('i-curr').value;
    let sym = 'Rs';
    if(cur === 'USD') sym = '$';
    if(cur === 'INR') sym = '₹';

    // Render Items
    const rows = document.querySelectorAll('.item-single');
    const outBox = document.getElementById('o-items');
    outBox.innerHTML = '';
    
    let subtotal = 0;

    rows.forEach(r => {
        const name = r.querySelector('.inp-name').value;
        const qty = r.querySelector('.inp-qty').value;
        const price = parseFloat(r.querySelector('.inp-price').value) || 0;
        const icon = r.querySelector('.inp-icon').value;
        
        // Total per item logic (can be Price * Qty OR just Price)
        // Here assuming Price is Total Price for that service
        const total = price; 
        subtotal += total;

        if(name) {
            outBox.innerHTML += `
            <div class="t-row">
                <img src="${brandPath}${icon}" class="t-icon" onerror="this.src='../assets/img/icons/smm.png'">
                <div class="t-desc">
                    <span class="t-name">${name}</span>
                    <span class="t-sub">Qty: ${qty}</span>
                </div>
                <div class="t-price">${sym} ${total.toLocaleString()}</div>
            </div>`;
        }
    });

    // Totals
    const disc = parseFloat(document.getElementById('i-disc').value) || 0;
    const grand = subtotal - disc;

    document.getElementById('o-sub').innerText = `${sym} ${subtotal.toLocaleString()}`;
    document.getElementById('o-disc').innerText = `-${sym} ${disc.toLocaleString()}`;
    document.getElementById('o-total').innerText = `${sym} ${grand.toLocaleString()}`;
}

function downloadHD() {
    const btn = document.querySelector('.btn-dl');
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '⚙️ Generating...';

    const element = document.getElementById('receipt');

    html2canvas(element, {
        scale: 3, // HD Quality
        useCORS: true,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const a = document.createElement('a');
        a.download = 'Invoice-' + document.getElementById('i-inv').value + '.jpg';
        a.href = canvas.toDataURL('image/jpeg', 0.95);
        a.click();
        btn.innerHTML = oldHtml;
    });
}
</script>

<?php include '_footer.php'; ?>