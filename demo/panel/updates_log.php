<?php
include '_header.php';
requireAdmin();

if(isset($_POST['clear'])) {
    $db->query("TRUNCATE TABLE service_updates");
    echo "<script>window.location.href='updates_log.php';</script>";
}

$logs = $db->query("SELECT * FROM service_updates ORDER BY created_at DESC")->fetchAll();
?>

<style>
    /* Reset & Apple System Typography */
    :root {
        --bg-color: #F5F5F7;
        --card-bg: #FFFFFF;
        --text-main: #1D1D1F;
        --text-muted: #86868B;
        --border-color: #E5E5EA;
        --apple-red: #FF3B30;
        --apple-red-bg: #FFE5E5;
        --apple-green: #34C759;
        --apple-green-bg: #E5F9E0;
        --apple-blue: #007AFF;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background-color: var(--bg-color);
        color: var(--text-main);
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        overflow-x: hidden;
    }

    /* Safe Container */
    .apple-container {
        width: min(100%, 1000px);
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Header Section */
    .header-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .apple-title {
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.015em;
        margin: 0;
        color: var(--text-main);
    }

    /* iOS Style Button */
    .btn-apple-destructive {
        appearance: none;
        background-color: var(--card-bg);
        color: var(--apple-red);
        border: 1px solid var(--border-color);
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        transition: all 0.2s cubic-bezier(0.25, 0.1, 0.25, 1);
    }

    .btn-apple-destructive:hover {
        background-color: var(--apple-red-bg);
        border-color: var(--apple-red);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 59, 48, 0.15);
    }

    .btn-apple-destructive:active {
        transform: scale(0.97);
    }

    .btn-apple-destructive:focus-visible {
        outline: 2px solid var(--apple-red);
        outline-offset: 2px;
    }

    /* Card & Table */
    .apple-card {
        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border-color);
        overflow: hidden;
        width: 100%;
    }

    .admin-table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .admin-table th {
        background: var(--card-bg);
        padding: 16px 20px;
        color: var(--text-muted);
        font-weight: 500;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .admin-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        white-space: nowrap;
        transition: background-color 0.15s ease;
    }

    .admin-table tbody tr {
        transition: all 0.2s ease;
    }

    .admin-table tbody tr:hover td {
        background-color: #F5F5F7;
    }

    .admin-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .apple-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
        letter-spacing: -0.01em;
    }

    .badge-removed {
        background-color: var(--apple-red-bg);
        color: var(--apple-red);
    }

    .badge-added {
        background-color: var(--apple-green-bg);
        color: var(--apple-green);
    }

    .apple-time {
        color: var(--text-muted);
        font-variant-numeric: tabular-nums;
    }

    /* Reduced Motion Accessibility */
    @media (prefers-reduced-motion: reduce) {
        *, ::before, ::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
        .btn-apple-destructive:hover {
            transform: none;
        }
        .btn-apple-destructive:active {
            transform: none;
        }
    }

    /* Mobile Adjustments */
    @media (max-width: 600px) {
        .header-group {
            flex-direction: column;
            align-items: flex-start;
        }
        .btn-apple-destructive {
            width: 100%;
            justify-content: center;
        }
        .apple-container {
            padding: 24px 16px;
        }
    }
</style>

<div class="apple-container">
    <div class="header-group">
        <h1 class="apple-title">📢 Service Updates Log</h1>
        <form method="POST" style="margin: 0; width: 100%; max-width: max-content;">
            <button name="clear" class="btn-apple-destructive" onclick="return confirm('Clear all logs? This action cannot be undone.')" aria-label="Clear All History">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                Clear All History
            </button>
        </form>
    </div>

    <div class="apple-card">
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px;">No service updates found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach($logs as $l): ?>
                    <tr>
                        <td>
                            <span class="apple-badge <?= $l['type'] == 'removed' ? 'badge-removed' : 'badge-added' ?>">
                                <?= $l['type'] ?>
                            </span>
                        </td>
                        <td style="font-weight: 500;"><?= sanitize($l['service_name']) ?></td>
                        <td><?= sanitize($l['category_name']) ?></td>
                        <td class="apple-time"><?= $l['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '_footer.php'; ?>