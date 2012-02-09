<?php

// Global names

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class GniService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'Global Names Index';

		$this->identifierSpace 	= 'http://gni.globalnames.org/';
		
		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://gni.globalnames.org/name_strings/{{id}}';

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
		$url = 'http://gni.globalnames.org/name_strings.json?search_term=' . rawurlencode($text);
		
		$limit = 5;
		
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
			
		$n = min($limit, $obj->name_strings_total);
		for ($i = 0; $i < $n; $i++)
		{
			$hit = new stdclass;
			$hit->match = ($obj->name_strings_total == 1);
			$hit->name 	= $obj->name_strings[$i]->name;
			$hit->id 	= $obj->name_strings[$i]->id;
			similar_text($text, $hit->name, $hit->score);
			
			$this->StoreHit($query_key, $hit);
		}
		
	
	}
	
	
}

$service = new GniService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>