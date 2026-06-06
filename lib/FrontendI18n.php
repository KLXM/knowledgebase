<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_addon;
use rex_clang;

final class FrontendI18n
{
    /**
     * @var list<string>
     */
    private const MANAGED_KEYS = [
        'knowledgebase_frontend_missing_base',
        'knowledgebase_search_label',
        'knowledgebase_search_submit',
        'knowledgebase_nav_toggle',
        'knowledgebase_nav_title',
        'knowledgebase_nav_filter_label',
        'knowledgebase_nav_filter_placeholder',
        'knowledgebase_nav_expand_all',
        'knowledgebase_nav_collapse_all',
        'knowledgebase_nav_glossary',
        'knowledgebase_nav_all_levels',
        'knowledgebase_frontend_missing_article',
        'knowledgebase_article_label',
        'knowledgebase_glossary_title',
        'knowledgebase_glossary_empty',
        'knowledgebase_search_results',
        'knowledgebase_search_empty',
        'knowledgebase_toc_empty',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const DEFAULT_TRANSLATIONS = [
        'de' => [
            'knowledgebase_frontend_missing_base' => 'Die Wissensbasis ist nicht verfuegbar.',
            'knowledgebase_search_label' => 'Suche',
            'knowledgebase_search_submit' => 'Volltextsuche',
            'knowledgebase_nav_toggle' => 'Kapitel',
            'knowledgebase_nav_title' => 'Inhaltsverzeichnis',
            'knowledgebase_nav_filter_label' => 'Kapitel filtern',
            'knowledgebase_nav_filter_placeholder' => 'Titel oder Unterkapitel',
            'knowledgebase_nav_expand_all' => 'Alle aufklappen',
            'knowledgebase_nav_collapse_all' => 'Alle einklappen',
            'knowledgebase_nav_glossary' => 'Glossar',
            'knowledgebase_nav_all_levels' => 'Inhaltsverzeichnis (alle Ebenen)',
            'knowledgebase_frontend_missing_article' => 'Es ist noch kein Beitrag vorhanden.',
            'knowledgebase_article_label' => 'Kapitel',
            'knowledgebase_glossary_title' => 'Glossar',
            'knowledgebase_glossary_empty' => 'Noch keine Glossar-Begriffe vorhanden.',
            'knowledgebase_search_results' => 'Suchergebnisse',
            'knowledgebase_search_empty' => 'Keine Treffer gefunden.',
            'knowledgebase_toc_empty' => 'Für diesen Beitrag sind noch keine Kapitelmarken vorhanden.',
        ],
        'en' => [
            'knowledgebase_frontend_missing_base' => 'The knowledge base is not available.',
            'knowledgebase_search_label' => 'Search',
            'knowledgebase_search_submit' => 'Full text search',
            'knowledgebase_nav_toggle' => 'Chapters',
            'knowledgebase_nav_title' => 'Table of contents',
            'knowledgebase_nav_filter_label' => 'Filter chapters',
            'knowledgebase_nav_filter_placeholder' => 'Title or subchapter',
            'knowledgebase_nav_expand_all' => 'Expand all',
            'knowledgebase_nav_collapse_all' => 'Collapse all',
            'knowledgebase_nav_glossary' => 'Glossary',
            'knowledgebase_nav_all_levels' => 'Table of contents (all levels)',
            'knowledgebase_frontend_missing_article' => 'No article is available yet.',
            'knowledgebase_article_label' => 'Chapter',
            'knowledgebase_glossary_title' => 'Glossary',
            'knowledgebase_glossary_empty' => 'No glossary terms available yet.',
            'knowledgebase_search_results' => 'Search results',
            'knowledgebase_search_empty' => 'No results found.',
            'knowledgebase_toc_empty' => 'No chapter markers available for this article yet.',
        ],
    ];

    public static function msg(string $key, string $fallback = ''): string
    {
        $languageCode = self::detectFrontendLanguageCode();
        $translations = self::getTranslationsForLanguage($languageCode);
        $value = trim((string) ($translations[$key] ?? ''));

        if ($value !== '') {
            return $value;
        }

        return $fallback !== '' ? $fallback : $key;
    }

    /**
     * @return list<string>
     */
    public static function getManagedKeys(): array
    {
        return self::MANAGED_KEYS;
    }

    /**
     * @return array<int, rex_clang>
     */
    public static function getActiveFrontendLanguages(): array
    {
        return rex_clang::getAll(true);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function getConfigTranslations(): array
    {
        $addon = rex_addon::get('knowledgebase');
        $raw = $addon->getConfig('frontend_i18n', []);
        if (!is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $langCode => $entries) {
            if (!is_string($langCode) || !is_array($entries)) {
                continue;
            }

            $code = strtolower(trim($langCode));
            if ($code === '') {
                continue;
            }

            foreach (self::MANAGED_KEYS as $key) {
                $normalized[$code][$key] = trim((string) ($entries[$key] ?? ''));
            }
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $translations
     */
    public static function saveConfigTranslations(array $translations): void
    {
        $normalized = [];
        foreach ($translations as $langCode => $entries) {
            if (!is_string($langCode) || !is_array($entries)) {
                continue;
            }

            $code = strtolower(trim($langCode));
            if ($code === '') {
                continue;
            }

            foreach (self::MANAGED_KEYS as $key) {
                $normalized[$code][$key] = trim((string) ($entries[$key] ?? ''));
            }
        }

        rex_addon::get('knowledgebase')->setConfig('frontend_i18n', $normalized);
    }

    /**
     * @return array<string, string>
     */
    public static function getTranslationsForLanguage(string $languageCode): array
    {
        $code = strtolower(trim($languageCode));
        $baseCode = substr($code, 0, 2);

        $default = self::DEFAULT_TRANSLATIONS[$baseCode] ?? self::DEFAULT_TRANSLATIONS['en'];
        $config = self::getConfigTranslations();

        $override = $config[$code] ?? $config[$baseCode] ?? [];

        $result = $default;
        foreach (self::MANAGED_KEYS as $key) {
            $candidate = trim((string) ($override[$key] ?? ''));
            if ($candidate !== '') {
                $result[$key] = $candidate;
            }
        }

        return $result;
    }

    private static function detectFrontendLanguageCode(): string
    {
        $clang = rex_clang::getCurrent();
        $code = strtolower(trim((string) $clang->getCode()));
        if ($code !== '') {
            return $code;
        }

        return 'de';
    }
}
