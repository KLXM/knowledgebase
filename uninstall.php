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

$moduleLookupSql = rex_sql::factory();
if ($hasKey) {
    $moduleLookupSql->setQuery(
        'SELECT id, name FROM ' . $moduleTable . ' WHERE `key` = :module_key',
        ['module_key' => $moduleKey],
    );
} else {
    $moduleLookupSql->setQuery(
        'SELECT id, name FROM ' . $moduleTable . ' WHERE name = :module_name',
        ['module_name' => $moduleName],
    );
}

$moduleIds = [];
foreach ($moduleLookupSql as $moduleRow) {
    $moduleIds[] = (int) $moduleRow->getValue('id');
}

if ([] !== $moduleIds) {
    $sliceSql = rex_sql::factory();
    $sliceSql->setQuery(
        'SELECT s.id AS slice_id, s.article_id, s.clang_id, s.ctype_id, s.priority, a.name AS article_name, c.name AS clang_name '
        . 'FROM ' . rex::getTable('article_slice') . ' s '
        . 'LEFT JOIN ' . rex::getTable('article') . ' a ON a.id = s.article_id AND a.clang_id = s.clang_id '
        . 'LEFT JOIN ' . rex::getTable('clang') . ' c ON c.id = s.clang_id '
        . 'WHERE s.module_id IN (' . implode(',', array_map('intval', $moduleIds)) . ') '
        . 'ORDER BY s.article_id, s.clang_id, s.ctype_id, s.priority',
    );

    if ($sliceSql->getRows() > 0) {
        $usageLines = [];
        foreach ($sliceSql as $sliceRow) {
            $usageLines[] = sprintf(
                'Slice #%d in Artikel "%s" (article_id=%d, clang_id=%d, ctype_id=%d, priority=%d)%s',
                (int) $sliceRow->getValue('slice_id'),
                (string) ($sliceRow->getValue('article_name') ?: 'unbekannt'),
                (int) $sliceRow->getValue('article_id'),
                (int) $sliceRow->getValue('clang_id'),
                (int) $sliceRow->getValue('ctype_id'),
                (int) $sliceRow->getValue('priority'),
                '' !== (string) $sliceRow->getValue('clang_name') ? ' [Sprache: ' . (string) $sliceRow->getValue('clang_name') . ']' : '',
            );
        }

        throw new rex_functional_exception(
            'Das Modul "' . $moduleName . '" ist noch in Verwendung und kann nicht entfernt werden. '
            . 'Bitte zuerst alle Slices mit diesem Modul umstellen oder löschen. Gefunden in: ' . PHP_EOL
            . implode(PHP_EOL, $usageLines),
        );
    }

    $moduleDeleteSql = rex_sql::factory();
    $moduleDeleteSql->setQuery(
        'DELETE FROM ' . $moduleTable . ' WHERE id IN (' . implode(',', array_map('intval', $moduleIds)) . ')',
    );
}

foreach ($tables as $tableName) {
    $dropSql = rex_sql::factory();
    $dropSql->setQuery('DROP TABLE IF EXISTS `' . $tableName . '`');
}

rex_delete_cache();
