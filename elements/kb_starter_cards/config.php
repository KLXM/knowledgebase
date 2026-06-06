<?php
/**
 * Starter Cards - einfache Karten
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;

return [
    'label' => 'KB Cards',
    'icon' => 'fa fa-th-large',
    'description' => 'Vereinfachte Karten mit Bild, Titel, Text und Link.',
    'version' => '1.13.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-th-large',
            'fields' => ['headline', 'items'],
        ],
        'layout_tab' => [
            'label' => 'Layout',
            'icon' => 'fa-columns',
            'fields' => $elementConfig::getGridFieldNames(),
        ],
        'section_settings_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-columns',
            'fields' => array_merge(
                $elementConfig::getWrapperControlFieldNames(),
                $elementConfig::getSectionFieldNames()
            ),
        ],
    ],
    'fields' => array_merge([
        'headline' => [
            'type' => 'text',
            'label' => 'Ueberschrift',
        ],
        'items' => [
            'type' => 'repeater',
            'label' => 'Karten',
            'min' => 1,
            'add_label' => 'Karte hinzufuegen',
            'fields' => [
                'image' => [
                    'type' => 'be_media',
                    'label' => 'Bild',
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Titel',
                ],
                'text' => [
                    'type' => 'tinymce',
                    'label' => 'Text',
                    'profile' => 'default',
                ],
                'link_url' => [
                    'type' => 'text',
                    'label' => 'Link URL',
                ],
                'link_text' => [
                    'type' => 'text',
                    'label' => 'Link Text',
                    'default' => 'Mehr erfahren',
                ],
            ],
        ],
    ], $elementConfig::getGridFields(), $elementConfig::getOptionalSectionFields()),
];
