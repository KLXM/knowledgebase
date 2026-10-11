<?php

declare(strict_types=1);

$text = trim((string) ($elementData['text'] ?? ''));
if ('' === trim(strip_tags($text, '<img><iframe><video><table>'))) {
    return;
}
?>
<div class="kb-text">
    <?= $text ?>
</div>
