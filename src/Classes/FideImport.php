<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Classes;

use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Backend-Import der FIDE-Ratingliste (XML) in eine einzelne Elo-Liste.
 *
 * Aufgerufen wird die Klasse als Operation am Datensatz (key=import&id=<Liste>)
 * im Backend-Modul "Elo-Verwaltung"; der Import läuft also immer in genau die
 * Liste, deren Schaltfläche angeklickt wurde.
 *
 * Die FIDE-Datei ist sehr groß (die komplette Weltrangliste, rund 1,9 Millionen
 * Spieler und etwa 835 MB als XML). Deshalb läuft alles in kleinen AJAX-Schritten:
 *
 * - Upload: Der Browser zerlegt die Datei in 2-MB-Blöcke und hängt sie
 *   nacheinander an eine temporäre Datei an. Auch die gezippte Fassung von
 *   ratings.fide.com wird angenommen und serverseitig entpackt.
 * - Import: Je Aufruf wird ein Paket von <player>-Blöcken verarbeitet. Der
 *   Byte-Offset wandert mit, damit der nächste Schritt per fseek an derselben
 *   Stelle weitermachen kann (Puffer-Parser statt XMLReader, weil der über
 *   mehrere Aufrufe hinweg keinen Zustand behalten könnte).
 *
 * Importregeln dieser Erweiterung:
 * - Es werden ausschließlich Spieler mit der Länderkennung GER übernommen.
 * - Vorhandene Spieler der Liste werden anhand der FIDE-ID überschrieben.
 * - Spieler der Liste, die in der Importdatei fehlen, werden gelöscht.
 */
class FideImport
{
	/**
	 * Anzahl der <player>-Blöcke, die ein Import-Schritt verarbeitet.
	 *
	 * Der Wert liegt deutlich höher als beim Wertungsportal-Bundle, weil hier
	 * nur deutsche Spieler ausgewertet werden: Für die übrigen rund 98 Prozent
	 * der Blöcke fällt nur eine Textsuche an, kein XML-Parsen.
	 */
	public const SPIELER_PRO_SCHRITT = 20000;

	/**
	 * Blockgröße beim Lesen der Importdatei in Bytes.
	 */
	public const LESEBLOCK = 65536;

	/**
	 * Länderkennungen, die übernommen werden.
	 *
	 * Neben dem heutigen GER auch die historischen Kennungen für die DDR (GDR)
	 * und die Bundesrepublik vor der Wiedervereinigung (FRG) — so wie es schon
	 * das frühere Konvertierungsskript hielt. Ohne sie fehlten Spieler, die seit
	 * den achtziger Jahren eine Elo-Zahl haben.
	 */
	public const LAENDER = array('GER', 'GDR', 'FRG');

	/**
	 * @var Connection Doctrine-Verbindung für alle Datenbankzugriffe
	 */
	private $connection;

	/**
	 * @var string Wurzelverzeichnis des Projekts, Basis für die temporäre Datei
	 */
	private $projektVerzeichnis;

	/**
	 * Nimmt die Abhängigkeiten entgegen.
	 *
	 * Die Klasse wird nicht selbst instanziiert, sondern von Contao über
	 * System::importStatic() aus dem Service-Container geholt. Deshalb ist sie
	 * in der services.yaml unter ihrem Klassennamen als öffentlicher Dienst
	 * eingetragen.
	 *
	 * @param Connection $connection         Verbindung zur Contao-Datenbank
	 * @param string     $projektVerzeichnis Absoluter Pfad zum Projektverzeichnis
	 */
	public function __construct(Connection $connection, string $projektVerzeichnis)
	{
		$this->connection = $connection;
		$this->projektVerzeichnis = $projektVerzeichnis;
	}

