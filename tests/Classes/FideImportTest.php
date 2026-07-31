<?php

declare(strict_types=1);

/*
 * Dieses Bundle verwaltet FIDE-Elo-Listen in Contao 4.13 und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoEloBundle\Tests\Classes;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoEloBundle\Classes\FideImport;

/**
 * Testet die Auswertung eines <player>-Blocks der FIDE-Ratingliste.
 *
 * Geprüft wird ausschließlich parseSpieler(); die Methode ist statisch und
 * kommt ohne Contao-Umgebung aus, weil sie nur Text verarbeitet.
 */
class FideImportTest extends TestCase
{
	/**
	 * Baut einen <player>-Block, wie ihn die FIDE-XML enthält.
	 *
	 * @param array $werte Feldname => Wert; überschreibt die Vorgabewerte eines
	 *                     vollständig gefüllten deutschen Spielers
	 *
	 * @return string Der fertige XML-Block
	 */
	private function block(array $werte = array()): string
	{
		$felder = array_merge(array
		(
			'fideid'       => '4611111',
			'name'         => 'Mustermann, Max',
			'country'      => 'GER',
			'sex'          => 'M',
			'title'        => 'GM',
			'w_title'      => '',
			'o_title'      => '',
			'foa_title'    => '',
			'rating'       => '2611',
			'games'        => '42',
			'k'            => '10',
			'rapid_rating' => '2550',
			'rapid_games'  => '12',
			'rapid_k'      => '20',
			'blitz_rating' => '2500',
			'blitz_games'  => '8',
			'blitz_k'      => '20',
			'birthday'     => '1975',
			'flag'         => '',
			'rapid_flag'   => '',
			'blitz_flag'   => '',
		), $werte);

		$xml = '<player>';

		foreach ($felder as $name => $wert)
		{
			$xml .= '<'.$name.'>'.$wert.'</'.$name.'>';
		}

		return $xml.'</player>';
	}

	/**
	 * Ein vollständiger deutscher Spieler muss mit allen Feldern ankommen.
	 */
	public function testUebernimmtDeutschenSpieler(): void
	{
		$spieler = FideImport::parseSpieler($this->block());

		$this->assertIsArray($spieler);
		$this->assertSame(4611111, $spieler['fideid']);
		$this->assertSame('Mustermann', $spieler['surname']);
		$this->assertSame('Max', $spieler['prename']);
		$this->assertSame('', $spieler['intent']);
		$this->assertSame('GER', $spieler['country']);
		$this->assertSame('M', $spieler['sex']);
		$this->assertSame('GM', $spieler['title']);
		$this->assertSame(2611, $spieler['rating']);
		$this->assertSame(42, $spieler['games']);
		$this->assertSame(2550, $spieler['rapid_rating']);
		$this->assertSame(2500, $spieler['blitz_rating']);
		$this->assertSame(1975, $spieler['birthday']);
	}

	/**
	 * Spieler anderer Länder gehören nicht in die Liste.
	 */
	public function testUebergehtAndereLaender(): void
	{
		$this->assertNull(FideImport::parseSpieler($this->block(array('country' => 'NOR'))));
		$this->assertNull(FideImport::parseSpieler($this->block(array('country' => 'AUT'))));
		$this->assertNull(FideImport::parseSpieler($this->block(array('country' => 'SUI'))));
	}

	/**
	 * Die historischen deutschen Kennungen für DDR und BRD gehören dazu, sonst
	 * fehlten Spieler mit einer Elo-Zahl aus der Zeit vor der Wiedervereinigung.
	 */
	public function testUebernimmtHistorischeDeutscheKennungen(): void
	{
		foreach (array('GER', 'GDR', 'FRG') as $land)
		{
			$spieler = FideImport::parseSpieler($this->block(array('country' => $land)));

			$this->assertIsArray($spieler, 'Länderkennung '.$land.' muss übernommen werden');
			$this->assertSame($land, $spieler['country']);
		}
	}

