<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   fen
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2013
 */

/**
 * Palette
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['eloliste'] = '{type_legend},type,headline;{eloliste_legend},eloliste_id,eloliste_typ,eloliste_number;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';

/**
 * Felder
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['eloliste_id'] = array
(
	'label'                => &$GLOBALS['TL_LANG']['tl_content']['eloliste_id'],
	'exclude'              => true,
	'options_callback'     => array('tl_content_eloliste', 'getEloliste'),
	'inputType'            => 'select',
	'eval'                 => array
	(
		'mandatory'      => true,
		'multiple'       => false,
		'chosen'         => true,
		'submitOnChange' => true,
		'tl_class'       => 'w50'
	),
	'sql'                  => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['eloliste_typ'] = array
(
	'label'                => &$GLOBALS['TL_LANG']['tl_content']['eloliste_typ'],
	'inputType'            => 'select',
	'options'              => array
	(
		'eloN'           => 'Normalschach-Elo alle',
		'eloR'           => 'Schnellschach-Elo alle',
		'eloB'           => 'Blitzschach-Elo alle',
		'eloNw'          => 'Normalschach-Elo Frauen',
		'eloRw'          => 'Schnellschach-Elo Frauen',
		'eloBw'          => 'Blitzschach-Elo Frauen',
	),
	'eval'                 => array
	(
		'tl_class'       => 'w50'
	),
	'sql'                  => "varchar(5) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['eloliste_number'] = array
(
	'label'                => &$GLOBALS['TL_LANG']['tl_content']['eloliste_number'],
	'inputType'            => 'text',
	'default'              => 100,
	'eval'                 => array
	(
		'tl_class'        => 'w50',
		'maxlength'       => 10,
		'rgxp'            => 'digit'
	),
	'sql'                  => "int(10) unsigned NOT NULL default '100'"
);

/**
 * Class tl_content_eloliste
 */
class tl_content_eloliste extends Backend
{ 
	/**
	 * Entfernt die am Ende überflüssigen Zeichen aus dem FEN-Code
	 * @param mixed
	 * @param \DataContainer
	 * @return mixed
	 */
	public function getEloliste(DataContainer $dc)
	{
		$array = array();
		$objListe = \Database::getInstance()->prepare('SELECT * FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
		                                    ->execute(1);

		while($objListe->next())
		{
			$array[$objListe->id] = $objListe->title.' ('.$objListe->datum.')';
		}
		return $array;

	}
}
