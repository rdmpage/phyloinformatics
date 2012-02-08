<?php

/**
 * @file lcs.php
 *
 * Longest common subsequence
 *
 */

//--------------------------------------------------------------------------------------------------
// from http://en.wikipedia.org/wiki/Longest_common_subsequence_problem

function LCSLength($X, $Y)
{
	$C = array();

	$m = strlen($X);
	$n = strlen($Y);

	for ($i = 0; $i <= $m; $i++)
	{
		$C[$i][0] = 0;
	}
	for ($j = 0; $j <= $n; $j++)
	{
		$C[0][$j] = 0;
	}

	for ($i = 1; $i <= $m; $i++)
	{
		for ($j = 1; $j <= $n; $j++)
		{
			if ($X{$i-1} == $Y{$j-1})
			{
				$C[$i][$j] = $C[$i-1][$j-1]+1;
			}
			else
			{
				$C[$i][$j] = max($C[$i][$j-1], $C[$i-1][$j]);
			}
		}
	}

	return $C;
}

?>