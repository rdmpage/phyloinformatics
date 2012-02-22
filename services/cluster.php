<?php

require_once(dirname(__FILE__) . '/lcs.php');
require_once(dirname(__FILE__) . '/components.php');
require_once(dirname(__FILE__) . '/fingerprint.php');

function cluster($strings)
{
	$result = new stdclass;
	
	$n = count($strings);
	
	// clean
	
	for ($i = 0; $i < $n; $i++)
	{
		$strings[$i] = finger_print(trim($strings[$i]));
	}
	
	$map = array();
	$inv_map = array();
	$count = 0;
	foreach ($strings as $k => $v)
	{
		$map[$k] = $count;
		$inv_map[$count] = $k;
		
		$count++;
	}

	// Create adjacency matrix and fill with 0's
	$X = array();
	for ($i = 0; $i < $n; $i++)
	{
		$X[$i] = array();
		
		for ($j = 0; $j < $n; $j++)
		{ 
			$X[$i][$j] = 0;
		}
	}
	
	$nodes = '';
	$edges = '';
	
	// Compare names using approximate string matching
	$i = 0;
	foreach ($strings as $k1 => $v1)
	{
		$nodes .= "node $i [label=\"" . $v1 . "\"];\n";
		
		if ($i < $n-1)
		{
			$j = 0;
			foreach ($strings as $k2 => $v2)
			{
				if (($j > $i) && ($j < $n))
				{
					// Find longest common subsequence for this pair of cleaned names
					$lcs = new LongestCommonSequence($v1, $v2);	
					$d = $lcs->score();

					// Filter by longest common substring (to ensure we have a "meaningful" 
					// match), that is, so that we avoid subsequences that have little continuity					
					$str = '';
					$lcstr = LongestCommonSubstring($v1, $v2, $str);
					if ($lcstr >= 4)
					{
						// Ignore matches just on date, we want more than that
						if (is_numeric(trim($str)))
						{
						}
						else
						{
							// If longest common subsequence is > 70% of the length of both strings
							// we accept it.
							if (($d / strlen($v1) >= 0.7) || ($d / strlen($v2) >= 0.7))
							{
								$X[$map[$k1]][$map[$k2]] = 1;
								$X[$map[$k2]][$map[$k1]] = 1;	
								$edges .=  $i . " -- " . $j . " [label=\"" . $lcstr . "\"];\n";
						
							}
						}
					}
					else
					{
						// If just a short match is it the start if the string (e.g., an abbreviation)
						$abbreviation = false;
						if (strlen($v1) == $d)
						{
							if (strpos($v2, $v1, 0) === false)
							{
							}
							else
							{
								$abbreviation = true;
							}
						}
						else
						{
							if (strpos($v1, $v2, 0) === false)
							{
							}
							else
							{
								$abbreviation = true;
							}						
						}
						// Accept abbreviation
						if ($abbreviation)
						{
							$X[$map[$k1]][$map[$k2]] = 1;
							$X[$map[$k2]][$map[$k1]] = 1;	
							$edges .=  $i . " -- " . $j . " [label=\"" . $lcstr . "\"];\n";						
						}
					}
				}
				$j++;
			}
			$i++;
		}
	}
	
	$result->graph = "graph {\n" . $nodes . $edges . "}\n";
	
	//echo $graph;
	
	// Get components of adjacency matrix
	$components = get_components($X);
	
	$result->clusters = array();
	
	foreach ($components as $component)
	{
		$cluster = array();
		
		foreach ($component as $k => $v)
		{
			$member = new stdclass;
			$member->id = $inv_map[$v];
			$member->string = $strings[$inv_map[$v]];
			
			$cluster[] = $member;
		}
		$result->clusters[] = $cluster;
	}
	
	//print_r($map);
	
	
	if (0)
	{
		print_r($c);
	}
	
	return $result;
}


/*


$strings = array(
1008426 => finger_print('Miranda Ribeiro 1926'),
3372490 => finger_print('Mir. Ribeiro 1926'),
760437  => finger_print('Miranda-Ribeiro 1926')
);



$strings = array(
1009410 => finger_print('Ferrusac 1821'),
2463448 => finger_print('Bonavita 1965'),
2843674  => finger_print('Ferussa 1821'),
3050161  => finger_print('Fer.'),
4273943  => finger_print('Lamarck 1812'),
913824  => finger_print('Ferussac 1821')
);


$strings = array(
1 => 'Schaffer 1756',
2 => 'Scopoli',
3 => 'Scopoli 1777',
4 => 'Schaeffer 1756',
5 => 'Schoch 1868'
);

$r = cluster($strings);

print_r($r);

*/

?>