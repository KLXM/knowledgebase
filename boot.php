<?php

declare(strict_types=1);

if (rex_addon::get('yform')->isAvailable()) {
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('knowledgebase'),
        'rex_data_knowledgebase',
    );
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('knowledgebase_article'),
        'rex_data_knowledgebase_article',
    );
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('knowledgebase_glossary'),
        'rex_data_knowledgebase_glossary',
    );
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('knowledgebase_interactive_image'),
        'rex_data_knowledgebase_interactive_image',
    );

    rex_extension::register('YFORM_DATA_ADD', ['FriendsOfREDAXO\\Knowledgebase\\SlugManager', 'handleYformBeforeSave'], rex_extension::EARLY);
    rex_extension::register('YFORM_DATA_UPDATE', ['FriendsOfREDAXO\\Knowledgebase\\SlugManager', 'handleYformBeforeSave'], rex_extension::EARLY);
    rex_extension::register('YFORM_DATA_ADDED', ['FriendsOfREDAXO\\Knowledgebase\\SearchIndexer', 'handleYformEvent']);
    rex_extension::register('YFORM_DATA_UPDATED', ['FriendsOfREDAXO\\Knowledgebase\\SearchIndexer', 'handleYformEvent']);
    rex_extension::register('YFORM_DATA_ADDED', static function (rex_extension_point $ep): void {
        \FriendsOfREDAXO\Knowledgebase\GlossaryService::handleYformEvent($ep);
    });
    rex_extension::register('YFORM_DATA_UPDATED', static function (rex_extension_point $ep): void {
        \FriendsOfREDAXO\Knowledgebase\GlossaryService::handleYformEvent($ep);
    });
    rex_extension::register('YFORM_DATA_DELETED', static function (rex_extension_point $ep): void {
        \FriendsOfREDAXO\Knowledgebase\GlossaryService::handleYformEvent($ep);
    });
}

if (rex_addon::get('yform_content_builder')->isAvailable()) {
    rex_extension::register(
        'YFORM_CONTENT_BUILDER_ELEMENT_MODE',
        static function (): string {
            // Eigene Elemente zusätzlich anbieten, Core-Elemente aber behalten.
            return 'merge';
        },
        rex_extension::EARLY,
    );

    rex_extension::register(
        'YFORM_CONTENT_BUILDER_ELEMENT_PATHS',
        static function (rex_extension_point $ep): array {
            $paths = $ep->getSubject();
            if (!is_array($paths)) {
                $paths = [];
            }

            $paths[] = rex_path::addon('knowledgebase', 'elements/');

            return $paths;
        },
        rex_extension::EARLY,
    );

    if (rex::isBackend()) {
        $addon = rex_addon::get('knowledgebase');
        rex_view::addCssFile($addon->getAssetsUrl('css/interactive_images_editor.css'));
        rex_view::addJsFile($addon->getAssetsUrl('js/interactive_images_editor.js'));
    }
}