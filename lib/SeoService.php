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
            $text = self::plain((string) $article->getValue('search_text'));
            $title = self::plain((string) $article->getValue('title'));
            if ('' !== $title && str_starts_with($text, $title)) {
                $text = ltrim(mb_substr($text, mb_strlen($title)), " \t\n\r-–:");
            }
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
