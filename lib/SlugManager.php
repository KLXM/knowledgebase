<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_extension_point;
use rex_sql;
use rex_string;
use rex_yform;
use rex_yform_manager_table;

final class SlugManager
{
    /**
     * @param rex_extension_point<mixed> $ep
     */
    public static function handleYformBeforeSave(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }

        $tableName = $table->getTableName();
        if (!in_array($tableName, [\rex::getTable('knowledgebase'), \rex::getTable('knowledgebase_article')], true)) {
            return;
        }

        $yform = $ep->getSubject();
        if (!$yform instanceof rex_yform) {
            return;
        }

        $title = trim((string) $yform->getFieldValue('title'));
        $navTitle = trim((string) $yform->getFieldValue('nav_title'));
        $slugInput = trim((string) $yform->getFieldValue('slug'));
        $slugSource = $title !== '' ? $title : $navTitle;
        $baseSlug = self::slugify($slugInput !== '' ? $slugInput : $slugSource);

        if ($baseSlug === '') {
            $baseSlug = $tableName === \rex::getTable('knowledgebase') ? 'wissensbasis' : 'artikel';
        }

        $dataId = (int) $ep->getParam('data_id', 0);
        if ($tableName === \rex::getTable('knowledgebase')) {
            $uniqueSlug = self::ensureUniqueKnowledgebaseSlug($baseSlug, $dataId > 0 ? $dataId : null);
        } else {
            $knowledgebaseId = self::resolveArticleKnowledgebaseId($yform, $dataId > 0 ? $dataId : null);
            if ($knowledgebaseId <= 0) {
                return;
            }

            $uniqueSlug = self::ensureUniqueArticleSlug($knowledgebaseId, $baseSlug, $dataId > 0 ? $dataId : null);
        }

        $yform->setFieldValue('slug', [], $uniqueSlug);
    }

    private static function resolveArticleKnowledgebaseId(rex_yform $yform, ?int $dataId = null): int
    {
        $knowledgebaseId = (int) $yform->getFieldValue('knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        $setValues = \rex_request('rex_yform_set', 'array', []);
        $knowledgebaseId = self::resolveIdFromRequestArray($setValues, 'knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        $filterValues = \rex_request('rex_yform_filter', 'array', []);
        $knowledgebaseId = self::resolveIdFromRequestArray($filterValues, 'knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        if ($dataId !== null && $dataId > 0) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT knowledgebase_id FROM ' . \rex::getTable('knowledgebase_article') . ' WHERE id = :id LIMIT 1',
                ['id' => $dataId],
            );

            if ($sql->getRows() > 0) {
                return (int) $sql->getValue('knowledgebase_id');
            }
        }

        return 0;
    }

    /**
     * @param array<mixed> $values
     */
    private static function resolveIdFromRequestArray(array $values, string $key): int
    {
        if (!isset($values[$key])) {
            return 0;
        }

        $value = $values[$key];
        if (is_array($value)) {
            $first = reset($value);
            return $first !== false ? (int) $first : 0;
        }

        return (int) $value;
    }

    private static function slugify(string $value): string
    {
        $normalized = rex_string::normalize($value, '-');
        $normalized = strtolower(trim($normalized));

        return trim($normalized, '-');
    }

    private static function ensureUniqueArticleSlug(int $knowledgebaseId, string $baseSlug, ?int $excludeId = null): string
    {
        $candidate = $baseSlug;
        $suffix = 1;

        while (self::articleSlugExists($knowledgebaseId, $candidate, $excludeId)) {
            $candidate = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function articleSlugExists(int $knowledgebaseId, string $slug, ?int $excludeId = null): bool
    {
        $sql = rex_sql::factory();
        $query = 'SELECT id FROM ' . \rex::getTable('knowledgebase_article') . ' WHERE knowledgebase_id = :knowledgebase_id AND slug = :slug';
        $params = [
            'knowledgebase_id' => $knowledgebaseId,
            'slug' => $slug,
        ];

        if ($excludeId !== null && $excludeId > 0) {
            $query .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $query .= ' LIMIT 1';

        $sql->setQuery($query, $params);

        return $sql->getRows() > 0;
    }

    private static function ensureUniqueKnowledgebaseSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $candidate = $baseSlug;
        $suffix = 1;

        while (self::knowledgebaseSlugExists($candidate, $excludeId)) {
            $candidate = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private static function knowledgebaseSlugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = rex_sql::factory();
        $query = 'SELECT id FROM ' . \rex::getTable('knowledgebase') . ' WHERE slug = :slug';
        $params = [
            'slug' => $slug,
        ];

        if ($excludeId !== null && $excludeId > 0) {
            $query .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $query .= ' LIMIT 1';

        $sql->setQuery($query, $params);

        return $sql->getRows() > 0;
    }
}
