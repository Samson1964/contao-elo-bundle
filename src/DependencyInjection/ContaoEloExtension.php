<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Bindet die Service-Konfiguration des Bundles in den Symfony-Container ein.
 */
class ContaoEloExtension extends Extension
{
	/**
	 * Lädt die Service-Definitionen des Bundles.
	 *
	 * @param array<mixed>     $mergedConfig Die zusammengeführte Bundle-Konfiguration;
	 *                                       das Bundle besitzt keine eigene
	 *                                       Konfigurationsstruktur und wertet sie nicht aus
	 * @param ContainerBuilder $container    Der Container, in den die Services eingetragen werden
	 *
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);

		$loader->load('services.yaml');
	}
}
