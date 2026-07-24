<?php include '_header.php'; ?>

<style>
    .sec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
    .sec-card { background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .sec-info h3 { margin: 0; font-size: 1.1rem; color: #1e293b; font-weight: 700; }
    .sec-info p { margin: 5px 0 0; color: #64748b; font-size: 0.9rem; }
    
    .switch { position: relative; display: inline-block; width: 50px; height: 28px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #4f46e5; }
    input:checked + .slider:before { transform: translateX(22px); }
</style>

<div class="header-flex" style="margin-bottom:30px;">
    <h1>🛡️ Security Controls</h1>
    <p>Manage Iron-Core protection layers.</p>
</div>

<div class="sec-grid">
    <div class="sec-card">
        <div class="sec-info">
            <h3>🧬 Session DNA Lock</h3>
            <p>Lock admin session to IP & Browser. Logout on change.</p>
        </div>
        <label class="switch">
            <input type="checkbox" id="lockSwitch" <?php echo ($GLOBALS['settings']['security_session_lock']=='1')?'checked':''; ?> onchange="updateSec('security_session_lock', this.checked)">
            <span class="slider"></span>
        </label>
    </div>

    <div class="sec-card">
        <div class="sec-info">
            <h3>🚨 Login Alerts</h3>
            <p>Send email notification on every admin login.</p>
        </div>
        <label class="switch">
            <input type="checkbox" id="alertSwitch" <?php echo ($GLOBALS['settings']['security_login_alert']=='1')?'checked':''; ?> onchange="updateSec('security_login_alert', this.checked)">
            <span class="slider"></span>
        </label>
    </div>
</div>

<script>
function updateSec(key, status) {
    const val = status ? '1' : '0';
    // Ensure you create 'update_settings_ajax.php' or handle this logic
    fetch('update_settings_ajax.php', { 
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'key='+key+'&value='+val
    }).then(res => { console.log('Saved'); });
}
</script>

<?php include '_footer.php'; ?>