	/**
	 * Einstiegspunkt der Operation "FIDE-Daten importieren".
	 *
	 * Beantwortet zunächst die beiden AJAX-Aktionen (die dabei jeweils eine
	 * ResponseException werfen und damit nicht zurückkehren) und liefert sonst
	 * die Importseite aus.
	 *
	 * @param object $dc Der DataContainer der Liste; wird nicht ausgewertet, die
	 *                   Listen-ID kommt aus der URL, damit sie auch in den
	 *                   AJAX-Schritten zur Verfügung steht
	 *
	 * @return string Das gerenderte Backend-Template
	 */
	public function run($dc): string
	{
		$listenId = (int) Input::get('id');

		$aktion = Input::post('eloAktion');

		if ('upload' === $aktion)
		{
			$this->ajaxUpload($listenId);
		}

		if ('import' === $aktion)
		{
			$this->ajaxImport($listenId);
		}

		$liste = $this->ladeListe($listenId);

		$objTemplate = new BackendTemplate('be_elo_import');
		$objTemplate->zurueck = StringUtil::ampersand(str_replace('&key=import', '', Environment::get('request')));
		$objTemplate->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
		$objTemplate->listenTitel = $liste ? $liste['title'] : '';
		$objTemplate->listenMonat = $liste ? (string) $liste['listmonth'] : '';
		$objTemplate->listeFehlt = null === $liste;
		$objTemplate->zipMoeglich = class_exists(\ZipArchive::class);
		$objTemplate->laender = implode(', ', self::LAENDER);

		return $objTemplate->parse();
	}

	/**
	 * Liest den Kopfdatensatz der Liste, in die importiert werden soll.
	 *
	 * @param int $listenId ID des Datensatzes in tl_elo_listen
	 *
	 * @return array|null Die Felder der Liste, oder null wenn es sie nicht gibt
	 */
	private function ladeListe(int $listenId): ?array
	{
		if (!$listenId)
		{
			return null;
		}

		$zeile = $this->connection->fetchAssociative('SELECT id, title, listmonth FROM tl_elo_listen WHERE id = ?', array($listenId));

		return false === $zeile ? null : $zeile;
	}

	/**
	 * Liefert den Pfad der temporären Importdatei.
	 *
	 * Der Name enthält Benutzer- und Listen-ID, damit sich gleichzeitige
	 * Importe verschiedener Redakteure oder in verschiedene Listen nicht in
	 * die Quere kommen. Das Verzeichnis wird bei Bedarf angelegt.
	 *
	 * @param int $listenId ID der Liste, in die importiert wird
	 *
	 * @return string Absoluter Pfad der temporären Datei
	 */
	private function tempDatei(int $listenId): string
	{
		$verzeichnis = $this->projektVerzeichnis.'/var/tmp';

		if (!is_dir($verzeichnis))
		{
			mkdir($verzeichnis, 0777, true);
		}

		return $verzeichnis.'/elo-import-'.(int) BackendUser::getInstance()->id.'-'.$listenId.'.xml';
	}

	/**
	 * AJAX-Schritt: Hängt einen hochgeladenen Block an die temporäre Datei an.
	 *
	 * Beim Offset 0 wird eine eventuell vorhandene Datei verworfen und neu
	 * begonnen. Bei allen weiteren Blöcken muss der übergebene Offset zur
	 * bisherigen Dateigröße passen; sonst wäre die Datei löchrig und der
	 * spätere Import würde mitten in einem <player>-Block aufsetzen.
	 *
	 * @param int $listenId ID der Liste, in die importiert wird
	 *
	 * @return void Die Methode kehrt nicht zurück, sondern wirft eine
	 *              ResponseException mit der JSON-Antwort
	 */
	private function ajaxUpload(int $listenId): void
	{
		$offset = (int) Input::post('offset');
		$ziel = $this->tempDatei($listenId);

		if (!isset($_FILES['chunk']) || UPLOAD_ERR_OK !== $_FILES['chunk']['error'])
		{
			$this->jsonAntwort(array('fehler' => 'Upload-Fehler: Der Block wurde nicht empfangen (eventuell sind post_max_size oder upload_max_filesize zu klein).'));
		}

		if (0 === $offset)
		{
			if (file_exists($ziel))
			{
				unlink($ziel);
			}

			file_put_contents($ziel, file_get_contents($_FILES['chunk']['tmp_name']));
		}
		else
		{
			if (!file_exists($ziel) || filesize($ziel) !== $offset)
			{
				$this->jsonAntwort(array('fehler' => 'Der Upload lässt sich nicht fortsetzen, weil der Offset nicht zur Dateigröße passt. Bitte den Import neu starten.'));
			}

			file_put_contents($ziel, file_get_contents($_FILES['chunk']['tmp_name']), FILE_APPEND);
		}

		clearstatcache();

		$this->jsonAntwort(array('ok' => true, 'groesse' => filesize($ziel)));
	}

