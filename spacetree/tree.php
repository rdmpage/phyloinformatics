<?php


//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'col_3JAN2011'	# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

function main($node = 0,  $callback='')
{
	global $db;
	
	// Create some data for us to plot
	
	$root = new stdclass;
	$root->id = 0;
	$root->name = "root";
	$root->data = "";
	$root->children = array();
	
	
	$subtree_root = $node;
	
	$sql = 'SELECT * FROM taxa 
		WHERE record_id = ' . $subtree_root . ' LIMIT 1';
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{
		$root->id = $subtree_root;
		$root->name =  $result->fields['name'];
	}
	
	
	// Get children of this node
	$sql = 'SELECT * FROM taxa 
		WHERE parent_id = ' . $subtree_root .
		' AND (record_id <> 0)
		AND (is_accepted_name = 1)
		ORDER BY name';

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	while (!$result->EOF) 
	{
		$tree_node = new stdclass;
		$tree_node->id = $result->fields['record_id'];
		$tree_node->name = str_replace(' ', '&nbsp;', $result->fields['name']);
		$tree_node->data = ''; 
		$tree_node->children = array();
		
		array_push($root->children, $tree_node);

		$result->MoveNext();	
	}
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo json_encode($root);
	if ($callback != '')
	{
		echo ')';
	}

}

//--------------------------------------------------------------------------------------------------
$node = 0;
$callback='';

if (isset($_GET['node']))
{
	$node = $_GET['node'];
}
if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

main($node, $callback);

?>





	