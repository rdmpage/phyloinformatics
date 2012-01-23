<?php

// Fetch a map from GBIF

require_once (dirname(dirname(__FILE__)) . '/lib.php');

$name = $_GET['name'];

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

$url = 'http://data.gbif.org/ws/rest/taxon/list?scientificname=' . urlencode($name) . '&dataresourcekey=1';

$xml = get($url);

$js = '';

if ($xml != '')
{
	// Convert GBIF XML to Javascript
	$xp = new XsltProcessor();
	$xsl = new DomDocument;
	$xsl->load('gbif-taxa2json.xsl');
	$xp->importStylesheet($xsl);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);

	$js = $xp->transformToXML($dom);
}

if ($callback != '')
{
	echo $callback . '(';
}
echo $js;
if ($callback != '')
{
	echo ')';
}
?>