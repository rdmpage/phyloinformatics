<?php

//--------------------------------------------------------------------------------------------------
/**
 * @brief Extract components of a graph represented by an adjacency matrix
 *
 * @param X 2D array representation of adjacency matrix
 *
 * @return List of components (an array of arrays)
 *
 */
function get_components($X)
{
	$i = 0;
	$comp = 0;
	$nodes = count($X);
	
	$L = array();
	for ($j=1; $j < $nodes; $j++)
	{
		$L[$j] = -1;
	}
	
	// 1
	do
	{
		$L[$i] = $i;
		//print_r($L);
		do
		{
			$j = $i + 1;
			$change = false;
			do
			{
				if (($X[$i][$j] == 1) && ($L[$j] == -1))
				{
					//echo "i=$i, j=$j\n";
				
					
					$L[$j] = $i;
					for ($k = 0; $k < $nodes; $k++)
					{
						if ($L[$k] == -1)
						{
							$X[$i][$k] = max($X[$i][$k], $X[$j][$k]);
							$X[$k][$i] = $X[$i][$k];
							$change = true;
						}
					}
				}
				$j++;
			} while ($j < $nodes);
		} while ($change === true);
		
		//echo "change i=$i j=$j\n";
		//print_r($L);
		//exit();
		$comp++;
		
		// Find row k which hasn't been deleted
		$k = $i;
		do 
		{
			$k++;
		} while ( ($k < $nodes) && ($L[$k] != -1)); 
		
		if ($k < $nodes)
		{
			if ($L[$k] == -1)
			{
				$i = $k;
			}
		}
		//echo "i=$i k=$k\n";
	} while ($i == $k);
	
	//echo "components=$comp\n";
	
	//print_r($L);
	
	// list components
	
	$c = array();
	if ($comp == 1)
	{
		$c[0] = array();
		// Graph is connected
		for ($i=0;$i<$nodes;$i++)
		{
			array_push($c[0], $i);
		}
	}
	else
	{
		for ($k = 0; $k < $nodes; $k++)
		{
			if ($L[$k] == $k)
			{
				$c[$k] = array();
				for ($j = $k; $j < $nodes; $j++)
				{
					if ($L[$j] == $k)
					{
						array_push($c[$k], $j);
					}
				}
			}
		}
	}
	
	//print_r($c);
	
	return $c;
}

?>
