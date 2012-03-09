<?php

// Search GBIF for occurence (local copy and remote)


require_once (dirname(dirname(__FILE__)) . '/lib.php');

//--------------------------------------------------------------------------------------------------
function get_other_identifiers(&$occurrence)
{
	global $db;
	
	
	$sql = 'SELECT * FROM gbif_identifiers WHERE occurrenceID=' . $occurrence->occurrenceID . ' LIMIT 6';

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	
	while (!$result->EOF)
	{
		if (!isset($occurrence->identifiers))
		{
			$occurrence->identifiers =  array();
		}
		
		$identifier = new stdclass;
		$identifier->identifier = $result->fields['identifier'];
		$identifier->identifierType = $result->fields['identifierType'];
		
		$occurrence->identifiers[] = $identifier;
	
		$result->MoveNext();	
	}
}


//--------------------------------------------------------------------------------------------------
function get_synonyms(&$occurrence)
{
	global $db;
	
	$sql = 'SELECT * FROM nub_2011_10_31 WHERE name =  ' . $db->qstr($occurrence->scientificName) 
		. ' AND s <> ' . $db->qstr($occurrence->scientificName)
		. ' AND s IS NOT NULL';
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	while (!$result->EOF) 
	{	
		if (!isset($occurrence->synonyms))
		{
			$occurrence->synonyms =  array();
		}
		$occurrence->synonyms[] = $result->fields['s'];
	
		$result->MoveNext();
	}
	
	$sql = 'SELECT * FROM nub_2011_10_31 WHERE s =  ' . $db->qstr($occurrence->scientificName) 
		. ' AND name <> ' . $db->qstr($occurrence->scientificName);
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	while (!$result->EOF) 
	{			
		if (!isset($occurrence->synonyms))
		{
			$occurrence->synonyms =  array();
		}
		$occurrence->synonyms[] = $result->fields['name'];
	
		$result->MoveNext();
	}
	
	if (isset($occurrence->synonyms))
	{
		$occurrence->synonyms = array_unique($occurrence->synonyms);
	}
}

//--------------------------------------------------------------------------------------------------
function get_lineage ($taxonID, &$occurrence)
{
	$occurrence->lineage = array();
	
	$url = 'http://data.gbif.org/ws/rest/taxon/get/' . $taxonID;
	
		
	$xml = get($url);
	
	//echo $xml;
	
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath->registerNamespace('gbif', 	'http://portal.gbif.org/ws/response/gbif');
	$xpath->registerNamespace('to', 	'http://rs.tdwg.org/ontology/voc/TaxonOccurrence#');
	$xpath->registerNamespace('tc', 	'http://rs.tdwg.org/ontology/voc/TaxonConcept#');
	$xpath->registerNamespace('tc', 'http://rs.tdwg.org/ontology/voc/TaxonConcept#');
	$xpath->registerNamespace('tn', 'http://rs.tdwg.org/ontology/voc/TaxonName#');
						
						
	$xpath_query = "//tn:TaxonName";
	$nodeCollection = $xpath->query ($xpath_query);
	foreach($nodeCollection as $node)
	{
		$name = '';
		
		$xpath_query = "tn:nameComplete";
		$nc = $xpath->query ($xpath_query, $node);
		foreach($nc as $n)
		{
			$name = $n->firstChild->nodeValue;					
		}


		$xpath_query = "tn:rankString";
		$nc = $xpath->query ($xpath_query, $node);
		foreach($nc as $n)
		{
			$rank = $n->firstChild->nodeValue;		
			switch ($rank)
			{
				case 'family':
					$occurrence->family = $name;
					$occurrence->lineage[] = $name;
					break;
				case 'class':
					$occurrence->class = $name;
					$occurrence->lineage[] = $name;
					break;
				case 'kingdom':
					$occurrence->kingdom = $name;
					$occurrence->lineage[] = $name;
					break;
				default:
					break;
			}
		}
	}
	
	
					
	

	

}


//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'gbif'			# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$code = '';
$institutioncode = '';
$catalognumber = '';
$callback = '';

$js = '';

if (isset($_GET['institutioncode']))
{
	$institutioncode = $_GET['institutioncode'];
}
if (isset($_GET['catalognumber']))
{
	$catalognumber = $_GET['catalognumber'];
}
if (isset($_GET['code']))
{
	$code = $_GET['code'];
}

if ($code != '')
{
	if (preg_match('/^KU Natural History Museum \d+/', $code))
	{
		$code = str_replace('KU Natural History Museum ', 'KU ', $code);
	}

	$parts = explode(' ', $code);
	$institutioncode 	= $parts[0];
	
	// clean
	switch ($institutioncode)
	{
		case 'IBUNAM-EM':
			$institutioncode = 'IBUNAM';
			break;
			
		default:
			break;
	}
	
	
	
	$catalognumber 		= $parts[1];
	
	$n = count($parts);
	for ($i = 2; $i<$n; $i++)
	{
		$catalognumber .= ' ' . $parts[$i];
	}
	
	if (preg_match('/^CASENT (?<cat>.*)$/', $code, $m))
	{
		$institutioncode = 'casent';
		$catalognumber = 'casent' . $m['cat'];
	}
}

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

