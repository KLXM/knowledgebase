<?php

declare(strict_types=1);

$addon = rex_addon::get('knowledgebase');

if (!rex_addon::get('yform')->isAvailable()) {
    throw new rex_functional_exception('YForm muss installiert und aktiviert sein.');
}

if (!rex_addon::get('yform_content_builder')->isAvailable()) {
    throw new rex_functional_exception('YForm Content Builder muss installiert und aktiviert sein.');
}

rex_yform_manager_table::deleteCache();

$tablesetContent = rex_file::get($addon->getPath('install/tablesets/knowledgebase.json'));
if (is_string($tablesetContent) && '' !== $tablesetContent) {
    rex_yform_manager_table_api::importTablesets($tablesetContent);
}

// Altlasten aus frueheren Versionen entfernen: parent_id wird nicht mehr genutzt.
$legacyFieldSql = rex_sql::factory();
$legacyFieldSql->setQuery(
    'DELETE FROM ' . rex::getTable('yform_field') . ' WHERE table_name = :table_name AND name = :field_name',
    [
        'table_name' => rex::getTable('knowledgebase_article'),
        'field_name' => 'parent_id',
    ],
);

$slugValidateSql = rex_sql::factory();
$slugValidateSql->setQuery(
    'DELETE FROM ' . rex::getTable('yform_field') . ' WHERE table_name = :table_name AND type_id = :type_id AND type_name = :type_name AND name = :field_name',
    [
        'table_name' => rex::getTable('knowledgebase'),
        'type_id' => 'validate',
        'type_name' => 'empty',
        'field_name' => 'slug',
    ],
);

$slugValidateSql = rex_sql::factory();
$slugValidateSql->setQuery(
    'DELETE FROM ' . rex::getTable('yform_field') . ' WHERE table_name = :table_name AND type_id = :type_id AND type_name = :type_name AND name = :field_name',
    [
        'table_name' => rex::getTable('knowledgebase_article'),
        'type_id' => 'validate',
        'type_name' => 'empty',
        'field_name' => 'slug',
    ],
);

$relationTypeFixSql = rex_sql::factory();
$relationTypeFixSql->setQuery(
    'UPDATE ' . rex::getTable('yform_field') . ' SET `type` = :relation_type WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id AND type_name = :type_name',
    [
        'relation_type' => '0',
        'table_name' => rex::getTable('knowledgebase_article'),
        'field_name' => 'knowledgebase_id',
        'type_id' => 'value',
        'type_name' => 'be_manager_relation',
    ],
);

$slugTypeSql = rex_sql::factory();
$slugTypeSql->setQuery(
    'UPDATE ' . rex::getTable('yform_field') . ' SET type_name = :type_name WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id',
    [
        'type_name' => 'knowledgebase_slug',
        'table_name' => rex::getTable('knowledgebase'),
        'field_name' => 'slug',
        'type_id' => 'value',
    ],
);

$articleTableName = rex::getTable('knowledgebase_article');
$navTitleFieldSql = rex_sql::factory();
$navTitleFieldSql->setQuery(
    'SELECT prio FROM ' . rex::getTable('yform_field') . ' WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id LIMIT 1',
    [
        'table_name' => $articleTableName,
        'field_name' => 'nav_title',
        'type_id' => 'value',
    ],
);

if ($navTitleFieldSql->getRows() > 0) {
    $navBadgePrio = (int) $navTitleFieldSql->getValue('prio') + 1;

    $navBadgeFieldSql = rex_sql::factory();
    $navBadgeFieldSql->setQuery(
        'UPDATE ' . rex::getTable('yform_field')
        . ' SET prio = :prio, list_hidden = :list_hidden, label = :label, notice = :notice'
        . ' WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id',
        [
            'prio' => $navBadgePrio,
            'list_hidden' => 0,
            'label' => 'Navigation-Badge',
            'notice' => 'Optional: Zahl 1-99 oder UIKit3-Iconname (z.B. file-text, bookmark, info, question, tag, star).',
            'table_name' => $articleTableName,
            'field_name' => 'nav_badge',
            'type_id' => 'value',
        ],
    );
}

$slugTypeSql = rex_sql::factory();
$slugTypeSql->setQuery(
    'UPDATE ' . rex::getTable('yform_field') . ' SET type_name = :type_name WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id',
    [
        'type_name' => 'knowledgebase_slug',
        'table_name' => rex::getTable('knowledgebase_article'),
        'field_name' => 'slug',
        'type_id' => 'value',
    ],
);

$relationTypeFixSql = rex_sql::factory();
$relationTypeFixSql->setQuery(
    'UPDATE ' . rex::getTable('yform_field') . ' SET `type` = :relation_type WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id AND type_name = :type_name',
    [
        'relation_type' => '0',
        'table_name' => rex::getTable('knowledgebase_glossary'),
        'field_name' => 'knowledgebase_id',
        'type_id' => 'value',
        'type_name' => 'be_manager_relation',
    ],
);

