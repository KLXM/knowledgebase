(function () {
  'use strict';

  function isTextInput(node) {
    return node instanceof HTMLInputElement && node.type === 'text';
  }

  function fieldName(input) {
    return (input.getAttribute('name') || '').toLowerCase();
  }

  // YForm 5 vergibt numerische Feldnamen (FORM[…][7]); der Feldname steht in der ID der Feldgruppe (…-slug)
  function groupIdEndsWith(input, suffix) {
    var group = input.closest('.form-group');
    return Boolean(group && group.id && group.id.slice(-suffix.length) === suffix);
  }

  function isTitleField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)title\]?$/.test(name) || Boolean(input.closest('.yform-name-title')) || groupIdEndsWith(input, '-title');
  }

  function isNavTitleField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)nav_title\]?$/.test(name) || Boolean(input.closest('.yform-name-nav_title')) || groupIdEndsWith(input, '-nav_title');
  }

  function isSlugField(input) {
    if (!isTextInput(input)) {
      return false;
    }

    var name = fieldName(input);
    return /(^|\[)slug\]?$/.test(name) || Boolean(input.closest('.yform-name-slug')) || groupIdEndsWith(input, '-slug');
  }

  function slugify(value) {
    if (!value) {
      return '';
    }

    // Umlaute wie rex_string::normalize() auf dem Server umschreiben (ä → ae …), damit Vorschau und Speichern übereinstimmen
    var normalized = String(value)
      .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue')
      .replace(/Ä/g, 'Ae').replace(/Ö/g, 'Oe').replace(/Ü/g, 'Ue')
      .replace(/ß/g, 'ss')
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
    // Navigationstitel hat Vorrang (kürzer, passt zur Navigation), sonst der Titel
    if (pair.navTitleInput && pair.navTitleInput.value.trim() !== '') {
      return pair.navTitleInput.value;
    }

    if (pair.titleInput && pair.titleInput.value.trim() !== '') {
      return pair.titleInput.value;
    }

    return '';
  }

  /**
   * Knopf „Aus Navigationstitel erzeugen“ am Slug-Feld. Bei bestehenden Beiträgen Hinweis,
   * dass sich die Adresse ändert (Links aus dem Knowledgebase-Plugin passen sich an, externe nicht).
   */
  function addGeneratorButton(pair) {
    var input = pair.slugInput;
    if (input.dataset.kbSlugGeneratorBound === '1') {
      return;
    }
    input.dataset.kbSlugGeneratorBound = '1';

    var initialValue = input.value.trim();
    var wrap = document.createElement('div');
    wrap.className = 'kb-slug-generator';
    wrap.style.marginTop = '6px';

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-default btn-xs';
    button.innerHTML = '<i class="rex-icon fa-magic" aria-hidden="true"></i> Aus Navigationstitel erzeugen';

    var notice = document.createElement('div');
    notice.className = 'help-block';
    notice.style.display = 'none';
    notice.textContent = 'Die Adresse des Beitrags ändert sich beim Speichern. Links aus dem Knowledgebase-Plugin passen sich an, Lesezeichen und Links von außen nicht.';

    button.addEventListener('click', function () {
      var generated = slugify(getSlugSource(pair));
      if (generated === '') {
        return;
      }
      input.value = generated;
      input.dataset.kbSlugTouched = '1';
      input.dispatchEvent(new Event('change', { bubbles: true }));
      notice.style.display = initialValue !== '' && initialValue !== generated ? '' : 'none';
    });

    wrap.appendChild(button);
    wrap.appendChild(notice);
    input.insertAdjacentElement('afterend', wrap);
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
      addGeneratorButton(pair);
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
