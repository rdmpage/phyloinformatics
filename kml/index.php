<?php

require_once (dirname(dirname(__FILE__)) . '/lib.php');
require_once (dirname(dirname(__FILE__)) . '/treeviewer/nexus.php');
require_once (dirname(dirname(__FILE__)) . '/treeviewer/tree.php');
require_once (dirname(__FILE__) . '/latlong.php');
require_once (dirname(__FILE__) . '/lcs.php');

$matching_path = '/Users/rpage/Sites/phyloinformatics/matching/';

$taxa = array();
$data = array();

define(TYPE_UNKNOWN, 						0);
define(TYPE_LATITUDE, 						1);
define(TYPE_LONGITUDE, 						2);
define(TYPE_LATITUDE_LONGITUDE, 			3);
define(TYPE_ALTERNATING_LATITUDE_LONGITUDE, 4);
define(TYPE_VOUCHER, 						5);
define(TYPE_GENBANK, 						6);
define(TYPE_LOCALITY, 						7);
define(TYPE_OTU, 							8);

//--------------------------------------------------------------------------------------------------
function compare ($str1, $str2)
{
	$n1 = strlen($str1);
	$n2 = strlen($str2);
	
	$C = LCSLength($str1, $str2);
	
	$subsequence_length = $C[$n1][$n2];
	
	$symdiff = 1.0 - (($n1 + $n2) - 2 * $subsequence_length)/($n1 + $n2);
	
	return $symdiff;
}

//--------------------------------------------------------------------------------------------------
function create_graph($a, $b)
{
	// 2. matching
	$count = 0;
	
	$gml = "graph [
comment \"Matching\"
directed 1
# First set of labels\n";
	
	foreach ($a as $str)
	{
		$gml .= "node [ id $count label \"" . addcslashes($str, '"') . "\" ]\n";
		$count++;
	}
	
	$gml .= "# Second set of labels\n";
	
	foreach ($b as $str)
	{
		$gml .= "node [ id $count label \"" . addcslashes($str, '"') . "\" ]\n";
		$count++;
	}
	
	$m = count($a);
	$n = count($b);
	
	for ($i = 0; $i < $m; $i++)
	{
		for ($j = 0; $j < $n; $j++)
		{
			$gml .= "edge [ source $i target " . ($m + $j) . " label \"" . floor(100 * compare ($a[$i], $b[$j])) . "\" ]\n";
		}
	}
	
	$gml .= "]\n";
	
	/*
	echo '<pre>';
	echo $gml;
	echo '</pre>';
	*/
	
	return $gml;
}

//--------------------------------------------------------------------------------------------------
function extract_table ($table_text)
{
	$rows = explode("\n", trim($table_text));
	
	$format = 'csv';
	
	if (preg_match('/\t/', $rows[0]))
	{
		$format = 'tsv';
	}
	
	// Headings 
	$headings = array();
	switch ($format)
	{
		case 'tsv':
			$headings = explode("\t", $rows[0]);
			break;

		case 'csv':
			$headings = str_getcsv($rows[0]);
			break;			
	}
	
	// Contents
	$content = array();
	$nrows = count($rows);
	for ($i=1;$i < $nrows; $i++)
	{
		switch ($format)
		{
			case 'tsv':
				$cells = explode("\t", $rows[$i]);
				break;

			case 'csv':
				$cells = str_getcsv($rows[$i]);
				break;				
		}
		$content[] = $cells;
	}
	
	// Classify columns
	$column_types = array();
	$ncols = count($headings);
	for ($i=0;$i<$ncols;$i++)
	{
		$column_types[$i] = TYPE_UNKNOWN;
		
		// Locality
		if (preg_match('/coordinates/i', $headings[$i]))
		{
			$column_types[$i] = TYPE_LATITUDE_LONGITUDE;
		}
		if (preg_match('/latlong/i', $headings[$i]))
		{
			$column_types[$i] = TYPE_LATITUDE_LONGITUDE;
		}
		if (preg_match('/latitude/i', $headings[$i]))
		{
			$column_types[$i] = TYPE_LATITUDE;
		}
		if (preg_match('/longitude/i', $headings[$i]))
		{
			$column_types[$i] = TYPE_LONGITUDE;
		}
	}
	
	// By assumption
	$column_types[0] = TYPE_OTU;
	
	if (0)
	{
		echo '<pre>';
		print_r($headings);
		print_r($column_types);
		print_r($content);
		echo '</pre>';
	}
	
	$data = array();
	
	foreach ($content as $row)
	{
		$otu = new stdclass;
		$otu->label = $row[0];
	
		$n = count($row);
		for ($i=1;$i<$n;$i++)
		{
			//echo "i=" . $i . ' ' . $column_types[$i] . "<br/>";
			switch ($column_types[$i])
			{
				case TYPE_LATITUDE_LONGITUDE:
					if (IsLatLong($row[$i], $latlong))
					{
						$otu->latlong = $latlong;
					}
					break;

				case TYPE_LATITUDE:
					if (IsLatitude($row[$i], $latitude))
					{
						if (!isset($otu->latlong))
						{
							$otu->latlong = array();
						}
						$otu->latlong['latitude'] = $latitude;
					}
					break;

				case TYPE_LONGITUDE:
					if (IsLongitude($row[$i], $longitude))
					{
						if (!isset($otu->latlong))
						{
							$otu->latlong = array();
						}
						$otu->latlong['longitude'] = $longitude;
					}
					break;
					
				default:
					break;
			}
		}
		
		$data[] = $otu;
	
	}
	
	/*
	echo '<pre>';
	print_r($data);
	echo '</pre>';
	*/
	
	return $data;
}

