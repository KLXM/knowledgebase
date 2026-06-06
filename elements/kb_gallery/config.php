<?php
/**
 * Galerie Element - Konfiguration
 * Nutzt zentrale Konfiguration für Section-Einstellungen
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;
$hasThemeBuilder = $elementConfig::hasThemeBuilder();

// Element-spezifische Felder für settings_modal
$elementSpecificFields = ['layout', 'aspect_ratio', 'lightbox'];

return [
    'label' => 'KB Galerie',
    'description' => 'Medien-Galerie mit Grid/Masonry Layout',
    'version' => '1.13.0',
    'icon' => 'fa-th',
    'category' => 'Knowledgebase',
    
    // Settings Modal für Grid/Section-Einstellungen
    'settings_modal' => [
        'label' => 'Grid & Sektion Einstellungen',
        'icon' => 'fa-cog',
        'fields' => array_merge($elementConfig::getGridFieldNames(), $elementSpecificFields, $elementConfig::getOptionalSectionFieldNames())
    ],
    
    'fields' => array_merge(
        // Grid-Felder
        $elementConfig::getGridFields(),
        
        // Element-spezifische Felder
        [
            'layout' => [
                'type' => 'choice',
                'label' => 'Layout',
                'choices' => [
                    'grid' => 'Grid (gleichmäßig)',
                    'masonry' => 'Masonry (Pinterest-Style)',
                    'featured' => 'Featured (Erstes Bild groß)',
                    'logowall' => 'Logo Wall (Optimiert für Logos)'
                ],
                'default' => 'grid'
            ],
            'aspect_ratio' => [
                'type' => 'choice',
                'label' => 'Seitenverhältnis',
                'choices' => [
                    'auto' => 'Original',
                    '16:9' => '16:9 (Widescreen)',
                    '4:3' => '4:3 (Standard)',
                    '1:1' => '1:1 (Quadrat)',
                    '3:2' => '3:2 (Klassisch)'
                ],
                'default' => 'auto'
            ],
            'lightbox' => [
                'type' => 'checkbox',
                'label' => 'Lightbox aktivieren',
                'default' => true
            ],
            'media_caption_fallback' => [
                'type' => 'checkbox',
                'label' => 'Medienpool Titel als Fallback',
                'notice' => 'Wenn in der Item-Liste keine Bildunterschrift eingegeben wurde, wird der Titel aus dem Medienpool verwendet.'
            ],
            
            // Items Repeater
            'items' => [
                'type' => 'repeater',
                'label' => 'Galerie-Items',
                'add_label' => 'Medium hinzufügen',
                'view' => 'grid',
                'grid_columns' => 4,
                
                // Modal für erweiterte Optionen
                'item_modal' => [
                    'label' => 'Erweiterte Optionen',
                    'icon' => 'fa-cog',
                    'fields' => ['caption', 'alt_text', 'link_url']
                ],
                
                'fields' => [
                    'media' => [
                        'type' => 'be_media',
                        'label' => 'Bild/Video',
                        'allowed_types' => 'image,video',
                        'preview' => true,
                        'notice' => 'Bild oder Video auswählen'
                    ],
                    'caption' => [
                        'type' => 'text',
                        'label' => 'Bildunterschrift',
                        'notice' => 'Optional: Beschreibung für das Medium'
                    ],
                    'alt_text' => [
                        'type' => 'text',
                        'label' => 'Alt-Text',
                        'notice' => 'Für Barrierefreiheit und SEO'
                    ],
                    'link_url' => [
                        'type' => 'text',
                        'label' => 'Link (optional)',
                        'notice' => 'Überschreibt die Lightbox für dieses Element'
                    ]
                ]
            ]
        ],
        
        // Section-Felder
        $elementConfig::getOptionalSectionFields()
    )
];
