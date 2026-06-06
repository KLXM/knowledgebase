<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

final class SearchTextExtractor
{
    /**
     * @var list<string>
     */
    private const IGNORED_KEYS = [
        'id',
        'type',
        'class',
        'css_class',
        'image',
        'images',
        'media',
        'file',
        'files',
        'link_url',
        'url',
        'anchor',
        'section_bg_image',
        'background_image',
        'icon',
    ];

    public static function extractFromContentBuilder(string $value): string
    {
        $trimmed = trim($value);
        if ('' === $trimmed) {
            return '';
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return self::normalize(strip_tags($trimmed));
        }

        $parts = [];
        self::collect($decoded, $parts);

        return self::normalize(implode(' ', $parts));
    }

    public static function normalize(string $value): string
    {
        $normalized = strip_tags($value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return is_string($normalized) ? trim($normalized) : trim($value);
    }

    /**
     * @param mixed $value
     * @param list<string> $parts
     */
    private static function collect(mixed $value, array &$parts, string $key = ''): void
    {
        if (is_string($value)) {
            $candidate = self::normalize($value);
            if ('' !== $candidate && !in_array($key, self::IGNORED_KEYS, true)) {
                $parts[] = $candidate;
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as $childKey => $childValue) {
            $childKeyString = is_string($childKey) ? $childKey : '';
            self::collect($childValue, $parts, $childKeyString);
        }
    }
}