<?php

require_once('../lib.php');

$max_images=1;

$id = $_GET['id'];

$url = 'http://eol.org/api/pages/1.0/' . $id . '.json?details=1&common_names=1&images=' . $max_images;

$json = get($url);
$obj = json_decode($json);

$html = '
<!DOCTYPE html> 
<html> 
	<head>
		<meta charset="utf-8" />
	</head> 
	<body style="font-family:sans-serif;font-size:14px;background-color:white;">
		<div>
			<b>' . $obj->scientificName . '</b><br/>';
			
	// Vernacular name?
	if (isset($obj->vernacularNames))
	{
		$vernacularName = '';
		
		foreach ($obj->vernacularNames as $v)
		{
			if ($v->eol_preferred)
			{
				$vernacularName = $v->vernacularName;
			}
		}
		
		if ($vernacularName != '')
		{
			$html .= '<span style="color:rgb(128,128,128);">' . $vernacularName . '</span>';
		}
		
		
	}
	
	// Image
	if (isset($obj->dataObjects))
	{
		$imageUrl = '';
		$n = count($obj->dataObjects);
		$i = 0;
		while (($imageUrl == '') && ($i < $n))
		{
			if (isset($obj->dataObjects[$i]->eolThumbnailURL))
			{
				$imageUrl = $obj->dataObjects[$i]->eolMediaURL;
			}
			$i++;
		}
		
		if ($imageUrl != '')
		{
			$html .= '<div>';
			$html .= '<img style="margin-top:2px;border:1px solid rgb(128,128,128);" src="eolthumbnail.php?url=' . urlencode($imageUrl) . '" width="100">';
			$html .= '</div>';
		}
	}
	
			
			
$html .= 			
		'</div>
	</body>
</html>';

echo $html;

?>