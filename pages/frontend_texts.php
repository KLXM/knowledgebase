<?php

use FriendsOfREDAXO\Knowledgebase\FrontendI18n;

$addon = rex_addon::get('knowledgebase');
$csrfToken = rex_csrf_token::factory('knowledgebase_frontend_i18n');

if ('save' === rex_request('func', 'string') && $csrfToken->isValid()) {
    $posted = rex_request('translations', 'array', []);
    $translations = [];

    foreach ($posted as $langCode => $entries) {
        if (!is_string($langCode) || !is_array($entries)) {
            continue;
        }

        foreach (FrontendI18n::getManagedKeys() as $key) {
            $translations[$langCode][$key] = trim((string) ($entries[$key] ?? ''));
        }
    }

    FrontendI18n::saveConfigTranslations($translations);
    echo rex_view::success('Frontend-Texte wurden gespeichert.');
} elseif ('save' === rex_request('func', 'string')) {
    echo rex_view::error('CSRF-Token ist ungültig. Bitte Seite neu laden.');
}

$currentConfig = FrontendI18n::getConfigTranslations();
$languages = FrontendI18n::getActiveFrontendLanguages();

if (count($languages) === 0) {
    echo rex_view::warning('Keine aktiven Frontend-Sprachen gefunden.');
    return;
}

$labels = [
    'knowledgebase_eyebrow' => 'Titelzeile: Wissensbasis (z. B. Knowledge Base)',
    'knowledgebase_frontend_missing_base' => 'Fehlertext: Wissensbasis nicht verfügbar',
    'knowledgebase_search_label' => 'Label: Suche',
    'knowledgebase_search_submit' => 'Button: Volltextsuche',
    'knowledgebase_suggest_unavailable' => 'Hinweis: Autosuggest nicht verfügbar',
    'knowledgebase_suggest_empty' => 'Hinweis: Keine Vorschläge (Autosuggest)',
    'knowledgebase_nav_toggle' => 'Button: Kapitel (mobil)',
    'knowledgebase_nav_title' => 'Titel: Inhaltsverzeichnis',
    'knowledgebase_nav_filter_label' => 'Label: Kapitel filtern',
    'knowledgebase_nav_filter_placeholder' => 'Placeholder: Kapitel filtern',
    'knowledgebase_nav_expand_all' => 'Button: Alle aufklappen',
    'knowledgebase_nav_collapse_all' => 'Button: Alle einklappen',
    'knowledgebase_nav_glossary' => 'Button/Link: Glossar',
    'knowledgebase_nav_glossary_badge' => 'Badge: Glossar (z. B. A-Z)',
    'knowledgebase_nav_all_levels' => 'Button/Link: Inhaltsverzeichnis (alle Ebenen)',
    'knowledgebase_nav_tags' => 'Titel: Tags-Filter',
    'knowledgebase_nav_tags_all' => 'Option: Alle Tags',
    'knowledgebase_frontend_missing_article' => 'Hinweis: Kein Beitrag vorhanden',
    'knowledgebase_article_label' => 'Label: Kapitel',
    'knowledgebase_glossary_title' => 'Titel: Glossar',
    'knowledgebase_glossary_empty' => 'Hinweis: Glossar leer',
    'knowledgebase_search_results' => 'Titel: Suchergebnisse',
    'knowledgebase_search_empty' => 'Hinweis: Keine Treffer',
];

echo '<form action="' . rex_url::currentBackendPage() . '" method="post">';
echo $csrfToken->getHiddenField();
echo '<input type="hidden" name="func" value="save">';

foreach ($languages as $clang) {
    $code = strtolower((string) $clang->getCode());
    $name = (string) $clang->getName();
    $effective = FrontendI18n::getTranslationsForLanguage($code);

    echo '<section class="panel panel-default">';
    echo '<header class="panel-heading"><strong>' . rex_escape($name) . ' (' . rex_escape($code) . ')</strong></header>';
    echo '<div class="panel-body">';

    foreach (FrontendI18n::getManagedKeys() as $key) {
        $value = (string) ($currentConfig[$code][$key] ?? '');
        $placeholder = (string) ($effective[$key] ?? '');
        $label = $labels[$key] ?? $key;

        echo '<div class="form-group">';
        echo '<label class="control-label" for="kb-i18n-' . rex_escape($code . '-' . $key) . '">' . rex_escape($label) . '</label>';
        echo '<input class="form-control" type="text" id="kb-i18n-' . rex_escape($code . '-' . $key) . '" name="translations[' . rex_escape($code) . '][' . rex_escape($key) . ']" value="' . rex_escape($value) . '" placeholder="' . rex_escape($placeholder) . '">';
        echo '</div>';
    }

    echo '</div>';
    echo '</section>';
}

echo '<p><button class="btn btn-save rex-form-aligned" type="submit">Speichern</button></p>';
echo '</form>';
