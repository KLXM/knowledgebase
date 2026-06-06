<?php
/**
 * Starter Text - sehr einfaches Textelement
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;

return [
    'label' => 'KB Text',
    'icon' => 'fa fa-align-left',
    'description' => 'Einfacher Textblock mit TinyMCE (Profil: default).',
    'version' => '1.13.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => 'Inhalt',
            'icon' => 'fa-file-text-o',
            'fields' => ['text'],
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
        'text' => [
            'type' => 'tinymce',
            'profile' => 'default',
            'label' => 'Text',
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
