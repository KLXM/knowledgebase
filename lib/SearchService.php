<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_sql;
use Throwable;

final class SearchService
{
    /**
     * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}>
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
     * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}>
     */
    private static function searchFulltext(int $knowledgebaseId, string $query, int $limit): array
    {
        $booleanQuery = self::toBooleanModeQuery($query);
        if ('' === $booleanQuery) {
            return [];
        }

        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id, title, nav_title, slug, intro, search_text, content '
            . 'FROM ' . rex::getTable('knowledgebase_article') . ' '
            . 'WHERE knowledgebase_id = :knowledgebase_id AND online = 1 '
            . 'AND MATCH(search_text) AGAINST (:term IN BOOLEAN MODE) '
            . 'ORDER BY MATCH(search_text) AGAINST (:term IN BOOLEAN MODE) DESC, priority ASC, title ASC '
            . 'LIMIT ' . $limit,
            [
                'knowledgebase_id' => $knowledgebaseId,
                'term' => $booleanQuery,
            ],
        );

        return self::hydrateResults($sql, $query);
    }

    /**
     * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}>
     */
    private static function searchLike(int $knowledgebaseId, string $query, int $limit): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT id, title, nav_title, slug, intro, search_text, content '
            . 'FROM ' . rex::getTable('knowledgebase_article') . ' '
            . 'WHERE knowledgebase_id = :knowledgebase_id AND online = 1 '
            . 'AND (title LIKE :term OR nav_title LIKE :term OR intro LIKE :term OR search_text LIKE :term OR content LIKE :term) '
            . 'ORDER BY priority ASC, title ASC '
            . 'LIMIT ' . $limit,
            [
                'knowledgebase_id' => $knowledgebaseId,
                'term' => '%' . $query . '%',
            ],
        );

        return self::hydrateResults($sql, $query);
    }

    /**
     * @return list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}>
     */
    private static function hydrateResults(rex_sql $sql, string $query): array
    {
        $results = [];
        foreach ($sql as $row) {
            $title = trim((string) $row->getValue('title'));
            $navTitle = trim((string) $row->getValue('nav_title'));
            $intro = trim((string) $row->getValue('intro'));
            $searchText = trim((string) $row->getValue('search_text'));
            $content = trim((string) $row->getValue('content'));

            $excerptSource = '' !== $searchText
                ? $searchText
                : ('' !== $intro
                    ? $intro
                    : ('' !== $content ? $content : $title));

            $results[] = [
                'id' => (int) $row->getValue('id'),
                'title' => $title,
                'nav_title' => $navTitle,
                'slug' => (string) $row->getValue('slug'),
                'intro' => $intro,
                'excerpt' => self::buildExcerpt($excerptSource, $query),
            ];
        }

        return $results;
    }

    private static function buildExcerpt(string $text, string $query): string
    {
        $normalized = SearchTextExtractor::normalize($text);
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

    private static function toBooleanModeQuery(string $query): string
    {
        $tokens = preg_split('/\s+/u', trim($query)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => mb_strlen($token) >= 2));

        if ([] === $tokens) {
            return '';
        }

        return implode(' ', array_map(static fn (string $token): string => '+' . $token . '*', $tokens));
    }
}