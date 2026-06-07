<?php

declare(strict_types=1);

/**
 * @method int getId()
 */
class rex_data_knowledgebase extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase';

    public static function findOnlineById(int $id): ?self
    {
        $dataset = self::get($id);

        if (!$dataset instanceof self) {
            return null;
        }

        return (int) $dataset->getValue('status') === 1 ? $dataset : null;
    }

    public function getPlaceholder(): string
    {
        $placeholder = trim((string) $this->getValue('search_placeholder'));

        return '' !== $placeholder ? $placeholder : 'Suche in dieser Wissensbasis …';
    }

    public function isGlossaryEnabled(): bool
    {
        return (int) $this->getValue('glossary_enabled') === 1;
    }

    public function isTagFilterEnabled(): bool
    {
        $value = $this->getValue('tag_filter_enabled');

        // Abwaertskompatibel: Wenn das Feld noch nicht im Schema vorhanden ist, bleibt der Filter aktiv.
        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 1;
    }

    public function getArticleSortField(): string
    {
        $field = trim((string) $this->getValue('article_sort_field'));
        $allowedFields = ['priority', 'title', 'updatedate'];

        return in_array($field, $allowedFields, true) ? $field : 'priority';
    }

    /**
     * @return 'ASC'|'DESC'
     */
    public function getArticleSortOrder(): string
    {
        $order = strtoupper(trim((string) $this->getValue('article_sort_order')));

        return in_array($order, ['ASC', 'DESC'], true) ? $order : 'ASC';
    }

    /**
     * @return rex_yform_manager_collection<rex_yform_manager_dataset>
     */
    public function getOnlineArticles(): rex_yform_manager_collection
    {
        $sortField = $this->getArticleSortField();
        $sortOrder = $this->getArticleSortOrder();

        $query = rex_yform_manager_dataset::query(rex::getTable('knowledgebase_article'))
            ->where('knowledgebase_id', $this->getId())
            ->where('online', 1);

        if ($sortField === 'priority') {
            if ($sortOrder === 'DESC') {
                $query->orderBy('priority', 'DESC');
            } else {
                $query->orderBy('priority', 'ASC');
            }
        } else {
            // Prioritaet bleibt immer fuer manuelle Sortierung wirksam.
            $query->orderBy('priority', 'ASC');
            if ($sortOrder === 'DESC') {
                $query->orderBy($sortField, 'DESC');
            } else {
                $query->orderBy($sortField, 'ASC');
            }
        }

        return $query
            ->orderBy('title', 'ASC')
            ->find();
    }

    public function getFirstArticle(): ?rex_yform_manager_dataset
    {
        $articles = $this->getOnlineArticles();

        return $articles->first();
    }
}