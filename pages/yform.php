<?php

/** @var rex_addon $this */

$yform = $this->getProperty('yform', []);
$config = $yform[rex_be_controller::getCurrentPage()] ?? [];

$tableNameConfig = $config['table_name'] ?? '';
if (is_string($tableNameConfig) && is_subclass_of($tableNameConfig, rex_yform_manager_dataset::class)) {
    $tableName = $tableNameConfig::table()->getTableName();
} elseif (is_string($tableNameConfig) && '' !== $tableNameConfig) {
    $tableName = rex::getTable($tableNameConfig);
} else {
    $tableName = '';
}

$tableName = rex_request('table_name', 'string', $tableName);
$currentPage = rex_be_controller::getCurrentPage();

if ('' === $tableName) {
    echo rex_view::error('YForm-Tabelle nicht konfiguriert.');
    return;
}

/** @phpstan-ignore-next-line */
$_REQUEST['table_name'] = $tableName;

rex_extension::register(
    'YFORM_MANAGER_DATA_PAGE_HEADER',
    static function (rex_extension_point $ep) use ($tableName, $currentPage) {
        if (rex_be_controller::getCurrentPage() !== $currentPage) {
            return $ep->getSubject();
        }

        /** @var rex_yform_manager $manager */
        $manager = $ep->getParam('yform');
        if ($manager->table instanceof rex_yform_manager_table && $manager->table->getTableName() === $tableName) {
            return '';
        }

        return $ep->getSubject();
    },
    rex_extension::EARLY,
);

if (is_file(rex_path::addon('yform', 'pages/manager.data_edit.php'))) {
    include rex_path::addon('yform', 'pages/manager.data_edit.php');
} else {
    include rex_path::plugin('yform', 'manager', 'pages/data_edit.php');
}