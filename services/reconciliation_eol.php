<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class EolService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'Encyclopedia of Life';

		// Freebase has a namespace for EOL
		// http://www.freebase.com/edit/topic/biology/eol
		$this->identifierSpace 	= 'http://rdf.freebase.com/ns/en.encyclopedia_of_life';
		
		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://eol.org/pages/{{id}}';

		$preview_url = 'http://iphylo.org/~rpage/phyloinformatics/eoliphone/preview.php?id={{id}}';	
		$width = 300;
		$height = 200;
		
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
		$url = 'http://eol.org/api/search/1.0/' . rawurlencode($text) . '.json';
		
		/*
		if ($limit == 1)
		{
			$url .= '?exact=1';
		}
		else
		{
			$url .= '?exact=0';
		}
		*/
		$limit = 3;
		$url .= '?exact=0';
		
		if (0)
		{
			file_put_contents('tmp/r.txt', "URL = $url\n", FILE_APPEND);
		}			
						
		$json = get($url);
		
		if (0)
		{
			file_put_contents('tmp/r.txt', $json . "\n", FILE_APPEND);
		}			
		
		
		$obj = json_decode($json);
		
		if (0)
		{
			file_put_contents('tmp/r.txt', print_r($obj, true), FILE_APPEND);
		}			
			
		$n = min($limit, $obj->totalResults);
		for ($i = 0; $i < $n; $i++)
		{
			$hit = new stdclass;
			$hit->score = 1;
			$hit->match = ($obj->totalResults == 1);
			$hit->name 	= $obj->results[$i]->title;
			$hit->id 	= $obj->results[$i]->id;
			
			$this->StoreHit($query_key, $hit);
		}
		
	
	}
	
	
}

$service = new EolService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>