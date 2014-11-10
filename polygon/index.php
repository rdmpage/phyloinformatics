<?php

require_once(dirname(dirname(__FILE__)) . '/lib.php');

require_once(dirname(__FILE__) . '/convex.php');
require_once(dirname(__FILE__) . '/concave.php');

//--------------------------------------------------------------------------------------------------
// get points from GBIF
function get_gbif_data($name = 'Hapalemur')
{
	
	$url = 'http://api.gbif.org/v1/occurrence/search';
	
	$parameters = array(
		'scientificName' => $name,
		'limit' => 300,
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
				$result->decimalLongitude,
				$result->decimalLatitude
				);
			
			$points[] = $point;
		}
	}
	
	$uniques = cleanList($points);
	
	return $uniques;
}

//--------------------------------------------------------------------------------------------------
// build poygon for points
function make_polygon($points)
{
	$polygon = array();
	
	$geojson = new stdclass;
	$geojson->type = 'FeatureCollection';
	$geojson->features = array();

	$num_points = count($points);
	
	switch ($num_points)
	{
		case 1:
			$feature = new stdclass;
			$feature->type = 'Feature';
			
			$feature->properties = new stdclass;
			$feature->properties->name = "One point";
		
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Point';
			$feature->geometry->coordinates = $points[0];
			
			$geojson->features[] = $feature;
			break;
			
		case 2:
			break;
		
		default:
			$feature = new stdclass;
			$feature->type = 'Feature';
			
			$feature->properties = new stdclass;
			$feature->properties->name = "Points";
		
			
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'MultiPoint';
			$feature->geometry->coordinates = $points;
			
			$geojson->features[] = $feature;
		
			if (1)
			{
				$polygon = convex_hull($points);
			}
			else
			{
				$polygon = concavehull($points,6);
			}
						
			$polygon[] = $polygon[0];
			
			$feature = new stdclass;
			$feature->type = 'Feature';
			
			$feature->properties = new stdclass;
			$feature->properties->name = "Polygon";			
			
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Polygon';
			$feature->geometry->coordinates = array();
			$feature->geometry->coordinates[] = $polygon ;
			$geojson->features[] = $feature;
			break;
	
	}
	
	return $geojson;
}

//--------------------------------------------------------------------------------------------------
function draw_map($name)
{
	$data = get_gbif_data($name);
	
	//print_r($data);exit();
	
	$points = get_points($data);
	
	//print_r($points);
	
	if (count($points) == 0)
	{
		echo "No data for <b>$name</b><br /><a href=\".\">Go back</a>";
		exit();
	}
	
	$geojson = make_polygon($points);
	
	$min_long = 180;
	$max_long = -180;
	$min_lat = 90;
	$max_lat = -90;
	
	foreach ($points as $pt)
	{
		$min_long = min($min_long, $pt[0]);
		$max_long = max($max_long, $pt[0]);
		$min_lat = min($min_lat, $pt[1]);
		$max_lat = max($max_lat, $pt[1]);
	}
	
	
	$polygon = array();
	
	foreach ($geojson->features as $feature)
	{
		if ($feature->properties->name == 'Polygon')
		{
			$polygon = $feature->geometry->coordinates[0];
		}
	}
	
	$num_cells = 0;
	$num_cells_occupied = 0;
	
	$size = 1;
	for ($long = $min_long; $long < $max_long; $long += $size)
	{
		for ($lat = $min_lat; $lat < $max_lat; $lat += $size)
		{
	
			$feature = new stdclass;
			$feature->type = 'Feature';
			
			$feature->properties = new stdclass;
			$feature->properties->name = "Polygon";			
			
			$feature->geometry = new stdclass;
			$feature->geometry->type = 'Polygon';
			
			$feature->geometry->coordinates = array();
			
			$box = array();
			
			$x1 = min($long, $long + $size);
			$x2 = max($long, $long + $size);
			$y1 = min($lat, $lat + $size);
			$y2 = max($lat, $lat + $size);
			
			
			
			$box[] = array($x1, $y2);
			$box[] = array($x2, $y2);
			$box[] = array($x2, $y1);
			$box[] = array($x1, $y1);
			$box[] = array($x1, $y2);
			
			
			// note edge case of polygon with spike where none of corners of square are in polygon 
			// (e.g, Mehelya nyassae)
			$in_polygon = 0;
			$i = 0;
			while ($i < 4)
			{
				if (pointInPolyonQ($box[$i], $polygon))
				{
					$in_polygon++;
				}
				$i++;
			}
			
			if ($in_polygon > 0)
			{
				$num_cells++;
			}
			
			
				{
					$in_cell = 0;
					foreach ($points as $pt)
					{
						if (pointInPolyonQ($pt, $box))
						{	
							$in_cell++;
						}
					}
					if ($in_cell == 0)
					{
						$in_polygon = 0;
					}
					else
					{
						$num_cells_occupied++;
					}
				
				}			
			
			
			
			if ($in_polygon > 0)
			{
				$feature->geometry->coordinates[] = $box;
				$geojson->features[] = $feature;
			}
		}
	}
	
	$template = <<<EOT
	<!DOCTYPE html>
	<html>
	  <head>
		<title><NAME></title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
		<meta charset="utf-8">
		<style>
		body { font-family: sans-serif; }
		 #map-canvas {
			height: 500px;
			margin: 0px;
			padding: 20px
		  }
		</style>
		<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
		<script>
	var map;
	function initialize() {
	  // Create a simple map.
	  map = new google.maps.Map(document.getElementById('map-canvas'), {
		zoom: 4,
		center: {lat: <LATITUDE>, lng: <LONGITUDE>}
	  });
	
	  // Load a GeoJSON 
	  
	  var j = <GEOJSON>;
	  
	  map.data.addGeoJson(j);
	}
	
	google.maps.event.addDomListener(window, 'load', initialize);
	
		</script>
	  </head>
	  <body>
		<h1><NAME></h1>
		<div id="map-canvas"></div>
		<p>Number of cells: <b><NUMCELLS></b>, number occupied: <b><NUMCELLSOCCUPIED></b></p>
	  </body>
	</html>
EOT;
	
	$template = str_replace('<NAME>', $name, $template);
	$template = str_replace('<GEOJSON>', json_encode($geojson), $template);
	$template = str_replace('<LATITUDE>', $min_lat + ($max_lat - $min_lat)/2, $template);
	$template = str_replace('<LONGITUDE>', $min_long + ($max_long - $min_long)/2, $template);
	$template = str_replace('<NUMCELLS>', $num_cells, $template);
	$template = str_replace('<NUMCELLSOCCUPIED>', $num_cells_occupied, $template);
	
	echo $template;

}

$name = '';

if (isset($_GET['name']))
{
	$name = $_GET['name'];
	
	draw_map($name);
}
else
{
$template = <<<EOT
	<!DOCTYPE html>
	<html>
	  <head>
		<title>Map maker</title>
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
		<meta charset="utf-8">
		<style>
		body { font-family: sans-serif; }
		</style>
	  </head>
	  <body>
	     <h1>Make map for a species using GBIF data</h1>
	     <form action=".">
	     	<input name="name" id="name" type="text" size="40">
	     	<input name="Go" type="submit">
	     </form>
	  </body>
	</html>
EOT;

echo $template;

}

	

?>