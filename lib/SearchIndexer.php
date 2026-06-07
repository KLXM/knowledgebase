<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_extension_point;
use rex_sql;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

final class SearchIndexer
{
    /**
     * @param rex_extension_point<mixed> $ep
     */
    public static function handleYformEvent(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }

        if ($table->getTableName() !== \rex::getTable('knowledgebase_article')) {
            return;
        }

        $dataset = $ep->getParam('data');
        if (!$dataset instanceof rex_yform_manager_dataset || !$dataset instanceof \rex_data_knowledgebase_article) {
            return;
        }

        self::syncArticle($dataset);
    }

    public static function syncArticle(\rex_data_knowledgebase_article $article): void
    {
        $searchText = SearchTextExtractor::normalize(
            implode(' ', array_filter([
                (string) $article->getValue('title'),
                (string) $article->getValue('nav_title'),
                implode(' ', $article->getTags()),
                (string) $article->getValue('intro'),
                SearchTextExtractor::extractFromContentBuilder((string) $article->getValue('content')),
            ], static fn (string $value): bool => '' !== trim($value))),
        );

        if ($searchText === (string) $article->getValue('search_text')) {
            return;
        }

        $sql = rex_sql::factory();
        $sql->setTable(\rex::getTable('knowledgebase_article'));
        $sql->setWhere(['id' => $article->getId()]);
        $sql->setValue('search_text', $searchText);
        $sql->update();
    }
}