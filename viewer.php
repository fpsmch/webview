<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$manager = new SessionManager();
$session = null;
$errorMessage = '';
$joinCode = strtoupper(sanitize_string($_REQUEST['code'] ?? '', 6));
$password = (string)($_POST['viewer_password'] ?? '');

if ($joinCode !== '') {
    $candidate = $manager->getByJoinCode($joinCode);

    if ($candidate === null) {
        $errorMessage = 'No active session matches that code.';
    } elseif (requires_session_password($candidate) && !$manager->viewerAuthorized($candidate, $password)) {
        $errorMessage = 'This session is password protected. Please enter the correct password.';
        $session = null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || !requires_session_password($candidate)) {
        $session = $candidate;
        $manager->markJoin((string)$session['session_id']);
    } else {
        $session = $candidate;
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
<body data-role="viewer" class="viewer-body">
<div class="ambient-bg"></div>
<div class="app-shell app-shell-wide viewer-shell">
    <header class="glass-panel topbar">
        <div>
            <p class="eyebrow">Client / audience access</p>
            <h1>Join a live presentation</h1>
            <p class="muted">Enter the session code and password if required. The stage refreshes automatically and supports fullscreen viewing.</p>
        </div>
        <a class="secondary-btn" href="index.php">Back to upload</a>
    </header>

    <?php if ($session === null || (requires_session_password($session) && $_SERVER['REQUEST_METHOD'] !== 'POST')): ?>
        <section class="glass-panel join-panel narrow premium-form">
            <div class="section-heading compact-heading">
                <div>
                    <p class="eyebrow">Secure join</p>
                    <h2>Access your session</h2>
                </div>
            </div>
            <form class="stack" action="viewer.php" method="post">
                <label class="input-label" for="code">Join code</label>
                <input id="code" type="text" name="code" maxlength="6" placeholder="ABC123" value="<?= htmlspecialchars($joinCode) ?>" required>

                <?php if ($joinCode !== '' && ($session !== null ? requires_session_password($session) : true)): ?>
                    <label class="input-label" for="viewer_password">Session password</label>
                    <input id="viewer_password" type="password" name="viewer_password" maxlength="80" placeholder="Required for protected sessions">
                <?php else: ?>
                    <label class="input-label" for="viewer_password">Session password</label>
                    <input id="viewer_password" type="password" name="viewer_password" maxlength="80" placeholder="Leave blank if not protected">
                <?php endif; ?>

                <button class="primary-btn" type="submit">Enter presentation</button>
            </form>
            <?php if ($errorMessage !== ''): ?>
                <div class="feedback error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php elseif ($session !== null && requires_session_password($session)): ?>
                <div class="feedback">This presentation is protected. Enter the session password to continue.</div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php $file = $session['file']; $state = $session['state']; $details = $session['details']; ?>
        <main class="viewer-stage-layout">
            <section class="glass-panel viewer-stage-shell fullscreen-ready">
                <?php include __DIR__ . '/includes/view_renderer.php'; ?>
            </section>
            <aside class="glass-panel floating-viewer-panel">
                <div class="section-heading compact-heading">
                    <div>
                        <p class="eyebrow">Live session</p>
                        <h2><?= htmlspecialchars($details['title']) ?></h2>
                    </div>
                    <span id="viewer-connection-indicator" class="status-dot online">Synced</span>
                </div>
                <p class="muted"><?= htmlspecialchars($details['organization'] ?: 'Live presentation') ?><?= $details['presenter_name'] !== '' ? ' · ' . htmlspecialchars($details['presenter_name']) : '' ?></p>
                <div class="session-meta">
                    <div><span>Join code</span><strong><?= htmlspecialchars($session['join_code']) ?></strong></div>
                    <div><span>Current slide</span><strong id="viewer-slide-display"><?= (int)$state['current_slide'] ?></strong></div>
                    <div><span>Status</span><strong id="viewer-status-display"><?= htmlspecialchars(ucfirst($state['status'])) ?></strong></div>
                    <div><span>Elapsed</span><strong id="viewer-elapsed-display"><?= htmlspecialchars(format_seconds((int)$state['elapsed_seconds'])) ?></strong></div>
                </div>
                <div class="live-banner" id="viewer-highlight-message"><?= htmlspecialchars($state['highlight_message'] ?? 'Live presentation') ?></div>
            </aside>
        </main>
        <script>
        window.WEBVIEW_SESSION = <?= json_encode([
            'sessionId' => $session['session_id'],
            'joinCode' => $session['join_code'],
            'state' => $state,
            'analytics' => $session['analytics'],
            'pollInterval' => POLL_INTERVAL_MS,
        ], JSON_UNESCAPED_SLASHES) ?>;
        </script>
    <?php endif; ?>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