if (($institutioncode != '') && ($catalognumber != ''))
{
	$local = true;
	
	switch ($institutioncode)
	{
		case 'KU':
			//$local = false;
			break;
			
		default:
			break;
	}
	
	if ($local)
	{
	
		switch ($institutioncode)
		{
			case 'USNM':
				$sql = 'SELECT * FROM gbif_occurrences
					WHERE (institutionCode = ' . $db->qstr($institutioncode) .
					') AND (catalogueNumber LIKE ' . $db->qstr($catalognumber . '%') .
					') LIMIT 10';
				break;
				
			default:
				$sql = 'SELECT * FROM gbif_occurrences
					WHERE (institutionCode = ' . $db->qstr($institutioncode) .
					') AND (catalogueNumber = ' . $db->qstr($catalognumber) .
					') LIMIT 10';
				break;
		}
		
		$obj = new stdclass;
		$obj->occurrences = array();
		
		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
		while (!$result->EOF) 
		{
			$occurrence = new stdclass;
			$occurrence->occurrenceID 	 	= $result->fields['occurrenceID'];
			$occurrence->datasetID 	 		= $result->fields['datasetID'];
			$occurrence->institutionCode 	= $result->fields['institutionCode'];
			$occurrence->collectionCode  	= $result->fields['collectionCode'];
			$occurrence->catalogueNumber   	= $result->fields['catalogueNumber'];
			
			$occurrence->taxonID 	 		= $result->fields['taxonID'];
			$occurrence->scientificName 	= $result->fields['scientificName'];
		
			$occurrence->kingdom		   	= $result->fields['kingdom'];
			$occurrence->class   			= $result->fields['class'];
			$occurrence->family   			= $result->fields['family'];
			
			$occurrence->lineage = array($result->fields['kingdom'], $result->fields['class'], $result->fields['family']);
			
			// GBIF synonyms...
			get_synonyms($occurrence);
			
			// Other identifiers
			get_other_identifiers($occurrence);
			
			$obj->occurrences[$occurrence->occurrenceID] = $occurrence;
			
			$result->MoveNext();	
			
		}
	}
	else
	{
		$url = 'http://data.gbif.org/ws/rest/occurrence/list?institutioncode=' . $institutioncode . '&catalognumber=' . $catalognumber;
		
		$xml = get($url);
		
		//echo $xml;
		
		$dom= new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
		
		$xpath->registerNamespace('gbif', 	'http://portal.gbif.org/ws/response/gbif');
		$xpath->registerNamespace('to', 	'http://rs.tdwg.org/ontology/voc/TaxonOccurrence#');
		$xpath->registerNamespace('tc', 	'http://rs.tdwg.org/ontology/voc/TaxonConcept#');
		$xpath->registerNamespace('tn', 	'http://rs.tdwg.org/ontology/voc/TaxonName#');

		$obj = new stdclass;
		$obj->occurrences = array();		
		
		$nodeCollection = $xpath->query ("//to:TaxonOccurrence");
		foreach($nodeCollection as $node)
		{
			$occurrence = new stdclass;
			$occurrence->institutionCode = $institutioncode;
			
			if ($node->hasAttributes()) 
			{ 
				$attributes = array();
				$attrs = $node->attributes; 
				
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
				
				$occurrence->occurrenceID = $attributes['gbifKey'];
				
				$xpath_query = "to:catalogNumber";
				$nc = $xpath->query ($xpath_query, $node);
				foreach($nc as $n)
				{
					$occurrence->catalogueNumber = $n->firstChild->nodeValue;					
				}
					
				$xpath_query = "to:identifiedTo/to:Identification/to:taxon/tc:TaxonConcept/@gbifKey";
				$nc = $xpath->query ($xpath_query, $node);
				foreach($nc as $n)
				{
					$occurrence->taxonID = $n->firstChild->nodeValue;					
				}

				$xpath_query = "to:identifiedTo/to:Identification/to:taxon/tc:TaxonConcept/tc:hasName/tn:TaxonName/tn:nameComplete";
				$nc = $xpath->query ($xpath_query, $node);
				foreach($nc as $n)
				{
					$occurrence->scientificName = $n->firstChild->nodeValue;					
				}
			}
			
			get_lineage($occurrence->taxonID, $occurrence);
			$obj->occurrences[$occurrence->occurrenceID] = $occurrence;
			
		}
	
	
	
	
	}
	
	$js = json_encode($obj);
}

if ($callback != '')
{
	echo $callback . '(';
}
echo $js;
if ($callback != '')
{
	echo ')';
}
?>