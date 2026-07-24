<?php
include '_header.php';
requireAdmin();

// --- 1. SAVE HANDLER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $designData = $_POST['design_data'];
    
    // Database Update
    $check = $db->query("SELECT id FROM settings WHERE setting_key='card_design_config'");
    if($check->fetch()){
        $stmt = $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='card_design_config'");
    } else {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('card_design_config', ?)");
    }
    $stmt->execute([$designData]);
    
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'success', title: 'Layout Saved!', text: 'User cards updated successfully.', confirmButtonColor: '#4f46e5' });
        });
    </script>";
}

// --- 2. FETCH SETTINGS ---
$saved = $db->query("SELECT setting_value FROM settings WHERE setting_key='card_design_config'")->fetchColumn();

// Default Configuration (Matches User Panel Logic)
$defaultConfig = [
    'canvas' => ['width' => 480, 'height' => 650, 'bg' => '#f8f8ff'],
    'elements' => [
        'logo_block' => [
            'type' => 'image', 'x' => 165, 'y' => 30, 'w' => 150, 'h' => 50,
            'text' => '', 'src' => 'logo', 'styles' => []
        ],
        'verified_badge' => [
            'type' => 'text', 'x' => 320, 'y' => 35, 'w' => 130, 'h' => 40,
            'text' => "Verified Service\n★ ★ ★ ★ ★",
            'styles' => ['fontSize'=>'10px', 'color'=>'#94a3b8', 'fontWeight'=>'700', 'textAlign'=>'right', 'lineHeight'=>'1.5']
        ],
        'cat_tag' => [
            'type' => 'text', 'x' => 25, 'y' => 100, 'w' => 150, 'h' => 30,
            'text' => 'CATEGORY NAME',
            'styles' => ['fontSize'=>'11px', 'color'=>'#4f46e5', 'bg'=>'#eff6ff', 'border'=>'1px solid #c7d2fe', 'radius'=>'50px', 'textAlign'=>'center', 'padding'=>'6px', 'fontWeight'=>'700']
        ],
        'service_title' => [
            'type' => 'text', 'x' => 25, 'y' => 140, 'w' => 430, 'h' => 60,
            'text' => 'Service Name Here',
            'styles' => ['fontSize'=>'26px', 'color'=>'#1e293b', 'fontWeight'=>'800', 'lineHeight'=>'1.2', 'textAlign'=>'left']
        ],
        'price_box' => [ // COMPLEX CONTAINER
            'type' => 'container', 'x' => 25, 'y' => 210, 'w' => 430, 'h' => 75,
            'text' => '', 
            'content' => '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;height:100%;"><div style="font-size:12px;text-transform:uppercase;font-weight:700;opacity:0.8;">Rate per 1000</div><div style="font-size:32px;font-weight:800;">Rs 500</div></div>',
            'styles' => ['bg'=>'#ecfdf5', 'color'=>'#059669', 'border'=>'1px solid #a7f3d0', 'radius'=>'16px', 'padding'=>'0 25px', 'display'=>'flex', 'alignItems'=>'center']
        ],
        'stats_row' => [ // COMPLEX CONTAINER
            'type' => 'container', 'x' => 25, 'y' => 300, 'w' => 430, 'h' => 60,
            'text' => '',
            'content' => '<div style="display:flex;gap:10px;width:100%;height:100%;"><div style="flex:1;border:1px solid #cbd5e1;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:10px;font-weight:700;opacity:0.6;text-transform:uppercase;">Time</div><div style="font-size:13px;font-weight:700;">Instant</div></div><div style="flex:1;border:1px solid #cbd5e1;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:10px;font-weight:700;opacity:0.6;text-transform:uppercase;">Refill</div><div style="font-size:13px;font-weight:700;">Yes</div></div><div style="flex:1;border:1px solid #cbd5e1;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;"><div style="font-size:10px;font-weight:700;opacity:0.6;text-transform:uppercase;">Cancel</div><div style="font-size:13px;font-weight:700;">No</div></div></div>',
            'styles' => ['bg'=>'transparent', 'color'=>'#64748b', 'radius'=>'0', 'padding'=>'0']
        ],
        'desc_box' => [
            'type' => 'text', 'x' => 25, 'y' => 380, 'w' => 430, 'h' => 120,
            'text' => 'Service description will appear in this box. Auto-scroll enabled for long text.',
            'styles' => ['bg'=>'#ffffff', 'color'=>'#475569', 'border'=>'1px solid #e2e8f0', 'borderLeft'=>'4px solid #4f46e5', 'radius'=>'12px', 'padding'=>'20px', 'fontSize'=>'14px', 'lineHeight'=>'1.6', 'textAlign'=>'left']
        ],
        'footer_btn' => [
            'type' => 'button', 'x' => 25, 'y' => 580, 'w' => 140, 'h' => 40,
            'text' => 'ORDER NOW',
            'styles' => ['bg'=>'#4f46e5', 'color'=>'#ffffff', 'radius'=>'50px', 'textAlign'=>'center', 'padding'=>'10px', 'fontWeight'=>'800', 'fontSize'=>'13px']
        ],
        'footer_wa' => [
            'type' => 'text', 'x' => 250, 'y' => 585, 'w' => 200, 'h' => 30,
            'text' => '✆ +92 300 1234567',
            'styles' => ['color'=>'#0f172a', 'fontSize'=>'16px', 'fontWeight'=>'700', 'textAlign'=>'right']
        ]
    ]
];

