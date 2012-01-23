<?php

// Fetch trees from TreeBASE

require_once (dirname(dirname(__FILE__)) . '/lib.php');


$taxon = $_GET['taxon'];

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

// Names with spaces need special treatment
if (preg_match('/\s/', $taxon))
{
	$taxon = '%22' . str_replace(' ', '+', $taxon) . '%22';
}

// Note that the URL used to search TreeBASE is not the same as that returned in the RSS,
// i.e. channel/rdf:about is rewritten. This is probably a bug, but it screwed me up for some 
// time as I couldn't find the search results in my triple store.

// Search URL
$url = 'http://purl.org/phylo/treebase/phylows/taxon/find?query=tb.title.taxon+%3D+' . $taxon . '&format=rss1&recordSchema=tree';

$xml = get($url);

$js = '';

if ($xml != '')
{
	// Convert GBIF XML to Javascript for Google Maps
	$xp = new XsltProcessor();
	$xsl = new DomDocument;
	$xsl->load('treebase-search.xsl');
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