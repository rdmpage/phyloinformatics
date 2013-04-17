<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');

$url = '';

if (isset($_GET['url']))
{
	$url = $_GET['url'];
}

// Form
echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<style type="text/css" title="text/css">
	body
	{
		font-family:sans-serif;padding:20px;
	}
	</style>
	<title>Inspector</title>
</head>
<body>
<a href="index.html">Services</a>
<h1>Service inspector</h1>
<p>Enter a URL and get display the content. Useful if you want to see what a webservice returns.
For example, you can see the <a href="?url=http%3A%2F%2Fapi.flickr.com%2Fservices%2Ffeeds%2Fgeo%2F%3Fg%3D806927%40N20%26lang%3Den-us%26format%3Drss_200">RSS returned from Flickr</a></p>
<h2>URL</h2>
<form method="get" action="inspector.php">
	<input style="font-size:24px;" id="url" name="url" size="80" value="' . $url . '"></input>
	<input style="font-size:24px;" type="submit" value="Go"></input>
</form>';


if ($url != '')
{
	echo '<h2>Output</h2>';
	echo '<div style="background-color:#eeeeee;padding:4px;border:1px solid rgb(128,128,128);overflow:auto;width:auto;height:600px;">';
	
	$content = get($url, 'iphylo');
	
	//echo $content;
	
	// classify
	
	$what = 'unknown';
	
	if ($what == 'unknown')
	{
		if (preg_match('/<html/m', $content))
		{
			$what = 'html';
			$content = htmlentities($content);
			$content = mb_convert_encoding($content, 'UTF-8');
		}
	}

	if ($what == 'unknown')
	{
		if (preg_match('/<\?xml/m', $content))
		{
			$what = 'xml';
			$content = htmlentities($content);
			$content = mb_convert_encoding($content, 'UTF-8');
		}
	}
	
	if ($what == 'unknown')
	{
		if (preg_match('/^(\w+\()?\s*{/m', $content))
		{
			$what = 'json';
			$content = json_format($content);
		}
	}
	
	echo '<pre>';
		
	echo $content;
	echo '</pre>';

	echo '</div>';
}
echo '</body>
</html>';

?>	