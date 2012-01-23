<?php

// Fetch trees from TreeBASE

require_once (dirname(dirname(__FILE__)) . '/lib.php');

require_once (dirname(dirname(__FILE__)) . '/treeviewer/nexus.php');
require_once (dirname(dirname(__FILE__)) . '/treeviewer/tree2svg.php');


$id = $_GET['id'];

$url = 'http://treebase.org/treebase-web/tree_for_phylowidget/' . $id;

$svg = '<?xml version="1.0" ?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns="http://www.w3.org/2000/svg"
	width="100px" 
    height="100px" 
>
</svg>';

$nexus = get($url);

if ($nexus != '')
{
	$obj = parse_nexus($nexus);
	$svg = tree2svg($obj, 600, 400, 300, 10, false, 'translate' );		
}

header('Content-type: image/svg+xml');
echo $svg;

?>