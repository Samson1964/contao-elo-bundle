<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoEloBundle\ContaoEloBundle;

/**
 * Registriert das Bundle im Contao Manager.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Meldet das Bundle beim Contao Manager an.
	 *
	 * Das Bundle wird nach dem Contao-Kern geladen, damit dessen DCA- und
	 * Sprachdateien bereits vorhanden sind, wenn dieses Bundle darauf aufsetzt.
	 *
	 * @param ParserInterface $parser Wird vom Contao Manager übergeben und hier
	 *                                nicht benötigt, da keine fremden Bundles
	 *                                eingelesen werden
	 *
	 * @return array<BundleConfig> Die Bundle-Konfiguration dieses Pakets
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return array
		(
			BundleConfig::create(ContaoEloBundle::class)
				->setLoadAfter(array(ContaoCoreBundle::class)),
		);
	}
}
