<?php

require_once('../config.inc.php');
require_once('../lib.php');

require_once('../services/csq.php');

//--------------------------------------------------------------------------------------------------
function cleanList($points)
{
	$dataset = array();
	
	foreach ($points as $pt)
	{
		if (!in_array($pt, $dataset))
		{
			$dataset[] = $pt;
		}
	}
	
	return $dataset;
}

//--------------------------------------------------------------------------------------------------
// get points from GBIF
function get_gbif_data($name = 'Hapalemur')
{
	
	$url = 'http://api.gbif.org/v1/occurrence/search';
	
	$parameters = array(
		'scientificName' => $name,
		'limit' => 1000,
		'spatialIssues' => 'false',
		'hasCoordinate' => 'true'
		);
		
	$url .= '?' . http_build_query($parameters);
	
	//echo $url;
	
	$json = get($url);
	
	$obj = json_decode($json);
	
	return $obj;
}

//--------------------------------------------------------------------------------------------------
// return list of unique points
function get_points($obj)
{
	$points = array();
	
	foreach ($obj->results as $result)
	{
		$issues = $result->issues;
		
		$accept = true;
		
		if (in_array('ZERO_COORDINATE', $result->issues))
		{
			$accept = false;
		}
		if (in_array('COUNTRY_COORDINATE_MISMATCH', $result->issues))
		{
			$accept = false;
		}
		
		if ($accept)
		{
			$point = array(
				
				$result->decimalLatitude,
				$result->decimalLongitude
				);
			
			$points[] = $point;
		}
	}
	
	$uniques = cleanList($points);
	
	return $uniques;
}

//--------------------------------------------------------------------------------------------------


function display_form()
{
	$html = '<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Overlap</title>
	</head>
	<body>
	<b>Compare distributions for species in GBIF and Flickr</b>
	<form method="get" action="index.php">
		<input id="q" name="q" size="60" placeholder="taxon name">
		<input type="submit" value="Go"></input>
	</form>
</body>
</html>';

	echo $html;


}

