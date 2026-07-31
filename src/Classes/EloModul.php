<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Classes;

use Contao\BackendTemplate;
use Contao\Module;
use Contao\System;

/**
 * Gemeinsame Basis der vier Frontend-Module.
 *
 * Übernimmt die Platzhalter-Darstellung im Backend, die alle vier Module
 * gleichermaßen brauchen. Die Module selbst setzen nur noch ihren Platzhaltertext
 * und ihre compile()-Methode.
 */
abstract class EloModul extends Module
{
	/**
	 * @var string Text des Platzhalters in der Backend-Seitenstruktur
	 */
	protected $strPlatzhalter = '### ELO ###';

	/**
	 * Erzeugt das Modul.
	 *
	 * Im Backend wird statt der Ausgabe nur ein Platzhalter mit dem Modulnamen
	 * gezeigt — die Elo-Listen ließen sich dort ohnehin nicht sinnvoll
	 * darstellen und die Abfragen wären reine Last.
	 *
	 * @return string Der Platzhalter im Backend, sonst die Ausgabe des Moduls
	 */
	public function generate(): string
	{
		if ($this->isBackendRequest())
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = $this->strPlatzhalter;
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Stellt fest, ob die Anfrage aus dem Backend kommt.
	 *
	 * Ersetzt die in Contao 5 entfallene Konstante TL_MODE. Ohne aktiven
	 * Request — etwa auf der Kommandozeile — gilt die Anfrage als Frontend,
	 * weil dort auch kein Backend-Platzhalter gebraucht wird.
	 *
	 * @return bool True, wenn die Anfrage im Backend-Bereich läuft
	 */
	protected function isBackendRequest(): bool
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return false;
		}

		$request = $container->get('request_stack')->getCurrentRequest();

		if (null === $request)
		{
			return false;
		}

		return $container->get('contao.routing.scope_matcher')->isBackendRequest($request);
	}
}
