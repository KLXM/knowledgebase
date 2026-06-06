<?php

declare(strict_types=1);

$tables = [
    rex::getTable('knowledgebase_article'),
    rex::getTable('knowledgebase_glossary'),
    rex::getTable('knowledgebase_interactive_image'),
    rex::getTable('knowledgebase'),
];

if (rex_addon::get('yform')->isAvailable()) {
    rex_yform_manager_table::deleteCache();

    foreach ($tables as $tableName) {
        $fieldSql = rex_sql::factory();
        $fieldSql->setQuery(
            'DELETE FROM ' . rex::getTable('yform_field') . ' WHERE table_name = :table_name',
            ['table_name' => $tableName],
        );

        $tableSql = rex_sql::factory();
        $tableSql->setQuery(
            'DELETE FROM ' . rex::getTable('yform_table') . ' WHERE table_name = :table_name',
            ['table_name' => $tableName],
        );
    }

    rex_yform_manager_table::deleteCache();
}

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
    $moduleSql->setQuery(
        'DELETE FROM ' . $moduleTable . ' WHERE `key` = :module_key',
        ['module_key' => $moduleKey],
    );
} else {
    $moduleSql->setQuery(
        'DELETE FROM ' . $moduleTable . ' WHERE name = :module_name',
        ['module_name' => $moduleName],
    );
}

foreach ($tables as $tableName) {
    $dropSql = rex_sql::factory();
    $dropSql->setQuery('DROP TABLE IF EXISTS `' . $tableName . '`');
}

$addon = rex_addon::get('knowledgebase');
$config = $addon->getConfig();
if (is_array($config)) {
    foreach (array_keys($config) as $configKey) {
        if (is_string($configKey) && '' !== $configKey) {
            $addon->removeConfig($configKey);
        }
    }
}
rex_delete_cache();
