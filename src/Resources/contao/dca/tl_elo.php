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
 * Table tl_elo
 */
$GLOBALS['TL_DCA']['tl_elo'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'             => 'Table',
		'ptable'					=> 'tl_elo_listen',
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

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('surname'),
			'flag'                    => 1,
			'headerFields'            => array('title', 'datum'), 
			'panelLayout'             => 'sort,filter;search,limit',
			'child_record_callback'   => array('tl_elo', 'listPlayers'),
			'child_record_class'      => 'no_padding',
		),
		'label' => array
		(
			'fields'                  => array('surname', 'prename'),
			'showColumns'             => true,
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_elo']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_elo']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_elo']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_elo']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_elo']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Select
	'select' => array
	(
		'buttons_callback' => array()
	),

	// Edit
	'edit' => array
	(
		'buttons_callback' => array()
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array(''),
		'default'                     => '{name_legend},surname,prename,intent,birthday,sex,country;{fide_legend},fideid,title,w_title,o_title,foa_title;{flag_legend},flag,rapid_flag,blitz_flag;{elo_legend},rating,games,rapid_rating,rapid_games,blitz_rating,blitz_games;{publish_legend},published'
	),

	// Subpalettes
	'subpalettes' => array
	(
		''                            => ''
	),

	// Fields
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

/**
 * Provide miscellaneous methods that are used by the data configuration array
 */
class tl_elo extends Backend
{
	 
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

    /**
     * Generiere eine Zeile als HTML
     * @param array
     * @return string
     */
    public function listPlayers($arrRow)
    {
        $line = '';
        $line .= '<div>';
        $line .= $arrRow['surname'];
        if($arrRow['prename']) $line .= ', '.$arrRow['prename'];
        if($arrRow['intent']) $line .= ', '.$arrRow['intent'];
        if($arrRow['rating']) $line .= ' - Elo '.$arrRow['rating'];
        if($arrRow['blitz_rating']) $line .= ' - Blitz '.$arrRow['blitz_rating'];
        if($arrRow['rapid_rating']) $line .= ' - Rapid '.$arrRow['rapid_rating'];
        $line .= "</div>";
        $line .= "\n";
        return($line);

    }

}
