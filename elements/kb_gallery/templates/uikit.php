<?php
/**
 * Gallery Element - UIkit Template
 * Grid/Masonry Layout für Medien-Galerie mit Lightbox Support
 * @var array $elementData
 */

$layout = $elementData['layout'] ?? 'grid';
$columns = intval($elementData['columns'] ?? 3);
$columnsTablet = intval($elementData['columns_tablet'] ?? 2);
$columnsMobile = intval($elementData['columns_mobile'] ?? 1);
$aspectRatio = $elementData['aspect_ratio'] ?? 'auto';
$gap = $elementData['gap'] ?? 'medium';
$lightbox = !empty($elementData['lightbox']);
$mediaCaptionFallback = !empty($elementData['media_caption_fallback']);
$items = $elementData['items'] ?? [];

// Section-Einstellungen
$sectionBg = $elementData['section_bg'] ?? '';
$sectionBgImage = $elementData['section_bg_image'] ?? '';
$sectionPadding = $elementData['section_padding'] ?? '';
$containerWidth = $elementData['container_width'] ?? 'uk-container';
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

if (empty($items)) {
    return;
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

// Grid Klassen
$gridClasses = ['uk-grid'];

// Gap für UIkit
$gapMap = [
    'collapse' => 'uk-grid-collapse',
    'small' => 'uk-grid-small',
    'medium' => '',
    'large' => 'uk-grid-large'
];
if (isset($gapMap[$gap]) && $gapMap[$gap]) {
    $gridClasses[] = $gapMap[$gap];
}

// Responsive Width Classes
if ($layout !== 'featured' && $layout !== 'logowall') {
    $gridClasses[] = 'uk-child-width-1-' . $columnsMobile;
    $gridClasses[] = 'uk-child-width-1-' . $columnsTablet . '@s';
    $gridClasses[] = 'uk-child-width-1-' . $columns . '@m';
} elseif ($layout === 'logowall') {
    $gridClasses[] = 'uk-child-width-1-2';
    $gridClasses[] = 'uk-child-width-1-4@s';
    $gridClasses[] = 'uk-child-width-1-6@m';
}

// Masonry mit UIkit's nativem Masonry
$gridAttrs = 'uk-grid';
if ($layout === 'masonry') {
    $gridAttrs = 'uk-grid="masonry: true"';
    $gridClasses[] = 'uk-grid-match';
}

if ($layout === 'logowall') {
    $gridClasses[] = 'uk-flex-middle uk-flex-center';
}

$gridClassStr = implode(' ', $gridClasses);

// Aspect Ratio Mapping
$ratioMap = [
    '16:9' => '16-9',
    '4:3' => '4-3',
    '1:1' => '1-1',
    '3:2' => '3-2',
    'auto' => ''
];
$ratioClass = isset($ratioMap[$aspectRatio]) && $ratioMap[$aspectRatio] ? 'uk-cover-container' : '';

// Lightbox ID
$lightboxId = 'gallery-' . uniqid();
?>

<?= $wrapper->parse('kb_elements/wrapper.php') ?>

<div class="gallery-element">
    <div class="<?= $gridClassStr ?>" <?= $gridAttrs ?><?php if ($lightbox): ?> uk-lightbox="animation: slide"<?php endif; ?>>
        <?php foreach ($items as $index => $item): ?>
            <?php 
            $media = $item['media'] ?? '';
            $caption = $item['caption'] ?? '';
            $altTextManual = $item['alt_text'] ?? '';
            $linkUrl = $item['link_url'] ?? '';
            
            if (!$media) continue;
            
            // Medienpool Informationen holen
            $mediaPoolTitle = '';
            $mediaPoolAlt = '';
            $rexMedia = rex_media::get($media);
            if ($rexMedia) {
                $mediaPoolTitle = $rexMedia->getTitle();
                $mediaPoolAlt = $rexMedia->getValue('med_description') ?: $mediaPoolTitle;
            }

            // Bildunterschrift Priorität: Manuell -> Medienpool (wenn Fallback check) -> Leer
            $displayCaption = $caption ?: ($mediaCaptionFallback ? $mediaPoolTitle : '');
            
            // Alt-Text Priorität: Manuell -> Medienpool Alt -> Medienpool Titel -> Filename
            $finalAlt = $altTextManual ?: ($mediaPoolAlt ?: $media);

            // Media-Typ bestimmen
            $isImage = \KLXM\YFormContentBuilder\Helper::isImage($media);
            $isVideo = \KLXM\YFormContentBuilder\Helper::isVideo($media);
            
            // Originalbild für Lightbox, Thumbnail für Grid
            $fullImageUrl = rex_url::media($media);
            
            // MediaManager Typ auswählen
            $mmType = ($aspectRatio === 'auto' || $layout === 'featured' && $index === 0) ? 'gallery_resize' : 'gallery_thumb';
            $thumbUrl = $isImage ? rex_media_manager::getUrl($mmType, $media) : '';
            
            // Item Width
            $itemWidthClass = '';
            if ($layout === 'featured') {
                if ($index === 0) {
                    $itemWidthClass = 'uk-width-1-1';
                } else {
                    $itemWidthClass = 'uk-width-1-' . $columnsMobile;
                    $itemWidthClass .= ' uk-width-1-' . $columnsTablet . '@s';
                    $itemWidthClass .= ' uk-width-1-' . $columns . '@m';
                }
            }
            ?>
            
            <?php
            $wrapperClasses = [];
            if ($itemWidthClass) $wrapperClasses[] = trim($itemWidthClass);
            if ($layout === 'logowall') $wrapperClasses[] = 'uk-transition-toggle';
            $wrapperClassStr = count($wrapperClasses) ? ' class="' . implode(' ', $wrapperClasses) . '"' : '';
            ?>
            
            <div<?= $wrapperClassStr ?>>
                <?php if ($isImage): ?>
                    <?php 
                    $linkClasses = ['uk-display-block', 'uk-link-reset'];
                    $linkClassStr = implode(' ', $linkClasses);
                    ?>

                    <?php if ($lightbox && empty($linkUrl)): ?>
                        <!-- Lightbox Link -->
                        <a href="<?= $fullImageUrl ?>" data-caption="<?= rex_escape($displayCaption) ?>" class="<?= $linkClassStr ?>">
                            <span class="uk-hidden"><?= rex_escape($finalAlt) ?></span>
                    <?php elseif ($linkUrl): ?>
                        <!-- Custom Link -->
                        <a href="<?= rex_escape($linkUrl) ?>" class="<?= $linkClassStr ?>">
                            <span class="uk-hidden"><?= rex_escape($finalAlt) ?></span>
                    <?php endif; ?>
                    
                    <?php if ($layout === 'logowall'): ?>
                        <!-- Logo Wall Item -->
                        <div class="uk-text-center uk-flex uk-flex-column uk-flex-middle uk-flex-center" style="min-height: 120px;">
                            <div class="uk-flex uk-flex-middle uk-flex-center uk-flex-1">
                                <img src="<?= $thumbUrl ?>" alt="<?= rex_escape($finalAlt) ?>" style="max-height: 80px; max-width: 100%; width: auto; height: auto; transition: transform 0.3s ease-in-out; display: inline-block;" class="uk-transition-opaque uk-transition-scale-up">
                            </div>
                            <?php if ($displayCaption): ?>
                                <p class="uk-text-meta uk-margin-small-top uk-margin-remove-bottom"><?= rex_escape($displayCaption) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($layout === 'featured' && $index === 0): ?>
                        <!-- Featured Image Presentation -->
                        <div class="uk-margin-medium-bottom">
                            <div class="uk-inline-clip uk-transition-toggle uk-width-1-1">
                                <img src="<?= $fullImageUrl ?>" alt="<?= rex_escape($finalAlt) ?>" class="uk-width-1-1">
                                <?php if ($displayCaption): ?>
                                    <div class="uk-overlay uk-overlay-primary uk-position-bottom">
                                        <p class="uk-margin-remove"><?= rex_escape($displayCaption) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Standard Card Item -->
                        <div class="uk-card uk-card-default uk-card-hover uk-overflow-hidden">
                            <?php if ($aspectRatio !== 'auto'): ?>
                                <!-- Fixed Aspect Ratio -->
                                <div class="uk-inline-clip uk-transition-toggle" style="width: 100%;">
                                    <canvas width="<?= explode(':', $aspectRatio)[0] * 100 ?>" height="<?= explode(':', $aspectRatio)[1] * 100 ?>"></canvas>
                                    <img src="<?= $thumbUrl ?>" alt="<?= rex_escape($finalAlt) ?>" class="uk-transition-opaque uk-transition-scale-up" uk-cover>
                                </div>
                            <?php else: ?>
                                <!-- Auto Aspect Ratio -->
                                <div class="uk-inline-clip uk-transition-toggle">
                                    <img src="<?= $thumbUrl ?>" alt="<?= rex_escape($finalAlt) ?>" class="uk-width-1-1" style="transition: transform 0.3s ease;">
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($displayCaption): ?>
                                <div class="uk-card-body uk-padding-small">
                                    <p class="uk-text-small uk-margin-remove"><?= rex_escape($displayCaption) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (($lightbox && empty($linkUrl)) || $linkUrl): ?>
                        </a>
                    <?php endif; ?>
                    
                <?php elseif ($isVideo): ?>
                    <!-- Video -->
                    <div class="uk-card uk-card-default uk-overflow-hidden">
                        <div class="uk-inline-clip uk-transition-toggle uk-light" tabindex="0">
                            <?php if ($aspectRatio !== 'auto' && !($layout === 'featured' && $index === 0)): ?>
                                <canvas width="<?= explode(':', $aspectRatio)[0] * 100 ?>" height="<?= explode(':', $aspectRatio)[1] * 100 ?>"></canvas>
                                <video class="uk-transition-opaque" preload="metadata" playsinline uk-cover>
                                    <source src="<?= rex_url::media($media) ?>" type="video/<?= strtolower(pathinfo($media, PATHINFO_EXTENSION)) ?>">
                                </video>
                            <?php else: ?>
                                <video class="uk-width-1-1" preload="metadata" playsinline controls>
                                    <source src="<?= rex_url::media($media) ?>" type="video/<?= strtolower(pathinfo($media, PATHINFO_EXTENSION)) ?>">
                                </video>
                            <?php endif; ?>
                            
                            <?php if ($aspectRatio !== 'auto' && !($layout === 'featured' && $index === 0)): ?>
                                <!-- Play Button Overlay -->
                                <div class="uk-position-center">
                                    <a class="uk-icon-button" uk-icon="icon: play; ratio: 2" href="<?= rex_url::media($media) ?>" uk-lightbox="video-autoplay: true"></a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($displayCaption): ?>
                            <div class="uk-card-body uk-padding-small">
                                <p class="uk-text-small uk-margin-remove"><?= rex_escape($displayCaption) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>