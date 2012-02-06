<?php

// Base class for Google Refine reconciliation service
// http://code.google.com/p/google-refine/wiki/ReconciliationServiceApi

require_once (dirname(dirname(__FILE__)) . '/lib.php');

//--------------------------------------------------------------------------------------------------
class ReconciliationService
{
	var $name;
	var $identifierSpace;
	var $schemaSpace;
	var $defaultTypes = array();
	var $result;
	
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= '';
		$this->identifierSpace 	= '';
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id'; // FreeBase object id

		
		$this->Types();
		
		$view_url = '';

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
	function View($url)
	{
		$this->view = new stdclass;
		$this->view->url = $url;	
	}

	//----------------------------------------------------------------------------------------------
	function Preview($url, $width = 430, $height = 300)
	{
		$this->preview = new stdclass;
		$this->preview->url = $url;	
		$this->preview->width = $width;	
		$this->preview->height = $height;	
	}
		
	//----------------------------------------------------------------------------------------------
	function Types()
	{
		$type = new stdclass;
		$type->id = 'type/rawstring';
		$type->name = 'Machine readable string';
		$this->defaultTypes[] = $type;
	}
	
	//----------------------------------------------------------------------------------------------
	function Metadata($callback = '')
	{
		header ("Content-type: text/plain\n\n");
		if ($callback != '')
		{
			echo $callback . '(';
		}
		
		$metadata = new stdclass;
		$metadata->name 			=  $this->name;
		$metadata->identifierSpace 	=  $this->identifierSpace;
		$metadata->schemaSpace 		=  $this->schemaSpace;
		
		if (isset($this->view))
		{
			$metadata->view =  $this->view;
		}
		if (isset($this->preview))
		{
			$metadata->preview =  $this->preview;
		}
		if (isset($this->defaultTypes))
		{
			$metadata->defaultTypes =  $this->defaultTypes;
		}

		echo json_format(json_encode($metadata));
		if ($callback != '')
		{
			echo ')';
		}
	}

	//----------------------------------------------------------------------------------------------
	// Do any query intialisation here, such as construct SOAP client
	function QueryInitialise()
	{
	}

	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function StoreHit($query_key, $hit)
	{
		$hit->type[] = $this->defaultTypes[0];
		$this->result->${query_key}->result[] = $hit;	
	}

	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1)
	{
	
	}

	//----------------------------------------------------------------------------------------------
	function Query($queries, $callback)
	{
		$q = json_decode(stripcslashes($queries));
		
		$this->result = new stdclass;
		
		$this->QueryInitialise();
		
		foreach ($q	as $query_key => $query)
		{
			$text = $query->query;
			
			$limit = 1;
			if (isset($query->limit))
			{
				$limit = $query->limit;
			}				
			$this->result->${query_key}->result = array();
			
			$this->OneQuery($query_key, $text, $limit);

			if (count($this->result->${query_key}->result) == 0)
			{
				unset($this->result->${query_key}->result);
			}


		}
		
		if (0)
		{
			file_put_contents('tmp/r.txt', "Return: \n" . print_r($this->result, true), FILE_APPEND);
		}			
				
		header ("Content-type: text/plain\n\n");
		if ($callback != '')
		{
			echo $callback . '(';
		}
		echo json_format(json_encode($this->result));
		if ($callback != '')
		{
			echo ')';
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Call($parameters)
	{
		$queries = '';
		if (isset($parameters['queries']))
		{
			$queries = $parameters['queries'];
		}
		
		$callback = '';
		if (isset($parameters['callback']))
		{
			$callback = $parameters['callback'];
		}								
		
		if ($queries == '')
		{
			$this->Metadata($callback);
		}
		else
		{
			$this->Query($queries, $callback);		
		}
	}
}


?>