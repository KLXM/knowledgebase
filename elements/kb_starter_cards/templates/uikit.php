<?php
$headline = (string) ($elementData['headline'] ?? '');
$items = $elementData['items'] ?? [];
$columns = (string) ($elementData['columns'] ?? '3');
$columnsTablet = (string) ($elementData['columns_tablet'] ?? '2');
$columnsMobile = (string) ($elementData['columns_mobile'] ?? '1');
$gap = (string) ($elementData['gap'] ?? 'medium');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

if (!is_array($items) || $items === []) {
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

$gridClass = 'uk-child-width-1-' . $columnsMobile;
$gridClass .= ' uk-child-width-1-' . $columnsTablet . '@s';
$gridClass .= ' uk-child-width-1-' . $columns . '@m';
$gapClass = $gap === 'collapse' ? '' : 'uk-grid-' . $gap;
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>
        <?php if ($headline !== ''): ?><h3><?= rex_escape($headline) ?></h3><?php endif; ?>
        <div class="<?= rex_escape(trim($gridClass . ' ' . $gapClass)) ?>" uk-grid>
            <?php foreach ($items as $index => $item): ?>
                <?php
                $itemTitle = (string) ($item['title'] ?? '');
                $itemText = (string) ($item['text'] ?? '');
                $itemLinkText = trim((string) ($item['link_text'] ?? 'Mehr erfahren'));
                if ($itemLinkText === '') {
                    $itemLinkText = 'Mehr erfahren';
                }
                $fallback = $headline !== '' ? $headline . ' ' . ($index + 1) : 'Karte ' . ($index + 1);
                $itemImageAlt = \KLXM\YFormContentBuilder\MediaAltResolver::resolve((string) ($item['image'] ?? ''), '', $itemTitle !== '' ? $itemTitle : $fallback);
                ?>
                <div>
                    <article class="uk-card uk-card-default uk-card-small uk-card-body" style="height:100%;">
                        <?php if (!empty($item['image'])): ?>
                            <div class="uk-card-media-top" style="margin:-15px -15px 12px -15px;">
                                <img src="<?= rex_url::media((string) $item['image']) ?>" alt="<?= rex_escape($itemImageAlt) ?>" class="uk-width-1-1" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <?php if ($itemTitle !== ''): ?><h4 class="uk-margin-small-bottom"><?= rex_escape($itemTitle) ?></h4><?php endif; ?>
                        <?php if (trim(strip_tags($itemText)) !== ''): ?><div><?= $itemText ?></div><?php endif; ?>
                        <?php if (!empty($item['link_url'])): ?>
                            <a href="<?= rex_escape((string) $item['link_url']) ?>" class="uk-button uk-button-text" aria-label="<?= rex_escape($itemLinkText . ($itemTitle !== '' ? ' - ' . $itemTitle : '')) ?>">
                                <?= rex_escape($itemLinkText) ?>
                            </a>
                        <?php endif; ?>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
