<?php

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;

/**
 * Backend: Vorschau eines Beitrags im Frontend öffnen.
 *
 * Ermittelt die echte Adresse des Beitrags (URL-Profil oder Standard-Seite der Wissensbasis)
 * und leitet dorthin weiter – so stimmt die Vorschau auch für Unterseiten.
 *
 * Parameter: kb_preview_id (ID des Beitrags)
 */
class rex_api_knowledgebase_preview extends rex_api_function
{
    protected $published = false;

    public function execute(): rex_api_result
    {
        $user = rex::getUser();
        if (!$user || !$user->hasPerm('knowledgebase[articles]')) {
            throw new rex_api_exception('Keine Berechtigung für die Vorschau.');
        }

        $articleId = rex_request('kb_preview_id', 'int', 0);
        $article = $articleId > 0 ? rex_yform_manager_dataset::get($articleId, rex::getTable('knowledgebase_article')) : null;
        if (!$article) {
            throw new rex_api_exception('Beitrag nicht gefunden.');
        }

        $url = KnowledgebaseUrl::getPreviewUrl((int) $article->getValue('knowledgebase_id'), (string) $article->getValue('slug'));
        if ('' === $url) {
            throw new rex_api_exception('Für diese Wissensbasis ist keine Standard-Seite hinterlegt (Wissensbasen → „Standard-Seite (Vorschau)“).');
        }

        rex_response::cleanOutputBuffers();
        rex_response::sendRedirect($url);
    }
}
