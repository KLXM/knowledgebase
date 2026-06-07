<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_addon;
use rex_sql;
use rex;

/**
 * Zentrale URL-Auflösung für Knowledgebase-Artikel.
 *
 * Wenn das url-Addon aktiv ist und für eine Wissensbasis ein Profil existiert,
 * wird die saubere URL geliefert. Andernfalls greift der interne Fallback.
 */
final class KnowledgebaseUrl
{
    private const GLOSSARY_SEGMENT = 'glossar';
    private const TOC_SEGMENT = 'inhaltsverzeichnis';
    private const SEARCH_SEGMENT = 'suche';

    /**
     * Gibt die URL für einen Artikel zurück.
     * Priorität: URL-Addon-Profil → interner Fallback.
     */
    public static function getArticleUrl(int $knowledgebaseId, string $slug, string $anchor = ''): string
    {
        if (self::urlAddonAvailable()) {
            $url = self::resolveViaUrlAddon($knowledgebaseId, $slug);
            if ('' !== $url) {
                return $anchor !== '' ? $url . '#' . rawurlencode($anchor) : $url;
            }
        }

        return self::buildFallbackUrl($knowledgebaseId, $slug, $anchor);
    }

    /**
     * Gibt die Basis-URL für eine Wissensbasis zurück (erster Artikel).
     */
    public static function getBaseUrl(int $knowledgebaseId): string
    {
        if (self::urlAddonAvailable()) {
            $profileId = self::getProfileIdForKb($knowledgebaseId);
            if ($profileId > 0) {
                // Ersten aktiven Datensatz des Profils holen
                $sql = rex_sql::factory();
                $rows = $sql->getArray(
                    'SELECT url FROM ' . rex::getTable('url_generator_url')
                    . ' WHERE profile_id = :pid ORDER BY data_id ASC LIMIT 1',
                    ['pid' => $profileId],
                );
                if (isset($rows[0]['url']) && '' !== (string) $rows[0]['url']) {
                    return (string) $rows[0]['url'];
                }
            }
        }

        return self::buildFallbackUrl($knowledgebaseId, '');
    }

    public static function getGlossaryUrl(int $knowledgebaseId): string
    {
        if (!self::urlAddonAvailable() || !self::hasProfile($knowledgebaseId)) {
            return self::buildFallbackUrl($knowledgebaseId, '') . '?kb_' . $knowledgebaseId . '_glossary=1';
        }

        return self::buildProfileSectionUrl($knowledgebaseId, self::GLOSSARY_SEGMENT);
    }

    public static function getTocUrl(int $knowledgebaseId): string
    {
        if (!self::urlAddonAvailable() || !self::hasProfile($knowledgebaseId)) {
            return self::buildFallbackUrl($knowledgebaseId, '') . '?kb_' . $knowledgebaseId . '_toc=1';
        }

        return self::buildProfileSectionUrl($knowledgebaseId, self::TOC_SEGMENT);
    }

    public static function getSearchUrl(int $knowledgebaseId, string $query): string
    {
        $trimmedQuery = trim($query);
        if ($trimmedQuery === '') {
            return self::getBaseUrl($knowledgebaseId);
        }

        if (!self::urlAddonAvailable() || !self::hasProfile($knowledgebaseId)) {
            $base = self::buildFallbackUrl($knowledgebaseId, '');
            $separator = str_contains($base, '?') ? '&' : '?';

            return $base . $separator . http_build_query([
                'kb_' . $knowledgebaseId . '_q' => $trimmedQuery,
            ]);
        }

        $base = self::buildProfileSectionUrl($knowledgebaseId, self::SEARCH_SEGMENT);

        return $base . '?q=' . rawurlencode($trimmedQuery);
    }

    public static function getSearchBaseUrl(int $knowledgebaseId): string
    {
        if (!self::urlAddonAvailable() || !self::hasProfile($knowledgebaseId)) {
            return self::buildFallbackUrl($knowledgebaseId, '');
        }

        return self::buildProfileSectionUrl($knowledgebaseId, self::SEARCH_SEGMENT);
    }

    /**
     * @return array{slug:string,search_query:string,glossary:bool,toc:bool}
     */
    public static function resolveCurrentRequest(int $knowledgebaseId): array
    {
        $state = [
            'slug' => '',
            'search_query' => '',
            'glossary' => false,
            'toc' => false,
        ];

        if (!self::urlAddonAvailable()) {
            return $state;
        }

        $profileId = self::getProfileIdForKb($knowledgebaseId);
        if ($profileId <= 0) {
            return $state;
        }

        $requestPath = self::normalizePath((string) parse_url(rex_server('REQUEST_URI', 'string', ''), PHP_URL_PATH));
        if ($requestPath === '/') {
            return $state;
        }

        $glossaryPath = self::normalizePath((string) parse_url(self::buildProfileSectionUrl($knowledgebaseId, self::GLOSSARY_SEGMENT), PHP_URL_PATH));
        if ($requestPath === $glossaryPath) {
            $state['glossary'] = true;
            return $state;
        }

        $tocPath = self::normalizePath((string) parse_url(self::buildProfileSectionUrl($knowledgebaseId, self::TOC_SEGMENT), PHP_URL_PATH));
        if ($requestPath === $tocPath) {
            $state['toc'] = true;
            return $state;
        }

        $searchPath = self::normalizePath((string) parse_url(self::buildProfileSectionUrl($knowledgebaseId, self::SEARCH_SEGMENT), PHP_URL_PATH));
        if ($requestPath === $searchPath) {
            $state['search_query'] = trim((string) rex_request('q', 'string', ''));
            return $state;
        }

        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT u.url, a.slug
            FROM ' . rex::getTable('url_generator_url') . ' u
            INNER JOIN ' . rex::getTable('knowledgebase_article') . ' a ON a.id = u.data_id
            WHERE u.profile_id = :pid AND a.knowledgebase_id = :kbid AND a.online = 1',
            ['pid' => $profileId, 'kbid' => $knowledgebaseId],
        );

