<?php

use FriendsOfREDAXO\Knowledgebase\AddonSettings;

if (!rex::getUser() || !rex::getUser()->isAdmin()) {
    echo rex_view::error('Nur Administratoren duerfen diese Seite bearbeiten.');
    return;
}

$csrfToken = rex_csrf_token::factory('knowledgebase_addon_settings');

if ('save' === rex_request('func', 'string') && $csrfToken->isValid()) {
    $menuTitle = trim(rex_request('menu_title', 'string', ''));
    $elementMode = rex_request('element_mode', 'string', 'merge');
    $searchRecentDays = rex_request('search_recent_days', 'int', 14);
    $searchMultiContextExcerpts = rex_request('search_multi_context_excerpts', 'int', 0) === 1;

    AddonSettings::setMenuTitle($menuTitle);
    AddonSettings::setElementMode($elementMode);
    AddonSettings::setSearchRecentDays($searchRecentDays);
    AddonSettings::setSearchMultiContextExcerpts($searchMultiContextExcerpts);

    echo rex_view::success('Einstellungen wurden gespeichert.');
} elseif ('save' === rex_request('func', 'string')) {
    echo rex_view::error('CSRF-Token ist ungueltig. Bitte Seite neu laden.');
}

$currentMenuTitle = (string) rex_addon::get('knowledgebase')->getConfig('menu_title', '');
$currentElementMode = AddonSettings::getElementMode();
$currentSearchRecentDays = AddonSettings::getSearchRecentDays();
$currentSearchMultiContextExcerpts = AddonSettings::isSearchMultiContextExcerptsEnabled();

$content = '';
$content .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$content .= $csrfToken->getHiddenField();
$content .= '<input type="hidden" name="func" value="save">';
$content .= '<fieldset>';
$content .= '<legend>Allgemein</legend>';
$content .= '<div class="form-group">';
$content .= '<label class="control-label" for="kb-menu-title">AddOn-Name im Backend-Menue</label>';
$content .= '<input class="form-control" type="text" id="kb-menu-title" name="menu_title" value="' . rex_escape($currentMenuTitle) . '" placeholder="Knowledge Base">';
$content .= '<p class="help-block">Leer lassen = Standardtitel aus Sprachdatei verwenden.</p>';
$content .= '</div>';
$content .= '</fieldset>';

$content .= '<fieldset>';
$content .= '<legend>Element-Auswahl</legend>';
$content .= '<div class="form-group">';
$content .= '<label class="control-label" for="kb-element-mode">Content-Builder-Modus</label>';
$content .= '<select class="form-control" id="kb-element-mode" name="element_mode">';
$content .= '<option value="merge"' . ('merge' === $currentElementMode ? ' selected' : '') . '>Merge (Original-Elemente + KB-Elemente)</option>';
$content .= '<option value="replace"' . ('replace' === $currentElementMode ? ' selected' : '') . '>Replace (nur KB-Elemente)</option>';
$content .= '</select>';
$content .= '<p class="help-block">Steuert den YForm Content Builder Elementmodus fuer dieses AddOn.</p>';
$content .= '</div>';
$content .= '</fieldset>';

$content .= '<fieldset>';
$content .= '<legend>Suche</legend>';
$content .= '<div class="form-group">';
$content .= '<label class="control-label" for="kb-search-recent-days">Badge: Kürzlich aktualisiert (Tage)</label>';
$content .= '<input class="form-control" type="number" min="1" max="365" step="1" id="kb-search-recent-days" name="search_recent_days" value="' . $currentSearchRecentDays . '">';
$content .= '<p class="help-block">Artikel innerhalb dieses Zeitraums erhalten in den Suchtreffern das Badge "Kürzlich aktualisiert". Standard: 14 Tage.</p>';
$content .= '</div>';
$content .= '<div class="checkbox">';
$content .= '<label><input type="checkbox" name="search_multi_context_excerpts" value="1"' . ($currentSearchMultiContextExcerpts ? ' checked' : '') . '> Volltextsuche: mehrere Fundstellen-Kontexte anzeigen</label>';
$content .= '<p class="help-block">Optional. Zeigt pro Treffer mehrere kurze Fundstellen-Ausschnitte statt nur eines einzelnen Teasers.</p>';
$content .= '</div>';
$content .= '</fieldset>';

$content .= '<p><button class="btn btn-save" type="submit">Speichern</button></p>';
$content .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Knowledgebase Einstellungen', false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
