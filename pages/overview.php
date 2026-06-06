<?php

$addon = rex_addon::get('knowledgebase');

$getCount = static function (string $table, ?string $condition = null): int {
    $sql = rex_sql::factory();
    $query = 'SELECT COUNT(*) AS cnt FROM ' . $table;
    if (is_string($condition) && '' !== $condition) {
        $query .= ' WHERE ' . $condition;
    }

    $sql->setQuery($query);

    return (int) $sql->getValue('cnt');
};

$baseTable = rex::getTable('knowledgebase');
$articleTable = rex::getTable('knowledgebase_article');
$glossaryTable = rex::getTable('knowledgebase_glossary');
$imageTable = rex::getTable('knowledgebase_interactive_image');

$articleCsrfParams = [];
$articleTableObj = rex_yform_manager_table::get($articleTable);
if ($articleTableObj instanceof rex_yform_manager_table) {
    $articleCsrfParams = rex_csrf_token::factory($articleTableObj->getCSRFKey())->getUrlParams();
}

$baseCount = $getCount($baseTable);
$baseActiveCount = $getCount($baseTable, 'status = 1');
$articleCount = $getCount($articleTable);
$articleOnlineCount = $getCount($articleTable, 'online = 1');
$glossaryCount = $getCount($glossaryTable);
$glossaryOnlineCount = $getCount($glossaryTable, 'online = 1');
$imageCount = $getCount($imageTable);
$imageOnlineCount = $getCount($imageTable, 'online = 1');

$recentSql = rex_sql::factory();
$recentSql->setQuery(
    'SELECT id, knowledgebase_id, title, online, createdate, createuser, updatedate, updateuser
        FROM ' . $articleTable . '
        ORDER BY updatedate DESC, id DESC
        LIMIT 8'
);
$recentArticles = $recentSql->getArray();

$basesSql = rex_sql::factory();
$basesSql->setQuery('SELECT id, title FROM ' . $baseTable . ' ORDER BY title');
$baseTitleMap = [];
foreach ($basesSql->getArray() as $baseRow) {
    $baseTitleMap[(int) $baseRow['id']] = (string) $baseRow['title'];
}

$formatDate = static function (string $value): string {
    $trimmed = trim($value);
    if ('' === $trimmed || '0000-00-00 00:00:00' === $trimmed || '0000-00-00' === $trimmed) {
        return '';
    }

    try {
        return (string) rex_formatter::strftime($trimmed, 'date');
    } catch (Throwable $exception) {
        return '';
    }
};

$stats = [
    [
        'title' => 'Wissensbasen',
        'total' => $baseCount,
        'online' => $baseActiveCount,
        'label' => 'aktiv',
        'link' => rex_url::backendPage('knowledgebase/bases'),
    ],
    [
        'title' => 'Beiträge',
        'total' => $articleCount,
        'online' => $articleOnlineCount,
        'label' => 'online',
        'link' => rex_url::backendPage('knowledgebase/articles'),
    ],
    [
        'title' => 'Glossar-Einträge',
        'total' => $glossaryCount,
        'online' => $glossaryOnlineCount,
        'label' => 'online',
        'link' => rex_url::backendPage('knowledgebase/glossary'),
    ],
    [
        'title' => 'Interaktive Bilder',
        'total' => $imageCount,
        'online' => $imageOnlineCount,
        'label' => 'online',
        'link' => rex_url::backendPage('knowledgebase/interactive_images'),
    ],
];

$dashboard = '';
$dashboard .= '<style>';
$dashboard .= '.kb-dashboard-card{border:1px solid #e6e9ef;border-radius:8px;padding:14px;background:#fff;box-shadow:0 6px 14px rgba(15,35,60,.06);animation:kbFadeIn .45s ease both;}';
$dashboard .= '.kb-dashboard-meta{color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:.06em;}';
$dashboard .= '.kb-dashboard-value{font-size:30px;line-height:1.1;font-weight:700;margin:8px 0 10px;}';
$dashboard .= '.kb-dashboard-link{margin-top:10px;display:inline-block;}';
$dashboard .= '.kb-dashboard-list td{vertical-align:middle;}';
$dashboard .= '@keyframes kbFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}';
$dashboard .= '</style>';

$dashboard .= '<p>' . rex_escape($addon->i18n('knowledgebase_overview_intro')) . '</p>';
$dashboard .= '<div class="row">';

