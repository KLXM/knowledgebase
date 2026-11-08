<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\GlossaryService;

/**
 * Backend-API für den Editor-Knopf „Glossar“ (TinyMCE-Plugin knowledgebase_glossary).
 *
 * action=lookup  (GET)  kb_id, text  → vorhandener Eintrag (Begriff oder Schreibweise), Wissensbasen, Begriffe, Token
 * action=create  (POST) kb_id, term, definition, description, online
 * action=synonym (POST) id, text     → Schreibweise an bestehenden Eintrag anhängen
 *
 * Lesen: Recht „Beiträge“ oder „Glossar“. Schreiben: Recht „Glossar“.
 */
class rex_api_knowledgebase_glossary extends rex_api_function
{
    protected $published = false;

    private const CSRF_KEY = 'knowledgebase_glossary';

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        $user = rex::getUser();
        if (!$user || !($user->hasPerm('knowledgebase[glossary]') || $user->hasPerm('knowledgebase[articles]'))) {
            $this->send(['success' => false, 'message' => 'Keine Berechtigung.'], rex_response::HTTP_FORBIDDEN);
        }
        $canWrite = $user->isAdmin() || $user->hasPerm('knowledgebase[glossary]');

        $action = rex_request('action', 'string', 'lookup');
        if ('lookup' === $action) {
            $this->lookup($canWrite);
        }

        if (!$canWrite) {
            $this->send(['success' => false, 'message' => 'Für das Glossar fehlt die Berechtigung.'], rex_response::HTTP_FORBIDDEN);
        }
        if ('post' !== strtolower(rex_request_method()) || !rex_csrf_token::factory(self::CSRF_KEY)->isValid()) {
            $this->send(['success' => false, 'message' => 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden.'], rex_response::HTTP_FORBIDDEN);
        }

        match ($action) {
            'create' => $this->create($user),
            'synonym' => $this->addSynonym($user),
            default => $this->send(['success' => false, 'message' => 'Unbekannte Aktion.'], rex_response::HTTP_BAD_REQUEST),
        };

        exit;
    }

    private function lookup(bool $canWrite): never
    {
        $kbId = rex_request('kb_id', 'int', 0);
        $text = trim(rex_request('text', 'string', ''));

        $bases = [];
        foreach (rex_sql::factory()->getArray('SELECT id, title FROM ' . rex::getTable('knowledgebase') . ' ORDER BY title') as $row) {
            $bases[] = ['id' => (int) $row['id'], 'title' => (string) $row['title']];
        }
        if ($kbId <= 0 && 1 === count($bases)) {
            $kbId = $bases[0]['id'];
        }

        $terms = [];
        if ($kbId > 0) {
            foreach (rex_sql::factory()->getArray('SELECT id, term FROM ' . rex::getTable('knowledgebase_glossary') . ' WHERE knowledgebase_id = ? ORDER BY term', [$kbId]) as $row) {
                $terms[] = ['id' => (int) $row['id'], 'term' => (string) $row['term']];
            }
        }

        $this->send([
            'success' => true,
            'kb_id' => $kbId,
            'bases' => $bases,
            'terms' => $terms,
            'match' => $kbId > 0 && '' !== $text ? GlossaryService::findEntry($kbId, $text) : null,
            'can_write' => $canWrite,
            'token' => $canWrite ? rex_csrf_token::factory(self::CSRF_KEY)->getValue() : '',
            'edit_url' => rex_url::backendPage('knowledgebase/glossary', ['table_name' => rex::getTable('knowledgebase_glossary'), 'func' => 'edit'], false),
        ]);
    }

    private function create(rex_user $user): never
    {
        $kbId = rex_post('kb_id', 'int', 0);
        $term = trim((string) preg_replace('/\s+/u', ' ', rex_post('term', 'string', '')));
        if ($kbId <= 0 || '' === $term || mb_strlen($term) > 191) {
            $this->send(['success' => false, 'message' => 'Bitte Wissensbasis und Begriff angeben.'], rex_response::HTTP_BAD_REQUEST);
        }
        $existing = GlossaryService::findEntry($kbId, $term);
        if (null !== $existing) {
            $this->send(['success' => false, 'message' => '„' . $term . '“ steht schon im Glossar (' . $existing['term'] . ').', 'match' => $existing], '409 Conflict');
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('knowledgebase_glossary'));
        $sql->setValue('knowledgebase_id', $kbId);
        $sql->setValue('term', $term);
        $sql->setValue('definition', mb_substr(trim(rex_post('definition', 'string', '')), 0, 191));
        $sql->setValue('description', trim(rex_post('description', 'string', '')));
        $sql->setValue('online', rex_post('online', 'int', 1) === 1 ? 1 : 0);
        $this->setMeta($sql, $user, true);
        $sql->insert();
        GlossaryService::clearCache($kbId);

        $this->send(['success' => true, 'id' => (int) $sql->getLastId(), 'message' => '„' . $term . '“ ist jetzt im Glossar.']);
    }

    private function addSynonym(rex_user $user): never
    {
        $id = rex_post('id', 'int', 0);
        $text = trim((string) preg_replace('/\s+/u', ' ', rex_post('text', 'string', '')));
        $rows = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('knowledgebase_glossary') . ' WHERE id = ?', [$id]);
        if ([] === $rows || '' === $text) {
            $this->send(['success' => false, 'message' => 'Eintrag nicht gefunden.'], rex_response::HTTP_NOT_FOUND);
        }
        $row = $rows[0];
        $kbId = (int) $row['knowledgebase_id'];
        $existing = GlossaryService::findEntry($kbId, $text);
        if (null !== $existing) {
            $this->send(['success' => false, 'message' => '„' . $text . '“ gehört schon zu „' . $existing['term'] . '“.', 'match' => $existing], '409 Conflict');
        }

        $forms = GlossaryService::parseSynonyms((string) ($row['synonyms'] ?? ''), (string) $row['term']);
        $forms[] = $text;

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('knowledgebase_glossary'));
        $sql->setWhere(['id' => $id]);
        $sql->setValue('synonyms', implode(', ', $forms));
        $this->setMeta($sql, $user, false);
        $sql->update();
        GlossaryService::clearCache($kbId);

        $this->send(['success' => true, 'id' => $id, 'message' => '„' . $text . '“ ist jetzt eine Schreibweise von „' . $row['term'] . '“.']);
    }

    private function setMeta(rex_sql $sql, rex_user $user, bool $create): void
    {
        $columns = array_column(rex_sql::showColumns(rex::getTable('knowledgebase_glossary')), 'name');
        $now = date('Y-m-d H:i:s');
        if (in_array('updatedate', $columns, true)) {
            $sql->setValue('updatedate', $now);
        }
        if (in_array('updateuser', $columns, true)) {
            $sql->setValue('updateuser', $user->getLogin());
        }
        if ($create && in_array('createdate', $columns, true)) {
            $sql->setValue('createdate', $now);
        }
        if ($create && in_array('createuser', $columns, true)) {
            $sql->setValue('createuser', $user->getLogin());
        }
    }

    /** @param array<string, mixed> $data */
    private function send(array $data, string $status = rex_response::HTTP_OK): never
    {
        rex_response::setStatus($status);
        rex_response::sendJson($data);
        exit;
    }
}
