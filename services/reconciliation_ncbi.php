<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class NCBITaxonomyService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'NCBI Taxonomy';
		
		// Freebase has a namespace for NCBI taxonomy
		// http://www.freebase.com/view/biology/ncbi
		// e.g. http://rdf.freebase.com/ns/biology.ncbi.119089
		$this->identifierSpace 	= 'http://rdf.freebase.com/ns/biology.ncbi';

		// NCBI Taxon ID
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/biology.organism_classification.ncbi_taxon_id	';
		
		$this->Types();
		
		$view_url = 'http://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?mode=Info&id={{id}}';

		$preview_url = '';	
		$width = 430;
		$height = 300;
		
		if ($view_url != '')
		{
			$this->View($view_url);
		}
		if ($preview_url != '')
		{
			$this->Preview($preview_url, $width, $height);
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Types()
	{
		$type = new stdclass;
		$type->id = '/biology/organism_classification/scientific_name';
		$type->name = 'Scientific name';
		$this->defaultTypes[] = $type;
	} 	
		
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1)
	{
		$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=taxonomy" 
			. "&tool=TNS"
			. "&term=" . urlencode($text)
			. "&retmode=xml";

		//echo $url;

		$xml = get($url);
		
		//echo $xml;
		
		if ($xml != '')
		{
			$dom= new DOMDocument;
			$dom->loadXML($xml);
			$xpath = new DOMXPath($dom);
			$xpath_query = "//eSearchResult/Count";
			
			$count = 0;
			
			$nodeCollection = $xpath->query ($xpath_query);
			foreach($nodeCollection as $node)
			{
				$count = $node->firstChild->nodeValue;
			}
			
			if ($count > 0)
			{
				$xpath_query = "//eSearchResult/IdList/Id";
				$nodeCollection = $xpath->query ($xpath_query);
				foreach($nodeCollection as $node)
				{
					$hit = new stdclass;
					$hit->score = 1;
					$hit->match = ($count == 1);
					$hit->name 	= $text;
					$hit->id 	= $node->firstChild->nodeValue;
					
					$this->StoreHit($query_key, $hit);
				}
			}
		}
		
	}
	
	
}

$service = new NCBITaxonomyService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>