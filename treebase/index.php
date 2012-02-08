<?php

require_once(dirname(__FILE__) . '/ncbi.php');

require_once(dirname(dirname(__FILE__)) . '/treeviewer/tree.php');

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

$tax_id = 8342; // anura
$tax_id=40674; // mammals
$tax_id=9722; // cetaceans
$tax_id=32523; // tetrapods
$tax_id=41666; 


if (isset($_GET['tax_id']))
{
	$tax_id = $_GET['tax_id'];
}

echo '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>TreeBASE Browser</title>
	
   <!-- Le HTML5 shim, for IE6-8 support of HTML elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->


   <script type="text/javascript"
      src="../js/jquery.js">
    </script>


    <script src="../js/bootstrap-twipsy.js"></script>
    <script src="../js/bootstrap-popover.js"></script>

  <link href="../css/bootstrap.css" rel="stylesheet">
     
    
    <script type="text/javascript">
 
 	var selected = "";
 	
  	function showstudy(id)
      {
      	$("#study").html("");
      	
      	$.getJSON("get_study.php?id=" + id + "&callback=?",
			function(data){
				if (data.title)
				{
					var html = "";
					html += "<b>" + data.title + "</b><br/>";
					
					if (data.publication_outlet)
					{
						html += "<i>" + data.publication_outlet + "</i>";
					}
					if (data.year)
					{
						html += " " + data.year;
					}
					
					if (data.identifiers.doi)
					{
						html += \' <a href="http://dx.doi.org/\' + data.identifiers.doi + \'" target="_new">http://dx.doi.org/\' + data.identifiers.doi + \'</a>\';
					}
					if (data.identifiers.treebase2)
					{
						html += \' [<a href="http://purl.org/phylo/treebase/phylows/study/TB2:\' + data.identifiers.treebase2 + \'?format=html" target="_new">\' + data.identifiers.treebase2 + \'</a>]\';
					}
					html += \' [<a href="http://purl.org/phylo/treebase/phylows/study/TB2:\' + data.identifiers.treebase2 + \'?format=nexus" target="_new">NEXUS</a>]\';
					html += \' [<a href="get_tree.php?id=\' + id + \'&format=nexus" target="_new">tree</a>]\';
					$("#study").html(html);
				}
			}
		);
      }
 	
 	
 	function showtree(id)
	{
		if (selected != "")
		{
			$("#" + selected).css("background-color", "rgb(228,228,228)");
		}
		selected = id.replace("TB2:", "");
		$("#" + selected).css("background-color", "rgb(64,64,64)");
		
	
		$(\'#svgload\').html("Loading tree " + id);
		$(\'#svgload\').load("get_tree.php?id=" + id);
		
		showstudy(id);
	}
	</script>

    </head>
    </body>';
    
    echo '<h1>TreeBASE browser</h1>';



// dimensions

$tb_width = 500;
$tb_height = 500;

// get span
$sql = "SELECT *, AsText(bbox) AS bbox 
FROM ncbi_tree 
INNER JOIN ncbi_names USING (tax_id)
WHERE tax_id=$tax_id 
AND name_class = 'scientific name'
LIMIT 1";

$result = $db->Execute($sql);
if ($result == false) die("failed [" . __FILE__ . ":" . __LINE__ . "]: " . $sql);

if ($result->NumRows() == 1)
{
	$wkt = $result->fields['bbox'];
	
	$span = $result->fields['right'] - $result->fields['left'];
	$scale = $tb_height/$span;
	$left = $result->fields['left'];
	
	
	if (0)
	{
	echo '<div>' . '<a href="index.php?tax_id=' . $result->fields['parent_tax_id'] . '">←</a>' .  $result->fields['name_txt'] 
	. '<img src="thumbnail.php?tax_id=' . $result->fields['tax_id'] . '" height="80"/>'
	. '</div>';
	}
	else
	{
	echo '<h2>' . '<a href="index.php?tax_id=' . $result->fields['parent_tax_id'] . '">←</a>' .  $result->fields['name_txt'] 
	. '</h2>';
	
	}
	
	
	echo '<div style="position:relative;">';

	
	echo '<div style="width:' . $tb_width . 'px;height:' . $tb_height . 'px;background-color:whitesmoke;border:1px solid rgb(128,128,128);overflow:auto;">';
	
	
	// NCBI tree
	echo '<div id="' . $result->fields['tax_id'] . '" style="position:absolute;'
		. 'left:0px;top:0px;width:30px;height:' . $tb_height . 'px;background-color:white;">';
		
	$w = $span * $tb_height;
	$t = $result->fields['left'] * $tb_height;
	
	
	echo '<div style="position:absolute;'
		. 'left:0px;top:' . $t . 'px;width:30px;height:' . $w . 'px;background-color:powderblue;"></div>';
		
		
	echo '</div>';
		
		
	// get children
	$sql = "SELECT *, AsText(bbox) AS bbox FROM ncbi_tree 
	INNER JOIN ncbi_names USING(tax_id)
	WHERE parent_tax_id=$tax_id
	AND ncbi_names.name_class='scientific name'";
	
	$result = $db->Execute($sql);
	if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
	while (!$result->EOF) 
	{
	
		$s = $result->fields['right'] - $result->fields['left'];
		
		$height = $s/$span * $tb_height;
		
		$min_height = 24;
		
		if ($height > 18)
		{
		
		$top = ($result->fields['left'] - $left) * $scale;
	
		echo '<a href="index.php?tax_id=' . $result->fields['tax_id'] . '">';

		echo '<div id="' . $result->fields['tax_id'] . '" style="position:absolute;'
		. 'left:30px;top:' . $top . 'px;width:' . ($tb_width - 30) . 'px;height:' . $height . 'px;background:-webkit-gradient(linear, left top, right bottom, from(#CCDEFD), to(#FFFFFF));background: -moz-linear-gradient(-45deg, #aaa, #fff);filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#aaaaaa\', EndColorStr=\'#ffffff\');">';
		
		// label
		
		echo '<div style="position:absolute;left:0px;top:0px;font-size:18px;z-index:100;pointer-events:none;opacity:0.4;">';
	
		echo $result->fields['name_txt'];
		echo '</div>';

		echo '</div>';
		echo '</a>';
		
		}
		
		$result->MoveNext();		
	}	
	
	
	
		

echo '</div>';
	
	
	// trees 

// get trees from TreeBASE
$sql = 'SELECT * FROM treebase
INNER JOIN ncbi_tree ON treebase.majority_taxon_tax_id = ncbi_tree.tax_id
WHERE (MBRContains(GeomFromText(\'' . $wkt .'\'), majority_taxon_bbox) = 1)
ORDER by ncbi_tree.post_order DESC
LIMIT 200';

$offset = 100;
$count = 0;

$paths = array();

$tree_objs = array();

$result = $db->Execute($sql);
if ($result == false) die("failed [" . __LINE__ . "]: " . $sql);
while (!$result->EOF) 
{

	$tree = new stdclass;
	$tree->id = $result->fields['id'];
	$tree->left = $result->fields['left'];
	$tree->right = $result->fields['right'];
	$tree->majority_taxon_tax_id = $result->fields['majority_taxon_tax_id'];
	$tree->publication = json_decode($result->fields['publication']);

	$tree_objs[$result->fields['id']] = $tree;
	
	// Store path for this tree
	$paths[$result->fields['id']] = array_reverse(ncbi_ancestors($result->fields['majority_taxon_tax_id'], $tax_id));
	$paths[$result->fields['id']][] = $result->fields['majority_taxon_tax_id'];
	
	$result->MoveNext();		
}	
		
// compute layout...

	// Construct a tree of the majority taxa and use it to arrange trees in "layers"
	$t = new Tree();
	
	$node_list = array(); // list of nodes in path tree
	$tree_list = array(); // list of trees assigned to each node
	
	$first = true;
	foreach ($paths as $k => $path)
	{
		//print_r($path);
		$n = count($path);
		
		if ($first)
		{
			// Build first path
			$first = false;
		
			$curnode = $t->NewNode($path[0]);
			$node_list[$path[0]] = $curnode;
			$t->SetRoot($curnode);
			$n = count($path);
			for ($i = 1; $i < $n; $i++)
			{
				$node = $t->NewNode($path[$i]);
				$node_list[$path[$i]] = $node;
				$curnode->SetChild($node);
				$node->SetAncestor($curnode);
				$curnode = $node;
			}
			
			// Store tree location
			$tree_list[$path[$n-1]] = array();
			$tree_list[$path[$n-1]][] = $k;
		}
		else
		{
			// Add remaining paths
			for ($i = 0; $i < $n; $i++)
			{
				if (isset($node_list[$path[$i]]))
				{
				}
				else
				{
					$anc = $path[$i-1];
					$curnode = $node_list[$anc];
					$q = $curnode->GetChild();
					if ($q)
					{
						while ($q->GetSibling())
						{
							$q = $q->GetSibling();
						}
						$node = $t->NewNode($path[$i]);
						$node_list[$path[$i]] = $node;
						$q->SetSibling($node);
						$node->SetAncestor($curnode);						
					}
					else
					{
						$node = $t->NewNode($path[$i]);
						$node_list[$path[$i]] = $node;
						$curnode->SetChild($node);
						$node->SetAncestor($curnode);
					}
				}
			}
			// Store tree location (i.e., trees associated with this node)
			if (!isset($tree_list[$path[$n-1]]))
			{
				$tree_list[$path[$n-1]] = array();
			}
			$tree_list[$path[$n-1]][] = $k;
		}
	}
	
	/*
	echo '<pre>';
	$t->Dump();
	
	echo $t->WriteDot();
	
	print_r($tree_list);
	echo '</pre>';
	*/
	
	// We now have a tree of trees, we want to assign these to a linear order of layers for drawing.
	// Nodes that have no trees are "ignored"
	$layers = array();
	$depth = 0;
	
	$p = new PreorderIterator($t->GetRoot());
	$q = $p->Begin();
	while ($q != NULL)
	{	
		if ($q == $t->GetRoot())
		{
			// Do any studies map to this node?
			if (isset($tree_list[$q->GetLabel()]))
			{
				$q->SetAttribute('depth', 0);
				if (!isset($layers[$depth]))
				{
					$layers[$depth] = array();
				}
				$layers[$depth][] = $q->GetLabel();
			}
			else
			{
				$q->SetAttribute('depth', -1);
			}
		}
		else
		{
			$anc = $q->GetAncestor();
			
			// Do any studies map to this node?
			if (isset($tree_list[$q->GetLabel()]))
			{
				// Yes, so we increase the depth of this node
				// Note that we need to allow for the possibility that multiple trees
				// map to the previous layer.
				$depth = $anc->GetAttribute('depth');
				
				if ($depth == -1)
				{
					$depth = 0;
				}
				else
				{
					$r = $anc;
					while (!isset($tree_list[$r->GetLabel()]))
					{
						$r = $r->GetAncestor();
					}					
					$depth += count($tree_list[$r->GetLabel()]);
				}
				
				
				$q->SetAttribute('depth', $depth);
				
				if (!isset($layers[$depth]))
				{
					$layers[$depth] = array();
				}
				$layers[$depth][] = $q->GetLabel();	
			}
			else
			{
				$depth = $anc->GetAttribute('depth');
				$q->SetAttribute('depth', $depth);
			}
		}
		
		$q = $p->Next();
	}
	
	/*
	echo '<pre>';
	$t->Dump();
	
	echo $t->WriteDot();
	
	print_r($tree_list);
	
	print_r($layers);
	echo '</pre>';
	*/
	
	// Traverse in preorder...



// display trees 

foreach ($layers as $layer_number => $taxids_in_this_layer)
{
	foreach ($taxids_in_this_layer as $taxid)
	{
		$count = 0;
		foreach ($tree_list[$taxid] as $tree_id)
		{
			$tree = $tree_objs[$tree_id];
			
			$s = $tree->right - $tree->left;
			
			$tree_width = 10;
			$height = $s/$span * $tb_height;
			$top = ($tree->left-$left) * $scale;
			
			$x = $offset; // away from taxonomy
			$x += ($layer_number * $tree_width); // offset for this layer
			$x += ($tree_width * $count); // offset for this tree within layer

			$count++;
		
			echo '<div rel="twipsy" data-placement="right" data-original-title="' . $tree->publication->title . '" id="' . str_replace("TB2:", '', $tree->id) . '" style="position:absolute;'
				. 'left:' . $x . 'px;top:' . $top . 'px;width:' . $tree_width . 'px;height:' . $height . 'px;background-color:rgb(228,228,228);border:1px solid rgb(128,128,128);" onclick="showtree(\'' . $tree->id . '\');">';
		
			echo '</div>';
		}
	}

}

echo '<h3>Shortcuts to taxa</h3>';
echo '<div>';

echo '<a href="?tax_id=8342"><img src="images/200px-Caerulea3_crop.jpg" height="48" /></a>';
echo ' ';
echo '<a href="?tax_id=71240"><img src="images/200px-Sweetbay_Magnolia_Magnolia_virginiana_Flower_Closeup_2242px.jpg" height="48"/></a>';
echo ' ';
echo '<a href="?tax_id=6960"><img src="images/200px-Diptera_01gg.jpg" height="48"/></a>';
echo ' ';
echo '<a href="?tax_id=4751"><img src="images/280px-Fungi_collage.jpg" height="48"/></a>';
echo ' ';
echo '<a href="?tax_id=40674"><img src="images/200px-Mamíferos.jpg" height="48"/></a>';



echo '</div>';
	

echo '<div>';


echo  '<div style="position:absolute;left:' . ($tb_width + 30) . 'px;width:700px;height:600px;top:0px;float:right;overflow:hide;">';
echo '<div id="study" style="position:absolute;left:0px;top:0px;"></div>';
echo  '<div id="svgload" style="position:absolute;left:0px;top:70px;width:700px;height:600px;float:right;overflow:hide;"></div>';
echo '</div>';

echo '</div>';

echo '<script>
            $(function () {
              $("div[rel=twipsy]").twipsy({
                live: true
              })
            })

          </script>';

echo '</body>';
echo '</html>';

}

?>
