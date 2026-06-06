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
$dashboard .= '.kb-dashboard-card{position:relative;overflow:hidden;border:1px solid #e6e9ef;border-radius:10px;padding:14px;background:linear-gradient(160deg,#ffffff 0%,#f7fbff 100%);box-shadow:0 8px 20px rgba(15,35,60,.08);opacity:0;transform:translateY(14px) scale(.98);transition:transform .2s ease,box-shadow .25s ease,border-color .25s ease;}';
$dashboard .= '.kb-dashboard-card:before{content:"";position:absolute;left:0;top:0;right:0;height:3px;background:linear-gradient(90deg,#1f7fd0 0%,#4ea6e9 100%);opacity:.85;}';
$dashboard .= '.kb-dashboard-card:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(15,35,60,.12);border-color:#cbd8ea;}';
$dashboard .= '.kb-dashboard-card.is-visible{animation:kbCardIn .55s cubic-bezier(.22,.61,.36,1) forwards;}';
$dashboard .= '.kb-dashboard-meta{color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:.06em;}';
$dashboard .= '.kb-dashboard-value{font-size:30px;line-height:1.1;font-weight:700;margin:8px 0 10px;}';
$dashboard .= '.kb-dashboard-link{margin-top:10px;display:inline-block;transition:transform .18s ease,box-shadow .2s ease;}';
$dashboard .= '.kb-dashboard-link:hover{transform:translateY(-1px);box-shadow:0 4px 10px rgba(15,35,60,.18);}';
$dashboard .= '.kb-dashboard-list td{vertical-align:middle;}';
$dashboard .= '.kb-dashboard-panel{opacity:0;transform:translateY(16px);}';
$dashboard .= '.kb-dashboard-panel.is-visible{animation:kbPanelIn .5s ease forwards;}';
$dashboard .= '.kb-dashboard-row{opacity:0;transform:translateX(-10px);}';
$dashboard .= '.kb-dashboard-row.is-visible{animation:kbRowIn .35s ease forwards;}';
$dashboard .= '@keyframes kbCardIn{0%{opacity:0;transform:translateY(14px) scale(.98)}100%{opacity:1;transform:translateY(0) scale(1)}}';
$dashboard .= '@keyframes kbPanelIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}';
$dashboard .= '@keyframes kbRowIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}';
$dashboard .= '@media (prefers-reduced-motion: reduce){.kb-dashboard-card,.kb-dashboard-panel,.kb-dashboard-row{animation:none !important;opacity:1 !important;transform:none !important;transition:none !important;}}';
$dashboard .= '</style>';

$dashboard .= '<p>' . rex_escape($addon->i18n('knowledgebase_overview_intro')) . '</p>';
$dashboard .= '<div class="row">';

foreach ($stats as $index => $stat) {
    $ratio = $stat['total'] > 0 ? (int) round(($stat['online'] / $stat['total']) * 100) : 0;

    $dashboard .= '<div class="col-sm-6 col-lg-3">';
    $dashboard .= '<div class="kb-dashboard-card" data-card-index="' . rex_escape((string) $index) . '">';
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
$dashboard .= '<div class="panel panel-default kb-dashboard-panel">';
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

        $dashboard .= '<tr class="kb-dashboard-row">';
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

$dashboard .= '<script>';
$dashboard .= '(function(){';
$dashboard .= 'var prefersReduced=false;';
$dashboard .= 'try{prefersReduced=window.matchMedia("(prefers-reduced-motion: reduce)").matches;}catch(e){}';
$dashboard .= 'if(prefersReduced){return;}';
$dashboard .= 'var cards=document.querySelectorAll(".kb-dashboard-card");';
$dashboard .= 'var panel=document.querySelector(".kb-dashboard-panel");';
$dashboard .= 'var rows=document.querySelectorAll(".kb-dashboard-row");';
$dashboard .= 'cards.forEach(function(card,index){window.setTimeout(function(){card.classList.add("is-visible");},80+(index*90));});';
$dashboard .= 'if(panel){window.setTimeout(function(){panel.classList.add("is-visible");},420);}';
$dashboard .= 'rows.forEach(function(row,index){window.setTimeout(function(){row.classList.add("is-visible");},540+(index*55));});';
$dashboard .= '})();';
$dashboard .= '</script>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('knowledgebase_overview_title'), false);
$fragment->setVar('body', $dashboard, false);
echo $fragment->parse('core/page/section.php');
