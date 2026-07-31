<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Classes;

use Contao\Database;

/**
 * Frontend-Modul "Elo-Topliste eines Monats".
 *
 * Zeigt aus der jüngsten veröffentlichten Elo-Liste die besten Spieler in
 * sechs Wertungen: Normal-, Schnell- und Blitzschach, jeweils gesamt und
 * für Frauen.
 */
class Elo extends EloModul
{
	/**
	 * @var string Template des Moduls
	 */
	protected $strTemplate = 'mod_elo';

	/**
	 * @var string Text des Platzhalters im Backend
	 */
	protected $strPlatzhalter = '### ELO-LISTE ###';

	/**
	 * Haltbarkeit der zwischengespeicherten Auswertungen in Sekunden.
	 *
	 * Vierzig Tage, damit eine Auswertung bis zur nächsten Monatsliste der FIDE
	 * gültig bleibt.
	 */
	private const CACHEDAUER = 3600 * 24 * 40;

	/**
	 * Befüllt das Template.
	 *
	 * Ohne veröffentlichte Liste bleibt die Ausgabe leer statt mit einem Fehler
	 * abzubrechen — im Frontend soll eine noch nicht gepflegte Elo-Verwaltung
	 * die Seite nicht zerstören.
	 *
	 * @return void
	 */
	protected function compile(): void
	{
		$objActiv = Database::getInstance()
			->prepare('SELECT id, title, datum FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
			->limit(1)
			->execute('1');

		$this->Template->headline = 'FIDE-Ratingliste Deutschland';
		$this->Template->hl = 'h2';

		// Die sechs Wertungen werden immer gesetzt, auch wenn es keine Liste
		// gibt: Das Template läuft in jedem Fall per foreach darüber
		foreach (array('eloN', 'eloB', 'eloR', 'eloNw', 'eloBw', 'eloRw') as $typ)
		{
			$this->Template->{$typ} = $objActiv->numRows
				? $this->getEloliste((int) $objActiv->id, $typ, (int) $this->elo_topcount)
				: array();
		}

		if (!$objActiv->numRows)
		{
			$this->Template->datum = '';
			$this->Template->count = 0;

			return;
		}

		$this->Template->datum = date('d.m.Y', (int) $objActiv->datum).' ('.$objActiv->title.')';
		$this->Template->count = $this->elo_topcount;
	}

	/**
	 * Lädt die besten Spieler einer Liste in einer bestimmten Wertung.
	 *
	 * Spieler mit der Inaktiv-Markierung der FIDE ("i" im Feld flag) bleiben
	 * außen vor, damit die Topliste den aktuellen Spielbetrieb abbildet.
	 *
	 * @param int    $listid   ID der Elo-Liste in tl_elo_listen
	 * @param string $listtype Wertung: eloN, eloB, eloR jeweils gesamt,
	 *                         mit angehängtem "w" nur Frauen
	 * @param int    $count    Anzahl der gewünschten Plätze
	 *
	 * @return array<int,array<string,mixed>> Die Spieler in der Reihenfolge der
	 *                                        Wertung; leer, wenn die Liste keine
	 *                                        passenden Spieler enthält
	 */
	private function getEloliste(int $listid, string $listtype, int $count): array
	{
		$sortierung = $this->sortierung($listtype);

		if ('' === $sortierung)
		{
			return array();
		}

		return Zwischenspeicher::hole(
			'elo_topliste',
			$listtype.'_'.$count.'_'.$listid,
			self::CACHEDAUER,
			function () use ($listid, $listtype, $count, $sortierung): array {
				$objElo = Database::getInstance()
					->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sortierung)
					->limit($count)
					->execute($listid, '1', '%i%');

				$result = array();

				while ($objElo->next())
				{
					$result[] = array
					(
						'name'  => $this->name($objElo),
						'elo'   => $this->wertung($objElo, $listtype),
						'fid'   => $objElo->fideid,
						'title' => $objElo->title ? $objElo->title.' ' : ($objElo->w_title ? $objElo->w_title.' ' : ''),
					);
				}

				return $result;
			}
		);
	}

	/**
	 * Liefert die Sortierung samt Zusatzbedingung zur gewünschten Wertung.
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
				return 'ORDER BY rating DESC';
			case 'eloB':
				return 'ORDER BY blitz_rating DESC';
			case 'eloR':
				return 'ORDER BY rapid_rating DESC';
			case 'eloNw':
				return "AND sex='F' ORDER BY rating DESC";
			case 'eloBw':
				return "AND sex='F' ORDER BY blitz_rating DESC";
			case 'eloRw':
				return "AND sex='F' ORDER BY rapid_rating DESC";
			default:
				return '';
		}
	}

	/**
	 * Setzt den anzuzeigenden Namen aus Titel, Vor- und Nachname zusammen.
	 *
	 * @param object $objElo Der Datensatz des Spielers
	 *
	 * @return string Der zusammengesetzte Name
	 */
	private function name($objElo): string
	{
		return trim($objElo->intent.' '.$objElo->prename.' '.$objElo->surname);
	}

	/**
	 * Liefert die zur Wertung passende Elo-Zahl des Spielers.
	 *
	 * @param object $objElo   Der Datensatz des Spielers
	 * @param string $listtype Kennung der Wertung
	 *
	 * @return int Die Elo-Zahl, 0 wenn der Spieler in dieser Wertung keine hat
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
