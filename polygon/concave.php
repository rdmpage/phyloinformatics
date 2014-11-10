<?php


// NEED TO CLEAN UP, AND THINK SOME MORE
// POSSIBLE APPOROACH IS TO COMPUTE PAIRWISE DISTANCES, USE THRESHOLD, JOIN IF < THRESHOLD
// THEN GET COMPONENTS AND OUT CONCAX HULLS AROUND THEM, RESUTL IS DISTRIBUTION

require_once(dirname(__FILE__) . '/spatial.php');


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
function findMinYPoint($points)
{
	$minY = 1000;
	
	$min_pt = null;
	
	foreach ($points as $pt)
	{
		if ($pt[1] < $minY)
		{
			$minY = $pt[1];
			$min_pt  = $pt;
		}
	}
	
	return $min_pt;
}

//--------------------------------------------------------------------------------------------------
function removePoint($points, $pt)
{
	$hit = -1;

	foreach ($points as $key => $value)
	{
		if ($value == $pt)
		{
			$hit = $key;
		}
	}
		
	if ($hit != -1)
	{
		//unset($points[$hit]);
		array_splice($points, $hit, 1);
	}
	
	
	return $points;
}

//--------------------------------------------------------------------------------------------------
function nearestPoints($points, $current_point, $k)
{
	$k = min($k, count($points));
	
	$nearest = array();
	
	$distance = array();
	
	foreach ($points as $pt)
	{
		$o = $pt[1] - $current_point[1];
		$a = $pt[0] - $current_point[0];		
		$h = sqrt($a*$a + $o*$o); 
	
		array_push($distance, $h);
	}
	
	// Sort array of points by distance
	array_multisort($distance, SORT_ASC, $points);
	
	$nearest = array_splice($points, 0, $k);
	
	//print_r($nearest);
	
	return $nearest;
}

//--------------------------------------------------------------------------------------------------
function get_angle($pt0, $pt1)
{
	$o = $pt1[1] - $pt0[1];
	$a = $pt1[0] - $pt0[0];		
	$h = sqrt($a*$a + $o*$o); 
	
	$angle = rad2deg(atan2($o, $a));
	
	return $angle;
}

//--------------------------------------------------------------------------------------------------
function sortByAngle($points, $current_point, $previous_angle = 0)
{
	$angles = array();
	
	//echo "Sorted by angles\n";
	
	foreach ($points as $pt)
	{
		$angle = get_angle($current_point, $pt);

		$angle -= $previous_angle;
		
		if ($angle < 0)
		{
			$angle += 360;
		}
		
		array_push($angles, $angle);
		
		//echo '[' . $current_point[0] . ',' . $current_point[1] . ']-[' . $pt[0] . ',' . $pt[1] . '] ' . $angle . "\n";
		
	}
	
	// Sort array of points by angle
	array_multisort($angles, SORT_DESC, $points);
	
	return $points;
}

//--------------------------------------------------------------------------------------------------
function intersectsQ($pt1, $pt2, $pt3, $pt4)
{
/*
	echo "intersectsQ\n";
	
	
	print_r($pt1);
	print_r($pt2);
	print_r($pt3);
	print_r($pt4);*/
	
	$line1 = new Line($pt1[0], $pt1[1], $pt2[0], $pt2[1]);
	$line2 = new Line($pt3[0], $pt3[1], $pt4[0], $pt4[1]);
	
	$intersects = doLinesIntersect($line1, $line2);
	
	/*
	if ($intersects)
	{
		echo "*** lines intersect *** \n";
	}
	*/
	
	return $intersects;
}

//--------------------------------------------------------------------------------------------------
// based on http://stackoverflow.com/a/15045467/9684
function pointInPolyonQ($point, $polygon)
{
	$result = false;
	
	// Check if the point is inside the polygon or on the boundary
	$intersections = 0; 
	$vertices_count = count($polygon);
	for ($i=1; $i < $vertices_count; $i++) 
	{
		$vertex1 = $polygon[$i-1]; 
		$vertex2 = $polygon[$i];
		if ($vertex1[1] == $vertex2[1] and $vertex1[1] == $point[1] and $point[0] > min($vertex1[0], $vertex2[0]) and $point[0] < max($vertex1[0], $vertex2[0])) 
		{ 
			// This point is on an horizontal polygon boundary
			$result = TRUE;
			// set $i = $vertices_count so that loop exits as we have a boundary point
			$i = $vertices_count;
		}
		if ($point[1] > min($vertex1[1], $vertex2[1]) and $point[1] <= max($vertex1[1], $vertex2[1]) and $point[0] <= max($vertex1[0], $vertex2[0]) and $vertex1[1] != $vertex2[1]) 
		{ 
			$xinters = ($point[1] - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) / ($vertex2[1] - $vertex1[1]) + $vertex1[0]; 
			if ($xinters == $point[0]) 
			{ // This point is on the polygon boundary (other than horizontal)
				$result = TRUE;
				// set $i = $vertices_count so that loop exits as we have a boundary point
				$i = $vertices_count;
			}
			if ($vertex1[0] == $vertex2[0] || $point[0] <= $xinters) 
			{
				$intersections++; 
			}
		} 
	}
	// If the number of edges we passed through is even, then it's in the polygon. 
	// Have to check here also to make sure that we haven't already determined that a point is on a boundary line
	if ($intersections % 2 != 0 && $result == FALSE) 
	{
		$result = TRUE;
	} 
	
	return $result;
}


