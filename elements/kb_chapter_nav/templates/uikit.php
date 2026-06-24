<?php

declare(strict_types=1);

$title = trim((string) ($elementData['title'] ?? ''));
$text = trim((string) ($elementData['text'] ?? ''));
$anchorInput = trim((string) ($elementData['anchor_id'] ?? ''));
$headingLevel = strtolower(trim((string) ($elementData['heading_level'] ?? 'h2')));

if (!in_array($headingLevel, ['h2', 'h3', 'h4'], true)) {
    $headingLevel = 'h2';
}

if ('' === $title) {
    return;
}

$anchor = \FriendsOfREDAXO\Knowledgebase\FrontendRenderer::resolveChapterRenderAnchor($anchorInput, $title);

if ('' === $anchor) {
    return;
}
?>
<section id="<?= rex_escape($anchor) ?>" class="kb-chapter-nav uk-section uk-section-xsmall uk-padding-remove-top uk-padding-remove-horizontal">
        <<?= $headingLevel ?> class="uk-heading-bullet uk-margin-small-bottom"><?= rex_escape($title) ?></<?= $headingLevel ?>>

    <?php
    if ('' !== $text) {
        echo '<p class="uk-text-muted uk-margin-bottom">' . nl2br(rex_escape($text)) . '</p>';
    }
    ?>
</section>