<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

final class AddonSettings
{
    private const KEY_MENU_TITLE = 'menu_title';
    private const KEY_ELEMENT_MODE = 'element_mode';
    private const KEY_SEARCH_RECENT_DAYS = 'search_recent_days';
    private const KEY_SEARCH_MULTI_CONTEXT_EXCERPTS = 'search_multi_context_excerpts';
    private const KEY_ALLOW_CHILD_PAGES = 'allow_child_pages';
    private const KEY_LINK_PDF_INFO = 'link_pdf_info';
    private const KEY_LINK_EXTERNAL_MARKER = 'link_external_marker';
    private const KEY_CHAPTER_HEADING_STYLE = 'chapter_heading_style';

    /** Darstellungen der Kapitelüberschriften */
    public const CHAPTER_HEADING_STYLES = ['bullet', 'line'];

    public static function getMenuTitle(): string
    {
        $addon = \rex_addon::get('knowledgebase');
        $fallback = (string) $addon->i18n('knowledgebase_title');
        $configuredTitle = trim((string) $addon->getConfig(self::KEY_MENU_TITLE, ''));

        return '' !== $configuredTitle ? $configuredTitle : $fallback;
    }

    public static function setMenuTitle(string $title): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_MENU_TITLE, trim($title));
    }

    public static function getElementMode(): string
    {
        $mode = (string) \rex_addon::get('knowledgebase')->getConfig(self::KEY_ELEMENT_MODE, 'merge');

        return 'replace' === $mode ? 'replace' : 'merge';
    }

    public static function setElementMode(string $mode): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_ELEMENT_MODE, 'replace' === $mode ? 'replace' : 'merge');
    }

    public static function getSearchRecentDays(): int
    {
        $configured = (int) \rex_addon::get('knowledgebase')->getConfig(self::KEY_SEARCH_RECENT_DAYS, 14);

        return max(1, min(365, $configured));
    }

    public static function setSearchRecentDays(int $days): void
    {
        $normalizedDays = max(1, min(365, $days));
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_SEARCH_RECENT_DAYS, $normalizedDays);
    }

    public static function isSearchMultiContextExcerptsEnabled(): bool
    {
        $configured = \rex_addon::get('knowledgebase')->getConfig(self::KEY_SEARCH_MULTI_CONTEXT_EXCERPTS, false);

        if (is_int($configured)) {
            return $configured === 1;
        }

        if (is_string($configured)) {
            $normalized = trim($configured);

            return $normalized === '1' || $normalized === '|1|';
        }

        return $configured === true;
    }

    public static function setSearchMultiContextExcerpts(bool $enabled): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_SEARCH_MULTI_CONTEXT_EXCERPTS, $enabled ? 1 : 0);
    }

    public static function isChildPagesEnabled(): bool
    {
        $configured = \rex_addon::get('knowledgebase')->getConfig(self::KEY_ALLOW_CHILD_PAGES, false);

        if (is_int($configured)) {
            return $configured === 1;
        }

        if (is_string($configured)) {
            $normalized = trim($configured);

            return $normalized === '1' || $normalized === '|1|';
        }

        return $configured === true;
    }

    public static function setChildPagesEnabled(bool $enabled): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_ALLOW_CHILD_PAGES, $enabled ? 1 : 0);
    }

    /** PDF-Links im Beitrag mit „(PDF, Größe)“ auszeichnen (Standard: aus). */
    public static function isLinkPdfInfoEnabled(): bool
    {
        return self::isFlag(self::KEY_LINK_PDF_INFO);
    }

    public static function setLinkPdfInfo(bool $enabled): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_LINK_PDF_INFO, $enabled ? 1 : 0);
    }

    /** Externe Links im Beitrag mit Symbol kennzeichnen (Standard: aus). */
    public static function isLinkExternalMarkerEnabled(): bool
    {
        return self::isFlag(self::KEY_LINK_EXTERNAL_MARKER);
    }

    public static function setLinkExternalMarker(bool $enabled): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_LINK_EXTERNAL_MARKER, $enabled ? 1 : 0);
    }

    /** Kapitelüberschrift: 'bullet' = Balken links (Standard), 'line' = feine Linie darunter, Nummer hinterlegt */
    public static function getChapterHeadingStyle(): string
    {
        $style = (string) \rex_addon::get('knowledgebase')->getConfig(self::KEY_CHAPTER_HEADING_STYLE, 'bullet');

        return in_array($style, self::CHAPTER_HEADING_STYLES, true) ? $style : 'bullet';
    }

    public static function setChapterHeadingStyle(string $style): void
    {
        \rex_addon::get('knowledgebase')->setConfig(self::KEY_CHAPTER_HEADING_STYLE, in_array($style, self::CHAPTER_HEADING_STYLES, true) ? $style : 'bullet');
    }

    private static function isFlag(string $key): bool
    {
        $configured = \rex_addon::get('knowledgebase')->getConfig($key, false);
        if (is_int($configured)) {
            return 1 === $configured;
        }
        if (is_string($configured)) {
            return in_array(trim($configured), ['1', '|1|'], true);
        }

        return true === $configured;
    }
}
