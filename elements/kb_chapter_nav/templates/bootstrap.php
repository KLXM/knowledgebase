<?php

declare(strict_types=1);

$title = trim((string) ($elementData['title'] ?? ''));
$text = trim((string) ($elementData['text'] ?? ''));
$anchorInput = trim((string) ($elementData['anchor_id'] ?? ''));

if ('' === $title) {
    return;
}

$anchorBase = '' !== $anchorInput ? $anchorInput : $title;
$anchor = strtolower(trim($anchorBase));
$anchor = preg_replace('/[^a-z0-9\-_]+/u', '-', $anchor);
$anchor = is_string($anchor) ? trim($anchor, '-') : '';

if ('' === $anchor) {
    return;
}
?>
<section id="<?= rex_escape($anchor) ?>" class="panel panel-default kb-chapter-nav kb-chapter-nav--bootstrap">
    <div class="panel-body">
        <h4 style="margin-top:0;"><?= rex_escape($title) ?></h4>

        <?php if ('' !== $text): ?>
            <p class="text-muted" style="margin-bottom:12px;"><?= nl2br(rex_escape($text)) ?></p>
        <?php endif; ?>
    </div>
</section>
