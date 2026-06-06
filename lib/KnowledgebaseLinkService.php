<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_sql;

final class KnowledgebaseLinkService
{
    /**
     * @return list<array{id:int,title:string,articles:list<array{id:int,title:string,slug:string,online:bool,anchors:list<array{title:string,anchor:string}>}>}>
     */
    public static function getTree(): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery(
            'SELECT kb.id AS kb_id, kb.title AS kb_title, '
            . 'a.id AS article_id, a.title AS article_title, a.nav_title AS article_nav_title, a.slug AS article_slug, a.online AS article_online, a.content AS article_content '
            . 'FROM ' . rex::getTable('knowledgebase') . ' kb '
            . 'LEFT JOIN ' . rex::getTable('knowledgebase_article') . ' a ON a.knowledgebase_id = kb.id '
            . 'WHERE kb.status = 1 '
            . 'ORDER BY kb.title ASC, a.priority ASC, a.title ASC'
        );

        $treeById = [];
        foreach ($sql as $row) {
            $kbId = (int) $row->getValue('kb_id');
            if ($kbId <= 0) {
                continue;
            }

            if (!isset($treeById[$kbId])) {
                $treeById[$kbId] = [
                    'id' => $kbId,
                    'title' => trim((string) $row->getValue('kb_title')),
                    'articles' => [],
                ];
            }

            $articleId = (int) $row->getValue('article_id');
            if ($articleId <= 0) {
                continue;
            }

            $slug = trim((string) $row->getValue('article_slug'));
            if ('' === $slug) {
                continue;
            }

            $title = trim((string) $row->getValue('article_nav_title'));
            if ('' === $title) {
                $title = trim((string) $row->getValue('article_title'));
            }

            $treeById[$kbId]['articles'][] = [
                'id' => $articleId,
                'title' => $title,
                'slug' => $slug,
                'online' => (int) $row->getValue('article_online') === 1,
                'anchors' => self::extractAnchors((string) $row->getValue('article_content')),
            ];
        }

        /** @var list<array{id:int,title:string,articles:list<array{id:int,title:string,slug:string,online:bool,anchors:list<array{title:string,anchor:string}>}>}> $result */
        $result = array_values($treeById);

        return $result;
    }

    /**
     * @return list<array{title:string,anchor:string}>
     */
    private static function extractAnchors(string $content): array
    {
        $content = trim($content);
        if ('' === $content) {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        $anchors = [];
        foreach ($decoded as $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $type = trim((string) ($slice['type'] ?? ''));
            if ('kb_chapter_nav' !== $type) {
                continue;
            }

            $data = $slice['data'] ?? null;
            if (!is_array($data)) {
                continue;
            }

            $title = trim((string) ($data['title'] ?? ''));
            if ('' === $title) {
                continue;
            }

            $anchorInput = trim((string) ($data['anchor_id'] ?? ''));
            $anchor = self::sanitizeAnchor('' !== $anchorInput ? $anchorInput : $title);
            if ('' === $anchor) {
                continue;
            }

            $anchors[] = [
                'title' => $title,
                'anchor' => $anchor,
            ];
        }

        return $anchors;
    }

    private static function sanitizeAnchor(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9\-_]+/u', '-', $normalized);
        if (!is_string($normalized)) {
            return '';
        }

        return trim($normalized, '-');
    }
}
