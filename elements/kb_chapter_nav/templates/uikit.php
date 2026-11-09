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
$headingNumber = \FriendsOfREDAXO\Knowledgebase\FrontendRenderer::chapterHeadingNumber($elementData);
$spacingTop = (string) ($elementData['spacing_top'] ?? '');
$spacingTop = in_array($spacingTop, ['medium', 'large'], true) ? $spacingTop : '';

if ('' === $anchor) {
    return;
}
?>
<section id="<?= rex_escape($anchor) ?>" class="kb-chapter-nav uk-section uk-section-xsmall uk-padding-remove-top uk-padding-remove-horizontal<?= '' !== $spacingTop ? ' kb-chapter-nav--space-' . $spacingTop : '' ?> kb-chapter-nav--heading-<?= \FriendsOfREDAXO\Knowledgebase\AddonSettings::getChapterHeadingStyle() ?>">
        <<?= $headingLevel ?> class="kb-chapter-nav__heading uk-heading-bullet uk-margin-small-bottom<?= '' !== $headingNumber ? ' kb-chapter-nav__heading--numbered' : '' ?>"><?php if ('' !== $headingNumber): ?><span class="kb-chapter-nav__number"><?= rex_escape($headingNumber) ?></span> <span class="kb-chapter-nav__title"><?= rex_escape($title) ?></span><?php else: ?><?= rex_escape($title) ?><?php endif ?></<?= $headingLevel ?>>

    <?php
    if ('' !== $text) {
        echo '<p class="uk-text-muted uk-margin-bottom">' . nl2br(rex_escape($text)) . '</p>';
    }
    ?>
</section>