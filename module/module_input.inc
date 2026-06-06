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