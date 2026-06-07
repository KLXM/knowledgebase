<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_addon;
use rex_escape;
use rex_fragment;
use rex_path;
use rex_server;
use rex_url;
use rex_yform_manager_dataset;

final class FrontendRenderer
{
    private static bool $assetsRendered = false;
    private static int $instanceCounter = 0;

    public static function render(int $knowledgebaseId, string $startSlug = '', int $stickyHeaderOffset = 0, int $stickyNavOffset = 0): string
    {
        UrlProfileManager::ensureSectionRoutes($knowledgebaseId);

        $knowledgebase = \rex_data_knowledgebase::findOnlineById($knowledgebaseId);
        if (!$knowledgebase instanceof \rex_data_knowledgebase) {
            return '<div class="uk-alert-danger" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_frontend_missing_base', 'Die Wissensbasis ist nicht verfuegbar.')) . '</div>';
        }

        self::$instanceCounter++;
        $instanceId = 'kb-app-' . $knowledgebaseId . '-' . self::$instanceCounter;
        $articleParam = 'kb_' . $knowledgebaseId . '_article';
        $searchParam = 'kb_' . $knowledgebaseId . '_q';
        $glossaryParam = 'kb_' . $knowledgebaseId . '_glossary';
        $tocParam = 'kb_' . $knowledgebaseId . '_toc';

        $requestedSlug = trim((string) \rex_request($articleParam, 'string', $startSlug));
        $searchQuery = trim((string) \rex_request($searchParam, 'string', ''));
        $glossaryRequested = (int) \rex_request($glossaryParam, 'int', 0) === 1;
        $tocRequested = (int) \rex_request($tocParam, 'int', 0) === 1;

        // Clean-Path-Routing via URL-Addon (z. B. /glossar/, /inhaltsverzeichnis/, /suche/)
        if (KnowledgebaseUrl::hasProfile($knowledgebaseId)) {
            $routeState = KnowledgebaseUrl::resolveCurrentRequest($knowledgebaseId);
            if ($routeState['slug'] !== '') {
                $requestedSlug = $routeState['slug'];
            }
            if ($routeState['search_query'] !== '') {
                $searchQuery = $routeState['search_query'];
            }
            if ($routeState['glossary']) {
                $glossaryRequested = true;
                $tocRequested = false;
                $searchQuery = '';
            }
            if ($routeState['toc']) {
                $tocRequested = true;
                $glossaryRequested = false;
                $searchQuery = '';
            }
        }

        $articles = $knowledgebase->getOnlineArticles()->toArray();

        $currentArticle = null;
        if ('' !== $requestedSlug) {
            $currentArticle = \rex_data_knowledgebase_article::findOnlineBySlug($knowledgebaseId, $requestedSlug);
        }
        if (!$currentArticle instanceof \rex_data_knowledgebase_article) {
            $firstArticle = $knowledgebase->getFirstArticle();
            $currentArticle = $firstArticle instanceof \rex_data_knowledgebase_article ? $firstArticle : null;
        }

        $searchResults = '' !== $searchQuery
            ? call_user_func(['FriendsOfREDAXO\\Knowledgebase\\SearchService', 'search'], $knowledgebaseId, $searchQuery, 30)
            : [];

        FrontendContext::push($knowledgebaseId, $articleParam, $searchParam);
        $content = self::renderInner(
            $instanceId,
            $knowledgebase,
            $articles,
            $currentArticle,
            $articleParam,
            $searchParam,
            $glossaryParam,
            $tocParam,
            $searchQuery,
            $searchResults,
            $glossaryRequested,
            $tocRequested,
            max(0, $stickyHeaderOffset),
            max(0, $stickyNavOffset),
        );
        FrontendContext::pop();

        return self::renderAssets() . $content;
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     * @param list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}> $searchResults
     */
    private static function renderInner(string $instanceId, \rex_data_knowledgebase $knowledgebase, array $articles, ?\rex_data_knowledgebase_article $currentArticle, string $articleParam, string $searchParam, string $glossaryParam, string $tocParam, string $searchQuery, array $searchResults, bool $glossaryRequested, bool $tocRequested, int $stickyHeaderOffset, int $stickyNavOffset): string
    {
        $basePath = self::getCurrentPath();
        $stickyOffsetTotal = $stickyHeaderOffset + $stickyNavOffset;
        $glossaryEnabled = $knowledgebase->isGlossaryEnabled();
        $showGlossaryPage = $glossaryEnabled && $glossaryRequested && $searchQuery === '';
        $showTocPage = $tocRequested && !$showGlossaryPage && $searchQuery === '';
        $nav = self::renderNavigation($articles, $currentArticle, $articleParam, $glossaryParam, $tocParam, $glossaryEnabled, $showGlossaryPage, $showTocPage);
        $main = '' !== $searchQuery
            ? self::renderSearchResults($searchResults, $articleParam, $searchQuery)
            : ($showGlossaryPage
                ? self::renderGlossaryIndex($knowledgebase, $glossaryParam)
                : ($showTocPage
                    ? self::renderTocPage($knowledgebase, $articles, $currentArticle, $articleParam, $tocParam)
                    : self::renderArticle($knowledgebase, $currentArticle)));

        $title = rex_escape((string) $knowledgebase->getValue('title'));
        $description = trim((string) $knowledgebase->getValue('description'));
        $descriptionHtml = '' !== $description ? '<p class="kb-app__description">' . rex_escape($description) . '</p>' : '';
        $usesCleanProfile = KnowledgebaseUrl::hasProfile($knowledgebase->getId());
        $searchFormAction = $usesCleanProfile ? KnowledgebaseUrl::getSearchBaseUrl($knowledgebase->getId()) : $basePath;
        $searchFieldName = $usesCleanProfile ? 'q' : $searchParam;
        $eyebrow = FrontendI18n::msg('knowledgebase_eyebrow', 'Knowledge Base');
        $glossaryBadgeLabel = FrontendI18n::msg('knowledgebase_nav_glossary_badge', 'A-Z');
        $suggestUnavailable = FrontendI18n::msg('knowledgebase_suggest_unavailable', 'Autosuggest momentan nicht verfuegbar.');
        $suggestEmpty = FrontendI18n::msg('knowledgebase_suggest_empty', 'Keine Vorschlaege gefunden.');
        $searchStateInputs = $usesCleanProfile
            ? ''
            : '<input type="hidden" name="' . rex_escape($glossaryParam) . '" value="0">'
                . '<input type="hidden" name="' . rex_escape($tocParam) . '" value="0">';

        $offcanvasId = $instanceId . '-nav';

        return '<section id="' . rex_escape($instanceId) . '" class="kb-app uk-card uk-card-default" data-kb-base-path="' . rex_escape($basePath) . '" data-kb-id="' . $knowledgebase->getId() . '" data-kb-article-param="' . rex_escape($articleParam) . '" data-kb-search-param="' . rex_escape($searchParam) . '" data-kb-api="' . rex_escape(self::buildUrl(['rex-api-call' => 'knowledgebase_search'])) . '" data-kb-sticky-header-offset="' . $stickyHeaderOffset . '" data-kb-sticky-nav-offset="' . $stickyNavOffset . '" data-kb-sticky-offset="' . $stickyOffsetTotal . '" data-kb-sticky-media="960" data-kb-suggest-unavailable="' . rex_escape($suggestUnavailable) . '" data-kb-suggest-empty="' . rex_escape($suggestEmpty) . '">'
            . '<div class="kb-app__hero uk-section uk-section-xsmall uk-section-muted">'
            . '<div class="kb-app__hero-inner uk-grid-small" uk-grid>'
            . '<div>'
            . '<div class="kb-app__eyebrow">' . rex_escape($eyebrow) . '</div>'
            . '<div class="kb-app__title uk-margin-remove">' . $title . '</div>'
            . $descriptionHtml
            . '</div>'
            . '<div class="kb-app__search-panel">'
            . '<form class="kb-app__search-form" method="get" action="' . rex_escape($searchFormAction) . '">'
            . $searchStateInputs
            . '<label class="kb-app__search-label" for="' . rex_escape($instanceId . '-search') . '">' . rex_escape(FrontendI18n::msg('knowledgebase_search_label', 'Suche')) . '</label>'
            . '<div class="kb-app__search-control">'
            . '<span uk-icon="search"></span>'
            . '<input id="' . rex_escape($instanceId . '-search') . '" class="kb-app__search-input" type="search" name="' . rex_escape($searchFieldName) . '" value="' . rex_escape($searchQuery) . '" placeholder="' . rex_escape($knowledgebase->getPlaceholder()) . '" autocomplete="off">'
            . '<button class="kb-app__mobile-nav-trigger" type="button" uk-toggle="target: #' . rex_escape($offcanvasId) . '" aria-label="' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '"><span uk-icon="list"></span><span class="kb-app__mobile-nav-trigger-label">' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '</span></button>'
            . '<button class="uk-button uk-button-primary uk-button-small" type="submit">' . rex_escape(FrontendI18n::msg('knowledgebase_search_submit', 'Volltextsuche')) . '</button>'
            . '</div>'
            . '<div class="kb-app__search-results" hidden></div>'
            . '</form>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div id="' . rex_escape($offcanvasId) . '" uk-offcanvas="mode: slide; overlay: true">'
            . '<div class="uk-offcanvas-bar kb-app__offcanvas">'
            . '<button class="uk-offcanvas-close" type="button" uk-close></button>'
            . $nav
            . '</div>'
            . '</div>'
            . '<div class="kb-app__layout">'
            . '<aside class="kb-app__sidebar">' . $nav . '</aside>'
            . '<div class="kb-app__content">' . $main . '</div>'
            . '</div>'
            . '</section>';
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function renderNavigation(array $articles, ?\rex_data_knowledgebase_article $currentArticle, string $articleParam, string $glossaryParam, string $tocParam, bool $glossaryEnabled, bool $glossaryActive, bool $tocActive): string
    {
        $currentId = $currentArticle instanceof \rex_data_knowledgebase_article ? $currentArticle->getId() : 0;
        $navSearchId = 'kb-nav-search-' . $articleParam;
        $expandLabel = FrontendI18n::msg('knowledgebase_nav_expand_all', 'Alle aufklappen');
        $collapseLabel = FrontendI18n::msg('knowledgebase_nav_collapse_all', 'Alle einklappen');
        $glossaryLabel = FrontendI18n::msg('knowledgebase_nav_glossary', 'Glossar');
        $glossaryBadgeLabel = FrontendI18n::msg('knowledgebase_nav_glossary_badge', 'A-Z');
        $tocLabel = FrontendI18n::msg('knowledgebase_nav_all_levels', 'Inhaltsverzeichnis (alle Ebenen)');
        $items = '<ul class="kb-app__nav-list">';

        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }

            if ((int) $article->getValue('show_in_nav') !== 1) {
                continue;
            }

            $isCurrent = $article->getId() === $currentId;
            $items .= '<li class="kb-app__nav-main-item" data-kb-nav-main>';
            $chapters = self::extractArticleChapters($article);
            $hasChapters = count($chapters) > 0;
            $items .= '<div class="kb-app__nav-main-row">';
            $items .= '<a class="kb-app__nav-link' . ($isCurrent ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl([$articleParam => (string) $article->getValue('slug')])) . '" aria-expanded="' . ($isCurrent ? 'true' : 'false') . '">';
            $items .= self::renderNavBadge((string) $article->getValue('nav_badge'), 'file-text');
            $items .= '<span>' . rex_escape($article->getNavLabel()) . '</span>';
            $items .= '</a>';

            if ($hasChapters) {
                $items .= '<span class="kb-app__nav-main-toggle" data-kb-nav-main-toggle role="button" tabindex="0" aria-expanded="' . ($isCurrent ? 'true' : 'false') . '" aria-label="' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '"><span uk-icon="chevron-down"></span></span>';
            }
            $items .= '</div>';

            if (count($chapters) > 0) {
                $items .= '<ul class="kb-app__nav-list kb-app__nav-sublist" data-kb-nav-sublist' . ($isCurrent ? '' : ' hidden') . '>';
                foreach ($chapters as $chapter) {
                    $href = self::buildUrl([$articleParam => (string) $article->getValue('slug')]) . '#' . rawurlencode($chapter['anchor']);
                    $items .= '<li class="kb-app__nav-chapter-item" data-kb-nav-chapter>';
                    $items .= '<a class="kb-app__nav-link kb-app__nav-link--chapter" data-kb-nav-chapter-link href="' . rex_escape($href) . '">';
                    $items .= self::renderNavBadge($chapter['badge']);
                    $items .= '<span>' . rex_escape($chapter['title']) . '</span>';
                    $items .= '</a>';
                    $items .= '</li>';
                }
                $items .= '</ul>';
            }

            $items .= '</li>';
        }

        if ($glossaryEnabled) {
            $items .= '<li class="kb-app__nav-main-item kb-app__nav-main-item--glossary" data-kb-nav-main>';
            $items .= '<a class="kb-app__nav-link kb-app__nav-link--glossary' . ($glossaryActive ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl([$glossaryParam => 1])) . '">';
            $items .= '<span class="kb-app__nav-badge">' . rex_escape($glossaryBadgeLabel) . '</span>';
            $items .= '<span>' . rex_escape($glossaryLabel) . '</span>';
            $items .= '</a>';
            $items .= '</li>';
        }

        $items .= '<li class="kb-app__nav-main-item kb-app__nav-main-item--toc" data-kb-nav-main>';
        $items .= '<a class="kb-app__nav-link kb-app__nav-link--toc' . ($tocActive ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl([$tocParam => 1])) . '">';
        $items .= self::renderNavBadgeIcon('list');
        $items .= '<span>' . rex_escape($tocLabel) . '</span>';
        $items .= '</a>';
        $items .= '</li>';

        $items .= '</ul>';

        return '<div class="kb-app__nav-shell">'
            . '<div class="kb-app__nav-title">' . rex_escape(FrontendI18n::msg('knowledgebase_nav_title', 'Inhaltsverzeichnis')) . '</div>'
            . '<div class="kb-app__nav-search-wrap">'
            . '<label class="kb-app__nav-search-label" for="' . rex_escape($navSearchId) . '">' . rex_escape(FrontendI18n::msg('knowledgebase_nav_filter_label', 'Kapitel filtern')) . '</label>'
            . '<div class="kb-app__nav-search-control">'
            . '<span uk-icon="search"></span>'
            . '<input id="' . rex_escape($navSearchId) . '" class="kb-app__nav-search-input" type="search" placeholder="' . rex_escape(FrontendI18n::msg('knowledgebase_nav_filter_placeholder', 'Titel oder Unterkapitel')) . '" autocomplete="off" data-kb-nav-search>'
            . '</div>'
                . '<button type="button" class="kb-app__nav-expand-toggle" data-kb-nav-expand-toggle data-kb-label-expand="' . rex_escape($expandLabel) . '" data-kb-label-collapse="' . rex_escape($collapseLabel) . '" aria-pressed="false">' . rex_escape($expandLabel) . '</button>'
            . '</div>'
            . $items
            . '</div>';
    }

    private static function renderGlossaryIndex(\rex_data_knowledgebase $knowledgebase, string $glossaryParam): string
    {
        $terms = GlossaryService::getTermsForKnowledgebase($knowledgebase->getId());
        if (count($terms) === 0) {
            return '<section class="kb-app__glossary-page">'
                . '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_glossary_title', 'Glossar')) . '</div>'
                . '<div class="kb-app__article-title">' . rex_escape((string) $knowledgebase->getValue('title')) . '</div>'
                . '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_glossary_empty', 'Noch keine Glossar-Begriffe vorhanden.')) . '</div>'
                . '</section>';
        }

        usort($terms, static fn (array $left, array $right): int => strcasecmp((string) $left['term'], (string) $right['term']));

        $groups = [];
        foreach ($terms as $term) {
            $letter = self::extractGlossaryLetter((string) $term['term']);
            if (!isset($groups[$letter])) {
                $groups[$letter] = [];
            }
            $groups[$letter][] = $term;
        }

        $alphabetLinks = '';
        $entries = '';
        foreach ($groups as $letter => $groupTerms) {
            $anchor = 'kb-glossary-letter-' . strtolower($letter);
            $alphabetLinks .= '<a href="#' . rex_escape($anchor) . '" class="kb-app__glossary-letter-link">' . rex_escape($letter) . '</a>';

            $entries .= '<section class="kb-app__glossary-group" id="' . rex_escape($anchor) . '">';
            $entries .= '<h4 class="kb-app__glossary-group-title">' . rex_escape($letter) . '</h4>';
            $entries .= '<ul class="kb-app__glossary-list">';

            foreach ($groupTerms as $term) {
                $termId = (int) $term['id'];
                $modalId = GlossaryService::getModalId($knowledgebase->getId(), $termId);
                $definition = trim((string) $term['definition']);

                $entries .= '<li class="kb-app__glossary-item">';
                $entries .= '<a href="#' . rex_escape($modalId) . '" class="kb-app__glossary-link kb-app__glossary-link--index" data-kb-glossary-link="1" uk-toggle>' . rex_escape((string) $term['term']) . '</a>';
                if ($definition !== '') {
                    $entries .= '<div class="kb-app__glossary-item-definition">' . rex_escape($definition) . '</div>';
                }
                $entries .= '</li>';
            }

            $entries .= '</ul>';
            $entries .= '</section>';
        }

        $modals = GlossaryService::renderGlossaryModals($terms, $knowledgebase->getId());
        $backUrl = self::buildUrl([$glossaryParam => null]);

        return '<section class="kb-app__glossary-page">'
            . '<div class="kb-app__breadcrumbs"><a href="' . rex_escape($backUrl) . '">' . rex_escape((string) $knowledgebase->getValue('title')) . '</a><span>/</span><span>' . rex_escape(FrontendI18n::msg('knowledgebase_glossary_title', 'Glossar')) . '</span></div>'
            . '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_glossary_title', 'Glossar')) . '</div>'
            . '<div class="kb-app__article-title">' . rex_escape((string) $knowledgebase->getValue('title')) . '</div>'
            . '<div class="kb-app__glossary-letters">' . $alphabetLinks . '</div>'
            . $entries
            . $modals
            . '</section>';
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function renderTocPage(\rex_data_knowledgebase $knowledgebase, array $articles, ?\rex_data_knowledgebase_article $currentArticle, string $articleParam, string $tocParam): string
    {
        if (count($articles) === 0) {
            return '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_frontend_missing_article', 'Es ist noch kein Beitrag vorhanden.')) . '</div>';
        }

        $tocTitle = FrontendI18n::msg('knowledgebase_nav_all_levels', 'Inhaltsverzeichnis (alle Ebenen)');
        $backSlug = $currentArticle instanceof \rex_data_knowledgebase_article
            ? (string) $currentArticle->getValue('slug')
            : (string) $articles[0]->getValue('slug');
        $backUrl = self::buildUrl([$articleParam => $backSlug, $tocParam => null]);
        $breadcrumbs = '<div class="kb-app__breadcrumbs"><a href="' . rex_escape($backUrl) . '">' . rex_escape((string) $knowledgebase->getValue('title')) . '</a><span>/</span><span>' . rex_escape($tocTitle) . '</span></div>';

        $groupsHtml = '';
        $hasAnyChapter = false;

        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }
            if ((int) $article->getValue('show_in_nav') !== 1) {
                continue;
            }

            $chapters = self::extractArticleChapters($article, false);
            if (count($chapters) === 0) {
                continue;
            }

            $hasAnyChapter = true;
            $articleUrl = self::buildUrl([$articleParam => (string) $article->getValue('slug'), $tocParam => null]);
            $groupItems = '';

            foreach ($chapters as $chapter) {
                $indentClass = '';
                if ($chapter['level'] === 'h3') {
                    $indentClass = ' uk-margin-left';
                } elseif ($chapter['level'] === 'h4') {
                    $indentClass = ' uk-margin-large-left';
                }

                $href = $articleUrl . '#' . rawurlencode($chapter['anchor']);
                $groupItems .= '<li class="kb-app__toc-item' . $indentClass . '">';
                $groupItems .= '<a class="kb-app__nav-link kb-app__nav-link--chapter" href="' . rex_escape($href) . '">';
                $groupItems .= self::renderNavBadge($chapter['badge']);
                $groupItems .= '<span>' . rex_escape($chapter['title']) . '</span>';
                $groupItems .= '</a>';
                $groupItems .= '</li>';
            }

            $groupsHtml .= '<section class="kb-app__toc-group">';
            $groupsHtml .= '<h2 class="kb-app__toc-group-title"><a href="' . rex_escape($articleUrl) . '">' . rex_escape($article->getNavLabel()) . '</a></h2>';
            $groupsHtml .= '<ul class="kb-app__nav-list kb-app__toc-list">' . $groupItems . '</ul>';
            $groupsHtml .= '</section>';
        }

        if (!$hasAnyChapter) {
            return '<section class="kb-app__toc-page">'
                . $breadcrumbs
                . '<div class="kb-app__article-meta">' . rex_escape($tocTitle) . '</div>'
                . '<div class="kb-app__article-title">' . rex_escape((string) $knowledgebase->getValue('title')) . '</div>'
                . '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_toc_empty', 'Für diesen Beitrag sind noch keine Kapitelmarken vorhanden.')) . '</div>'
                . '</section>';
        }

        return '<section class="kb-app__toc-page">'
            . $breadcrumbs
            . '<div class="kb-app__article-meta">' . rex_escape($tocTitle) . '</div>'
            . '<div class="kb-app__article-title">' . rex_escape((string) $knowledgebase->getValue('title')) . '</div>'
            . $groupsHtml
            . '</section>';
    }

    private static function extractGlossaryLetter(string $term): string
    {
        $trimmed = trim($term);
        if ($trimmed === '') {
            return '#';
        }

        $first = function_exists('mb_substr') ? mb_substr($trimmed, 0, 1, 'UTF-8') : substr($trimmed, 0, 1);
        if ($first === '') {
            return '#';
        }

        $upper = function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first);
        return preg_match('/[A-ZÄÖÜ]/u', $upper) === 1 ? $upper : '#';
    }

    /**
     */
    private static function renderArticle(\rex_data_knowledgebase $knowledgebase, ?\rex_data_knowledgebase_article $article): string
    {
        if (!$article instanceof \rex_data_knowledgebase_article) {
            return '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_frontend_missing_article', 'Es ist noch kein Beitrag vorhanden.')) . '</div>';
        }

        $intro = trim((string) $article->getValue('intro'));
        $introHtml = '' !== $intro ? '<div class="kb-app__intro">' . $intro . '</div>' : '';
        $breadcrumbs = self::renderBreadcrumbs($knowledgebase, $article);
        $articleBody = GlossaryService::enhanceArticleHtml($knowledgebase, $article->renderContent());

        return '<article class="kb-app__article">'
            . $breadcrumbs
            . '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_article_label', 'Kapitel')) . '</div>'
            . '<h1 class="kb-app__article-title">' . rex_escape((string) $article->getValue('title')) . '</h1>'
            . $introHtml
            . '<div class="kb-app__article-body">' . $articleBody . '</div>'
            . '</article>';
    }

    private static function renderBreadcrumbs(\rex_data_knowledgebase $knowledgebase, \rex_data_knowledgebase_article $article): string
    {
        $crumbs = [
            rex_escape((string) $knowledgebase->getValue('title')),
            rex_escape($article->getNavLabel()),
        ];

        return '<div class="kb-app__breadcrumbs">' . implode('<span>/</span>', array_map(static fn (string $crumb): string => '<span>' . $crumb . '</span>', $crumbs)) . '</div>';
    }

    /**
     * @param list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}> $results
     */
    private static function renderSearchResults(array $results, string $articleParam, string $searchQuery): string
    {
        $heading = '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_search_results', 'Suchergebnisse')) . '</div>'
            . '<h3 class="kb-app__article-title">' . rex_escape($searchQuery) . '</h3>';

        if (count($results) === 0) {
            return '<section class="kb-app__search-page">' . $heading . '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_search_empty', 'Keine Treffer gefunden.')) . '</div></section>';
        }

        $items = '';
        foreach ($results as $result) {
            $title = '' !== trim($result['nav_title']) ? $result['nav_title'] : $result['title'];
            $items .= '<li class="kb-app__search-item">';
            $items .= '<a class="kb-app__search-item-link" href="' . rex_escape(self::buildUrl([$articleParam => $result['slug']])) . '">';
            $items .= '<strong>' . rex_escape($title) . '</strong>';
            $items .= '<span>' . rex_escape($result['excerpt']) . '</span>';
            $items .= '</a>';
            $items .= '</li>';
        }

        return '<section class="kb-app__search-page">' . $heading . '<ul class="kb-app__search-page-list">' . $items . '</ul></section>';
    }

    /**
    * @return list<array{title:string,badge:string,anchor:string,level:string}>
     */
    private static function extractArticleChapters(\rex_data_knowledgebase_article $article, bool $topLevelOnly = true): array
    {
        $content = trim((string) $article->getValue('content'));
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return [];
        }

        $chapters = [];
        foreach ($decoded as $slice) {
            if (!is_array($slice)) {
                continue;
            }

            $type = trim((string) ($slice['type'] ?? ''));
            if ($type !== 'kb_chapter_nav') {
                continue;
            }

            $data = $slice['data'] ?? null;
            if (!is_array($data)) {
                continue;
            }

            $headingLevel = strtolower(trim((string) ($data['heading_level'] ?? 'h2')));
            if (!in_array($headingLevel, ['h2', 'h3', 'h4'], true)) {
                $headingLevel = 'h2';
            }

            if ($topLevelOnly && $headingLevel !== 'h2') {
                continue;
            }

            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $badge = trim((string) ($data['badge'] ?? ''));
            $anchorBase = trim((string) ($data['anchor_id'] ?? ''));
            $anchor = self::sanitizeAnchor($anchorBase !== '' ? $anchorBase : $title);
            if ($anchor === '') {
                continue;
            }

            $chapters[] = [
                'title' => $title,
                'badge' => $badge,
                'anchor' => $anchor,
                'level' => $headingLevel,
            ];
        }

        return $chapters;
    }

    private static function sanitizeAnchor(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9\-_]+/u', '-', $normalized);
        if (!is_string($normalized)) {
            return '';
        }

        return trim($normalized, '-');
    }

    private static function renderNavBadge(string $badge, ?string $defaultIcon = null): string
    {
        $trimmed = trim($badge);

        if ($trimmed === '') {
            return self::renderNavBadgeIcon($defaultIcon);
        }

        if (preg_match('/^\d{1,2}$/', $trimmed) === 1) {
            $number = (int) $trimmed;
            if ($number > 0) {
                return '<span class="kb-app__nav-badge">' . rex_escape((string) $number) . '</span>';
            }

            return self::renderNavBadgeIcon($defaultIcon);
        }

        if (preg_match('/^[a-z0-9-]{2,40}$/', $trimmed) === 1) {
            return self::renderNavBadgeIcon($trimmed);
        }

        return self::renderNavBadgeIcon($defaultIcon);
    }

    private static function renderNavBadgeIcon(?string $icon): string
    {
        $trimmed = trim((string) $icon);
        if ($trimmed === '' || preg_match('/^[a-z0-9-]{2,40}$/', $trimmed) !== 1) {
            return '';
        }

        return '<span class="kb-app__nav-badge kb-app__nav-badge--icon"><span uk-icon="icon: ' . rex_escape($trimmed) . '; ratio: 0.85" aria-hidden="true"></span></span>';
    }

    /**
     * @param array<string, scalar|null> $params
     */
    public static function buildUrl(array $params): string
    {
        $kbId = 0;
        foreach (array_keys($params) as $key) {
            if (preg_match('/^kb_(\d+)_(article|q|glossary|toc)$/', (string) $key, $m) === 1) {
                $kbId = (int) $m[1];
                break;
            }
        }

        if ($kbId > 0 && KnowledgebaseUrl::hasProfile($kbId)) {
            $articleKey = 'kb_' . $kbId . '_article';
            $searchKey = 'kb_' . $kbId . '_q';
            $glossaryKey = 'kb_' . $kbId . '_glossary';
            $tocKey = 'kb_' . $kbId . '_toc';

            $searchValue = $params[$searchKey] ?? null;
            if (is_scalar($searchValue) && '' !== trim((string) $searchValue)) {
                return KnowledgebaseUrl::getSearchUrl($kbId, (string) $searchValue);
            }

            $glossaryValue = $params[$glossaryKey] ?? null;
            if ((is_scalar($glossaryValue) && (int) $glossaryValue === 1) || $glossaryValue === true) {
                return KnowledgebaseUrl::getGlossaryUrl($kbId);
            }

            $tocValue = $params[$tocKey] ?? null;
            if ((is_scalar($tocValue) && (int) $tocValue === 1) || $tocValue === true) {
                return KnowledgebaseUrl::getTocUrl($kbId);
            }

            $articleValue = $params[$articleKey] ?? null;
            if (is_scalar($articleValue) && '' !== (string) $articleValue) {
                return KnowledgebaseUrl::getArticleUrl($kbId, (string) $articleValue);
            }

            return KnowledgebaseUrl::getBaseUrl($kbId);
        }

        $query = http_build_query(array_filter($params, static fn (mixed $value): bool => is_scalar($value) && '' !== (string) $value));

        return self::getCurrentPath() . ('' !== $query ? '?' . $query : '');
    }

    private static function getCurrentPath(): string
    {
        $requestUri = rex_server('REQUEST_URI', 'string', '');
        $path = parse_url($requestUri, PHP_URL_PATH);

        return is_string($path) && '' !== $path ? $path : '/';
    }

    private static function renderAssets(): string
    {
        if (self::$assetsRendered) {
            return '';
        }

        self::$assetsRendered = true;
        $cssVersion = self::getAssetVersion('css/knowledgebase.css');
        $jsVersion = self::getAssetVersion('js/knowledgebase.js');

        return '<link rel="stylesheet" href="' . rex_escape(rex_url::addonAssets('knowledgebase', 'css/knowledgebase.css?v=' . $cssVersion)) . '">'
            . '<script src="' . rex_escape(rex_url::addonAssets('knowledgebase', 'js/knowledgebase.js?v=' . $jsVersion)) . '" defer></script>';
    }

    private static function getAssetVersion(string $relativePath): string
    {
        $path = rex_path::addonAssets('knowledgebase', $relativePath);
        $mtime = @filemtime($path);
        if (is_int($mtime)) {
            return (string) $mtime;
        }

        return (string) rex_addon::get('knowledgebase')->getVersion();
    }
}