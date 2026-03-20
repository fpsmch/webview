<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$manager = new SessionManager();
$session = null;
$joinCode = sanitize_string($_GET['code'] ?? '');

if ($joinCode !== '') {
    $session = $manager->getByJoinCode($joinCode);
    if ($session !== null) {
        $manager->markJoin((string)$session['session_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viewer · <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-role="viewer">
<div class="app-shell viewer-shell">
    <header class="glass-panel page-header">
        <div>
            <p class="eyebrow">Viewer access</p>
            <h1>Join a presentation</h1>
            <p class="muted">Enter the presenter’s join code to sync the file state in near real time.</p>
        </div>
        <a class="secondary-btn" href="index.php">Back to upload</a>
    </header>

    <?php if ($session === null): ?>
        <section class="glass-panel join-panel narrow">
            <form class="stack" action="viewer.php" method="get">
                <label class="input-label" for="code">Join code</label>
                <input id="code" type="text" name="code" maxlength="6" placeholder="ABC123" required>
                <button class="primary-btn" type="submit">Join session</button>
            </form>
            <?php if ($joinCode !== ''): ?>
                <div class="feedback error">No active session matches that code.</div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php $file = $session['file']; $state = $session['state']; ?>
        <main class="grid viewer-layout">
            <section class="glass-panel viewer-frame-panel">
                <?php include __DIR__ . '/includes/view_renderer.php'; ?>
            </section>
            <aside class="glass-panel viewer-status-panel">
                <h2><?= htmlspecialchars($file['original_name']) ?></h2>
                <div class="session-meta">
                    <div><span>Join code</span><strong><?= htmlspecialchars($session['join_code']) ?></strong></div>
                    <div><span>Current slide</span><strong id="viewer-slide-display"><?= (int)$state['current_slide'] ?></strong></div>
                    <div><span>Status</span><strong id="viewer-status-display"><?= htmlspecialchars(ucfirst($state['status'])) ?></strong></div>
                    <div><span>Elapsed</span><strong id="viewer-elapsed-display"><?= htmlspecialchars(format_seconds((int)$state['elapsed_seconds'])) ?></strong></div>
                </div>
                <p class="muted small">This view refreshes automatically every <?= (int)(POLL_INTERVAL_MS / 1000) ?>.<?= (int)((POLL_INTERVAL_MS % 1000) / 100) ?> seconds.</p>
            </aside>
        </main>
        <script>
        window.WEBVIEW_SESSION = <?= json_encode([
            'sessionId' => $session['session_id'],
            'joinCode' => $session['join_code'],
            'state' => $state,
            'pollInterval' => POLL_INTERVAL_MS,
        ], JSON_UNESCAPED_SLASHES) ?>;
        </script>
    <?php endif; ?>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
