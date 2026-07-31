# FIDE-Elo-Listen ToDo

## Offene Aufgaben

* **Frauentitel in der Statistik prüfen.** Die vier Auswertungen zu WGM, WIM, WFM
  und WCM vergleichen das Feld `title`. Die FIDE liefert Frauentitel aber im Feld
  `w_title` — die vier Auswertungen dürften deshalb dauerhaft 0 ergeben. Das
  Verhalten wurde bei der Portierung bewusst unverändert gelassen, weil es sich
  nur an echten Daten entscheiden lässt. Zu prüfen in
  `src/Classes/Statistik.php`, Konstante `BEDINGUNGEN`.
* **Import mit der echten Datei durchspielen.** Bisher nur mit einer nachgebauten
  Datei aus 50.000 Spielern verifiziert, nicht mit der echten
  `players_list_xml.zip` der FIDE (835 MB entpackt, rund 1,9 Millionen Spieler).
* **Bestandsdaten prüfen.** Spieler in `tl_elo` mit `pid = 0` sind in der
  Elternansicht unsichtbar und würden beim nächsten Import der jeweiligen Liste
  nicht erfasst.
* **Frontend am Live-System gegenprüfen.** Die vier Module und das
  Inhaltselement wurden mit Testdaten gerendert, nicht mit dem echten Bestand.
  Besonders die beiden Fehlerbehebungen sind sichtbar: Männertitel erscheinen
  jetzt in der ewigen Bestenliste, und die Überschrift des Inhaltselements wird
  als echtes Überschriften-Element ausgegeben statt als `<></>`.
* **Zwischenspeicher nach dem Import.** Neue Zahlen erscheinen im Frontend erst
  nach Ablauf der Haltbarkeit (Module fest 40 Tage) oder nach dem Leeren des
  Contao-Caches. Eine automatische Leerung am Ende des Imports wäre denkbar.

## Erledigt

* Kompatibilität mit PHP 8 sowie Contao 4.13 und 5 herstellen (3.0.0, verifiziert
  in `F:\Claude\contao-test` 5.7.7 und `F:\Claude\contao-test-413` 4.13.58:
  Backend-Listen, Elternansicht, Bearbeitungsmasken, alle vier Frontend-Module
  und das Inhaltselement)
* FIDE-XML-Import als Operation für die jeweilige Liste, nur deutsche
  Länderkennungen, vorhandene Spieler überschreiben und fehlende löschen (3.0.0)
* README ausführlich ergänzen, Import-Anleitung in `docs/import.md` (3.0.0)
