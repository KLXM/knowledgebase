<?php
/**
 * KB Timeline - UIkit Template
 * @var array $elementData
 */

$heading = (string) ($elementData['heading'] ?? '');
$tag = 'h3';
$intro = (string) ($elementData['intro'] ?? '');
$items = $elementData['items'] ?? [];

$style = (string) ($elementData['style'] ?? 'default');
$iconDefault = (string) ($elementData['icon_default'] ?? 'circle');
$iconColor = (string) ($elementData['icon_color'] ?? 'primary');
$lineColor = (string) ($elementData['line_color'] ?? 'solid');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

if (!is_array($items) || $items === []) {
    return;
}

$colorMap = [
    'primary' => 'var(--uk-color-primary, #1e87f0)',
    'secondary' => 'var(--uk-color-secondary, #222)',
    'success' => '#32d296',
    'warning' => '#faa05a',
    'danger' => '#f0506e',
    'muted' => '#999',
];
$dotColor = $colorMap[$iconColor] ?? $colorMap['primary'];

$borderStyle = 'solid';
if ($lineColor === 'dashed' || $lineColor === 'dotted') {
    $borderStyle = $lineColor;
}

$isAlternating = $style === 'alternating';
$isCard = $style === 'card';

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_bg_image', $sectionBgImage, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>

<?php if ($heading !== '' || $intro !== ''): ?>
<div class="uk-margin-medium-bottom<?= $isAlternating ? ' uk-text-center' : '' ?>">
    <?php if ($heading !== ''): ?>
        <<?= $tag ?> class="uk-margin-small-bottom"><?= rex_escape($heading) ?></<?= $tag ?>>
    <?php endif; ?>
    <?php if ($intro !== ''): ?>
        <p class="uk-text-muted"><?= nl2br(rex_escape($intro)) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="cse-timeline cse-timeline--<?= rex_escape($style) ?>" role="list">
    <?php foreach ($items as $i => $item): ?>
        <?php
        $date = (string) ($item['date'] ?? '');
        $title = (string) ($item['title'] ?? '');
        $text = (string) ($item['text'] ?? '');
        $icon = (string) ($item['icon'] ?? '');
        $badge = (string) ($item['badge'] ?? '');
        $highlight = !empty($item['highlight']);

        if ($title === '') {
            continue;
        }

        $isRight = $isAlternating && ($i % 2 !== 0);
        ?>
        <div class="cse-timeline__item<?= $highlight ? ' cse-timeline__item--highlight' : '' ?><?= $isRight ? ' cse-timeline__item--right' : '' ?>" role="listitem">
            <div class="cse-timeline__marker" aria-hidden="true">
                <div class="cse-timeline__dot">
                    <?php if ($icon !== ''): ?>
                        <span uk-icon="icon: <?= rex_escape($icon) ?>; ratio: 0.7"></span>
                    <?php elseif ($iconDefault !== 'none' && $iconDefault !== 'circle'): ?>
                        <span uk-icon="icon: <?= rex_escape($iconDefault) ?>; ratio: 0.7"></span>
                    <?php endif; ?>
                </div>
                <div class="cse-timeline__line"></div>
            </div>

            <div class="cse-timeline__content<?= $isCard ? ' uk-card uk-card-default uk-card-body uk-card-small' : '' ?>">
                <?php if ($date !== '' || $badge !== ''): ?>
                <div class="cse-timeline__meta uk-margin-small-bottom">
                    <?php if ($date !== ''): ?>
                        <span class="cse-timeline__date uk-text-muted uk-text-small"><?= rex_escape($date) ?></span>
                    <?php endif; ?>
                    <?php if ($badge !== ''): ?>
                        <span class="uk-badge uk-margin-small-left"><?= rex_escape($badge) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <h3 class="cse-timeline__title uk-margin-remove-top uk-margin-small-bottom<?= $highlight ? ' uk-text-bold' : '' ?>">
                    <?= rex_escape($title) ?>
                </h3>

                <?php if ($text !== ''): ?>
                    <p class="uk-margin-remove uk-text-muted"><?= nl2br(rex_escape($text)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>

<style>
.cse-timeline {
    position: relative;
}
.cse-timeline__item {
    display: grid;
    grid-template-columns: 32px 1fr;
    gap: 0 20px;
    position: relative;
    padding-bottom: 32px;
}
.cse-timeline__item:last-child {
    padding-bottom: 0;
}
.cse-timeline__item:last-child .cse-timeline__line {
    display: none;
}
.cse-timeline__marker {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.cse-timeline__dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: <?= rex_escape($dotColor) ?>;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    z-index: 1;
}
.cse-timeline__item--highlight .cse-timeline__dot {
    width: 38px;
    height: 38px;
}
.cse-timeline__line {
    flex: 1;
    width: 2px;
    border-left: 2px <?= rex_escape($borderStyle) ?> color-mix(in srgb, <?= rex_escape($dotColor) ?> 30%, transparent);
    margin-top: 4px;
    min-height: 24px;
}
.cse-timeline__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
@media (min-width: 960px) {
    .cse-timeline--alternating {
        max-width: 800px;
        margin-inline: auto;
    }
    .cse-timeline--alternating .cse-timeline__item {
        grid-template-columns: 1fr 32px 1fr;
    }
    .cse-timeline--alternating .cse-timeline__marker {
        order: 2;
    }
    .cse-timeline--alternating .cse-timeline__content {
        order: 1;
        text-align: right;
    }
    .cse-timeline--alternating .cse-timeline__item--right .cse-timeline__content {
        order: 3;
        text-align: left;
    }
    .cse-timeline--alternating .cse-timeline__line {
        display: none;
    }
    .cse-timeline--alternating::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        transform: translateX(-50%);
        border-left: 2px <?= rex_escape($borderStyle) ?> color-mix(in srgb, <?= rex_escape($dotColor) ?> 30%, transparent);
    }
}
</style>
