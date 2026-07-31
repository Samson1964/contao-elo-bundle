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
 * Frontend-Modul "Ewige Elo-Bestenliste".
 *
 * Sucht über alle veröffentlichten Monatslisten hinweg für jeden Spieler die
 * höchste je erreichte Elo-Zahl und stellt daraus eine Rangliste zusammen.
 */
class Bestenliste extends EloModul
{
	/**
	 * @var string Template des Moduls
	 */
	protected $strTemplate = 'mod_elobestenliste';

	/**
	 * @var string Text des Platzhalters im Backend
	 */
	protected $strPlatzhalter = '### ELO-BESTENLISTE ###';

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
		$schluessel = $this->elo_fromdate.'_'.$this->elo_todate.'_'.$this->elo_min.'_'.$this->elo_gender.'_'.$this->elo_topcount;

		$result = Zwischenspeicher::hole('elo_bestenliste', $schluessel, self::CACHEDAUER, function (): array {
			return $this->ermittle();
		});

		$this->Template->content = $this->tabelle($result);
	}

	/**
	 * Ermittelt je Spieler den höchsten Eintrag über alle Monatslisten.
	 *
	 * Die Abfrage sortiert absteigend nach Elo-Zahl; der erste Treffer eines
	 * Spielers ist damit sein bester. Spieler mit der Inaktiv-Markierung der
	 * FIDE bleiben außen vor.
	 *
	 * @return array<int,array<string,mixed>> Die Rangliste, absteigend sortiert
	 */
	private function ermittle(): array
	{
		$sql = '';

		// Die drei Werte stammen aus dem Modul-DCA und sind dort auf Ziffern
		// bzw. eine Auswahl begrenzt; sie werden zusätzlich hart auf Zahlen
		// gecastet, damit hier nichts Fremdes in die Abfrage gerät
		if ($this->elo_fromdate)
		{
			$sql .= 'AND tl_elo_listen.listmonth >= '.(int) $this->elo_fromdate.' ';
		}

		if ($this->elo_todate)
		{
			$sql .= 'AND tl_elo_listen.listmonth <= '.(int) $this->elo_todate.' ';
		}

		if ('W' === $this->elo_gender)
		{
			$sql .= "AND tl_elo.sex = 'F' ";
		}

		$objElo = Database::getInstance()
			->prepare('SELECT *, tl_elo_listen.title AS listentitel, tl_elo.title AS elotitel FROM tl_elo LEFT JOIN tl_elo_listen ON tl_elo.pid = tl_elo_listen.id WHERE tl_elo_listen.published = ? AND tl_elo.published = ? AND tl_elo_listen.listmonth > 0 AND tl_elo.rating >= ? AND tl_elo.flag NOT LIKE ? '.$sql.'ORDER BY tl_elo.rating DESC, tl_elo_listen.listmonth ASC')
			->execute('1', '1', (int) $this->elo_min, '%i%');

		$result = array();
		$ids = array();

		while ($objElo->next())
		{
			// Je Spieler zählt nur der erste (= beste) Treffer
			if (isset($ids[$objElo->fideid]))
			{
				continue;
			}

			$result[] = array
			(
				'monat' => $objElo->listentitel,
				'name'  => trim($objElo->intent.' '.$objElo->prename.' '.$objElo->surname),
				'elo'   => (int) $objElo->rating,
				'fid'   => $objElo->fideid,
				'title' => $objElo->elotitel ? $objElo->elotitel.' ' : ($objElo->w_title ? $objElo->w_title.' ' : ''),
			);

			$ids[$objElo->fideid] = true;

			if ($this->elo_topcount && \count($result) >= (int) $this->elo_topcount)
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Baut die Ausgabetabelle.
	 *
	 * Bei gleicher Elo-Zahl bleibt die Platzziffer leer, damit Gleichstände als
	 * solche erkennbar sind.
	 *
	 * @param array<int,array<string,mixed>> $result Die Rangliste
	 *
	 * @return string Die Tabelle als HTML; bei leerer Rangliste nur der
	 *                Tabellenkopf
	 */
	private function tabelle(array $result): string
	{
		$content = '<table>';
		$content .= '<tr>';
		$content .= '<th class="head_0 col_first">Platz</th>';
		$content .= '<th class="head_1">Name</th>';
		$content .= '<th class="head_2">Titel</th>';
		$content .= '<th class="head_3">Elo</th>';
		$content .= '<th class="head_4 col_last">Monat</th>';
		$content .= '</tr>';

		$altelo = 0;
		$odd = 'odd';
		$anzahl = \count($result);

		for ($x = 0; $x < $anzahl; ++$x)
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
			$content .= '<th class="col_0 col_first place">'.($altelo === $result[$x]['elo'] ? '' : $x + 1).'</th>';
			$content .= '<td class="col_1 name">'.$result[$x]['name'].'</td>';
			$content .= '<td class="col_2 titel">'.$result[$x]['title'].'</td>';
			$content .= '<td class="col_3 elo">'.$result[$x]['elo'].'</td>';
			$content .= '<td class="col_4 col_last monat">'.$result[$x]['monat'].'</td>';
			$content .= '</tr>';

			$altelo = $result[$x]['elo'];
		}

		return $content.'</table>';
	}
}
