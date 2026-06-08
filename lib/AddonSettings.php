<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

final class AddonSettings
{
    private const KEY_MENU_TITLE = 'menu_title';
    private const KEY_ELEMENT_MODE = 'element_mode';
    private const KEY_SEARCH_RECENT_DAYS = 'search_recent_days';

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
}
