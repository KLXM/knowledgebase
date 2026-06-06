# Cards: Layout-Baukasten

Das Element **Cards** erzeugt ein flexibles Karten-Grid mit Bild/Video, Text, Badge, Link und optionalen Animationen.

## 1. Allgemeine Block-Einstellungen

Diese Einstellungen findest du ueber den Button **Allgemeine Block-Einstellungen**.

### Grid

- **Spalten (Desktop / Tablet / Mobile)**: Steuert, wie viele Karten pro Zeile angezeigt werden.
- **Abstand zwischen Cards**: `collapse` (kein Abstand), `column-collapse` (kein Abstand links/rechts), `small`, `medium`, `large`.
- **Gleiche Hoehe fuer alle Cards**: Sinnvoll bei unterschiedlich langen Texten.

### Karten-Stil

- **Karten-Farbe**: Globaler Stil fuer alle Cards.
- **Card Padding**: `small`, `default`, `large`.
- **Schatten**: Globaler Schattenstil.

### Sektion

- **Sektions-Hintergrund**: Hintergrundfarbe fuer den gesamten Block.
- **Sektions-Hintergrund (Bild/Video)**: Optionales Hintergrundmedium fuer den Block.
- **Sektions-Padding**: Vertikale/innere Abstaende fuer den gesamten Abschnitt.
- **Container-Breite**: Begrenzte oder volle Breite.

### Animationen (UIkit)

- **Animationen aktivieren**: Schaltet Animationen global ein.
- **ScrollSpy aktivieren**: Animation startet erst beim Scrollen in den Viewport.
- **Animationsverzoegerung (ms)**: Abstand zwischen Animationen.
- **Animationen wiederholen**: Animation bei erneutem Einblenden erneut starten.
- **Kaskadierende Verzoegerung**: Jede folgende Card startet mit zusaetzlicher Verzoegerung.

## 2. Cards bearbeiten

Im Repeater **Cards** legst du einzelne Karten an.

### Direkt sichtbare Felder pro Card

- **Layout**: `media-top`, `media-bottom`, `media-left`, `media-right`, `media-overlay`.
- **Karten-Farbe**: Optionaler Override gegenueber der globalen **Karten-Farbe**.
- **Hinweis bei leerem Wert**: *Geerbt (Globale Einstellung)*.
- **Animation**: Card-spezifische Animation.
- **Bild oder Video**: Medium aus dem Medienpool.
- **Titel / Untertitel / Text**: Hauptinhalt der Karte.

## 3. Modal: Medieneinstellungen

Oeffne **Medieneinstellungen** pro Karte, um Details fuer Bild/Video zu steuern:

- **Alt-Text** und **Dekoratives Bild**
- **Bildunterschrift**
- **Medien-Breite** (bei links/rechts Layout)
- **Seitenverhaeltnis**
- **Lightbox**
- **Cover-Modus**
- **Video-Darstellung** und **Video-Steuerung**

Hinweis: Bei aktivierter kompletter Card-Verlinkung sollte das Bild in der Regel als dekorativ markiert sein.

## 4. Modal: Layout-Einstellungen

Hier steuerst du card-spezifische Layoutdetails:

- **Breite Mobil / Tablet / Desktop**
- **Badge** + **Badge-Farbe**
- **Vertikale Ausrichtung** bei horizontalen Layouts
- **Schatten (ueberschreiben)**

Wichtig: Das Modal wird im Repeater direkt nach dem Feld **Layout** angeboten.

## 5. Modal: Verlinkung

Fuer jede Card kannst du einen Link konfigurieren:

- **Link-Typ**: Kein Link, Externe URL oder Interne Seite
- **Externe URL** oder **Interne Seite** (je nach Link-Typ)
- **Link-Text**
- **Button-Stil**
- **Button-Ausrichtung**
- **Gesamte Card verlinken**

Wichtig: Das Modal wird im Repeater direkt nach dem Feld **Karten-Farbe** angeboten.

## 6. Optionales Modal: Extras

Wenn in deiner Installation Zusatzfelder ueber `CardsRepeaterExtra` bereitgestellt werden, erscheint zusaetzlich ein Modal **Extras** pro Karte.

- Dieses Modal ist optional und nur sichtbar, wenn Extra-Felder konfiguriert sind.
- Typische Inhalte sind projektspezifische Zusatzoptionen.

Empfehlung:

- Bei teaserartigen Karten ist **Gesamte Card verlinken** oft die beste UX.
- Fuer redaktionelle Flexibilitaet kann stattdessen ein klassischer Button genutzt werden.

## 7. Praktische Empfehlungen

- Nutze fuer ruhige Layouts einheitliche Medienformate und aktiviere **Gleiche Hoehe fuer alle Cards**.
- Bei vielen Cards lieber kleine bis mittlere Abstaende waehlen.
- Animationen sparsam einsetzen und Verzoegerungen nicht zu hoch setzen.
- Fuer mobile Geraete meist **1 Spalte** waehlen.