function overlap($query)
{
	$html = '';
	
	$html .= '<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Overlap</title>
	</head>
	<body>';
	
	$html .= '<p><a href=".">Back</a>';
	$html .= '<p>Results for <b>' . $query . '</b></p>';
	
	// GBIF
	$gbif_pts  = array();
	$gbif_csquare = array();
	
	$data = get_gbif_data($query);
	
	//print_r($data);exit();
	
	$gbif_pts = get_points($data);
	
	/*
	echo '<pre>';
	print_r($gbif_pts);
	echo '</pre>';
	//exit();
	*/
	
	// map
	$html .= '<h3>GBIF</h3>';
	$html .= '<object id="gbif" type="image/svg+xml" width="360" height="180" data="map.php?coordinates=' . json_encode($gbif_pts) . '"></object>';
	$html .= '<p><a href="http://www.gbif.org/species/search?q=' . $query . '" target="_new">View in GBIF</a></p>';
	//echo $html;
	
	
	foreach ($gbif_pts as $pt)
	{
		$csq = lat_lon_2_csquare($pt[0], $pt[1]);
		
		$gbif_csquare[] = $csq;
	}

	$gbif_csquare = array_unique($gbif_csquare);

	
	/*
	$url = 'http://data.gbif.org/ws/rest/taxon/list?scientificname=' . urlencode($query) . '&dataresourcekey=1';
	
	$xml = get($url);
	
	$js = '';
	
	if ($xml != '')
	{
		// Convert GBIF XML to Javascript
		$xp = new XsltProcessor();
		$xsl = new DomDocument;
		$xsl->load('../services/gbif-taxa2json.xsl');
		$xp->importStylesheet($xsl);
		
		$dom = new DOMDocument;
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
	
		$js = $xp->transformToXML($dom);
	}
	
	$obj = json_decode($js);
	
	if (count($obj->taxonConcepts) >= 1)
	{
		$id = $obj->taxonConcepts[0]->gbifKey;
		
		$url = 'http://data.gbif.org/ws/rest/density/list?taxonconceptkey=' . $id;
	
		$xml = get($url);
	
		$js = '';
	
		if ($xml != '')
		{
			// Convert GBIF XML to Javascript for Google Maps
			$xp = new XsltProcessor();
			$xsl = new DomDocument;
			$xsl->load('../services/gbif2json.xsl');
			$xp->importStylesheet($xsl);
			
			$dom = new DOMDocument;
			$dom->loadXML($xml);
			$xpath = new DOMXPath($dom);
		
			$js = $xp->transformToXML($dom);
			
			//print_r($js);
			
			$map = json_decode($js);
			
			//print_r($map);
			
			foreach ($map->cells as $cell)
			{
				$pt = array();
				$pt[0] = $cell->minLatitude;
				$pt[1] = $cell->minLongitude;
				$gbif_pts[] =  $pt;
				
			}
			
			//print_r($gbif_pts);
			
			// map
			$html .= '<h3>GBIF</h3>';
			$html .= '<object id="gbif" type="image/svg+xml" width="360" height="180" data="map.php?coordinates=' . json_encode($gbif_pts) . '"></object>';
			$html .= '<p><a href="http://data.gbif.org/species/' . $id . '" target="_new">View in GBIF</a></p>';
			//echo $html;
			
			
			foreach ($gbif_pts as $pt)
			{
				$csq = lat_lon_2_csquare($pt[0], $pt[1]);
				
				$gbif_csquare[] = $csq;
			}
	
			$gbif_csquare = array_unique($gbif_csquare);
			//print_r($gbif_csquare);
		}
		
	}
	
	if (count($obj->taxonConcepts) > 1)
	{
		$html .= '<p>More than one taxon with this name</p>';
	}
	*/
	
	// Flickr
	
	$flickr_pts  = array();
	$flickr_csquare = array();
	
	$tag = str_replace(' ', '', $query);
	$tag = strtolower($tag);
	
	$url = 'http://api.flickr.com/services/feeds/geo/?tags=' . $tag . '&lang=en-us&format=rss_200';
	
	$xml = get($url);
	
	//echo $xml;
	
	// Extract lat and long
	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);
	
	$xpath->registerNamespace('geo', 		'http://www.w3.org/2003/01/geo/wgs84_pos#');
	$xpath->registerNamespace('georss', 	'http://www.georss.org/georss');
	
	$nodeCollection = $xpath->query ("//item/georss:point");
	foreach($nodeCollection as $node)
	{
		$pt = $node->firstChild->nodeValue;
		$pts = explode(' ', $pt);
		$pts[0] = (Double)$pts[0];
		$pts[1] = (Double)$pts[1];
		$flickr_pts[] = $pts;
	}
	
	// make a map
	
	//print_r($flickr_pts);
	
	$html .= '<h3>Flicker</h3>';
	$html .= '<object id="map" type="image/svg+xml" width="360" height="180" data="map.php?coordinates=' . json_encode($flickr_pts) . '"></object>';
	$html .= '<p><a href="http://www.flickr.com/photos/tags/' . $tag . '/" target="_new">View in Flickr</a></p>';
	
	//echo $html;
	
	// compute overlap with GBIF
	
	foreach ($flickr_pts as $pt)
	{
		$csq = lat_lon_2_csquare($pt[0], $pt[1]);
		
		$flickr_csquare[] = $csq;
	}
	
	$flickr_csquare = array_unique($flickr_csquare);
	
	//print_r($flickr_csquare);
	
	// compute difference 
	
	$html .= '<h3>Summary</h3>';
	
	$html .= '<p>GBIF has ' . count($gbif_pts) . ' records.</p>';
	$html .= '<p>Flickr has ' . count($flickr_pts) . ' geotagged photos.</p>';
	
	$html .= '<p>GBIF has records from ' . count($gbif_csquare) . ' csquares.</p>';
	$html .= '<p>Flickr has records from ' . count($flickr_csquare) . ' csquares</p>';
	
	$html .= '<p>Overlap between GBIF and Flickr = ' . count(array_intersect($gbif_csquare, $flickr_csquare)) . '</p>';
	
	
	$html .= '</body>
	</html>';
	
	echo $html;
}


function main()
{
	$query = '';
	if (isset($_GET['q']))
	{
		$query = $_GET['q'];
		overlap($query);
	}
	else
	{
		display_form();
	}
}
	


main();


?>