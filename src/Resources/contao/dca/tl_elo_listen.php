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
 * Table tl_elo_listen
 */
$GLOBALS['TL_DCA']['tl_elo_listen'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'                 => 'Table',
		'ctable'                        => array('tl_elo'),
		'enableVersioning'              => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'                    => 'primary',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                      => 1,
			'fields'                    => array('datum'),
			'panelLayout'               => 'filter,sort;search,limit',
			'flag'                      => 12,
			'disableGrouping'           => true,
		),
		'label' => array
		(
			'fields'                    => array('id', 'listmonth', 'datum', 'title'),
			'showColumns'               => true,
			//'label_callback'          => array('tl_elo_listen', 'convertDate')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                  => 'act=select',
				'class'                 => 'header_edit_all',
				'attributes'            => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['edit'],
				'href'                  => 'table=tl_elo',
				'icon'                  => 'edit.gif',
			),
			'editheader' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['editheader'],
				'href'                  => 'act=edit',
				'icon'                  => 'header.gif',
			),
			'copy' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['copy'],
				'href'                  => 'act=copy',
				'icon'                  => 'copy.gif'
			),
			'delete' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['delete'],
				'href'                  => 'act=delete',
				'icon'                  => 'delete.gif',
				'attributes'            => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['toggle'],
				'icon'                  => 'visible.gif',
				'attributes'            => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'       => array('tl_elo_listen', 'toggleIcon')
			),
			'show' => array
			(
				'label'                 => &$GLOBALS['TL_LANG']['tl_elo_listen']['show'],
				'href'                  => 'act=show',
				'icon'                  => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                  => array(''),
		'default'                       => '{title_legend},listmonth,title,datum;{publish_legend},published'
	),

	// Subpalettes
	'subpalettes' => array
	(
		''                              => ''
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                     => &$GLOBALS['TL_LANG']['tl_elo_listen']['id'],
			'sql'                       => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                       => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                     => &$GLOBALS['TL_LANG']['tl_elo_listen']['title'],
			'exclude'                   => true,
			'inputType'                 => 'text',
			'search'                    => true,
			'eval'                      => array
			(
				'mandatory'             => true,
				'maxlength'             => 64,
				'tl_class'              => 'w50 clr'
			),
			'sql'                       => "varchar(64) NOT NULL default ''"
		),
		'datum' => array
		(
			'label'                     => &$GLOBALS['TL_LANG']['tl_elo_listen']['datum'],
			'exclude'                   => true,
			'default'                   => time(),
			'filter'                    => true,
			'search'                    => true,
			'inputType'                 => 'text',
			'flag'                      => 8,
			'eval'                      => array
			(
				'rgxp'                  => 'date',
				'datepicker'            => true,
				'mandatory'             => true,
				'maxlength'             => 10,
				'tl_class'              => 'w50 widget'
			),
			'sql'                       => "int(10) unsigned NOT NULL default '0'"
		),
		'listmonth' => array
		(
			'label'                     => &$GLOBALS['TL_LANG']['tl_elo_listen']['listmonth'],
			'exclude'                   => true,
			'default'                   => date('Ym'),
			'filter'                    => true,
			'search'                    => true,
			'inputType'                 => 'text',
			'flag'                      => 11,
			'eval'                      => array
			(
				'mandatory'             => true,
				'maxlength'             => 6,
				'tl_class'              => 'w50'
			),
			'sql'                       => "int(6) unsigned NOT NULL default '0'"
		),
		'published' => array
		(
			'label'                     => &$GLOBALS['TL_LANG']['tl_elo_listen']['published'],
			'exclude'                   => true,
			'search'                    => false,
			'sorting'                   => false,
			'filter'                    => true,
			'inputType'                 => 'checkbox',
			'eval'                      => array('tl_class' => 'w50','isBoolean' => true),
			'sql'                       => "char(1) NOT NULL default ''"
		),
	)
);


/**
 * Class tl_elo_listen
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_elo_listen extends Backend
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
	 * Ändert das Aussehen des Toggle-Buttons.
	 * @param $row
	 * @param $href
	 * @param $label
	 * @param $title
	 * @param $icon
	 * @param $attributes
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
		$this->import('BackendUser', 'User');

		if (strlen($this->Input->get('tid')))
		{
			$this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 0));
			$this->redirect($this->getReferer());
		}

		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_elo_listen::published', 'alexf'))
		{
			return '';
		}

		$href .= '&amp;id='.$this->Input->get('id').'&amp;tid='.$row['id'].'&amp;state='.$row[''];

		if (!$row['published'])
		{
			$icon = 'invisible.gif';
		}

		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}

	/**
	 * Toggle the visibility of an element
	 * @param integer
	 * @param boolean
	 */
	public function toggleVisibility($intId, $blnPublished)
	{
		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_elo_listen::published', 'alexf'))
		{
			$this->log('Not enough permissions to show/hide record ID "'.$intId.'"', 'tl_elo_listen toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$this->createInitialVersion('tl_elo_listen', $intId);

		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_elo_listen']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_elo_listen']['fields']['published']['save_callback'] as $callback)
			{
				$this->import($callback[0]);
				$blnPublished = $this->$callback[0]->$callback[1]($blnPublished, $this);
			}
		}

		// Update the database
		$this->Database->prepare("UPDATE tl_elo_listen SET tstamp=". time() .", published='" . ($blnPublished ? '' : '1') . "' WHERE id=?")
		               ->execute($intId);
		$this->createNewVersion('tl_elo_listen', $intId);
	}

}
