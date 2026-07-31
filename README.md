# FIDE-Elo-Listen für Contao

Verwaltet FIDE-Elo-Listen im Contao-Backend und gibt sie im Frontend aus. Jede
Liste entspricht einer Monatsliste der FIDE und enthält die deutschen Spieler mit
ihren Wertungszahlen für klassisches Schach, Schnellschach und Blitzschach. Die
Ratingliste der FIDE wird als XML direkt im Backend importiert.

## Voraussetzungen ##

* PHP 7.4 oder neuer
* Contao 4.13 oder Contao 5

## Installation ##

Über den Contao Manager das Paket `schachbulle/contao-elo-bundle` installieren
oder auf der Kommandozeile:

```bash
composer require schachbulle/contao-elo-bundle
```

Anschließend die Datenbank aktualisieren — im Contao Manager über das
Install-Tool oder auf der Kommandozeile:

```bash
vendor/bin/contao-console contao:migrate
```

Dabei werden die beiden Tabellen `tl_elo_listen` und `tl_elo` angelegt.

## Aufbau ##

Das Bundle bringt ein Backend-Modul **Elo-Verwaltung** unter **Inhalte** mit. Es
verwaltet zwei Ebenen:

* **Elo-Listen** (`tl_elo_listen`) — je Datensatz eine FIDE-Monatsliste
* **Spieler** (`tl_elo`) — die Spieler dieser Liste

Die Spieler sind Kinddatensätze der Liste. Sie gehören fest zu genau einer Liste
und werden zusammen mit ihr gelöscht.

## Listen anlegen ##

**Inhalte → Elo-Verwaltung → Neue Liste**. Eine Liste hat vier Felder:

| Feld | Bedeutung |
|---|---|
| **Monat** | Monat der Liste im Format `JJJJMM`, z. B. `202607`. Dient der internen Zuordnung und muss eindeutig gepflegt sein. |
| **Titel** | Überschrift der Liste, z. B. „Juli 2026". Erscheint auch im Frontend. |
| **Datum** | Stichtag der Liste im Format `TT.MM.JJJJ` |
| **Veröffentlicht** | Schaltet die Liste an oder aus. Nur veröffentlichte Listen erscheinen im Frontend. |

In der Übersicht sind die Listen absteigend nach Datum sortiert.

## Spieler pflegen ##

Die Spieler einer Liste erreicht man über die Operation **Spieler der Liste
bearbeiten**. Die Kopfzeile nennt Titel, Monat und Datum der Liste, zu der die
angezeigten Spieler gehören; über **Zurück** geht es in die Listenübersicht.

Die Felder entsprechen den Spalten der FIDE-Ratingliste: Personendaten,
FIDE-Daten (FIDE-ID und die vier Titelfelder), Markierungen (`i` steht für
inaktiv, je einmal für klassisch, Schnell- und Blitzschach) sowie die Wertungs-
und Partienzahlen der drei Bedenkzeiten.

In der Regel werden die Spieler nicht von Hand gepflegt, sondern importiert.

## FIDE-Daten importieren ##

Die Operation **FIDE-Daten importieren** an einer Liste übernimmt die
XML-Ratingliste der FIDE in genau diese Liste. Übernommen werden ausschließlich
Spieler mit einer deutschen Länderkennung (`GER`, `GDR`, `FRG`); vorhandene
Spieler der Liste werden überschrieben und Spieler, die in der Datei fehlen,
gelöscht.

Die ausführliche Anleitung samt Feldzuordnung, Serveranforderungen und Verhalten
bei einem Abbruch steht in **[docs/import.md](docs/import.md)**.

## Frontend ##

### Module ###

| Modul | Ausgabe |
|---|---|
| **Elo-Topliste eines Monats** | Aus der jüngsten veröffentlichten Liste die besten Spieler in sechs Wertungen (Normal-, Schnell- und Blitzschach, jeweils gesamt und Frauen), aufbereitet als Slider |
| **Ewige Elo-Bestenliste** | Über alle veröffentlichten Monatslisten hinweg je Spieler die höchste je erreichte Elo-Zahl |
| **Elo-Toplisten mehrerer Monate** | Je Monatsliste die ersten Plätze nebeneinander, sodass sich die Entwicklung der Spitze ablesen lässt |
| **Elo-Statistik** | Auszählungen zu einer Liste, etwa Anzahl der Großmeister oder der Spieler ab Elo 2500 |

Spieler mit der Inaktiv-Markierung der FIDE bleiben in der Topliste, der
Bestenliste und im Inhaltselement außen vor.

### Inhaltselement ###

Das Inhaltselement **Elo-Liste** (Gruppe „Schach") gibt eine einzelne Wertung als
Rangliste aus. Ohne feste Listenauswahl wird die jüngste veröffentlichte Liste
genommen. In der Überschrift lassen sich `%anzahl%` und `%monat%` als Platzhalter
verwenden.

### Zwischenspeicher ###

Die Frontend-Ausgaben werden zwischengespeichert, weil sie aus mehreren tausend
Datensätzen entstehen und sich nur einmal im Monat ändern. Die Dateien liegen im
Contao-Cacheverzeichnis und verschwinden beim Leeren des Caches. Die Haltbarkeit
für das Inhaltselement steht in den Contao-Einstellungen unter **Elo-Listen** in
Tagen; die vier Module rechnen fest mit 40 Tagen.

Nach einem Import erscheinen die neuen Zahlen im Frontend erst, wenn der
Zwischenspeicher abgelaufen ist oder der Contao-Cache geleert wurde.

## Entwicklung ##

Die Unit-Tests laufen mit PHPUnit 9:

```bash
vendor/bin/phpunit
```

Das Bundle führt bewusst kein eigenes `vendor/`-Verzeichnis mit; der
Test-Bootstrap kommt ohne Composer-Autoloader aus, wenn keiner vorhanden ist.

## Entwickler ##

**Frank Hoppe**
