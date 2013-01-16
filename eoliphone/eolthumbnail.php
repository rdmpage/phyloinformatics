<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');

$convert = true;

$thumbnail_size = 200;

$url = $_GET['url'];

$hash = md5($url);

$filename = 'tmp/' . $hash .  '.jpg';

//echo $filename;

if (!file_exists($filename))
{
	$image = $url;
	
	//echo $image;

	// fetch and store				
	$img = get($image);
	
	if ($convert)
	{
		file_put_contents('tmp/' . $hash, $img);
		$command = "/usr/local/bin/convert -thumbnail '" . $thumbnail_size . "x" . $thumbnail_size . "^' -gravity center -extent " . $thumbnail_size . "x" . $thumbnail_size . " " . 'tmp/' . $hash . ' ' . $filename;

		// ImageMagick 6.3.1 
		$command = "/usr/local/bin/convert -resize '" . ($thumbnail_size+100) . "' -gravity center -extent " . $thumbnail_size . "x" . $thumbnail_size . " " . 'tmp/' . $hash . ' ' . $filename;
		
		//echo $command;
		system($command);
		
		unlink('tmp/' . $hash);
	}
	else
	{
		file_put_contents($filename, $img);
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