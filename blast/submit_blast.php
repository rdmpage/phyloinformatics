<?php

require_once(dirname(__FILE__) . '/ncbi_blast.php');

// Submit a BLAST job to NCBI and return RID and rtoe

$gi = '';

if (isset($_GET['gi']))
{
	$gi = $_GET['gi'];
}

$callback = '';
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

// BLAST
$job = send_blast_job($gi);

if ($callback != '')
{
	echo $callback . '(';
}
echo json_encode($job);
if ($callback != '')
{
	echo ')';
}


?>