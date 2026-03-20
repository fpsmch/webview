<?php

declare(strict_types=1);

$category = $file['category'] ?? 'document';
$url = htmlspecialchars($file['url']);
$name = htmlspecialchars($file['original_name']);
?>
<div class="render-stage stage-shell" id="presentation-stage" data-file-category="<?= htmlspecialchars($category) ?>">
    <div class="stage-overlay">
        <div>
            <strong><?= htmlspecialchars($session['details']['title'] ?? $file['original_name']) ?></strong>
            <span><?= htmlspecialchars($session['details']['organization'] ?? 'Live presentation') ?></span>
        </div>
        <button class="secondary-btn icon-btn" type="button" id="toggle-fullscreen">⛶ Full screen</button>
    </div>

    <div class="stage-content">
        <?php if ($category === 'pdf'): ?>
            <iframe class="content-frame" src="<?= $url ?>#toolbar=0&navpanes=0"></iframe>
        <?php elseif ($category === 'video'): ?>
            <video class="content-media" controls preload="metadata">
                <source src="<?= $url ?>" type="<?= htmlspecialchars($file['mime_type']) ?>">
            </video>
        <?php elseif ($category === 'audio'): ?>
            <div class="audio-stage">
                <p>Audio presentation: <?= $name ?></p>
                <audio class="content-media" controls preload="metadata">
                    <source src="<?= $url ?>" type="<?= htmlspecialchars($file['mime_type']) ?>">
                </audio>
            </div>
        <?php elseif ($category === 'image'): ?>
            <img class="content-image" src="<?= $url ?>" alt="<?= $name ?>">
        <?php elseif ($category === 'presentation'): ?>
            <iframe class="content-frame" src="<?= $url ?>"></iframe>
            <div class="fallback-note">PowerPoint files may need local download for rich animations. The uploaded file remains available for direct access.</div>
        <?php else: ?>
            <iframe class="content-frame" src="<?= $url ?>"></iframe>
        <?php endif; ?>
    </div>

    <div class="stage-footer-bar">
        <span><?= strtoupper(htmlspecialchars($file['extension'])) ?> · <?= htmlspecialchars(format_file_size((int)$file['size'])) ?></span>
        <a href="<?= $url ?>" download class="secondary-btn compact-btn">Download source</a>
    </div>
</div>
