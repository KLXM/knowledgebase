(function () {
    'use strict';

    function escHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildHref(knowledgebaseId, slug, anchor) {
        var href = '?kb_' + encodeURIComponent(String(knowledgebaseId)) + '_article=' + encodeURIComponent(String(slug));
        if (String(anchor || '').trim() !== '') {
            href += '#' + encodeURIComponent(String(anchor).trim());
        }
        return href;
    }

    /**
     * Löst die URL über den API-Endpoint auf (URL-Addon-Profil bevorzugt).
     * Fällt zurück auf den internen Fallback-Link wenn kein Profil aktiv ist.
     */
    function resolveHref(knowledgebaseId, slug, anchor) {
        var params = 'rex-api-call=knowledgebase_url'
            + '&kb_id=' + encodeURIComponent(String(knowledgebaseId))
            + '&slug=' + encodeURIComponent(String(slug));
        if (String(anchor || '').trim() !== '') {
            params += '&anchor=' + encodeURIComponent(String(anchor).trim());
        }

        return fetch('./index.php?' + params, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.ok ? response.json() : null;
        }).then(function (data) {
            if (data && data.url && String(data.url).trim() !== '') {
                return String(data.url);
            }
            return buildHref(knowledgebaseId, slug, anchor);
        }).catch(function () {
            return buildHref(knowledgebaseId, slug, anchor);
        });
    }

    function insertLink(editor, href, label) {
        if (editor.selection.isCollapsed()) {
            editor.insertContent('<a href="' + escHtml(href) + '">' + escHtml(label) + '</a>');
            return;
        }

        editor.execCommand('mceInsertLink', false, { href: href });
    }

    function fetchTree() {
        return fetch('./index.php?rex-api-call=knowledgebase_links', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (response) {
            return response.ok ? response.json() : { success: false, tree: [] };
        });
    }

    function createMenuItems(editor, tree) {
        if (!Array.isArray(tree) || tree.length === 0) {
            return [
                {
                    type: 'menuitem',
                    text: 'Keine Wissensbasis gefunden',
                    onAction: function () {}
                }
            ];
        }

        return tree.map(function (kb) {
            var kbTitle = String(kb.title || ('Wissensbasis #' + String(kb.id || '')));
            var articles = Array.isArray(kb.articles) ? kb.articles : [];

            return {
                type: 'nestedmenuitem',
                text: kbTitle,
                getSubmenuItems: function () {
                    if (articles.length === 0) {
                        return [{ type: 'menuitem', text: 'Keine Artikel', onAction: function () {} }];
                    }

                    return articles.map(function (article) {
                        var articleTitle = String(article.title || article.slug || 'Artikel');
                        var articleLabel = article.online === false ? articleTitle + ' (offline)' : articleTitle;
                        var anchors = Array.isArray(article.anchors) ? article.anchors : [];

                        return {
                            type: 'nestedmenuitem',
                            text: articleLabel,
                            getSubmenuItems: function () {
                                var items = [];
                                items.push({
                                    type: 'menuitem',
                                    text: 'Artikel verlinken',
                                    onAction: function () {
                                        var selectedText = editor.selection.getContent({ format: 'text' });
                                        var linkText = String(selectedText || '').trim() !== '' ? selectedText : articleTitle;
                                        resolveHref(kb.id, article.slug, '').then(function (href) {
                                            insertLink(editor, href, linkText);
                                        });
                                    }
                                });

                                if (anchors.length > 0) {
                                    items.push({
                                        type: 'nestedmenuitem',
                                        text: 'Anker im Artikel',
                                        getSubmenuItems: function () {
                                            return anchors.map(function (anchor) {
                                                var anchorTitle = String(anchor.title || anchor.anchor || 'Anker');
                                                return {
                                                    type: 'menuitem',
                                                    text: anchorTitle,
                                                    onAction: function () {
                                                        var anchorVal = anchor.anchor || '';
                                                        var selectedText = editor.selection.getContent({ format: 'text' });
                                                        var linkText = String(selectedText || '').trim() !== '' ? selectedText : anchorTitle;
                                                        resolveHref(kb.id, article.slug, anchorVal).then(function (href) {
                                                            insertLink(editor, href, linkText);
                                                        });
                                                    }
                                                };
                                            });
                                        }
                                    });
                                }

                                return items;
                            }
                        };
                    });
                }
            };
        });
    }

    function setup(editor) {
        var cachedTree = null;

        function ensureTree() {
            if (cachedTree !== null) {
                return Promise.resolve(cachedTree);
            }

            return fetchTree().then(function (data) {
                cachedTree = Array.isArray(data.tree) ? data.tree : [];
                return cachedTree;
            });
        }

        editor.ui.registry.addMenuButton('knowledgebase_link', {
            icon: 'link',
            tooltip: 'Knowledgebase-Link einfügen',
            fetch: function (callback) {
                ensureTree().then(function (tree) {
                    callback(createMenuItems(editor, tree));
                }).catch(function () {
                    callback([{ type: 'menuitem', text: 'Fehler beim Laden', onAction: function () {} }]);
                });
            }
        });

        editor.ui.registry.addButton('knowledgebase_link_quick', {
            icon: 'link',
            tooltip: 'Knowledgebase-Link einfügen',
            onAction: function () {
                ensureTree().then(function (tree) {
                    if (!Array.isArray(tree) || tree.length === 0) {
                        editor.notificationManager.open({
                            text: 'Keine Wissensbasis gefunden.',
                            type: 'warning',
                            timeout: 3000
                        });
                        return;
                    }

                    editor.notificationManager.open({
                        text: 'Bitte den Menü-Button für die Struktur-Auswahl verwenden.',
                        type: 'info',
                        timeout: 2600
                    });
                }).catch(function () {
                    editor.notificationManager.open({
                        text: 'Knowledgebase-Linkstruktur konnte nicht geladen werden.',
                        type: 'error',
                        timeout: 4000
                    });
                });
            }
        });
    }

    if (typeof tinymce !== 'undefined') {
        tinymce.PluginManager.add('knowledgebase_link', setup);
    }
})();
