# Redakteurs-Guide: Arbeiten mit der Knowledge Base

Dieser Leitfaden erklärt den redaktionellen Ablauf in einfacher Form.

## Ziel

Einen Beitrag so aufbauen, dass:

- die Kapitel-Navigation sauber funktioniert,
- das Inhaltsverzeichnis korrekt springt,
- Inhalte (Text, Bilder, interaktive Elemente) verständlich bleiben.

## Kurz erklärt: Wie die Struktur funktioniert

In der Knowledge Base werden Beiträge mit dem YForm Content Builder gepflegt.

Wichtig ist der Unterschied zwischen:

- Kapitelmarken: steuern Navigation und Inhaltsverzeichnis
- Inhaltselementen: enthalten den eigentlichen Content (Text, Bild, usw.)

Merke:
Nur Kapitelmarken erzeugen Kapitelpunkte. Normale Überschriften in Textelementen tun das nicht.

## Schritt-für-Schritt: Neuen Beitrag erstellen

1. Wissensbasis wählen
- Öffne das AddOn und wechsle in den Bereich „Beiträge“.
- Wähle zuerst die richtige Wissensbasis.

2. Beitrag anlegen
- Klicke auf „Hinzufügen (+)“.
- Pflichtfelder ausfüllen:
  - Wissensbasis
  - Titel

3. Kapitelstruktur zuerst setzen
- Füge als erstes Element eine Kapitelmarke ein (`kb_chapter_nav`).
- Vergib einen klaren Kapitelnamen.

4. Inhalte unter dem Kapitel ergänzen
- Füge danach Inhaltselemente ein, z. B.:
  - Text
  - Bild
  - weitere verfügbare Content-Builder-Elemente

5. Weitere Abschnitte aufbauen
- Für jeden neuen Abschnitt erneut eine Kapitelmarke setzen.
- Danach wieder passende Inhaltselemente ergänzen.

6. Speichern und prüfen
- Beitrag speichern.
- Im Frontend prüfen:
  - Sind alle Kapitel in der Navigation sichtbar?
  - Springen Links im Inhaltsverzeichnis zur richtigen Stelle?
  - Sind Bilder und Inhalte vollständig?

## Tags: wozu sie gut sind

Tags helfen bei der inhaltlichen Zuordnung und verbessern die Auffindbarkeit.

Nutzen von Tags:

- Beiträge zu Themenclustern gruppieren (z. B. „Installation", „API", „Fehlerbehebung")
- Suche verbessern, weil Tags in die Suchdaten einfließen
- Verwandte Inhalte leichter pflegen und wiederfinden

Empfehlungen für gute Tags:

- Lieber wenige, klare Tags statt viele ähnliche Varianten
- Einheitliche Schreibweise verwenden (z. B. immer „API", nicht gemischt mit „Api")
- Pro Beitrag nur wirklich passende Tags setzen

## Glossar: wie es funktioniert

Das Glossar ist für Begriffe gedacht, die im Projekt häufiger vorkommen und kurz erklärt werden sollen.

So arbeitest du mit dem Glossar:

1. Im AddOn den Bereich „Glossar" öffnen
2. Begriff anlegen
3. Kurze, präzise Erklärung hinterlegen
4. Beitrag speichern und Frontend prüfen

Wofür das Glossar gut ist:

- Fachbegriffe werden für Leserinnen und Leser verständlicher
- Wiederkehrende Erklärungen müssen nicht in jedem Beitrag neu geschrieben werden
- Inhalte bleiben konsistent, weil eine zentrale Definition gepflegt wird

Hinweis:
Je nach Ausgabe und Konfiguration können Glossarbegriffe im Frontend automatisch verlinkt oder hervorgehoben werden.

## Arbeiten mit interaktiven Bildern

Interaktive Bilder werden separat gepflegt:

1. Im AddOn in den Bereich „Interaktive Bilder“ wechseln
2. Bild und Marker anlegen
3. Marker sinnvoll beschriften (Titel + kurzer Erklärungstext)
4. Im Beitrag über das passende Content-Builder-Element einbinden

Wichtig für gute Usability:

- Marker-Texte bewusst kurz halten
- Pro Marker nur eine Kerninformation vermitteln
- Lange Erklärungen lieber in den Fließtext des Beitrags auslagern
- Marker so setzen, dass der Bezugspunkt im Bild eindeutig ist

Faustregel:
Ein Marker-Text sollte meist in 1 bis 3 kurzen Sätzen verständlich sein.

## Redaktionsregeln (empfohlen)

- Kapitelnamen kurz und eindeutig halten.
- Kapitel nicht als Styling-Mittel verwenden, nur für echte Abschnitte.
- Pro Abschnitt lieber wenige, klare Elemente statt langer Mischblöcke.
- Bei Bildern auf verständliche Kontexte und gute Reihenfolge achten.

## Häufige Fehler

1. Kapitel fehlen in der Navigation
- Ursache: Es wurden nur Überschriften im Text benutzt, aber keine Kapitelmarken.
- Lösung: `kb_chapter_nav`-Elemente an den Abschnittsanfang setzen.

2. Inhaltsverzeichnis wirkt unlogisch
- Ursache: Kapitelmarken sind unsauber benannt oder in falscher Reihenfolge.
- Lösung: Kapitelnamen und Reihenfolge überarbeiten.

3. Falscher Beitragskontext
- Ursache: Beitrag wurde ohne gesetzten Wissensbasis-Filter angelegt.
- Lösung: Vor dem Bearbeiten immer Wissensbasis auswählen.

## Checkliste vor Veröffentlichung

- Wissensbasis korrekt
- Titel verständlich
- Kapitelmarken vollständig
- Inhaltsverzeichnis korrekt
- Bilder/Elemente vollständig
- Beitrag auf online gesetzt (falls gewünscht)

## Hinweise zur Barrierefreiheit

Gute Inhalte sind für alle Menschen verständlich und bedienbar. Achte deshalb auf folgende Punkte:

### Überschriften und Struktur

- Verwende eine klare Reihenfolge der Überschriften (keine Sprünge ohne Grund)
- Nutze Kapitelmarken für echte Abschnitte, nicht nur für optische Effekte
- Halte Absätze eher kurz und thematisch sauber getrennt

### Bilder

- Verwende Bilder nur, wenn sie einen inhaltlichen Mehrwert haben
- Achte auf verständliche Bildkontexte im umgebenden Text
- Pflege nach Möglichkeit Alternativtexte/Bildbeschreibungen (z. B. im Medienkontext), damit Inhalte auch ohne Sicht verständlich bleiben
- Wenn Informationen wichtig sind, dürfen sie nicht ausschließlich im Bild stehen
- Vermeide Text in Bildern, wenn derselbe Inhalt als normaler Text möglich ist

### Links und Formulierungen

- Linktexte sollten aussagekräftig sein (nicht nur „hier klicken")
- Schreibe möglichst klar, konkret und in kurzen Sätzen
- Erkläre Fachbegriffe entweder direkt oder über das Glossar

### Tabellen und Elemente

- Tabellen nur für echte tabellarische Daten verwenden
- Komplexe Inhalte lieber in mehrere einfache Blöcke aufteilen
- Bei interaktiven Bildern kurze Markertexte verwenden und Zusatzinfos im Fließtext ergänzen
