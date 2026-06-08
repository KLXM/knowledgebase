<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;

return [
    'label' => 'KB Neueste Beiträge',
    'icon' => 'fa fa-clock-o',
    'description' => 'Listet die neuesten Beiträge der aktuellen Wissensbasis und verlinkt direkt auf die Artikel.',
    'version' => '1.0.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['heading', 'intro', 'limit', 'sort_field', 'sort_order', 'show_date', 'empty_text'],
        ],
        'section_settings_tab' => [
            'label' => 'Sektion',
            'icon' => 'fa-columns',
            'fields' => array_merge(
                $elementConfig::getWrapperControlFieldNames(),
                $elementConfig::getSectionFieldNames(),
            ),
        ],
    ],
    'fields' => array_merge([
        'heading' => [
            'type' => 'text',
            'label' => 'Überschrift',
            'default' => 'Neueste Beiträge',
        ],
        'intro' => [
            'type' => 'textarea',
            'label' => 'Einleitung (optional)',
        ],
        'limit' => [
            'type' => 'choice',
            'label' => 'Anzahl Beiträge',
            'choices' => [
                '3' => '3',
                '5' => '5',
                '8' => '8',
                '10' => '10',
                '15' => '15',
            ],
            'default' => '5',
        ],
        'sort_field' => [
            'type' => 'choice',
            'label' => 'Sortierfeld',
            'choices' => [
                'updatedate' => 'Zuletzt aktualisiert',
                'createdate' => 'Erstellt am',
            ],
            'default' => 'updatedate',
        ],
        'sort_order' => [
            'type' => 'choice',
            'label' => 'Sortierung',
            'choices' => [
                'DESC' => 'Neueste zuerst',
                'ASC' => 'Älteste zuerst',
            ],
            'default' => 'DESC',
        ],
        'show_date' => [
            'type' => 'checkbox',
            'label' => 'Datum anzeigen',
            'default' => true,
        ],
        'empty_text' => [
            'type' => 'text',
            'label' => 'Hinweis bei keinen Treffern',
            'default' => 'Noch keine Beiträge vorhanden.',
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
