<?php

// uBio FindIT

require_once (dirname(__FILE__) . '/reconciliation_api.php');


//--------------------------------------------------------------------------------------------------
class GBIFTaxonomyService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'GBIF';
		
		$this->identifierSpace 	= 'http://portal.gbif.org/ws/response/gbif';

		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		
		$this->Types();
		
		$view_url = 'http://data.gbif.org/species/{{id}}';

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
		$url = 'http://data.gbif.org/ws/rest/taxon/list?scientificname=' . urlencode($text) . '&dataresourcekey=1';
	
		$xml = get($url);
		
		//echo $xml;
		
		if ($xml != '')
		{
			$dom= new DOMDocument;
			$dom->loadXML($xml);
			$xpath = new DOMXPath($dom);
			
			$xpath->registerNamespace('tc', 'http://rs.tdwg.org/ontology/voc/TaxonConcept#');
			$xpath->registerNamespace('tn', 'http://rs.tdwg.org/ontology/voc/TaxonName#');
						
			$xpath_query = "//tc:TaxonConcept";
			
			

			$nodeCollection = $xpath->query ($xpath_query);
			$count = $nodeCollection->length;
			foreach($nodeCollection as $node)
			{
				$hit = new stdclass;
				
				if ($node->hasAttributes()) 
				{ 
					$attributes = array();
					$attrs = $node->attributes; 
					
					foreach ($attrs as $i => $attr)
					{
						$attributes[$attr->name] = $attr->value; 
					}
					
					$hit->id = $attributes['gbifKey'];
				}
				
				$xpath_query = "//tn:nameComplete";
				$nc = $xpath->query ($xpath_query, $node);
				foreach($nc as $n)
				{
					$hit->name 	= $n->firstChild->nodeValue;
					similar_text($text, $hit->name, $hit->score);
					$hit->match = ($count == 1);
					
				}
				
				//print_r($hit);
				
				$this->StoreHit($query_key, $hit);
			}
		}
		
	}
	
	
}

$service = new GBIFTaxonomyService();

if (0)
{
	file_put_contents('tmp/r.txt', $_REQUEST['queries'], FILE_APPEND);
}

$service->Call($_REQUEST);

?>