<?php

declare(strict_types=1);

$title = trim((string) ($elementData['title'] ?? ''));
$text = trim((string) ($elementData['text'] ?? ''));
$badge = trim((string) ($elementData['badge'] ?? ''));
$anchorInput = trim((string) ($elementData['anchor_id'] ?? ''));
$headingLevel = strtolower(trim((string) ($elementData['heading_level'] ?? 'h2')));

if (!in_array($headingLevel, ['h2', 'h3', 'h4'], true)) {
    $headingLevel = 'h2';
}

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
<section id="<?= rex_escape($anchor) ?>" class="kb-chapter-nav kb-chapter-nav--plain" style="border-top:1px solid #ddd;padding-top:14px;margin-top:14px;">
    <?php if ('' !== $badge): ?>
        <div style="font-size:12px;color:#666;margin-bottom:6px;"><?= rex_escape($badge) ?></div>
    <?php endif; ?>

        <<?= $headingLevel ?> style="margin:0 0 8px;"><?= rex_escape($title) ?></<?= $headingLevel ?>>

    <?php if ('' !== $text): ?>
        <p style="margin:0 0 12px;color:#444;"><?= nl2br(rex_escape($text)) ?></p>
    <?php endif; ?>
</section>
