<?php

$knowledgebases = [];
if (rex_addon::get('yform')->isAvailable() && class_exists('rex_data_knowledgebase')) {
    $collection = rex_yform_manager_dataset::query(rex::getTable('knowledgebase'))
        ->orderBy('title')
        ->find();

    foreach ($collection as $dataset) {
        if ($dataset instanceof rex_yform_manager_dataset) {
            $knowledgebases[(int) $dataset->getId()] = (string) $dataset->getValue('title');
        }
    }
}

$selectedKnowledgebase = 'REX_VALUE[1]';
$startSlug = 'REX_VALUE[2]';
$stickyHeaderOffset = (int) 'REX_VALUE[3]';
$stickyNavOffset = (int) 'REX_VALUE[4]';
?>
<div class="form-group">
    <label for="rex-value-1">Wissensbasis</label>
    <select class="form-control" id="rex-value-1" name="REX_INPUT_VALUE[1]">
        <option value="">Bitte wählen</option>
        <?php foreach ($knowledgebases as $id => $title): ?>
            <option value="<?= $id ?>"<?= (string) $id === (string) $selectedKnowledgebase ? ' selected' : '' ?>><?= rex_escape($title) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="form-group">
    <label for="rex-value-2">Optionaler Start-Slug</label>
    <input class="form-control" id="rex-value-2" type="text" name="REX_INPUT_VALUE[2]" value="<?= rex_escape($startSlug) ?>">
    <p class="help-block">Leer lassen, damit automatisch das erste Online-Kapitel geöffnet wird.</p>
</div>
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label for="rex-value-3">Sticky-Offset Header (px)</label>
            <input class="form-control" id="rex-value-3" type="number" min="0" step="1" name="REX_INPUT_VALUE[3]" value="<?= rex_escape((string) $stickyHeaderOffset) ?>">
            <p class="help-block">Höhe des fixierten Headers.</p>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="form-group">
            <label for="rex-value-4">Sticky-Offset Navigation (px)</label>
            <input class="form-control" id="rex-value-4" type="number" min="0" step="1" name="REX_INPUT_VALUE[4]" value="<?= rex_escape((string) $stickyNavOffset) ?>">
            <p class="help-block">Zusätzlicher Abstand über der Navigation.</p>
        </div>
    </div>
</div>