	/**
	 * AJAX-Schritt: Verarbeitet ein Paket von <player>-Blöcken ab dem
	 * übergebenen Byte-Offset.
	 *
	 * Der Lauf-Zeitstempel kommt vom Browser und bleibt über alle Schritte
	 * gleich. Er dient als Markierung: Jeder angefasste Spieler bekommt ihn als
	 * tstamp, und beim letzten Schritt werden alle Spieler der Liste gelöscht,
	 * die noch auf 0 stehen — also in der Importdatei nicht vorkamen.
	 *
	 * @param int $listenId ID der Liste, in die importiert wird
	 *
	 * @return void Die Methode kehrt nicht zurück, sondern wirft eine
	 *              ResponseException mit der JSON-Antwort
	 */
	private function ajaxImport(int $listenId): void
	{
		if (null === $this->ladeListe($listenId))
		{
			$this->jsonAntwort(array('fehler' => 'Die Liste, in die importiert werden soll, existiert nicht mehr.'));
		}

		$offset = (int) Input::post('offset');
		$lauf = (int) Input::post('lauf');

		if (!$lauf)
		{
			$lauf = time();
		}

		$datei = $this->tempDatei($listenId);

		if (!file_exists($datei))
		{
			$this->jsonAntwort(array('fehler' => 'Es wurde keine hochgeladene Datei gefunden. Bitte den Import neu starten.'));
		}

		if (0 === $offset)
		{
			// Eine gezippte FIDE-Datei zuerst auspacken
			$this->entpackeFallsZip($datei);
			clearstatcache();

			// Alle vorhandenen Spieler der Liste entmarkieren. Was am Ende des
			// Laufs noch auf 0 steht, kam in der Importdatei nicht vor und wird
			// gelöscht. Das Nullsetzen ist eindeutig — ein Vergleich gegen den
			// Lauf-Zeitstempel wäre es nicht, weil ein Bestandsdatensatz
			// zufällig denselben tstamp tragen könnte.
			$this->connection->executeStatement('UPDATE tl_elo SET tstamp = 0 WHERE pid = ?', array($listenId));
		}

		$gesamt = filesize($datei);
		$fp = fopen($datei, 'r');

		if ($offset > 0)
		{
			fseek($fp, $offset);
		}

		$puffer = '';
		$gelesen = 0;
		$uebersprungen = 0;
		$arrSpieler = array();

		// Datei blockweise lesen und vollständige <player>-Blöcke herausschneiden.
		// Auf '<player>' inklusive schließender Klammer prüfen, sonst würde auch
		// das umschließende '<playerslist>' als Blockanfang gelten.
		while ($gelesen < self::SPIELER_PRO_SCHRITT && ($block = fread($fp, self::LESEBLOCK)) !== false && '' !== $block)
		{
			$puffer .= $block;

			while ($gelesen < self::SPIELER_PRO_SCHRITT && ($ende = strpos($puffer, '</player>')) !== false)
			{
				$start = strpos($puffer, '<player>');

				if (false === $start || $start > $ende)
				{
					// Kein passender Anfang vor diesem Ende: Pufferanfang verwerfen
					$puffer = substr($puffer, $ende + 9);
					continue;
				}

				$xml = substr($puffer, $start, $ende + 9 - $start);
				$puffer = substr($puffer, $ende + 9);
				++$gelesen;

				$spieler = self::parseSpieler($xml);

				if (null === $spieler)
				{
					++$uebersprungen;
					continue;
				}

				// Doppelte FIDE-IDs innerhalb einer Datei: der letzte Block gewinnt
				$arrSpieler[$spieler['fideid']] = $spieler;
			}
		}

		// Verbrauchte Bytes = Leseposition abzüglich des unverarbeiteten Restes;
		// der Rest wird im nächsten Schritt erneut gelesen
		$neuerOffset = ftell($fp) - \strlen($puffer);
		$fertig = (0 === $gelesen) && feof($fp);
		fclose($fp);

		$ergebnis = $this->schreibeSpieler($listenId, $arrSpieler, $lauf);

		$geloescht = 0;

		if ($fertig)
		{
			$geloescht = $this->entferneFehlende($listenId);

			if (file_exists($datei))
			{
				unlink($datei);
			}
		}

		$this->jsonAntwort(array
		(
			'ok'            => true,
			'offset'        => $neuerOffset,
			'gesamt'        => $gesamt,
			'fertig'        => $fertig,
			'gelesen'       => $gelesen,
			'uebersprungen' => $uebersprungen,
			'neu'           => $ergebnis['neu'],
			'aktualisiert'  => $ergebnis['aktualisiert'],
			'unveraendert'  => $ergebnis['unveraendert'],
			'geloescht'     => $geloescht,
		));
	}

