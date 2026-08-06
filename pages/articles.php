<?php

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;
use FriendsOfREDAXO\Knowledgebase\ArticleHierarchyService;

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

/**
 * Baut eine Frontend-URL-Vorlage für die aktuell gewählte Wissensbasis.
 */
function knowledgebaseGetArticleUrlTemplate(int $knowledgebaseId): string
{
    if ($knowledgebaseId <= 0 || !KnowledgebaseUrl::hasProfile($knowledgebaseId)) {
        return '';
    }

    $baseUrl = trim(KnowledgebaseUrl::getBaseUrl($knowledgebaseId));
    if ('' === $baseUrl) {
        return '';
    }

    $path = trim((string) parse_url($baseUrl, PHP_URL_PATH));
    if ('' === $path) {
        return '';
    }

    $parentPath = dirname(rtrim($path, '/'));
    if ('.' === $parentPath || '' === $parentPath) {
        $parentPath = '/';
    }

    return rtrim($parentPath, '/') . '/___slug___/';
}

$articleUrlTemplate = knowledgebaseGetArticleUrlTemplate($selectedKnowledgebaseId);

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
$content .= ' <a class="btn btn-default" href="' . rex_escape(rex_url::backendPage($currentPage, [
    'table_name' => $tableName,
    'knowledgebase_id' => $selectedKnowledgebaseId,
    'func' => 'add',
    'rex_yform_filter' => ['knowledgebase_id' => $selectedKnowledgebaseId],
    'rex_yform_set' => ['knowledgebase_id' => $selectedKnowledgebaseId, 'parent_id' => 0],
])) . '"' . ($selectedKnowledgebaseId <= 0 ? ' style="pointer-events:none;opacity:.6;"' : '') . '><i class="rex-icon fa-plus"></i> Hauptseite anlegen</a>';
$content .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Beiträge nach Wissensbasis');
$fragment->setVar('content', $content, false);
echo $fragment->parse('core/page/section.php');

if ($selectedKnowledgebaseId <= 0) {
    echo rex_view::info('Bitte zuerst eine Wissensbasis auswählen. Danach werden nur die zugehörigen Beiträge angezeigt und bearbeitet.');
    return;
}

$currentArticleId = rex_request('data_id', 'int', 0);
if ($currentArticleId <= 0) {
    $currentArticleId = rex_request('id', 'int', 0);
}

$hierarchyOptions = [];
$hierarchyDepthById = [];
$hierarchyOrderById = [];
$hierarchyOrderedIds = [];

