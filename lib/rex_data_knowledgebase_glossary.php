<?php

declare(strict_types=1);

/**
 * @method int getId()
 */
class rex_data_knowledgebase_glossary extends rex_yform_manager_dataset
{
    protected static string $table = 'rex_knowledgebase_glossary';

    /**
     * @return rex_yform_manager_collection<static>
     */
    public static function findOnlineByKnowledgebase(int $knowledgebaseId): rex_yform_manager_collection
    {
        return self::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('online', 1)
            ->orderBy('term')
            ->find();
    }
}
