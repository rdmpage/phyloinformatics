<?php

/**
 * @file latlong.php
 *
 */


//--------------------------------------------------------------------------------------------------
/**
 * @brief Convert degrees, minites, seconds to a decimal value
 *
 * @param degrees Degrees
 * @param minutes Minutes
 * @param seconds Seconds
 * @param hemisphere Hemisphre (optional)
 *
 * @result Decimal coordinates
 *
 */
function degrees2decimal($degrees, $minutes=0, $seconds=0, $hemisphere='N')
{
	$result = $degrees;
	$result += $minutes/60.0;
	$result += $seconds/3600.0;
	
	if ($hemisphere == 'S')
	{
		$result *= -1.0;
	}
	if ($hemisphere == 'W')
	{
		$result *= -1.0;
	}
	return $result;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Extract latitude from a text string
 *
 * @param str Text string
 * @para latitude Decimal latitude
 *
 * @result True if successfully parsed
 *
 */
function IsLatitude($str, &$latitude)
{
	$result = false;
	$str = trim($str);
	$str = str_replace("−", "-", $str);
	
	//echo "|$str|<br/>";
	
	if (is_numeric($str))
	{
		$latitude = (double)$str;
		//echo "x";
		return true;
	}

	
	
	if (!$result)
	{	
		// 30° 1'39.53"S
		if (preg_match('/(?<degrees>\d+)°\s*(?<minutes>\d+)\'(?<seconds>\d+(\.\d+)?)"(?<hemisphere>[S|N])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	

	if (!$result)
	{	
		// 23°03'44''N
		if (preg_match('/(?<degrees>\d+)°((?<minutes>\d+)\')?((?<seconds>\d+)\'\')?(?<hemisphere>[S|N])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	if (!$result)
	{	
		// N10° 54.448'
		if (preg_match('/(?<hemisphere>[S|N])(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?)\')?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	if (!$result)
	{	
		// 35° 56.218′ N
		if (preg_match('/(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?))?′\s*(?<hemisphere>[S|N])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	if (!$result)
	{	
		// // S 01°06.18
		if (preg_match('/(?<hemisphere>[S|N])\s+(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?))?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	
	
/*	if (!$result)
	{
	
		// 5° 67'
		if (preg_match('/(?<hemisphere>-)?(?<degrees>\d+)°(\s*(?<minutes>\d+)\')?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			if ($matches['hemisphere'] == '-')
			{
				$matches['hemisphere'] = 'S';
			}
			else
			{
				$matches['hemisphere'] = 'N';
			}
			
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
*/	
	return $result;
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Extract longitude from a text string
 *
 * @param str Text string
 * @para latitude Decimal longitude
 *
 * @result True if successfully parsed
 *
 */
function IsLongitude($str, &$longitude)
{
	$result = false;
	
	$str = trim($str);
	$str = str_replace("−", "-", $str);
	
	if (is_numeric($str))
	{
		$longitude = (double)$str;
		return true;
	}
	
	if (!$result)
	{	
		// 30° 1'39.53"S
		if (preg_match('/(?<degrees>\d+)°\s*(?<minutes>\d+)\'(?<seconds>\d+(\.\d+)?)"(?<hemisphere>[W|E])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	
	if (!$result)
	{	
		// 23°03'44''N
		if (preg_match('/(?<degrees>\d+)°((?<minutes>\d+)\')?((?<seconds>\d+)\'\')?(?<hemisphere>[W|E])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	if (!$result)
	{
		if (preg_match('/(?<hemisphere>[W|E])(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?)\')?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
		
	if (!$result)
	{	
		// // W 77°35.67
		if (preg_match('/(?<hemisphere>[W|E])\s+(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?))?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}


	
	if (!$result)
	{	
		// 117° 54.343′ W
		if (preg_match('/(?<degrees>\d+)°(\s*(?<minutes>\d+(.\d+)?))?′\s*(?<hemisphere>[W|E])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}
	
	
/*	if (!$result)
	{	
		// 5° 67'
		if (preg_match('/(?<hemisphere>-)?(?<degrees>\d+)°(\s*(?<minutes>\d+)\')?/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
	
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degrees'];
			if (isset($matches['minutes']))
			{
				$minutes = $matches['minutes'];
			}
			if (isset($matches['seconds']))
			{
				$seconds = $matches['seconds'];
			}
			if ($matches['hemisphere'] == '-')
			{
				$matches['hemisphere'] = 'W';
			}
			else
			{
				$matches['hemisphere'] = 'E';
			}
			
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphere']);
		}
	}	
*/

	return $result;
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief Extract latitude and longitude from a text string
 *
 * @param str Text string
 * @para latlong Latitude and longitude (as members of an array)
 *
 * @result True if successfully parsed
 *
 */
function IsLatLong($str, &$latlong)
{
	$result = false;
	$matches = array();
		
	// remove prime (backtick)
	$str = str_replace("′", "'", $str);
		
	$str = trim($str, '(');
	$str = rtrim($str, ')');
	
	//echo $str . '<br/>';
		
	// 115.59E/37.64N
	if (!$result)
	{
		if (preg_match('/(?<longitude>\d+(.\d+)?)(?<longitudeHemisphere>[W|E])\/(?<latitude>\d+(.\d+)?)(?<latitudeHemisphere>[S|N])/', $str, $matches)) //115.59E/37.64N
		{
			//print_r($matches);
			
			$longitude = $matches['longitude'];
			if ($matches['longitudeHemisphere'] == 'W')
			{
				$longitude *= -1.0;
			}
			$latlong['longitude'] = $longitude;
			
			$latitude = $matches['latitude'];
			if ($matches['latitudeHemisphere'] == 'S')
			{
				$latitude *= -1.0;
			}
			$latlong['latitude'] = $latitude;
	
	
			$result = true;
			
		}
	}
	
	if (!$result)
	{
		// S 9°3'; W 72°44'
		if (preg_match('/(?<latitudeHemisphere>[S|N])\s*(?<latitude>\d+)°((?<latitudeMinutes>\d+)\')?((?<latitudeSeconds>\d+)")?[;|,]\s*(?<longitudeHemisphere>[W|E])\s*(?<longitude>\d+)°((?<longitudeMinutes>\d+)\')?((?<longitudeSeconds>\d+)")?/', $str, $matches)) 
		{
			//print_r($matches);
			
			// longitude
			$longitude = $matches['longitude'];
			if (isset($matches['longitudeMinutes']))
			{
				$longitude += $matches['longitudeMinutes']/60.0;
			}
			if (isset($matches['longitudeSeconds']))
			{
				$longitude += $matches['longitudeSeconds']/60.0;
			}
			if ($matches['longitudeHemisphere'] == 'W')
			{
				$longitude *= -1.0;
			}
			$latlong['longitude'] = $longitude;
			
			// latitude
			$latitude = $matches['latitude'];
			
			if (isset($matches['latitudeMinutes']))
			{
				$latitude += $matches['latitudeMinutes']/60.0;
			}
			if (isset($matches['latitudeSeconds']))
			{
				$latitude += $matches['latitudeSeconds']/3600.0;
			}
			
			
			if ($matches['latitudeHemisphere'] == 'S')
			{
				$latitude *= -1.0;
			}
			$latlong['latitude'] = $latitude;
	
	
			$result = true;
			
		}
	}
	
	//36°57'N, 10°37'E
	
	if (!$result)
	{
		if (preg_match('/(?<degreesLatitude>\d+)°(\s*(?<minutesLatitude>\d+(.\d+)?)\')?((?<secondsLatitude>\d+(.\d+)?)(\'\'|"))?\s*(?<hemisphereLatitude>[S|N])(;|,|–|\/)?\s*(?<degreesLongitude>\d+)°(\s*(?<minutesLongitude>\d+(.\d+)?)\')?((?<secondsLongitude>\d+(.\d+)?)(\'\'|"))?\s*(?<hemisphereLongitude>[W|E])/', $str, $matches)) 
//		if (preg_match('/(?<degreesLatitude>\d+)°((?<minutesLatitude>\d+(.\d+)?)\')((?<secondsLatitude>\d+)\'\')?(.*)/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
			
			// latitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLatitude'];
			if (isset($matches['minutesLatitude']))
			{
				$minutes = $matches['minutesLatitude'];
			}
			if (isset($matches['secondsLatitude']))
			{
				$seconds = $matches['secondsLatitude'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLatitude']);
			$latlong['latitude'] = $latitude;

			// longitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLongitude'];
			if (isset($matches['minutesLongitude']))
			{
				$minutes = $matches['minutesLongitude'];
			}
			if (isset($matches['secondsLongitude']))
			{
				$seconds = $matches['secondsLongitude'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLongitude']);
			$latlong['longitude'] = $longitude;

		}
	}
	
	// 39:49:35N; 3:08:50E
	if (!$result)
	{
		if (preg_match('/(?<degreesLatitude>\d+)(:(?<minutesLatitude>\d+))?(:(?<secondsLatitude>\d+))?(?<hemisphereLatitude>[S|N])(;|,)?\s*(?<degreesLongitude>\d+)(:(?<minutesLongitude>\d+))?(:(?<secondsLongitude>\d+))?(?<hemisphereLongitude>[W|E])/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
			
			// latitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLatitude'];
			if (isset($matches['minutesLatitude']))
			{
				$minutes = $matches['minutesLatitude'];
			}
			if (isset($matches['secondsLatitude']))
			{
				$seconds = $matches['secondsLatitude'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLatitude']);
			$latlong['latitude'] = $latitude;

			// longitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLongitude'];
			if (isset($matches['minutesLongitude']))
			{
				$minutes = $matches['minutesLongitude'];
			}
			if (isset($matches['secondsLongitude']))
			{
				$seconds = $matches['secondsLongitude'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLongitude']);
			$latlong['longitude'] = $longitude;

		}
	}
	
	// S 4.45' W 73.57'
	if (!$result)
	{
		if (preg_match('/(?<hemisphereLatitude>[S|N]) (?<degreesLatitude>\d+(.\d+)?)\' (?<hemisphereLongitude>[W|E]) (?<degreesLongitude>\d+(.\d+)?)\'/', $str, $matches)) 
		{
			$result = true;
			
			//print_r($matches);
			
			// latitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLatitude'];
			if (isset($matches['minutesLatitude']))
			{
				$minutes = $matches['minutesLatitude'];
			}
			if (isset($matches['secondsLatitude']))
			{
				$seconds = $matches['secondsLatitude'];
			}
			$latitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLatitude']);
			$latlong['latitude'] = $latitude;

			// longitude
			$seconds = 0;
			$minutes = 0;
			$degrees = $matches['degreesLongitude'];
			if (isset($matches['minutesLongitude']))
			{
				$minutes = $matches['minutesLongitude'];
			}
			if (isset($matches['secondsLongitude']))
			{
				$seconds = $matches['secondsLongitude'];
			}
			$longitude = degrees2decimal($degrees, $minutes, $seconds, $matches['hemisphereLongitude']);
			$latlong['longitude'] = $longitude;

		}
	}
	
	
	return $result;
}


//--------------------------------------------------------------------------------------------------
if (0)
{
	// tests
	
	$tests =  array();
	$failed = array();
/*	array_push($tests, '115.59E/37.64N');
	array_push($tests, 'S 9°3\'; W 72°44\'');
	array_push($tests, 'N 0°6\'41"; W 77°22\'28"');
	array_push($tests, '4°30\'S, 10°54\'E');
	array_push($tests,'39:49:35N; 3:08:50E');
	array_push($tests, 'N10° 54.448\'');
	array_push($tests, 'S 4.45\' W 73.57\'');
	array_push($tests, '4°54.87′S, 29°35.85′E');
//	array_push($tests, str_replace("′","'", '4°54.87′S, 29°35.85′E'));
	
//	array_push($tests, '4°54.87\'S, 29°35.85\'E');

	array_push($tests, '5° 67\'');
	array_push($tests, '14°39′ S, 145°27′ E');
	
	array_push($tests, '054°03′N–112°46′W');
	array_push($tests, "38° 02' N, 32° 51' E");
	
	array_push($tests, '42°20′ N/9°10′ E');*/
	
//	array_push($tests, 'N 0°6\'41"; W 77°22\'28"');
	
	//array_push($tests,'35° 56.218′ N');
	//array_push($tests,'117° 54.343′ W');
	
	//array_push($tests, 'S 01°06.18');
	//array_push($tests, 'W 77°35.67');
	
	array_push($tests, '29°55\'21.62"N');
	
	
	$ok = 0;
	
	
	foreach ($tests as $str)
	{
		$latlong = array();
		if (IsLatitude($str, $latlong))
		
//		if (IsLongitude($str, $latlong))
//		if (IsLatLong($str, $latlong))
		{
			print_r($latlong);	
			
			$ok++;
			
		}
		else
		{
			array_push($failed, $str);
		}
	}
	
	// report
	
	echo "--------------------------\n";
	echo count($tests) . ' strings, ' . (count($tests) - $ok) . ' failed' . "\n";
	print_r($failed);
}

?>