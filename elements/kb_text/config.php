<?php

declare(strict_types=1);

use FriendsOfREDAXO\Builder\Helper;

$_ci = Helper::elementTranslator('kb_text');

return [
    'label' => $_ci('label', 'KB Text'),
    'icon' => 'fa fa-align-left',
    'description' => $_ci('description', 'Fließtext eines Beitrags (TinyMCE) – ohne eigene Section/Container, für Abschnitte unter einer Kapitelmarke.'),
    'category' => '00 :: Knowledgebase',
    'version' => '1.0.0',
    'field_groups' => [
        'content' => [
            'label' => $_ci('group_content', 'Inhalt'),
            'icon' => 'fa-file-text-o',
            'fields' => ['text'],
        ],
    ],
    'fields' => [
        'text' => [
            'type' => 'tinymce',
            'profile' => 'default',
            'label' => $_ci('field_text', 'Text'),
        ],
    ],
];
