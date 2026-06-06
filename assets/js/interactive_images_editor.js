(function ($) {
    'use strict';

    if (typeof $ !== 'function') {
        return;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function sanitizePercent(value) {
        var number = parseFloat(String(value || ''));
        if (Number.isNaN(number)) {
            number = 50;
        }
        return Math.max(0, Math.min(100, number));
    }

    function mediaToUrl(fileName) {
        var clean = String(fileName || '').trim();
        if (clean === '') {
            return '';
        }

        if (/^https?:\/\//i.test(clean) || clean.charAt(0) === '/') {
            return clean;
        }

        var normalized = clean.split('/').map(function (part) {
            return encodeURIComponent(part);
        }).join('/');

        return '/media/' + normalized;
    }

    function parseMarkers(raw) {
        var text = String(raw || '').trim();
        if (text === '') {
            return [];
        }

        var decoded;
        try {
            decoded = JSON.parse(text);
        } catch (error) {
            return [];
        }

        if (!Array.isArray(decoded)) {
            return [];
        }

        return decoded
            .filter(function (item) { return item && typeof item === 'object'; })
            .map(function (item) {
                return {
                    title: String(item.title || '').trim(),
                    content: String(item.content || ''),
                    x: sanitizePercent(item.x),
                    y: sanitizePercent(item.y)
                };
            });
    }

    function fieldNameMatches(name, fieldName) {
        var n = String(name || '');
        if (n === fieldName) {
            return true;
        }
        if (n.slice(-('[' + fieldName + ']').length) === '[' + fieldName + ']') {
            return true;
        }
        if (n.indexOf('[' + fieldName + ']') !== -1) {
            return true;
        }
        return false;
    }

    function findField($form, fieldName) {
        var result = $();
        $form.find('input[type="text"], textarea').each(function () {
            var $field = $(this);
            var name = String($field.attr('name') || '');
            if (fieldNameMatches(name, fieldName)) {
                result = $field;
                return false;
            }
            return true;
        });
        return result;
    }

    function findMarkersTextarea() {
        var $field = $('textarea').filter(function () {
            var $el = $(this);
            var name = String($el.attr('name') || '');
            var id = String($el.attr('id') || '');

            if (fieldNameMatches(name, 'markers_json') || id.indexOf('markers_json') !== -1) {
                return true;
            }

            var labelText = '';
            if (id !== '') {
                labelText = String($('label[for="' + id + '"]').first().text() || '').toLowerCase();
            }

            if (labelText.indexOf('marker-daten') !== -1 || labelText.indexOf('marker daten') !== -1) {
                return true;
            }

            return false;
        }).first();

        return $field;
    }

    function findImageInputInForm($form) {
        var $byName = findField($form, 'image');
        if ($byName.length) {
            return $byName;
        }

        var $media = $form.find('input[type="text"][id^="REX_MEDIA_"]').first();
        if ($media.length) {
            return $media;
        }

        return $();
    }

    function renderEditor($root, state) {
        var imageUrl = mediaToUrl(state.image);
        var selectedIndex = state.selectedIndex;

        if (selectedIndex < 0) {
            selectedIndex = 0;
        }
        if (selectedIndex >= state.markers.length) {
            selectedIndex = state.markers.length > 0 ? state.markers.length - 1 : 0;
        }
        state.selectedIndex = selectedIndex;

        var html = '';
        html += '<div class="kb-intimg-editor__head">';
        html += '<h3>Interaktiver Marker-Editor</h3>';
        html += '<p>Marker hinzufügen, im Bild per Klick/Drag positionieren und Inhalt pflegen.</p>';
        html += '</div>';

        if (imageUrl === '') {
            html += '<div class="kb-intimg-editor__empty">Bitte zuerst im Feld "Bild" eine Datei wählen.</div>';
            $root.html(html);
            return;
        }

        html += '<div class="kb-intimg-editor__layout">';
        html += '<div class="kb-intimg-editor__stage" data-role="stage">';
        html += '<img src="' + escapeHtml(imageUrl) + '" alt="">';

        state.markers.forEach(function (marker, index) {
            var isActive = index === state.selectedIndex;
            html += '<button type="button" class="kb-intimg-editor__dot' + (isActive ? ' is-active' : '') + '"';
            html += ' data-index="' + index + '"';
            html += ' style="left:' + marker.x.toFixed(3) + '%;top:' + marker.y.toFixed(3) + '%;">';
            html += String(index + 1);
            html += '</button>';
        });

        html += '</div>';
        html += '<div class="kb-intimg-editor__panel">';
        html += '<div class="kb-intimg-editor__actions">';
        html += '<button type="button" class="btn btn-primary btn-sm" data-action="add">Marker hinzufügen</button>';
        html += '<button type="button" class="btn btn-default btn-sm" data-action="remove"' + (state.markers.length === 0 ? ' disabled' : '') + '>Aktiven Marker löschen</button>';
        html += '</div>';

        if (state.markers.length === 0) {
            html += '<p class="kb-intimg-editor__empty-inline">Noch keine Marker vorhanden.</p>';
        } else {
            html += '<div class="kb-intimg-editor__list">';
            state.markers.forEach(function (marker, index) {
                var active = index === state.selectedIndex;
                html += '<button type="button" class="kb-intimg-editor__pick' + (active ? ' is-active' : '') + '" data-index="' + index + '">';
                html += '<span class="kb-intimg-editor__pick-no">' + (index + 1) + '</span>';
                html += '<span>' + escapeHtml(marker.title || ('Marker ' + String(index + 1))) + '</span>';
                html += '</button>';
            });
            html += '</div>';

            var activeMarker = state.markers[state.selectedIndex] || state.markers[0];
            if (activeMarker) {
                html += '<div class="kb-intimg-editor__fields">';
                html += '<label>Titel</label>';
                html += '<input type="text" class="form-control" data-role="marker-title" value="' + escapeHtml(activeMarker.title) + '">';
                html += '<label>Modal-Inhalt (HTML erlaubt)</label>';
                html += '<textarea class="form-control" rows="6" data-role="marker-content">' + escapeHtml(activeMarker.content) + '</textarea>';
                html += '</div>';
            }
        }

        html += '</div>';
        html += '</div>';

        $root.html(html);
    }

    function writeMarkers($markersField, markers) {
        var $form = $markersField.closest('form');
        if ($form.length) {
            $form.data('kb-intimg-silent-write', 1);
        }
        $markersField.val(JSON.stringify(markers)).trigger('input').trigger('change');
        if ($form.length) {
            $form.data('kb-intimg-silent-write', 0);
        }
    }

    function setPositionFromPointer($root, state, pageX, pageY) {
        if (state.markers.length === 0) {
            return;
        }

        var marker = state.markers[state.selectedIndex];
        if (!marker) {
            return;
        }

        var $stage = $root.find('[data-role="stage"]').first();
        if (!$stage.length) {
            return;
        }

        var offset = $stage.offset();
        var width = $stage.outerWidth();
        var height = $stage.outerHeight();
        if (!offset || !width || !height) {
            return;
        }

        marker.x = sanitizePercent(((pageX - offset.left) / width) * 100);
        marker.y = sanitizePercent(((pageY - offset.top) / height) * 100);
    }

    function mount($form, $imageField, $markersField) {
        var $editorHost = $('<div class="kb-intimg-editor"></div>');
        $markersField.closest('.form-group').after($editorHost);

        var state = {
            image: String($imageField.val() || ''),
            markers: parseMarkers($markersField.val()),
            selectedIndex: 0,
            dragging: false
        };

        function syncAndRender() {
            writeMarkers($markersField, state.markers);
            renderEditor($editorHost, state);
        }

        renderEditor($editorHost, state);

        $imageField.on('input change', function () {
            state.image = String($(this).val() || '');
            renderEditor($editorHost, state);
        });

        $markersField.on('input change', function () {
            if (state.dragging) {
                return;
            }
            if ($form.data('kb-intimg-silent-write') === 1) {
                return;
            }
            state.markers = parseMarkers($(this).val());
            renderEditor($editorHost, state);
        });

        $editorHost.on('click', '[data-action="add"]', function () {
            state.markers.push({ title: '', content: '', x: 50, y: 50 });
            state.selectedIndex = state.markers.length - 1;
            syncAndRender();
        });

        $editorHost.on('click', '[data-action="remove"]', function () {
            if (state.markers.length === 0) {
                return;
            }
            state.markers.splice(state.selectedIndex, 1);
            if (state.selectedIndex >= state.markers.length) {
                state.selectedIndex = Math.max(0, state.markers.length - 1);
            }
            syncAndRender();
        });

        $editorHost.on('click', '.kb-intimg-editor__pick, .kb-intimg-editor__dot', function (event) {
            event.preventDefault();
            var index = parseInt(String($(this).attr('data-index') || '0'), 10);
            if (!Number.isNaN(index) && index >= 0 && index < state.markers.length) {
                state.selectedIndex = index;
                renderEditor($editorHost, state);
            }
        });

        $editorHost.on('input', '[data-role="marker-title"]', function () {
            if (!state.markers[state.selectedIndex]) {
                return;
            }
            state.markers[state.selectedIndex].title = String($(this).val() || '');
            writeMarkers($markersField, state.markers);

            var currentTitle = state.markers[state.selectedIndex].title;
            var label = currentTitle !== '' ? currentTitle : ('Marker ' + String(state.selectedIndex + 1));
            $editorHost.find('.kb-intimg-editor__pick[data-index="' + String(state.selectedIndex) + '"] span').last().text(label);
        });

        $editorHost.on('input', '[data-role="marker-content"]', function () {
            if (!state.markers[state.selectedIndex]) {
                return;
            }
            state.markers[state.selectedIndex].content = String($(this).val() || '');
            writeMarkers($markersField, state.markers);
        });

        $editorHost.on('click', '[data-role="stage"]', function (event) {
            if ($(event.target).is('button')) {
                return;
            }
            setPositionFromPointer($editorHost, state, event.pageX, event.pageY);
            syncAndRender();
        });

        $editorHost.on('mousedown', '.kb-intimg-editor__dot', function (event) {
            event.preventDefault();
            var index = parseInt(String($(this).attr('data-index') || '0'), 10);
            if (!Number.isNaN(index) && index >= 0 && index < state.markers.length) {
                state.selectedIndex = index;
                state.dragging = true;
                setPositionFromPointer($editorHost, state, event.pageX, event.pageY);
                syncAndRender();
            }
        });

        $(document).on('mousemove.kbIntImgEditor', function (event) {
            if (!state.dragging) {
                return;
            }
            setPositionFromPointer($editorHost, state, event.pageX, event.pageY);
            syncAndRender();
        });

        $(document).on('mouseup.kbIntImgEditor', function () {
            state.dragging = false;
        });
    }

    function init() {
        var $markersField = findMarkersTextarea();
        if (!$markersField.length) {
            return;
        }

        var $form = $markersField.closest('form');
        if (!$form.length) {
            return;
        }

        var $imageField = findImageInputInForm($form);

        if (!$imageField.length || !$markersField.length) {
            return;
        }

        if ($form.data('kb-intimg-mounted') === 1) {
            return;
        }
        $form.data('kb-intimg-mounted', 1);

        // Raw JSON stays in the form for persistence, but is hidden from normal editing.
        $markersField.closest('.form-group').addClass('kb-intimg-editor__raw-json kb-intimg-editor__raw-json--hidden');

        mount($form, $imageField, $markersField);
    }

    $(document).on('rex:ready', init);
    $(init);
})(window.jQuery);
