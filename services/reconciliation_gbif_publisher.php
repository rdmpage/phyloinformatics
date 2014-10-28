<?php

// GBIF publisher

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class GBIFPublisherService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'GBIF Publisher';
		
		$this->identifierSpace 	= 'http://www.gbif.org/';

		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://www.gbif.org/publisher/{{id}}';

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
		$type->id = '/common/topic';
		$type->name = 'Freebase topic';
		$this->defaultTypes[] = $type;
	} 	
		
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1, $properties = null)
	{
	
		$url = 'http://api.gbif.org/v1/organization?q=' . urlencode($text);
	
		$json = get($url);
		
		if ($json != '')
		{
			$obj = json_decode($json);
			
			foreach ($obj->results as $result)
			{
				$hit = new stdclass;
				$hit->id 	= $result->key;
				$hit->name 	= $result->title;
				similar_text($text, $hit->name, $hit->score);
				$hit->match = ($hit->score == 1);
				$this->StoreHit($query_key, $hit);
			}
		}
		
	}
	
	
}

$service = new GBIFPublisherService();

if (1)
{
	file_put_contents(dirname(__FILE__) . '/tmp/publisher.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>