<?php

declare(strict_types=1);

use FriendsOfREDAXO\Knowledgebase\InteractiveImageRenderer;

/**
 * @param mixed $rawValue
 */
$extractId = static function ($rawValue) use (&$extractId): int {
    if (is_int($rawValue) || is_float($rawValue)) {
        return (int) $rawValue;
    }

    if (is_string($rawValue)) {
        $trimmed = trim($rawValue);
        if ('' === $trimmed) {
            return 0;
        }

        if (ctype_digit($trimmed)) {
            return (int) $trimmed;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $extractId($decoded);
        }

        if (preg_match('/#?(\d+)/', $trimmed, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    if (is_array($rawValue)) {
        foreach (['id', 'value', 'interactive_image_id', 0] as $candidateKey) {
            if (array_key_exists($candidateKey, $rawValue)) {
                $candidateId = $extractId($rawValue[$candidateKey]);
                if ($candidateId > 0) {
                    return $candidateId;
                }
            }
        }

        foreach ($rawValue as $candidateValue) {
            $candidateId = $extractId($candidateValue);
            if ($candidateId > 0) {
                return $candidateId;
            }
        }
    }

    return 0;
};

$rawId = $elementData['interactive_image_id']
    ?? $elementData['interactive_image']
    ?? $elementData['image_id']
    ?? $elementData['asset_id']
    ?? 0;

$id = $extractId($rawId);

if ($id <= 0) {
    return;
}

echo InteractiveImageRenderer::renderById($id);
