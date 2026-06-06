<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex_server;

final class FrontendContext
{
    /**
     * @var list<array{knowledgebase_id:int,article_param:string,search_param:string}>
     */
    private static array $stack = [];

    public static function push(int $knowledgebaseId, string $articleParam, string $searchParam): void
    {
        self::$stack[] = [
            'knowledgebase_id' => $knowledgebaseId,
            'article_param' => $articleParam,
            'search_param' => $searchParam,
        ];
    }

    public static function pop(): void
    {
        array_pop(self::$stack);
    }

    /**
     * @return array{knowledgebase_id:int,article_param:string,search_param:string}|null
     */
    public static function current(): ?array
    {
        $current = end(self::$stack);

        return is_array($current) ? $current : null;
    }

    public static function articleUrlForId(int $articleId): string
    {
        $context = self::current();
        if (!is_array($context) || $articleId <= 0) {
            return '';
        }

        $article = \rex_data_knowledgebase_article::get($articleId);
        if (!$article instanceof \rex_data_knowledgebase_article) {
            return '';
        }

        if ((int) $article->getValue('knowledgebase_id') !== $context['knowledgebase_id']) {
            return '';
        }

        $query = http_build_query([
            $context['article_param'] => (string) $article->getValue('slug'),
        ]);
        $requestUri = rex_server('REQUEST_URI', 'string', '');
        $path = parse_url($requestUri, PHP_URL_PATH);
        $basePath = is_string($path) && '' !== $path ? $path : '/';

        return $basePath . ('' !== $query ? '?' . $query : '');
    }
}