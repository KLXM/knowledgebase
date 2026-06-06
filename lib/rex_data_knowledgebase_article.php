<?php

declare(strict_types=1);

use KLXM\YFormContentBuilder\Helper;

/**
 * @method int getId()
 */
class rex_data_knowledgebase_article extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase_article';

    /**
        * @return rex_yform_manager_collection<static>
     */
    public static function findOnlineByKnowledgebase(int $knowledgebaseId): rex_yform_manager_collection
    {
        return self::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('online', 1)
            ->orderBy('priority')
            ->orderBy('title')
            ->find();
    }

    public static function findOnlineBySlug(int $knowledgebaseId, string $slug): ?self
    {
        $article = self::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('slug', $slug)
            ->where('online', 1)
            ->findOne();

        return $article instanceof self ? $article : null;
    }

    public function getNavLabel(): string
    {
        $navTitle = trim((string) $this->getValue('nav_title'));

        return '' !== $navTitle ? $navTitle : (string) $this->getValue('title');
    }

    public function renderContent(): string
    {
        $content = trim((string) $this->getValue('content'));
        if ('' === $content) {
            return '';
        }

        if (!rex_addon::get('yform_content_builder')->isAvailable()) {
            return nl2br(rex_escape($content));
        }

        return Helper::render($content, 'uikit');
    }
}