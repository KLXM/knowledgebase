<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_sql;
use Throwable;

final class SearchService
{
    /**
    * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string,is_recent:bool}>
     */
    public static function search(int $knowledgebaseId, string $query, int $limit = 8): array
    {
        $normalizedQuery = trim($query);
        if ($knowledgebaseId <= 0 || '' === $normalizedQuery) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        try {
            $results = self::searchFulltext($knowledgebaseId, $normalizedQuery, $limit);
            if ([] !== $results) {
                return $results;
            }
        } catch (Throwable $exception) {
            \rex_logger::logException($exception);
        }

        return self::searchLike($knowledgebaseId, $normalizedQuery, $limit);
    }

    /**
        * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string,is_recent:bool}>
     */
    private static function searchFulltext(int $knowledgebaseId, string $query, int $limit): array
    {
        $booleanQuery = self::toBooleanModeQuery($query);
        if ('' === $booleanQuery) {
            return [];
        }

        $likeTerm = '%' . $query . '%';

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id, title, nav_title, slug, intro, createdate, updatedate, search_text, content, '
            . 'MATCH(title, nav_title, intro, search_text) AGAINST (:term IN BOOLEAN MODE) AS relevance, '
            . '(CASE WHEN title LIKE :like_term THEN 40 ELSE 0 END '
            . ' + CASE WHEN nav_title LIKE :like_term THEN 30 ELSE 0 END '
            . ' + CASE WHEN intro LIKE :like_term THEN 20 ELSE 0 END '
            . ' + CASE WHEN search_text LIKE :like_term THEN 10 ELSE 0 END) AS boost '
            . 'FROM ' . rex::getTable('knowledgebase_article') . ' '
            . 'WHERE knowledgebase_id = :knowledgebase_id AND online = 1 '
            . 'AND MATCH(title, nav_title, intro, search_text) AGAINST (:term IN BOOLEAN MODE) '
            . 'ORDER BY relevance DESC, boost DESC, priority ASC, title ASC '
            . 'LIMIT ' . $limit,
            [
                'knowledgebase_id' => $knowledgebaseId,
                'term' => $booleanQuery,
                'like_term' => $likeTerm,
            ],
        );

        return self::hydrateResults($sql, $query);
    }

    /**
        * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string,is_recent:bool}>
     */
    private static function searchLike(int $knowledgebaseId, string $query, int $limit): array
    {
        $likeTerm = '%' . $query . '%';
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id, title, nav_title, slug, intro, createdate, updatedate, search_text, content, '
            . '(CASE WHEN title LIKE :like_term THEN 40 ELSE 0 END '
            . ' + CASE WHEN nav_title LIKE :like_term THEN 30 ELSE 0 END '
            . ' + CASE WHEN intro LIKE :like_term THEN 20 ELSE 0 END '
            . ' + CASE WHEN search_text LIKE :like_term THEN 10 ELSE 0 END '
            . ' + CASE WHEN content LIKE :like_term THEN 5 ELSE 0 END) AS boost '
            . 'FROM ' . rex::getTable('knowledgebase_article') . ' '
            . 'WHERE knowledgebase_id = :knowledgebase_id AND online = 1 '
            . 'AND (title LIKE :term OR nav_title LIKE :term OR intro LIKE :term OR search_text LIKE :term OR content LIKE :term) '
            . 'ORDER BY boost DESC, priority ASC, title ASC '
            . 'LIMIT ' . $limit,
            [
                'knowledgebase_id' => $knowledgebaseId,
                'term' => $likeTerm,
                'like_term' => $likeTerm,
            ],
        );

        return self::hydrateResults($sql, $query);
    }

    /**
        * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string,is_recent:bool}>
     */
    private static function hydrateResults(rex_sql $sql, string $query): array
    {
        $results = [];
        foreach ($sql as $row) {
            $title = trim((string) $row->getValue('title'));
            $navTitle = trim((string) $row->getValue('nav_title'));
            $intro = trim((string) $row->getValue('intro'));
            $content = trim((string) $row->getValue('content'));
            $createdAt = trim((string) $row->getValue('createdate'));
            $updatedAt = trim((string) $row->getValue('updatedate'));

            // Treffer duerfen weiterhin ueber search_text (inkl. Tags) gefunden werden,
            // aber im Excerpt sollen keine Tag-Rohdaten erscheinen.
            $excerptSource = '' !== $intro
                ? $intro
                : ('' !== $content ? $content : $title);

            $results[] = [
                'id' => (int) $row->getValue('id'),
                'title' => $title,
                'nav_title' => $navTitle,
                'slug' => (string) $row->getValue('slug'),
                'intro' => $intro,
                'excerpt' => self::buildExcerpt($excerptSource, $query),
                'is_recent' => self::isRecentlyUpdated($createdAt, $updatedAt),
            ];
        }

        return $results;
    }

    private static function buildExcerpt(string $text, string $query): string
    {
        $cleanedText = self::sanitizeExcerptSource($text);
        $normalized = SearchTextExtractor::normalize($cleanedText);
        if ('' === $normalized) {
            return '';
        }

        $position = mb_stripos($normalized, $query);
        if ($position === false) {
            return mb_substr($normalized, 0, 180) . (mb_strlen($normalized) > 180 ? ' …' : '');
        }

        $start = max(0, $position - 60);
        $excerpt = mb_substr($normalized, $start, 180);
        if ($start > 0) {
            $excerpt = '… ' . $excerpt;
        }
        if ($start + 180 < mb_strlen($normalized)) {
            $excerpt .= ' …';
        }

        return $excerpt;
    }

    private static function sanitizeExcerptSource(string $text): string
    {
        $sanitized = $text;

        // fields_tagging JSON-Objekte in lesbare Tag-Namen umwandeln.
        $sanitized = preg_replace('/\{\s*"text"\s*:\s*"([^"]+)"\s*,\s*"color"\s*:\s*"#?[0-9a-fA-F]{3,6}"\s*\}/u', ' $1 ', $sanitized);
        $sanitized = preg_replace('/\{\s*"text"\s*:\s*"([^"]+)"\s*"color"\s*:\s*"#?[0-9a-fA-F]{3,6}"\s*\}/u', ' $1 ', $sanitized);

        // Restliche JSON-Syntaxzeichen entfernen, falls noch Fragmente enthalten sind.
        $sanitized = preg_replace('/"color"\s*:\s*"#?[0-9a-fA-F]{3,6}"/u', ' ', $sanitized);
        $sanitized = preg_replace('/"text"\s*:\s*"/u', '', $sanitized);
        $sanitized = str_replace(['[', ']', '{', '}', '"'], ' ', is_string($sanitized) ? $sanitized : $text);

        return is_string($sanitized) ? $sanitized : $text;
    }

    private static function toBooleanModeQuery(string $query): string
    {
        $tokens = preg_split('/\s+/u', trim($query)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) >= 2));

        if ([] === $tokens) {
            return '';
        }

        return implode(' ', array_map(static fn (string $token): string => '+' . $token . '*', $tokens));
    }

    private static function isRecentlyUpdated(string $createdAt, string $updatedAt): bool
    {
        $reference = '' !== $updatedAt ? $updatedAt : $createdAt;
        if ('' === $reference) {
            return false;
        }

        $timestamp = strtotime($reference);
        if (false === $timestamp) {
            return false;
        }

        $recentDays = AddonSettings::getSearchRecentDays();

        return $timestamp >= strtotime('-' . $recentDays . ' days');
    }
}