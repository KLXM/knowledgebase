<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_extension_point;
use rex_i18n;
use rex_request;
use rex_sql;
use rex_yform;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

final class ArticleHierarchyService
{
    private const MAX_DEPTH = 2;

    /**
     * @param rex_extension_point<mixed> $ep
     */
    public static function handleYformBeforeSave(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table || $table->getTableName() !== rex::getTable('knowledgebase_article')) {
            return;
        }

        $yform = $ep->getSubject();
        if (!$yform instanceof rex_yform) {
            return;
        }

        $dataId = (int) $ep->getParam('data_id', 0);
        $knowledgebaseId = self::resolveKnowledgebaseId($yform, $dataId);
        if ($knowledgebaseId <= 0) {
            return;
        }

        $parentId = max(0, (int) $yform->getFieldValue('parent_id'));
        $yform->setFieldValue('parent_id', [], (string) $parentId);

        if ($parentId <= 0) {
            return;
        }

        $articles = self::getArticlesForKnowledgebase($knowledgebaseId);
        $articleMap = self::buildArticleMap($articles);

        if (!isset($articleMap[$parentId])) {
            self::addWarning($yform, rex_i18n::msg('knowledgebase_article_parent_invalid'));
            return;
        }

        if ($dataId > 0) {
            if ($parentId === $dataId) {
                self::addWarning($yform, rex_i18n::msg('knowledgebase_article_parent_self'));
                return;
            }

            $descendantIds = self::collectDescendantIds($articleMap, $dataId);
            if (in_array($parentId, $descendantIds, true)) {
                self::addWarning($yform, rex_i18n::msg('knowledgebase_article_parent_cycle'));
                return;
            }
        }

