<?php
// File: panel/seo_logs.php
include '_header.php';

// Clear Logs Action
if(isset($_GET['clear'])) {
    $db->query("TRUNCATE TABLE seo_logs");
    echo "<script>window.location='seo_logs.php';</script>";
}
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">🕵️ SEO Watchdog</h1>
            <p class="text-slate-500">Live logs of AI auto-generation tasks.</p>
        </div>
        <a href="?clear=1" class="px-4 py-2 bg-red-100 text-red-600 rounded-lg font-bold text-sm hover:bg-red-200 transition">
            Clear Logs
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                        <th class="p-4 font-extrabold">Time</th>
                        <th class="p-4 font-extrabold">Action Type</th>
                        <th class="p-4 font-extrabold">Details</th>
                        <th class="p-4 font-extrabold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    <?php
                    $stmt = $db->query("SELECT * FROM seo_logs ORDER BY id DESC LIMIT 100");
                    while($row = $stmt->fetch()):
                        $statusClass = (strpos($row['details'], 'Error') !== false) ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600';
                        $statusText = (strpos($row['details'], 'Error') !== false) ? 'Failed' : 'Success';
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 text-slate-500 whitespace-nowrap">
                            <?= date('d M, H:i', strtotime($row['created_at'])) ?>
                        </td>
                        <td class="p-4">
                            <span class="font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded">
                                <?= htmlspecialchars($row['action']) ?>
                            </span>
                        </td>
                        <td class="p-4 text-slate-600 max-w-md truncate" title="<?= htmlspecialchars($row['details']) ?>">
                            <?= htmlspecialchars($row['details']) ?>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if($stmt->rowCount() == 0): ?>
                        <tr><td colspan="4" class="p-8 text-center text-slate-400">No logs found. Run the cron jobs to see magic! ✨</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '_footer.php'; ?>