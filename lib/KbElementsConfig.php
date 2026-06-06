<?php

namespace FriendsOfREDAXO\Knowledgebase;

class KbElementsConfig
{
    private static ?bool $hasUikitThemeBuilder = null;
    private static ?array $backgroundChoices = null;
    private static ?array $backgroundColors = null;
    private static ?array $paddingOptions = null;
    private static ?array $containerOptions = null;

    public static function hasThemeBuilder(): bool
    {
        if (null === self::$hasUikitThemeBuilder) {
            self::$hasUikitThemeBuilder = \rex_addon::get('uikit_theme_builder')->isAvailable()
                && class_exists('UikitThemeBuilder\\DomainContext');
        }

        return self::$hasUikitThemeBuilder;
    }

    public static function getBackgroundChoices(): array
    {
        if (null === self::$backgroundChoices) {
            self::$backgroundChoices = [
                '' => 'Keine',
                'uk-background-default' => 'Default (Weiß)',
                'uk-background-muted' => 'Muted (Grau)',
                'uk-background-primary' => 'Primary',
                'uk-background-secondary' => 'Secondary',
            ];

            if (self::hasThemeBuilder()) {
                $themeBackgrounds = \UikitThemeBuilder\DomainContext::getBackgroundOptions();
                if ([] !== $themeBackgrounds) {
                    self::$backgroundChoices = ['' => 'Keine'];
                    foreach ($themeBackgrounds as $class => $data) {
                        self::$backgroundChoices[$class] = $data['label'] ?? ucfirst(str_replace('uk-background-', '', $class));
                    }
                }
            }
        }

        return self::$backgroundChoices;
    }

    public static function getBackgroundColors(): array
    {
        if (null === self::$backgroundColors) {
            self::$backgroundColors = [
                '' => ['color' => 'transparent', 'label' => 'Keine'],
                'uk-background-default' => ['color' => '#ffffff', 'label' => 'Default (Weiß)'],
                'uk-background-muted' => ['color' => '#f8f8f8', 'label' => 'Muted (Grau)'],
                'uk-background-primary' => ['color' => '#1e87f0', 'label' => 'Primary'],
                'uk-background-secondary' => ['color' => '#222222', 'label' => 'Secondary'],
            ];

            if (self::hasThemeBuilder()) {
                $themeBackgrounds = \UikitThemeBuilder\DomainContext::getBackgroundOptions();
                if ([] !== $themeBackgrounds) {
                    self::$backgroundColors = ['' => ['color' => 'transparent', 'label' => 'Keine']];
                    foreach ($themeBackgrounds as $class => $data) {
                        self::$backgroundColors[$class] = $data;
                    }
                }
            }
        }

        return self::$backgroundColors;
    }

    public static function getPaddingOptions(): array
    {
        if (null === self::$paddingOptions) {
            self::$paddingOptions = [
                '' => 'Standard',
                'uk-padding-remove' => 'Keine Füllung',
                'uk-padding-small' => 'Klein',
                'uk-padding' => 'Mittel',
                'uk-padding-large' => 'Groß',
            ];
        }

        return self::$paddingOptions;
    }

    public static function getContainerOptions(): array
    {
        if (null === self::$containerOptions) {
            self::$containerOptions = [
                'uk-container' => 'Standard',
                'uk-container uk-container-xsmall' => 'Extra schmal',
                'uk-container uk-container-small' => 'Schmal',
                'uk-container uk-container-large' => 'Weit',
                'uk-container uk-container-xlarge' => 'Extra weit',
                'uk-container uk-container-expand' => 'Maximale Breite',
                '' => 'Volle Breite (kein Container)',
            ];
        }

        return self::$containerOptions;
    }

    public static function getColumnOptions(string $device = 'desktop'): array
    {
        switch ($device) {
            case 'mobile':
                return [
                    '1' => '1 Spalte',
                    '2' => '2 Spalten',
                ];
            case 'tablet':
                return [
                    '1' => '1 Spalte',
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                ];
            default:
                return [
                    '1' => '1 Spalte (100%)',
                    '2' => '2 Spalten',
                    '3' => '3 Spalten',
                    '4' => '4 Spalten',
                    '5' => '5 Spalten',
                    '6' => '6 Spalten',
                ];
        }
    }

