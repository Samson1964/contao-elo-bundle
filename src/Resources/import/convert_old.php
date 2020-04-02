<?php

// Konvertiert die getrennten XML-Dateien (alte Downloads vor aktuellem Monat)

ini_set('memory_limit', '1024M');
set_time_limit(0);

$xmlFile = array
(
	'standard_aug12frl_xml.xml', // Standard
	'blitz_aug12frl_xml.xml', // Blitz
	'rapid_aug12frl_xml.xml' // Schnell
);
	
$listdate = 20120817;
$pid = 48; // ID der Eloliste in Contao
$time = time();

$nr = 1;
$count = 0;
$fp = fopen("tl_elo_$nr.sql", 'w');

// Dateien zeilenweise lesen und SQL-Import erstellen
for($x = 0; $x < count($xmlFile); $x++)
{
	$xml = simplexml_load_file($xmlFile[$x]);

	foreach($xml->player as $row)
	{
		if($row->country == 'GER' || $row->country == 'GDR' || $row->country == 'FRG')
		{
			$count++;
			if($count == 8000)
			{
				// Neue SQL-Datei anlegen
				fclose($fp);
				$nr++;
				$count = 0;
				$fp = fopen("tl_elo_$nr.sql", 'w');
			}
			$row->fideid += 0;
			$row->rating += 0;
			$row->games += 0;
			$row->birthday += 0;
			if($x == 0)
			{
				// Standardrating: hier normale Inserts machen			
				fputs($fp, 'INSERT INTO tl_elo (pid, tstamp, fideid, surname, prename, intent, country, sex, title, w_title, o_title, foa_title, rating, games, rapid_rating, rapid_games, blitz_rating, blitz_games, birthday, flag, published) VALUES (');
				fputs($fp, $pid . ', ');
				fputs($fp, $time . ', ');
				fputs($fp, $row->fideid . ', ');
				list($surname, $prename, $intent) = explode(',', $row->name);
				fputs($fp, "'" . addslashes(trim($surname)) . "', ");
				fputs($fp, "'" . addslashes(trim($prename)) . "', ");
				fputs($fp, "'" . addslashes(trim($intent)) . "', ");
				fputs($fp, "'" . addslashes($row->country) . "', ");
				fputs($fp, "'" . addslashes($row->sex) . "', ");
				fputs($fp, "'" . addslashes($row->title) . "', ");
				fputs($fp, "'" . addslashes($row->w_title) . "', ");
				fputs($fp, "'" . addslashes($row->o_title) . "', ");
				fputs($fp, "'" . addslashes($row->foa_title) . "', ");
				fputs($fp, $row->rating . ', ');
				fputs($fp, $row->games . ', ');
				fputs($fp, '0, ');
				fputs($fp, '0, ');
				fputs($fp, '0, ');
				fputs($fp, '0, ');
				fputs($fp, $row->birthday . ', ');
				fputs($fp, "'" . addslashes($row->flag) . "', ");
				fputs($fp, '1');
				fputs($fp, ");\n");
			}
			elseif($x == 1)
			{
				// Blitzrating: hier Updates machen			
				fputs($fp, 'UPDATE tl_elo SET blitz_rating = '.$row->rating.', blitz_games = '.$row->games.' WHERE fideid = '.$row->fideid.' AND pid = '.$pid);
				fputs($fp, ";\n");
			}
			elseif($x == 2)
			{
				// Schnellrating: hier Updates machen			
				fputs($fp, 'UPDATE tl_elo SET rapid_rating = '.$row->rating.', rapid_games = '.$row->games.' WHERE fideid = '.$row->fideid.' AND pid = '.$pid);
				fputs($fp, ";\n");
			}
		}
	}

}

echo 'Fertig';
