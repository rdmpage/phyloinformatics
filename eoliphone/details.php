<?php

require_once('../lib.php');

$max_images=20;

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

$url = 'http://eol.org/api/pages/1.0/' . $id . '.json?details=1&common_names=1&images=' . $max_images;

$json = get($url);
$obj = json_decode($json);


$html = '
<!DOCTYPE html> 
<html> 
	<head> 
	<title></title> 
	
	 <meta name="viewport" content="user-scalable=no, width=device-width,  initial-scale=1, minimum-scale=1,  maximum-scale=1"/>    
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link rel="stylesheet" href="../css/jquery.mobile-1.0.css" />
	<link rel="stylesheet"  href="jquery.mobile.scrollview.css" />
	<link rel="stylesheet"  href="jquery-mobile.css" />
	<link rel="stylesheet"  href="photoswipe.min.css" />
	
	
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery.mobile-1.0.js"></script>
	
	<script src="jquery.easing.1.3.js"></script>
	<script src="jquery.mobile.scrollview.js"></script>
	<script src="scrollview.js"></script>
	

	<script src="klass.min.js"></script>
	<script src="code.photoswipe.jquery-3.0.4.min.js"></script>
	
	<script type="text/javascript">
	
		// Need to bind to this event so that PhotoSwipe loads		
			$( "#details" ).live("pageinit", function() {
				try {
					var myPhotoSwipe = $(\'ul.gallery a\').photoSwipe({
						enableMouseWheel : false,
						enableKeyboard : false
					});
				} catch(err) {
					console.log(\'unabled to load photoswipe\');
				}
			});
			
	</script>
	
	
</head> 

<body>

<div id="details" data-role="page" class="gallery-page">

	<div data-role="header">
		<a id="back" href="children.php?id=' . $_GET['taxonID'] . '" data-transition="slide" data-direction="reverse" data-icon="arrow-l">Back</a>
		<h1>' .  $obj->scientificName . '</h1>
		<a href="index.html" data-icon="home">Home</a>
	</div><!-- /header -->

	<div id="content" data-role="content" data-scroll="true">';
	
	
	$html .= '<h1>' .  $obj->scientificName . '</h1>';
	
	
	if (isset($obj->taxonConcepts))
	{
		$html .= '<h2>Classifications</h2>';
		$html .= '<ul data-role="listview" data-inset="true">';
		foreach ($obj->taxonConcepts as $taxonConcept)
		{
			switch ($taxonConcept->nameAccordingTo)
			{
				case 'Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010':
					$html .= '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon" src="images/catalogueoflife16x16.png"/>Catalogue of Life</a></li>';
					break;
				case 'NCBI Taxonomy':
					$html .= '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon"  src="images/ncbi16x16.png"/>NCBI Taxonomy</a></li>';
					break;
				case 'WORMS Species Information (Marine Species)':
					$html .= '<li><a href="children.php?id=' . $taxonConcept->identifier . '"><img class="ui-li-icon"  src="images/worms16x16.png"/>World Register of Marine Species</a></li>';
					break;
				default:
					break;
			}
		}
		$html .= '</ul>';
	}
	
	
	if (isset($obj->dataObjects))
	{
		$html .= '<h2>Images</h2>';
		
		$html .= '<ul class="gallery" id="Gallery">';
		foreach ($obj->dataObjects as $dataObject)
		{
//			if (isset($dataObject->eolThumbnailURL))
			if ($dataObject->dataType == 'http://purl.org/dc/dcmitype/StillImage')
			{
				$caption = '';
				if (isset($dataObject->title))
				{
					$caption = $dataObject->title;
				}
			
			
				$html .= '<li>';
				$html .= '<a href="' . $dataObject->eolMediaURL . '" rel="external">';
//				$html .= '<img src="eolthumbnail.php?url=' . urlencode($dataObject->eolThumbnailURL) . '" alt = "' . $caption . '"></img>';
				$html .= '<img src="eolthumbnail.php?url=' . urlencode($dataObject->eolMediaURL) . '" alt = "' . $caption . '"></img>';
				$html .= '</a>';
				$html .= '</li>';
			}
		}
		$html .= '</ul>';
	}
	
$html .= '
	</div><!-- /content -->

</div><!-- /page -->

</body>
</html>';

header("Content-type: text/html");
echo $html;

?>