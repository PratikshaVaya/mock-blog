<?php
/**
 * Mock Blogging Website with 200 Dynamic Articles for Search Console Testing
 * Works dynamically on any domain (localhost or production).
 * Contains a built-in API Push console to directly index data.
 */

// Resolve dynamic base URL based on how the script is accessed
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8081';
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$currentBaseUrl = rtrim("$scheme://$host$scriptDir", '/\\');

// 1. Generate 200 mock articles deterministically based on ID
function get_mock_article(int $id, string $baseUrl): ?array {
    if ($id < 1 || $id > 200) {
        return null;
    }

    $subjects = [
        "AI Integration", "Responsive Design", "Continuous Integration", "Database Optimization", 
        "Cybersecurity", "Cloud Computing", "SAML SSO Protocols", "Web Crawlers", 
        "JavaScript Frameworks", "API Token Management", "CSS Glassmorphism", "SEO Strategies",
        "OpenLiteSpeed Servers", "CyberPanel Hosting", "Version Control", "Docker Containers"
    ];
    $adjectives = [
        "Modern", "Advanced", "Scalable", "Secure", "Dynamic", "High-Performance", 
        "Optimized", "Automated", "Enterprise-Grade", "Resilient", "Efficient"
    ];
    $verbs = [
        "Revolutionizing", "Enhancing", "Securing", "Scaling", "Refactoring", 
        "Automating", "Auditing", "Unifying", "Debugging", "Analyzing"
    ];
    
    $subject = $subjects[$id % count($subjects)];
    $adj = $adjectives[($id * 3) % count($adjectives)];
    $verb = $verbs[($id * 7) % count($verbs)];
    
    $title = "$adj Approach: $verb $subject in 2026 (Article #$id)";
    $category = ["Technology", "Development", "Security", "Infrastructure", "Operations"][($id * 2) % 5];
    
    $tags = [
        strtolower(str_replace(' ', '-', $subject)),
        strtolower($category),
        "tutorial"
    ];

    $desc = "Discover how to leverage $subject using a $adj architecture to achieve optimal results. This article covers $verb strategies, security auditing, and step-by-step setup guides.";
    
    $content = "<h2>Introduction to $subject</h2><p>Implementing a $adj approach to $subject has transitioned from a best practice to an absolute necessity. Organizations are actively $verb legacy workflows to maintain security compliance.</p>";

    return [
        'external_id' => "mock-article-$id",
        'record_type' => 'article',
        'title' => $title,
        'summary' => $desc,
        'body_text' => $content,
        'url' => "$baseUrl/index.php?id=$id",
        'category' => $category,
        'tags' => implode(',', $tags),
        'visibility' => 'public'
    ];
}

