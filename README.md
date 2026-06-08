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
- fields >= 1.4.0
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

## URL-Addon-Integration: automatische URL-Profil-Generierung

Ja, das Add-on kann URL-Profile automatisch für Wissensbasen erzeugen und synchron halten. Damit entstehen saubere Frontend-URLs ohne manuelle Profilpflege pro Datensatz.

### Voraussetzungen

- url Add-on (v2) aktiv
- Eine Seite, auf der das Knowledgebase-Modul eingebunden ist

### Was automatisch passiert

1. Im Backend-Bereich "URL-Profile & Sitemap" wird eine Wissensbasis einem REDAXO-Artikel (Modul-Seite) zugeordnet.
2. Beim Speichern wird automatisch ein Profil mit Namespace `knowledgebase_{id}` angelegt oder aktualisiert.
3. Das Profil wird auf die jeweilige Wissensbasis eingegrenzt (`knowledgebase_id = {id}`).
4. URLs werden direkt neu aufgebaut.
5. Beim Entfernen einer Zuordnung wird das zugehörige Profil wieder gelöscht.

### Zusätzlich erzeugte Routen

Neben den Artikel-URLs erzeugt das Add-on auch eigene Pfade für zentrale Ansichten:

- Glossar: `/.../glossar/`
- Inhaltsverzeichnis: `/.../inhaltsverzeichnis/`
- Suche: `/.../suche/?q=...`

Die Suche verwendet bei aktivem Profil automatisch den sauberen Suchpfad und nicht mehr die alten `kb_*`-Queryparameter.

### Wichtige Regel

Ein REDAXO-Artikel darf nur einer Wissensbasis zugeordnet werden. Doppelte Zuordnungen werden serverseitig validiert und mit Fehlermeldung abgewiesen.

## Troubleshooting

### TinyMCE: automatischer Sprung zum Editor im Backend

In einzelnen REDAXO-Backend-Formularen kann es vorkommen, dass der Cursor beim Laden automatisch in den ersten TinyMCE-Editor gesetzt wird. Dadurch springt die Seite sofort nach unten.

Einordnung:

- Das ist in der Regel kein isolierter Fehler eines einzelnen Knowledge-Base-Elements.
- Meist ist es ein Zusammenspiel aus TinyMCE-Initialfokus, Formularaufbau und Browser-Scrollverhalten.

Lösung im Add-on:

- Für `knowledgebase/articles` (Ansicht `func=add`) wird ein gezielter Fokus-Schutz geladen.
- Der initiale Editor-Fokus wird abgefangen, damit der Einstieg im Formular oben bleibt.

Wichtig bei neuen Asset-Dateien:

- Nach neuen Dateien unter `redaxo/src/addons/knowledgebase/assets` müssen die öffentlichen Add-on-Assets synchronisiert werden.
- Wenn die Datei nicht unter `public/assets/addons/knowledgebase` verfügbar ist, kann der Fix im Backend scheinbar "nicht greifen".

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

1. Gewichtete Fulltext-Suche auf title, nav_title, intro und search_text (MATCH AGAINST)
2. Fallback mit LIKE auf title, nav_title, intro, search_text und content

### Aufbau von search_text

search_text wird beim Speichern oder Aktualisieren eines Artikels automatisch erzeugt aus:

1. title
2. nav_title
3. intro
4. extrahierten Textinhalten aus dem Content Builder
5. Tags

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
