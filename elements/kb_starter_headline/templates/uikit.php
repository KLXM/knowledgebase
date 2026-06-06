<?php
$eyebrow = trim((string) ($elementData['eyebrow'] ?? ''));
$headline = trim((string) ($elementData['headline'] ?? ''));
$highlight = trim((string) ($elementData['highlight'] ?? ''));
$subline = trim((string) ($elementData['subline'] ?? ''));
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

if ($headline === '') {
    return;
}

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('container_width', $containerWidth, false);

$headlineSeed = $eyebrow . '|' . $headline . '|' . $highlight . '|' . $subline;
$headlineId = 'headline-' . substr(md5($headlineSeed), 0, 10);
$sublineId = $headlineId . '-subline';

$renderHeadline = static function (string $headlineText, string $highlightPart): string {
    if ($highlightPart === '') {
        return rex_escape($headlineText);
    }

    $pos = mb_stripos($headlineText, $highlightPart);
    if ($pos === false) {
        return rex_escape($headlineText);
    }

    $before = mb_substr($headlineText, 0, $pos);
    $match = mb_substr($headlineText, $pos, mb_strlen($highlightPart));
    $after = mb_substr($headlineText, $pos + mb_strlen($highlightPart));

    return rex_escape($before)
        . '<mark>' . rex_escape($match) . '</mark>'
        . rex_escape($after);
};
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>

    <header>
        <?php if ($eyebrow !== ''): ?>
        <p class="uk-text-meta uk-text-uppercase uk-margin-small-bottom"><?= rex_escape($eyebrow) ?></p>
        <?php endif; ?>

        <h2 class="uk-margin-remove" id="<?= rex_escape($headlineId) ?>"<?= $subline !== '' ? ' aria-describedby="' . rex_escape($sublineId) . '"' : '' ?>>
            <?= $renderHeadline($headline, $highlight) ?>
        </h2>

        <?php if ($subline !== ''): ?>
        <p id="<?= rex_escape($sublineId) ?>" class="uk-text-lead uk-margin-xsmall-top uk-margin-remove-bottom"><?= rex_escape($subline) ?></p>
        <?php endif; ?>
    </header>
<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
