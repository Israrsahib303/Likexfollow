<?php
// File: panel/seo_tools.php
// Purpose: All-in-One Free SEO Tools Collection with Integration Guides

require_once '_header.php'; 
?>

<style>
    :root { --primary: #4f46e5; --secondary: #7c3aed; }
    
    .seo-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        color: white; padding: 3rem 2rem; border-radius: 1.5rem;
        margin-bottom: 3rem; position: relative; overflow: hidden;
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.4);
    }
    .seo-header::before {
        content: ''; position: absolute; top: -50%; right: -20%; width: 600px; height: 600px;
        background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%);
        border-radius: 50%; animation: pulse 10s infinite;
    }
    
    .tool-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;
    }
    
    .tool-card {
        background: white; border-radius: 1.25rem; padding: 1.5rem;
        border: 1px solid #e2e8f0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex; flex-direction: column; position: relative; overflow: hidden;
    }
    .tool-card:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1); border-color: var(--primary); }
    
    .icon-box {
        width: 60px; height: 60px; border-radius: 1rem; display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; margin-bottom: 1.5rem; transition: transform 0.3s;
    }
    .tool-card:hover .icon-box { transform: scale(1.1) rotate(5deg); }
    
    .status-badge {
        position: absolute; top: 1.5rem; right: 1.5rem; padding: 0.25rem 0.75rem;
        border-radius: 99px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
    }
    
    .btn-action {
        margin-top: auto; padding: 0.75rem 1rem; border-radius: 0.75rem; font-weight: 600;
        text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        transition: 0.2s; cursor: pointer; border: none; width: 100%;
    }
    .btn-visit { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
    .btn-visit:hover { background: #f1f5f9; color: #0f172a; }
    
    .btn-guide { background: #eef2ff; color: var(--primary); border: 1px solid #c7d2fe; margin-top: 0.5rem; }
    .btn-guide:hover { background: var(--primary); color: white; border-color: var(--primary); }

    /* Modal */
    .guide-modal {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.7); z-index: 9999; backdrop-filter: blur(5px);
        align-items: center; justify-content: center; padding: 1rem;
    }
    .modal-content {
        background: white; width: 100%; max-width: 600px; border-radius: 1.5rem;
        padding: 2rem; position: relative; animation: slideUp 0.3s ease-out;
        max-height: 90vh; overflow-y: auto;
    }
    .code-block {
        background: #1e293b; color: #a5f3fc; padding: 1rem; border-radius: 0.75rem;
        font-family: monospace; font-size: 0.85rem; overflow-x: auto; margin: 1rem 0;
        border: 1px solid #334155;
    }
    
    @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="container-fluid p-4" style="max-width: 1400px;">
    
    <div class="seo-header">
        <div style="position: relative; z-index: 10;">
            <span class="badge bg-indigo-500 text-white mb-3 px-3 py-2 rounded-lg">🚀 SEO Headquarters</span>
            <h1 class="fw-bold display-5 mb-3">Professional SEO Toolkit</h1>
            <p class="lead opacity-75 mb-0" style="max-width: 600px;">
                Everything you need to rank <strong>LikexFollow</strong> on Google #1. 
                Free tools, easy integration guides, and advanced analytics.
            </p>
        </div>
    </div>

    <div class="tool-grid">
        
        <div class="tool-card">
            <span class="status-badge bg-green-100 text-green-700">Essential</span>
            <div class="icon-box bg-blue-100 text-blue-600"><i class="fab fa-google"></i></div>
            <h3 class="h5 fw-bold mb-2">Google Search Console</h3>
            <p class="text-muted small mb-4">Monitor ranking, performance, and indexing issues directly from Google.</p>
            <a href="https://search.google.com/search-console" target="_blank" class="btn-action btn-visit">Open Tool <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('gsc')" class="btn-action btn-guide">How to Integrate <i class="fas fa-plug"></i></button>
        </div>

        <div class="tool-card">
            <span class="status-badge bg-green-100 text-green-700">Essential</span>
            <div class="icon-box bg-orange-100 text-orange-600"><i class="fas fa-chart-line"></i></div>
            <h3 class="h5 fw-bold mb-2">Google Analytics 4</h3>
            <p class="text-muted small mb-4">Track user traffic, behavior, and sales conversions in real-time.</p>
            <a href="https://analytics.google.com/" target="_blank" class="btn-action btn-visit">Open Tool <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('ga4')" class="btn-action btn-guide">Integration Guide <i class="fas fa-plug"></i></button>
        </div>

        <div class="tool-card">
            <span class="status-badge bg-blue-100 text-blue-700">Performance</span>
            <div class="icon-box bg-teal-100 text-teal-600"><i class="fas fa-tachometer-alt"></i></div>
            <h3 class="h5 fw-bold mb-2">PageSpeed Insights</h3>
            <p class="text-muted small mb-4">Analyze site speed and Core Web Vitals. Fast sites rank higher.</p>
            <a href="https://pagespeed.web.dev/" target="_blank" class="btn-action btn-visit">Check Speed <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('speed')" class="btn-action btn-guide">Optimization Tips <i class="fas fa-lightbulb"></i></button>
        </div>

        <div class="tool-card">
            <span class="status-badge bg-purple-100 text-purple-700">Rich Snippets</span>
            <div class="icon-box bg-indigo-100 text-indigo-600"><i class="fas fa-code"></i></div>
            <h3 class="h5 fw-bold mb-2">Schema Markup Gen</h3>
            <p class="text-muted small mb-4">Create JSON-LD for "Product", "FAQ", and "Review" snippets in Google.</p>
            <a href="https://technicalseo.com/tools/schema-markup-generator/" target="_blank" class="btn-action btn-visit">Generate Code <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('schema')" class="btn-action btn-guide">Where to Paste? <i class="fas fa-paste"></i></button>
        </div>

        <div class="tool-card">
            <span class="status-badge bg-yellow-100 text-yellow-700">Research</span>
            <div class="icon-box bg-red-100 text-red-600"><i class="fas fa-fire"></i></div>
            <h3 class="h5 fw-bold mb-2">Google Trends</h3>
            <p class="text-muted small mb-4">Find trending topics for your AI Blog to get instant viral traffic.</p>
            <a href="https://trends.google.com/" target="_blank" class="btn-action btn-visit">Find Trends <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('trends')" class="btn-action btn-guide">Usage Guide <i class="fas fa-book"></i></button>
        </div>

        <div class="tool-card">
            <span class="status-badge bg-gray-100 text-gray-700">Technical</span>
            <div class="icon-box bg-slate-100 text-slate-600"><i class="fas fa-sitemap"></i></div>
            <h3 class="h5 fw-bold mb-2">Sitemap Validator</h3>
            <p class="text-muted small mb-4">Ensure Google can read your dynamic sitemap correctly.</p>
            <a href="https://www.xml-sitemaps.com/validate-xml-sitemap.html" target="_blank" class="btn-action btn-visit">Validate Now <i class="fas fa-external-link-alt small"></i></a>
            <button onclick="showGuide('sitemap')" class="btn-action btn-guide">Check URL <i class="fas fa-link"></i></button>
        </div>

    </div>
</div>

<div id="modal-gsc" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">Google Search Console Integration</h3>
        <p>Follow these steps to verify your site ownership:</p>
        <ol class="list-group list-group-numbered mb-3">
            <li class="list-group-item">Go to Search Console and add property `likexfollow.com`.</li>
            <li class="list-group-item">Choose <strong>HTML Tag</strong> verification method.</li>
            <li class="list-group-item">Copy the meta tag code (e.g., `&lt;meta name="google-site-verification"...&gt;`).</li>
            <li class="list-group-item">Paste it into the <strong>Google Config</strong> page in your Admin Panel.</li>
        </ol>
        <div class="alert alert-info small"><i class="fas fa-info-circle"></i> We have already created a settings page for this! Go to <strong>System > Google Config</strong>.</div>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<div id="modal-ga4" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">Google Analytics 4 Setup</h3>
        <p>Track every visitor on your site:</p>
        <ol class="list-group list-group-numbered mb-3">
            <li class="list-group-item">Create a property in Google Analytics.</li>
            <li class="list-group-item">Go to <strong>Data Streams > Web</strong>.</li>
            <li class="list-group-item">Copy the <strong>Measurement ID</strong> (Starts with `G-XXXXXX`).</li>
            <li class="list-group-item">Paste this ID in <strong>Admin > System > Google Config</strong>.</li>
        </ol>
        <p class="small text-muted">Your system will automatically inject the tracking script into `user/_header.php`.</p>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<div id="modal-schema" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">Schema Integration</h3>
        <p>We have already automated this for Blogs!</p>
        <div class="code-block">
            // Already added in blog_view.php<br>
            &lt;script type="application/ld+json"&gt;<br>
            ...JSON Data...<br>
            &lt;/script&gt;
        </div>
        <p><strong>For Services:</strong> You can add Product Schema. Go to `service.php` and ask your developer to inject product schema dynamically based on price and reviews.</p>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<div id="modal-sitemap" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">Sitemap Info</h3>
        <p>Your sitemap is dynamic and updates automatically.</p>
        <div class="p-3 bg-light border rounded mb-3">
            <strong>Your Sitemap URL:</strong><br>
            <a href="../sitemap.xml" target="_blank">https://likexfollow.com/sitemap.xml</a>
        </div>
        <p>Submit this URL to Google Search Console under the "Sitemaps" section.</p>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<div id="modal-trends" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">How to use Google Trends?</h3>
        <p>Use this to find topics for your AI Blog Poster.</p>
        <ul class="list-unstyled">
            <li class="mb-2">1. Search for "TikTok" or "SMM Panel".</li>
            <li class="mb-2">2. Look for "Rising" queries.</li>
            <li class="mb-2">3. Example: If "How to verify tiktok" is rising, create a blog post on it manually or let AI do it.</li>
        </ul>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<div id="modal-speed" class="guide-modal" onclick="closeModal(this)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <h3 class="h4 fw-bold mb-3">Speed Optimization Tips</h3>
        <ul class="list-group mb-3">
            <li class="list-group-item"><strong>Images:</strong> Convert all PNG/JPG to WebP format.</li>
            <li class="list-group-item"><strong>Cache:</strong> Use Cloudflare (Free) for CDN caching.</li>
            <li class="list-group-item"><strong>Database:</strong> Your admin panel has a "Cron Job" for database cleanup. Run it weekly.</li>
        </ul>
        <button class="btn btn-dark w-100 mt-2" onclick="closeModal(this.parentElement.parentElement)">Close</button>
    </div>
</div>

<script>
    function showGuide(id) {
        document.getElementById('modal-' + id).style.display = 'flex';
    }
    function closeModal(el) {
        el.style.display = 'none';
    }
</script>

<?php include '_footer.php'; ?>