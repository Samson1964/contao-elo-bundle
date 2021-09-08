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
class Statistik extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_elostatistik';
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

			$objTemplate->wildcard = '### ELO-STATISTIK ###';
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
		$cachekey = $this->elo_liste.'_'.$this->elo_statistik;

		// Cache initialisieren
		$this->cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache('Elostatistik');
		$this->cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

		if($this->cache->isCached($cachekey))
		{
			// Daten aus dem Cache laden
			$result = $this->cache->retrieve($cachekey);
		}
		else
		{
			if($this->elo_liste)
			{
				// Bestimmte Elo-Liste angefordert
				$liste = $this->elo_liste;
			}
			else
			{
				// Aktuelle Elo-Liste angefordert, jetzt diese ermitteln
				$objListe = \Database::getInstance()->prepare('SELECT id FROM tl_elo_listen WHERE published = ? ORDER BY listmonth DESC')
				                                    ->limit(1)
				                                    ->execute(1);
				if($objListe->numRows)
				{
					$liste = $objListe->id;
				}
			}

			$abfrage = unserialize($this->elo_statistik); // Welche Statistiken werden gewünscht?
			$result = array(); // Nimmt die Statistiken auf
			foreach($abfrage as $value)
			{
				$statname = $GLOBALS['TL_LANG']['tl_module']['elo_statistik_options'][$value];
				switch($value)
				{
					case '1': // Anzahl aller Spieler
						$sql = ''; break;
					case '2': // Anzahl der Spieler mit Elo-Zahl
						$sql = ' AND rating > 0'; break;
					case '3': // Anzahl der Spieler ohne Elo-Zahl
						$sql = ' AND rating = 0'; break;
					case '4': // Anzahl der Spieler mit GM-Titel
						$sql = " AND title = 'GM'"; break;
					case '5': // Anzahl der Spieler mit IM-Titel
						$sql = " AND title = 'IM'"; break;
					case '6': // Anzahl der Spieler mit FM-Titel
						$sql = " AND title = 'FM'"; break;
					case '7': // Anzahl der Spieler mit CM-Titel
						$sql = " AND title = 'CM'"; break;
					case '8': // Anzahl der Spieler mit WGM-Titel
						$sql = " AND title = 'WGM'"; break;
					case '9': // Anzahl der Spieler mit WIM-Titel
						$sql = " AND title = 'WIM'"; break;
					case '10': // Anzahl der Spieler mit WFM-Titel
						$sql = " AND title = 'WFM'"; break;
					case '11': // Anzahl der Spieler mit WCM-Titel
						$sql = " AND title = 'WCM'"; break;
					case '12': // Anzahl der Spieler mit Elo 2500 und höher
						$sql = ' AND rating > 2499'; break;
					case '13': // Anzahl der Spieler mit Elo 2400 und höher
						$sql = ' AND rating > 2399'; break;
					case '14': // Anzahl der Spieler mit Elo 2300 und höher
						$sql = ' AND rating > 2299'; break;
					case '15': // Anzahl der Spieler mit Elo 2200 und höher
						$sql = ' AND rating > 2199'; break;
					case '16': // Anzahl der Spieler mit Elo 2100 und höher
						$sql = ' AND rating > 2099'; break;
					case '17': // Anzahl der Spieler mit Elo 2000 und höher
						$sql = ' AND rating > 1999'; break;
					default:
						$sql = '';
				}
				// Abfrage starten
				$objElo = \Database::getInstance()->prepare('SELECT * FROM tl_elo WHERE published = ? AND pid = ?'.$sql)
				                                  ->execute(1, $liste);
				// Wert zuweisen
				$result[] =array
				(
					'name'  => $statname,
					'value' => $objElo->numRows,
				);
			}

			// Daten im Cache speichern
			$this->cache->store($cachekey, $result, $cachetime);
		}

		// Ausgabe schreiben
		$this->Template->statistik = $result;

	}

}
