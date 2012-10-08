<?php

require_once('../lib.php');

$id = $_GET['id'];

if ($id == 0)
{
	header('Location: index.html');
	exit(0);
}

$url = 'http://eol.org/api/hierarchy_entries/1.0/' . $id . '.json';

//echo $url;
$json = get($url);
$obj = json_decode($json);

echo'
<!DOCTYPE html> 
<html> 
	<head> 
	<title>EOL Classification</title> 
	
	<meta name="viewport" content="width=device-width; initial-scale=1.0; user-scalable=0;"> 
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
		<a id="back" href="';
		
		
	if ($obj->parentNameUsageID != 0)
	{
		echo 'children.php?id=' . $obj->parentNameUsageID;
	}
	else
	{
		switch ($obj->nameAccordingTo[0])
		{
			case 'WORMS Species Information (Marine Species)':
				echo 'worms.html';
				break;
				
			case 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010':
				echo 'col.html';
				break;

			case 'NCBI Taxonomy':
				echo 'ncbi.html';
				break;
				
			default:
				break;
		}
	}
		
		
echo '" data-transition="slide" data-direction="reverse" data-icon="arrow-l">' . $obj->scientificName . '</a>
		<h1></h1>
		<a href="details.php?id=' . $obj->taxonConceptID . '&taxonID=' . $obj->taxonID . '" data-icon="grid">Details</a>
		</div><!-- /header -->

	<div id="content" data-role="content" data-scroll="true" >
	<ul data-role="listview">';
	
	foreach ($obj->children as $child)
	{
		echo '<li>';
		echo '<a href="children.php?id=' . $child->taxonID . '">';
		echo '<img src="thumbnail.php?taxonConceptID=' . $child->taxonConceptID . '"></img>';
		echo $child->scientificName;
		echo '</a>';
		echo '</li>';
	}
	
	
echo '
	</ul>
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>';

?>