// Support JSON extraction of all articles for JavaScript push client
if (isset($_GET['api_export'])) {
    header('Content-Type: application/json');
    $export = [];
    for ($i = 1; $i <= 200; $i++) {
        $export[] = get_mock_article($i, $currentBaseUrl);
    }
    echo json_encode($export);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Handle Sitemap Request
if (isset($_GET['sitemap'])) {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    echo '  <url><loc>' . $currentBaseUrl . '/index.php</loc></url>' . "\n";
    for ($i = 1; $i <= 200; $i++) {
        echo '  <url><loc>' . $currentBaseUrl . '/index.php?id=' . $i . '</loc></url>' . "\n";
    }
    echo '</urlset>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? "Article #$id" : "Mock Blogging Portal" ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #cbd5e1; line-height: 1.6; margin: 0; padding: 24px; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; padding: 32px; border-radius: 12px; border: 1px solid #334155; }
        h1, h2, h3 { color: #f1f5f9; }
        a { color: #38bdf8; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .meta { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }
        .tag { background: #334155; color: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 11px; margin-right: 6px; font-weight: 600; }
        .post-list-item { border-bottom: 1px solid #334155; padding: 16px 0; }
        .post-list-item:last-child { border-bottom: none; }
        .back-link { display: inline-block; margin-bottom: 20px; font-weight: 600; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #64748b; }
        
        /* Push API Console Styles */
        .sync-card { background: #111827; border: 1px solid #10b981; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .input-group { margin-bottom: 12px; }
        .input-group label { display: block; font-size: 12px; font-weight: 600; color: #10b981; margin-bottom: 4px; }
        .input-group input { width: 100%; box-sizing: border-box; background: #1f2937; border: 1px solid #374151; color: #fff; padding: 8px 12px; border-radius: 4px; }
        .sync-btn { background: #10b981; color: #111827; border: none; padding: 10px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; display: inline-block; }
        .sync-btn:hover { background: #059669; }
        .progress-bar-container { background: #374151; height: 10px; border-radius: 5px; margin-top: 15px; display: none; overflow: hidden; }
        .progress-bar { background: #10b981; width: 0%; height: 100%; transition: width 0.1s ease; }
        .log-box { background: #000; border: 1px solid #1f2937; height: 150px; overflow-y: auto; font-family: monospace; font-size: 12px; padding: 10px; margin-top: 15px; border-radius: 4px; display: none; }
        .log-success { color: #10b981; }
        .log-error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($id): 
            $article = get_mock_article($id, $currentBaseUrl);
            if (!$article):
                echo "<h1>404 Post Not Found</h1><p><a href='index.php'>Back to Home</a></p>";
            else:
        ?>
            <a href="index.php" class="back-link">← Back to Blog Home</a>
            <h1><?= esc($article['title']) ?></h1>
            <div class="meta">
                Published in <strong><?= esc($article['category']) ?></strong>
                <div style="margin-top: 8px;">
                    <?php foreach (explode(',', $article['tags']) as $t): ?>
                        <span class="tag">#<?= esc($t) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px solid #334155; margin: 24px 0;">
            <div class="post-content">
                <?= $article['body_text'] ?>
            </div>
        <?php endif; else: ?>
            <h1>Mock Blogging Portal (200 Articles)</h1>
            <p>This is a mock blogging site containing 200 dynamically generated articles, complete with a <a href="index.php?sitemap=1">XML Sitemap</a> for crawling.</p>
            
            <!-- Index Synchronization Card -->
            <div class="sync-card">
                <h3>🚀 Push API Indexing Console</h3>
                <p style="font-size: 13px; color:#9ca3af; margin-top:-8px;">Directly push these 200 articles into your Search Console database using a generated API Token.</p>
                
                <div class="input-group">
                    <label>Search Console Endpoint URL</label>
                    <input type="text" id="apiUrl" value="https://search.test.soi.co.in/api/index">
                </div>
                <div class="input-group">
                    <label>API Key / Token</label>
                    <input type="password" id="apiToken" placeholder="Paste your generated token (soi_tok_...) or legacy API key">
                </div>
                
                <button type="button" class="sync-btn" id="startSyncBtn">Push 200 Articles via API</button>
                
                <div class="progress-bar-container" id="progContainer">
                    <div class="progress-bar" id="progBar"></div>
                </div>
                
                <div id="syncStatus" style="font-size:13px; margin-top:8px; font-weight:600;"></div>
                <div class="log-box" id="logBox"></div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #334155; margin: 24px 0;">
            
            <h2>Latest Articles</h2>
            <div class="post-list">
                <?php 
                for ($i = 1; $i <= 200; $i++): 
                    $post = get_mock_article($i, $currentBaseUrl);
                ?>
                    <div class="post-list-item">
                        <h3><a href="index.php?id=<?= $i ?>"><?= esc($post['title']) ?></a></h3>
                        <p style="margin: 8px 0; color: #94a3b8;"><?= esc($post['summary']) ?></p>
                        <span class="tag"><?= esc($post['category']) ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            Mock Blogging Portal &copy; 2026. Built for Search Console Indexing and Crawler Stress Testing.
        </div>
    </div>

    <!-- JavaScript Push Ingestion Script -->
    <script>
        document.getElementById('startSyncBtn')?.addEventListener('click', async function() {
            const apiUrl = document.getElementById('apiUrl').value.trim();
            const token = document.getElementById('apiToken').value.trim();
            const statusDiv = document.getElementById('syncStatus');
            const logBox = document.getElementById('logBox');
            const progContainer = document.getElementById('progContainer');
            const progBar = document.getElementById('progBar');
            
            if (!token) {
                alert('Please enter your API Key or Token.');
                return;
            }
            
            // Set UI elements active
            logBox.style.display = 'block';
            progContainer.style.display = 'block';
            logBox.innerHTML = 'Fetching mock articles...<br>';
            statusDiv.innerText = 'Initializing sync...';
            progBar.style.width = '0%';
            
            // 1. Fetch articles from export endpoint
            let articles = [];
            try {
                const response = await fetch('index.php?api_export=1');
                articles = await response.json();
                logBox.innerHTML += `Loaded ${articles.length} articles for sync.<br>`;
            } catch (err) {
                logBox.innerHTML += `<span class="log-error">Failed to load articles: ${err.message}</span><br>`;
                statusDiv.innerText = 'Sync failed.';
                return;
            }
            
            // 2. Parallel push batch iteration
            let successCount = 0;
            let failCount = 0;
            
            for (let i = 0; i < articles.length; i++) {
                const doc = articles[i];
                logBox.innerHTML += `Pushing: "${doc.title}"... `;
                logBox.scrollTop = logBox.scrollHeight;
                
                try {
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Key': token
                        },
                        body: JSON.stringify(doc)
                    });
                    
                    const resJson = await response.json();
                    
                    if (response.ok && resJson.success) {
                        successCount++;
                        logBox.innerHTML += `<span class="log-success">SUCCESS</span><br>`;
                    } else {
                        failCount++;
                        logBox.innerHTML += `<span class="log-error">FAILED (${resJson.error || 'Server Error'})</span><br>`;
                    }
                } catch (e) {
                    failCount++;
                    logBox.innerHTML += `<span class="log-error">NETWORK ERROR: ${e.message}</span><br>`;
                }
                
                // Update Progress UI
                const progress = Math.round(((i + 1) / articles.length) * 100);
                progBar.style.width = `${progress}%`;
                statusDiv.innerText = `Syncing: ${i + 1}/${articles.length} | Success: ${successCount} | Failed: ${failCount}`;
            }
            
            statusDiv.innerText = `Sync Complete! Success: ${successCount} | Failed: ${failCount}`;
            logBox.innerHTML += '====================================<br>Sync operation finished.<br>';
            logBox.scrollTop = logBox.scrollHeight;
        });
    </script>
</body>
</html>
<?php
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
