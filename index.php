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
    <title><?= htmlspecialchars(APP_NAME) ?> · Secure upload</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="ambient-bg"></div>
<div class="app-shell app-shell-wide">
    <header class="hero-panel glass-panel hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Business-ready remote presenting</p>
            <h1>Secure, elegant file presentations for clients, stakeholders, and executive briefings.</h1>
            <p class="muted lead">Launch polished remote sessions with protected access, fullscreen playback, live presenter controls, and lightweight PHP deployment on standard hosting.</p>
            <div class="hero-badges">
                <span>Protected viewer access</span>
                <span>Fullscreen presentation stage</span>
                <span>Logs + JSON session storage</span>
            </div>
        </div>
        <div class="hero-stats card-stack">
            <article class="metric-card">
                <strong>Deploy anywhere</strong>
                <span>Apache, Nginx, or shared PHP hosting</span>
            </article>
            <article class="metric-card">
                <strong>Near real-time sync</strong>
                <span>AJAX polling every <?= (int)(POLL_INTERVAL_MS / 1000) ?>.<?= (int)((POLL_INTERVAL_MS % 1000) / 100) ?>s</span>
            </article>
            <article class="metric-card">
                <strong>Executive UX</strong>
                <span>Dark cinematic visuals with glass panels</span>
            </article>
        </div>
    </header>

    <main class="grid landing-layout">
        <section class="glass-panel upload-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Create session</p>
                    <h2>Upload your presentation asset</h2>
                </div>
                <span class="pill">Max <?= htmlspecialchars(format_file_size(MAX_UPLOAD_SIZE)) ?></span>
            </div>

            <form id="upload-form" class="stack" action="api/create_session.php" method="post" enctype="multipart/form-data">
                <div class="grid form-grid">
                    <div>
                        <label class="input-label" for="session_title">Session title</label>
                        <input id="session_title" type="text" name="session_title" maxlength="120" placeholder="Quarterly business review" required>
                    </div>
                    <div>
                        <label class="input-label" for="organization">Organization / client</label>
                        <input id="organization" type="text" name="organization" maxlength="120" placeholder="Acme Holdings">
                    </div>
                    <div>
                        <label class="input-label" for="presenter_name">Presenter name</label>
                        <input id="presenter_name" type="text" name="presenter_name" maxlength="80" placeholder="Jordan Lee">
                    </div>
                    <div>
                        <label class="input-label" for="viewer_password">Viewer password</label>
                        <input id="viewer_password" type="password" name="viewer_password" maxlength="80" placeholder="Optional access password">
                    </div>
                </div>
                <div>
                    <label class="input-label" for="audience_note">Audience note</label>
                    <input id="audience_note" type="text" name="audience_note" maxlength="220" placeholder="Optional note shown to viewers, e.g. Financial highlights for board members">
                </div>
                <div>
                    <label class="input-label" for="presentation_file">Presentation file</label>
                    <input id="presentation_file" type="file" name="presentation_file" required>
                </div>
                <div class="button-row button-row-3">
                    <button class="primary-btn" type="submit">Create protected session</button>
                    <a class="secondary-btn" href="viewer.php">Join a live session</a>
                    <span class="subtle-text">Supports PDF, PPT/PPTX, video, audio, images, and text.</span>
                </div>
            </form>

            <div id="upload-feedback" class="feedback" aria-live="polite"></div>
        </section>

        <section class="glass-panel sidebar-panel">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Why teams use it</p>
                    <h2>Built for modern business communication</h2>
                </div>
            </div>
            <div class="feature-tiles">
                <article class="feature-card">
                    <strong>Password-protected access</strong>
                    <p class="muted">Protect confidential sessions with a viewer password and a private presenter control token.</p>
                </article>
                <article class="feature-card">
                    <strong>Fullscreen remote stage</strong>
                    <p class="muted">Present content in a distraction-free stage view that feels premium on desktops and displays.</p>
                </article>
                <article class="feature-card">
                    <strong>Operational visibility</strong>
                    <p class="muted">Track uploads, joins, state changes, and cleanup events in a reusable file logger.</p>
                </article>
            </div>
        </section>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