// Optionale UX-Verbesserung mit Fields-Addon:
// Status- und Sortierungsfelder werden in der YForm-Liste inline editierbar.
if (rex_addon::get('fields')->isAvailable()) {
    $fieldUpdates = [
        [
            'table_name' => rex::getTable('knowledgebase'),
            'name' => 'title',
            'type_name' => 'fields_inline',
        ],
        [
            'table_name' => rex::getTable('knowledgebase'),
            'name' => 'status',
            'type_name' => 'fields_inline_switch',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_article'),
            'name' => 'title',
            'type_name' => 'fields_inline',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_article'),
            'name' => 'nav_title',
            'type_name' => 'fields_inline',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_article'),
            'name' => 'online',
            'type_name' => 'fields_inline_switch',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_article'),
            'name' => 'show_in_nav',
            'type_name' => 'fields_inline_switch',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_article'),
            'name' => 'priority',
            'type_name' => 'fields_inline_number',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_glossary'),
            'name' => 'term',
            'type_name' => 'fields_inline',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_glossary'),
            'name' => 'online',
            'type_name' => 'fields_inline_switch',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_interactive_image'),
            'name' => 'title',
            'type_name' => 'fields_inline',
        ],
        [
            'table_name' => rex::getTable('knowledgebase_interactive_image'),
            'name' => 'online',
            'type_name' => 'fields_inline_switch',
        ],
    ];

    foreach ($fieldUpdates as $fieldUpdate) {
        $fieldSql = rex_sql::factory();
        $fieldSql->setTable(rex::getTable('yform_field'));
        $fieldSql->setWhere([
            'table_name' => $fieldUpdate['table_name'],
            'name' => $fieldUpdate['name'],
            'type_id' => 'value',
        ]);
        $fieldSql->setValue('type_name', $fieldUpdate['type_name']);
        $fieldSql->update();
    }

    rex_yform_manager_table_api::generateTablesAndFields();
}

