<?php

declare(strict_types=1);

$category = $file['category'] ?? 'document';
$url = htmlspecialchars($file['url']);
$name = htmlspecialchars($file['original_name']);
?>
<div class="render-stage" data-file-category="<?= htmlspecialchars($category) ?>">
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
        <div class="fallback-note">If your browser cannot render this presentation, download the file to view it locally.</div>
    <?php else: ?>
        <iframe class="content-frame" src="<?= $url ?>"></iframe>
    <?php endif; ?>
</div>
