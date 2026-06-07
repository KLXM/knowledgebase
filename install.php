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

// Choice-Migration: historische Label-/Trunkatwerte auf stabile Keys normalisieren.
$knowledgebaseTable = rex::getTable('knowledgebase');
$sql = rex_sql::factory();

// Legacy-Migration: alte Status-Spalte in das neue Online-Feld übernehmen.
try {
    $columns = $sql->getArray('SHOW COLUMNS FROM ' . $sql->escapeIdentifier($knowledgebaseTable));
    $hasStatus = false;
    $hasOnline = false;
    foreach ($columns as $column) {
        $fieldName = (string) ($column['Field'] ?? '');
        if ($fieldName === 'status') {
            $hasStatus = true;
        }
        if ($fieldName === 'online') {
            $hasOnline = true;
        }
    }

    if ($hasStatus && $hasOnline) {
        $sql->setQuery(
            'UPDATE ' . $knowledgebaseTable . ' SET online = status'
        );
    }
} catch (Throwable) {
    // Ignore on fresh installs / unexpected schema states.
}

$sql->setQuery(
    'UPDATE ' . $knowledgebaseTable . ' '
    . 'SET layout_mode = CASE '
    . '    WHEN LOWER(layout_mode) LIKE :layout_classic THEN :layout_classic_value '
    . '    WHEN LOWER(layout_mode) LIKE :layout_compact THEN :layout_compact_value '
    . '    WHEN LOWER(layout_mode) LIKE :layout_focus THEN :layout_focus_value '
    . '    ELSE layout_mode '
    . 'END, '
    . 'article_sort_field = CASE '
    . '    WHEN LOWER(article_sort_field) LIKE :sort_priority THEN :sort_priority_value '
    . '    WHEN LOWER(article_sort_field) LIKE :sort_title THEN :sort_title_value '
    . '    WHEN LOWER(article_sort_field) LIKE :sort_updatedate THEN :sort_updatedate_value '
    . '    ELSE article_sort_field '
    . 'END, '
    . 'article_sort_order = CASE '
    . '    WHEN LOWER(article_sort_order) LIKE :order_asc THEN :order_asc_value '
    . '    WHEN LOWER(article_sort_order) LIKE :order_asc_short THEN :order_asc_value '
    . '    WHEN LOWER(article_sort_order) LIKE :order_desc THEN :order_desc_value '
    . '    ELSE article_sort_order '
    . 'END',
    [
        'layout_classic' => 'klassisch%',
        'layout_classic_value' => 'classic',
        'layout_compact' => 'kompakt%',
        'layout_compact_value' => 'compact',
        'layout_focus' => 'fokus%',
        'layout_focus_value' => 'focus',
        'sort_priority' => 'priorit%',
        'sort_priority_value' => 'priority',
        'sort_title' => 'titel%',
        'sort_title_value' => 'title',
        'sort_updatedate' => 'zuletzt aktualisiert%',
        'sort_updatedate_value' => 'updatedate',
        'order_asc' => 'aufst%',
        'order_asc_short' => 'aufs%',
        'order_asc_value' => 'ASC',
        'order_desc' => 'abst%',
        'order_desc_value' => 'DESC',
    ],
);

// Sicherstellen, dass das Tagging-Feld eine Quelle für Vorschläge hat.
rex_sql::factory()->setQuery(
    'UPDATE ' . rex::getTable('yform_field') . '
    SET source_table = :source_table, source_field = :source_field
    WHERE table_name = :table_name AND name = :field_name AND type_name = :type_name',
    [
        'source_table' => rex::getTable('knowledgebase_article'),
        'source_field' => 'tags',
        'table_name' => rex::getTable('knowledgebase_article'),
        'field_name' => 'tags',
        'type_name' => 'fields_tagging',
    ],
);

rex_sql_table::get(rex::getTable('knowledgebase'))
    ->ensureIndex(new rex_sql_index('knowledgebase_slug', ['slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_online', ['online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_enabled', ['glossary_enabled']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_sort', ['article_sort_field', 'article_sort_order']))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_article'))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_online', ['knowledgebase_id', 'online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_priority', ['knowledgebase_id', 'priority']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_slug', ['knowledgebase_id', 'slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_search', ['search_text'], rex_sql_index::FULLTEXT))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_glossary'))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_base_online', ['knowledgebase_id', 'online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_base_term', ['knowledgebase_id', 'term'], rex_sql_index::UNIQUE))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_interactive_image'))
    ->ensureIndex(new rex_sql_index('knowledgebase_interactive_image_title', ['title']))
    ->ensureIndex(new rex_sql_index('knowledgebase_interactive_image_online', ['online']))
    ->ensure();

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