//--------------------------------------------------------------------------------------------------
function concavehull($points, $k = 3)
{
	$kk = max($k, 3);
	
	// remove duplicate points
	$dataset = cleanList($points);
	
	//print_r($dataset);
	
	// a minimum of 3 dissimilar points is required
	if (count($points) < 3)
	{
		return null;
	}
	
	// for a 3 points dataset, the polygon is the dataset itself
	if (count($points) == 3)
	{
		return $dataset;
	}
	
	// make sure that k neighbours can be found
	$kk = min($kk, count($dataset));
	
	$first_point = findMinYPoint($dataset);
	
	//echo "first_point\n";
	//print_r($first_point);
	
	$hull[] = $first_point;
	
	$current_point = $first_point;
	
	$dataset = removePoint($dataset, $first_point);
	
	//print_r($dataset);
	//exit();
	
	$previous_angle = 0;
	$step = 2;
	
	while ((($current_point != $first_point) || ($step == 2)) && (count($dataset) > 0))
	{
		/*
		echo "--------------------\n";
		echo "current_point\n";
		print_r($current_point);
		*/
	
		//echo "Step=$step" . ' ' . count($dataset) . ' ' . " hull size=" . count($hull) . "\n";
		if ($step == 4)
		{
			// add the firstPoint again
			$dataset[] = $first_point;
		}
		
		// find nearest neighbours
		$k_nearest_points = nearestPoints($dataset, $current_point, $kk);
		$c_points = sortByAngle($k_nearest_points, $current_point, $previous_angle);
		
		/*
		echo "Nearest points\n";
		print_r($k_nearest_points);
		echo "Sorted by angle\n";
		print_r($c_points);
		*/
		
		$its = true;
		$i = -1;
		while ($its && ($i < count($c_points)-1))
		{
			$i++;
			if ($c_points[$i] == $first_point)
			{
				$last_point = 1;
			}
			else
			{
				$last_point = 0;
			}
			
			$j = 2;
			$its = false;
			$n = count($hull);
			while (!$its && ($j < ($n - $lastPoint - 1)))
			{
				//echo "j=$j [" . ($n-1-$j) . "," . ($n-$j) . "]\n";			
								
				$its = intersectsQ($hull[$n-1], $c_points[$i], $hull[$n-1-$j], $hull[$n-$j]);
				$j++;
			}
		}
		
		// to do:
		
		if ($its)
		{
			// since all candidates intersect at least one edge, try again with a higher number of neighbours
			return concavehull($points, $kk + 1);
		}
				
		// a valid candidate was found
		$current_point = $c_points[$i];
		$hull[] = $current_point;
		
		//file_put_contents('step' . $step . '.svg', polygon2svg($hull, $points));
		
		$n = count($hull);
		$previous_angle = get_angle($hull[$n-1], $hull[$n-2]);
		
		//echo "previous_angle=$previous_angle\n";
		
		$dataset = removePoint($dataset, $current_point);
		$step++;
		
		//echo "Current point\n";
		//print_r($current_point);
		
		//print_r($dataset);
		//echo "------\n";
		//print_r($hull);
		
		//if (count($hull) == 5) { exit(); }
	}
	
	// check if all the given points are inside the computed polygon
	$all_inside = true;
	$i = count($dataset) - 1;
	while ($all_inside && ($i >= 0))
	{
		$all_inside = pointInPolyonQ($dataset[$i], $hull);
		
		/*
		echo '[' . $dataset[$i][0] . ',' . $dataset[$i][1] . '] ';
		if (pointInPolyonQ($dataset[$i], $hull))
		{
			echo 'in';
		}
		echo "\n";
		*/
		$i--;
	}
	
	if (!$all_inside)
	{
		// since at least one point is out of the computed polygon,
		// try again with a higher number of neighbours
		return concavehull($points, $kk + 1);	
	}


	return $hull;
	
	
	
	
	
}



?>