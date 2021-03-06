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
namespace Schachbulle\ContaoEloBundle\ContentElements;

/**
 * Class Elo
 *
 * @copyright  Frank Hoppe 2016
 * @author     Frank Hoppe
 * @package    Devtools
 */

class EloArchiv extends \ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_eloliste';
	var $monat;
	var $cachetime;

	/**
	 * Inhaltselement generieren
	 */
	protected function compile()
	{
		$this->Template = new \FrontendTemplate($this->strTemplate);

		$this->cachetime = 3600 * 24 * $GLOBALS['TL_CONFIG']['eloliste_cachetime'];

		// Nummer der Liste ermitteln
		if($this->eloliste_checkbox) $liste = $this->eloliste_id;
		else $liste = 0;

		// Cache initialisieren
		$this->cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache('Elo');
		$this->cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

		$this->Template->class = $this->strTemplate;
		$this->Template->elo = $this->getEloliste($liste, $this->eloliste_typ, $this->eloliste_number);
		return;

	}

	/**
	 * Eloliste aus Datenbank laden
	 * @param integer $listid         ID der Eloliste
	 * @param string $listtyp         Typ der Eloliste
	 * @param integer $count          Anzahl der Topeinträge der Eloliste
	 * @return array                  Gefundene Datensätze
	 */
	function getEloliste($listid, $listtype, $count)
	{

		log_message($listid, 'elo.log');
		if($listid == 0)
		{
			// Aktuellste Elo-Liste ist gewünscht
			$objActiv = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
			                                    ->limit(1)
			                                    ->execute(1);
			$listid = $objActiv->id;
		}
		else
		{
			// Stammdaten der gewünschten Eloliste laden
			$objActiv = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE id=?')
			                                    ->execute($listid);
		}

		switch($listtype)
		{
			case 'eloN':
				$sql = 'AND rating > 0 ORDER BY rating DESC';
				break;
			case 'eloB':
				$sql = 'AND blitz_rating > 0 ORDER BY blitz_rating DESC';
				break;
			case 'eloR':
				$sql = 'AND rapid_rating > 0 ORDER BY rapid_rating DESC';
				break;
			case 'eloNw':
				$sql = 'AND sex=\'F\' AND rating > 0 ORDER BY rating DESC';
				break;
			case 'eloBw':
				$sql = 'AND sex=\'F\' AND blitz_rating > 0 ORDER BY blitz_rating DESC';
				break;
			case 'eloRw':
				$sql = 'AND sex=\'F\' AND rapid_rating > 0 ORDER BY rapid_rating DESC';
				break;
			default:
		}

		$cachekey = $listtype.'_'.$count.'_'.$listid;

		if($this->cache->isCached($cachekey))
		{
			// Daten aus dem Cache laden
			$result = $this->cache->retrieve($cachekey);
		}
		else
		{
			// Daten aus der Datenbank laden
			$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE pid=? AND published=? AND flag NOT LIKE ? '.$sql)
			                                  ->limit($count)
			                                  ->execute($listid, 1, '%i%');

			// Überschrift ändern
			$this->headline = str_replace('%anzahl%',$count,$this->headline);
			$this->headline = str_replace('%monat%',$objActiv->title,$this->headline);

			// Ausgabe-Array initialisieren
			$result = array();
			$result['headline'] = $this->headline;
			$result['liste'] = array();
			
			// Elo zuweisen
			if($objElo->numRows > 1)
			{
				
				$i = 0;

				// Datensätze anzeigen
				while($objElo->next()) 
				{

					$line = $objElo->intent;
					$line .= ($line) ? ' '.$objElo->prename : $objElo->prename;
					$line .= ($line) ? ' '.$objElo->surname : $objElo->surname;
					$elo = (substr($listtype,0,4) == 'eloN') ? $objElo->rating : ((substr($listtype,0,4) == 'eloB') ? $objElo->blitz_rating : $objElo->rapid_rating);
					$i++;

					$result['liste'][] = array
					(
						'rank' 	=> ($oldelo == $elo) ? '' : $i.'.',
						'name' 	=> $line,
						'elo'  	=> $elo,
						'fid' 	=> $objElo->fideid,
						'title'	=> ($objElo->title) ? $objElo->title . ' ' : (($objElo->w_title) ? $objElo->w_title . ' ': ''),
					);

					$oldelo = $elo;

				}
				// Daten im Cache speichern
				$this->cache->store($cachekey, $result, $this->cachetime);
			}
		}

		return $result;
	}
}
