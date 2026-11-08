<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase;

use rex;
use rex_media;
use rex_yrewrite;

/**
 * Kennzeichnet Links im Beitragsinhalt (Einstellungen → Links, beides standardmäßig aus):
 *
 * - PDF-Links: „(PDF, 1,2 MB)“ bei Dateien aus dem Medienpool, „(PDF)“ bei externen PDF-Dateien.
 *   Die Größe wird nur aus dem Medienpool gelesen, nie über das Netz.
 * - Externe Links: Symbol + für Screenreader „(externer Link)“, bei target="_blank" zusätzlich
 *   „öffnet in neuem Fenster“; externe PDFs bekommen außerdem „(PDF)“.
 *
 * Nicht gekennzeichnet werden Links mit Bild/Grafik (der Alt-Text beschreibt das Ziel, ein Zusatz würde das
 * Layout stören), Links ohne Text, Sprungmarken, mailto:/tel:, Glossar-Links und Links, deren Text schon
 * „PDF“ nennt.
 */
final class LinkMarker
{
    /** @var list<string>|null */
    private static ?array $internalHosts = null;

    public static function isActive(): bool
    {
        return AddonSettings::isLinkPdfInfoEnabled() || AddonSettings::isLinkExternalMarkerEnabled();
    }

    /**
     * @param bool|null $markPdf      null = Einstellung verwenden (Parameter für Tests/Vorschau)
     * @param bool|null $markExternal null = Einstellung verwenden
     */
    public static function enhance(string $html, ?bool $markPdf = null, ?bool $markExternal = null): string
    {
        $markPdf ??= AddonSettings::isLinkPdfInfoEnabled();
        $markExternal ??= AddonSettings::isLinkExternalMarkerEnabled();
        if ('' === trim($html) || (!$markPdf && !$markExternal) || false === stripos($html, '<a')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '~<a\b([^>]*)>(.*?)</a>~is',
            static function (array $m) use ($markPdf, $markExternal): string {
                [$full, $attributes, $inner] = $m;

                if (!preg_match('~\shref\s*=\s*(["\'])(.*?)\1~is', ' ' . $attributes, $hrefMatch)) {
                    return $full;
                }
                $href = html_entity_decode(trim($hrefMatch[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ('' === $href || preg_match('~^(#|mailto:|tel:|javascript:|data:)~i', $href)) {
                    return $full;
                }
                // Glossar-Links und bereits gekennzeichnete Links auslassen
                if (preg_match('~kb-app__glossary-link|data-kb-link-marked~i', $attributes)) {
                    return $full;
                }
                // Bild-/Grafik-Links: der Alt-Text beschreibt das Ziel, ein Zusatz würde das Layout stören
                if (preg_match('~<(img|svg|picture|figure|video|iframe)\b~i', $inner)) {
                    return $full;
                }
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ('' === $text) {
                    return $full;
                }

                $isExternal = self::isExternal($href);
                $isPdf = self::isPdf($href);
                $textNamesPdf = 1 === preg_match('~\bpdf\b~i', $text);
                $suffix = '';

                if ($isPdf && !$textNamesPdf && ($markPdf || ($markExternal && $isExternal))) {
                    $size = (!$isExternal && $markPdf) ? self::mediaSize($href) : '';
                    $label = FrontendI18n::msg('knowledgebase_link_pdf', 'PDF') . ('' !== $size ? ', ' . $size : '');
                    $suffix .= '<span class="kb-app__link-meta"> (' . rex_escape($label) . ')</span>';
                }

                if ($markExternal && $isExternal) {
                    $hidden = FrontendI18n::msg('knowledgebase_link_external', 'externer Link');
                    if (preg_match('~\starget\s*=\s*(["\'])_blank\1~i', ' ' . $attributes)) {
                        $hidden .= ', ' . FrontendI18n::msg('knowledgebase_link_new_window', 'öffnet in neuem Fenster');
                    }
                    $suffix .= self::externalIcon() . '<span class="uk-hidden-visually"> (' . rex_escape($hidden) . ')</span>';
                }

                if ('' === $suffix) {
                    return $full;
                }

                return '<a' . $attributes . ' data-kb-link-marked>' . $inner . $suffix . '</a>';
            },
            $html,
        );
    }

    private static function isPdf(string $href): bool
    {
        $path = (string) parse_url($href, PHP_URL_PATH);
        if (str_ends_with(strtolower($path), '.pdf')) {
            return true;
        }
        // Media Manager: index.php?rex_media_type=…&rex_media_file=datei.pdf
        $query = (string) parse_url($href, PHP_URL_QUERY);
        parse_str($query, $params);

        return isset($params['rex_media_file']) && is_string($params['rex_media_file'])
            && str_ends_with(strtolower($params['rex_media_file']), '.pdf');
    }

    private static function isExternal(string $href): bool
    {
        if (!preg_match('~^(https?:)?//~i', $href)) {
            return false; // relative Links sind intern
        }
        $host = strtolower((string) parse_url(str_starts_with($href, '//') ? 'https:' . $href : $href, PHP_URL_HOST));
        if ('' === $host) {
            return false;
        }

        return !in_array(preg_replace('~^www\.~', '', $host), self::internalHosts(), true);
    }

    /**
     * Hosts dieser Installation (alle YRewrite-Domains und der aktuelle Host), ohne „www.“.
     *
     * @return list<string>
     */
    private static function internalHosts(): array
    {
        if (null !== self::$internalHosts) {
            return self::$internalHosts;
        }
        $hosts = [];
        if (class_exists(rex_yrewrite::class)) {
            foreach (rex_yrewrite::getDomains() as $domain) {
                $hosts[] = strtolower((string) parse_url($domain->getUrl(), PHP_URL_HOST));
            }
        }
        $hosts[] = strtolower((string) parse_url(rex::getServer(), PHP_URL_HOST));
        $hosts[] = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        return self::$internalHosts = array_values(array_unique(array_filter(array_map(
            static fn (string $h): string => (string) preg_replace('~^www\.~', '', $h),
            $hosts,
        ))));
    }

    /** Größe einer Medienpool-Datei, z. B. „1,2 MB“; leer, wenn die Datei nicht im Medienpool liegt. */
    private static function mediaSize(string $href): string
    {
        $query = (string) parse_url($href, PHP_URL_QUERY);
        parse_str($query, $params);
        $file = isset($params['rex_media_file']) && is_string($params['rex_media_file'])
            ? $params['rex_media_file']
            : basename((string) parse_url($href, PHP_URL_PATH));
        $media = '' !== $file ? rex_media::get(rawurldecode($file)) : null;
        if (null === $media) {
            return '';
        }
        $bytes = (int) $media->getSize();
        if ($bytes <= 0) {
            return '';
        }
        if ($bytes < 1024 * 1024) {
            return max(1, (int) round($bytes / 1024)) . ' KB';
        }

        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    private static function externalIcon(): string
    {
        // Pfeil aus dem Kasten (wie „link-external“), Farbe folgt dem Linktext
        return '<svg class="kb-app__link-external" width="12" height="12" viewBox="0 0 20 20" aria-hidden="true" focusable="false">'
            . '<path fill="none" stroke="currentColor" stroke-width="1.6" d="M15 11v5.5a1 1 0 0 1-1 1H3.5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1H9"/>'
            . '<path fill="none" stroke="currentColor" stroke-width="1.6" d="M12.5 2.5h5v5M17.5 2.5 9 11"/></svg>';
    }
}
