function initKnowledgebaseUi() {
    var findNavMainToggle = function (target) {
        var current = target;

        while (current && current !== document) {
            if (current.nodeType === 1 && current.hasAttribute('data-kb-nav-main-toggle')) {
                return current;
            }

            current = current.parentNode;
        }

        return null;
    };

    var toggleKnowledgebaseNavItem = function (toggle) {
        if (!toggle) {
            return;
        }

        var mainItem = toggle.closest('[data-kb-nav-main]');
        if (!mainItem) {
            return;
        }

        var sublist = mainItem.querySelector('[data-kb-nav-sublist]');
        if (!sublist) {
            return;
        }

        var mainLink = mainItem.querySelector('[data-kb-nav-main-link]');
        var expanded = sublist.hidden;

        sublist.hidden = !expanded;
        mainItem.classList.toggle('is-open', expanded);

        if (mainLink) {
            mainLink.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };


    document.querySelectorAll('.kb-app').forEach(function (app) {
        var searchInput = app.querySelector('.kb-app__search-input');
        var searchForm = app.querySelector('.kb-app__search-form');
        var resultsBox = app.querySelector('.kb-app__search-results');
        var apiUrl = app.getAttribute('data-kb-api') || '';
        var knowledgebaseId = app.getAttribute('data-kb-id') || '';
        var articleParam = app.getAttribute('data-kb-article-param') || '';
        var tagParam = app.getAttribute('data-kb-tag-param') || '';
        var tagsParam = app.getAttribute('data-kb-tags-param') || '';
        var selectedTag = app.getAttribute('data-kb-tag-selected') || '';
        var selectedTags = app.getAttribute('data-kb-tags-selected') || '';
        var basePath = app.getAttribute('data-kb-base-path') || window.location.pathname;
        var suggestUnavailableText = app.getAttribute('data-kb-suggest-unavailable') || 'Autosuggest momentan nicht verfügbar.';
        var suggestEmptyText = app.getAttribute('data-kb-suggest-empty') || 'Keine Vorschläge gefunden.';
        var recentLabel = app.getAttribute('data-kb-search-recent-label') || 'Kürzlich aktualisiert';
        var searchHistoryEnabled = app.getAttribute('data-kb-search-history-enabled') !== '0';
        var searchHistoryHeading = 'Letzte Suchanfragen';
        var searchHistoryKey = 'kb_search_history_' + knowledgebaseId;
        var requestToken = 0;

        var searchToggle = app.querySelector('[data-kb-search-toggle]');
        var searchControl = app.querySelector('[data-kb-search-control]');
        if (searchToggle && searchControl) {
            app.classList.add('kb-app--search-toggle-enabled');
        }

        var readSearchHistory = function () {
            try {
                var stored = JSON.parse(localStorage.getItem(searchHistoryKey) || '[]');
                if (Array.isArray(stored)) {
                    return stored.filter(function (entry) {
                        return typeof entry === 'string' && entry.trim() !== '';
                    });
                }
            } catch (_e) {
                // ignore broken local storage payload
            }

            return [];
        };

        var writeSearchHistory = function (query) {
            var normalized = (query || '').trim();
            if (normalized === '') {
                return;
            }

            var history = readSearchHistory().filter(function (entry) {
                return entry.toLowerCase() !== normalized.toLowerCase();
            });
            history.unshift(normalized);
            history = history.slice(0, 8);

            try {
                localStorage.setItem(searchHistoryKey, JSON.stringify(history));
            } catch (_e) {
                // localStorage might be unavailable
            }
        };

        var renderSearchHistorySuggestions = function (query, showAllOnEmpty) {
            if (!searchHistoryEnabled) {
                return false;
            }

            var needle = (query || '').trim().toLowerCase();
            var history = readSearchHistory();
            var matches = [];

            if (needle === '') {
                if (!showAllOnEmpty) {
                    return false;
                }

                matches = history.slice(0, 5);
            } else {
                matches = history.filter(function (entry) {
                    return entry.toLowerCase().indexOf(needle) >= 0;
                }).slice(0, 5);
            }

            if (matches.length === 0) {
                return false;
            }

            resultsBox.innerHTML = '<div class="kb-app__search-history-title">' + escapeHtml(searchHistoryHeading) + '</div>'
                + matches.map(function (entry) {
                    return '<button class="kb-app__search-hit kb-app__search-hit--history" type="button" data-kb-search-history-query="' + escapeHtml(entry) + '">' 
                        + '<strong>' + escapeHtml(entry) + '</strong>'
                        + '</button>';
                }).join('');
            resultsBox.hidden = false;

            return true;
        };

        var fetchSuggestions = function (showAllHistoryWhenEmpty) {
            var query = searchInput.value.trim();
            requestToken += 1;
            var currentToken = requestToken;

            if (query.length < 3) {
                if (!renderSearchHistorySuggestions(query, !!showAllHistoryWhenEmpty)) {
                    resultsBox.innerHTML = '';
                    resultsBox.hidden = true;
                }
                return;
            }

            var url = apiUrl
                + (apiUrl.indexOf('?') >= 0 ? '&' : '?')
                + 'knowledgebase_id=' + encodeURIComponent(knowledgebaseId)
                + '&q=' + encodeURIComponent(query)
                + '&limit=8';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    return response.ok ? response.json() : { results: [] };
                })
                .then(function (payload) {
                    if (currentToken !== requestToken) {
                        return;
                    }

                    renderResults(payload.results || [], query);
                })
                .catch(function () {
                    if (currentToken !== requestToken) {
                        return;
                    }

                    resultsBox.innerHTML = '<div class="kb-app__search-hit kb-app__search-hit--empty">' + escapeHtml(suggestUnavailableText) + '</div>';
                    resultsBox.hidden = false;
                });
        };

        var renderResults = function (results, query) {
            if (!Array.isArray(results) || results.length === 0) {
                if (!renderSearchHistorySuggestions(query || '')) {
                    resultsBox.innerHTML = '<div class="kb-app__search-hit kb-app__search-hit--empty">' + escapeHtml(suggestEmptyText) + '</div>';
                    resultsBox.hidden = false;
                }
                return;
            }

            resultsBox.innerHTML = results.map(function (item) {
                var title = item.nav_title && item.nav_title.trim() !== '' ? item.nav_title : item.title;
                var url = basePath + '?' + encodeURIComponent(articleParam) + '=' + encodeURIComponent(item.slug);

                if (tagParam && selectedTag) {
                    url += '&' + encodeURIComponent(tagParam) + '=' + encodeURIComponent(selectedTag);
                }

                if (tagsParam && selectedTags) {
                    url += '&' + encodeURIComponent(tagsParam) + '=' + encodeURIComponent(selectedTags);
                }

                return '<a class="kb-app__search-hit" href="' + url + '">'
                    + '<strong>' + escapeHtml(title) + '</strong>'
                    + (item.is_recent ? '<span class="kb-app__search-hit-badge">' + escapeHtml(recentLabel) + '</span>' : '')
                    + '<span>' + escapeHtml(item.excerpt || '') + '</span>'
                    + '</a>';
            }).join('');
            resultsBox.hidden = false;
        };

        if (searchInput && resultsBox) {
            searchInput.addEventListener('input', function () {
                fetchSuggestions(false);
            });
            searchInput.addEventListener('keyup', function () {
                fetchSuggestions(false);
            });
            searchInput.addEventListener('focus', function () {
                fetchSuggestions(true);
            });

            resultsBox.addEventListener('click', function (event) {
                var historyButton = event.target.closest('[data-kb-search-history-query]');
                if (!historyButton) {
                    return;
                }

                event.preventDefault();
                var historyQuery = historyButton.getAttribute('data-kb-search-history-query') || '';
                if (historyQuery.trim() === '') {
                    return;
                }

                searchInput.value = historyQuery;
                if (searchForm) {
                    searchForm.requestSubmit();
                }
            });

            if (searchForm) {
                searchForm.addEventListener('submit', function () {
                    if (searchHistoryEnabled) {
                        writeSearchHistory(searchInput.value);
                    }
                });
            }

            document.addEventListener('click', function (event) {
                if (!app.contains(event.target)) {
                    resultsBox.hidden = true;
                }
            });
        }

        app.querySelectorAll('.kb-app__nav-shell').forEach(function (navShell) {
            var navSearchInput = navShell.querySelector('[data-kb-nav-search]');
            var navExpandToggle = navShell.querySelector('[data-kb-nav-expand-toggle]');

            var mainItems = Array.prototype.slice.call(navShell.querySelectorAll('[data-kb-nav-main]'));
            var allExpanded = false;

            var setMainExpanded = function (mainItem, expanded) {
                var mainLink = mainItem.querySelector('[data-kb-nav-main-link]');
                var mainToggle = mainItem.querySelector('[data-kb-nav-main-toggle]');
                var sublist = mainItem.querySelector('[data-kb-nav-sublist]');

                if (!sublist) {
                    return;
                }

                sublist.hidden = !expanded;
                mainItem.classList.toggle('is-open', expanded);

                if (mainLink) {
                    mainLink.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                }

                if (mainToggle) {
                    mainToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                }
            };

            var updateExpandToggleLabel = function () {
                if (!navExpandToggle) {
                    return;
                }

                var labelExpand = navExpandToggle.getAttribute('data-kb-label-expand') || 'Alle aufklappen';
                var labelCollapse = navExpandToggle.getAttribute('data-kb-label-collapse') || 'Alle einklappen';
                navExpandToggle.textContent = allExpanded ? labelCollapse : labelExpand;
                navExpandToggle.setAttribute('aria-pressed', allExpanded ? 'true' : 'false');
            };

            mainItems.forEach(function (mainItem) {
                var mainToggle = mainItem.querySelector('[data-kb-nav-main-toggle]');
                if (!mainToggle || mainToggle.getAttribute('data-kb-bound') === '1') {
                    return;
                }

                mainToggle.setAttribute('data-kb-bound', '1');
            });

            var expandAll = function () {
                mainItems.forEach(function (mainItem) {
                    var sublist = mainItem.querySelector('[data-kb-nav-sublist]');

                    mainItem.hidden = false;

                    var chapterItems = mainItem.querySelectorAll('[data-kb-nav-chapter]');
                    chapterItems.forEach(function (chapterItem) {
                        chapterItem.hidden = false;
                    });

                    setMainExpanded(mainItem, !!sublist);
                });
            };

            var resetNavState = function () {
                mainItems.forEach(function (mainItem) {
                    var mainLink = mainItem.querySelector('[data-kb-nav-main-link]');
                    var sublist = mainItem.querySelector('[data-kb-nav-sublist]');
                    var isCurrent = !!(mainLink && mainLink.classList.contains('is-current'));
                    var isTrail = !!(mainLink && mainLink.classList.contains('is-trail'));
                    var defaultOpen = mainItem.getAttribute('data-kb-nav-default-open') === '1';

                    mainItem.hidden = false;

                    var chapterItems = mainItem.querySelectorAll('[data-kb-nav-chapter]');
                    chapterItems.forEach(function (chapterItem) {
                        chapterItem.hidden = false;
                    });

                    if (sublist) {
                        setMainExpanded(mainItem, isCurrent || isTrail || defaultOpen);
                    }
                });
            };

            if (navExpandToggle) {
                navExpandToggle.addEventListener('click', function () {
                    allExpanded = !allExpanded;

                    if (allExpanded) {
                        expandAll();
                    } else {
                        resetNavState();
                    }

                    updateExpandToggleLabel();
                });
            }

            if (navSearchInput) {
                navSearchInput.addEventListener('input', function () {
                var query = navSearchInput.value.trim().toLowerCase();

                if (query === '') {
                    if (allExpanded) {
                        expandAll();
                    } else {
                        resetNavState();
                    }

                    updateExpandToggleLabel();
                    return;
                }

                mainItems.forEach(function (mainItem) {
                    var mainLink = mainItem.querySelector('[data-kb-nav-main-link]');
                    var sublist = mainItem.querySelector('[data-kb-nav-sublist]');
                    var descendantLinks = Array.prototype.slice.call(mainItem.querySelectorAll('[data-kb-nav-search-link]'));

                    var mainText = (mainLink ? mainLink.textContent : '').toLowerCase();
                    var mainMatch = mainText.indexOf(query) >= 0;
                    var descendantMatch = false;

                    descendantLinks.forEach(function (descendantLink) {
                        if (descendantLink === mainLink) {
                            return;
                        }

                        var descendantItem = descendantLink.closest('[data-kb-nav-main], [data-kb-nav-chapter]');
                        var descendantText = descendantLink.textContent.toLowerCase();
                        var currentDescendantMatch = descendantText.indexOf(query) >= 0;

                        if (descendantItem && descendantItem !== mainItem) {
                            descendantItem.hidden = !currentDescendantMatch;
                        }

                        if (currentDescendantMatch) {
                            descendantMatch = true;
                        }
                    });

                    mainItem.hidden = !(mainMatch || descendantMatch);

                    if (sublist) {
                        setMainExpanded(mainItem, descendantMatch || mainMatch);
                    }
                });

                updateExpandToggleLabel();
                });
            }

            resetNavState();
            updateExpandToggleLabel();
        });

        initResponsiveSidebarState(app);
        initStickyNavigation(app);
        initNavTrigger(app);
        initAnchorNavigation(app);
        initArticleRecommendations(app);
        initScrollToTopButton(app);
    });

    initInteractiveImageMenuFallback();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKnowledgebaseUi);
} else {
    initKnowledgebaseUi();
}

