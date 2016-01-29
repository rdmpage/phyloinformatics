<?php

// Based in part on Phoebe Zhang's Java code as currently used in maps plotted from OBIS Distributed Data Search
// see http://www.marine.csiro.au/csquares/resources.html

/**
 * @brief Convert latitude longitude pair into a c-square code
 *
 * @param lat Latitude
 * @param lon longitude
 * @param res resolution (default is 1° × 1°), code supports 10° or 1°
 *
 * @result c-square value
 */
function lat_lon_2_csquare($lat, $lon, $res = 1)
{
	$sb = '';
	
	// global quadrant
	if ($lat >= 0)
	{
		if ($lon >= 0)
		{
			$sb .= '1';
		}
		else
		{
			$sb .= '7';
		}
	}
	else
	{
		if ($lon >= 0)
		{
			$sb .= '3';
		}
		else
		{
			$sb .= '5';
		}
	}
				
	$llat = abs($lat);
	if ($llat >= 90) $llat=89.9;
	$llon = abs($lon);
	if ($llon >= 180) $llon=179.9;
	
	$i=floor($llat/10);
	$sb .= $i;
	
	$j=floor($llon/10);
	
	if($j<10)
	{
		$sb .= '0';
	}
	$sb .= $j;
	
	// If we want 10° square we're done
	if ($res == 10)
	{
		return $sb;
	}
	
	$sb .= ':';
	$llat -= $i*10;
	$llon -= $j*10;
	
	$i = floor($llat);
	$j = floor($llon);
	
	if ($i  < 5)
	{
		if ($j < 5)
		{
			$sb .= '1';
		}
		else
		{
			$sb .= '2';
		}
	}
	else
	{
		if ($j < 5)
		{
			$sb .= '3';
		}
		else
		{
			$sb .= '4';
		}
	}
	
	if ($res == 5)
	{
		echo $sb . "\n";
		exit();
	}
	
	$sb .= $i;
	$sb .= $j;
	
	// If we want 1° square we're done
	if ($res == 1)
	{
		return $sb;
	}
	
	return $sb;

}

/**
 * @brief Unpack a c-square value into corresponding bounding box
 *
 * @param sb c-square value
 * @param box 
 * @param res resolution (default is 1° × 1°), code supports 10° or 1°
 */
function unpack_csquare ($sb, &$box, $res = 1)
{
	$box->wkt = '';
	
	$global_quadrant = substr($sb, 0, 1);
	
	//echo $global_quadrant . "\n";
	
	$lat = (int)(substr($sb, 1, 1)) * 10;
	$lon = (int)(substr($sb, 2, 2)) * 10;
	
	
	if ($res == 1)
	{
		$lat += (int)(substr($sb, 6, 1));
		$lon += (int)(substr($sb, 7, 1));
	}
	
	switch ($global_quadrant)
	{
		case '1': // NE
			$box->MINX = $lon;
			$box->MAXX = $box->MINX + $res;
			$box->MINY = $lat;
			$box->MAXY = $box->MINY + $res;
			break;
			
		case '3': // SE
			$box->MINX = $lon;
			$box->MAXX = $box->MINX + $res;
			$box->MINY = 0 - $lat - $res;
			$box->MAXY = 0 - $lat;
			break;

		case '5': // SW
			$box->MINX = 0 - $lon - $res;
			$box->MAXX = $box->MINX + $res;
			$box->MINY = 0 - $lat - $res;
			$box->MAXY = 0 - $lat;
			break;

		case '7': // NW
			$box->MINX = 0 - $lon - $res;
			$box->MAXX = $box->MINX + $res;
			$box->MINY = $lat;
			$box->MAXY = $box->MINY + $res;
			break;

		default:
			break;
	}

	$box->wkt = 'POLYGON((' . $box->MINY . ' ' . $box->MAXX . ',' 
		. $box->MINY . ' ' . $box->MINX . ',' 
		. $box->MAXY . ' ' . $box->MINX . ',' 
		. $box->MAXY . ' ' . $box->MAXX . ','
		. $box->MINY . ' ' . $box->MAXX . '))';
	
}


if (0)
{
// how to display in Google?
// bounds of enclosing 10 degree square


$lat = 42.85;
$lon = 147.28;
//$lat = -51;
//$lon = 71;


$sb = lat_lon_2_csquare($lat, $lon);

echo "$sb\n";

$box = new stdclass;

unpack_csquare($sb, $box);

echo "\n";

print_r($box);

}


?>
