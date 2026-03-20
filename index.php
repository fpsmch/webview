<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> · Upload</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <header class="hero-card glass-panel">
        <div>
            <p class="eyebrow">Remote presentation made simple</p>
            <h1><?= htmlspecialchars(APP_NAME) ?></h1>
            <p class="muted">Upload a file, launch a session, and sync viewers in real time using plain PHP and lightweight polling.</p>
        </div>
        <div class="hero-actions">
            <a class="secondary-btn" href="viewer.php">Join as viewer</a>
        </div>
    </header>

    <main class="grid two-col">
        <section class="glass-panel">
            <h2>Start a presentation</h2>
            <p class="muted">Supported formats: PDF, PPT/PPTX, video, audio, images, and text. Max upload: 50 MB.</p>
            <form id="upload-form" class="stack" action="api/create_session.php" method="post" enctype="multipart/form-data">
                <label class="input-label" for="presentation_file">Choose file</label>
                <input id="presentation_file" type="file" name="presentation_file" required>
                <button class="primary-btn" type="submit">Create session</button>
            </form>
            <div id="upload-feedback" class="feedback" aria-live="polite"></div>
        </section>

        <section class="glass-panel">
            <h2>How it works</h2>
            <ol class="feature-list">
                <li>Upload a supported file to generate a secure presenter session.</li>
                <li>Share the generated join code with your remote audience.</li>
                <li>Control playback or page navigation while viewers stay in sync.</li>
            </ol>
            <div class="mini-grid">
                <article class="stat-card">
                    <strong>Polling</strong>
                    <span>Every <?= (int)(POLL_INTERVAL_MS / 1000) ?>–2 seconds</span>
                </article>
                <article class="stat-card">
                    <strong>Logging</strong>
                    <span>Uploads, joins, errors, cleanup</span>
                </article>
            </div>
        </section>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
