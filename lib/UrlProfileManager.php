<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_sql;
use rex_sql_exception;

/**
 * Verwaltet URL-Addon-Profile für Knowledgebase-Wissensbasen.
 *
 * Erstellt, aktualisiert und löscht Einträge in rex_url_generator_profile
 * und löst nach Änderungen einen URL-Rebuild aus.
 */
final class UrlProfileManager
{
    /**
     * @var array<int, bool>
     */
    private static array $ensuredSectionRoutes = [];

    /**
     * Erstellt oder aktualisiert ein URL-Profil für eine Wissensbasis.
     *
     * @param int $knowledgebaseId  ID der Wissensbasis
     * @param int $redaxoArticleId  REDAXO-Artikel, auf dem das Modul liegt
     * @param int $clangId          Sprach-ID (Standard: 1)
     */
    public static function createOrUpdate(int $knowledgebaseId, int $redaxoArticleId, int $clangId = 1): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        $namespace = KnowledgebaseUrl::buildNamespace($knowledgebaseId);
        $articleTable = rex::getTable('knowledgebase_article');
        $profileTable = rex::getTable('url_generator_profile');
        $tableName = \Url\Database::merge('1', $articleTable);

        // Table-Parameters für url-Addon v2 (flaches Key-Format wie in generator.profiles.php)
        $tableParameters = json_encode([
            'column_id' => 'id',
            'column_clang_id' => '',
            'column_segment_part_1' => 'slug',
            'column_segment_part_2' => '',
            'column_segment_part_2_separator' => '/',
            'column_segment_part_3' => '',
            'column_segment_part_3_separator' => '/',
            'restriction_1_column' => 'knowledgebase_id',
            'restriction_1_comparison_operator' => '=',
            'restriction_1_value' => (string) $knowledgebaseId,
            'restriction_2_column' => '',
            'restriction_2_logical_operator' => '',
            'restriction_2_comparison_operator' => '=',
            'restriction_2_value' => '',
            'restriction_3_column' => '',
            'restriction_3_logical_operator' => '',
            'restriction_3_comparison_operator' => '=',
            'restriction_3_value' => '',
            'relation_1_column' => '',
            'relation_1_position' => 'BEFORE',
            'relation_2_column' => '',
            'relation_2_position' => 'BEFORE',
            'relation_3_column' => '',
            'relation_3_position' => 'BEFORE',
            'column_seo_title' => 'nav_title',
            'column_seo_description' => 'title',
            'column_seo_image' => '',
            'column_sitemap_lastmod' => '',
            'sitemap_frequency' => 'weekly',
            'sitemap_priority' => '0.8',
            'sitemap_add' => '1',
            'append_structure_categories' => '0',
            'append_user_paths' => '',
        ], JSON_UNESCAPED_UNICODE);

        if (false === $tableParameters) {
            return false;
        }

        $sql = rex_sql::factory();

        // Vorhandenes Profil prüfen
        $existing = $sql->getArray(
            'SELECT id FROM ' . $profileTable . ' WHERE namespace = :ns LIMIT 1',
            ['ns' => $namespace],
        );

        try {
            if ([] !== $existing) {
                // Update
                $update = rex_sql::factory();
                $update->setTable($profileTable);
                $update->setWhere('namespace = :ns', ['ns' => $namespace]);
                $update->setValue('article_id', $redaxoArticleId);
                $update->setValue('clang_id', $clangId);
                $update->setValue('table_name', $tableName);
                $update->setValue('table_parameters', $tableParameters);
                $update->setValue('relation_1_table_parameters', '[]');
                $update->setValue('relation_2_table_parameters', '[]');
                $update->setValue('relation_3_table_parameters', '[]');
                $update->addGlobalUpdateFields();
                $update->update();
            } else {
                // Insert
                $insert = rex_sql::factory();
                $insert->setTable($profileTable);
                $insert->setValue('namespace', $namespace);
                $insert->setValue('article_id', $redaxoArticleId);
                $insert->setValue('clang_id', $clangId);
                $insert->setValue('ep_pre_save_called', 0);
                $insert->setValue('table_name', $tableName);
                $insert->setValue('table_parameters', $tableParameters);
                $insert->setValue('relation_1_table_name', '');
                $insert->setValue('relation_2_table_name', '');
                $insert->setValue('relation_3_table_name', '');
                $insert->setValue('relation_1_table_parameters', '[]');
                $insert->setValue('relation_2_table_parameters', '[]');
                $insert->setValue('relation_3_table_parameters', '[]');
                $insert->addGlobalCreateFields();
                $insert->insert();
            }
        } catch (rex_sql_exception $e) {
            return false;
        }

