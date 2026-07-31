<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\ContentElements;

use Contao\Config;
use Contao\ContentElement;
use Contao\Database;
use Schachbulle\ContaoEloBundle\Classes\Zwischenspeicher;

/**
 * Inhaltselement "Elo-Liste".
 *
 * Gibt eine einzelne Wertung einer Monatsliste als Rangliste aus. Die Liste
 * kann fest gewählt werden; ohne Auswahl wird die jüngste veröffentlichte
 * genommen.
 */
class EloArchiv extends ContentElement
{
	/**
	 * @var string Template des Inhaltselements
	 */
	protected $strTemplate = 'ce_eloliste';

	/**
	 * Erzeugt die Ausgabe des Inhaltselements.
	 *
	 * @return void
	 */
	protected function compile(): void
	{
		// Das Template wird bewusst NICHT neu erzeugt: Contao hat es in
		// generate() bereits angelegt und mit den Daten des Inhaltselements
		// gefüllt. Die frühere Neuanlage warf genau diese Daten weg, weshalb im
		// Template $this->hl leer blieb und die Überschrift als "<></>"
		// ausgegeben wurde; cssID und style fehlten ebenfalls.
		$this->Template->class = $this->strTemplate;

		// Ohne Häkchen gilt 0 als "jüngste veröffentlichte Liste"
		$liste = $this->eloliste_checkbox ? (int) $this->eloliste_id : 0;

		$this->Template->elo = $this->getEloliste($liste, (string) $this->eloliste_typ, (int) $this->eloliste_number);
	}

	/**
	 * Lädt die Rangliste einer Wertung.
	 *
	 * @param int    $listid   ID der Elo-Liste, oder 0 für die jüngste
	 *                         veröffentlichte
	 * @param string $listtype Wertung: eloN, eloB, eloR jeweils gesamt,
	 *                         mit angehängtem "w" nur Frauen
	 * @param int    $count    Anzahl der auszugebenden Plätze
	 *
	 * @return array<string,mixed> Mit den Schlüsseln "headline" und "liste";
	 *                             "liste" ist leer, wenn es keine passende
	 *                             Elo-Liste oder keine Spieler darin gibt
	 */
	private function getEloliste(int $listid, string $listtype, int $count): array
	{
		$sortierung = $this->sortierung($listtype);

		if ('' === $sortierung)
		{
			return array('headline' => $this->headline, 'liste' => array());
		}

		// Die Cachedauer steht als Anzahl Tage in den Contao-Einstellungen
		$dauer = 3600 * 24 * max(1, (int) Config::get('eloliste_cachetime'));

		return Zwischenspeicher::hole(
			'elo_archiv',
			$listtype.'_'.$count.'_'.$listid,
			$dauer,
			function () use ($listid, $listtype, $count, $sortierung): array {
				return $this->ermittle($listid, $listtype, $count, $sortierung);
			}
		);
	}

	/**
	 * Baut die Rangliste aus der Datenbank auf.
	 *
	 * Spieler mit derselben Elo-Zahl teilen sich den Platz: Bei Gleichstand
	 * bleibt die Platzziffer leer.
	 *
	 * @param int    $listid     ID der Elo-Liste, oder 0 für die jüngste
	 * @param string $listtype   Kennung der Wertung
	 * @param int    $count      Anzahl der auszugebenden Plätze
	 * @param string $sortierung Der an die Abfrage anzuhängende SQL-Teil
	 *
	 * @return array<string,mixed> Überschrift und Rangliste
	 */
	private function ermittle(int $listid, string $listtype, int $count, string $sortierung): array
	{
		if (!$listid)
		{
			$objActiv = Database::getInstance()
				->prepare('SELECT id, title FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
				->limit(1)
				->execute('1');
		}
		else
		{
			$objActiv = Database::getInstance()
				->prepare('SELECT id, title FROM tl_elo_listen WHERE id=?')
				->execute($listid);
		}

		if (!$objActiv->numRows)
		{
			return array('headline' => $this->headline, 'liste' => array());
		}

		$listid = (int) $objActiv->id;

		// Platzhalter in der Überschrift ersetzen
		$headline = str_replace(
			array('%anzahl%', '%monat%'),
			array((string) $count, (string) $objActiv->title),
			(string) $this->headline
		);

		$objElo = Database::getInstance()
			->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sortierung)
			->limit($count)
			->execute($listid, '1', '%i%');

		$liste = array();
		$oldelo = null;
		$i = 0;

		while ($objElo->next())
		{
			$elo = $this->wertung($objElo, $listtype);
			++$i;

			$liste[] = array
			(
				'rank'  => ($oldelo === $elo) ? '' : $i.'.',
				'name'  => trim($objElo->intent.' '.$objElo->prename.' '.$objElo->surname),
				'elo'   => $elo,
				'fid'   => $objElo->fideid,
				'title' => $objElo->title ? $objElo->title.' ' : ($objElo->w_title ? $objElo->w_title.' ' : ''),
			);

			$oldelo = $elo;
		}

		return array('headline' => $headline, 'liste' => $liste);
	}

	/**
	 * Liefert die Sortierung samt Zusatzbedingung zur gewünschten Wertung.
	 *
	 * Anders als beim Modul "Topliste" werden hier nur Spieler ausgegeben, die
	 * in der jeweiligen Wertung überhaupt eine Zahl haben.
	 *
	 * @param string $listtype Kennung der Wertung
	 *
	 * @return string Der anzuhängende SQL-Teil, oder ein leerer String bei einer
	 *                unbekannten Kennung
	 */
	private function sortierung(string $listtype): string
	{
		switch ($listtype)
		{
			case 'eloN':
				return 'AND rating > 0 ORDER BY rating DESC';
			case 'eloB':
				return 'AND blitz_rating > 0 ORDER BY blitz_rating DESC';
			case 'eloR':
				return 'AND rapid_rating > 0 ORDER BY rapid_rating DESC';
			case 'eloNw':
				return "AND sex='F' AND rating > 0 ORDER BY rating DESC";
			case 'eloBw':
				return "AND sex='F' AND blitz_rating > 0 ORDER BY blitz_rating DESC";
			case 'eloRw':
				return "AND sex='F' AND rapid_rating > 0 ORDER BY rapid_rating DESC";
			default:
				return '';
		}
	}

	/**
	 * Liefert die zur Wertung passende Elo-Zahl des Spielers.
	 *
	 * @param object $objElo   Der Datensatz des Spielers
	 * @param string $listtype Kennung der Wertung
	 *
	 * @return int Die Elo-Zahl
	 */
	private function wertung($objElo, string $listtype): int
	{
		$art = substr($listtype, 0, 4);

		if ('eloN' === $art)
		{
			return (int) $objElo->rating;
		}

		if ('eloB' === $art)
		{
			return (int) $objElo->blitz_rating;
		}

		return (int) $objElo->rapid_rating;
	}
}