rex_sql_table::get(rex::getTable('knowledgebase'))
    ->ensureIndex(new rex_sql_index('knowledgebase_slug', ['slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_status', ['status']))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_enabled', ['glossary_enabled']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_sort', ['article_sort_field', 'article_sort_order']))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_article'))
    // Relation-/Slug-Spalten explizit auf indexfreundliche Typen setzen,
    // damit der kombinierte Index auch auf restriktiven DB-Setups anlegbar bleibt.
    ->ensureColumn(new rex_sql_column('knowledgebase_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('slug', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('nav_badge', 'varchar(64)'))
    ->removeIndex('knowledgebase_article_base_parent')
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_online', ['knowledgebase_id', 'online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_priority', ['knowledgebase_id', 'priority']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_slug', ['knowledgebase_id', 'slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_search', ['search_text'], rex_sql_index::FULLTEXT))
    ->removeColumn('parent_id')
    ->ensure();

$articlePrioFieldSql = rex_sql::factory();
$articlePrioFieldSql->setQuery(
    'UPDATE ' . rex::getTable('yform_field')
    . ' SET type_name = :type_name, label = :label, fields = :fields, scope = :scope, db_type = :db_type'
    . ' WHERE table_name = :table_name AND name = :name AND type_id = :type_id',
    [
        'type_name' => 'prio',
        'label' => 'Prio',
        'fields' => 'title,nav_title',
        'scope' => 'knowledgebase_id',
        'db_type' => 'int(10) unsigned',
        'table_name' => rex::getTable('knowledgebase_article'),
        'name' => 'priority',
        'type_id' => 'value',
    ],
);

rex_sql_table::get(rex::getTable('knowledgebase_glossary'))
    ->ensureColumn(new rex_sql_column('knowledgebase_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('term', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('online', 'tinyint(1)'))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_base_online', ['knowledgebase_id', 'online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_base_term', ['knowledgebase_id', 'term'], rex_sql_index::UNIQUE))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_interactive_image'))
    ->ensureColumn(new rex_sql_column('title', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('type', 'varchar(32)'))
    ->ensureColumn(new rex_sql_column('image', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('online', 'tinyint(1)'))
    ->ensureIndex(new rex_sql_index('knowledgebase_interactive_image_title', ['title']))
    ->ensureIndex(new rex_sql_index('knowledgebase_interactive_image_online', ['online']))
    ->ensure();

$interactiveTypeSql = rex_sql::factory();
$interactiveTypeSql->setQuery(
    'UPDATE ' . rex::getTable('knowledgebase_interactive_image')
    . ' SET type = :normalized WHERE LOWER(TRIM(type)) IN (:legacy_a, :legacy_b, :legacy_c)',
    [
        'normalized' => 'marker_map',
        'legacy_a' => 'markerbild',
        'legacy_b' => 'marker map',
        'legacy_c' => 'marker-map',
    ],
);

$glossaryTinyAttributes = json_encode([
    'class' => 'tiny-editor',
    'data-profile' => 'default',
]);

if ($glossaryTinyAttributes !== false) {
    $glossaryFieldSql = rex_sql::factory();
    $glossaryFieldSql->setQuery(
        'UPDATE ' . rex::getTable('yform_field') . ' SET attributes = :attributes WHERE table_name = :table_name AND name = :field_name AND type_id = :type_id',
        [
            'attributes' => $glossaryTinyAttributes,
            'table_name' => rex::getTable('knowledgebase_glossary'),
            'field_name' => 'description',
            'type_id' => 'value',
        ],
    );
}

$sql = rex_sql::factory();
$sql->setQuery('SELECT id, title, nav_title, intro, content FROM ' . rex::getTable('knowledgebase_article'));
foreach ($sql as $row) {
    $extractSearchText = static function (string $value): string {
        $normalize = static function (string $text): string {
            $normalized = strip_tags($text);
            $normalized = preg_replace('/\s+/u', ' ', $normalized);

            return is_string($normalized) ? trim($normalized) : trim($text);
        };

        $trimmed = trim($value);
        if ('' === $trimmed) {
            return '';
        }

        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            return $normalize($trimmed);
        }

        $ignoredKeys = ['id', 'type', 'class', 'css_class', 'image', 'images', 'media', 'file', 'files', 'link_url', 'url', 'anchor', 'section_bg_image', 'background_image', 'icon'];
        $parts = [];

        $collect = static function (mixed $node, array &$parts, string $key = '') use (&$collect, $normalize, $ignoredKeys): void {
            if (is_string($node)) {
                $candidate = $normalize($node);
                if ('' !== $candidate && !in_array($key, $ignoredKeys, true)) {
                    $parts[] = $candidate;
                }

                return;
            }

            if (!is_array($node)) {
                return;
            }

            foreach ($node as $childKey => $childValue) {
                $collect($childValue, $parts, is_string($childKey) ? $childKey : '');
            }
        };

        $collect($decoded, $parts);

        return $normalize(implode(' ', $parts));
    };

    $normalize = static function (string $text): string {
        $normalized = strip_tags($text);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return is_string($normalized) ? trim($normalized) : trim($text);
    };

    $searchText = $normalize(
        implode(' ', array_filter([
            (string) ($row->getValue('title') ?? ''),
            (string) ($row->getValue('nav_title') ?? ''),
            (string) ($row->getValue('intro') ?? ''),
            $extractSearchText((string) ($row->getValue('content') ?? '')),
        ], static fn (string $value): bool => '' !== trim($value))),
    );

    $updateSql = rex_sql::factory();
    $updateSql->setTable(rex::getTable('knowledgebase_article'));
    $updateSql->setWhere(['id' => (int) $row->getValue('id')]);
    $updateSql->setValue('search_text', $searchText);
    $updateSql->update();
}

$moduleInput = rex_file::get($addon->getPath('module/module_input.inc'));
$moduleOutput = rex_file::get($addon->getPath('module/module_output.inc'));

if (is_string($moduleInput) && '' !== $moduleInput && is_string($moduleOutput) && '' !== $moduleOutput) {
    $moduleTable = rex::getTable('module');
    $moduleName = 'Knowledge Base';
    $moduleKey = 'knowledgebase_module';

    $hasKey = false;
    foreach (rex_sql::showColumns($moduleTable) as $column) {
        if ('key' === $column['name']) {
            $hasKey = true;
            break;
        }
    }

    $moduleSql = rex_sql::factory();
    if ($hasKey) {
        $moduleSql->setQuery('SELECT id FROM ' . $moduleTable . ' WHERE `key` = :module_key', ['module_key' => $moduleKey]);
    } else {
        $moduleSql->setQuery('SELECT id FROM ' . $moduleTable . ' WHERE name = :module_name', ['module_name' => $moduleName]);
    }

    if ($moduleSql->getRows() > 0) {
        $updateModuleSql = rex_sql::factory();
        $updateModuleSql->setTable($moduleTable);
        $updateModuleSql->setWhere(['id' => (int) $moduleSql->getValue('id')]);
        $updateModuleSql->setValue('name', $moduleName);
        $updateModuleSql->setValue('input', $moduleInput);
        $updateModuleSql->setValue('output', $moduleOutput);
        if ($hasKey) {
            $updateModuleSql->setValue('key', $moduleKey);
        }
        $updateModuleSql->addGlobalUpdateFields();
        $updateModuleSql->update();
    } else {
        $insertModuleSql = rex_sql::factory();
        $insertModuleSql->setTable($moduleTable);
        $insertModuleSql->setValue('name', $moduleName);
        $insertModuleSql->setValue('input', $moduleInput);
        $insertModuleSql->setValue('output', $moduleOutput);
        if ($hasKey) {
            $insertModuleSql->setValue('key', $moduleKey);
        }
        $insertModuleSql->addGlobalCreateFields();
        $insertModuleSql->insert();
    }
}

rex_delete_cache();