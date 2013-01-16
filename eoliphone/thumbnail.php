<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');

$convert = true;

$taxonConceptID = $_GET['taxonConceptID'];

$filename = 'tmp/' . $taxonConceptID .  '.jpg';

if (!file_exists($filename))
{
	
	$url = 'http://eol.org/api/pages/1.0/' . $taxonConceptID . '.json?details=1';
	
	$json = get($url);
	$obj = json_decode($json);
	
	if (isset($obj->dataObjects))
	{
		foreach ($obj->dataObjects as $dataObject)
		{
			if (isset($dataObject->eolThumbnailURL))
			{
				$image = $dataObject->eolThumbnailURL;
	
				// fetch and store				
				$img = get($image);
				
				if ($convert)
				{
					file_put_contents('tmp/' . $taxonConceptID, $img);
					
					// more recent versions
					$command = "/usr/local/bin/convert -thumbnail '80x80^' -gravity center -extent 80x80 " . 'tmp/' . $taxonConceptID . ' ' . $filename;

					// ImageMagick 6.3.1 
					$command = "/usr/local/bin/convert -resize '120' -gravity center -extent 80x80 " . 'tmp/' . $taxonConceptID . ' ' . $filename;
					
					//echo $command . "\n";
					
					system($command);
					
					unlink('tmp/' . $taxonConceptID);
				}
				else
				{
					file_put_contents($filename, $img);
				}
				break;
			}
		}
		
	}
}


if (file_exists($filename))
{
	$img = file_get_contents($filename);
	header("Content-type: image/jpeg");
	echo $img;
}
else
{
	$img = file_get_contents('images/80x80.png');
	header("Content-type: image/png");
	echo $img;
}


?>