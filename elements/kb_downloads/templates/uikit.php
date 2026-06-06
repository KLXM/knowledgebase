<?php
/**
 * Downloads Element - UIkit Template
 * 
 * @var array $elementData Element-Daten aus dem Content Builder
 * @var string|null $closeType Optional: 'open' oder 'close' für Section-Elemente
 */

// Daten extrahieren
$headline = $elementData['headline'] ?? '';
$description = $elementData['description'] ?? '';
$layout = $elementData['layout'] ?? 'list';
$cardStyle = $elementData['card_style'] ?? 'uk-card-default';
$showFilesize = !empty($elementData['show_filesize']);
$showFiletype = !empty($elementData['show_filetype']);
$showIcon = !empty($elementData['show_icon']);
$iconStyle = $elementData['icon_style'] ?? 'auto';
$showPreview = !empty($elementData['show_preview']);
$openInNewTab = !empty($elementData['open_in_new_tab']);
$items = $elementData['items'] ?? [];

// Grid-Einstellungen
$columns = $elementData['columns'] ?? '3';
$columnsTablet = $elementData['columns_tablet'] ?? '2';
$columnsMobile = $elementData['columns_mobile'] ?? '1';
$gap = $elementData['gap'] ?? 'medium';
$matchHeight = !empty($elementData['match_height']);

// Section-Einstellungen
$sectionBg = $elementData['section_bg'] ?? '';
$sectionBgImage = $elementData['section_bg_image'] ?? '';
$sectionPadding = $elementData['section_padding'] ?? '';
$containerWidth = $elementData['container_width'] ?? 'uk-container';
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

// Keine Items? Nichts ausgeben
if (empty($items)) {
    return;
}

// Helper-Funktion für Datei-Icons
$getFileIcon = function($filename, $iconStyle, $customIcon = '') {
    if ($iconStyle === 'custom' && !empty($customIcon)) {
        return ['type' => 'custom', 'icon' => $customIcon];
    }
    
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // UIkit Icons (bevorzugt im UIkit Template)
    $ukIcons = [
        'pdf' => 'file-pdf',
        'doc' => 'file-text',
        'docx' => 'file-text',
        'odt' => 'file-text',
        'rtf' => 'file-text',
        'xls' => 'table',
        'xlsx' => 'table',
        'ods' => 'table',
        'csv' => 'table',
        'ppt' => 'desktop',
        'pptx' => 'desktop',
        'odp' => 'desktop',
        'zip' => 'album',
        'rar' => 'album',
        '7z' => 'album',
        'tar' => 'album',
        'gz' => 'album',
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'webp' => 'image',
        'svg' => 'image',
        'bmp' => 'image',
        'tiff' => 'image',
        'mp3' => 'play-circle',
        'wav' => 'play-circle',
        'ogg' => 'play-circle',
        'flac' => 'play-circle',
        'aac' => 'play-circle',
        'mp4' => 'video-camera',
        'webm' => 'video-camera',
        'mov' => 'video-camera',
        'avi' => 'video-camera',
        'mkv' => 'video-camera',
        'txt' => 'file-text',
        'md' => 'file-text',
        'json' => 'code',
        'xml' => 'code',
        'html' => 'code',
        'css' => 'code',
        'js' => 'code',
        'php' => 'code',
        'sql' => 'database',
        'exe' => 'cog',
        'dmg' => 'cog',
        'iso' => 'album',
    ];
    
    // Font Awesome Icons (Fallback)
    $faIcons = [
        'pdf' => 'fa-file-pdf-o',
        'doc' => 'fa-file-word-o',
        'docx' => 'fa-file-word-o',
        'xls' => 'fa-file-excel-o',
        'xlsx' => 'fa-file-excel-o',
        'ppt' => 'fa-file-powerpoint-o',
        'pptx' => 'fa-file-powerpoint-o',
        'zip' => 'fa-file-archive-o',
        'rar' => 'fa-file-archive-o',
        '7z' => 'fa-file-archive-o',
        'jpg' => 'fa-file-image-o',
        'jpeg' => 'fa-file-image-o',
        'png' => 'fa-file-image-o',
        'gif' => 'fa-file-image-o',
        'webp' => 'fa-file-image-o',
        'svg' => 'fa-file-image-o',
        'mp3' => 'fa-file-audio-o',
        'wav' => 'fa-file-audio-o',
        'mp4' => 'fa-file-video-o',
        'webm' => 'fa-file-video-o',
        'mov' => 'fa-file-video-o',
        'txt' => 'fa-file-text-o',
        'csv' => 'fa-file-text-o',
    ];
    
    // Bei 'auto' oder 'uikit' -> UIkit Icons verwenden (da wir im UIkit Template sind)
    if ($iconStyle === 'auto' || $iconStyle === 'uikit') {
        return ['type' => 'uikit', 'icon' => $ukIcons[$ext] ?? 'download'];
    }
    
    // Font Awesome
    return ['type' => 'fa', 'icon' => 'fa ' . ($faIcons[$ext] ?? 'fa-file-o')];
};

