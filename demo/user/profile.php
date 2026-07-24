<?php
include '_header.php';

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// User Data Fetch (Fetching all fields to support new features)
$stmt_user = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Safe variables in case columns don't exist in DB yet
$current_name = $user['name'] ?? '';
$current_username = $user['username'] ?? '';
$current_timezone = $user['timezone'] ?? 'UTC';
$two_step_auth = $user['two_step_auth'] ?? 0;

// --- 1. Update Personal Info Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security Token Mismatch. Please refresh page.';
    } else {
        $name = htmlspecialchars($_POST['name']);
        $username = htmlspecialchars($_POST['username']);
        $timezone = htmlspecialchars($_POST['timezone']);
        
        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, username = ?, timezone = ? WHERE id = ?");
            $stmt->execute([$name, $username, $timezone, $user_id]);
            
            // Update local variables to show new data immediately
            $current_name = $name;
            $current_username = $username;
            $current_timezone = $timezone;
            $success = 'Profile information updated successfully!';
        } catch (PDOException $e) {
            $error = 'Error updating profile. Ensure columns (name, username, timezone) exist in DB.';
        }
    }
}

// --- 2. Change Password Logic (SECURED) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    // CSRF CHECK
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security Token Mismatch. Please refresh page.';
    } else {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_pass = $stmt->fetch();

        if ($new_pass !== $confirm_pass) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (password_verify($current_pass, $user_pass['password_hash'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$new_hash, $user_id]);
            $success = 'Password updated successfully!';
        } else {
            $error = 'Incorrect current password.';
        }
    }
}

// --- 3. Generate API Key Logic (SECURED) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_api_key'])) {
    // CSRF CHECK
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security Token Mismatch. Please refresh page.';
    } else {
        try {
            $new_api_key = bin2hex(random_bytes(16)); 
            $db->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$new_api_key, $user_id]);
            // Update local variable to show new key immediately
            $user['api_key'] = $new_api_key;
            $success = 'New API Key generated!';
        } catch (PDOException $e) {
            $error = 'Error generating key. Please try again.';
        }
    }
}

// --- 4. Two-Step Authentication (2FA) Logic ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_2fa'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security Token Mismatch. Please refresh page.';
    } else {
        $new_2fa_status = isset($_POST['two_step_auth']) ? 1 : 0;
        try {
            $db->prepare("UPDATE users SET two_step_auth = ? WHERE id = ?")->execute([$new_2fa_status, $user_id]);
            $two_step_auth = $new_2fa_status;
            $success = $new_2fa_status ? 'Two-Step Authentication Enabled!' : 'Two-Step Authentication Disabled!';
        } catch (PDOException $e) {
            $error = 'Error updating 2FA settings. Ensure column (two_step_auth) exists in DB.';
        }
    }
}
?>

<style>
/* --- ☀️ LIGHT THEME & PREMIUM UI --- */
:root {
    --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    --secondary-gradient: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    --bg-page: #f3f4f6;
    --card-bg: #ffffff;
    --text-main: #111827;
    --text-muted: #6b7280;
    --border-light: #e5e7eb;
    --shadow-soft: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
    --shadow-hover: 0 20px 40px -5px rgba(79, 70, 229, 0.15);
}

.profile-page {
    min-height: 85vh;
    background: var(--bg-page);
    color: var(--text-main);
    font-family: 'Plus Jakarta Sans', sans-serif;
    position: relative;
    padding: 2rem 0;
    overflow: hidden;
}

/* Background Blobs (Light Pastels) */
.bg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    z-index: 0;
    opacity: 0.6;
    animation: float 10s infinite alternate;
}
.blob-1 { top: -10%; left: -5%; width: 400px; height: 400px; background: #e0e7ff; }
.blob-2 { bottom: -10%; right: -5%; width: 350px; height: 350px; background: #fae8ff; animation-delay: -5s; }

@keyframes float {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(20px) scale(1.05); }
}

.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
    position: relative;
    z-index: 2;
}

/* Header Section */
.profile-header {
    background: var(--card-bg);
    border-radius: 24px;
    padding: 2rem;
    box-shadow: var(--shadow-soft);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-light);
    transition: transform 0.3s ease;
}
.profile-header:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }

.avatar-box {
    width: 75px; height: 75px;
    background: var(--primary-gradient);
    border-radius: 22px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 2rem;
    box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
}

.user-info h1 { font-size: 1.85rem; font-weight: 800; margin: 0; color: var(--text-main); letter-spacing: -0.5px; }
.user-info p { color: var(--text-muted); margin: 5px 0 0 0; font-weight: 500; font-size: 1rem; }

/* Grid Layout */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 2rem;
}

/* Cards */
.setting-card {
    background: var(--card-bg);
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: var(--shadow-soft);
    border: 1px solid var(--border-light);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.setting-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
    border-color: #c7d2fe;
}

.card-top { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-light); }
.card-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    color: white;
    font-size: 1.2rem;
}
.icon-profile { background: var(--info-gradient); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.25); }
.icon-security { background: var(--primary-gradient); box-shadow: 0 8px 16px rgba(79, 70, 229, 0.25); }
.icon-api { background: var(--secondary-gradient); box-shadow: 0 8px 16px rgba(236, 72, 153, 0.25); }
.icon-2fa { background: var(--success-gradient); box-shadow: 0 8px 16px rgba(16, 185, 129, 0.25); }

.card-title h3 { font-size: 1.25rem; font-weight: 700; margin: 0; }
.card-title span { font-size: 0.85rem; color: var(--text-muted); }

