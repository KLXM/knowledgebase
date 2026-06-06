<?php

declare(strict_types=1);

class rex_api_knowledgebase_links extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $tree = \FriendsOfREDAXO\Knowledgebase\KnowledgebaseLinkService::getTree();

        rex_response::sendJson([
            'success' => true,
            'tree' => $tree,
        ]);
        exit;
    }
}
