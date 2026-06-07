(function () {
    'use strict';

    function isKnowledgebaseArticleAddPage() {
        var params = new URLSearchParams(window.location.search);
        return params.get('page') === 'knowledgebase/articles' && (params.get('func') === 'add' || params.get('func') === 'edit');
    }

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

    window.addEventListener('load', function () {
        var target = getTargetInput();
        if (target) {
            target.setAttribute('autofocus', 'autofocus');
        }
    });
})();
