<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$sessionId = sanitize_string($_GET['session'] ?? '', 64);
$presenterToken = sanitize_string($_GET['token'] ?? '', 128);
$manager = new SessionManager();
$session = $manager->getBySessionId($sessionId);

if ($session === null || !$manager->presenterAuthorized($session, $presenterToken)) {
    http_response_code(403);
    echo 'Presenter access denied.';
    exit;
}

$file = $session['file'];
$state = $session['state'];
$details = $session['details'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presenter · <?= htmlspecialchars($details['title']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-role="presenter">
<div class="ambient-bg"></div>
<div class="app-shell app-shell-wide">
    <header class="glass-panel topbar topbar-presenter">
        <div>
            <p class="eyebrow">Presenter control suite</p>
            <h1><?= htmlspecialchars($details['title']) ?></h1>
            <p class="muted"><?= htmlspecialchars($details['organization'] ?: 'Business presentation') ?><?= $details['presenter_name'] !== '' ? ' · Presented by ' . htmlspecialchars($details['presenter_name']) : '' ?></p>
        </div>
        <div class="header-actions wrap-actions">
            <span class="pill">Join code <?= htmlspecialchars($session['join_code']) ?></span>
            <?php if (!empty($session['security']['password_required'])): ?><span class="pill accent-pill">Password protected</span><?php endif; ?>
            <a class="secondary-btn" href="viewer.php?code=<?= urlencode($session['join_code']) ?>" target="_blank" rel="noopener">Open viewer</a>
            <a class="secondary-btn" href="index.php">New session</a>
        </div>
    </header>

    <main class="grid premium-layout">
        <section class="stage-column">
            <?php include __DIR__ . '/includes/view_renderer.php'; ?>
        </section>

        <aside class="glass-panel command-panel">
            <div class="section-heading compact-heading">
                <div>
                    <p class="eyebrow">Live session</p>
                    <h2>Executive controls</h2>
                </div>
                <span id="connection-indicator" class="status-dot online">Live</span>
            </div>

            <div class="session-meta stat-grid">
                <div><span>Current slide</span><strong id="slide-display"><?= (int)$state['current_slide'] ?></strong></div>
                <div><span>Elapsed</span><strong id="elapsed-display"><?= htmlspecialchars(format_seconds((int)$state['elapsed_seconds'])) ?></strong></div>
                <div><span>Status</span><strong id="status-display"><?= htmlspecialchars(ucfirst($state['status'])) ?></strong></div>
                <div><span>Viewers</span><strong id="viewer-count-display"><?= (int)($session['analytics']['viewer_count'] ?? 0) ?></strong></div>
            </div>

            <div class="stack panel-block">
                <label class="input-label" for="highlight-message">Audience banner</label>
                <input id="highlight-message" type="text" maxlength="120" value="<?= htmlspecialchars($state['highlight_message'] ?? '') ?>" placeholder="Now discussing revenue highlights">
            </div>

            <div class="button-row button-row-2">
                <button class="secondary-btn" id="prev-slide">Previous</button>
                <button class="secondary-btn" id="next-slide">Next</button>
            </div>
            <div class="button-row button-row-2">
                <button class="primary-btn" id="start-presentation">Start live</button>
                <button class="secondary-btn" id="pause-presentation">Pause</button>
            </div>
            <div class="button-row button-row-2">
                <button class="danger-btn" id="stop-presentation">Reset</button>
                <button class="secondary-btn" id="refresh-state">Sync now</button>
            </div>
            <div class="button-row button-row-2">
                <button class="secondary-btn" id="toggle-focus">Toggle focus mode</button>
                <button class="secondary-btn" id="copy-viewer-link">Copy viewer link</button>
            </div>

            <div class="share-panel">
                <div>
                    <span class="share-label">Viewer link</span>
                    <code id="viewer-link-text"><?= htmlspecialchars(viewer_url($session['join_code'])) ?></code>
                </div>
                <div>
                    <span class="share-label">Presenter link</span>
                    <code><?= htmlspecialchars(presenter_url($session['session_id'], $session['security']['presenter_token'])) ?></code>
                </div>
            </div>

            <p class="muted small"><?= htmlspecialchars($details['audience_note'] !== '' ? $details['audience_note'] : 'Use fullscreen mode for boardrooms, demos, and client presentations.') ?></p>
        </aside>
    </main>
</div>
<script>
window.WEBVIEW_SESSION = <?= json_encode([
    'sessionId' => $session['session_id'],
    'joinCode' => $session['join_code'],
    'presenterToken' => $session['security']['presenter_token'],
    'viewerUrl' => viewer_url($session['join_code']),
    'state' => $state,
    'analytics' => $session['analytics'],
    'pollInterval' => POLL_INTERVAL_MS,
], JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
