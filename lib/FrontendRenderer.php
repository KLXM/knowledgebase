<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_addon;
use rex_backend_login;
use rex_csrf_token;
use rex_escape;
use rex_fragment;
use rex_media;
use rex_path;
use rex_server;
use rex_url;
use rex_yform_manager_table;
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
        $tagParam = 'kb_' . $knowledgebaseId . '_tag';
        $tagsParam = 'kb_' . $knowledgebaseId . '_tags';
        $tagFilterEnabled = $knowledgebase->isTagFilterEnabled();
        $tagMultiEnabled = $knowledgebase->isTagMultiSelectEnabled();
        $recentEnabled = $knowledgebase->isRecentlyViewedEnabled();
        $recentLimit = $knowledgebase->getRecentlyViewedLimit();
        $relatedEnabled = $knowledgebase->isRelatedArticlesEnabled();
        $relatedLimit = $knowledgebase->getRelatedArticlesLimit();
        $searchHistoryEnabled = $knowledgebase->isSearchHistoryEnabled();
        $layoutMode = $knowledgebase->getLayoutMode();
        $tagModeActive = false;

        $requestedSlug = trim((string) \rex_request($articleParam, 'string', $startSlug));
        $searchQuery = trim((string) \rex_request($searchParam, 'string', ''));
        $glossaryRequested = (int) \rex_request($glossaryParam, 'int', 0) === 1;
        $tocRequested = (int) \rex_request($tocParam, 'int', 0) === 1;
        $selectedTags = $tagFilterEnabled && $tagMultiEnabled
            ? self::parseTagList((string) \rex_request($tagsParam, 'string', ''))
            : [];
        $selectedTag = $tagFilterEnabled
            ? \rex_data_knowledgebase_article::normalizeTag((string) \rex_request($tagParam, 'string', ''))
            : '';

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
            if ($routeState['tag'] !== '') {
                $selectedTag = (string) $routeState['tag'];
            }
            if ($tagMultiEnabled && $routeState['tags'] !== '') {
                $selectedTags = self::parseTagList((string) $routeState['tags']);
            }
            if ((bool) ($routeState['tags_mode'] ?? false)) {
                $tagModeActive = true;
            }
        }

        $articles = $knowledgebase->getOnlineArticles()->toArray();
        $availableTags = $tagFilterEnabled ? self::extractAvailableTags($articles) : [];
        if ($tagMultiEnabled && $selectedTags !== []) {
            $selectedTags = array_values(array_filter(
                $selectedTags,
                static fn (string $tag): bool => isset($availableTags[$tag]),
            ));
            if ($selectedTags !== []) {
                $selectedTag = '';
            }
        }
        if ($selectedTag !== '' && !isset($availableTags[$selectedTag])) {
            $selectedTag = '';
        }

        $filteredArticles = $tagMultiEnabled
            ? ($selectedTags !== [] ? self::filterArticlesByTags($articles, $selectedTags) : $articles)
            : ($selectedTag !== '' ? self::filterArticlesByTag($articles, $selectedTag) : $articles);
        $showTagPage = ($tagMultiEnabled ? $selectedTags !== [] : $selectedTag !== '')
            && $requestedSlug === ''
            && $searchQuery === ''
            && !$glossaryRequested
            && !$tocRequested;

        $currentArticle = null;
        if ('' !== $requestedSlug) {
            $currentArticle = \rex_data_knowledgebase_article::findOnlineBySlug($knowledgebaseId, $requestedSlug);
            if (
                $currentArticle instanceof \rex_data_knowledgebase_article
                && (
                    ($tagMultiEnabled && $selectedTags !== [] && !self::articleMatchesAnyTag($currentArticle, $selectedTags))
                    || (!$tagMultiEnabled && $selectedTag !== '' && !$currentArticle->hasTag($selectedTag))
                )
            ) {
                $currentArticle = null;
            }
        }
        if (!$currentArticle instanceof \rex_data_knowledgebase_article) {
            $firstFiltered = self::getFirstArticle($filteredArticles);
            if ($firstFiltered instanceof \rex_data_knowledgebase_article) {
                $currentArticle = $firstFiltered;
            } else {
                $firstArticle = $knowledgebase->getFirstArticle();
                $currentArticle = $firstArticle instanceof \rex_data_knowledgebase_article ? $firstArticle : null;
            }
        }

        $searchResults = '' !== $searchQuery
            ? call_user_func(['FriendsOfREDAXO\\Knowledgebase\\SearchService', 'search'], $knowledgebaseId, $searchQuery, 30)
            : [];

        if (($tagMultiEnabled ? $selectedTags !== [] : $selectedTag !== '') && [] !== $searchResults) {
            $allowedArticleIds = [];
            foreach ($filteredArticles as $article) {
                if ($article instanceof \rex_data_knowledgebase_article) {
                    $allowedArticleIds[$article->getId()] = true;
                }
            }

            $searchResults = array_values(array_filter(
                $searchResults,
                static fn (array $result): bool => isset($allowedArticleIds[(int) ($result['id'] ?? 0)]),
            ));
        }

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
            $tagParam,
            $tagsParam,
            $searchQuery,
            $searchResults,
            $filteredArticles,
            $glossaryRequested,
            $tocRequested,
            $showTagPage,
            $tagFilterEnabled,
            $tagMultiEnabled,
            $tagModeActive,
            $availableTags,
            $selectedTag,
            $selectedTags,
            $recentEnabled,
            $recentLimit,
            $relatedEnabled,
            $relatedLimit,
            $searchHistoryEnabled,
            $layoutMode,
            max(0, $stickyHeaderOffset),
            max(0, $stickyNavOffset),
        );
        FrontendContext::pop();

        return self::renderAssets() . $content;
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     * @param list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string}> $searchResults
    * @param list<rex_yform_manager_dataset> $filteredArticles
     */
    private static function renderInner(string $instanceId, \rex_data_knowledgebase $knowledgebase, array $articles, ?\rex_data_knowledgebase_article $currentArticle, string $articleParam, string $searchParam, string $glossaryParam, string $tocParam, string $tagParam, string $tagsParam, string $searchQuery, array $searchResults, array $filteredArticles, bool $glossaryRequested, bool $tocRequested, bool $showTagPage, bool $tagFilterEnabled, bool $tagMultiEnabled, bool $tagModeActive, array $availableTags, string $selectedTag, array $selectedTags, bool $recentEnabled, int $recentLimit, bool $relatedEnabled, int $relatedLimit, bool $searchHistoryEnabled, string $layoutMode, int $stickyHeaderOffset, int $stickyNavOffset): string
    {
        $basePath = self::getCurrentPath();
        $stickyOffsetTotal = $stickyHeaderOffset + $stickyNavOffset;
        $glossaryEnabled = $knowledgebase->isGlossaryEnabled();
        $showGlossaryPage = $glossaryEnabled && $glossaryRequested && $searchQuery === '';
        $showTocPage = $tocRequested && !$showGlossaryPage && $searchQuery === '';
        $selectedTagLabel = $selectedTag;
        if ($tagMultiEnabled && $selectedTags !== []) {
            $selectedTagLabel = implode(', ', array_map(
                static fn (string $tag): string => (string) ($availableTags[$tag]['label'] ?? $tag),
                $selectedTags,
            ));
        } elseif ($selectedTag !== '' && isset($availableTags[$selectedTag]['label'])) {
            $selectedTagLabel = (string) $availableTags[$selectedTag]['label'];
        }
        $nav = self::renderNavigation($articles, $currentArticle, $articleParam, $glossaryParam, $tocParam, $tagParam, $tagsParam, $glossaryEnabled, $showGlossaryPage, $showTocPage, $tagFilterEnabled, $tagMultiEnabled, $tagModeActive, $availableTags, $selectedTag, $selectedTags);
        $main = '' !== $searchQuery
            ? self::renderSearchResults($searchResults, $articleParam, $searchQuery)
            : ($showGlossaryPage
                ? self::renderGlossaryIndex($knowledgebase, $glossaryParam)
                : ($showTocPage
                    ? self::renderTocPage($knowledgebase, $articles, $currentArticle, $articleParam, $tocParam)
                    : ($showTagPage
                        ? self::renderTagResults($filteredArticles, $articleParam, $tagParam, $tagsParam, $selectedTag, $selectedTags, $selectedTagLabel)
                        : self::renderArticle($knowledgebase, $currentArticle, $relatedEnabled))));
        $jsonLd = self::renderJsonLd($knowledgebase, $currentArticle, $searchQuery, $showGlossaryPage, $showTocPage);
        $articleIndexJson = self::buildArticleClientIndexJson($articles, $articleParam);
        $currentArticleSlug = $currentArticle instanceof \rex_data_knowledgebase_article
            ? trim((string) $currentArticle->getValue('slug'))
            : '';

        $title = rex_escape((string) $knowledgebase->getValue('title'));
        $description = trim((string) $knowledgebase->getValue('description'));
        $descriptionHtml = '' !== $description ? '<p class="kb-app__description">' . rex_escape($description) . '</p>' : '';
        $headerLogoHtml = self::renderHeaderLogo($knowledgebase);
        $usesCleanProfile = KnowledgebaseUrl::hasProfile($knowledgebase->getId());
        $searchFormAction = $usesCleanProfile ? KnowledgebaseUrl::getSearchBaseUrl($knowledgebase->getId()) : $basePath;
        $searchFieldName = $usesCleanProfile ? 'q' : $searchParam;
        $eyebrow = FrontendI18n::msg('knowledgebase_eyebrow', 'Knowledge Base');
        $glossaryBadgeLabel = FrontendI18n::msg('knowledgebase_nav_glossary_badge', 'A-Z');
        $suggestUnavailable = FrontendI18n::msg('knowledgebase_suggest_unavailable', 'Autosuggest momentan nicht verfuegbar.');
        $suggestEmpty = FrontendI18n::msg('knowledgebase_suggest_empty', 'Keine Vorschlaege gefunden.');
        $recentLabel = FrontendI18n::msg('knowledgebase_search_recently_updated', 'Kürzlich aktualisiert');
        $historyLabel = FrontendI18n::msg('knowledgebase_history_label', 'Lesehistorie');
        $historyHeading = FrontendI18n::msg('knowledgebase_history_heading', 'Zuletzt gelesen');
        $historyEmpty = FrontendI18n::msg('knowledgebase_history_empty', 'Noch keine gelesenen Beitraege vorhanden.');
        $relatedHeading = FrontendI18n::msg('knowledgebase_related_heading', 'Aehnliche Artikel');
        $relatedEmpty = FrontendI18n::msg('knowledgebase_related_empty', 'Keine passenden Beitraege gefunden.');
        $searchStateInputs = $usesCleanProfile
            ? ''
            : '<input type="hidden" name="' . rex_escape($glossaryParam) . '" value="0">'
                . '<input type="hidden" name="' . rex_escape($tocParam) . '" value="0">';
        $tagStateInput = $selectedTag !== ''
            ? '<input type="hidden" name="' . rex_escape($tagParam) . '" value="' . rex_escape($selectedTag) . '">'
            : '';
        $tagsStateInput = $selectedTags !== []
            ? '<input type="hidden" name="' . rex_escape($tagsParam) . '" value="' . rex_escape(self::serializeTagList($selectedTags)) . '">'
            : '';

        $offcanvasId = $instanceId . '-nav';

        $layoutClass = ' kb-app--layout-' . rex_escape($layoutMode);

        return $jsonLd
            . '<section id="' . rex_escape($instanceId) . '" class="kb-app uk-card uk-card-default' . $layoutClass . '" data-kb-base-path="' . rex_escape($basePath) . '" data-kb-id="' . $knowledgebase->getId() . '" data-kb-article-param="' . rex_escape($articleParam) . '" data-kb-search-param="' . rex_escape($searchParam) . '" data-kb-tag-param="' . rex_escape($tagFilterEnabled ? $tagParam : '') . '" data-kb-tags-param="' . rex_escape($tagFilterEnabled ? $tagsParam : '') . '" data-kb-tag-selected="' . rex_escape($selectedTag) . '" data-kb-tags-selected="' . rex_escape(self::serializeTagList($selectedTags)) . '" data-kb-api="' . rex_escape(self::buildUrl(['rex-api-call' => 'knowledgebase_search'])) . '" data-kb-sticky-header-offset="' . $stickyHeaderOffset . '" data-kb-sticky-nav-offset="' . $stickyNavOffset . '" data-kb-sticky-offset="' . $stickyOffsetTotal . '" data-kb-sticky-media="960" data-kb-suggest-unavailable="' . rex_escape($suggestUnavailable) . '" data-kb-suggest-empty="' . rex_escape($suggestEmpty) . '" data-kb-search-recent-label="' . rex_escape($recentLabel) . '" data-kb-search-history-enabled="' . ($searchHistoryEnabled ? '1' : '0') . '" data-kb-recent-enabled="' . ($recentEnabled ? '1' : '0') . '" data-kb-recent-limit="' . max(1, $recentLimit) . '" data-kb-related-enabled="' . ($relatedEnabled ? '1' : '0') . '" data-kb-related-limit="' . max(1, $relatedLimit) . '" data-kb-current-article="' . rex_escape($currentArticleSlug) . '" data-kb-articles="' . rex_escape($articleIndexJson) . '" data-kb-history-label="' . rex_escape($historyLabel) . '" data-kb-history-heading="' . rex_escape($historyHeading) . '" data-kb-history-empty="' . rex_escape($historyEmpty) . '" data-kb-related-empty="' . rex_escape($relatedEmpty) . '" data-kb-related-heading="' . rex_escape($relatedHeading) . '">'
            . '<div class="kb-app__hero uk-section uk-section-xsmall uk-section-muted">'
            . '<div class="kb-app__hero-inner uk-grid-small" uk-grid>'
            . '<div class="kb-app__hero-brand">'
            . $headerLogoHtml
            . '<div class="kb-app__hero-copy">'
            . '<div class="kb-app__eyebrow">' . rex_escape($eyebrow) . '</div>'
            . '<div class="kb-app__title uk-margin-remove">' . $title . '</div>'
            . $descriptionHtml
            . '</div>'
            . '</div>'
            . '<div class="kb-app__search-panel">'
            . '<form class="kb-app__search-form" method="get" action="' . rex_escape($searchFormAction) . '">'
            . $searchStateInputs
            . $tagStateInput
            . $tagsStateInput
            . '<label class="kb-app__search-label" for="' . rex_escape($instanceId . '-search') . '">' . rex_escape(FrontendI18n::msg('knowledgebase_search_label', 'Suche')) . '</label>'
            . '<div class="kb-app__search-row">'
            . '<div class="kb-app__search-control">'
            . '<div class="kb-app__search-input-wrap uk-inline">'
            . '<span class="kb-app__search-icon uk-form-icon" uk-icon="search"></span>'
            . '<input id="' . rex_escape($instanceId . '-search') . '" class="kb-app__search-input uk-input" type="search" name="' . rex_escape($searchFieldName) . '" value="' . rex_escape($searchQuery) . '" placeholder="' . rex_escape($knowledgebase->getPlaceholder()) . '" autocomplete="off">'
            . '</div>'
            . '<button class="kb-app__search-submit uk-button uk-button-primary uk-button-small" type="submit"><span class="kb-app__search-submit-icon" uk-icon="search"></span><span class="kb-app__search-submit-label">' . rex_escape(FrontendI18n::msg('knowledgebase_search_label', 'Suche')) . '</span></button>'
            . '<button class="kb-app__mobile-nav-trigger" type="button" uk-toggle="target: #' . rex_escape($offcanvasId) . '" aria-label="' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '"><span uk-icon="list"></span><span class="kb-app__mobile-nav-trigger-label">' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '</span></button>'
            . '</div>'
            . '<div class="kb-app__history-wrap uk-inline">'
            . '<button class="kb-app__history-toggle uk-button uk-button-default uk-button-small" type="button" data-kb-history-toggle aria-label="' . rex_escape($historyLabel) . '"><span class="kb-app__history-toggle-icon" aria-hidden="true">↺</span><span class="kb-app__history-toggle-label">' . rex_escape($historyLabel) . '</span></button>'
            . '<div class="kb-app__history-dropdown" data-kb-history-dropdown uk-dropdown="mode: click; pos: bottom-right; offset: 8">'
            . '<div class="kb-app__history-title">' . rex_escape($historyHeading) . '</div>'
            . '<ul class="kb-app__history-list uk-nav uk-nav-default" data-kb-history-list></ul>'
            . '<div class="kb-app__history-empty" data-kb-history-empty>' . rex_escape($historyEmpty) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="kb-app__search-results" hidden></div>'
            . '</form>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<div id="' . rex_escape($offcanvasId) . '" class="kb-app__offcanvas-panel" uk-offcanvas="mode: slide; overlay: true">'
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

    private static function renderJsonLd(\rex_data_knowledgebase $knowledgebase, ?\rex_data_knowledgebase_article $currentArticle, string $searchQuery, bool $showGlossaryPage, bool $showTocPage): string
    {
        $baseUrl = self::toAbsoluteUrl(KnowledgebaseUrl::getBaseUrl($knowledgebase->getId()));
        $currentUrl = self::toAbsoluteUrl(self::getCurrentPath());
        $kbName = trim((string) $knowledgebase->getValue('title'));
        if ($kbName === '') {
            $kbName = 'Knowledge Base';
        }

        $graph = [
            [
                '@type' => 'DefinedTermSet',
                '@id' => $baseUrl . '#knowledgebase',
                'name' => $kbName,
                'url' => $baseUrl,
            ],
            [
                '@type' => 'WebPage',
                '@id' => $currentUrl . '#webpage',
                'url' => $currentUrl,
                'name' => $kbName,
                'isPartOf' => ['@id' => $baseUrl . '#knowledgebase'],
            ],
        ];

        if ($showGlossaryPage) {
            $graph[] = [
                '@type' => 'CollectionPage',
                '@id' => $currentUrl . '#collection',
                'name' => FrontendI18n::msg('knowledgebase_glossary_title', 'Glossar'),
                'url' => $currentUrl,
                'isPartOf' => ['@id' => $baseUrl . '#knowledgebase'],
            ];
        } elseif ($showTocPage) {
            $graph[] = [
                '@type' => 'CollectionPage',
                '@id' => $currentUrl . '#collection',
                'name' => FrontendI18n::msg('knowledgebase_nav_all_levels', 'Inhaltsverzeichnis (alle Ebenen)'),
                'url' => $currentUrl,
                'isPartOf' => ['@id' => $baseUrl . '#knowledgebase'],
            ];
        } elseif ($searchQuery !== '') {
            $graph[] = [
                '@type' => 'SearchResultsPage',
                '@id' => $currentUrl . '#search',
                'name' => FrontendI18n::msg('knowledgebase_search_results', 'Suchergebnisse'),
                'url' => $currentUrl,
                'isPartOf' => ['@id' => $baseUrl . '#knowledgebase'],
                'query' => $searchQuery,
            ];
        } elseif ($currentArticle instanceof \rex_data_knowledgebase_article) {
            $articleTitle = trim((string) $currentArticle->getValue('title'));
            $articleIntro = trim(strip_tags((string) $currentArticle->getValue('intro')));
            $articleSlug = (string) $currentArticle->getValue('slug');
            $articleUrl = self::toAbsoluteUrl(KnowledgebaseUrl::getArticleUrl($knowledgebase->getId(), $articleSlug));

            $graph[] = [
                '@type' => 'Article',
                '@id' => $articleUrl . '#article',
                'headline' => $articleTitle,
                'url' => $articleUrl,
                'description' => $articleIntro,
                'isPartOf' => ['@id' => $baseUrl . '#knowledgebase'],
            ];
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return '';
        }

        return '<script type="application/ld+json">' . $json . '</script>';
    }

    private static function toAbsoluteUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            $trimmed = '/';
        }

        if (preg_match('#^https?://#i', $trimmed) === 1) {
            return $trimmed;
        }

        $scheme = strtolower(rex_server('REQUEST_SCHEME', 'string', 'https'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'https';
        }

        if (str_starts_with($trimmed, '//')) {
            return $scheme . ':' . $trimmed;
        }

        $host = trim(rex_server('HTTP_HOST', 'string', ''));
        if ($host === '') {
            return $trimmed;
        }

        $path = str_starts_with($trimmed, '/') ? $trimmed : '/' . $trimmed;

        return $scheme . '://' . $host . $path;
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function renderNavigation(array $articles, ?\rex_data_knowledgebase_article $currentArticle, string $articleParam, string $glossaryParam, string $tocParam, string $tagParam, string $tagsParam, bool $glossaryEnabled, bool $glossaryActive, bool $tocActive, bool $tagFilterEnabled, bool $tagMultiEnabled, bool $tagModeActive, array $availableTags, string $selectedTag, array $selectedTags): string
    {
        $currentId = $currentArticle instanceof \rex_data_knowledgebase_article ? $currentArticle->getId() : 0;
        $navSearchId = 'kb-nav-search-' . $articleParam;
        $expandLabel = FrontendI18n::msg('knowledgebase_nav_expand_all', 'Alle aufklappen');
        $collapseLabel = FrontendI18n::msg('knowledgebase_nav_collapse_all', 'Alle einklappen');
        $glossaryLabel = FrontendI18n::msg('knowledgebase_nav_glossary', 'Glossar');
        $glossaryBadgeLabel = FrontendI18n::msg('knowledgebase_nav_glossary_badge', 'A-Z');
        $tocLabel = FrontendI18n::msg('knowledgebase_nav_all_levels', 'Inhaltsverzeichnis (alle Ebenen)');
        $tagsLabel = FrontendI18n::msg('knowledgebase_nav_tags', 'Tags');
        $tagsAllLabel = FrontendI18n::msg('knowledgebase_nav_tags_all', 'Alle Tags');
        $items = '<ul class="kb-app__nav-list">';
        $baseParams = [];
        if ($tagFilterEnabled && $tagMultiEnabled && $selectedTags !== []) {
            $baseParams[$tagsParam] = self::serializeTagList($selectedTags);
        } elseif ($tagFilterEnabled && $selectedTag !== '') {
            $baseParams[$tagParam] = $selectedTag;
        }

        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }

            if ((int) $article->getValue('show_in_nav') !== 1) {
                continue;
            }

            $isCurrent = $article->getId() === $currentId && !$glossaryActive && !$tocActive && !$tagModeActive;
            $items .= '<li class="kb-app__nav-main-item" data-kb-nav-main>';
            $chapters = self::extractArticleChapters($article);
            $hasChapters = count($chapters) > 0;
            $items .= '<div class="kb-app__nav-main-row">';
            $articleUrlParams = $baseParams;
            $articleUrlParams[$articleParam] = (string) $article->getValue('slug');

            $items .= '<a class="kb-app__nav-link' . ($isCurrent ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl($articleUrlParams)) . '" aria-expanded="' . ($isCurrent ? 'true' : 'false') . '">';
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
                    $href = self::buildUrl($articleUrlParams) . '#' . rawurlencode($chapter['anchor']);
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

        $items .= '<li class="kb-app__nav-main-item kb-app__nav-main-item--toc" data-kb-nav-main>';
        $tocUrlParams = [
            $tocParam => 1,
            $tagParam => null,
            $tagsParam => null,
        ];
        $items .= '<a class="kb-app__nav-link kb-app__nav-link--toc' . ($tocActive ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl($tocUrlParams)) . '">';
        $items .= self::renderNavBadgeIcon('list');
        $items .= '<span>' . rex_escape($tocLabel) . '</span>';
        $items .= '</a>';
        $items .= '</li>';

        if ($tagFilterEnabled && [] !== $availableTags) {
            $tagExpanded = $tagModeActive || ($tagMultiEnabled ? $selectedTags !== [] : $selectedTag !== '');
            $items .= '<li class="kb-app__nav-main-item kb-app__nav-main-item--tags" data-kb-nav-main' . ($tagModeActive ? ' data-kb-nav-default-open="1"' : '') . '>';
            $items .= '<div class="kb-app__nav-main-row">';
            $items .= '<div class="kb-app__nav-link kb-app__nav-link--tags' . ($tagExpanded ? ' is-current is-trail' : '') . '">';
            $items .= self::renderNavBadgeIcon('tag');
            $items .= '<span>' . rex_escape($tagsLabel) . '</span>';
            $items .= '</div>';
            $items .= '<span class="kb-app__nav-main-toggle" data-kb-nav-main-toggle role="button" tabindex="0" aria-expanded="' . ($tagExpanded ? 'true' : 'false') . '" aria-label="' . rex_escape(FrontendI18n::msg('knowledgebase_nav_toggle', 'Kapitel')) . '"><span uk-icon="chevron-down"></span></span>';
            $items .= '</div>';

            $items .= '<ul class="kb-app__nav-list kb-app__nav-sublist kb-app__nav-tag-list" data-kb-nav-sublist' . ($tagExpanded ? '' : ' hidden') . '>';

            $allTagParams = [$tagParam => null, $tagsParam => null];
            $items .= '<li class="kb-app__nav-chapter-item" data-kb-nav-chapter>';
            $items .= '<a class="kb-app__nav-link kb-app__nav-link--chapter' . (($tagMultiEnabled ? $selectedTags === [] : $selectedTag === '') ? ' is-current' : '') . '" data-kb-nav-chapter-link href="' . rex_escape(self::buildUrl($allTagParams)) . '">';
            $items .= '<span>' . rex_escape($tagsAllLabel) . '</span>';
            $items .= '</a>';
            $items .= '</li>';

            foreach ($availableTags as $tagValue => $tagData) {
                $tagLabel = (string) ($tagData['label'] ?? '');
                $tagColor = (string) ($tagData['color'] ?? '');
                $tagCount = (int) ($tagData['count'] ?? 0);
                $isChecked = $tagMultiEnabled
                    ? in_array($tagValue, $selectedTags, true)
                    : $selectedTag === $tagValue;

                if ($tagMultiEnabled) {
                    $newTags = $selectedTags;
                    if ($isChecked) {
                        $newTags = array_values(array_filter(
                            $newTags,
                            static fn (string $tag): bool => $tag !== $tagValue,
                        ));
                    } else {
                        $newTags[] = $tagValue;
                    }

                    $tagParams = [
                        $tagParam => null,
                        $tagsParam => self::serializeTagList($newTags),
                    ];
                } else {
                    $tagParams = [$tagParam => $tagValue, $tagsParam => null];
                }

                $items .= '<li class="kb-app__nav-chapter-item" data-kb-nav-chapter>';
                $items .= '<a class="kb-app__nav-link kb-app__nav-link--chapter kb-app__nav-link--tag-option' . ($isChecked ? ' is-current' : '') . '" data-kb-nav-chapter-link href="' . rex_escape(self::buildUrl($tagParams)) . '">';
                $items .= self::renderTagDot($tagColor);
                if ($tagMultiEnabled) {
                    $items .= '<span class="kb-app__tag-check"><input type="checkbox" tabindex="-1"' . ($isChecked ? ' checked' : '') . '></span>';
                }
                $items .= '<span>' . rex_escape($tagLabel);
                if ($tagCount > 0) {
                    $items .= ' <small class="kb-app__tag-count">(' . $tagCount . ')</small>';
                }
                $items .= '</span>';
                $items .= '</a>';
                $items .= '</li>';
            }

            $items .= '</ul>';
            $items .= '</li>';
        }

        if ($glossaryEnabled) {
            $items .= '<li class="kb-app__nav-main-item kb-app__nav-main-item--glossary" data-kb-nav-main>';
            $glossaryUrlParams = [
                $glossaryParam => 1,
                $tagParam => null,
                $tagsParam => null,
            ];
            $items .= '<a class="kb-app__nav-link kb-app__nav-link--glossary' . ($glossaryActive ? ' is-current is-trail' : '') . '" data-kb-nav-main-link href="' . rex_escape(self::buildUrl($glossaryUrlParams)) . '">';
            $items .= '<span class="kb-app__nav-badge">' . rex_escape($glossaryBadgeLabel) . '</span>';
            $items .= '<span>' . rex_escape($glossaryLabel) . '</span>';
            $items .= '</a>';
            $items .= '</li>';
        }

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
            $hasAnyChapter = true;
            $articleUrl = self::buildUrl([$articleParam => (string) $article->getValue('slug'), $tocParam => null]);

            $groupItems = '<li class="kb-app__toc-item">';
            $groupItems .= '<a class="kb-app__nav-link kb-app__nav-link--chapter" href="' . rex_escape($articleUrl) . '">';
            $groupItems .= self::renderNavBadge((string) $article->getValue('nav_badge'), 'file-text');
            $groupItems .= '<span>' . rex_escape($article->getNavLabel()) . '</span>';
            $groupItems .= '</a>';
            $groupItems .= '</li>';

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
    private static function renderArticle(\rex_data_knowledgebase $knowledgebase, ?\rex_data_knowledgebase_article $article, bool $relatedEnabled): string
    {
        if (!$article instanceof \rex_data_knowledgebase_article) {
            return '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_frontend_missing_article', 'Es ist noch kein Beitrag vorhanden.')) . '</div>';
        }

        $intro = trim((string) $article->getValue('intro'));
        $introHtml = '' !== $intro ? '<div class="kb-app__intro">' . $intro . '</div>' : '';
        $breadcrumbs = self::renderBreadcrumbs($knowledgebase, $article);
        $editButton = self::renderBackendEditButton($article);
        $articleBody = GlossaryService::enhanceArticleHtml($knowledgebase, $article->renderContent());
        $recommendationBlocks = '';

        if ($relatedEnabled) {
            $recommendationBlocks .= '<section class="kb-app__recommendation-section" data-kb-related-section hidden>'
                . '<h2 class="kb-app__recommendation-title" data-kb-related-heading>' . rex_escape(FrontendI18n::msg('knowledgebase_related_heading', 'Aehnliche Artikel')) . '</h2>'
                . '<ul class="kb-app__compact-list" data-kb-related-list></ul>'
                . '<div class="uk-alert-warning" data-kb-related-empty hidden>' . rex_escape(FrontendI18n::msg('knowledgebase_related_empty', 'Keine passenden Beitraege gefunden.')) . '</div>'
                . '</section>';
        }

        $recommendations = $recommendationBlocks !== ''
            ? '<section class="kb-app__recommendations">' . $recommendationBlocks . '</section>'
            : '';

        return '<article class="kb-app__article">'
            . $breadcrumbs
            . $editButton
            . '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_article_label', 'Kapitel')) . '</div>'
            . '<h1 class="kb-app__article-title">' . rex_escape((string) $article->getValue('title')) . '</h1>'
            . $introHtml
            . '<div class="kb-app__article-body">' . $articleBody . '</div>'
            . $recommendations
            . '</article>';
    }

    private static function renderBackendEditButton(\rex_data_knowledgebase_article $article): string
    {
        if (PHP_SAPI === 'cli' || !rex_backend_login::hasSession()) {
            return '';
        }

        $tableName = rex::getTable('knowledgebase_article');
        $kbId = (int) $article->getValue('knowledgebase_id');

        // CSRF-Token im Backend-Kontext erzeugen – exakt wie overview.php
        $csrfParams = self::buildBackendCsrfParamsForTable($tableName);

        $editUrl = rex_url::backendPage('knowledgebase/articles', [
            'table_name'      => $tableName,
            'knowledgebase_id' => $kbId,
            'func'            => 'edit',
            'data_id'         => (string) $article->getId(),
            'rex_yform_filter' => ['knowledgebase_id' => $kbId],
            'rex_yform_set'   => ['knowledgebase_id' => $kbId],
        ] + $csrfParams);

        // html_entity_decode wie overview.php, da rex_url backendPage die & kodiert
        $url = html_entity_decode($editUrl, ENT_QUOTES, 'UTF-8');

        return '<div class="kb-app__article-tools">'
            . '<a class="kb-app__article-edit-button uk-button uk-button-default uk-button-small" href="' . rex_escape($url) . '" target="_blank" rel="noopener">'
            . rex_escape(FrontendI18n::msg('knowledgebase_edit_button', 'Bearbeiten'))
            . '</a>'
            . '</div>';
    }

    /**
     * @return array<string, string>
     */
    private static function buildBackendCsrfParamsForTable(string $tableName): array
    {
        if (PHP_SAPI === 'cli') {
            return [];
        }

        $wasBackendContext = rex::isBackend();
        rex::setProperty('redaxo', true);

        try {
            $table = rex_yform_manager_table::get($tableName);
            if (!$table instanceof rex_yform_manager_table) {
                return [];
            }

            return rex_csrf_token::factory($table->getCSRFKey())->getUrlParams();
        } catch (\Throwable) {
            return [];
        } finally {
            rex::setProperty('redaxo', $wasBackendContext);
        }
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function buildArticleClientIndexJson(array $articles, string $articleParam): string
    {
        $index = [];

        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }

            $slug = trim((string) $article->getValue('slug'));
            if ($slug === '') {
                continue;
            }

            $intro = trim(strip_tags((string) $article->getValue('intro')));
            if ($intro === '') {
                $intro = trim(strip_tags((string) $article->renderContent()));
            }
            if (function_exists('mb_substr')) {
                $intro = mb_substr($intro, 0, 220, 'UTF-8');
            } else {
                $intro = substr($intro, 0, 220);
            }

            $tags = array_map(
                static fn (array $entry): string => (string) ($entry['value'] ?? ''),
                $article->getTagEntries(),
            );
            $tags = array_values(array_filter($tags, static fn (string $tag): bool => $tag !== ''));

            $index[] = [
                'slug' => $slug,
                'title' => $article->getNavLabel(),
                'intro' => $intro,
                'tags' => $tags,
                'badge' => trim((string) $article->getValue('nav_badge')),
                'url' => self::buildUrl([$articleParam => $slug]),
            ];
        }

        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : '[]';
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
    * @param list<array{id:int,title:string,nav_title:string,slug:string,intro:string,excerpt:string,is_recent:bool,anchor:string}> $results
     */
    private static function renderSearchResults(array $results, string $articleParam, string $searchQuery): string
    {
        $heading = '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_search_results', 'Suchergebnisse')) . '</div>'
            . '<h3 class="kb-app__article-title">' . rex_escape($searchQuery) . '</h3>';
        $recentLabel = FrontendI18n::msg('knowledgebase_search_recently_updated', 'Kürzlich aktualisiert');

        if (count($results) === 0) {
            return '<section class="kb-app__search-page">' . $heading . '<div class="uk-alert-warning" uk-alert>' . rex_escape(FrontendI18n::msg('knowledgebase_search_empty', 'Keine Treffer gefunden.')) . '</div></section>';
        }

        $items = '';
        foreach ($results as $result) {
            $title = '' !== trim($result['nav_title']) ? $result['nav_title'] : $result['title'];
            $url = self::buildUrl([$articleParam => $result['slug']]);
            $anchor = trim((string) ($result['anchor'] ?? ''));
            if ($anchor !== '') {
                $url .= '#' . rawurlencode($anchor);
            }

            $items .= '<li class="kb-app__search-item">';
            $items .= '<a class="kb-app__search-item-link" href="' . rex_escape($url) . '">';
            $items .= '<strong>' . rex_escape($title) . '</strong>';
            if (!empty($result['is_recent'])) {
                $items .= '<span class="kb-app__search-hit-badge">' . rex_escape($recentLabel) . '</span>';
            }
            $items .= '<span>' . rex_escape($result['excerpt']) . '</span>';
            $items .= '</a>';
            $items .= '</li>';
        }

        return '<section class="kb-app__search-page">' . $heading . '<ul class="kb-app__search-page-list">' . $items . '</ul></section>';
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function renderTagResults(array $articles, string $articleParam, string $tagParam, string $tagsParam, string $selectedTag, array $selectedTags, string $selectedTagLabel): string
    {
        $heading = '<div class="kb-app__article-meta">' . rex_escape(FrontendI18n::msg('knowledgebase_nav_tags', 'Tags')) . '</div>'
            . '<h3 class="kb-app__article-title">' . rex_escape($selectedTagLabel) . '</h3>';

        if (count($articles) === 0) {
            return '<section class="kb-app__search-page">' . $heading . '<div class="uk-alert-warning" uk-alert>' . rex_escape('Keine Beiträge mit diesem Tag gefunden.') . '</div></section>';
        }

        $items = '';
        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }

            $title = $article->getNavLabel();
            $intro = trim(strip_tags((string) $article->getValue('intro')));
            $excerpt = $intro !== '' ? $intro : trim(strip_tags((string) $article->renderContent()));
            if (function_exists('mb_substr')) {
                $excerpt = mb_substr($excerpt, 0, 180, 'UTF-8');
            } else {
                $excerpt = substr($excerpt, 0, 180);
            }

            $items .= '<li class="kb-app__search-item">';
            $tagState = $selectedTags !== []
                ? [$tagParam => null, $tagsParam => self::serializeTagList($selectedTags)]
                : [$tagParam => $selectedTag, $tagsParam => null];

            $items .= '<a class="kb-app__search-item-link" href="' . rex_escape(self::buildUrl([
                $articleParam => (string) $article->getValue('slug'),
                ...$tagState,
            ])) . '">';
            $items .= '<strong>' . rex_escape($title) . '</strong>';
            $items .= '<span>' . rex_escape($excerpt) . '</span>';
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
            if (preg_match('/^kb_(\d+)_(article|q|glossary|toc|tag|tags)$/', (string) $key, $m) === 1) {
                $kbId = (int) $m[1];
                break;
            }
        }

        if ($kbId > 0 && KnowledgebaseUrl::hasProfile($kbId)) {
            $articleKey = 'kb_' . $kbId . '_article';
            $searchKey = 'kb_' . $kbId . '_q';
            $glossaryKey = 'kb_' . $kbId . '_glossary';
            $tocKey = 'kb_' . $kbId . '_toc';
            $tagKey = 'kb_' . $kbId . '_tag';
            $tagsKey = 'kb_' . $kbId . '_tags';
            $tagValue = $params[$tagKey] ?? null;
            $tagsValue = $params[$tagsKey] ?? null;
            $hasTagParam = array_key_exists($tagKey, $params);
            $hasTagsParam = array_key_exists($tagsKey, $params);
            $url = '';

            if (
                ($hasTagParam || $hasTagsParam)
                && !array_key_exists($articleKey, $params)
                && !array_key_exists($searchKey, $params)
                && !array_key_exists($glossaryKey, $params)
                && !array_key_exists($tocKey, $params)
            ) {
                $tag = is_scalar($tagValue) ? trim((string) $tagValue) : '';
                $tags = is_scalar($tagsValue) ? trim((string) $tagsValue) : '';

                return KnowledgebaseUrl::getTagsUrl($kbId, $tag, $tags);
            }

            $searchValue = $params[$searchKey] ?? null;
            if (is_scalar($searchValue) && '' !== trim((string) $searchValue)) {
                $url = KnowledgebaseUrl::getSearchUrl($kbId, (string) $searchValue);
                $url = self::appendOptionalQueryParam($url, $tagKey, $tagValue);

                return self::appendOptionalQueryParam($url, $tagsKey, $tagsValue);
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
                $url = KnowledgebaseUrl::getArticleUrl($kbId, (string) $articleValue);
                $url = self::appendOptionalQueryParam($url, $tagKey, $tagValue);

                return self::appendOptionalQueryParam($url, $tagsKey, $tagsValue);
            }

            $url = KnowledgebaseUrl::getBaseUrl($kbId);

            $url = self::appendOptionalQueryParam($url, $tagKey, $tagValue);

            return self::appendOptionalQueryParam($url, $tagsKey, $tagsValue);
        }

        $query = http_build_query(array_filter($params, static fn (mixed $value): bool => is_scalar($value) && '' !== (string) $value));

        return self::getCurrentPath() . ('' !== $query ? '?' . $query : '');
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
    * @return array<string, array{label:string,color:string,count:int}>
     */
    private static function extractAvailableTags(array $articles): array
    {
        $tags = [];

        foreach ($articles as $article) {
            if (!$article instanceof \rex_data_knowledgebase_article) {
                continue;
            }

            foreach ($article->getTagEntries() as $tagEntry) {
                $normalized = (string) ($tagEntry['value'] ?? '');
                $label = trim((string) ($tagEntry['label'] ?? ''));
                $color = trim((string) ($tagEntry['color'] ?? ''));
                if ($normalized === '' || $label === '' || isset($tags[$normalized])) {
                    if ($normalized !== '' && $label !== '' && isset($tags[$normalized])) {
                        $tags[$normalized]['count']++;
                        if ($tags[$normalized]['color'] === '' && $color !== '') {
                            $tags[$normalized]['color'] = $color;
                        }
                    }
                    continue;
                }

                $tags[$normalized] = [
                    'label' => $label,
                    'color' => $color,
                    'count' => 1,
                ];
            }
        }

        uasort($tags, static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']));

        return $tags;
    }

    private static function renderTagDot(string $color): string
    {
        $style = $color !== '' ? ' style="--kb-tag-color: ' . rex_escape($color) . ';"' : '';

        return '<span class="kb-app__tag-dot" aria-hidden="true"' . $style . '></span>';
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     * @return list<rex_yform_manager_dataset>
     */
    private static function filterArticlesByTag(array $articles, string $tag): array
    {
        if ($tag === '') {
            return $articles;
        }

        $result = [];
        foreach ($articles as $article) {
            if ($article instanceof \rex_data_knowledgebase_article && $article->hasTag($tag)) {
                $result[] = $article;
            }
        }

        return $result;
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     * @param list<string> $tags
     * @return list<rex_yform_manager_dataset>
     */
    private static function filterArticlesByTags(array $articles, array $tags): array
    {
        if ($tags === []) {
            return $articles;
        }

        $result = [];
        foreach ($articles as $article) {
            if ($article instanceof \rex_data_knowledgebase_article && self::articleMatchesAnyTag($article, $tags)) {
                $result[] = $article;
            }
        }

        return $result;
    }

    /**
     * @param list<string> $tags
     */
    private static function articleMatchesAnyTag(\rex_data_knowledgebase_article $article, array $tags): bool
    {
        foreach ($tags as $tag) {
            if ($article->hasTag($tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function parseTagList(string $raw): array
    {
        $parts = preg_split('/[,;\n]+/u', $raw) ?: [];
        $result = [];
        $seen = [];

        foreach ($parts as $part) {
            $tag = \rex_data_knowledgebase_article::normalizeTag((string) $part);
            if ($tag === '' || isset($seen[$tag])) {
                continue;
            }

            $seen[$tag] = true;
            $result[] = $tag;
        }

        return $result;
    }

    /**
     * @param list<string> $tags
     */
    private static function serializeTagList(array $tags): string
    {
        if ($tags === []) {
            return '';
        }

        return implode(',', $tags);
    }

    /**
     * @param list<rex_yform_manager_dataset> $articles
     */
    private static function getFirstArticle(array $articles): ?\rex_data_knowledgebase_article
    {
        foreach ($articles as $article) {
            if ($article instanceof \rex_data_knowledgebase_article) {
                return $article;
            }
        }

        return null;
    }

    private static function appendOptionalQueryParam(string $url, string $paramKey, mixed $paramValue): string
    {
        if (!is_scalar($paramValue)) {
            return $url;
        }

        $value = trim((string) $paramValue);
        if ($value === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . rawurlencode($paramKey) . '=' . rawurlencode($value);
    }

    private static function getCurrentPath(): string
    {
        $requestUri = rex_server('REQUEST_URI', 'string', '');
        $path = parse_url($requestUri, PHP_URL_PATH);

        return is_string($path) && '' !== $path ? $path : '/';
    }

    private static function renderHeaderLogo(\rex_data_knowledgebase $knowledgebase): string
    {
        $logoFile = trim((string) $knowledgebase->getValue('header_logo'));
        if ($logoFile !== '' && \rex_file::extension($logoFile) !== 'svg') {
            $logoFile = '';
        }

        if ($logoFile === '') {
            return '';
        }

        $media = rex_media::get($logoFile);
        if (!$media instanceof rex_media) {
            return '';
        }

        return '<div class="kb-app__hero-logo">'
            . '<img class="kb-app__hero-logo-image" src="' . rex_escape(rex_url::media($logoFile)) . '" alt="' . rex_escape((string) $knowledgebase->getValue('title')) . '">'
            . '</div>';
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