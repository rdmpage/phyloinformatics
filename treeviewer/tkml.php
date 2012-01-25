<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');

require_once (dirname(__FILE__) . '/nexus.php');
require_once (dirname(__FILE__) . '/tree2svg.php');

function tree2kml($obj, $default_labels='taxa')
{
	$t = new Tree();
	$t->Parse($obj->tree->newick);
	$t->BuildWeights($t->GetRoot());
	
	// compute KML coordinates
	$attr = array();
	$td = new KmlTreeDrawer($t, $attr);
	$td->CalcCoordinates();	
		
	// raw labels (OTUs)
//	$port->StartGroup('otu', (('otu' == $default_labels) || !isset($obj->translations)) );


	echo "<?xml version =\"1.0\" encoding=\"UTF-8\"?>\n";
	echo "<kml xmlns=\"http://earth.google.com/kml/2.1\">\n";
    echo "<Document>\n";
	
	echo "<Style id=\"treeLine\">\n";
    echo "<LineStyle><color>7fffffff</color><width>2</width></LineStyle>\n";
	echo "</Style>\n";
	
	echo "<Style id=\"whiteBall\">\n";
	echo "<IconStyle>\n";
	echo "<Icon>\n";
	echo "<href>http://iphylo.org/~rpage/phyloinformatics/images/whiteBall.png</href>\n";
	echo "</Icon>\n";
	echo "</IconStyle>\n";
	echo "<LineStyle>\n";
	echo "<width>2</width>\n";
	echo "</LineStyle>\n";
	echo "</Style>\n";
	
	$td->Draw(null);	

	echo "<Folder>\n";
	echo "<name>Labels</name>\n";
	
	// labels
	$ni = new NodeIterator ($t->getRoot());
	
	
	$q = $ni->Begin();
	while ($q != NULL)
	{	
		if ($q->IsLeaf ())
		{
			echo "<Placemark>\n";
			echo "<name>" .  $q->Getlabel() . "</name>\n";
			echo "<styleUrl>#whiteBall</styleUrl>\n";
			echo "<Point>\n";
			echo "<altitudeMode>absolute</altitudeMode>\n";
			echo "<extrude>1</extrude>\n";
			echo "<coordinates>\n";
			echo $q->GetAttribute('long') . "," . $q->GetAttribute('lat') . "," . $q->GetAttribute('altitude') . "\n";
			echo "</coordinates>\n";
			echo "</Point>\n";
			echo "</Placemark>\n";
		}		
		$q = $ni->Next();
	}
	echo "</Folder>\n";
	
    echo "</Document>\n";
    echo "</kml>\n";


	
}


$have_tree = false;

if (isset($_POST['tree']))
{
	$obj = parse_nexus($_POST['tree']);
	$have_tree = true;
}


if ($have_tree)
{
	// Make KML
	
	header('Content-disposition: attachment; filename=tree.kml');
	header('Content-type: application/vnd.google-earth.kml+xml');
	$kml = tree2kml($obj,'translate');
}
else
{
	echo 
'<html>
<head>
</head>
<body>
<p>Paste in a tree in NEXUS format.</p>
<form method="post" action="tkml.php">
	<textarea id="tree" name="tree" rows="30" cols="60"></textarea>
	<input type="submit" value="Go"></input>
</form>

<p>As an example copy and paste the NEXUS file below:</p>
<textarea cols="80" rows="20" readonly="readonly">
#NEXUS

[! Example of a tree file with latitude and longitude appended to taxon name. ]

BEGIN TREES;

tree * Banza = ((\'B. nihoa Alat=23.0622222222222long=-161.926111111111\',\'B. nihoa Blat=23.0622222222222long=-161.926111111111\'),((\'B. unica Alat=21.3372222222222long=-157.817777777778\',\'B. unica Blat=21.4163888888889long=-158.103611111111\'),(((((\'B. kauaiensis Alat=21.975long=-159.466111111111\',\'B. kauaiensis Blat=22.1538888888889long=-159.625\'),(\'B. parvula Alat=21.4447222222222long=-158.101388888889\',\'B. parvula Blat=21.5491666666667long=-158.186944444444\')),(\'B. molokaiensis Alat=21.1097222222222long=-156.902777777778\',\'B. molokaiensis Blat=21.1097222222222long=-156.902777777778\')),(\'B. deplanata Alat=20.8147222222222long=-156.875833333333\',\'B. deplanata Blat=20.8147222222222long=-156.870555555556\')),((\'B. brunnea Alat=20.9372222222222long=-156.619444444444\',\'B. brunnea Blat=20.855long=-156.603333333333\'),(((\'B. mauiensis Alat=20.845long=-156.557222222222\',\'B. mauiensis Blat=20.845long=-156.557222222222\'),(\'B. pilimauiensis Alat=20.8177777777778long=-156.230277777778\',\'B. pilimauiensis Blat=20.8177777777778long=-156.230277777778\')),((\'B. nitida Alat=19.5088888888889long=-155.862777777778\',(\'B. nitida Blat=19.5705555555556long=-155.188611111111\',\'B. nitida Clat=20.1291666666667long=-155.769166666667\'))))))));

END;
</textarea>
</body>
</html>';
}
?>	