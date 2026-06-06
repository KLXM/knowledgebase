<?php

$knowledgebaseId = (int) 'REX_VALUE[1]';
$startSlug = trim((string) 'REX_VALUE[2]');

if ($knowledgebaseId <= 0) {
    echo '<div class="uk-alert-warning" uk-alert>Bitte im Modul eine Wissensbasis auswählen.</div>';
    return;
}

echo FriendsOfREDAXO\Knowledgebase\FrontendRenderer::render($knowledgebaseId, $startSlug);