    public static function getGapOptions(): array
    {
        return [
            'collapse' => 'Kein Abstand',
            'small' => 'Klein (15px)',
            'medium' => 'Mittel (30px)',
            'large' => 'Groß (40px)',
        ];
    }

    public static function getGridFields(): array
    {
        return [
            'columns' => [
                'type' => 'choice',
                'label' => 'Spalten (Desktop)',
                'choices' => self::getColumnOptions('desktop'),
                'default' => '3',
            ],
            'columns_tablet' => [
                'type' => 'choice',
                'label' => 'Spalten (Tablet)',
                'choices' => self::getColumnOptions('tablet'),
                'default' => '2',
            ],
            'columns_mobile' => [
                'type' => 'choice',
                'label' => 'Spalten (Mobile)',
                'choices' => self::getColumnOptions('mobile'),
                'default' => '1',
            ],
            'gap' => [
                'type' => 'choice',
                'label' => 'Abstand zwischen Elementen',
                'choices' => self::getGapOptions(),
                'default' => 'medium',
            ],
        ];
    }

    public static function getGridFieldNames(): array
    {
        return ['columns', 'columns_tablet', 'columns_mobile', 'gap'];
    }

    public static function getSectionFields(): array
    {
        return [
            'section_bg' => [
                'type' => 'choice',
                'label' => 'Sektions-Hintergrund',
                'choices' => self::getBackgroundChoices(),
                'choice_colors' => self::getBackgroundColors(),
                'selectpicker' => true,
                'default' => '',
                'visible_if' => ['enable_section' => '1'],
            ],
            'section_bg_image' => [
                'type' => 'be_media',
                'label' => 'Sektions-Hintergrund (Bild/Video)',
                'notice' => 'Hintergrundbild oder -video (MP4, WebM). Video wird automatisch mit Autoplay und Loop abgespielt.',
                'visible_if' => ['enable_section' => '1'],
            ],
            'section_padding' => [
                'type' => 'choice',
                'label' => 'Sektions-Padding',
                'choices' => self::getPaddingOptions(),
                'default' => '',
                'visible_if' => ['enable_section' => '1'],
            ],
            'container_width' => [
                'type' => 'choice',
                'label' => 'Container-Breite',
                'choices' => self::getContainerOptions(),
                'default' => 'uk-container',
                'visible_if' => ['enable_container' => '1'],
            ],
            'section_light' => [
                'type' => 'checkbox',
                'label' => 'Heller Text (uk-light)',
                'notice' => 'Aktiviert uk-light Klasse für Text auf dunklem Hintergrund',
                'visible_if' => ['enable_section' => '1'],
            ],
        ];
    }

    public static function getSectionFieldNames(): array
    {
        return ['section_bg', 'section_bg_image', 'section_padding', 'container_width', 'section_light'];
    }

    public static function getWrapperControlFields(array $overrides = []): array
    {
        $fields = [
            'enable_section' => [
                'type' => 'checkbox',
                'label' => 'Sektion aktivieren',
                'default' => false,
                'notice' => 'Nur aktivieren, wenn dieses Element eine eigene Section-Umhuellung benoetigt.',
            ],
            'enable_container' => [
                'type' => 'checkbox',
                'label' => 'Container aktivieren',
                'default' => false,
                'notice' => 'Nur aktivieren, wenn ein eigener Container gesetzt werden soll.',
            ],
        ];

        foreach ($overrides as $fieldName => $fieldOverrides) {
            if (isset($fields[$fieldName]) && is_array($fieldOverrides)) {
                $fields[$fieldName] = array_merge($fields[$fieldName], $fieldOverrides);
            }
        }

        return $fields;
    }

    public static function getWrapperControlFieldNames(): array
    {
        return array_keys(self::getWrapperControlFields());
    }

    public static function getOptionalSectionFieldNames(): array
    {
        return array_merge(self::getWrapperControlFieldNames(), self::getSectionFieldNames());
    }

    public static function getOptionalSectionFields(array $wrapperOverrides = []): array
    {
        return array_merge(self::getWrapperControlFields($wrapperOverrides), self::getSectionFields());
    }
}