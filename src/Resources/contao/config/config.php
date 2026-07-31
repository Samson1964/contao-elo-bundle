<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoEloBundle\Classes\Bestenliste;
use Schachbulle\ContaoEloBundle\Classes\Elo;
use Schachbulle\ContaoEloBundle\Classes\FideImport;
use Schachbulle\ContaoEloBundle\Classes\Statistik;
use Schachbulle\ContaoEloBundle\Classes\TopX;
use Schachbulle\ContaoEloBundle\ContentElements\EloArchiv;

/*
 * Backend-Modul
 *
 * Beide Tabellen gehören zum selben Modul: tl_elo ist die Kindtabelle von
 * tl_elo_listen und wird über die Operation "Spieler der Liste bearbeiten"
 * geöffnet. Contao lässt dabei nur Tabellen zu, die hier eingetragen sind.
 */
$GLOBALS['BE_MOD']['content']['elo'] = array
(
	'tables'                  => array('tl_elo_listen', 'tl_elo'),
	'icon'                    => 'bundles/contaoelo/images/icon.svg',

	// Operation "FIDE-Daten importieren" am Listendatensatz (key=import&id=<Liste>).
	// Contao holt die Klasse über System::importStatic() aus dem Service-Container,
	// weil sie in der services.yaml unter ihrem Klassennamen registriert ist.
	'import'                  => array(FideImport::class, 'run')
);

/*
 * Frontend-Module
 *
 * Die Registrierung über $GLOBALS['FE_MOD'] funktioniert in Contao 4.13 und
 * Contao 5 gleichermaßen; die Klassen erben weiterhin von Contao\Module.
 */
$GLOBALS['FE_MOD']['elo'] = array
(
	'elo_toplist'             => Elo::class,
	'elo_bestlist'            => Bestenliste::class,
	'elo_topx'                => TopX::class,
	'elo_statistik'           => Statistik::class,
);

/*
 * Inhaltselemente
 */
$GLOBALS['TL_CTE']['schach']['eloliste'] = EloArchiv::class;

/*
 * Voreinstellungen
 *
 * Cachedauer der Frontend-Ausgaben in Tagen; einstellbar in den
 * Contao-Einstellungen (tl_settings).
 */
$GLOBALS['TL_CONFIG']['eloliste_cachetime'] = 30;
