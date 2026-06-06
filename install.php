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

rex_sql_table::get(rex::getTable('knowledgebase'))
    ->ensureIndex(new rex_sql_index('knowledgebase_slug', ['slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_status', ['status']))
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