<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use rex;
use rex_extension_point;
use rex_file;
use rex_path;
use rex_sql;
use rex_string;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

final class GlossaryService
{
    /**
     * @param rex_extension_point<mixed> $ep
     */
    public static function handleYformEvent(rex_extension_point $ep): void
    {
        $table = $ep->getParam('table');
        if (!$table instanceof rex_yform_manager_table) {
            return;
        }

        $tableName = $table->getTableName();
        if ($tableName !== rex::getTable('knowledgebase_glossary') && $tableName !== rex::getTable('knowledgebase')) {
            return;
        }

        $dataset = $ep->getParam('data');
        if (!$dataset instanceof rex_yform_manager_dataset) {
            return;
        }

        if ($tableName === rex::getTable('knowledgebase_glossary')) {
            $knowledgebaseId = (int) $dataset->getValue('knowledgebase_id');
            if ($knowledgebaseId > 0) {
                self::clearCacheForKnowledgebase($knowledgebaseId);
            }
            return;
        }

        self::clearCacheForKnowledgebase((int) $dataset->getId());
    }

    public static function enhanceArticleHtml(\rex_data_knowledgebase $knowledgebase, string $html): string
    {
        if (!$knowledgebase->isGlossaryEnabled()) {
            return $html;
        }

        $terms = self::getTermsForKnowledgebase($knowledgebase->getId());
        if (count($terms) === 0) {
            return $html;
        }

        $normalizedMap = [];
        $termsForPattern = [];
        foreach ($terms as $term) {
            $normalized = self::normalizeTerm((string) $term['term']);
            if ($normalized === '' || isset($normalizedMap[$normalized])) {
                continue;
            }

            $normalizedMap[$normalized] = $term;
            $termsForPattern[] = preg_quote((string) $term['term'], '/');
        }

        if (count($termsForPattern) === 0) {
            return $html;
        }

        usort($termsForPattern, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
        $pattern = '/(?<![\\p{L}\\p{N}_-])(' . implode('|', $termsForPattern) . ')(?![\\p{L}\\p{N}_-])/ui';

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $loadResult = @$dom->loadHTML('<?xml encoding="utf-8" ?><div id="kb-glossary-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if ($loadResult !== true) {
            return $html;
        }

        $root = $dom->getElementById('kb-glossary-root');
        if (!$root instanceof DOMElement) {
            return $html;
        }

        $usedTerms = [];
        self::replaceTermsInNode($dom, $root, $pattern, $normalizedMap, $knowledgebase->getId(), $usedTerms);

        $result = '';
        foreach (iterator_to_array($root->childNodes) as $childNode) {
            $result .= $dom->saveHTML($childNode);
        }

        // Defensive fallback: malformed fragment HTML can cause DOMDocument
        // to drop trailing nodes while re-serializing. In that case, keep the
        // original builder output so later slices are not lost.
        if (self::isLikelyTruncatedResult($html, $result)) {
            $result = $html;
        }

        if (count($usedTerms) > 0) {
            $result .= self::renderModals($usedTerms, $knowledgebase->getId());
        }

        return $result;
    }

    private static function isLikelyTruncatedResult(string $originalHtml, string $processedHtml): bool
    {
        $originalLen = strlen(trim($originalHtml));
        if ($originalLen === 0) {
            return false;
        }

        $processedLen = strlen(trim($processedHtml));
        if ($processedLen === 0) {
            return true;
        }

        // Glossary replacement normally keeps or increases length.
        // A strong shrink is a robust indicator of parser truncation.
        return $processedLen < (int) floor($originalLen * 0.7);
    }

    /**
     * @param array<string, array{id:int,term:string,definition:string,description:string}> $normalizedMap
     * @param array<int, array{id:int,term:string,definition:string,description:string}> $usedTerms
     */
    private static function replaceTermsInNode(DOMDocument $dom, DOMNode $node, string $pattern, array $normalizedMap, int $knowledgebaseId, array &$usedTerms): void
    {
        if ($node instanceof DOMElement && self::isExcludedElement($node)) {
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $childNode) {
            if ($childNode instanceof DOMText) {
                self::replaceTextNode($dom, $childNode, $pattern, $normalizedMap, $knowledgebaseId, $usedTerms);
                continue;
            }

            self::replaceTermsInNode($dom, $childNode, $pattern, $normalizedMap, $knowledgebaseId, $usedTerms);
        }
    }

    /**
     * @param array<string, array{id:int,term:string,definition:string,description:string}> $normalizedMap
     * @param array<int, array{id:int,term:string,definition:string,description:string}> $usedTerms
     */
    private static function replaceTextNode(DOMDocument $dom, DOMText $textNode, string $pattern, array $normalizedMap, int $knowledgebaseId, array &$usedTerms): void
    {
        $text = $textNode->wholeText;
        if (trim($text) === '') {
            return;
        }

        $replaced = preg_replace_callback(
            $pattern,
            static function (array $matches) use ($normalizedMap, $knowledgebaseId, &$usedTerms): string {
                $matchedText = (string) ($matches[1] ?? '');
                $normalized = self::normalizeTerm($matchedText);
                if (!isset($normalizedMap[$normalized])) {
                    return $matchedText;
                }

                $term = $normalizedMap[$normalized];
                $termId = (int) $term['id'];
                $modalId = self::buildModalId($knowledgebaseId, $termId);

                $usedTerms[$termId] = $term;

                return '<a href="#' . self::escapeAttribute($modalId) . '" class="kb-app__glossary-link" data-kb-glossary-link="1" uk-toggle>' . self::escapeText($matchedText) . '</a>';
            },
            $text,
        );

        if (!is_string($replaced) || $replaced === $text) {
            return;
        }

        $fragmentDom = new DOMDocument('1.0', 'UTF-8');
        $loaded = @$fragmentDom->loadHTML('<?xml encoding="utf-8" ?><div id="kb-glossary-fragment">' . $replaced . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if ($loaded !== true) {
            return;
        }

        $container = $fragmentDom->getElementById('kb-glossary-fragment');
        if (!$container instanceof DOMElement) {
            return;
        }

        $fragment = $dom->createDocumentFragment();
        foreach (iterator_to_array($container->childNodes) as $newNode) {
            $fragment->appendChild($dom->importNode($newNode, true));
        }

        if (!$fragment->hasChildNodes() || !$textNode->parentNode instanceof DOMNode) {
            return;
        }

        $textNode->parentNode->replaceChild($fragment, $textNode);
    }

    /**
     * @param array<int, array{id:int,term:string,definition:string,description:string}> $usedTerms
     */
    private static function renderModals(array $usedTerms, int $knowledgebaseId): string
    {
        $html = '';

        foreach ($usedTerms as $term) {
            $termId = (int) $term['id'];
            $modalId = self::buildModalId($knowledgebaseId, $termId);
            $title = trim((string) $term['term']);
            $description = trim((string) $term['description']);
            $definition = trim((string) $term['definition']);

            $body = '';
            if ($description !== '') {
                $body = rex_string::sanitizeHtml($description);
            } elseif ($definition !== '') {
                $body = '<p>' . nl2br(self::escapeText($definition)) . '</p>';
            }

            if ($body === '') {
                continue;
            }

            $html .= '<div id="' . self::escapeAttribute($modalId) . '" uk-modal>';
            $html .= '<div class="uk-modal-dialog uk-modal-body kb-app__glossary-modal">';
            $html .= '<button class="uk-modal-close-default" type="button" uk-close></button>';
            $html .= '<h3 class="uk-modal-title kb-app__glossary-modal-title">' . self::escapeText($title) . '</h3>';
            $html .= '<div class="kb-app__glossary-modal-body">' . $body . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @return list<array{id:int,term:string,definition:string,description:string}>
     */
    public static function getTermsForKnowledgebase(int $knowledgebaseId): array
    {
        if ($knowledgebaseId <= 0) {
            return [];
        }

        $cacheFile = self::getCacheFile($knowledgebaseId);
        $cached = rex_file::getCache($cacheFile, null);
        if (is_array($cached)) {
            /** @var list<array{id:int,term:string,definition:string,description:string}> $cached */
            return $cached;
        }

        $sql = rex_sql::factory();
        $rows = $sql->getArray(
            'SELECT id, term, definition, description FROM ' . rex::getTable('knowledgebase_glossary') . ' WHERE knowledgebase_id = :knowledgebase_id AND online = 1',
            ['knowledgebase_id' => $knowledgebaseId],
        );

        $terms = [];
        foreach ($rows as $row) {
            $term = trim((string) ($row['term'] ?? ''));
            if ($term === '') {
                continue;
            }

            $terms[] = [
                'id' => (int) ($row['id'] ?? 0),
                'term' => $term,
                'definition' => trim((string) ($row['definition'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }

        usort($terms, static fn (array $left, array $right): int => strlen($right['term']) <=> strlen($left['term']));

        rex_file::putCache($cacheFile, $terms);

        return $terms;
    }

    private static function clearCacheForKnowledgebase(int $knowledgebaseId): void
    {
        if ($knowledgebaseId <= 0) {
            return;
        }

        rex_file::delete(self::getCacheFile($knowledgebaseId));
    }

    private static function getCacheFile(int $knowledgebaseId): string
    {
        return rex_path::addonCache('knowledgebase', 'glossary_' . $knowledgebaseId . '.cache');
    }

    private static function normalizeTerm(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower(trim($value), 'UTF-8');
        }

        return strtolower(trim($value));
    }

    private static function isExcludedElement(DOMElement $element): bool
    {
        $tagName = strtolower($element->tagName);
        $excludedTags = ['a', 'button', 'script', 'style', 'svg', 'code', 'pre', 'dfn', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        if (in_array($tagName, $excludedTags, true)) {
            return true;
        }

        return $element->hasAttribute('data-kb-glossary-lock');
    }

    private static function buildModalId(int $knowledgebaseId, int $termId): string
    {
        return 'kb-glossary-modal-' . $knowledgebaseId . '-' . $termId;
    }

    public static function getModalId(int $knowledgebaseId, int $termId): string
    {
        return self::buildModalId($knowledgebaseId, $termId);
    }

    /**
     * @param array<int, array{id:int,term:string,definition:string,description:string}> $terms
     */
    public static function renderGlossaryModals(array $terms, int $knowledgebaseId): string
    {
        return self::renderModals($terms, $knowledgebaseId);
    }

    private static function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
