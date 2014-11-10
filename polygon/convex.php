<?php

require_once(dirname(__FILE__) . '/spatial.php');

//--------------------------------------------------------------------------------------------------
function cross_product($p1, $p2, $p3)
{
	return ($p2[0] - $p1[0]) * ($p3[1] - $p1[1]) - ($p3[0] - $p1[0]) * ($p2[1] - $p1[1]);
}

//--------------------------------------------------------------------------------------------------
// From http://en.wikipedia.org/wiki/Graham_scan
function convex_hull($points)
{
	// Find pivot point (has lowest y-value)
	$minX = 180.0;
	$minY = 90;
	$pivot = 0;

	$n = count($points);

	for ($i=0;  $i < $n; $i++)
	{	
		if ($points[$i][1] <= $minY)
		{
			if ($points[$i][1] < $minY)
			{
				$pivot = $i;
				$minY = $points[$i][1];
				$minX = $points[$i][0];
			}
			else
			{
				if ($points[$i][0] < $minX)
				{
					$pivot = $i;
					$minX = $points[$i][0];
				}
			}
		}
	}

	$angle = array();
	$distance = array();

	// Compute tangents
	for ($i=0;  $i < $n; $i++)
	{	
		if ($i != $pivot)
		{
			$o = $points[$i][1] - $points[$pivot][1];
			$a = $points[$i][0] - $points[$pivot][0];		
			$h = sqrt($a*$a + $o*$o); 
		
			array_push($angle, rad2deg(atan2($o, $a)));
			array_push($distance, $h);
		}
		else
		{
			array_push($angle, 0.0);
			array_push($distance, 0.0);
		}
	}

	// Sort array of points by angle, then distance
	array_multisort($angle, SORT_ASC, $distance, SORT_DESC, $points);

	// Fnd hull
	$stack = array();
	array_push($stack, $points[0]);
	array_push($stack, $points[1]);

	for ($i = 2; $i < $n; $i++)
	{
		$stack_count = count($stack);
		$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		while ($cp <= 0 && $stack_count >= 2)
		{
			array_pop($stack);
			$stack_count = count($stack);
			$cp = cross_product($stack[$stack_count-2], $stack[$stack_count-1], $points[$i]);
		}
		array_push($stack, $points[$i]);
	}

	return $stack;
}

?>