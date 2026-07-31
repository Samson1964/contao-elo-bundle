<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Database;
use Contao\DataContainer;

/*
 * Erweiterung von tl_content um das Inhaltselement "Elo-Liste".
 *
 * Der frühere Schutz "if (!defined('TL_ROOT')) die(...)" ist entfallen: Die
 * Konstante gibt es in Contao 5 nicht mehr, die Datei hätte dort also bei jedem
 * Aufruf die Anfrage abgebrochen. Contao lädt DCA-Dateien ohnehin nur über den
 * DcaLoader, ein direkter Aufruf ist über die Verzeichnisstruktur des Bundles
 * gar nicht erreichbar.
 */

/*
 * Palette
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'eloliste_checkbox';
$GLOBALS['TL_DCA']['tl_content']['palettes']['eloliste'] = '{type_legend},type,headline;{eloliste_legend},eloliste_checkbox;{eloliste2_legend},eloliste_typ,eloliste_number;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['eloliste_checkbox'] = 'eloliste_id';

/*
 * Felder
 */

$GLOBALS['TL_DCA']['tl_content']['fields']['eloliste_checkbox'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_content']['eloliste_checkbox'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50 clr',
		'isBoolean'           => true,
		'submitOnChange'      => true
	),
	'sql'                     => "char(1) NOT NULL default ''",
);

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
		'submitOnChange' => false,
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
 * Stellt die Auswahlliste der Elo-Listen für das Inhaltselement bereit.
 *
 * Die Klasse erbt bewusst nicht von Contao\Backend: Sie braucht nichts daraus,
 * und den Klassenalias "Backend" ohne Namensraum gibt es in Contao 5 nicht
 * mehr. Contao erzeugt sie über System::importStatic(), wofür ein öffentlicher
 * Konstruktor genügt.
 */
class tl_content_eloliste
{
	/**
	 * Liefert alle veröffentlichten Elo-Listen als Auswahlmöglichkeiten.
	 *
	 * Sortiert wird absteigend nach Datum, damit die aktuellste Liste oben
	 * steht. Der Wert ist die Datensatz-ID, die Beschriftung setzt sich aus
	 * Titel und Datum zusammen.
	 *
	 * @param DataContainer|null $dc Der DataContainer des Inhaltselements; wird
	 *                               nicht ausgewertet, da alle Listen zur
	 *                               Auswahl stehen
	 *
	 * @return array<int,string> ID der Liste => Beschriftung. Leer, wenn keine
	 *                           Liste veröffentlicht ist
	 */
	public function getEloliste($dc = null): array
	{
		$array = array();

		$objListe = Database::getInstance()
			->prepare('SELECT id, title, datum FROM tl_elo_listen WHERE published=? ORDER BY datum DESC')
			->execute('1');

		while ($objListe->next())
		{
			$array[(int) $objListe->id] = $objListe->title.' ('.date('d.m.Y', (int) $objListe->datum).')';
		}

		return $array;
	}
}
