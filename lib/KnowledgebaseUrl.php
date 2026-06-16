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
    private const TAGS_SEGMENT = 'tags';

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

    public static function getTagsUrl(int $knowledgebaseId, string $tag = '', string $tags = ''): string
    {
        $normalizedTag = trim($tag);
        $normalizedTags = trim($tags);

        if (!self::urlAddonAvailable() || !self::hasProfile($knowledgebaseId)) {
            $base = self::buildFallbackUrl($knowledgebaseId, '');
            if ($normalizedTag === '' && $normalizedTags === '') {
                return $base;
            }

            $separator = str_contains($base, '?') ? '&' : '?';

            $params = [];
            if ($normalizedTag !== '') {
                $params['kb_' . $knowledgebaseId . '_tag'] = $normalizedTag;
            }
            if ($normalizedTags !== '') {
                $params['kb_' . $knowledgebaseId . '_tags'] = $normalizedTags;
            }

            return $base . $separator . http_build_query($params);
        }

        $base = self::buildProfileSectionUrl($knowledgebaseId, self::TAGS_SEGMENT);
        if ($normalizedTag === '' && $normalizedTags === '') {
            return $base;
        }

        if ($normalizedTags !== '') {
            return $base . '?tags=' . rawurlencode($normalizedTags);
        }

        return $base . '?tag=' . rawurlencode($normalizedTag);
    }

    /**
     * @return array{slug:string,search_query:string,glossary:bool,toc:bool,tag:string,tags:string,tags_mode:bool}
     */
    public static function resolveCurrentRequest(int $knowledgebaseId): array
    {
        $state = [
            'slug' => '',
            'search_query' => '',
            'glossary' => false,
            'toc' => false,
            'tag' => '',
            'tags' => '',
            'tags_mode' => false,
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

        $tagsPath = self::normalizePath((string) parse_url(self::buildProfileSectionUrl($knowledgebaseId, self::TAGS_SEGMENT), PHP_URL_PATH));
        if ($requestPath === $tagsPath) {
            $state['tags_mode'] = true;
            $state['tag'] = \rex_data_knowledgebase_article::normalizeTag((string) rex_request('tag', 'string', ''));
            $state['tags'] = trim((string) rex_request('tags', 'string', ''));
            return $state;
        }

        $requestedSlug = trim((string) rex_request('kb_' . $knowledgebaseId . '_article', 'string', ''));
        if ($requestedSlug !== '') {
            $state['slug'] = $requestedSlug;
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

        // URLs aus url_generator_url-Tabelle holen.
        // Es koennen mehrere Treffer existieren (z. B. Sektionen wie /glossar/),
        // deshalb den zur Article-Slug passenden Pfad bevorzugen.
        $urlRows = $sql->getArray(
            'SELECT url FROM ' . rex::getTable('url_generator_url')
            . ' WHERE profile_id = :pid AND data_id = :did',
            ['pid' => $profileId, 'did' => $dataId],
        );

        if ([] === $urlRows) {
            return '';
        }

        $needle = '/' . trim($slug, '/') . '/';
        foreach ($urlRows as $urlRow) {
            $candidate = (string) ($urlRow['url'] ?? '');
            $candidatePath = self::normalizePath((string) parse_url($candidate, PHP_URL_PATH));

            if (str_contains($candidatePath, $needle)) {
                return $candidate;
            }
        }

        // Fallback fuer historische Datensaetze ohne eindeutigen Slug-Treffer.
        return (string) ($urlRows[0]['url'] ?? '');
    }

    /**
     * Gibt die Profile-ID für eine Wissensbasis zurück (0 = kein Profil).
     */
    private static function getProfileIdForKb(int $knowledgebaseId): int
    {
        $profile = self::getProfileRowForKb($knowledgebaseId);
        if ($profile === null) {
            return 0;
        }

        return (int) ($profile['id'] ?? 0);
    }

    private static function getProfileBasePath(int $knowledgebaseId): string
    {
        $profile = self::getProfileRowForKb($knowledgebaseId);
        if ($profile === null) {
            return '/';
        }

        $articleId = (int) ($profile['article_id'] ?? 0);
        $clangId = (int) ($profile['clang_id'] ?? 1);
        if ($articleId <= 0) {
            return '/';
        }

        $article = \rex_article::get($articleId, $clangId > 0 ? $clangId : null);
        if (!$article instanceof \rex_article) {
            return '/';
        }

        return self::normalizePath((string) parse_url($article->getUrl(), PHP_URL_PATH));
    }

    /**
     * @return array{id:int,article_id:int,clang_id:int,namespace:string,table_name:string,table_parameters:string}|null
     */
    private static function getProfileRowForKb(int $knowledgebaseId): ?array
    {
        $namespace = self::buildNamespace($knowledgebaseId);
        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT id, article_id, clang_id, namespace, table_name, table_parameters FROM ' . rex::getTable('url_generator_profile')
            . ' WHERE namespace = :ns OR table_name = :table_name ORDER BY CASE WHEN namespace = :ns THEN 0 ELSE 1 END, id ASC',
            [
                'ns' => $namespace,
                'table_name' => rex::getTable('knowledgebase_article'),
            ],
        );

        foreach ($rows as $row) {
            if ((string) ($row['namespace'] ?? '') === $namespace) {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'article_id' => (int) ($row['article_id'] ?? 0),
                    'clang_id' => (int) ($row['clang_id'] ?? 1),
                    'namespace' => (string) ($row['namespace'] ?? ''),
                    'table_name' => (string) ($row['table_name'] ?? ''),
                    'table_parameters' => (string) ($row['table_parameters'] ?? ''),
                ];
            }

            if ((string) ($row['table_name'] ?? '') !== rex::getTable('knowledgebase_article')) {
                continue;
            }

            $tableParameters = json_decode((string) ($row['table_parameters'] ?? ''), true);
            if (!is_array($tableParameters)) {
                continue;
            }

            if ((string) ($tableParameters['restriction_1_value'] ?? '') === (string) $knowledgebaseId) {
                return [
                    'id' => (int) ($row['id'] ?? 0),
                    'article_id' => (int) ($row['article_id'] ?? 0),
                    'clang_id' => (int) ($row['clang_id'] ?? 1),
                    'namespace' => (string) ($row['namespace'] ?? ''),
                    'table_name' => (string) ($row['table_name'] ?? ''),
                    'table_parameters' => (string) ($row['table_parameters'] ?? ''),
                ];
            }
        }

        return null;
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
