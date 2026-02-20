<?php
include '_header.php';
requireAdmin(); 

$success = '';
$error = '';

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_secret'])) {
    $title = sanitize($_POST['title']);
    $user = sanitize($_POST['username']);
    $pass = $_POST['secret_value'];
    $cat = sanitize($_POST['category']);
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE admin_vault SET title=?, username=?, secret_value=?, category=? WHERE id=?");
        if ($stmt->execute([$title, $user, $pass, $cat, $id])) $success = "Secret updated!";
    } else {
        $stmt = $db->prepare("INSERT INTO admin_vault (title, username, secret_value, category) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $user, $pass, $cat])) $success = "New secret saved!";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM admin_vault WHERE id=?")->execute([$id]);
    $success = "Item removed.";
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM admin_vault";
$params = [];
if ($search) { $sql .= " WHERE title LIKE ? OR category LIKE ?"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$secrets = $stmt->fetchAll();
?>

<style>
    /* Apple System Core Styles */
    :root {
        --system-bg: #F5F5F7;
        --card-bg: #FFFFFF;
        --text-primary: #1D1D1F;
        --text-secondary: #86868B;
        --accent-blue: #007AFF;
        --accent-blue-hover: #0066CC;
        --system-red: #FF3B30;
        --system-green: #34C759;
        --border-color: rgba(0, 0, 0, 0.08);
        --field-bg: rgba(118, 118, 128, 0.12);
        --vault-dark: #1C1C1E;
        --transition-spring: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: var(--system-bg);
        color: var(--text-primary);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .apple-vault-wrapper {
        width: min(100%, 1200px);
        margin: 40px auto;
        padding: 0 20px;
    }

    /* Success Alert */
    .apple-alert {
        background: #E5F9E0;
        color: #248A3D;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 500;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(52, 199, 89, 0.1);
    }

    /* Header */
    .apple-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .apple-header h1 {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--text-primary);
    }

    .apple-header p {
        margin: 4px 0 0 0;
        color: var(--text-secondary);
        font-size: 15px;
    }

    /* iOS Button */
    #btnOpenVault {
        background: var(--accent-blue);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition-spring);
        box-shadow: 0 2px 10px rgba(0, 122, 255, 0.2);
    }

    #btnOpenVault:hover {
        background: var(--accent-blue-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(0, 122, 255, 0.3);
    }

    #btnOpenVault:active {
        transform: scale(0.96);
    }

    #btnOpenVault:focus-visible {
        outline: 3px solid rgba(0, 122, 255, 0.5);
        outline-offset: 2px;
    }

    /* iOS Search Bar */
    .apple-search {
        background: var(--card-bg);
        padding: 12px 16px;
        border-radius: 14px;
        margin-bottom: 32px;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        transition: var(--transition-spring);
    }

    .apple-search:focus-within {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }

    .search-input {
        border: none;
        width: 100%;
        font-size: 17px;
        outline: none;
        background: transparent;
        color: var(--text-primary);
    }

    .search-input::placeholder {
        color: var(--text-secondary);
    }

    /* Grid */
    .vault-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    /* macOS Style Card */
    .secret-card {
        background: var(--card-bg);
        border-radius: 18px;
        padding: 24px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: var(--transition-spring);
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 16px;
        overflow: hidden;
    }

    .secret-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border-color: rgba(0, 0, 0, 0.12);
    }

    .sc-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .sc-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--field-bg);
        color: var(--accent-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .sc-cat {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        background: rgba(0, 122, 255, 0.1);
        color: var(--accent-blue);
        padding: 4px 10px;
        border-radius: 8px;
        letter-spacing: 0.5px;
    }

    .sc-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 12px 0 4px;
        letter-spacing: -0.01em;
        word-break: break-word;
    }

    .sc-user {
        color: var(--text-secondary);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Secret Field (macOS Password Style) */
    .sc-field {
        background: var(--vault-dark);
        border-radius: 14px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #fff;
        margin-top: auto;
        gap: 10px;
    }

    .sc-value {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        letter-spacing: 2px;
        font-size: 16px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    /* Icon Buttons */
    .btn-icon {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition-spring);
    }

    .btn-icon:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }

    .btn-icon:active {
        transform: scale(0.95);
    }

    /* Card Options (Edit/Delete) */
    .card-opts {
        position: absolute;
        top: 24px;
        right: 24px;
        display: flex;
        gap: 8px;
        opacity: 0;
        transition: var(--transition-spring);
    }

    .secret-card:hover .card-opts {
        opacity: 1;
    }

    .btn-opt {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid var(--border-color);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(5px);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        transition: var(--transition-spring);
    }

    .btn-opt:hover {
        background: var(--system-bg);
        color: var(--text-primary);
    }

    .btn-opt-delete:hover {
        color: var(--system-red);
        border-color: rgba(255, 59, 48, 0.3);
        background: rgba(255, 59, 48, 0.05);
    }

    /* macOS Style Modal */
    .vault-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 999999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .vault-modal-box {
        background: var(--card-bg);
        width: 100%;
        max-width: 480px;
        padding: 32px;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        animation: vaultZoom 0.25s cubic-bezier(0.25, 0.1, 0.25, 1);
        border: 1px solid var(--border-color);
    }

    @keyframes vaultZoom {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 8px;
        color: var(--text-primary);
    }

    .form-input {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        outline: none;
        font-size: 15px;
        background: var(--system-bg);
        color: var(--text-primary);
        transition: var(--transition-spring);
    }

    .form-input:focus {
        border-color: var(--accent-blue);
        background: var(--card-bg);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.15);
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        *, ::before, ::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* Mobile Safety */
    @media (max-width: 600px) {
        .card-opts { opacity: 1; position: static; justify-content: flex-end; margin-bottom: -10px; }
        .apple-header { flex-direction: column; align-items: stretch; }
        .apple-header button { width: 100%; justify-content: center; }
        .grid-2-col { grid-template-columns: 1fr !important; }
        .vault-modal-box { padding: 24px; }
    }