        $newDepth = self::calculateDepth($articleMap, $parentId) + 1;
        $subtreeHeight = $dataId > 0 ? self::calculateSubtreeHeight($articleMap, $dataId) : 0;
        if ($newDepth + $subtreeHeight > self::MAX_DEPTH) {
            self::addWarning($yform, rex_i18n::msg('knowledgebase_article_parent_depth_exceeded'));
        }
    }

    /**
     * @return list<\rex_data_knowledgebase_article>
     */
    public static function getArticlesForKnowledgebase(int $knowledgebaseId): array
    {
        if ($knowledgebaseId <= 0) {
            return [];
        }

        $articles = [];
        foreach (\rex_data_knowledgebase_article::findByKnowledgebase($knowledgebaseId) as $article) {
            if ($article instanceof \rex_data_knowledgebase_article) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    /**
     * @param list<\rex_data_knowledgebase_article> $articles
     * @return list<array<string, mixed>>
     */
    public static function buildTree(array $articles): array
    {
        $articleMap = self::buildArticleMap($articles);
        $childrenMap = self::buildChildrenMap($articleMap);

        return self::buildTreeLevel($articleMap, $childrenMap, 0, 0);
    }

    /**
     * @param list<\rex_data_knowledgebase_article> $articles
     * @return list<array{id:int,title:string,label:string,depth:int,disabled:bool}>
     */
    public static function buildSelectableOptions(array $articles, int $currentArticleId = 0): array
    {
        $tree = self::buildTree($articles);
        $articleMap = self::buildArticleMap($articles);
        $descendantIds = $currentArticleId > 0 ? self::collectDescendantIds($articleMap, $currentArticleId) : [];
        $subtreeHeight = $currentArticleId > 0 ? self::calculateSubtreeHeight($articleMap, $currentArticleId) : 0;
        $options = [];

        $append = static function (array $nodes, int $depth) use (&$append, &$options, $currentArticleId, $descendantIds, $articleMap, $subtreeHeight): void {
            foreach ($nodes as $node) {
                $article = $node['article'];
                if (!$article instanceof \rex_data_knowledgebase_article) {
                    continue;
                }

                $id = $article->getId();
                $candidateDepth = ArticleHierarchyService::calculateDepth($articleMap, $id);
                $disabled = $id === $currentArticleId
                    || in_array($id, $descendantIds, true)
                    || ($candidateDepth + 1 + $subtreeHeight) > ArticleHierarchyService::MAX_DEPTH;

                $options[] = [
                    'id' => $id,
                    'title' => (string) $article->getValue('title'),
                    'label' => $article->getNavLabel(),
                    'depth' => $depth,
                    'disabled' => $disabled,
                ];

                $append($node['children'], $depth + 1);
            }
        };

        $append($tree, 0);

        return $options;
    }

    /**
     * @param list<\rex_data_knowledgebase_article> $articles
     * @return array<int, \rex_data_knowledgebase_article>
     */
    private static function buildArticleMap(array $articles): array
    {
        $articleMap = [];
        foreach ($articles as $article) {
            $articleMap[$article->getId()] = $article;
        }

        return $articleMap;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     * @return array<int, list<int>>
     */
    private static function buildChildrenMap(array $articleMap): array
    {
        $childrenMap = [];
        foreach ($articleMap as $id => $article) {
            $parentId = max(0, (int) $article->getValue('parent_id'));
            if ($parentId > 0 && isset($articleMap[$parentId])) {
                $childrenMap[$parentId][] = $id;
                continue;
            }

            $childrenMap[0][] = $id;
        }

        return $childrenMap;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     * @param array<int, list<int>> $childrenMap
     * @return list<array<string, mixed>>
     */
    private static function buildTreeLevel(array $articleMap, array $childrenMap, int $parentId, int $depth): array
    {
        $nodes = [];
        foreach ($childrenMap[$parentId] ?? [] as $id) {
            if (!isset($articleMap[$id])) {
                continue;
            }

            $nodes[] = [
                'article' => $articleMap[$id],
                'children' => $depth < self::MAX_DEPTH ? self::buildTreeLevel($articleMap, $childrenMap, $id, $depth + 1) : [],
            ];
        }

        return $nodes;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     * @return list<int>
     */
    private static function collectDescendantIds(array $articleMap, int $articleId): array
    {
        $descendants = [];
        $stack = [$articleId];

        while ($stack !== []) {
            $currentId = array_pop($stack);
            foreach ($articleMap as $candidateId => $candidate) {
                if (max(0, (int) $candidate->getValue('parent_id')) !== $currentId) {
                    continue;
                }

                $descendants[] = $candidateId;
                $stack[] = $candidateId;
            }
        }

        return $descendants;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     */
    private static function calculateDepth(array $articleMap, int $articleId): int
    {
        $depth = 0;
        $guard = 0;
        $currentId = $articleId;

        while (isset($articleMap[$currentId]) && $guard < 10) {
            $parentId = max(0, (int) $articleMap[$currentId]->getValue('parent_id'));
            if ($parentId <= 0 || !isset($articleMap[$parentId])) {
                break;
            }

            ++$depth;
            $currentId = $parentId;
            ++$guard;
        }

        return $depth;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     */
    private static function calculateSubtreeHeight(array $articleMap, int $articleId): int
    {
        $maxHeight = 0;
        foreach ($articleMap as $candidateId => $candidate) {
            if ($candidateId === $articleId) {
                continue;
            }

            $distance = self::distanceToAncestor($articleMap, $candidateId, $articleId);
            if ($distance > $maxHeight) {
                $maxHeight = $distance;
            }
        }

        return $maxHeight;
    }

    /**
     * @param array<int, \rex_data_knowledgebase_article> $articleMap
     */
    private static function distanceToAncestor(array $articleMap, int $articleId, int $ancestorId): int
    {
        $distance = 0;
        $guard = 0;
        $currentId = $articleId;

        while (isset($articleMap[$currentId]) && $guard < 10) {
            $parentId = max(0, (int) $articleMap[$currentId]->getValue('parent_id'));
            if ($parentId <= 0 || !isset($articleMap[$parentId])) {
                return 0;
            }

            ++$distance;
            if ($parentId === $ancestorId) {
                return $distance;
            }

            $currentId = $parentId;
            ++$guard;
        }

        return 0;
    }

    private static function addWarning(rex_yform $yform, string $message): void
    {
        $messages = $yform->getObjectparams('warning_messages');
        if (!is_array($messages)) {
            $messages = [];
        }
        $messages[] = $message;
        $yform->setObjectparams('warning_messages', $messages);
    }

    private static function resolveKnowledgebaseId(rex_yform $yform, int $dataId): int
    {
        $knowledgebaseId = (int) $yform->getFieldValue('knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        $setValues = rex_request('rex_yform_set', 'array', []);
        $knowledgebaseId = self::resolveIdFromRequestArray($setValues, 'knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        $filterValues = rex_request('rex_yform_filter', 'array', []);
        $knowledgebaseId = self::resolveIdFromRequestArray($filterValues, 'knowledgebase_id');
        if ($knowledgebaseId > 0) {
            return $knowledgebaseId;
        }

        if ($dataId > 0) {
            $sql = rex_sql::factory();
            $sql->setQuery(
                'SELECT knowledgebase_id FROM ' . rex::getTable('knowledgebase_article') . ' WHERE id = :id LIMIT 1',
                ['id' => $dataId],
            );

            if ($sql->getRows() > 0) {
                return (int) $sql->getValue('knowledgebase_id');
            }
        }

        return 0;
    }

    /**
     * @param array<mixed> $values
     */
    private static function resolveIdFromRequestArray(array $values, string $key): int
    {
        if (!isset($values[$key])) {
            return 0;
        }

        $value = $values[$key];
        if (is_array($value)) {
            $first = reset($value);
            return $first !== false ? (int) $first : 0;
        }

        return (int) $value;
    }
}
