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
 * Tabelle tl_elo_listen
 *
 * Eine Elo-Liste entspricht einer Monatsliste der FIDE. Die zugehörigen Spieler
 * liegen als Kinddatensätze in tl_elo und verweisen über "pid" hierher.
 */
$GLOBALS['TL_DCA']['tl_elo_listen'] = array
(

	// Konfiguration
	'config' => array
	(
		// Contao 5 erwartet den vollqualifizierten Klassennamen; Contao 4.13 versteht
		// ihn seit 4.9 ebenfalls und verwarnt umgekehrt den Kurznamen "Table"
		'dataContainer'                 => DC_Table::class,
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

	// Datensätze auflisten
	'list' => array
	(
		'sorting' => array
		(
			'mode'                      => 1, // DataContainer::MODE_SORTED
			'fields'                    => array('datum'),
			'panelLayout'               => 'filter,sort;search,limit',
			'flag'                      => 12, // DataContainer::SORT_MONTH_DESC
			'disableGrouping'           => true,
		),
		'label' => array
		(
			'fields'                    => array('id', 'listmonth', 'datum', 'title'),
			'showColumns'               => true,
		),
		// global_operations und operations werden weiter unten versionsabhängig gesetzt
	),

	// Paletten
	'palettes' => array
	(
		'__selector__'                  => array(''),
		'default'                       => '{title_legend},listmonth,title,datum;{publish_legend},published'
	),

	// Unterpaletten
	'subpalettes' => array
	(
		''                              => ''
	),

	// Felder
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
			'flag'                      => 8, // DataContainer::SORT_MONTH_ASC
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
			'flag'                      => 11, // DataContainer::SORT_YEAR_DESC
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
			'toggle'                    => true, // Aktiviert den Contao-eigenen Schnellschalter in der Übersicht
			'exclude'                   => true,
			'search'                    => false,
			'sorting'                   => false,
			'filter'                    => true,
			'inputType'                 => 'checkbox',
			'eval'                      => array('tl_class' => 'w50', 'isBoolean' => true),
			'sql'                       => "char(1) NOT NULL default ''"
		),
	)
);

/*
 * Operationen versionsabhängig setzen: Contao 5 kennt die Kurzschreibweise, bei
 * der Label und Icon aus dem Kern stammen, Contao 4.13 benötigt vollständige
 * Arrays. Der Toggler läuft in beiden Versionen über das Contao-eigene
 * "act=toggle"; die frühere Umsetzung über codefog/contao-haste ist entfallen,
 * weil Haste nicht für Contao 5 verfügbar ist.
 */
if (ContaoEloBundle::isContao5())
{
	$GLOBALS['TL_DCA']['tl_elo_listen']['list']['global_operations'] = array
	(
		'all'
	);

	$GLOBALS['TL_DCA']['tl_elo_listen']['list']['operations'] = array
	(
		'edit' => array
		(
			'href'                => 'table=tl_elo',
			'icon'                => 'bundles/contaoelo/images/spieler.svg',
		),
		'editheader' => array
		(
			'href'                => 'act=edit',
			'icon'                => 'header.svg',
		),
		'copy',
		'delete',
		'toggle',
		'show',
		'import' => array
		(
			'href'                => 'key=import',
			'icon'                => 'bundles/contaoelo/images/import.svg',
			'attributes'          => 'onclick="if (!confirm(\''.($GLOBALS['TL_LANG']['tl_elo_listen']['importConfirm'] ?? '').'\')) return false; Backend.getScrollOffset();"'
		),
	);
}
else
{
	$GLOBALS['TL_DCA']['tl_elo_listen']['list']['global_operations'] = array
	(
		'all' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
			'href'                => 'act=select',
			'class'               => 'header_edit_all',
			'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
		)
	);

	$GLOBALS['TL_DCA']['tl_elo_listen']['list']['operations'] = array
	(
		'edit' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['edit'],
			'href'                => 'table=tl_elo',
			'icon'                => 'bundles/contaoelo/images/spieler.svg',
		),
		'editheader' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['editheader'],
			'href'                => 'act=edit',
			'icon'                => 'header.svg',
		),
		'copy' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['copy'],
			'href'                => 'act=copy',
			'icon'                => 'copy.svg'
		),
		'delete' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['delete'],
			'href'                => 'act=delete',
			'icon'                => 'delete.svg',
			'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '').'\'))return false;Backend.getScrollOffset()"'
		),
		'toggle' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['toggle'],
			'href'                => 'act=toggle&amp;field=published',
			'icon'                => 'visible.svg'
		),
		'show' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['show'],
			'href'                => 'act=show',
			'icon'                => 'show.svg'
		),
		'import' => array
		(
			'label'               => &$GLOBALS['TL_LANG']['tl_elo_listen']['import'],
			'href'                => 'key=import',
			'icon'                => 'bundles/contaoelo/images/import.svg',
			'attributes'          => 'onclick="if (!confirm(\''.($GLOBALS['TL_LANG']['tl_elo_listen']['importConfirm'] ?? '').'\')) return false; Backend.getScrollOffset();"'
		),
	);
}
