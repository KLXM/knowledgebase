<?php
/**
 * KB Headline - erweiterte Ueberschrift
 */

use KLXM\YFormContentBuilder\Helper;
use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;
$_ci = Helper::elementTranslator('kb_headline');
$hasThemeBuilder = $elementConfig::hasThemeBuilder();

$colorOptions = [
    '' => 'Standard',
    'primary' => 'Primary',
    'secondary' => 'Secondary',
    'success' => 'Success',
    'warning' => 'Warning',
    'danger' => 'Danger',
    'muted' => 'Muted',
];

if ($hasThemeBuilder && class_exists('UikitThemeBuilder\\DomainContext')) {
    $themeColors = \UikitThemeBuilder\DomainContext::getTextColorOptions();
    if (is_array($themeColors)) {
        foreach ($themeColors as $key => $value) {
            if (is_string($value)) {
                $colorOptions[$key] = $value;
            }
        }
    }
}

return [
    'label' => $_ci('label', 'KB Headline'),
    'icon' => 'fa fa-header',
    'description' => $_ci('description', 'Ueberschrift mit UIkit Stil-, Link- und Layoutoptionen.'),
    'version' => '1.0.0',
    'category' => 'Knowledgebase',
    'field_groups' => [
        'content_tab' => [
            'label' => $_ci('group_content_label', 'Inhalt'),
            'icon' => 'fa-text-width',
            'fields' => ['text', 'size', 'modifier'],
        ],
        'layout_tab' => [
            'label' => $_ci('group_layout_label', 'Layout'),
            'icon' => 'fa-columns',
            'fields' => ['alignment', 'color', 'underline', 'spacing_top', 'spacing_bottom'],
        ],
        'link_tab' => [
            'label' => $_ci('group_link_label', 'Link'),
            'icon' => 'fa-link',
            'fields' => ['link_type', 'link_url', 'link_internal'],
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
        'text' => [
            'type' => 'text',
            'label' => $_ci('field_text_label', 'Ueberschrift'),
            'required' => true,
        ],
        'size' => [
            'type' => 'choice',
            'label' => $_ci('field_size_label', 'Groesse'),
            'choices' => [
                '' => 'Standard (entsprechend Tag)',
                'small' => 'Klein (uk-heading-small)',
                'medium' => 'Mittel (uk-heading-medium)',
                'large' => 'Gross (uk-heading-large)',
                'xlarge' => 'X-Gross (uk-heading-xlarge)',
                '2xlarge' => '2X-Gross (uk-heading-2xlarge)',
            ],
            'default' => '',
        ],
        'modifier' => [
            'type' => 'choice',
            'label' => $_ci('field_modifier_label', 'UIkit Modifier'),
            'choices' => [
                '' => 'Keine',
                'divider' => 'Mit Trennlinie (uk-heading-divider)',
                'bullet' => 'Mit Bullet (uk-heading-bullet)',
                'line' => 'Mit Mittel-Linie (uk-heading-line)',
            ],
            'default' => '',
        ],
        'alignment' => [
            'type' => 'choice',
            'label' => $_ci('field_alignment_label', 'Ausrichtung'),
            'choices' => [
                'left' => 'Links',
                'center' => 'Zentriert',
                'right' => 'Rechts',
            ],
            'default' => 'left',
        ],
        'color' => [
            'type' => 'choice',
            'label' => $_ci('field_color_label', 'Farbe'),
            'choices' => $colorOptions,
            'default' => '',
        ],
        'spacing_top' => [
            'type' => 'choice',
            'label' => $_ci('field_spacing_top_label', 'Abstand oben'),
            'choices' => [
                '' => 'Standard',
                'none' => 'Kein',
                'small' => 'Klein',
                'medium' => 'Mittel',
                'large' => 'Gross',
            ],
            'default' => '',
        ],
        'spacing_bottom' => [
            'type' => 'choice',
            'label' => $_ci('field_spacing_bottom_label', 'Abstand unten'),
            'choices' => [
                '' => 'Standard',
                'none' => 'Kein',
                'small' => 'Klein',
                'medium' => 'Mittel',
                'large' => 'Gross',
            ],
            'default' => '',
        ],
        'underline' => [
            'type' => 'checkbox',
            'label' => $_ci('field_underline_label', 'Unterstreichung'),
        ],
        'link_type' => [
            'type' => 'choice',
            'label' => $_ci('field_link_type_label', 'Link-Art'),
            'choices' => [
                '' => 'Kein Link',
                'external' => 'Externe URL',
                'internal' => 'Interne Seite',
            ],
            'default' => '',
        ],
        'link_url' => [
            'type' => 'text',
            'label' => $_ci('field_link_url_label', 'Externe URL'),
            'visible_if' => ['link_type' => 'external'],
        ],
        'link_internal' => [
            'type' => 'be_link',
            'label' => $_ci('field_link_internal_label', 'Interne Seite'),
            'visible_if' => ['link_type' => 'internal'],
        ],
    ], $elementConfig::getOptionalSectionFields()),
];