if (class_exists(rex_data_knowledgebase_article::class)) {
    $articles = ArticleHierarchyService::getArticlesForKnowledgebase($selectedKnowledgebaseId);
    $hierarchyOptions = ArticleHierarchyService::buildSelectableOptions($articles, $currentArticleId);
    foreach ($hierarchyOptions as $index => $hierarchyOption) {
        $articleId = (int) $hierarchyOption['id'];
        $hierarchyDepthById[$articleId] = (int) $hierarchyOption['depth'];
        $hierarchyOrderById[$articleId] = $index;
        $hierarchyOrderedIds[] = $articleId;
    }

    rex_view::setJsProperty('knowledgebaseArticleHierarchy', [
        'knowledgebaseId' => $selectedKnowledgebaseId,
        'currentArticleId' => $currentArticleId,
        'rootLabel' => 'Keine übergeordnete Seite',
        'options' => $hierarchyOptions,
    ]);
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
    static function (rex_extension_point $ep) use ($selectedKnowledgebaseId, $hierarchyOrderedIds) {
        $query = $ep->getSubject();
        if (!$query instanceof rex_yform_manager_query) {
            return $query;
        }

        if ($query->getTable()->getTableName() !== rex::getTable('knowledgebase_article')) {
            return $query;
        }

        $query->where('knowledgebase_id', $selectedKnowledgebaseId);

        if ([] !== $hierarchyOrderedIds) {
            $alias = $query->getTableAlias();
            $orderedIds = implode(',', array_map('intval', $hierarchyOrderedIds));
            $query->resetOrderBy()->orderByRaw('FIELD(`' . $alias . '`.`id`, ' . $orderedIds . ')');
        }

        return $query;
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_DATA_LIST',
    static function (rex_extension_point $ep) use ($tableName, $currentPage, $selectedKnowledgebaseId, $hierarchyDepthById, $hierarchyOrderById) {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table || $table->getTableName() !== $tableName) {
            return $ep->getSubject();
        }

        /** @var rex_yform_list $list */
        $list = $ep->getSubject();

        $list->removeColumn('knowledgebase_id');
        $list->removeColumn('search_text');
        $list->removeColumn('parent_id');

        $list->setColumnLabel('title', 'Seitenstruktur');
        $list->setColumnFormat('title', 'custom', static function (array $params) use ($currentPage, $selectedKnowledgebaseId, $hierarchyDepthById): string {
            /** @var rex_yform_list $rowList */
            $rowList = $params['list'];
            $articleId = (int) $rowList->getValue('id');
            $depth = $hierarchyDepthById[$articleId] ?? 0;
            $title = trim((string) $params['value']);
            $navTitle = trim((string) $rowList->getValue('nav_title'));
            $label = '' !== $navTitle ? $navTitle : $title;
            $editUrl = rex_url::backendPage($currentPage, [
                'table_name' => rex::getTable('knowledgebase_article'),
                'knowledgebase_id' => $selectedKnowledgebaseId,
                'func' => 'edit',
                'data_id' => $articleId,
                'rex_yform_filter' => ['knowledgebase_id' => $selectedKnowledgebaseId],
                'rex_yform_set' => ['knowledgebase_id' => $selectedKnowledgebaseId],
            ]);

            $navHint = ('' !== $navTitle && $navTitle !== $title)
                ? '<span class="kb-article-list-meta">Navi: ' . rex_escape($navTitle) . '</span>'
                : '';

            return '<div class="kb-article-list-title-wrap">'
                . '<span class="kb-article-list-indent kb-article-list-indent--depth-' . $depth . '" aria-hidden="true"></span>'
                . '<div class="kb-article-list-title-content">'
                . '<a class="kb-article-list-title" href="' . rex_escape($editUrl) . '">' . rex_escape($label) . '</a>'
                . $navHint
                . '</div>'
                . '</div>';
        });

        $list->setRowAttributes(static function (rex_yform_list $rowList) use ($hierarchyDepthById, $hierarchyOrderById): string {
            $articleId = (int) $rowList->getValue('id');
            $depth = $hierarchyDepthById[$articleId] ?? 0;
            $order = $hierarchyOrderById[$articleId] ?? 999999;

            return 'class="kb-article-list-row kb-article-list-row--depth-' . $depth . '" data-kb-article-id="' . $articleId . '" data-kb-article-depth="' . $depth . '" data-kb-article-order="' . $order . '"';
        });

        return $list;
    },
    rex_extension::EARLY,
);

rex_extension::register(
    'YFORM_DATA_LIST_ACTION_BUTTONS',
    static function (rex_extension_point $ep) use ($articleUrlTemplate, $currentPage, $selectedKnowledgebaseId): array {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table || $table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return is_array($ep->getSubject()) ? $ep->getSubject() : [];
        }

        $actionButtons = is_array($ep->getSubject()) ? $ep->getSubject() : [];

        if ('' !== $articleUrlTemplate) {
            $actionButtons['frontend'] = [
                'params' => [],
                'content' => '<i class="rex-icon fa-external-link"></i> Frontend öffnen',
                'url' => $articleUrlTemplate,
                'attributes' => [
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                ],
            ];
        }

        $actionButtons['add_child'] = [
            'params' => [],
            'content' => '<i class="rex-icon fa-level-down"></i> Unterseite anlegen',
            'url' => rex_url::backendPage($currentPage, [
                'table_name' => rex::getTable('knowledgebase_article'),
                'knowledgebase_id' => $selectedKnowledgebaseId,
                'func' => 'add',
                'rex_yform_filter' => ['knowledgebase_id' => $selectedKnowledgebaseId],
                'rex_yform_set' => ['knowledgebase_id' => $selectedKnowledgebaseId, 'parent_id' => '___id___'],
            ]),
        ];

        return $actionButtons;
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
