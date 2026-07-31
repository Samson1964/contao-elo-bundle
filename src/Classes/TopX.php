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
 * Frontend-Modul "Elo-Toplisten mehrerer Monate".
 *
 * Stellt je Monatsliste die ersten Plätze nebeneinander dar, sodass sich die
 * Entwicklung der Spitze über die Monate hinweg ablesen lässt.
 */
class TopX extends EloModul
{
	/**
	 * @var string Template des Moduls
	 *
	 * Wie die Bestenliste gibt auch dieses Modul eine fertige Tabelle aus und
	 * nutzt deshalb dasselbe schlanke Template.
	 */
	protected $strTemplate = 'mod_elobestenliste';

	/**
	 * @var string Text des Platzhalters im Backend
	 */
	protected $strPlatzhalter = '### ELO-TOPLISTEN ###';

	/**
	 * Haltbarkeit der zwischengespeicherten Auswertung in Sekunden (40 Tage).
	 */
	private const CACHEDAUER = 3600 * 24 * 40;

	/**
	 * Befüllt das Template mit der fertigen Tabelle.
	 *
	 * @return void
	 */
	protected function compile(): void
	{
		$schluessel = $this->elo_fromdate.'_'.$this->elo_todate.'_'.$this->elo_topx.'_'.$this->elo_gender.'_'.$this->elo_fidelink;

		$result = Zwischenspeicher::hole('elo_toplisten', $schluessel, self::CACHEDAUER, function (): array {
			return $this->ermittle();
		});

		$this->Template->content = $this->tabelle($result);
	}

	/**
	 * Sammelt je Monatsliste die besten Spieler ein.
	 *
	 * Ohne gesetzte Grenzen wird der gesamte Zeitraum ausgewertet; die
	 * Vorgabewerte 196000 und 999900 umschließen jede denkbare Monatsangabe im
	 * Format JJJJMM.
	 *
	 * @return array<int|string,array<int,array<string,mixed>>> Monat => Plätze;
	 *                                                          leer, wenn keine
	 *                                                          Liste passt
	 */
	private function ermittle(): array
	{
		$sql = ('W' === $this->elo_gender) ? "AND sex = 'F' " : '';
		$vonListe = $this->elo_fromdate ? (int) $this->elo_fromdate : 196000;
		$bisListe = $this->elo_todate ? (int) $this->elo_todate : 999900;

		$objListe = Database::getInstance()
			->prepare('SELECT id, title, listmonth FROM tl_elo_listen WHERE published = ? AND listmonth >= ? AND listmonth <= ? ORDER BY listmonth DESC')
			->execute('1', $vonListe, $bisListe);

		$result = array();

		while ($objListe->next())
		{
			$objElo = Database::getInstance()
				->prepare('SELECT * FROM tl_elo WHERE pid = ? AND published = ? '.$sql.'ORDER BY rating DESC')
				->limit((int) $this->elo_topx)
				->execute($objListe->id, '1');

			while ($objElo->next())
			{
				$result[$objListe->listmonth][] = array
				(
					'monat' => $objListe->title,
					'name'  => trim($objElo->intent.' '.$objElo->prename.' '.$objElo->surname),
					'elo'   => (int) $objElo->rating,
					'fid'   => $objElo->fideid,
					'titel' => $objElo->title ? $objElo->title.' ' : ($objElo->w_title ? $objElo->w_title.' ' : ''),
				);
			}
		}

		return $result;
	}

	/**
	 * Baut die Ausgabetabelle: je Zeile eine Monatsliste, je Spalte ein Platz.
	 *
	 * @param array<int|string,array<int,array<string,mixed>>> $result Monat => Plätze
	 *
	 * @return string Die Tabelle als HTML; bei leerem Ergebnis nur der
	 *                Tabellenkopf
	 */
	private function tabelle(array $result): string
	{
		$plaetze = (int) $this->elo_topx;

		$content = '<table>';
		$content .= '<tr>';
		$content .= '<th class="head_0 col_first">Liste</th>';

		for ($z = 1; $z <= $plaetze; ++$z)
		{
			$content .= '<th class="head_'.$z.'">'.$z.'. Platz</th>';
		}

		if ($this->elo_fidelink)
		{
			$content .= '<th class="head_'.($plaetze + 1).' col_last">Link</th>';
		}

		$content .= '</tr>';

		$odd = 'odd';
		$x = 0;
		$anzahl = \count($result);

		foreach ($result as $zeile)
		{
			$class = 'row_'.$x.' ';

			if (0 === $x)
			{
				$class .= 'row_first ';
			}
			elseif ($x + 1 === $anzahl)
			{
				$class .= 'row_last ';
			}

			$class .= $odd;
			$odd = ('odd' === $odd) ? 'even' : 'odd';

			$content .= '<tr class="'.$class.'">';
			$content .= '<th class="col_0 col_first monat">'.($zeile[0]['monat'] ?? '').'</th>';

			for ($z = 0; $z < $plaetze; ++$z)
			{
				// Eine Monatsliste kann weniger Spieler enthalten als Plätze
				// gefordert sind; die restlichen Zellen bleiben leer
				$platz = $zeile[$z] ?? null;
				$text = null === $platz ? '' : trim($platz['titel'].' '.$platz['name'].' '.$platz['elo']);

				$content .= '<td class="col_'.($z + 1).' name">'.$text.'</td>';
			}

			if ($this->elo_fidelink)
			{
				$content .= '<td class="col_'.($plaetze + 1).' col_last link"></td>';
			}

			$content .= '</tr>';
			++$x;
		}

		return $content.'</table>';
	}
}
