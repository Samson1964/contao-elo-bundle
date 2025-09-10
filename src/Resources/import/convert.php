<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

$xmlFile = 'players_list_xml_foa.xml';
$listdate = 20200331;
$pid = 93; // ID der Eloliste in Contao

// Datei zeilenweise lesen und in tempor‰re Dateien aufteilen
$x = 1;
$count = 0;
$file_handle = fopen($xmlFile, 'r');

// 1. tempor‰re Datei
$fp = fopen("$x.tmp", 'w');
fputs($fp, "<playerslist>\r\n");

while (!feof($file_handle)) 
{
  	$line = trim(fgets($file_handle));
  	if($line == '<playerslist>' || $line == '</playerslist>')
  	{
  		// Nichts machen
  	}
  	elseif($line == '<player>')
  	{
  		$count++;
  		if($count == 5000)
  		{
  			// Alte Datei schlieﬂen und neue beginnen
    		fputs($fp, "</playerslist>\r\n");
			fclose($fp);
			$x++;
			$fp = fopen("$x.tmp", 'w');
    		fputs($fp, "<playerslist>\r\n");
    		fputs($fp, $line . "\r\n");
    		$count = 1;
		}
		else
		{
    		fputs($fp, $line . "\r\n");
		}
  	}
  	elseif($line != '')
  	{
   		fputs($fp, $line . "\r\n");
  	}
}

fputs($fp, "</playerslist>\r\n");
fclose($fp);
fclose($file_handle);

// SQL-Import erstellen
$nr = 1;
$count = 0;
$fp = fopen("tl_elo_$nr.sql", 'w');

for($y = 1; $y <= $x; $y++)
{
	$xmlFile = "$y.tmp";
	if(file_exists($xmlFile))
	{
	    $xml = simplexml_load_file($xmlFile);
		$time = time();
		$cols = array();
			
	    foreach($xml->player as $row)
	    {
			if($row->country == 'GER' || $row->country == 'GDR' || $row->country == 'FRG')
			{
				$count++;
				if($count == 8000)
				{
					fclose($fp);
					$nr++;
					$count = 0;
					$fp = fopen("tl_elo_$nr.sql", 'w');
				}
				$row->fideid += 0;
				$row->rating += 0;
				$row->games += 0;
				$row->rapid_rating += 0;
				$row->rapid_games += 0;
				$row->blitz_rating += 0;
				$row->blitz_games += 0;
				$row->birthday += 0;
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
				fputs($fp, $row->rapid_rating . ', ');
				fputs($fp, $row->rapid_games . ', ');
				fputs($fp, $row->blitz_rating . ', ');
				fputs($fp, $row->blitz_games . ', ');
				fputs($fp, $row->birthday . ', ');
				fputs($fp, "'" . addslashes($row->flag) . "', ");
				fputs($fp, '1');
				fputs($fp, ");\n");
			}
	    }
	}
	unlink("$y.tmp");
}

fclose($fp);
echo 'Fertig';
