<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;
use Schachbulle\ContaoEloBundle\ContaoEloBundle;

/**
 * Tabelle tl_elo
 *
 * Kindtabelle von tl_elo_listen: Ein Datensatz ist ein Spieler einer
 * FIDE-Monatsliste. Die Felder bilden die Spalten der FIDE-XML ab; "pid"
 * verweist auf die Liste, zu der der Spieler gehört.
 */
$GLOBALS['TL_DCA']['tl_elo'] = array
(

	// Konfiguration
	'config' => array
	(
		// Contao 5 erwartet den vollqualifizierten Klassennamen; Contao 4.13 versteht
		// ihn seit 4.9 ebenfalls und verwarnt umgekehrt den Kurznamen "Table"
		'dataContainer'             => DC_Table::class,
		'ptable'                    => 'tl_elo_listen',
		'enableVersioning'          => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'                            => 'primary',
				'pid'                           => 'index',
				'fideid'                        => 'index',
				'surname'                       => 'index',
				'rating'                        => 'index',
				'games'                         => 'index',
				'rapid_rating'                  => 'index',
				'rapid_games'                   => 'index',
				'blitz_rating'                  => 'index',
				'blitz_games'                   => 'index',
				'pid,published,flag,sex,rating' => 'index'
			)
		)
	),

	// Datensätze auflisten
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4, // DataContainer::MODE_PARENT
			'fields'                  => array('surname'),
			'flag'                    => 1, // DataContainer::SORT_INITIAL_LETTER_ASC
			// Kopfzeile der Elternansicht: Daten der Liste, zu der die Spieler gehören
			'headerFields'            => array('title', 'listmonth', 'datum'),
			'panelLayout'             => 'sort,filter;search,limit',
			'child_record_class'      => 'no_padding',
			// Die Zeilendarstellung wird weiter unten versionsabhängig gesetzt
		),
		'label' => array
		(
			'fields'                  => array('surname', 'prename'),
			'format'                  => '%s'
		),
		// global_operations und operations werden weiter unten versionsabhängig gesetzt
	),

	// Paletten
	'palettes' => array
	(
		'__selector__'                => array(''),
		'default'                     => '{name_legend},surname,prename,intent,birthday,sex,country;{fide_legend},fideid,title,w_title,o_title,foa_title;{flag_legend},flag,rapid_flag,blitz_flag;{elo_legend},rating,games,rapid_rating,rapid_games,blitz_rating,blitz_games;{publish_legend},published'
	),

	// Unterpaletten
	'subpalettes' => array
	(
		''                            => ''
	),

	// Felder
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'fideid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['fideid'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 16,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(16) unsigned NOT NULL default '0'"
		),
		'surname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['surname'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 64,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'prename' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['prename'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 64,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'intent' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['intent'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 16,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(16) NOT NULL default ''"
		),
		'country' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['country'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'sex' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['sex'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 1,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(1) NOT NULL default ''"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['title'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'w_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['w_title'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'o_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['o_title'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'foa_title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['foa_title'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 3,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(3) NOT NULL default ''"
		),
		'flag' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['flag'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 8,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(8) NOT NULL default ''"
		),
		'rapid_flag' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['rapid_flag'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 8,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "varchar(8) NOT NULL default ''"
		),
		'blitz_flag' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['blitz_flag'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 8,
				'tl_class'            => 'w50 clr'
			),
			'sql'                     => "varchar(8) NOT NULL default ''"
		),
		'rating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['rating'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'games' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['games'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'rapid_rating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['rapid_rating'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'rapid_games' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['rapid_games'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'blitz_rating' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['blitz_rating'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'blitz_games' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['blitz_games'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 4,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(4) unsigned NOT NULL default '0'"
		),
		'birthday' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['birthday'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 8,
				'tl_class'            => 'w50'
			),
			'sql'                     => "int(8) unsigned NOT NULL default '0'"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_elo']['published'],
			'toggle'                  => true, // Aktiviert den Contao-eigenen Schnellschalter in der Übersicht
			'exclude'                 => true,
			'search'                  => false,
			'sorting'                 => false,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array
			(
				'tl_class'            => 'w50',
				'isBoolean'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);

/*
 * Zeilendarstellung und Operationen versionsabhängig setzen.
 *
 * Die Zeile der Elternansicht baut in beiden Fällen tl_elo::zeile() zusammen,
 * angesprochen wird sie aber unterschiedlich: Contao 4.13 kennt nur den
 * child_record_callback, Contao 5.7 verwarnt diesen und erwartet stattdessen
 * einen label_callback. Die beiden Einstiegsmethoden unterscheiden sich nur
 * darin, dass Contao 4.13 fertiges HTML einsetzt und Contao 5 reinen Text.
 *
 * Bei den Operationen kennt Contao 5 die Kurzschreibweise, bei der Label und
 * Icon aus dem Kern stammen; Contao 4.13 benötigt vollständige Arrays. Der
 * Toggler läuft in beiden Versionen über das Contao-eigene "act=toggle" — die
 * frühere Umsetzung über codefog/contao-haste ist entfallen, weil Haste nicht
 * für Contao 5 verfügbar ist.
 */
if (ContaoEloBundle::isContao5())
{
	$GLOBALS['TL_DCA']['tl_elo']['list']['label']['label_callback'] = array('tl_elo', 'listPlayersLabel');

	$GLOBALS['TL_DCA']['tl_elo']['list']['global_operations'] = array
	(
		'all'
	);

	$GLOBALS['TL_DCA']['tl_elo']['list']['operations'] = array
	(
		'edit',
		'copy',
		'delete',
		'toggle',
		'show'
	);
}
else
{
	$GLOBALS['TL_DCA']['tl_elo']['list']['sorting']['child_record_callback'] = array('tl_elo', 'listPlayers');

	$GLOBALS['TL_DCA']['tl_elo']['list']['global_operations'] = array
	(
		'all' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
			'href'                => 'act=select',
			'class'               => 'header_edit_all',
			'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
		)
	);

	$GLOBALS['TL_DCA']['tl_elo']['list']['operations'] = array
	(
		'edit' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo']['edit'],
			'href'                => 'act=edit',
			'icon'                => 'edit.svg'
		),
		'copy' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo']['copy'],
			'href'                => 'act=copy',
			'icon'                => 'copy.svg'
		),
		'delete' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo']['delete'],
			'href'                => 'act=delete',
			'icon'                => 'delete.svg',
			'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"'
		),
		'toggle' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo']['toggle'],
			'href'                => 'act=toggle&amp;field=published',
			'icon'                => 'visible.svg'
		),
		'show' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo']['show'],
			'href'                => 'act=show',
			'icon'                => 'show.svg'
		)
	);
}

