<?php

declare(strict_types=1);

class rex_api_knowledgebase_search extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $knowledgebaseId = rex_request('knowledgebase_id', 'int', 0);
        $query = trim((string) rex_request('q', 'string', ''));
        $limit = max(1, min(20, rex_request('limit', 'int', 8)));

        if ($knowledgebaseId <= 0 || '' === $query) {
            rex_response::sendJson(['results' => []]);
            exit;
        }

        $knowledgebase = rex_data_knowledgebase::findOnlineById($knowledgebaseId);
        if (!$knowledgebase instanceof rex_data_knowledgebase) {
            rex_response::sendJson(['results' => []]);
            exit;
        }

        $results = call_user_func(['FriendsOfREDAXO\\Knowledgebase\\SearchService', 'search'], $knowledgebaseId, $query, $limit);

        rex_response::sendJson(['results' => $results]);
        exit;
    }
}