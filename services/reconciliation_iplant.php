<?php

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class iPlantService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'iPlant Taxonomic Name Resolution Service';

		$this->identifierSpace 	= 'http://tnrs.iplantcollaborative.org/';
		
		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = ''; // no identifiers (yet)

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
		$url = 'http://tnrs.iplantc.org/tnrsm-svc/matchNames?retrieve=all&names=' . rawurlencode($text);
		
		$limit = 3;
		
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
			
		$n = min($limit, count($obj->items));
		for ($i = 0; $i < $n; $i++)
		{
			$hit = new stdclass;
			$hit->match = (count($obj->items) == 1);
			$hit->name 	= $obj->items[$i]->acceptedName;
			similar_text($text, $hit->name, $hit->score);
			$hit->id 	= '';
			
			$this->StoreHit($query_key, $hit);
		}
		
	
	}
	
	
}

$service = new iPlantService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>