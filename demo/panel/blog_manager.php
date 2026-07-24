<?php
// File: panel/blog_manager.php
include '_header.php';
require_once '../includes/AiEngine.php';

// --- HANDLE GENERATION ---
$alert = '';
if (isset($_POST['generate_blog'])) {
    $topic = trim($_POST['topic']);
    if (!empty($topic)) {
        $ai = new AiEngine($db);
        $content = $ai->generateContent($topic);
        
        if (strpos($content, 'Error') === false) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $topic)));
            $slug .= '-' . rand(100, 999);
            
            // AI generated content ko thora clean karte hain
            // Pehla paragraph nikal kar meta desc bana lete hain
            $meta_desc = substr(strip_tags($content), 0, 150) . '...';

            $stmt = $db->prepare("INSERT INTO blogs (title, slug, content, meta_desc, status) VALUES (?, ?, ?, ?, 'published')");
            if ($stmt->execute([$topic, $slug, $content, $meta_desc])) {
                $alert = '<div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 shadow-sm border border-green-200">✅ Article Generated & Published Successfully! <a href="../blog_view.php?slug='.$slug.'" target="_blank" class="underline font-bold">View Now</a></div>';
            } else {
                $alert = '<div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6">❌ Database Error.</div>';
            }
        } else {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6">❌ '.$content.'</div>';
        }
    }
}

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM blogs WHERE id=$id");
    echo "<script>window.location='blog_manager.php';</script>";
}
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    
    <?= $alert ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-lg border border-indigo-100 p-6 sticky top-24">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-3">✍️</div>
                    <h2 class="text-xl font-bold text-slate-800">AI Article Writer</h2>
                    <p class="text-sm text-slate-500">Generate SEO-optimized blog posts in seconds.</p>
                </div>

                <form method="POST" onsubmit="document.getElementById('loadingBtn').style.display='flex'; document.getElementById('genBtn').style.display='none';">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Topic / Keyword</label>
                    <input type="text" name="topic" placeholder="e.g. How to get TikTok followers" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 mb-4 outline-none" required>
                    
                    <button type="submit" name="generate_blog" id="genBtn" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95">
                        ✨ Generate Article
                    </button>
                    
                    <button type="button" id="loadingBtn" class="w-full py-3 bg-indigo-400 text-white font-bold rounded-xl cursor-not-allowed hidden items-center justify-center gap-2" disabled>
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Writing...
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-bold text-slate-700 text-lg">Published Articles</h3>
                    <span class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-500">
                        Total: <?= $db->query("SELECT COUNT(*) FROM blogs")->fetchColumn() ?>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs uppercase text-slate-400 border-b border-slate-100">
                                <th class="p-4 font-bold">Title</th>
                                <th class="p-4 font-bold">Views</th>
                                <th class="p-4 font-bold">Date</th>
                                <th class="p-4 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php
                            $stmt = $db->query("SELECT * FROM blogs ORDER BY id DESC LIMIT 50");
                            while($row = $stmt->fetch()):
                            ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                <td class="p-4 font-bold text-slate-700">
                                    <?= htmlspecialchars($row['title']) ?>
                                    <div class="text-xs text-slate-400 font-normal mt-1 truncate w-48"><?= $row['slug'] ?></div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-50 text-blue-600 text-xs font-bold">
                                        👁️ <?= number_format($row['views']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500">
                                    <?= date('d M', strtotime($row['created_at'])) ?>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="../blog_view.php?slug=<?= $row['slug'] ?>" target="_blank" class="inline-block p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View">
                                        🔗
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" class="inline-block p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '_footer.php'; ?>