//--------------------------------------------------------------------------------------------------
function get_taxa_from_tree($obj)
{
	$taxa = array();
	
	
	if (isset($obj->translations->translate))
	{
		// get labels from translation table
		foreach ($obj->translations->translate as $k => $v)
		{
			$taxa[] = $v;
		}
	}
	else
	{
		// get labels from tree
		$t = new Tree();
		$t->Parse($obj->tree->newick);
		
		$ni = new NodeIterator ($t->GetRoot());
				
		$q = $ni->Begin();
		while ($q != NULL)
		{	
			if ($q->IsLeaf ())
			{
				$taxa[] = $q->GetLabel();
			}
			$q = $ni->Next();
		}
	}
	
	return $taxa;
}


//--------------------------------------------------------------------------------------------------
function tree2kml($t)
{
	// compute KML coordinates
	$attr = array();
	$td = new KmlTreeDrawer($t, $attr);
	$td->CalcCoordinates();	
	
	$kml = '';
			
	$kml .= "<?xml version =\"1.0\" encoding=\"UTF-8\"?>\n";
	$kml .= "<kml xmlns=\"http://earth.google.com/kml/2.2\">\n";
    $kml .= "<Document>\n";
	
	$kml .= "<Style id=\"treeLine\">\n";
    $kml .= "<LineStyle><color>7fffffff</color><width>2</width></LineStyle>\n";
	$kml .= "</Style>\n";
	
	$kml .= "<Style id=\"whiteBall\">\n";
	$kml .= "<IconStyle>\n";
	$kml .= "<Icon>\n";
	$kml .=  "<href>http://iphylo.org/~rpage/phyloinformatics/images/whiteBall.png</href>\n";
	$kml .=  "</Icon>\n";
	$kml .=  "</IconStyle>\n";
	$kml .=  "<LineStyle>\n";
	$kml .=  "<width>2</width>\n";
	$kml .=  "</LineStyle>\n";
	$kml .=  "</Style>\n";
	
	$td->Draw(null);	
	$kml .= $td->kml;
	
	$kml .=  "<Folder>\n";
	$kml .=  "<name>Labels</name>\n";
	
	// labels
	$ni = new NodeIterator ($t->GetRoot());
	
	$q = $ni->Begin();
	while ($q != NULL)
	{	
		if ($q->IsLeaf ())
		{
			$kml .=  "<Placemark>\n";
			$kml .=  "<name>" .  $q->Getlabel() . "</name>\n";
			$kml .=  "<styleUrl>#whiteBall</styleUrl>\n";
			$kml .=  "<Point>\n";
			$kml .=  "<altitudeMode>absolute</altitudeMode>\n";
			$kml .=  "<extrude>1</extrude>\n";
			$kml .=  "<coordinates>\n";
			$kml .=  $q->GetAttribute('long') . "," . $q->GetAttribute('lat') . "," . $q->GetAttribute('altitude') . "\n";
			$kml .=  "</coordinates>\n";
			$kml .=  "</Point>\n";
			$kml .=  "</Placemark>\n";
		}		
		$q = $ni->Next();
	}
	$kml .=  "</Folder>\n";
	
	
    $kml .=  "</Document>\n";
    $kml .=  "</kml>\n";
    
    return $kml;

}

