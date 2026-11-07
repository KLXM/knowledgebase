<?php

declare(strict_types=1);

$addon = rex_addon::get('knowledgebase');

$defaultConfigs = [
    'element_mode' => 'merge',
    'search_recent_days' => 14,
    'search_multi_context_excerpts' => 0,
    'allow_child_pages' => 0,
];

foreach ($defaultConfigs as $configKey => $configValue) {
    if (!$addon->hasConfig($configKey)) {
        $addon->setConfig($configKey, $configValue);
    }
}

if (!rex_addon::get('yform')->isAvailable()) {
    throw new rex_functional_exception('YForm muss installiert und aktiviert sein.');
}

if (!rex_addon::get('builder')->isAvailable()) {
    throw new rex_functional_exception('YForm Content Builder muss installiert und aktiviert sein.');
}

rex_yform_manager_table::deleteCache();

$tablesetContent = rex_file::get($addon->getPath('install/tablesets/knowledgebase.json'));
if (is_string($tablesetContent) && '' !== $tablesetContent) {
    $kbTables = [
        rex::getTable('knowledgebase'),
        rex::getTable('knowledgebase_article'),
        rex::getTable('knowledgebase_glossary'),
        rex::getTable('knowledgebase_interactive_image'),
    ];
    $tablePlaceholders = implode(',', array_fill(0, count($kbTables), '?'));
    $fieldColumns = array_column(rex_sql::showColumns(rex::getTable('yform_field')), 'name');

    // Einstellungen, die im Projekt gepflegt werden und ein Update nie überschreiben darf
    $projectColumns = array_values(array_intersect(['allowed_elements', 'list_hidden', 'search'], $fieldColumns));

    $existingFields = rex_sql::factory()->getArray(
        'SELECT * FROM ' . rex::getTable('yform_field') . ' WHERE table_name IN (' . $tablePlaceholders . ')',
        $kbTables,
    );
    $fieldKey = static fn (array $field): string => $field['table_name'] . '|' . $field['type_id'] . '|' . $field['type_name'] . '|' . $field['name'];

    // Tabellenschema nur importieren bei Neuinstallation oder wenn es sich im AddOn geändert hat –
    // sonst bleiben alle im Projekt geänderten Feldeinstellungen unberührt
    $tablesetHash = md5($tablesetContent);
    if ([] === $existingFields || $addon->getConfig('tableset_hash') !== $tablesetHash) {
        $snapshot = [];
        foreach ($existingFields as $field) {
            $snapshot[$fieldKey($field)] = $field;
        }

        rex_yform_manager_table_api::importTablesets($tablesetContent);

        // Projekt-Einstellungen bestehender Felder wiederherstellen (neue Felder behalten die Vorgaben)
        if ([] !== $snapshot && [] !== $projectColumns) {
            $importedFields = rex_sql::factory()->getArray(
                'SELECT * FROM ' . rex::getTable('yform_field') . ' WHERE table_name IN (' . $tablePlaceholders . ')',
                $kbTables,
            );
            foreach ($importedFields as $field) {
                $previous = $snapshot[$fieldKey($field)] ?? null;
                if (null === $previous) {
                    continue;
                }
                $restore = rex_sql::factory()->setTable(rex::getTable('yform_field'))->setWhere(['id' => $field['id']]);
                $changed = false;
                foreach ($projectColumns as $column) {
                    if ((string) $previous[$column] !== (string) $field[$column]) {
                        $restore->setValue($column, $previous[$column]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $restore->update();
                }
            }
        }

        $addon->setConfig('tableset_hash', $tablesetHash);
    }

    // Fließtext-Element kb_text einmalig (Umstieg auf 0.9.7+) auch in angepassten Listen erlauben;
    // danach entscheidet die Auswahl im Projekt (wer es abwählt, behält das)
    if (!$addon->getConfig('kb_text_allowed_once', false)) {
        $contentField = rex_sql::factory()->getArray(
            'SELECT id, allowed_elements FROM ' . rex::getTable('yform_field') . ' WHERE table_name = ? AND name = ? AND type_name = ?',
            [rex::getTable('knowledgebase_article'), 'content', 'content_builder'],
        )[0] ?? null;
        if (null !== $contentField) {
            $allowedElements = array_values(array_filter(array_map('trim', explode(',', (string) $contentField['allowed_elements']))));
            if ([] !== $allowedElements && !in_array('kb_text', $allowedElements, true)) {
                array_splice($allowedElements, (int) array_search('kb_chapter_nav', $allowedElements, true) + 1, 0, ['kb_text']);
                rex_sql::factory()->setQuery('UPDATE ' . rex::getTable('yform_field') . ' SET allowed_elements = ? WHERE id = ?', [implode(',', $allowedElements), $contentField['id']]);
            }
        }
        $addon->setConfig('kb_text_allowed_once', true);
    }
}

rex_sql_table::get(rex::getTable('knowledgebase'))
    ->ensureIndex(new rex_sql_index('knowledgebase_slug', ['slug'], rex_sql_index::UNIQUE))
    ->ensureIndex(new rex_sql_index('knowledgebase_online', ['online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_glossary_enabled', ['glossary_enabled']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_sort', ['article_sort_field', 'article_sort_order']))
    ->ensure();

rex_sql_table::get(rex::getTable('knowledgebase_article'))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_fulltext', ['title', 'nav_title', 'intro', 'search_text'], rex_sql_index::FULLTEXT))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_online', ['knowledgebase_id', 'online']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_parent', ['parent_id']))
    ->ensureIndex(new rex_sql_index('knowledgebase_article_base_parent_priority', ['knowledgebase_id', 'parent_id', 'priority']))
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
        if ($hasKey) {
            $updateModuleSql->setWhere(['key' => $moduleKey]);
        } else {
            $updateModuleSql->setWhere(['name' => $moduleName]);
        }
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