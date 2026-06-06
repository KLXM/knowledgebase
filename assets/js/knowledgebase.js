document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.kb-app').forEach(function (app) {
        var searchInput = app.querySelector('.kb-app__search-input');
        var resultsBox = app.querySelector('.kb-app__search-results');
        var apiUrl = app.getAttribute('data-kb-api') || '';
        var knowledgebaseId = app.getAttribute('data-kb-id') || '';
        var articleParam = app.getAttribute('data-kb-article-param') || '';
        var basePath = app.getAttribute('data-kb-base-path') || window.location.pathname;
        var requestToken = 0;
        var fetchSuggestions = function () {
            var query = searchInput.value.trim();
            requestToken += 1;
            var currentToken = requestToken;

            if (query.length < 3) {
                resultsBox.innerHTML = '';
                resultsBox.hidden = true;
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

                    renderResults(payload.results || []);
                })
                .catch(function () {
                    if (currentToken !== requestToken) {
                        return;
                    }

                    resultsBox.innerHTML = '<div class="kb-app__search-hit kb-app__search-hit--empty">Autosuggest momentan nicht verfügbar.</div>';
                    resultsBox.hidden = false;
                });
        };

        var renderResults = function (results) {
            if (!Array.isArray(results) || results.length === 0) {
                resultsBox.innerHTML = '<div class="kb-app__search-hit kb-app__search-hit--empty">Keine Vorschläge gefunden.</div>';
                resultsBox.hidden = false;
                return;
            }

            resultsBox.innerHTML = results.map(function (item) {
                var title = item.nav_title && item.nav_title.trim() !== '' ? item.nav_title : item.title;
                var url = basePath + '?' + encodeURIComponent(articleParam) + '=' + encodeURIComponent(item.slug);

                return '<a class="kb-app__search-hit" href="' + url + '">'
                    + '<strong>' + escapeHtml(title) + '</strong>'
                    + '<span>' + escapeHtml(item.excerpt || '') + '</span>'
                    + '</a>';
            }).join('');
            resultsBox.hidden = false;
        };

        if (searchInput && resultsBox) {
            searchInput.addEventListener('input', fetchSuggestions);
            searchInput.addEventListener('keyup', fetchSuggestions);
            searchInput.addEventListener('focus', fetchSuggestions);

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

                    mainItem.hidden = false;

                    var chapterItems = mainItem.querySelectorAll('[data-kb-nav-chapter]');
                    chapterItems.forEach(function (chapterItem) {
                        chapterItem.hidden = false;
                    });

                    if (sublist) {
                        setMainExpanded(mainItem, isCurrent);
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

        initStickyNavigation(app);
    });
});

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