<?php

require_once(dirname(dirname(__FILE__)) . '/lib.php');

//--------------------------------------------------------------------------------------------------
function post($url, $postfields)
{
	global $config;

	$ch = curl_init(); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,	1); 
	if ($config['proxy_name'] != '')
	{
		curl_setopt ($ch, CURLOPT_PROXY, $config['proxy_name'] . ':' . $config['proxy_port']);
	}
	
	$html = '';
	
	curl_setopt ($ch, CURLOPT_URL, $url); 	
	curl_setopt ($ch, CURLOPT_POST, TRUE);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
	$curl_result = curl_exec ($ch); 
	
	//echo $curl_result;
	
	if (curl_errno ($ch) != 0 )
	{
		echo "CURL error: ", curl_errno ($ch), " ", curl_error($ch);
	}
	else
	{
		$info = curl_getinfo($ch);
				
		$http_code = $info['http_code'];
		if (HttpCodeValid ($http_code))
		{
			$html = $curl_result;
		}
	}
	return $html;
}

//--------------------------------------------------------------------------------------------------
function send_blast_job($gi)
{
	$job = new stdclass;
	$job->rid = '';
	$job->rtoe = 0;

	// BLAST
	$cmds = array(
	"CMD" 			=> "Put",
	"PROGRAM" 		=> "blastn",
	"DATABASE" 		=> "nr",
	"QUERY" 		=> $gi
	);
	
	$html = post('http://www.ncbi.nlm.nih.gov/blast/Blast.cgi', $cmds);
	
	if (preg_match('/^    RID = (?<rid>.*)$/m', $html, $m))
	{
		$job->rid = $m['rid'];
	}
	if (preg_match('/^    RTOE = (?<rtoe>.*)$/m', $html, $m))
	{
		$job->rtoe = $m['rtoe'];
	}

	return $job;
}


//--------------------------------------------------------------------------------------------------
// Poll BLAST to see if our results are ready
function get_results($rid)
{
	$done = false;
	while (!$done)
	{
		sleep(5);
		
		$url = "http://www.ncbi.nlm.nih.gov/blast/Blast.cgi?CMD=Get&RID=$rid";
		
		$html = get($url);
		
		//echo $html;
		
		if (preg_match('/\s+Status=WAITING/m', $html))
		{
			echo "Searching...\n";
		}
		if (preg_match('/\s+Status=FAILED/m', $html))
		{
			echo "Search $rid failed; please report to blast-help\@ncbi.nlm.nih.gov.\n";
			exit(4);
		}
		if (preg_match('/\s+Status=UNKNOWN/m', $html))
		{
			echo "Search $rid expired.\n";
			exit(3);
		}
		if (preg_match('/\s+Status=READY/m', $html))
		{
			echo "Search complete, retrieving results...\n";
			$done = true;
		}
	}
	
	$url = "http://www.ncbi.nlm.nih.gov/blast/Blast.cgi?CMD=Get&RID=$rid&FORMAT_TYPE=XML";
	$result = get($url);
	
	return $result;
}


if (0)
{
	$gi = 110332583;
	$gi = 110332932;
	$gi =  98374981;
	$gi = 238819949; // FJ559186 Gephyromantis cf. 'decaryi' 9 MV-2009 voucher ZCMV 5223
	
	$gi = 262235462; // GQ899001 Watshamiella sp. 1 ex Ficus sur cytochrome oxidase subunit I (COI) gene, partial cds; mitochondrial
	
	// Fern hits angiosperm
	$gi = 156535324; // EF590582 Adiantum venustum voucher USBG 00-0037A RNA polymerase C (rpoC1) gene, partial cds; chloroplast.
	
	$gi = 291297522; // Hyperiidea sp. AC-2010 18S ribosomal RNA gene, partial sequence
	
	// BLAST
	$cmds = array(
	"CMD" 			=> "Put",
	"PROGRAM" 		=> "blastn",
	"DATABASE" 		=> "nr",
	"QUERY" 		=> $gi
	);
	
	$html = post('http://www.ncbi.nlm.nih.gov/blast/Blast.cgi', $cmds);
	
	//echo $html;
	
	$rid = '';
	$rtoe = 0;
	
	if (preg_match('/^    RID = (?<rid>.*)$/m', $html, $m))
	{
		$rid = $m['rid'];
	}
	if (preg_match('/^    RTOE = (?<rtoe>.*)$/m', $html, $m))
	{
		$rtoe = (Integer)$m['rtoe'];
	}
	
	if ($rid != '')
	{
		echo "Request RID=$rid\n";
		echo "Estimated time to completion=$rtoe\n";
		
		sleep($rtoe);
		
		$xml = get_results($rid);
		
		file_put_contents('tmp/' . $rid . '.xml', $xml);
		
		echo $xml;
		
		// Convert GBIF XML to Javascript for Google Maps
		$xp = new XsltProcessor();
		$xsl = new DomDocument;
		$xsl->load('blast2fasta.xsl');
		$xp->importStylesheet($xsl);
		
		$dom = new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
	
		$fasta = $xp->transformToXML($dom);
		
		echo $fasta;
		
		file_put_contents('tmp/' . $rid . '.fas', $fasta);
		

		
	}
}

?>