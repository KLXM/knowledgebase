# Changelog

## [0.9.7] - 2026-10-11

### Neu
- **Element `kb_text`**: Fließtext eines Beitrags (TinyMCE) ohne eigene Section/Container. Bisher gab es im Inhaltsfeld kein reines Textelement – das Builder-Element `text` wird vom Content Builder nicht ausgegeben (keine `templates/*.php`). Wird bei Installation/Update im Inhaltsfeld erlaubt, auch bei angepasster Elementliste.
- **Farben komplett über CSS-Variablen**: alle ~40 fest eingetragenen Blautöne (Hintergründe, Rahmen, Links, Markierungen, Schatten) folgen jetzt `--kb-accent` / `--kb-accent-2` (abgeleitet per `color-mix`: `--kb-accent-soft`, `--kb-accent-line`, `--kb-accent-muted`), dunkle Navigationsschrift `--kb-ink`, das Orange der Hervorhebungen `--kb-highlight` / `--kb-highlight-soft`. Dazu `--kb-heading-font` und `--kb-heading-color` statt fester Georgia-Schrift. Wer nur `--kb-accent` setzt, bekommt eine einheitlich eingefärbte Ausgabe; der Standard sieht (bis auf minimal andere Zwischentöne) aus wie bisher.

### Behoben
- **AI Chat**: Der Content-Provider implementierte nur `FriendsOfRedaxo\KlxmChat\…\ContentProviderInterface`. Mit AI Chat führte das beim Speichern jedes YForm-Datensatzes zu einem Fatal Error („Interface … not found“), weil AI Chat den Provider lädt. Er implementiert jetzt die Schnittstelle des installierten Chat-AddOns (AI Chat oder KLXM Chat).
- **Beiträge per API anlegen** (`$dataset->save()`): Der Suchindex fragte im Event `YFORM_DATA_ADDED` die ID des noch nicht gespeicherten Datensatzes ab („Calling getId() on new, non-existing datasets“). Jetzt über die ID des Events; ebenso der Glossar-Cache.

### Verbessert
- **Glossar**: Ein Begriff wird je Beitrag nur beim ersten Vorkommen verlinkt (statt bei jedem). Alle Vorkommen: Config `glossary_link_all`.

## [0.9.6-dev] - 2026-06-08

### Behoben
- **Element „Neueste Beiträge“**: Datumsanzeige nutzt jetzt zuerst `updatedate` und fällt bei leerem/ungültigem Wert auf `createdate` zurück.
- **Datumsvalidierung**: Ungültige Werte wie leere Timestamps, `0000-00-00` oder unbrauchbare Datumswerte werden abgefangen.
- **Anzeigeproblem**: Verhindert falsche Ausgaben wie `30.11.-0001` im Frontend.

### Verbessert
- **Sortierung im Element**: Bei Sortierfeld `updatedate` wird `createdate` als Fallback-Sortierung genutzt.
- **Volltextsuche optional erweitert**: Neue Einstellung für mehrere Fundstellen-Kontexte pro Treffer statt nur eines einzelnen Teasers.