        self::rebuildUrls($namespace);

        return true;
    }

    /**
     * Löscht das URL-Profil für eine Wissensbasis.
     */
    public static function delete(int $knowledgebaseId): bool
    {
        if (!self::isAvailable()) {
            return false;
        }

        $namespace = KnowledgebaseUrl::buildNamespace($knowledgebaseId);
        $profileTable = rex::getTable('url_generator_profile');

        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT id FROM ' . $profileTable . ' WHERE namespace = :ns LIMIT 1',
            ['ns' => $namespace],
        );

        if ([] === $rows) {
            return true;
        }

        $profileId = (int) $rows[0]['id'];

        try {
            // URLs löschen
            $del = rex_sql::factory();
            $del->setQuery(
                'DELETE FROM ' . rex::getTable('url_generator_url') . ' WHERE profile_id = :pid',
                ['pid' => $profileId],
            );

            // Profil löschen
            $delProfile = rex_sql::factory();
            $delProfile->setQuery(
                'DELETE FROM ' . $profileTable . ' WHERE id = :pid',
                ['pid' => $profileId],
            );
        } catch (rex_sql_exception $e) {
            return false;
        }

        self::resetUrlProfileCache();

        return true;
    }

    /**
     * Gibt alle in der Config gespeicherten Mappings zurück.
     * Format: [ ['kb_id' => int, 'article_id' => int, 'clang_id' => int], ... ]
     *
     * @return list<array{kb_id: int, article_id: int, clang_id: int}>
     */
    public static function getMappings(): array
    {
        $raw = \rex_addon::get('knowledgebase')->getConfig('url_profile_mappings', []);
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $result[] = [
                'kb_id' => (int) ($entry['kb_id'] ?? 0),
                'article_id' => (int) ($entry['article_id'] ?? 0),
                'clang_id' => (int) ($entry['clang_id'] ?? 1),
            ];
        }

        return $result;
    }

    /**
     * Speichert die Mappings und synchronisiert Profile.
     *
     * @param list<array{kb_id: int, article_id: int, clang_id: int}> $mappings
     */
    public static function saveMappings(array $mappings): void
    {
        $addon = \rex_addon::get('knowledgebase');

        // Welche KB-IDs aktuell aktiv sind
        $existingKbIds = array_column(self::getMappings(), 'kb_id');
        $newKbIds = array_column($mappings, 'kb_id');

        // Profile entfernen, die aus der Auswahl rausflogen
        foreach (array_diff($existingKbIds, $newKbIds) as $removedKbId) {
            self::delete((int) $removedKbId);
        }

        // Profile anlegen/aktualisieren
        foreach ($mappings as $entry) {
            if ($entry['kb_id'] > 0 && $entry['article_id'] > 0) {
                self::createOrUpdate($entry['kb_id'], $entry['article_id'], $entry['clang_id']);
            }
        }

        $addon->setConfig('url_profile_mappings', $mappings);
    }

    /**
     * Gibt alle REDAXO-Artikel zurück, die das Knowledgebase-Modul nutzen.
     *
     * @return list<array{article_id: int, article_name: string, clang_id: int}>
     */
    public static function findModuleArticles(): array
    {
        // Modul-Schlüssel des Knowledgebase-Moduls
        $moduleKey = 'knowledgebase_module';
        $moduleTable = rex::getTable('module');
        $sliceTable = rex::getTable('article_slice');
        $articleTable = rex::getTable('article');

        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT DISTINCT s.article_id, s.clang_id, a.name AS article_name
            FROM ' . $sliceTable . ' s
            INNER JOIN ' . $moduleTable . ' m ON m.id = s.module_id
            INNER JOIN ' . $articleTable . ' a ON a.id = s.article_id AND a.clang_id = s.clang_id
            WHERE m.`key` = :key
            ORDER BY a.name ASC',
            ['key' => $moduleKey],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'article_id' => (int) $row['article_id'],
                'article_name' => (string) $row['article_name'],
                'clang_id' => (int) $row['clang_id'],
            ];
        }

        return $result;
    }

    private static function isAvailable(): bool
    {
        return \rex_addon::get('url')->isAvailable()
            && class_exists(\Url\Profile::class);
    }

    public static function ensureSectionRoutes(int $knowledgebaseId): void
    {
        if ($knowledgebaseId <= 0 || !self::isAvailable()) {
            return;
        }

        if (isset(self::$ensuredSectionRoutes[$knowledgebaseId])) {
            return;
        }

        self::$ensuredSectionRoutes[$knowledgebaseId] = true;

        $namespace = KnowledgebaseUrl::buildNamespace($knowledgebaseId);

        try {
            $profileRow = rex_sql::factory()->getArray(
                'SELECT id FROM ' . rex::getTable('url_generator_profile') . ' WHERE namespace = :ns LIMIT 1',
                ['ns' => $namespace],
            );
            if (!isset($profileRow[0]['id'])) {
                return;
            }

            $profileId = (int) $profileRow[0]['id'];
            if ($profileId <= 0 || self::hasAllSectionRoutes($profileId)) {
                return;
            }

            self::resetUrlProfileCache();
            $profile = \Url\Profile::get($profileId);
            if (!$profile instanceof \Url\Profile) {
                return;
            }

            self::appendSectionRoutes($profile, $namespace);
            self::resetUrlProfileCache();
        } catch (\Throwable $e) {
            // Selbstheilung darf den Frontend-Request nicht abbrechen.
        }
    }

    private static function rebuildUrls(string $namespace): void
    {
        if (!self::isAvailable()) {
            return;
        }

        try {
            $profiles = \Url\Profile::getAll();
            if (!$profiles) {
                return;
            }

            foreach ($profiles as $profile) {
                if ($profile->getNamespace() !== $namespace) {
                    continue;
                }

                $urlTable = rex::getTable('url_generator_url');
                $delSql = rex_sql::factory();
                $delSql->setQuery(
                    'DELETE FROM ' . $urlTable . ' WHERE profile_id = :pid',
                    ['pid' => $profile->getId()],
                );

                $profile->buildUrls();
                self::appendSectionRoutes($profile, $namespace);
                break;
            }

            // Nach DB-Änderungen immer den URL-Profilcache invalidieren,
            // sonst kann Profile::get($id) auf veraltete IDs laufen.
            self::resetUrlProfileCache();
        } catch (\Throwable $e) {
            // Rebuild-Fehler nicht fatal
        }
    }

    private static function resetUrlProfileCache(): void
    {
        if (!class_exists(\Url\Cache::class) || !class_exists(\Url\Profile::class)) {
            return;
        }

        try {
            \Url\Cache::deleteProfiles();
            \Url\Profile::reset();
        } catch (\Throwable $e) {
            // Cache-Reset Fehler nicht fatal
        }
    }

    private static function appendSectionRoutes(\Url\Profile $profile, string $namespace): void
    {
        $kbId = self::extractKnowledgebaseIdFromNamespace($namespace);
        if ($kbId <= 0) {
            return;
        }

        $profileUrls = rex_sql::factory()->getArray(
            'SELECT url FROM ' . rex::getTable('url_generator_url')
            . ' WHERE profile_id = :pid AND is_user_path = 0 ORDER BY id ASC LIMIT 1',
            ['pid' => $profile->getId()],
        );
        if (!isset($profileUrls[0]['url'])) {
            return;
        }

        $sampleUrl = (string) $profileUrls[0]['url'];
        if ($sampleUrl === '') {
            return;
        }

        $baseUrl = preg_replace('~/[^/]+/$~', '/', rtrim($sampleUrl, '/') . '/');
        if (!is_string($baseUrl) || $baseUrl === '') {
            return;
        }

        $firstDataRow = rex_sql::factory()->getArray(
            'SELECT id FROM ' . rex::getTable('knowledgebase_article')
            . ' WHERE knowledgebase_id = :kbid AND online = 1 ORDER BY id ASC LIMIT 1',
            ['kbid' => $kbId],
        );
        if (!isset($firstDataRow[0]['id'])) {
            return;
        }

        $dataId = (int) $firstDataRow[0]['id'];
        if ($dataId <= 0) {
            return;
        }

        $routes = [
            ['segment' => 'glossar', 'sitemap' => 1],
            ['segment' => 'inhaltsverzeichnis', 'sitemap' => 1],
            ['segment' => 'suche', 'sitemap' => 0],
        ];

        // Alte Zusatzrouten dieses Profils bereinigen und dann neu aufbauen.
        rex_sql::factory()->setQuery(
            'DELETE FROM ' . rex::getTable('url_generator_url') . ' WHERE profile_id = :pid AND is_user_path = 1',
            ['pid' => $profile->getId()],
        );

        foreach ($routes as $route) {
            $url = $baseUrl . $route['segment'] . '/';
            $urlHash = md5($url);

            $exists = rex_sql::factory()->getArray(
                'SELECT id FROM ' . rex::getTable('url_generator_url') . ' WHERE url_hash = :hash LIMIT 1',
                ['hash' => $urlHash],
            );
            if ([] !== $exists) {
                continue;
            }

            $insert = rex_sql::factory();
            $insert->setTable(rex::getTable('url_generator_url'));
            $insert->setValue('profile_id', $profile->getId());
            $insert->setValue('article_id', $profile->getArticleId());
            $insert->setValue('clang_id', (int) ($profile->getArticleClangId() ?? 1));
            $insert->setValue('data_id', $dataId);
            $insert->setValue('is_user_path', 1);
            $insert->setValue('is_structure', 0);
            $insert->setValue('url', $url);
            $insert->setValue('url_hash', $urlHash);
            $insert->setValue('sitemap', (int) $route['sitemap']);
            $insert->setValue('lastmod', date('c'));
            $insert->setValue('seo', '{"title":"","description":"","image":false}');
            $insert->addGlobalCreateFields();
            $insert->insert();
        }
    }

    private static function hasAllSectionRoutes(int $profileId): bool
    {
        $rows = rex_sql::factory()->getArray(
            'SELECT url FROM ' . rex::getTable('url_generator_url')
            . ' WHERE profile_id = :pid AND is_user_path = 1',
            ['pid' => $profileId],
        );

        if ([] === $rows) {
            return false;
        }

        $foundSegments = [];
        foreach ($rows as $row) {
            $path = (string) parse_url((string) ($row['url'] ?? ''), PHP_URL_PATH);
            $segment = trim((string) basename(rtrim($path, '/')));
            if ($segment !== '') {
                $foundSegments[$segment] = true;
            }
        }

        return isset($foundSegments['glossar'])
            && isset($foundSegments['inhaltsverzeichnis'])
            && isset($foundSegments['suche']);
    }

    private static function extractKnowledgebaseIdFromNamespace(string $namespace): int
    {
        if (preg_match('/^knowledgebase_(\d+)$/', $namespace, $match) !== 1) {
            return 0;
        }

        return (int) $match[1];
    }
}