$config = $saved ? json_decode($saved, true) : $defaultConfig;
if(!isset($config['elements'])) $config = $defaultConfig;
$elements = $config['elements'];

// Helpers
$siteLogo = !empty($GLOBALS['settings']['site_logo']) ? "../assets/img/".$GLOBALS['settings']['site_logo'] : "https://via.placeholder.com/150x50?text=LOGO";
function styleMap($k) { $map=['fontSize'=>'font-size','bg'=>'background-color','textAlign'=>'text-align','fontWeight'=>'font-weight','radius'=>'border-radius','borderLeft'=>'border-left','lineHeight'=>'line-height','alignItems'=>'align-items','justifyContent'=>'justify-content','display'=>'display','padding'=>'padding','color'=>'color','border'=>'border']; return $map[$k]??$k; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root { --primary: #4f46e5; --dark: #1e293b; }
    body { background: #e2e8f0; font-family: 'Outfit', sans-serif; height: 100vh; margin: 0; overflow: hidden; display: flex; }

    /* SIDEBAR */
    .sidebar { width: 340px; background: #fff; border-right: 1px solid #cbd5e1; display: flex; flex-direction: column; z-index: 20; box-shadow: 10px 0 30px rgba(0,0,0,0.05); }
    .sb-header { padding: 20px; border-bottom: 1px solid #f1f5f9; }
    .sb-body { flex: 1; padding: 20px; overflow-y: auto; }
    
    .prop-group { margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
    .prop-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
    .prop-row { display: flex; gap: 8px; margin-bottom: 8px; }
    .inp { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 13px; background: #fff; }
    .inp:focus { border-color: var(--primary); outline: none; }
    input[type="color"] { padding: 2px; height: 35px; cursor: pointer; }
    .mode-select { width: 100%; padding: 12px; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: 8px; font-weight: 700; cursor: pointer; margin-bottom: 20px; outline:none; }

    /* WORKSPACE */
    .workspace { flex: 1; background-color: #cbd5e1; background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 20px 20px; display: flex; justify-content: center; align-items: center; overflow: auto; }
    #canvas { position: relative; background: <?= $config['canvas']['bg'] ?>; width: <?= $config['canvas']['width'] ?>px; height: <?= $config['canvas']['height'] ?>px; box-shadow: 0 50px 100px -20px rgba(0,0,0,0.3); transition: 0.3s; }

    /* ELEMENTS */
    .el { position: absolute; cursor: move; user-select: none; box-sizing: border-box; border: 1px dashed transparent; display: flex; align-items: center; overflow: hidden; }
    .el:hover { border: 1px dashed #4f46e5; background: rgba(79, 70, 229, 0.05); }
    .el.selected { border: 2px solid var(--primary); z-index: 100; background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(2px); }
    .resizer { width: 12px; height: 12px; background: var(--primary); position: absolute; right: 0; bottom: 0; cursor: se-resize; border-radius: 2px; display: none; z-index: 101; }
    .el.selected .resizer { display: block; }
    .el-inner { width: 100%; height: 100%; pointer-events: none; white-space: pre-wrap; word-break: break-word; }
    
    .btn-save { width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; }
</style>
</head>
<body>

<form id="saveForm" method="POST" class="sidebar">
    <input type="hidden" name="design_data" id="jsonOutput">
    
    <div class="sb-header">
        <h3 style="margin:0 0 15px 0;">🎨 Card Designer</h3>
        <select id="previewMode" class="mode-select" onchange="switchMode()">
            <option value="receipt">👀 Receipt (SMM Order)</option>
            <option value="update">🚀 Alert (New Update)</option>
            <option value="download">📥 Digital (Download)</option>
        </select>
        <a href="index.php" style="color:#64748b; font-size:12px; text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Exit Editor</a>
    </div>

    <div class="sb-body">
        <div class="prop-group">
            <div class="prop-label">Canvas Properties</div>
            <div class="prop-row">
                <input type="color" id="canvasBg" value="<?= $config['canvas']['bg'] ?>" class="inp">
                <input type="number" id="canvasW" value="<?= $config['canvas']['width'] ?>" class="inp">
                <input type="number" id="canvasH" value="<?= $config['canvas']['height'] ?>" class="inp">
            </div>
        </div>

        <div id="elementProps" style="display:none;">
            <div style="background:#eef2ff; color:#4f46e5; padding:8px; border-radius:6px; font-size:11px; font-weight:800; margin-bottom:10px; text-align:center;">EDITING: <span id="selName"></span></div>
            <div class="prop-group">
                <label class="prop-label">Text Content</label>
                <textarea id="styText" class="inp" rows="3"></textarea>
            </div>
            <div class="prop-group">
                <label class="prop-label">Style</label>
                <div class="prop-row">
                    <input type="number" id="stySize" class="inp" placeholder="Size (px)">
                    <input type="color" id="styColor" class="inp" title="Text Color">
                    <input type="color" id="styBg" class="inp" title="BG Color">
                </div>
                <div class="prop-row">
                    <input type="number" id="styWeight" class="inp" placeholder="Weight">
                    <input type="text" id="styRadius" class="inp" placeholder="Radius">
                    <input type="text" id="styBorder" class="inp" placeholder="Border">
                </div>
            </div>
            <div class="prop-group">
                <label class="prop-label">Position</label>
                <div class="prop-row">
                    <input type="number" id="posX" class="inp" placeholder="X">
                    <input type="number" id="posY" class="inp" placeholder="Y">
                    <input type="number" id="posW" class="inp" placeholder="W">
                    <input type="number" id="posH" class="inp" placeholder="H">
                </div>
            </div>
        </div>
        <div id="noSel" style="text-align:center; color:#94a3b8; font-size:13px; padding:30px 10px;">Click any element to edit.</div>
    </div>

    <div style="padding:20px; border-top:1px solid #eee;">
        <button type="button" class="btn-save" onclick="saveDesign()"><i class="fa-solid fa-floppy-disk"></i> Save Layout</button>
    </div>
</form>

<div class="workspace">
    <div id="canvas">
        <?php foreach($elements as $key => $el): 
            $styleStr = "";
            if(isset($el['styles'])) foreach($el['styles'] as $k=>$v) $styleStr .= styleMap($k).":$v;";
        ?>
            <div id="<?= $key ?>" class="el" 
                 style="left:<?= $el['x'] ?>px; top:<?= $el['y'] ?>px; width:<?= $el['w'] ?>px; height:<?= $el['h'] ?>px; <?= $styleStr ?>"
                 data-type="<?= $el['type'] ?>" 
                 data-original-text="<?= htmlspecialchars($el['text']) ?>"
                 data-original-content="<?= htmlspecialchars($el['content'] ?? '') ?>">
                <div class="el-inner">
                    <?php if($el['type'] === 'image' && $key === 'logo_block'): ?>
                        <img src="<?= $siteLogo ?>">
                    <?php elseif($el['type'] === 'container'): ?>
                        <?= $el['content'] ?? '' ?>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($el['text'])) ?>
                    <?php endif; ?>
                </div>
                <div class="resizer"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const canvas = document.getElementById('canvas');
let activeEl = null, isDragging = false, isResizing = false;
let dragOffset = {x:0, y:0}, startDim = {w:0, h:0, x:0, y:0};

// --- PREVIEW LOGIC (Exact HTML Match) ---
const scenarios = {
    receipt: {
        cat_tag: 'INSTAGRAM',
        service_title: 'Instagram Followers | Real HQ',
        price_val: 'Rs 150.00',
        stats_data: ['1 Hour', 'Yes', 'No'], // Time, Refill, Cancel
        desc_box: 'Get high quality real followers with lifetime guarantee.',
        footer_btn: 'ORDER NOW'
    },
    update: {
        cat_tag: 'PRICE DROP',
        service_title: 'TikTok Views - 50% OFF 🔥',
        price_val: 'Rs 10.00',
        stats_data: ['Instant', 'No', 'Yes'],
        desc_box: 'Massive price drop! Cheapest in market. Limited time offer.',
        footer_btn: 'CHECK NOW'
    },
    download: {
        cat_tag: 'DIGITAL ITEM',
        service_title: 'Netflix 4K UHD (1 Month)',
        price_val: 'Rs 800.00',
        stats_data: ['Size: 2MB', 'English', 'Private'], 
        desc_box: 'Instant download. Email/Pass provided. 30 Days warranty.',
        footer_btn: 'BUY NOW'
    }
};

function switchMode() {
    const mode = document.getElementById('previewMode').value;
    const data = scenarios[mode];
    
    // Update Text Elements
    updateText('cat_tag', data.cat_tag);
    updateText('service_title', data.service_title);
    updateText('desc_box', data.desc_box);
    updateText('footer_btn', data.footer_btn);

    // Update Price Box (Keep Layout)
    const pBox = document.getElementById('price_box');
    if(pBox) {
        const innerHTML = pBox.querySelector('.el-inner').innerHTML;
        // Regex to replace Price Amount only
        pBox.querySelector('.el-inner').innerHTML = innerHTML.replace(/Rs\s*[\d\.]+/g, data.price_val);
    }

    // Update Stats Row (REBUILD PILLS)
    const sRow = document.getElementById('stats_row');
    if(sRow) {
        const labels = mode === 'download' ? ['Size', 'Lang', 'Type'] : ['Time', 'Refill', 'Cancel'];
        // Reconstruct the 3-pill layout exactly as User Side
        let pillsHTML = `<div style="display:flex;gap:10px;width:100%;height:100%;">`;
        for(let i=0; i<3; i++) {
            pillsHTML += `
            <div style="flex:1;border:1px solid #cbd5e1;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <div style="font-size:10px;font-weight:700;opacity:0.6;text-transform:uppercase;">${labels[i]}</div>
                <div style="font-size:13px;font-weight:700;">${data.stats_data[i]}</div>
            </div>`;
        }
        pillsHTML += `</div>`;
        sRow.querySelector('.el-inner').innerHTML = pillsHTML;
    }
}

function updateText(id, txt) {
    const el = document.getElementById(id);
    if(el) el.querySelector('.el-inner').innerText = txt;
}

// --- CONTROLS ---
document.getElementById('canvasBg').addEventListener('input', e => canvas.style.background = e.target.value);
document.getElementById('canvasW').addEventListener('input', e => canvas.style.width = e.target.value + 'px');
document.getElementById('canvasH').addEventListener('input', e => canvas.style.height = e.target.value + 'px');

document.querySelectorAll('.el').forEach(el => {
    el.addEventListener('mousedown', function(e) {
        if(e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') e.preventDefault();
        if(e.target.classList.contains('resizer')) {
            isResizing = true; activeEl = el;
            startDim = { w: el.offsetWidth, h: el.offsetHeight, x: e.clientX, y: e.clientY };
            setActive(el); e.stopPropagation();
        } else {
            setActive(el); isDragging = true;
            dragOffset = { x: e.clientX - el.offsetLeft, y: e.clientY - el.offsetTop };
        }
    });
});

document.addEventListener('mousemove', function(e) {
    if(!activeEl) return;
    if(isDragging) {
        activeEl.style.left = (e.clientX - dragOffset.x) + 'px';
        activeEl.style.top = (e.clientY - dragOffset.y) + 'px';
        updateInputs();
    } else if(isResizing) {
        activeEl.style.width = (startDim.w + (e.clientX - startDim.x)) + 'px';
        activeEl.style.height = (startDim.h + (e.clientY - startDim.y)) + 'px';
        updateInputs();
    }
});
document.addEventListener('mouseup', () => { isDragging = false; isResizing = false; });

const inputs = {
    x: document.getElementById('posX'), y: document.getElementById('posY'),
    w: document.getElementById('posW'), h: document.getElementById('posH'),
    text: document.getElementById('styText'),
    fontSize: document.getElementById('stySize'), color: document.getElementById('styColor'), bg: document.getElementById('styBg'),
    weight: document.getElementById('styWeight'), border: document.getElementById('styBorder'), radius: document.getElementById('styRadius')
};

function setActive(el) {
    if(activeEl) activeEl.classList.remove('selected');
    activeEl = el; activeEl.classList.add('selected');
    document.getElementById('noSel').style.display = 'none';
    document.getElementById('elementProps').style.display = 'block';
    document.getElementById('selName').innerText = el.id.toUpperCase();
    updateInputs();
}

function updateInputs() {
    if(!activeEl) return;
    inputs.x.value = parseInt(activeEl.style.left); inputs.y.value = parseInt(activeEl.style.top);
    inputs.w.value = parseInt(activeEl.style.width); inputs.h.value = parseInt(activeEl.style.height);
    inputs.fontSize.value = parseInt(activeEl.style.fontSize)||''; 
    inputs.color.value = rgbToHex(activeEl.style.color)||'#000000';
    inputs.bg.value = rgbToHex(activeEl.style.backgroundColor)||'#ffffff';
    inputs.weight.value = activeEl.style.fontWeight||'';
    inputs.border.value = activeEl.style.border||'';
    inputs.radius.value = activeEl.style.borderRadius||'';
    // Load text from Original Content (To avoid showing Preview text in input)
    inputs.text.value = activeEl.dataset.originalText || activeEl.innerText;
}

// Apply Logic
inputs.x.addEventListener('input', e => activeEl.style.left = e.target.value + 'px');
inputs.y.addEventListener('input', e => activeEl.style.top = e.target.value + 'px');
inputs.w.addEventListener('input', e => activeEl.style.width = e.target.value + 'px');
inputs.h.addEventListener('input', e => activeEl.style.height = e.target.value + 'px');
inputs.fontSize.addEventListener('input', e => activeEl.style.fontSize = e.target.value + 'px');
inputs.color.addEventListener('input', e => activeEl.style.color = e.target.value);
inputs.bg.addEventListener('input', e => activeEl.style.backgroundColor = e.target.value);
inputs.weight.addEventListener('input', e => activeEl.style.fontWeight = e.target.value);
inputs.border.addEventListener('input', e => activeEl.style.border = e.target.value);
inputs.radius.addEventListener('input', e => activeEl.style.borderRadius = e.target.value);

inputs.text.addEventListener('input', e => {
    if(activeEl.dataset.type === 'text' || activeEl.dataset.type === 'button') {
        activeEl.querySelector('.el-inner').innerText = e.target.value;
        activeEl.dataset.originalText = e.target.value;
    }
});

function saveDesign() {
    let elementsData = {};
    document.querySelectorAll('.el').forEach(el => {
        elementsData[el.id] = {
            type: el.dataset.type,
            x: parseInt(el.style.left), y: parseInt(el.style.top),
            w: parseInt(el.style.width), h: parseInt(el.style.height),
            // Always save the original template text/html
            text: el.dataset.originalText || el.querySelector('.el-inner').innerText,
            content: el.dataset.originalContent || (el.dataset.type==='container' ? el.querySelector('.el-inner').innerHTML : ''),
            styles: {
                fontSize: el.style.fontSize, color: el.style.color, bg: el.style.backgroundColor,
                textAlign: el.style.textAlign, fontWeight: el.style.fontWeight,
                border: el.style.border, radius: el.style.borderRadius,
                display: el.style.display, alignItems: el.style.alignItems, padding: el.style.padding
            }
        };
    });

    let data = {
        canvas: {
            width: parseInt(canvas.style.width), height: parseInt(canvas.style.height),
            bg: rgbToHex(canvas.style.backgroundColor) || document.getElementById('canvasBg').value
        },
        elements: elementsData
    };

    document.getElementById('jsonOutput').value = JSON.stringify(data);
    document.getElementById('saveForm').submit();
}

function rgbToHex(rgb) {
    if(!rgb || rgb === 'rgba(0, 0, 0, 0)') return '';
    if(rgb.startsWith('#')) return rgb;
    let sep = rgb.indexOf(",") > -1 ? "," : " ";
    rgb = rgb.substr(4).split(")")[0].split(sep);
    let r = (+rgb[0]).toString(16), g = (+rgb[1]).toString(16), b = (+rgb[2]).toString(16);
    if (r.length == 1) r = "0" + r; if (g.length == 1) g = "0" + g; if (b.length == 1) b = "0" + b;
    return "#" + r + g + b;
}
</script>

</body>
</html>