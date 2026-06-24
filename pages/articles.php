<?php

$tableName = rex::getTable('knowledgebase_article');
$currentPage = rex_be_controller::getCurrentPage();
$sessionKey = 'knowledgebase_articles_selected_kb';
$selectedKnowledgebaseId = rex_request('knowledgebase_id', 'int', 0);

if ($selectedKnowledgebaseId <= 0) {
    $requestFilter = rex_request('rex_yform_filter', 'array', []);
    if (isset($requestFilter['knowledgebase_id'])) {
        $selectedKnowledgebaseId = (int) $requestFilter['knowledgebase_id'];
    }
}

if ($selectedKnowledgebaseId <= 0) {
    $requestSet = rex_request('rex_yform_set', 'array', []);
    if (isset($requestSet['knowledgebase_id'])) {
        $selectedKnowledgebaseId = (int) $requestSet['knowledgebase_id'];
    }
}

if ($selectedKnowledgebaseId <= 0) {
    $selectedKnowledgebaseId = rex_session($sessionKey, 'int', 0);
}

$knowledgebaseOptions = [];
if (class_exists('rex_data_knowledgebase')) {
    $knowledgebases = rex_yform_manager_dataset::query(rex::getTable('knowledgebase'))
        ->orderBy('title')
        ->find();

    foreach ($knowledgebases as $knowledgebase) {
        if (!$knowledgebase instanceof rex_yform_manager_dataset) {
            continue;
        }

        $knowledgebaseOptions[(int) $knowledgebase->getId()] = (string) $knowledgebase->getValue('title');
    }
}

if ($selectedKnowledgebaseId > 0 && !array_key_exists($selectedKnowledgebaseId, $knowledgebaseOptions)) {
    $selectedKnowledgebaseId = 0;
}

if ($selectedKnowledgebaseId > 0) {
    rex_set_session($sessionKey, $selectedKnowledgebaseId);
}

$content = '<form method="get" action="' . rex_url::currentBackendPage() . '" class="form-inline" style="margin-bottom: 15px;">';
$content .= '<input type="hidden" name="page" value="' . rex_escape($currentPage) . '">';
$content .= '<div class="form-group">';
$content .= '<label for="kb-article-kb-filter" style="margin-right:8px;">Wissensbasis</label>';
$content .= '<select id="kb-article-kb-filter" name="knowledgebase_id" class="form-control" style="min-width:280px;">';
$content .= '<option value="0">Bitte Wissensbasis wählen</option>';
foreach ($knowledgebaseOptions as $knowledgebaseId => $knowledgebaseTitle) {
    $selected = $knowledgebaseId === $selectedKnowledgebaseId ? ' selected' : '';
    $content .= '<option value="' . $knowledgebaseId . '"' . $selected . '>' . rex_escape($knowledgebaseTitle) . '</option>';
}
$content .= '</select>';
$content .= '</div>';
$content .= '<button class="btn btn-primary" type="submit" style="margin-left:8px;">Anzeigen</button>';
$content .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Beiträge nach Wissensbasis');
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');

if ($selectedKnowledgebaseId <= 0) {
    echo rex_view::info('Bitte zuerst eine Wissensbasis auswählen. Danach werden nur die zugehörigen Beiträge angezeigt und bearbeitet.');
    return;
}

echo '<style>#yform-data_edit-rex_knowledgebase_article-knowledgebase_id,#yform-data_edit-rex_knowledgebase_article-search_text{display:none;}</style>';

/** @phpstan-ignore-next-line */
$_REQUEST['table_name'] = rex_request('table_name', 'string', $tableName);

rex_extension::register(
    'YFORM_MANAGER_DATA_PAGE',
    static function (rex_extension_point $ep) use ($selectedKnowledgebaseId, $tableName) {
        $manager = $ep->getSubject();
        if (!$manager instanceof rex_yform_manager) {
            return $manager;
        }

        if (!$manager->table instanceof rex_yform_manager_table || $manager->table->getTableName() !== $tableName) {
            return $manager;
        }

        $manager->setLinkVars(['knowledgebase_id' => $selectedKnowledgebaseId]);

        return $manager;
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_MANAGER_DATA_EDIT_FILTER',
    static function (rex_extension_point $ep) use ($selectedKnowledgebaseId): array {
        $subject = $ep->getSubject();
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table || $table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return is_array($subject) ? $subject : [];
        }

        $filter = is_array($subject) ? $subject : [];
        $filter['knowledgebase_id'] = $selectedKnowledgebaseId;

        return $filter;
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_MANAGER_DATA_EDIT_SET',
    static function (rex_extension_point $ep) use ($selectedKnowledgebaseId): array {
        $subject = $ep->getSubject();
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table || $table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return is_array($subject) ? $subject : [];
        }

        $setValues = is_array($subject) ? $subject : [];
        $setValues['knowledgebase_id'] = $selectedKnowledgebaseId;

        return $setValues;
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_DATA_LIST_QUERY',
    static function (rex_extension_point $ep) use ($selectedKnowledgebaseId) {
        $query = $ep->getSubject();
        if (!$query instanceof rex_yform_manager_query) {
            return $query;
        }

        if ($query->getTable()->getTableName() !== rex::getTable('knowledgebase_article')) {
            return $query;
        }

        return $query->where('knowledgebase_id', $selectedKnowledgebaseId);
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_MANAGER_DATA_PAGE_HEADER',
    static function (rex_extension_point $ep) use ($currentPage): string {
        if (rex_be_controller::getCurrentPage() !== $currentPage) {
            return (string) $ep->getSubject();
        }

        /** @var rex_yform_manager $manager */
        $manager = $ep->getParam('yform');
        if ($manager->table instanceof rex_yform_manager_table && $manager->table->getTableName() === rex::getTable('knowledgebase_article')) {
            return '';
        }

        return (string) $ep->getSubject();
    },
    rex_extension::EARLY,
);

if (is_file(rex_path::addon('yform', 'pages/manager.data_edit.php'))) {
    include rex_path::addon('yform', 'pages/manager.data_edit.php');
} else {
    include rex_path::plugin('yform', 'manager', 'pages/data_edit.php');
}
