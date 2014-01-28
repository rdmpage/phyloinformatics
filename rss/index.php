<?php 
$format = 'html';
$feed_prefix = 'http://iphylo.org/~rpage/phyloinformatics/rss/';

if (isset($_GET['format']))
{
	$format = $_GET['format'];
	
	switch ($format)
	{
		case 'opml':
		case 'html':
			break;
			
		default:
			$format = 'html';
	}
}

// List of feeds I generate, placed in categories
$feeds = array(


	// Barcodes
	
	'Barcodes' => array(
		array('title' => 'Birds', 'url' => 'barcode.php?taxon_id=8782'),
		array('title' => 'Amphibia', 'url' => 'barcode.php?taxon_id=8292'),
		array('title' => 'Fish', 'url' => 'barcode.php?taxon_id=7898'),
		array('title' => 'Insects', 'url' => 'barcode.php?taxon_id=6960'),
		array('title' => 'Crustacea', 'url' => 'barcode.php?taxon_id=6657'),
		array('title' => 'Fungi', 'url' => 'barcode.php?taxon_id=4751'),
		array('title' => 'Plants', 'url' => 'barcode.php?taxon_id=3193')
	)
	
	/*
	'GenBank' => array(
		array('title' => 'Geotagged sequences', 'url' => 'genbank.php')
	),	
	*/

		
	);

switch ($format)
{
	case 'opml':
		// header
		$doc = new DomDocument('1.0');
		$opml = $doc->createElement('opml');
		$opml->setAttribute('version', '1.0');
		$opml = $doc->appendChild($opml);

		// head
		$head = $opml->appendChild($doc->createElement('head'));

		// title
		$title = $head->appendChild($doc->createElement('title'));
		$title->appendChild($doc->createTextNode('bioGUID RSS feeds'));
		
		// body
		$body = $opml->appendChild($doc->createElement('body'));
	
		foreach ($feeds as $category => $list)
		{
			$outline = $body->appendChild($doc->createElement('outline'));
			$outline->setAttribute('title', $category);	
			$outline->setAttribute('text', $category);	
			
			foreach ($list as $item)
			{
			
				$feed = $outline->appendChild($doc->createElement('outline'));
				$feed->setAttribute('type', 'atom');
			
				foreach ($item as $k => $v)
				{
					switch ($k)
					{
						case 'title':
							$feed->setAttribute('title', $v);
							$feed->setAttribute('text', $v);
							break;
						case 'url':
							if (preg_match('/^http:/', $v))
							{
								$feed->setAttribute('xmlUrl', $v);
							}
							else
							{								
								$feed->setAttribute('xmlUrl', $feed_prefix . $v);
							}
							break;
						default:
							break;
					}
				}
			}
		}
		
		header("Content-type: text/xml");
		echo $doc->saveXML();
		
		break;
		
	default:
		break;
		
}	

?>
<!DOCTYPE html>
<html>
<head>
	<link rel="subscriptions" type="text/x-opml" title="bioGUID RSS feeds" ref="?format=opml" />

  <title>RSS feeds</title>
  
    <style type="text/css">
	body 
	{
		font-family: Verdana, Arial, sans-serif;
		font-size: 12px;
		padding:30px;
	
	}
	
.blueRect {
	background-color: rgb(239, 239, 239);
	border:1px solid rgb(239, 239, 239);
	background-repeat: repeat-x;
	color: #000;
	width: 400px;
}
.blueRect .bottom {
	height: 10px;
}
.blueRect .middle {
	margin: 10px 12px 0px 12px;
}
.blueRect .cn {
	background-image: url(../images/c6.png);
	background-repeat: no-repeat;
	height: 10px;
	line-height: 10px;
	position: relative;
	width: 10px;
}
.blueRect .tl {
	background-position: top left;
	float: left;
	margin: -2px 0px 0px -2px;
}
.blueRect .tr {
	background-position: top right;
	float: right;
	margin: -2px -2px 0px 0px;
}
.blueRect .bl {
	background-position: bottom left;
	float: left;
	margin: 2px 0px -2px -2px;
}
.blueRect .br {
	background-position: bottom right;
	float: right;
	margin: 2px -2px -2px 0px;
}		
    
	#details
	{
		display: none;
		position:absolute;
		background-color:white;
		border: 1px solid rgb(128,128,128);
	}
    </style>
  

</head>
<body>

  <h1>Feeds</h1>
  <p><a href="?format=opml"><img src="images/opml-icon-32x32.png" border="0"/></a> <a href="?format=opml">OPML listing of feeds</a></p>

<?php
	
	foreach ($feeds as $category => $list)
	{
		echo "<h2>$category</h2>\n";
		echo "<ul>\n";
		
		foreach ($list as $item)
		{
			$url_text = '';
			$title_text = '';
			foreach ($item as $k => $v)
			{
				switch ($k)
				{
					case 'title':
						$title_text = $v;
						break;
					case 'url':
						if (preg_match('/^http:/', $v))
						{
							$url_text = '<a href="' . $v . '">';
						}
						else
						{								
							$url_text = '<a href="' . $feed_prefix . $v . '">';
						}
						break;
						break;
					default:
						break;
				}
			}
			
			echo '<li>' . $url_text . $title_text . '</a></li>';
		}
		echo "</ul>\n";
		
	}
?>

</body>
</html>