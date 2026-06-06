<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\InteractiveImageRenderer;

$rawId = $elementData['interactive_image_id'] ?? 0;
$id = 0;

if (is_int($rawId) || is_float($rawId)) {
    $id = (int) $rawId;
} elseif (is_string($rawId)) {
    $trimmed = trim($rawId);
    if (ctype_digit($trimmed)) {
        $id = (int) $trimmed;
    } elseif (preg_match('/#?(\d+)/', $trimmed, $matches) === 1) {
        $id = (int) $matches[1];
    }
} elseif (is_array($rawId)) {
    $first = reset($rawId);
    if (is_scalar($first)) {
        $id = (int) $first;
    }
}

if ($id <= 0) {
    return;
}

echo InteractiveImageRenderer::renderById($id);