	/**
	 * Entpackt die temporäre Datei, sofern es sich um ein Zip-Archiv handelt.
	 *
	 * Die FIDE bietet die Ratingliste gezippt an (rund 50 MB statt 835 MB), was
	 * den Upload erheblich verkürzt. Erkannt wird das Archiv an seinen ersten
	 * vier Bytes; die erste enthaltene XML-Datei ersetzt anschließend die
	 * temporäre Datei. Entpackt wird streamend, weil die XML nicht in den
	 * Speicher passt.
	 *
	 * @param string $datei Pfad der temporären Datei
	 *
	 * @return void Bei einem defekten oder XML-losen Archiv wird der Vorgang mit
	 *              einer Fehlermeldung abgebrochen
	 */
	private function entpackeFallsZip(string $datei): void
	{
		$fp = fopen($datei, 'r');
		$magic = fread($fp, 4);
		fclose($fp);

		if ("PK\x03\x04" !== $magic)
		{
			return;
		}

		if (!class_exists(\ZipArchive::class))
		{
			$this->jsonAntwort(array('fehler' => 'Die hochgeladene Datei ist ein Zip-Archiv, aber die PHP-Erweiterung "zip" ist nicht verfügbar. Bitte die entpackte XML-Datei hochladen.'));
		}

		$zip = new \ZipArchive();

		if (true !== $zip->open($datei))
		{
			$this->jsonAntwort(array('fehler' => 'Das hochgeladene Zip-Archiv konnte nicht geöffnet werden.'));
		}

		$eintrag = false;

		for ($x = 0; $x < $zip->numFiles; ++$x)
		{
			$name = $zip->getNameIndex($x);

			if ('.xml' === strtolower(substr($name, -4)))
			{
				$eintrag = $name;
				break;
			}
		}

		if (false === $eintrag)
		{
			$zip->close();
			$this->jsonAntwort(array('fehler' => 'Das Zip-Archiv enthält keine XML-Datei.'));
		}

		$quelle = $zip->getStream($eintrag);
		$ziel = fopen($datei.'.entpackt', 'w');

		while (!feof($quelle))
		{
			fwrite($ziel, fread($quelle, self::LESEBLOCK));
		}

		fclose($quelle);
		fclose($ziel);
		$zip->close();

		unlink($datei);
		rename($datei.'.entpackt', $datei);
	}

