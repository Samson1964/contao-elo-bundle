<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Test-Bootstrap.
 *
 * Das Bundle hat bewusst kein eigenes vendor/-Verzeichnis; PHPUnit wird aus der
 * gemeinsamen Installation unter F:\Claude\tools\phpunit9 aufgerufen. Wenn ein
 * vendor/autoload.php vorhanden ist (etwa in einer CI-Umgebung nach
 * "composer install"), wird es genutzt — sonst genügt ein schlanker
 * PSR-4-Autoloader für den Namensraum des Bundles, weil die getesteten Methoden
 * keine Contao-Klassen benötigen.
 */

$autoload = __DIR__.'/../vendor/autoload.php';

if (file_exists($autoload))
{
	require $autoload;

	return;
}

spl_autoload_register(static function (string $klasse): void {
	$praefix = 'Schachbulle\\ContaoEloBundle\\';

	if (0 !== strpos($klasse, $praefix)) {
		return;
	}

	$rest = substr($klasse, \strlen($praefix));

	// Die Tests liegen in tests/, der übrige Namensraum in src/
	if (0 === strpos($rest, 'Tests\\')) {
		$datei = __DIR__.'/'.str_replace('\\', '/', substr($rest, 6)).'.php';
	} else {
		$datei = __DIR__.'/../src/'.str_replace('\\', '/', $rest).'.php';
	}

	if (file_exists($datei)) {
		require $datei;
	}
});
