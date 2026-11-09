<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_extension;
use rex_extension_point;
use rex_user;

/**
 * Wer darf welche Wissensbasis bearbeiten?
 *
 * Standard: Admins und alle mit dem Recht „knowledgebase[articles]“ – für jede Wissensbasis.
 * Eigene Regeln (z. B. je Wissensbasis) über den Extension Point KNOWLEDGEBASE_CAN_EDIT:
 *
 *     rex_extension::register('KNOWLEDGEBASE_CAN_EDIT', static function (rex_extension_point $ep): bool {
 *         return $ep->getSubject() && 2 !== $ep->getParam('knowledgebase_id');
 *     });
 */
final class KnowledgebaseAccess
{
    public static function canEdit(int $knowledgebaseId, ?rex_user $user = null): bool
    {
        $user ??= rex::getUser();
        if (!$user instanceof rex_user) {
            return false;
        }
        $allowed = $user->isAdmin() || $user->hasPerm('knowledgebase[articles]');

        return (bool) rex_extension::registerPoint(new rex_extension_point('KNOWLEDGEBASE_CAN_EDIT', $allowed, [
            'knowledgebase_id' => $knowledgebaseId,
            'user' => $user,
        ]));
    }
}
