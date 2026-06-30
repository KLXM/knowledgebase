<?php

declare(strict_types=1);

/**
 * @method int getId()
 */
class rex_data_knowledgebase_interactive_image extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase_interactive_image';

    public function isOnline(): bool
    {
        return (int) $this->getValue('online') === 1;
    }

    public function getType(): string
    {
        $type = trim((string) $this->getValue('type'));

        return '' !== $type ? $type : 'marker_map';
    }

    public function getImage(): string
    {
        return trim((string) $this->getValue('image'));
    }

    public function getImageAlt(): string
    {
        return trim((string) $this->getValue('image_alt'));
    }

    public function getMenuLabel(): string
    {
        $label = trim((string) $this->getValue('menu_label'));

        return '' !== $label ? $label : 'Marker-Menü';
    }

    /**
    * @return list<array{title:string,content:string,button_label:string,button_knowledgebase_id:int,button_article_slug:string,x:float,y:float}>
     */
    public function getMarkers(): array
    {
        $raw = trim((string) $this->getValue('markers_json'));
        if ('' === $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
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
