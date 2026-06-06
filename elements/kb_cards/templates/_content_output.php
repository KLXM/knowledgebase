<?php
/**
 * Content Output Helper für Cards
 * Variablen werden vom Parent-Template bereitgestellt:
 * $title, $subtitle, $text, $badge, $badgeColor, $href, $linkText, $linkCard, $isTransparent, $item
 */

// Bei transparent Cards: seitliche Paddings entfernen
$transparentPadding = isset($isTransparent) && $isTransparent ? ' uk-padding-remove-left uk-padding-remove-right' : '';
// Body zusätzlich: auch top-Padding entfernen
$transparentBodyPadding = isset($isTransparent) && $isTransparent ? ' uk-padding-remove-left uk-padding-remove-right uk-padding-remove-top' : '';

// Standard-Feldnamen die NICHT als Extra-Felder zählen
$standardFields = [
    'layout', 'image', 'image_alt', 'image_decorative', 'image_title', 'media_width', 'media_ratio',
    'media_lightbox', 'media_cover', 'media_vertical_align', 'video_display', 'video_controls',
    'title', 'text', 'subtitle', 'badge', 'badge_color',
    'card_width', 'card_width_mobile', 'card_width_tablet', 'card_style_override', 'card_shadow_override',
    'link_type', 'link_url', 'link_internal', 'link_text', 'link_button_style', 'link_button_align', 'link_card',
    'animation'
];

// Extra-Felder erkennen (alles was nicht in Standard-Feldliste)
$extraFields = [];
if (is_array($item)) {
    foreach ($item as $key => $value) {
        if (!in_array($key, $standardFields) && !empty($value)) {
            $extraFields[$key] = $value;
        }
    }
}

// Extra-Felder in HTML konvertieren wenn GetOutput vorhanden
$extraFieldsHtml = '';
if (!empty($extraFields)) {
    // Versuche die GetOutput Methode von CardsRepeaterExtra zu nutzen
    if (class_exists('CardsRepeaterExtra') && method_exists('CardsRepeaterExtra', 'GetOutput')) {
        $extraFieldsHtml = CardsRepeaterExtra::GetOutput($item);
    } else {
        // Fallback: Einfache Ausgabe der rohen Werte
        foreach ($extraFields as $fieldKey => $fieldValue) {
            $extraFieldsHtml .= '<div class="extra-field extra-field-' . rex_escape($fieldKey) . '">';
            $extraFieldsHtml .= $fieldValue;
            $extraFieldsHtml .= '</div>';
        }
    }
}
?>

<?php if ($isHorizontal): ?>
<div class="uk-card-content-wrapper<?= $contentWrapperClass ?? '' ?>">
<?php endif; ?>
    <?php if (!empty($badge)): ?>
        <div class="uk-card-badge uk-label uk-label-<?= rex_escape($badgeColor) ?>"><?= rex_escape($badge) ?></div>
    <?php endif; ?>
    <?php if (!empty($title) || !empty($subtitle)): ?>
        <?php 
        $headerPaddingClass = $transparentPadding;
        if ($isHorizontal && !$isTransparent) {
            $headerPaddingClass .= ' uk-border-remove';
        }
        ?>
        <div class="uk-card-header<?= $headerPaddingClass ?>">
            <?php if (!empty($title)): ?>
                <h3 class="uk-card-title"><?= rex_escape($title) ?></h3>
            <?php endif; ?>
            <?php if (!empty($subtitle)): ?>
                <p class="uk-text-meta uk-margin-remove-top"><?= rex_escape($subtitle) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($extraFields)): ?>
        <?php if (!empty($extraFieldsHtml)): ?>
            <div class="uk-card-body">
                <?= $extraFieldsHtml ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($text)): ?>
        <?php 
        $bodyFlexClass = '';
        // In horizontalen Layouts mit vertikaler Ausrichtung darf der Body nicht flex-1 sein,
        // sonst funktioniert das Zentrieren der gesamten Gruppe nicht.
        if ($matchHeight && !$isHorizontal) {
            $bodyFlexClass = ' uk-flex-1';
        }
        ?>
        <div class="uk-card-body<?= $bodyFlexClass ?><?= $transparentBodyPadding ?>">
            <div class="uk-text"><?= $text ?></div>
        </div>
    <?php endif; ?>

    <?php
        $linkButtonStyle = $item['link_button_style'] ?? 'uk-button-text';
        $linkButtonAlign = $item['link_button_align'] ?? 'uk-text-center';
        $linkButtonAlignClass = $linkButtonAlign ? ' ' . $linkButtonAlign : '';
        $linkIcon = ($linkButtonStyle === 'uk-button-text') ? ' <span uk-icon="chevron-right"></span>' : '';
    ?>
    <?php if ($href && !$linkCard): ?>
        <?php if ($isHorizontal): ?>
            <div class="uk-card-body uk-padding-remove-top<?= $transparentPadding ?>">
                <div class="<?= rex_escape(ltrim($linkButtonAlignClass)) ?>">
                    <a href="<?= $href ?>" class="uk-button <?= rex_escape($linkButtonStyle) ?>">
                        <?= rex_escape($linkText) ?><?= $linkIcon ?>
                    </a>
                </div>
            </div>
        <?php elseif (!$isOverlay): ?>
            <?php $footerPadding = ($isTransparent ?? false) ? ' uk-padding-remove-left uk-padding-remove-right' : ''; ?>
            <div class="uk-card-footer<?= ($matchHeight ?? false) ? ' uk-margin-auto-top' : '' ?><?= $footerPadding ?>">
                <div class="<?= rex_escape(ltrim($linkButtonAlignClass)) ?>">
                    <a href="<?= $href ?>" class="uk-button <?= rex_escape($linkButtonStyle) ?>">
                        <?= rex_escape($linkText) ?><?= $linkIcon ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

<?php if ($isHorizontal): ?>
</div>
<?php endif; ?>