	/**
	 * Wandelt einen <player>-Block der FIDE-XML in ein Feld-Array für
	 * tl_elo um.
	 *
	 * Vor dem eigentlichen Parsen wird per Textsuche geprüft, ob der Block
	 * überhaupt die gesuchte Länderkennung trägt. Das spart bei einer
	 * Weltrangliste mit 1,9 Millionen Einträgen den XML-Aufbau für rund
	 * 98 Prozent der Blöcke.
	 *
	 * Der FIDE-Name steht in einem einzigen Feld als "Nachname, Vorname" und
	 * gelegentlich mit einem dritten Bestandteil (etwa "Dr."); er wird an den
	 * Kommata zerlegt. Alle Textwerte werden auf die Feldlänge der Tabelle
	 * gekürzt, damit MySQL im strengen Modus nicht abbricht.
	 *
	 * @param string $xml Ein vollständiger Block <player>...</player>
	 *
	 * @return array|null Die Feldwerte, oder null bei einem Spieler aus einem
	 *                    anderen Land, bei fehlender FIDE-ID oder wenn sich der
	 *                    Block nicht als XML lesen lässt
	 */
	public static function parseSpieler(string $xml): ?array
	{
		// Schnellprüfung vor dem Parsen. Verglichen wird gegen das vollständige
		// Element, damit etwa "GERMANY" nicht als "GER" durchrutscht.
		$treffer = false;

		foreach (self::LAENDER as $land)
		{
			if (false !== strpos($xml, '<country>'.$land.'</country>'))
			{
				$treffer = true;
				break;
			}
		}

		if (!$treffer)
		{
			return null;
		}

		$player = @simplexml_load_string($xml);

		if (false === $player)
		{
			return null;
		}

		$fideid = (int) $player->fideid;

		if (!$fideid)
		{
			return null;
		}

		// Nach der Schnellprüfung noch einmal sauber gegen das geparste Feld
		if (!\in_array((string) $player->country, self::LAENDER, true))
		{
			return null;
		}

		$name = array_map('trim', explode(',', (string) $player->name, 3));

		return array
		(
			'fideid'       => $fideid,
			'surname'      => mb_substr($name[0], 0, 64),
			'prename'      => isset($name[1]) ? mb_substr($name[1], 0, 64) : '',
			'intent'       => isset($name[2]) ? mb_substr($name[2], 0, 16) : '',
			'country'      => mb_substr((string) $player->country, 0, 3),
			'sex'          => mb_substr((string) $player->sex, 0, 1),
			'title'        => mb_substr((string) $player->title, 0, 3),
			'w_title'      => mb_substr((string) $player->w_title, 0, 3),
			'o_title'      => mb_substr((string) $player->o_title, 0, 3),
			'foa_title'    => mb_substr((string) $player->foa_title, 0, 3),
			'flag'         => mb_substr((string) $player->flag, 0, 8),
			'rapid_flag'   => mb_substr((string) $player->rapid_flag, 0, 8),
			'blitz_flag'   => mb_substr((string) $player->blitz_flag, 0, 8),
			'rating'       => (int) $player->rating,
			'games'        => (int) $player->games,
			'rapid_rating' => (int) $player->rapid_rating,
			'rapid_games'  => (int) $player->rapid_games,
			'blitz_rating' => (int) $player->blitz_rating,
			'blitz_games'  => (int) $player->blitz_games,
			'birthday'     => (int) $player->birthday,
		);
	}

