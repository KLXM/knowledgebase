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

if ((rex_addon::exists('builder') && rex_addon::get('builder')->isAvailable()) || rex_addon::get('builder')->isAvailable()) {
    $registerForNames = static function (string $legacyName, callable $callable): void {
        $names = [$legacyName];
        if (str_starts_with($legacyName, 'BUILDER_')) {
            $names[] = 'BUILDER_' . substr($legacyName, strlen('BUILDER_'));
        }

        foreach (array_values(array_unique($names)) as $name) {
            rex_extension::register($name, $callable, rex_extension::EARLY);
        }
    };

    $registerForNames(
        'BUILDER_ELEMENT_MODE',
        static function (): string {
            return AddonSettings::getElementMode();
        },
    );

    $registerForNames(
        'BUILDER_ELEMENT_PATHS',
        static function (rex_extension_point $ep): array {
            $paths = $ep->getSubject();
            if (!is_array($paths)) {
                $paths = [];
            }

            $paths[] = rex_path::addon('knowledgebase', 'elements/');

            return $paths;
        },
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
    rex_view::addCssFile(rex_url::addonAssets('knowledgebase', 'css/articles-chapter-toolbar.css') . '?v=' . rawurlencode((string) $addon->getVersion()));
    rex_view::addJsFile(rex_url::addonAssets('knowledgebase', 'js/articles-focus-fix.js') . '?v=' . rawurlencode((string) $addon->getVersion()));
}

if (rex_addon::exists('klxmchat') && rex_addon::get('klxmchat')->isAvailable()) {
    rex_extension::register('KLXMCHAT_CONTENT_PROVIDERS', static function (rex_extension_point $ep): array {
        $providers = $ep->getSubject();
        if (!is_array($providers)) {
            $providers = [];
        }

        if (class_exists(\FriendsOfRedaxo\KlxmChat\ContentProvider\ContentProviderInterface::class)) {
            $providers['knowledgebase'] = new \FriendsOfREDAXO\Knowledgebase\ContentProvider\KnowledgebaseContentProvider();
        }

        return $providers;
    });
}

// URL-Addon-Integration: URL-Rebuild bei Artikel-Änderungen und OUTPUT_FILTER für Link-Rewriting
if (rex_addon::get('url')->isAvailable() && class_exists(\Url\Profile::class)) {
    rex_extension::register('URL_TABLE_UPDATED', static function (): void {
        foreach (\FriendsOfREDAXO\Knowledgebase\UrlProfileManager::getMappings() as $mapping) {
            $kbId = (int) ($mapping['kb_id'] ?? 0);
            if ($kbId > 0) {
                \FriendsOfREDAXO\Knowledgebase\UrlProfileManager::ensureSectionRoutes($kbId);
            }
        }
    });

    // URLs neu aufbauen wenn ein Knowledgebase-Artikel gespeichert wird
    rex_extension::register('YFORM_DATA_ADDED', static function (rex_extension_point $ep): void {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }
        if ($table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return;
        }
        $dataset = $ep->getSubject();
        if (!$dataset instanceof rex_yform_manager_dataset) {
            return;
        }
        $kbId = (int) $dataset->getValue('knowledgebase_id');
        if ($kbId > 0 && \FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl::hasProfile($kbId)) {
            \FriendsOfREDAXO\Knowledgebase\UrlProfileManager::rebuildForKnowledgebase($kbId);
        }
    });

    rex_extension::register('YFORM_DATA_UPDATED', static function (rex_extension_point $ep): void {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }
        if ($table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return;
        }
        $dataset = $ep->getSubject();
        if (!$dataset instanceof rex_yform_manager_dataset) {
            return;
        }
        $kbId = (int) $dataset->getValue('knowledgebase_id');
        if ($kbId > 0 && \FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl::hasProfile($kbId)) {
            \FriendsOfREDAXO\Knowledgebase\UrlProfileManager::rebuildForKnowledgebase($kbId);
        }
    });

    rex_extension::register('YFORM_DATA_DELETED', static function (rex_extension_point $ep): void {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }
        if ($table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return;
        }

        $dataset = $ep->getSubject();
        if (!$dataset instanceof rex_yform_manager_dataset) {
            return;
        }

        $kbId = (int) $dataset->getValue('knowledgebase_id');
        if ($kbId > 0 && \FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl::hasProfile($kbId)) {
            \FriendsOfREDAXO\Knowledgebase\UrlProfileManager::rebuildForKnowledgebase($kbId);
        }
    });

    // OUTPUT_FILTER: Interne Fallback-URLs (?kb_X_article=slug) durch saubere URLs ersetzen
    if (rex::isFrontend()) {
        rex_extension::register('OUTPUT_FILTER', static function (rex_extension_point $ep): void {
            $subject = $ep->getSubject();
            if (!is_string($subject)) {
                return;
            }

            // Pattern: ?kb_{id}_article={slug} oder &kb_{id}_article={slug}
            $subject = preg_replace_callback(
                '/(["\'])([^"\']*)\?kb_(\d+)_article=([^&"\'#]+)([^"\']*)\1/',
                static function (array $m): string {
                    $quote = $m[1];
                    $prefix = $m[2];
                    $kbId = (int) $m[3];
                    $slug = rawurldecode($m[4]);
                    $suffix = $m[5];

                    if (!\FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl::hasProfile($kbId)) {
                        return $m[0];
                    }

                    $cleanUrl = \FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl::getArticleUrl($kbId, $slug);
                    if ('' === $cleanUrl) {
                        return $m[0];
                    }

                    return $quote . $prefix . $cleanUrl . $suffix . $quote;
                },
                $subject,
            );

            $ep->setSubject($subject ?? $ep->getSubject());
        });
    }
}