// Helper-Funktion für Icon-HTML-Ausgabe
$renderIcon = function($iconData, $size = 'normal') {
    if ($iconData['type'] === 'uikit') {
        $ratio = $size === 'large' ? '3' : ($size === 'small' ? '1' : '1.5');
        return '<span uk-icon="icon: ' . rex_escape($iconData['icon']) . '; ratio: ' . $ratio . '" class="uk-text-primary"></span>';
    } elseif ($iconData['type'] === 'custom') {
        $sizeClass = $size === 'large' ? 'fa-4x' : ($size === 'small' ? '' : 'fa-2x');
        return '<i class="' . rex_escape($iconData['icon']) . ' ' . $sizeClass . ' uk-text-muted"></i>';
    } else {
        $sizeClass = $size === 'large' ? 'fa-4x' : ($size === 'small' ? '' : 'fa-2x');
        return '<i class="' . rex_escape($iconData['icon']) . ' ' . $sizeClass . ' uk-text-muted"></i>';
    }
};

// Helper-Funktion für Dateigröße
$formatFilesize = function($filename) {
    $path = rex_path::media($filename);
    if (!file_exists($path)) {
        return '';
    }
    $bytes = filesize($path);
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
};

// Helper für Dateityp-Label
$getFiletypeLabel = function($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $labels = [
        'pdf' => 'PDF',
        'doc' => 'Word',
        'docx' => 'Word',
        'xls' => 'Excel',
        'xlsx' => 'Excel',
        'ppt' => 'PowerPoint',
        'pptx' => 'PowerPoint',
        'zip' => 'ZIP',
        'rar' => 'RAR',
        'jpg' => 'Bild',
        'jpeg' => 'Bild',
        'png' => 'Bild',
        'gif' => 'Bild',
        'mp3' => 'Audio',
        'mp4' => 'Video',
    ];
    return $labels[$ext] ?? strtoupper($ext);
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

// Grid-Klassen basierend auf Spalten
$gridClass = 'uk-child-width-1-' . $columnsMobile;
$gridClass .= ' uk-child-width-1-' . $columnsTablet . '@s';
$gridClass .= ' uk-child-width-1-' . $columns . '@m';

// Gap-Klasse
$gapClass = $gap === 'collapse' ? '' : 'uk-grid-' . $gap;
?>

<?= $wrapper->parse('kb_elements/wrapper.php') ?>
    
    <?php if ($headline || $description): ?>
        <div class="uk-margin-medium-bottom">
            <?php if ($headline): ?>
                <h2 class="uk-heading-line"><span><?= rex_escape($headline) ?></span></h2>
            <?php endif; ?>
            <?php if ($description): ?>
                <p class="uk-text-lead"><?= rex_escape($description) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($layout === 'list' || $layout === 'compact'): ?>
        <!-- Listen-Ansicht -->
        <ul class="uk-list <?= $layout === 'compact' ? 'uk-list-divider' : 'uk-list-large uk-list-divider' ?>">
            <?php foreach ($items as $item): ?>
                <?php
                $file = $item['file'] ?? '';
                if (!$file) continue;
                
                $title = $item['title'] ?? $file;
                $desc = $item['description'] ?? '';
                $category = $item['category'] ?? '';
                $badge = $item['badge'] ?? '';
                $badgeColor = $item['badge_color'] ?? 'primary';
                $icon = $getFileIcon($file, $iconStyle, $item['custom_icon'] ?? '');
                $filesize = $showFilesize ? $formatFilesize($file) : '';
                $filetype = $showFiletype ? $getFiletypeLabel($file) : '';
                $downloadUrl = rex_url::media($file);
                $target = $openInNewTab ? '_blank' : '_self';
                ?>
                <li>
                    <a href="<?= $downloadUrl ?>" target="<?= $target ?>" class="uk-link-reset uk-flex uk-flex-middle" download>
                        <?php if ($showIcon): ?>
                            <span class="uk-margin-right">
                                <?= $renderIcon($icon, 'normal') ?>
                            </span>
                        <?php endif; ?>
                        
                        <div class="uk-flex-1">
                            <div class="uk-text-bold">
                                <?= rex_escape($title) ?>
                                <?php if ($badge): ?>
                                    <span class="uk-label uk-label-<?= rex_escape($badgeColor) ?> uk-margin-small-left"><?= rex_escape($badge) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($desc): ?>
                                <div class="uk-text-small uk-text-muted"><?= rex_escape($desc) ?></div>
                            <?php endif; ?>
                            
                            <div class="uk-text-meta">
                                <?php if ($category): ?>
                                    <span class="uk-margin-small-right"><?= rex_escape($category) ?></span>
                                <?php endif; ?>
                                <?php if ($filetype): ?>
                                    <span class="uk-margin-small-right"><?= $filetype ?></span>
                                <?php endif; ?>
                                <?php if ($filesize): ?>
                                    <span><?= $filesize ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <span uk-icon="download" class="uk-margin-left"></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        
    <?php elseif ($layout === 'cards'): ?>
        <!-- Kachel-Ansicht -->
        <div class="<?= $gridClass ?> <?= $gapClass ?>" uk-grid<?= $matchHeight ? ' uk-height-match="target: > div > a > .uk-card"' : '' ?>>
            <?php foreach ($items as $item): ?>
                <?php
                $file = $item['file'] ?? '';
                if (!$file) continue;
                
                $title = $item['title'] ?? $file;
                $desc = $item['description'] ?? '';
                $category = $item['category'] ?? '';
                $badge = $item['badge'] ?? '';
                $badgeColor = $item['badge_color'] ?? 'primary';
                $icon = $getFileIcon($file, $iconStyle, $item['custom_icon'] ?? '');
                $filesize = $showFilesize ? $formatFilesize($file) : '';
                $filetype = $showFiletype ? $getFiletypeLabel($file) : '';
                $downloadUrl = rex_url::media($file);
                $target = $openInNewTab ? '_blank' : '_self';
                $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                ?>
                <div>
                    <a href="<?= $downloadUrl ?>" target="<?= $target ?>" class="uk-link-reset" download>
                        <div class="uk-card <?= rex_escape($cardStyle ?: 'uk-card-default') ?> uk-card-body uk-card-hover">
                            
                            <?php if ($badge): ?>
                                <div class="uk-card-badge uk-label uk-label-<?= rex_escape($badgeColor) ?>"><?= rex_escape($badge) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($showPreview && $isImage): ?>
                                <div class="uk-text-center uk-margin-bottom">
                                    <?php if (rex_addon::get('media_manager')->isAvailable()): ?>
                                        <img src="<?= rex_media_manager::getUrl('yform_content_builder_preview', $file) ?>" alt="<?= rex_escape($title) ?>" class="uk-border-rounded" style="max-width: 64px; max-height: 64px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="<?= rex_url::media($file) ?>" alt="<?= rex_escape($title) ?>" class="uk-border-rounded" style="max-width: 64px; max-height: 64px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($showIcon): ?>
                                <div class="uk-text-center uk-margin-bottom">
                                    <?= $renderIcon($icon, 'large') ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="uk-card-title uk-margin-small-bottom"><?= rex_escape($title) ?></h3>
                            
                            <?php if ($desc): ?>
                                <p class="uk-text-small uk-text-muted"><?= rex_escape($desc) ?></p>
                            <?php endif; ?>
                            
                            <div class="uk-text-meta uk-margin-small-top">
                                <?php if ($category): ?>
                                    <span class="uk-margin-small-right"><?= rex_escape($category) ?></span>
                                <?php endif; ?>
                                <?php if ($filetype): ?>
                                    <span class="uk-margin-small-right"><?= $filetype ?></span>
                                <?php endif; ?>
                                <?php if ($filesize): ?>
                                    <span><?= $filesize ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="uk-margin-top">
                                <span class="uk-button uk-button-text">
                                    <span uk-icon="download"></span> Download
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php elseif ($layout === 'table'): ?>
        <!-- Tabellen-Ansicht -->
        <div class="uk-overflow-auto">
            <table class="uk-table uk-table-hover uk-table-divider">
                <thead>
                    <tr>
                        <?php if ($showIcon): ?><th class="uk-table-shrink"></th><?php endif; ?>
                        <th>Datei</th>
                        <?php if ($showFiletype): ?><th class="uk-table-shrink">Typ</th><?php endif; ?>
                        <?php if ($showFilesize): ?><th class="uk-table-shrink">Größe</th><?php endif; ?>
                        <th class="uk-table-shrink"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $file = $item['file'] ?? '';
                        if (!$file) continue;
                        
                        $title = $item['title'] ?? $file;
                        $desc = $item['description'] ?? '';
                        $badge = $item['badge'] ?? '';
                        $badgeColor = $item['badge_color'] ?? 'primary';
                        $icon = $getFileIcon($file, $iconStyle, $item['custom_icon'] ?? '');
                        $filesize = $showFilesize ? $formatFilesize($file) : '';
                        $filetype = $showFiletype ? $getFiletypeLabel($file) : '';
                        $downloadUrl = rex_url::media($file);
                        $target = $openInNewTab ? '_blank' : '_self';
                        ?>
                        <tr>
                            <?php if ($showIcon): ?>
                                <td>
                                    <?= $renderIcon($icon, 'small') ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <strong><?= rex_escape($title) ?></strong>
                                <?php if ($badge): ?>
                                    <span class="uk-label uk-label-<?= rex_escape($badgeColor) ?> uk-margin-small-left"><?= rex_escape($badge) ?></span>
                                <?php endif; ?>
                                <?php if ($desc): ?>
                                    <div class="uk-text-small uk-text-muted"><?= rex_escape($desc) ?></div>
                                <?php endif; ?>
                            </td>
                            <?php if ($showFiletype): ?>
                                <td class="uk-text-nowrap"><?= $filetype ?></td>
                            <?php endif; ?>
                            <?php if ($showFilesize): ?>
                                <td class="uk-text-nowrap"><?= $filesize ?></td>
                            <?php endif; ?>
                            <td>
                                <a href="<?= $downloadUrl ?>" target="<?= $target ?>" class="uk-button uk-button-small uk-button-primary" download>
                                    <span uk-icon="download"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
