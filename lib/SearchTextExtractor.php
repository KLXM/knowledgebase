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

    /**
     * @var list<string>
     */
    private const TEXT_LIKE_KEYS = [
        'title',
        'heading',
        'headline',
        'subheadline',
        'text',
        'content',
        'intro',
        'lead',
        'description',
        'caption',
        'summary',
        'quote',
        'body',
        'term',
        'question',
        'answer',
        'name',
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
            if ('' !== $candidate && self::shouldIndexValue($key, $candidate)) {
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

    private static function shouldIndexValue(string $key, string $value): bool
    {
        if (in_array($key, self::IGNORED_KEYS, true)) {
            return false;
        }

        if (self::isTextLikeKey($key)) {
            return true;
        }

        if (self::looksTechnicalValue($value)) {
            return false;
        }

        // Fallback: Unbekannte Felder nur indexieren, wenn es nach echtem Fließtext aussieht.
        return preg_match('/\s/u', $value) === 1 && preg_match('/[\p{L}]{3,}/u', $value) === 1;
    }

    private static function isTextLikeKey(string $key): bool
    {
        $normalizedKey = trim(mb_strtolower($key));
        if ($normalizedKey === '') {
            return false;
        }

        foreach (self::TEXT_LIKE_KEYS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private static function looksTechnicalValue(string $value): bool
    {
        $normalized = trim(mb_strtolower($value));
        if ($normalized === '') {
            return true;
        }

        if (str_starts_with($normalized, 'template not found:')) {
            return true;
        }

        // Typische Utility-/Framework-Tokens, Slugs, Optionen oder Dateinamen.
        if (preg_match('/^(uk|fa|col|row|container|btn|alert|badge|shadow|ratio|grid|flex|text|bg|m|p)[-_a-z0-9\s]*$/u', $normalized) === 1) {
            return true;
        }

        if (preg_match('/^[a-z0-9._\/-]+$/u', $normalized) === 1 && preg_match('/\s/u', $normalized) !== 1) {
            return true;
        }

        return false;
    }
}