<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase\ContentProvider;

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;
use FriendsOfREDAXO\Knowledgebase\SearchTextExtractor;
use FriendsOfRedaxo\KlxmChat\ContentProvider\ContentProviderInterface;
use rex;
use rex_addon;

final class KnowledgebaseContentProvider implements ContentProviderInterface
{
    public function getKey(): string
    {
        return 'knowledgebase';
    }

    public function getLabel(): string
    {
        return 'Knowledgebase Beiträge indexieren';
    }

    /**
     * @return list<string>
     */
    public function getSupportedSourceTypes(): array
    {
        return ['knowledgebase_article'];
    }

    public function getPromptInstruction(): string
    {
        return 'Knowledgebase enthält redaktionelle Hilfetexte und Anleitungen. Bevorzuge konkrete Antworten mit klarer Handlungsempfehlung und verweise bei Bedarf auf den passenden Artikel-Link.';
    }

    /**
     * @return array<string, string>
     */
    public function getSourceTypeLabels(): array
    {
        return [
            'knowledgebase_article' => 'Knowledgebase',
        ];
    }

    public function getSearchIconSvg(string $sourceType): string
    {
        if ($sourceType !== 'knowledgebase_article') {
            return '';
        }

        return '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a1 1 0 1 0 0-2H4V5h14v6a1 1 0 1 0 2 0V5a2 2 0 0 0-2-2H4Zm3 4a1 1 0 0 0 0 2h8a1 1 0 1 0 0-2H7Zm0 4a1 1 0 1 0 0 2h5a1 1 0 1 0 0-2H7Zm10 2a5 5 0 1 0 3.2 8.84l1.98 1.98a1 1 0 0 0 1.42-1.42l-1.98-1.98A5 5 0 0 0 17 13Zm-3 5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Z"/></svg>';
    }

    public function isAvailable(): bool
    {
        return rex_addon::exists('knowledgebase') && rex_addon::get('knowledgebase')->isAvailable();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collectTasks(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $sql = \rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT a.id, a.knowledgebase_id, a.title, a.nav_title, a.slug, a.updatedate, a.createdate '
            . 'FROM ' . rex::getTable('knowledgebase_article') . ' a '
            . 'INNER JOIN ' . rex::getTable('knowledgebase') . ' b ON b.id = a.knowledgebase_id '
            . 'WHERE a.online = 1 AND b.online = 1'
        );

        $tasks = [];
        foreach ($rows as $row) {
            $articleId = (int) ($row['id'] ?? 0);
            $knowledgebaseId = (int) ($row['knowledgebase_id'] ?? 0);
            if ($articleId <= 0 || $knowledgebaseId <= 0) {
                continue;
            }

            $title = trim((string) ($row['nav_title'] ?? ''));
            if ($title === '') {
                $title = trim((string) ($row['title'] ?? ''));
            }
            if ($title === '') {
                $title = 'Knowledgebase Beitrag #' . $articleId;
            }

            $updatedAt = $this->toTimestamp((string) ($row['updatedate'] ?? ''));
            if ($updatedAt <= 0) {
                $updatedAt = $this->toTimestamp((string) ($row['createdate'] ?? ''));
            }
            if ($updatedAt <= 0) {
                $updatedAt = time();
            }

            $tasks[] = [
                'type' => 'provider_item',
                'provider' => $this->getKey(),
                'source_type' => 'knowledgebase_article',
                'source_id' => (string) $knowledgebaseId . ':' . (string) $articleId,
                'knowledgebase_id' => $knowledgebaseId,
                'article_id' => $articleId,
                'title' => $title,
                'updatedate_ts' => $updatedAt,
            ];
        }

        return $tasks;
    }

    /**
     * @param array<string, mixed> $task
     * @return array<string, mixed>|null
     */
    public function prepareDocument(array $task): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $knowledgebaseId = (int) ($task['knowledgebase_id'] ?? 0);
        $articleId = (int) ($task['article_id'] ?? 0);
        if ($knowledgebaseId <= 0 || $articleId <= 0) {
            return null;
        }

        $article = \rex_data_knowledgebase_article::get($articleId);
        if (!$article instanceof \rex_data_knowledgebase_article) {
            return null;
        }

        if ((int) $article->getValue('knowledgebase_id') !== $knowledgebaseId || (int) $article->getValue('online') !== 1) {
            return null;
        }

        $knowledgebase = \rex_data_knowledgebase::findOnlineById($knowledgebaseId);
        if (!$knowledgebase instanceof \rex_data_knowledgebase) {
            return null;
        }

        $title = $article->getNavLabel();
        if ($title === '') {
            $title = trim((string) $article->getValue('title'));
        }
        if ($title === '') {
            $title = 'Knowledgebase Beitrag #' . $articleId;
        }

        $intro = trim((string) $article->getValue('intro'));
        $content = SearchTextExtractor::extractFromContentBuilder((string) $article->getValue('content'));
        $tags = implode(', ', $article->getTags());

        $parts = [
            'Wissensbasis: ' . trim((string) $knowledgebase->getValue('title')),
            'Titel: ' . $title,
        ];

        if ($intro !== '') {
            $parts[] = 'Einleitung: ' . SearchTextExtractor::normalize($intro);
        }
        if ($content !== '') {
            $parts[] = 'Inhalt: ' . $content;
        }
        if ($tags !== '') {
            $parts[] = 'Tags: ' . $tags;
        }

        $documentText = trim(implode("\n", $parts));
        if ($documentText === '') {
            return null;
        }

        $slug = trim((string) $article->getValue('slug'));
        $url = '';
        if ($slug !== '' && KnowledgebaseUrl::hasProfile($knowledgebaseId)) {
            $url = KnowledgebaseUrl::getArticleUrl($knowledgebaseId, $slug);
        }

        $updatedAt = $this->toTimestamp((string) $article->getValue('updatedate'));
        if ($updatedAt <= 0) {
            $updatedAt = $this->toTimestamp((string) $article->getValue('createdate'));
        }
        if ($updatedAt <= 0) {
            $updatedAt = time();
        }

        return [
            'source_type' => 'knowledgebase_article',
            'source_id' => (string) $knowledgebaseId . ':' . (string) $articleId,
            'title' => $title,
            'content' => $documentText,
            'url' => $url,
            'updatedate_ts' => $updatedAt,
        ];
    }

    private function toTimestamp(string $dateTime): int
    {
        if ($dateTime === '') {
            return 0;
        }

        $ts = strtotime($dateTime);

        return $ts !== false ? (int) $ts : 0;
    }
}
