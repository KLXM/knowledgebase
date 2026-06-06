<?php
/**
 * KB Tabelle - table_editor basiert
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;

return [
    'label' => 'KB Tabelle',
    'icon' => 'fa fa-table',
    'description' => 'Responsive, barrierefreie Tabelle mit table_editor.',
    'version' => '1.0.0',
    'category' => 'data',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-table',
            'fields' => ['table_data'],
        ],
        'layout_tab' => [
            'label' => 'Layout',
            'icon' => 'fa-columns',
            'fields' => ['table_style', 'table_size', 'table_hover', 'table_responsive', 'table_align'],
        ],
        'section_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-toggle-on',
            'fields' => array_merge(
                $elementConfig::getWrapperControlFieldNames(),
                $elementConfig::getSectionFieldNames()
            ),
        ],
    ],
    'fields' => array_merge([
        'table_data' => [
            'type' => 'table_editor',
            'label' => 'Tabelleneditor',
            'notice' => 'Kopfzeile und Kopfspalte koennen direkt gesetzt werden. Caption verbessert die Accessibility.',
            'min_cols' => 1,
            'min_rows' => 1,
            'header_row_policy' => 'user',
            'header_col_policy' => 'user',
            'enable_textarea' => true,
            'enable_media' => false,
            'enable_link' => false,
        ],
        'table_style' => [
            'type' => 'choice',
            'label' => 'Stil',
            'choices' => [
                'default' => 'Standard',
                'uk-table-divider' => 'Mit Trennlinien',
                'uk-table-striped' => 'Zebra-Streifen',
                'uk-table-striped uk-table-divider' => 'Zebra + Linien',
            ],
            'default' => 'default',
        ],
        'table_size' => [
            'type' => 'choice',
            'label' => 'Groesse',
            'choices' => [
                'default' => 'Standard',
                'uk-table-small' => 'Klein',
                'uk-table-large' => 'Gross',
            ],
            'default' => 'default',
        ],
        'table_hover' => [
            'type' => 'checkbox',
            'label' => 'Hover-Effekt',
        ],
        'table_responsive' => [
            'type' => 'choice',
            'label' => 'Responsiv',
            'choices' => [
                '' => 'Horizontal scrollen',
                'uk-table-responsive' => 'Spalten-Stacking auf Mobile',
            ],
            'default' => '',
        ],
        'table_align' => [
            'type' => 'choice',
            'label' => 'Vertikale Ausrichtung',
            'choices' => [
                '' => 'Standard',
                'uk-table-middle' => 'Mittig',
            ],
            'default' => '',
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
