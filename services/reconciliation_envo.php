<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');
require_once (dirname(__FILE__) . '/lib/nusoap.php');


//--------------------------------------------------------------------------------------------------
class EnvoService extends ReconciliationService
{
	var $client;
	
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'Environment Ontology';
		
		// ENVO has PURLs
		$this->identifierSpace 	= 'http://purl.obolibrary.org/obo/';

		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://purl.obolibrary.org/obo/{{id}}';

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
		$type->id = '/base/ecology/habitat';
		$type->name = 'Habitat';
		$this->defaultTypes[] = $type;
	} 
	
	
	//----------------------------------------------------------------------------------------------
	// Do any query intialisation here, such as construct SOAP client
	function QueryInitialise()
	{
		global $config;
		
		$this->client = new nusoap_client('http://www.ebi.ac.uk/ontology-lookup/OntologyQuery.wsdl', 'wsdl',
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
	function OneQuery($query_key, $text, $limit = 5)
	{
		$param = array(
			'partialName' => $text, 
			'ontologyName' => 'ENVO',
			'reverseKeyOrder' => false
			);			
			
		//print_r($param);
			
		$envo_result = $this->client->call('getTermsByName', $param);
		
		//print_r($envo_result);
		
		//$envo_result = $this->client->call('getOntologyNames');
		
		
		//print_r($this->client);
			
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
				//print_r($envo_result);
				
				// Extract names

				$n = count($envo_result['getTermsByNameReturn']['item']);
				
				//echo "n=$n\n";
				
				$n = min($n, $limit);
				for ($i = 0; $i < $n; $i++)
				{
					$hit = new stdclass;
					
					//echo $envo_result['getTermsByNameReturn']['item'][$i]['key'] . "\n";
					
					similar_text($text, $envo_result['getTermsByNameReturn']['item'][$i]['value'], $hit->score);
					$hit->match = ($hit->score == 100);					
					$hit->id 	= $envo_result['getTermsByNameReturn']['item'][$i]['key'];
					$hit->name 	= $envo_result['getTermsByNameReturn']['item'][$i]['value'];
					$this->StoreHit($query_key, $hit); 
				}
				
			}
		}
		
	}
	
	
}

$service = new EnvoService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

if (0)
{
	$service->QueryInitialise();
	$service->OneQuery('q0', 'hydrothermal vent');
	print_r($service->result);
}


?>