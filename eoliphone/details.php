<?php

require_once('../lib.php');

$id = $_GET['id'];

if ($id == 0)
{
	header('Location: index.html');
	exit(0);
}

$taxonID = 0;
if (isset($_GET['taxonID']))
{
	$taxonID = $_GET['taxonID'];
}

$url = 'http://eol.org/api/pages/1.0/' . $id . '.json?details=1&common_names=1&images=10';

$json = get($url);
$obj = json_decode($json);


echo'
<!DOCTYPE html> 
<html> 
	<head> 
	<title></title> 
	
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
		<a id="back" href="children.php?id=' . $_GET['taxonID'] . '" data-transition="slide" data-direction="reverse" data-icon="arrow-l">Back</a>
		<h1>' .  $obj->scientificName . '</h1>
		<a href="index.html" data-icon="home">Home</a>
	</div><!-- /header -->

	<div id="content" data-role="content" data-scroll="true">';
	
	echo '<h1>' .  $obj->scientificName . '</h1>';
	
	
	if (isset($obj->taxonConcepts))
	{
		echo '<h2>Classifications</h2>';
		echo '<ul data-role="listview" data-inset="true">';
		foreach ($obj->taxonConcepts as $taxonConcept)
		{
			switch ($taxonConcept->nameAccordingTo)
			{
				case 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010':
					echo '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon" src="images/catalogueoflife16x16.png"/>Catalogue of Life</a></li>';
					break;
				case 'NCBI Taxonomy':
					echo '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon"  src="images/ncbi16x16.png"/>NCBI Taxonomy</a></li>';
					break;
				case 'WORMS Species Information (Marine Species)':
					echo '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon"  src="images/worms16x16.png"/>World Register of Marine Species</a></li>';
					break;
				default:
					break;
			}
		}
		echo '</ul>';
	}
	
	echo'
	<div class="ui-grid-d">';
	
	if (isset($obj->dataObjects))
	{
		echo '<h2>Images</h2>';
		$count = 0;
		foreach ($obj->dataObjects as $dataObject)
		{
			if (isset($dataObject->eolThumbnailURL))
			{
				switch ($count)
				{
					case 0:
						echo '<div class="ui-block-a">';
						break;
					case 1:
						echo '<div class="ui-block-b">';
						break;
					case 2:
						echo '<div class="ui-block-c">';
						break;
					case 3:
						echo '<div class="ui-block-d">';
						break;
					case 4:
						echo '<div class="ui-block-e">';
						break;
				}		
				$count++;
				if ($count == 5)
				{
					$count = 0;
				}
			
				echo '<img src="' . $dataObject->eolThumbnailURL . '"></img>';
				echo '</div>';
			}
		}
	}
echo  '</div><!-- /grid-b -->';
	
echo '
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>';

?>