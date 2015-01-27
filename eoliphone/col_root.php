<?php

require_once('../lib.php');

// 1188 CoL

$url = 'http://eol.org/api/hierarchies/1.0/1188.json?cache_ttl=';

$json = get($url);

$obj = json_decode($json);

echo '
<!DOCTYPE html> 
<html> 
	<head> 
	<title>Catalogue of Life</title> 
	
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0"> 
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link rel="stylesheet" href="../css/jquery.mobile-1.0.css" />
	<link rel="stylesheet"  href="jquery.mobile.scrollview.css" />

	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery.mobile-1.0.js"></script>
	
	<script src="jquery.easing.1.3.js"></script>
	<script src="jquery.mobile.scrollview.js"></script>
	<script src="scrollview.js"></script>

</head> 

<body> 

<div data-role="page">

	<div data-role="header">
		<a href="index.html" data-transition="slide" data-direction="reverse" data-icon="arrow-l">EOL Classifications</a>
		<h1>Catalogue of Life</h1>
	</div><!-- /header -->

	<div data-role="content" data-scroll="true">	

	<!-- CoL -->
	
	<!-- this is hard coded which means it will break with new classifications -->
  	<ul data-role="listview" >';
  	
  	foreach ($obj->roots as $root)
  	{
  		echo '<li><a href="children.php?id=' . $root->taxonID . '"><img src="thumbnail.php?taxonConceptID=' . $root->taxonConceptID . '"/>' . $root->scientificName . '</a></li>' . "\n";
	}  	
echo '    </ul>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>';