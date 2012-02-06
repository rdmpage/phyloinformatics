<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');
require_once (dirname(__FILE__) . '/lib/nusoap.php');


//--------------------------------------------------------------------------------------------------
class UbioService extends ReconciliationService
{
	var $client;
	
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'uBio FindIT';
		
		// NameBankID as LSID
		$this->identifierSpace 	= 'urn:lsid:ubio.org:namebank:';

		// Freebase object
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://www.ubio.org/browser/details.php?namebankID={{id}}';

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
		
		$this->client = new nusoap_client('http://names.ubio.org/soap/', 'wsdl',
					$config['proxy_name'], $config['proxy_port'], '', '');
			
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
			'url' => '',
			'freeText' => base64_encode($text),
			'strict' => 0,
			'threshold' => 0.5
			);			
	
		$proxy = $this->client->getProxy();				
		$ubio_result = $proxy->findIT(
			$param['url'], 
			$param['freeText'], 
			$param['strict'], 
			$param['threshold']
			);
			
		// Check for a fault
		if ($proxy->fault) 
		{
			// handle this
		} 
		else 
		{
			// Check for errors
			$err = $proxy->getError();
			if ($err) 
			{
			}
			else 
			{
				// Extract names
				$xml = $ubio_result['returnXML'];
								
				// Fix entities that uBio may mangle
				$xml = str_replace("&Atilde;", "Ã", $xml );
				$xml = str_replace("&shy;", '-', $xml);
				$xml = str_replace("&copy;", '©', $xml);
				
				if ($xml != '')
				{
					$dom= new DOMDocument;
					$dom->loadXML($xml);
					$xpath = new DOMXPath($dom);
					$xpath_query = "//allNames/entity";
					$nodeCollection = $xpath->query ($xpath_query);
					$nameString = '';
					
					foreach($nodeCollection as $node)
					{
						$hit = new stdclass;
						
						foreach ($node->childNodes as $v) 
						{						
							$name = $v->nodeName;
							if ($name == "nameString")
							{
								//$nameString = $v->firstChild->nodeValue;
							}
							if ($name == "score")
							{
								$hit->score = (double)$v->firstChild->nodeValue;
								$hit->match = ($hit->score == 1);
							}
							if ($name == "namebankID")
							{
								$hit->id = $v->firstChild->nodeValue;
							}
							if ($name == "parsedName")
							{
								// Much grief, we need to get attribute of this node
								$n = $v->attributes->getNamedItem('canonical');
								$hit->name = $n->nodeValue;
							}						
						}						
						$this->StoreHit($query_key, $hit); 
					}
					
				}
			}
		}
	
	
	}
	
	
}

$service = new UbioService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>