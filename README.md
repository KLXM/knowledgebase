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
- builder

Hinweis zu Abhängigkeiten:

Für den vollen Funktionsumfang werden Add-ons benötigt, die je nach Projektkontext nicht immer öffentlich verfügbar sind. Das betrifft insbesondere builder in einer kompatiblen Projektversion.

## Installation

1. Add-on nach redaxo/src/addons/knowledgebase kopieren.
2. Abhängigkeiten installieren und aktivieren (yform, builder).
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

Treffer können optional mit dem Hinweis „Kürzlich aktualisiert“ markiert werden. Der Zeitraum ist in den AddOn-Einstellungen konfigurierbar (Standard: 14 Tage).

### Aufbau von search_text

search_text wird beim Speichern oder Aktualisieren eines Artikels automatisch erzeugt aus:

1. title
2. nav_title
3. intro
4. extrahierten Textinhalten aus dem Content Builder
5. Tags

Damit werden auch textuelle Inhalte aus Content-Builder-Feldern berücksichtigt.

## Integration in KLXM Chat (Content-Provider)

Die Knowledgebase kann Inhalte für den KLXM-Chat-Index bereitstellen. Dafür registriert sich das Addon selbst über den Extension-Point `KLXMCHAT_CONTENT_PROVIDERS`.

### Vollständiges Registrierungsbeispiel in boot.php

```php
if (rex_addon::exists('klxmchat') && rex_addon::get('klxmchat')->isAvailable()) {
	rex_extension::register('KLXMCHAT_CONTENT_PROVIDERS', static function (rex_extension_point $ep): array {
		$providers = $ep->getSubject();
		if (!is_array($providers)) {
			$providers = [];
		}

		if (class_exists(\FriendsOfRedaxo\KlxmChat\ContentProvider\ContentProviderInterface::class)) {
			$providers['knowledgebase'] = new \FriendsOfREDAXO\Knowledgebase\ContentProvider\KnowledgebaseContentProvider();
		}

		return $providers;
	});
}
```

### API-Erklärung: Was der Provider liefern muss

Die Klasse muss `FriendsOfRedaxo\KlxmChat\ContentProvider\ContentProviderInterface` implementieren.

Pflichtmethoden und Zweck:

- `getKey()`
  - technischer, stabiler Schlüssel; für Knowledgebase `knowledgebase`
- `getLabel()`
  - Name in den KLXM-Chat-Einstellungen
- `isAvailable()`
  - prüft Abhängigkeiten (z.B. eigenes Addon aktiv)
- `getSupportedSourceTypes()`
  - liefert die vom Provider erzeugten Source-Types
- `getSourceTypeLabels()`
  - lesbare Labels für Filter und Backend-Listen
- `collectTasks()`
  - sammelt indexierbare Einträge als Task-Liste
- `prepareDocument(array $task)`
  - baut aus einem Task das finale Index-Dokument
- `getPromptInstruction()`
  - ergänzt provider-spezifische Anweisung für die Antwortqualität
- `getSearchIconSvg(string $sourceType)`
  - optionales Icon je Source-Type

### Vollständiges Provider-Beispiel

