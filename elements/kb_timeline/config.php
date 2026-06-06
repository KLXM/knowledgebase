<?php
/**
 * KB Timeline - vertikale Zeitlinie
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;

return [
    'label' => 'KB Timeline',
    'icon' => 'fa fa-stream',
    'description' => 'Vertikale Zeitlinie fuer Meilensteine, Prozesse oder Ereignisse.',
    'version' => '1.0.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-stream',
            'fields' => ['heading', 'intro', 'items'],
        ],
        'layout_tab' => [
            'label' => 'Layout',
            'icon' => 'fa-sliders',
            'fields' => ['style', 'icon_default', 'icon_color', 'line_color'],
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
        'heading' => [
            'type' => 'text',
            'label' => 'Ueberschrift (optional)',
        ],
        'intro' => [
            'type' => 'textarea',
            'label' => 'Einleitung (optional)',
        ],
        'items' => [
            'type' => 'repeater',
            'label' => 'Timeline-Eintraege',
            'add_label' => 'Eintrag hinzufuegen',
            'view' => 'list',
            'fields' => [
                'date' => [
                    'type' => 'text',
                    'label' => 'Datum / Zeitraum',
                    'notice' => 'z.B. 2024, Maerz 2024, Seit 2022',
                    'col' => 4,
                ],
                'title' => [
                    'type' => 'text',
                    'label' => 'Titel / Meilenstein',
                    'required' => true,
                    'col' => 8,
                ],
                'text' => [
                    'type' => 'textarea',
                    'label' => 'Beschreibung (optional)',
                    'col' => 12,
                ],
                'icon' => [
                    'type' => 'text',
                    'label' => 'UIkit-Icon (optional)',
                    'notice' => 'Name des UIkit-Icons, z.B. star, check, heart, settings',
                    'col' => 6,
                ],
                'badge' => [
                    'type' => 'text',
                    'label' => 'Badge-Text (optional)',
                    'notice' => 'z.B. Neu, Live',
                    'col' => 6,
                ],
                'highlight' => [
                    'type' => 'checkbox',
                    'label' => 'Hervorheben',
                    'notice' => 'Eintrag als besonders wichtig markieren',
                    'col' => 12,
                ],
            ],
        ],
        'style' => [
            'type' => 'choice',
            'label' => 'Stil',
            'choices' => [
                'default' => 'Standard (Punkt + Linie)',
                'card' => 'Karten (uk-card-default)',
                'alternating' => 'Alternierend (links/rechts)',
            ],
            'default' => 'default',
        ],
        'icon_default' => [
            'type' => 'choice',
            'label' => 'Standard-Icon',
            'choices' => [
                'circle' => 'Gefuellter Kreis',
                'check' => 'Haekchen',
                'star' => 'Stern',
                'bolt' => 'Blitz',
                'none' => 'Keins (nur Punkt)',
            ],
            'default' => 'circle',
        ],
        'icon_color' => [
            'type' => 'choice',
            'label' => 'Icon-/Punkt-Farbe',
            'choices' => [
                'primary' => 'Primary',
                'secondary' => 'Secondary',
                'success' => 'Success',
                'warning' => 'Warning',
                'danger' => 'Danger',
                'muted' => 'Muted',
            ],
            'default' => 'primary',
        ],
        'line_color' => [
            'type' => 'choice',
            'label' => 'Linien-Stil',
            'choices' => [
                'solid' => 'Durchgezogen',
                'dashed' => 'Gestrichelt',
                'dotted' => 'Gepunktet',
            ],
            'default' => 'solid',
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
