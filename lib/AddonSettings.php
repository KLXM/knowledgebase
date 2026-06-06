<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

final class AddonSettings
{
    private const KEY_MENU_TITLE = 'menu_title';
    private const KEY_ELEMENT_MODE = 'element_mode';

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
}