//--------------------------------------------------------------------------------------------------
function main()
{
	global $matching_path;
	
	$have_tree = false;
	$have_taxa = false;
	$have_table = false;
	$newick = '';
	
	if (isset($_POST['tree']))
	{
		$obj = parse_nexus(stripcslashes($_POST['tree']));
		$taxa = get_taxa_from_tree($obj);
		$newick = $obj->tree->newick;
		
		//echo $newick;
		
		//print_r($obj);
		
		$t = new Tree();
		$t->Parse($newick);
		
		$ni = new NodeIterator ($t->getRoot());
				
		$q = $ni->Begin();
		while ($q != NULL)
		{	
			if ($q->IsLeaf ())
			{
				if (isset($obj->translations->translate))
				{
					$q->SetLabel($obj->translations->translate[$q->GetLabel()]);
				}
			}
			$q = $ni->Next();
		}
		$newick = $t->WriteNewick();	
		
		//echo $newick;
		
		$have_tree = true;
	}
	
	
	if (isset($_POST['taxa']))
	{
		$have_taxa = true;
	}
	
	
	if (isset($_POST['table']))
	{
		$table = $_POST['table'];
		$have_table = true;
	}
	
	if (isset($_POST['newick']))
	{
		$newick = $_POST['newick'];
	}
	
	if ($have_tree || $have_taxa)
	{
		if ($have_table)
		{
			// get taxa
			$taxa = explode("\n", stripcslashes($_POST['taxa']));
			$n = count($taxa);
			for ($i=0;$i<$n;$i++)
			{
				$taxa[$i] = trim($taxa[$i]);
			}
			
			// get table data
			$table = stripcslashes($_POST['table']);
			
			// Interpret table automatically...
			
			// assume column one contains OTUs, and some other column(s) have lat and long
			
			
			$data = extract_table($table);
			
			//print_r($data);
			
			if (count($taxa) != count($data))
			{
				echo '<html>
	<head>
	<meta charset="utf-8" />
			<style type="text/css" title="text/css">
			body { font-family:sans-serif;padding:20px; }
			</style>
	<title>Create KML tree - Error</title>
	</head>
	<body>
	<a href=".">Back</a>
	<h1>Error</h1>
	<p>The number of taxa in the tree (' . count($taxa) . ') does not match the number in the table (' . count($data) . ')</p>
	</body>
	</html>';

			exit();				
			}
			
			$data_lookup_by_label = array();
			foreach ($data as $d)
			{
				$data_lookup_by_label[$d->label] = $d;
			}			
			
			// show matching...
			/*
			echo '			<form method="post" action=".">
				<table border="1">';
				
				echo '<tr><th>Taxa in tree</th><th>Taxa in table</th><th>Latitude</th><th>Longitude</th></tr>';
				
				$nrows = count($data);
				for ($i=0;$i < $nrows; $i++)
				{
					echo '<tr>';
					
					echo '<td>' . $taxa[$i] . '</td>';
					echo '<td>' . $data[$i]->label . '</td>';
					echo '<td>' . $data[$i]->latlong['latitude'] . '</td>';
					echo '<td>' . $data[$i]->latlong['longitude'] . '</td>';
					echo '</td></tr>';
				}					

								
				
			echo '</table>
			
				
				<input type="submit" value="Go"></input>
			</form>';
			*/
			
			
			// build graph for matching
			
			// Create GML file for graph linking taxon labels in tree and table
			
			// Taxon labels in table
			$b = array();
			foreach ($data as $row)
			{
				$b[] = $row->label;
			}			
			
			$filename = 'tmp/' . uniqid() . '.gml';		
			$gml = create_graph($taxa, $b);
			file_put_contents($filename, $gml);
			
			//echo $gml;
			
			// Compute maximum weight bipartite matching
			$command = $matching_path . 'matching ' . $filename;
			$output = array();
			exec ($command, $output);
			
			//echo $command;
			
			$json = join("", $output);
			
			//echo $json;
			
			$match = json_decode($json);
			
			if (0)
			{
				echo '<pre>';
				$n = count($taxa);
				foreach ($match->matching as $pair)
				{
					echo $taxa[$pair[0]]  . "|\t" . $b[$pair[1] - $n] . "\n";
				}
				echo '</pre>';
			}			
			
			// Mapping between tree labels and table labels
			$match_by_label = array();
			$n = count($taxa);
			foreach ($match->matching as $pair)
			{
				$match_by_label[$taxa[$pair[0]]] = $b[$pair[1] - $n];
			}
			


			// match and build KML file
			$t = new Tree();
			$t->Parse($newick);
			$t->BuildWeights($t->GetRoot());
			
			//echo $newick;
			
			$ni = new NodeIterator ($t->getRoot());
					
			$q = $ni->Begin();
			while ($q != NULL)
			{	
				if ($q->IsLeaf ())
				{
					$data = $data_lookup_by_label[$match_by_label[$q->GetLabel()]];
					
					$q->SetAttribute('lat', $data->latlong['latitude']);
					$q->SetAttribute('long', $data->latlong['longitude']);
				}
				$q = $ni->Next();
			}
			
			// KML...
			//echo '<pre>';
			//$t->Dump();
			//echo '</pre>';
			
			$kml = tree2kml($t);
			
			

			echo
			'<html>
			<head>
			<meta charset="utf-8" />
			<style type="text/css" title="text/css">
			body { font-family:sans-serif;padding:20px; }
			</style>
			<title>Create KML tree - Step 3</title>
   <script type="text/javascript" src="https://www.google.com/jsapi"> </script>
   <script type="text/javascript">
      var ge;
      google.load("earth", "1");

      function init() {
         google.earth.createInstance(\'map3d\', initCB, failureCB);
      }

      function initCB(instance) {
         ge = instance;
         ge.getWindow().setVisibility(true);
         
         var treeDoc = ge.parseKml(';
         
         $kml_lines = explode("\n", $kml);
         
         $k = join(" ", $kml_lines);
         echo "'" . $k . "'";
		 echo ');	
         ge.getFeatures().appendChild(treeDoc);
         
      }

      function failureCB(errorCode) {
      }

      google.setOnLoadCallback(init);
   </script>
			
			</head>
			<body>
			<a href=".">Home</a>
			<h1>Step 3: Match tree to table and create KML</h1>';
			
	

			// Display mapping
			echo '<h2>Tree and table matching</h2>';

			echo '<table border="1">';
				
				echo '<tr><th>Taxa in tree</th><th>Taxa in table</th><th>Latitude</th><th>Longitude</th></tr>';
				
				$n = count($taxa);
				foreach ($match->matching as $pair)
				{
					echo '<tr>';
					
					echo '<td>';
					echo $taxa[$pair[0]];
					echo '</td>';

					$data = $data_lookup_by_label[$match_by_label[$taxa[$pair[0]]]];
					echo '<td>' . $data->label . '</td>';
					echo '<td>' . $data->latlong['latitude'] . '</td>';
					echo '<td>' . $data->latlong['longitude'] . '</td>';
					
					echo '</tr>';
				}

			echo '</table>';
			
			
			echo '<h2>KML</h2>';
			echo '<textarea rows="30" cols="100">';
			echo $kml;
			echo '</textarea>';
			
			echo '<h2>Google Earth plugin</h2>';
			echo ' <div id="map3d" style="height: 400px; width: 600px;"></div>';
			
			
			echo 
			'</body>
			</html>';			
			
		}
		else
		{
			// We have the tree but no data yet
			
			echo 
			'<html>
			<head>
			<meta charset="utf-8" />
			<style type="text/css" title="text/css">
			body { font-family:sans-serif;padding:20px; }
			</style>
			<title>Create KML tree - Step 2</title>
			</head>
			<body>
			<a href=".">Home</a>
			<h1>Step 2: Add table</h1>
			<form method="post" action=".">
			
				<input name="newick" type="hidden" value="' . $newick . '">
				
				<table>
				<tr><th>Taxa in tree</th><th>Paste in table with taxa (in first column), and latitude and longitude.<br/>The first row of the table must contain column headings.</th></tr>
				<tr>
				<td>
				<textarea id="taxa" name="taxa" rows="30" cols="60" readonly="readonly">'			
				. join ("\n", $taxa) . '</textarea>
				</td>
				<td>
				<textarea  id="table" name="table" rows="30" cols="60"></textarea>
				</td>
				</tr>
				</table>
				
				<input type="submit" value="Next step"></input>
			</form>
			</body>
			</html>';
		}		
		
	}
	else
	{
		// Starting point, get tree
		echo 
	'<html>
	<head>
	<meta charset="utf-8" />
			<style type="text/css" title="text/css">
			body { font-family:sans-serif;padding:20px; }
			</style>
	<title>Create KML tree - Step 1</title>
	</head>
	<body>
	<h1>Step 1: Paste in a tree in NEXUS format</h1>
	<form method="post" action=".">
		<textarea id="tree" name="tree" rows="30" cols="60"></textarea>
		<br />
		<input type="submit" value="Next step"></input>
	</form>
	</body>
	</html>';
	}
}

main();

?>	