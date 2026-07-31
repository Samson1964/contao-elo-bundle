<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle;

use Composer\InstalledVersions;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Haupt-Bundle-Klasse der FIDE-Elo-Verwaltung.
 */
class ContaoEloBundle extends Bundle
{
	/**
	 * Prüft anhand der installierten Version, ob Contao 5 (oder neuer) läuft.
	 *
	 * Die DCA-Dateien brauchen diese Information, weil Contao 5 die
	 * Kurzschreibweise der Operationen kennt, bei der Label und Icon aus dem
	 * Kern stammen, während Contao 4.13 vollständige Arrays benötigt. Auch die
	 * Zeilendarstellung der Elternansicht unterscheidet sich: Contao 5 erwartet
	 * ein label_callback, Contao 4.13 ein child_record_callback.
	 *
	 * Ausgewertet wird der Composer-Laufzeitindex und nicht die Konstante
	 * VERSION, da diese in Contao 5 entfallen ist.
	 *
	 * @return bool True bei Contao 5 oder neuer, false bei Contao 4.13 und
	 *              ebenso dann, wenn sich die Version nicht ermitteln lässt
	 */
	public static function isContao5(): bool
	{
		if (!class_exists(InstalledVersions::class))
		{
			return false;
		}

		return version_compare(
			(string) InstalledVersions::getVersion('contao/core-bundle'),
			'5.0.0',
			'>='
		);
	}
}
