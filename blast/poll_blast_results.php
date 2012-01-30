<?php

require_once(dirname(__FILE__) . '/ncbi_blast.php');

// See if our results are ready...

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

$url = "http://www.ncbi.nlm.nih.gov/blast/Blast.cgi?CMD=Get&RID=$rid";
sleep(2);

$html = get($url);

$obj = new stdclass;

if (preg_match('/\s+Status=WAITING/m', $html))
{
	$obj->msg = "Waiting for results";
	$obj->status = 'WAITING';
}
if (preg_match('/\s+Status=FAILED/m', $html))
{
	$obj->msg = "Search $rid failed; please report to blast-help\@ncbi.nlm.nih.gov";
	$obj->status = 'FAILED';
}
if (preg_match('/\s+Status=UNKNOWN/m', $html))
{
	$obj->msg = "Search $rid expired";
	$obj->status = 'UNKNOWN';
}
if (preg_match('/\s+Status=READY/m', $html))
{
	$obj->msg = "Search complete, retrieving results...";
	$obj->status = 'READY';
}

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