```php
<?php

declare(strict_types=1);

namespace FriendsOfREDAXO\Knowledgebase\ContentProvider;

use FriendsOfREDAXO\Knowledgebase\KnowledgebaseUrl;
use FriendsOfREDAXO\Knowledgebase\SearchTextExtractor;
use FriendsOfRedaxo\KlxmChat\ContentProvider\ContentProviderInterface;
use rex;
use rex_addon;

final class KnowledgebaseContentProvider implements ContentProviderInterface
{
	public function getKey(): string
	{
		return 'knowledgebase';
	}

	public function getLabel(): string
	{
		return 'Knowledgebase Beiträge indexieren';
	}

	public function isAvailable(): bool
	{
		return rex_addon::exists('knowledgebase') && rex_addon::get('knowledgebase')->isAvailable();
	}

	public function getSupportedSourceTypes(): array
	{
		return ['knowledgebase_article'];
	}

	public function getSourceTypeLabels(): array
	{
		return ['knowledgebase_article' => 'Knowledgebase'];
	}

	public function getPromptInstruction(): string
	{
		return 'Knowledgebase enthält redaktionelle Hilfetexte und Anleitungen.';
	}

	public function getSearchIconSvg(string $sourceType): string
	{
		return $sourceType === 'knowledgebase_article' ? '<svg viewBox="0 0 24 24"><path d="..."/></svg>' : '';
	}

	public function collectTasks(): array
	{
		if (!$this->isAvailable()) {
			return [];
		}

		$sql = rex_sql::factory();
		$rows = $sql->getArray(
			'SELECT a.id, a.knowledgebase_id, a.title, a.nav_title, a.updatedate, a.createdate '
			. 'FROM ' . rex::getTable('knowledgebase_article') . ' a '
			. 'INNER JOIN ' . rex::getTable('knowledgebase') . ' b ON b.id = a.knowledgebase_id '
			. 'WHERE a.online = 1 AND b.online = 1'
		);

		$tasks = [];
		foreach ($rows as $row) {
			$articleId = (int) ($row['id'] ?? 0);
			$knowledgebaseId = (int) ($row['knowledgebase_id'] ?? 0);
			if ($articleId <= 0 || $knowledgebaseId <= 0) {
				continue;
			}

			$title = trim((string) ($row['nav_title'] ?? ''));
			if ($title === '') {
				$title = trim((string) ($row['title'] ?? ''));
			}

			$tasks[] = [
				'type' => 'provider_item',
				'provider' => $this->getKey(),
				'source_type' => 'knowledgebase_article',
				'source_id' => $knowledgebaseId . ':' . $articleId,
				'knowledgebase_id' => $knowledgebaseId,
				'article_id' => $articleId,
				'title' => $title !== '' ? $title : ('Knowledgebase Beitrag #' . $articleId),
				'updatedate_ts' => time(),
			];
		}

		return $tasks;
	}

	public function prepareDocument(array $task): ?array
	{
		if (!$this->isAvailable()) {
			return null;
		}

		$knowledgebaseId = (int) ($task['knowledgebase_id'] ?? 0);
		$articleId = (int) ($task['article_id'] ?? 0);
		if ($knowledgebaseId <= 0 || $articleId <= 0) {
			return null;
		}

		$article = \rex_data_knowledgebase_article::get($articleId);
		$knowledgebase = \rex_data_knowledgebase::findOnlineById($knowledgebaseId);
		if (!$article instanceof \rex_data_knowledgebase_article || !$knowledgebase instanceof \rex_data_knowledgebase) {
			return null;
		}

		$title = $article->getNavLabel();
		if ($title === '') {
			$title = trim((string) $article->getValue('title'));
		}

		$intro = trim((string) $article->getValue('intro'));
		$content = SearchTextExtractor::extractFromContentBuilder((string) $article->getValue('content'));

		$docText = trim(implode("\n", array_filter([
			'Wissensbasis: ' . trim((string) $knowledgebase->getValue('title')),
			'Titel: ' . $title,
			$intro !== '' ? 'Einleitung: ' . SearchTextExtractor::normalize($intro) : '',
			$content !== '' ? 'Inhalt: ' . $content : '',
		])));

		if ($docText === '') {
			return null;
		}

		$slug = trim((string) $article->getValue('slug'));
		$url = '';
		if ($slug !== '' && KnowledgebaseUrl::hasProfile($knowledgebaseId)) {
			$url = KnowledgebaseUrl::getArticleUrl($knowledgebaseId, $slug);
		}

		return [
			'source_type' => 'knowledgebase_article',
			'source_id' => $knowledgebaseId . ':' . $articleId,
			'title' => $title !== '' ? $title : ('Knowledgebase Beitrag #' . $articleId),
			'content' => $docText,
			'url' => $url,
			'updatedate_ts' => time(),
		];
	}
}
```

### Wichtige Regeln für URL-Felder

- URL nur setzen, wenn eine stabile öffentliche URL aufgelöst werden kann.
- In Knowledgebase bedeutet das: ohne URL-Profil keine URL ausgeben.

### Aktivierung in KLXM Chat

- Nach Registrierung erscheint der Provider in KLXM Chat unter Zusätzliche Content-Provider.
- Indexierung erfolgt erst, wenn der Provider dort aktiviert und gespeichert wurde.

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
