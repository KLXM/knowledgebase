<?php

declare(strict_types=1);

/**
 * @method int getId()
 */
class rex_data_knowledgebase extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase';

    /**
     * @var array<string, array<string, bool>>
     */
    private static array $columnCache = [];

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

    public function getHeaderLogo(): string
    {
        $file = trim((string) $this->getOptionalValue('header_logo'));

        if ($file === '') {
            return '';
        }

        return \rex_file::extension($file) === 'svg' ? $file : '';
    }

    public function isGlossaryEnabled(): bool
    {
        return (int) $this->getValue('glossary_enabled') === 1;
    }

    public function isTagFilterEnabled(): bool
    {
        $value = $this->getOptionalValue('tag_filter_enabled');

        // Abwaertskompatibel: Wenn das Feld noch nicht im Schema vorhanden ist, bleibt der Filter aktiv.
        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 1;
    }

    public function isTagMultiSelectEnabled(): bool
    {
        $value = $this->getOptionalValue('tag_filter_multi_enabled');

        if ($value === null || $value === '') {
            return false;
        }

        return (int) $value === 1;
    }

    public function isRecentlyViewedEnabled(): bool
    {
        $value = $this->getOptionalValue('recently_viewed_enabled');

        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 1;
    }

    public function getRecentlyViewedLimit(): int
    {
        $value = (int) $this->getOptionalValue('recently_viewed_limit');
        if ($value < 1) {
            return 4;
        }

        return min(5, $value);
    }

    public function isRelatedArticlesEnabled(): bool
    {
        $value = $this->getOptionalValue('related_articles_enabled');

        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 1;
    }

    public function isSearchHistoryEnabled(): bool
    {
        $value = $this->getOptionalValue('search_history_enabled');

        if ($value === null || $value === '') {
            return true;
        }

        return (int) $value === 1;
    }

    public function getRelatedArticlesLimit(): int
    {
        $value = (int) $this->getOptionalValue('related_articles_limit');
        if ($value < 1) {
            return 3;
        }

        return min(5, $value);
    }

    /**
     * @return 'classic'|'compact'|'focus'
     */
    public function getLayoutMode(): string
    {
        $mode = trim((string) $this->getOptionalValue('layout_mode'));
        $mode = self::normalizeLayoutMode($mode);

        return in_array($mode, ['classic', 'compact', 'focus'], true) ? $mode : 'classic';
    }

    public function getArticleSortField(): string
    {
        $field = trim((string) $this->getOptionalValue('article_sort_field'));
        $field = self::normalizeArticleSortField($field);
        $allowedFields = ['priority', 'title', 'updatedate'];

        return in_array($field, $allowedFields, true) ? $field : 'priority';
    }

    /**
     * @return 'ASC'|'DESC'
     */
    public function getArticleSortOrder(): string
    {
        $order = strtoupper(trim((string) $this->getOptionalValue('article_sort_order')));
        $order = self::normalizeArticleSortOrder($order);

        return in_array($order, ['ASC', 'DESC'], true) ? $order : 'ASC';
    }

    private static function normalizeLayoutMode(string $mode): string
    {
        $value = mb_strtolower(trim($mode));

        return match (true) {
            $value === 'classic', str_starts_with($value, 'klassisch') => 'classic',
            $value === 'compact', str_starts_with($value, 'kompakt') => 'compact',
            $value === 'focus', str_starts_with($value, 'fokus') => 'focus',
            default => $mode,
        };
    }

    private static function normalizeArticleSortField(string $field): string
    {
        $value = mb_strtolower(trim($field));

        return match (true) {
            $value === 'priority', str_starts_with($value, 'priorit') => 'priority',
            $value === 'title', str_starts_with($value, 'titel') => 'title',
            $value === 'updatedate', str_starts_with($value, 'zuletzt aktualisiert') => 'updatedate',
            default => $field,
        };
    }

    private static function normalizeArticleSortOrder(string $order): string
    {
        $value = mb_strtolower(trim($order));

        return match (true) {
            $value === 'asc', str_starts_with($value, 'aufst'), str_starts_with($value, 'aufs') => 'ASC',
            $value === 'desc', str_starts_with($value, 'abst') => 'DESC',
            default => $order,
        };
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

    private function getOptionalValue(string $field): mixed
    {
        if (!$this->hasColumn($field)) {
            return null;
        }

        return $this->getValue($field);
    }

    private function hasColumn(string $field): bool
    {
        $table = rex::getTable('knowledgebase');
        if (!isset(self::$columnCache[$table])) {
            $columns = [];
            foreach (rex_sql::showColumns($table) as $column) {
                $name = (string) ($column['name'] ?? '');
                if ($name !== '') {
                    $columns[$name] = true;
                }
            }

            self::$columnCache[$table] = $columns;
        }

        return isset(self::$columnCache[$table][$field]);
    }
}