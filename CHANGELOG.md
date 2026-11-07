# Changelog

## [0.9.12] - 2026-11-07

### Behoben
- **TinyMCE-Plugin „Knowledgebase-Link“: Abschnitts-Links sprangen nicht**: Das Plugin baute die Anker anders als die Ausgabe – ohne das Präfix `kb-chapter-` automatisch erzeugter Anker und ohne Nummerierung bei gleichen Titeln. Auf hage.de passte keiner der 89 Anker zur Seite; innerhalb der Wissensbasis fing das Skript es teilweise ab, von außen und bei gleichen Titeln nicht. Jetzt nutzt das Plugin dieselbe Logik wie die Ausgabe (`FrontendRenderer::chapterAnchorFor()`): alle Anker der Online-Beiträge passen. Bereits eingefügte Links funktionieren innerhalb der Wissensbasis weiter.

### Neu
- **Selbstreparierende Links aus dem Plugin**: Das Plugin fügt keinen festen Link mehr ein, sondern einen Verweis auf Beitrag und Kapitelanker per ID (`data-kb-article`, `data-kb-chapter`). Bei der Ausgabe wird daraus die aktuelle Adresse über das URL-Profil (Einstellungen → URL-Profile; ohne Profil die Fallback-Adresse) samt aktuellem Anker – auf derselben Domain als Pfad (`/kb/…/#…` statt `//hage.de/kb/…`). Links stimmen damit auch nach Umbenennen von Beitrag (Slug) oder Kapitel und nach Ändern des Profil-Artikels. Unabhängig vom URL-AddOn (`KnowledgebaseLinkService::rewriteContentLinks()`, `KnowledgebaseUrl::resolveContentLink()`).

### Verbessert
- Im Plugin-Menü „Abschnitt im Artikel“ (vorher „Anker im Artikel“) stehen die Kapitelnummern vor dem Titel, Unterabschnitte (H3/H4) sind eingerückt.

## [0.9.11] - 2026-11-07

### Neu
- **Layout „Handbuch kompakt“**: wie „Handbuch“ (links nur die Kapitel), die Abschnitte aber immer als aufklappbares Menü „In diesem Kapitel“ unter der Überschrift – auch auf dem Desktop, ohne Spalte rechts. Mehr Platz für den Text.

### Verbessert
- **Beiträge**: In der Leiste „Beiträge nach Wissensbasis“ öffnet „Wissensbasis im Frontend“ die Standard-Seite der gewählten Wissensbasis (sonst die Basis-Adresse des URL-Profils).
- **Handbuch-Layouts**: Die Suche in der Sidebar heißt „Kapitel suchen“ statt „Titel oder Unterkapitel“ (dort stehen nur Kapitel; Frontend-Text `knowledgebase_nav_filter_placeholder_pages`). „Alle aufklappen“ erscheint nur noch, wenn es aufklappbare Kapitel gibt.
- Die Auswahl der Wissensbasis nutzt den Selectpicker des Backends und wechselt beim Auswählen direkt; Label und Knöpfe stehen auf einer Linie.

## [0.9.10] - 2026-11-07

### Neu
- **Wissensbasen: Feld „Standard-Seite (Vorschau)“** (Artikelauswahl aus der Struktur). Nur eine Bearbeitungshilfe für die Vorschau – die Wissensbasis kann weiterhin auf beliebigen Artikeln ausgegeben werden. Leer = Artikel aus „URL-Profile“.
- **Vorschau-Buttons**: In der Beitragsliste „Vorschau“ (Aktionen) und beim Bearbeiten „Vorschau im Frontend“ in der oberen Leiste neben „Hauptseite anlegen“. Beide öffnen die echte Adresse des Beitrags in einem neuen Tab (URL-Profil, sonst Standard-Seite mit Parameter) – auch für Unterseiten. Weiterleitung über die Backend-API `knowledgebase_preview` (Recht `knowledgebase[articles]`).

### Geändert
- **Reiter neu geordnet** (passen in eine Zeile): Übersicht · Beiträge · Glossar · Interaktive Bilder · Wissensbasen · Einstellungen, rechts „Hilfe“ (vorher „Redakteurs-Guide“). Unter „Einstellungen“: Allgemein · URL-Profile & Sitemap · Frontend-Texte (neue Adressen `knowledgebase/settings/…`; Rechte unverändert).
- Der bisherige Button „Frontend öffnen“ baute die Adresse aus einer Vorlage (nur oberste Ebene, nur mit URL-Profil) und ist durch „Vorschau“ ersetzt.

