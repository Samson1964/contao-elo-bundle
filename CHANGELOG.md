# FIDE-Elo-Listen Changelog

## Version 3.0.0 (2026-07-31)

Große Fassung: Das Bundle läuft jetzt unter Contao 5, und die Elo-Liste der FIDE
wird direkt im Backend importiert statt von Hand in die Datenbank geschrieben.

### Contao 5 und PHP 8

* Add: Unterstützung für Contao 5 (getestet mit 5.7.7), Contao 4.13 bleibt unterstützt
* Add: `declare(strict_types=1)` und deutsche Kommentarblöcke in allen PHP-Dateien
* Change: PHP-Anforderung auf `^7.4 || ^8.0` angehoben, die Unterstützung für PHP 5.6 und 7.0 bis 7.3 entfällt
* Fix: Vier Dateien begannen mit `if (!defined('TL_ROOT')) die(...)`. Die Konstante gibt es in Contao 5 nicht mehr, die Dateien hätten dort jede Anfrage abgebrochen — betroffen waren die DCA- und Sprachdateien zu `tl_settings` und `tl_content`
* Change: `TL_MODE == 'BE'` in den Frontend-Modulen durch den `contao.routing.scope_matcher` ersetzt; die Konstante ist in Contao 5 entfallen
* Change: Die Klassen sprechen Contao über vollqualifizierte Namen an (`Contao\Module`, `Contao\ContentElement`, `Contao\Database`); die Klassenaliase ohne Namensraum gibt es in Contao 5 nicht mehr
* Change: Die beiden Hilfsklassen in den DCA-Dateien erben nicht mehr von `Backend` und importieren nicht mehr den Backend-Benutzer — beides wurde nicht gebraucht
* Change: `dataContainer` verwendet den vollqualifizierten Klassennamen `Contao\DC_Table`, den Contao 4.13 seit 4.9 ebenfalls versteht
* Change: Operationsleisten werden versionsabhängig aufgebaut, da Contao 5 die Kurzschreibweise erwartet und Contao 4.13 vollständige Arrays
* Change: Die Zeilendarstellung der Spielerliste läuft in Contao 5 über einen `label_callback`; der `child_record_callback` ist dort seit 5.7 abgekündigt. Die Ausgabe bleibt in beiden Versionen dieselbe
* Change: Der Veröffentlichungs-Schalter läuft über das Contao-eigene `act=toggle` statt über `haste_ajax_operation`
* Change: Operations-Icons von `.gif` auf `.svg` umgestellt, Modul- und Operations-Icons als SVG statt PNG
* Change: `services.yml` in `services.yaml` umbenannt und der `_instanceof`-Block entfernt, der auf das in Symfony 7 entfallene `ContainerAwareInterface` verwies
* Remove: Abhängigkeit `codefog/contao-haste`, die für Contao 5 nicht verfügbar ist
* Remove: Abhängigkeit `schachbulle/contao-helper-bundle`; die daraus genutzte Cache-Klasse ist durch `symfony/cache` ersetzt

### FIDE-XML-Import

* Add: Operation **FIDE-Daten importieren** an der einzelnen Liste (`key=import`). Übernommen werden ausschließlich Spieler mit einer deutschen Länderkennung (GER, GDR, FRG — wie im bisherigen Konvertierungsskript); vorhandene Spieler der Liste werden anhand der FIDE-ID überschrieben und Spieler, die in der Importdatei fehlen, gelöscht. Andere Listen bleiben unberührt
* Add: Die gezippte Ratingliste der FIDE wird angenommen und serverseitig streamend entpackt (ca. 50 MB statt 835 MB Upload)
* Add: Upload und Import laufen in kleinen AJAX-Schritten, damit die rund 1,9 Millionen Datensätze der Weltrangliste nicht an Zeit- oder Speichergrenzen scheitern
* Remove: Die Skripte unter `src/Resources/import/` entfallen. Sie mussten von Hand auf dem Server ausgeführt werden, erzeugten SQL-Dateien und trugen die Listen-ID fest im Quelltext

### Fehlerbehebungen im Frontend

