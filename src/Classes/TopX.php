<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   Elo
 * @author    Frank Hoppe
 * @license   GNU/LPGL
 * @copyright Frank Hoppe 2016
 */


/**
 * Namespace
 */
namespace Schachbulle\ContaoEloBundle\Classes;

/**
 * Class Elo
 *
 * @copyright  Frank Hoppe 2016
 * @author     Frank Hoppe
 * @package    Devtools
 */
class TopX extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_elobestenliste';
	var $cache = false;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ELO-TOPXLISTE ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$cachetime = 3600 * 24 * 40; // 40 Tage
		$cachekey = $this->elo_fromdate.'_'.$this->elo_todate.'_'.$this->elo_topx.'_'.$this->elo_gender.'_'.$this->elo_fidelink;

		// Cache initialisieren
		$this->cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache('Elotopxliste');
		$this->cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

		if($this->cache->isCached($cachekey))
		{
			// Daten aus dem Cache laden
			$result = $this->cache->retrieve($cachekey);
		}
		else
		{
			// Liste der veröffentlichen Elo-Listen ermitteln
			if($this->elo_gender == 'W') $sql = 'AND sex = \'F\' ';
			$vonListe = ($this->elo_fromdate) ? $this->elo_fromdate : 196000;
			$bisListe = ($this->elo_todate) ? $this->elo_todate : 999900;
			$objListe = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published = ? AND listmonth >= ? AND listmonth <= ? ORDER BY listmonth DESC')
			                                    ->execute(1, $vonListe, $bisListe);

			// Gewünschte Elolisten abarbeiten
			if($objListe->numRows > 1)
			{
				$result = array();
				while($objListe->next())
				{
					$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE pid = ? AND published = ? '.$sql.'ORDER BY rating DESC')
					                                  ->limit($this->elo_topx)
					                                  ->execute($objListe->id, 1);
					if($objElo->numRows > 1)
					{
						while($objElo->next())
						{
							$line = $objElo->intent;
							$line .= ($line) ? ' '.$objElo->prename : $objElo->prename;
							$line .= ($line) ? ' '.$objElo->surname : $objElo->surname;
							$result[$objListe->listmonth][] = array
							(
								'monat' => $objListe->title,
								'name'  => $line,
								'elo'   => $objElo->rating,
								'fid'   => $objElo->fideid,
								'titel' => $objElo->title ? $objElo->title . ' ' : ($objElo->w_title ? $objElo->w_title . ' ': ''),
							);
						}
					}
				}
				// Daten im Cache speichern
				//$this->cache->store($cachekey, $result, $cachetime);
			}
		}

		// Ausgabe schreiben
		$content = '<table>';
		$content .= '<tr>';
		$content .= '<th class="head_0 col_first">Liste</th>';
		for($z = 1; $z <= $this->elo_topx; $z++)
		{
			$content .= '<th class="head_'.$z.'">'.$z.'. Platz</th>';
		}
		if($this->elo_fidelink) $content .= '<th class="head_4 col_last">Link</th>';
		$content .= '</tr>';
		$odd = 'odd';
		$x = 0;
		foreach($result as $key => $value)
		{
			$class = 'row_'.$x.' ';
			if($x == 0) $class .= 'row_first ';
			elseif($x + 1 == count($result)) $class .= 'row_last ';
			$class .= $odd;
			if($odd == 'odd') $odd = 'even';
			else $odd = 'odd';
			// Daten zuordnen
			$monat = $result[$key][0]['monat'];
			for($y = 0; $y < count($result[$key]); $y++)
			{
				$platz[$y] = $result[$key][$y]['titel'] ? $result[$key][$y]['titel'].' '.$result[$key][$y]['name'].' '.$result[$key][$y]['elo'] : $result[$key][$y]['name'].' '.$result[$key][$y]['elo'];
			}
			
			$content .= '<tr class="'.$class.'">';
			if($altelo == $result[$x]['elo']) $content .= '<td class="col_0 col_first place">'.$monat.'</td>';
			else $content .= '<td class="col_0 col_first monat">'.$monat.'</td>';
			for($z = 1; $z <= $this->elo_topx; $z++)
			{
				$content .= '<td class="col_'.$z.' name">'.$platz[$z-1].'</td>';
			}
			if($this->elo_fidelink) $content .= '<td class="col_4 col_last link"></td>';
			$content .= '</tr>';
			$x++;
		}
		$content .= '</table>';

		$this->Template->content = $content;

	}

}