/* Inputs */
.form-group { margin-bottom: 1.5rem; }
.form-label { display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem; font-size: 0.9rem; }
.form-input, .form-select {
    width: 100%; padding: 14px 16px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem; color: var(--text-main);
    transition: all 0.2s;
    outline: none;
    font-family: inherit;
}
.form-input:focus, .form-select:focus {
    background: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

/* Copy Group */
.copy-group { position: relative; display: flex; }
.copy-group input { padding-right: 50px; background: #f3f4f6; color: #4b5563; font-family: monospace; font-weight: 600; }
.btn-copy {
    position: absolute; right: 8px; top: 8px;
    width: 36px; height: 36px;
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 8px; cursor: pointer;
    color: #6b7280; display: flex; align-items: center; justify-content: center;
    transition: 0.2s;
}
.btn-copy:hover { background: #f3f4f6; color: #4f46e5; border-color: #d1d5db; }

/* Custom Toggle Switch for 2FA */
.toggle-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f9fafb;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
}
.toggle-text h4 { margin: 0; font-size: 1rem; color: #111827; }
.toggle-text p { margin: 4px 0 0 0; font-size: 0.85rem; color: #6b7280; }

.switch { position: relative; display: inline-block; width: 54px; height: 30px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
input:checked + .slider { background-color: #10b981; }
input:checked + .slider:before { transform: translateX(24px); }

/* Buttons */
.btn-save {
    width: 100%; padding: 14px;
    background: var(--primary-gradient);
    color: white; border: none; border-radius: 12px;
    font-weight: 700; font-size: 1rem;
    cursor: pointer; transition: 0.3s;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    margin-top: auto;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4); }

.btn-danger {
    background: #fff; color: #ef4444; border: 2px solid #fee2e2;
    box-shadow: none;
}
.btn-danger:hover {
    background: #fef2f2; border-color: #fca5a5; color: #dc2626;
    transform: translateY(-2px);
}
.btn-outline {
    background: #fff; color: #10b981; border: 2px solid #d1fae5;
    box-shadow: none;
}
.btn-outline:hover { background: #ecfdf5; border-color: #6ee7b7; transform: translateY(-2px); }

/* Alerts */
.alert { padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: slideDown 0.4s ease; }
.alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .settings-grid { grid-template-columns: 1fr; }
    .profile-header { flex-direction: column; text-align: center; }
}
</style>

<div class="profile-page">
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="profile-container">
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="avatar-box">
                <i class="fa-solid fa-user-astronaut"></i>
            </div>
            <div class="user-info">
                <h1>Hi, <?php echo $current_name ?: 'User'; ?>!</h1>
                <p>Manage your account settings and preferences for <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
            </div>
        </div>

        <div class="settings-grid">
            
            <div class="setting-card">
                <div class="card-top">
                    <div class="card-icon icon-profile">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <div class="card-title">
                        <h3>Personal Info</h3>
                        <span>Update your public details</span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($current_name); ?>" placeholder="e.g. John Doe">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" value="<?php echo htmlspecialchars($current_username); ?>" placeholder="johndoe123">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-select">
                            <option value="UTC" <?php echo $current_timezone == 'UTC' ? 'selected' : ''; ?>>UTC (Standard)</option>
                            <option value="Asia/Karachi" <?php echo $current_timezone == 'Asia/Karachi' ? 'selected' : ''; ?>>Asia/Karachi (PKT)</option>
                            <option value="Asia/Kolkata" <?php echo $current_timezone == 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                            <option value="America/New_York" <?php echo $current_timezone == 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                            </select>
                    </div>
                    
                    <button type="submit" class="btn-save">Save Profile Details</button>
                </form>
            </div>

            <div class="setting-card">
                <div class="card-top">
                    <div class="card-icon icon-security">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="card-title">
                        <h3>Security</h3>
                        <span>Update your login password</span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" placeholder="Enter current password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" placeholder="Repeat new password" required>
                    </div>
                    
                    <button type="submit" class="btn-save" style="background: var(--text-main); box-shadow: 0 4px 12px rgba(17, 24, 39, 0.2);">Update Password</button>
                </form>
            </div>

            <div class="setting-card">
                <div class="card-top">
                    <div class="card-icon icon-2fa">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div class="card-title">
                        <h3>Two-Step Authentication</h3>
                        <span>Add an extra layer of security</span>
                    </div>
                </div>

                <form method="POST" id="form-2fa" style="display: flex; flex-direction: column; height: 100%;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="update_2fa" value="1">
                    
                    <div class="toggle-wrapper">
                        <div class="toggle-text">
                            <h4>Enable 2FA Protection</h4>
                            <p>Require an email code when logging in.</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="two_step_auth" onchange="document.getElementById('form-2fa').submit();" <?php echo $two_step_auth ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="text-align: center; margin-top: 10px;">
                        <img src="https://cdn-icons-png.flaticon.com/512/6314/6314985.png" alt="2FA Shield" style="width: 120px; opacity: 0.8; margin-bottom: 20px;">
                        <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                            When enabled, we will send a secure 6-digit OTP code to your registered email address every time you attempt to log in from a new device.
                        </p>
                    </div>
                    
                    <button type="button" class="btn-save btn-outline" style="cursor: default; background: transparent; border-color: transparent;">
                        <?php echo $two_step_auth ? '<i class="fa-solid fa-lock"></i> Account is Protected' : ''; ?>
                    </button>
                </form>
            </div>

            <div class="setting-card">
                <div class="card-top">
                    <div class="card-icon icon-api">
                        <i class="fa-solid fa-code"></i>
                    </div>
                    <div class="card-title">
                        <h3>Developer API</h3>
                        <span>Connect your apps via API</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Your API Key</label>
                    <div class="copy-group">
                        <input type="text" id="apiKey" class="form-input" value="<?php echo $user['api_key'] ? $user['api_key'] : 'Not Generated'; ?>" readonly>
                        <button type="button" class="btn-copy" onclick="copyToClipboard('apiKey')" title="Copy Key">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">API Endpoint URL</label>
                    <div class="copy-group">
                        <input type="text" id="apiUrl" class="form-input" value="<?php echo SITE_URL; ?>/api_v2.php" readonly>
                        <button type="button" class="btn-copy" onclick="copyToClipboard('apiUrl')" title="Copy URL">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirm('Generating a new key will stop the old one from working. Are you sure?');">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="generate_api_key" value="1">
                    
                    <button type="submit" class="btn-save btn-danger">
                        <i class="fa-solid fa-arrows-rotate"></i> 
                        <?php echo $user['api_key'] ? 'Regenerate Key' : 'Generate New Key'; ?>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
function copyToClipboard(id) {
    var copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999); 
    document.execCommand("copy");
    
    // Change icon temporarily
    let btn = copyText.nextElementSibling;
    let originalIcon = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check" style="color:#10b981"></i>';
    setTimeout(() => { btn.innerHTML = originalIcon; }, 1500);
}
</script>

<?php include '_footer.php'; ?>
