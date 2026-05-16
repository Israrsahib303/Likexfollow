<?php
// 1. Error Reporting On (Debug ke liye)
ini_set('display_errors', 0); // Production mein off rakhein
error_reporting(E_ALL);

include '_header.php';
requireAdmin();
require_once __DIR__ . '/../includes/smm_api.class.php';

// Fetch Settings for Rate
$usd_to_pkr = (float)($GLOBALS['settings']['currency_conversion_rate'] ?? 280.00);

// Fetch ALL providers
try {
    $all_providers = $db->query("SELECT * FROM smm_providers")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_providers = [];
    $db_error = $e->getMessage();
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
        --ios-orange: #FF9500;
        --ios-divider: #E5E5EA;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
        --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", sans-serif;
        background-color: var(--ios-bg);
        color: var(--ios-text);
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
    }

    /* Layout */
    .ios-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
        animation: fadeIn 0.6s ease-out;
    }

    /* Header Section */
    .header-group {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-title h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.5px;
        color: var(--ios-text);
    }

    .page-title p {
        margin: 6px 0 0 0;
        font-size: 15px;
        color: var(--ios-text-sec);
        font-weight: 400;
    }

    /* iOS Segmented Control */
    .segmented-control {
        background-color: #E3E3E8;
        padding: 3px;
        border-radius: 9px;
        display: inline-flex;
        position: relative;
        user-select: none;
    }

    .segment-btn {
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: #636366;
        cursor: pointer;
        border-radius: 7px;
        transition: all 0.2s ease;
        position: relative;
        z-index: 2;
    }

    .segment-btn:hover {
        color: #000;
    }

    .segment-btn.active {
        background-color: #FFFFFF;
        color: #000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Card Table */
    .ios-card {
        background: var(--ios-card);
        border-radius: 18px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.02);
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .ios-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
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
        border-bottom: 1px solid var(--ios-divider);
    }

    .ios-table td {
        padding: 18px 24px;
        border-bottom: 1px solid var(--ios-divider);
        vertical-align: middle;
        font-size: 15px;
        font-weight: 500;
    }

    .ios-table tr:last-child td {
        border-bottom: none;
    }

    .ios-table tr:hover td {
        background-color: #F9F9F9;
    }

    /* Elements */
    .provider-id {
        color: var(--ios-text-sec);
        font-family: 'SF Mono', monospace;
        font-size: 13px;
    }

    .provider-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .provider-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #F2F2F7;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    /* Status Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .badge-active { background: rgba(52, 199, 89, 0.15); color: var(--ios-green); }
    .badge-inactive { background: rgba(255, 59, 48, 0.15); color: var(--ios-red); }
    
    .api-status { font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    .status-ok { color: var(--ios-green); }
    .status-err { color: var(--ios-red); }
    .status-check { color: var(--ios-text-sec); }

    /* Balance */
    .balance-usd, .balance-pkr {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .balance-usd { color: var(--ios-text); font-weight: 600; font-size: 16px; font-variant-numeric: tabular-nums; }
    .balance-pkr { color: var(--ios-green); font-weight: 600; font-size: 16px; font-variant-numeric: tabular-nums; display: none; }

    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Message */
    .message-box {
        padding: 16px;
        margin-bottom: 20px;
        border-radius: 12px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .msg-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
</style>

<div class="ios-container">

    <div class="header-group">
        <div class="page-title">
            <h1>Balance Checker</h1>
            <p>Monitor your connected API funds.</p>
        </div>

        <div class="segmented-control">
            <button class="segment-btn active" onclick="switchCurrency('USD', this)">USD ($)</button>
            <button class="segment-btn" onclick="switchCurrency('PKR', this)">PKR (Rs)</button>
        </div>
    </div>

    <?php if (isset($db_error)): ?>
        <div class="message-box msg-error">
            <i class="fa-solid fa-triangle-exclamation"></i> Database Error: <?= htmlspecialchars($db_error) ?>
        </div>
    <?php endif; ?>

    <div class="ios-card">
        <div class="table-responsive">
            <table class="ios-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Provider Name</th>
                        <th width="120">Status</th>
                        <th>API Connection</th>
                        <th width="150" style="text-align:right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_providers)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:40px; color:var(--ios-text-sec);">
                                <i class="fa-solid fa-layer-group" style="font-size:24px; opacity:0.3; margin-bottom:10px;"></i><br>
                                No providers configured.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($all_providers as $p): 
                            $api_status_html = '<span class="api-status status-check"><i class="fa-solid fa-circle-notch fa-spin"></i> Checking...</span>';
                            $bal_usd = 0.00;
                            $bal_pkr = 0.00;
                            $row_class = '';

                            try {
                                if (empty($p['api_url']) || empty($p['api_key'])) {
                                    throw new Exception("Config Missing");
                                }

                                // API Call
                                $api = new SmmApi($p['api_url'], $p['api_key']);
                                $bal_data = $api->balance();
                                
                                if (isset($bal_data['balance'])) {
                                    $api_status_html = '<span class="api-status status-ok"><i class="fa-solid fa-check-circle"></i> Connected</span>';
                                    
                                    // Clean balance string (remove symbols if any)
                                    $raw_bal = preg_replace('/[^0-9.]/', '', $bal_data['balance']);
                                    $bal_usd = (float)$raw_bal;
                                    
                                    // Convert to PKR
                                    $bal_pkr = $bal_usd * $usd_to_pkr;
                                } else {
                                    throw new Exception("Invalid Response");
                                }

                            } catch (Exception $e) {
                                $api_status_html = '<span class="api-status status-err"><i class="fa-solid fa-circle-xmark"></i> ' . $e->getMessage() . '</span>';
                                $row_class = 'opacity: 0.7;';
                            }
                        ?>
                        <tr style="<?= $row_class ?>">
                            <td><span class="provider-id">#<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                            <td>
                                <div class="provider-name">
                                    <div class="provider-icon"><i class="fa-solid fa-server"></i></div>
                                    <strong><?= sanitize($p['name']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <?php if($p['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $api_status_html ?></td>
                            
                            <td style="text-align:right;">
                                <div class="balance-wrapper">
                                    <div class="balance-usd">
                                        $<?= number_format($bal_usd, 2) ?>
                                    </div>
                                    <div class="balance-pkr">
                                        Rs <?= number_format($bal_pkr, 2) ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="text-align:center; margin-top:20px; color:#86868B; font-size:13px;">
        Conversion Rate: 1 USD ≈ <?= number_format($usd_to_pkr, 2) ?> PKR
    </div>

</div>

<script>
function switchCurrency(curr, btn) {
    // Buttons Active State
    document.querySelectorAll('.segment-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Toggle Visibility with Animation
    const usdElements = document.querySelectorAll('.balance-usd');
    const pkrElements = document.querySelectorAll('.balance-pkr');

    if (curr === 'USD') {
        pkrElements.forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 200);
        });
        setTimeout(() => {
            usdElements.forEach(el => {
                el.style.display = 'block';
                setTimeout(() => el.style.opacity = '1', 50);
            });
        }, 200);
    } else {
        usdElements.forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 200);
        });
        setTimeout(() => {
            pkrElements.forEach(el => {
                el.style.display = 'block';
                setTimeout(() => el.style.opacity = '1', 50);
            });
        }, 200);
    }
}
</script>

<?php include '_footer.php'; ?>