/**
 * Stellt die Zeilendarstellung der Elternansicht bereit.
 *
 * Die Klasse erbt bewusst nicht von Contao\Backend: Sie braucht nichts daraus,
 * und der Klassenalias "Backend" ohne Namensraum existiert in Contao 5 nicht
 * mehr. Contao erzeugt sie über System::importStatic(), wofür ein öffentlicher
 * Konstruktor genügt.
 */
class tl_elo
{
	/**
	 * Baut die Zeile eines Spielers für die Elternansicht in Contao 4.13.
	 *
	 * Contao setzt den Rückgabewert als fertiges HTML in die Zeile ein, deshalb
	 * das umschließende div.
	 *
	 * @param array $arrRow Der Datensatz des Spielers
	 *
	 * @return string Die Zeile als HTML
	 */
	public function listPlayers($arrRow): string
	{
		return '<div>'.$this->zeile($arrRow)."</div>\n";
	}

	/**
	 * Baut die Zeile eines Spielers für die Elternansicht in Contao 5.
	 *
	 * Contao 5 erwartet an dieser Stelle reinen Text und kümmert sich selbst um
	 * das Markup der Zeile.
	 *
	 * @param array       $arrRow Der Datensatz des Spielers
	 * @param string      $strLabel Das von Contao aus label.fields vorbereitete
	 *                              Label; wird hier durch die eigene Zeile ersetzt
	 * @param object|null $dc     Der DataContainer, hier nicht benötigt
	 * @param array       $args   Die einzelnen Label-Felder, hier nicht benötigt
	 *
	 * @return string Die Zeile als Text
	 */
	public function listPlayersLabel($arrRow, $strLabel = '', $dc = null, $args = array()): string
	{
		return $this->zeile($arrRow);
	}

	/**
	 * Setzt die Beschreibung eines Spielers zusammen.
	 *
	 * Ausgegeben werden Name und die vorhandenen Wertungszahlen. Fehlende Werte
	 * werden weggelassen, damit die Zeile bei einem Spieler ohne Blitz- oder
	 * Schnellschach-Elo nicht mit leeren Angaben endet.
	 *
	 * @param array $arrRow Der Datensatz des Spielers
	 *
	 * @return string Die zusammengesetzte Zeile ohne Markup
	 */
	private function zeile($arrRow): string
	{
		$line = (string) ($arrRow['surname'] ?? '');

		if ($arrRow['prename'] ?? '')
		{
			$line .= ', '.$arrRow['prename'];
		}

		if ($arrRow['intent'] ?? '')
		{
			$line .= ', '.$arrRow['intent'];
		}

		if ($arrRow['rating'] ?? 0)
		{
			$line .= ' - Elo '.$arrRow['rating'];
		}

		if ($arrRow['blitz_rating'] ?? 0)
		{
			$line .= ' - Blitz '.$arrRow['blitz_rating'];
		}

		if ($arrRow['rapid_rating'] ?? 0)
		{
			$line .= ' - Rapid '.$arrRow['rapid_rating'];
		}

		return $line;
	}
}
