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
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{eloliste_legend:hide},eloliste_cachetime';

/**
 * fields
 */

$GLOBALS['TL_DCA']['tl_settings']['fields']['eloliste_cachetime'] = array
(
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['eloliste_cachetime'],
	'inputType'     => 'text',
	'eval'          => array
	(
		'tl_class'  => 'w50',
		'rgxp'      => 'digit',
		'maxlength' => 3 
	)
);