	/**
	 * Schreibt ein Spielerpaket in die Kindtabelle der Liste.
	 *
	 * Der Bestand wird blockweise über die FIDE-IDs geladen. Vorhandene Spieler
	 * werden überschrieben, fehlende angelegt. In beiden Fällen bekommt der
	 * Datensatz den Lauf-Zeitstempel als tstamp und ist damit als "in diesem
	 * Import enthalten" markiert.
	 *
	 * Das Veröffentlichungskennzeichen wird nur beim Anlegen gesetzt, damit ein
	 * von Hand ausgeblendeter Spieler beim nächsten Import nicht wieder
	 * auftaucht.
	 *
	 * @param int   $listenId   ID der Liste, in die importiert wird
	 * @param array $arrSpieler FIDE-ID => Feld-Array aus parseSpieler()
	 * @param int   $lauf       Zeitstempel des Importlaufs
	 *
	 * @return array Anzahl je Kategorie: neu, aktualisiert, unveraendert
	 */
	private function schreibeSpieler(int $listenId, array $arrSpieler, int $lauf): array
	{
		$ergebnis = array('neu' => 0, 'aktualisiert' => 0, 'unveraendert' => 0);

		if (!\count($arrSpieler))
		{
			return $ergebnis;
		}

		$felder = array_keys(reset($arrSpieler));

		// Bestand der Liste blockweise über die FIDE-IDs laden
		$arrBestand = array();

		foreach (array_chunk(array_keys($arrSpieler), 500) as $chunk)
		{
			$platzhalter = implode(',', array_fill(0, \count($chunk), '?'));

			$zeilen = $this->connection->fetchAllAssociative(
				'SELECT id, tstamp, '.implode(', ', $felder).' FROM tl_elo WHERE pid = ? AND fideid IN ('.$platzhalter.')',
				array_merge(array($listenId), $chunk)
			);

			foreach ($zeilen as $zeile)
			{
				$arrBestand[(int) $zeile['fideid']] = $zeile;
			}
		}

		$arrInsert = array();

		foreach ($arrSpieler as $fideid => $spieler)
		{
			if (!isset($arrBestand[$fideid]))
			{
				$zeile = array($listenId, $lauf);

				foreach ($felder as $feld)
				{
					$zeile[] = $spieler[$feld];
				}

				$zeile[] = '1';
				$arrInsert[] = $zeile;

				continue;
			}

			$set = array();

			foreach ($felder as $feld)
			{
				if ((string) $arrBestand[$fideid][$feld] !== (string) $spieler[$feld])
				{
					$set[$feld] = $spieler[$feld];
				}
			}

			// Der tstamp wird immer geschrieben, auch wenn sich am Spieler nichts
			// geändert hat — er ist die Markierung für den Löschlauf am Ende
			$zuweisung = array('tstamp = ?');
			$werte = array($lauf);

			foreach ($set as $feld => $wert)
			{
				$zuweisung[] = $feld.' = ?';
				$werte[] = $wert;
			}

			$werte[] = $arrBestand[$fideid]['id'];

			$this->connection->executeStatement(
				'UPDATE tl_elo SET '.implode(', ', $zuweisung).' WHERE id = ?',
				$werte
			);

			if ($set)
			{
				++$ergebnis['aktualisiert'];
			}
			else
			{
				++$ergebnis['unveraendert'];
			}
		}

		// Neue Datensätze blockweise anlegen
		if ($arrInsert)
		{
			$spalten = 'pid, tstamp, '.implode(', ', $felder).', published';
			$tupel = '('.implode(', ', array_fill(0, \count($felder) + 3, '?')).')';

			foreach (array_chunk($arrInsert, 100) as $chunk)
			{
				$werte = implode(', ', array_fill(0, \count($chunk), $tupel));

				$this->connection->executeStatement(
					'INSERT INTO tl_elo ('.$spalten.') VALUES '.$werte,
					array_merge(...$chunk)
				);
			}

			$ergebnis['neu'] = \count($arrInsert);
		}

		return $ergebnis;
	}

	/**
	 * Löscht die Spieler der Liste, die in der Importdatei nicht vorkamen.
	 *
	 * Erkennbar sind sie am tstamp 0: Zu Beginn des Laufs werden alle Spieler
	 * der Liste entmarkiert, jeder importierte Spieler bekommt anschließend den
	 * Lauf-Zeitstempel. Wird der Import abgebrochen, bleiben die Datensätze
	 * erhalten — nur ihr Änderungsdatum steht dann auf 0, bis der nächste
	 * vollständige Lauf es wieder setzt.
	 *
	 * @param int $listenId ID der Liste, in die importiert wurde
	 *
	 * @return int Anzahl der gelöschten Spieler
	 */
	private function entferneFehlende(int $listenId): int
	{
		return (int) $this->connection->executeStatement(
			'DELETE FROM tl_elo WHERE pid = ? AND tstamp = 0',
			array($listenId)
		);
	}

	/**
	 * Beendet die Verarbeitung mit einer JSON-Antwort.
	 *
	 * Contao erwartet an dieser Stelle keinen Rückgabewert, sondern eine
	 * Response; die ResponseException ist der vorgesehene Weg, sie aus der Tiefe
	 * eines Callbacks heraus auszuliefern.
	 *
	 * @param array $daten Die Nutzdaten der Antwort
	 *
	 * @return void Die Methode kehrt nie zurück
	 *
	 * @throws ResponseException Immer
	 */
	private function jsonAntwort(array $daten): void
	{
		throw new ResponseException(new JsonResponse($daten));
	}
}
