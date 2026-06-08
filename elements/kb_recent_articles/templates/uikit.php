<?php
/**
 * KB Neueste Beiträge - UIkit Template
 *
 * @var array $elementData
 */

use FriendsOfREDAXO\Knowledgebase\FrontendContext;
use FriendsOfREDAXO\Knowledgebase\FrontendRenderer;

$heading = trim((string) ($elementData['heading'] ?? 'Neueste Beiträge'));
$intro = trim((string) ($elementData['intro'] ?? ''));
$limit = (int) ($elementData['limit'] ?? 5);
$limit = max(1, min(15, $limit));

$sortFieldRaw = trim((string) ($elementData['sort_field'] ?? 'updatedate'));
$sortField = in_array($sortFieldRaw, ['updatedate', 'createdate'], true) ? $sortFieldRaw : 'updatedate';

$sortOrderRaw = strtoupper(trim((string) ($elementData['sort_order'] ?? 'DESC')));
$sortOrder = 'ASC' === $sortOrderRaw ? 'ASC' : 'DESC';

$showDate = !empty($elementData['show_date']);
$emptyText = trim((string) ($elementData['empty_text'] ?? 'Noch keine Beiträge vorhanden.'));
if ($emptyText === '') {
    $emptyText = 'Noch keine Beiträge vorhanden.';
}

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

$context = FrontendContext::current();
$articles = [];
$articleParam = '';

if (is_array($context)) {
    $knowledgebaseId = (int) ($context['knowledgebase_id'] ?? 0);
    $articleParam = (string) ($context['article_param'] ?? '');

    if ($knowledgebaseId > 0) {
        $articles = \rex_data_knowledgebase_article::query()
            ->where('knowledgebase_id', $knowledgebaseId)
            ->where('online', 1)
            ->orderBy($sortField, $sortOrder)
            ->orderBy('priority', 'ASC')
            ->orderBy('title', 'ASC')
            ->limit($limit)
            ->find()
            ->toArray();
    }
}

$formatDate = static function (string $rawDate): string {
    $rawDate = trim($rawDate);
    if ($rawDate === '') {
        return '';
    }

    $timestamp = strtotime($rawDate);
    if ($timestamp === false) {
        return '';
    }

    return date('d.m.Y', $timestamp);
};

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_bg_image', $sectionBgImage, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>

<div class="kb-recent-articles">
    <?php if ($heading !== ''): ?>
        <h2 class="uk-h3 uk-margin-small-bottom"><?= rex_escape($heading) ?></h2>
    <?php endif; ?>

    <?php if ($intro !== ''): ?>
        <p class="uk-text-muted uk-margin-bottom"><?= rex_escape($intro) ?></p>
    <?php endif; ?>

    <?php if ($articles === []): ?>
        <div class="uk-alert-warning" uk-alert><?= rex_escape($emptyText) ?></div>
    <?php else: ?>
        <ul class="uk-list uk-list-divider">
            <?php foreach ($articles as $article): ?>
                <?php if (!$article instanceof \rex_data_knowledgebase_article) { continue; } ?>
                <?php
                $slug = trim((string) $article->getValue('slug'));
                if ($slug === '' || $articleParam === '') {
                    continue;
                }

                $url = FrontendRenderer::buildUrl([$articleParam => $slug]);
                $title = $article->getNavLabel();
                $dateField = $sortField === 'createdate' ? 'createdate' : 'updatedate';
                $dateText = $formatDate((string) $article->getValue($dateField));
                ?>
                <li>
                    <a class="uk-link-reset" href="<?= rex_escape($url) ?>">
                        <div class="uk-flex uk-flex-between uk-flex-middle uk-gap-small">
                            <strong><?= rex_escape($title) ?></strong>
                            <?php if ($showDate && $dateText !== ''): ?>
                                <span class="uk-text-meta"><?= rex_escape($dateText) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
