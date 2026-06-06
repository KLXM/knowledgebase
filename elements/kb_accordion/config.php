<?php
/**
 * KB Accordion - UIkit Accordion/Tabs
 */

use KLXM\YFormContentBuilder\Helper;
use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;
$_ci = Helper::elementTranslator('kb_accordion');
$hasThemeBuilder = $elementConfig::hasThemeBuilder();

$styleChoices = [
    'default' => 'Standard',
    'primary' => 'Primary',
    'secondary' => 'Secondary',
    'muted' => 'Muted',
];

if ($hasThemeBuilder && class_exists('UikitThemeBuilder\\DomainContext')) {
    $themeCardStyles = \UikitThemeBuilder\DomainContext::getCardStyleOptions();
    if (is_array($themeCardStyles) && $themeCardStyles !== []) {
        $styleChoices = ['default' => 'Standard'];
        foreach ($themeCardStyles as $class => $data) {
            $label = is_array($data) && isset($data['label']) ? (string) $data['label'] : ucfirst(str_replace(['uk-card-', 'uk-background-'], '', (string) $class));
            $styleChoices[(string) $class] = $label;
        }
    }
}

return [
    'label' => $_ci('label', 'KB Accordion'),
    'icon' => 'fa fa-list',
    'description' => $_ci('description', 'Accordion oder Tabs mit UIkit und repeater-basierten Inhalten.'),
    'version' => '1.0.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => $_ci('group_content_label', 'Inhalt'),
            'icon' => 'fa-file-text-o',
            'fields' => ['items'],
        ],
        'layout_tab' => [
            'label' => $_ci('group_layout_label', 'Layout'),
            'icon' => 'fa-columns',
            'fields' => [
                'display_type',
                'style',
                'first_open',
                'accordion_collapsible',
                'accordion_multiple',
                'accordion_animation',
                'tab_style',
                'tab_alignment',
            ],
        ],
        'section_tab' => [
            'label' => $_ci('group_section_label', 'Sektion'),
            'icon' => 'fa-toggle-on',
            'fields' => array_merge(
                $elementConfig::getWrapperControlFieldNames(),
                $elementConfig::getSectionFieldNames()
            ),
        ],
    ],
    'fields' => array_merge([
        'display_type' => [
            'type' => 'choice',
            'label' => $_ci('field_display_type_label', 'Darstellung'),
            'choices' => [
                'accordion' => 'Accordion',
                'tabs' => 'Tabs (horizontal)',
                'tabs-left' => 'Tabs (links vertikal)',
            ],
            'default' => 'accordion',
        ],
        'style' => [
            'type' => 'choice',
            'label' => $_ci('field_style_label', 'Card-Stil'),
            'choices' => $styleChoices,
            'default' => 'default',
            'selectpicker' => true,
        ],
        'accordion_collapsible' => [
            'type' => 'checkbox',
            'label' => $_ci('field_accordion_collapsible_label', 'Accordion: Alle schliessbar'),
            'visible_if' => ['display_type' => 'accordion'],
        ],
        'accordion_multiple' => [
            'type' => 'checkbox',
            'label' => $_ci('field_accordion_multiple_label', 'Accordion: Mehrere offen'),
            'visible_if' => ['display_type' => 'accordion'],
        ],
        'accordion_animation' => [
            'type' => 'choice',
            'label' => $_ci('field_accordion_animation_label', 'Accordion Animation'),
            'choices' => [
                'true' => 'Aktiv',
                'false' => 'Deaktiviert',
            ],
            'default' => 'true',
            'visible_if' => ['display_type' => 'accordion'],
        ],
        'first_open' => [
            'type' => 'checkbox',
            'label' => $_ci('field_first_open_label', 'Erstes Element geoeffnet'),
            'default' => true,
            'visible_if' => ['display_type' => ['accordion', 'tabs', 'tabs-left']],
        ],
        'tab_style' => [
            'type' => 'choice',
            'label' => $_ci('field_tab_style_label', 'Tab-Stil'),
            'choices' => [
                'default' => 'Standard',
                'pill' => 'Pills',
                'divider' => 'Divider',
            ],
            'default' => 'default',
            'visible_if' => ['display_type' => 'tabs'],
        ],
        'tab_alignment' => [
            'type' => 'choice',
            'label' => $_ci('field_tab_alignment_label', 'Tab-Ausrichtung'),
            'choices' => [
                'left' => 'Links',
                'center' => 'Zentriert',
                'right' => 'Rechts',
                'expand' => 'Volle Breite',
            ],
            'default' => 'left',
            'visible_if' => ['display_type' => 'tabs'],
        ],
        'items' => [
            'type' => 'repeater',
            'label' => $_ci('field_items_label', 'Elemente'),
            'min' => 1,
            'add_label' => $_ci('field_items_add_label', 'Element hinzufuegen'),
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'label' => $_ci('field_item_title_label', 'Titel'),
                ],
                'icon' => [
                    'type' => 'text',
                    'label' => $_ci('field_item_icon_label', 'Icon (optional)'),
                    'notice' => $_ci('field_item_icon_notice', 'UIkit Iconname (z.B. home, star) oder Font Awesome Klasse (z.B. fa-star).'),
                ],
                'content' => [
                    'type' => 'tinymce',
                    'profile' => 'default',
                    'label' => $_ci('field_item_content_label', 'Inhalt'),
                ],
                'image' => [
                    'type' => 'be_media',
                    'label' => $_ci('field_item_image_label', 'Bild (optional)'),
                ],
                'disabled' => [
                    'type' => 'checkbox',
                    'label' => $_ci('field_item_disabled_label', 'Deaktiviert'),
                ],
            ],
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
