<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Classes;

use Contao\Database;
use Contao\StringUtil;

/**
 * Frontend-Modul "Elo-Statistik".
 *
 * Zählt zu einer Monatsliste aus, wie viele Spieler bestimmte Kriterien
 * erfüllen — etwa Spieler mit Großmeistertitel oder mit einer Elo ab 2500.
 */
class Statistik extends EloModul
{
	/**
	 * @var string Template des Moduls
	 */
	protected $strTemplate = 'mod_elostatistik';

	/**
	 * @var string Text des Platzhalters im Backend
	 */
	protected $strPlatzhalter = '### ELO-STATISTIK ###';

	/**
	 * Haltbarkeit der zwischengespeicherten Auswertung in Sekunden (40 Tage).
	 */
	private const CACHEDAUER = 3600 * 24 * 40;

	/**
	 * Bedingung je Auswertungsart.
	 *
	 * Die Schlüssel entsprechen den Werten der Auswahl im Modul-DCA
	 * (tl_module.elo_statistik). Der Wert wird an die Zählabfrage angehängt;
	 * eine leere Bedingung zählt alle Spieler der Liste.
	 *
	 * ACHTUNG: Die vier Frauentitel werden hier wie bisher gegen das Feld
	 * "title" geprüft. Die FIDE liefert sie aber im Feld "w_title" — diese vier
	 * Auswertungen dürften deshalb dauerhaft 0 ergeben. Das Verhalten wurde
	 * bewusst unverändert übernommen, weil es sich nur an echten Daten
	 * entscheiden lässt; siehe TODO.md.
	 */
	private const BEDINGUNGEN = array
	(
		'1'  => '',
		'2'  => ' AND rating > 0',
		'3'  => ' AND rating = 0',
		'4'  => " AND title = 'GM'",
		'5'  => " AND title = 'IM'",
		'6'  => " AND title = 'FM'",
		'7'  => " AND title = 'CM'",
		'8'  => " AND title = 'WGM'",
		'9'  => " AND title = 'WIM'",
		'10' => " AND title = 'WFM'",
		'11' => " AND title = 'WCM'",
		'12' => ' AND rating > 2499',
		'13' => ' AND rating > 2399',
		'14' => ' AND rating > 2299',
		'15' => ' AND rating > 2199',
		'16' => ' AND rating > 2099',
		'17' => ' AND rating > 1999',
	);

	/**
	 * Befüllt das Template mit den ausgezählten Werten.
	 *
	 * @return void
	 */
	protected function compile(): void
	{
		$schluessel = $this->elo_liste.'_'.md5((string) $this->elo_statistik);

		$this->Template->statistik = Zwischenspeicher::hole('elo_statistik', $schluessel, self::CACHEDAUER, function (): array {
			return $this->ermittle();
		});
	}

	/**
	 * Zählt die gewünschten Kriterien aus.
	 *
	 * Ist im Modul keine Liste ausgewählt, wird die jüngste veröffentlichte
	 * genommen. Gibt es überhaupt keine, bleibt die Auswertung leer.
	 *
	 * @return array<int,array<string,mixed>> Je Kriterium Name und Anzahl
	 */
	private function ermittle(): array
	{
		$liste = (int) $this->elo_liste;

		if (!$liste)
		{
			$objListe = Database::getInstance()
				->prepare('SELECT id FROM tl_elo_listen WHERE published = ? ORDER BY listmonth DESC')
				->limit(1)
				->execute('1');

			if (!$objListe->numRows)
			{
				return array();
			}

			$liste = (int) $objListe->id;
		}

		// deserialize() statt unserialize(): Contao speichert Mehrfachauswahlen
		// serialisiert, ein einzelner Wert steht aber roh in der Spalte
		$abfrage = StringUtil::deserialize($this->elo_statistik, true);

		$result = array();

		foreach ($abfrage as $value)
		{
			$value = (string) $value;

			if (!isset(self::BEDINGUNGEN[$value]))
			{
				continue;
			}

			$anzahl = Database::getInstance()
				->prepare('SELECT COUNT(*) AS anzahl FROM tl_elo WHERE published = ? AND pid = ?'.self::BEDINGUNGEN[$value])
				->execute('1', $liste)
				->anzahl;

			$result[] = array
			(
				'name'  => $GLOBALS['TL_LANG']['tl_module']['elo_statistik_options'][$value] ?? $value,
				'value' => (int) $anzahl,
			);
		}

		return $result;
	}
}
