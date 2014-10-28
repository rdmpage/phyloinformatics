<?php

// VIAF person name

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class ViafService extends ReconciliationService
{
	var $client;
	
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'Virtual International Authority File';
		
		// Freebase has a namespace for VIAF
		// https://www.freebase.com/user/hangy/viaf		
		$this->identifierSpace 	= 'http://rdf.freebase.com/ns/user/hangy/viaf';

		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://viaf.org/viaf/{{id}}';

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
		$type->id = '/people/person';
		$type->name = 'Person';
		$this->defaultTypes[] = $type;
	} 
	
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1, $properties = null)
	{
		$url = 'http://viaf.org/viaf/search?query=' . urlencode('local.personalNames all "' . $text . '"')
			. '&httpAccept=' . urlencode('application/rss+xml');
			
		//echo $url . "\n";
			
		$xml = get($url);
		//echo $xml;
		
		if ($xml != '')
		{
			$dom= new DOMDocument;
			$dom->loadXML($xml);
			$xpath = new DOMXPath($dom);
			
			$xpath->registerNamespace('opensearch', 'http://a9.com/-/spec/opensearch/1.1/');
			
			$xpath_query = "//opensearch:totalResults";
			
			$count = 0;
			
			$nodeCollection = $xpath->query ($xpath_query);
			foreach($nodeCollection as $node)
			{
				$count = $node->firstChild->nodeValue;
			}
			
			if ($count > 0)
			{
				$xpath_query = "//item/title";
				$nodeCollection = $xpath->query ($xpath_query);
				foreach($nodeCollection as $node)
				{
					$hit = new stdclass;
					$hit->score = 1;
					$hit->match = ($count == 1);
					$hit->name 	= $node->firstChild->nodeValue;
					
					$nc = $xpath->query ('../guid', $node);
					foreach($nc as $n)
					{
						$hit->id = str_replace('http://viaf.org/viaf/', '', $n->firstChild->nodeValue);
					}
					
					$this->StoreHit($query_key, $hit);
				}
			}
		}
		
		
	}	
	
}

$service = new ViafService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

if (0)
{
	$service->QueryInitialise();
	$service->OneQuery('q0', 'Mary J Rathbun');
	print_r($service->result);
}

?>