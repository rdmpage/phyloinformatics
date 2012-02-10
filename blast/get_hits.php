<?php

require_once (dirname(dirname(__FILE__)) . '/services/genbank.php');

const NumSequences = 30;

function main()
{
	
	$callback = '';
	if (isset($_GET['callback']))
	{
		$callback = $_GET['callback'];
	}
	
	if (isset($_GET['rid']))
	{
		$rid = $_GET['rid'];
	}
	
	$accessions = array();
	
	$xml_filename = 'tmp/' . $rid . '.xml';
	
	$xml = file_get_contents($xml_filename);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath_query = "//Hit_accession";
	
	
	$nodeCollection = $xpath->query ($xpath_query);
	foreach($nodeCollection as $node)
	{
		$accessions[] = $node->firstChild->nodeValue;
	}
	
	$ids = array_slice($accessions, 0, NumSequences);
	
	$hits = fetch_sequences($ids);
		
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	
	echo json_format(json_encode($hits));
	
	if ($callback != '')
	{
		echo ')';
	}
}

main();

?>