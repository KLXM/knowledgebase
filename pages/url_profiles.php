<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;
use FriendsOfREDAXO\Knowledgebase\UrlProfileManager;

// URL-Addon muss verfügbar sein
if (!rex_addon::get('url')->isAvailable()) {
    echo rex_view::warning('Das URL-Addon ist nicht installiert oder aktiviert. Diese Seite ist nur nutzbar, wenn das URL-Addon aktiv ist.');
    return;
}

$csrfToken = rex_csrf_token::factory('knowledgebase_url_profiles');
$postedMappingsByKbId = [];

// Formular-Verarbeitung
if (rex_post('kb_url_profiles_submit', 'string', '') === 'save') {
    if (!$csrfToken->isValid()) {
        echo rex_view::error(rex_i18n::msg('csrf_token_invalid'));
    } else {
        $rawMappings = rex_request('mappings', 'array', []);
        $sanitized = [];
        $usedArticleIds = [];
        $duplicateArticleIds = [];

        foreach ($rawMappings as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $kbId = (int) ($entry['kb_id'] ?? 0);
            $articleId = (int) ($entry['article_id'] ?? 0);
            $clangId = max(1, (int) ($entry['clang_id'] ?? 1));

            if ($kbId <= 0) {
                continue;
            }

            // Für die Formular-Rückgabe immer merken, auch wenn article_id = 0 ist.
            $postedMappingsByKbId[$kbId] = [
                'kb_id' => $kbId,
                'article_id' => $articleId,
                'clang_id' => $clangId,
            ];

            if ($articleId > 0) {
                if (isset($usedArticleIds[$articleId])) {
                    $duplicateArticleIds[$articleId] = true;
                }
                $usedArticleIds[$articleId] = true;
                $sanitized[] = ['kb_id' => $kbId, 'article_id' => $articleId, 'clang_id' => $clangId];
            }
        }

        if ([] !== $duplicateArticleIds) {
            $duplicateIds = implode(', ', array_map('strval', array_keys($duplicateArticleIds)));
            echo rex_view::error('Ein REDAXO-Artikel darf nur einer Wissensbasis zugeordnet werden. Doppelt ausgewählte Artikel-ID(s): ' . $duplicateIds . '.');
        } else {
            UrlProfileManager::saveMappings($sanitized);
            echo rex_view::success('URL-Profile wurden gespeichert und URLs wurden neu aufgebaut.');
        }
    }
}

// Daten laden
$moduleArticles = UrlProfileManager::findModuleArticles();
$currentMappings = UrlProfileManager::getMappings();
$mappingsByKbId = [];
foreach ($currentMappings as $m) {
    $mappingsByKbId[$m['kb_id']] = $m;
}

// Nach Validierungsfehlern sollen die zuletzt abgeschickten Werte sichtbar bleiben.
foreach ($postedMappingsByKbId as $kbId => $mapping) {
    $mappingsByKbId[$kbId] = $mapping;
}

// Wissensbasen laden
$baseSql = rex_sql::factory();
$knowledgebases = $baseSql->getArray(
    'SELECT id, title FROM ' . rex::getTable('knowledgebase') . ' ORDER BY title',
);

// Sprachen
$clangs = rex_clang::getAll();

// Inhalt aufbauen
$content = '';

if ([] === $moduleArticles) {
    $content .= rex_view::info('Kein REDAXO-Artikel gefunden, in dem das Knowledgebase-Modul verwendet wird. Binde das Modul zuerst in einer Seite ein.');
} else {
    $content .= '<form method="post" action="' . rex_escape(rex_url::currentBackendPage()) . '">';
    $content .= $csrfToken->getHiddenField();
    $content .= '<input type="hidden" name="kb_url_profiles_submit" value="save">';

    $content .= '<table class="table table-striped">';
    $content .= '<thead><tr>';
    $content .= '<th>Wissensbasis</th>';
    $content .= '<th>REDAXO-Artikel (Modul-Seite)</th>';
    $content .= '<th>Sprache</th>';
    $content .= '<th>URL-Profil aktiv</th>';
    $content .= '<th>Aktuelle URL</th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';

    foreach ($knowledgebases as $kb) {
        $kbId = (int) $kb['id'];
        $kbTitle = (string) $kb['title'];
        $existingMapping = $mappingsByKbId[$kbId] ?? null;
        $hasProfile = KnowledgebaseUrl::hasProfile($kbId);

        // Artikel-Select-Optionen
        $articleOptions = '<option value="0">— bitte wählen —</option>';
        foreach ($moduleArticles as $artRow) {
            $selected = $existingMapping && $existingMapping['article_id'] === $artRow['article_id'] ? ' selected' : '';
            $articleOptions .= '<option value="' . $artRow['article_id'] . '"' . $selected . '>'
                . rex_escape($artRow['article_name']) . ' [' . $artRow['article_id'] . ', Sprache ' . $artRow['clang_id'] . ']'
                . '</option>';
        }

        // Sprachen-Select
        $clangOptions = '';
        foreach ($clangs as $clang) {
            $selected = $existingMapping && $existingMapping['clang_id'] === $clang->getId() ? ' selected' : '';
            $clangOptions .= '<option value="' . $clang->getId() . '"' . $selected . '>'
                . rex_escape($clang->getName()) . '</option>';
        }

        // Aktuelle URL-Vorschau
        $previewUrl = $hasProfile ? KnowledgebaseUrl::getBaseUrl($kbId) : '(kein Profil)';

        $content .= '<tr>';
        $content .= '<td><strong>' . rex_escape($kbTitle) . '</strong>';
        // Versteckte kb_id
        $content .= '<input type="hidden" name="mappings[' . $kbId . '][kb_id]" value="' . $kbId . '">';
        $content .= '</td>';
        $content .= '<td><select class="form-control" name="mappings[' . $kbId . '][article_id]">' . $articleOptions . '</select></td>';
        $content .= '<td><select class="form-control" name="mappings[' . $kbId . '][clang_id]">' . $clangOptions . '</select></td>';
        $content .= '<td>';
        $content .= $hasProfile
            ? '<span class="label label-success">Aktiv</span>'
            : '<span class="label label-default">Kein Profil</span>';
        $content .= '</td>';
        $content .= '<td><code>' . rex_escape($previewUrl) . '</code></td>';
        $content .= '</tr>';
    }

    $content .= '</tbody></table>';
    $content .= '<div class="form-group" style="margin-top:16px;">';
    $content .= '<button class="btn btn-save" type="submit">';
    $content .= rex_i18n::msg('form_save');
    $content .= '</button>';
    $content .= '</div>';
    $content .= '</form>';

    $content .= '<div class="alert alert-info" style="margin-top:16px;">';
    $content .= '<strong>Hinweis:</strong> Wenn du einen Artikel auswählst und speicherst, wird automatisch ein URL-Profil im URL-Addon erstellt und die URLs werden sofort aufgebaut. Die Knowledgebase-Beiträge landen damit in der Sitemap. Wenn du den Artikel-Select auf "— bitte wählen —" stellst, wird ein vorhandenes Profil gelöscht.';
    $content .= '</div>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', 'URL-Profile & Sitemap', false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
