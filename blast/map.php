<?php

$coordinates = array();

if (isset($_GET['coordinates']))
{
	$c = stripcslashes($_GET['coordinates']);
	$coordinates = json_decode($c);
}

/*
$coordinates = array();

$coordinates[] = array(100,-40);
$coordinates[] = array(80,-30);
*/

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="360px" height="180px">
   <style type="text/css">
      <![CDATA[     
      .region 
      { 
        fill:blue; 
        opacity:0.4; 
        stroke:blue;
      }
      ]]>
   </style>
  <rect id="dot" x="-3" y="-3" width="6" height="6" style="stroke:black; stroke-width:1; fill:white"/>
 <image x="0" y="0" width="360" height="180" xlink:href="' . 'images/mape.png"/>

 <g transform="translate(180,90) scale(1,-1)">';
 

foreach ($coordinates as $pt)
{
	$xml .= '   <use xlink:href="#dot" transform="translate(' . $pt[0] . ',' . $pt[1] . ')" />';
}

$xml .= '
      </g>
	</svg>';
	
	
header("Content-type: image/svg+xml");

echo $xml;

?>

