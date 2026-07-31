<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Classes;

use Contao\System;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Zwischenspeicher der Frontend-Ausgaben.
 *
 * Die Ausgaben dieses Bundles werden aus mehreren tausend Spielerdatensätzen
 * zusammengesetzt und ändern sich nur einmal im Monat, wenn eine neue
 * FIDE-Liste importiert wird. Sie werden deshalb zwischengespeichert.
 *
 * Früher übernahm das die Cache-Klasse aus dem contao-helper-bundle. Die ist
 * entfallen, weil das Helper-Bundle nicht für Contao 5 verfügbar ist; an ihre
 * Stelle tritt der Dateispeicher von symfony/cache. Die Dateien liegen
 * unterhalb des Contao-Cacheverzeichnisses und gehen beim Leeren des Caches
 * verloren — das ist gewollt, denn die Ausgaben sind jederzeit neu errechenbar.
 */
final class Zwischenspeicher
{
	/**
	 * Liefert einen zwischengespeicherten Wert oder erzeugt ihn neu.
	 *
	 * @param string   $bereich    Name des Speicherbereichs, z. B. "elo_bestenliste".
	 *                             Jedes Modul bekommt einen eigenen, damit sich die
	 *                             Schlüssel nicht überschneiden
	 * @param string   $schluessel Kennung des Eintrags innerhalb des Bereichs;
	 *                             ungültige Zeichen werden ersetzt
	 * @param int      $dauer      Haltbarkeit in Sekunden
	 * @param callable $erzeuger   Wird aufgerufen, wenn nichts im Speicher liegt,
	 *                             und muss den zu speichernden Wert zurückliefern
	 *
	 * @return mixed Der gespeicherte oder neu erzeugte Wert. Steht kein
	 *               Cacheverzeichnis zur Verfügung, wird jedes Mal neu gerechnet
	 */
	public static function hole(string $bereich, string $schluessel, int $dauer, callable $erzeuger)
	{
		$speicher = self::speicher($bereich);

		if (null === $speicher)
		{
			return $erzeuger();
		}

		$eintrag = $speicher->getItem(self::schluessel($schluessel));

		if ($eintrag->isHit())
		{
			return $eintrag->get();
		}

		$wert = $erzeuger();

		$eintrag->set($wert);
		$eintrag->expiresAfter($dauer);
		$speicher->save($eintrag);

		return $wert;
	}

	/**
	 * Erzeugt den Dateispeicher eines Bereichs.
	 *
	 * @param string $bereich Name des Speicherbereichs
	 *
	 * @return FilesystemAdapter|null Der Speicher, oder null wenn kein
	 *                                Cacheverzeichnis bekannt ist
	 */
	private static function speicher(string $bereich): ?FilesystemAdapter
	{
		$container = System::getContainer();

		if (null === $container || !$container->hasParameter('kernel.cache_dir'))
		{
			return null;
		}

		return new FilesystemAdapter($bereich, 0, (string) $container->getParameter('kernel.cache_dir'));
	}

	/**
	 * Macht aus einer beliebigen Kennung einen für PSR-6 zulässigen Schlüssel.
	 *
	 * Die Zeichen {}()/\@: sind dort reserviert und lösen sonst eine
	 * InvalidArgumentException aus. Da die Schlüssel dieses Bundles aus
	 * Moduleinstellungen zusammengesetzt werden, ist das nicht auszuschließen.
	 *
	 * @param string $schluessel Die rohe Kennung
	 *
	 * @return string Die bereinigte Kennung
	 */
	private static function schluessel(string $schluessel): string
	{
		return preg_replace('/[^A-Za-z0-9_.-]/', '_', $schluessel) ?? $schluessel;
	}
}
