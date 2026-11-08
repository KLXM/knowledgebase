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
            . 'WHERE kb.online = 1 '
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
     * Links aus dem Editor-Plugin neu auflösen: <a … data-kb-article="ID" data-kb-chapter="slice_…">
     * bekommt die aktuelle Adresse (URL-Profil, sonst Fallback) samt aktuellem Anker. Nicht (mehr)
     * auflösbare Links bleiben unverändert.
     */
    public static function rewriteContentLinks(string $html): string
    {
        $result = preg_replace_callback(
            '/<a\b[^>]*\bdata-kb-article=(["\'])(\d+)\1[^>]*>/i',
            static function (array $m): string {
                $tag = $m[0];
                $chapter = preg_match('/\bdata-kb-chapter=(["\'])([^"\']*)\1/i', $tag, $c) === 1 ? $c[2] : '';
                $href = preg_match('/\bhref=(["\'])([^"\']*)\1/i', $tag, $h) === 1 ? html_entity_decode($h[2], ENT_QUOTES) : '';
                if ('' === $href) {
                    return $tag;
                }
                $hashPos = strpos($href, '#');
                $fallbackAnchor = false !== $hashPos ? rawurldecode(substr($href, $hashPos + 1)) : '';
                $url = KnowledgebaseUrl::resolveContentLink((int) $m[2], $chapter, $fallbackAnchor);
                if ('' === $url) {
                    return $tag;
                }

                $newHref = 'href="' . rex_escape($url) . '"';

                return (string) preg_replace_callback('/\bhref=(["\'])[^"\']*\1/i', static fn (): string => $newHref, $tag, 1);
            },
            $html,
        );

        return is_string($result) ? $result : $html;
    }

    /**
     * Kapitelanker eines Beitrags mit genau den Ankern, die die Ausgabe setzt
     * (FrontendRenderer::chapterAnchorFor), dazu Ebene und Nummer für die Anzeige im Editor.
     *
     * @return list<array{title:string,anchor:string,slice:string,level:string,badge:string}>
     */
    public static function extractAnchors(string $content): array
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
        $registry = [];
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

            $anchor = FrontendRenderer::chapterAnchorFor(trim((string) ($data['anchor_id'] ?? '')), $title, $registry);
            if ('' === $anchor) {
                continue;
            }

            $level = strtolower(trim((string) ($data['heading_level'] ?? 'h2')));

            $anchors[] = [
                'title' => $title,
                'anchor' => $anchor,
                'slice' => (string) ($slice['id'] ?? ''),
                'level' => in_array($level, ['h2', 'h3', 'h4'], true) ? $level : 'h2',
                'badge' => FrontendRenderer::chapterBadgeText($data),
            ];
        }

        return $anchors;
    }
}
