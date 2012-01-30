<?php


$rid = '';

if (isset($_GET['rid']))
{
	$rid = $_GET['rid'];
}

$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

// Align

$filename = 'tmp/' . $rid . '.fas';

$basename = preg_replace('/\.fas$/', '', $filename);

$command = '/usr/local/bin/clustalw2 -INFILE=' . $filename . ' -QUICKTREE -OUTORDER=INPUT -OUTPUT=NEXUS' . ' 1>tmp/' . $rid . '_CLUSTALW.log';

//echo $command;

system($command);

// Create NEXUS file for PAUP
$nxs_filename = $basename . '.nxs';

$nexus = file_get_contents($nxs_filename);

$nexus .= "\n";
$nexus .="[PAUP block]\n";
$nexus .="begin paup;\n";
$nexus .="   [root trees at midpoint]\n";
$nexus .="   set rootmethod=midpoint;\n";
$nexus .="   [construct tree using neighbour-joining]\n";
$nexus .="   nj;\n";
$nexus .="   [ensure branch lengths are output as substituions per nucleotide]\n";
$nexus .="   set criterion=distance;\n";
$nexus .="   [write rooted trees in Newick format with branch lengths]\n";
$nexus .="   savetrees format=nexus root=yes brlen=yes replace=yes;\n";
$nexus .="   quit;\n";
$nexus .="end;\n";

$nexus_filename = $basename . '.nex';
file_put_contents($nexus_filename, $nexus);

// Run PAUP
$command = '/usr/local/bin/paup ' . $nexus_filename .  ' 1>tmp/' . $rid . '_PAUP.log';

//echo $command; 
system($command);

// Ensure tree has full names...
if (1)
{
	$translate_filename = $basename . '.txt';
	$translate = file_get_contents($translate_filename);
	
	$tree_filename = $basename . '.tre';
	$tree = file_get_contents($tree_filename);
	
	$before = '';
	$after = '';
	
	$state = 0;
	
	$rows = explode("\n", $tree);
	foreach ($rows as $row)
	{
		switch ($state)
		{
			case 0:
				if (preg_match('/^\s+Translate/', $row))
				{
					$state = 1;
				}
				else
				{
					$before .= $row . "\n";
				}
				break;
			case 1:
				if (preg_match('/^\s+;$/', $row))
				{
					$state = 2;
				}
				break;
			case 2:
				$after .= $row . "\n";
				break;
			default:
				break;
		}
	}
	
	$tree = $before . $translate . $after;
	file_put_contents($tree_filename, $tree);
}

$obj = new stdclass;
$obj->tree_url = $basename . '.tre';

if ($callback != '')
{
	echo $callback . '(';
}
echo json_encode($obj);
if ($callback != '')
{
	echo ')';
}



?>