</style>

<div class="apple-vault-wrapper">
    <?php if($success): ?>
    <div class="apple-alert">
        <i class="fa-solid fa-circle-check"></i> <?= $success ?>
    </div>
    <?php endif; ?>
    
    <div class="apple-header">
        <div>
            <h1>🔐 Secure Vault</h1>
            <p>Store and manage your sensitive keys & passwords.</p>
        </div>
        <button type="button" id="btnOpenVault">
            <i class="fa-solid fa-plus"></i> Add New Secret
        </button>
    </div>

    <div class="apple-search">
        <i class="fa-solid fa-magnifying-glass" style="color: var(--text-secondary); font-size: 18px;"></i>
        <input type="text" class="search-input" placeholder="Search secrets..." onkeyup="filterSecrets(this.value)">
    </div>

    <div class="vault-grid">
        <?php if(empty($secrets)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--text-secondary); background: var(--card-bg); border-radius: 18px; border: 1px dashed var(--border-color);">
                <i class="fa-solid fa-vault" style="font-size: 40px; margin-bottom: 15px; color: var(--border-color);"></i>
                <p style="margin:0; font-size: 16px;">Vault is empty. Add your first secret securely.</p>
            </div>
        <?php else: ?>
            <?php foreach($secrets as $s): 
                $icon = 'fa-key';
                if(stripos($s['category'], 'server')!==false) $icon='fa-server';
                if(stripos($s['category'], 'mail')!==false) $icon='fa-envelope';
                if(stripos($s['category'], 'db')!==false) $icon='fa-database';
            ?>
            <div class="secret-card" data-title="<?= strtolower($s['title']) ?>">
                <div class="card-opts">
                    <button type="button" class="btn-opt" onclick='editVaultSecret(<?= json_encode($s) ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                    <a href="?delete=<?= $s['id'] ?>" class="btn-opt btn-opt-delete" onclick="return confirm('Are you sure you want to delete this secret?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                </div>
                
                <div class="sc-top">
                    <div class="sc-icon"><i class="fa-solid <?= $icon ?>"></i></div>
                    <span class="sc-cat"><?= sanitize($s['category']) ?></span>
                </div>
                
                <div>
                    <h3 class="sc-title"><?= sanitize($s['title']) ?></h3>
                    <div class="sc-user"><i class="fa-regular fa-user"></i> <?= sanitize($s['username']) ?: 'No username' ?></div>
                </div>

                <div class="sc-field">
                    <span class="sc-value" id="val_<?= $s['id'] ?>">••••••••••••</span>
                    <input type="hidden" id="real_<?= $s['id'] ?>" value="<?= sanitize($s['secret_value']) ?>">
                    <div style="display:flex; gap:6px; flex-shrink: 0;">
                        <button type="button" class="btn-icon" onclick="toggleViz(<?= $s['id'] ?>)" title="Toggle Visibility"><i class="fa-regular fa-eye" id="icon_<?= $s['id'] ?>"></i></button>
                        <button type="button" class="btn-icon" onclick="copySecret(<?= $s['id'] ?>)" title="Copy Secret"><i class="fa-regular fa-copy"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="vault-modal-overlay" id="vaultModal">
    <div class="vault-modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 22px; font-weight: 700; color: var(--text-primary);" id="modalTitle">Add New Secret</h3>
            <button type="button" id="btnCloseVault" style="background: none; border: none; font-size: 28px; color: var(--text-secondary); cursor: pointer; padding: 0; line-height: 1; transition: 0.2s;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="save_secret" value="1">
            <input type="hidden" name="id" id="inp_id">

            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" id="inp_title" class="form-input" placeholder="e.g. AWS Production DB" required>
            </div>
            
            <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="inp_cat" class="form-input">
                        <option value="General">General</option>
                        <option value="Server/Hosting">Server/Hosting</option>
                        <option value="Email/SMTP">Email/SMTP</option>
                        <option value="API Keys">API Keys</option>
                        <option value="Database">Database</option>
                        <option value="Wallet">Wallet/Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="inp_user" class="form-input" placeholder="Optional">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Secret / Password</label>
                <textarea name="secret_value" id="inp_val" class="form-input" rows="3" placeholder="Enter secret key or password" required style="font-family: ui-monospace, monospace; resize: vertical; min-height: 80px;"></textarea>
            </div>

            <button type="submit" style="width: 100%; padding: 16px; background: var(--accent-blue); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer; transition: var(--transition-spring); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.2);">Save Securely</button>
        </form>
    </div>
