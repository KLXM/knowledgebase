<?php

declare(strict_types=1);

use KLXM\YFormContentBuilder\Helper;

$_ci = Helper::elementTranslator('kb_chapter_nav');

return [
    'label' => $_ci('label', 'KB Kapitel / Überschrift'),
    'icon' => 'fa fa-bookmark',
    'description' => $_ci('description', 'Definiert eine Kapitel-Überschrift inkl. Anker für TOC und Navigation.'),
    'category' => 'Knowledgebase',
    'version' => '1.0.0',
    'field_groups' => [
        'content' => [
            'label' => $_ci('group_content', 'Inhalt'),
            'icon' => 'fa-book',
            'fields' => ['title', 'heading_level', 'text', 'badge', 'anchor_id'],
        ],
    ],
    'fields' => [
        'title' => [
            'type' => 'text',
            'label' => $_ci('field_title', 'Titel'),
            'required' => true,
        ],
        'heading_level' => [
            'type' => 'choice',
            'label' => $_ci('field_heading_level', 'Ebene'),
            'choices' => [
                'h2' => 'H2',
                'h3' => 'H3',
                'h4' => 'H4',
            ],
            'default' => 'h2',
            'notice' => $_ci('field_heading_level_notice', 'Nur H2 wird in der Navigation gelistet. H3/H4 bleiben im Inhalt.'),
        ],
        'text' => [
            'type' => 'textarea',
            'label' => $_ci('field_text', 'Kurzbeschreibung'),
        ],
        'badge' => [
            'type' => 'text',
            'label' => $_ci('field_badge', 'Badge / Kapitelnummer'),
            'notice' => $_ci('field_badge_notice', 'Optional: Zahl 1-99 oder UIKit3-Iconname (z.B. file-text, bookmark, info, question, tag, star).'),
        ],
        'anchor_id' => [
            'type' => 'text',
            'label' => $_ci('field_anchor_id', 'Anchor-ID (optional)'),
            'notice' => $_ci('field_anchor_notice', 'Leer lassen = wird automatisch aus dem Titel erzeugt.'),
        ],
    ],
];