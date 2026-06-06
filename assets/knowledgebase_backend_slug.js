(function () {
  'use strict';

  function isTextInput(node) {
    return node instanceof HTMLInputElement && node.type === 'text';
  }

  function fieldName(input) {
    return (input.getAttribute('name') || '').toLowerCase();
  }

  function isTitleField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)title\]?$/.test(name) || Boolean(input.closest('.yform-name-title'));
  }

  function isNavTitleField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)nav_title\]?$/.test(name) || Boolean(input.closest('.yform-name-nav_title'));
  }

  function isSlugField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)slug\]?$/.test(name) || Boolean(input.closest('.yform-name-slug'));
  }

  function slugify(value) {
    if (!value) {
      return '';
    }

    var normalized = String(value)
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');

    return normalized;
  }

  function findSlugPair(context) {
    if (!(context instanceof HTMLElement) && !(context instanceof Document)) {
      return null;
    }

    var root = context;
    var form = root instanceof HTMLFormElement ? root : root.querySelector('form');
    if (!(form instanceof HTMLFormElement)) {
      return null;
    }

    var textInputs = form.querySelectorAll('input[type="text"]');
    var titleInput = null;
    var navTitleInput = null;
    var slugInput = null;

    textInputs.forEach(function (input) {
      if (!titleInput && isTitleField(input)) {
        titleInput = input;
      }

      if (!navTitleInput && isNavTitleField(input)) {
        navTitleInput = input;
      }

      if (!slugInput && isSlugField(input)) {
        slugInput = input;
      }
    });

    if (!slugInput) {
      return null;
    }

    return {
      titleInput: titleInput,
      navTitleInput: navTitleInput,
      slugInput: slugInput
    };
  }

  function getSlugSource(pair) {
    if (pair.titleInput && pair.titleInput.value.trim() !== '') {
      return pair.titleInput.value;
    }

    if (pair.navTitleInput && pair.navTitleInput.value.trim() !== '') {
      return pair.navTitleInput.value;
    }

    return '';
  }

  function updateSlug(pair) {
    if (!pair || !isTextInput(pair.slugInput)) {
      return;
    }

    var generatedSlug = slugify(getSlugSource(pair));
    var wasEmpty = pair.slugInput.value.trim() === '';
    var untouched = pair.slugInput.dataset.kbSlugTouched !== '1';

    if (untouched || wasEmpty) {
      pair.slugInput.value = generatedSlug;
    }
  }

  function init(context) {
    var root = context || document;
    var pairs = [];

    if (root instanceof HTMLFormElement) {
      pairs.push(findSlugPair(root));
    } else {
      var forms = root.querySelectorAll ? root.querySelectorAll('form') : [];
      forms.forEach(function (form) {
        pairs.push(findSlugPair(form));
      });
    }

    pairs.forEach(function (pair) {
      if (!pair) {
        return;
      }

      if (pair.slugInput.dataset.kbSlugAutofillBound === '1') {
        return;
      }

      pair.slugInput.dataset.kbSlugAutofillBound = '1';
      if (pair.slugInput.value.trim() !== '') {
        pair.slugInput.dataset.kbSlugTouched = '1';
      }
      updateSlug(pair);
    });
  }

  document.addEventListener('input', function (event) {
    var target = event.target;
    if (!isTextInput(target)) {
      return;
    }

    var form = target.closest('form');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    var pair = findSlugPair(form);
    if (!pair) {
      return;
    }

    if (target === pair.slugInput) {
      if (pair.slugInput.value.trim() === '') {
        pair.slugInput.dataset.kbSlugTouched = '0';
        updateSlug(pair);
      } else {
        pair.slugInput.dataset.kbSlugTouched = '1';
      }

      return;
    }

    if (target === pair.titleInput || target === pair.navTitleInput) {
      updateSlug(pair);
    }
  }, true);

  if (window.jQuery) {
    window.jQuery(document).on('rex:ready', function (event, viewRoot) {
      init(viewRoot || document);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      init(document);
    });
  } else {
    init(document);
  }
})();
