<?php

require_once(dirname(__FILE__) . '/ncbi_blast.php');

const NumSequences = 30;

// Get BLAST results and convert to FASTA
$rid = '';

if (isset($_GET['rid']))
{
	$rid = $_GET['rid'];
}

$url = "http://www.ncbi.nlm.nih.gov/blast/Blast.cgi?CMD=Get&RID=$rid&FORMAT_TYPE=XML";
$xml = get($url);

$obj = new stdclass;


if ($xml != '')
{
	$obj->xmlfile = 'tmp/' . $rid . '.xml';
	file_put_contents($obj->xmlfile, $xml);
	
	// Convert BLAST XML to FASTA
	$xp = new XsltProcessor();
	$xsl = new DomDocument;
	$xsl->load('blast2fasta.xsl');
	$xp->importStylesheet($xsl);
	
	$dom = new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xp->setParameter('', 'number', NumSequences);	

	$fasta = $xp->transformToXML($dom);
	
	$obj->fastafile = 'tmp/' . $rid . '.fas';
	file_put_contents($obj->fastafile, $fasta);
	
	// NEXUS translation table
	$xp2 = new XsltProcessor();
	$xsl2 = new DomDocument;
	$xsl2->load('blast2translate.xsl');
	$xp2->importStylesheet($xsl2);
	
	$xp2->setParameter('', 'number', NumSequences);	
	$translate = $xp2->transformToXML($dom);
	
	$obj->translatefile = 'tmp/' . $rid . '.txt';
	file_put_contents($obj->translatefile, $translate);
}


$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

if ($callback != '')
{
	echo $callback . '(';
}
echo json_encode($obj);
if ($callback != '')
{
	echo ')';
}


?>