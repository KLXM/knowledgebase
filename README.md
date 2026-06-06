# Knowledge Base

Ein REDAXO-Add-on zur Verwaltung und Ausgabe strukturierter Wissensinhalte mit Artikeln, Glossar, Suche und interaktiven Bildern.

## Projektstatus

- Status: aktive Entwicklung
- Frontend-Unterstützung: aktuell UIKit
- Weitere Frontend-Frameworks: geplant
- Eine spätere Überführung zu FriendsOfREDAXO ist nicht ausgeschlossen

## Funktionsumfang

- Verwaltung mehrerer Wissensbasen
- Artikel mit Navigation und Kapitelstruktur
- Glossar mit Verlinkung im Artikeltext
- Volltextsuche mit Trefferauszug
- Interaktive Bilder mit Marker-Editor im Backend

## Voraussetzungen

- REDAXO >= 5.18
- PHP >= 8.2
- yform >= 5.0
- yform_content_builder

Hinweis zu Abhängigkeiten:

Für den vollen Funktionsumfang werden Add-ons benötigt, die je nach Projektkontext nicht immer öffentlich verfügbar sind. Das betrifft insbesondere yform_content_builder in einer kompatiblen Projektversion.

## Installation

1. Add-on nach redaxo/src/addons/knowledgebase kopieren.
2. Abhängigkeiten installieren und aktivieren (yform, yform_content_builder).
3. Add-on im REDAXO-Backend installieren.
4. Update-Skripte ausführen, damit Tabellen, Indizes und Suchdaten konsistent sind.

Hinweis für Container-Setups:

- Nach Änderungen an PHP-Dateien ggf. den OPcache leeren.

## Backend-Bereiche

- Übersicht
- Frontend-Texte
- Wissensbasen
- Glossar
- Interaktive Bilder
- Beiträge

## Content Builder

Das Add-on registriert eigene Content-Builder-Elemente und ergänzt den vorhandenen Element-Pool.

Aktueller Fokus:

- UIKit-Ausgabe im Frontend
- Auswahl und Ausgabe wiederverwendbarer interaktiver Bilder

## Interaktive Bilder

Interaktive Bilder werden als eigene Datensätze gepflegt und anschließend in Artikeln referenziert.

### Funktionen

- Bild auswählen
- Marker per Klick setzen
- Marker per Drag positionieren
- Titel und Inhalt pro Marker pflegen
- Ausgabe als nummerierte Marker im Frontend
- Responsives Marker-Menü, mobil standardmäßig sichtbar
- Responsives Marker-Menü, im Desktop per Toggle einblendbar

## Suche

Die Suche arbeitet zweistufig:

1. Fulltext-Suche auf search_text (MATCH AGAINST)
2. Fallback mit LIKE auf title, nav_title, intro und search_text

### Aufbau von search_text

search_text wird beim Speichern oder Aktualisieren eines Artikels automatisch erzeugt aus:

- title
- nav_title
- intro
- extrahierten Textinhalten aus dem Content Builder

Damit werden auch textuelle Inhalte aus Content-Builder-Feldern berücksichtigt.

## Konfigurierbare Frontend-Texte

Frontend-Texte können pro Sprache im Backend-Bereich Frontend-Texte gepflegt werden.

## Einschränkungen

- Derzeit ist ausschließlich UIKit offiziell unterstützt
- Weitere Frameworks folgen schrittweise

## Perspektive

Das Add-on wird aktuell im projektspezifischen Einsatz weiterentwickelt. Eine spätere Übernahme beziehungsweise Überführung in den FriendsOfREDAXO-Kontext ist möglich.

## Lizenz

MIT License

Copyright (c) 2026 KLXM Crossmedia GmbH, im Auftrag der HAGE (hage.de)

Der vollständige Lizenztext befindet sich in LICENSE.md.