## [0.9.9] - 2026-11-07

### Behoben
- **Update überschrieb Feldeinstellungen**: Der Tableset-Import bei Installation/Update setzte alle Feldeinstellungen der Wissensbasis-Tabellen auf die Vorgaben zurück, u. a. die im Projekt angepasste Auswahl „Erlaubte Elemente“ im Inhaltsfeld. Jetzt:
  - Das Tabellenschema wird nur noch bei Neuinstallation importiert oder wenn es sich im AddOn geändert hat (Prüfsumme in der Konfiguration, `tableset_hash`). Normale Updates lassen die Felder unberührt.
  - Muss importiert werden, bleiben bei bestehenden Feldern die Projekt-Einstellungen erhalten: „Erlaubte Elemente“, „In Liste ausblenden“, „Durchsuchbar“.
  - `kb_text` wird nur noch einmalig (Umstieg auf 0.9.7+) ergänzt; wer es danach abwählt, behält das.

## [0.9.8] - 2026-11-07

### Neu
- **Layout „Handbuch“** (Einstellung Layout-Modus): für lange Handreichungen mit vielen Abschnitten. Links nur die Kapitel (Beiträge), die Abschnitte eines Beitrags stehen in einer eigenen Übersicht „In diesem Kapitel“ – ab 1200 px als mitlaufende Spalte rechts, darunter als aufklappbarer Kasten unter der Überschrift. Der aktuelle Abschnitt ist markiert und bleibt sichtbar. Text der Überschrift: Frontend-Texte → `knowledgebase_toc_heading`.
- **Element „KB Kapitel / Überschrift“**: Option „Nummer auch in der Überschrift zeigen“ – die gewählte Kapitelnummer (Freitext wie „2.1“ oder Zahl) steht dann auch vor dem Titel im Inhalt (`.kb-chapter-nav__number`).
- **Element „KB Kapitel / Überschrift“**: Auswahl „Abstand davor“ (Standard / Mehr Abstand / Viel Abstand) für mehr Luft vor einem neuen Themenblock (`.kb-chapter-nav--space-medium|large`).

### Verbessert
- **Navigation links (Desktop)**: bleibt beim Lesen sichtbar (CSS `position: sticky` statt UIkit-Sticky) und hat eine eigene Scrollfläche (höchstens Fensterhöhe). Das Mausrad scrollt in der Navigation, nicht die ganze Seite; der aktuelle Abschnitt wird darin automatisch sichtbar gehalten.
- **Aktiver Eintrag**: ohne Fettschrift (die bisher Umbrüche verursachte), stattdessen Akzentfarbe, Hintergrund und Balken links. Einträge brechen nur an Wortgrenzen bzw. mit Silbentrennung um, nicht mehr mitten im Wort.
- **Layout „Fokus“ auf dem Desktop**: Die Navigation öffnet als Schublade vom Rand des Inhaltsbereichs (max. 360 px) statt als Off-Canvas über die ganze Bildschirmbreite. Schließen per Knopf, Esc oder Klick daneben; der Fokus springt in die Navigation und zurück zum Menü-Knopf. Auf dem Handy bleibt das Off-Canvas.
- **Off-Canvas (mobil)**: zeigt beim Öffnen den aktuellen Eintrag.

### Behoben
- **Aktiver Abschnitt folgte nicht dem Text**: Stand ein Kapitel-Anker in der Adresse, blieb dieses Kapitel markiert, egal wohin gescrollt wurde. Die Markierung folgt jetzt immer der Leseposition (am Seitenende: letzter Abschnitt).
- **Mobil lief der Inhalt rechts aus dem Bild** (Spalte `1fr` gab breiten Inhalten nach) – jetzt `minmax(0, 1fr)`.
- **Mobil überlappten Titel und Knöpfe im Kopf** – eine Regel für „bis 1280 px“ erzwang zwei gleich breite Spalten. Jetzt Titel links, Knöpfe rechts.

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
