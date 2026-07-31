# FIDE-XML-Import

Der Import übernimmt die Ratingliste der FIDE in **eine** Elo-Liste. Er wird
immer am Listendatensatz gestartet, nie global — so ist eindeutig, welche
Monatsliste befüllt wird.

## Datei besorgen

Die FIDE veröffentlicht die Ratingliste monatlich unter
<https://ratings.fide.com/download_lists.phtml>. Gebraucht wird die Liste im
XML-Format:

* `players_list_xml.zip` — rund 50 MB, **empfohlen**
* `players_list_xml_foa.xml` — die entpackte Fassung, rund 835 MB

Die Zip-Datei kann direkt hochgeladen werden; sie wird auf dem Server entpackt.
Das spart beim Upload etwa 785 MB. Nur wenn die PHP-Erweiterung `zip` auf dem
Server fehlt, muss die entpackte XML hochgeladen werden — die Importseite weist
in dem Fall darauf hin.

## Ablauf

1. Im Backend **Inhalte → Elo-Verwaltung** öffnen.
2. Bei der gewünschten Liste auf **FIDE-Daten importieren** klicken und die
   Sicherheitsabfrage bestätigen.
3. Die Datei auswählen und auf **Import starten** klicken.
4. Der Fortschritt läuft in zwei Balken: erst der Upload, dann der Import.
   Das Browserfenster muss dabei geöffnet bleiben.
5. Am Ende steht eine Übersicht, wie viele Spieler angelegt, aktualisiert,
   unverändert gelassen und gelöscht wurden.

Die Datei enthält die komplette Weltrangliste mit rund 1,9 Millionen Spielern.
Upload und Import laufen deshalb in kleinen Schritten und dauern einige Minuten.

## Importregeln

* Übernommen werden **ausschließlich Spieler mit einer deutschen Länderkennung**:
  `GER` sowie die historischen Kennungen `GDR` (DDR) und `FRG` (Bundesrepublik vor
  der Wiedervereinigung). Alle anderen werden übersprungen und in der
  Ergebnisübersicht gezählt.
* Zuordnung erfolgt über die **FIDE-ID innerhalb der Liste**. Ein Spieler kann
  also in mehreren Monatslisten mit derselben FIDE-ID stehen, ohne dass sich die
  Listen ins Gehege kommen.
* Vorhandene Spieler der Liste werden **überschrieben**.
* Spieler der Liste, die in der Importdatei **nicht mehr vorkommen, werden
  gelöscht**.
* **Andere Listen bleiben unberührt.**
* Das Veröffentlichungskennzeichen wird nur beim Anlegen gesetzt. Ein von Hand
  ausgeblendeter Spieler bleibt also beim nächsten Import ausgeblendet.

## Feldzuordnung

| FIDE-XML | Feld in `tl_elo` | Anmerkung |
|---|---|---|
| `fideid` | `fideid` | Ohne FIDE-ID wird der Spieler übersprungen |
| `name` | `surname`, `prename`, `intent` | An den Kommata zerlegt: „Nachname, Vorname, Zusatz" |
| `country` | `country` | `GER`, `GDR` oder `FRG` |
| `sex` | `sex` | |
| `title`, `w_title`, `o_title`, `foa_title` | gleichnamig | |
| `rating`, `games` | gleichnamig | Klassisch |
| `rapid_rating`, `rapid_games` | gleichnamig | Schnellschach |
| `blitz_rating`, `blitz_games` | gleichnamig | Blitzschach |
| `flag`, `rapid_flag`, `blitz_flag` | gleichnamig | z. B. `i` für inaktiv |
| `birthday` | `birthday` | Die FIDE liefert nur das Jahr |

Zu lange Werte werden zeichenweise auf die Feldlänge gekürzt, damit MySQL im
strengen Modus nicht abbricht.

## Was passiert bei einem Abbruch?

Wird der Import unterbrochen (Browser geschlossen, Netzwerkfehler), bleiben die
vorhandenen Spieler der Liste **erhalten** — gelöscht wird erst ganz am Ende
eines vollständigen Laufs. Lediglich das Änderungsdatum der Spieler steht dann
auf 0, bis der nächste vollständige Import es wieder setzt.

Grund: Zu Beginn eines Laufs wird das Änderungsdatum aller Spieler der Liste auf
0 gesetzt. Jeder importierte Spieler bekommt anschließend den Zeitstempel des
Laufs. Was am Ende noch auf 0 steht, kam in der Datei nicht vor und wird
gelöscht. Diese Markierung ist eindeutig — ein Vergleich gegen den Zeitstempel
allein wäre es nicht, weil ein Bestandsdatensatz zufällig denselben tragen
könnte.

Die hochgeladene Datei liegt während des Imports unter `var/tmp/` und wird nach
dem letzten Schritt gelöscht. Bei einem Abbruch bleibt sie liegen; der nächste
Import überschreibt sie.

## Serveranforderungen

* Schreibrecht auf `var/tmp/`. Für die entpackte XML werden dort vorübergehend
  rund 900 MB frei benötigt — bei einem Zip-Upload kurzzeitig sogar etwas mehr,
  weil Archiv und entpackte Datei nebeneinander liegen.
* Die PHP-Erweiterung `zip`, wenn die gezippte Datei genutzt werden soll.
* `post_max_size` und `upload_max_filesize` müssen mindestens 2 MB betragen —
  mehr nicht, weil der Browser die Datei in 2-MB-Blöcke zerlegt.

## Technischer Hintergrund

Die XML ist zu groß, um sie am Stück zu verarbeiten. Deshalb:

* **Upload:** Der Browser zerlegt die Datei in 2-MB-Blöcke und hängt sie
  nacheinander an eine temporäre Datei an.
* **Import:** Je Aufruf verarbeitet der Server ein Paket von `<player>`-Blöcken
  und meldet den erreichten Byte-Offset zurück. Der nächste Schritt setzt per
  `fseek` dort wieder auf. Gelesen wird mit einem Puffer-Parser statt mit
  `XMLReader`, weil der über mehrere Aufrufe hinweg keinen Zustand behalten
  könnte.
* Bevor ein Block als XML geparst wird, prüft eine einfache Textsuche, ob er
  überhaupt eine der drei Länderkennungen trägt. Für rund 98 Prozent der Blöcke
  fällt damit kein XML-Aufbau an. Verglichen wird gegen das vollständige Element
  `<country>GER</country>`, damit etwa `GERMANY` nicht durchrutscht.

Die Logik steht in [`src/Classes/FideImport.php`](../src/Classes/FideImport.php),
die Oberfläche im Template
[`be_elo_import.html5`](../src/Resources/contao/templates/be_elo_import.html5).
