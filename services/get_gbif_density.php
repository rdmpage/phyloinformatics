<?php

// Fetch a map from GBIF

require_once (dirname(dirname(__FILE__)) . '/lib.php');

$id = $_GET['id'];

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

$url = 'http://data.gbif.org/ws/rest/density/list?taxonconceptkey=' . $id;

$xml = get($url);

$js = '';

if ($xml != '')
{
	// Convert GBIF XML to Javascript for Google Maps
	$xp = new XsltProcessor();
	$xsl = new DomDocument;
	$xsl->load('gbif2json.xsl');
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