<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');
require_once (dirname(__FILE__) . '/lib/nusoap.php');


//--------------------------------------------------------------------------------------------------
class WormsService extends ReconciliationService
{
	var $client;
	
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'World Register of Marine Species';
		
		// WORMS as LSID
		$this->identifierSpace 	= 'urn:lsid:marinespecies.org:taxname:';

		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://www.marinespecies.org/aphia.php?p=taxdetails&id={{id}}';

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
	// Do any query intialisation here, such as construct SOAP client
	function QueryInitialise()
	{
		global $config;
		
		$this->client = new nusoap_client('http://www.marinespecies.org/aphia.php?p=soap&wsdl=1', 'wsdl',
					$config['proxy_name'], $config['proxy_port'], '', '');
					
		//print_r($this->client);					
			
		$err = $this->client->getError();
		if ($err) 
		{
			// handle this...
			
		}
		$this->client->setUseCurl(true);
	}
	
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1)
	{
		$param = array(
			'scientificnames' => array($text), // getAphiaRecordsByNames expects array
			'like' => true,
			'fuzzy' => true,
			'marine_only' => false
			);			
			
		$worms_result = $this->client->call('getAphiaRecordsByNames', $param);
			
		// Check for a fault
		if ($this->client->fault) 
		{
			// handle this
		} 
		else 
		{
			// Check for errors
			$err = $this->client->getError();
			if ($err) 
			{
			}
			else 
			{
				// Extract names
				$limit = 3;
				
				$n = count($worms_result[0]);
				
				$n = min($n, $limit);
				for ($i = 0; $i < $n; $i++)
				{
					$hit = new stdclass;
					
					similar_text($text, $worms_result[0][$i]['scientificname'], $hit->score);
					$hit->match = ($hit->score == 100);					
					$hit->id 	= $worms_result[0][$i]['AphiaID'];
					$hit->name 	= $worms_result[0][$i]['scientificname'];
					$this->StoreHit($query_key, $hit); 
				}
			}
		}
		
	}
	
	
}

$service = new WormsService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

//$service->QueryInitialise();
//$service->OneQuery('q0', 'Delphinus delphis');
//print_r($service->result);


?>