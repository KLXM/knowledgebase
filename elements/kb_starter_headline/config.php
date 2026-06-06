<?php
return [
    'name' => 'KB Starter Headline',
    'description' => 'Feste Ueberschrift mit Zusatzzeilen fuer Knowledgebase-Inhalte.',
    'group' => 'Knowledgebase',
    'content' => [
        'field_groups' => [
            'content_tab' => [
                'label' => 'Inhalt',
                'icon' => 'fa-header',
                'fields' => ['eyebrow', 'headline', 'highlight', 'subline'],
            ],
            'section_tab' => [
                'label' => 'Sektion',
                'icon' => 'fa-square-o',
                'fields' => ['enable_section', 'enable_container', 'container_width', 'section_bg', 'section_light', 'section_padding'],
            ],
        ],
        'fields' => [
            'eyebrow' => [
                'type' => 'text',
                'label' => 'Eyebrow',
            ],
            'headline' => [
                'type' => 'text',
                'label' => 'Ueberschrift',
            ],
            'highlight' => [
                'type' => 'text',
                'label' => 'Hervorhebung',
            ],
            'subline' => [
                'type' => 'textarea',
                'label' => 'Unterzeile',
                'rows' => 2,
            ],
            'enable_section' => [
                'type' => 'bool',
                'label' => 'Als Sektion ausgeben',
                'default' => 1,
            ],
            'enable_container' => [
                'type' => 'bool',
                'label' => 'Mit Container ausgeben',
                'default' => 1,
            ],
            'container_width' => [
                'type' => 'choice',
                'label' => 'Container-Breite',
                'choices' => [
                    'uk-container' => 'Standard',
                    'uk-container-small' => 'Small',
                    'uk-container-large' => 'Large',
                    'uk-container-expand' => 'Expand',
                ],
                'default' => 'uk-container',
            ],
            'section_bg' => [
                'type' => 'choice',
                'label' => 'Sektion Hintergrund',
                'choices' => [
                    '' => 'Ohne',
                    'muted' => 'Muted',
                    'primary' => 'Primary',
                    'secondary' => 'Secondary',
                    'accent' => 'Accent',
                    'danger' => 'Danger',
                    'success' => 'Success',
                ],
                'default' => '',
            ],
            'section_light' => [
                'type' => 'bool',
                'label' => 'Helle Sektion',
            ],
            'section_padding' => [
                'type' => 'choice',
                'label' => 'Sektion Padding',
                'choices' => [
                    '' => 'Standard',
                    'small' => 'Small',
                    'large' => 'Large',
                ],
                'default' => '',
            ],
        ],
    ],
];
