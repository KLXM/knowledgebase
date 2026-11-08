<?php

namespace FriendsOfREDAXO\Knowledgebase;

use rex_data_knowledgebase_article;
use rex_extension_point;

/**
 * Meta-Beschreibung für Beiträge.
 *
 * Das URL-Profil liefert als Beschreibung nur den Titel. Hier wird daraus ein echter
 * Anreißer: Kurzbeschreibung (intro) oder – falls leer – der Anfang des Beitragstexts.
 */
final class SeoService
{
    private const MAX_LENGTH = 160;

    /** Beschreibung für einen Beitrag (leer, wenn es keinen Text gibt). */
    public static function description(rex_data_knowledgebase_article $article): string
    {
        $text = self::plain((string) $article->getValue('intro'));
        if ('' === $text) {
            $text = self::firstParagraph((string) $article->getValue('content'));
        }

        return self::shorten($text);
    }

    /**
     * EP URL_SEO_TAGS: Beschreibung der Beitragsseiten ersetzen.
     *
     * @param rex_extension_point<array<string, string>> $ep
     * @return array<string, string>
     */
    public static function handleUrlSeoTags(rex_extension_point $ep): array
    {
        $tags = $ep->getSubject();
        if (!class_exists(\Url\Url::class)) {
            return $tags;
        }
        $manager = \Url\Url::resolveCurrent();
        $profile = $manager ? $manager->getProfile() : null;
        if (!$profile || $profile->getTableName() !== \rex::getTable('knowledgebase_article')) {
            return $tags;
        }
        $article = rex_data_knowledgebase_article::get((int) $manager->getDatasetId());
        if (!$article instanceof rex_data_knowledgebase_article) {
            return $tags;
        }
        $description = self::description($article);
        if ('' === $description) {
            return $tags;
        }
        $content = rex_escape($description);
        $tags['description'] = '<meta name="description" content="' . $content . '" />';
        $tags['og:description'] = '<meta property="og:description" content="' . $content . '" />';
        $tags['twitter:description'] = '<meta name="twitter:description" content="' . $content . '" />';

        return $tags;
    }

    /** Erster Absatz (<p>) aus dem Builder-Inhalt mit genug Text. */
    private static function firstParagraph(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return '';
        }
        $found = '';
        $walk = static function (mixed $node) use (&$walk, &$found): void {
            if ('' !== $found) {
                return;
            }
            if (is_string($node)) {
                if (str_contains($node, '<p') && preg_match_all('#<p[^>]*>(.*?)</p>#is', $node, $m)) {
                    foreach ($m[1] as $paragraph) {
                        $text = self::plain($paragraph);
                        if (mb_strlen($text) >= 60) {
                            $found = $text;

                            return;
                        }
                    }
                }

                return;
            }
            if (is_array($node)) {
                if (array_key_exists('online', $node) && !$node['online']) {
                    return;
                }
                foreach ($node as $child) {
                    $walk($child);
                }
            }
        };
        $walk($data);

        return $found;
    }

    private static function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>', '</h'], [' <br', ' </p>', ' </li>', ' </h'], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function shorten(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }
        $cut = mb_substr($text, 0, self::MAX_LENGTH - 1);
        $space = mb_strrpos($cut, ' ');
        if (false !== $space && $space > 80) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,;:–-") . '…';
    }
}
