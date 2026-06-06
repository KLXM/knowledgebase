<?php
/**
 * KB Headline - UIkit Template
 * @var array $elementData
 */

$text = trim((string) ($elementData['text'] ?? ''));
$tag = (string) ($elementData['tag'] ?? 'h2');
$size = (string) ($elementData['size'] ?? '');
$modifier = (string) ($elementData['modifier'] ?? '');
$alignment = (string) ($elementData['alignment'] ?? 'left');
$color = (string) ($elementData['color'] ?? '');
$spacingTop = (string) ($elementData['spacing_top'] ?? '');
$spacingBottom = (string) ($elementData['spacing_bottom'] ?? '');
$underline = !empty($elementData['underline']);
$linkType = (string) ($elementData['link_type'] ?? '');
$linkUrl = trim((string) ($elementData['link_url'] ?? ''));
$linkInternal = (string) ($elementData['link_internal'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$tag = 'h1';
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

if ($text === '') {
    return;
}

$finalLink = '';
if ($linkType === 'external' && $linkUrl !== '') {
    $finalLink = $linkUrl;
} elseif ($linkType === 'internal' && $linkInternal !== '') {
    $finalLink = rex_getUrl((int) $linkInternal);
}

$classes = [];
$sizeMap = [
    'small' => 'uk-heading-small',
    'medium' => 'uk-heading-medium',
    'large' => 'uk-heading-large',
    'xlarge' => 'uk-heading-xlarge',
    '2xlarge' => 'uk-heading-2xlarge',
];
if (isset($sizeMap[$size])) {
    $classes[] = $sizeMap[$size];
}

$modifierMap = [
    'divider' => 'uk-heading-divider',
    'bullet' => 'uk-heading-bullet',
    'line' => 'uk-heading-line',
];
if (isset($modifierMap[$modifier])) {
    $classes[] = $modifierMap[$modifier];
}

$alignmentMap = [
    'left' => 'uk-text-left',
    'center' => 'uk-text-center',
    'right' => 'uk-text-right',
];
if (isset($alignmentMap[$alignment])) {
    $classes[] = $alignmentMap[$alignment];
}

if ($color !== '') {
    $classes[] = 'uk-text-' . $color;
}

$spacingMapTop = [
    'none' => 'uk-margin-remove-top',
    'small' => 'uk-margin-small-top',
    'medium' => 'uk-margin-top',
    'large' => 'uk-margin-large-top',
];
if (isset($spacingMapTop[$spacingTop])) {
    $classes[] = $spacingMapTop[$spacingTop];
}

$spacingMapBottom = [
    'none' => 'uk-margin-remove-bottom',
    'small' => 'uk-margin-small-bottom',
    'medium' => 'uk-margin-bottom',
    'large' => 'uk-margin-large-bottom',
];
if (isset($spacingMapBottom[$spacingBottom])) {
    $classes[] = $spacingMapBottom[$spacingBottom];
}

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

$classStr = trim(implode(' ', $classes));
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>

<<?= $tag ?> class="<?= rex_escape($classStr) ?>">
    <?php if ($finalLink !== ''): ?>
        <a href="<?= rex_escape($finalLink) ?>" class="uk-link-reset">
            <?php if ($underline): ?><span style="text-decoration: underline;"><?php endif; ?>
            <?= rex_escape($text) ?>
            <?php if ($underline): ?></span><?php endif; ?>
        </a>
    <?php else: ?>
        <?php if ($underline): ?><span style="text-decoration: underline;"><?php endif; ?>
        <?= rex_escape($text) ?>
        <?php if ($underline): ?></span><?php endif; ?>
    <?php endif; ?>
</<?= $tag ?>>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
