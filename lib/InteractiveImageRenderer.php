<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_addon;
use rex_escape;
use rex_path;
use rex_url;

class InteractiveImageRenderer
{
    private static bool $assetsRendered = false;

    public static function renderById(int $id): string
    {
        $dataset = \rex_yform_manager_dataset::query(\rex::getTable('knowledgebase_interactive_image'))
            ->where('id', $id)
            ->findOne();

        if (!$dataset instanceof \rex_yform_manager_dataset) {
            return '';
        }

        $type = strtolower(trim((string) $dataset->getValue('type')));
        if ('' === $type) {
            $type = 'marker_map';
        }

        if (!in_array($type, ['marker_map', 'markerbild'], true)) {
            $type = 'marker_map';
        }

        return self::renderAssets() . self::renderMarkerMap($dataset);
    }

    private static function renderMarkerMap(\rex_yform_manager_dataset $dataset): string
    {
        $image = trim((string) $dataset->getValue('image'));
        if ('' === $image) {
            return '';
        }

        $markers = self::parseMarkers((string) $dataset->getValue('markers_json'));
        $datasetId = (int) $dataset->getValue('id');
        $instanceId = 'kb-interactive-' . $datasetId . '-' . uniqid('', false);
        $imageUrl = rex_url::media($image);
        $menuLabel = trim((string) $dataset->getValue('menu_label'));
        if ('' === $menuLabel) {
            $menuLabel = 'Marker-Menü';
        }
        $imageAlt = trim((string) $dataset->getValue('image_alt'));

        $html = '<div class="kb-marker-map" id="' . rex_escape($instanceId) . '">';
        $html .= '<div class="kb-marker-map__figure uk-inline-clip uk-transition-toggle" tabindex="0">';
        $html .= '<img src="' . rex_escape($imageUrl) . '" alt="' . rex_escape($imageAlt) . '" loading="lazy">';

        foreach ($markers as $index => $marker) {
            $number = $index + 1;
            $modalId = $instanceId . '-modal-' . $number;
            $tooltip = 'title: ' . $marker['title'];
            $ariaLabel = sprintf('Marker %d: %s', $number, $marker['title']);

            $html .= '<button type="button" class="kb-marker-map__marker uk-transform-center"';
            $html .= ' style="left:' . rex_escape(number_format($marker['x'], 3, '.', '')) . '%;top:' . rex_escape(number_format($marker['y'], 3, '.', '')) . '%;"';
            $html .= ' data-marker-index="' . rex_escape((string) $number) . '"';
            $html .= ' aria-label="' . rex_escape($ariaLabel) . '"';
            $html .= ' aria-controls="' . rex_escape($modalId) . '"';
            $html .= ' uk-tooltip="' . rex_escape($tooltip) . '"';
            $html .= ' uk-toggle="target: #' . rex_escape($modalId) . '"';
            $html .= '></button>';
        }

        $html .= '</div>';

        if ([] !== $markers) {
            $menuId = $instanceId . '-menu';
            $toggleLabelOpen = $menuLabel . ' anzeigen';

            $html .= '<button type="button" class="kb-marker-map__menu-toggle"';
            $html .= ' aria-controls="' . rex_escape($menuId) . '"';
            $html .= ' aria-expanded="false"';
            $html .= ' data-kb-marker-menu-toggle="#' . rex_escape($menuId) . '"';
            $html .= ' uk-toggle="target: #' . rex_escape($menuId) . '; cls: is-collapsed"';
            $html .= '>' . rex_escape($toggleLabelOpen) . '</button>';

            $html .= '<nav class="kb-marker-map__menu is-collapsed" id="' . rex_escape($menuId) . '" aria-label="' . rex_escape($menuLabel) . '">';
            $html .= '<h3 class="kb-marker-map__menu-title">' . rex_escape($menuLabel) . '</h3>';
            $html .= '<ul class="kb-marker-map__menu-list uk-list uk-list-small">';

            foreach ($markers as $index => $marker) {
                $number = $index + 1;
                $modalId = $instanceId . '-modal-' . $number;

                $html .= '<li>';
                $html .= '<button type="button" class="kb-marker-map__menu-button" uk-toggle="target: #' . rex_escape($modalId) . '">';
                $html .= '<span class="kb-marker-map__menu-number">' . rex_escape((string) $number) . '</span>';
                $html .= '<span>' . rex_escape($marker['title']) . '</span>';
                $html .= '</button>';
                $html .= '</li>';
            }

            $html .= '</ul>';
            $html .= '</nav>';

            foreach ($markers as $index => $marker) {
                $number = $index + 1;
                $modalId = $instanceId . '-modal-' . $number;

                $html .= '<div id="' . rex_escape($modalId) . '" uk-modal>';
                $html .= '<div class="uk-modal-dialog uk-modal-body">';
                $html .= '<button class="uk-modal-close-default" type="button" uk-close></button>';
                $html .= '<h3 class="uk-modal-title">' . rex_escape($marker['title']) . '</h3>';
                $html .= '<div class="kb-marker-map__modal-content">' . \rex_string::sanitizeHtml($marker['content']) . '</div>';
                $html .= self::renderMarkerButton($marker);
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param array{title:string,content:string,button_label:string,button_knowledgebase_id:int,button_article_slug:string,x:float,y:float} $marker
     */
    private static function renderMarkerButton(array $marker): string
    {
        $knowledgebaseId = (int) ($marker['button_knowledgebase_id'] ?? 0);
        $articleSlug = trim((string) ($marker['button_article_slug'] ?? ''));
        $label = trim((string) ($marker['button_label'] ?? ''));

        if ($knowledgebaseId <= 0 || '' === $articleSlug || '' === $label) {
            return '';
        }

        $url = KnowledgebaseUrl::getArticleUrl($knowledgebaseId, $articleSlug);
        if ('' === trim($url)) {
            return '';
        }

        return '<p class="kb-marker-map__modal-actions"><a class="kb-marker-map__modal-button uk-button uk-button-primary" href="' . rex_escape($url) . '">' . rex_escape($label) . '</a></p>';
    }

    private static function renderAssets(): string
    {
        if (self::$assetsRendered) {
            return '';
        }

        self::$assetsRendered = true;
        $cssVersion = self::getAssetVersion('css/knowledgebase.css');
        $jsVersion = self::getAssetVersion('js/knowledgebase.js');

        return '<link rel="stylesheet" href="' . rex_escape(rex_url::addonAssets('knowledgebase', 'css/knowledgebase.css?v=' . $cssVersion)) . '">'
            . '<script src="' . rex_escape(rex_url::addonAssets('knowledgebase', 'js/knowledgebase.js?v=' . $jsVersion)) . '" defer></script>';
    }

    private static function getAssetVersion(string $relativePath): string
    {
        $path = rex_path::addonAssets('knowledgebase', $relativePath);
        $mtime = @filemtime($path);
        if (is_int($mtime)) {
            return (string) $mtime;
        }

        return (string) rex_addon::get('knowledgebase')->getVersion();
    }

    /**
    * @return list<array{title:string,content:string,button_label:string,button_knowledgebase_id:int,button_article_slug:string,x:float,y:float}>
     */
    private static function parseMarkers(string $rawJson): array
    {
        $rawJson = trim($rawJson);
        if ('' === $rawJson) {
            return [];
        }

        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $markers = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ('' === $title) {
                continue;
            }

            $markers[] = [
                'title' => $title,
                'content' => \rex_string::sanitizeHtml((string) ($item['content'] ?? '')),
                'button_label' => trim((string) ($item['buttonLabel'] ?? $item['button_label'] ?? '')),
                'button_knowledgebase_id' => (int) ($item['buttonKnowledgebaseId'] ?? $item['button_knowledgebase_id'] ?? 0),
                'button_article_slug' => trim((string) ($item['buttonArticleSlug'] ?? $item['button_article_slug'] ?? '')),
                'x' => max(0.0, min(100.0, (float) ($item['x'] ?? 50))),
                'y' => max(0.0, min(100.0, (float) ($item['y'] ?? 50))),
            ];
        }

        return $markers;
    }
}
