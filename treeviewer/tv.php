<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');

require_once (dirname(__FILE__) . '/nexus.php');
require_once (dirname(__FILE__) . '/tree2svg.php');

$have_tree = false;

if (isset($_POST['tree']))
{
	$tree = $_POST['tree'];
	$tree = stripcslashes($tree);
	$obj = parse_nexus($tree);
	$have_tree = true;
}

if (isset($_GET['url']))
{
	$nexus = get($_GET['url']);
	if ($nexus != '')
	{
		$obj = parse_nexus($nexus);
		$have_tree = true;	
	}
}


if ($have_tree)
{
	// Make SVG
	
	echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Untitled</title>';
	
echo '<script>
function node_info(otu)
    {
    	var info = document.getElementById("info");
    	info.innerHTML = otu;
    }
</script>';	
	
	echo '
</head>
<body>';
	
	echo '<div id="info" style="float:right;width:300px;height:100px;border:1px solid red;"></div>';
	
	echo '<div style="border:1px solid red;width:650px; height:100%; overflow:auto;">';
	
	$svg = tree2svg($obj, 600, 400, 300, 10, false, 'translate' );
	//header('Content-type: image/svg+xml');
	echo $svg;
	echo '</div>';
	
echo '
</body>
</html>';
	
	
}
else
{
	echo 
'<html>
<head>
</head>
<body>
<form method="get" action="tv.php">
	<input id="url" name="url" size="60"></input>
	<input type="submit" value="Go"></input>
</form>
<form method="post" action="tv.php">
	<textarea id="tree" name="tree" rows="30" cols="60"></textarea>
	<input type="submit" value="Go"></input>
</form>
</body>
</html>';
}
?>	