foreach ($stats as $index => $stat) {
    $delay = ($index + 1) * 0.06;
    $ratio = $stat['total'] > 0 ? (int) round(($stat['online'] / $stat['total']) * 100) : 0;

    $dashboard .= '<div class="col-sm-6 col-lg-3">';
    $dashboard .= '<div class="kb-dashboard-card" style="animation-delay:' . rex_escape((string) $delay) . 's">';
    $dashboard .= '<div class="kb-dashboard-meta">' . rex_escape($stat['title']) . '</div>';
    $dashboard .= '<div class="kb-dashboard-value">' . rex_escape((string) $stat['total']) . '</div>';
    $dashboard .= '<div class="small text-muted">' . rex_escape((string) $stat['online']) . ' ' . rex_escape($stat['label']) . '</div>';
    $dashboard .= '<div class="progress" style="margin:8px 0 0;height:8px;">';
    $dashboard .= '<div class="progress-bar progress-bar-striped active" role="progressbar" style="width:' . $ratio . '%;"></div>';
    $dashboard .= '</div>';
    $safeStatLink = html_entity_decode((string) $stat['link'], ENT_QUOTES, 'UTF-8');
    $dashboard .= '<a class="btn btn-default btn-xs kb-dashboard-link" href="' . rex_escape($safeStatLink) . '">Öffnen</a>';
    $dashboard .= '</div>';
    $dashboard .= '</div>';
}

$dashboard .= '</div>';

$dashboard .= '<div class="row" style="margin-top:16px;">';
$dashboard .= '<div class="col-sm-12">';
$dashboard .= '<div class="panel panel-default">';
$dashboard .= '<div class="panel-heading"><strong>Neueste Beiträge</strong></div>';
$dashboard .= '<div class="table-responsive">';
$dashboard .= '<table class="table table-striped kb-dashboard-list" style="margin-bottom:0;">';
$dashboard .= '<thead><tr><th>Titel</th><th>Wissensbasis</th><th>Status</th><th>Erstellt von</th><th>Zuletzt bearbeitet</th><th>Aktion</th></tr></thead><tbody>';

if ([] === $recentArticles) {
    $dashboard .= '<tr><td colspan="6" class="text-muted">Noch keine Beiträge vorhanden.</td></tr>';
} else {
    foreach ($recentArticles as $row) {
        $articleId = (int) ($row['id'] ?? 0);
        $kbId = (int) ($row['knowledgebase_id'] ?? 0);
        $title = (string) ($row['title'] ?? '');
        $isOnline = '1' === (string) ($row['online'] ?? '0');
        $createUser = trim((string) ($row['createuser'] ?? ''));
        $updateUser = trim((string) ($row['updateuser'] ?? ''));
        $createDate = (string) ($row['createdate'] ?? '');
        $updateDate = (string) ($row['updatedate'] ?? '');

        $statusBadge = $isOnline
            ? '<span class="label label-success">Online</span>'
            : '<span class="label label-default">Offline</span>';

        $createdBy = '' !== $createUser ? $createUser : '-';
        $formattedCreateDate = $formatDate($createDate);
        if ('' !== $formattedCreateDate) {
            $createdBy .= '<br><small class="text-muted">' . rex_escape($formattedCreateDate) . '</small>';
        }

        $updatedBy = '' !== $updateUser ? $updateUser : '-';
        $formattedUpdateDate = $formatDate($updateDate);
        if ('' !== $formattedUpdateDate) {
            $updatedBy .= '<br><small class="text-muted">' . rex_escape($formattedUpdateDate) . '</small>';
        }

        // Auf eingebettete Knowledgebase-Artikelseite verlinken,
        // inkl. gültigem YForm-CSRF-Token für Edit-Requests.
        $editUrl = rex_url::backendPage('knowledgebase/articles', [
            'table_name' => $articleTable,
            'knowledgebase_id' => $kbId,
            'func' => 'edit',
            'data_id' => $articleId,
            'rex_yform_filter' => ['knowledgebase_id' => $kbId],
            'rex_yform_set' => ['knowledgebase_id' => $kbId],
        ] + $articleCsrfParams);
        $safeEditUrl = html_entity_decode($editUrl, ENT_QUOTES, 'UTF-8');

        $dashboard .= '<tr>';
        $dashboard .= '<td><strong>' . rex_escape($title) . '</strong></td>';
        $dashboard .= '<td>' . rex_escape((string) ($baseTitleMap[$kbId] ?? 'Unbekannt')) . '</td>';
        $dashboard .= '<td>' . $statusBadge . '</td>';
        $dashboard .= '<td>' . $createdBy . '</td>';
        $dashboard .= '<td>' . $updatedBy . '</td>';
        $dashboard .= '<td><a class="btn btn-primary btn-xs" href="' . rex_escape($safeEditUrl) . '">Bearbeiten</a></td>';
        $dashboard .= '</tr>';
    }
}

$dashboard .= '</tbody></table>';
$dashboard .= '</div>';
$dashboard .= '</div>';
$dashboard .= '</div>';
$dashboard .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('knowledgebase_overview_title'), false);
$fragment->setVar('body', $dashboard, false);
echo $fragment->parse('core/page/section.php');
