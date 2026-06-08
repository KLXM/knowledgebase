# Changelog

## [0.9.6-dev] - 2026-06-08

### Behoben
- **Element „Neueste Beiträge“**: Datumsanzeige nutzt jetzt zuerst `updatedate` und fällt bei leerem/ungültigem Wert auf `createdate` zurück.
- **Datumsvalidierung**: Ungültige Werte wie leere Timestamps, `0000-00-00` oder unbrauchbare Datumswerte werden abgefangen.
- **Anzeigeproblem**: Verhindert falsche Ausgaben wie `30.11.-0001` im Frontend.

### Verbessert
- **Sortierung im Element**: Bei Sortierfeld `updatedate` wird `createdate` als Fallback-Sortierung genutzt.
- **Volltextsuche optional erweitert**: Neue Einstellung für mehrere Fundstellen-Kontexte pro Treffer statt nur eines einzelnen Teasers.
