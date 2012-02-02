<?php

//--------------------------------------------------------------------------------------------------
// MySQL
require_once(dirname(dirname(__FILE__)).'/adodb5/adodb.inc.php');

$db = NewADOConnection('mysql');
$db->Connect(
	"localhost", 	# machine hosting the database, e.g. localhost
	'root', 		# MySQL user name
	'', 			# password
	'treebase'		# database
	);
	
// Ensure fields are (only) indexed by column name
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

//--------------------------------------------------------------------------------------------------
// Get set of tax_ids along path from node to root;
function get_ncbi_taxon($tax_id)
{
	global $db;
	
	$taxon = new stdclass;
	$taxon->tax_id = (Integer)$tax_id;

	$sql = 'SELECT * FROM ncbi_nodes WHERE tax_id=' . $tax_id . ' LIMIT 1';

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	
	$taxon->parent_tax_id = (Integer)$result->fields['parent_tax_id'];
	$taxon->rank = $result->fields['rank'];


	$sql = 'SELECT * FROM ncbi_names WHERE tax_id=' . $tax_id;

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
	
	while (!$result->EOF) 
	{
		switch ($result->fields['name_class'])
		{
			case 'scientific name':
				$taxon->name = $result->fields['name_txt'];
				break;
				
			default:
				$taxon->synonyms[] = $result->fields['name_txt'];
				break;
		}
		$result->MoveNext();
	}	
		
	return $taxon;
}


//--------------------------------------------------------------------------------------------------
// Get set of tax_ids along path from node to root;
function ncbi_ancestors($tax_id, $root = 1)
{
	global $db;

	$ancestors = array();
	$ancestor_id = $tax_id;
	
	//echo "----\n";
	while ($ancestor_id != $root)
	{
		$sql = 'SELECT * FROM ncbi_nodes WHERE tax_id=' . $ancestor_id . ' LIMIT 1';
		//echo $sql . "\n";

		$result = $db->Execute($sql);
		if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);
		
		if ($result->NumRows() == 1)
		{		
			$ancestor_id = $result->fields['parent_tax_id'];
			$ancestors[] = (Integer)$ancestor_id;
		}
		else
		{
			// problem (e.g., NCBI taxonomy version conflict)
			$ancestor_id = 1; // break
			$ancestors = array();	
		}
	}
	//echo "--done--\n";
	
	return $ancestors;
}

//--------------------------------------------------------------------------------------------------
// V. crude LCA
function ncbi_lca($tax_id_1, $tax_id_2)
{
	global $db;

	// If tax_id is the same, then return as lca
	if ($tax_id_1 == $tax_id_2)
	{
		return $tax_id_1;
	}

	$path_1 = ncbi_ancestors($tax_id_1);	
	$path_2 = ncbi_ancestors($tax_id_2);
	
	$path_1 = array_reverse($path_1);
	$path_2 = array_reverse($path_2);
	
	// ensure tax_ids are part of this path (handle case where one node is lca of itself and other node)
	$path_1[] = $tax_id_1;
	$path_2[] = $tax_id_2;
		
	$i = 0;
	
	$m = count($path_1);
	$n = count($path_2);
	
	$x = min($m, $n);
	
	//print_r($path_1);
	//print_r($path_2);
	
	while (($path_1[$i] == $path_2[$i]) && ($i < $x))
	{
		$i++;
	}
	
	$lca = $path_1[$i-1];
	return $lca;
}

//--------------------------------------------------------------------------------------------------
// Get bounding box of taxon
function ncbi_bbox($tax_id)
{
	global $db;
	
	$wkt = '';
	
	$sql = "SELECT AsText(bbox) AS bbox FROM ncbi_tree WHERE tax_id=$tax_id LIMIT 1";

	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{
		$wkt = $result->fields['bbox'];
	}
	
	return $wkt;
}

//--------------------------------------------------------------------------------------------------
// Get bounding box of taxa in tree
function ncbi_span($tax_ids)
{
	global $db;
	
	$span = new stdclass;
	
	// left
	$sql = "SELECT * FROM ncbi_tree
WHERE tax_id IN (" . join(",", $tax_ids) . ")
ORDER BY `left` ASC
LIMIT 1";
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{
		$span->left = $result->fields['left'];
		$span->left_tax_id = $result->fields['tax_id'];
	}
	
	
	// right
	$sql = "SELECT * FROM ncbi_tree
WHERE tax_id IN (" . join(",", $tax_ids) . ")
ORDER BY right DESC
LIMIT 1";
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

	if ($result->NumRows() == 1)
	{
		$span->right = $result->fields['right'];
		$span->right_tax_id = $result->fields['tax_id'];
	}
	
	return $span;
}
	

?>