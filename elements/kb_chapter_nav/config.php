<?php

declare(strict_types=1);

use KLXM\YFormContentBuilder\Helper;

$_ci = Helper::elementTranslator('kb_chapter_nav');

return [
    'label' => $_ci('label', 'KB Kapitel / Überschrift'),
    'icon' => 'fa fa-bookmark',
    'description' => $_ci('description', 'Definiert eine Kapitel-Überschrift inkl. Anker für TOC und Navigation.'),
    'category' => '00 :: Knowledgebase',
    'version' => '1.0.0',
    'field_groups' => [
        'content' => [
            'label' => $_ci('group_content', 'Inhalt'),
            'icon' => 'fa-book',
            'fields' => ['title', 'heading_level', 'text', 'badge_mode', 'badge_icon', 'badge_text', 'anchor_id'],
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
        'badge_mode' => [
            'type' => 'choice',
            'label' => $_ci('field_badge_mode', 'Badge-Typ'),
            'col' => 4,
            'choices' => [
                'auto' => $_ci('field_badge_mode_auto', 'Automatisch (bestehender Wert)'),
                'none' => $_ci('field_badge_mode_none', 'Kein Icon / kein Badge'),
                'text' => $_ci('field_badge_mode_text', 'Freier Text / Kapitelnummer'),
                'icon' => $_ci('field_badge_mode_icon', 'UIKit3 Icon'),
            ],
            'default' => 'auto',
            'notice' => $_ci('field_badge_mode_notice', 'Steuert, ob ein Icon oder freier Text neben dem Kapitel angezeigt wird.'),
            'selectpicker' => true,
        ],
        'badge_icon' => [
            'type' => 'choice',
            'label' => $_ci('field_badge_icon', 'Icon waehlen'),
            'col' => 4,
            'choices' => [
                '' => $_ci('field_badge_icon_none', 'Bitte waehlen'),
                'bookmark' => 'bookmark',
                'eye' => 'eye',
                'video-camera' => 'video-camera',
                'file-text' => 'file-text',
                'list' => 'list',
                'grid' => 'grid',
                'tag' => 'tag',
                'info' => 'info',
                'question' => 'question',
                'star' => 'star',
                'check' => 'check',
                'warning' => 'warning',
                'bolt' => 'bolt',
                'cog' => 'cog',
                'calendar' => 'calendar',
                'clock' => 'clock',
                'world' => 'world',
                'users' => 'users',
                'comment' => 'comment',
                'link' => 'link',
                'link-external' => 'link-external',
            ],
            'choice_icons' => [
                'bookmark' => '<span uk-icon="icon: bookmark"></span>',
                'eye' => '<span uk-icon="icon: eye"></span>',
                'video-camera' => '<span uk-icon="icon: video-camera"></span>',
                'file-text' => '<span uk-icon="icon: file-text"></span>',
                'list' => '<span uk-icon="icon: list"></span>',
                'grid' => '<span uk-icon="icon: grid"></span>',
                'tag' => '<span uk-icon="icon: tag"></span>',
                'info' => '<span uk-icon="icon: info"></span>',
                'question' => '<span uk-icon="icon: question"></span>',
                'star' => '<span uk-icon="icon: star"></span>',
                'check' => '<span uk-icon="icon: check"></span>',
                'warning' => '<span uk-icon="icon: warning"></span>',
                'bolt' => '<span uk-icon="icon: bolt"></span>',
                'cog' => '<span uk-icon="icon: cog"></span>',
                'calendar' => '<span uk-icon="icon: calendar"></span>',
                'clock' => '<span uk-icon="icon: clock"></span>',
                'world' => '<span uk-icon="icon: world"></span>',
                'users' => '<span uk-icon="icon: users"></span>',
                'comment' => '<span uk-icon="icon: comment"></span>',
                'link' => '<span uk-icon="icon: link"></span>',
                'link-external' => '<span uk-icon="icon: link-external"></span>',
            ],
            'selectpicker' => true,
            'visible_if' => ['badge_mode' => 'icon'],
        ],
        'badge_text' => [
            'type' => 'text',
            'label' => $_ci('field_badge_text', 'Freitext / Kapitelnummer'),
            'col' => 4,
            'notice' => $_ci('field_badge_text_notice', 'Beispiele: 1, 2.1, A, Intro'),
            'visible_if' => ['badge_mode' => 'text'],
        ],
        'anchor_id' => [
            'type' => 'text',
            'label' => $_ci('field_anchor_id', 'Anchor-ID (optional)'),
            'notice' => $_ci('field_anchor_notice', 'Leer lassen = wird automatisch aus dem Titel erzeugt.'),
        ],
    ],
];