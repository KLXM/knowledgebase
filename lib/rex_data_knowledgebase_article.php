<?php

declare(strict_types=1);

use KLXM\YFormContentBuilder\Helper;

/**
 * @method int getId()
 */
class rex_data_knowledgebase_article extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase_article';

    /**
        * @return rex_yform_manager_collection<static>
     */
    public static function findOnlineByKnowledgebase(int $knowledgebaseId): rex_yform_manager_collection
    {
        return self::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('online', 1)
            ->orderBy('priority')
            ->orderBy('title')
            ->find();
    }

    public static function findOnlineBySlug(int $knowledgebaseId, string $slug): ?self
    {
        $article = self::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('slug', $slug)
            ->where('online', 1)
            ->findOne();

        return $article instanceof self ? $article : null;
    }

    public function getNavLabel(): string
    {
        $navTitle = trim((string) $this->getValue('nav_title'));

        return '' !== $navTitle ? $navTitle : (string) $this->getValue('title');
    }

    public function renderContent(): string
    {
        $content = trim((string) $this->getValue('content'));
        if ('' === $content) {
            return '';
        }

        if (!rex_addon::get('yform_content_builder')->isAvailable()) {
            return nl2br(rex_escape($content));
        }

        return Helper::render($content, 'uikit');
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return array_map(
            static fn (array $entry): string => $entry['label'],
            $this->getTagEntries(),
        );
    }

    /**
     * @return list<array{value:string,label:string,color:string}>
     */
    public function getTagEntries(): array
    {
        $raw = trim((string) $this->getValue('tags'));
        if ($raw === '') {
            return [];
        }

        $parts = [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (is_array($entry)) {
                    $text = trim((string) ($entry['text'] ?? ''));
                    if ($text !== '') {
                        $parts[] = [
                            'label' => $text,
                            'color' => self::normalizeTagColor((string) ($entry['color'] ?? '')),
                        ];
                    }
                    continue;
                }

                if (is_scalar($entry)) {
                    $text = trim((string) $entry);
                    if ($text !== '') {
                        $parts[] = [
                            'label' => $text,
                            'color' => '',
                        ];
                    }
                }
            }
        }

        if ([] === $parts) {
            $fallbackParts = preg_split('/[,;\n]+/u', $raw) ?: [];
            foreach ($fallbackParts as $part) {
                $text = trim((string) $part);
                if ($text === '') {
                    continue;
                }

                $parts[] = [
                    'label' => $text,
                    'color' => '',
                ];
            }
        }

        $seen = [];
        $result = [];

        foreach ($parts as $part) {
            $tag = trim((string) ($part['label'] ?? ''));
            if ($tag === '') {
                continue;
            }

            $normalized = self::normalizeTag($tag);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $result[] = [
                'value' => $normalized,
                'label' => $tag,
                'color' => self::normalizeTagColor((string) ($part['color'] ?? '')),
            ];
        }

        return $result;
    }

    public function hasTag(string $tag): bool
    {
        $needle = self::normalizeTag($tag);
        if ($needle === '') {
            return false;
        }

        foreach ($this->getTagEntries() as $current) {
            if ($current['value'] === $needle) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeTag(string $tag): string
    {
        $normalized = trim($tag);
        if ($normalized === '') {
            return '';
        }

        return function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
    }

    private static function normalizeTagColor(string $color): string
    {
        $normalized = trim($color);
        if ($normalized === '') {
            return '';
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $normalized) === 1 ? strtolower($normalized) : '';
    }
}