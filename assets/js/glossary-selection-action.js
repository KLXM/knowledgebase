/**
 * Knowledgebase: „Ins Glossar“ für markierten Text in der Builder-Ansicht.
 *
 * Meldet sich bei der Markier-Leiste des Builders an (window.BuilderSelectionActions, builder ≥ 1.4).
 * Gibt es den Begriff schon (als Begriff oder Schreibweise), kommt ein Hinweis mit Link zum Eintrag,
 * sonst lässt er sich als neuer Eintrag oder als weitere Schreibweise eines vorhandenen Begriffs anlegen.
 * Im Text wird nichts verändert – Glossarbegriffe verlinkt die Ausgabe automatisch.
 */
(function () {
    'use strict';

    var API = './index.php?rex-api-call=knowledgebase_glossary';
    var MODAL_ID = 'kb-glossary-modal';

    function esc(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function cleanText(text) {
        return String(text || '')
            .replace(/\s+/g, ' ')
            .replace(/^[\s„“"'‚‘(\[]+|[\s„“"'‚‘)\].,;:!?]+$/g, '')
            .trim();
    }

    function knowledgebaseId(ctx) {
        var scope = (ctx && ctx.form) || document;
        var field = scope.querySelector('[id$="-knowledgebase_id"] select, [id$="-knowledgebase_id"] input');
        var value = field ? parseInt(field.value, 10) : 0;
        if (value > 0) {
            return value;
        }
        var match = /[?&](?:knowledgebase_id|rex_yform_set%5Bknowledgebase_id%5D|rex_yform_set\[knowledgebase_id\])=(\d+)/.exec(window.location.search);
        return match ? parseInt(match[1], 10) : 0;
    }

    function lookup(kbId, text) {
        return fetch(API + '&action=lookup&kb_id=' + encodeURIComponent(String(kbId || 0)) + '&text=' + encodeURIComponent(text), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) { return response.json(); });
    }

    function post(action, params, token) {
        var body = new URLSearchParams();
        Object.keys(params).forEach(function (key) { body.append(key, String(params[key])); });
        body.append('_csrf_token', token);
        return fetch(API + '&action=' + encodeURIComponent(action), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (response) { return response.json(); });
    }

    function message(text, type) {
        var box = document.createElement('div');
        box.className = 'alert alert-' + (type || 'info') + ' kb-glossary-toast';
        box.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        box.textContent = text;
        document.body.appendChild(box);
        setTimeout(function () { box.remove(); }, 5000);
    }

    function modal(title, bodyHtml, footerHtml) {
        var old = document.getElementById(MODAL_ID);
        if (old) {
            jQuery(old).modal('hide');
            old.remove();
        }
        var html = '<div class="modal fade" id="' + MODAL_ID + '" tabindex="-1" role="dialog" aria-labelledby="' + MODAL_ID + '-title">'
            + '<div class="modal-dialog" role="document"><div class="modal-content">'
            + '<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Schließen"><span aria-hidden="true">&times;</span></button>'
            + '<h4 class="modal-title" id="' + MODAL_ID + '-title">' + esc(title) + '</h4></div>'
            + '<div class="modal-body">' + bodyHtml + '</div>'
            + '<div class="modal-footer">' + footerHtml + '</div>'
            + '</div></div></div>';
        document.body.insertAdjacentHTML('beforeend', html);
        var el = document.getElementById(MODAL_ID);
        jQuery(el).on('hidden.bs.modal', function () { el.remove(); });
        jQuery(el).on('shown.bs.modal', function () {
            var first = el.querySelector('.modal-body select, .modal-body input, .modal-footer .btn-primary');
            if (first) {
                first.focus();
            }
        });
        jQuery(el).modal('show');
        return el;
    }

    function editUrl(data, id) {
        return data.edit_url + '&data_id=' + encodeURIComponent(String(id));
    }

    function showExisting(data, text) {
        var match = data.match;
        var how = match.matched === 'term'
            ? 'als Begriff'
            : 'als Schreibweise von „' + esc(match.term) + '“';
        var offline = match.online ? '' : '<p>Der Eintrag ist zurzeit <strong>offline</strong> und wird deshalb im Text nicht verlinkt.</p>';
        modal('Steht schon im Glossar',
            '<p>„' + esc(text) + '“ steht ' + how + ' im Glossar und wird auf der Website automatisch verlinkt.</p>' + offline,
            '<a class="btn btn-default" target="_blank" rel="noopener" href="' + esc(editUrl(data, match.id)) + '">Eintrag bearbeiten</a>'
            + '<button type="button" class="btn btn-primary" data-dismiss="modal">Schließen</button>');
    }

    function showCreate(data, text) {
        var options = '<option value="new">Neuer Eintrag im Glossar</option>';
        (data.terms || []).forEach(function (term) {
            options += '<option value="' + term.id + '">Weitere Schreibweise von: ' + esc(term.term) + '</option>';
        });

        var body = '<form class="kb-glossary-form" novalidate>'
            + '<div class="form-group"><label for="kb-glossary-assign">Wie soll „' + esc(text) + '“ ins Glossar?</label>'
            + '<select class="form-control" id="kb-glossary-assign" name="assign">' + options + '</select></div>'
            + '<div data-kb-glossary-new>'
            + '<div class="form-group"><label for="kb-glossary-term">Begriff</label>'
            + '<input class="form-control" id="kb-glossary-term" name="term" value="' + esc(text) + '" maxlength="191" required></div>'
            + '<div class="form-group"><label for="kb-glossary-definition">Kurz erklärt</label>'
            + '<input class="form-control" id="kb-glossary-definition" name="definition" maxlength="191">'
            + '<p class="help-block">Ein Satz – erscheint beim Antippen des Begriffs und im Glossar.</p></div>'
            + '<div class="form-group"><label for="kb-glossary-description">Ausführliche Erklärung <span class="text-muted">(optional)</span></label>'
            + '<textarea class="form-control" id="kb-glossary-description" name="description" rows="4"></textarea>'
            + '<p class="help-block">Lässt sich später im Glossar mit Formatierungen ergänzen.</p></div>'
            + '<div class="checkbox"><label><input type="checkbox" name="online" value="1" checked> Gleich online stellen</label></div>'
            + '</div>'
            + '<p class="text-muted" style="margin:0">Im Text muss nichts verlinkt werden – die Website erkennt Glossarbegriffe automatisch.</p>'
            + '</form>';

        var el = modal('Ins Glossar aufnehmen', body,
            '<button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>'
            + '<button type="button" class="btn btn-primary" data-kb-glossary-save>Speichern</button>');

        var form = el.querySelector('form');
        var newFields = el.querySelector('[data-kb-glossary-new]');
        var assign = form.querySelector('[name="assign"]');
        assign.addEventListener('change', function () {
            newFields.hidden = assign.value !== 'new';
        });

        function save() {
            var request;
            if (assign.value === 'new') {
                var term = form.querySelector('[name="term"]').value.trim();
                if (term === '') {
                    form.querySelector('[name="term"]').focus();
                    return;
                }
                request = post('create', {
                    kb_id: data.kb_id,
                    term: term,
                    definition: form.querySelector('[name="definition"]').value,
                    description: form.querySelector('[name="description"]').value,
                    online: form.querySelector('[name="online"]').checked ? 1 : 0
                }, data.token);
            } else {
                request = post('synonym', { id: assign.value, text: text }, data.token);
            }

            var button = el.querySelector('[data-kb-glossary-save]');
            button.disabled = true;
            request.then(function (result) {
                button.disabled = false;
                if (result && result.success) {
                    jQuery(el).modal('hide');
                    message(result.message, 'success');
                    return;
                }
                message((result && result.message) || 'Speichern hat nicht geklappt.', 'danger');
            }).catch(function () {
                button.disabled = false;
                message('Speichern hat nicht geklappt.', 'danger');
            });
        }

        el.querySelector('[data-kb-glossary-save]').addEventListener('click', save);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            save();
        });
    }

    function chooseKnowledgebase(data, text, done) {
        var options = data.bases.map(function (base) {
            return '<option value="' + base.id + '">' + esc(base.title) + '</option>';
        }).join('');
        var el = modal('Welche Wissensbasis?',
            '<div class="form-group"><label for="kb-glossary-kb">Wissensbasis</label><select class="form-control" id="kb-glossary-kb">' + options + '</select></div>',
            '<button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button><button type="button" class="btn btn-primary" data-kb-next>Weiter</button>');
        el.querySelector('[data-kb-next]').addEventListener('click', function () {
            var id = parseInt(el.querySelector('#kb-glossary-kb').value, 10);
            jQuery(el).one('hidden.bs.modal', function () { done(id); });
            jQuery(el).modal('hide');
        });
    }

    function run(kbId, text) {
        lookup(kbId, text).then(function (data) {
            if (!data || !data.success) {
                message((data && data.message) || 'Das Glossar ist gerade nicht erreichbar.', 'danger');
                return;
            }
            if (!data.kb_id) {
                if (data.bases && data.bases.length > 1) {
                    chooseKnowledgebase(data, text, function (id) { run(id, text); });
                } else {
                    message('Es gibt noch keine Wissensbasis.', 'danger');
                }
                return;
            }
            if (data.match) {
                showExisting(data, text);
                return;
            }
            if (!data.can_write) {
                message('„' + text + '“ steht noch nicht im Glossar. Für neue Einträge fehlt dir die Berechtigung.', 'warning');
                return;
            }
            showCreate(data, text);
        }).catch(function () {
            message('Das Glossar ist gerade nicht erreichbar.', 'danger');
        });
    }

    window.BuilderSelectionActions = window.BuilderSelectionActions || [];
    window.BuilderSelectionActions.push({
        id: 'knowledgebase-glossary',
        label: 'Ins Glossar',
        icon: 'fa-book',
        maxLength: 120,
        isAvailable: function (ctx) {
            return cleanText(ctx.text) !== '';
        },
        run: function (ctx) {
            run(knowledgebaseId(ctx), cleanText(ctx.text));
        }
    });
})();
