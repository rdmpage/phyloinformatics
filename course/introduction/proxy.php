<?php

// proxy 

error_reporting(E_ALL);

$url = '';
if (isset($_GET['url']))
{
	$url = $_GET['url'];
}

if ($url != '')
{
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);

  	echo $data;
}

?>
