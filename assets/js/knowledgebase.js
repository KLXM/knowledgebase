document.addEventListener('DOMContentLoaded', function () {
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
        var searchHistoryEnabled = app.getAttribute('data-kb-search-history-enabled') !== '0';
        var searchHistoryHeading = 'Letzte Suchanfragen';
        var searchHistoryKey = 'kb_search_history_' + knowledgebaseId;
        var requestToken = 0;

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
            if (!navSearchInput) {
                return;
            }

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

            mainItems.forEach(function (mainItem) {
                var mainToggle = mainItem.querySelector('[data-kb-nav-main-toggle]');
                var sublist = mainItem.querySelector('[data-kb-nav-sublist]');

                if (!mainToggle || !sublist) {
                    return;
                }

                mainToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var expanded = sublist.hidden;
                    setMainExpanded(mainItem, expanded);
                });

                mainToggle.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    var expanded = sublist.hidden;
                    setMainExpanded(mainItem, expanded);
                });
            });

            var updateExpandToggleLabel = function () {
                if (!navExpandToggle) {
                    return;
                }

                var labelExpand = navExpandToggle.getAttribute('data-kb-label-expand') || 'Alle aufklappen';
                var labelCollapse = navExpandToggle.getAttribute('data-kb-label-collapse') || 'Alle einklappen';
                navExpandToggle.textContent = allExpanded ? labelCollapse : labelExpand;
                navExpandToggle.setAttribute('aria-pressed', allExpanded ? 'true' : 'false');
            };

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
                    var defaultOpen = mainItem.getAttribute('data-kb-nav-default-open') === '1';

                    mainItem.hidden = false;

                    var chapterItems = mainItem.querySelectorAll('[data-kb-nav-chapter]');
                    chapterItems.forEach(function (chapterItem) {
                        chapterItem.hidden = false;
                    });

                    if (sublist) {
                        setMainExpanded(mainItem, isCurrent || defaultOpen);
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
                    var chapterLinks = Array.prototype.slice.call(mainItem.querySelectorAll('[data-kb-nav-chapter-link]'));

                    var mainText = (mainLink ? mainLink.textContent : '').toLowerCase();
                    var mainMatch = mainText.indexOf(query) >= 0;
                    var chapterMatch = false;

                    chapterLinks.forEach(function (chapterLink) {
                        var chapterItem = chapterLink.closest('[data-kb-nav-chapter]');
                        var chapterText = chapterLink.textContent.toLowerCase();
                        var currentChapterMatch = chapterText.indexOf(query) >= 0;

                        if (chapterItem) {
                            chapterItem.hidden = !currentChapterMatch;
                        }

                        if (currentChapterMatch) {
                            chapterMatch = true;
                        }
                    });

                    mainItem.hidden = !(mainMatch || chapterMatch);

                    if (sublist) {
                        setMainExpanded(mainItem, chapterMatch || mainMatch);
                    }
                });

                updateExpandToggleLabel();
            });

            resetNavState();
            updateExpandToggleLabel();
        });

        initResponsiveSidebarState(app);
        initStickyNavigation(app);
        initAnchorNavigation(app);
        initArticleRecommendations(app);
        initScrollToTopButton(app);
    });
});

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

    var getAnchorElement = function (anchorId) {
        if (!anchorId) {
            return null;
        }

        return document.getElementById(anchorId);
    };

    var markActiveAnchor = function (activeAnchorId) {
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
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

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

        markActiveAnchor(activeId);
    };

    app.addEventListener('click', function (event) {
        var link = event.target.closest('[data-kb-nav-chapter-link]');
        if (!link || !app.contains(link) || !isSamePageAnchorLink(link)) {
            return;
        }

        var anchorId = getAnchorId(link);
        var target = getAnchorElement(anchorId);
        if (!target) {
            return;
        }

        event.preventDefault();

        var targetTop = target.getBoundingClientRect().top + window.scrollY - stickyOffset - 8;
        window.scrollTo({
            top: Math.max(0, targetTop),
            behavior: reduceMotion ? 'auto' : 'smooth'
        });

        if (!target.hasAttribute('tabindex')) {
            target.setAttribute('tabindex', '-1');
        }
        target.focus({ preventScroll: true });

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
    updateActiveFromScroll();
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
    var navShell = app.querySelector('.kb-app__sidebar .kb-app__nav-shell');
    var content = app.querySelector('.kb-app__content');
    var layout = app.querySelector('.kb-app__layout');

    if (!navShell || !content || !layout) {
        return;
    }

    var stickyInitialized = false;

    var tryInitSticky = function () {
        if (stickyInitialized) {
            return;
        }

        if (typeof window.UIkit === 'undefined' || typeof window.UIkit.sticky !== 'function') {
            return;
        }

        stickyInitialized = true;
        initWithUIKit();
    };

    var initWithUIKit = function () {
        var stickyOffset = parseInt(app.getAttribute('data-kb-sticky-offset') || '0', 10);
        var stickyMedia = parseInt(app.getAttribute('data-kb-sticky-media') || '960', 10);
        var stickyInstance = null;

        if (isNaN(stickyOffset) || stickyOffset < 0) {
            stickyOffset = 0;
        }

        if (isNaN(stickyMedia) || stickyMedia < 1) {
            stickyMedia = 960;
        }

        var endSelector = '#' + app.id + ' .kb-app__content';

        var destroySticky = function () {
            if (stickyInstance && typeof stickyInstance.$destroy === 'function') {
                stickyInstance.$destroy(true);
            }

            stickyInstance = null;
            navShell.style.maxHeight = '';
            navShell.style.overflowY = '';
        };

        var applyStickyState = function () {
            var isDesktop = window.innerWidth >= stickyMedia;
            var availableHeight = window.innerHeight - stickyOffset;
            var navHeight = navShell.offsetHeight;
            var contentHeight = content.offsetHeight;
            var canStick = isDesktop && availableHeight > 0 && navHeight <= availableHeight && navHeight < contentHeight;

            if (!canStick) {
                destroySticky();
                return;
            }

            if (!stickyInstance) {
                stickyInstance = window.UIkit.sticky(navShell, {
                    offset: stickyOffset,
                    end: endSelector,
                    media: stickyMedia,
                });
            }

            if (typeof window.UIkit.update === 'function') {
                window.UIkit.update(navShell);
            }
        };

        var resizeTimer = null;
        var onResize = function () {
            if (resizeTimer) {
                window.clearTimeout(resizeTimer);
            }

            resizeTimer = window.setTimeout(function () {
                applyStickyState();
            }, 120);
        };

        applyStickyState();
        window.addEventListener('resize', onResize);
    };

    tryInitSticky();

    if (stickyInitialized) {
        return;
    }

    var retryCount = 0;
    var retryTimer = window.setInterval(function () {
        retryCount += 1;
        tryInitSticky();

        if (stickyInitialized || retryCount >= 40) {
            window.clearInterval(retryTimer);
        }
    }, 100);

    window.addEventListener('load', function () {
        tryInitSticky();
        if (stickyInitialized) {
            window.clearInterval(retryTimer);
        }
    }, { once: true });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}