* Fix: In der ewigen Bestenliste wurde der FIDE-Titel über das Feld `fidetitel` gelesen, das die Abfrage gar nicht liefert (sie benennt es `elotitel`). Männertitel fehlten dadurch in der Ausgabe
* Fix: Das Inhaltselement legte in `compile()` ein zweites Template an und warf damit die von Contao vorbereiteten Daten weg. Die Überschrift wurde deshalb als `<></>` ausgegeben, `cssID` und `style` fehlten
* Fix: Mehrere Zugriffe auf nicht gesetzte Variablen und Array-Schlüssel beseitigt, die unter PHP 8 Warnungen erzeugten (`$sql` und `$altelo` in den Toplisten, `$ids[...]` in der Bestenliste, fehlende Plätze in der Monatsübersicht)
* Fix: Die Statistik liest die Auswahl jetzt über `StringUtil::deserialize()` statt über `unserialize()` und zählt per `COUNT(*)`, statt alle Datensätze zu laden
* Fix: Ohne veröffentlichte Liste geben die Module eine leere Ausgabe aus, statt mit einem Fehler abzubrechen

### Dokumentation und Tests

* Add: README mit vollständiger Anleitung, Dokumentation des Imports in `docs/import.md`
* Add: Unit-Tests für die Auswertung eines `<player>`-Blocks (`tests/`, PHPUnit 9)
* Add: `country` ist in der Spielerliste filterbar, da nun drei Länderkennungen vorkommen können

## Version 2.3.2 (2026-07-29)

* Fix: Warning: Undefined array key "deleteConfirm" bei contao:migrate -> Lesezugriffe auf $GLOBALS['TL_LANG'] in den DCA-Dateien mit `?? null` bzw. `?? array()` abgesichert, da der DcaLoader die Sprachdateien noch nicht geladen hat
* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 2.3.1 (2025-09-09)

* Fix: Warning: Undefined array key "elo_gender_options" in src/Resources/contao/dca/tl_module.php (line 63) 

## Version 2.3.0 (2024-04-17)

* Add: Abhängigkeit codefog/contao-haste
* Add: PHP-8-Unterstützung
* Add: Haste-Toggler

## Version 2.2.0 (2021-09-08)

* Add: customTpl in der Palette bei den Frontend-Modulen
* Add: Frontend-Modul Statistik

## Version 2.1.1 (2020-09-08)

* Fehler in Bestenliste beim FIDE-Titel behoben (falsche Korrektur)
* 1. Spalte in Bestenliste und TopX-Liste als th formatiert

## Version 2.1.0 (2020-09-08)

* DCA-Paletten tl_module ergänzt
* FE-Modul für Ausgabe Top-X bestimmter Listen erstellt
* Fehler in Bestenliste beim FIDE-Titel behoben

## Version 2.0.3 (2020-09-03)

* CSS-Klassen für Elo-Bestenlisten ergänzt

## Version 2.0.2 (2020-09-03)

* Inaktive Spieler nicht ausgeben in Elo-Bestenlisten

## Version 2.0.1 (2020-09-03)

* pre-Tag im Template mod_elobestenliste entfernt
* bei gleicher Elo Platznummer nicht ausgeben

## Version 2.0.0 (2020-09-03)

* Neues Feld in Elo-Listen: Monat der Liste im Format JJJJMM
* Ewige Elo-Bestenliste als Modul erstellt

## Version 1.0.0 (2020-06-03)

* Fix: Inhaltselement EloArchiv verschoben in Verzeichnisstruktur
* Fix: Inhaltselement EloArchiv: Anzeige Auswahl Eloliste korrigiert
* Fix: Inhaltselement EloArchiv: submitOnChange bei Auswahl Eloliste deaktiviert
* Add: Inhaltselement EloArchiv: Aktuellste Eloliste ausgeben als Standard (war bis April noch i.O., danach nicht mehr)
* Add: Einstellung der Cachedauer in den Einstellungen
* Fix: Überschrift wurde nicht gecached / Template ce_eloliste geändert

## Version 0.0.5 (2020-04-07)

* Fix: Änderung der Überschrift hat gefehlt -> aus alter Version wiederhergestellt

## Version 0.0.4 (2020-04-06)

* Add: Abhängigkeit schachbulle/contao-helper-bundle

## Version 0.0.3 (2020-04-02)

* Add: Flag-Spalten für die 3 verschiedenen Elo-Zahlen (noch nicht im Import vorgesehen)
* Add: Übersetzungen BE-Formulare

## Version 0.0.2 (2020-04-02)

* Fix: contao/config.php, Problem mit BE-Moduleinbindung

## Version 0.0.1 (2020-04-02)

* Initialversion als Contao-4-Bundle auf der Grundlage von Version 1.2.0 für Contao 3
