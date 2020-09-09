<?php
/**
 * Avatar for Contao Open Source CMS
 *
 * Copyright (C) 2013 Kirsten Roschanski
 * Copyright (C) 2013 Tristan Lins <http://bit3.de>
 *
 * @package    Avatar
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Add palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['elo_toplist']  = '{title_legend},name,headline,type;{options_legend},elo_topcount;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['elo_bestlist'] = '{title_legend},name,headline,type;{options_legend},elo_fromdate,elo_todate,elo_min,elo_gender,elo_topcount;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['elo_topx']     = '{title_legend},name,headline,type;{options_legend},elo_topx,elo_gender,elo_fromdate,elo_todate,elo_fidelink;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['elo_topcount'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_topcount'],
	'default'                            => 30,
	'exclude'                            => true,
	'inputType'                          => 'text',
	'eval'                               => array('tl_class'=>'w50', 'rgxp'=>'digit', 'maxlength'=>6),
	'sql'                                => "varchar(6) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['elo_fromdate'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_fromdate'],
	'exclude'                            => true,
	'inputType'                          => 'text',
	'eval'                               => array('tl_class'=>'w50 clr', 'rgxp'=>'digit', 'maxlength'=>6),
	'sql'                                => "varchar(6) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['elo_todate'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_todate'],
	'exclude'                            => true,
	'inputType'                          => 'text',
	'eval'                               => array('tl_class'=>'w50', 'rgxp'=>'digit', 'maxlength'=>6),
	'sql'                                => "varchar(6) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['elo_min'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_min'],
	'exclude'                            => true,
	'inputType'                          => 'text',
	'eval'                               => array('tl_class'=>'w50', 'rgxp'=>'digit', 'maxlength'=>4),
	'sql'                                => "varchar(4) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['elo_gender'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_gender'],
	'exclude'                            => true,
	'default'                            => 'M',
	'inputType'                          => 'select',
	'options'                            => $GLOBALS['TL_LANG']['tl_module']['elo_gender_options'],
	'eval'                               => array('tl_class'=>'w50'),
	'sql'                                => "char(1) NOT NULL default 'M'"
);

// Anzahl der Top-Plätze, die angezeigt werden sollen (max. 9)
$GLOBALS['TL_DCA']['tl_module']['fields']['elo_topx'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_topx'],
	'exclude'                            => true,
	'inputType'                          => 'text',
	'default'                            => 3,
	'eval'                               => array
	(
		'tl_class'                       => 'w50',
		'rgxp'                           => 'digit',
		'maxlength'                      => 1
	),
	'sql'                                => "int(1) unsigned NOT NULL default 3"
);

// FIDE-Link anzeigen ja/nein
$GLOBALS['TL_DCA']['tl_module']['fields']['elo_fidelink'] = array
(
	'label'                              => &$GLOBALS['TL_LANG']['tl_module']['elo_fidelink'],
	'exclude'                            => true,
	'inputType'                          => 'checkbox',
	'eval'                               => array('tl_class'=>'w50 clr'),
	'sql'                                => "char(1) NOT NULL default ''"
);
