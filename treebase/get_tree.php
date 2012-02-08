<?php

//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

require_once (dirname(dirname(__FILE__)) . '/treeviewer/nexus.php');
require_once (dirname(dirname(__FILE__)) . '/treeviewer/tree2svg.php');


$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'treebase'		# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$id = $_GET['id'];

$format = 'svg';

if (isset($_GET['format']))
{
	$format = $_GET['format'];
	
	switch ($format)
	{
		case 'nexus':
		case 'svg':
			break;
			
		default:
			$format = 'svg';
			break;
	}
}

$sql = 'SELECT tree FROM treebase WHERE id=' . $db->qstr($id) . ' LIMIT 1';

$result = $db->Execute($sql);
if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

$nexus = $result->fields['tree'];

switch ($format)
{
	case 'nexus':
		header('Content-type: text/plain');
		echo $nexus;	
		break;
		
	case 'svg':
	default:
		$obj = parse_nexus($nexus);
		$svg = tree2svg($obj, 600, 400, 300, 10, false, 'translate' );		
		
		header('Content-type: image/svg+xml');
		echo $svg;
		break;
}


?>