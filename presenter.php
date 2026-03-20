<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$sessionId = sanitize_string($_GET['session'] ?? '');
$manager = new SessionManager();
$session = $manager->getBySessionId($sessionId);

if ($session === null) {
    http_response_code(404);
    echo 'Session not found.';
    exit;
}

$file = $session['file'];
$state = $session['state'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presenter · <?= htmlspecialchars($file['original_name']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-role="presenter">
<div class="app-shell">
    <header class="glass-panel page-header">
        <div>
            <p class="eyebrow">Presenter console</p>
            <h1><?= htmlspecialchars($file['original_name']) ?></h1>
            <p class="muted">Session ID: <?= htmlspecialchars($session['session_id']) ?> · Join code: <strong><?= htmlspecialchars($session['join_code']) ?></strong></p>
        </div>
        <div class="header-actions">
            <a class="secondary-btn" href="viewer.php?code=<?= urlencode($session['join_code']) ?>" target="_blank" rel="noopener">Open viewer</a>
            <a class="secondary-btn" href="index.php">New upload</a>
        </div>
    </header>

    <main class="grid presenter-layout">
        <section class="glass-panel viewer-frame-panel">
            <?php include __DIR__ . '/includes/view_renderer.php'; ?>
        </section>

        <aside class="glass-panel control-panel">
            <h2>Controls</h2>
            <div class="session-meta">
                <div><span>Current slide</span><strong id="slide-display"><?= (int)$state['current_slide'] ?></strong></div>
                <div><span>Elapsed</span><strong id="elapsed-display"><?= htmlspecialchars(format_seconds((int)$state['elapsed_seconds'])) ?></strong></div>
                <div><span>Status</span><strong id="status-display"><?= htmlspecialchars(ucfirst($state['status'])) ?></strong></div>
                <div><span>Total slides</span><strong><?= (int)$state['total_slides'] ?></strong></div>
            </div>
            <div class="button-row">
                <button class="secondary-btn" id="prev-slide">Previous</button>
                <button class="secondary-btn" id="next-slide">Next</button>
            </div>
            <div class="button-row">
                <button class="primary-btn" id="start-presentation">Start</button>
                <button class="danger-btn" id="stop-presentation">Stop</button>
            </div>
            <div class="button-row">
                <button class="secondary-btn" id="pause-presentation">Pause</button>
                <button class="secondary-btn" id="refresh-state">Refresh sync</button>
            </div>
            <p class="muted small">PPT/PPTX files are embedded when the browser can render them; otherwise viewers receive a download-friendly fallback.</p>
        </aside>
    </main>
</div>
<script>
window.WEBVIEW_SESSION = <?= json_encode([
    'sessionId' => $session['session_id'],
    'joinCode' => $session['join_code'],
    'state' => $state,
    'pollInterval' => POLL_INTERVAL_MS,
], JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
