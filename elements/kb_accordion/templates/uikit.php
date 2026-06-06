<?php
/**
 * KB Accordion - UIkit Template
 * @var array $elementData
 */

$displayType = (string) ($elementData['display_type'] ?? 'accordion');
$style = (string) ($elementData['style'] ?? 'default');
$items = $elementData['items'] ?? [];

$collapsible = !empty($elementData['accordion_collapsible']);
$multiple = !empty($elementData['accordion_multiple']);
$animation = ((string) ($elementData['accordion_animation'] ?? 'true')) === 'true';
$firstOpen = !empty($elementData['first_open']);
$tabStyle = (string) ($elementData['tab_style'] ?? 'default');
$tabAlignment = (string) ($elementData['tab_alignment'] ?? 'left');

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

$styleMap = [
    'default' => 'uk-card-default',
    'primary' => 'uk-card-primary',
    'secondary' => 'uk-card-secondary',
    'muted' => 'uk-card-muted',
];
$styleClass = $styleMap[$style] ?? $style;

$tabStyleClasses = ['uk-subnav'];
if ($displayType === 'tabs') {
    if ($tabStyle === 'pill') {
        $tabStyleClasses[] = 'uk-subnav-pill';
    } elseif ($tabStyle === 'divider') {
        $tabStyleClasses[] = 'uk-subnav-divider';
    } else {
        $tabStyleClasses[] = 'uk-subnav-default';
    }

    if ($tabAlignment === 'center') {
        $tabStyleClasses[] = 'uk-flex-center';
    } elseif ($tabAlignment === 'right') {
        $tabStyleClasses[] = 'uk-flex-right';
    } elseif ($tabAlignment === 'expand') {
        $tabStyleClasses = ['uk-tab', 'uk-child-width-expand'];
    }
}

$accordionAttrs = [];
if ($collapsible) {
    $accordionAttrs[] = 'collapsible: true';
}
if ($multiple) {
    $accordionAttrs[] = 'multiple: true';
}
if (!$animation) {
    $accordionAttrs[] = 'animation: false';
}
$accordionAttrStr = $accordionAttrs === [] ? '' : implode('; ', $accordionAttrs);

$uniqueId = 'cse-accordion-' . uniqid();

$renderIcon = static function (string $icon): string {
    $icon = trim($icon);
    if ($icon === '') {
        return '';
    }

    if (strpos($icon, 'fa-') !== false) {
        return '<i class="fa ' . rex_escape($icon) . ' uk-margin-small-right" aria-hidden="true"></i>';
    }

    $iconName = preg_replace('/^(uk-icon-|icon-)/', '', $icon);

    return '<span uk-icon="icon: ' . rex_escape((string) $iconName) . '" class="uk-margin-small-right" aria-hidden="true"></span>';
};

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

<?php if ($displayType === 'tabs'): ?>
<div class="cse-accordion-tabs" uk-scrollspy="cls: uk-animation-fade">
    <ul class="<?= rex_escape(implode(' ', $tabStyleClasses)) ?>" uk-switcher="animation: uk-animation-fade">
        <?php foreach ($items as $index => $item): ?>
            <?php
            $isDisabled = !empty($item['disabled']);
            $liClasses = [];
            if ($firstOpen && $index === 0) {
                $liClasses[] = 'uk-active';
            }
            if ($isDisabled) {
                $liClasses[] = 'uk-disabled';
            }
            ?>
            <li<?= $liClasses !== [] ? ' class="' . rex_escape(implode(' ', $liClasses)) . '"' : '' ?>>
                <a href="#">
                    <?= $renderIcon((string) ($item['icon'] ?? '')) ?>
                    <?= rex_escape((string) ($item['title'] ?? 'Tab ' . ($index + 1))) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <ul class="uk-switcher uk-margin">
        <?php foreach ($items as $item): ?>
            <li>
                <div class="uk-card uk-card-body <?= rex_escape($styleClass) ?>">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?= rex_url::media((string) $item['image']) ?>" alt="<?= rex_escape((string) ($item['title'] ?? '')) ?>" class="uk-margin-bottom" loading="lazy">
                    <?php endif; ?>
                    <?= (string) ($item['content'] ?? '') ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php elseif ($displayType === 'tabs-left'): ?>
<div class="cse-accordion-tabs-left" uk-scrollspy="cls: uk-animation-fade">
    <div class="uk-grid-small uk-child-width-expand@s" uk-grid>
        <div class="uk-width-auto@m">
            <ul class="uk-tab-left" uk-tab="connect: #<?= rex_escape($uniqueId) ?>-content; animation: uk-animation-fade">
                <?php foreach ($items as $index => $item): ?>
                    <?php
                    $isDisabled = !empty($item['disabled']);
                    $liClasses = [];
                    if ($firstOpen && $index === 0) {
                        $liClasses[] = 'uk-active';
                    }
                    if ($isDisabled) {
                        $liClasses[] = 'uk-disabled';
                    }
                    ?>
                    <li<?= $liClasses !== [] ? ' class="' . rex_escape(implode(' ', $liClasses)) . '"' : '' ?>>
                        <a href="#">
                            <?= $renderIcon((string) ($item['icon'] ?? '')) ?>
                            <?= rex_escape((string) ($item['title'] ?? 'Tab ' . ($index + 1))) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="uk-width-expand@m">
            <ul id="<?= rex_escape($uniqueId) ?>-content" class="uk-switcher">
                <?php foreach ($items as $item): ?>
                    <li>
                        <div class="uk-card uk-card-body <?= rex_escape($styleClass) ?>">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= rex_url::media((string) $item['image']) ?>" alt="<?= rex_escape((string) ($item['title'] ?? '')) ?>" class="uk-margin-bottom" loading="lazy">
                            <?php endif; ?>
                            <?= (string) ($item['content'] ?? '') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php else: ?>
<div class="cse-accordion" uk-scrollspy="cls: uk-animation-fade">
    <ul uk-accordion<?= $accordionAttrStr !== '' ? '="' . rex_escape($accordionAttrStr) . '"' : '' ?>>
        <?php foreach ($items as $index => $item): ?>
            <?php
            $title = (string) ($item['title'] ?? '');
            $content = (string) ($item['content'] ?? '');
            $isDisabled = !empty($item['disabled']);

            $liClasses = [];
            if ($styleClass !== '') {
                $liClasses[] = $styleClass;
            }
            if ($firstOpen && $index === 0) {
                $liClasses[] = 'uk-open';
            }
            if ($isDisabled) {
                $liClasses[] = 'uk-disabled';
            }
            ?>
            <li<?= $liClasses !== [] ? ' class="' . rex_escape(implode(' ', $liClasses)) . '"' : '' ?>>
                <a class="uk-padding-small uk-accordion-title<?= $isDisabled ? ' uk-text-muted' : '' ?>" href="#">
                    <?= $renderIcon((string) ($item['icon'] ?? '')) ?>
                    <?= rex_escape($title) ?>
                </a>
                <div class="uk-accordion-content uk-padding-small">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?= rex_url::media((string) $item['image']) ?>" alt="<?= rex_escape($title) ?>" class="uk-margin-bottom" loading="lazy">
                    <?php endif; ?>
                    <?= $content ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
