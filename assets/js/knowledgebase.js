document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.kb-app').forEach(function (app) {
        var searchInput = app.querySelector('.kb-app__search-input');
        var resultsBox = app.querySelector('.kb-app__search-results');
        var apiUrl = app.getAttribute('data-kb-api') || '';
        var knowledgebaseId = app.getAttribute('data-kb-id') || '';
        var articleParam = app.getAttribute('data-kb-article-param') || '';
        var basePath = app.getAttribute('data-kb-base-path') || window.location.pathname;
        var requestToken = 0;

        var renderResults = function (results) {
            if (!Array.isArray(results) || results.length === 0) {
                resultsBox.innerHTML = '';
                resultsBox.hidden = true;
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
            searchInput.addEventListener('input', function () {
                var query = searchInput.value.trim();
                requestToken += 1;
                var currentToken = requestToken;

                if (query.length < 2) {
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

                        resultsBox.innerHTML = '';
                        resultsBox.hidden = true;
                    });
            });

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

                    if (sublist) {
                        sublist.hidden = false;
                    }
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
                        sublist.hidden = !isCurrent;
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
                        sublist.hidden = !(chapterMatch || mainMatch);
                    }
                });

                updateExpandToggleLabel();
            });

            resetNavState();
            updateExpandToggleLabel();
        });
    });
});

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}