</div>

<script>
// Direct Event Listeners (Conflict Proof)
document.addEventListener('DOMContentLoaded', function() {
    
    const modal = document.getElementById('vaultModal');
    const btnOpen = document.getElementById('btnOpenVault');
    const btnClose = document.getElementById('btnCloseVault');

    if(btnOpen) {
        btnOpen.addEventListener('click', function() {
            resetForm();
            modal.style.display = 'flex';
        });
    }

    if(btnClose) {
        btnClose.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Hover effect for close button
        btnClose.addEventListener('mouseover', () => btnClose.style.color = 'var(--text-primary)');
        btnClose.addEventListener('mouseout', () => btnClose.style.color = 'var(--text-secondary)');
    }

    // Close on outside click
    window.addEventListener('click', function(e) {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });
});

// Helper Functions
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Add New Secret';
    document.getElementById('inp_id').value = '';
    document.getElementById('inp_title').value = '';
    document.getElementById('inp_user').value = '';
    document.getElementById('inp_val').value = '';
}

function editVaultSecret(data) {
    document.getElementById('modalTitle').innerText = 'Edit Secret';
    document.getElementById('inp_id').value = data.id;
    document.getElementById('inp_title').value = data.title;
    document.getElementById('inp_user').value = data.username;
    document.getElementById('inp_val').value = data.secret_value;
    document.getElementById('inp_cat').value = data.category;
    document.getElementById('vaultModal').style.display = 'flex';
}

function toggleViz(id) {
    let field = document.getElementById('val_' + id);
    let real = document.getElementById('real_' + id).value;
    let icon = document.getElementById('icon_' + id);
    if (field.innerText.includes('•••')) {
        field.innerText = real;
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        field.innerText = '••••••••••••';
        icon.className = 'fa-regular fa-eye';
    }
}

function copySecret(id) {
    let real = document.getElementById('real_' + id).value;
    navigator.clipboard.writeText(real).then(() => {
        let btn = document.querySelector(`#val_${id}`).nextElementSibling.querySelector('button:nth-child(2)');
        let originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check" style="color: #34C759;"></i>';
        setTimeout(() => { btn.innerHTML = originalHTML; }, 1500);
    });
}

function filterSecrets(q) {
    let term = q.toLowerCase();
    document.querySelectorAll('.secret-card').forEach(card => {
        card.style.display = card.dataset.title.includes(term) ? 'flex' : 'none';
    });
}
</script>

<?php include '_footer.php'; ?>