function initInteractiveImageMenuFallback() {
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-kb-marker-menu-toggle]');
        if (!toggle) {
            return;
        }

        var selector = toggle.getAttribute('data-kb-marker-menu-toggle') || '';
        if (selector === '') {
            return;
        }

        var root = toggle.closest('.kb-marker-map') || document;
        var menu = root.querySelector(selector);
        if (!menu) {
            return;
        }

        // If UIkit handles uk-toggle we avoid double toggling.
        if (typeof window.UIkit !== 'undefined') {
            return;
        }

        event.preventDefault();
        var nextCollapsed = !menu.classList.contains('is-collapsed');
        menu.classList.toggle('is-collapsed', nextCollapsed);
        toggle.setAttribute('aria-expanded', nextCollapsed ? 'false' : 'true');
    });
}

function initScrollToTopButton(app) {
    var article = app.querySelector('.kb-app__article');
    if (!article) {
        return;
    }

    var content = app.querySelector('.kb-app__content');
    if (!content) {
        return;
    }

    var reduceMotion = false;
    if (typeof window.matchMedia === 'function') {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    var getScrollTop = function () {
        return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    };

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'kb-app__scroll-top';
    button.setAttribute('aria-label', 'Nach oben');
    button.setAttribute('title', 'Nach oben');
    button.innerHTML = '<span class="kb-app__scroll-top-icon" uk-icon="icon: chevron-up; ratio: 0.9" aria-hidden="true"></span>'
        + '<span class="kb-app__scroll-top-label">Nach oben</span>';
    document.body.appendChild(button);

    var isLongArticle = function () {
        var articleHeight = article.scrollHeight;
        var contentHeight = content.scrollHeight;
        var minHeight = window.innerHeight * 1.35;
        return Math.max(articleHeight, contentHeight) > minHeight;
    };

    var applyVisibility = function () {
        var show = isLongArticle() && getScrollTop() > window.innerHeight;
        button.classList.toggle('is-visible', show);
    };

    button.addEventListener('click', function () {
        var startY = getScrollTop();
        window.scrollTo({
            top: 0,
            behavior: reduceMotion ? 'auto' : 'smooth'
        });

        if (!reduceMotion && startY > 0) {
            window.setTimeout(function () {
                if (getScrollTop() === startY) {
                    window.scrollTo(0, 0);
                }
            }, 220);
        }
    });

    var onViewportChange = function () {
        applyVisibility();
    };

    window.addEventListener('scroll', onViewportChange, { passive: true });
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('orientationchange', onViewportChange);
    window.setInterval(onViewportChange, 280);
    applyVisibility();
}

function initArticleRecommendations(app) {
    var currentSlug = (app.getAttribute('data-kb-current-article') || '').trim();
    if (currentSlug === '') {
        return;
    }

    var kbId = (app.getAttribute('data-kb-id') || '').trim();
    if (kbId === '') {
        return;
    }

    var recentEnabled = app.getAttribute('data-kb-recent-enabled') === '1';
    var relatedEnabled = app.getAttribute('data-kb-related-enabled') === '1';
    var recentLimit = parseInt(app.getAttribute('data-kb-recent-limit') || '4', 10);
    var relatedLimit = parseInt(app.getAttribute('data-kb-related-limit') || '3', 10);
    var historyLabel = app.getAttribute('data-kb-history-label') || 'Lesehistorie';
    var historyHeading = app.getAttribute('data-kb-history-heading') || 'Zuletzt gelesen';
    var historyEmptyText = app.getAttribute('data-kb-history-empty') || 'Noch keine gelesenen Beiträge vorhanden.';
    if (isNaN(recentLimit) || recentLimit < 1) {
        recentLimit = 4;
    }
    if (isNaN(relatedLimit) || relatedLimit < 1) {
        relatedLimit = 3;
    }

    recentLimit = Math.min(5, recentLimit);
    relatedLimit = Math.min(5, relatedLimit);

    var indexRaw = app.getAttribute('data-kb-articles') || '[]';
    var articleIndex = [];
    try {
        var parsed = JSON.parse(indexRaw);
        if (Array.isArray(parsed)) {
            articleIndex = parsed;
        }
    } catch (_e) {
        articleIndex = [];
    }

    if (articleIndex.length === 0) {
        return;
    }

    var bySlug = {};
    articleIndex.forEach(function (entry) {
        if (!entry || typeof entry !== 'object') {
            return;
        }

        var slug = typeof entry.slug === 'string' ? entry.slug.trim() : '';
        if (slug === '') {
            return;
        }

        bySlug[slug] = {
            slug: slug,
            title: typeof entry.title === 'string' ? entry.title : slug,
            intro: typeof entry.intro === 'string' ? entry.intro : '',
            url: typeof entry.url === 'string' ? entry.url : '#',
            badge: typeof entry.badge === 'string' ? entry.badge.trim() : '',
            tags: Array.isArray(entry.tags) ? entry.tags.filter(function (tag) {
                return typeof tag === 'string' && tag.trim() !== '';
            }) : []
        };
    });

    var currentArticle = bySlug[currentSlug] || null;
    if (!currentArticle) {
        return;
    }

    var storageKey = 'kb_recent_' + kbId;
    var history = [];
    try {
        var stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
        if (Array.isArray(stored)) {
            history = stored.filter(function (value) {
                return typeof value === 'string' && value.trim() !== '';
            });
        }
    } catch (_err) {
        history = [];
    }

    history = history.filter(function (slug) { return slug !== currentSlug; });
    history.unshift(currentSlug);
    history = history.slice(0, 30);

    try {
        localStorage.setItem(storageKey, JSON.stringify(history));
    } catch (_err2) {
        // Wenn localStorage nicht verfuegbar ist, wird nur die aktuelle Session ohne Persistenz gerendert.
    }

    var renderBadge = function (badge, fallbackIcon) {
        var value = (badge || '').trim();
        var badgeHtml = '';
        var fallback = (fallbackIcon || 'file-text').trim().toLowerCase();

        if (/^\d{1,2}$/.test(value)) {
            var number = parseInt(value, 10);
            if (!isNaN(number) && number >= 1) {
                badgeHtml = '<span class="kb-app__nav-badge">' + escapeHtml(String(number)) + '</span>';
            }
        }

        if (badgeHtml === '' && /^[a-z0-9-]{2,40}$/i.test(value)) {
            badgeHtml = '<span class="kb-app__nav-badge kb-app__nav-badge--icon"><span uk-icon="icon: ' + escapeHtml(value.toLowerCase()) + '; ratio: 0.85" aria-hidden="true"></span></span>';
        }

        if (badgeHtml === '' && /^[a-z0-9-]{2,40}$/i.test(fallback)) {
            badgeHtml = '<span class="kb-app__nav-badge kb-app__nav-badge--icon"><span uk-icon="icon: ' + escapeHtml(fallback) + '; ratio: 0.85" aria-hidden="true"></span></span>';
        }

        return '<span class="kb-app__compact-badge">' + badgeHtml + '</span>';
    };

    var truncateTeaser = function (value, maxLength) {
        var text = (value || '').replace(/\s+/g, ' ').trim();
        if (text === '') {
            return '';
        }

        if (text.length <= maxLength) {
            return text;
        }

        return text.slice(0, Math.max(0, maxLength - 1)).trim() + '…';
    };

    var renderCompactList = function (targetList, entries) {
        targetList.innerHTML = entries.map(function (entry) {
            var teaser = truncateTeaser(entry.intro || '', 130);
            return '<li class="kb-app__compact-item">'
                + '<a class="kb-app__compact-link" href="' + escapeHtml(entry.url) + '">'
                + '<span class="kb-app__compact-head">'
                + renderBadge(entry.badge, 'file-text')
                + '<strong>' + escapeHtml(entry.title) + '</strong>'
                + '</span>'
                + (teaser !== '' ? '<span class="kb-app__compact-intro">' + escapeHtml(teaser) + '</span>' : '')
                + '</a>'
                + '</li>';
        }).join('');

        if (typeof window.UIkit !== 'undefined' && typeof window.UIkit.update === 'function') {
            window.UIkit.update(targetList);
        }
    };

    var renderHistoryList = function (targetList, entries) {
        targetList.innerHTML = entries.map(function (entry) {
            return '<li class="kb-app__history-item">'
                + '<a class="kb-app__history-link" href="' + escapeHtml(entry.url) + '">'
                + renderBadge(entry.badge, 'history')
                + '<span>' + escapeHtml(entry.title) + '</span>'
                + '</a>'
                + '</li>';
        }).join('');

        if (typeof window.UIkit !== 'undefined' && typeof window.UIkit.update === 'function') {
            window.UIkit.update(targetList);
        }
    };

    var recentSelection = history
        .filter(function (slug) { return slug !== currentSlug; })
        .map(function (slug) { return bySlug[slug] || null; })
        .filter(function (entry) { return !!entry; })
        .slice(0, recentLimit);

    var historyToggle = app.querySelector('[data-kb-history-toggle]');
    var historyDropdown = app.querySelector('[data-kb-history-dropdown]');
    var historyList = app.querySelector('[data-kb-history-list]');
    var historyEmpty = app.querySelector('[data-kb-history-empty]');

    if (historyToggle && historyDropdown && historyList && historyEmpty) {
        if (!recentEnabled) {
            historyToggle.hidden = true;
            historyDropdown.style.display = 'none';
        } else {
            historyToggle.hidden = false;
            historyToggle.setAttribute('title', historyLabel);
            historyDropdown.style.display = '';

            var headingNode = historyDropdown.querySelector('.kb-app__history-title');
            if (headingNode) {
                headingNode.textContent = historyHeading;
            }

            if (recentSelection.length > 0) {
                renderHistoryList(historyList, recentSelection);
                historyEmpty.hidden = true;
            } else {
                historyList.innerHTML = '';
                historyEmpty.textContent = historyEmptyText;
                historyEmpty.hidden = false;
            }
        }
    }

    if (relatedEnabled) {
        var relatedSection = app.querySelector('[data-kb-related-section]');
        var relatedList = app.querySelector('[data-kb-related-list]');

        if (relatedSection && relatedList) {
            var currentTags = currentArticle.tags || [];

            if (currentTags.length === 0) {
                relatedList.innerHTML = '';
                relatedSection.hidden = true;
                return;
            }

            var readMap = {};
            history.forEach(function (slug) {
                if (typeof slug === 'string' && slug.trim() !== '') {
                    readMap[slug] = true;
                }
            });

            var relatedCandidates = articleIndex
                .map(function (entry) {
                    return entry && typeof entry.slug === 'string' ? bySlug[entry.slug] : null;
                })
                .filter(function (entry) { return !!entry && entry.slug !== currentSlug && !readMap[entry.slug]; })
                .map(function (entry) {
                    var score = 0;
                    if (currentTags.length > 0 && entry.tags.length > 0) {
                        entry.tags.forEach(function (tag) {
                            if (currentTags.indexOf(tag) >= 0) {
                                score += 1;
                            }
                        });
                    }

                    return { entry: entry, score: score };
                });

            relatedCandidates.sort(function (left, right) {
                if (left.score !== right.score) {
                    return right.score - left.score;
                }

                return left.entry.title.localeCompare(right.entry.title, 'de');
            });

            var withScore = relatedCandidates.filter(function (candidate) {
                return candidate.score > 0;
            });
            var chosen = withScore.slice(0, relatedLimit).map(function (candidate) {
                return candidate.entry;
            });

            if (chosen.length > 0) {
                renderCompactList(relatedList, chosen);
                relatedSection.hidden = false;
            } else {
                relatedList.innerHTML = '';
                relatedSection.hidden = true;
            }
        }
    }
}

function initAnchorNavigation(app) {
    var chapterLinks = Array.prototype.slice.call(app.querySelectorAll('[data-kb-nav-chapter-link]'));
    if (chapterLinks.length === 0) {
        return;
    }

    var stickyOffset = parseInt(app.getAttribute('data-kb-sticky-offset') || '0', 10);
    if (isNaN(stickyOffset) || stickyOffset < 0) {
        stickyOffset = 0;
    }

    var reduceMotion = false;
    if (typeof window.matchMedia === 'function') {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    var getLinkUrl = function (link) {
        try {
            return new URL(link.getAttribute('href') || '', window.location.href);
        } catch (_err) {
            return null;
        }
    };

    var isSamePageAnchorLink = function (link) {
        var url = getLinkUrl(link);
        if (!url || !url.hash) {
            return false;
        }

        return url.pathname === window.location.pathname && url.search === window.location.search;
    };

    var getAnchorId = function (link) {
        var url = getLinkUrl(link);
        if (!url || !url.hash) {
            return '';
        }

        return decodeURIComponent(url.hash.replace(/^#/, ''));
    };

    var resolveActiveAnchorId = function () {
        var rawHash = decodeURIComponent(window.location.hash.replace(/^#/, ''));
        if (rawHash === '') {
            return '';
        }

        var exactMatch = chapterLinks.find(function (link) {
            return isSamePageAnchorLink(link) && getAnchorId(link) === rawHash;
        });
        if (exactMatch) {
            return rawHash;
        }

        if (!rawHash.startsWith('kb-chapter-')) {
            var prefixedHash = 'kb-chapter-' + rawHash;
            var prefixedMatch = chapterLinks.find(function (link) {
                return isSamePageAnchorLink(link) && getAnchorId(link) === prefixedHash;
            });
            if (prefixedMatch) {
                return prefixedHash;
            }
        }

        return '';
    };

    var getAnchorElement = function (anchorId) {
        if (!anchorId) {
            return null;
        }

        return document.getElementById(anchorId);
    };

    var scrollToAnchor = function (anchorId, behavior) {
        var target = getAnchorElement(anchorId);
        if (!target) {
            return false;
        }

        var targetTop = target.getBoundingClientRect().top + window.scrollY - stickyOffset - 8;
        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: behavior,
        });

        if (!target.hasAttribute('tabindex')) {
            target.setAttribute('tabindex', '-1');
        }
        target.focus({ preventScroll: true });

        return true;
    };

    var lastActiveAnchorId = null;
    var markActiveAnchor = function (activeAnchorId) {
        var changed = activeAnchorId !== lastActiveAnchorId;
        lastActiveAnchorId = activeAnchorId;
        chapterLinks.forEach(function (link) {
            if (!isSamePageAnchorLink(link)) {
                link.classList.remove('is-active-anchor');
                link.removeAttribute('aria-current');
                return;
            }

            var anchorId = getAnchorId(link);
            var isActive = activeAnchorId !== '' && anchorId === activeAnchorId;
            link.classList.toggle('is-active-anchor', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'location');
                if (changed) {
                    var shell = link.closest('.kb-app__sidebar .kb-app__nav-shell, .kb-app__toc--aside');
                    if (shell) {
                        kbScrollNavIntoView(shell, link);
                    }
                }
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    // Aktiver Abschnitt folgt immer der Scrollposition (ein Anker in der Adresse gilt nur beim Laden)
    var updateActiveFromScroll = function () {
        var candidates = [];

        chapterLinks.forEach(function (link) {
            if (!isSamePageAnchorLink(link)) {
                return;
            }

            var anchorId = getAnchorId(link);
            if (!anchorId) {
                return;
            }

            var element = getAnchorElement(anchorId);
            if (!element) {
                return;
            }

            var top = element.getBoundingClientRect().top + window.scrollY;
            candidates.push({ id: anchorId, top: top });
        });

        if (candidates.length === 0) {
            markActiveAnchor('');
            return;
        }

        candidates.sort(function (a, b) { return a.top - b.top; });

        var threshold = window.scrollY + stickyOffset + 24;
        var activeId = candidates[0].id;
        candidates.forEach(function (candidate) {
            if (candidate.top <= threshold) {
                activeId = candidate.id;
            }
        });

        // Seitenende erreicht: letzter Abschnitt, auch wenn er die Schwelle nicht mehr erreicht
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
            activeId = candidates[candidates.length - 1].id;
        }

        markActiveAnchor(activeId);
    };

    app.addEventListener('click', function (event) {
        var link = event.target.closest('[data-kb-nav-chapter-link]');
        if (!link || !app.contains(link) || !isSamePageAnchorLink(link)) {
            return;
        }

        // aufklappbare Übersicht „In diesem Kapitel“ nach der Auswahl schließen
        var inlineToc = link.closest('details.kb-app__toc--inline');
        if (inlineToc) {
            inlineToc.open = false;
        }

        var anchorId = getAnchorId(link);
        if (!scrollToAnchor(anchorId, reduceMotion ? 'auto' : 'smooth')) {
            return;
        }

        event.preventDefault();

        history.replaceState(null, '', '#' + encodeURIComponent(anchorId));
        markActiveAnchor(anchorId);
    });

    var scrollTicking = false;
    var onScroll = function () {
        if (scrollTicking) {
            return;
        }

        scrollTicking = true;
        window.requestAnimationFrame(function () {
            updateActiveFromScroll();
            scrollTicking = false;
        });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    window.addEventListener('hashchange', onScroll);
    if (resolveActiveAnchorId() === '') {
        updateActiveFromScroll();
    }

    var initialHash = decodeURIComponent(window.location.hash.replace(/^#/, ''));
    if (initialHash !== '') {
        window.requestAnimationFrame(function () {
            var activeAnchorId = resolveActiveAnchorId();
            if (activeAnchorId === '') {
                activeAnchorId = initialHash;
            }

            scrollToAnchor(activeAnchorId, 'auto');
            markActiveAnchor(activeAnchorId);
        });
    }
}

function initResponsiveSidebarState(app) {
    var sidebar = app.querySelector('.kb-app__sidebar');
    var offcanvas = app.querySelector('[uk-offcanvas]');
    var stickyMedia = parseInt(app.getAttribute('data-kb-sticky-media') || '960', 10);

    if (!sidebar) {
        return;
    }

    if (isNaN(stickyMedia) || stickyMedia < 1) {
        stickyMedia = 960;
    }

    var closeOffcanvasIfOpen = function () {
        var openOffcanvas = Array.prototype.slice.call(document.querySelectorAll('.uk-offcanvas.uk-open'));
        if (offcanvas && openOffcanvas.indexOf(offcanvas) < 0) {
            openOffcanvas.push(offcanvas);
        }

        openOffcanvas = openOffcanvas.filter(function (node) {
            if (!node || !node.id) {
                return false;
            }

            return node.id === 'mobile-nav' || node.id.indexOf('kb-app-') === 0;
        });

        openOffcanvas.forEach(function (node) {
            if (document.activeElement && node.contains(document.activeElement) && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }

            if (typeof window.UIkit !== 'undefined' && typeof window.UIkit.offcanvas === 'function') {
                var instance = window.UIkit.offcanvas(node);
                if (instance && typeof instance.hide === 'function') {
                    instance.hide();
                }
            }

            node.classList.remove('uk-open');

            var dialogNode = node.closest('[role="dialog"], dialog');
            if (dialogNode) {
                dialogNode.classList.remove('uk-open');
            }
        });

        document.documentElement.classList.remove('uk-offcanvas-page');
        document.body.classList.remove('uk-offcanvas-page');
        document.body.classList.remove('uk-offcanvas-container');
    };

    var syncSidebar = function () {
        var isDesktop = window.innerWidth >= stickyMedia;
        if (!isDesktop) {
            sidebar.style.display = '';
            return;
        }

        closeOffcanvasIfOpen();
        sidebar.hidden = false;
        sidebar.style.display = '';
    };

    var resizeTimer = null;
    var onResize = function () {
        if (resizeTimer) {
            window.clearTimeout(resizeTimer);
        }

        resizeTimer = window.setTimeout(function () {
            syncSidebar();
        }, 120);
    };

    syncSidebar();
    window.addEventListener('resize', onResize);
    window.addEventListener('orientationchange', onResize);
}

function initStickyNavigation(app) {
    // Die Navigation klebt per CSS (position: sticky, eigene Scrollfläche); hier nur den Abstand
    // unter einem festen Seitenkopf setzen und den aktuellen Eintrag in den sichtbaren Bereich holen.
    var stickyOffset = parseInt(app.getAttribute('data-kb-sticky-offset') || '0', 10);
    if (isNaN(stickyOffset) || stickyOffset < 0) {
        stickyOffset = 0;
    }
    app.style.setProperty('--kb-sidebar-sticky-top', stickyOffset + 'px');

    var shell = app.querySelector('.kb-app__sidebar .kb-app__nav-shell');
    if (shell) {
        var current = shell.querySelector('.kb-app__nav-link.is-current');
        if (current) {
            kbScrollNavIntoView(shell, current);
        }
    }
}

/**
 * Eintrag innerhalb einer scrollbaren Navigation sichtbar machen, ohne die Seite zu bewegen.
 */
function kbScrollNavIntoView(container, link) {
    if (!container || !link || container.scrollHeight <= container.clientHeight + 1) {
        return;
    }

    var containerRect = container.getBoundingClientRect();
    var linkRect = link.getBoundingClientRect();
    var margin = 48;

    if (linkRect.top < containerRect.top + margin) {
        container.scrollTop += linkRect.top - containerRect.top - margin;
    } else if (linkRect.bottom > containerRect.bottom - margin) {
        container.scrollTop += linkRect.bottom - containerRect.bottom + margin;
    }
}

/**
 * Menü-Knopf: auf dem Desktop im Layout „focus“ Schublade am Container-Rand,
 * sonst (mobil) das Off-Canvas.
 */
function initNavTrigger(app) {
    var trigger = app.querySelector('[data-kb-nav-trigger]');
    var sidebar = app.querySelector('.kb-app__sidebar');
    if (!trigger || !sidebar) {
        return;
    }

    var desktopMedia = parseInt(app.getAttribute('data-kb-sticky-media') || '960', 10);
    if (isNaN(desktopMedia) || desktopMedia < 1) {
        desktopMedia = 960;
    }

    var isFocusLayout = app.classList.contains('kb-app--layout-focus');
    var isDesktop = function () {
        return window.innerWidth >= desktopMedia;
    };
    var useDrawer = function () {
        return isFocusLayout && isDesktop();
    };

    var focusFirstIn = function (container) {
        var target = container.querySelector('input, a[href], button:not([data-kb-drawer-close])');
        if (target && typeof target.focus === 'function') {
            target.focus({ preventScroll: true });
        }
    };

    var showCurrentIn = function (container) {
        var scroller = container.querySelector('.kb-app__nav-shell') || container;
        var active = container.querySelector('.kb-app__nav-link.is-active-anchor') || container.querySelector('.kb-app__nav-link.is-current');
        if (active) {
            kbScrollNavIntoView(scroller, active);
            kbScrollNavIntoView(container, active);
        }
    };

    var openDrawer = function () {
        app.classList.add('is-drawer-open');
        trigger.setAttribute('aria-expanded', 'true');
        window.setTimeout(function () {
            showCurrentIn(sidebar);
            focusFirstIn(sidebar);
        }, 60);
    };

    var closeDrawer = function (returnFocus) {
        if (!app.classList.contains('is-drawer-open')) {
            return;
        }
        app.classList.remove('is-drawer-open');
        trigger.setAttribute('aria-expanded', 'false');
        if (returnFocus) {
            trigger.focus({ preventScroll: true });
        }
    };

    var offcanvasNode = function () {
        var id = trigger.getAttribute('data-kb-offcanvas');
        return id ? document.getElementById(id) : null;
    };

    trigger.addEventListener('click', function (event) {
        event.preventDefault();
        if (useDrawer()) {
            if (app.classList.contains('is-drawer-open')) {
                closeDrawer(false);
            } else {
                openDrawer();
            }
            return;
        }

        var node = offcanvasNode();
        if (node && typeof window.UIkit !== 'undefined' && typeof window.UIkit.offcanvas === 'function') {
            window.UIkit.offcanvas(node).show();
        }
    });

    var node = offcanvasNode();
    if (node) {
        node.addEventListener('shown', function () {
            var bar = node.querySelector('.uk-offcanvas-bar') || node;
            showCurrentIn(bar);
        });
    }

    var closeButton = sidebar.querySelector('[data-kb-drawer-close]');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            closeDrawer(true);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && app.classList.contains('is-drawer-open')) {
            closeDrawer(true);
        }
    });

    document.addEventListener('click', function (event) {
        if (!app.classList.contains('is-drawer-open')) {
            return;
        }
        if (sidebar.contains(event.target) || trigger.contains(event.target)) {
            // Klick auf einen Kapitel-Link derselben Seite: Schublade schließen, Text bleibt sichtbar
            if (event.target.closest && event.target.closest('[data-kb-nav-chapter-link]')) {
                closeDrawer(false);
            }
            return;
        }
        closeDrawer(false);
    });

    window.addEventListener('resize', function () {
        if (!useDrawer()) {
            closeDrawer(false);
        }
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}