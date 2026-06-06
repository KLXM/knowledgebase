<?php

declare(strict_types=1);

use KLXM\YFormContentBuilder\Helper;

$_ci = Helper::elementTranslator('kb_interactive_image');

return [
    'label' => $_ci('label', 'Interaktives Bild auswählen'),
    'icon' => 'fa fa-crosshairs',
    'description' => $_ci('description', 'Wählt ein interaktives Bild-Asset aus dem Knowledgebase-Backend aus.'),
    'category' => 'media',
    'version' => '1.0.0',
    'fields' => [
        'interactive_image_id' => [
            'type' => 'be_table_select',
            'label' => $_ci('field_asset', 'Interaktives Bild'),
            'table' => rex::getTable('knowledgebase_interactive_image'),
            'field' => 'title',
            'notice' => $_ci('field_asset_notice', 'Asset in Knowledgebase > Interaktive Bilder anlegen und hier auswählen.'),
        ],
    ],
];
