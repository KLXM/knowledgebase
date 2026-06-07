<?php

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;

/**
 * API-Endpoint: Gibt die aufgelöste URL für einen Knowledgebase-Artikel zurück.
 * Wird vom TinyMCE-Plugin genutzt, um saubere URLs vs. Fallback zu entscheiden.
 *
 * Parameter:
 *   kb_id   - int, Wissensbasis-ID
 *   slug    - string, Artikel-Slug
 *   anchor  - string, optional, Anker-Fragment
 *
 * Antwort JSON:
 *   { "url": "...", "source": "profile|fallback" }
 */
class rex_api_knowledgebase_url extends rex_api_function
{
    protected $published = true;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $kbId = rex_request('kb_id', 'int', 0);
        $slug = trim(rex_request('slug', 'string', ''));
        $anchor = trim(rex_request('anchor', 'string', ''));

        if ($kbId <= 0 || '' === $slug) {
            rex_response::sendJson(['error' => 'Missing parameters']);
            exit;
        }

        $hasProfile = KnowledgebaseUrl::hasProfile($kbId);
        $url = KnowledgebaseUrl::getArticleUrl($kbId, $slug, $anchor);

        rex_response::sendJson([
            'url' => $url,
            'source' => $hasProfile ? 'profile' : 'fallback',
        ]);

        exit;
    }
}