        foreach ($rows as $row) {
            $rowPath = self::normalizePath((string) parse_url((string) $row['url'], PHP_URL_PATH));
            if ($rowPath === $requestPath) {
                $state['slug'] = (string) $row['slug'];
                return $state;
            }
        }

        return $state;
    }

    /**
     * Prüft, ob für eine Wissensbasis ein URL-Profil angelegt ist.
     */
    public static function hasProfile(int $knowledgebaseId): bool
    {
        return self::getProfileIdForKb($knowledgebaseId) > 0;
    }

    /**
     * Gibt den Namespace zurück, den ein Profil für eine KB haben soll.
     */
    public static function buildNamespace(int $knowledgebaseId): string
    {
        return 'knowledgebase_' . $knowledgebaseId;
    }

    // ------------------------------------------------------------------
    // Interna
    // ------------------------------------------------------------------

    private static function urlAddonAvailable(): bool
    {
        return rex_addon::get('url')->isAvailable()
            && class_exists(\Url\Profile::class);
    }

    private static function resolveViaUrlAddon(int $knowledgebaseId, string $slug): string
    {
        if ('' === $slug) {
            return '';
        }

        $profileId = self::getProfileIdForKb($knowledgebaseId);
        if ($profileId <= 0) {
            return '';
        }

        // Artikel-Datensatz-ID per Slug ermitteln
        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT id FROM ' . rex::getTable('knowledgebase_article')
            . ' WHERE knowledgebase_id = :kbid AND slug = :slug AND online = 1 LIMIT 1',
            ['kbid' => $knowledgebaseId, 'slug' => $slug],
        );
        if ([] === $rows) {
            return '';
        }

        $dataId = (int) $rows[0]['id'];

        // URL aus url_generator_url-Tabelle holen
        $urlRows = $sql->getArray(
            'SELECT url FROM ' . rex::getTable('url_generator_url')
            . ' WHERE profile_id = :pid AND data_id = :did LIMIT 1',
            ['pid' => $profileId, 'did' => $dataId],
        );

        return isset($urlRows[0]['url']) ? (string) $urlRows[0]['url'] : '';
    }

    /**
     * Gibt die Profile-ID für eine Wissensbasis zurück (0 = kein Profil).
     */
    private static function getProfileIdForKb(int $knowledgebaseId): int
    {
        $namespace = self::buildNamespace($knowledgebaseId);
        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT id FROM ' . rex::getTable('url_generator_profile')
            . ' WHERE namespace = :ns LIMIT 1',
            ['ns' => $namespace],
        );

        return isset($rows[0]['id']) ? (int) $rows[0]['id'] : 0;
    }

    private static function getProfileBasePath(int $knowledgebaseId): string
    {
        $namespace = self::buildNamespace($knowledgebaseId);
        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT article_id, clang_id FROM ' . rex::getTable('url_generator_profile')
            . ' WHERE namespace = :ns LIMIT 1',
            ['ns' => $namespace],
        );

        if (!isset($rows[0])) {
            return '/';
        }

        $articleId = (int) ($rows[0]['article_id'] ?? 0);
        $clangId = (int) ($rows[0]['clang_id'] ?? 1);
        if ($articleId <= 0) {
            return '/';
        }

        $article = \rex_article::get($articleId, $clangId > 0 ? $clangId : null);
        if (!$article instanceof \rex_article) {
            return '/';
        }

        return self::normalizePath((string) parse_url($article->getUrl(), PHP_URL_PATH));
    }

    private static function buildProfileSectionUrl(int $knowledgebaseId, string $segment): string
    {
        $basePath = self::getProfileBasePath($knowledgebaseId);
        $path = rtrim($basePath, '/') . '/' . trim($segment, '/') . '/';

        return self::normalizePath($path);
    }

    private static function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/' . trim($trimmed, '/') . '/';
    }

    /**
     * Interne Fallback-URL (Query-String-basiert).
     * Baut die URL direkt ohne Umweg über FrontendRenderer::buildUrl(),
     * um eine gegenseitige Rekursion zu vermeiden.
     */
    public static function buildFallbackUrl(int $knowledgebaseId, string $slug, string $anchor = ''): string
    {
        $requestUri = \rex_server('REQUEST_URI', 'string', '');
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        if ('' === $path) {
            $path = '/';
        }

        $params = [];
        if ('' !== $slug) {
            $params['kb_' . $knowledgebaseId . '_article'] = $slug;
        }

        $query = http_build_query(array_filter($params, static fn ($v) => '' !== (string) $v));
        $base = $path . ('' !== $query ? '?' . $query : '');

        if ('' !== $anchor) {
            $base .= '#' . rawurlencode($anchor);
        }

        return $base;
    }
}
