(function () {
    'use strict';

    function isKnowledgebaseArticlePage() {
        var params = new URLSearchParams(window.location.search);
        return params.get('page') === 'knowledgebase/articles';
    }

    function isKnowledgebaseArticleAddPage() {
        var params = new URLSearchParams(window.location.search);
        return isKnowledgebaseArticlePage() && (params.get('func') === 'add' || params.get('func') === 'edit');
    }

    function getHierarchyPayload() {
        if (window.rex && window.rex.knowledgebaseArticleHierarchy && Array.isArray(window.rex.knowledgebaseArticleHierarchy.options)) {
            return window.rex.knowledgebaseArticleHierarchy;
        }

        var holder = document.getElementById('kb-article-hierarchy-data');
        if (!holder) {
            return null;
        }

        var raw = holder.getAttribute('data-hierarchy');
        if (!raw) {
            return null;
        }

        try {
            var parsed = JSON.parse(raw);
            if (parsed && Array.isArray(parsed.options)) {
                return parsed;
            }
        } catch (error) {
            return null;
        }

        return null;
    }

    function getParentSelect() {
        var select = document.querySelector('select[name$="[parent_id]"]');
        if (select) {
            return select;
        }

        var label = Array.from(document.querySelectorAll('label')).find(function (el) {
            return (el.textContent || '').indexOf('Übergeordnete Seite') !== -1;
        });
        if (!label) {
            return null;
        }

        var fieldId = label.getAttribute('for');
        if (!fieldId) {
            return null;
        }

        return document.getElementById(fieldId);
    }

    function initHierarchyUi() {
        var hierarchy = getHierarchyPayload();
        var parentSelect = getParentSelect();
        if (parentSelect && hierarchy && Array.isArray(hierarchy.options)) {
            var currentValue = String(parentSelect.value || '');
            var rootLabel = hierarchy.rootLabel || 'Keine übergeordnete Seite';
            parentSelect.innerHTML = '';

            var rootOption = document.createElement('option');
            rootOption.value = '0';
            rootOption.textContent = rootLabel;
            parentSelect.appendChild(rootOption);

            var currentArticleId = String((hierarchy.currentArticleId || 0));

            hierarchy.options.forEach(function (optionData) {
                if (!optionData || typeof optionData !== 'object') {
                    return;
                }

                var option = document.createElement('option');
                option.value = String(optionData.id || '');
                option.textContent = Array((parseInt(optionData.depth || 0, 10) || 0) + 1).join('— ') + (optionData.label || optionData.title || option.value);
                if (optionData.disabled) {
                    option.disabled = true;
                    if (String(option.value) === currentArticleId) {
                        option.textContent += ' (aktuelle Seite)';
                    } else {
                        option.textContent += ' (nicht erlaubt)';
                    }
                }
                parentSelect.appendChild(option);
            });

            parentSelect.value = currentValue !== '' ? currentValue : '0';
        }

    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }

        if (window.jQuery) {
            window.jQuery(document).on('rex:ready', callback);
        }
    }

    if (!isKnowledgebaseArticlePage()) {
        return;
    }

    onReady(initHierarchyUi);

    if (!isKnowledgebaseArticleAddPage()) {
        return;
    }

    var stopEnforcement = false;
    var focusHandled = false;
    var startedAt = Date.now();
    var maxRuntimeMs = 1600;

    function getTargetInput() {
        return document.querySelector('input[id^="yform-data_edit-rex_knowledgebase_article-field-"][type="text"]:not([disabled])');
    }

    function tinyEditorIsActive() {
        var active = document.activeElement;
        return !!active && active.matches('iframe.tox-edit-area__iframe');
    }

    function shouldEnforce() {
        if (stopEnforcement) {
            return false;
        }

        return (Date.now() - startedAt) <= maxRuntimeMs;
    }

    function enforceInitialFocus() {
        var target = getTargetInput();
        if (!target || !shouldEnforce()) {
            return;
        }

        if (tinyEditorIsActive()) {
            var active = document.activeElement;
            if (active && typeof active.blur === 'function') {
                active.blur();
            }

            try {
                target.focus({ preventScroll: true });
                focusHandled = true;
            } catch (error) {
                // Browser ohne preventScroll-Unterstuetzung ignorieren wir hier,
                // damit kein erzwungenes Scroll-Springen entsteht.
            }
        }
    }

    document.addEventListener('mousedown', function () {
        stopEnforcement = true;
    }, true);

    document.addEventListener('keydown', function () {
        stopEnforcement = true;
    }, true);

    document.addEventListener('focusin', function (event) {
        if (!shouldEnforce() || focusHandled) {
            return;
        }

        if (event.target && event.target.matches('iframe.tox-edit-area__iframe')) {
            window.setTimeout(enforceInitialFocus, 0);
        }
    }, true);

    document.addEventListener('submit', function (event) {
        if (!(event.target instanceof HTMLFormElement)) {
            return;
        }

        if (!event.target.closest('form')) {
            return;
        }

        if (typeof window.tinymce === 'undefined' || !Array.isArray(window.tinymce.editors)) {
            return;
        }

        window.tinymce.triggerSave();

        window.tinymce.editors.forEach(function (editor) {
            if (!editor || typeof editor.getElement !== 'function' || typeof editor.setDirty !== 'function') {
                return;
            }

            var element = editor.getElement();
            if (element && event.target.contains(element)) {
                editor.setDirty(false);
            }
        });
    }, true);

    window.addEventListener('load', function () {
        var target = getTargetInput();
        if (target) {
            target.setAttribute('autofocus', 'autofocus');
        }
    });
})();
