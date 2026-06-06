<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;
$baseConfigPath = rex_path::addon('yform_content_builder', 'elements/cards/config.php');
$baseConfig = include $baseConfigPath;

if (!is_array($baseConfig)) {
    return [];
}

$dropFieldKeys = [
    'columns',
    'columns_tablet',
    'columns_mobile',
    'gap',
    'section_bg',
    'section_bg_image',
    'section_padding',
    'container_width',
];

$fields = [];
foreach (($baseConfig['fields'] ?? []) as $fieldKey => $definition) {
    if (!in_array((string) $fieldKey, $dropFieldKeys, true)) {
        $fields[$fieldKey] = $definition;
    }
}

$baseConfig['label'] = 'KB Cards';
$baseConfig['description'] = 'Karten-Grid mit flexiblen Layouts und Media-Optionen';
$baseConfig['category'] = 'Knowledgebase';
$baseConfig['icon'] = 'fa fa-th-large';

$baseConfig['settings_modal'] = [
    'label' => 'Allgemeine Block-Einstellungen',
    'icon' => 'fa-cog',
    'fields' => array_merge(
        $elementConfig::getOptionalSectionFieldNames(),
        $elementConfig::getGridFieldNames(),
        ['match_height', 'card_style', 'card_size', 'card_shadow', 'animations_enabled', 'animations_scrollspy', 'animations_delay', 'animations_repeat', 'animations_cascading'],
    ),
];

$baseConfig['fields'] = array_merge(
    $elementConfig::getOptionalSectionFields(),
    $elementConfig::getGridFields(),
    $fields,
);

return $baseConfig;