	/**
	 * "GERMANY" o. ä. darf nicht als GER durchrutschen: Die Schnellprüfung
	 * matcht auf das vollständige Element, nicht auf den Anfang des Wertes.
	 */
	public function testUebergehtLandmitGleichemAnfang(): void
	{
		$this->assertNull(FideImport::parseSpieler($this->block(array('country' => 'GERMANY'))));
	}

	/**
	 * Ohne FIDE-ID lässt sich der Spieler später nicht zuordnen.
	 */
	public function testUebergehtSpielerOhneFideId(): void
	{
		$this->assertNull(FideImport::parseSpieler($this->block(array('fideid' => '0'))));
	}

	/**
	 * Kaputte Blöcke dürfen den Import nicht abbrechen.
	 */
	public function testUebergehtUngueltigesXml(): void
	{
		$this->assertNull(FideImport::parseSpieler('<player><country>GER</country><name>Ohne Ende'));
	}

	/**
	 * Der FIDE-Name kann einen dritten Bestandteil tragen; er landet in "intent".
	 */
	public function testZerlegtNamenMitDrittemBestandteil(): void
	{
		$spieler = FideImport::parseSpieler($this->block(array('name' => 'Mustermann, Max, Dr.')));

		$this->assertSame('Mustermann', $spieler['surname']);
		$this->assertSame('Max', $spieler['prename']);
		$this->assertSame('Dr.', $spieler['intent']);
	}

	/**
	 * Ein Name ohne Komma ist nur ein Nachname.
	 */
	public function testZerlegtNamenOhneKomma(): void
	{
		$spieler = FideImport::parseSpieler($this->block(array('name' => 'Mustermann')));

		$this->assertSame('Mustermann', $spieler['surname']);
		$this->assertSame('', $spieler['prename']);
		$this->assertSame('', $spieler['intent']);
	}

	/**
	 * Fehlende Elemente sind in der FIDE-Datei üblich (etwa alle Titelfelder bei
	 * einem titellosen Spieler) und müssen zu leeren Werten führen.
	 */
	public function testVertraegtFehlendeElemente(): void
	{
		$spieler = FideImport::parseSpieler('<player><fideid>12345678</fideid><name>Neu, Nina</name><country>GER</country><rating>1500</rating></player>');

		$this->assertSame(12345678, $spieler['fideid']);
		$this->assertSame('', $spieler['title']);
		$this->assertSame('', $spieler['sex']);
		$this->assertSame(0, $spieler['blitz_rating']);
		$this->assertSame(0, $spieler['birthday']);
	}

	/**
	 * Übermäßig lange Werte müssen auf die Feldlänge gekürzt werden, sonst
	 * bricht MySQL im strengen Modus mit "Data too long" ab.
	 */
	public function testKuerztZuLangeWerte(): void
	{
		$spieler = FideImport::parseSpieler($this->block(array
		(
			'name'  => str_repeat('a', 100).', '.str_repeat('b', 100).', '.str_repeat('c', 40),
			'flag'  => str_repeat('i', 20),
		)));

		$this->assertSame(64, mb_strlen($spieler['surname']));
		$this->assertSame(64, mb_strlen($spieler['prename']));
		$this->assertSame(16, mb_strlen($spieler['intent']));
		$this->assertSame(8, mb_strlen($spieler['flag']));
	}

	/**
	 * Umlaute dürfen beim Kürzen nicht zerschnitten werden — deshalb mb_substr
	 * und nicht substr.
	 */
	public function testKuerztUmlauteZeichenweise(): void
	{
		$spieler = FideImport::parseSpieler($this->block(array('name' => str_repeat('ä', 100))));

		$this->assertSame(64, mb_strlen($spieler['surname']));
		$this->assertSame(str_repeat('ä', 64), $spieler['surname']);
	}

	/**
	 * Die Inaktiv-Markierung der FIDE muss erhalten bleiben.
	 */
	public function testUebernimmtMarkierungen(): void
	{
		$spieler = FideImport::parseSpieler($this->block(array
		(
			'flag'       => 'i',
			'rapid_flag' => 'wi',
			'blitz_flag' => 'i',
		)));

		$this->assertSame('i', $spieler['flag']);
		$this->assertSame('wi', $spieler['rapid_flag']);
		$this->assertSame('i', $spieler['blitz_flag']);
	}
}
