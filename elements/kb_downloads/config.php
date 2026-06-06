<?php
/**
 * Downloads Element - Konfiguration
 * Download-Liste/Kacheln für Dateien aus dem Medienpool
 * Nutzt zentrale Konfiguration für Section-Einstellungen
 */

use FriendsOfREDAXO\Knowledgebase\KbElementsConfig;

$elementConfig = KbElementsConfig::class;
$hasThemeBuilder = $elementConfig::hasThemeBuilder();

// Element-spezifische Felder für settings_modal
$elementSpecificFields = ['layout', 'match_height', 'card_style', 'show_filesize', 'show_filetype', 'show_icon', 'icon_style', 'show_preview', 'open_in_new_tab'];

// Datei-Icon-Mapping
$iconStyles = [
    'auto' => 'Automatisch (nach Dateityp)',
    'fa' => 'Font Awesome Icons',
    'uikit' => 'UIkit Icons',
    'custom' => 'Eigenes Icon pro Datei'
];

// Card-Style Optionen (für Kachelansicht)
$cardStyleChoices = [
    '' => 'Standard (Default)',
    'uk-card-default' => 'Default (mit Rahmen)',
    'uk-card-primary' => 'Primary',
    'uk-card-secondary' => 'Secondary',
    'uk-card-muted' => 'Muted (Grau)'
];

if ($hasThemeBuilder) {
    $themeCardStyles = \UikitThemeBuilder\DomainContext::getCardStyleOptions();
    if (!empty($themeCardStyles)) {
        $cardStyleChoices = ['' => 'Standard (Default)'];
        foreach ($themeCardStyles as $class => $data) {
            $cardStyleChoices[$class] = $data['label'] ?? ucfirst(str_replace(['uk-card-', 'uk-background-'], '', $class));
        }
    }
}

return [
    'label' => 'KB Downloads',
    'description' => 'Download-Liste oder Kacheln für Dateien',
    'version' => '1.13.0',
    'icon' => 'fa-download',
    'category' => 'Knowledgebase',
    
    // Settings Modal für Grid/Section-Einstellungen
    'settings_modal' => [
        'label' => 'Layout & Sektion Einstellungen',
        'icon' => 'fa-cog',
        'fields' => array_merge($elementConfig::getGridFieldNames(), $elementSpecificFields, $elementConfig::getOptionalSectionFieldNames())
    ],
    
    'fields' => array_merge(
        // Grid-Felder
        $elementConfig::getGridFields(),
        
        // Element-spezifische Felder
        [
            'headline' => [
                'type' => 'text',
                'label' => 'Überschrift',
                'notice' => 'Optional: Überschrift für den Download-Bereich'
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Beschreibung',
                'notice' => 'Optional: Einleitungstext'
            ],
            'layout' => [
                'type' => 'choice',
                'label' => 'Darstellung',
                'choices' => [
                    'list' => 'Liste (untereinander)',
                    'cards' => 'Kacheln (Grid)',
                    'compact' => 'Kompakte Liste',
                    'table' => 'Tabelle'
                ],
                'default' => 'list'
            ],
            'match_height' => [
                'type' => 'checkbox',
                'label' => 'Gleiche Höhe für alle Kacheln'
            ],
            'card_style' => [
                'type' => 'choice',
                'label' => 'Kachel-Farbe (nur bei Kacheln)',
                'choices' => $cardStyleChoices,
                'selectpicker' => true,
                'default' => ''
            ],
            'show_filesize' => [
                'type' => 'checkbox',
                'label' => 'Dateigröße anzeigen',
                'default' => true
            ],
            'show_filetype' => [
                'type' => 'checkbox',
                'label' => 'Dateityp anzeigen',
                'default' => true
            ],
            'show_icon' => [
                'type' => 'checkbox',
                'label' => 'Datei-Icon anzeigen',
                'default' => true
            ],
            'icon_style' => [
                'type' => 'choice',
                'label' => 'Icon-Stil',
                'choices' => $iconStyles,
                'default' => 'auto'
            ],
            'show_preview' => [
                'type' => 'checkbox',
                'label' => 'Vorschaubild anzeigen (bei Bildern/PDFs)',
                'default' => false
            ],
            'open_in_new_tab' => [
                'type' => 'checkbox',
                'label' => 'In neuem Tab öffnen',
                'default' => false
            ],
            
            // Items Repeater
            'items' => [
                'type' => 'repeater',
                'label' => 'Downloads',
                'add_label' => 'Download hinzufügen',
                'view' => 'list',
                
                // Modal für erweiterte Optionen
                'item_modal' => [
                    'label' => 'Erweiterte Optionen',
                    'icon' => 'fa-cog',
                    'fields' => ['description', 'custom_icon', 'category', 'badge', 'badge_color']
                ],
                
                'fields' => [
                    'file' => [
                        'type' => 'be_media',
                        'label' => 'Datei',
                        'preview' => true,
                        'notice' => 'Datei aus dem Medienpool auswählen'
                    ],
                    'title' => [
                        'type' => 'text',
                        'label' => 'Titel',
                        'notice' => 'Überschreibt den Dateinamen'
                    ],
                    'description' => [
                        'type' => 'text',
                        'label' => 'Beschreibung',
                        'notice' => 'Optional: Kurze Beschreibung der Datei'
                    ],
                    'custom_icon' => [
                        'type' => 'text',
                        'label' => 'Eigenes Icon',
                        'notice' => 'z.B. fa-file-pdf-o (nur bei Icon-Stil "Eigenes Icon")'
                    ],
                    'category' => [
                        'type' => 'text',
                        'label' => 'Kategorie',
                        'notice' => 'Optional: z.B. "Formulare", "Broschüren"'
                    ],
                    'badge' => [
                        'type' => 'text',
                        'label' => 'Badge',
                        'notice' => 'z.B. "NEU", "Aktualisiert"'
                    ],
                    'badge_color' => [
                        'type' => 'choice',
                        'label' => 'Badge Farbe',
                        'choices' => [
                            'primary' => 'Primary',
                            'success' => 'Success',
                            'warning' => 'Warning',
                            'danger' => 'Danger'
                        ],
                        'default' => 'primary'
                    ]
                ]
            ]
        ],
        
        // Section-Felder
        $elementConfig::getOptionalSectionFields()
    )
];
