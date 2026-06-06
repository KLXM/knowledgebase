<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\AddonSettings;

if (rex::isBackend() && null !== rex::getUser()) {
    rex_perm::register('knowledgebase[]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[overview]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[articles]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[bases]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[glossary]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[interactive_images]', null, rex_perm::OPTIONS);
    rex_perm::register('knowledgebase[frontend_texts]', null, rex_perm::OPTIONS);
}

if (rex::isBackend()) {
    rex_extension::register('PACKAGES_INCLUDED', static function (): void {
        $addon = rex_addon::get('knowledgebase');
        $page = $addon->getProperty('page');
        if (is_array($page)) {
            $page['title'] = AddonSettings::getMenuTitle();
            $addon->setProperty('page', $page);
        }
    }, rex_extension::EARLY);
}

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
            return AddonSettings::getElementMode();
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

if (rex::isBackend() && null !== rex::getUser() && rex_addon::get('tinymce')->isAvailable() && class_exists(\FriendsOfRedaxo\TinyMce\PluginRegistry::class)) {
    \FriendsOfRedaxo\TinyMce\PluginRegistry::addPlugin(
        'knowledgebase_link',
        rex_url::addonAssets('knowledgebase', 'js/tinymce-knowledgebase-link-plugin.js'),
        'knowledgebase_link'
    );
}

if (rex::isBackend() && null !== rex::getUser() && rex_be_controller::getCurrentPage() === 'knowledgebase/articles') {
    $addon = rex_addon::get('knowledgebase');
    rex_view::addJsFile(rex_url::addonAssets('knowledgebase', 'js/articles-focus-fix.js') . '?v=' . rawurlencode((string) $addon->getVersion()));
}