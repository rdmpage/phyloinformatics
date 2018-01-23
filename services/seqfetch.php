<?php

// sequences from ids

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/lib.php');
//require_once (dirname(__FILE__) . '/genbank.php');

//----------------------------------------------------------------------------------------
function fetch_sequences($ids)
{
	$fasta = '';

	// Query URL	
	$url = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=nucleotide&id='
		. preg_replace('/\s+/', '', join(",", $ids))
		. '&rettype=gb&retmode=xml';
	
	
	//echo $url . '<br/>';
	
	$xml = get($url);
	
	echo 'Get sequences' . '<br/>';
	
	//echo $xml;
	
	
	// process
	if ($xml != '')
	{
		$dom = new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
	
	
		$GBSeqs = $xpath->query ('//GBSeq');
		foreach ($GBSeqs as $GBSeq)
		{	
			//echo 'y';
			
			$accs = $xpath->query ('GBSeq_locus', $GBSeq);
			foreach ($accs as $acc)
			{
				$fasta .= ">" . $acc->firstChild->nodeValue . "\n";
				
				echo $acc->firstChild->nodeValue . ' ';
			}
			
			$seqs = $xpath->query ('GBSeq_sequence', $GBSeq);
			foreach ($seqs as $seq)
			{
				$fasta .= chunk_split($seq->firstChild->nodeValue, 60, "\n");
				$fasta .= "\n";
			}			

		}
	}
	
	echo '<br/>';
	
	
	echo '<pre>';
	echo $fasta;
	echo '</pre>';
	
	/*
	$rid = time();
	
	
	$filename = 'tmp/' . $rid . '.fas';
	
	file_put_contents($filename, $fasta);

	$basename = preg_replace('/\.fas$/', '', $filename);

	$command = '/usr/local/bin/clustalw2 -INFILE=' . $filename . ' -QUICKTREE -OUTORDER=INPUT -OUTPUT=NEXUS' . ' 1>tmp/' . $rid . '_CLUSTALW.log';

	echo 'Alignments' . '<br/>';

	echo $command;

	system($command);
	
// Create NEXUS file for PAUP
$nxs_filename = $basename . '.nxs';

$nexus = file_get_contents($nxs_filename);

$nexus .= "\n";
$nexus .="[PAUP block]\n";
$nexus .="begin paup;\n";
$nexus .="   [root trees at midpoint]\n";
$nexus .="   set rootmethod=midpoint;\n";
$nexus .="   set outroot=monophyl;\n";
$nexus .="   [construct tree using neighbour-joining]\n";
$nexus .="   nj;\n";
$nexus .="   [ensure branch lengths are output as substituions per nucleotide]\n";
$nexus .="   set criterion=distance;\n";
$nexus .="   [write rooted trees in Newick format with branch lengths]\n";
$nexus .="   savetrees format=nexus root=yes brlen=yes replace=yes;\n";
$nexus .="   quit;\n";
$nexus .="end;\n";

$nexus_filename = $basename . '.nex';
file_put_contents($nexus_filename, $nexus);


echo 'Building tree' . '<br />';

// Run PAUP
$command = '/usr/local/bin/paup ' . $nexus_filename .  ' 1>tmp/' . $rid . '_PAUP.log';

echo $command; 
system($command);	

$tree_url = $basename . '.tre';
	
	// draw tree
	echo '<a href="tree_url_svg.php?url=http://iphylo.org/~rpage/services/' + $tree_url + '" target="_new"><img src="images/inkscape.png" border="0" align="absmiddle">SVG</a>';

	*/
	
}


// align


// maketree


//----------------------------------------------------------------------------------------
function display_form()
{
	echo 
'<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" /><style type="text/css" title="text/css">
	body {
		font-family: sans-serif;
		margin:20px;
		}
</style>
<title>Get sequences</title>
</head>
<body>
<a href="index.html">Services</a>
<h1>Get sequences</h1>
<p>Fetch sequence using their accession numberss</p>
<p>Paste accession numbers, one per line</p>
<form method="post" action="seqfetch.php">
	<textarea id="text" name="text" rows="30" cols="60"></textarea><br />
	<!--
	<select name="format">
		<option value="html">HTML</option>
		<option value="json">JSON</option>
	</select><br />
	-->
	<input type="submit" value="Go"></input>
</form>

</body>
</html>';


}


function seq_fetch($text, $format = 'fasta')
{
	//$obj = new stdclass;
	//$obj->seqs = array();
	
	$ids = explode("\n", trim($text));
	
	$hits = fetch_sequences($ids);
	
	echo '<pre>';
	print_r($hits);
	echo '</pre>';


}


function main()
{
	$text = '';
	$format = '';
	if (isset($_POST['text']) && ($_POST['text'] != ''))
	{
		$text = $_POST['text'];
		
		if (isset( $_POST['format']))
		{
			$format = $_POST['format'];
		}
		
		seq_fetch($text, $format);
	}
	else
	{
		display_form();
	}
}
	


main();

?>