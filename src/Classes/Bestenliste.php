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
class Bestenliste extends \Module
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

			$objTemplate->wildcard = '### ELO-BESTENLISTE ###';
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
		$cachekey = $this->elo_fromdate.'_'.$this->elo_todate.'_'.$this->elo_min.'_'.$this->elo_gender.'_'.$this->elo_topcount;

		// Cache initialisieren
		$this->cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache('Elobestenliste');
		$this->cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

		if($this->cache->isCached($cachekey))
		{
			// Daten aus dem Cache laden
			$result = $this->cache->retrieve($cachekey);
		}
		else
		{
			$sql = '';
			if($this->elo_fromdate) $sql .= 'AND tl_elo_listen.listmonth >= '.$this->elo_fromdate.' ';
			if($this->elo_todate) $sql .= 'AND tl_elo_listen.listmonth <= '.$this->elo_todate.' ';
			if($this->elo_gender == 'W') $sql .= 'AND tl_elo.sex = \'F\' ';

			// Elo laden
			$objElo = \Database::getInstance()->prepare('SELECT *, tl_elo_listen.title AS listentitel, tl_elo.title AS elotitel FROM tl_elo LEFT JOIN tl_elo_listen ON tl_elo.pid = tl_elo_listen.id WHERE tl_elo_listen.published = ? AND tl_elo.published = ? AND tl_elo_listen.listmonth > 0 AND tl_elo.rating >= ? '.$sql.'ORDER BY tl_elo.rating DESC, tl_elo_listen.listmonth ASC')
			//                                  ->limit(50)
			                                  ->execute(1, 1, $this->elo_min);

			// Elo zuweisen
			if($objElo->numRows > 1)
			{
				$result = array();
				$ids = array();
				// Datensätze anzeigen
				while($objElo->next())
				{
					if(!$ids[$objElo->fideid])
					{
						$line = $objElo->intent;
						$line .= ($line) ? ' '.$objElo->prename : $objElo->prename;
						$line .= ($line) ? ' '.$objElo->surname : $objElo->surname;
						$result[] = array
						(
							'monat' => $objElo->listentitel,
							'name'  => $line,
							'elo'   => $objElo->rating,
							'fid'   => $objElo->fideid,
							'title' => ($objElo->elotitel) ? $objElo->elotitel . ' ' : (($objElo->w_title) ? $objElo->w_title . ' ': ''),
						);
						$ids[$objElo->fideid] = true;
					}
					// Ausgabe auf Top-X beschränken, wenn gewünscht
					if($this->elo_topcount)
					{
						if(count($result) == $this->elo_topcount) break;
					}
				}
				// Daten im Cache speichern
				$this->cache->store($cachekey, $result, $cachetime);
			}
		}

		// Ausgabe schreiben
		$content = '<table>';
		$content .= '<tr>';
		$content .= '<th>Platz</th>';
		$content .= '<th>Name</th>';
		$content .= '<th>Titel</th>';
		$content .= '<th>Elo</th>';
		$content .= '<th>Monat</th>';
		$content .= '</tr>';
		$altelo = 0;
		for($x = 0; $x < count($result); $x++)
		{
			$content .= '<tr>';
			if($altelo == $result[$x]['elo']) $content .= '<td class="place"></td>';
			else $content .= '<td class="place">'.($x+1).'</td>';
			$content .= '<td class="name">'.$result[$x]['name'].'</td>';
			$content .= '<td class="titel">'.$result[$x]['title'].'</td>';
			$content .= '<td class="elo">'.$result[$x]['elo'].'</td>';
			$content .= '<td class="monat">'.$result[$x]['monat'].'</td>';
			$content .= '</tr>';
			$altelo = $result[$x]['elo'];
		}
		$content .= '</table>';

		$this->Template->content = $content;

	}

}
